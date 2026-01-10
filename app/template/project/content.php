<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../lib/connect.php');
// ต้องมีการเรียกใช้ไฟล์ base_directory.php เพื่อให้ global $base_path ใช้งานได้
// แม้ว่าฟังก์ชัน ensureWebPNativePj จะไม่ได้ใช้ $base_path ตรงๆ แต่ควรรักษารูปแบบ dependency ไว้
require_once(__DIR__ . '/../../../lib/base_directory.php');
global $conn;
global $base_path; // เพิ่ม global variable นี้ตามโค้ดตัวอย่าง

// ------------------------
// 1️⃣ Ensure WebP exists
// ------------------------
/**
 * ตรวจสอบและแปลงรูปภาพต้นฉบับ (JPG, PNG, GIF) เป็น WebP หากยังไม่มีไฟล์ WebP อยู่
 * โดยใช้ฟังก์ชัน GD Native ของ PHP (imagewebp)
 * * @param string $originalPath พาธสัมพัทธ์ของไฟล์รูปภาพต้นฉบับ (เช่น 'upload/img/file.jpg')
 * @param string|null $destDir ไดเรกทอรีปลายทางสำหรับไฟล์ WebP (ค่าเริ่มต้นคือไดเรกทอรีเดียวกับไฟล์ต้นฉบับ)
 * @param int $quality คุณภาพของ WebP (0-100)
 * @return string พาธของไฟล์ WebP ที่ถูกสร้างขึ้น หรือพาธไฟล์ต้นฉบับหากเกิดข้อผิดพลาด
 */
function ensureWebPNativePj($originalPath, $destDir = null, $quality = 80) {
    // Sanitize path for security and fix relative path to be absolute within the context
    // ลบการนำทางแบบ '..' ซ้ำซ้อน และเพิ่ม '../' นำหน้าเพื่อให้เข้าถึงไฟล์ได้ถูกต้องตามโครงสร้างที่โค้ดแรกใช้
    $originalPath = preg_replace('#^(\.\./)+#', '', $originalPath);
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
/**
 * ปรับขนาดและครอปรูปภาพ WebP หรือรูปภาพอื่นๆ ที่รองรับ ให้เป็นขนาดที่กำหนด 
 * โดยใช้หลักการ "Cover" (ครอปส่วนเกินออกเพื่อให้ภาพเต็มพื้นที่เป้าหมาย)
 * * @param string $srcPath พาธสัมพัทธ์ของไฟล์รูปภาพต้นฉบับ (ควรเป็นไฟล์ WebP จาก ensureWebPNativePj แล้ว)
 * @param int|null $targetWidth ความกว้างเป้าหมาย
 * @param int|null $targetHeight ความสูงเป้าหมาย
 * @param int $quality คุณภาพของ WebP (0-100)
 * @return string พาธของไฟล์ WebP ที่ถูกปรับขนาด หรือพาธไฟล์ต้นฉบับหากเกิดข้อผิดพลาด
 */
function resizeWebPPj($srcPath, $targetWidth = null, $targetHeight = null, $quality = 80) {
    if (!file_exists($srcPath)) return $srcPath;

    $info = getimagesize($srcPath);
    if (!$info) return $srcPath;

    list($origW, $origH) = $info;
    
    // If no target width or height is provided, return original path
    if ($targetWidth === null && $targetHeight === null) return $srcPath;

    // Use original dimensions if target is not specified, but this would not trigger resizing if both are null
    $targetWidth  = $targetWidth ?? $origW;
    $targetHeight = $targetHeight ?? $origH;
    
    // If target size is the same as original size, return original path
    if ($targetWidth == $origW && $targetHeight == $origH) return $srcPath;

    // สร้างโฟลเดอร์ 'resized' ในไดเรกทอรีของไฟล์ต้นฉบับ
    $destDir = dirname($srcPath) . '/resized';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $fileName      = basename($srcPath);
    // Remove extension to build the new file name
    $fileNameNoExt = preg_replace('/\.\w+$/', '', $fileName);
    // New path includes dimensions for uniqueness
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

    // Determine the scaling ratio that will 'cover' the target dimensions (max ratio)
    $ratio = max($widthRatio, $heightRatio);

    // Calculate the dimensions and position of the source rectangle to crop
    // newW/newH are the dimensions the original image WOULD have if scaled to cover the target
    $newW = $origW * $ratio;
    $newH = $origH * $ratio;

    // srcX/srcY is the starting point in the ORIGINAL image to crop from
    // Divide by $ratio to get back to original image coordinates
    $srcX = ($newW - $targetWidth) / 2 / $ratio;
    $srcY = ($newH - $targetHeight) / 2 / $ratio;
    
    // srcW/srcH is the width/height of the area to crop from the ORIGINAL image
    $srcW = $origW - (2 * $srcX);
    $srcH = $origH - (2 * $srcY);

    // Create the new canvas
    $resizedImg = imagecreatetruecolor($targetWidth, $targetHeight);

    // Handle transparency for PNG, GIF, and WebP
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
/**
 * ฟังก์ชันรวม: ตรวจสอบ/แปลงเป็น WebP และปรับขนาด/ครอป
 * * @param string $originalPath พาธสัมพัทธ์ของไฟล์รูปภาพต้นฉบับ (เช่น 'upload/img/file.jpg')
 * @param int|null $width ความกว้างเป้าหมาย
 * @param int|null $height ความสูงเป้าหมาย
 * @param int $quality คุณภาพของ WebP (0-100)
 * @return string พาธของไฟล์ WebP ที่ถูกจัดการแล้ว
 */
function ensureWebPAndResizePj($originalPath, $width = null, $height = null, $quality = 80) {
    // 1. Convert to WebP (if necessary)
    // ใช้ destDir เป็น null เพื่อให้ไฟล์ WebP อยู่ในไดเรกทอรีเดียวกับไฟล์ต้นฉบับ
    $webpPath = ensureWebPNativePj($originalPath, null, $quality);
    
    // 2. Resize/Crop (if dimensions are specified)
    if ($width !== null || $height !== null) {
        $webpPath = resizeWebPPj($webpPath, $width, $height, $quality);
    }
    return $webpPath;
}

// ------------------------
// ตั้งค่าภาษา (Language setup)
// ------------------------
$supportedLangs = ['en', 'th', 'cn', 'jp', 'kr'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

$perPage = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$subject_col = 'subject_project' . ($lang !== 'th' ? '_' . $lang : '');
$description_col = 'description_project' . ($lang !== 'th' ? '_' . $lang : '');

// ------------------------
// ดึงข้อมูลรวม (Total Count Query)
// ------------------------
$totalQuery = "SELECT COUNT(DISTINCT dn.project_id) as total
               FROM dn_project dn
               LEFT JOIN dn_project_doc dnc ON dn.project_id = dnc.project_id
               WHERE dn.del = '0'";
if ($searchQuery) {
    $totalQuery .= " AND dn.{$subject_col} LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
}
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalItems = $totalRow['total'];
$totalPages = ceil($totalItems / $perPage);

// ------------------------
// ดึงข้อมูลโปรเจกต์ (Fetch Project data)
// ------------------------
$sql = "SELECT
            dn.project_id,
            dn.subject_project,
            dn.subject_project_en,
            dn.subject_project_cn,
            dn.subject_project_jp,
            dn.subject_project_kr,
            dn.description_project,
            dn.description_project_en,
            dn.description_project_cn,
            dn.description_project_jp,
            dn.description_project_kr,
            dn.content_project,
            dn.date_create,
            GROUP_CONCAT(DISTINCT dnc.file_name) AS file_name,
            GROUP_CONCAT(DISTINCT dnc.file_path) AS pic_path
        FROM
            dn_project dn
        LEFT JOIN
            dn_project_doc dnc ON dn.project_id = dnc.project_id
                                 AND dnc.del = '0'
                                 AND dnc.status = '1'
        WHERE
            dn.del = '0'";

if ($searchQuery) {
    $sql .= " AND dn.{$subject_col} LIKE '%" . $conn->real_escape_string($searchQuery) . "%'";
}

$sql .= "
GROUP BY dn.project_id
ORDER BY dn.date_create DESC
LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);

$boxesNews = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // เลือก Subject/Description ตามภาษา
        $title = $row[$subject_col] ?: $row['subject_project'];
        $description = $row[$description_col] ?: $row['description_project'];
        
        $content = $row['content_project'];
        
        $iframeSrc = null;
        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
        }

        // api_path จาก DB
        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];
        $files = !empty($row['file_name']) ? explode(',', $row['file_name']) : [];

        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        // ------------------------
        // 🚀 ใช้ฟังก์ชัน WebP และ Resize/Crop (กำหนดขนาด 400x200)
        // ------------------------
        $projectImageWebP = !empty($paths) ? ensureWebPAndResizePj($paths[0], 400, height: 200) : null;


        $boxesNews[] = [
            'id' => $row['project_id'],
            // ใช้พาธ WebP ที่ถูกปรับขนาดแล้ว
            'image' => $projectImageWebP, 
            'date_time' => $row['date_create'],
            'title' => $title,
            'description' => $description,
            'iframe' => $iframe
        ];
    }
} else {
    $noResultsText = [
        'en' => 'No project found.',
        'cn' => '未找到项目。',
        'jp' => 'プロジェクトが見つかりません。',
        'kr' => '프로젝트를 찾을 수 없습니다.',
        'th' => 'ไม่พบโปรเจกต์'
    ];
    echo $noResultsText[$lang];
}
?>
<div style="display: flex; justify-content: space-between;">
    <div>
    </div>
    <div>
        <form method="GET" action="">
            <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
            <div class="input-group">
                <?php
                $placeholderText = [
                    'en' => 'Search project...',
                    'cn' => '搜索项目...',
                    'jp' => 'プロジェクトを検索...',
                    'kr' => '프로젝트 검색...',
                    'th' => 'ค้นหาโปรเจกต์...'
                ];
                ?>
                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="<?php echo $placeholderText[$lang]; ?>">
                <button class="btn-search" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>
<div class="content-news">
    <?php foreach ($boxesNews as $index => $box): ?>
        <div class="box-news">
            <div class="box-image">
                <?php
                    $encodedId = urlencode(base64_encode($box['id']));
                    $detailUrl = "project_detail.php?id=" . $encodedId . "&lang=" . htmlspecialchars($lang);
                ?>
                <a href="<?php echo $detailUrl; ?>" class="text-news">
                    <?php
                    if(!empty($box['iframe'])){
                        echo '<iframe frameborder="0" src="' . $box['iframe'] . '" width="100%" height="100%" class="note-video-clip"></iframe>';
                    } else if (!empty($box['image'])){
                        // ใช้ <picture> tag เพื่อรองรับ WebP และกำหนดขนาดใน HTML/CSS
                        echo '<picture>';
                        echo '<source srcset="' . htmlspecialchars($box['image']) . '" type="image/webp">';
                        // ถ้าไฟล์ WebP ถูกสร้างและปรับขนาดแล้ว src จะเป็นไฟล์ WebP
                        // ถ้าเกิดข้อผิดพลาดในการสร้าง WebP, $box['image'] จะเป็นพาธไฟล์ต้นฉบับ
                        echo '<img src="' . htmlspecialchars($box['image']) . '" alt="Image for ' . htmlspecialchars($box['title']) . '" style="width: 100%; height: 200px; object-fit: cover;" loading="lazy">';
                        echo '</picture>';
                    } else {
                        echo '<img src="path/to/default/project_placeholder.jpg" alt="No image available" style="width: 100%; height: 200px; object-fit: cover;">';
                    }
                    ?>
                </a>
            </div>
            <div class="box-content">
                <a href="<?php echo $detailUrl; ?>" class="text-news">
                    <h5 class="line-clamp"><?php echo htmlspecialchars($box['title']); ?></h5>
                    <p class="line-clamp"><?php echo htmlspecialchars($box['description']); ?></p>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?>">
            <?php
            $prevText = [
                'en' => 'Previous',
                'cn' => '上一页',
                'jp' => '前へ',
                'kr' => '이전',
                'th' => 'ก่อนหน้า'
            ];
            echo $prevText[$lang];
            ?>
        </a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?>">
            <?php
            $nextText = [
                'en' => 'Next',
                'cn' => '下一页',
                'jp' => '次へ',
                'kr' => '다음',
                'th' => 'ถัดไป'
            ];
            echo $nextText[$lang];
            ?>
        </a>
    <?php endif; ?>
</div>