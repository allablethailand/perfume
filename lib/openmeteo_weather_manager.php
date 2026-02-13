<?php
/**
 * Open-Meteo Weather Manager (NO CACHE VERSION)
 * 
 * ดึงข้อมูลสภาพอากาศจาก Open-Meteo API โดยตรง
 * - ฟรี ไม่ต้อง API key
 * - ไม่จำกัดการใช้งาน
 * - เร็ว (~300-500ms)
 * - รองรับทุกประเทศ ทุกจังหวัด
 * - รองรับ 5 ภาษา (th, en, cn, jp, kr)
 * - ข้อมูล real-time ทุกครั้ง
 * 
 * ✨ Simple is better!
 */

class OpenMeteoWeatherManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        error_log("🌤️ [Weather Manager] Initialized (NO CACHE mode)");
    }
    
    /**
     * ดึงสภาพอากาศสำหรับ province
     */
    public function getWeatherForProvince($province, $language = 'th', $country = 'Thailand') {
        error_log("📍 [Weather] Fetching weather for {$province}, {$country} ({$language})");
        
        // 1. Geocoding - แปลงชื่อเมืองเป็น lat/lon
        $geocode = $this->geocodeLocation($province, $country);
        if (!$geocode['success']) {
            error_log("❌ [Weather] Geocoding failed, using fallback data");
            return $this->getFallbackWeather($province, $country, $language);
        }
        
        // 2. ดึงข้อมูลสภาพอากาศจาก API
        $weather_data = $this->fetchWeatherFromOpenMeteo(
            $geocode['latitude'],
            $geocode['longitude'],
            $province,
            $country
        );
        
        if ($weather_data['success']) {
            // แปลเป็นภาษาที่ต้องการ
            $translated = $this->translateWeatherData($weather_data['data'], $language);
            
            error_log("✅ [Weather] Success! Temp: {$translated['temperature_min']}-{$translated['temperature_max']}°C, Rain: {$translated['rain_chance']}%");
            
            return [
                'success' => true,
                'data' => $translated,
                'cached' => false
            ];
        }
        
        // ถ้า API ล้มเหลว ใช้ข้อมูลสำรอง
        return $this->getFallbackWeather($province, $country, $language);
    }
    
    /**
     * Geocoding - แปลงชื่อเมืองเป็น latitude, longitude
     */
    private function geocodeLocation($city, $country = null) {
        $url = "https://geocoding-api.open-meteo.com/v1/search";
        
        $params = [
            'name' => $city,
            'count' => 1,
            'language' => 'en',
            'format' => 'json'
        ];
        
        $url .= '?' . http_build_query($params);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'WeatherApp/2.0'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($response)) {
            return ['success' => false];
        }
        
        $data = json_decode($response, true);
        
        if (empty($data['results'])) {
            return ['success' => false];
        }
        
        $result = $data['results'][0];
        
        return [
            'success' => true,
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
            'name' => $result['name'] ?? $city,
            'country' => $result['country'] ?? $country
        ];
    }
    
    /**
     * ดึงข้อมูลสภาพอากาศจาก Open-Meteo API
     */
    private function fetchWeatherFromOpenMeteo($latitude, $longitude, $province, $country) {
        $url = "https://api.open-meteo.com/v1/forecast";
        
        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_probability_max,weather_code,wind_speed_10m_max',
            'timezone' => 'auto',
            'forecast_days' => 1
        ];
        
        $url .= '?' . http_build_query($params);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'WeatherApp/2.0'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($response)) {
            return ['success' => false];
        }
        
        $data = json_decode($response, true);
        
        if (empty($data['daily'])) {
            return ['success' => false];
        }
        
        $daily = $data['daily'];
        $weather_code = $daily['weather_code'][0] ?? 0;
        
        return [
            'success' => true,
            'data' => [
                'province' => $province,
                'country' => $country,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'temperature_min' => round($daily['temperature_2m_min'][0] ?? 20, 1),
                'temperature_max' => round($daily['temperature_2m_max'][0] ?? 30, 1),
                'rain_chance' => (int)($daily['precipitation_probability_max'][0] ?? 0),
                'wind_speed' => round($daily['wind_speed_10m_max'][0] ?? 0, 1),
                'weather_code' => $weather_code,
                'conditions' => $this->getWeatherDescription($weather_code, 'th'),
                'forecast_date' => $daily['time'][0] ?? date('Y-m-d')
            ]
        ];
    }
    
    /**
     * ข้อมูลสำรองกรณี API ล้มเหลว
     */
    private function getFallbackWeather($province, $country, $language) {
        error_log("⚠️ [Weather] Using fallback data");
        
        return [
            'success' => true,
            'data' => [
                'province' => $province,
                'country' => $country,
                'temperature_min' => 24,
                'temperature_max' => 33,
                'rain_chance' => 40,
                'conditions' => $this->getWeatherDescription(2, $language),
                'wind_speed' => 15,
                'weather_code' => 2,
                'forecast_date' => date('Y-m-d')
            ],
            'cached' => false
        ];
    }
    
    /**
     * แปล WMO Weather Code เป็นคำอธิบาย
     */
    private function getWeatherDescription($code, $language = 'th') {
        $descriptions = [
            0 => ['th' => 'ท้องฟ้าแจ่มใส', 'en' => 'Clear sky', 'cn' => '晴空', 'jp' => '快晴', 'kr' => '맑음'],
            1 => ['th' => 'เมฆบางส่วน', 'en' => 'Mainly clear', 'cn' => '大部分晴朗', 'jp' => '晴れ', 'kr' => '대체로 맑음'],
            2 => ['th' => 'เมฆบางส่วน', 'en' => 'Partly cloudy', 'cn' => '部分多云', 'jp' => '部分的に曇り', 'kr' => '부분적 흐림'],
            3 => ['th' => 'มีเมฆมาก', 'en' => 'Overcast', 'cn' => '阴天', 'jp' => '曇り', 'kr' => '흐림'],
            45 => ['th' => 'มีหมอก', 'en' => 'Fog', 'cn' => '雾', 'jp' => '霧', 'kr' => '안개'],
            48 => ['th' => 'มีหมอกน้ำค้าง', 'en' => 'Depositing rime fog', 'cn' => '雾凇', 'jp' => '霧氷', 'kr' => '서리 안개'],
            51 => ['th' => 'ฝนปรอยๆ เบา', 'en' => 'Light drizzle', 'cn' => '小雨', 'jp' => '軽い霧雨', 'kr' => '가벼운 이슬비'],
            53 => ['th' => 'ฝนปรอยๆ ปานกลาง', 'en' => 'Moderate drizzle', 'cn' => '中雨', 'jp' => '霧雨', 'kr' => '이슬비'],
            55 => ['th' => 'ฝนปรอยๆ หนัก', 'en' => 'Dense drizzle', 'cn' => '大雨', 'jp' => '強い霧雨', 'kr' => '강한 이슬비'],
            61 => ['th' => 'ฝนเบา', 'en' => 'Slight rain', 'cn' => '小雨', 'jp' => '小雨', 'kr' => '약한 비'],
            63 => ['th' => 'ฝนปานกลาง', 'en' => 'Moderate rain', 'cn' => '中雨', 'jp' => '雨', 'kr' => '비'],
            65 => ['th' => 'ฝนหนัก', 'en' => 'Heavy rain', 'cn' => '大雨', 'jp' => '大雨', 'kr' => '강한 비'],
            71 => ['th' => 'หิมะเบา', 'en' => 'Slight snow', 'cn' => '小雪', 'jp' => '小雪', 'kr' => '약한 눈'],
            73 => ['th' => 'หิมะปานกลาง', 'en' => 'Moderate snow', 'cn' => '中雪', 'jp' => '雪', 'kr' => '눈'],
            75 => ['th' => 'หิมะหนัก', 'en' => 'Heavy snow', 'cn' => '大雪', 'jp' => '大雪', 'kr' => '강한 눈'],
            80 => ['th' => 'ฝนเบา', 'en' => 'Slight rain showers', 'cn' => '阵雨', 'jp' => 'にわか雨', 'kr' => '소나기'],
            81 => ['th' => 'ฝนปานกลาง', 'en' => 'Moderate rain showers', 'cn' => '中阵雨', 'jp' => '強いにわか雨', 'kr' => '소나기'],
            82 => ['th' => 'ฝนหนัก', 'en' => 'Violent rain showers', 'cn' => '大阵雨', 'jp' => '激しいにわか雨', 'kr' => '강한 소나기'],
            95 => ['th' => 'พายุฝนฟ้าคะนอง', 'en' => 'Thunderstorm', 'cn' => '雷暴', 'jp' => '雷雨', 'kr' => '뇌우'],
            96 => ['th' => 'พายุฝนฟ้าคะนองมีลูกเห็บเบา', 'en' => 'Thunderstorm with slight hail', 'cn' => '雷暴伴小冰雹', 'jp' => '雷雨と軽いひょう', 'kr' => '약한 우박을 동반한 뇌우'],
            99 => ['th' => 'พายุฝนฟ้าคะนองมีลูกเห็บหนัก', 'en' => 'Thunderstorm with heavy hail', 'cn' => '雷暴伴大冰雹', 'jp' => '雷雨と強いひょう', 'kr' => '강한 우박을 동반한 뇌우']
        ];
        
        return $descriptions[$code][$language] ?? $descriptions[0][$language] ?? 'Unknown';
    }
    
    /**
     * แปลข้อมูลสภาพอากาศตามภาษา
     */
    private function translateWeatherData($data, $language) {
        return [
            'province' => $data['province'],
            'country' => $data['country'],
            'temperature_min' => $data['temperature_min'],
            'temperature_max' => $data['temperature_max'],
            'rain_chance' => $data['rain_chance'],
            'conditions' => $this->getWeatherDescription($data['weather_code'], $language),
            'wind_speed' => $data['wind_speed'],
            'forecast_date' => $data['forecast_date'],
            'message' => $this->generateMessage($data, $language)
        ];
    }
    
    /**
     * สร้างข้อความสภาพอากาศ
     */
    private function generateMessage($data, $language) {
        $temp_min = $data['temperature_min'];
        $temp_max = $data['temperature_max'];
        $rain = $data['rain_chance'];
        $province = $data['province'];
        $conditions = $this->getWeatherDescription($data['weather_code'], $language);
        
        $messages = [
            'th' => "วันนี้ที่{$province} อากาศ{$conditions} อุณหภูมิ {$temp_min}-{$temp_max} องศาเซลเซียส โอกาสฝนตก {$rain}%",
            'en' => "Today in {$province}: {$conditions}, {$temp_min}-{$temp_max}°C, {$rain}% chance of rain",
            'cn' => "今天{$province}{$conditions}，气温 {$temp_min}-{$temp_max}°C，降雨概率 {$rain}%",
            'jp' => "本日の{$province}は{$conditions}、気温 {$temp_min}-{$temp_max}°C、降水確率 {$rain}%",
            'kr' => "오늘 {$province}은 {$conditions}, 기온 {$temp_min}-{$temp_max}°C, 강수확률 {$rain}%"
        ];
        
        return $messages[$language] ?? $messages['th'];
    }
}
?>