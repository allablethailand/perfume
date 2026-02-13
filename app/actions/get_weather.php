<?php
/**
 * Get Weather for User's Location (NO CACHE VERSION)
 * 
 * GET: /app/actions/get_weather.php
 * 
 * ✅ ดึงสภาพอากาศตาม province จาก user_addresses
 * ✅ รองรับทุกประเทศผ่าน Open-Meteo API (ฟรี ไม่จำกัด)
 * ✅ รองรับ 5 ภาษา
 * ✅ ทักทายตามเวลาจริง (เช้า/บ่าย/เย็น/ค่ำ)
 * ✅ ข้อมูล real-time ทุกครั้ง
 * ✨ Simple & Fast!
 */

// ตรวจสอบและโหลด dependencies
$required_files = [
    'connect.php' => '../../lib/connect.php',
    'jwt_helper.php' => '../../lib/jwt_helper.php',
    'openmeteo_weather_manager.php' => '../../lib/openmeteo_weather_manager.php'
];

foreach ($required_files as $name => $path) {
    if (!file_exists($path)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => "Missing required file: {$name}",
            'path' => $path,
            'hint' => 'Please upload openmeteo_weather_manager.php to /lib/ directory'
        ]);
        exit;
    }
    require_once($path);
}

global $conn;

header('Content-Type: application/json');

// ตรวจสอบว่า class โหลดได้หรือไม่
if (!class_exists('OpenMeteoWeatherManager')) {
    echo json_encode([
        'status' => 'error',
        'message' => 'OpenMeteoWeatherManager class not found',
        'hint' => 'Please check if openmeteo_weather_manager.php is uploaded to /lib/ directory'
    ]);
    exit;
}

// รับ language จาก URL
$language = $_GET['lang'] ?? 'th';

// Map language codes
$lang_map = [
    'th' => 'th',
    'en' => 'en',
    'cn' => 'cn',
    'jp' => 'jp',
    'kr' => 'kr'
];
$language = $lang_map[$language] ?? 'th';

// ========================================
// ✅ เช็คเวลาปัจจุบัน (Thailand Time Zone)
// ========================================
date_default_timezone_set('Asia/Bangkok');
$current_hour = (int)date('H');

// กำหนดช่วงเวลา
if ($current_hour >= 5 && $current_hour < 12) {
    $time_period = 'morning'; // เช้า 05:00-11:59
} elseif ($current_hour >= 12 && $current_hour < 16) {
    $time_period = 'afternoon'; // บ่าย 12:00-15:59
} elseif ($current_hour >= 16 && $current_hour < 18) {
    $time_period = 'evening'; // เย็น 16:00-17:59
} else {
    $time_period = 'night'; // ค่ำ/กลางคืน 18:00-04:59
}

// ========================================
// ตรวจสอบ Authentication (รองรับทั้ง JWT และ Guest)
// ========================================
$user_id = null;
$province = null;
$country = 'Thailand'; // Default

// ลอง JWT ก่อน
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $jwt = str_replace('Bearer ', '', $headers['Authorization']);
    try {
        $decoded = verifyJWT($jwt);
        if ($decoded) {
            $user_id = $decoded->data->user_id ?? null;
        }
    } catch (Exception $e) {
        // JWT ไม่ valid
    }
}

// ถ้ามี user_id ให้ดึง province จาก default address
if ($user_id) {
    $stmt = $conn->prepare("
        SELECT province, country
        FROM user_addresses
        WHERE user_id = ? AND is_default = 1 AND del = 0
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $addr = $result->fetch_assoc();
        $province = $addr['province'];
        $country = $addr['country'] ?? 'Thailand';
    }
    $stmt->close();
}

// ถ้ายังไม่ได้ province ให้ใช้ default
if (empty($province)) {
    $province = 'กรุงเทพมหานคร'; // Default
    $country = 'Thailand';
}

// ========================================
// ดึงข้อมูลสภาพอากาศจาก Open-Meteo (ตรงๆ ไม่ผ่าน cache)
// ========================================
try {
    $weatherManager = new OpenMeteoWeatherManager($conn);
    $weather_result = $weatherManager->getWeatherForProvince($province, $language, $country);
} catch (Exception $e) {
    error_log("❌ [Weather] Exception: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Weather system error: ' . $e->getMessage()
    ]);
    exit;
}

if (!$weather_result['success']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch weather data'
    ]);
    exit;
}

$weather = $weather_result['data'];

// ========================================
// ✅ สร้างข้อความทักทายตามเวลา + สภาพอากาศ
// ========================================

// คำทักทายตามเวลาในแต่ละภาษา
$greetings = [
    'morning' => [
        'th' => "สวัสดียามเช้าค่ะ 🌅",
        'en' => "Good morning! 🌅",
        'cn' => "早上好！🌅",
        'jp' => "おはようございます！🌅",
        'kr' => "좋은 아침입니다! 🌅"
    ],
    'afternoon' => [
        'th' => "สวัสดีตอนบ่ายค่ะ ☀️",
        'en' => "Good afternoon! ☀️",
        'cn' => "下午好！☀️",
        'jp' => "こんにちは！☀️",
        'kr' => "좋은 오후입니다! ☀️"
    ],
    'evening' => [
        'th' => "สวัสดียามเย็นค่ะ 🌆",
        'en' => "Good evening! 🌆",
        'cn' => "傍晚好！🌆",
        'jp' => "こんばんは！🌆",
        'kr' => "좋은 저녁입니다! 🌆"
    ],
    'night' => [
        'th' => "สวัสดียามค่ำคืนค่ะ 🌙",
        'en' => "Good evening! 🌙",
        'cn' => "晚上好！🌙",
        'jp' => "こんばんは！🌙",
        'kr' => "안녕하세요! 🌙"
    ]
];

$greeting = $greetings[$time_period][$language] ?? $greetings['morning']['th'];

// ✅ สร้างข้อความสภาพอากาศ (รวมทักทายเข้าไปด้วย)
$weather_messages = [
    'th' => "{$greeting} 🌤️ วันนี้ที่{$weather['province']} อากาศ{$weather['conditions']} " .
            "อุณหภูมิ {$weather['temperature_min']}-{$weather['temperature_max']} องศาเซลเซียส " .
            "โอกาสฝนตก {$weather['rain_chance']}%",
    
    'en' => "{$greeting} 🌤️ Today in {$weather['province']}: {$weather['conditions']}, " .
            "{$weather['temperature_min']}-{$weather['temperature_max']}°C, " .
            "{$weather['rain_chance']}% chance of rain",
    
    'cn' => "{$greeting} 🌤️ 今天{$weather['province']}{$weather['conditions']}，" .
            "气温 {$weather['temperature_min']}-{$weather['temperature_max']}°C，" .
            "降雨概率 {$weather['rain_chance']}%",
    
    'jp' => "{$greeting} 🌤️ 本日の{$weather['province']}は{$weather['conditions']}、" .
            "気温 {$weather['temperature_min']}-{$weather['temperature_max']}°C、" .
            "降水確率 {$weather['rain_chance']}%",
    
    'kr' => "{$greeting} 🌤️ 오늘 {$weather['province']}은 {$weather['conditions']}, " .
            "기온 {$weather['temperature_min']}-{$weather['temperature_max']}°C, " .
            "강수확률 {$weather['rain_chance']}%"
];

$message = $weather_messages[$language] ?? $weather_messages['th'];

echo json_encode([
    'status' => 'success',
    'data' => [
        'province' => $weather['province'],
        'country' => $weather['country'] ?? $country,
        'temperature_min' => $weather['temperature_min'],
        'temperature_max' => $weather['temperature_max'],
        'rain_chance' => $weather['rain_chance'],
        'conditions' => $weather['conditions'],
        'wind_speed' => $weather['wind_speed'] ?? null,
        'message' => $message,
        'language' => $language,
        'cached' => false, // เสมอเพราะไม่มี cache
        'time_period' => $time_period,
        'current_hour' => $current_hour,
        'api_mode' => 'direct' // บอกว่าเรียก API ตรง
    ]
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>