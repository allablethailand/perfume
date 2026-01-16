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
        'th' => 'จัดการ AI Models',
        'en' => 'Manage AI Models',
        'cn' => '管理 AI 模型',
        'jp' => 'AI モデル管理',
        'kr' => 'AI 모델 관리'
    ],
    'add_model' => [
        'th' => 'เพิ่ม AI Model',
        'en' => 'Add AI Model',
        'cn' => '添加 AI 模型',
        'jp' => 'AI モデルを追加',
        'kr' => 'AI 모델 추가'
    ],
    'table_no' => [
        'th' => 'ลำดับ',
        'en' => 'No.',
        'cn' => '序号',
        'jp' => '番号',
        'kr' => '번호'
    ],
    'table_model' => [
        'th' => 'Model',
        'en' => 'Model',
        'cn' => '模型',
        'jp' => 'モデル',
        'kr' => '모델'
    ],
    'table_provider' => [
        'th' => 'ผู้ให้บริการ',
        'en' => 'Provider',
        'cn' => '提供商',
        'jp' => 'プロバイダー',
        'kr' => '공급자'
    ],
    'table_status' => [
        'th' => 'สถานะ',
        'en' => 'Status',
        'cn' => '状态',
        'jp' => 'ステータス',
        'kr' => '상태'
    ],
    'table_priority' => [
        'th' => 'ลำดับความสำคัญ',
        'en' => 'Priority',
        'cn' => '优先级',
        'jp' => '優先度',
        'kr' => '우선 순위'
    ],
    'table_api_key' => [
        'th' => 'API Key',
        'en' => 'API Key',
        'cn' => 'API 密钥',
        'jp' => 'API キー',
        'kr' => 'API 키'
    ],
    'table_action' => [
        'th' => 'การจัดการ',
        'en' => 'Action',
        'cn' => '操作',
        'jp' => 'アクション',
        'kr' => '작업'
    ],
    'active' => [
        'th' => 'ใช้งาน',
        'en' => 'Active',
        'cn' => '激活',
        'jp' => 'アクティブ',
        'kr' => '활성'
    ],
    'inactive' => [
        'th' => 'ไม่ใช้งาน',
        'en' => 'Inactive',
        'cn' => '未激活',
        'jp' => '非アクティブ',
        'kr' => '비활성'
    ],
    'free' => [
        'th' => 'ฟรี',
        'en' => 'Free',
        'cn' => '免费',
        'jp' => '無料',
        'kr' => '무료'
    ],
    'paid' => [
        'th' => 'เสียเงิน',
        'en' => 'Paid',
        'cn' => '付费',
        'jp' => '有料',
        'kr' => '유료'
    ],
    'configured' => [
        'th' => 'ตั้งค่าแล้ว',
        'en' => 'Configured',
        'cn' => '已配置',
        'jp' => '設定済み',
        'kr' => '구성됨'
    ],
    'not_configured' => [
        'th' => 'ยังไม่ได้ตั้งค่า',
        'en' => 'Not Configured',
        'cn' => '未配置',
        'jp' => '未設定',
        'kr' => '구성되지 않음'
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
        .badge-status {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
        }
        .badge-active {
            background-color: #28a745;
            color: white;
        }
        .badge-inactive {
            background-color: #6c757d;
            color: white;
        }
        .badge-free {
            background-color: #17a2b8;
            color: white;
        }
        .badge-paid {
            background-color: #ffc107;
            color: #333;
        }
        .badge-configured {
            background-color: #007bff;
            color: white;
        }
        .badge-not-configured {
            background-color: #dc3545;
            color: white;
        }
        .btn-circle {
            border: none;
            width: 30px;
            height: 28px;
            border-radius: 50%;
            font-size: 14px;
        }
        .btn-edit {
            background-color: #FFC107;
            color: #ffffff;
        }
        .btn-key {
            background-color: #17a2b8;
            color: #ffffff;
        }
        .btn-toggle {
            background-color: #28a745;
            color: #ffffff;
        }
        .priority-badge {
            display: inline-block;
            min-width: 40px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="box-content">
                <div class="row">
                    <div>
                        <div style="margin: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h4 class="line-ref mb-0"> 
                                    <i class="fas fa-robot"></i>   
                                    <?= getTextByLang('page_title') ?>
                                </h4>
                                <a type="button" class="btn btn-primary" href="setup_ai_model.php">
                                    <i class="fa-solid fa-plus"></i>
                                    <?= getTextByLang('add_model') ?>
                                </a>
                            </div>
                            
                            <table id="td_list_ai_models" class="table table-hover" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;"><?= getTextByLang('table_no') ?></th>
                                        <th style="width: 25%;"><?= getTextByLang('table_model') ?></th>
                                        <th style="width: 12%;"><?= getTextByLang('table_provider') ?></th>
                                        <th style="width: 10%;"><?= getTextByLang('table_status') ?></th>
                                        <th style="width: 10%;"><?= getTextByLang('table_priority') ?></th>
                                        <th style="width: 13%;"><?= getTextByLang('table_api_key') ?></th>
                                        <th style="width: 15%;"><?= getTextByLang('table_action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/ai_models_.js?v=<?php echo time(); ?>'></script>
</body>
</html>