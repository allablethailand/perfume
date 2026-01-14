<?php
include '../check_permission.php'; 
// require_once(__DIR__ . '/../../../../lib/connect.php');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit About Page</title>

    <link rel="icon" type="image/x-icon" href="../../../public/img/q-removebg-preview1.png">
    
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>

    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css" crossorigin="anonymous" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&family=Roboto&display=swap" rel="stylesheet">

    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>

    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>

    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-iconpicker/1.10.0/css/bootstrap-iconpicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-iconpicker/1.10.0/js/bootstrap-iconpicker.bundle.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.js"></script>

    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <style>
        .button-class { background-color: #4CAF50; color: white; border: none; padding: 10px 15px; cursor: pointer; }
        .responsive-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 10px; }
        @media (max-width: 768px) { .responsive-grid { grid-template-columns: 1fr; } }
        .btn-circle { border: none; width: 30px; height: 28px; border-radius: 50%; font-size: 14px; }
        .btn-edit { background-color: #FFC107; color: #ffffff; }
        .btn-del { background-color: #ff4537; color: #ffffff; }
        body, .note-editable { font-family: 'Kanit', 'Roboto', sans-serif; }
        .note-editable { font-family: 'Kanit', 'Roboto', sans-serif !important; font-size: inherit; }
        .note-editable span[style*="font-size"] { display: inline !important; }
        #loading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); display: flex; justify-content: center; align-items: center; z-index: 9999; }
        .spinner-border { width: 3rem; height: 3rem; }
        .lang-switch-btn {
            padding: 5px 10px;
            border: 1px solid #ccc;
            background-color: #f8f9fa;
        }
        .lang-switch-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
</head>

<?php include '../template/header.php' ?>

<body>
<div id="loading-overlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>แก้ไขเนื้อหา "เกี่ยวกับเรา"</h3>
        <div class="btn-group">
            <button type="button" class="btn lang-switch-btn active" data-lang="th">
                <img src="https://flagcdn.com/th.svg" alt="Thai" width="24"> TH
            </button>
            <button type="button" class="btn lang-switch-btn" data-lang="en">
                <img src="https://flagcdn.com/us.svg" alt="English" width="24"> EN
            </button>
            <button type="button" class="btn lang-switch-btn" data-lang="cn">
                <img src="https://flagcdn.com/cn.svg" alt="Chinese" width="24"> CN
            </button>
            <button type="button" class="btn lang-switch-btn" data-lang="jp">
                <img src="https://flagcdn.com/jp.svg" alt="Japanese" width="24"> JP
            </button>
            <button type="button" class="btn lang-switch-btn" data-lang="kr">
                <img src="https://flagcdn.com/kr.svg" alt="Korean" width="24"> KR
            </button>
        </div>
    </div>
    
    <div id="add-new-block" class="card mb-4 border-success">
        <div class="card-body">
            <h4>เพิ่มเนื้อหาใหม่</h4>
            <form id="addAboutForm" method="post" enctype="multipart/form-data">
                <div class="lang-section th-lang active">
                    <label>ประเภท</label>
                    <select name="type_th" class="form-control" required>
                        <option value="text">ข้อความ</option>
                        <option value="image">รูปภาพ + ข้อความ</option>
                        <option value="quote">คำคม</option>
                    </select>
                    <label>เนื้อหา (HTML)</label>
                    <textarea name="content_th" class="form-control summernote"></textarea>
                    <label>ผู้พูด (สำหรับคำคม)</label>
                    <input type="text" name="author_th" class="form-control">
                    <label>ตำแหน่ง</label>
                    <input type="text" name="position_th" class="form-control">
                </div>
                <div class="lang-section en-lang" style="display:none;">
                    <label>Type</label>
                    <select name="type_en" class="form-control">
                        <option value="text">Text</option>
                        <option value="image">Image + Text</option>
                        <option value="quote">Quote</option>
                    </select>
                    <label>Content (HTML)</label>
                    <textarea name="content_en" class="form-control summernote"></textarea>
                    <label>Author (for quotes)</label>
                    <input type="text" name="author_en" class="form-control">
                    <label>Position</label>
                    <input type="text" name="position_en" class="form-control">
                </div>
                <div class="lang-section cn-lang" style="display:none;">
                    <label>类型</label>
                    <select name="type_cn" class="form-control">
                        <option value="text">文本</option>
                        <option value="image">图片 + 文本</option>
                        <option value="quote">引文</option>
                    </select>
                    <label>内容 (HTML)</label>
                    <textarea name="content_cn" class="form-control summernote"></textarea>
                    <label>作者 (引文)</label>
                    <input type="text" name="author_cn" class="form-control">
                    <label>职位</label>
                    <input type="text" name="position_cn" class="form-control">
                </div>
                <div class="lang-section jp-lang" style="display:none;">
                    <label>タイプ</label>
                    <select name="type_jp" class="form-control">
                        <option value="text">テキスト</option>
                        <option value="image">画像 + テキスト</option>
                        <option value="quote">引用</option>
                    </select>
                    <label>コンテンツ (HTML)</label>
                    <textarea name="content_jp" class="form-control summernote"></textarea>
                    <label>話者 (引用文)</label>
                    <input type="text" name="author_jp" class="form-control">
                    <label>役職</label>
                    <input type="text" name="position_jp" class="form-control">
                </div>
                <div class="lang-section kr-lang" style="display:none;">
                    <label>유형</label>
                    <select name="type_kr" class="form-control">
                        <option value="text">텍스트</option>
                        <option value="image">이미지 + 텍스트</option>
                        <option value="quote">인용문</option>
                    </select>
                    <label>내용 (HTML)</label>
                    <textarea name="content_kr" class="form-control summernote"></textarea>
                    <label>화자 (인용문)</label>
                    <input type="text" name="author_kr" class="form-control">
                    <label>직책</label>
                    <input type="text" name="position_kr" class="form-control">
                </div>
                <label>อัปโหลดรูปภาพ (ถ้ามี)</label>
                <input type="file" name="image_file" class="form-control">
                <button class="btn btn-primary mt-3" type="submit" id="submitAdd">เพิ่มเนื้อหาใหม่</button>
            </form>
        </div>
    </div>

    <hr>

    <form id="editAboutForm" method="post" enctype="multipart/form-data">
        <?php
        $result = $conn->query("SELECT * FROM about_content ORDER BY id ASC");
        while ($row = $result->fetch_assoc()):
            $id = htmlspecialchars($row['id']);
            $type_th = htmlspecialchars($row['type'] ?? '');
            $content_th = $row['content'] ?? '';
            $image_url = htmlspecialchars($row['image_url'] ?? '');
            $author = htmlspecialchars($row['author'] ?? '');
            $position = htmlspecialchars($row['position'] ?? '');
            
            // ดึงข้อมูลภาษาอังกฤษ, จีน, ญี่ปุ่น และเกาหลี
            $type_en = htmlspecialchars($row['type_en'] ?? '');
            $content_en = $row['content_en'] ?? '';
            $type_cn = htmlspecialchars($row['type_cn'] ?? '');
            $content_cn = $row['content_cn'] ?? '';
            $type_jp = htmlspecialchars($row['type_jp'] ?? '');
            $content_jp = $row['content_jp'] ?? '';
            $type_kr = htmlspecialchars($row['type_kr'] ?? '');
            $content_kr = $row['content_kr'] ?? '';
        ?>
            <div class="card mb-3 block-item" data-id="<?= $id ?>">
                <div class="card-body">
                    <input type="hidden" name="ids[]" value="<?= $id ?>">
                    
                    <div class="lang-section th-lang active">
                        <label>ประเภท</label>
                        <select name="types_th[]" class="form-control">
                            <option value="text" <?= $type_th == 'text' ? 'selected' : '' ?>>ข้อความ</option>
                            <option value="image" <?= $type_th == 'image' ? 'selected' : '' ?>>รูปภาพ + ข้อความ</option>
                            <option value="quote" <?= $type_th == 'quote' ? 'selected' : '' ?>>คำคม</option>
                        </select>
                        <label>เนื้อหา (HTML)</label>
                        <textarea name="contents_th[]" class="form-control summernote"><?= $content_th ?></textarea>
                        <label>ผู้พูด</label>
                        <input type="text" name="authors[]" class="form-control" value="<?= $author ?>">
                        <label>ตำแหน่ง</label>
                        <input type="text" name="positions[]" class="form-control" value="<?= $position ?>">
                    </div>

                    <div class="lang-section en-lang" style="display:none;">
                        <button type="button" class="btn btn-info btn-sm mb-2 copy-from-th" data-id="<?= $id ?>">Copy from Thai</button>
                        <label>Type</label>
                        <select name="types_en[]" class="form-control">
                            <option value="text" <?= $type_en == 'text' ? 'selected' : '' ?>>Text</option>
                            <option value="image" <?= $type_en == 'image' ? 'selected' : '' ?>>Image + Text</option>
                            <option value="quote" <?= $type_en == 'quote' ? 'selected' : '' ?>>Quote</option>
                        </select>
                        <label>Content (HTML)</label>
                        <textarea name="contents_en[]" class="form-control summernote"><?= $content_en ?></textarea>
                        <label>Author</label>
                        <input type="text" name="authors_en[]" class="form-control" value="<?= $author ?>">
                        <label>Position</label>
                        <input type="text" name="positions_en[]" class="form-control" value="<?= $position ?>">
                    </div>

                    <div class="lang-section cn-lang" style="display:none;">
                        <button type="button" class="btn btn-info btn-sm mb-2 copy-from-th" data-id="<?= $id ?>">Copy from Thai</button>
                        <label>类型</label>
                        <select name="types_cn[]" class="form-control">
                            <option value="text" <?= $type_cn == 'text' ? 'selected' : '' ?>>文本</option>
                            <option value="image" <?= $type_cn == 'image' ? 'selected' : '' ?>>图片 + 文本</option>
                            <option value="quote" <?= $type_cn == 'quote' ? 'selected' : '' ?>>引文</option>
                        </select>
                        <label>内容 (HTML)</label>
                        <textarea name="contents_cn[]" class="form-control summernote"><?= $content_cn ?></textarea>
                        <label>作者</label>
                        <input type="text" name="authors_cn[]" class="form-control" value="<?= $author ?>">
                        <label>职位</label>
                        <input type="text" name="positions_cn[]" class="form-control" value="<?= $position ?>">
                    </div>
                    
                    <div class="lang-section jp-lang" style="display:none;">
                        <button type="button" class="btn btn-info btn-sm mb-2 copy-from-th" data-id="<?= $id ?>">Copy from Thai</button>
                        <label>タイプ</label>
                        <select name="types_jp[]" class="form-control">
                            <option value="text" <?= $type_jp == 'text' ? 'selected' : '' ?>>テキスト</option>
                            <option value="image" <?= $type_jp == 'image' ? 'selected' : '' ?>>画像 + テキスト</option>
                            <option value="quote" <?= $type_jp == 'quote' ? 'selected' : '' ?>>引用</option>
                        </select>
                        <label>コンテンツ (HTML)</label>
                        <textarea name="contents_jp[]" class="form-control summernote"><?= $content_jp ?></textarea>
                        <label>話者</label>
                        <input type="text" name="authors_jp[]" class="form-control" value="<?= $author ?>">
                        <label>役職</label>
                        <input type="text" name="positions_jp[]" class="form-control" value="<?= $position ?>">
                    </div>

                    <div class="lang-section kr-lang" style="display:none;">
                        <button type="button" class="btn btn-info btn-sm mb-2 copy-from-th" data-id="<?= $id ?>">Copy from Thai</button>
                        <label>유형</label>
                        <select name="types_kr[]" class="form-control">
                            <option value="text" <?= $type_kr == 'text' ? 'selected' : '' ?>>텍스트</option>
                            <option value="image" <?= $type_kr == 'image' ? 'selected' : '' ?>>이미지 + 텍스트</option>
                            <option value="quote" <?= $type_kr == 'quote' ? 'selected' : '' ?>>인용문</option>
                        </select>
                        <label>내용 (HTML)</label>
                        <textarea name="contents_kr[]" class="form-control summernote"><?= $content_kr ?></textarea>
                        <label>화자</label>
                        <input type="text" name="authors_kr[]" class="form-control" value="<?= $author ?>">
                        <label>직책</label>
                        <input type="text" name="positions_kr[]" class="form-control" value="<?= $position ?>">
                    </div>

                    <div class="image-section mt-3">
                        <label>อัปโหลดรูปภาพใหม่ (ถ้ามี)</label>
                        <?php if (!empty($image_url)): ?>
                            <div class="mb-2">
                                <img src="<?= $image_url ?>" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                <br><small>รูปภาพปัจจุบัน</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image_files[]" class="form-control">
                        <input type="hidden" name="images_old[]" value="<?= $image_url ?>">
                    </div>
                    <button type="button" class="btn btn-danger btn-sm mt-2 remove-block" data-id="<?= $id ?>">ลบบล็อกนี้</button>
                </div>
            </div>
        <?php endwhile; ?>
        <button class="btn btn-success mt-3" type="submit" id="submitEdit">บันทึกทั้งหมด</button>
    </form>
</div>

<script src='../js/index_.js?v=<?php echo time(); ?>'></script>
<script src='js/about_.js?v=<?php echo time(); ?>'></script>
</body>
</html>