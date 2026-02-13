<?php
/**
 * Get Weather for User's Location (GEOLOCATION VERSION)
 * 
 * GET: /app/actions/get_weather.php
 * 
 * ✅ ดึงสภาพอากาศจากพิกัดจริง (Geolocation API)
 * ✅ รองรับทุกประเทศผ่าน Open-Meteo API (ฟรี ไม่จำกัด)
 * ✅ รองรับ 5 ภาษา
 * ✅ ทักทายตามเวลาจริง (เช้า/บ่าย/เย็น/ค่ำ)
 * ✅ ข้อมูล real-time ทุกครั้ง
 * ✅ ไม่อนุญาต = ไม่พูด (Privacy-first)
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
// ✅ รับพิกัดจาก URL (จาก Geolocation API)
// ========================================
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;

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

// ========================================
// ✅ ถ้ามีพิกัด = ผู้ใช้อนุญาต → ดึงสภาพอากาศ
// ========================================
if ($lat !== null && $lon !== null) {
    error_log("🌍 [Weather] Using coordinates: {$lat}, {$lon}");
    
    try {
        $weatherManager = new OpenMeteoWeatherManager($conn);
        $weather_result = $weatherManager->getWeatherByCoordinates($lat, $lon, $language);
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
    
    // ✅ ดึงชื่อสถานที่จาก weather data
    $location_name = $weather['location'] ?? 'ตำแหน่งของคุณ';
    
    // ✅ สร้างข้อความสภาพอากาศตามภาษา (ใช้ชื่อจังหวัด/เมืองจริง)
    $weather_messages = [
        'th' => "{$greeting} 🌤️ วันนี้ที่{$location_name} อากาศ{$weather['conditions']} " .
                "อุณหภูมิ {$weather['temperature_min']}-{$weather['temperature_max']} องศาเซลเซียส " .
                "โอกาสฝนตก {$weather['rain_chance']}%",
        
        'en' => "{$greeting} 🌤️ Today in {$location_name}: {$weather['conditions']}, " .
                "{$weather['temperature_min']}-{$weather['temperature_max']}°C, " .
                "{$weather['rain_chance']}% chance of rain",
        
        'cn' => "{$greeting} 🌤️ 今天{$location_name}{$weather['conditions']}，" .
                "气温 {$weather['temperature_min']}-{$weather['temperature_max']}°C，" .
                "降雨概率 {$weather['rain_chance']}%",
        
        'jp' => "{$greeting} 🌤️ 本日の{$location_name}は{$weather['conditions']}、" .
                "気温 {$weather['temperature_min']}-{$weather['temperature_max']}°C、" .
                "降水確率 {$weather['rain_chance']}%",
        
        'kr' => "{$greeting} 🌤️ 오늘 {$location_name}은 {$weather['conditions']}, " .
                "기온 {$weather['temperature_min']}-{$weather['temperature_max']}°C, " .
                "강수확률 {$weather['rain_chance']}%"
    ];

    $message = $weather_messages[$language] ?? $weather_messages['th'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'location' => $weather['location'] ?? 'Your location',
            'country' => $weather['country'] ?? '',
            'temperature_min' => $weather['temperature_min'],
            'temperature_max' => $weather['temperature_max'],
            'rain_chance' => $weather['rain_chance'],
            'conditions' => $weather['conditions'],
            'wind_speed' => $weather['wind_speed'] ?? null,
            'message' => $message,
            'language' => $language,
            'cached' => false,
            'time_period' => $time_period,
            'current_hour' => $current_hour,
            'api_mode' => 'geolocation',
            'latitude' => $lat,
            'longitude' => $lon
        ]
    ], JSON_UNESCAPED_UNICODE);

    $conn->close();
    exit;
}

// ========================================
// ✅ ถ้าไม่มีพิกัด = ผู้ใช้ไม่อนุญาต → ไม่ส่งข้อมูลสภาพอากาศ
// ========================================
error_log("⚠️ [Weather] No coordinates provided - user denied location permission");

echo json_encode([
    'status' => 'no_permission',
    'message' => 'Location permission not granted',
    'greeting' => $greeting,
    'time_period' => $time_period
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>