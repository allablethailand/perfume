<?php
// เริ่มการใช้งาน Session ต้องอยู่บรรทัดแรกสุดของไฟล์เสมอ
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
require_once(__DIR__ . '/../../../lib/connect.php');
require_once(__DIR__ . '/../../../lib/base_directory.php'); // ดึง base_path หากจำเป็นต้องใช้
global $conn;
global $base_path; // ประกาศ global เพื่อให้ฟังก์ชันเข้าถึงได้ หากจำเป็น

// ===========================================
// 🛠️ FUNCTIONS FOR WEBP CONVERSION & RESIZING
// ===========================================

// ------------------------
// 1️⃣ Ensure WebP exists (คัดลอกมาทั้งหมดและรักษาตรรกะ Path)
// ------------------------
function ensureWebPNativeProject($originalPath, $destDir = null, $quality = 80) {
    // Sanitize path for security and fix relative path to be absolute within the context
    // ตรรกะการแก้ Path ให้เหมือนโค้ดต้นฉบับ
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
    // ตรวจสอบว่า imagewebp() พร้อมใช้งานหรือไม่
    if (!function_exists('imagewebp')) {
        imagedestroy($img);
        error_log("GD library's imagewebp function is not available.");
        return $originalPath; 
    }
    
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
function resizeWebPProject($srcPath, $targetWidth = null, $targetHeight = null, $quality = 80) {
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

    // ตรวจสอบว่า GD library พร้อมใช้งานหรือไม่
    if (!function_exists('imagecreatetruecolor')) {
        error_log("GD library functions are not available for resizing.");
        return $srcPath; 
    }

    $destDir = dirname($srcPath) . '/resized';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $fileName      = basename($srcPath);
    $fileNameNoExt = preg_replace('/\.\w+$/', '', $fileName);
    $resizedPath = $destDir . '/' . $fileNameNoExt . "-{$targetWidth}x{$targetHeight}.webp";

    if (file_exists($resizedPath)) return $resizedPath;

    $mime = $info['mime'];
    // ใช้ imagecreatefromwebp สำหรับไฟล์ WebP ด้วย
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
    // ตรวจสอบว่า imagewebp() พร้อมใช้งานหรือไม่
    if (!function_exists('imagewebp')) {
        imagedestroy($img); 
        imagedestroy($resizedImg);
        error_log("GD library's imagewebp function is not available.");
        return $srcPath; 
    }
    
    imagewebp($resizedImg, $resizedPath, $quality);

    imagedestroy($img);
    imagedestroy($resizedImg);

    return $resizedPath;
}

// ------------------------
// 3️⃣ Merged function
// ------------------------
function ensureWebPAndResizeProject($originalPath, $width = null, $height = null, $quality = 80) {
    // 1. Convert to WebP (if necessary) - ใช้ ensureWebPNativeProject ที่มีการแก้ Path
    $webpPath = ensureWebPNativeProject($originalPath, null, $quality);
    
    // 2. Resize/Crop (if dimensions are specified)
    if ($width !== null || $height !== null) {
        // ใช้ resizeWebPProject กับ Path ที่อาจเป็น WebP แล้ว
        $webpPath = resizeWebPProject($webpPath, $width, $height, $quality);
    }
    return $webpPath;
}


// ===========================================
// 💾 PROJECT DATA FETCHING
// ===========================================

// 1. ตรวจสอบพารามิเตอร์ lang ใน URL และบันทึกใน Session
$supportedLangs = ['en', 'th', 'cn', 'jp', 'kr'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// 2. กำหนดค่า lang จาก Session หรือค่าเริ่มต้น 'th'
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

// ดึงข้อมูลทั้งหมดในทุกภาษาเพื่อความยืดหยุ่นในการแสดงผล
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
            GROUP_CONCAT(dnc.file_name) AS file_name,
            GROUP_CONCAT(dnc.file_path) AS pic_path
        FROM 
            dn_project dn
        LEFT JOIN 
            dn_project_doc dnc ON dn.project_id = dnc.project_id
        WHERE 
            dn.del = '0' AND
            dnc.del = '0' AND
            dnc.status = '1'
        GROUP BY dn.project_id 
        ORDER BY dn.date_create DESC
        LIMIT 10";

$result = $conn->query($sql);
$boxesproject = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // ใช้ตัวแปรภาษาเพื่อเลือกคอลัมน์ที่ถูกต้อง และใช้ภาษาไทยเป็นค่าสำรอง
        $subject = $row['subject_project' . ($lang !== 'th' ? '_' . $lang : '')];
        $description = $row['description_project' . ($lang !== 'th' ? '_' . $lang : '')];
        $content = $row['content_project' . ($lang !== 'th' ? '_' . $lang : '')];

        $subject = !empty($subject) ? $subject : $row['subject_project'];
        $description = !empty($description) ? $description : $row['description_project'];
        $content = !empty($content) ? $content : $row['content_project'];

        $iframeSrc = null;
        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
        }

        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];
        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        // ------------------------
        // 🚀 แปลงภาพเป็น WebP อัตโนมัติ (และ Crop/Resize แบบคงสัดส่วน)
        // ------------------------
        // Project Image: ใช้ ensureWebPAndResizeProject
        // Target: อัตราส่วน 4:3 (padding-top: 80%) สมมติขนาด 300x240 เพื่อให้ภาพคมชัดในขนาดที่เหมาะสมสำหรับ carousel card
        $projectImageWebP = !empty($paths) ? ensureWebPAndResizeProject($paths[0], 600, height: 450) : null;


        $boxesproject[] = [
            'id' => $row['project_id'],
            // ใช้ $projectImageWebP ที่ถูกแปลงและปรับขนาดแล้ว
            'image' => $projectImageWebP, 
            'title' => $subject,
            'description' => $description,
            'iframe' => $iframe
        ];
    }
}
?>

<style>
/* Style สำหรับส่วน Project ที่ปรับปรุงใหม่ */
.project-carousel {
    position: relative;
    /* เพิ่ม padding-left และ padding-right เพื่อให้เงาแสดงผลครบ */
    /* padding-left: 50px;
    padding-right: 50px; */
    /* ลบ overflow: hidden; ออกจากที่นี่ */
}

/* เพิ่มสไตล์สำหรับ carousel item ที่แสดงผล 4 กล่อง */
.project-carousel .carousel-inner {
    overflow: hidden;
}

.project-carousel .project-slider {
    display: flex;
    flex-wrap: nowrap;
    gap: 1.5rem;
    overflow-x: scroll; /* ทำให้เลื่อนได้ด้วย scrollbar */
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth; /* ทำให้เลื่อนแบบนุ่มนวล */
    /* เพิ่ม padding-top เพื่อป้องกันส่วนบนของกล่องถูกตัดเมื่อ hover */
    padding-top: 10px;
}

/* ซ่อน scrollbar */
.project-carousel .project-slider::-webkit-scrollbar {
    display: none;
}
.project-carousel .project-slider {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}

.project-carousel .col-md-3 {
    flex: 0 0 auto; /* ป้องกันการย่อขนาด */
    width: 25%;
    max-width: 25%;
}

@media (max-width: 768px) {
    .project-carousel .col-md-3 {
        width: 100%;
        max-width: 100%;
    }
}

.project-card {
    display: flex;
    flex-direction: column;
    height: 100%;
    border-radius: 6px;
    overflow: hidden;
    background-color: #fff;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.7);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.project-card:hover {
    transform: translateY(-5px);
    box-shadow: 10px 15px 30px rgba(0, 0, 0, 0.8); 
}

.project-image-wrapper {
    position: relative;
    padding-top: 80%; /* เพิ่มความสูงของรูปภาพ (ตัวอย่างสำหรับอัตราส่วน 4:3) */
    overflow: hidden; /* เพิ่มกลับมาเพื่อให้รูปภาพที่ปรับขนาดครอบคลุม */
}

.project-img-top {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

/* ลบสไตล์การ hover นี้ออก เพราะจะทำให้ภาพถูกตัด */
/* .project-card:hover .project-img-top {
    transform: scale(1.05);
} */

.project-body {
    padding: 1.25rem 1.25rem 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.project-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #555;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}

.project-text {
    font-size: 0.95rem;
    color: #777;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}

.project-card .learn-more {
    font-weight: 600;
    font-size: 0.9rem;
    color: #6a1a8c;
    margin-top: 1rem;
    align-self: flex-start;
    display: block;
}

/* responsive controls */
.carousel-control-prev,
.carousel-control-next {
    width: 40px;
    height: 40px;
    background-color: #c7c7c7dc;
    border-radius: 50%;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    opacity: 1;
    transition: all 0.3s ease;
    /* แก้ไขตำแหน่งตรงนี้ */
    position: absolute;
    top: 50%; /* ย้ายปุ่มไปกลาง */
    margin-top: -20px; /* เลื่อนขึ้นครึ่งหนึ่งของความสูง */
}

.carousel-control-prev:hover,
.carousel-control-next:hover {
    background-color: #c7c7c7;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
}

.carousel-control-prev {
    left: -25px;
    transform: translateX(-50%);
}
.carousel-control-next {
    right: -25px;
    transform: translateX(50%);
}
.carousel-control-prev-icon,
.carousel-control-next-icon {
    background-image: none;
    font-size: 1.5rem;
    color: #6a1a8c;
    line-height: 1;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}

.carousel-control-prev-icon::before {
    content: '‹';
}

.carousel-control-next-icon::before {
    content: '›';
}
</style>

<div class="row-project">
    <div class="col-12">
        <div id="projectCarousel" class="project-carousel">
            <div class="project-slider-wrapper" style="overflow: hidden;">
                <div class="project-slider">
                    <?php foreach ($boxesproject as $box): ?>
                        <div class="col-md-3 mb-4 d-flex">
                           <a href="project_detail.php?id=<?= urlencode(base64_encode($box['id'])) ?>&lang=<?= htmlspecialchars($lang) ?>" class="text-decoration-none text-dark w-100">
                                <div class="project-card d-flex flex-column">
                                    <?php if (empty($box['image']) && !empty($box['iframe'])): ?>
                                        <iframe frameborder="0" src="<?= htmlspecialchars($box['iframe']) ?>" width="100%" height="100%" class="note-video-clip" style="border-radius: 20px 20px 0 0;"></iframe>
                                    <?php else: ?>
                                        <div class="project-image-wrapper">
                                            <?php if ($box['image']): ?>
                                                <picture>
                                                    <source srcset="<?= htmlspecialchars($box['image']) ?>" type="image/webp">
                                                    <img src="<?= htmlspecialchars($box['image']) ?>" class="project-img-top" alt="<?= htmlspecialchars($box['title']) ?>" loading="lazy">
                                                </picture>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="project-body d-flex flex-column">
                                        <h6 class="project-title flex-grow-1"><?= htmlspecialchars($box['title']) ?></h6>
                                        <p class="project-text"><?= htmlspecialchars($box['description']) ?></p>
                                        <span class="learn-more">
                                            <?php 
                                                if ($lang === 'en') {
                                                    echo 'Learn more >';
                                                } elseif ($lang === 'cn') {
                                                    echo '了解更多 >';
                                                } elseif ($lang === 'jp') {
                                                    echo 'もっと見る >';
                                                } elseif ($lang === 'kr') {
                                                    echo '더 알아보기 >'; // เพิ่ม kr
                                                } else {
                                                    echo 'ดูเพิ่มเติม >';
                                                }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button class="carousel-control-prev" type="button" onclick="scrollProject('prev')">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" onclick="scrollProject('next')">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</div>
<script>
    function scrollProject(direction) {
        const slider = document.querySelector('.project-slider');
        const scrollAmount = 300; // เลื่อนทีละ 300px
        if (direction === 'prev') {
            slider.scrollLeft -= scrollAmount;
        } else {
            slider.scrollLeft += scrollAmount;
        }
    }
</script>