<?php
/* หา .env แล้วโหลดครั้งเดียว — include ซ้ำได้ ไม่พัง
 *
 * 🔑 ที่เดิมของ .env อยู่ในรากเว็บ = เปิดจากเบราว์เซอร์ได้ตรง ๆ
 *    (ตรวจแล้ว 2026-09-01 https://www.trandar.com/perfume/.env ตอบ 200 จริง)
 *    กฎบล็อกใน .htaccess ไม่มีผลบน prod เพราะ prod ใช้ nginx ซึ่งไม่อ่านไฟล์นั้น
 *    การย้ายไฟล์ออกนอกรากเว็บจึงแก้ได้ถาวรกว่า ไม่ต้องพึ่งกฎของเว็บเซิร์ฟเวอร์
 *
 * ⚠️ โฟลเดอร์ต้องแยกจากเว็บอื่นในเซิร์ฟเวอร์เดียวกัน (`/secrets/perfume`)
 *    เพราะคนละฐานข้อมูล ถ้าใช้ที่เดียวกันจะอ่าน .env ของ trandar แล้วต่อผิดฐาน
 *
 * เรียงจากปลอดภัยไปหาที่เดิม เพื่อให้ย้ายบน prod ได้โดยไม่ต้องแก้เครื่องพัฒนาให้ตรงกัน
 */
if (!isset($GLOBALS['__perfume_env_loaded'])) {
    $envDir = null;
    foreach ([
        getenv('PERFUME_ENV_DIR') ?: null,        // ตั้งค่าเองได้ ถ้าอยากเก็บที่อื่น
        dirname(__DIR__, 4) . '/secrets/perfume', // นอกรากเว็บทั้งหมด — ที่ที่ควรอยู่
        dirname(__DIR__, 3) . '/secrets/perfume',
        dirname(__DIR__),                         // ที่เดิม (รากเว็บ) — ใช้บนเครื่องพัฒนา
    ] as $candidate) {
        if ($candidate && is_file(rtrim($candidate, "/" . DIRECTORY_SEPARATOR) . "/.env")) {
            $envDir = $candidate;
            break;
        }
    }

    if ($envDir === null) {
        error_log('env_boot.php: หาไฟล์ .env ไม่เจอเลยสักที่');
        http_response_code(500);
        exit('Configuration error');
    }

    \Dotenv\Dotenv::createImmutable($envDir)->load();
    $GLOBALS['__perfume_env_loaded'] = true;
}
