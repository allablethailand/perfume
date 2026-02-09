<?php

// ป้องกันการเรียกใช้จาก web
// if (php_sapi_name() !== 'cli') {
//     die("This script must be run from command line\n");
// }

require_once(__DIR__ . '/../lib/connect.php');
require_once(__DIR__ . '/../lib/devrev_manager.php');

global $conn;

echo "[" . date('Y-m-d H:i:s') . "] DevRev Sync Worker Started\n";

try {
    // ดึง pending tasks (จำกัด 10 tasks ต่อรอบ)
    $stmt = $conn->prepare("
        SELECT queue_id, conversation_id, user_id, sync_data, retry_count
        FROM devrev_sync_queue
        WHERE status = 'pending' AND retry_count < 3
        ORDER BY created_at ASC
        LIMIT 10
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_tasks = $result->num_rows;
    echo "Found {$total_tasks} pending tasks\n";
    
    if ($total_tasks === 0) {
        echo "No tasks to process. Exiting.\n";
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
        
        echo "\n--- Processing Queue #{$queue_id} (Conversation #{$conversation_id}) ---\n";
        
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
            
            echo "User: {$display_name} ({$email})\n";
            
            // ดึงข้อมูลจาก sync_data
            $ai_companion = $sync_data['ai_companion'] ?? [];
            $user_personality = $sync_data['user_personality'] ?? [];
            $language = $sync_data['language'] ?? 'th';
            $user_companion_id = $sync_data['user_companion_id'] ?? 0;
            
            echo "AI: " . ($ai_companion['ai_name'] ?? 'Unknown') . ", Language: {$language}\n";
            
            // สร้าง DevRevManager และ sync
            $devrev = new DevRevManager($conn);
            $devrev_result = $devrev->processChat(
                $conversation_id,
                $user_id,
                $user_companion_id,
                $email,
                $display_name,
                $ai_companion,
                $user_personality,
                $language
            );
            
            // ตรวจสอบผลลัพธ์
            $has_errors = !empty($devrev_result['errors']);
            $chat_synced = !empty($devrev_result['chat_article_id']);
            $prompt_synced = !empty($devrev_result['prompt_article_id']);
            
            if ($has_errors) {
                $error_msg = implode('; ', $devrev_result['errors']);
                echo "⚠️  Sync completed with errors: {$error_msg}\n";
                
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
                    echo "Scheduled for retry (attempt " . ($new_retry + 1) . "/3)\n";
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
                    echo "❌ Failed permanently after 3 attempts\n";
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
                
                echo "✅ Sync completed successfully\n";
                echo "   Chat Article: " . ($chat_synced ? $devrev_result['chat_article_id'] : 'N/A') . "\n";
                echo "   Prompt Article: " . ($prompt_synced ? $devrev_result['prompt_article_id'] : 'N/A') . "\n";
                $processed++;
            }
            
        } catch (Exception $sync_error) {
            $error_msg = $sync_error->getMessage();
            echo "❌ Exception: {$error_msg}\n";
            
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
                echo "Scheduled for retry (attempt " . ($new_retry + 1) . "/3)\n";
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
                echo "❌ Failed permanently after 3 attempts\n";
                $failed++;
            }
        }
        
        // หน่วงเวลาเล็กน้อยเพื่อไม่ให้ทำงานหนักเกินไป
        sleep(1);
    }
    
    $stmt->close();
    
    echo "\n=== Summary ===\n";
    echo "Total tasks: {$total_tasks}\n";
    echo "Processed successfully: {$processed}\n";
    echo "Failed: {$failed}\n";
    echo "Pending retry: " . ($total_tasks - $processed - $failed) . "\n";
    
} catch (Exception $e) {
    echo "❌ Worker Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
echo "\n[" . date('Y-m-d H:i:s') . "] DevRev Sync Worker Completed\n";
exit(0);
?>