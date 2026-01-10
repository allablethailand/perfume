<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../lib/connect.php');
// ต้องมีการ include ไฟล์ที่เก็บ base_directory.php หรือกำหนดค่า $base_path ที่นี่
// require_once(__DIR__ . '/../../../lib/base_directory.php'); 
global $conn;
// global $base_path; // สมมติว่าไฟล์นี้ไม่ได้ใช้ $base_path โดยตรงในฟังก์ชัน

// ------------------------
// 1️⃣ Ensure WebP exists
// ------------------------
function ensureWebPNativeB($originalPath, $destDir = null, $quality = 80) {
    // Sanitize path for security and fix relative path to be absolute within the context
    // ตรรกะนี้อาจต้องปรับปรุงหาก /../../../lib/connect.php ไม่ใช่จุดเริ่มต้นที่ถูกต้อง
    $originalPath = preg_replace('#^(\.\./)+#', '', $originalPath);
    // เนื่องจาก path ใน db เป็น api_path ที่น่าจะ relative อยู่แล้ว จึงเติม ../
    // เพื่อให้เข้าถึงไฟล์ได้ถูกต้องจากตำแหน่งของไฟล์ปัจจุบัน (สมมติว่าไฟล์นี้อยู่ในระดับเดียวกัน)
    $originalPath = "../" . $originalPath;

    if (!file_exists($originalPath)) return $originalPath;

    // Use directory of original file if destination directory is not specified
    if ($destDir === null) $destDir = dirname($originalPath);

    $fileName = basename($originalPath);
    $destPath = rtrim($destDir, '/') . '/' . $fileName;
    // Replace extension with .webp
    $webpPath = preg_replace('/\.\w+$/', '.webp', $destPath);

    if (file_exists($webpPath)) return $webpPath;

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $info = @getimagesize($originalPath); // ใช้ @ ป้องกัน warning หากไฟล์เสียหาย
    if (!$info) return $originalPath;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($originalPath); break;
        case 'image/png':  $img = imagecreatefrompng($originalPath); break;
        case 'image/gif':  $img = imagecreatefromgif($originalPath); break;
        default: return $originalPath;
    }

    if (!$img) return $originalPath;
    
    // Save as WebP
    if (!imagewebp($img, $webpPath, $quality)) { 
        imagedestroy($img); 
        return $originalPath; 
    }
    imagedestroy($img);

    return $webpPath;
}

// ------------------------
// 2️⃣ Resize WebP dynamically (Modified for Aspect Ratio "Cover" - Crop-and-Resize)
// ------------------------
function resizeWebPB($srcPath, $targetWidth = null, $targetHeight = null, $quality = 80) {
    if (!file_exists($srcPath)) return $srcPath;

    $info = @getimagesize($srcPath);
    if (!$info) return $srcPath;

    list($origW, $origH) = $info;
    
    // If no target width or height is provided, return original path
    if ($targetWidth === null && $targetHeight === null) return $srcPath;

    // Use original dimensions if target is not specified, but this would not trigger resizing if both are null
    $targetWidth  = $targetWidth ?? $origW;
    $targetHeight = $targetHeight ?? $origH;
    
    // If target size is the same as original size, return original path
    if ($targetWidth == $origW && $targetHeight == $origH) return $srcPath;

    $destDir = dirname($srcPath) . '/resized';
    // ตรวจสอบและสร้างโฟลเดอร์สำหรับไฟล์ที่ถูก resize
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $fileName      = basename($srcPath);
    $fileNameNoExt = preg_replace('/\.\w+$/', '', $fileName);
    $resizedPath = $destDir . '/' . $fileNameNoExt . "-{$targetWidth}x{$targetHeight}.webp";

    if (file_exists($resizedPath)) return $resizedPath;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($srcPath); break;
        case 'image/png':  $img = imagecreatefrompng($srcPath); break;
        case 'image/gif':  $img = imagecreatefromgif($srcPath); break;
        case 'image/webp': $img = imagecreatefromwebp($srcPath); break;
        default: return $srcPath;
    }

    if (!$img) return $srcPath;

    // --- Crop-and-Resize (Aspect Ratio "Cover") Logic ---
    $widthRatio  = $targetWidth / $origW;
    $heightRatio = $targetHeight / $origH;

    // Determine the scaling ratio that will 'cover' the target dimensions
    $ratio = max($widthRatio, $heightRatio);

    // Calculate the dimensions and position of the source rectangle to crop
    $newW = $origW * $ratio;
    $newH = $origH * $ratio;

    $srcX = ($newW - $targetWidth) / 2 / $ratio;
    $srcY = ($newH - $targetHeight) / 2 / $ratio;
    
    $srcW = $origW - (2 * $srcX);
    $srcH = $origH - (2 * $srcY);

    // Create the new canvas
    $resizedImg = imagecreatetruecolor($targetWidth, $targetHeight);

    // Handle transparency for PNG, GIF and WebP
    if (in_array($mime, ['image/png', 'image/gif', 'image/webp'])) {
        imagecolortransparent($resizedImg, imagecolorallocatealpha($resizedImg, 0, 0, 0, 127));
        imagealphablending($resizedImg, false);
        imagesavealpha($resizedImg, true);
    }

    // Resample the cropped area onto the new canvas
    imagecopyresampled($resizedImg, $img, 
        0, 0, // Destination coordinates (start at top-left of the new image)
        (int)$srcX, (int)$srcY, // Source coordinates (start at the calculated crop point)
        $targetWidth, $targetHeight, // Destination width and height
        (int)$srcW, (int)$srcH // Source width and height (the cropped area)
    );

    // Save the new WebP image
    imagewebp($resizedImg, $resizedPath, $quality);

    imagedestroy($img);
    imagedestroy($resizedImg);

    return $resizedPath;
}

// ------------------------
// 3️⃣ Merged function
// ------------------------
function ensureWebPAndResizeB($originalPath, $width = null, $height = null, $quality = 80) {
    // 1. Convert to WebP (if necessary)
    $webpPath = ensureWebPNativeB($originalPath, null, $quality);
    
    // 2. Resize/Crop (if dimensions are specified)
    if ($width !== null || $height !== null) {
        $webpPath = resizeWebPB($webpPath, $width, $height, $quality);
    }
    return $webpPath;
}

// ------------------------
// ดึงข้อมูลบล็อก (Fetch blog data)
// ------------------------
$perPage = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;
$supportedLangs = ['en', 'cn', 'jp', 'kr'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$subjectCol = 'subject_blog';
$descriptionCol = 'description_blog';
$contentCol = 'content_blog';
if ($lang !== 'th') {
    $subjectCol .= '_' . $lang;
    $descriptionCol .= '_' . $lang;
    $contentCol .= '_' . $lang;
}

$totalQuery = "SELECT COUNT(DISTINCT dn.blog_id) as total
                FROM dn_blog dn
                WHERE dn.del = '0'";
if ($searchQuery) {

    $totalQuery .= " AND (dn.subject_blog LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
    foreach ($supportedLangs as $slang) {
        $totalQuery .= " OR dn.subject_blog_" . $slang . " LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
    }
    $totalQuery .= ")";
}

$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalItems = $totalRow['total'];
$totalPages = ceil($totalItems / $perPage);

$sql = "SELECT
            dn.blog_id,
            dn.subject_blog,
            dn.subject_blog_en,
            dn.subject_blog_cn,
            dn.subject_blog_jp,
            dn.subject_blog_kr,
            dn.description_blog,
            dn.description_blog_en,
            dn.description_blog_cn,
            dn.description_blog_jp,
            dn.description_blog_kr,
            dn.content_blog,
            dn.content_blog_en,
            dn.content_blog_cn,
            dn.content_blog_jp,
            dn.content_blog_kr,
            dn.date_create,
            GROUP_CONCAT(DISTINCT dnc.file_name) AS file_name,
            GROUP_CONCAT(DISTINCT dnc.file_path) AS pic_path
        FROM
            dn_blog dn
        LEFT JOIN
            dn_blog_doc dnc ON dn.blog_id = dnc.blog_id
                             AND dnc.del = '0'
                             AND dnc.status = '1'
        WHERE
            dn.del = '0'";

if ($searchQuery) {
    $sql .= "
    AND (dn.subject_blog LIKE '%" . $conn->real_escape_string($searchQuery) . "%' ";
    foreach ($supportedLangs as $slang) {
        $sql .= " OR dn.subject_blog_" . $slang . " LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
    }
    $sql .= ")";
}

$sql .= "
GROUP BY dn.blog_id
ORDER BY dn.date_create DESC
LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);

$boxesNews = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $displaySubject = $row[$subjectCol] ?: $row['subject_blog'];
        $displayDescription = $row[$descriptionCol] ?: $row['description_blog'];
        $displayContent = $row[$contentCol] ?: $row['content_blog'];

        $iframeSrc = null;
        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $displayContent, $matches)) {
            $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
        }

        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];
        $files = !empty($row['file_name']) ? explode(',', $row['file_name']) : [];

        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        // ------------------------
        // แปลงภาพเป็น WebP อัตโนมัติ (และ Crop/Resize แบบคงสัดส่วน)
        // กำหนดขนาดที่ต้องการ เช่น 400x250 สำหรับภาพ thumbnail ของ Blog
        // ------------------------
        $mainImage = !empty($paths) ? ensureWebPAndResizeB($paths[0], 400, height: 250) : null;


        $boxesNews[] = [
            'id' => $row['blog_id'],
            // ใช้ตัวแปรที่ถูกแปลงแล้ว
            'image' => $mainImage,
            'date_time' => $row['date_create'],
            'title' => $displaySubject,
            'description' => $displayDescription,
            'iframe' => $iframe
        ];
    }
} else {
    echo ($lang === 'en' ? 'No blog found.' : ($lang === 'cn' ? '无博客内容。' : ($lang === 'jp' ? 'ブログが見つかりません。' : ($lang === 'kr' ? '블로그를 찾을 수 없습니다.' : 'ไม่พบบทความ'))));
}
?>

<div style="display: flex; justify-content: space-between;">
    <div></div>
    <div>
        <form method="GET" action="">
            <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
            <div class="input-group">
                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="<?= $lang === 'en' ? 'Search blog...' : ($lang === 'cn' ? '搜索文章...' : ($lang === 'jp' ? 'ブログを検索...' : ($lang === 'kr' ? '블로그 검색...' : 'ค้นหาบทความ...'))); ?>">
                <button class="btn-search" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="content-news">
    <?php foreach ($boxesNews as $index => $box): ?>
        <div class="box-news">
            <div class="box-image">
                <?php $encodedId = urlencode(base64_encode($box['id'])); ?>
                <a href="Blog_detail.php?id=<?php echo $encodedId; ?>&lang=<?php echo htmlspecialchars($lang); ?>" class="text-news">
                    <?php if(!empty($box['iframe'])): ?>
                        <iframe frameborder="0" src="<?= htmlspecialchars($box['iframe']); ?>" width="100%" height="100%" class="note-video-clip"></iframe>
                    <?php elseif (!empty($box['image'])): ?>
                        <picture>
                            <source srcset="<?= htmlspecialchars($box['image']) ?>" type="image/webp">
                            <img src="<?= htmlspecialchars($box['image']); ?>" alt="Image for <?= htmlspecialchars($box['title']); ?>">
                        </picture>
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc;">No Image</div>
                    <?php endif; ?>
                </a>
            </div>
            <div class="box-content">
                <a href="Blog_detail.php?id=<?php echo $encodedId; ?>&lang=<?php echo htmlspecialchars($lang); ?>" class="text-news">
                    <h5 class="line-clamp"><?= htmlspecialchars($box['title']); ?></h5>
                    <p class="line-clamp"><?= htmlspecialchars($box['description']); ?></p>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>


<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?>">
            <?= $lang === 'en' ? 'Previous' : ($lang === 'cn' ? '上一页' : ($lang === 'jp' ? '前へ' : ($lang === 'kr' ? '이전' : 'ก่อนหน้า'))); ?>
        </a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?>">
            <?= $lang === 'en' ? 'Next' : ($lang === 'cn' ? '下一页' : ($lang === 'jp' ? '次へ' : ($lang === 'kr' ? '다음' : 'ถัดไป'))); ?>
        </a>
    <?php endif; ?>
</div>