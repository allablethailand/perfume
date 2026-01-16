<?php
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
    'page_title' => [
        'th' => 'ตั้งค่า AI Model',
        'en' => 'Setup AI Model',
        'cn' => '设置 AI 模型',
        'jp' => 'AI モデル設定',
        'kr' => 'AI 모델 설정'
    ],
    'model_info' => [
        'th' => 'ข้อมูล Model',
        'en' => 'Model Information',
        'cn' => '模型信息',
        'jp' => 'モデル情報',
        'kr' => '모델 정보'
    ],
    'model_code' => [
        'th' => 'รหัส Model',
        'en' => 'Model Code',
        'cn' => '模型代码',
        'jp' => 'モデルコード',
        'kr' => '모델 코드'
    ],
    'model_name' => [
        'th' => 'ชื่อ Model',
        'en' => 'Model Name',
        'cn' => '模型名称',
        'jp' => 'モデル名',
        'kr' => '모델 이름'
    ],
    'provider' => [
        'th' => 'ผู้ให้บริการ',
        'en' => 'Provider',
        'cn' => '提供商',
        'jp' => 'プロバイダー',
        'kr' => '공급자'
    ],
    'api_key' => [
        'th' => 'API Key',
        'en' => 'API Key',
        'cn' => 'API 密钥',
        'jp' => 'API キー',
        'kr' => 'API 키'
    ],
    'api_endpoint' => [
        'th' => 'API Endpoint',
        'en' => 'API Endpoint',
        'cn' => 'API 端点',
        'jp' => 'API エンドポイント',
        'kr' => 'API 엔드포인트'
    ],
    'is_free' => [
        'th' => 'Model ฟรี',
        'en' => 'Free Model',
        'cn' => '免费模型',
        'jp' => '無料モデル',
        'kr' => '무료 모델'
    ],
    'max_tokens' => [
        'th' => 'จำนวน Tokens สูงสุด',
        'en' => 'Max Tokens',
        'cn' => '最大令牌数',
        'jp' => '最大トークン数',
        'kr' => '최대 토큰 수'
    ],
    'cost_per_1k' => [
        'th' => 'ราคาต่อ 1,000 Tokens',
        'en' => 'Cost per 1,000 Tokens',
        'cn' => '每 1,000 个令牌的成本',
        'jp' => '1,000 トークンあたりのコスト',
        'kr' => '1,000 토큰당 비용'
    ],
    'priority' => [
        'th' => 'ลำดับความสำคัญ',
        'en' => 'Priority',
        'cn' => '优先级',
        'jp' => '優先度',
        'kr' => '우선 순위'
    ],
    'is_active' => [
        'th' => 'เปิดใช้งาน',
        'en' => 'Active',
        'cn' => '激活',
        'jp' => 'アクティブ',
        'kr' => '활성'
    ],
    'save_button' => [
        'th' => 'บันทึก',
        'en' => 'Save',
        'cn' => '保存',
        'jp' => '保存',
        'kr' => '저장'
    ],
    'back_button' => [
        'th' => 'กลับ',
        'en' => 'Back',
        'cn' => '返回',
        'jp' => '戻る',
        'kr' => '뒤로'
    ],
    'priority_note' => [
        'th' => 'เลขมาก = ใช้ก่อน (100 = สูงสุด)',
        'en' => 'Higher number = Higher priority (100 = Highest)',
        'cn' => '数字越大 = 优先级越高（100 = 最高）',
        'jp' => '数字が大きい = 優先度が高い（100 = 最高）',
        'kr' => '높은 숫자 = 높은 우선 순위 (100 = 최고)'
    ],
    'placeholder_model_code' => [
        'th' => 'เช่น llama-3.3-70b-versatile',
        'en' => 'e.g. llama-3.3-70b-versatile',
        'cn' => '例如 llama-3.3-70b-versatile',
        'jp' => '例: llama-3.3-70b-versatile',
        'kr' => '예: llama-3.3-70b-versatile'
    ],
    'placeholder_api_key' => [
        'th' => 'กรอก API Key (ถ้ามี)',
        'en' => 'Enter API Key (if available)',
        'cn' => '输入 API 密钥（如果有）',
        'jp' => 'API キーを入力（利用可能な場合）',
        'kr' => 'API 키 입력 (사용 가능한 경우)'
    ],
    'placeholder_endpoint' => [
        'th' => 'เช่น https://api.groq.com/openai/v1/chat/completions',
        'en' => 'e.g. https://api.groq.com/openai/v1/chat/completions',
        'cn' => '例如 https://api.groq.com/openai/v1/chat/completions',
        'jp' => '例: https://api.groq.com/openai/v1/chat/completions',
        'kr' => '예: https://api.groq.com/openai/v1/chat/completions'
    ]
];

function getTextByLang($key) {
    global $texts, $lang;
    return $texts[$key][$lang] ?? $texts[$key]['th'];
}
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
                        <i class="fas fa-robot"></i>
                        <?= getTextByLang('page_title') ?>
                    </h4>
                    
                    <form id="formAiModel">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Model Information Section -->
                                <div class="form-section">
                                    <h5><i class="fas fa-info-circle"></i> <?= getTextByLang('model_info') ?></h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="model_code">
                                                <?= getTextByLang('model_code') ?> <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="model_code" 
                                                   name="model_code"
                                                   placeholder="<?= getTextByLang('placeholder_model_code') ?>"
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="model_name">
                                                <?= getTextByLang('model_name') ?> <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="model_name" 
                                                   name="model_name"
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="provider">
                                                <?= getTextByLang('provider') ?> <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control" id="provider" name="provider" required>
                                                <option value="">-- เลือก --</option>
                                                <option value="groq">Groq</option>
                                                <option value="openai">OpenAI</option>
                                                <option value="anthropic">Anthropic</option>
                                                <option value="google">Google (Gemini)</option>
                                                <option value="other">อื่นๆ</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="max_tokens">
                                                <?= getTextByLang('max_tokens') ?>
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="max_tokens" 
                                                   name="max_tokens"
                                                   placeholder="8192">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="api_endpoint">
                                            <?= getTextByLang('api_endpoint') ?>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="api_endpoint" 
                                               name="api_endpoint"
                                               placeholder="<?= getTextByLang('placeholder_endpoint') ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="api_key">
                                            <?= getTextByLang('api_key') ?>
                                        </label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="api_key" 
                                                   name="api_key"
                                                   placeholder="<?= getTextByLang('placeholder_api_key') ?>">
                                            <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> API Key จะถูกเข้ารหัสก่อนบันทึก
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Settings Section -->
                                <div class="form-section">
                                    <h5><i class="fas fa-cog"></i> ตั้งค่า</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="priority">
                                                <?= getTextByLang('priority') ?> <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="priority" 
                                                   name="priority"
                                                   min="0"
                                                   max="100"
                                                   value="50"
                                                   required>
                                            <small class="text-muted">
                                                <?= getTextByLang('priority_note') ?>
                                            </small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="cost_per_1k_tokens">
                                                <?= getTextByLang('cost_per_1k') ?> ($)
                                            </label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="cost_per_1k_tokens" 
                                                   name="cost_per_1k_tokens"
                                                   step="0.000001"
                                                   placeholder="0.000000">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="is_free" 
                                                       name="is_free"
                                                       checked>
                                                <label class="form-check-label" for="is_free">
                                                    <?= getTextByLang('is_free') ?>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="is_active" 
                                                       name="is_active"
                                                       checked>
                                                <label class="form-check-label" for="is_active">
                                                    <?= getTextByLang('is_active') ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div style="text-align: end;">
                                    <button type="button" id="backToList" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i>
                                        <?= getTextByLang('back_button') ?>
                                    </button>
                                    <button type="button" id="submitAiModel" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        <?= getTextByLang('save_button') ?>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Info Panel -->
                            <div class="col-md-4">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-lightbulb"></i> คำแนะนำ</h6>
                                    <ul>
                                        <li>ตัว Model ที่มี Priority สูงสุดและ Active จะถูกใช้เป็นตัวหลัก</li>
                                        <li>ถ้า Model ฟรี ไม่ต้องใส่ API Key</li>
                                        <li>รองรับหลาย Provider: Groq, OpenAI, Anthropic, Google</li>
                                        <li>API Key จะถูกเข้ารหัสก่อนบันทึกลง Database</li>
                                    </ul>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Groq Models (Free)</h6>
                                    <small>
                                        <strong>ตัวอย่าง Model Codes:</strong><br>
                                        • llama-3.3-70b-versatile<br>
                                        • llama-3.1-70b-versatile<br>
                                        • mixtral-8x7b-32768<br>
                                        • gemma2-9b-it
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/ai_models_.js?v=<?php echo time(); ?>'></script>
    
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
    </script>
</body>
</html>