<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../lib/connect.php');
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

$subjectCol = 'subject_idia' . ($lang !== 'th' ? '_' . $lang : '');
$descriptionCol = 'description_idia' . ($lang !== 'th' ? '_' . $lang : '');
$contentCol = 'content_idia' . ($lang !== 'th' ? '_' . $lang : '');

$totalQuery = "SELECT COUNT(DISTINCT dn.idia_id) as total
                FROM dn_idia dn
                WHERE dn.del = '0'"; 
if ($searchQuery) {
    $totalQuery .= " AND (dn.subject_idia LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_idia_en LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_idia_cn LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_idia_jp LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_idia_kr LIKE '%" . $conn->real_escape_string($searchQuery) . "%')";
}

$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalItems = $totalRow['total'];
$totalPages = ceil($totalItems / $perPage);

$sql = "SELECT
            dn.idia_id,
            dn.subject_idia,
            dn.subject_idia_en,
            dn.subject_idia_cn,
            dn.subject_idia_jp,
            dn.subject_idia_kr,
            dn.description_idia,
            dn.description_idia_en,
            dn.description_idia_cn,
            dn.description_idia_jp,
            dn.description_idia_kr,
            dn.content_idia,
            dn.content_idia_en,
            dn.content_idia_cn,
            dn.content_idia_jp,
            dn.content_idia_kr,
            dn.date_create,
            GROUP_CONCAT(DISTINCT dnc.file_name) AS file_name,
            GROUP_CONCAT(DISTINCT dnc.api_path) AS pic_path
        FROM
            dn_idia dn
        LEFT JOIN
            dn_idia_doc dnc ON dn.idia_id = dnc.idia_id
                             AND dnc.del = '0'
                             AND dnc.status = '1'
        WHERE
            dn.del = '0'";

if ($searchQuery) {
    $sql .= " AND (dn.subject_idia LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_idia_en LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_idia_cn LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_idia_jp LIKE '%" . $conn->real_escape_string($searchQuery) . "%' OR dn.subject_idia_kr LIKE '%" . $conn->real_escape_string($searchQuery) . "%')";
}

$sql .= "
GROUP BY dn.idia_id
ORDER BY dn.date_create DESC
LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);

$boxesidia = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $title = $row[$subjectCol] ?: $row['subject_idia'];
        $description = $row[$descriptionCol] ?: $row['description_idia'];
        $content = $row[$contentCol] ?: $row['content_idia'];

        $iframeSrc = null;
        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
        }

        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];

        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        $boxesidia[] = [
            'id' => $row['idia_id'],
            'image' => !empty($paths) ? $paths[0] : null,
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

<div style="display: flex; justify-content: space-between;">
    <div></div>
    <div>
        <form method="GET" action="">
            <div class="input-group">
                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="<?php echo match ($lang) {
                    'en' => 'Search idia...',
                    'cn' => '搜索新闻...',
                    'jp' => 'ニュースを検索...',
                    'kr' => '뉴스 검색...',
                    default => 'ค้นหาข่าว...',
                }; ?>">
                <button class="btn-search" type="submit"><i class="fas fa-search"></i></button>
            </div>
            <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
        </form>
    </div>
</div>

<div class="content-news">
    <?php foreach ($boxesidia as $index => $box): ?>
        <div class="box-news">
            <div class="box-image">
                <?php $encodedId = urlencode(base64_encode($box['id'])); ?>
                <a href="idia_detail.php?id=<?php echo $encodedId; ?>&lang=<?php echo $lang; ?>" class="text-news">
                    <?php if(!empty($box['iframe'])): ?>
                        <iframe frameborder="0" src="<?= htmlspecialchars($box['iframe']); ?>" width="100%" height="100%" class="note-video-clip"></iframe>
                    <?php elseif (!empty($box['image'])): ?>
                        <img src="<?= htmlspecialchars($box['image']); ?>" alt="Image for <?= htmlspecialchars($box['title']); ?>">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc;">No Image</div>
                    <?php endif; ?>
                </a>
            </div>
            <div class="box-content">
                <a href="idia_detail.php?id=<?php echo $encodedId; ?>&lang=<?php echo $lang; ?>" class="text-news">
                    <h5 class="line-clamp"><?= htmlspecialchars($box['title']); ?></h5>
                    <p class="line-clamp"><?= htmlspecialchars($box['description']); ?></p>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo $lang; ?>">
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
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo $lang; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo $lang; ?>">
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