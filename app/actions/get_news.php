<?php
/**
 * get_news.php
 * ดึงข่าวจาก RSS Feed หลายแหล่ง (ฟรี ไม่ต้อง API key)
 * → แปลด้วย Google Translate (ฟรี)
 * ✅ Filter title ที่เป็น error/garbage ออก
 * ✅ Fallback หลายแหล่งถ้าแหล่งแรก fail
 */

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');

define('CACHE_DIR', __DIR__ . '/../../../../cache/news/');
define('CACHE_TTL', 1800);

$type    = $_GET['type']    ?? 'world';
$country = $_GET['country'] ?? 'th';
$lang    = $_GET['lang']    ?? 'th';

$gtLangMap = ['th'=>'th','en'=>'en','cn'=>'zh-CN','jp'=>'ja','kr'=>'ko'];
$gtLang    = $gtLangMap[$lang] ?? 'th';


// ─── RSS Feed map ─────────────────────────────────────────────────
// แต่ละประเทศมีหลาย feed — ลองตามลำดับจนกว่าจะได้ข่าว
$feedMap = [
    'world' => [
        'https://feeds.bbci.co.uk/news/world/rss.xml',
        'https://www.theguardian.com/world/rss',
        'https://rss.nytimes.com/services/xml/rss/nyt/World.xml',
        'https://www.aljazeera.com/xml/rss/all.xml',
        'https://abcnews.go.com/abcnews/internationalheadlines',
    ],
    'th' => [
        'https://www.bangkokpost.com/rss/data/topstories.xml',
        'https://www.nationthailand.com/rss',
        'https://feeds.bbci.co.uk/news/world/asia/rss.xml',
        // Google News search keyword fallback
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
];

// Default fallback สำหรับประเทศที่ไม่มีใน map
$countryNamesEN = [
    'th'=>'Thailand',  'us'=>'United States', 'gb'=>'United Kingdom',
    'jp'=>'Japan',     'cn'=>'China',         'kr'=>'South Korea',
    'sg'=>'Singapore', 'au'=>'Australia',     'de'=>'Germany',
    'fr'=>'France',    'in'=>'India',         'id'=>'Indonesia',
    'my'=>'Malaysia',  'ph'=>'Philippines',   'vn'=>'Vietnam',
    'mm'=>'Myanmar',   'kh'=>'Cambodia',
];

// ─── Cache ────────────────────────────────────────────────────────
if (!is_dir(CACHE_DIR)) { @mkdir(CACHE_DIR, 0755, true); }
$cacheKey  = md5("rss2_{$type}_{$country}_{$lang}");
$cacheFile = CACHE_DIR . $cacheKey . '.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TTL) {
    echo file_get_contents($cacheFile);
    exit;
}

// ─── เลือก feed URLs ──────────────────────────────────────────────
if ($type === 'world') {
    $feedUrls = $feedMap['world'];
} else {
    $feedUrls = $feedMap[$country] ?? [];
    // ถ้าไม่มี map → fallback Google News search
    if (empty($feedUrls)) {
        $kw = $countryNamesEN[$country] ?? strtoupper($country);
        $feedUrls = [
            "https://news.google.com/rss/search?q=" . urlencode($kw . " news") . "&hl=en",
            "https://feeds.bbci.co.uk/news/world/rss.xml",
        ];
    }
}

// ─── Fetch + Parse RSS ────────────────────────────────────────────
function fetchRSS($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout'         => 10,
            'ignore_errors'   => true,
            'follow_location' => true,
            'max_redirects'   => 3,
            'header'          => implode("\r\n", [
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120",
                "Accept: application/rss+xml, application/xml, text/xml, */*",
                "Accept-Language: en-US,en;q=0.9",
                "Cache-Control: no-cache",
            ]) . "\r\n",
        ]
    ]);
    return @file_get_contents($url, false, $ctx);
}

function isValidTitle($title, $errorPatterns) {
    if (empty($title) || strlen($title) < 8) return false;
    $lower = mb_strtolower($title);
    foreach ($errorPatterns as $pattern) {
        if (preg_match('/' . $pattern . '/iu', $lower)) return false;
    }
    return true;
}

// ✅ ประกาศ $errorPatterns ตรงนี้ ก่อน loop — ให้ scope ถูกต้อง
$errorPatterns = [
    'feed.*not.*available', 'ฟีดนี้ไม่', 'this feed.*not',
    'unavailable', '^error$', 'access denied', 'forbidden',
    '^404$', 'not found', 'javascript.*required',
    'enable javascript', 'please enable', 'subscribe to',
    'sign in to', 'log in to',
];

$headlines = [];

foreach ($feedUrls as $feedUrl) {
    if (count($headlines) >= 5) break;

    $raw = fetchRSS($feedUrl);
    if (!$raw || strlen($raw) < 100) continue;

    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
    if (!$xml) continue;

    $items = [];
    if (isset($xml->channel->item))  $items = $xml->channel->item;  // RSS 2.0
    elseif (isset($xml->entry))       $items = $xml->entry;          // Atom

    foreach ($items as $item) {
        if (count($headlines) >= 5) break;

        $title = trim((string)($item->title ?? ''));
        // ตัด " - Source" ท้าย
        $title = preg_replace('/\s*[-–|]\s*[^-–|]{2,60}$/', '', $title);
        $title = trim($title);

        if (isValidTitle($title, $errorPatterns)) {
            $headlines[] = $title;
        }
    }
}

if (empty($headlines)) {
    error_log("get_news.php: No valid headlines | type=$type country=$country feeds=" . implode(',', $feedUrls));
    echo json_encode(['status'=>'error','message'=>'No news available']);
    exit;
}

// ─── Google Translate (ฟรี) — sl=auto detect ภาษา source เอง ────
function translateText($text, $targetLang) {
    if ($targetLang === 'en') return $text; // ไม่ต้องแปลถ้า target เป็น EN

    $url = 'https://translate.googleapis.com/translate_a/single?' . http_build_query([
        'client' => 'gtx',
        'sl'     => 'auto',  // ✅ auto detect — รองรับ JP, KR, CN, TH ฯลฯ
        'tl'     => $targetLang,
        'dt'     => 't',
        'q'      => $text,
    ]);
    $ctx = stream_context_create([
        'http' => ['timeout'=>8, 'ignore_errors'=>true, 'header'=>"User-Agent: Mozilla/5.0\r\n"]
    ]);
    $response = @file_get_contents($url, false, $ctx);
    if (!$response) return $text;
    $result = json_decode($response, true);
    if (!$result || !isset($result[0])) return $text;
    $translated = '';
    foreach ($result[0] as $part) { if (isset($part[0])) $translated .= $part[0]; }
    return $translated ?: $text;
}

// ─── แปล headlines ───────────────────────────────────────────────
$translatedHeadlines = [];
foreach ($headlines as $h) {
    // ✅ ไม่ต้องระบุ sourceLang — Google auto detect เอง
    $translatedHeadlines[] = translateText($h, $gtLang);
    usleep(150000);
}

// ─── Build message ────────────────────────────────────────────────
$countryNameEN = $countryNamesEN[$country] ?? strtoupper($country);
$introEN = ($type === 'country')
    ? "Here are today's top headlines from {$countryNameEN}."
    : "Here are today's top world headlines.";
$intro   = translateText($introEN, $gtLang);
$message = $intro . ' ' . implode(' ', $translatedHeadlines);

$response = [
    'status'    => 'success',
    'message'   => $message,
    'headlines' => $translatedHeadlines,
    'type'      => $type,
    'country'   => $country,
    'lang'      => $lang,
];

@file_put_contents($cacheFile, json_encode($response, JSON_UNESCAPED_UNICODE));
echo json_encode($response, JSON_UNESCAPED_UNICODE);