<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add AI Companion</title>

    <link rel="icon" type="image/x-icon" href="../../../public/product_images/695e0bf362d49_1767771123.jpg">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <link href='css/ai-companion.css?v=<?php echo time(); ?>' rel='stylesheet'>
</head>

<?php 
include '../check_permission.php';
require_once('../../../lib/connect.php');
global $conn;

$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    }
} else {
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang'];
    }
}

$items_query = "
    SELECT 
        pi.item_id, 
        pi.serial_number
    FROM product_items pi
    LEFT JOIN ai_companions ai ON pi.item_id = ai.item_id AND ai.del = 0
    WHERE pi.del = 0 
    AND ai.ai_id IS NULL
    ORDER BY pi.serial_number
";
$items_result = $conn->query($items_query);
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}

$emotions = [
    'happy'      => ['icon' => 'fa-smile',             'color' => '#e6a817', 'label' => 'Happy 😊',      'desc' => 'ตอบแบบดีใจ, ชมเชย, ข่าวดี'],
    'sad'        => ['icon' => 'fa-sad-tear',           'color' => '#5a7fa8', 'label' => 'Sad 😢',        'desc' => 'ปลอบใจ, เศร้า, เห็นอกเห็นใจ'],
    'excited'    => ['icon' => 'fa-grin-stars',         'color' => '#e06530', 'label' => 'Excited 🤩',    'desc' => 'แนะนำสิ่งใหม่, ตื่นเต้น'],
    'calm'       => ['icon' => 'fa-smile-beam',         'color' => '#2d8a55', 'label' => 'Calm 😌',       'desc' => 'ตอบข้อมูลทั่วไป, เป็นกลาง'],
    'thinking'   => ['icon' => 'fa-brain',              'color' => '#4a6fd4', 'label' => 'Thinking 🤔',   'desc' => 'วิเคราะห์, คำถามซับซ้อน'],
    'surprised'  => ['icon' => 'fa-surprise',           'color' => '#b84a8c', 'label' => 'Surprised 😲',  'desc' => 'ข้อมูลน่าสนใจ, ไม่คาดคิด'],
    'empathetic' => ['icon' => 'fa-hand-holding-heart', 'color' => '#c04040', 'label' => 'Empathetic 🤗', 'desc' => 'เข้าใจความรู้สึก, สนับสนุน'],
];

include '../template/header.php';
?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="page-header">
                <h4>
                    <i class="fas fa-robot"></i>
                    Add AI Companion
                </h4>
                <button type='button' id='backToAIList' class='btn btn-secondary'>
                    <i class='fas fa-arrow-left'></i>
                    Back
                </button>
            </div>

            <form id="formAICompanion" enctype="multipart/form-data">
                <div class="row">

                    <!-- ============================================================
                         Left Column: Media & Basic Info
                         ============================================================ -->
                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-user-robot"></i> AI Media & Info</h5>
                            </div>
                            <div class="card-body">

                                <!-- Item Selection -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-bottle-droplet"></i> Select Perfume Bottle *</label>
                                    <select class="form-control" id="item_id" name="item_id" required>
                                        <option value="">-- Select Bottle --</option>
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?= $item['item_id'] ?>">
                                                Serial Number: <?= htmlspecialchars($item['serial_number']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Each bottle can only have one AI Companion
                                    </small>
                                </div>

                                <!-- AI Code -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-qrcode"></i> AI Code (Unique) *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="ai_code" name="ai_code" required placeholder="e.g., AI-ROSE-001">
                                        <button class="btn btn-outline-secondary" type="button" id="btnGenerateCode">
                                            <i class="fas fa-random"></i> Generate
                                        </button>
                                    </div>
                                    <small class="text-muted">This code will be used for QR scanning</small>
                                </div>

                                <!-- AI Avatar -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-image"></i> AI Avatar Image</label>
                                    <div class="upload-zone" onclick="document.getElementById('aiAvatar').click()">
                                        <div id="avatarPreview">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <p>Click to upload avatar</p>
                                            <small>PNG, JPG, GIF (Max 5MB)</small>
                                        </div>
                                    </div>
                                    <input type="file" id="aiAvatar" name="ai_avatar" accept="image/*" style="display: none;">
                                </div>

                                <!-- AI Intro Video -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-video"></i> AI Intro Video</label>
                                    <div class="upload-zone" onclick="document.getElementById('aiVideo').click()">
                                        <div id="videoPreview">
                                            <i class="fas fa-film"></i>
                                            <p>Click to upload intro video</p>
                                            <small>MP4, WebM (Max 50MB)</small>
                                        </div>
                                    </div>
                                    <input type="file" id="aiVideo" name="ai_video" accept="video/*" style="display: none;">
                                    <small class="text-muted">วิดีโอแนะนำตัวเมื่อเริ่มต้นใช้งาน AI</small>
                                </div>

                                <!-- Idle Videos -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-pause-circle"></i> Idle Videos (หลายไฟล์)</label>
                                    <div class="upload-zone" onclick="document.getElementById('idleVideos').click()">
                                        <i class="fas fa-pause-circle"></i>
                                        <p>Click to upload idle videos</p>
                                        <small>MP4, WebM (Max 50MB each) - Multiple files allowed</small>
                                    </div>
                                    <input type="file" id="idleVideos" name="idle_videos[]" accept="video/*" multiple style="display: none;">
                                    <div id="idleVideosPreview" class="video-grid"></div>
                                    <small class="text-muted">วิดีโอที่แสดงเมื่อ AI ไม่ได้พูด (จะ Random เล่น)</small>
                                </div>

                                <!-- Talking Videos -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-play-circle"></i> Talking Videos (หลายไฟล์)</label>
                                    <div class="upload-zone" onclick="document.getElementById('talkingVideos').click()">
                                        <i class="fas fa-play-circle"></i>
                                        <p>Click to upload talking videos</p>
                                        <small>MP4, WebM (Max 50MB each) - Multiple files allowed</small>
                                    </div>
                                    <input type="file" id="talkingVideos" name="talking_videos[]" accept="video/*" multiple style="display: none;">
                                    <div id="talkingVideosPreview" class="video-grid"></div>
                                    <small class="text-muted">วิดีโอที่แสดงเมื่อ AI กำลังพูด (จะ Random เล่น)</small>
                                </div>

                                <!-- 2D EMOTION VIDEOS -->
                                <div class="form-group mb-4">
                                    <label>
                                        <i class="fas fa-heart"></i>
                                        2D Emotion Videos/GIFs
                                        <span class="emotion-mode-badge m2d">2D</span>
                                    </label>
                                    <small class="text-muted d-block mb-2">
                                        <i class="fas fa-info-circle"></i>
                                        วิดีโอสั้น/GIF แสดงอารมณ์ข้าง Avatar ใน 2D Mode — แต่ละอารมณ์อัพโหลดได้หลายไฟล์ (จะ Random เล่น)
                                    </small>
                                    <div class="emotion-manager-card">
                                        <div class="emotion-manager-header">
                                            <div class="emotion-tab-bar" role="tablist">
                                                <?php $first2d = true; foreach ($emotions as $ek => $ei): ?>
                                                <button type="button"
                                                        class="emo-tab-btn <?= $first2d ? 'active' : '' ?>"
                                                        data-target="pane2d-<?= $ek ?>"
                                                        data-manager="mgr2d"
                                                        onclick="switchEmoTab(this)">
                                                    <span class="emo-dot" style="background:<?= $ei['color'] ?>"></span>
                                                    <?= $ei['label'] ?>
                                                    <span class="emo-count" id="cnt2d-<?= $ek ?>">0</span>
                                                </button>
                                                <?php $first2d = false; endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="emotion-manager-body" id="mgr2d">
                                            <?php $first2d = true; foreach ($emotions as $ek => $ei): ?>
                                            <div class="emo-pane <?= $first2d ? 'active' : '' ?>" id="pane2d-<?= $ek ?>">
                                                <div class="emo-pane-title"><?= $ei['label'] ?></div>
                                                <div class="emo-pane-desc"><?= $ei['desc'] ?></div>
                                                <div class="emo-video-grid" id="grid2d-<?= $ek ?>"></div>
                                                <div class="emo-drop-zone" id="dz2d-<?= $ek ?>">
                                                    <input type="file"
                                                           id="emotionVideos_<?= $ek ?>"
                                                           name="emotion_videos[<?= $ek ?>][]"
                                                           accept="video/*,image/gif"
                                                           multiple
                                                           data-emotion="<?= $ek ?>"
                                                           data-grid="grid2d-<?= $ek ?>"
                                                           data-counter="cnt2d-<?= $ek ?>">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <div class="dz-text">ลาก & วางไฟล์ที่นี่ หรือ คลิกเพื่อเลือก</div>
                                                    <div class="dz-hint">MP4, WebM, GIF — อัพโหลดได้หลายไฟล์</div>
                                                </div>
                                            </div>
                                            <?php $first2d = false; endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3D EMOTION VIDEOS -->
                                <div class="form-group mb-4">
                                    <label>
                                        <i class="fas fa-cube"></i>
                                        3D Emotion Videos/GIFs
                                        <span class="emotion-mode-badge m3d">3D</span>
                                    </label>
                                    <small class="text-muted d-block mb-2">
                                        <i class="fas fa-info-circle"></i>
                                        วิดีโอ/GIF อารมณ์สำหรับ 3D Mode — แต่ละอารมณ์มี <strong>Idle</strong> และ <strong>Talking</strong> แยกกัน
                                    </small>
                                    <div class="emotion-manager-card">
                                        <div class="emotion-manager-header">
                                            <div class="emotion-tab-bar" role="tablist">
                                                <?php $first3d = true; foreach ($emotions as $ek => $ei): ?>
                                                <button type="button"
                                                        class="emo-tab-btn <?= $first3d ? 'active' : '' ?>"
                                                        data-target="pane3d-<?= $ek ?>"
                                                        data-manager="mgr3d"
                                                        onclick="switchEmoTab(this)">
                                                    <span class="emo-dot" style="background:<?= $ei['color'] ?>"></span>
                                                    <?= $ei['label'] ?>
                                                    <span class="emo-count" id="cnt3d-<?= $ek ?>">0</span>
                                                </button>
                                                <?php $first3d = false; endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="emotion-manager-body" id="mgr3d">
                                            <?php $first3d = true; foreach ($emotions as $ek => $ei): ?>
                                            <div class="emo-pane <?= $first3d ? 'active' : '' ?>" id="pane3d-<?= $ek ?>">
                                                <div class="emo-pane-title"><?= $ei['label'] ?> — 3D Mode</div>
                                                <div class="emo-pane-desc"><?= $ei['desc'] ?> — แต่ละ state จะ Random เล่น</div>
                                                <div class="state-split">
                                                    <div class="state-col">
                                                        <div class="state-col-head">
                                                            <span class="state-label-text idle">
                                                                <i class="fas fa-pause-circle"></i> Idle (ยืนเฉย)
                                                            </span>
                                                            <span class="state-count-badge" id="cnt3d-<?= $ek ?>-idle">0</span>
                                                        </div>
                                                        <div class="state-col-body">
                                                            <div class="emo-video-grid" id="grid3d-<?= $ek ?>-idle"></div>
                                                            <div class="emo-drop-zone compact">
                                                                <input type="file"
                                                                       id="em3d_<?= $ek ?>_idle"
                                                                       name="emotion_videos_3d[<?= $ek ?>][idle][]"
                                                                       accept="video/*,image/gif"
                                                                       multiple
                                                                       data-emotion="<?= $ek ?>"
                                                                       data-state="idle"
                                                                       data-grid="grid3d-<?= $ek ?>-idle"
                                                                       data-counter="cnt3d-<?= $ek ?>-idle"
                                                                       data-parent-counter="cnt3d-<?= $ek ?>">
                                                                <i class="fas fa-plus"></i>
                                                                <div class="dz-text">เพิ่ม Idle</div>
                                                                <div class="dz-hint">MP4, GIF</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="state-col" style="border-color:#c3e6cb;">
                                                        <div class="state-col-head talking-head">
                                                            <span class="state-label-text talking">
                                                                <i class="fas fa-play-circle"></i> Talking (กำลังพูด)
                                                            </span>
                                                            <span class="state-count-badge" id="cnt3d-<?= $ek ?>-talking">0</span>
                                                        </div>
                                                        <div class="state-col-body">
                                                            <div class="emo-video-grid" id="grid3d-<?= $ek ?>-talking"></div>
                                                            <div class="emo-drop-zone compact">
                                                                <input type="file"
                                                                       id="em3d_<?= $ek ?>_talking"
                                                                       name="emotion_videos_3d[<?= $ek ?>][talking][]"
                                                                       accept="video/*,image/gif"
                                                                       multiple
                                                                       data-emotion="<?= $ek ?>"
                                                                       data-state="talking"
                                                                       data-grid="grid3d-<?= $ek ?>-talking"
                                                                       data-counter="cnt3d-<?= $ek ?>-talking"
                                                                       data-parent-counter="cnt3d-<?= $ek ?>">
                                                                <i class="fas fa-plus"></i>
                                                                <div class="dz-text">เพิ่ม Talking</div>
                                                                <div class="dz-hint">MP4, GIF</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php $first3d = false; endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- AI Voice -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-volume-up"></i> AI Voice (ElevenLabs)</label>
                                    <input type="text" class="form-control mb-2" id="voice_id" name="voice_id"
                                        placeholder="Enter Voice ID (e.g., UdFuclGJ1KL5tAeoBeE0)">
                                    <input type="text" class="form-control mb-2" id="voice_name" name="voice_name"
                                        placeholder="Voice Name (e.g., Rachel - Female, Multilingual)">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Enter ElevenLabs Voice ID and name
                                    </small>
                                </div>

                                <!-- ==========================================
                                     STARTUP ACTIONS
                                     ========================================== -->
                                <div class="form-group mb-4">
                                    <label>
                                        <i class="fas fa-play-circle"></i>
                                        Startup Actions
                                        <span class="badge badge-info" style="font-size:11px;vertical-align:middle;margin-left:6px;background:#17a2b8;color:#fff;padding:3px 8px;border-radius:12px;">เมื่อเปิดแอป</span>
                                    </label>
                                    <small class="text-muted d-block mb-3">
                                        <i class="fas fa-info-circle"></i>
                                        เลือกว่า AI จะพูดอะไรหลังจากทักทาย — ถ้าไม่เปิดจะข้ามไปเลย
                                    </small>

                                    <div style="background:#f8f9ff;border:1px solid #d0d7ff;border-radius:12px;padding:18px 20px;">

                                        <!-- Weather -->
                                        <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 0;border-bottom:1px solid #e8ecff;">
                                            <div style="padding-top:2px;">
                                                <div class="form-check form-switch" style="margin:0;">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="sa_weather" name="sa_weather" value="1"
                                                           style="width:42px;height:22px;cursor:pointer;">
                                                </div>
                                            </div>
                                            <div style="flex:1;">
                                                <div style="font-weight:600;font-size:14px;color:#333;">
                                                    <i class="fas fa-cloud-sun" style="color:#f5a623;margin-right:6px;"></i>
                                                    พยากรณ์อากาศ
                                                </div>
                                                <div style="font-size:12px;color:#777;margin-top:3px;">
                                                    AI จะรายงานสภาพอากาศ ณ ตำแหน่งของ user (ต้องอนุญาต Location)
                                                </div>
                                            </div>
                                        </div>

                                        <!-- World News -->
                                        <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 0;border-bottom:1px solid #e8ecff;">
                                            <div style="padding-top:2px;">
                                                <div class="form-check form-switch" style="margin:0;">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="sa_news_world" name="sa_news_world" value="1"
                                                           style="width:42px;height:22px;cursor:pointer;">
                                                </div>
                                            </div>
                                            <div style="flex:1;">
                                                <div style="font-weight:600;font-size:14px;color:#333;">
                                                    <i class="fas fa-globe" style="color:#4a90e2;margin-right:6px;"></i>
                                                    ข่าวระดับโลก (Top World News)
                                                </div>
                                                <div style="font-size:12px;color:#777;margin-top:3px;">
                                                    AI จะเล่าข่าวสำคัญระดับโลกประจำวัน (ดึงจาก NewsAPI)
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Country News -->
                                        <div style="display:flex;align-items:flex-start;gap:14px;padding:12px 0;">
                                            <div style="padding-top:2px;">
                                                <div class="form-check form-switch" style="margin:0;">
                                                    <input class="form-check-input" type="checkbox"
                                                           id="sa_news_country" name="sa_news_country" value="1"
                                                           style="width:42px;height:22px;cursor:pointer;"
                                                           onchange="toggleCountryPicker(this)">
                                                </div>
                                            </div>
                                            <div style="flex:1;">
                                                <div style="font-weight:600;font-size:14px;color:#333;">
                                                    <i class="fas fa-flag" style="color:#27ae60;margin-right:6px;"></i>
                                                    ข่าวระดับประเทศ (Country News)
                                                </div>
                                                <div style="font-size:12px;color:#777;margin-top:3px;">
                                                    AI จะเล่าข่าวในประเทศที่เลือก
                                                </div>
                                                <div id="countryPickerWrap" style="margin-top:10px;display:none;gap:10px;flex-wrap:wrap;align-items:center;">
                                                    <div>
                                                        <label style="font-size:12px;font-weight:600;color:#555;margin-bottom:4px;display:block;">ประเทศ</label>
                                                        <select class="form-control form-control-sm" id="sa_country_code" name="sa_country_code" style="min-width:160px;">
                                                            <option value="th">🇹🇭 ไทย (Thailand)</option>
                                                            <option value="us">🇺🇸 สหรัฐฯ (US)</option>
                                                            <option value="gb">🇬🇧 อังกฤษ (UK)</option>
                                                            <option value="jp">🇯🇵 ญี่ปุ่น (Japan)</option>
                                                            <option value="cn">🇨🇳 จีน (China)</option>
                                                            <option value="kr">🇰🇷 เกาหลี (Korea)</option>
                                                            <option value="sg">🇸🇬 สิงคโปร์ (SG)</option>
                                                            <option value="au">🇦🇺 ออสเตรเลีย (AU)</option>
                                                            <option value="de">🇩🇪 เยอรมนี (DE)</option>
                                                            <option value="fr">🇫🇷 ฝรั่งเศส (FR)</option>
                                                        </select>
                                                    </div>
                                                    <div style="flex:1;padding-top:20px;">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle text-primary"></i>
                                                            AI จะพูดตามภาษาที่ตั้งไว้ใน Preferred Language ของ companion อัตโนมัติ
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- END STARTUP ACTIONS -->

                                <!-- Status -->
                                <div class="form-group">
                                    <label><i class="fas fa-toggle-on"></i> Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="1" selected>Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- ============================================================
                         Right Column: Multilingual Content
                         ============================================================ -->
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header p-0">
                                <ul class="nav nav-tabs" id="languageTabs" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#th">
                                            <img src="https://flagcdn.com/w20/th.png" class="flag-icon"> ไทย
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#en">
                                            <img src="https://flagcdn.com/w20/gb.png" class="flag-icon"> English
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cn">
                                            <img src="https://flagcdn.com/w20/cn.png" class="flag-icon"> 中文
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#jp">
                                            <img src="https://flagcdn.com/w20/jp.png" class="flag-icon"> 日本語
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#kr">
                                            <img src="https://flagcdn.com/w20/gb.png" class="flag-icon"> 한국어
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">

                                    <!-- Thai -->
                                    <div class="tab-pane fade show active" id="th">
                                        <div class="form-group mb-3">
                                            <label>AI Name (TH) *</label>
                                            <input type="text" class="form-control" id="ai_name_th" name="ai_name_th" required placeholder="เช่น โรส ผู้เข้าใจคุณ">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (TH)</label>
                                            <textarea class="form-control" id="system_prompt_th" name="system_prompt_th" rows="4" placeholder="คุณคือ AI ที่รู้ใจและเข้าใจผู้ใช้..."></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (TH)</label>
                                            <textarea class="form-control" id="perfume_knowledge_th" name="perfume_knowledge_th" rows="5"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (TH)</label>
                                            <textarea class="form-control" id="style_suggestions_th" name="style_suggestions_th" rows="5"></textarea>
                                        </div>
                                    </div>

                                    <!-- English -->
                                    <div class="tab-pane fade" id="en">
                                        <div class="form-group mb-3">
                                            <label>AI Name (EN)</label>
                                            <input type="text" class="form-control" id="ai_name_en" name="ai_name_en" placeholder="e.g., Rose - Your Understanding Friend">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (EN)</label>
                                            <textarea class="form-control" id="system_prompt_en" name="system_prompt_en" rows="4"></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (EN)</label>
                                            <textarea class="form-control" id="perfume_knowledge_en" name="perfume_knowledge_en" rows="5"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (EN)</label>
                                            <textarea class="form-control" id="style_suggestions_en" name="style_suggestions_en" rows="5"></textarea>
                                        </div>
                                    </div>

                                    <!-- Chinese -->
                                    <div class="tab-pane fade" id="cn">
                                        <div class="form-group mb-3">
                                            <label>AI Name (CN)</label>
                                            <input type="text" class="form-control" id="ai_name_cn" name="ai_name_cn">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (CN)</label>
                                            <textarea class="form-control" id="system_prompt_cn" name="system_prompt_cn" rows="4"></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (CN)</label>
                                            <textarea class="form-control" id="perfume_knowledge_cn" name="perfume_knowledge_cn" rows="5"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (CN)</label>
                                            <textarea class="form-control" id="style_suggestions_cn" name="style_suggestions_cn" rows="5"></textarea>
                                        </div>
                                    </div>

                                    <!-- Japanese -->
                                    <div class="tab-pane fade" id="jp">
                                        <div class="form-group mb-3">
                                            <label>AI Name (JP)</label>
                                            <input type="text" class="form-control" id="ai_name_jp" name="ai_name_jp">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (JP)</label>
                                            <textarea class="form-control" id="system_prompt_jp" name="system_prompt_jp" rows="4"></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (JP)</label>
                                            <textarea class="form-control" id="perfume_knowledge_jp" name="perfume_knowledge_jp" rows="5"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (JP)</label>
                                            <textarea class="form-control" id="style_suggestions_jp" name="style_suggestions_jp" rows="5"></textarea>
                                        </div>
                                    </div>

                                    <!-- Korean -->
                                    <div class="tab-pane fade" id="kr">
                                        <div class="form-group mb-3">
                                            <label>AI Name (KR)</label>
                                            <input type="text" class="form-control" id="ai_name_kr" name="ai_name_kr">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (KR)</label>
                                            <textarea class="form-control" id="system_prompt_kr" name="system_prompt_kr" rows="4"></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (KR)</label>
                                            <textarea class="form-control" id="perfume_knowledge_kr" name="perfume_knowledge_kr" rows="5"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (KR)</label>
                                            <textarea class="form-control" id="style_suggestions_kr" name="style_suggestions_kr" rows="5"></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="button" id="submitAddAI" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Create AI Companion
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/ai-companion.js?v=<?php echo time(); ?>'></script>
    <script>
    function toggleCountryPicker(checkbox) {
        document.getElementById('countryPickerWrap').style.display = checkbox.checked ? 'flex' : 'none';
    }
    </script>
</body>
</html>