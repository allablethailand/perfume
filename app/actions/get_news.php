<?php
/**
 * get_news.php  (PATCHED)
 * ✅ ดึงข่าว TOP 1 พร้อม detail (ย่อยจาก description/content)
 * ✅ Pre-cache ทุกประเทศ/ภาษา — TTL = หมดเที่ยงคืน (วันต่อวัน)
 * ✅ Filter title/description ที่เป็น garbage
 * ✅ Fallback หลาย feed
 */

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');

defined('CACHE_DIR') || define('CACHE_DIR', __DIR__ . '/../../cache/news/');

// ✅ TTL = หมดเที่ยงคืน (ไม่ใช่ fixed 30 นาที)
if (!function_exists('getCacheTTL')) {
    function getCacheTTL() {
        $now      = time();
        $midnight = mktime(0, 0, 0, date('n'), date('j') + 1); // เที่ยงคืนคืนนี้
        return $midnight - $now; // วินาทีที่เหลือถึงเที่ยงคืน
    }
}

$type    = $_GET['type']    ?? 'world';
$country = $_GET['country'] ?? 'th';
$lang    = $_GET['lang']    ?? 'th';

$gtLangMap = ['th'=>'th','en'=>'en','cn'=>'zh-CN','jp'=>'ja','kr'=>'ko'];
$gtLang    = $gtLangMap[$lang] ?? 'th';

// ─── RSS Feed map ──────────────────────────────────────────
$feedMap = [
    'world' => [
        'https://feeds.bbci.co.uk/news/world/rss.xml',
        'https://www.theguardian.com/world/rss',
        'https://rss.nytimes.com/services/xml/rss/nyt/World.xml',
        'https://www.aljazeera.com/xml/rss/all.xml',
    ],
    'th' => [
        'https://www.bangkokpost.com/rss/data/topstories.xml',
        'https://www.nationthailand.com/rss',
        'https://feeds.bbci.co.uk/news/world/asia/rss.xml',
        'https://news.google.com/rss/search?q=Thailand+news&hl=en&gl=TH&ceid=TH:en',
    ],
    'us' => [
        'https://feeds.bbci.co.uk/news/world/us_and_canada/rss.xml',
        'https://rss.nytimes.com/services/xml/rss/nyt/US.xml',
        'https://abcnews.go.com/abcnews/usheadlines',
    ],
    'gb' => [
        'https://feeds.bbci.co.uk/news/england/rss.xml',
        'https://www.theguardian.com/uk/rss',
    ],
    'jp' => [
        'https://www3.nhk.or.jp/rss/news/cat0.xml',
        'https://feeds.bbci.co.uk/news/world/asia/rss.xml',
        'https://news.google.com/rss/search?q=Japan+news&hl=en&gl=JP&ceid=JP:en',
    ],
    'cn' => [
        'https://feeds.bbci.co.uk/news/world/asia/rss.xml',
        'https://news.google.com/rss/search?q=China+news&hl=en&gl=CN&ceid=CN:en',
    ],
    'kr' => [
        'https://www.koreaherald.com/rss/0100000000000.xml',
        'https://news.google.com/rss/search?q=South+Korea+news&hl=en&gl=KR&ceid=KR:en',
    ],
    'sg' => [
        'https://www.straitstimes.com/news/singapore/rss.xml',
        'https://news.google.com/rss/search?q=Singapore+news&hl=en&gl=SG&ceid=SG:en',
    ],
    'au' => [
        'https://www.abc.net.au/news/feed/51120/rss.xml',
        'https://news.google.com/rss/search?q=Australia+news&hl=en&gl=AU&ceid=AU:en',
    ],
    'de' => [
        'https://www.dw.com/en/top-stories/s-9097/rss',
        'https://news.google.com/rss/search?q=Germany+news&hl=en&gl=DE&ceid=DE:en',
    ],
    'fr' => [
        'https://www.france24.com/en/rss',
        'https://news.google.com/rss/search?q=France+news&hl=en&gl=FR&ceid=FR:en',
    ],
    'in' => [
        'https://feeds.feedburner.com/ndtvnews-top-stories',
        'https://news.google.com/rss/search?q=India+news&hl=en&gl=IN&ceid=IN:en',
    ],
    'id' => [
        'https://news.google.com/rss/search?q=Indonesia+news&hl=en&gl=ID&ceid=ID:en',
    ],
    'my' => [
        'https://news.google.com/rss/search?q=Malaysia+news&hl=en&gl=MY&ceid=MY:en',
    ],
];

$countryNamesEN = [
    'th'=>'Thailand',  'us'=>'United States', 'gb'=>'United Kingdom',
    'jp'=>'Japan',     'cn'=>'China',         'kr'=>'South Korea',
    'sg'=>'Singapore', 'au'=>'Australia',     'de'=>'Germany',
    'fr'=>'France',    'in'=>'India',         'id'=>'Indonesia',
    'my'=>'Malaysia',  'ph'=>'Philippines',   'vn'=>'Vietnam',
];

// ─── Cache ────────────────────────────────────────────────
if (!is_dir(CACHE_DIR)) { @mkdir(CACHE_DIR, 0755, true); }
$cacheKey  = md5("news_v3_{$type}_{$country}_{$lang}");
$cacheFile = CACHE_DIR . $cacheKey . '.json';

$cacheTTL = getCacheTTL();
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    echo file_get_contents($cacheFile);
    exit;
}

// ─── Fetch RSS ────────────────────────────────────────────
if (!function_exists('fetchRSS')) {
function fetchRSS($url) {
    $ctx = stream_context_create(['http' => [
        'timeout'         => 10,
        'ignore_errors'   => true,
        'follow_location' => true,
        'max_redirects'   => 3,
        'header'          => "User-Agent: Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/120\r\n"
                           . "Accept: application/rss+xml, application/xml, text/xml, */*\r\n"
                           . "Accept-Language: en-US,en;q=0.9\r\n",
    ]]);
    return @file_get_contents($url, false, $ctx);
}
}

$errorPatterns = [
    'feed.*not.*available','unavailable','^error$','access denied',
    'forbidden','^404$','not found','javascript.*required',
    'enable javascript','please enable','subscribe to','sign in to','log in to',
];

if (!function_exists('isValidTitle')) {
function isValidTitle($title, $errorPatterns) {
    if (empty($title) || strlen($title) < 8) return false;
    $lower = mb_strtolower($title);
    foreach ($errorPatterns as $p) { if (preg_match('/'.$p.'/iu', $lower)) return false; }
    return true;
}
}

// ✅ ดึง description/content ออกจาก RSS item
if (!function_exists('extractDescription')) {
function extractDescription($item) {
    // ลอง field ต่างๆ ตามลำดับ
    $raw = '';
    if (!empty((string)($item->description ?? '')))        $raw = (string)$item->description;
    elseif (!empty((string)($item->summary ?? '')))        $raw = (string)$item->summary;
    elseif (!empty((string)($item->content ?? '')))        $raw = (string)$item->content;

    if (empty($raw)) {
        // ลอง namespace content:encoded
        $content = $item->children('content', true);
        if (!empty($content->encoded)) $raw = (string)$content->encoded;
    }

    if (empty($raw)) return '';

    // ตัด HTML tags ออก
    $text = strip_tags($raw);
    // ตัด whitespace
    $text = preg_replace('/\s+/', ' ', trim($text));
    // ตัดพวก "Read more..." / "Continue reading..." ท้ายประโยค
    $text = preg_replace('/\s*(read more|continue reading|full story|more\.\.\.).*$/iu', '.', $text);
    // ไม่เอายาวเกิน 300 ตัวอักษร — ตัดที่ประโยคสุดท้ายที่ครบ
    if (mb_strlen($text) > 300) {
        $short = mb_substr($text, 0, 300);
        // หาตำแหน่งจบประโยคสุดท้าย
        $lastDot = max(
            mb_strrpos($short, '.'),
            mb_strrpos($short, '!'),
            mb_strrpos($short, '?')
        );
        $text = $lastDot > 100 ? mb_substr($short, 0, $lastDot + 1) : $short . '...';
    }
    return $text;
}
}

// ─── เลือก feed ──────────────────────────────────────────
if ($type === 'world') {
    $feedUrls = $feedMap['world'];
} else {
    $feedUrls = $feedMap[$country] ?? [];
    if (empty($feedUrls)) {
        $kw = $countryNamesEN[$country] ?? strtoupper($country);
        $feedUrls = [
            "https://news.google.com/rss/search?q=" . urlencode($kw . " news") . "&hl=en",
            "https://feeds.bbci.co.uk/news/world/rss.xml",
        ];
    }
}

// ─── Parse — ดึงแค่ข่าวแรกที่ valid ──────────────────────
$topNews = null; // ['title' => ..., 'description' => ...]

foreach ($feedUrls as $feedUrl) {
    if ($topNews) break;

    $raw = fetchRSS($feedUrl);
    if (!$raw || strlen($raw) < 100) continue;

    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
    if (!$xml) continue;

    $items = [];
    if (isset($xml->channel->item)) $items = $xml->channel->item;
    elseif (isset($xml->entry))      $items = $xml->entry;

    foreach ($items as $item) {
        $title = trim((string)($item->title ?? ''));
        $title = preg_replace('/\s*[-–|]\s*[^-–|]{2,60}$/', '', $title);
        $title = trim($title);

        if (!isValidTitle($title, $errorPatterns)) continue;

        $desc = extractDescription($item);

        // ✅ ต้องมี description ด้วย ถ้าไม่มีข้ามไปข่าวถัดไป
        if (empty($desc) || mb_strlen($desc) < 20) continue;

        $topNews = ['title' => $title, 'description' => $desc];
        break;
    }
}

if (!$topNews) {
    echo json_encode(['status'=>'error','message'=>'No news available']);
    exit;
}

// ─── Google Translate ─────────────────────────────────────
if (!function_exists('translateText')) {
function translateText($text, $targetLang) {
    if ($targetLang === 'en') return $text;
    $url = 'https://translate.googleapis.com/translate_a/single?' . http_build_query([
        'client'=>'gtx','sl'=>'auto','tl'=>$targetLang,'dt'=>'t','q'=>$text,
    ]);
    $ctx = stream_context_create(['http'=>['timeout'=>8,'ignore_errors'=>true,'header'=>"User-Agent: Mozilla/5.0\r\n"]]);
    $res = @file_get_contents($url, false, $ctx);
    if (!$res) return $text;
    $r = json_decode($res, true);
    if (!$r || !isset($r[0])) return $text;
    $out = '';
    foreach ($r[0] as $part) { if (isset($part[0])) $out .= $part[0]; }
    return $out ?: $text;
}
}

// ─── แปล ─────────────────────────────────────────────────
$translatedTitle = translateText($topNews['title'], $gtLang);
usleep(200000);
$translatedDesc  = translateText($topNews['description'], $gtLang);

// ─── Build message — title + detail ──────────────────────
$countryNameEN = $countryNamesEN[$country] ?? strtoupper($country);
if ($type === 'country') {
    $introEN = "Top news from {$countryNameEN} today:";
} else {
    $introEN = "Top world news today:";
}
$intro = translateText($introEN, $gtLang);

// รวม intro + title + detail เป็นประโยคเดียวกัน
$message = $intro . ' ' . $translatedTitle . '. ' . $translatedDesc;

$response = [
    'status'      => 'success',
    'message'     => $message,
    'title'       => $translatedTitle,
    'description' => $translatedDesc,
    'type'        => $type,
    'country'     => $country,
    'lang'        => $lang,
    'cached_until'=> date('Y-m-d H:i:s', mktime(0,0,0,date('n'),date('j')+1)),
];

@file_put_contents($cacheFile, json_encode($response, JSON_UNESCAPED_UNICODE));
echo json_encode($response, JSON_UNESCAPED_UNICODE);