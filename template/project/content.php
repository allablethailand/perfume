<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../lib/connect.php');
global $conn;
global $base_path;

// ------------------------
// 1️⃣ Ensure WebP exists
// ------------------------
function ensureWebPNativePj($originalPath, $destDir = null, $quality = 80) {
    $originalPath = preg_replace('#^(\.\./)+#', '', $originalPath);
    $originalPath = "../" . $originalPath;

    if (!file_exists($originalPath)) return $originalPath;

    if ($destDir === null) $destDir = dirname($originalPath);

    $fileName = basename($originalPath);
    $destPath = rtrim($destDir, '/') . '/' . $fileName;
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
    
    if (!imagewebp($img, $webpPath, $quality)) { 
        imagedestroy($img); 
        return $originalPath; 
    }
    imagedestroy($img);

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

// ------------------------
// ดึงข้อมูลกลุ่มโครงการ (Fetch Project Groups) - **ไม่มีการเปลี่ยนแปลงหลัก**
// ------------------------
$group_name_col = 'group_name' . ($lang !== 'th' ? '_' . $lang : '');

$groupSql = "SELECT 
              group_id, 
              group_name,
              group_name_en,
              group_name_cn,
              group_name_jp,
              group_name_kr,
              image_path,
              sort_order 
            FROM dn_project_groups 
            WHERE del = '0' AND status = '1'
            ORDER BY sort_order ASC, group_id ASC";

$groupResult = $conn->query($groupSql);
$groups = [];
if ($groupResult->num_rows > 0) {
    while($row = $groupResult->fetch_assoc()) {
        $groups[] = $row;
    }
}

// ------------------------
// รับค่า filter กลุ่ม
// ------------------------
$selectedGroup = isset($_GET['group']) ? (int)$_GET['group'] : 0;

$perPage = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : ''; // 💡 trim() เพื่อความปลอดภัย

$subject_col = 'subject_project' . ($lang !== 'th' ? '_' . $lang : '');
$description_col = 'description_project' . ($lang !== 'th' ? '_' . $lang : '');

// 💡 เตรียมเงื่อนไข WHERE สำหรับ Search
$searchCondition = "";
if ($searchQuery) {
    $searchTerm = $conn->real_escape_string($searchQuery);
    // เพิ่ม JOIN/LEFT JOIN ใน SQL จริงสำหรับเงื่อนไขนี้
    // ⚠️ ต้องใช้ GROUP_CONCAT ในการ Query จริงสำหรับกลุ่ม แต่ใน COUNT ใช้ JOIN ธรรมดาได้
    
    // เงื่อนไขการค้นหา:
    // 1. ค้นหาจากชื่อโครงการ (subject_project) ตามภาษา
    // 2. ค้นหาจากชื่อกลุ่ม (group_name_X) ทุกภาษา
    $searchCondition = " AND (dn.{$subject_col} LIKE '%{$searchTerm}%'";
    
    // เพิ่มการค้นหาจากชื่อกลุ่มทุกภาษา
    $searchCondition .= " OR pg.group_name LIKE '%{$searchTerm}%'";
    $searchCondition .= " OR pg.group_name_en LIKE '%{$searchTerm}%'";
    $searchCondition .= " OR pg.group_name_cn LIKE '%{$searchTerm}%'";
    $searchCondition .= " OR pg.group_name_jp LIKE '%{$searchTerm}%'";
    $searchCondition .= " OR pg.group_name_kr LIKE '%{$searchTerm}%')";
}


// ------------------------
// ดึงข้อมูลรวม (Total Count Query) - **แก้ไขสำหรับ Many-to-Many Filter และ Search**
// ------------------------
$totalQuery = "SELECT COUNT(DISTINCT dn.project_id) as total
               FROM dn_project dn
               LEFT JOIN dn_project_doc dnc ON dn.project_id = dnc.project_id";

// เพิ่ม JOIN สำหรับ Filter กลุ่ม หรือ Search ด้วยชื่อกลุ่ม
if ($selectedGroup > 0 || $searchQuery) {
    $totalQuery .= " JOIN dn_project_group_relations r ON dn.project_id = r.project_id";
    $totalQuery .= " JOIN dn_project_groups pg ON r.group_id = pg.group_id";
}

$totalQuery .= " WHERE dn.del = '0'";

// ใช้เงื่อนไข Filter กลุ่มจากตารางเชื่อมโยง
if ($selectedGroup > 0) {
    $totalQuery .= " AND r.group_id = " . $selectedGroup;
}

// 💡 เพิ่มเงื่อนไขการค้นหา
$totalQuery .= $searchCondition;


$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalItems = $totalRow['total'];
$totalPages = ceil($totalItems / $perPage);

// ------------------------
// ดึงข้อมูลโปรเจกต์ (Fetch Project data) - **แก้ไขสำหรับ Many-to-Many และ Search**
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
            
            -- ดึงข้อมูลกลุ่มทั้งหมดที่เชื่อมโยง
            GROUP_CONCAT(DISTINCT pg.group_id) AS group_ids,
            GROUP_CONCAT(DISTINCT pg.group_name) AS group_names,
            GROUP_CONCAT(DISTINCT pg.group_name_en) AS group_names_en,
            GROUP_CONCAT(DISTINCT pg.group_name_cn) AS group_names_cn,
            GROUP_CONCAT(DISTINCT pg.group_name_jp) AS group_names_jp,
            GROUP_CONCAT(DISTINCT pg.group_name_kr) AS group_names_kr,
            GROUP_CONCAT(DISTINCT pg.image_path) AS group_images,
            
            GROUP_CONCAT(DISTINCT dnc.file_name) AS file_name,
            GROUP_CONCAT(DISTINCT dnc.file_path) AS pic_path
        FROM
            dn_project dn
        LEFT JOIN
            dn_project_doc dnc ON dn.project_id = dnc.project_id
                                 AND dnc.del = '0'
                                 AND dnc.status = '1'";
        
// 💡 ใช้ LEFT JOIN สำหรับ r และ pg เพื่อดึงโปรเจกต์ทั้งหมด (แม้ไม่มีกลุ่ม)
// แต่ถ้ามีการ Filter ด้วย $selectedGroup > 0 หรือ $searchQuery จะต้องใช้ JOIN เพื่อให้เงื่อนไขทำงาน
$joinType = ($selectedGroup > 0 || $searchQuery) ? "INNER JOIN" : "LEFT JOIN";

$sql .= "
        {$joinType}
            dn_project_group_relations r ON dn.project_id = r.project_id
        {$joinType}
            dn_project_groups pg ON r.group_id = pg.group_id
                                 AND pg.del = '0'
                                 AND pg.status = '1'
        WHERE
            dn.del = '0'";

// ใช้เงื่อนไข Filter กลุ่มจากตารางเชื่อมโยง
if ($selectedGroup > 0) {
    $sql .= " AND r.group_id = " . $selectedGroup;
}

// 💡 เพิ่มเงื่อนไขการค้นหา
$sql .= $searchCondition;


$sql .= "
GROUP BY dn.project_id
ORDER BY dn.date_create DESC, dn.project_id DESC
LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);

$boxesNews = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $title = $row[$subject_col] ?: $row['subject_project'];
        $description = $row[$description_col] ?: $row['description_project'];
        
        $content = $row['content_project'];
        
        $iframeSrc = null;
        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
        }

        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];
        $files = !empty($row['file_name']) ? explode(',', $row['file_name']) : [];

        $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

        // WebP conversion only (no resize)
        $projectImageWebP = !empty($paths) ? ensureWebPNativePj($paths[0]) : null;

        // ----------------------------------------------------
        // 🚨 การจัดการกลุ่มหลายกลุ่ม (Many-to-Many Group Handling)
        // ----------------------------------------------------
        $groupData = [];
        if (!empty($row['group_ids'])) {
            $ids = explode(',', $row['group_ids']);
            $names = explode(',', $row['group_names']);
            $names_en = explode(',', $row['group_names_en']);
            $names_cn = explode(',', $row['group_names_cn']);
            $names_jp = explode(',', $row['group_names_jp']);
            $names_kr = explode(',', $row['group_names_kr']);
            $images = explode(',', $row['group_images']);

            // กำหนดชื่อคอลัมน์กลุ่มปัจจุบัน
            $current_name_col_base = 'group_names';
            $current_name_col = $current_name_col_base . ($lang !== 'th' ? '_' . $lang : '');
            
            // ดึงค่า array ของชื่อกลุ่มตามภาษาปัจจุบัน
            $current_names_array = explode(',', $row[$current_name_col]);
            if ($lang === 'th') {
                 $current_names_array = $names; // ถ้าเป็น th ให้ใช้ group_names โดยตรง
            }


            // สร้าง Array ของ Group Data ที่ถูกต้องตามภาษา
            for ($i = 0; $i < count($ids); $i++) {
                
                // ใช้ชื่อตามภาษาปัจจุบัน หรือกลับไปใช้ชื่อหลัก (TH) ถ้าภาษาปัจจุบันไม่มี
                $groupName = isset($current_names_array[$i]) && $current_names_array[$i] ? $current_names_array[$i] : $names[$i]; 
                
                $groupData[] = [
                    'id' => $ids[$i],
                    'name' => htmlspecialchars($groupName),
                    // ใช้ ensureWebPNativePj สำหรับรูปภาพกลุ่ม
                    'image' => !empty($images[$i]) ? ensureWebPNativePj($images[$i]) : null 
                ];

                // จำกัดเพียง 3 กลุ่มแรกเท่านั้น
                if (count($groupData) >= 3) break; 
            }
        }
        
        // ----------------------------------------------------

        $boxesNews[] = [
            'id' => $row['project_id'],
            'image' => $projectImageWebP, 
            'date_time' => $row['date_create'],
            'title' => $title,
            'description' => $description,
            'iframe' => $iframe,
            'groups' => $groupData // ใช้ groups แทน group_name/group_image
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
}
?>

<style>
/* --------------------------------- */
/* CSS ที่เพิ่ม/แก้ไขใหม่ */
/* --------------------------------- */

/* Grid สำหรับรายการโปรเจกต์ */
.content-news {
    display: grid;
    /* ปรับ gap ให้ลดลงจากเดิม */
    gap: 15px; 
    /* ให้มี 3 คอลัมน์ ขนาดเท่ากัน (1fr) */
    grid-template-columns: repeat(3, 1fr); 
}

/* กล่องแต่ละโปรเจกต์ */
.box-news {
    /* ให้กล่องมีความสูงยืดหยุ่นตามเนื้อหาแต่ยังคงความสม่ำเสมอใน Grid Row */
    display: flex;
    flex-direction: column;
    /* ปรับ padding/margin ในกล่องลดลงตามต้องการ */
    padding: 0; 
    margin: 0;
    border: 1px solid #eee;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    /* เพิ่ม transition เพื่อความสวยงาม */
    transition: box-shadow 0.3s ease;
}

.box-news:hover {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.box-image a {
    display: block;
    height: 200px; /* กำหนดความสูงของรูปภาพให้คงที่ */
    overflow: hidden;
}

.box-image img, .box-image iframe {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.box-content {
    /* ใช้ flex-grow: 1 เพื่อให้เนื้อหาส่วนนี้ยืดเต็มพื้นที่ที่เหลือ (ถ้าต้องการให้กล่องสูงเท่ากัน) */
    flex-grow: 1; 
    padding: 15px; /* ลด padding ลง */
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* ช่วยดันส่วนท้ายสุด (ถ้ามี) */
}

.text-news {
    text-decoration: none;
    color: inherit;
    display: block; /* ทำให้ลิงก์ครอบคลุมทั้งหมด */
    /* ไม่ต้อง flex-grow: 1 ที่นี่ ปล่อยให้มันใหญ่เท่าที่จำเป็น */
}

/* จำกัด Description ให้ไม่เกิน 2 บรรทัด */
.line-clamp {
    overflow: hidden;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    /* ใช้สำหรับจำกัดจำนวนบรรทัด */
    -webkit-line-clamp: 2; 
    line-height: 1.4; /* ปรับ line-height เพื่อความสวยงาม */
    margin: 0 0 5px 0;
}

/* ตั้งค่าสำหรับหัวข้อ Title (อาจต้องใช้ line-clamp 2 หรือ 3) */
.box-content h5 {
    font-size: 16px;
    font-weight: bold;
    color: #333;
    -webkit-line-clamp: 2; /* จำกัด Title ไว้ 2 บรรทัด */
}

/* ตั้งค่าสำหรับ Description */
.box-content p {
    font-size: 14px;
    color: #666;
    -webkit-line-clamp: 2; /* จำกัด Description ไว้ 2 บรรทัด ตามต้องการ */
    margin-top: 5px;
}

/* ---------------------------------------------------- */
/* 🆕 สไตล์สำหรับส่วนไอคอน/ชื่อกลุ่ม (Group Info) - รองรับหลายกลุ่ม */
/* ---------------------------------------------------- */
.groups-info-container {
    display: flex; 
    /* จัดให้อยู่ทางขวา */
    justify-content: flex-end; 
    align-items: flex-start; /* จัดให้ไอคอนอยู่ด้านบนสุด */
    gap: 10px; /* ระยะห่างระหว่างไอคอนกลุ่ม */
    margin-bottom: 10px;
}

.group-info {
    display: flex;
    flex-direction: column;
    align-items: center; 
    text-align: center;
    /* จำกัดความกว้างรวมของกลุ่ม */
    max-width: 60px; 
}

.group-info-icon {
    /* เพิ่มขนาดไอคอน/รูปกลุ่ม */
    width: 35px; 
    height: 35px; 
    border-radius: 50%; 
    object-fit: cover;
    margin-bottom: 4px; 
    border: 1px solid #ddd; /* เพิ่มขอบเล็กน้อย */
}

.group-info-text {
    font-size: 10px; /* ลดขนาด font ชื่อกลุ่ม */
    color: #666;
    /* จำกัดชื่อกลุ่มให้แสดง 1 บรรทัด */
    max-width: 60px; 
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Responsive ปรับ layout เมื่อจอเล็กลง */
@media (max-width: 1024px) {
    .content-news {
        /* เปลี่ยนเป็น 2 คอลัมน์ */
        grid-template-columns: repeat(2, 1fr); 
    }
}

@media (max-width: 600px) {
    .content-news {
        /* เปลี่ยนเป็น 1 คอลัมน์ */
        grid-template-columns: 1fr; 
    }
}


/* --------------------------------- */
/* CSS เดิม (ปรับปรุงเล็กน้อยเพื่อความสอดคล้อง) */
/* --------------------------------- */

/* CSS สำหรับซ่อน Scrollbar */
.hide-scrollbar-x {
    /* สำหรับ Firefox */
    scrollbar-width: none;
    /* สำหรับ IE 10+ และ Edge */
    -ms-overflow-style: none;
}

/* สำหรับ WebKit (Chrome, Safari) */
.hide-scrollbar-x::-webkit-scrollbar {
    display: none;
}

/* เพิ่มเงาที่ขอบกล่องกลุ่ม */
.group-box-shadow {
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); /* เงาที่เข้มขึ้น */
    transition: box-shadow 0.3s ease-in-out;
}

.group-box-shadow:hover {
    box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.6); /* เงาเข้มขึ้นเมื่อ hover */
}

/* --------------------------------- */
/* CSS สำหรับ ปุ่มเลื่อนและคอนเทนเนอร์ (ไม่มีการเปลี่ยนแปลงหลัก) */
/* --------------------------------- */
.group-carousel-container {
    position: relative;
    /* เพิ่ม padding ซ้าย-ขวา เพื่อให้มีที่ว่างสำหรับปุ่มที่เลื่อนออกไปด้านข้าง */
    padding: 20px 50px; /* เพิ่มระยะห่าง 50px */
}

.group-list-container {
    overflow-x: auto;
    white-space: nowrap;
    display: flex;
    gap: 20px;
    align-items: center;
    scroll-behavior: smooth; /* เลื่อนอย่างราบรื่น */
}

.group-list-wrapper {
    display: inline-flex;
    gap: 20px;
    align-items: center;
}

.group-item {
    text-decoration: none; 
    display: inline-block; 
    text-align: center; 
    min-width: 100px;
    flex-shrink: 0; /* ป้องกันไม่ให้รายการหดตัว */
}

/* สไตล์ปุ่มเลื่อนที่กำหนดเอง (ปรับปรุง left/right) */
.scroll-btn-custom {
    position: absolute;
    top: 50%; /* จัดให้อยู่ตรงกลางแนวตั้ง */
    transform: translateY(-50%);
    background-color: #77777738; /* สีส้มตามตัวอย่าง */
    color: white;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
    z-index: 10;
    font-size: 20px;
    line-height: 1;
    border-radius: 50%; /* ทำให้เป็นวงกลม */
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s, opacity 0.3s;
    opacity: 0.9;
}

.scroll-btn-custom:hover {
    background-color: #ff9800; /* สีส้มเข้มขึ้นเมื่อ hover */
    opacity: 1;
}

.scroll-btn-custom.left {
    /* ขยับปุ่มออกไปด้านซ้ายนอกคอนเทนเนอร์ */
    left: 0; 
}

.scroll-btn-custom.right {
    /* ขยับปุ่มออกไปด้านขวาออกคอนเทนเนอร์ */
    right: 0; 
}

/* ซ่อนปุ่มเมื่ออยู่บนหน้าจอเล็ก (ถ้าต้องการ) */
@media (max-width: 768px) {
    .scroll-btn-custom {
        display: none;
    }
    /* ยกเลิก padding เพื่อให้เนื้อหาเต็มจอ */
    .group-carousel-container {
        padding: 20px 0;
    }
}

</style>


<?php if (!empty($groups)): ?>
<div class="group-carousel-container">
    <button class="scroll-btn-custom left" onclick="scrollGroups(-1)">❮</button>

    <div class="hide-scrollbar-x group-list-container" id="groupListScroll">
        <div class="group-list-wrapper">
            <a href="?project&lang=<?php echo htmlspecialchars($lang); ?>" 
                class="group-item" 
                id="group-item-0"> 
                <div class="group-box-shadow" style="width: 140px; height: 80px; border-radius: 10%; padding:10px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; margin: 0 auto; <?php echo $selectedGroup == 0 ? 'border: 3px solid #ff9800;' : ''; ?>">
                    <img src="https://www.perfume.com//public/news_img/691d7af8bb1bb_1763539704.png" alt="ทั้งหมด" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <p style="margin-top: 10px; color: #333; font-size: 14px; white-space: normal; word-wrap: break-word;">
                    <?php 
                    $allText = ['th' => 'ทั้งหมด', 'en' => 'All', 'cn' => '全部', 'jp' => '全て', 'kr' => '전체'];
                    echo $allText[$lang]; 
                    ?>
                </p>
            </a>

            <?php foreach ($groups as $group): ?>
                <?php
                $gName = $group[$group_name_col] ?: $group['group_name'];
                $gImage = $group['image_path'] ? ensureWebPNativePj($group['image_path']) : null;
                ?>
                <a href="?project&group=<?php echo $group['group_id']; ?>&lang=<?php echo htmlspecialchars($lang); ?>" 
                    class="group-item"
                    id="group-item-<?php echo $group['group_id']; ?>">
                    <div class="group-box-shadow" style="width: 140px; height: 80px; border-radius: 10%; padding:10px; overflow: hidden; background: #f0f0f0; margin: 0 auto; <?php echo $selectedGroup == $group['group_id'] ? 'border: 3px solid #ff9800;' : ''; ?>">
                        <?php if ($gImage): ?>
                            <img src="<?php echo htmlspecialchars(str_replace('../', '', $gImage)); ?>" 
                                        alt="<?php echo htmlspecialchars($gName); ?>"
                                        style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-folder" style="font-size: 32px; color: #666;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p style="margin-top: 10px; color: #333; font-size: 14px; white-space: normal; word-wrap: break-word;">
                        <?php echo htmlspecialchars($gName); ?>
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <button class="scroll-btn-custom right" onclick="scrollGroups(1)">❯</button>
</div>

<script>
    function scrollGroups(direction) {
        const container = document.getElementById('groupListScroll');
        // กำหนดระยะเลื่อน: 80% ของความกว้างคอนเทนเนอร์ (ปรับได้)
        const scrollDistance = container.clientWidth * 0.8; 

        // เลื่อนคอนเทนเนอร์ตามทิศทางที่กำหนด
        container.scrollLeft += direction * scrollDistance;
    }

    // ---------------------------------
    // ** ฟังก์ชันใหม่สำหรับเลื่อนไปที่กลุ่มที่เลือก **
    // ---------------------------------
    document.addEventListener('DOMContentLoaded', (event) => {
        const selectedGroupId = <?php echo $selectedGroup; ?>;
        const selectedElement = document.getElementById(`group-item-${selectedGroupId}`);
        const container = document.getElementById('groupListScroll');
        
        if (selectedElement && container) {
            // คำนวณตำแหน่งที่ต้องเลื่อน: 
            // ตำแหน่งของ Element ที่เลือก (rel. to viewport) + scroll ปัจจุบัน - (ความกว้างของ container / 2)
            // การลบด้วยครึ่งหนึ่งของความกว้างคอนเทนเนอร์จะช่วยให้รายการที่เลือกอยู่ค่อนมาตรงกลางมากขึ้น
            const elementOffset = selectedElement.offsetLeft - container.offsetLeft;
            const containerCenter = container.clientWidth / 2;
            const scrollTarget = elementOffset - containerCenter + (selectedElement.clientWidth / 2);
            
            // เลื่อนคอนเทนเนอร์ไปที่ตำแหน่งเป้าหมายอย่างราบรื่น
            // การใช้ requestAnimationFrame เพื่อให้แน่ใจว่า DOM Render เสร็จก่อนจะเลื่อน
            window.requestAnimationFrame(() => {
                container.scrollLeft = scrollTarget;
            });
        }
    });
</script>

<?php endif; ?>

<div style="display: flex; justify-content: space-between; padding: 20px 0;">
    <div></div>
    <div>
        <form method="GET" action="">
            <input type="hidden" name="project" value=""> 
            
            <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
            <?php if ($selectedGroup > 0): ?>
                <input type="hidden" name="group" value="<?php echo $selectedGroup; ?>">
            <?php endif; ?>
            <div class="input-group">
                <?php
                $placeholderText = [
                    'en' => 'Search project...',
                    'cn' => '搜索项目...',
                    'jp' => 'プロジェクトを検索...',
                    'kr' => '프로젝트 검색...',
                    'th' => 'ค้นหาโครงการ...'
                ];
                ?>
                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="<?php echo $placeholderText[$lang]; ?>">
                <button class="btn-search" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="content-news">
    <?php 
    // ตรวจสอบว่ามีผลลัพธ์หรือไม่
    if (!empty($boxesNews)):
        foreach ($boxesNews as $index => $box): ?>
            <div class="box-news" >
                <div class="box-image" style="min-height: 200px;">
                    <?php
                        $encodedId = urlencode(base64_encode($box['id']));
                        $detailUrl = "?project_detail&id=" . $encodedId . "&lang=" . htmlspecialchars($lang);
                    ?>
                    <a href="<?php echo $detailUrl; ?>" class="text-news">
                        <?php
                        if(!empty($box['iframe'])){
                            echo '<iframe frameborder="0" src="' . $box['iframe'] . '" width="100%" height="100%" class="note-video-clip"></iframe>';
                        } else if (!empty($box['image'])){
                            echo '<picture>';
                            echo '<source srcset="' . htmlspecialchars($box['image']) . '" type="image/webp">';
                            echo '<img src="' . htmlspecialchars(str_replace('../','',$box['image'])) . '" alt="Image for ' . htmlspecialchars($box['title']) . '" style="width: 100%; height: 200px; object-fit: cover;" loading="lazy">';
                            echo '</picture>';  
                        } else {
                            // ใช้รูปภาพ placeholder หากไม่มีรูปภาพหรือวิดีโอ
                            echo '<img src="path/to/default/project_placeholder.jpg" alt="No image available" style="width: 100%; height: 200px; object-fit: cover;">';
                        }
                        ?>
                    </a>
                </div>
                <div class="box-content">
                    
                    <?php if (!empty($box['groups'])): ?>
                        <div class="groups-info-container">
                            <?php foreach ($box['groups'] as $group): ?>
                                <div class="group-info">
                                    <?php if ($group['image']): ?>
                                        <img src="<?php echo htmlspecialchars(str_replace('../', '', $group['image'])); ?>" 
                                                     alt="<?php echo $group['name']; ?>"
                                                     class="group-info-icon">
                                    <?php else: ?>
                                        <div class="group-info-icon" style="background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-folder-open" style="font-size: 18px; color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                        <span class="group-info-text">
                                            <?php echo $group['name']; ?>
                                        </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <a href="<?php echo $detailUrl; ?>" class="text-news">
                        <h5 class="line-clamp"><?php echo htmlspecialchars($box['title']); ?></h5>
                        <p class="line-clamp"><?php echo htmlspecialchars($box['description']); ?></p>
                    </a>
                </div>
            </div>
        <?php endforeach; 
    else: // แสดงข้อความเมื่อไม่พบผลลัพธ์
        $noResultsText = [
            'en' => 'No project found.',
            'cn' => '未找到项目。',
            'jp' => 'プロジェクトが見つかりません。',
            'kr' => '프로젝트를 찾을 수 없습니다.',
            'th' => 'ไม่พบโปรเจกต์'
        ];
        ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
            <p style="font-size: 18px; color: #999;"><?php echo $noResultsText[$lang]; ?></p>
        </div>
    <?php endif; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?project&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?><?php echo $selectedGroup > 0 ? '&group=' . $selectedGroup : ''; ?>">
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
        <a href="?project&page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?><?php echo $selectedGroup > 0 ? '&group=' . $selectedGroup : ''; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>>
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="?project&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>&lang=<?php echo htmlspecialchars($lang); ?><?php echo $selectedGroup > 0 ? '&group=' . $selectedGroup : ''; ?>">
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