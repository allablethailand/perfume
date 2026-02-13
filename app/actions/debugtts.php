<?php
/**
 * TTS Cache Debug Tool
 * ⚠️ ใช้ชั่วคราวเท่านั้น — ลบออกหลัง debug เสร็จ!
 *
 * เปิดที่: https://www.trandar.com/perfume/app/actions/debug_tts_cache.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../lib/connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

global $conn;
$out = [];

// ── 1. Path analysis ──────────────────────────────────────────
$projectRoot = realpath(__DIR__ . '/../../');
$ttsCacheDir = $projectRoot . '/public/tts_cache/';
$docRoot     = rtrim(realpath($_SERVER['DOCUMENT_ROOT']), '/');
$webBase     = rtrim(str_replace($docRoot, '', $projectRoot), '/');
$protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$ttsCacheUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $webBase . '/public/tts_cache/';

$out['1_paths'] = [
    '__DIR__'       => __DIR__,
    'projectRoot'   => $projectRoot,
    'ttsCacheDir'   => $ttsCacheDir,
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
    'webBase'       => $webBase,
    'ttsCacheUrl'   => $ttsCacheUrl,
    'dir_exists'    => file_exists($ttsCacheDir),
    'dir_writable'  => is_writable($ttsCacheDir ?: '/tmp'),
];

// ── 2. MySQL sql_mode ──────────────────────────────────────────
$r = $conn->query("SELECT @@sql_mode AS m");
$out['2_mysql_mode'] = $r ? $r->fetch_assoc()['m'] : 'query failed';

// ── 3. Table columns ───────────────────────────────────────────
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM ai_tts_cache");
if ($r) {
    while ($row = $r->fetch_assoc()) $cols[] = $row;
}
$out['3_columns'] = $cols;

// ── 4. Indexes & unique keys ───────────────────────────────────
$idxs = [];
$r = $conn->query("SHOW INDEX FROM ai_tts_cache");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $idxs[] = [
            'Key_name'    => $row['Key_name'],
            'Non_unique'  => $row['Non_unique'],
            'Column_name' => $row['Column_name'],
        ];
    }
}
$out['4_indexes'] = $idxs;

// ── 5. Foreign keys ────────────────────────────────────────────
$fks = [];
$r = $conn->query("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'ai_tts_cache'
      AND REFERENCED_TABLE_NAME IS NOT NULL
");
if ($r) {
    while ($row = $r->fetch_assoc()) $fks[] = $row;
}
$out['5_foreign_keys'] = $fks ?: 'none';

// ── 6. Test INSERT (null question_id / choice_id) ──────────────
$t_text    = 'debug_test_' . time();
$t_hash    = hash('sha256', $t_text . '|debug_voice|th');
$t_voice   = 'debug_voice';
$t_lang    = 'th';
$t_url     = 'https://test.example.com/test.mp3';
$t_path    = '/tmp/test_debug.mp3';
$t_size    = 1000;
$t_chars   = 10;
$t_model   = 'eleven_v3';
$t_type    = 'debug';
$t_ai_id   = 0;
$null_val  = null;

$stmt = $conn->prepare("
    INSERT INTO ai_tts_cache (
        ai_id, voice_id, language_code, text_content, text_hash,
        audio_file_url, audio_file_path, audio_file_size, character_count,
        model_used, cache_type, question_id, choice_id, hit_count, last_used_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
");

$stmt->bind_param(
    'issssssiiisss',
    $t_ai_id, $t_voice, $t_lang, $t_text, $t_hash,
    $t_url, $t_path, $t_size, $t_chars,
    $t_model, $t_type, $null_val, $null_val
);

$ok  = $stmt->execute();
$iid = $ok ? (int)$conn->insert_id : 0;
$out['6_test_insert'] = [
    'success'         => $ok,
    'insert_id'       => $iid,
    'errno'           => $stmt->errno,
    'error'           => $stmt->error,
    'bind_type_used'  => 'issssssiiisss  (question_id/choice_id → s for NULL-safe)',
];
$stmt->close();

// cleanup test record
if ($iid > 0) {
    $conn->query("DELETE FROM ai_tts_cache WHERE cache_id = $iid");
    $out['6_test_insert']['cleaned_up'] = true;
}

// ── 7. Test INSERT with ai_id = real value (ถ้ามี) ─────────────
$r = $conn->query("SELECT ai_id, voice_id FROM ai_companions WHERE status=1 AND del=0 LIMIT 1");
if ($r && $row = $r->fetch_assoc()) {
    $real_ai_id  = (int)$row['ai_id'];
    $real_voice  = $row['voice_id'];
    $t2_text     = 'debug_real_ai_' . time();
    $t2_hash     = hash('sha256', $t2_text . '|' . $real_voice . '|th');
    $null_val2   = null;

    $stmt2 = $conn->prepare("
        INSERT INTO ai_tts_cache (
            ai_id, voice_id, language_code, text_content, text_hash,
            audio_file_url, audio_file_path, audio_file_size, character_count,
            model_used, cache_type, question_id, choice_id, hit_count, last_used_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt2->bind_param(
        'issssssiiisss',
        $real_ai_id, $real_voice, $t_lang, $t2_text, $t2_hash,
        $t_url, $t_path, $t_size, $t_chars,
        $t_model, $t_type, $null_val2, $null_val2
    );
    $ok2  = $stmt2->execute();
    $iid2 = $ok2 ? (int)$conn->insert_id : 0;
    $out['7_test_real_ai_insert'] = [
        'ai_id'     => $real_ai_id,
        'voice_id'  => $real_voice,
        'success'   => $ok2,
        'insert_id' => $iid2,
        'errno'     => $stmt2->errno,
        'error'     => $stmt2->error,
    ];
    $stmt2->close();
    if ($iid2 > 0) {
        $conn->query("DELETE FROM ai_tts_cache WHERE cache_id = $iid2");
        $out['7_test_real_ai_insert']['cleaned_up'] = true;
    }
}

// ── 8. Recent 5 records ────────────────────────────────────────
$recent = [];
$r = $conn->query("SELECT cache_id, ai_id, cache_type, status, del, created_at FROM ai_tts_cache ORDER BY cache_id DESC LIMIT 5");
if ($r) {
    while ($row = $r->fetch_assoc()) $recent[] = $row;
}
$out['8_recent_records'] = $recent ?: 'empty table';

$conn->close();

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>