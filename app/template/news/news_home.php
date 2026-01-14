<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../../lib/connect.php');
require_once(__DIR__ . '/../../../lib/base_directory.php');
global $conn;
global $base_path;

// ------------------------
// 1️⃣ Ensure WebP exists
// ------------------------
function ensureWebPNative($originalPath, $destDir = null, $quality = 80) {
    // Sanitize path for security and fix relative path to be absolute within the context
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
function resizeWebP($srcPath, $targetWidth = null, $targetHeight = null, $quality = 80) {
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

    $destDir = dirname($srcPath) . '/resized';
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

    return $resizedPath;
}

// ------------------------
// 3️⃣ Merged function
// ------------------------
function ensureWebPAndResize($originalPath, $width = null, $height = null, $quality = 80) {
    // 1. Convert to WebP (if necessary)
    $webpPath = ensureWebPNative($originalPath, null, $quality);
    
    // 2. Resize/Crop (if dimensions are specified)
    if ($width !== null || $height !== null) {
        $webpPath = resizeWebP($webpPath, $width, $height, $quality);
    }
    return $webpPath;
}

// ------------------------
// ตั้งค่าภาษา (Language setup)
// ------------------------
$supportedLangs = ['en', 'cn', 'jp', 'kr'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

$subjectCol = 'subject_news' . ($lang !== 'th' ? '_' . $lang : '');
$descriptionCol = 'description_news' . ($lang !== 'th' ? '_' . $lang : '');
$contentCol = 'content_news' . ($lang !== 'th' ? '_' . $lang : '');

// ------------------------
// ดึงข้อมูลข่าว (Fetch news data)
// ------------------------
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
            GROUP_CONCAT(dnc.file_name) AS file_name,
            GROUP_CONCAT(dnc.file_path) AS pic_path
        FROM 
            dn_news dn
        LEFT JOIN 
            dn_news_doc dnc ON dn.news_id = dnc.news_id
        WHERE 
            dn.del = '0' AND
            dnc.del = '0' AND
            dnc.status = '1'
        GROUP BY dn.news_id 
        ORDER BY dn.date_create DESC
        LIMIT 5";

$result = $conn->query($sql);
$boxesNews = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $content = $row[$contentCol] ?: $row['content_news'];
        $title = $row[$subjectCol] ?: $row['subject_news'];
        $description = $row[$descriptionCol] ?: $row['description_news'];

        $iframeSrc = null;
        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
        }

        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];
        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        // ------------------------
        // แปลงภาพเป็น WebP อัตโนมัติ (และ Crop/Resize แบบคงสัดส่วน)
        // ------------------------
        // Main News: Crop to 500x450, keeping aspect ratio (e.g., center crop)
        $mainImageWebP = !empty($paths) ? ensureWebPAndResize($paths[0], 600, height: 450) : null;
        
        // Sub News (ใช้ภาพเดียวกัน แต่ขนาด 250x170 สำหรับภาพย่อย)
        // **Note:** The sub-news image size is 170px high in CSS, but for better visual, 
        // I'll calculate a proportional width for 170 height or use a standard small crop size like 250x170
        // The original logic calls for a single resize, let's just make two calls for different sizes:
        $subImageWebP = !empty($paths) ? ensureWebPAndResize($paths[0], 250, height: 170) : null;


        $boxesNews[] = [
            'id' => $row['news_id'],
            // Use different variables for main and sub news to allow for distinct resizing/cropping
            'main_image' => $mainImageWebP, 
            'sub_image' => $subImageWebP,
            'title' => $title,
            'description' => $description,
            'iframe' => $iframe
        ];
    }
}
?>

<style>
    .card-premium {
        border: none;
        border-radius: 6px;
        overflow: hidden;
        background-color: #ffffff;
        color: #333;
        transition: transform 0.4s ease-in-out, box-shadow 0.4s ease-in-out;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 5px 15px rgba(0, 0, 0, 0.05);
        height: 100%;
        display: flex;
        flex-direction: column;
        /* Touch Target Fix: Ensure sufficient size */
        min-height: 250px; 
    }

    .card-premium:hover {
        transform: translateY(-8px);
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.25), 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    /* FIX: news-box-title เป็น H4 */
    .news-box-title {
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #555;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 1.5rem; 
        line-height: 1.3em;
    }
    .sub-news-image-wrapper {
        position: relative;
        overflow: hidden;
        border-radius: 6px 6px 0 0; /* Changed to only top corners for consistency */
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.4s ease-in-out;
        height: 170px; /* Set fixed height for wrapper to contain the image */
    }
    /* Hover effect on wrapper for main news area */
    .card-premium:hover .sub-news-image-wrapper {
        transform: scale(1.05);
    }

    .sub-news-img {
        width: 100%;
        height: 100%; /* Image now fills the wrapper */
        object-fit: cover; /* This CSS property ensures cover/crop-and-resize, but the PHP function also pre-crops for efficiency */
        display: block;
        border-radius: 6px 6px 0 0; /* Changed to only top corners for consistency */
        transition: transform 0.4s ease-in-out;
    }

    .card-img-top {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px 6px 0 0;
    }

    .card-body.sub-news {
        padding: 10px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .main-news-description {
        font-size: 1rem;
        color: #666;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* FIX: sub-news-title เป็น H4 */
    .sub-news-title {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
        font-size: 1.1rem; 
        margin-bottom: 4px;
        line-height: 1.4em;
        color: #555;
    }

    .news-small-text {
        font-size: 0.85rem;
        color: #888;
    }

    #mainNewsCarousel .carousel-item img {
        height: 450px;
        max-height: 450px;
        width: 100%;
        object-fit: cover;
        border-radius: 6px 6px 0 0;
    }

    .p-3.bg-light {
        background-color: #f5f6f8 !important;
        border-radius: 0 0 6px 6px;
        padding: 20px !important;
    }

    /* Touch Target Fix: Increase size and spacing */
    .carousel-control-prev,
    .carousel-control-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: #2525256d;
        border: none;
        border-radius: 50%;
        width: 44px; /* Increased from 40px */
        height: 44px; /* Increased from 40px */
        font-size: 1.5rem;
        text-align: center;
        line-height: 44px; 
        cursor: pointer;
        z-index: 5;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        opacity: 1;
    }

    .carousel-control-prev:hover,
    .carousel-control-next:hover {
        background-color: #3b3b3b4a;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        opacity: 1;
    }
    
    .carousel-control-prev {
        left: -20px;
    }

    .carousel-control-next {
        right: -20px;
    }
    /* End Touch Target Fix */

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        background-size: 100%, 100%;
        width: 1.5rem;
        height: 1.5rem;
    }

    .carousel-control-prev-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z'/%3e%3c/svg%3e");
    }

    .carousel-control-next-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
</style>


<?php if (!empty($boxesNews)): ?>
    <div class="row g-4 d-flex align-items-stretch">
        <div class="col-md-8 d-flex flex-column">
            <div id="mainNewsCarousel" class="carousel slide h-100 d-flex flex-column" data-bs-ride="carousel">
                <div class="card-premium">
                    <div class="carousel-inner flex-grow-1">
                        <?php foreach (array_slice($boxesNews, 0, 4) as $i => $box): ?>
                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                <a href="news_detail.php?id=<?= urlencode(base64_encode($box['id'])) ?>&lang=<?= htmlspecialchars($lang) ?>" class="text-decoration-none text-dark d-block">
                                    <picture>
                                        <?php if ($box['main_image']): ?>
                                            <source srcset="<?= htmlspecialchars($box['main_image']) ?>" type="image/webp">
                                            <img src="<?= htmlspecialchars($box['main_image']) ?>" alt="Image news" class="d-block w-100" style="border-radius: 6px 6px 0 0; height: 450px; object-fit: cover;" loading="lazy">
                                        <?php endif; ?>
                                    </picture>
                                    <div class="p-3 bg-light">
                                        <h4 class="news-box-title"><?= htmlspecialchars($box['title']) ?></h4>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#mainNewsCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden"><?= match ($lang) { 'cn' => '上一页', 'jp' => '前へ', 'en' => 'Previous', 'kr' => '이전', default => 'ก่อนหน้า', }; ?></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#mainNewsCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden"><?= match ($lang) { 'cn' => '下一页', 'jp' => '次へ', 'en' => 'Next', 'kr' => '다음', default => 'ถัดไป', }; ?></span>
                </button>
            </div>
        </div>

        <div class="col-md-4 d-flex flex-column">
            <div class="row row-cols-1 row-cols-md-2 g-4 h-100">
                <?php 
                // Skip the first item since it's the main carousel, then take the next 4 (total 5 news items)
                $subNews = array_slice($boxesNews, offset: 1, length: 4);
                foreach ($subNews as $box): 
                ?>
                    <div class="col d-flex">
                        <a href="news_detail.php?id=<?= urlencode(base64_encode($box['id'])) ?>&lang=<?= htmlspecialchars($lang) ?>" class="text-decoration-none text-dark w-100">
                            <div class="card-premium p-0 d-flex flex-column">
                                <div class="sub-news-image-wrapper flex-shrink-0">
                                    <?php if ($box['sub_image']): ?>
                                        <picture>
                                            <source srcset="<?= htmlspecialchars($box['sub_image']) ?>" type="image/webp">
                                            <img src="<?= htmlspecialchars($box['sub_image']) ?>" alt="Image news" class="sub-news-img" loading="lazy">
                                        </picture>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body sub-news d-flex flex-column">
                                    <h4 class="sub-news-title flex-grow-1"><?= htmlspecialchars($box['title']) ?></h4>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>