<?php
/**
 * Get Chat Data API (Guest Mode Supported)
 * 
 * GET: /app/actions/get_chat_data.php
 * 
 * Actions:
 * - list_conversations: ดึงรายการ conversations
 * - get_history: ดึงประวัติแชท
 * - delete_conversation: ลบ conversation
 * 
 * ✅ รองรับ guest mode: ใช้ user_companion_id หรือ ai_code
 */

require_once('../../lib/connect.php');
require_once('../../lib/jwt_helper.php');

global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : 'list_conversations';
$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$language = isset($_GET['lang']) ? $_GET['lang'] : 'th';

// ✅ รับ user_companion_id และ ai_code (สำหรับ guest mode)
$user_companion_id_input = isset($_GET['user_companion_id']) ? intval($_GET['user_companion_id']) : 0;
$ai_code_input = isset($_GET['ai_code']) ? strtoupper(trim($_GET['ai_code'])) : '';

// ========== ระบุตัวตน ==========
$user_id = null;
$user_companion_id = null;
$ai_id = null;
$is_guest_mode = false;

// ลอง JWT ก่อน
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    try {
        $decoded = verifyJWT($jwt);
        if ($decoded) {
            $user_id = requireAuth();
        }
    } catch (Exception $e) {
        // JWT ไม่ valid
    }
}

// ถ้าไม่มี JWT -> ลอง guest mode
if (!$user_id) {
    $is_guest_mode = true;
    
    // ✅ วิธีที่ 1: ใช้ user_companion_id โดยตรง
    if ($user_companion_id_input > 0) {
        $stmt = $conn->prepare("
            SELECT user_companion_id, user_id, ai_id
            FROM user_ai_companions
            WHERE user_companion_id = ? AND status = 1 AND del = 0
        ");
        $stmt->bind_param('i', $user_companion_id_input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $companion = $result->fetch_assoc();
            $user_companion_id = $companion['user_companion_id'];
            $user_id = $companion['user_id'];
            $ai_id = $companion['ai_id'];
        }
        $stmt->close();
    }
    
    // ✅ วิธีที่ 2: ใช้ ai_code (ถ้ายังไม่ได้ companion_id)
    if (!$user_companion_id && !empty($ai_code_input)) {
        // หา AI จาก ai_code
        $stmt = $conn->prepare("
            SELECT ai_id 
            FROM ai_companions 
            WHERE ai_code = ? AND status = 1 AND del = 0
        ");
        $stmt->bind_param('s', $ai_code_input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $ai_data = $result->fetch_assoc();
            $ai_id = $ai_data['ai_id'];
            
            // ✅ ลอง companion_id จาก session หรือสร้างใหม่
            if (isset($_SESSION['user_companion_id'])) {
                $user_companion_id = $_SESSION['user_companion_id'];
                
                // ตรวจสอบว่า companion นี้ match กับ ai_code หรือไม่
                $stmt2 = $conn->prepare("
                    SELECT uc.user_id, uc.ai_id
                    FROM user_ai_companions uc
                    WHERE uc.user_companion_id = ? AND uc.ai_id = ? AND uc.status = 1 AND uc.del = 0
                ");
                $stmt2->bind_param('ii', $user_companion_id, $ai_id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                
                if ($result2->num_rows > 0) {
                    $comp_data = $result2->fetch_assoc();
                    $user_id = $comp_data['user_id'];
                } else {
                    // companion ไม่ match -> reset
                    $user_companion_id = null;
                    unset($_SESSION['user_companion_id']);
                }
                $stmt2->close();
            }
            
            // ✅ ถ้ายังไม่มี companion -> หาหรือสร้างใหม่
            if (!$user_companion_id) {
                // จัดการ guest user
                if (!isset($_SESSION['guest_user_id'])) {
                    $guest_stmt = $conn->prepare("
                        INSERT INTO mb_user (first_name, email, password, login_method, verify, del) 
                        VALUES (?, ?, '', 'guest', 0, 0)
                    ");
                    $guest_username = 'guest_' . uniqid();
                    $guest_email = $guest_username . '@guest.local';
                    $guest_stmt->bind_param('ss', $guest_username, $guest_email);
                    $guest_stmt->execute();
                    $guest_user_id = $conn->insert_id;
                    $_SESSION['guest_user_id'] = $guest_user_id;
                    $guest_stmt->close();
                } else {
                    $guest_user_id = $_SESSION['guest_user_id'];
                }
                
                $user_id = $guest_user_id;
                
                // ✅ ตรวจสอบว่ามี companion อยู่แล้วหรือไม่
                $check_existing = $conn->prepare("
                    SELECT user_companion_id 
                    FROM user_ai_companions 
                    WHERE user_id = ? AND ai_id = ? AND status = 1 AND del = 0
                ");
                $check_existing->bind_param('ii', $user_id, $ai_id);
                $check_existing->execute();
                $existing_result = $check_existing->get_result();
                
                if ($existing_result->num_rows > 0) {
                    // ✅ ใช้ companion ที่มีอยู่
                    $existing_companion = $existing_result->fetch_assoc();
                    $user_companion_id = $existing_companion['user_companion_id'];
                } else {
                    // ✅ สร้าง companion ใหม่ (ใช้ INSERT IGNORE หรือ ON DUPLICATE KEY)
                    $companion_stmt = $conn->prepare("
                        INSERT INTO user_ai_companions (user_id, ai_id, preferred_language, status) 
                        VALUES (?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE 
                            user_companion_id = LAST_INSERT_ID(user_companion_id),
                            preferred_language = VALUES(preferred_language)
                    ");
                    $companion_stmt->bind_param('iis', $user_id, $ai_id, $language);
                    $companion_stmt->execute();
                    $user_companion_id = $conn->insert_id;
                    $companion_stmt->close();
                }
                $check_existing->close();
                
                $_SESSION['user_companion_id'] = $user_companion_id;
            }
        }
        $stmt->close();
    }
    
    // ✅ วิธีที่ 3: ลอง session อย่างเดียว
    if (!$user_companion_id && isset($_SESSION['user_companion_id'])) {
        $user_companion_id = $_SESSION['user_companion_id'];
        
        $stmt = $conn->prepare("
            SELECT user_id, ai_id 
            FROM user_ai_companions 
            WHERE user_companion_id = ? AND status = 1 AND del = 0
        ");
        $stmt->bind_param('i', $user_companion_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $comp_data = $result->fetch_assoc();
            $user_id = $comp_data['user_id'];
            $ai_id = $comp_data['ai_id'];
        }
        $stmt->close();
    }
    
    if (!$user_id && !$user_companion_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please provide user_companion_id, ai_code, or login',
            'require_login' => false,
            'guest_mode_available' => true,
            'debug' => [
                'user_companion_id_input' => $user_companion_id_input,
                'ai_code_input' => $ai_code_input,
                'has_session_companion' => isset($_SESSION['user_companion_id'])
            ]
        ]);
        exit;
    }
}

try {
    if ($action === 'list_conversations') {
        // ✅ ดึงรายการ conversations
        if ($is_guest_mode && $user_companion_id) {
            // Guest mode: ดึงตาม user_companion_id
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
                WHERE c.user_companion_id = ? AND c.is_active = 1
                ORDER BY c.updated_at DESC
            ");
            $stmt->bind_param('i', $user_companion_id);
        } else {
            // Login mode: ดึงตาม user_id
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
        }
        
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
            'guest_mode' => $is_guest_mode,
            'user_companion_id' => $user_companion_id,
            'conversations' => $conversations,
            'total' => count($conversations)
        ]);
        
    } elseif ($action === 'get_history') {
        // ✅ ดึงประวัติแชท
        if ($conversation_id === 0) {
            throw new Exception('Conversation ID is required');
        }
        
        // ตรวจสอบสิทธิ์
        if ($is_guest_mode && $user_companion_id) {
            $check_stmt = $conn->prepare("
                SELECT c.conversation_id 
                FROM ai_chat_conversations c
                WHERE c.conversation_id = ? AND c.user_companion_id = ? AND c.is_active = 1
            ");
            $check_stmt->bind_param('ii', $conversation_id, $user_companion_id);
        } else {
            $check_stmt = $conn->prepare("
                SELECT c.conversation_id 
                FROM ai_chat_conversations c
                INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
                WHERE c.conversation_id = ? AND uc.user_id = ? AND c.is_active = 1
            ");
            $check_stmt->bind_param('ii', $conversation_id, $user_id);
        }
        
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception('Conversation not found or access denied');
        }
        $check_stmt->close();
        
        // ดึงประวัติ
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
                'role' => $row['role'],
                'message' => $row['message_text'],
                'model_used' => $row['ai_model_used'],
                'timestamp' => $row['created_at']
            ];
        }
        $history_stmt->close();
        
        echo json_encode([
            'status' => 'success',
            'guest_mode' => $is_guest_mode,
            'conversation_id' => $conversation_id,
            'messages' => $messages,
            'total_messages' => count($messages)
        ]);
        
    } elseif ($action === 'delete_conversation') {
        // ✅ ลบ conversation
        if ($conversation_id === 0) {
            throw new Exception('Conversation ID is required');
        }
        
        if ($is_guest_mode && $user_companion_id) {
            $delete_stmt = $conn->prepare("
                UPDATE ai_chat_conversations 
                SET is_active = 0
                WHERE conversation_id = ? AND user_companion_id = ?
            ");
            $delete_stmt->bind_param('ii', $conversation_id, $user_companion_id);
        } else {
            $delete_stmt = $conn->prepare("
                UPDATE ai_chat_conversations c
                INNER JOIN user_ai_companions uc ON c.user_companion_id = uc.user_companion_id
                SET c.is_active = 0
                WHERE c.conversation_id = ? AND uc.user_id = ?
            ");
            $delete_stmt->bind_param('ii', $conversation_id, $user_id);
        }
        
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