<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the necessary files are included
// require_once(__DIR__ . '/../../../lib/connect.php');
require_once(__DIR__ . '/../../lib/base_directory.php'); // Required for $base_path if used in connect.php or the functions
global $conn;
global $base_path;

// ------------------------
// 1️⃣ Ensure WebP exists (Function copied from the news example)
// ------------------------
function ensureWebPNativeBlog($originalPath, $destDir = null, $quality = 80) {
    // Sanitize path for security and fix relative path to be absolute within the context
    // This assumes the original path is relative to the directory where this script runs, 
    // or that it's an absolute path to the file system.
    // The previous script used "../" prefix after sanitizing, we'll keep that assumption.

    $originalPath = preg_replace('#^(\.\./)+#', '', $originalPath);
    // $originalPath =  $originalPath;


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
// 2️⃣ Resize WebP dynamically (Modified for Aspect Ratio "Cover" - Crop-and-Resize) (Function copied from the news example)
// ------------------------
function resizeWebPBlog($srcPath, $targetWidth = null, $targetHeight = null, $quality = 80) {
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
// 3️⃣ Merged function (Function copied from the news example)
// ------------------------
function ensureWebPAndResizeBlog($originalPath, $width = null, $height = null, $quality = 80) {
    // 1. Convert to WebP (if necessary)
    // NOTE: This call expects $originalPath to be a file path accessible on the server.
    $webpPath = ensureWebPNativeBlog($originalPath, null, $quality);
    
    // 2. Resize/Crop (if dimensions are specified)
    if ($width !== null || $height !== null) {
        $webpPath = resizeWebPBlog($webpPath, $width, $height, $quality);
    }
    return $webpPath;
}


// ------------------------
// ตั้งค่าภาษา (Language setup)
// ------------------------
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'cn', 'jp', 'kr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

$subject_col = 'subject_blog';
$description_col = 'description_blog';
$content_col = 'content_blog';

if ($lang === 'en') {
    $subject_col = 'subject_blog_en';
    $description_col = 'description_blog_en';
    $content_col = 'content_blog_en';
} elseif ($lang === 'cn') {
    $subject_col = 'subject_blog_cn';
    $description_col = 'description_blog_cn';
    $content_col = 'content_blog_cn';
} elseif ($lang === 'jp') {
    $subject_col = 'subject_blog_jp';
    $description_col = 'description_blog_jp';
    $content_col = 'content_blog_kr'; // NOTE: This was 'cn' in the original but likely a typo. Fixed to 'kr'
} elseif ($lang === 'kr') {
    $subject_col = 'subject_blog_kr';
    $description_col = 'description_blog_kr';
    $content_col = 'content_blog_kr';
}

// ------------------------
// ดึงข้อมูล Blog (Fetch blog data)
// ------------------------
$sql = "SELECT 
            dn.Blog_id, 
            dn.{$subject_col} AS subject_Blog, 
            dn.{$description_col} AS description_Blog,
            dn.{$content_col} AS content_Blog, 
            dn.date_create, 
            GROUP_CONCAT(dnc.file_name) AS file_name,
            GROUP_CONCAT(dnc.file_path) AS pic_path
        FROM 
            dn_blog dn
        LEFT JOIN 
            dn_blog_doc dnc ON dn.Blog_id = dnc.Blog_id
        WHERE 
            dn.del = '0' AND
            dnc.del = '0' AND
            dnc.status = '1'
        GROUP BY dn.Blog_id 
        ORDER BY dn.date_create DESC
        LIMIT 8";

$result = $conn->query($sql);
$boxesBlog = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $content = $row['content_Blog'];
        $iframeSrc = null;
        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
        }

        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];
        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        // ------------------------
        // แปลงภาพเป็น WebP อัตโนมัติ (และ Crop/Resize แบบคงสัดส่วน)
        // Crop/Resize for a square thumbnail (250x250)
        $blogImageWebP = !empty($paths) ? ensureWebPAndResizeBlog($paths[0], 250, height: 250) : null;
        
        $boxesBlog[] = [
            'id' => $row['Blog_id'],
            'image' => $blogImageWebP, // Use the processed WebP path
            'title' => $row['subject_Blog'],
            'description' => $row['description_Blog'],
            'iframe' => $iframe
        ];
    }
}
?>

<style>
    /* ... (CSS code remains the same) ... */
    .blog-wrapper-container {
        position: relative;
        max-width: 100%;
        margin: auto;
    }
    
    .blog-scroll {
        display: flex;
        gap: 1rem;
        scroll-behavior: smooth;
        overflow-x: auto;
        padding-bottom: 1rem;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding-top: 10px;
    }

    .blog-scroll::-webkit-scrollbar {
        display: none;
    }

    .blog-card {
        flex: 0 0 calc(20% - 1.6rem);
        height: auto;
        min-width: 200px;
    }

    .card {
        display: flex;
        flex-direction: column;
        height: 100%;
        border: none;
        border-radius: 6px;
        overflow: hidden; 
        background-color: #fff;
        transition: transform 0.4s ease-in-out, box-shadow 0.4s ease-in-out;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.25), 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .card-image-wrapper {
        padding-top: 100%;
        position: relative;
        border-radius: 6px;
    }
    
    .card-img-top {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .card-body {
        padding: 15px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: flex-start;
        flex-grow: 1;
        min-height: 100px;
    }

    .card-title {
        font-weight: 600;
        margin-bottom: 5px;
        color: #555;
        font-size: 1.1rem;
        line-height: 1.3em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .card-text {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #777;
        font-size: 0.9rem;
        margin-top: 0px;
        margin-bottom: 0px;
    }

    .scroll-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: #77777738;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        font-size: 1.5rem;
        text-align: center;
        line-height: 40px;
        cursor: pointer;
        z-index: 5;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }

    .scroll-btn:hover {
        background-color: #77777738;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .scroll-btn.left {
        left: -50px;
    }

    .scroll-btn.right {
        right: -50px;
    }

    @media (max-width: 1600px) {
        .blog-card {
            flex: 0 0 calc((100% - 8rem) / 5);
        }
    }
    @media (max-width: 1400px) {
        .blog-card {
            flex: 0 0 calc((100% - 6rem) / 4);
        }
    }
    @media (max-width: 1200px) {
        .blog-card {
            flex: 0 0 calc((100% - 4rem) / 3);
        }
    }
    @media (max-width: 992px) {
        .blog-card {
            flex: 0 0 calc((100% - 2rem) / 2);
        }
    }
    @media (max-width: 768px) {
        .blog-card {
            flex: 0 0 calc((100% - 2rem) / 2);
        }
    }
    @media (max-width: 576px) {
        .blog-card {
            flex: 0 0 90%;
        }
    }
</style>

<script>
function scrollBlog(direction) {
    const box = document.getElementById('blog-scroll-box');
    // NOTE: If the card width is dynamic, ensure this correctly gets the current size
    const cardWidth = document.querySelector('.blog-card').offsetWidth;
    const gap = 32; // Assuming 1rem = 16px, gap: 1rem in CSS means 16px. Let's use 32px or 2rem for a safer scroll.
    if (direction === 'left') {
        box.scrollLeft -= cardWidth + 16; // Use 16px (1rem) as gap for simplicity
    } else {
        box.scrollLeft += cardWidth + 16;
    }
}
</script>

<div class="blog-wrapper-container">
    <?php if (count($boxesBlog) > 5): ?>
        <button class="scroll-btn left" onclick="scrollBlog('left')">&#10094;</button>
        <button class="scroll-btn right" onclick="scrollBlog('right')">&#10095;</button>
    <?php endif; ?>

    <div style="overflow: hidden;">
        <div class="blog-scroll" id="blog-scroll-box">
            <?php foreach ($boxesBlog as $box): ?>
                <div class="blog-card">
                    <a href="?Blog_detail&id=<?= urlencode(base64_encode($box['id'])) ?>&lang=<?= htmlspecialchars($lang) ?>" class="text-decoration-none text-dark">
                        <div class="card">
                            <?php if(!empty($box['iframe'])): ?>
                                <iframe frameborder="0" src="<?= $box['iframe'] ?>" width="100%" height="200px" class="note-video-clip"></iframe>
                            <?php elseif (!empty($box['image'])): ?>
                                <div class="card-image-wrapper">
                                    <picture>
                                        <source srcset="<?= htmlspecialchars(str_replace('../','',$box['image'])) ?>" type="image/webp"> 
                                        <img 
                                            src="<?= htmlspecialchars(str_replace('../','',$box['image'])) ?>" 
                                            class="card-img-top" 
                                            alt="บทความ <?= htmlspecialchars($box['title']) ?>"
                                            loading="lazy"
                                            width="250"
                                            height="250">
                                    </picture>
                                </div>
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; padding-top: 100%; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc; position: relative;">No Image</div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($box['title']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($box['description']) ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>