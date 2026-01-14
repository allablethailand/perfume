<?php
// edit_homepage_layout.php
// ใช้โค้ดเริ่มต้นคล้าย edit_footer.php ในการตรวจสอบสิทธิ์และการเชื่อมต่อ
include '../check_permission.php'; 
// ต้องมีการเชื่อมต่อฐานข้อมูล $conn และ check_permission.php ตามโครงสร้างเดิม

// ***************************************************************
// 1. ดึงข้อมูลเลย์เอาต์จากตาราง homepage_layout
// ***************************************************************
global $conn;

try {
    // ดึงข้อมูล is_full_width มาด้วยเพื่อใช้ในการสร้างคลาสและส่งค่าไปอัปเดต แม้จะไม่ได้ให้ผู้ใช้แก้ไข
    $stmt = $conn->prepare("SELECT * FROM homepage_layout ORDER BY display_order ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $layout_blocks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    echo "<script>alert('Error loading layout data: " . $e->getMessage() . "'); window.location.href='../dashboard.php';</script>";
    exit;
}

if (empty($layout_blocks)) {
    echo "<script>alert('Homepage layout data is empty. Please run the initial SQL script.'); window.location.href='../dashboard.php';</script>";
    exit;
}

// กำหนดชื่อที่แสดงสำหรับผู้ใช้
$block_titles = [
    'news' => '📰 ข่าวสารล่าสุด',
    'project' => '🏗️ โครงการที่ผ่านมา',
    'blog' => '📝 บทความ',
    'video' => '▶️ วิดีโอแนะนำ',
    'product' => '🛍️ สินค้า (Product)',
];

// ***************************************************************
// 2. กำหนด HTML Content ของแต่ละบล็อก (ใช้สำหรับแสดงตัวอย่าง)
// ***************************************************************

// โค้ด HTML ของแต่ละบล็อกนี้จะเหมือนกับในไฟล์หน้าบ้าน แต่จะถูกเรียกใช้เป็น Function เพื่อนำไปแสดงใน Live Preview
$blocks_content = [
    'news' => function() {
        ob_start();
        ?>
            <h4 data-translate="WhatsNew" class="line-ref1" lang="th" >ข่าวสารล่าสุด (ตัวอย่าง)</h4>
            <div class="box-content text-center py-4 bg-light text-muted border rounded">
                เนื้อหาข่าวสารล่าสุดจะแสดงที่นี่ (template/news/news_home.php)
            </div>
        <?php
        return ob_get_clean();
    },
    'project' => function() {
        ob_start();
        ?>
            <div class="box-content-shop" style="background-color: transparent;">
                <h4 data-translate="project1" lang="th" class="line-ref2">โครงการที่ผ่านมา (ตัวอย่าง)</h4>
                <div class="box-content text-center py-4 bg-light text-muted border rounded">
                    เนื้อหาโครงการที่ผ่านมาจะแสดงที่นี่ (template/project/project_home.php)
                </div>
            </div>
        <?php
        return ob_get_clean();
    },
    'blog' => function() {
        ob_start();
        ?>
            <h4 data-translate="blog" lang="th" class="line-ref">บทความ (ตัวอย่าง)</h4>
            <div class="box-content text-center py-4 bg-light text-muted border rounded">
                เนื้อหาบทความจะแสดงที่นี่ (template/Blog/Blog_home.php)
            </div>
        <?php
        return ob_get_clean();
    },
    'video' => function() {
        ob_start();
        ?>
            <div class="box-content">
                <h4 data-translate="video" lang="th" class="line-ref">วิดีโอแนะนำ (ตัวอย่าง)</h4>
                <div class="box-content text-center py-4 bg-light text-muted border rounded">
                    เนื้อหาวิดีโอจะแสดงที่นี่ (template/video/video_home.php)
                </div>
            </div>
        <?php
        return ob_get_clean();
    },
    'product' => function() {
        ob_start();
        ?>
            <h4 data-translate="product1" lang="th" class="line-ref" >Product (ตัวอย่าง)</h4>
            <div class="box-content text-center py-4 bg-light text-muted border rounded">
                เนื้อหาสินค้าจะแสดงที่นี่ (template/product/shop_home.php)
            </div>
        <?php
        return ob_get_clean();
    },
];


// ***************************************************************
// 3. โค้ด CSS สำหรับ Live Preview (คัดลอกมาจากหน้าบ้าน + ปรับสำหรับ UI ใหม่)
// ***************************************************************
?>
<style>
/* CSS สำหรับ Live Preview */
#live-preview-area {
    margin-top: 2rem;
    border: 1px solid #dee2e6; /* ปรับให้ดูเป็นเนื้อหาที่จัดเรียง */
    padding: 0;
    min-height: 400px;
    border-radius: 8px;
    background-color: #f8f9fa; /* พื้นหลังรวมของพื้นที่จัดเรียง */
}
.live-preview-title {
    background-color: #343a40;
    color: white;
    padding: 10px 20px;
    margin-bottom: 0;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}
.live-preview-block-wrapper {
    /* เป็นตัวแทนของ li.list-group-item และ container บล็อก */
    width: 100%;
    margin: 0;
    padding: 3rem 15px 3rem 15px; /* เพิ่ม padding ในแนวตั้ง */
    position: relative; /* สำคัญสำหรับการใช้ full-width-block และการวางเครื่องมือ */
    cursor: grab; /* เพื่อแสดงว่าลากได้ */
    border-bottom: 1px solid #e9ecef; /* เส้นแบ่งบล็อก */
    transition: all 0.2s ease;
}
.live-preview-block-wrapper:hover {
    box-shadow: 0 0 10px rgba(0,0,0,0.1); /* เพิ่มเงาตอนชี้ */
    z-index: 10;
}
.live-preview-block-wrapper.disabled-block {
    opacity: 0.5;
}
.ui-state-highlight {
    /* สไตล์สำหรับ placeholder ตอนลาก */
    height: 100px;
    background-color: #ffe0b2 !important; /* สีส้มอ่อน */
    border: 2px dashed #ff9900;
    margin: 10px 0;
    border-radius: 4px;
}

/* สไตล์สำหรับ Inner Content (Standard vs Full Width) */
.content-wrapper {
    max-width: 1140px; /* คล้าย Bootstrap container */
    margin: 0 auto;
    padding: 0 15px; /* เผื่อขอบด้านข้างสำหรับ Standard */
}
.full-width-content-inner {
    max-width: 100%; 
    padding: 0 5%; /* เผื่อขอบด้านข้างสำหรับ Full Width */
}


/* สไตล์เครื่องมือแก้ไขใหม่ */
.block-toolbar {
    position: absolute;
    top: 5px; /* ชิดมุมบนขวา */
    right: 5px;
    z-index: 20; /* ให้อยู่เหนือเนื้อหา */
    background-color: rgba(255, 255, 255, 0.85);
    padding: 5px 10px;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    display: flex;
    gap: 10px;
    align-items: center;
}
.block-toolbar .form-control-color {
    padding: 0;
    height: 30px;
    width: 30px;
}

/* สไตล์หัวข้อที่คัดลอกมาจากหน้าบ้าน */
.line-ref, .line-ref1, .line-ref-custom, .line-ref2 {
    /* ... (CSS เดิมสำหรับหัวข้อ) ... */
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
.line-ref:after, .line-ref1:after, .line-ref-custom:after, .line-ref2:after {
    /* ... (CSS เดิมสำหรับเส้นสีส้ม) ... */
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    transform: translateY(-50%);
    width: 3px;
    height: 2.5rem;
    background-color: #ff9900; /* สีเส้นหลัก */
    border-radius: 2px;
}
.line-ref-white {
    color: #fff !important;
}
.line-ref-white:after {
    background-color: #fff !important;
}
/* สไตล์อื่นๆ ที่จำเป็นสำหรับ Live Preview */
.box-content-shop {
    padding: 20px;
    border-radius: 8px;
    color: #555;
}

/* ซ่อนตัวเลือกความกว้าง (ตามคำสั่ง) */
.full-width-control {
    display: none !important; 
}
</style>

<?php
// ***************************************************************
// 4. โค้ด HTML และ UI (ปรับปรุงให้เหลือคอลัมน์เดียว)
// ***************************************************************
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเลย์เอาต์หน้าหลัก</title>

    <link rel="icon" type="image/x-icon" href="../../../public/img/q-removebg-preview1.png">

    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>

    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">

    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>

    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>

    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css" />

    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
</head>

<body>
<?php include '../template/header.php'; ?>
<div class="content-sticky">
<div class="container-fluid mt-5">
    <h2 class="mb-4">⚙️ จัดการเลย์เอาต์หน้าหลัก (Homepage Layout)</h2>
    
    <div class="row justify-content-center"> <div class="col-lg-10"> <p class="text-muted mb-3">ลากบล็อกในพื้นที่ตัวอย่างเพื่อสลับลำดับ, คลิกปุ่มสลับเพื่อเปิด/ปิด, และเลือกสีพื้นหลัง(แก้เสร็จแล้วกดปุ่มันทึกด้านล่างสุดด้วยนะครับ)</p>
            
            <div class="card shadow-sm">
                <div class="card-header live-preview-title">
                    <h5 class="mb-0">✨ ตัวอย่างและแก้ไขเลย์เอาต์หน้าหลัก (Live Preview & Editor)</h5>
                </div>
                <form id="layoutForm" class="p-0">
                    <input type="hidden" name="action" value="update_layout">
                    
                    <div id="live-preview-area" class="list-group"> <?php foreach ($layout_blocks as $block): 
                            $block_name = htmlspecialchars($block['block_name']);
                            $title = $block_titles[$block_name] ?? ucfirst($block_name);
                            $is_active_class = $block['is_active'] ? '' : 'disabled-block';
                            $full_width = $block['is_full_width'];
                            $is_full_width_class = $full_width == 1 ? 'full-width-content-inner' : 'content-wrapper';
                            $wrapper_style = "background-color: " . htmlspecialchars($block['background_color']) . ";";
                            
                            // ดึงเนื้อหา HTML จากฟังก์ชัน
                            $content = $blocks_content[$block_name]();

                            // Logic สำหรับ White Text
                            $isDarkBg = (strtoupper($block['background_color']) !== '#FFFFFF' && strtoupper($block['background_color']) !== '#F8F9FA' && strtoupper($block['background_color']) !== '#FFEAD0');
                            $whiteTextClass = $isDarkBg ? 'line-ref-white' : '';

                            // แทนที่ class หัวข้อด้วย class ใหม่ที่มี white_text_class
                            $finalContent = str_replace(
                                ['class="line-ref1"', 'class="line-ref2"', 'class="line-ref"', 'class="line-ref-custom "'], 
                                "class=\"line-ref-custom {$whiteTextClass}\"", 
                                $content
                            );
                        ?>
                        
                        <div class="live-preview-block-wrapper list-group-item <?= $is_active_class ?>" 
                            data-block-name="<?= $block_name ?>"
                            data-color="<?= htmlspecialchars($block['background_color']) ?>"
                            data-full-width="<?= $full_width ?>" 
                            data-is-active="<?= $block['is_active'] ?>"
                            style="<?= $wrapper_style ?>">
                            
                            <div class="block-toolbar">
                                
                                <span class="badge bg-dark me-2">
                                    <?= $title ?> (<?= $full_width == 1 ? 'เต็มจอ' : 'มีขอบ' ?>)
                                </span>
                                
                                <div class="form-check form-switch" data-bs-toggle="tooltip" title="เปิด/ปิด">
                                    <input class="form-check-input block-active" type="checkbox" id="active_<?= $block_name ?>" 
                                        name="is_active_<?= $block_name ?>" value="1" 
                                        <?= $block['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="active_<?= $block_name ?>"></label>
                                </div>
                                
                                <label for="color_<?= $block_name ?>" data-bs-toggle="tooltip" title="สีพื้นหลัง">
                                    <input type="color" class="form-control form-control-color block-color" id="color_<?= $block_name ?>" 
                                        value="<?= htmlspecialchars($block['background_color']) ?>" title="เลือกสีพื้นหลังบล็อก">
                                </label>

                                <input type="hidden" class="block-full-width" value="<?= $full_width ?>">
                                <i class="fas fa-arrows-alt-v me-1 text-secondary" style="cursor: grab;" title="ลากเพื่อจัดลำดับ"></i>
                            </div>
                            
                            <div class="<?= $is_full_width_class ?>">
                                <?= $finalContent ?>
                            </div>
                            
                        </div>
                        <?php endforeach; ?>
                        
                    </div>
                    
                    <div class="card-footer bg-light p-3">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-save"></i> บันทึกการตั้งค่าเลย์เอาต์
                        </button>
                    </div>
                    
                </form>
            </div>
            
        </div>
    </div>
</div>
</div>
<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
<script>
// PHP blocks_content function mapping for JS use
const BLOCKS_CONTENT_MAP = <?= json_encode(array_keys($blocks_content)) ?>;
const BLOCK_HTML_MAP = {
    <?php foreach ($blocks_content as $name => $func): ?>
        // ไม่ต้องใช้ฟังก์ชันอีกแล้ว เพราะ HTML ถูกสร้างไว้ล่วงหน้า
        '<?= $name ?>': <?= json_encode($func()) ?>, 
    <?php endforeach; ?>
};
const BLOCK_TITLES = <?= json_encode($block_titles); ?>; // สำหรับแสดงใน Toolbar

function updateBlockUI(blockItem) {
    // 1. อ่านค่าจาก UI
    const isActive = blockItem.find('.block-active').is(':checked');
    const bgColor = blockItem.find('.block-color').val();
    const isFullWidth = blockItem.find('.block-full-width').val() === '1';
    
    // 2. ปรับ UI Element
    blockItem.toggleClass('disabled-block', !isActive);
    blockItem.css('background-color', isActive ? bgColor : '#f8f9fa'); // ใช้สีพื้นหลังของ block หรือสีเทาถ้าปิด

    // 3. ปรับสีข้อความ (White Text Logic)
    // ตรวจสอบสีเข้ม (สำหรับเปลี่ยนหัวข้อเป็นสีขาว)
    const isDarkBg = (bgColor !== '#ffffff' && bgColor !== '#f8f9fa' && bgColor !== '#ffead0');
    const whiteTextClass = isDarkBg ? 'line-ref-white' : '';
    
    // อัปเดต class หัวข้อทั้งหมดในบล็อก
    blockItem.find('h4').each(function() {
        const h4 = $(this);
        // ลบคลาส white text เก่าออกก่อน
        h4.removeClass('line-ref-white'); 
        // เพิ่มคลาส white text ใหม่ถ้าจำเป็น
        if (whiteTextClass) {
             h4.addClass('line-ref-white');
        }
    });

    // 4. อัปเดต Tooltip/Label
    const title = BLOCK_TITLES[blockItem.data('block-name')];
    const fullWidthText = isFullWidth ? 'เต็มจอ' : 'มีขอบ';
    blockItem.find('.badge.bg-dark').html(`${title} (${fullWidthText})`);
    blockItem.find('.block-active').next('label').text(isActive ? '' : ''); // ไม่แสดง 'เปิด/ปิด' ใน label แล้ว
}

function renderAllBlockUI() {
    $("#live-preview-area > .live-preview-block-wrapper").each(function() {
        updateBlockUI($(this));
    });
}


$(document).ready(function() {
    // 1. ทำให้รายการสามารถลากวางได้ (Sortable)
    $("#live-preview-area").sortable({
        items: ".live-preview-block-wrapper", // กำหนดว่าอะไรที่ลากได้
        handle: ".fa-arrows-alt-v", // กำหนดให้ลากได้เฉพาะที่ไอคอน
        cursor: "grabbing",
        placeholder: "ui-state-highlight", // คลาสสำหรับพื้นที่ว่างขณะลาก
        axis: "y", // อนุญาตให้ลากในแนวตั้งเท่านั้น
        update: renderAllBlockUI // อัปเดต Preview ทุกครั้งที่ลากวาง
    });
    
    // 2. เหตุการณ์สำหรับการอัปเดต Live Preview
    // เรียกใช้เมื่อมีการเปลี่ยนสถานะ/สี
    $('#live-preview-area').on('change', '.block-active', renderAllBlockUI);
    $('#live-preview-area').on('input', '.block-color', function() {
        // เมื่อเปลี่ยนสี ให้อัปเดต UI ของบล็อกทันที
        updateBlockUI($(this).closest('.live-preview-block-wrapper')); 
    });

    // 3. เริ่มต้นแสดงผลครั้งแรก (ปรับปรุงสีและสถานะ)
    renderAllBlockUI();


    // 4. จัดการการส่งฟอร์มด้วย AJAX 
    $('#layoutForm').submit(function(e) {
        e.preventDefault();
        
        // สร้าง Array สำหรับส่งข้อมูลจากการอ่านค่าล่าสุดใน UI (ตามลำดับใหม่)
        let layoutData = [];
        $("#live-preview-area > .live-preview-block-wrapper").each(function(index) {
            const blockItem = $(this);
            const blockName = blockItem.data('block-name');
            const colorInput = blockItem.find('.block-color');
            const fullWidthInput = blockItem.find('.block-full-width'); // ยังคงใช้ Hidden Input
            const activeCheckbox = blockItem.find('.block-active');

            layoutData.push({
                block_name: blockName,
                display_order: index + 1, // ลำดับจะเริ่มจาก 1
                background_color: colorInput.val(),
                is_full_width: fullWidthInput.val(), // ใช้ค่าที่ 'Fix' ไว้
                is_active: activeCheckbox.is(':checked') ? 1 : 0
            });
        });

        // ส่งข้อมูลไปยัง actions/process_layout.php
        $.ajax({
            url: 'actions/process_layout.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'update_layout',
                layout_data_json: JSON.stringify(layoutData)
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('สำเร็จ!', response.message, 'success');
                } else {
                    Swal.fire('ผิดพลาด!', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('ข้อผิดพลาด!', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์: ' + error, 'error');
            }
        });
    });
});
</script>

</body>
</html>