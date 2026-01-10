<?php
require_once('lib/connect.php');
global $conn;
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'cn', 'jp', 'kr']) ? $_GET['lang'] : 'th';
$subject_col = 'subject_project';
$content_col = 'content_project';
if ($lang === 'en') {
    $subject_col = 'subject_project_en';
    $content_col = 'content_project_en';
} elseif ($lang === 'cn') {
    $subject_col = 'subject_project_cn';
    $content_col = 'content_project_cn';
} elseif ($lang === 'jp') {
    $subject_col = 'subject_project_jp';
    $content_col = 'content_project_jp';
} elseif ($lang === 'kr') {
    $subject_col = 'subject_project_kr';
    $content_col = 'content_project_kr';
}

$subjectTitle = ($lang === 'en') ? "Project" : (($lang === 'cn') ? "项目" : (($lang === 'jp') ? "プロジェクト" : (($lang === 'kr') ? "프로젝트" : "โปรเจกต์"))); // Fallback title
$pageUrl = "";

if (isset($_GET['id'])) {
    $encodedId = $_GET['id'];
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $pageUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    $decodedId = base64_decode(urldecode($_GET['id']));

    if ($decodedId !== false) {
        $stmt = $conn->prepare("SELECT {$subject_col} as subject_project FROM dn_project WHERE del = 0 AND project_id = ?");
        $stmt->bind_param('i', $decodedId);
        $stmt->execute();
        $resultTitle = $stmt->get_result();
        if ($resultTitle->num_rows > 0) {
            $row = $resultTitle->fetch_assoc();
            $subjectTitle = $row['subject_project'];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($subjectTitle); ?></title>
    <?php include 'template/header.php' ?>
    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="app/css/news_.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        img {
            max-width: 100%;
            height: auto;
        }

        .shop-content-display {
            font-family: sans-serif, "Roboto" !important;
        }

        .shop-wrapper-container {
            position: relative;
            max-width: 1280px;
            margin: 0;
            overflow: hidden;
            padding: 0 40px;
        }

        .shop-scroll {
            display: flex;
            gap: 10px;
            scroll-behavior: smooth;
            overflow-x: auto;
            padding-bottom: 1rem;
            scrollbar-width: none;
        }

        .shop-scroll::-webkit-scrollbar {
            display: none;
        }

        .shop-card {
            flex: 0 0 300px;
            max-width: 300px;
            height: auto;
        }

        .related-shop-box {
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            background-color: #fff;
            text-decoration: none;
            color: #333;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .related-shop-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card-image-wrapper {
            height: 220px;
            overflow: hidden;
        }

        .card-img-top {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-body {
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            flex-grow: 1;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-text {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(255, 255, 255, 0.8);
            border: 1px solid #ccc;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            cursor: pointer;
            z-index: 5;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .scroll-btn.left {
            left: 5px;
        }

        .scroll-btn.right {
            right: 5px;
        }

        .social-share {
            margin-top: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .social-share p {
            margin-right: 15px;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .social-share a {
            margin-right: 10px;
            text-decoration: none;
        }

        .social-share img {
            height: 40px;
            transition: transform 0.2s ease;
        }

        .social-share a:hover img {
            transform: scale(1.1);
        }

        .copy-link-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 12px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .copy-link-btn:hover {
            background-color: #5a6268;
        }
    </style>

</head>

<body>
    <ul id="flag-dropdown-list" class="flag-dropdown" style="left: 74%;">
    </ul>
    <?php include 'template/navbar_slide.php' ?>

    <div class="content-sticky" id="">
        <div class="container" style="max-width: 90%;">
            <div class="box-content">
                <div class="social-share"
                    style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                    <p data-translate="share" lang="th" style="margin: 0; font-size:18px; font-family: sans-serif;">
                        แชร์หน้านี้:</p>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>"
                            target="_blank">
                            <img src="https://img.icons8.com/color/48/000000/facebook-new.png" alt="Share on Facebook">
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($subjectTitle) ?>"
                            target="_blank">
                            <img style="height: 33px;  border-radius: 6px;"
                                src="https://cdn.prod.website-files.com/5d66bdc65e51a0d114d15891/64cebdd90aef8ef8c749e848_X-EverythingApp-Logo-Twitter.jpg"
                                alt="Share on Twitter">
                        </a>
                        <a href="https://social-plugins.line.me/lineit/share?url=<?= urlencode($pageUrl) ?>"
                            target="_blank">
                            <img src="https://img.icons8.com/color/48/000000/line-me.png" alt="Share on Line">
                        </a>
                        <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode($pageUrl) ?>"
                            target="_blank">
                            <img src="https://img.icons8.com/color/48/000000/pinterest--v1.png"
                                alt="Share on Pinterest">
                        </a>
                        <a href="https://www.instagram.com/" target="_blank">
                            <img src="https://img.icons8.com/fluency/48/instagram-new.png" alt="Share on Instagram">
                        </a>
                        <a href="https://www.tiktok.com/" target="_blank">
                            <img src="https://img.icons8.com/fluency/48/tiktok.png" alt="Share on TikTok">
                        </a>
                        <button class="copy-link-btn" onclick="copyLink()">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="">
                        <?php
                        if (isset($_GET['id'])) {
                            $decodedId = base64_decode(urldecode($_GET['id']));

                            if ($decodedId !== false) {
                                $stmt = $conn->prepare("SELECT 
                                        dn.project_id, 
                                        dn.{$subject_col} AS subject_project, 
                                        dn.{$content_col} AS content_project, 
                                        dn.date_create, 
                                        GROUP_CONCAT(dnc.file_name) AS file_name,
                                        GROUP_CONCAT(dnc.api_path) AS pic_path
                                        FROM dn_project dn
                                        LEFT JOIN dn_project_doc dnc ON dn.project_id = dnc.project_id
                                        WHERE dn.project_id = ?
                                        GROUP BY dn.project_id");

                                $stmt->bind_param('i', $decodedId);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $content = $row['content_project'];
                                        $paths = !empty($row['pic_path']) ? explode(',', $row['pic_path']) : [];
                                        $files = !empty($row['file_name']) ? explode(',', $row['file_name']) : [];
                                        $found = false;

                                        foreach ($files as $index => $file) {
                                            $pattern = '/<img[^>]+data-filename="' . preg_quote($file, '/') . '"[^>]*>/i';

                                            if (isset($paths[$index]) && preg_match($pattern, $content, $matches)) {
                                                $new_src = $paths[$index];
                                                $new_img_tag = preg_replace('/(<img[^>]+)(src="[^"]*")/i', '$1 src="' . htmlspecialchars($new_src) . '"', $matches[0]);
                                                $content = str_replace($matches[0], $new_img_tag, $content);
                                                $found = true;
                                            }
                                        }

                                        if (!$found && count($paths) > 0) {
                                        }

                                        echo '<div class="shop-content-display">';
                                        echo $content;
                                        echo '</div>';
                                    }
                                } else {
                                    echo ($lang === 'en') ? "No data found." : (($lang === 'cn') ? "找不到数据。" : (($lang === 'jp') ? "データが見つかりません。" : (($lang === 'kr') ? "데이터를 찾을 수 없습니다." : "ไม่มีข้อมูล")));
                                }

                                $stmt->close();
                            } else {
                                echo ($lang === 'en') ? "Invalid ID." : (($lang === 'cn') ? "无效ID。" : (($lang === 'jp') ? "無効なIDです。" : (($lang === 'kr') ? "유효하지 않은 ID입니다." : "รหัสไม่ถูกต้อง")));
                            }
                        }
                        ?>
                    </div>
                </div>
                <hr style="border-top: dashed 1px; margin: 20px 0;">

                <div class="social-share" style="display: flex; align-items: center; gap: 10px;">
                    <button class="copy-link-btn" onclick="copyLink()">
                        <i class="fas fa-link"></i>
                    </button>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank">
                        <img src="https://img.icons8.com/color/48/000000/facebook-new.png" alt="Share on Facebook">
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($subjectTitle) ?>"
                        target="_blank">
                        <img style="height: 33px; border-radius: 6px;"
                            src="https://cdn.prod.website-files.com/5d66bdc65e51a0d114d15891/64cebdd90aef8ef8c749e848_X-EverythingApp-Logo-Twitter.jpg"
                            alt="Share on Twitter">
                    </a>
                    <a href="https://social-plugins.line.me/lineit/share?url=<?= urlencode($pageUrl) ?>"
                        target="_blank">
                        <img src="https://img.icons8.com/color/48/000000/line-me.png" alt="Share on Line">
                    </a>
                    <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode($pageUrl) ?>" target="_blank">
                        <img src="https://img.icons8.com/color/48/000000/pinterest--v1.png" alt="Share on Pinterest">
                    </a>
                    <a href="https://www.instagram.com/" target="_blank">
                        <img src="https://img.icons8.com/fluency/48/instagram-new.png" alt="Share on Instagram">
                    </a>
                    <a href="https://www.tiktok.com/" target="_blank">
                        <img src="https://img.icons8.com/fluency/48/tiktok.png" alt="Share on TikTok">
                    </a>

                </div>
                <div style="padding-left:50px;">
                    <hr style="border-top: dashed 1px; margin: 20px 0;">
                    <p><?= ($lang === 'en') ? 'Inquire/order perfume Acoustics products at' : (($lang === 'cn') ? '在以下位置咨询/订购 perfume Acoustics 产品：' : (($lang === 'jp') ? 'perfume Acoustics製品のお問い合わせ・ご注文はこちらから' : (($lang === 'kr') ? 'perfume Acoustics 제품 문의/주문은 다음을 통해 가능합니다' : 'สอบถาม/สั่งซื้อผลิตภัณฑ์ perfume Acoustics ได้ที่'))); ?>
                    </p>
                    <p>🛒 Website : <a style="color:#3e5beaff;" href="https://www.perfume.com/store/app/index.php"
                            target="_blank">www.perfume.com/store/</a></p>
                    <p>📱 Line OA : @perfumeaocoustic
                        <a style="color:#3e5beaff;" href="https://lin.ee/yoSCNwF"
                            target="_blank">https://lin.ee/yoSCNwF</a>
                    </p>
                    <p>📱 Line OA : @perfumestore
                        <a style="color:#3e5beaff;" href="https://lin.ee/xJr661u"
                            target="_blank">https://lin.ee/xJr661u</a>
                    </p>
                    <p>☎️ Tel : 02-722-7007</p>
                </div>

                <?php
                if (isset($_GET['id'])) {
                    $decodedId = base64_decode(urldecode($_GET['id']));
                    if ($decodedId !== false) {
                        $subject_shop_col = 'subject_shop';
                        $description_shop_col = 'description_shop';
                        $content_shop_col = 'content_shop';

                        if ($lang === 'en') {
                            $subject_shop_col = 'subject_shop_en';
                            $description_shop_col = 'description_shop_en';
                            $content_shop_col = 'content_shop_en';
                        } elseif ($lang === 'cn') {
                            $subject_shop_col = 'subject_shop_cn';
                            $description_shop_col = 'description_shop_cn';
                            $content_shop_col = 'content_shop_cn';
                        } elseif ($lang === 'jp') {
                            $subject_shop_col = 'subject_shop_jp';
                            $description_shop_col = 'description_shop_jp';
                            $content_shop_col = 'content_shop_jp';
                        } elseif ($lang === 'kr') {
                            $subject_shop_col = 'subject_shop_kr';
                            $description_shop_col = 'description_shop_kr';
                            $content_shop_col = 'content_shop_kr';
                        }

                        $stmt_shop = $conn->prepare("
                            SELECT 
                                ds.shop_id, 
                                ds.{$subject_shop_col} AS subject_shop, 
                                ds.{$description_shop_col} AS description_shop,
                                ds.{$content_shop_col} AS content_shop,
                                GROUP_CONCAT(dnd.api_path) AS pic_path
                            FROM dn_shop ds
                            JOIN dn_project_shop dps ON ds.shop_id = dps.shop_id
                            LEFT JOIN dn_shop_doc dnd ON ds.shop_id = dnd.shop_id AND dnd.del = '0' AND dnd.status = '1'
                            WHERE dps.project_id = ? AND ds.del = '0'
                            GROUP BY ds.shop_id
                        ");
                        $stmt_shop->bind_param('i', $decodedId);
                        $stmt_shop->execute();
                        $result_shop = $stmt_shop->get_result();

                        if ($result_shop->num_rows > 0) {
                            echo '<h3 style="padding-top: 40px;">' . (($lang === 'en') ? 'Related Products' : (($lang === 'cn') ? '相关产品' : (($lang === 'jp') ? '関連製品' : (($lang === 'kr') ? '관련 제품' : 'สินค้าที่เกี่ยวข้องกับโปรเจกต์นี้')))) . '</h3>';
                            echo '<div class="shop-wrapper-container">';
                            echo '<div class="scroll-btn left" onclick="scrollshop(\'left\')">&#10094;</div>';
                            echo '<div class="scroll-btn right" onclick="scrollshop(\'right\')">&#10095;</div>';
                            echo '<div class="shop-scroll" id="shop-scroll-box">';

                            while ($row_shop = $result_shop->fetch_assoc()) {
                                $shopIdEncoded = urlencode(base64_encode($row_shop['shop_id']));
                                $shop_link = "?product_detail&id=" . $shopIdEncoded . "&lang=" . htmlspecialchars($lang);

                                $content = $row_shop['content_shop'];
                                $iframeSrc = null;
                                if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
                                    $iframeSrc = isset($matches[1]) ? explode(',', $matches[1]) : null;
                                }
                                $iframe = isset($iframeSrc[0]) ? $iframeSrc[0] : null;

                                $paths = !empty($row_shop['pic_path']) ? explode(',', $row_shop['pic_path']) : [];
                                $image_path = !empty($paths) ? $paths[0] : null;

                                $placeholder_text = ($lang === 'en') ? 'Shop+Image' : (($lang === 'cn') ? '产品图片' : (($lang === 'jp') ? '製品画像' : (($lang === 'kr') ? '제품 이미지' : 'รูปภาพสินค้า')));
                                $placeholder_image = 'https://via.placeholder.com/300x220.png?text=' . $placeholder_text;

                                echo '<div class="shop-card">';
                                echo '<a href="' . htmlspecialchars($shop_link) . '" class="related-shop-box">';

                                if (!empty($iframe)) {
                                    echo '<iframe frameborder="0" src="' . htmlspecialchars($iframe) . '" width="100%" height="220px" class="note-video-clip"></iframe>';
                                } else if (!empty($image_path)) {
                                    echo '<div class="card-image-wrapper">';
                                    echo '<img src="' . htmlspecialchars($image_path) . '" class="card-img-top" alt="' . htmlspecialchars($row_shop['subject_shop']) . '">';
                                    echo '</div>';
                                } else {
                                    echo '<div class="card-image-wrapper">';
                                    echo '<img src="' . htmlspecialchars($placeholder_image) . '" class="card-img-top" alt="' . (($lang === 'en') ? 'No image available' : (($lang === 'cn') ? '没有可用的图像' : (($lang === 'jp') ? '画像がありません' : (($lang === 'kr') ? '이미지 없음' : 'ไม่มีรูปภาพ')))) . '">';
                                    echo '</div>';
                                }

                                echo '<div class="card-body">';
                                echo '<h5 class="card-title">' . htmlspecialchars($row_shop['subject_shop']) . '</h5>';
                                echo '<p class="card-text">' . htmlspecialchars($row_shop['description_shop']) . '</p>';
                                echo '</div>';
                                echo '</a>';
                                echo '</div>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                        $stmt_shop->close();
                    }
                }
                ?>

                <h3 style="padding-top: 40px;">
                    <?= ($lang === 'en') ? 'Comments' : (($lang === 'cn') ? '评论' : (($lang === 'jp') ? 'コメント' : (($lang === 'kr') ? '댓글' : 'ความคิดเห็น'))); ?>
                </h3>
                <p><?= ($lang === 'en') ? 'Your email will not be displayed to others. Required fields are marked *' : (($lang === 'cn') ? '您的电子邮件不会显示给其他人。必填字段已标记 *' : (($lang === 'jp') ? 'あなたのメールアドレスが他の人に表示されることはありません。必須項目は*でマークされています' : (($lang === 'kr') ? '귀하의 이메일은 다른 사람에게 표시되지 않습니다. 필수 필드는 *로 표시됩니다' : 'อีเมลของคุณจะไม่แสดงให้คนอื่นเห็น ช่องข้อมูลจำเป็นถูกทำเครื่องหมาย *'))); ?>
                </p>
                <form id="commentForm" style="max-width: 600px;">
                    <textarea id="commentText" name="comment" rows="5" required
                        placeholder="<?= ($lang === 'en') ? 'Comment *' : (($lang === 'cn') ? '评论 *' : (($lang === 'jp') ? 'コメント *' : (($lang === 'kr') ? '댓글 *' : 'ความคิดเห็น *'))); ?>"
                        style="width: 100%; padding: 12px; margin-bottom: 3px; border: 1px solid #ccc; border-radius: 6px;"></textarea><br>
                    <button type="submit"
                        style="background-color: red; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        <?= ($lang === 'en') ? 'Submit Comment' : (($lang === 'cn') ? '提交评论' : (($lang === 'jp') ? 'コメントを送信' : (($lang === 'kr') ? '댓글 제출' : 'แสดงความคิดเห็น'))); ?>
                    </button>
                </form>


                <script>
                    document.getElementById("commentForm").addEventListener("submit", function (e) {
                        e.preventDefault();

                        const jwt = sessionStorage.getItem("jwt");
                        const comment = document.getElementById("commentText").value;
                        const pageUrl = window.location.pathname;

                        if (!jwt) {
                            document.getElementById("myBtn-sign-in").click();
                            return;
                        }

                        fetch('actions/protected.php', {
                            method: 'GET',
                            headers: {
                                'Authorization': 'Bearer ' + jwt
                            }
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === "success" && parseInt(data.data.role_id) === 3) {
                                    fetch('actions/save_comment.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Authorization': 'Bearer ' + jwt
                                        },
                                        body: JSON.stringify({
                                            comment: comment,
                                            page_url: pageUrl
                                        })
                                    })
                                        .then(res => res.json())
                                        .then(result => {
                                            let alertMessage = "";
                                            if (result.status === 'success') {
                                                alertMessage = "<?= ($lang === 'en') ? 'Comment saved successfully.' : (($lang === 'cn') ? '评论已成功保存。' : (($lang === 'jp') ? 'コメントが正常に保存されました。' : (($lang === 'kr') ? '댓글이 성공적으로 저장되었습니다.' : 'บันทึกความคิดเห็นเรียบร้อยแล้ว'))); ?>";
                                                document.getElementById("commentText").value = '';
                                            } else {
                                                alertMessage = "<?= ($lang === 'en') ? 'Error: ' : (($lang === 'cn') ? '错误：' : (($lang === 'jp') ? 'エラー：' : (($lang === 'kr') ? '오류: ' : 'เกิดข้อผิดพลาด: '))); ?>" + result.message;
                                            }
                                            alert(alertMessage);
                                        });
                                } else {
                                    alert("<?= ($lang === 'en') ? 'You must be logged in as a viewer to comment.' : (($lang === 'cn') ? '您必须以查看者身份登录才能发表评论。' : (($lang === 'jp') ? 'コメントするには視聴者としてログインする必要があります。' : (($lang === 'kr') ? '댓글을 남기려면 뷰어로 로그인해야 합니다.' : 'ต้องเข้าสู่ระบบในฐานะ viewer เท่านั้น'))); ?>");
                                }
                            })
                            .catch(err => {
                                console.error("Error verifying user:", err);
                                alert("<?= ($lang === 'en') ? 'Authentication error occurred.' : (($lang === 'cn') ? '发生身份验证错误。' : (($lang === 'jp') ? '認証エラーが発生しました。' : (($lang === 'kr') ? '인증 오류가 발생했습니다.' : 'เกิดข้อผิดพลาดในการยืนยันตัวตน'))); ?>");
                            });
                    });

                    function scrollshop(direction) {
                        const box = document.getElementById('shop-scroll-box');
                        const scrollAmount = 300 + 10;
                        if (direction === 'left') {
                            box.scrollLeft -= scrollAmount;
                        } else {
                            box.scrollLeft += scrollAmount;
                        }
                    }
                    function copyLink() {
                        const pageUrl = "<?= $pageUrl ?>";
                        navigator.clipboard.writeText(pageUrl).then(function () {
                            alert("<?= ($lang === 'en') ? 'Link copied successfully!' : (($lang === 'cn') ? '链接复制成功！' : (($lang === 'jp') ? 'リンクが正常にコピーされました！' : (($lang === 'kr') ? '링크가 성공적으로 복사되었습니다!' : 'คัดลอกลิงก์เรียบร้อยแล้ว'))); ?>");
                        }, function () {
                            alert("<?= ($lang === 'en') ? 'Unable to copy link. Please copy it manually.' : (($lang === 'cn') ? '无法复制链接。请手动复制。' : (($lang === 'jp') ? 'リンクをコピーできません。手動でコピーしてください。' : (($lang === 'kr') ? '링크를 복사할 수 없습니다. 수동으로 복사하십시오.' : 'ไม่สามารถคัดลอกลิงก์ได้ กรุณาคัดลอกด้วยตนเอง'))); ?>");
                        });
                    }
                </script>
            </div>

        </div>

    </div>

    <?php include 'template/footer.php' ?>


    <script src="index_.js?v=<?php echo time(); ?>"></script>
    <script src="app/js/project/project_.js?v=<?php echo time(); ?>"></script>

</body>

</html>