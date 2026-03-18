<?php
/**
 * warmup_news_cache.php
 * ✅ Pre-cache ข่าว ทุกประเทศ/ภาษา ล่วงหน้า
 * ✅ TTL หมดเที่ยงคืน (วันต่อวัน)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/perfume/lib/connect.php';

$countries   = ['th','us','gb','jp','cn','kr','sg','au','de','fr','in','id','my'];
$langs       = ['th','en','cn','jp','kr'];
$newsScript  = $_SERVER['DOCUMENT_ROOT'] . '/perfume/app/actions/get_news.php';
$results     = [];

// ─── Warm up news (world) ─────────────────────────────────
foreach ($langs as $lang) {
    $_GET = ['type' => 'world', 'lang' => $lang, '_warmup' => '1'];
    ob_start();
    include $newsScript;
    $result    = ob_get_clean();
    $ok        = $result && str_contains($result, '"success"');
    $results[] = "world/{$lang}: " . ($ok ? '✅' : '❌');
}

// ─── Warm up news (country) ───────────────────────────────
foreach ($countries as $country) {
    $primaryLang = match($country) {
        'th'    => 'th',
        'jp'    => 'jp',
        'cn'    => 'cn',
        'kr'    => 'kr',
        default => 'en',
    };

    foreach (array_unique([$primaryLang, 'en']) as $lang) {
        $_GET = ['type' => 'country', 'country' => $country, 'lang' => $lang, '_warmup' => '1'];
        ob_start();
        include $newsScript;
        $result    = ob_get_clean();
        $ok        = $result && str_contains($result, '"success"');
        $results[] = "{$country}/{$lang}: " . ($ok ? '✅' : '❌');
    }
}

echo json_encode([
    'status'  => 'done',
    'warmed'  => count($results),
    'results' => $results,
    'time'    => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);