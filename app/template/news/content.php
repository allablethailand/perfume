<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../lib/connect.php');
// ต้องมีการ include ไฟล์ base_directory.php ด้วย หากฟังก์ชัน ensureWebPNativeNews ยังคงใช้ global $base_path หรือเพื่อให้โค้ดสมบูรณ์ตามตัวอย่างแรก
// require_once(__DIR__ . '/../../../lib/base_directory.php'); 
global $conn;
// global $base_path; // ถ้าไม่ใช้ global $base_path ในไฟล์นี้ ก็ไม่จำเป็นต้องประกาศ

// ------------------------
// 1️⃣ Ensure WebP exists (คัดลอกจากโค้ดตัวอย่าง)
// ------------------------
function ensureWebPNativeNews($originalPath, $destDir = null, $quality = 80) {
    // ปรับปรุงการจัดการ path: โค้ดตัวอย่างมีการเพิ่ม "../" เพื่อให้อ้างอิงพาธจากไฟล์ที่เรียกใช้
    // หากไฟล์นี้อยู่ที่ /path/to/project/news/index.php 
    // และรูปภาพอยู่ที่ /path/to/project/uploads/img.jpg (pic_path เป็น uploads/img.jpg) 
    // การเพิ่ม "../" อาจทำให้พาธผิด
    // ถ้า $originalPath ที่มาจากฐานข้อมูลเป็นพาธสัมพัทธ์ที่ถูกต้อง เช่น 'uploads/news/image.jpg' 
    // และไฟล์นี้ต้องการเข้าถึงมันจากตำแหน่งของมันเอง, อาจต้องใช้พาธสัมบูรณ์หรือสัมพัทธ์ที่ถูกต้องกว่า

    // สำหรับโค้ดตัวอย่างแรกที่มี: $originalPath = preg_replace('#^(\.\./)+#', '', $originalPath); $originalPath = "../" . $originalPath;
    // เราจะใช้การจัดการพาธแบบเดิมเพื่อรักษาความเข้ากันได้
    $originalPath = preg_replace('#^(\.\./)+#', '', $originalPath);
    $originalPath = "../" . $originalPath; // สันนิษฐานว่าการเพิ่ม '../' ทำให้พาธเข้าถึงไฟล์รูปภาพได้ถูกต้องจากที่ที่ไฟล์นี้อยู่

    if (!file_exists($originalPath)) return $originalPath;

    // Use directory of original file if destination directory is not specified
    if ($destDir === null) $destDir = dirname($originalPath);

    $fileName = basename($originalPath);
    $destPath = rtrim($destDir, '/') . '/' . $fileName;
    // Replace extension with .webp
    $webpPath = preg_replace('/\.\w+$/', '.webp', $destPath);

    if (file_exists($webpPath)) return $webpPath;

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $info = getimagesize($originalPath);
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
    if (!function_exists('imagewebp') || !imagewebp($img, $webpPath, $quality)) { 
        imagedestroy($img); 
        return $originalPath; 
    }
    imagedestroy($img);

    // ตัด '../' ออกเมื่อส่งคืนพาธเพื่อให้ใช้งานในแท็ก <img> ได้
    return str_replace('../', '', $webpPath);
}

// ------------------------
// 2️⃣ Resize WebP dynamically (Modified for Aspect Ratio "Cover" - Crop-and-Resize) (คัดลอกจากโค้ดตัวอย่าง)
// ------------------------
function resizeWebPNews($srcPath, $targetWidth = null, $targetHeight = null, $quality = 80) {
    // การเรียกใช้ resizeWebPNews() ในโค้ดตัวอย่างแรกคาดหวังพาธที่สามารถเข้าถึงไฟล์ได้ (เช่น มี '../' นำหน้า)
    // ดังนั้นจึงต้องตรวจสอบและเพิ่ม '../' เข้าไปใหม่หากไม่มี (เนื่องจาก ensureWebPNativeNews ลบออกไปแล้ว)
    $internalSrcPath = (strpos($srcPath, '../') !== 0) ? '../' . $srcPath : $srcPath;

    if (!file_exists($internalSrcPath)) return $srcPath;

    $info = getimagesize($internalSrcPath);
    if (!$info) return $srcPath;

    list($origW, $origH) = $info;
    
    // If no target width or height is provided, return original path
    if ($targetWidth === null && $targetHeight === null) return $srcPath;

    // Use original dimensions if target is not specified, but this would not trigger resizing if both are null
    $targetWidth  = $targetWidth ?? $origW;
    $targetHeight = $targetHeight ?? $origH;
    
    // If target size is the same as original size, return original path
    if ($targetWidth == $origW && $targetHeight == $origH) return $srcPath;

    // สร้างพาธสำหรับจัดเก็บไฟล์ที่ปรับขนาดแล้ว (พาธสัมพัทธ์สำหรับการเข้าถึงไฟล์ภายใน)
    $destDir = dirname($internalSrcPath) . '/resized';
    // สร้างพาธสำหรับส่งคืนผลลัพธ์ (พาธสำหรับแสดงผลบนเว็บ)
    $destDirWeb = dirname($srcPath) . '/resized';

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $fileName      = basename($srcPath);
    $fileNameNoExt = preg_replace('/\.\w+$/', '', $fileName);
    
    // พาธเต็มรูปแบบสำหรับการบันทึกไฟล์ (ภายใน)
    $resizedPath = $destDir . '/' . $fileNameNoExt . "-{$targetWidth}x{$targetHeight}.webp";
    // พาธเต็มรูปแบบสำหรับการแสดงผลบนเว็บ
    $resizedPathWeb = $destDirWeb . '/' . $fileNameNoExt . "-{$targetWidth}x{$targetHeight}.webp";

    if (file_exists($resizedPath)) return $resizedPathWeb; // ถ้ามีอยู่แล้วให้ส่งคืนพาธสำหรับเว็บ

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($internalSrcPath); break;
        case 'image/png':  $img = imagecreatefrompng($internalSrcPath); break;
        case 'image/gif':  $img = imagecreatefromgif($internalSrcPath); break;
        case 'image/webp': $img = imagecreatefromwebp($internalSrcPath); break;
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

    // Handle transparency for PNG and GIF
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

    return $resizedPathWeb; // ส่งคืนพาธสำหรับแสดงผลบนเว็บ
}

// ------------------------
// 3️⃣ Merged function (คัดลอกจากโค้ดตัวอย่าง)
// ------------------------
function ensureWebPAndResizeNews($originalPath, $width = null, $height = null, $quality = 80) {
    // 1. Convert to WebP (if necessary)
    // ensureWebPNativeNews จะส่งคืนพาธสำหรับเว็บ (ไม่มี '../' นำหน้า)
    $webpPath = ensureWebPNativeNews($originalPath, null, $quality);
    
    // 2. Resize/Crop (if dimensions are specified)
    if ($width !== null || $height !== null) {
        // resizeWebPNews จะรับพาธสำหรับเว็บ และจัดการเพิ่ม/ลด '../' ภายในฟังก์ชันเอง
        $webpPath = resizeWebPNews($webpPath, $width, $height, $quality);
    }
    return $webpPath;
}

// โค้ดส่วนดึงข้อมูลข่าว...
// ... (ส่วนที่ยังไม่เปลี่ยนแปลง)

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
            GROUP_CONCAT(DISTINCT dnc.file_name) AS file_name,
            GROUP_CONCAT(DISTINCT dnc.file_path) AS pic_path
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

        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];
        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        // ------------------------
        // ⭐ เรียกใช้ฟังก์ชันแปลงและปรับขนาด/ครอบตัด ⭐
        // ------------------------
        // กำหนดขนาดที่ต้องการสำหรับรูปภาพข่าว (สมมติ 350x240 พิกเซล)
        $processedImage = !empty($paths) ? ensureWebPAndResizeNews($paths[0], 350, 240) : null;
        // ------------------------

        $boxesNews[] = [
            'id' => $row['news_id'],
            'image' => $processedImage, // ⭐ ใช้พาธรูปภาพที่ถูกประมวลผลแล้ว
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
                    'en' => 'Search news...',
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
    <?php foreach ($boxesNews as $index => $box): ?>
        <div class="box-news">
            <div class="box-image">
                <?php $encodedId = urlencode(base64_encode($box['id'])); ?>
                <a href="news_detail.php?id=<?php echo $encodedId; ?>&lang=<?php echo $lang; ?>" class="text-news">
                    <?php if(!empty($box['iframe'])): ?>
                        <iframe frameborder="0" src="<?= htmlspecialchars($box['iframe']); ?>" width="100%" height="100%" class="note-video-clip"></iframe>
                    <?php elseif (!empty($box['image'])): ?>
                        <picture>
                            <source srcset="<?= htmlspecialchars($box['image']); ?>" type="image/webp">
                            <img src="<?= htmlspecialchars($box['image']); ?>" alt="Image for <?= htmlspecialchars($box['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                        </picture>
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc;">No Image</div>
                    <?php endif; ?>
                </a>
            </div>
            <div class="box-content">
                <a href="news_detail.php?id=<?php echo $encodedId; ?>&lang=<?php echo $lang; ?>" class="text-news">
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