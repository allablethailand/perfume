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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
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

// Get products for dropdown
$products_query = "SELECT product_id, name_th, name_en FROM products WHERE del = 0 ORDER BY name_th";
$products_result = $conn->query($products_query);
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

include '../template/header.php';
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
                                
                                <!-- Product Selection -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-box"></i> Select Product (Perfume) *</label>
                                    <select class="form-control" id="product_id" name="product_id" required>
                                        <option value="">-- Select Product --</option>
                                        <?php foreach ($products as $prod): ?>
                                            <option value="<?= $prod['product_id'] ?>" <?= $ai['product_id'] == $prod['product_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($prod['name_th']) ?> (<?= htmlspecialchars($prod['name_en']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- AI Code -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-qrcode"></i> AI Code (Unique) *</label>
                                    <input type="text" class="form-control" id="ai_code" name="ai_code" required value="<?= htmlspecialchars($ai['ai_code']) ?>">
                                    <small class="text-muted">This code will be used for QR scanning</small>
                                </div>

                                <!-- AI Avatar -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-image"></i> AI Avatar Image</label>
                                    <div class="ai-avatar-upload" onclick="document.getElementById('aiAvatar').click()">
                                        <div id="avatarPreview" class="avatar-preview">
                                            <?php if ($ai['ai_avatar_url']): ?>
                                                <img src="<?= htmlspecialchars($ai['ai_avatar_url']) ?>" style="width: 100%; height: 250px; object-fit: cover; border-radius: 8px;">
                                            <?php else: ?>
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <p>Click to upload avatar</p>
                                                <small>PNG, JPG, GIF (Max 5MB)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <input type="file" id="aiAvatar" name="ai_avatar" accept="image/*" style="display: none;">
                                </div>

                                <!-- AI Video (Optional) -->
                                <div class="form-group mb-4">
                                    <label><i class="fas fa-video"></i> AI Video/Animation (Optional)</label>
                                    <div class="ai-video-upload" onclick="document.getElementById('aiVideo').click()">
                                        <div id="videoPreview" class="video-preview">
                                            <?php if ($ai['ai_video_url']): ?>
                                                <video controls style="width: 100%; height: 250px; border-radius: 8px;">
                                                    <source src="<?= htmlspecialchars($ai['ai_video_url']) ?>">
                                                </video>
                                            <?php else: ?>
                                                <i class="fas fa-film"></i>
                                                <p>Click to upload video</p>
                                                <small>MP4, WebM (Max 50MB)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <input type="file" id="aiVideo" name="ai_video" accept="video/*" style="display: none;">
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
                                    
                                    <!-- Thai -->
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

                                    <!-- English -->
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

                                    <!-- Chinese -->
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

                                    <!-- Japanese -->
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

                                    <!-- Korean -->
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
    <script>
    // Edit-specific JavaScript
    $(document).ready(function() {
        // Submit Edit AI Companion
        $('#submitEditAI').on('click', function(e) {
            e.preventDefault();
            
            if (!$('#product_id').val()) {
                alertError('Please select a product');
                return;
            }
            
            if (!$('#ai_code').val()) {
                alertError('Please enter AI Code');
                return;
            }
            
            if (!$('#ai_name_th').val()) {
                alertError('Please enter AI Name (Thai)');
                return;
            }
            
            let formData = new FormData($('#formAICompanionEdit')[0]);
            formData.append('action', 'editAICompanion');
            
            $('#loading-overlay').fadeIn();
            
            $.ajax({
                url: 'actions/process_ai_companions.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        alertError(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alertError('Failed to update AI Companion: ' + error);
                },
                complete: function() {
                    $('#loading-overlay').fadeOut();
                }
            });
        });
        
        function alertError(message) {
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: "error",
                title: message
            });
        }
    });
    </script>
</body>
</html>