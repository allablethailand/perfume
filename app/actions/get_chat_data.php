<?php
/**
 * Get Chat Conversations & History API
 * 
 * GET: /app/actions/get_chat_conversations.php - ดึงรายการ conversations
 * GET: /app/actions/get_chat_history.php?conversation_id=X - ดึงประวัติแชท
 */

require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// ตรวจสอบ JWT
$headers = getallheaders();
$jwt = null;
$user_id = null;

if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = verifyJWT($jwt);
    if ($decoded) {
        $user_id = requireAuth();
    }
}

if (!$user_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login first',
        'require_login' => true
    ]);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list_conversations';
$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$language = isset($_GET['lang']) ? $_GET['lang'] : 'th';

try {
    if ($action === 'list_conversations') {
        // ✅ ดึงรายการ conversations ทั้งหมด
        $stmt = $conn->prepare("
            SELECT 
                c.conversation_id,
                c.conversation_title,
                c.language_used,
                c.message_count,
                c.created_at,
                c.updated_at,
                ai.ai_name_{$language} as ai_name,
                ai.ai_avatar_url,
                (SELECT message_text 
                 FROM ai_chat_history 
                 WHERE conversation_id = c.conversation_id 
                 ORDER BY created_at DESC 
                 LIMIT 1) as last_message
            FROM ai_chat_conversations c
            INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
            INNER JOIN ai_companions ai ON uc.ai_id = ai.ai_id
            WHERE uc.user_id = ? AND c.is_active = 1
            ORDER BY c.updated_at DESC
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conversations = [];
        while ($row = $result->fetch_assoc()) {
            $conversations[] = [
                'conversation_id' => $row['conversation_id'],
                'title' => $row['conversation_title'],
                'ai_name' => $row['ai_name'],
                'ai_avatar' => $row['ai_avatar_url'],
                'last_message' => $row['last_message'] ? mb_substr($row['last_message'], 0, 100) : '',
                'message_count' => $row['message_count'],
                'language' => $row['language_used'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
        $stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'conversations' => $conversations,
            'total' => count($conversations)
        ]);
        
    } elseif ($action === 'get_history') {
        // ✅ ดึงประวัติแชทของ conversation นั้น
        if ($conversation_id === 0) {
            throw new Exception('Conversation ID is required');
        }
        
        // ตรวจสอบว่า conversation นี้เป็นของ user นี้หรือไม่
        $check_stmt = $conn->prepare("
            SELECT c.conversation_id 
            FROM ai_chat_conversations c
            INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
            WHERE c.conversation_id = ? AND uc.user_id = ? AND c.is_active = 1
        ");
        $check_stmt->bind_param('ii', $conversation_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception('Conversation not found or access denied');
        }
        $check_stmt->close();
        
        // ดึงประวัติการแชท
        $history_stmt = $conn->prepare("
            SELECT 
                chat_id,
                role,
                message_text,
                ai_model_used,
                created_at
            FROM ai_chat_history 
            WHERE conversation_id = ? 
            ORDER BY created_at ASC
        ");
        $history_stmt->bind_param('i', $conversation_id);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        
        $messages = [];
        while ($row = $history_result->fetch_assoc()) {
            $messages[] = [
                'chat_id' => $row['chat_id'],
                'role' => $row['role'], // 'user' or 'assistant'
                'message' => $row['message_text'],
                'model_used' => $row['ai_model_used'],
                'timestamp' => $row['created_at']
            ];
        }
        $history_stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'conversation_id' => $conversation_id,
            'messages' => $messages,
            'total_messages' => count($messages)
        ]);
        
    } elseif ($action === 'delete_conversation') {
        // ✅ ลบ conversation (soft delete)
        if ($conversation_id === 0) {
            throw new Exception('Conversation ID is required');
        }
        
        $delete_stmt = $conn->prepare("
            UPDATE ai_chat_conversations c
            INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
            SET c.is_active = 0
            WHERE c.conversation_id = ? AND uc.user_id = ?
        ");
        $delete_stmt->bind_param('ii', $conversation_id, $user_id);
        $delete_stmt->execute();
        
        if ($delete_stmt->affected_rows === 0) {
            throw new Exception('Conversation not found or already deleted');
        }
        $delete_stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Conversation deleted successfully'
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>