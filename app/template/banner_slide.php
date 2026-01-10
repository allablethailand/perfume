<?php
require_once('../lib/connect.php');

$sql = "SELECT * FROM banner ORDER BY id ASC";
$result = $conn->query($sql);

$imagesItems = [];
while ($row = $result->fetch_assoc()) {
    // เก็บ paths ของภาพทั้งหมด
    $imagesItems[] = $row['image_path']; 
}

if (!empty($imagesItems)) {
    // Preload แค่ภาพแรก (LCP) เพื่อเพิ่มความเร็ว
    echo '<link rel="preload" as="image" href="' . $imagesItems[0] . '">'; 
}
?>

<div class="banner-section">
    <div class="banner-container">
        <?php foreach ($imagesItems as $index => $image): ?>
            <div class="banner-carousel-item <?= ($index === 0) ? 'active' : '' ?>">
                
                <?php
                    // กำหนด loading="eager" ให้กับแบนเนอร์แรก (LCP)
                    $loading_attribute = ($index === 0) ? 'loading="eager"' : 'loading="lazy"';
                    
                    // กำหนด Explicit width และ height
                    $width_attribute = 'width="1920"'; 
                    $height_attribute = 'height="450"';
                    
                    // ใช้ค่าของ index สำหรับ alt text
                    $alt_text = "Banner Slide " . ($index + 1);
                ?>
                
                <img src="<?= $image ?>" alt="<?= $alt_text ?>" class="banner-image" 
                    <?= $width_attribute ?> 
                    <?= $height_attribute ?>
                    <?= $loading_attribute ?> 
                    style="max-width: 100%; height: auto; object-fit: cover;">
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ปุ่มควบคุมการเลื่อน -->
    <button class="banner-control-prev" onclick="moveSlide(-1)" aria-label="สไลด์ก่อนหน้า">&#10094;</button>
    <button class="banner-control-next" onclick="moveSlide(1)" aria-label="สไลด์ถัดไป">&#10095;</button>

    <!-- ตัวเลือกสำหรับการเลื่อน เปลี่ยนจาก <span> เป็น <button> -->
    <div class="banner-indicators">
        <?php foreach ($imagesItems as $index => $image): ?>
            <button class="banner-pagination" onclick="goToSlide(<?= $index ?>)" aria-label="ไปที่สไลด์ที่ <?= $index + 1 ?>"></button>
        <?php endforeach; ?>
    </div>
</div>
