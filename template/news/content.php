<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../lib/connect.php');
global $conn;

$perPage = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$supportedLangs = ['en', 'cn', 'jp', 'kr'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$subjectCol = 'subject_news' . ($lang !== 'th' ? '_' . $lang : '');
$descriptionCol = 'description_news' . ($lang !== 'th' ? '_' . $lang : '');
$contentCol = 'content_news' . ($lang !== 'th' ? '_' . $lang : '');

$totalQuery = "SELECT COUNT(DISTINCT dn.news_id) as total
                FROM dn_news dn
                WHERE dn.del = '0'";
if ($searchQuery) {
    $totalQuery .= " AND (dn.subject_news LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_news_en LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_news_cn LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_news_jp LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_news_kr LIKE '%" . $conn->real_escape_string($searchQuery) . "%')";
}

$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalItems = $totalRow['total'];
$totalPages = ceil($totalItems / $perPage);

$sql = "SELECT
            dn.news_id,
            dn.subject_news,
            dn.subject_news_en,
            dn.subject_news_cn,
            dn.subject_news_jp,
            dn.subject_news_kr,
            dn.description_news,
            dn.description_news_en,
            dn.description_news_cn,
            dn.description_news_jp,
            dn.description_news_kr,
            dn.content_news,
            dn.content_news_en,
            dn.content_news_cn,
            dn.content_news_jp,
            dn.content_news_kr,
            dn.date_create,
            GROUP_CONCAT(DISTINCT dnc.api_path) AS api_path
        FROM
            dn_news dn
        LEFT JOIN
            dn_news_doc dnc ON dn.news_id = dnc.news_id
                             AND dnc.del = '0'
                             AND dnc.status = '1'
        WHERE
            dn.del = '0'";

if ($searchQuery) {
    $sql .= " AND (dn.subject_news LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_news_en LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_news_cn LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_news_jp LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_news_kr LIKE '%" . $conn->real_escape_string($searchQuery) . "%')";
}

$sql .= "
GROUP BY dn.news_id
ORDER BY dn.date_create DESC
LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);

$boxesNews = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $title = $row[$subjectCol] ?: $row['subject_news'];
        $description = $row[$descriptionCol] ?: $row['description_news'];
        $content = $row[$contentCol] ?: $row['content_news'];

        $iframeSrc = null;
        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
        }

        $paths = !empty($row['api_path']) ? explode(',', $row['api_path']) : [];
        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        // ใช้รูปต้นฉบับจาก api_path โดยตรง
        $originalImage = !empty($paths) ? $paths[0] : null;

        $boxesNews[] = [
            'id' => $row['news_id'],
            'image' => $originalImage,
            'date_time' => $row['date_create'],
            'title' => $title,
            'description' => $description,
            'iframe' => $iframe
        ];
    }
} else {
    echo match ($lang) {
        'en' => 'No news found.',
        'cn' => '无新闻内容。',
        'jp' => 'ニュースが見つかりません。',
        'kr' => '뉴스를 찾을 수 없습니다.',
        default => 'ไม่พบข่าว',
    };
}
?>

<div class="content-news">
    <?php foreach ($boxesNews as $index => $box): ?>
        <div class="box-news">
            <div class="box-image">
                <?php $encodedId = urlencode(base64_encode($box['id'])); ?>
                <a href="?news_detail&id=<?php echo $encodedId; ?>&lang=<?php echo $lang; ?>" class="text-news">
                    <?php if(!empty($box['iframe'])): ?>
                        <iframe frameborder="0" src="<?= htmlspecialchars($box['iframe']); ?>" width="100%" height="100%" class="note-video-clip"></iframe>
                    <?php elseif (!empty($box['image'])): ?>
                        <img src="<?= htmlspecialchars($box['image']); ?>" alt="Image for <?= htmlspecialchars($box['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc;">No Image</div>
                    <?php endif; ?>
                </a>
            </div>
            <div class="box-content">
                <a href="?news_detail&id=<?php echo $encodedId; ?>&lang=<?php echo $lang; ?>" class="text-news">
                    <h5 class="line-clamp"><?= htmlspecialchars($box['title']); ?></h5>
                    <p class="line-clamp"><?= htmlspecialchars($box['description']); ?></p>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?news&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo $lang; ?>">
            <?php echo match ($lang) {
                'en' => 'Previous',
                'cn' => '上一页',
                'jp' => '前へ',
                'kr' => '이전',
                default => 'ก่อนหน้า',
            }; ?>
        </a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?news&page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo $lang; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?news&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo $lang; ?>">
            <?php echo match ($lang) {
                'en' => 'Next',
                'cn' => '下一页',
                'jp' => '次へ',
                'kr' => '다음',
                default => 'ถัดไป',
            }; ?>
        </a>
    <?php endif; ?>
</div>