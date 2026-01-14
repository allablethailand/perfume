<?php
/**
 * JWT Helper - Compatible with existing check_login.php and protected.php
 * 
 * ✅ ใช้ Secret Key จาก .env
 * ✅ รองรับโครงสร้าง payload ที่มี "data" object
 * ✅ เข้ากันได้กับระบบเดิมทั้งหมด
 */

require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// โหลด .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// JWT Configuration - ใช้จาก .env
define('JWT_SECRET_KEY', $_ENV['JWT_SECRET_KEY']);
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 86400); // 24 hours (60 * 60 * 24)

/**
 * Generate JWT Token
 * 
 * @param int $user_id
 * @param int $role_id
 * @param string $first_name
 * @param string $last_name
 * @param string $email
 * @param string $phone_number
 * @return string JWT token
 */
function generateJWT($user_id, $role_id, $first_name, $last_name, $email, $phone_number = '') {
    $issued_at = time();
    $expiration = $issued_at + JWT_EXPIRATION;
    
    // โครงสร้างเดียวกับ check_login.php
    $payload = array(
        "iss" => "",
        "iat" => $issued_at,
        "exp" => $expiration,
        "data" => array(
            "user_id" => $user_id,
            "role_id" => $role_id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $email,
            "phone_number" => $phone_number
        )
    );
    
    return JWT::encode($payload, JWT_SECRET_KEY, JWT_ALGORITHM);
}

/**
 * Verify and Decode JWT Token
 * 
 * @param string $jwt
 * @return object|false Decoded token or false if invalid
 */
function verifyJWT($jwt) {
    try {
        if (empty($jwt)) {
            error_log("JWT: Empty token provided");
            return false;
        }
        
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, JWT_ALGORITHM));
        
        // ตรวจสอบ expiration
        if (time() > $decoded->exp) {
            error_log("JWT: Token expired at " . date('Y-m-d H:i:s', $decoded->exp));
            return false;
        }
        
        // ตรวจสอบว่ามี data object หรือไม่
        if (!isset($decoded->data)) {
            error_log("JWT: Invalid payload structure (missing 'data' object)");
            return false;
        }
        
        // ตรวจสอบว่ามี user_id หรือไม่
        if (!isset($decoded->data->user_id)) {
            error_log("JWT: Missing user_id in payload");
            return false;
        }
        
        return $decoded;
        
    } catch (Exception $e) {
        error_log("JWT Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Alias for verifyJWT - for backward compatibility
 */
function validateJWT($jwt) {
    return verifyJWT($jwt);
}

/**
 * Get User ID from JWT Token
 * 
 * @param string $jwt
 * @return int|false User ID or false if invalid
 */
function getUserIdFromJWT($jwt) {
    if (empty($jwt)) {
        return false;
    }
    
    $decoded = verifyJWT($jwt);
    if ($decoded && isset($decoded->data->user_id)) {
        return intval($decoded->data->user_id);
    }
    return false;
}

/**
 * Get User Data from JWT Token
 * 
 * @param string $jwt
 * @return array|false User data or false if invalid
 */
function getUserDataFromJWT($jwt) {
    if (empty($jwt)) {
        return false;
    }
    
    $decoded = verifyJWT($jwt);
    if ($decoded && isset($decoded->data)) {
        return array(
            'user_id' => $decoded->data->user_id ?? null,
            'role_id' => $decoded->data->role_id ?? null,
            'first_name' => $decoded->data->first_name ?? null,
            'last_name' => $decoded->data->last_name ?? null,
            'email' => $decoded->data->email ?? null,
            'phone_number' => $decoded->data->phone_number ?? null
        );
    }
    return false;
}

/**
 * Check if JWT is valid and not expired
 * 
 * @param string $jwt
 * @return bool
 */
function isJWTValid($jwt) {
    $decoded = verifyJWT($jwt);
    return $decoded !== false;
}

/**
 * Refresh JWT Token (generate new token with extended expiration)
 * 
 * @param string $jwt
 * @return string|false New JWT token or false if invalid
 */
function refreshJWT($jwt) {
    $decoded = verifyJWT($jwt);
    if ($decoded && isset($decoded->data)) {
        return generateJWT(
            $decoded->data->user_id,
            $decoded->data->role_id,
            $decoded->data->first_name,
            $decoded->data->last_name,
            $decoded->data->email,
            $decoded->data->phone_number ?? ''
        );
    }
    return false;
}

/**
 * Extract JWT from Authorization Header
 * 
 * @return string|null JWT token or null if not found
 */
function getJWTFromHeader() {
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        
        // Remove "Bearer " prefix if exists
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return trim($matches[1]);
        }
        
        return trim($auth_header);
    }
    
    return null;
}

/**
 * Verify JWT from Authorization Header and return decoded data
 * 
 * @return object|false Decoded token or false if invalid
 */
function verifyJWTFromHeader() {
    $jwt = getJWTFromHeader();
    
    if ($jwt) {
        return verifyJWT($jwt);
    }
    
    return false;
}

/**
 * ⭐ Helper function สำหรับใช้ใน API
 * ตรวจสอบ JWT และคืนค่า user_id พร้อมข้อมูลอื่นๆ
 * 
 * @return array ['success' => bool, 'user_id' => int|null, 'decoded' => object|null, 'message' => string]
 */
function authenticateRequest() {
    $jwt = getJWTFromHeader();
    
    if (!$jwt) {
        return [
            'success' => false,
            'user_id' => null,
            'decoded' => null,
            'message' => 'No token provided'
        ];
    }
    
    $decoded = verifyJWT($jwt);
    
    if (!$decoded) {
        return [
            'success' => false,
            'user_id' => null,
            'decoded' => null,
            'message' => 'Invalid or expired token'
        ];
    }
    
    return [
        'success' => true,
        'user_id' => $decoded->data->user_id,
        'role_id' => $decoded->data->role_id ?? null,
        'email' => $decoded->data->email ?? null,
        'decoded' => $decoded,
        'message' => 'Authenticated'
    ];
}

/**
 * ⭐ ตรวจสอบ JWT และส่ง JSON response ถ้า invalid (สะดวกสำหรับ API)
 * ถ้า valid จะคืนค่า user_id
 * ถ้า invalid จะ echo json และ exit
 * 
 * @return int User ID
 */
function requireAuth() {
    $auth = authenticateRequest();
    
    if (!$auth['success']) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $auth['message']
        ]);
        exit;
    }
    
    return $auth['user_id'];
}

?>