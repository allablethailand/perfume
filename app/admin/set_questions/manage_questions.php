<?php
include '../check_permission.php';

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

$texts = [
    'page_title' => [
        'th' => 'จัดการคำถาม AI Personality Test',
        'en' => 'Manage AI Personality Test Questions',
        'cn' => '管理AI性格测试问题',
        'jp' => 'AI性格テストの質問を管理',
        'kr' => 'AI 성격 테스트 질문 관리'
    ],
    'add_question' => [
        'th' => 'เพิ่มคำถามใหม่',
        'en' => 'Add New Question',
        'cn' => '添加新问题',
        'jp' => '新しい質問を追加',
        'kr' => '새 질문 추가'
    ],
    'filter_status' => [
        'th' => 'กรองสถานะ:',
        'en' => 'Filter Status:',
        'cn' => '筛选状态:',
        'jp' => 'ステータスでフィルタ:',
        'kr' => '상태 필터:'
    ],
    'all' => [
        'th' => 'ทั้งหมด',
        'en' => 'All',
        'cn' => '全部',
        'jp' => '全て',
        'kr' => '전체'
    ],
    'active' => [
        'th' => 'เปิดใช้งาน',
        'en' => 'Active',
        'cn' => '启用',
        'jp' => '有効',
        'kr' => '활성'
    ],
    'inactive' => [
        'th' => 'ปิดใช้งาน',
        'en' => 'Inactive',
        'cn' => '禁用',
        'jp' => '無効',
        'kr' => '비활성'
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

    <link rel="icon" type="image/x-icon" href="../../../public/product_images/695e0bf362d49_1767771123.jpg">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>
    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    
    <style>
        body {
            background: #f5f7fa;
        }

        .box-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
            margin-top: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            border-radius: 12px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 24px;
        }

        .page-header .btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .page-header .btn:hover {
            background: #ecf0f1;
            transform: translateY(-2px);
        }

        /* Status Filter */
        .status-filter-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
        }

        .filter-title {
            font-weight: 600;
            color: #495057;
            margin-right: 20px;
            font-size: 15px;
        }

        .status-btn {
            padding: 12px 24px;
            margin: 5px;
            border: 2px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .status-btn.active {
            border-color: #667eea;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .status-btn .badge {
            margin-left: 8px;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(255,255,255,0.3);
        }

        .status-btn-all { background: #6c757d; color: white; }
        .status-btn-active { background: #28a745; color: white; }
        .status-btn-inactive { background: #dc3545; color: white; }

        /* Table Styles */
        #td_list_questions {
            border-radius: 8px;
            overflow: hidden;
        }

        #td_list_questions thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        #td_list_questions thead th {
            font-weight: 600;
            font-size: 13px;
            padding: 15px 12px;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        #td_list_questions tbody tr {
            transition: all 0.2s;
            border-bottom: 1px solid #e9ecef;
        }

        #td_list_questions tbody tr:hover {
            background: #f8f9fa;
        }

        #td_list_questions tbody td {
            padding: 12px;
            vertical-align: middle;
            font-size: 14px;
        }

        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-warning { background: #ffc107; color: #333; }

        /* Question Type Badge */
        .type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
        }

        .type-multiple { background: #e3f2fd; color: #1976d2; }
        .type-rating { background: #fff3e0; color: #f57c00; }
        .type-text { background: #f3e5f5; color: #7b1fa2; }
        .type-yesno { background: #e8f5e9; color: #388e3c; }

        /* Buttons */
        .btn-modern {
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 2px;
        }

        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        .btn-toggle {
            background: #28a745;
            color: white;
        }

        .btn-toggle:hover {
            background: #218838;
            transform: scale(1.1);
        }

        /* Loading Overlay */
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Order Badge */
        .order-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            min-width: 40px;
            text-align: center;
            display: inline-block;
        }

        /* Question Text */
        .question-text {
            color: #2d3748;
            font-weight: 500;
            line-height: 1.5;
        }

        .question-lang-label {
            font-size: 10px;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
            display: block;
            margin-bottom: 3px;
        }

        /* Modal Styles */
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .flag-icon {
            width: 24px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: #667eea;
            font-weight: 600;
            border-bottom: 2px solid #667eea;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="box-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>
                            <i class="fas fa-question-circle"></i>
                            <?= getTextByLang('page_title') ?>
                        </h4>
                        <button type="button" onclick="openAddQuestionModal()" class="btn">
                            <i class="fas fa-plus"></i>
                            <?= getTextByLang('add_question') ?>
                        </button>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="status-filter-container">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="filter-title">
                            <i class="fas fa-filter"></i> <?= getTextByLang('filter_status') ?>
                        </span>
                        <button type="button" class="status-btn status-btn-all active" data-status="">
                            <?= getTextByLang('all') ?> <span class="badge badge-light" id="count-all">0</span>
                        </button>
                        <button type="button" class="status-btn status-btn-active" data-status="1">
                            <?= getTextByLang('active') ?> <span class="badge badge-light" id="count-active">0</span>
                        </button>
                        <button type="button" class="status-btn status-btn-inactive" data-status="0">
                            <?= getTextByLang('inactive') ?> <span class="badge badge-light" id="count-inactive">0</span>
                        </button>
                    </div>
                </div>

                <!-- Questions Table -->
                <div class="table-responsive">
                    <table id="td_list_questions" class="table table-hover" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 80px;">ลำดับ</th>
                                <th style="width: 350px;">คำถาม (TH)</th>
                                <th style="width: 100px;">ประเภท</th>
                                <th style="width: 90px;">ตัวเลือก</th>
                                <th style="width: 90px;">สถานะ</th>
                                <th style="width: 130px;">วันที่สร้าง</th>
                                <th style="width: 150px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Add/Edit Question Modal -->
    <div class="modal fade" id="questionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="questionModalTitle">
                        <i class="fas fa-plus-circle"></i> เพิ่มคำถามใหม่
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formQuestion">
                        <input type="hidden" id="question_id" name="question_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ลำดับคำถาม <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="question_order" name="question_order" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ประเภทคำถาม <span class="text-danger">*</span></label>
                                <select class="form-control" id="question_type" name="question_type" required>
                                    <option value="">-- เลือกประเภท --</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="rating">Rating Scale</option>
                                    <option value="text">Text Input</option>
                                    <option value="yes_no">Yes/No</option>
                                </select>
                            </div>
                        </div>

                        <!-- Language Tabs -->
                        <div class="card mb-3">
                            <div class="card-header p-0">
                                <ul class="nav nav-tabs" id="languageTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="th-tab" data-bs-toggle="tab" data-bs-target="#th" type="button" role="tab">
                                            <img src="https://flagcdn.com/w320/th.png" alt="Thai Flag" class="flag-icon">ไทย
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="en-tab" data-bs-toggle="tab" data-bs-target="#en" type="button" role="tab">
                                            <img src="https://flagcdn.com/w320/gb.png" alt="English Flag" class="flag-icon">English
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="cn-tab" data-bs-toggle="tab" data-bs-target="#cn" type="button" role="tab">
                                            <img src="https://flagcdn.com/w320/cn.png" alt="Chinese Flag" class="flag-icon">中文
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="jp-tab" data-bs-toggle="tab" data-bs-target="#jp" type="button" role="tab">
                                            <img src="https://flagcdn.com/w320/jp.png" alt="Japanese Flag" class="flag-icon">日本語
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="kr-tab" data-bs-toggle="tab" data-bs-target="#kr" type="button" role="tab">
                                            <img src="https://flagcdn.com/w320/kr.png" alt="Korean Flag" class="flag-icon">한국어
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="languageTabsContent">
                                    <div class="tab-pane fade show active" id="th" role="tabpanel">
                                        <label class="form-label">คำถาม (ไทย) <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="question_text_th" name="question_text_th" rows="3" required></textarea>
                                    </div>
                                    <div class="tab-pane fade" id="en" role="tabpanel">
                                        <label class="form-label">Question (English)</label>
                                        <textarea class="form-control" id="question_text_en" name="question_text_en" rows="3"></textarea>
                                    </div>
                                    <div class="tab-pane fade" id="cn" role="tabpanel">
                                        <label class="form-label">问题 (Chinese)</label>
                                        <textarea class="form-control" id="question_text_cn" name="question_text_cn" rows="3"></textarea>
                                    </div>
                                    <div class="tab-pane fade" id="jp" role="tabpanel">
                                        <label class="form-label">質問 (Japanese)</label>
                                        <textarea class="form-control" id="question_text_jp" name="question_text_jp" rows="3"></textarea>
                                    </div>
                                    <div class="tab-pane fade" id="kr" role="tabpanel">
                                        <label class="form-label">질문 (Korean)</label>
                                        <textarea class="form-control" id="question_text_kr" name="question_text_kr" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">สถานะ</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                <label class="form-check-label" for="status">เปิดใช้งาน</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveQuestion()">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/questions.js?v=<?php echo time(); ?>'></script>

    <!-- Choices Management Modal -->
    <div class="modal fade" id="choicesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-list-ul"></i> จัดการตัวเลือกคำตอบ
                        <span id="choicesQuestionText" class="ms-2" style="font-size: 14px; color: #ecf0f1;"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="choices_question_id">
                    
                    <!-- Add Choice Button -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-success btn-modern" onclick="openAddChoiceForm()">
                            <i class="fas fa-plus"></i> เพิ่มตัวเลือกใหม่
                        </button>
                    </div>

                    <!-- Add/Edit Choice Form (Hidden by default) -->
                    <div id="choiceFormContainer" style="display: none;" class="mb-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0" id="choiceFormTitle">
                                    <i class="fas fa-plus-circle"></i> เพิ่มตัวเลือกใหม่
                                </h6>
                            </div>
                            <div class="card-body">
                                <form id="formChoice">
                                    <input type="hidden" id="choice_id" name="choice_id">
                                    <input type="hidden" id="choice_question_id" name="choice_question_id">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">ลำดับตัวเลือก <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="choice_order" name="choice_order" min="1" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">สถานะ</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="choice_status" name="choice_status" checked>
                                                <label class="form-check-label" for="choice_status">เปิดใช้งาน</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Language Tabs for Choices -->
                                    <div class="card">
                                        <div class="card-header p-0">
                                            <ul class="nav nav-tabs" id="choiceLanguageTabs" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active" id="choice-th-tab" data-bs-toggle="tab" data-bs-target="#choice-th" type="button" role="tab">
                                                        <img src="https://flagcdn.com/w320/th.png" alt="Thai Flag" class="flag-icon">ไทย
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" id="choice-en-tab" data-bs-toggle="tab" data-bs-target="#choice-en" type="button" role="tab">
                                                        <img src="https://flagcdn.com/w320/gb.png" alt="English Flag" class="flag-icon">English
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" id="choice-cn-tab" data-bs-toggle="tab" data-bs-target="#choice-cn" type="button" role="tab">
                                                        <img src="https://flagcdn.com/w320/cn.png" alt="Chinese Flag" class="flag-icon">中文
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" id="choice-jp-tab" data-bs-toggle="tab" data-bs-target="#choice-jp" type="button" role="tab">
                                                        <img src="https://flagcdn.com/w320/jp.png" alt="Japanese Flag" class="flag-icon">日本語
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" id="choice-kr-tab" data-bs-toggle="tab" data-bs-target="#choice-kr" type="button" role="tab">
                                                        <img src="https://flagcdn.com/w320/kr.png" alt="Korean Flag" class="flag-icon">한국어
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="card-body">
                                            <div class="tab-content" id="choiceLanguageTabsContent">
                                                <div class="tab-pane fade show active" id="choice-th" role="tabpanel">
                                                    <label class="form-label">ข้อความตัวเลือก (ไทย) <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="choice_text_th" name="choice_text_th" required>
                                                </div>
                                                <div class="tab-pane fade" id="choice-en" role="tabpanel">
                                                    <label class="form-label">Choice Text (English)</label>
                                                    <input type="text" class="form-control" id="choice_text_en" name="choice_text_en">
                                                </div>
                                                <div class="tab-pane fade" id="choice-cn" role="tabpanel">
                                                    <label class="form-label">选项文本 (Chinese)</label>
                                                    <input type="text" class="form-control" id="choice_text_cn" name="choice_text_cn">
                                                </div>
                                                <div class="tab-pane fade" id="choice-jp" role="tabpanel">
                                                    <label class="form-label">選択肢のテキスト (Japanese)</label>
                                                    <input type="text" class="form-control" id="choice_text_jp" name="choice_text_jp">
                                                </div>
                                                <div class="tab-pane fade" id="choice-kr" role="tabpanel">
                                                    <label class="form-label">선택 텍스트 (Korean)</label>
                                                    <input type="text" class="form-control" id="choice_text_kr" name="choice_text_kr">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-end">
                                        <button type="button" class="btn btn-secondary" onclick="cancelChoiceForm()">
                                            <i class="fas fa-times"></i> ยกเลิก
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="saveChoice()">
                                            <i class="fas fa-save"></i> บันทึก
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Choices List -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="choicesTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 80px;">ลำดับ</th>
                                    <th>ตัวเลือก (TH)</th>
                                    <th>ตัวเลือก (EN)</th>
                                    <th style="width: 100px;">สถานะ</th>
                                    <th style="width: 120px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="choicesTableBody">
                                <!-- Choices will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #choicesModal .modal-dialog {
            max-width: 1000px;
        }

        #choicesTable {
            font-size: 13px;
        }

        #choicesTable thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        #choicesTable tbody tr {
            transition: all 0.2s;
        }

        #choicesTable tbody tr:hover {
            background: #f8f9fa;
        }

        .choice-order-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            min-width: 35px;
            text-align: center;
            display: inline-block;
        }

        .choice-text {
            color: #2d3748;
            font-weight: 500;
            line-height: 1.5;
        }

        #choiceFormContainer {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .choices-count-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            min-width: 30px;
            text-align: center;
        }
    </style>

</body>
</html>