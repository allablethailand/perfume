<?php
/**
 * Get Chat Data API (Guest Mode Supported) - FIXED VERSION
 * 
 * ✅ แก้ไขปัญหา: ไม่สร้าง user_id และ user_companion_id ใหม่ถ้ามีอยู่แล้ว
 * ✅ ตรวจสอบข้อมูลที่มีอยู่ก่อนสร้างใหม่
 * ✅ ใช้ user_companion_id เป็นหลักในการระบุตัวตน
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

// รับ user_companion_id และ ai_code
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
    
    // ========================================
    // ✅ FIX 1: ลำดับความสำคัญในการหา companion
    // ========================================
    
    // Priority 1: ใช้ user_companion_id ที่ส่งมา (ถ้ามี)
    if ($user_companion_id_input > 0) {
        error_log("🔍 Searching by user_companion_id: $user_companion_id_input");
        
        $stmt = $conn->prepare("
            SELECT user_companion_id, user_id, ai_id, preferred_language
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
            $language = !empty($companion['preferred_language']) ? $companion['preferred_language'] : 'th';
            
            error_log("✅ Found existing companion: user_id=$user_id, companion_id=$user_companion_id");
        } else {
            error_log("⚠️ Companion $user_companion_id_input not found");
        }
        $stmt->close();
    }
    
    // Priority 2: ลอง session (ถ้ายังไม่ได้ companion)
    if (!$user_companion_id && isset($_SESSION['user_companion_id'])) {
        $session_companion_id = intval($_SESSION['user_companion_id']);
        error_log("🔍 Searching by session companion_id: $session_companion_id");
        
        $stmt = $conn->prepare("
            SELECT user_companion_id, user_id, ai_id, preferred_language
            FROM user_ai_companions
            WHERE user_companion_id = ? AND status = 1 AND del = 0
        ");
        $stmt->bind_param('i', $session_companion_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $companion = $result->fetch_assoc();
            $user_companion_id = $companion['user_companion_id'];
            $user_id = $companion['user_id'];
            $ai_id = $companion['ai_id'];
            $language = !empty($companion['preferred_language']) ? $companion['preferred_language'] : 'th';
            
            error_log("✅ Found companion from session: user_id=$user_id, companion_id=$user_companion_id");
        } else {
            error_log("⚠️ Session companion $session_companion_id not found, clearing session");
            unset($_SESSION['user_companion_id']);
        }
        $stmt->close();
    }
    
    // Priority 3: ใช้ ai_code (สร้างใหม่เฉพาะถ้าจำเป็น)
    if (!$user_companion_id && !empty($ai_code_input)) {
        error_log("🔍 Searching by ai_code: $ai_code_input");
        
        // ========================================
        // ✅ FIX 2: หา AI ก่อน
        // ========================================
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
            error_log("✅ Found AI: ai_id=$ai_id");
            
            // ========================================
            // ✅ FIX 3: ลองหา companion ที่มีอยู่แล้วก่อน
            // ========================================
            
            // 3.1 หาจาก session user_id (ถ้ามี)
            if (isset($_SESSION['guest_user_id'])) {
                $guest_user_id = intval($_SESSION['guest_user_id']);
                error_log("🔍 Checking existing companion for guest_user_id: $guest_user_id, ai_id: $ai_id");
                
                $check_stmt = $conn->prepare("
                    SELECT user_companion_id, preferred_language
                    FROM user_ai_companions
                    WHERE user_id = ? AND ai_id = ? AND status = 1 AND del = 0
                    LIMIT 1
                ");
                $check_stmt->bind_param('ii', $guest_user_id, $ai_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // ✅ พบ companion ที่มีอยู่แล้ว - ใช้ของเก่า!
                    $existing_companion = $check_result->fetch_assoc();
                    $user_companion_id = $existing_companion['user_companion_id'];
                    $user_id = $guest_user_id;
                    $language = !empty($existing_companion['preferred_language']) 
                        ? $existing_companion['preferred_language'] 
                        : 'th';
                    
                    // บันทึกลง session
                    $_SESSION['user_companion_id'] = $user_companion_id;
                    
                    error_log("✅✅ Found EXISTING companion! user_id=$user_id, companion_id=$user_companion_id - NO NEW CREATION!");
                } else {
                    error_log("ℹ️ No companion found for this user+ai combo");
                }
                $check_stmt->close();
            }
            
            // ========================================
            // ✅ FIX 4: สร้างใหม่เฉพาะเมื่อไม่มีจริงๆ
            // ========================================
            if (!$user_companion_id) {
                error_log("📝 Creating NEW companion (not found in existing data)");
                
                // 4.1 จัดการ user (ใช้ของเก่าถ้ามี, สร้างใหม่ถ้าไม่มี)
                if (!isset($_SESSION['guest_user_id'])) {
                    error_log("📝 Creating new guest user");
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
                    error_log("✅ Created new guest user: $guest_user_id");
                } else {
                    $guest_user_id = $_SESSION['guest_user_id'];
                    error_log("✅ Using existing guest user: $guest_user_id");
                }
                
                $user_id = $guest_user_id;
                
                // 4.2 สร้าง companion ใหม่
                error_log("📝 Creating new companion for user_id=$user_id, ai_id=$ai_id");
                $companion_stmt = $conn->prepare("
                    INSERT INTO user_ai_companions (user_id, ai_id, preferred_language, status, del) 
                    VALUES (?, ?, ?, 1, 0)
                ");
                $companion_stmt->bind_param('iis', $user_id, $ai_id, $language);
                $companion_stmt->execute();
                $user_companion_id = $conn->insert_id;
                $companion_stmt->close();
                
                $_SESSION['user_companion_id'] = $user_companion_id;
                error_log("✅ Created NEW companion: $user_companion_id");
            }
        } else {
            error_log("❌ AI not found: $ai_code_input");
        }
        $stmt->close();
    }
    
    // ========================================
    // ตรวจสอบว่าได้ข้อมูลครบหรือไม่
    // ========================================
    if (!$user_id && !$user_companion_id) {
        error_log("❌ Failed to identify user - no valid authentication method");
        echo json_encode([
            'status' => 'error',
            'message' => 'Please provide user_companion_id, ai_code, or login',
            'require_login' => false,
            'guest_mode_available' => true,
            'debug' => [
                'user_companion_id_input' => $user_companion_id_input,
                'ai_code_input' => $ai_code_input,
                'has_session_companion' => isset($_SESSION['user_companion_id']),
                'has_session_guest_user' => isset($_SESSION['guest_user_id'])
            ]
        ]);
        exit;
    }
}

// ========================================
// ดำเนินการตาม action
// ========================================
try {
    if ($action === 'list_conversations') {
        // ดึงรายการ conversations
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
            // Login mode: หา user_companion_id ก่อน (ถ้ายังไม่มี)
            if (!$user_companion_id) {
                $comp_stmt = $conn->prepare("
                    SELECT user_companion_id, ai_id, preferred_language
                    FROM user_ai_companions 
                    WHERE user_id = ? AND status = 1 AND del = 0
                    ORDER BY last_active_at DESC
                    LIMIT 1
                ");
                $comp_stmt->bind_param('i', $user_id);
                $comp_stmt->execute();
                $comp_result = $comp_stmt->get_result();
                
                if ($comp_result->num_rows > 0) {
                    $comp_data = $comp_result->fetch_assoc();
                    $user_companion_id = $comp_data['user_companion_id'];
                    $ai_id = $comp_data['ai_id'];
                    $language = !empty($comp_data['preferred_language']) 
                        ? $comp_data['preferred_language'] 
                        : 'th';
                }
                $comp_stmt->close();
            }
            
            // ดึงตาม user_id
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
        
        error_log("✅ Returning conversations for companion_id: $user_companion_id");
        
        echo json_encode([
            'status' => 'success',
            'guest_mode' => $is_guest_mode,
            'user_companion_id' => $user_companion_id,
            'user_id' => $user_id,
            'conversations' => $conversations,
            'total' => count($conversations)
        ]);
        
    } elseif ($action === 'get_history') {
        // ดึงประวัติแชท
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
        // ลบ conversation
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