<?php

/**
 * DevRev Sync Worker with Enhanced Debug Logging
 * บันทึก Log ลงไฟล์ cron_debug.log ในโฟลเดอร์เดียวกัน
 */

// 1. ตั้งค่าการบันทึก Error ของ PHP ให้ลงไฟล์ log ด้วย
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$debug_log_file = __DIR__ . '/../cron/cron_debug.log';
ini_set('error_log', $debug_log_file);

// ฟังก์ชันช่วยบันทึก Log ทั้งลงหน้าจอ (echo) และลงไฟล์
function write_log($message) {
    global $debug_log_file;
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] $message\n";
    echo $formatted_message; // ออกหน้าจอ/Console
    file_put_contents($debug_log_file, $formatted_message, FILE_APPEND); // ลงไฟล์
}

write_log("=========================================");
write_log("DevRev Sync Worker Started (Run Mode: " . php_sapi_name() . ")");

// ป้องกันการเรียกใช้จาก web (เปิดไว้เพื่อความปลอดภัย แต่ถ้าจะเทสผ่าน browser ให้ comment ออก)
/*
if (php_sapi_name() !== 'cli') {
    write_log("WARNING: Script called via Web Browser");
}
*/

try {
    $connect_path = __DIR__ . '/../lib/connect.php';
    $manager_path = __DIR__ . '/../lib/devrev_manager.php';

    if (!file_exists($connect_path)) throw new Exception("File not found: $connect_path");
    if (!file_exists($manager_path)) throw new Exception("File not found: $manager_path");

    require_once($connect_path);
    require_once($manager_path);

    global $conn;

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // ดึง pending tasks (จำกัด 10 tasks ต่อรอบ)
    $stmt = $conn->prepare("
        SELECT queue_id, conversation_id, user_id, sync_data, retry_count
        FROM devrev_sync_queue
        WHERE status = 'pending' AND retry_count < 3
        ORDER BY created_at ASC
        LIMIT 10
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_tasks = $result->num_rows;
    write_log("Found {$total_tasks} pending tasks");
    
    if ($total_tasks === 0) {
        write_log("No tasks to process. Exiting.");
        exit(0);
    }
    
    $processed = 0;
    $failed = 0;
    
    while ($row = $result->fetch_assoc()) {
        $queue_id = $row['queue_id'];
        $conversation_id = $row['conversation_id'];
        $user_id = $row['user_id'];
        $sync_data = json_decode($row['sync_data'], true);
        $retry_count = $row['retry_count'];
        
        write_log("--- Processing Queue #{$queue_id} (Conversation #{$conversation_id}) ---");
        
        // Update status เป็น processing
        $update_stmt = $conn->prepare("
            UPDATE devrev_sync_queue
            SET status = 'processing', updated_at = NOW()
            WHERE queue_id = ?
        ");
        $update_stmt->bind_param('i', $queue_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        try {
            // ดึงข้อมูล user
            $user_stmt = $conn->prepare("
                SELECT user_id, first_name, last_name, email
                FROM mb_user
                WHERE user_id = ?
            ");
            $user_stmt->bind_param('i', $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user_data = $user_result->fetch_assoc();
            $user_stmt->close();
            
            if (!$user_data) {
                throw new Exception("User not found: {$user_id}");
            }
            
            $email = $user_data['email'] ?: "user{$user_id}@example.com";
            $display_name = trim($user_data['first_name'] . ' ' . $user_data['last_name']) ?: "User {$user_id}";
            
            write_log("User: {$display_name} ({$email})");
            
            // ✅ ดึงข้อมูล AI ใหม่จาก database
            $user_companion_id = $sync_data['user_companion_id'] ?? 0;
            
            if (!$user_companion_id) {
                throw new Exception("Missing user_companion_id in sync_data");
            }
            
            // ดึง AI companion ใหม่จาก database
            $ai_stmt = $conn->prepare("
                SELECT 
                    uc.ai_id,
                    COALESCE(ac.ai_name_en, ac.ai_name_th, ac.ai_name_cn, ac.ai_name_jp, ac.ai_name_kr) as ai_name,
                    COALESCE(ac.system_prompt_en, ac.system_prompt_th, ac.system_prompt_cn, ac.system_prompt_jp, ac.system_prompt_kr) as system_prompt,
                    COALESCE(ac.perfume_knowledge_en, ac.perfume_knowledge_th, ac.perfume_knowledge_cn, ac.perfume_knowledge_jp, ac.perfume_knowledge_kr) as perfume_knowledge,
                    COALESCE(ac.style_suggestions_en, ac.style_suggestions_th, ac.style_suggestions_cn, ac.style_suggestions_jp, ac.style_suggestions_kr) as style_suggestions
                FROM user_ai_companions uc
                INNER JOIN ai_companions ac ON uc.ai_id = ac.ai_id
                WHERE uc.user_companion_id = ? AND uc.status = 1 AND ac.status = 1
            ");
            $ai_stmt->bind_param('i', $user_companion_id);
            $ai_stmt->execute();
            $ai_result = $ai_stmt->get_result();
            
            if ($ai_result->num_rows === 0) {
                $ai_stmt->close();
                throw new Exception("AI companion not found for user_companion_id: {$user_companion_id}");
            }
            
            $ai_companion = $ai_result->fetch_assoc();
            $ai_stmt->close();
            
            // ดึง personality ใหม่จาก database
            $personality_stmt = $conn->prepare("
                SELECT 
                    COALESCE(q.question_text_en, q.question_text_th, q.question_text_cn, q.question_text_jp, q.question_text_kr) as question,
                    a.text_answer,
                    a.scale_value,
                    COALESCE(c.choice_text_en, c.choice_text_th, c.choice_text_cn, c.choice_text_jp, c.choice_text_kr) as choice_text,
                    q.question_order
                FROM user_personality_answers a
                INNER JOIN ai_personality_questions q ON a.question_id = q.question_id
                LEFT JOIN ai_question_choices c ON a.choice_id = c.choice_id
                WHERE a.user_companion_id = ?
                ORDER BY q.question_order
            ");
            $personality_stmt->bind_param('i', $user_companion_id);
            $personality_stmt->execute();
            $personality_result = $personality_stmt->get_result();
            
            $user_personality = [];
            while ($p_row = $personality_result->fetch_assoc()) {
                $user_personality[] = $p_row;
            }
            $personality_stmt->close();
            
            // ✅ ตรวจสอบและแก้ไข AI name
            if (empty($ai_companion['ai_name'])) {
                $ai_companion['ai_name'] = 'AI Assistant';
                write_log("⚠️ AI name was empty, using default: AI Assistant");
            }
            
            write_log("AI: {$ai_companion['ai_name']} (loaded fresh from database)");
            
            // ✅ สร้าง DevRevManager และ sync
            $devrev = new DevRevManager($conn);
            $devrev_result = $devrev->processChat(
                $conversation_id,
                $user_id,
                $user_companion_id,
                $email,
                $display_name,
                $ai_companion,
                $user_personality
            );
            
            // ตรวจสอบผลลัพธ์
            $has_errors = !empty($devrev_result['errors']);
            $chat_synced = !empty($devrev_result['chat_article_id']);
            $prompt_synced = !empty($devrev_result['prompt_article_id']);
            
            if ($has_errors) {
                $error_msg = implode('; ', $devrev_result['errors']);
                write_log("⚠️ Sync completed with errors: {$error_msg}");
                
                if ($retry_count < 2) {
                    $new_retry = $retry_count + 1;
                    $update_stmt = $conn->prepare("
                        UPDATE devrev_sync_queue
                        SET status = 'pending', 
                            retry_count = ?,
                            last_error = ?,
                            updated_at = NOW()
                        WHERE queue_id = ?
                    ");
                    $update_stmt->bind_param('isi', $new_retry, $error_msg, $queue_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    write_log("Scheduled for retry (attempt " . ($new_retry + 1) . "/3)");
                } else {
                    $update_stmt = $conn->prepare("
                        UPDATE devrev_sync_queue
                        SET status = 'failed',
                            last_error = ?,
                            processed_at = NOW(),
                            updated_at = NOW()
                        WHERE queue_id = ?
                    ");
                    $update_stmt->bind_param('si', $error_msg, $queue_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    write_log("❌ Failed permanently after 3 attempts");
                    $failed++;
                }
            } else {
                // สำเร็จ
                $update_stmt = $conn->prepare("
                    UPDATE devrev_sync_queue
                    SET status = 'completed',
                        processed_at = NOW(),
                        updated_at = NOW()
                    WHERE queue_id = ?
                ");
                $update_stmt->bind_param('i', $queue_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                write_log("✅ Sync completed successfully");
                write_log("   Chat Article: " . ($chat_synced ? $devrev_result['chat_article_id'] : 'N/A'));
                write_log("   Prompt Article: " . ($prompt_synced ? $devrev_result['prompt_article_id'] : 'N/A'));
                $processed++;
            }
            
        } catch (Exception $sync_error) {
            $error_msg = $sync_error->getMessage();
            write_log("❌ Inner Exception: {$error_msg}");
            
            if ($retry_count < 2) {
                $new_retry = $retry_count + 1;
                $update_stmt = $conn->prepare("
                    UPDATE devrev_sync_queue
                    SET status = 'pending',
                        retry_count = ?,
                        last_error = ?,
                        updated_at = NOW()
                    WHERE queue_id = ?
                ");
                $update_stmt->bind_param('isi', $new_retry, $error_msg, $queue_id);
                $update_stmt->execute();
                $update_stmt->close();
                write_log("Scheduled for retry (attempt " . ($new_retry + 1) . "/3)");
            } else {
                $update_stmt = $conn->prepare("
                    UPDATE devrev_sync_queue
                    SET status = 'failed',
                        last_error = ?,
                        processed_at = NOW(),
                        updated_at = NOW()
                    WHERE queue_id = ?
                ");
                $update_stmt->bind_param('si', $error_msg, $queue_id);
                $update_stmt->execute();
                $update_stmt->close();
                write_log("❌ Failed permanently");
                $failed++;
            }
        }
        
        sleep(1);
    }
    
    $stmt->close();
    
    write_log("\n=== Summary ===");
    write_log("Total tasks: {$total_tasks}");
    write_log("Processed: {$processed}");
    write_log("Failed: {$failed}");
    write_log("Pending retry: " . ($total_tasks - $processed - $failed));
    
} catch (Exception $e) {
    write_log("❌ CRITICAL ERROR: " . $e->getMessage());
    exit(1);
}

if (isset($conn)) $conn->close();
write_log("DevRev Sync Worker Completed Successfully");
write_log("=========================================\n");
exit(0);
?>