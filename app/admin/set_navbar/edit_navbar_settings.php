<?php
// edit_navbar_settings.php
// ใช้โค้ดเริ่มต้นคล้าย edit_homepage_layout.php

// ** ต้องเปลี่ยนพาธให้ถูกต้องตามโครงสร้างไฟล์ของคุณ **
include '../check_permission.php'; 
// ต้องมีการเชื่อมต่อฐานข้อมูล $conn และ check_permission.php ตามโครงสร้างเดิม
global $conn;

// ***************************************************************
// 1. ดึงข้อมูลตั้งค่าจากตาราง dn_settings (ไม่เปลี่ยนแปลง)
// ***************************************************************
$current_settings = [];
$required_keys = [
    'navbar_bg_color', 'navbar_text_color', 
    'news_ticker_display', 'news_ticker_bg_color', 
    'news_ticker_text_color', 'news_ticker_title_color'
];

try {
    // ดึงเฉพาะ Key ที่จำเป็น
    $placeholders = implode(',', array_fill(0, count($required_keys), '?'));
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM dn_settings WHERE setting_key IN ({$placeholders})");
    $types = str_repeat('s', count($required_keys));
    $stmt->bind_param($types, ...$required_keys);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
} catch (Exception $e) {
    echo "<script>alert('Error loading settings data: " . $e->getMessage() . "'); window.location.href='../dashboard.php';</script>";
    exit;
}

// ตรวจสอบและกำหนดค่าเริ่มต้นถ้าขาดหาย
foreach ($required_keys as $key) {
    if (!isset($current_settings[$key])) {
        // ใช้ค่าเริ่มต้นที่กำหนดไว้ใน process_navbar_settings.php
        $default_values = [
            'navbar_bg_color' => '#ff9900',
            'navbar_text_color' => '#ffffff',
            'news_ticker_display' => '1',
            'news_ticker_bg_color' => '#ffffffff',
            'news_ticker_text_color' => '#555',
            'news_ticker_title_color' => '#ff9900',
        ];
        $current_settings[$key] = $default_values[$key];
    }
}

$navbarBgColor = $current_settings['navbar_bg_color'];
$navbarTextColor = $current_settings['navbar_text_color'];
$newsTickerDisplay = $current_settings['news_ticker_display'];
$newsTickerBgColor = $current_settings['news_ticker_bg_color'];
$newsTickerTextColor = $current_settings['news_ticker_text_color'];
$newsTickerTitleColor = $current_settings['news_ticker_title_color'];


// ***************************************************************
// 3. ดึงข้อมูลรายการเมนูจากตาราง dn_navbar_menu (ใหม่)
// ***************************************************************
$menuItems = [];
try {
    $sql_menu = "SELECT `menu_id`, `is_dropdown`, `dropdown_id`, `link_url`, `icon_class`, `display_order`, `is_active`, `text_th`, `text_en`, `text_cn`, `text_jp`, `text_kr`, `translate_key` 
                 FROM `dn_navbar_menu` 
                 ORDER BY `display_order` ASC";
    $result_menu = $conn->query($sql_menu);
    if ($result_menu) {
        while ($row = $result_menu->fetch_assoc()) {
            $menuItems[] = $row;
        }
    }
} catch (Exception $e) {
    echo "<script>console.error('Error loading menu data: " . $e->getMessage() . "');</script>";
}

// ***************************************************************
// 2. โค้ด CSS สำหรับ Live Preview (คัดลอก/ปรับปรุงจาก navbar.php)
// ***************************************************************
?>
<style>
/* ------------------------------------------------------------- */
/* CSS Variables (กำหนดสีเริ่มต้นตามค่าที่ดึงมา) */
/* ------------------------------------------------------------- */
:root {
    --navbar-bg-color: <?= $navbarBgColor ?>;
    --navbar-text-color: <?= $navbarTextColor ?>;
    --news-ticker-bg-color: <?= $newsTickerBgColor ?>;
    --news-ticker-text-color: <?= $newsTickerTextColor ?>;
    --news-ticker-title-color: <?= $newsTickerTitleColor ?>;
    --news-ticker-display: <?= $newsTickerDisplay == '1' ? 'block' : 'none' ?>;
}

/* ------------------------------------------------------------- */
/* Live Preview Area */
/* ------------------------------------------------------------- */
#preview-container {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}
.live-preview-title {
    background-color: #343a40;
    color: white;
    padding: 10px 20px;
    margin-bottom: 0;
}

/* ------------------------------------------------------------- */
/* Navbar CSS (ดัดแปลงจากโค้ดเดิม) */
/* ------------------------------------------------------------- */
.navbar-desktop-preview {
    background-color: var(--navbar-bg-color); /* ใช้ Variable */
    padding: 6px 0;
}

.desktop-menu-container-preview {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 35px;
    height: 50px; /* กำหนดความสูงให้เห็นชัด */
}

.desktop-menu-item-preview {
    color: var(--navbar-text-color); /* ใช้ Variable */
    text-decoration: none;
    padding: 10px 15px;
    font-size: 18px;
    border-radius: 4px;
    background-color: transparent;
    transition: background-color 0.3s;
    /* จำลองการซ่อนเมนู */
    opacity: 1; 
    transition: opacity 0.3s;
}
.desktop-menu-item-preview.hidden {
    opacity: 0.3; /* ทำให้เมนูที่ซ่อนดูจางลงใน Preview */
}
.desktop-menu-item-preview:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

/* ------------------------------------------------------------- */
/* News Ticker CSS (ดัดแปลงจากโค้ดเดิม) */
/* ------------------------------------------------------------- */
#navbar-news-preview {
    display: var(--news-ticker-display); /* ปิด/เปิดแถบข่าวจาก Variable */
}
.news-ticker-preview {
    display: flex; 
    align-items: center;
    background-color: var(--news-ticker-bg-color); /* ใช้ Variable */
    color: var(--news-ticker-text-color); /* ใช้ Variable */
    font-size: 20px;
    font-weight: bold;
    border-radius: 0px 70px 10px 0px;
    padding-right: 15px;
}
.text-ticker-preview {
    /* ส่วนหัวข้อ "ข่าวประจำวัน" */
    background-color: var(--news-ticker-title-color); /* ใช้ Variable */
    color: var(--navbar-text-color); /* สีตัวอักษรหัวข้อ (ใช้สีเดียวกับ Navbar) */
    padding: 10px 20px;
    border-radius: 0px 70px 10px 0px;
    line-height: 1; 
    white-space: nowrap;
}
.marquee-content-preview {
    color: var(--news-ticker-text-color); /* สีข้อความข่าว */
    padding: 0 10px;
    overflow: hidden;
    flex-grow: 1;
}

/* ------------------------------------------------------------- */
/* Menu Management Table CSS */
/* ------------------------------------------------------------- */
#menuList {
    cursor: grab; /* เพื่อแสดงว่าสามารถลากได้ */
}
#menuList tr:hover {
    background-color: #f8f9fa;
}
#menuList tr.ui-sortable-helper {
    background: #e9ecef;
    border: 1px dashed #ced4da;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.menu-handle {
    cursor: grab;
    color: #495057;
    padding: 0 8px;
}
</style>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเลย์เอาต์หน้าหลัก</title>

    <link rel="icon" type="image/x-icon" href="https://www.perfume.com//public/news_img/%E0%B8%94%E0%B8%B5%E0%B9%84%E0%B8%8B%E0%B8%99%E0%B9%8C%E0%B8%97%E0%B8%B5%E0%B9%88%E0%B8%A2%E0%B8%B1%E0%B8%87%E0%B9%84%E0%B8%A1%E0%B9%88%E0%B9%84%E0%B8%94%E0%B9%89%E0%B8%95%E0%B8%B1%E0%B9%89%E0%B8%87%E0%B8%8A%E0%B8%B7%E0%B9%88%E0%B8%AD_5.png">

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
    <h2 class="mb-4" style="padding-left:15%;">🎨 จัดการ Navbar และ News Ticker</h2>
    
    <div class="row justify-content-center"> 
        <div class="col-lg-8"> 
            
            <div class="card shadow-sm mb-4">
                <div class="card-header live-preview-title">
                    <h5 class="mb-0">✨ ตัวอย่าง (Live Preview)</h5>
                </div>
                <div class="card-body p-0" id="preview-container">
                    
                    <div id="navbar-preview-area" class="navbar-desktop-preview">
                        <div class="desktop-menu-container-preview" id="menu-preview-container">
                            </div>
                    </div>
                    
                    <div id="navbar-news-preview" style="padding: 10px 0;">
                        <div style="margin-left: 5%;">
                            <div class="news-ticker-preview">
                                <span class="text-ticker-preview">
                                    ข่าวประจำวัน
                                </span>
                                <div class="marquee-content-preview">
                                    <span style="padding: 0 20px;">ข่าวที่ 1</span>
                                    <span style="padding: 0 20px;">ข่าวที่ 2</span>
                                    <span style="padding: 0 20px;">ข่าวที่ 3</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📋 จัดการรายการเมนู Navbar (ลาก-วาง เพื่อสลับตำแหน่ง)</h5>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted">คุณสามารถลาก-วาง (Drag & Drop) แถวเพื่อสลับลำดับการแสดงผล และใช้ปุ่มสลับเพื่อซ่อน/แสดงเมนู</p>
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;">ลำดับ</th>
                                <th style="width: 10%;">ลาก</th>
                                <th>หัวข้อ (TH)</th>
                                <th style="width: 15%;" class="text-center">สถานะ</th>
                                <th style="width: 15%;" class="text-center">การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody id="menuList">
                            <?php 
                            $i = 1;
                            foreach ($menuItems as $item): 
                            ?>
                            <tr data-menu-id="<?= $item['menu_id'] ?>" data-is-active="<?= $item['is_active'] ?>">
                                <td class="text-center order-text"><?= $i++ ?></td>
                                <td class="text-center menu-handle"><i class="fas fa-arrows-alt"></i></td>
                                <td>
                                    <strong><?= htmlspecialchars($item['text_th']) ?></strong>
                                    <br><small class="text-muted">Link: <?= htmlspecialchars($item['link_url']) ?></small>
                                    <?php if($item['is_dropdown'] == 1): ?>
                                    <span class="badge bg-secondary ms-2">Dropdown</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge status-badge <?= $item['is_active'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $item['is_active'] == 1 ? 'แสดง' : 'ซ่อน' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-<?= $item['is_active'] == 1 ? 'danger' : 'success' ?> toggle-status-btn" 
                                            data-id="<?= $item['menu_id'] ?>" 
                                            data-current-status="<?= $item['is_active'] ?>">
                                        <i class="fas fa-<?= $item['is_active'] == 1 ? 'eye-slash' : 'eye' ?>"></i> 
                                        <?= $item['is_active'] == 1 ? 'ซ่อน' : 'แสดง' ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <button type="button" id="saveMenuOrderBtn" class="btn btn-warning btn-lg w-100 mt-3">
                        <i class="fas fa-sync-alt"></i> บันทึกการจัดเรียงและการซ่อน/แสดงเมนู
                    </button>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">🛠️ การตั้งค่าสีและการแสดงผล</h5>
                </div>
                <form id="navbarSettingsForm" class="p-3">
                    <input type="hidden" name="action" value="update_navbar_settings">

                    <h4 class="mt-3">แถบเมนูหลัก (Navbar)</h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="navbar_bg_color" class="form-label">สีพื้นหลัง Navbar</label>
                            <input type="color" class="form-control form-control-color setting-input" id="navbar_bg_color" name="navbar_bg_color" value="<?= $navbarBgColor ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="navbar_text_color" class="form-label">สีตัวอักษร Navbar</label>
                            <input type="color" class="form-control form-control-color setting-input" id="navbar_text_color" name="navbar_text_color" value="<?= $navbarTextColor ?>">
                        </div>
                    </div>
                    <hr>

                    <h4 class="mt-4">แถบข่าวสาร (News Ticker)</h4>
                    <div class="mb-3">
                        <label class="form-label">สถานะการแสดงผล</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input setting-input" type="checkbox" id="news_ticker_display_check" 
                                name="news_ticker_display_check" value="1" <?= $newsTickerDisplay == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="news_ticker_display_check">
                                แสดงแถบข่าวสาร
                            </label>
                            <input type="hidden" id="news_ticker_display" name="news_ticker_display" value="<?= $newsTickerDisplay ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="news_ticker_title_color" class="form-label">สีพื้นหลัง **หัวข้อ**</label>
                            <input type="color" class="form-control form-control-color setting-input" id="news_ticker_title_color" name="news_ticker_title_color" value="<?= $newsTickerTitleColor ?>">
                            <small class="text-muted">เช่น สีของบล็อก "ข่าวประจำวัน"</small>
                        </div>
                        <div class="col-md-4">
                            <label for="news_ticker_bg_color" class="form-label">สีพื้นหลัง **ข่าววิ่ง**</label>
                            <input type="color" class="form-control form-control-color setting-input" id="news_ticker_bg_color" name="news_ticker_bg_color" value="<?= $newsTickerBgColor ?>">
                            <small class="text-muted">สีพื้นหลังส่วนที่มีข่าววิ่ง</small>
                        </div>
                        <div class="col-md-4">
                            <label for="news_ticker_text_color" class="form-label">สีตัวอักษรข่าววิ่ง</label>
                            <input type="color" class="form-control form-control-color setting-input" id="news_ticker_text_color" name="news_ticker_text_color" value="<?= $newsTickerTextColor ?>">
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light p-3 mt-4">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-save"></i> บันทึกการตั้งค่าสีและการแสดงผล
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
// ข้อมูลเมนูสำหรับ Live Preview
const menuData = <?= json_encode($menuItems); ?>;

function updateLivePreview() {
    // 1. ดึงค่าจาก Form
    const navbarBg = $('#navbar_bg_color').val();
    const navbarText = $('#navbar_text_color').val();
    const newsDisplay = $('#news_ticker_display_check').is(':checked') ? 'block' : 'none';
    const newsBg = $('#news_ticker_bg_color').val();
    const newsText = $('#news_ticker_text_color').val();
    const newsTitleBg = $('#news_ticker_title_color').val();
    
    // 2. อัปเดต Hidden Input สำหรับส่งค่าการแสดงผล
    $('#news_ticker_display').val(newsDisplay === 'block' ? '1' : '0');

    // 3. อัปเดต CSS Variables ใน Live Preview
    document.documentElement.style.setProperty('--navbar-bg-color', navbarBg);
    document.documentElement.style.setProperty('--navbar-text-color', navbarText);
    document.documentElement.style.setProperty('--news-ticker-bg-color', newsBg);
    document.documentElement.style.setProperty('--news-ticker-text-color', newsText);
    document.documentElement.style.setProperty('--news-ticker-title-color', newsTitleBg);
    document.documentElement.style.setProperty('--news-ticker-display', newsDisplay);
    
    // 4. อัปเดต Menu Preview ตามลำดับและสถานะปัจจุบัน
    updateMenuPreview();
}

function updateMenuPreview() {
    const $menuContainer = $('#menu-preview-container');
    $menuContainer.empty();

    // ลำดับและสถานะมาจากตาราง #menuList (หลังการลาก-วาง)
    $('#menuList tr').each(function() {
        const $row = $(this);
        const menuId = $row.data('menu-id');
        const isActive = $row.data('is-active') == 1;
        const menu = menuData.find(item => item.menu_id == menuId);

        if (menu) {
            const dropdownIcon = menu.is_dropdown == 1 ? ' <i class="fas fa-caret-down"></i>' : '';
            const $menuItem = $(`<span class="desktop-menu-item-preview">${menu.text_th}${dropdownIcon}</span>`);
            
            if (!isActive) {
                $menuItem.addClass('hidden'); // ทำให้จางลงถ้าซ่อน
            }
            
            $menuContainer.append($menuItem);
        }
    });
}

function updateMenuOrderAndStatus() {
    const menuUpdates = [];
    
    $('#menuList tr').each(function(index) {
        const $row = $(this);
        menuUpdates.push({
            id: $row.data('menu-id'),
            order: index + 1, // ลำดับใหม่
            is_active: $row.data('is-active')
        });
        
        // อัปเดตตัวเลขลำดับในตาราง
        $row.find('.order-text').text(index + 1);
    });
    
    // ส่งข้อมูลไปยัง actions/process_navbar_menu.php
    $.ajax({
        url: 'actions/process_navbar_menu.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'update_menu_order_status',
            menu_data: JSON.stringify(menuUpdates)
        },
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire('สำเร็จ!', response.message, 'success');
                // อัปเดต Live Preview อีกครั้งหลังจากบันทึก
                updateLivePreview();
            } else {
                Swal.fire('ผิดพลาด!', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.fire('ข้อผิดพลาด!', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์: ' + error, 'error');
        }
    });
}


$(document).ready(function() {
    // 1. เปิดใช้งาน jQuery UI Sortable สำหรับการลาก-วาง
    $("#menuList").sortable({
        items: "tr",
        cursor: "grabbing",
        opacity: 0.8,
        stop: function(event, ui) {
            // เมื่อหยุดลาก-วาง ให้อัปเดต Live Preview (แต่ยังไม่บันทึก DB)
            updateLivePreview(); 
        }
    });
    
    // 2. จัดการปุ่มบันทึกการจัดเรียง/ซ่อน/แสดง
    $('#saveMenuOrderBtn').click(function() {
        updateMenuOrderAndStatus();
    });

    // 3. จัดการปุ่มซ่อน/แสดง (Toggle Status)
    $('#menuList').on('click', '.toggle-status-btn', function() {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const currentStatus = parseInt($btn.data('current-status'));
        const newStatus = 1 - currentStatus; // สลับ 0 <-> 1
        
        // อัปเดตสถานะใน DOM (data attribute)
        $row.data('is-active', newStatus);
        
        // อัปเดต UI ของปุ่มและ Badge
        const badgeText = newStatus == 1 ? 'แสดง' : 'ซ่อน';
        const btnText = newStatus == 1 ? 'ซ่อน' : 'แสดง';
        const btnClass = newStatus == 1 ? 'danger' : 'success';
        
        $row.find('.status-badge').removeClass('bg-success bg-danger').addClass(`bg-${newStatus == 1 ? 'success' : 'danger'}`).text(badgeText);
        $btn.removeClass('btn-danger btn-success').addClass(`btn-${btnClass}`).html(`<i class="fas fa-${newStatus == 1 ? 'eye-slash' : 'eye'}"></i> ${btnText}`);
        $btn.data('current-status', newStatus);
        
        // อัปเดต Live Preview ทันที
        updateLivePreview();
        
        // *** ผู้ใช้ต้องกดปุ่ม 'บันทึกการจัดเรียงฯ' เพื่อบันทึกลงฐานข้อมูลจริง ***
    });

    // 4. จัดการ Live Preview สำหรับส่วนสี (เดิม)
    updateLivePreview(); 
    $('.setting-input').on('input change', function() {
        updateLivePreview();
    });
    
    // 5. จัดการการส่งฟอร์มตั้งค่าสีด้วย AJAX (เดิม)
    $('#navbarSettingsForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'actions/process_navbar_settings.php',
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize(),
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