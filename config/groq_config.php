<?php
/**
 * Groq API Configuration
 * 
 * วิธีรับ API Key ฟรี:
 * 1. ไปที่ https://console.groq.com/
 * 2. สมัครสมาชิก (ฟรี)
 * 3. ไปที่ API Keys
 * 4. สร้าง API Key ใหม่
 * 5. Copy มาใส่ในไฟล์นี้
 * 
 * Groq ให้ใช้ฟรี! เร็วมาก และมี rate limit สูง
 */

return [
    // ใส่ API Key ของคุณที่นี่
    'api_key' => $_ENV['GROQ_API_KEY'],
    
    // โมเดลเริ่มต้น (แนะนำ)
    'default_model' => 'llama-3.3-70b-versatile',
    
    // โมเดลอื่นๆ ที่ใช้ได้ (ทั้งหมดฟรี!)
    'available_models' => [
        'llama-3.3-70b-versatile' => [
            'name' => 'Llama 3.3 70B Versatile',
            'description' => 'โมเดลใหม่ล่าสุด เหมาะกับงานทั่วไป',
            'max_tokens' => 8192,
            'recommended' => true
        ],
        'llama-3.1-70b-versatile' => [
            'name' => 'Llama 3.1 70B Versatile',
            'description' => 'รุ่นก่อนหน้า ยังดีอยู่',
            'max_tokens' => 8192,
            'recommended' => false
        ],
        'mixtral-8x7b-32768' => [
            'name' => 'Mixtral 8x7B',
            'description' => 'รองรับ context ยาวมาก',
            'max_tokens' => 32768,
            'recommended' => false
        ],
        'gemma2-9b-it' => [
            'name' => 'Gemma 2 9B IT',
            'description' => 'เล็กแต่เร็ว',
            'max_tokens' => 8192,
            'recommended' => false
        ]
    ],
    
    // ตั้งค่าเพิ่มเติม
    'settings' => [
        'temperature' => 0.7, // 0.0-2.0 (ยิ่งสูงยิ่งสร้างสรรค์)
        'max_tokens' => 1024, // จำนวน tokens สูงสุดต่อคำตอบ
        'top_p' => 1,
        'timeout' => 30 // วินาที
    ],
    
    // Rate Limits (Groq ฟรี)
    'rate_limits' => [
        'requests_per_minute' => 30,
        'requests_per_day' => 14400,
        'tokens_per_minute' => 6000
    ]
];
?>