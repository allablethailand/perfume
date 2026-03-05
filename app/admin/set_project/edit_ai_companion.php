<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit AI Companion</title>

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

if (!isset($_GET['ai_id'])) {
    echo "<script>alert('AI ID is missing'); window.location.href='list_ai_companions.php';</script>";
    exit;
}

$ai_id = intval($_GET['ai_id']);

$stmt = $conn->prepare("SELECT * FROM ai_companions WHERE ai_id = ? AND del = 0");
$stmt->bind_param("i", $ai_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('AI Companion not found'); window.location.href='list_ai_companions.php';</script>";
    exit;
}

$ai = $result->fetch_assoc();
$stmt->close();

// Parse emotion_videos JSON
$emotion_videos_data = json_decode($ai['emotion_videos'] ?? '{}', true) ?: [];

$items_query = "
    SELECT 
        pi.item_id, 
        pi.serial_number
    FROM product_items pi
    LEFT JOIN ai_companions ai ON pi.item_id = ai.item_id AND ai.del = 0 AND ai.ai_id != ?
    WHERE pi.del = 0 
    AND (ai.ai_id IS NULL OR pi.item_id = ?)
    ORDER BY pi.serial_number
";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("ii", $ai_id, $ai['item_id']);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
include '../template/header.php';

$emotions = [
    'happy'      => ['icon' => 'fa-smile',            'color' => '#ffc107', 'label' => 'Happy 😊',      'desc' => 'ตอบแบบดีใจ, ชมเชย, ข่าวดี'],
    'sad'        => ['icon' => 'fa-sad-tear',          'color' => '#6c757d', 'label' => 'Sad 😢',        'desc' => 'ปลอบใจ, เศร้า, เห็นอกเห็นใจ'],
    'excited'    => ['icon' => 'fa-grin-stars',        'color' => '#fd7e14', 'label' => 'Excited 🤩',    'desc' => 'แนะนำสิ่งใหม่, ตื่นเต้น'],
    'calm'       => ['icon' => 'fa-smile-beam',        'color' => '#28a745', 'label' => 'Calm 😌',       'desc' => 'ตอบข้อมูลทั่วไป, เป็นกลาง'],
    'thinking'   => ['icon' => 'fa-brain',             'color' => '#007bff', 'label' => 'Thinking 🤔',   'desc' => 'วิเคราะห์, คำถามซับซ้อน'],
    'surprised'  => ['icon' => 'fa-surprise',          'color' => '#e83e8c', 'label' => 'Surprised 😲',  'desc' => 'ข้อมูลน่าสนใจ, ไม่คาดคิด'],
    'empathetic' => ['icon' => 'fa-hand-holding-heart','color' => '#dc3545', 'label' => 'Empathetic 🤗', 'desc' => 'เข้าใจความรู้สึก, สนับสนุน'],
];
?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="page-header">
                <h4>
                    <i class="fas fa-robot"></i>
                    Edit AI Companion
                </h4>
                <button type='button' id='backToAIList' class='btn btn-secondary'>
                    <i class='fas fa-arrow-left'></i>
                    Back
                </button>
            </div>

            <form id="formAICompanionEdit" enctype="multipart/form-data">
                <input type="hidden" name="ai_id" value="<?= $ai['ai_id'] ?>">
                
                <div class="row">
                    
                    <!-- Left Column: Media & Basic Info -->
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
                                            <option value="<?= $item['item_id'] ?>" 
                                                    <?= $ai['item_id'] == $item['item_id'] ? 'selected' : '' ?>>
                                                Serial Number: <?= htmlspecialchars($item['serial_number']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- AI Code -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-qrcode"></i> AI Code (Unique) *</label>
                                    <input type="text" class="form-control" id="ai_code" name="ai_code" required value="<?= htmlspecialchars($ai['ai_code']) ?>">
                                </div>

                                <!-- AI Avatar -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-image"></i> AI Avatar Image</label>
                                    <div class="upload-zone" onclick="document.getElementById('aiAvatar').click()">
                                        <div id="avatarPreview">
                                            <?php if ($ai['ai_avatar_url']): ?>
                                                <div class="upload-preview-avatar">
                                                    <img src="<?= htmlspecialchars($ai['ai_avatar_url']) ?>">
                                                    <button type="button" class="delete-btn" id="deleteAvatar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <p>Click to upload avatar</p>
                                                <small>PNG, JPG, GIF (Max 5MB)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <input type="file" id="aiAvatar" name="ai_avatar" accept="image/*" style="display: none;">
                                    <input type="hidden" id="deleteAvatarFlag" name="delete_avatar" value="0">
                                </div>

                                <!-- AI Intro Video -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-video"></i> AI Intro Video</label>
                                    <div class="upload-zone" onclick="document.getElementById('aiVideo').click()">
                                        <div id="videoPreview">
                                            <?php if ($ai['ai_video_url']): ?>
                                                <div class="upload-preview-video">
                                                    <video controls>
                                                        <source src="<?= htmlspecialchars($ai['ai_video_url']) ?>">
                                                    </video>
                                                    <button type="button" class="delete-btn" id="deleteVideo">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <i class="fas fa-film"></i>
                                                <p>Click to upload intro video</p>
                                                <small>MP4, WebM (Max 50MB)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <input type="file" id="aiVideo" name="ai_video" accept="video/*" style="display: none;">
                                    <input type="hidden" id="deleteVideoFlag" name="delete_video" value="0">
                                </div>

                                <!-- Idle Videos (Multiple) -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-pause-circle"></i> Idle Videos (หลายไฟล์)</label>
                                    
                                    <?php 
                                    $idle_urls = json_decode($ai['idle_video_urls'] ?? '[]', true);
                                    if (!empty($idle_urls)): 
                                    ?>
                                        <div id="existingIdleVideos" class="video-grid mb-3">
                                            <?php foreach ($idle_urls as $idx => $url): ?>
                                                <div class="video-item" data-url="<?= htmlspecialchars($url) ?>">
                                                    <video controls>
                                                        <source src="<?= htmlspecialchars($url) ?>">
                                                    </video>
                                                    <button type="button" class="delete-btn delete-idle-video" data-url="<?= htmlspecialchars($url) ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <div class="video-label">Idle Video <?= $idx + 1 ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="upload-zone" onclick="document.getElementById('idleVideos').click()">
                                        <i class="fas fa-pause-circle"></i>
                                        <p>Click to add more idle videos</p>
                                        <small>MP4, WebM (Max 50MB each)</small>
                                    </div>
                                    <input type="file" id="idleVideos" name="idle_videos[]" accept="video/*" multiple style="display: none;">
                                    <input type="hidden" id="deletedIdleVideos" name="deleted_idle_videos" value="[]">
                                    <div id="newIdleVideosPreview" class="video-grid mt-3"></div>
                                </div>

                                <!-- Talking Videos (Multiple) -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-play-circle"></i> Talking Videos (หลายไฟล์)</label>
                                    
                                    <?php 
                                    $talking_urls = json_decode($ai['talking_video_urls'] ?? '[]', true);
                                    if (!empty($talking_urls)): 
                                    ?>
                                        <div id="existingTalkingVideos" class="video-grid mb-3">
                                            <?php foreach ($talking_urls as $idx => $url): ?>
                                                <div class="video-item" data-url="<?= htmlspecialchars($url) ?>">
                                                    <video controls>
                                                        <source src="<?= htmlspecialchars($url) ?>">
                                                    </video>
                                                    <button type="button" class="delete-btn delete-talking-video" data-url="<?= htmlspecialchars($url) ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <div class="video-label">Talking Video <?= $idx + 1 ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="upload-zone" onclick="document.getElementById('talkingVideos').click()">
                                        <i class="fas fa-play-circle"></i>
                                        <p>Click to add more talking videos</p>
                                        <small>MP4, WebM (Max 50MB each)</small>
                                    </div>
                                    <input type="file" id="talkingVideos" name="talking_videos[]" accept="video/*" multiple style="display: none;">
                                    <input type="hidden" id="deletedTalkingVideos" name="deleted_talking_videos" value="[]">
                                    <div id="newTalkingVideosPreview" class="video-grid mt-3"></div>
                                </div>

                                <!-- ✅ NEW: 2D Emotion Videos (Edit) -->
                                <div class="form-group mb-4">
                                    <label>
                                        <i class="fas fa-heart"></i> 
                                        2D Emotion Videos/GIFs
                                        <span class="badge badge-info" style="background:#17a2b8;color:#fff;font-size:11px;padding:3px 7px;border-radius:10px;">NEW</span>
                                    </label>
                                    <small class="text-muted d-block mb-3">
                                        <i class="fas fa-info-circle"></i>
                                        วิดีโอสั้น/GIF แสดงอารมณ์ข้าง Avatar ใน 2D Mode — อัพโหลดได้หลายไฟล์ต่ออารมณ์ (จะ Random เล่น)
                                    </small>

                                    <?php foreach ($emotions as $emotion_key => $emotion_info): 
                                        $existing_urls = $emotion_videos_data[$emotion_key] ?? [];
                                    ?>
                                    <div class="emotion-upload-block mb-3" style="border:1px solid #e0e0e0; border-radius:10px; padding:15px; background:#fafafa;">
                                        <div class="d-flex align-items-center mb-2" style="gap:10px;">
                                            <div style="width:36px;height:36px;border-radius:50%;background:<?= $emotion_info['color'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i class="fas <?= $emotion_info['icon'] ?>" style="color:#fff;font-size:16px;"></i>
                                            </div>
                                            <div>
                                                <strong style="font-size:14px;"><?= $emotion_info['label'] ?></strong>
                                                <div style="font-size:11px;color:#888;"><?= $emotion_info['desc'] ?></div>
                                            </div>
                                            <div class="ms-auto d-flex gap-2">
                                                <span class="badge" style="background:<?= !empty($existing_urls) ? '#28a745' : '#ccc' ?>;color:#fff;font-size:11px;padding:4px 8px;border-radius:10px;align-self:center;">
                                                    <?= count($existing_urls) ?> file(s)
                                                </span>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary emotion-upload-btn"
                                                        onclick="document.getElementById('emotionVideos_<?= $emotion_key ?>').click()"
                                                        style="font-size:12px;">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Existing emotion videos -->
                                        <?php if (!empty($existing_urls)): ?>
                                        <div id="existingEmotionVideos_<?= $emotion_key ?>" class="video-grid mb-2">
                                            <?php foreach ($existing_urls as $vidx => $vurl): ?>
                                                <div class="video-item" data-url="<?= htmlspecialchars($vurl) ?>">
                                                    <?php 
                                                    $ext = strtolower(pathinfo($vurl, PATHINFO_EXTENSION));
                                                    if ($ext === 'gif'): ?>
                                                        <img src="<?= htmlspecialchars($vurl) ?>" style="width:100%;height:100px;object-fit:cover;border-radius:6px;">
                                                    <?php else: ?>
                                                        <video controls style="width:100%;height:100px;border-radius:6px;object-fit:cover;">
                                                            <source src="<?= htmlspecialchars($vurl) ?>">
                                                        </video>
                                                    <?php endif; ?>
                                                    <button type="button" 
                                                            class="delete-btn delete-emotion-video" 
                                                            data-url="<?= htmlspecialchars($vurl) ?>" 
                                                            data-emotion="<?= $emotion_key ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <div class="video-label"><?= ucfirst($emotion_key) ?> <?= $vidx + 1 ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <input type="file" 
                                               id="emotionVideos_<?= $emotion_key ?>" 
                                               name="emotion_videos[<?= $emotion_key ?>][]" 
                                               accept="video/*,image/gif" 
                                               multiple 
                                               style="display:none;"
                                               data-emotion="<?= $emotion_key ?>">
                                        <!-- Hidden field สำหรับ track ไฟล์ที่จะลบ -->
                                        <input type="hidden" 
                                               id="deletedEmotionVideos_<?= $emotion_key ?>" 
                                               name="deleted_emotion_videos[<?= $emotion_key ?>]" 
                                               value="[]">
                                        <div id="newEmotionPreview_<?= $emotion_key ?>" class="video-grid" style="margin-top:8px;"></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- AI Voice (ElevenLabs) -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-volume-up"></i> AI Voice (ElevenLabs)</label>
                                    <input type="text" class="form-control mb-2" id="voice_id" name="voice_id" 
                                        placeholder="Enter Voice ID"
                                        value="<?= htmlspecialchars($ai['voice_id'] ?? '') ?>">
                                    <input type="text" class="form-control mb-2" id="voice_name" name="voice_name" 
                                        placeholder="Voice Name"
                                        value="<?= htmlspecialchars($ai['voice_name'] ?? '') ?>">
                                </div>
                                
                                <!-- Status -->
                                <div class="form-group">
                                    <label><i class="fas fa-toggle-on"></i> Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="1" <?= $ai['status'] == 1 ? 'selected' : '' ?>>Active</option>
                                        <option value="0" <?= $ai['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Multilingual Content -->
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
                                            <img src="https://flagcdn.com/w20/kr.png" class="flag-icon"> 한국어
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="card-body">
                                <div class="tab-content">
                                    
                                    <div class="tab-pane fade show active" id="th">
                                        <div class="form-group mb-3">
                                            <label>AI Name (TH) *</label>
                                            <input type="text" class="form-control" id="ai_name_th" name="ai_name_th" required value="<?= htmlspecialchars($ai['ai_name_th']) ?>">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (TH)</label>
                                            <textarea class="form-control" id="system_prompt_th" name="system_prompt_th" rows="4"><?= htmlspecialchars($ai['system_prompt_th']) ?></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (TH)</label>
                                            <textarea class="form-control" id="perfume_knowledge_th" name="perfume_knowledge_th" rows="5"><?= htmlspecialchars($ai['perfume_knowledge_th']) ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (TH)</label>
                                            <textarea class="form-control" id="style_suggestions_th" name="style_suggestions_th" rows="5"><?= htmlspecialchars($ai['style_suggestions_th']) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="en">
                                        <div class="form-group mb-3">
                                            <label>AI Name (EN)</label>
                                            <input type="text" class="form-control" id="ai_name_en" name="ai_name_en" value="<?= htmlspecialchars($ai['ai_name_en']) ?>">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (EN)</label>
                                            <textarea class="form-control" id="system_prompt_en" name="system_prompt_en" rows="4"><?= htmlspecialchars($ai['system_prompt_en']) ?></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (EN)</label>
                                            <textarea class="form-control" id="perfume_knowledge_en" name="perfume_knowledge_en" rows="5"><?= htmlspecialchars($ai['perfume_knowledge_en']) ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (EN)</label>
                                            <textarea class="form-control" id="style_suggestions_en" name="style_suggestions_en" rows="5"><?= htmlspecialchars($ai['style_suggestions_en']) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="cn">
                                        <div class="form-group mb-3">
                                            <label>AI Name (CN)</label>
                                            <input type="text" class="form-control" id="ai_name_cn" name="ai_name_cn" value="<?= htmlspecialchars($ai['ai_name_cn']) ?>">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (CN)</label>
                                            <textarea class="form-control" id="system_prompt_cn" name="system_prompt_cn" rows="4"><?= htmlspecialchars($ai['system_prompt_cn']) ?></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (CN)</label>
                                            <textarea class="form-control" id="perfume_knowledge_cn" name="perfume_knowledge_cn" rows="5"><?= htmlspecialchars($ai['perfume_knowledge_cn']) ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (CN)</label>
                                            <textarea class="form-control" id="style_suggestions_cn" name="style_suggestions_cn" rows="5"><?= htmlspecialchars($ai['style_suggestions_cn']) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="jp">
                                        <div class="form-group mb-3">
                                            <label>AI Name (JP)</label>
                                            <input type="text" class="form-control" id="ai_name_jp" name="ai_name_jp" value="<?= htmlspecialchars($ai['ai_name_jp']) ?>">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (JP)</label>
                                            <textarea class="form-control" id="system_prompt_jp" name="system_prompt_jp" rows="4"><?= htmlspecialchars($ai['system_prompt_jp']) ?></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (JP)</label>
                                            <textarea class="form-control" id="perfume_knowledge_jp" name="perfume_knowledge_jp" rows="5"><?= htmlspecialchars($ai['perfume_knowledge_jp']) ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (JP)</label>
                                            <textarea class="form-control" id="style_suggestions_jp" name="style_suggestions_jp" rows="5"><?= htmlspecialchars($ai['style_suggestions_jp']) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="kr">
                                        <div class="form-group mb-3">
                                            <label>AI Name (KR)</label>
                                            <input type="text" class="form-control" id="ai_name_kr" name="ai_name_kr" value="<?= htmlspecialchars($ai['ai_name_kr']) ?>">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>System Prompt (KR)</label>
                                            <textarea class="form-control" id="system_prompt_kr" name="system_prompt_kr" rows="4"><?= htmlspecialchars($ai['system_prompt_kr']) ?></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Perfume Knowledge (KR)</label>
                                            <textarea class="form-control" id="perfume_knowledge_kr" name="perfume_knowledge_kr" rows="5"><?= htmlspecialchars($ai['perfume_knowledge_kr']) ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Style Suggestions (KR)</label>
                                            <textarea class="form-control" id="style_suggestions_kr" name="style_suggestions_kr" rows="5"><?= htmlspecialchars($ai['style_suggestions_kr']) ?></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-3 text-end">
                            <button type="button" id="submitEditAI" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Update AI Companion
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
</body>
</html>