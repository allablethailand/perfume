<?php
require_once('lib/connect.php');
global $conn;
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'cn', 'jp', 'kr']) ? $_GET['lang'] : 'th';

$subjectTitle = ($lang === 'cn' ? '商品' : ($lang === 'en' ? 'Product' : ($lang === 'jp' ? '製品' : ($lang === 'kr' ? '제품' : 'สินค้า')))); // fallback title
$pageUrl = "";

if (isset($_GET['id'])) {
    $encodedId = $_GET['id'];
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

    $pageUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    $decodedId = base64_decode(urldecode($_GET['id']));

    if ($decodedId !== false) {
        $stmt = $conn->prepare("SELECT subject_idia, subject_idia_en, subject_idia_cn, subject_idia_jp, subject_idia_kr FROM dn_idia WHERE del = 0 AND idia_id = ?");
        $stmt->bind_param('i', $decodedId);
        $stmt->execute();
        $resultTitle = $stmt->get_result();
        if ($resultTitle->num_rows > 0) {
            $row = $resultTitle->fetch_assoc();
            if ($lang === 'en' && !empty($row['subject_idia_en'])) {
                $subjectTitle = $row['subject_idia_en'];
            } elseif ($lang === 'cn' && !empty($row['subject_idia_cn'])) {
                $subjectTitle = $row['subject_idia_cn'];
            } elseif ($lang === 'jp' && !empty($row['subject_idia_jp'])) {
                $subjectTitle = $row['subject_idia_jp'];
            } elseif ($lang === 'kr' && !empty($row['subject_idia_kr'])) {
                $subjectTitle = $row['subject_idia_kr'];
            } else {
                $subjectTitle = $row['subject_idia'];
            }
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
    <link href="app/css/idia_.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        .shop-content-display {
            font-family: sans-serif, "Roboto" !important;
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
                                        dn.idia_id, 
                                        dn.subject_idia,
                                        dn.subject_idia_en,
                                        dn.subject_idia_cn,
                                        dn.subject_idia_jp,
                                        dn.subject_idia_kr,
                                        dn.content_idia,
                                        dn.content_idia_en,
                                        dn.content_idia_cn,
                                        dn.content_idia_jp,
                                        dn.content_idia_kr,
                                        dn.date_create, 
                                        GROUP_CONCAT(dnc.file_name) AS file_name,
                                        GROUP_CONCAT(dnc.api_path) AS pic_path
                                        FROM dn_idia dn
                                        LEFT JOIN dn_idia_doc dnc ON dn.idia_id = dnc.idia_id
                                        WHERE dn.idia_id = ?
                                        GROUP BY dn.idia_id");

                                $stmt->bind_param('i', $decodedId);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $content = $row['content_idia'];
                                        if ($lang === 'en' && !empty($row['content_idia_en'])) {
                                            $content = $row['content_idia_en'];
                                        } elseif ($lang === 'cn' && !empty($row['content_idia_cn'])) {
                                            $content = $row['content_idia_cn'];
                                        } elseif ($lang === 'jp' && !empty($row['content_idia_jp'])) {
                                            $content = $row['content_idia_jp'];
                                        } elseif ($lang === 'kr' && !empty($row['content_idia_kr'])) {
                                            $content = $row['content_idia_kr'];
                                        }

                                        $paths = explode(',', $row['pic_path']);
                                        $files = explode(',', $row['file_name']);
                                        $found = false;

                                        foreach ($files as $index => $file) {
                                            $pattern = '/<img[^>]+data-filename="' . preg_quote($file, '/') . '"[^>]*>/i';

                                            if (preg_match($pattern, $content, $matches)) {
                                                $new_src = $paths[$index];
                                                $new_img_tag = preg_replace('/(<img[^>]+)(src="[^"]*")/i', '$1 src="' . $new_src . '"', $matches[0]);

                                                $content = str_replace($matches[0], $new_img_tag, $content);

                                                $found = true;
                                            }
                                        }

                                        if (!$found) {
                                            echo "";
                                        }

                                        echo '<div class="shop-content-display">';
                                        echo $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                                        echo '</div>';
                                    }
                                } else {
                                    echo ($lang === 'cn' ? '无可用信息。' : ($lang === 'en' ? 'No information available.' : ($lang === 'jp' ? '利用可能な情報はありません。' : ($lang === 'kr' ? '이용 가능한 정보가 없습니다.' : 'ไม่มีข้อมูล'))));
                                }

                                $stmt->close();
                            } else {
                                echo ($lang === 'cn' ? 'ID 无效。' : ($lang === 'en' ? 'Invalid ID.' : ($lang === 'jp' ? '無効なIDです。' : ($lang === 'kr' ? '유효하지 않은 ID입니다.' : 'ID ไม่ถูกต้อง'))));
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
                    <p>🛒 Website : <a style="color:#3e5beaff;" href="https://www.perfume.com/store/"
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



                <h3 style="padding-top: 40px;">
                    <?php echo ($lang === 'cn' ? '评论' : ($lang === 'en' ? 'Comments' : ($lang === 'jp' ? 'コメント' : ($lang === 'kr' ? '댓글' : 'ความคิดเห็น')))); ?>
                </h3>
                <p><?php echo ($lang === 'cn' ? '您的电子邮件地址不会被公开。必填字段已标记 *' : ($lang === 'en' ? 'Your email will not be displayed to others. Required fields are marked *' : ($lang === 'jp' ? 'メールアドレスが公開されることはありません。必須フィールドは * でマークされています' : ($lang === 'kr' ? '귀하의 이메일 주소는 공개되지 않습니다. 필수 필드는 *로 표시되어 있습니다.' : 'อีเมลของคุณจะไม่แสดงให้คนอื่นเห็น ช่องข้อมูลจำเป็นถูกทำเครื่องหมาย *')))); ?>
                </p>
                <form id="commentForm" style="max-width: 600px;">
                    <textarea id="commentText" name="comment" rows="5" required
                        placeholder="<?php echo ($lang === 'cn' ? '评论 *' : ($lang === 'en' ? 'Comment *' : ($lang === 'jp' ? 'コメント *' : ($lang === 'kr' ? '댓글 *' : 'ความคิดเห็น *')))); ?>"
                        style="width: 100%; padding: 12px; margin-bottom: 3px; border: 1px solid #ccc; border-radius: 6px;"></textarea><br>
                    <button type="submit"
                        style="background-color: red; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">
                        <?php echo ($lang === 'cn' ? '发表评论' : ($lang === 'en' ? 'Post Comment' : ($lang === 'jp' ? 'コメントを投稿' : ($lang === 'kr' ? '댓글 달기' : 'แสดงความคิดเห็น')))); ?>
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

                        const lang = "<?= $lang ?>";
                        const loginAlertMsg = lang === 'en' ? "Please log in to post a comment." : (lang === 'cn' ? "请登录后发表评论。" : (lang === 'jp' ? "コメントを投稿するにはログインしてください。" : (lang === 'kr' ? "댓글을 게시하려면 로그인하세요." : "กรุณาเข้าสู่ระบบก่อนแสดงความคิดเห็น")));
                        const roleAlertMsg = lang === 'en' ? "You must be logged in as a viewer to post a comment." : (lang === 'cn' ? "您必须以浏览者身份登录才能发表评论。" : (lang === 'jp' ? "コメントを投稿するには、閲覧者としてログインする必要があります。" : (lang === 'kr' ? "댓글을 게시하려면 뷰어 권한으로 로그인해야 합니다." : "ต้องเข้าสู่ระบบในฐานะ viewer เท่านั้น")));
                        const errorAlertMsg = lang === 'en' ? "An error occurred during authentication." : (lang === 'cn' ? "身份验证过程中发生错误。" : (lang === 'jp' ? "認証中にエラーが発生しました。" : (lang === 'kr' ? "인증 중 오류가 발생했습니다." : "เกิดข้อผิดพลาดในการยืนยันตัวตน")));
                        const successAlertMsg = lang === 'en' ? "Comment saved successfully." : (lang === 'cn' ? "评论保存成功。" : (lang === 'jp' ? "コメントが正常に保存されました。" : (lang === 'kr' ? "댓글이 성공적으로 저장되었습니다." : "บันทึกความคิดเห็นเรียบร้อยแล้ว")));
                        const failAlertMsg = lang === 'en' ? "An error occurred: " : (lang === 'cn' ? "发生错误：" : (lang === 'jp' ? "エラーが発生しました：" : (lang === 'kr' ? "오류가 발생했습니다: " : "เกิดข้อผิดพลาด: ")));

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
                                            if (result.status === 'success') {
                                                alert(successAlertMsg);
                                                document.getElementById("commentText").value = '';
                                            } else {
                                                alert(failAlertMsg + result.message);
                                            }
                                        });
                                } else {
                                    alert(roleAlertMsg);
                                }
                            })
                            .catch(err => {
                                console.error("Error verifying user:", err);
                                alert(errorAlertMsg);
                            });
                    });
                    function copyLink() {
                        const pageUrl = "<?= $pageUrl ?>";
                        const lang = "<?= $lang ?>";
                        const successAlertMsg = lang === 'en' ? "Link copied successfully!" : (lang === 'cn' ? "链接复制成功！" : (lang === 'jp' ? "リンクが正常にコピーされました！" : (lang === 'kr' ? "링크가 성공적으로 복사되었습니다!" : "คัดลอกลิงก์เรียบร้อยแล้ว")));
                        const errorAlertMsg = lang === 'en' ? "Failed to copy link. Please copy it manually." : (lang === 'cn' ? "复制链接失败。请手动复制。" : (lang === 'jp' ? "リンクのコピーに失敗しました。手動でコピーしてください。" : (lang === 'kr' ? "링크 복사에 실패했습니다. 수동으로 복사하세요." : "ไม่สามารถคัดลอกลิงก์ได้ กรุณาคัดลอกด้วยตนเอง")));

                        navigator.clipboard.writeText(pageUrl).then(function () {
                            alert(successAlertMsg);
                        }, function () {
                            alert(errorAlertMsg);
                        });
                    }


                </script>
            </div>
        </div>
    </div>

    <?php include 'template/footer.php' ?>


    <script src="index_.js?v=<?php echo time(); ?>"></script>
    <script src="app/js/idia/idia_.js?v=<?php echo time(); ?>"></script>
    <script src="/perfume/js/tracker.js" defer></script>
</body>

</html>