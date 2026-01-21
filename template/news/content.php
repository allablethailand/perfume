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

// รับค่าการค้นหาจาก parameter 's' แทน 'search'
$searchQuery = isset($_GET['s']) ? trim($_GET['s']) : '';

$subjectCol = 'subject_news' . ($lang !== 'th' ? '_' . $lang : '');
$descriptionCol = 'description_news' . ($lang !== 'th' ? '_' . $lang : '');
$contentCol = 'content_news' . ($lang !== 'th' ? '_' . $lang : '');

// สร้าง WHERE clause สำหรับการค้นหา
$searchWhere = '';
if ($searchQuery !== '') {
    $searchTerm = $conn->real_escape_string($searchQuery);
    $searchWhere = " AND (
        dn.subject_news LIKE '%{$searchTerm}%' OR 
        dn.subject_news_en LIKE '%{$searchTerm}%' OR 
        dn.subject_news_cn LIKE '%{$searchTerm}%' OR 
        dn.subject_news_jp LIKE '%{$searchTerm}%' OR 
        dn.subject_news_kr LIKE '%{$searchTerm}%' OR
        dn.description_news LIKE '%{$searchTerm}%' OR 
        dn.description_news_en LIKE '%{$searchTerm}%' OR 
        dn.description_news_cn LIKE '%{$searchTerm}%' OR 
        dn.description_news_jp LIKE '%{$searchTerm}%' OR 
        dn.description_news_kr LIKE '%{$searchTerm}%' OR
        dn.content_news LIKE '%{$searchTerm}%' OR 
        dn.content_news_en LIKE '%{$searchTerm}%' OR 
        dn.content_news_cn LIKE '%{$searchTerm}%' OR 
        dn.content_news_jp LIKE '%{$searchTerm}%' OR 
        dn.content_news_kr LIKE '%{$searchTerm}%'
    )";
}

$totalQuery = "SELECT COUNT(DISTINCT dn.news_id) as total
                FROM dn_news dn
                WHERE dn.del = '0'{$searchWhere}";

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
            dn.del = '0'{$searchWhere}
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
    echo '<div style="text-align: center; padding: 60px 20px; color: #666;">';
    if ($searchQuery !== '') {
        echo match ($lang) {
            'en' => 'No news found for "' . htmlspecialchars($searchQuery) . '"',
            'cn' => '未找到 "' . htmlspecialchars($searchQuery) . '" 的新闻',
            'jp' => '"' . htmlspecialchars($searchQuery) . '" のニュースが見つかりません',
            'kr' => '"' . htmlspecialchars($searchQuery) . '"에 대한 뉴스를 찾을 수 없습니다',
            default => 'ไม่พบข่าวสำหรับ "' . htmlspecialchars($searchQuery) . '"',
        };
    } else {
        echo match ($lang) {
            'en' => 'No news found.',
            'cn' => '无新闻内容。',
            'jp' => 'ニュースが見つかりません。',
            'kr' => '뉴스를 찾을 수 없습니다.',
            default => 'ไม่พบข่าว',
        };
    }
    echo '</div>';
}
?>

<?php if (!empty($boxesNews)): ?>
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

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?news&page=<?php echo $page - 1; ?><?php echo $searchQuery ? '&s=' . urlencode($searchQuery) : ''; ?>&lang=<?php echo $lang; ?>">
            <?php echo match ($lang) {
                'en' => 'Previous',
                'cn' => '上一页',
                'jp' => '前へ',
                'kr' => '이전',
                default => 'ก่อนหน้า',
            }; ?>
        </a>
    <?php endif; ?>

    <?php 
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    
    if ($startPage > 1): ?>
        <a href="?news&page=1<?php echo $searchQuery ? '&s=' . urlencode($searchQuery) : ''; ?>&lang=<?php echo $lang; ?>">1</a>
        <?php if ($startPage > 2): ?>
            <span style="padding: 0 10px; color: #999;">...</span>
        <?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <a href="?news&page=<?php echo $i; ?><?php echo $searchQuery ? '&s=' . urlencode($searchQuery) : ''; ?>&lang=<?php echo $lang; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($endPage < $totalPages): ?>
        <?php if ($endPage < $totalPages - 1): ?>
            <span style="padding: 0 10px; color: #999;">...</span>
        <?php endif; ?>
        <a href="?news&page=<?php echo $totalPages; ?><?php echo $searchQuery ? '&s=' . urlencode($searchQuery) : ''; ?>&lang=<?php echo $lang; ?>"><?php echo $totalPages; ?></a>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?news&page=<?php echo $page + 1; ?><?php echo $searchQuery ? '&s=' . urlencode($searchQuery) : ''; ?>&lang=<?php echo $lang; ?>">
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
<?php endif; ?>
<?php endif; ?>