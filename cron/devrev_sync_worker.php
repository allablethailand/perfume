<?php

// ตั้งค่า error reporting และ logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// สร้าง log file ในตำแหน่งเดียวกับไฟล์นี้
$log_file = __DIR__ . '/devrev_sync_worker.log';
$error_log_file = __DIR__ . '/devrev_sync_worker_error.log';

// ฟังก์ชัน logging
function write_log($message, $is_error = false) {
    global $log_file, $error_log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";
    
    // เขียนลง log ปกติ
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // ถ้าเป็น error ให้เขียนลง error log ด้วย
    if ($is_error) {
        file_put_contents($error_log_file, $log_message, FILE_APPEND);
    }
    
    // แสดงผลทาง console ด้วย
    echo $log_message;
}

// เริ่มต้น worker
write_log("=== DevRev Sync Worker Started ===");
write_log("Script path: " . __FILE__);
write_log("Working directory: " . getcwd());
write_log("PHP version: " . PHP_VERSION);
write_log("User: " . get_current_user());

// ตรวจสอบไฟล์ที่ต้อง require
$required_files = [
    __DIR__ . '/../lib/connect.php',
    __DIR__ . '/../lib/devrev_manager.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        write_log("ERROR: Required file not found: {$file}", true);
        exit(1);
    }
    write_log("Found required file: {$file}");
}

try {
    require_once(__DIR__ . '/../lib/connect.php');
    write_log("✓ Loaded connect.php");
    
    require_once(__DIR__ . '/../lib/devrev_manager.php');
    write_log("✓ Loaded devrev_manager.php");
} catch (Exception $e) {
    write_log("ERROR loading required files: " . $e->getMessage(), true);
    exit(1);
}

global $conn;

// ตรวจสอบ database connection
if (!isset($conn) || !$conn) {
    write_log("ERROR: Database connection not available", true);
    exit(1);
}

write_log("✓ Database connection OK");

try {
    // ดึง pending tasks (จำกัด 10 tasks ต่อรอบ)
    $stmt = $conn->prepare("
        SELECT queue_id, conversation_id, user_id, sync_data, retry_count
        FROM devrev_sync_queue
        WHERE status = 'pending' AND retry_count < 3
        ORDER BY created_at ASC
        LIMIT 10
    ");
    
    if (!$stmt) {
        write_log("ERROR: Failed to prepare statement: " . $conn->error, true);
        exit(1);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_tasks = $result->num_rows;
    write_log("Found {$total_tasks} pending tasks");
    
    if ($total_tasks === 0) {
        write_log("No tasks to process. Exiting.");
        $stmt->close();
        $conn->close();
        write_log("=== DevRev Sync Worker Completed (No tasks) ===");
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
        
        write_log("\n--- Processing Queue #{$queue_id} (Conversation #{$conversation_id}) ---");
        
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
            
            // ดึงข้อมูล AI ใหม่จาก database
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
            while ($row = $personality_result->fetch_assoc()) {
                $user_personality[] = $row;
            }
            $personality_stmt->close();
            
            // ตรวจสอบและแก้ไข AI name
            if (empty($ai_companion['ai_name'])) {
                $ai_companion['ai_name'] = 'AI Assistant';
                write_log("⚠️  AI name was empty, using default: AI Assistant");
            }
            
            write_log("AI: {$ai_companion['ai_name']} (loaded fresh from database)");
            
            // สร้าง DevRevManager และ sync
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
                write_log("⚠️  Sync completed with errors: {$error_msg}", true);
                
                // ถ้ายังมีโอกาส retry
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
                    // เกิน retry limit แล้ว
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
                    write_log("❌ Failed permanently after 3 attempts", true);
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
                write_log("   AI Name: {$ai_companion['ai_name']}");
                write_log("   Chat Article: " . ($chat_synced ? $devrev_result['chat_article_id'] : 'N/A'));
                write_log("   Prompt Article: " . ($prompt_synced ? $devrev_result['prompt_article_id'] : 'N/A'));
                $processed++;
            }
            
        } catch (Exception $sync_error) {
            $error_msg = $sync_error->getMessage();
            write_log("❌ Exception: {$error_msg}", true);
            write_log("Stack trace: " . $sync_error->getTraceAsString(), true);
            
            // Retry logic
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
                write_log("❌ Failed permanently after 3 attempts", true);
                $failed++;
            }
        }
        
        // หน่วงเวลาเล็กน้อยเพื่อไม่ให้ทำงานหนักเกินไป
        sleep(1);
    }
    
    $stmt->close();
    
    write_log("\n=== Summary ===");
    write_log("Total tasks: {$total_tasks}");
    write_log("Processed successfully: {$processed}");
    write_log("Failed: {$failed}");
    write_log("Pending retry: " . ($total_tasks - $processed - $failed));
    
} catch (Exception $e) {
    write_log("❌ Worker Error: " . $e->getMessage(), true);
    write_log("Stack trace: " . $e->getTraceAsString(), true);
    $conn->close();
    exit(1);
}

$conn->close();
write_log("=== DevRev Sync Worker Completed ===\n");
exit(0);
?>