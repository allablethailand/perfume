<?php
include '../../../lib/connect.php';
include '../../../lib/base_directory.php';
include '../check_permission.php';

// ตรวจสอบและกำหนดภาษา
$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $lang = $newLang;
    }
}

// กำหนดข้อความตามภาษา
$texts = [
    'th' => [
        'page_title' => 'แก้ไข AI Model',
        'not_found' => 'ไม่พบข้อมูล AI Model ที่ต้องการแก้ไข',
        'back_button' => 'กลับ',
        'save_button' => 'บันทึกการแก้ไข',
        'model_info' => 'ข้อมูล Model',
        'model_code' => 'รหัส Model',
        'model_name' => 'ชื่อ Model',
        'provider' => 'ผู้ให้บริการ',
        'api_key' => 'API Key',
        'api_endpoint' => 'API Endpoint',
        'is_free' => 'Model ฟรี',
        'max_tokens' => 'จำนวน Tokens สูงสุด',
        'cost_per_1k' => 'ราคาต่อ 1,000 Tokens',
        'priority' => 'ลำดับความสำคัญ',
        'is_active' => 'เปิดใช้งาน',
        'priority_note' => 'เลขมาก = ใช้ก่อน (100 = สูงสุด)',
    ],
    'en' => [
        'page_title' => 'Edit AI Model',
        'not_found' => 'AI Model data to be edited not found',
        'back_button' => 'Back',
        'save_button' => 'Save Changes',
        'model_info' => 'Model Information',
        'model_code' => 'Model Code',
        'model_name' => 'Model Name',
        'provider' => 'Provider',
        'api_key' => 'API Key',
        'api_endpoint' => 'API Endpoint',
        'is_free' => 'Free Model',
        'max_tokens' => 'Max Tokens',
        'cost_per_1k' => 'Cost per 1,000 Tokens',
        'priority' => 'Priority',
        'is_active' => 'Active',
        'priority_note' => 'Higher number = Higher priority (100 = Highest)',
    ]
];

function getTextByLang($key) {
    global $texts, $lang;
    return $texts[$lang][$key] ?? $texts['th'][$key];
}

if (!isset($_POST['model_id'])) {
    echo "<div class='alert alert-danger'>" . getTextByLang('not_found') . "</div>";
    exit;
}

$model_id = $_POST['model_id'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getTextByLang('page_title') ?></title>
    <link rel="icon" type="image/x-icon" href="../../../public/img/q-removebg-preview1.png">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>

    <style>
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-section h5 {
            margin-bottom: 15px;
            color: #495057;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="box-content">
                <div class="row">
                    <h4 class="line-ref mb-3">
                        <i class="fas fa-robot"></i> <?= getTextByLang('page_title') ?>
                    </h4>

                    <?php
                    $stmt = $conn->prepare("SELECT * FROM ai_models WHERE model_id = ?");
                    $stmt->bind_param('i', $model_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        
                        echo "
                        <form id='formAiModel_edit'>
                            <input type='hidden' id='model_id' name='model_id' value='" . htmlspecialchars($row['model_id']) . "'>
                            
                            <div class='row'>
                                <div class='col-md-8'>
                                    <div class='form-section'>
                                        <h5><i class='fas fa-info-circle'></i> " . getTextByLang('model_info') . "</h5>
                                        
                                        <div class='row'>
                                            <div class='col-md-6 mb-3'>
                                                <label for='model_code'>" . getTextByLang('model_code') . " <span class='text-danger'>*</span></label>
                                                <input type='text' class='form-control' id='model_code' name='model_code' value='" . htmlspecialchars($row['model_code']) . "' required>
                                            </div>
                                            
                                            <div class='col-md-6 mb-3'>
                                                <label for='model_name'>" . getTextByLang('model_name') . " <span class='text-danger'>*</span></label>
                                                <input type='text' class='form-control' id='model_name' name='model_name' value='" . htmlspecialchars($row['model_name']) . "' required>
                                            </div>
                                        </div>
                                        
                                        <div class='row'>
                                            <div class='col-md-6 mb-3'>
                                                <label for='provider'>" . getTextByLang('provider') . " <span class='text-danger'>*</span></label>
                                                <select class='form-control' id='provider' name='provider' required>
                                                    <option value='groq' " . ($row['provider'] == 'groq' ? 'selected' : '') . ">Groq</option>
                                                    <option value='openai' " . ($row['provider'] == 'openai' ? 'selected' : '') . ">OpenAI</option>
                                                    <option value='anthropic' " . ($row['provider'] == 'anthropic' ? 'selected' : '') . ">Anthropic</option>
                                                    <option value='google' " . ($row['provider'] == 'google' ? 'selected' : '') . ">Google (Gemini)</option>
                                                    <option value='other' " . ($row['provider'] == 'other' ? 'selected' : '') . ">อื่นๆ</option>
                                                </select>
                                            </div>
                                            
                                            <div class='col-md-6 mb-3'>
                                                <label for='max_tokens'>" . getTextByLang('max_tokens') . "</label>
                                                <input type='number' class='form-control' id='max_tokens' name='max_tokens' value='" . htmlspecialchars($row['max_tokens']) . "'>
                                            </div>
                                        </div>
                                        
                                        <div class='mb-3'>
                                            <label for='api_endpoint'>" . getTextByLang('api_endpoint') . "</label>
                                            <input type='text' class='form-control' id='api_endpoint' name='api_endpoint' value='" . htmlspecialchars($row['api_endpoint']) . "'>
                                        </div>
                                        
                                        <div class='mb-3'>
                                            <label for='api_key'>" . getTextByLang('api_key') . "</label>
                                            <div class='input-group'>
                                                <input type='password' class='form-control' id='api_key' name='api_key' placeholder='ทิ้งว่างไว้หากไม่ต้องการเปลี่ยน'>
                                                <button class='btn btn-outline-secondary' type='button' id='toggleApiKey'>
                                                    <i class='fas fa-eye'></i>
                                                </button>
                                            </div>
                                            <small class='text-muted'><i class='fas fa-info-circle'></i> ทิ้งว่างไว้หากไม่ต้องการเปลี่ยน API Key</small>
                                        </div>
                                    </div>
                                    
                                    <div class='form-section'>
                                        <h5><i class='fas fa-cog'></i> ตั้งค่า</h5>
                                        
                                        <div class='row'>
                                            <div class='col-md-6 mb-3'>
                                                <label for='priority'>" . getTextByLang('priority') . " <span class='text-danger'>*</span></label>
                                                <input type='number' class='form-control' id='priority' name='priority' min='0' max='100' value='" . htmlspecialchars($row['priority']) . "' required>
                                                <small class='text-muted'>" . getTextByLang('priority_note') . "</small>
                                            </div>
                                            
                                            <div class='col-md-6 mb-3'>
                                                <label for='cost_per_1k_tokens'>" . getTextByLang('cost_per_1k') . " ($)</label>
                                                <input type='number' class='form-control' id='cost_per_1k_tokens' name='cost_per_1k_tokens' step='0.000001' value='" . htmlspecialchars($row['cost_per_1k_tokens']) . "'>
                                            </div>
                                        </div>
                                        
                                        <div class='row'>
                                            <div class='col-md-6 mb-3'>
                                                <div class='form-check form-switch'>
                                                    <input class='form-check-input' type='checkbox' id='is_free' name='is_free' " . ($row['is_free'] == 1 ? 'checked' : '') . ">
                                                    <label class='form-check-label' for='is_free'>" . getTextByLang('is_free') . "</label>
                                                </div>
                                            </div>
                                            
                                            <div class='col-md-6 mb-3'>
                                                <div class='form-check form-switch'>
                                                    <input class='form-check-input' type='checkbox' id='is_active' name='is_active' " . ($row['is_active'] == 1 ? 'checked' : '') . ">
                                                    <label class='form-check-label' for='is_active'>" . getTextByLang('is_active') . "</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style='text-align: end;'>
                                        <button type='button' id='backToList' class='btn btn-secondary'>
                                            <i class='fas fa-arrow-left'></i> " . getTextByLang('back_button') . "
                                        </button>
                                        <button type='button' id='submitEditAiModel' class='btn btn-success'>
                                            <i class='fas fa-save'></i> " . getTextByLang('save_button') . "
                                        </button>
                                    </div>
                                </div>
                                
                                <div class='col-md-4'>
                                    <div class='alert alert-info'>
                                        <h6><i class='fas fa-lightbulb'></i> คำแนะนำ</h6>
                                        <ul>
                                            <li>Model ที่มี Priority สูงสุดและ Active จะถูกใช้เป็นตัวหลัก</li>
                                            <li>ถ้าไม่ต้องการเปลี่ยน API Key ให้ปล่อยว่างไว้</li>
                                            <li>API Key จะถูกเข้ารหัสก่อนบันทึก</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </form>
                        ";
                    } else {
                        echo "<div class='alert alert-warning'>" . getTextByLang('not_found') . "</div>";
                    }
                    $stmt->close();
                    $conn->close();
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src='js/ai_models_.js?v=<?php echo time(); ?>'></script>
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    
    <script>
        // Toggle API Key visibility
        $('#toggleApiKey').on('click', function() {
            const input = $('#api_key');
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Auto-disable cost field if free is checked
        $('#is_free').on('change', function() {
            $('#cost_per_1k_tokens').prop('disabled', $(this).is(':checked'));
            if ($(this).is(':checked')) {
                $('#cost_per_1k_tokens').val('0');
            }
        });
        
        // Trigger on page load
        if ($('#is_free').is(':checked')) {
            $('#cost_per_1k_tokens').prop('disabled', true);
        }
    </script>
</body>
</html>