<?php
/**
 * Debug Script สำหรับ lib/devrev_manager.php
 * วางไฟล์นี้ใน lib/ แล้วรัน
 */

require_once('connect.php');
global $conn;

echo "=== DevRev Debug from lib/ folder ===\n\n";

// 1. ทดสอบ path ต่างๆ
echo "--- 1. ทดสอบ __DIR__ ---\n";
echo "__DIR__ = " . __DIR__ . "\n";
echo "dirname(__DIR__) = " . dirname(__DIR__) . "\n\n";

// 2. ทดสอบหา .env
echo "--- 2. ทดสอบหา .env ---\n";

$paths_to_try = [
    __DIR__ . '/../.env',           // lib/../.env
    dirname(__DIR__) . '/.env',     // perfume/.env
    __DIR__ . '/../../.env',        // กรณีมี subfolder
];

$env_found = null;
foreach ($paths_to_try as $i => $path) {
    $real_path = realpath($path);
    echo "Path " . ($i + 1) . ": $path\n";
    echo "  Realpath: " . ($real_path ?: 'NOT RESOLVED') . "\n";
    echo "  Exists: " . (file_exists($path) ? '✅ YES' : '❌ NO') . "\n";
    
    if (file_exists($path)) {
        $env_found = $path;
        echo "  ✅✅ FOUND!\n";
        break;
    }
    echo "\n";
}

if (!$env_found) {
    echo "❌ .env not found in any location\n";
    exit;
}

// 3. อ่าน .env
echo "\n--- 3. อ่าน .env ---\n";
echo "Using: $env_found\n\n";

$jwt_secret = null;
$devrev_token = null;

foreach (file($env_found, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') !== false) {
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, "\"'");
        
        if ($key === 'JWT_SECRET_KEY') {
            $jwt_secret = $value;
            echo "✅ JWT_SECRET_KEY found (length: " . strlen($jwt_secret) . ")\n";
        }
        
        if ($key === 'DEVREV_API_TOKEN') {
            $devrev_token = $value;
            echo "✅ DEVREV_API_TOKEN found (length: " . strlen($devrev_token) . ")\n";
        }
    }
}

if (!$jwt_secret) {
    echo "❌ JWT_SECRET_KEY not found\n";
    exit;
}

// 4. ดึงและ decrypt token จาก database
echo "\n--- 4. Database & Decrypt ---\n";

$stmt = $conn->prepare("SELECT api_key FROM ai_models WHERE provider='devrev' AND is_active=1 LIMIT 1");
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo "❌ No DevRev record in database\n";
    exit;
}

echo "✅ Found DevRev record\n";

try {
    $key = hash('sha256', $jwt_secret, true);
    $data = base64_decode($row['api_key']);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    
    if ($decrypted === false) {
        echo "❌ Decrypt FAILED\n";
        exit;
    }
    
    echo "✅ Decrypt SUCCESS\n";
    $masked = substr($decrypted, 0, 30) . '...' . substr($decrypted, -5);
    echo "Token: $masked\n";
    
    // 5. ทดสอบ API call
    echo "\n--- 5. ทดสอบ API ---\n";
    
    $ch = curl_init('https://api.devrev.ai/dev-users.self');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: ' . trim($decrypted)]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $http_code\n";
    
    if ($http_code === 200) {
        echo "✅✅✅ API Call SUCCESS!\n";
        $user = json_decode($response, true);
        if (isset($user['dev_user'])) {
            echo "Logged in as: {$user['dev_user']['display_name']}\n";
        }
    } else {
        echo "❌ API Call FAILED\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// 6. สรุป path ที่ใช้
echo "\n=== สรุป ===\n";
echo "✅ ใช้ path นี้: $env_found\n";
echo "✅ จาก lib/ ใช้: __DIR__ . '/../.env'\n";
echo "\n💡 ใส่โค้ดนี้ใน devrev_manager.php:\n";
echo "   \$env_path = __DIR__ . '/../.env';\n";

$conn->close();
?>