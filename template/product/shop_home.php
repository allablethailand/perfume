<?php
// เริ่มการใช้งาน Session ต้องอยู่บรรทัดแรกสุดของไฟล์เสมอ
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// เชื่อมต่อฐานข้อมูลโดยใช้ไฟล์ภายนอก
// require_once(__DIR__ . '/../../../lib/connect.php');
global $conn;

// --- จัดการภาษาด้วย Session ---
$supportedLangs = ['en', 'th', 'cn', 'jp', 'kr'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// กำหนดค่า lang จาก Session หรือค่าเริ่มต้น 'th'
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';
// --- สิ้นสุดการจัดการภาษา ---


// 1. สร้างตัวแปรสำหรับชื่อคอลัมน์กลุ่มสินค้าตามภาษาที่เลือก
$group_name_col = 'group_name' . ($lang !== 'th' ? '_' . $lang : '');
$description_col = 'description' . ($lang !== 'th' ? '_' . $lang : '');

// 2. SQL สำหรับดึงกลุ่มสินค้าหลักทั้งหมด
$sql = "SELECT
            group_id,
            group_name,
            group_name_en,
            group_name_cn,
            group_name_jp,
            group_name_kr,
            description,
            description_en,
            description_cn,
            description_jp,
            description_kr,
            image_path
        FROM
            dn_shop_groups
        WHERE
            del = '0' AND
            status = '1' AND
            parent_group_id IS NULL
        ORDER BY
            group_id ASC"; // สามารถเปลี่ยนเป็น ORDER BY $group_name_col ASC ถ้าต้องการเรียงตามชื่อกลุ่ม

$result = $conn->query($sql);
$group_data = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // ดึงชื่อกลุ่มสินค้าตามภาษาที่เลือก
        $group_name_display = $row[$group_name_col] ?: $row['group_name'];
        // ดึงรายละเอียดตามภาษาที่เลือก
        $description_display = $row[$description_col] ?: $row['description'];

        $group_data[] = [
            'id' => $row['group_id'],
            'name' => $group_name_display,
            'description' => $description_display,
            'image' => $row['image_path']
        ];
    }
}
?>

<style>
    .shop-wrapper-container {
        position: relative;
        max-width: 100%;
        margin: auto;
    }

    .shop-scroll {
        display: flex;
        gap: 2rem;
        scroll-behavior: smooth;
        overflow-x: auto;
        padding-bottom: 1rem;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding-top: 10px;
    }

    .shop-scroll::-webkit-scrollbar {
        display: none;
    }

    .shop-card {
        flex: 0 0 calc((100% - 6rem) / 4);
        height: auto;
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
        padding-top: 100%; /* ทำให้เป็นสี่เหลี่ยมจัตุรัส */
        position: relative;
        overflow: hidden; /* ซ่อนขอบที่เกินมา */
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
        -webkit-line-clamp: 2; /* เพิ่มเป็น 2 บรรทัด */
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
        color: white; /* เปลี่ยนสีลูกศร */
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .scroll-btn:hover {
        background-color: #77777766; /* เข้มขึ้นเมื่อโฮเวอร์ */
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .scroll-btn.left {
        left: 0;
    }

    .scroll-btn.right {
        right: 0;
    }

    @media (max-width: 1200px) {
        .shop-card {
            flex: 0 0 calc((100% - 6rem) / 4);
        }
    }
    @media (max-width: 992px) {
        .shop-card {
            flex: 0 0 calc((100% - 4rem) / 3);
        }
    }
    @media (max-width: 768px) {
        .shop-card {
            flex: 0 0 calc((100% - 2rem) / 2);
        }
    }
    @media (max-width: 576px) {
        .shop-card {
            flex: 0 0 90%;
        }
    }
    .content-sticky {
        padding-bottom: 0px;
        background-color: #ffffff;
        display: flex;
        justify-content: center;
    }
    .no-image-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #999;
        font-size: 1rem;
    }
</style>

<script>
function scrollshop(direction) {
    const box = document.getElementById('shop-scroll-box');
    const firstCard = document.querySelector('.shop-card');
    if (!firstCard) return;

    // คำนวณความกว้างของ 4 การ์ดรวมช่องว่าง (2rem = 32px)
    const cardWidth = firstCard.offsetWidth + 32; 

    if (direction === 'left') {
        box.scrollLeft -= cardWidth * 4;
    } else {
        box.scrollLeft += cardWidth * 4;
    }
}
</script>

<div class="shop-wrapper-container">
    <button class="scroll-btn left" onclick="scrollshop('left')" aria-label="Scroll Left">&#10094;</button>
    <button class="scroll-btn right" onclick="scrollshop('right')" aria-label="Scroll Right">&#10095;</button>

    <div style="overflow: hidden;">
        <div class="shop-scroll" id="shop-scroll-box">
            <?php if (empty($group_data)): ?>
                <?php
                $noGroupsText = [
                    'th' => 'ไม่พบกลุ่มสินค้าหลัก',
                    'en' => 'No main product groups found',
                    'cn' => '未找到主要产品组',
                    'jp' => '主要な製品グループが見つかりません',
                    'kr' => '주요 제품 그룹을 찾을 수 없습니다'
                ];
                ?>
                <div style="padding: 20px; text-align: center; width: 100%;"><?= $noGroupsText[$lang] ?></div>
            <?php else: ?>
                <?php foreach ($group_data as $group): ?>
                    <div class="shop-card">
                        <a href="?product&group_id=<?= htmlspecialchars($group['id']) ?>&lang=<?= htmlspecialchars($lang) ?>" class="text-decoration-none text-dark">
                            <div class="card">
                                <div class="card-image-wrapper">
                                    <?php if (!empty($group['image'])): ?>
                                        <img 
                                            src="<?= htmlspecialchars($group['image']) ?>" 
                                            class="card-img-top" 
                                            alt="<?= htmlspecialchars($group['name']) ?>"
                                            loading="lazy" 
                                            width="300" 
                                            height="300"
                                        >
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background-color: #f0f0f0;">
                                            <?php
                                            $noImageText = [
                                                'th' => 'ไม่มีรูปภาพกลุ่ม',
                                                'en' => 'No group image',
                                                'cn' => '无组图',
                                                'jp' => 'グループ画像なし',
                                                'kr' => '그룹 이미지 없음'
                                            ];
                                            ?>
                                            <div class="no-image-text"><?= $noImageText[$lang] ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($group['name']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($group['description']) ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>