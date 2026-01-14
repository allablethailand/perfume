<?php
// ต้องมีโค้ดเชื่อมต่อฐานข้อมูลก่อนส่วนนี้ (เช่น require_once('lib/connect.php');)
// สมมติว่าตัวแปร $conn เป็น Global
global $conn; // ใช้ตัวแปร $conn ที่เชื่อมต่อกับฐานข้อมูลแล้ว

// เพิ่มโค้ดส่วนนี้เพื่อสร้าง URL ของหน้าปัจจุบัน
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$pageUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$subjectTitle = "perfume - Website Title"; // กำหนดหัวข้อสำหรับแชร์ (สามารถเปลี่ยนได้)

// *******************************************************************
// ************ 🆕 ส่วนที่เพิ่ม: กำหนดลิงก์สำหรับแต่ละหัวข้อ *************
// *******************************************************************
$project_link = 'https://www.perfume.com/app/project.php'; // ลิงก์สำหรับ "โครงการที่ผ่านมา"
$news_link = 'https://www.perfume.com/app/news.php';     // เปลี่ยน '#' เป็นลิงก์หน้าข่าวสารหลัก
$blog_link = 'https://www.perfume.com/app/Blog.php';      // เปลี่ยน '#' เป็นลิงก์หน้าบทความหลัก
$video_link = 'https://www.perfume.com/app/Video.php';    // เปลี่ยน '#' เป็นลิงก์หน้าวิดีโอหลัก
$product_link = 'https://www.perfume.com/app/shop.php';  // เปลี่ยน '#' เป็นลิงก์หน้าสินค้าหลัก

// *******************************************************************
// ************ ส่วนที่ 1: การดึงข้อมูลเลย์เอาต์จาก Database จริง **********
// *******************************************************************

$layout_config_raw = [];
$layout_blocks = [];

// ตรวจสอบว่ามีการเชื่อมต่อฐานข้อมูลหรือไม่
if (isset($conn) && $conn instanceof mysqli) {
    try {
        // ดึงข้อมูลจริงจากตารางตามเงื่อนไข: เปิดใช้งาน (is_active = 1) และเรียงตามลำดับ (display_order)
        $stmt = $conn->prepare("SELECT block_name, background_color, is_full_width FROM homepage_layout WHERE is_active = 1 ORDER BY display_order ASC");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // จัดรูปแบบข้อมูลให้ตรงกับที่โค้ดเดิมใช้ (name => [order_placeholder, color, full_width])
            // เราไม่จำเป็นต้องเก็บ display_order แล้ว เพราะเราใช้ ORDER BY ใน SQL แล้ว
            $layout_blocks[$row['block_name']] = [
                0, // ค่า placeholder สำหรับ display_order เดิม
                $row['background_color'],
                $row['is_full_width']
            ];
        }
        $stmt->close();
    } catch (Exception $e) {
        // ในกรณีที่เกิดข้อผิดพลาดในการดึงข้อมูล จะใช้ข้อมูลเริ่มต้น (Fallback)
        error_log("Database layout error: " . $e->getMessage());
        // ใช้ข้อมูลเริ่มต้นแบบ Hardcode เป็น Fallback หาก DB ล่ม
        $layout_blocks = [
            'news' => [1, '#ffffff', 0],
            'project' => [2, '#ff9900', 1],
            'blog' => [3, '#f8f9fa', 0],
            'video' => [4, '#ffffff', 0],
        ];
    }
} else {
    // กรณีไม่มีการเชื่อมต่อฐานข้อมูลเลย ให้ใช้ข้อมูลเริ่มต้น
    $layout_blocks = [
        'news' => [1, '#ffffff', 0],
        'project' => [2, '#ff9900', 1],
        'blog' => [3, '#f8f9fa', 0],
        'video' => [4, '#ffffff', 0],
    ];
}

// ใช้ $layout_blocks แทน $layout_config ในส่วนที่เหลือของโค้ด

// กำหนด HTML Content ของแต่ละบล็อก
$blocks_content = [
    'news' => function () {
        ob_start();
        ?>
    <h2 data-translate="WhatsNew" class="line-ref1" lang="th">ข่าวสารล่าสุด</h2>
    <div class="box-content">
        <?php include 'template/news/news_home.php'; ?>
    </div>
    <?php
        return ob_get_clean();
    },
    'project' => function () {
        ob_start();
        ?>
    <div class="box-content-shop" style="background-color: transparent;">
        <h2 data-translate="project1" lang="th" class="line-ref2">โครงการที่ผ่านมา</h2>
        <?php include 'template/project/project_home.php'; ?>
    </div>
    <?php
        return ob_get_clean();
    },
    'blog' => function () {
        ob_start();
        ?>
    <h2 data-translate="blog" lang="th" class="line-ref">บทความ</h2>
    <div class="box-content">
        <?php include 'template/Blog/Blog_home.php'; ?>
    </div>
    <?php
        return ob_get_clean();
    },
    'video' => function () {
        ob_start();
        ?>
    <h2 data-translate="video" lang="th" class="line-ref">วิดีโอแนะนำ</h2>
    <div class="box-content">
        <?php include 'template/video/video_home.php'; ?>
    </div>
    <?php
        return ob_get_clean();
    },
    'product' => function () {
        ob_start();
        // บล็อก product ถูกซ่อนในข้อมูลจำลอง แต่ถ้าเปิดใช้งานจะใช้โค้ดนี้
        ?>
    <h2 data-translate="product1" lang="th" class="line-ref">Product</h2>
    <div class="box-content">
        <?php include 'template/product/shop_home.php'; ?>
    </div>
    <?php
        return ob_get_clean();
    },
];


// *******************************************************************
// ************ ส่วนที่ 2: โค้ด CSS ที่แก้ไข (รวม Responsive Image Fix) *****************
// *******************************************************************
?>
<style>
    /* เพิ่มโค้ดส่วนนี้เข้าไป */
    body,
    html {
        overflow-x: hidden;
        /* background-color: #f0f2f5; กำหนดสีพื้นหลังหลักของ body เพื่อให้ดูสะอาดตา */
    }

    /* สไตล์สำหรับ line-ref ที่แก้ไขแล้ว */
    /* *** 🆕 แก้ไข: เพิ่ม .line-ref-link เพื่อสไตล์ลิงก์ที่มาจาก h2 *** */
    .line-ref-link {
        color: inherit; /* ให้สีตัวอักษรเป็นไปตาม h2 */
        text-decoration: none; /* ลบขีดเส้นใต้ */
        display: block; /* ทำให้คลิกได้ทั้งบล็อก h2 */
        cursor: pointer;
    }
    
    .line-ref,
    .line-ref1,
    .line-ref-custom {
        display: block;
        margin-bottom: 1.5rem;
        font-size: 1.8rem;
        font-weight: 600;
        color: #555;
        position: relative;
        text-align: left;
        width: fit-content;
        padding-left: 15px;
    }

    .line-ref:after,
    .line-ref1:after,
    .line-ref-custom:after {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        width: 3px;
        height: 2.5rem;
        background-color: #ff9900;
        /* สีเส้นหลัก */
        border-radius: 2px;
    }

    /* สไตล์สำหรับหัวข้อที่อยู่บนพื้นหลังสีเข้ม (สีขาว) */
    .line-ref-white {
        color: #fff !important;
    }

    .line-ref-white:after {
        background-color: #fff !important;
    }
    
    /* สไตล์สำหรับส่วน "อะไรใหม่" ที่ปรับปรุงใหม่ (คงเดิม) */
    .news-wrapper {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 40px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }
    
    /* ... สไตล์ที่เหลือของ news-wrapper, news-main-card, ฯลฯ คงเดิม ... */
    
    /* สไตล์สำหรับบล็อกสินค้าเดิม */
    .section.product-bg {
        padding: 30px 0;
    }

    .box-content-shop {
        padding: 40px 20px 20px 20px;
        border-radius: 8px;
        color: #555;
    }

    .article-luxury-section {
        padding: 40px 0;
        border-radius: 6px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    /* สไตล์สำหรับบล็อกเนื้อหาหลักแบบมีขอบ (content-block) */
    .content-block {
        padding: 1.5rem;

        width: 100%;
        /* ********** แก้ไข: เพิ่มระยะห่างด้านล่าง 3em ********** */
        margin-bottom: 3em;
    }

    .content-wrapper1 {
        max-width: 90%;
        margin: 0 10%;
    }

    .content-wrapper {
        max-width: 90%;
        margin: 0 6%;
    }

    /* สไตล์สำหรับพื้นหลังสีเต็มจอ (full-width-block) */
    .full-width-block {
        width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        /* padding-top: 3rem;
    padding-bottom: 3rem; */
        /* ********** แก้ไข: เพิ่มระยะห่างด้านล่าง 3em ********** */
        margin-bottom: 3em;
        margin-top: 2em;
    }

    .full-width-content-inner {
        max-width: 90%;
        margin: 0 auto;
    }

    .full-width-content-inner .line-ref-custom {
        color: #fff;
    }

    .full-width-content-inner .line-ref-custom:after {
        background-color: #fff;
    }


    /* สไตล์สำหรับปุ่มคัดลอกลิงก์ที่ปรับปรุงใหม่ (คงเดิม) */
    .copy-link-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        /* ********** แก้ไข: ลดขนาดปุ่ม ********** */
        width: 36px;
        height: 36px;
        border: 1px solid #ccc;
        background-color: #fff;
        color: #666;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0;
        /* ********** แก้ไข: ลดขนาด font ********** */
        font-size: 1.2rem;
    }

    .copy-link-btn:hover {
        background-color: #f0f0f0;
        border-color: #999;
        color: #333;
    }

    .content-sticky1 {
        padding-bottom: 0px;
        background-color: #ffffff;
        display: flex;
        justify-content: end;
    }


    /* สไตล์เพิ่มเติมสำหรับก้อนแชร์ */
    .share-container-right {
        display: flex;
        /* ใช้ flexbox จัดก้อนแชร์ */
        justify-content: flex-end;
        /* ผลักก้อนไปทางขวา */
        width: 100%;
        /* ต้องกว้างเต็มพื้นที่เพื่อให้ justify-content ทำงานได้ */
        padding-top: 30px;
    }

    .share-container-left {
        display: flex;
        /* ใช้ flexbox จัดก้อนแชร์ */
        justify-content: flex-start;
        /* คงก้อนไว้ทางซ้าย (ค่า default ก็ได้ แต่ใส่เพื่อความชัดเจน) */
        width: 100%;
        padding-bottom: 3em;
    }

    /* ********** โค้ดที่เพิ่ม: สไตล์สำหรับไอคอนแชร์ใน mobile ********** */
    /* FIX: กำหนดขนาดการแสดงผลให้เป็น 36x36px เพื่อแก้ปัญหา Image Natural Dimensions */
    .social-share a img {
        width: 36px ;
        height: 36px ;
        max-width: 36px; 
        max-height: 36px; 
        display: block;
        /* แก้ปัญหาช่องว่างด้านล่างรูปภาพ */
    }

    /* ปรับขนาดไอคอน Twitter/X ที่มีการกำหนด width="36" height="36" ใน HTML
       ให้เหลือ 36px เพื่อความเป็นมาตรฐานและแก้ปัญหา Responsive Image */
    .social-share a img[alt="Share on Twitter"] {
        width: 30px ;
        height: 30px ;
        max-height: 36px !important;
        /* height: auto !important;
        width: auto !important; */
    }

    /* Responsive (คงเดิม) */
    @media (max-width: 992px) {
        .news-wrapper {
            grid-template-columns: 1fr;
        }
    }

    /* ********** แก้ไข: ปรับการจัดเรียงในโหมดมือถือ ********** */
    @media (max-width: 576px) {
        .news-wrapper {
            padding: 0 20px;
        }

        .news-side-img-wrapper {
            width: 100px;
        }

        /* ปรับ padding ของ content-wrapper ให้เหลือพื้นที่ขอบน้อยลง (หากจำเป็น) */
        .content-wrapper,
        .full-width-content-inner {
            max-width: 95%;
            margin: 0 auto;
        }

        .content-wrapper1 {
            /* ตรงนี้ใช้ 90% และ 10% อาจจะทำให้ชิดขวามากไป ปรับเป็น auto เพื่ออยู่ตรงกลาง */
            max-width: 95%;
            margin: 0 auto;
        }

        /* ********** เพิ่ม: ปรับระยะห่างระหว่างปุ่มแชร์ในโหมดมือถือ ********** */
        .social-share div {
            gap: 5px !important;
            /* ลดระยะห่างระหว่างปุ่ม */
        }

        /* ปรับขนาดตัวอักษร "แชร์หน้านี้" ในโหมดมือถือ */
        .share-container-left p,
        .share-container-right p {
            font-size: 16px !important;
        }
    }

    .box-content {
        background-color: transparent;
        padding: 0px 0px 20px 0px;
        border-radius: 8px;
        color: #555;
    }
</style>

<?php
// *******************************************************************
// ****************** ส่วนที่ 3: โค้ด HTML/PHP ที่แก้ไข ****************
// *******************************************************************
?>

<div class="content-sticky1" id="" style=" margin: 0 auto;">
    <div class="content-wrapper1">

        <div class="share-container-right">
            <div class="social-share" style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                <p data-translate="share" lang="th" style="margin: 0; font-size:18px; font-family: sans-serif;">
                    แชร์หน้านี้:</p>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button class="copy-link-btn" onclick="copyLink()" aria-label="Copy link to clipboard">
                        <i class="fas fa-link"></i>
                    </button>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank">
                        <img width="36" height="36" src="https://img.icons8.com/color/48/000000/facebook-new.png" alt="Share on Facebook">
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($subjectTitle) ?>"
                        target="_blank">
                        <img width="36" height="36" src="https://cdn.prod.website-files.com/5d66bdc65e51a0d114d15891/64cebdd90aef8ef8c749e848_X-EverythingApp-Logo-Twitter.jpg"
                            alt="Share on Twitter">
                    </a>
                    <a href="https://social-plugins.line.me/lineit/share?url=<?= urlencode($pageUrl) ?>"
                        target="_blank">
                        <img width="36" height="36" src="https://img.icons8.com/color/48/000000/line-me.png" alt="Share on Line">
                    </a>
                    <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode($pageUrl) ?>" target="_blank">
                        <img width="36" height="36" src="https://img.icons8.com/color/48/000000/pinterest--v1.png" alt="Share on Pinterest">
                    </a>
                    <a href="https://www.instagram.com/" target="_blank" aria-label="Share on Instagram"> <img width="36" height="36" src="https://img.icons8.com/fluency/48/instagram-new.png" alt="Share on Instagram">
                    </a>
                    <a href="https://www.tiktok.com/" target="_blank" aria-label="Share on TikTok"> <img width="36" height="36" src="https://img.icons8.com/fluency/48/tiktok.png" alt="Share on TikTok">
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>


<?php
foreach ($layout_blocks as $block_name => $config) {

    list($order_placeholder, $bg_color, $is_full_width) = $config;

    if (!isset($blocks_content[$block_name])) {
        continue;
    }

    $content = $blocks_content[$block_name]();
    
    // กำหนด Link และ Label สำหรับส่วนนี้
    $target_link = '#';
    $link_label = '';
    $original_h2_tag = '';
    
    // กำหนด Tag และ Class ที่ต้องหาเพื่อแทนที่
    if ($block_name === 'project') {
        $target_link = $project_link;
        $link_label = 'ดูโครงการที่ผ่านมาทั้งหมด';
        $original_h2_tag_start = '<h2 data-translate="project1" lang="th" class="line-ref2">';
    } elseif ($block_name === 'news') {
        $target_link = $news_link;
        $link_label = 'ดูข่าวสารล่าสุดทั้งหมด';
        // 🆕 แก้ไข: ใช้ RegEx ที่ยืดหยุ่นขึ้นเพื่อจับ Class เดิมของ news (line-ref1)
        $original_h2_tag_start = '<h2 data-translate="WhatsNew" class="line-ref1" lang="th">';
    } elseif ($block_name === 'blog') {
        $target_link = $blog_link;
        $link_label = 'อ่านบทความทั้งหมด';
        $original_h2_tag_start = '<h2 data-translate="blog" lang="th" class="line-ref">';
    } elseif ($block_name === 'video') {
        $target_link = $video_link;
        $link_label = 'ดูวิดีโอแนะนำทั้งหมด';
        $original_h2_tag_start = '<h2 data-translate="video" lang="th" class="line-ref">';
    } elseif ($block_name === 'product') {
        $target_link = $product_link;
        $link_label = 'ดูสินค้าทั้งหมด';
        // 🆕 แก้ไข: ใช้ RegEx ที่ยืดหยุ่นขึ้นเพื่อจับ Class เดิมของ product (line-ref)
        $original_h2_tag_start = '<h2 data-translate="product1" lang="th" class="line-ref">';
    }
    
    // *** การแก้ไขลิงก์ข่าวสารที่ไม่มีชื่อ (จากคำถามก่อนหน้า) ***
    if ($block_name === 'news') {
        $content = str_replace(
            '<a href="news_detail.php',
            '<a aria-label="Read more news detail" href="news_detail.php', 
            $content
        );
    }
    // *** สิ้นสุดการแก้ไขลิงก์ข่าวสาร ***

    $wrapper_class = $is_full_width ? 'full-width-block' : 'content-block';
    $wrapper_style = "background-color: {$bg_color};";
    $inner_wrapper_class = $is_full_width ? 'full-width-content-inner' : 'content-wrapper';

    $is_dark_bg = ($bg_color !== '#ffffff' && $bg_color !== '#f8f9fa' && $bg_color !== '#ffead0');
    $white_text_class = $is_dark_bg ? 'line-ref-white' : '';

    
    // *********************************************************************************
    // *** 🆕 ส่วนที่แก้ไข: การห่อหุ้ม <h2> ด้วย <a> เพื่อให้หัวข้อเป็นลิงก์ที่คลิกได้ ***
    // *********************************************************************************
    if (isset($original_h2_tag_start)) {
        
        // 1. กำหนด Class ใหม่
        $h2_tag_class = 'line-ref-custom';
        
        // 2. สร้างแท็ก <a> ที่หุ้ม <h2>...</h2>
        $link_wrap_open = '<a href="' . $target_link . '" class="line-ref-link" aria-label="' . $link_label . '">';
        $link_wrap_close = '</a>';
        
        // 3. ปรับปรุงแท็ก <h2> เปิด: ลบ Class เดิมทิ้ง และเพิ่ม Class ใหม่กับ Class สี
        // (ใช้ Regular Expression สำหรับการแทนที่ที่ยืดหยุ่นกว่า str_replace)
        
        // 🆕 แก้ไข: ใช้ preg_replace เพื่อแทนที่ Class เดิมที่ต่างกันของแต่ละ Block
        // รูปแบบ: <h2 [Attributes] class="[CLASS_OLD]" [Attributes]>
        // แทนที่ด้วย: <a href="..." ...><h2 [Attributes] class="line-ref-custom line-ref-white" [Attributes]>
        $content = preg_replace(
            '/(' . preg_quote(substr($original_h2_tag_start, 0, strrpos($original_h2_tag_start, ' class='))) . ')\s+class="[^"]*"([^>]*>)/',
            $link_wrap_open . '$1 class="' . $h2_tag_class . ' ' . $white_text_class . '"$2',
            $content,
            1 // แทนที่แค่ครั้งแรก (หัวข้อหลัก)
        );

        // 4. แทนที่แท็ก </h2> ปิด ด้วย </h2></a>
        $content = str_replace(
            '</h2>',
            '</h2>' . $link_wrap_close,
            $content
        );

    }
    // *********************************************************************************
    // *** สิ้นสุดการแก้ไขหัวข้อเป็นลิงก์ ***
    // *********************************************************************************
    
    ?>
    <div class="<?= $wrapper_class ?>" style="<?= $wrapper_style ?>">
        <div class="<?= $inner_wrapper_class ?>">
            <div class="row">
                <div class="col-md-12 section bottom-shasow">
                    <?= $content ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<div class="content-sticky1" id="" style=" margin: 0 auto;">
    <div class="content-wrapper1">
        <div class="row">
            <div class="col-md-12">
                <div class="share-container-left">
                    <p data-translate="share" lang="th"
                        style="margin: 0; padding-right: 10px; font-size:18px; font-family: sans-serif;">แชร์หน้านี้:
                    </p>
                    <div class="social-share" style="display: flex; align-items: center; gap: 10px;">
                        <button class="copy-link-btn" onclick="copyLink()" aria-label="Copy link to clipboard">
                            <i class="fas fa-link"></i>
                        </button>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>"
                            target="_blank">
                            <img width="36" height="36" src="https://img.icons8.com/color/48/000000/facebook-new.png" alt="Share on Facebook">
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($subjectTitle) ?>"
                            target="_blank">
                            <img width="36" height="36" src="https://cdn.prod.website-files.com/5d66bdc65e51a0d114d15891/64cebdd90aef8ef8c749e848_X-EverythingApp-Logo-Twitter.jpg"
                                alt="Share on Twitter">
                        </a>
                        <a href="https://social-plugins.line.me/lineit/share?url=<?= urlencode($pageUrl) ?>"
                            target="_blank">
                            <img width="36" height="36" src="https://img.icons8.com/color/48/000000/line-me.png" alt="Share on Line">
                        </a>
                        <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode($pageUrl) ?>"
                            target="_blank">
                            <img width="36" height="36" src="https://img.icons8.com/color/48/000000/pinterest--v1.png"
                                alt="Share on Pinterest">
                        </a>
                        <a href="https://www.instagram.com/" target="_blank" aria-label="Share on Instagram"> <img width="36" height="36" src="https://img.icons8.com/fluency/48/instagram-new.png" alt="Share on Instagram">
                        </a>
                        <a href="https://www.tiktok.com/" target="_blank" aria-label="Share on TikTok"> <img width="36" height="36" src="https://img.icons8.com/fluency/48/tiktok.png" alt="Share on TikTok">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyLink() {
        const pageUrl = "<?= $pageUrl ?>";
        navigator.clipboard.writeText(pageUrl).then(function () {
            alert("คัดลอกลิงก์เรียบร้อยแล้ว");
        }, function () {
            alert("ไม่สามารถคัดลอกลิงก์ได้ กรุณาคัดลอกด้วยตนเอง");
        });
    }
</script>