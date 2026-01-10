<?php
require_once('lib/connect.php');
global $conn;

$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'cn', 'jp', 'kr']) ? $_GET['lang'] : 'th';

$subjectTitle = ($lang === 'cn' ? '产品' : ($lang === 'en' ? 'Product' : ($lang === 'jp' ? '製品' : ($lang === 'kr' ? '제품' : 'สินค้า'))));
$pageUrl = "";

if (isset($_GET['id'])) {
    $encodedId = $_GET['id'];
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

    $pageUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    $decodedId = base64_decode(urldecode($_GET['id']));

    if ($decodedId !== false) {
        $stmt = $conn->prepare("SELECT subject_news, subject_news_en, subject_news_cn, subject_news_jp, subject_news_kr FROM dn_news WHERE del = 0 AND news_id = ?");
        $stmt->bind_param('i', $decodedId);
        $stmt->execute();
        $resultTitle = $stmt->get_result();
        if ($resultTitle->num_rows > 0) {
            $row = $resultTitle->fetch_assoc();
            $subjectTitle = $row['subject_news'];
            if ($lang === 'en' && !empty($row['subject_news_en'])) {
                $subjectTitle = $row['subject_news_en'];
            } elseif ($lang === 'cn' && !empty($row['subject_news_cn'])) {
                $subjectTitle = $row['subject_news_cn'];
            } elseif ($lang === 'jp' && !empty($row['subject_news_jp'])) {
                $subjectTitle = $row['subject_news_jp'];
            } elseif ($lang === 'kr' && !empty($row['subject_news_kr'])) {
                $subjectTitle = $row['subject_news_kr'];
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subjectTitle); ?></title>
    
    <?php include 'template/header.php' ?>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.7;
        }

        /* Article Container */
        .article-container {
            max-width: 900px;
            margin: 60px auto;
            padding: 0 40px;
        }

        /* Article Header */
        .article-header {
            margin-bottom: 50px;
            padding-bottom: 40px;
            border-bottom: 1px solid #e5e5e5;
        }

        .article-title {
            font-size: 48px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 30px;
            color: #1a1a1a;
        }

        .article-meta {
            display: flex;
            align-items: center;
            gap: 30px;
            color: #666;
            font-size: 15px;
        }

        .article-date {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Share Section - Top */
        .share-section-top {
            position: sticky;
            top: 100px;
            float: right;
            margin-right: -140px;
            margin-top: 20px;
        }

        .share-buttons-vertical {
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: white;
            padding: 15px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .share-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            overflow: hidden;
        }

        .share-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .share-btn img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        .share-btn.copy {
            background: #1a1a1a;
            color: white;
        }

        .share-btn.facebook:hover {
            background: #1877f2;
        }

        .share-btn.twitter:hover {
            background: #000000;
        }

        .share-btn.line:hover {
            background: #00c300;
        }

        .share-btn.pinterest:hover {
            background: #e60023;
        }

        .share-btn.instagram:hover {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }

        .share-btn.tiktok:hover {
            background: #000000;
        }

        /* Article Content */
        .article-content {
            font-size: 18px;
            line-height: 1.8;
            color: #2d2d2d;
            margin-bottom: 60px;
        }

        .article-content p {
            margin-bottom: 1.5em;
        }

        .article-content h1,
        .article-content h2,
        .article-content h3,
        .article-content h4 {
            margin-top: 2em;
            margin-bottom: 1em;
            font-weight: 700;
            line-height: 1.3;
            color: #1a1a1a;
        }

        .article-content h2 {
            font-size: 32px;
        }

        .article-content h3 {
            font-size: 26px;
        }

        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 2em 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .article-content iframe {
            max-width: 100%;
            border-radius: 12px;
            margin: 2em 0;
        }

        .article-content ul,
        .article-content ol {
            margin: 1.5em 0;
            padding-left: 2em;
        }

        .article-content li {
            margin-bottom: 0.5em;
        }

        .article-content blockquote {
            border-left: 4px solid #ffa719;
            padding-left: 24px;
            margin: 2em 0;
            font-style: italic;
            color: #666;
        }

        .article-content a {
            color: #ffa719;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-bottom 0.3s ease;
        }

        .article-content a:hover {
            border-bottom: 1px solid #ffa719;
        }

        /* Share Section - Bottom */
        .share-section-bottom {
            background: #f8f8f8;
            padding: 40px;
            border-radius: 20px;
            margin: 60px 0;
        }

        .share-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        .share-buttons-horizontal {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        /* Contact Section */
        .contact-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 50px;
            border-radius: 20px;
            margin: 60px 0;
            color: white;
        }

        .contact-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .contact-list {
            list-style: none;
            padding: 0;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .contact-icon {
            font-size: 20px;
            width: 30px;
        }

        .contact-item a {
            color: #ffa719;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-item a:hover {
            color: #ff9500;
        }

        /* Comments Section */
        .comments-section {
            margin-top: 80px;
            padding-top: 60px;
            border-top: 2px solid #e5e5e5;
        }

        .comments-header {
            margin-bottom: 40px;
        }

        .comments-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .comments-subtitle {
            color: #666;
            font-size: 15px;
        }

        .comment-form {
            margin-bottom: 40px;
        }

        .comment-textarea {
            width: 100%;
            padding: 20px;
            border: 2px solid #e5e5e5;
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
            min-height: 150px;
            transition: border-color 0.3s ease;
        }

        .comment-textarea:focus {
            outline: none;
            border-color: #ffa719;
        }

        .comment-submit {
            background: #ffa719;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .comment-submit:hover {
            background: #ff9500;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255, 167, 25, 0.3);
        }

        /* Success Message */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }

        /* Copy Link Modal */
        .copy-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeInOverlay 0.3s ease;
        }

        .copy-modal-overlay.active {
            display: flex;
        }

        @keyframes fadeInOverlay {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .copy-modal {
            background: white;
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .copy-modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .copy-modal-icon {
            width: 80px;
            height: 80px;
            background: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1) 0.2s backwards;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                rotate: -180deg;
            }
            to {
                transform: scale(1);
                rotate: 0deg;
            }
        }

        .copy-modal-icon svg {
            width: 40px;
            height: 40px;
        }

        .copy-modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .copy-modal-subtitle {
            font-size: 15px;
            color: #666;
        }

        .copy-modal-body {
            margin-bottom: 30px;
        }

        .copy-link-input-wrapper {
            position: relative;
            background: #f5f5f5;
            border: 2px solid #e5e5e5;
            border-radius: 12px;
            padding: 15px 50px 15px 15px;
            transition: all 0.3s ease;
        }

        .copy-link-input-wrapper:hover {
            border-color: #ffa719;
            background: #fff;
        }

        .copy-link-input {
            width: 100%;
            background: transparent;
            border: none;
            outline: none;
            font-size: 14px;
            color: #1a1a1a;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .copy-link-btn-mini {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: #1a1a1a;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .copy-link-btn-mini:hover {
            background: #ffa719;
            transform: translateY(-50%) scale(1.05);
        }

        .copy-link-btn-mini.copied {
            background: #28a745;
        }

        .copy-modal-footer {
            display: flex;
            gap: 15px;
        }

        .modal-btn {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn-secondary {
            background: #f5f5f5;
            color: #1a1a1a;
        }

        .modal-btn-secondary:hover {
            background: #e5e5e5;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .share-section-top {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .article-container {
                padding: 0 20px;
                margin: 40px auto;
            }

            .article-title {
                font-size: 32px;
            }

            .article-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .article-content {
                font-size: 16px;
            }

            .article-content h2 {
                font-size: 26px;
            }

            .article-content h3 {
                font-size: 22px;
            }

            .share-section-bottom {
                padding: 30px 20px;
            }

            .contact-section {
                padding: 30px 20px;
            }

            .comments-title {
                font-size: 26px;
            }

            .copy-modal {
                padding: 30px 20px;
            }

            .modal-btn {
                padding: 12px 20px;
                font-size: 15px;
            }
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .article-content {
            animation: fadeIn 0.6s ease forwards;
        }
    </style>
</head>

<body>
    <?php include 'template/banner_slide.php' ?>

    <!-- Copy Link Modal -->
    <div class="copy-modal-overlay" id="copyModal">
        <div class="copy-modal">
            <div class="copy-modal-header">
                <div class="copy-modal-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 7H7C5.89543 7 5 7.89543 5 9V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V13M9 15L20 4M20 4H15M20 4V9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="copy-modal-title">
                    <?php echo ($lang === 'en' ? 'Share this article' : ($lang === 'cn' ? '分享这篇文章' : ($lang === 'jp' ? 'この記事をシェア' : ($lang === 'kr' ? '이 기사 공유' : 'แชร์บทความนี้')))); ?>
                </h3>
                <p class="copy-modal-subtitle">
                    <?php echo ($lang === 'en' ? 'Copy the link below to share' : ($lang === 'cn' ? '复制下面的链接进行分享' : ($lang === 'jp' ? '以下のリンクをコピーして共有してください' : ($lang === 'kr' ? '아래 링크를 복사하여 공유하세요' : 'คัดลอกลิงก์ด้านล่างเพื่อแชร์')))); ?>
                </p>
            </div>
            <div class="copy-modal-body">
                <div class="copy-link-input-wrapper">
                    <input type="text" class="copy-link-input" id="linkInput" value="<?= htmlspecialchars($pageUrl) ?>" readonly>
                    <button class="copy-link-btn-mini" onclick="copyFromModal()">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="6" y="6" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M12 6V4C12 2.89543 11.1046 2 10 2H4C2.89543 2 2 2.89543 2 4V10C2 11.1046 2.89543 12 4 12H6" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="copy-modal-footer">
                <button class="modal-btn modal-btn-secondary" onclick="closeModal()">
                    <?php echo ($lang === 'en' ? 'Close' : ($lang === 'cn' ? '关闭' : ($lang === 'jp' ? '閉じる' : ($lang === 'kr' ? '닫기' : 'ปิด')))); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="article-container">
        <!-- Sticky Share Buttons (Desktop) -->
        <div class="share-section-top">
            <div class="share-buttons-vertical">
                <button class="share-btn copy" onclick="openModal()" title="<?php echo ($lang === 'en' ? 'Copy Link' : ($lang === 'cn' ? '复制链接' : ($lang === 'jp' ? 'リンクをコピー' : ($lang === 'kr' ? '링크 복사' : 'คัดลอกลิงก์')))); ?>">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 13C11.6569 13 13 11.6569 13 10C13 8.34315 11.6569 7 10 7C8.34315 7 7 8.34315 7 10C7 11.6569 8.34315 13 10 13Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 3V7M10 13V17M17 10H13M7 10H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" target="_blank" class="share-btn facebook">
                    <img src="https://img.icons8.com/color/48/000000/facebook-new.png" alt="Facebook">
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($subjectTitle) ?>" target="_blank" class="share-btn twitter">
                    <img style="border-radius: 6px;" src="https://cdn.prod.website-files.com/5d66bdc65e51a0d114d15891/64cebdd90aef8ef8c749e848_X-EverythingApp-Logo-Twitter.jpg" alt="Twitter">
                </a>
                <a href="https://social-plugins.line.me/lineit/share?url=<?= urlencode($pageUrl) ?>" target="_blank" class="share-btn line">
                    <img src="https://img.icons8.com/color/48/000000/line-me.png" alt="Line">
                </a>
                <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode($pageUrl) ?>" target="_blank" class="share-btn pinterest">
                    <img src="https://img.icons8.com/color/48/000000/pinterest--v1.png" alt="Pinterest">
                </a>
            </div>
        </div>

        <!-- Article Header -->
        <div class="article-header">
            <h1 class="article-title"><?= htmlspecialchars($subjectTitle); ?></h1>
            <?php
            if (isset($_GET['id'])) {
                $decodedId = base64_decode(urldecode($_GET['id']));
                if ($decodedId !== false) {
                    $stmt = $conn->prepare("SELECT date_create FROM dn_news WHERE news_id = ?");
                    $stmt->bind_param('i', $decodedId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $date = date('F d, Y', strtotime($row['date_create']));
                        echo '<div class="article-meta">';
                        echo '<div class="article-date">';
                        echo '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">';
                        echo '<rect x="2" y="3" width="14" height="13" rx="2" stroke="#666" stroke-width="1.5"/>';
                        echo '<path d="M6 1V4M12 1V4M2 7H16" stroke="#666" stroke-width="1.5" stroke-linecap="round"/>';
                        echo '</svg>';
                        echo '<span>' . $date . '</span>';
                        echo '</div>';
                        echo '</div>';
                    }
                    $stmt->close();
                }
            }
            ?>
        </div>

        <!-- Article Content -->
        <div class="article-content">
            <?php
            if (isset($_GET['id'])) {
                $decodedId = base64_decode(urldecode($_GET['id']));

                if ($decodedId !== false) {
                    $stmt = $conn->prepare("SELECT 
                            dn.news_id, 
                            dn.subject_news,
                            dn.subject_news_en,
                            dn.subject_news_cn,
                            dn.subject_news_jp,
                            dn.subject_news_kr,
                            dn.content_news,
                            dn.content_news_en,
                            dn.content_news_cn,
                            dn.content_news_jp,
                            dn.content_news_kr,
                            dn.date_create, 
                            GROUP_CONCAT(dnc.file_name) AS file_name,
                            GROUP_CONCAT(dnc.api_path) AS pic_path
                            FROM dn_news dn
                            LEFT JOIN dn_news_doc dnc ON dn.news_id = dnc.news_id
                            WHERE dn.news_id = ?
                            GROUP BY dn.news_id");

                    $stmt->bind_param('i', $decodedId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $content = $row['content_news'];
                            if ($lang === 'en' && !empty($row['content_news_en'])) {
                                $content = $row['content_news_en'];
                            } elseif ($lang === 'cn' && !empty($row['content_news_cn'])) {
                                $content = $row['content_news_cn'];
                            } elseif ($lang === 'jp' && !empty($row['content_news_jp'])) {
                                $content = $row['content_news_jp'];
                            } elseif ($lang === 'kr' && !empty($row['content_news_kr'])) {
                                $content = $row['content_news_kr'];
                            }

                            $paths = explode(',', $row['pic_path']);
                            $files = explode(',', $row['file_name']);

                            foreach ($files as $index => $file) {
                                $pattern = '/<img[^>]+data-filename="' . preg_quote($file, '/') . '"[^>]*>/i';

                                if (preg_match($pattern, $content, $matches)) {
                                    $new_src = $paths[$index];
                                    $new_img_tag = preg_replace('/(<img[^>]+)(src="[^"]*")/i', '$1 src="' . $new_src . '"', $matches[0]);
                                    $content = str_replace($matches[0], $new_img_tag, $content);
                                }
                            }

                            echo mb_convert_encoding($content, 'UTF-8', 'auto');
                        }
                    } else {
                        echo '<p>' . ($lang === 'cn' ? '无可用信息。' : ($lang === 'en' ? 'No information available.' : ($lang === 'jp' ? '情報がありません。' : ($lang === 'kr' ? '사용 가능한 정보가 없습니다.' : 'ไม่มีข้อมูล')))) . '</p>';
                    }

                    $stmt->close();
                } else {
                    echo '<p>' . ($lang === 'cn' ? '无效 ID。' : ($lang === 'en' ? 'Invalid ID.' : ($lang === 'jp' ? '無効なIDです。' : ($lang === 'kr' ? '유효하지 않은 ID입니다.' : 'ID ไม่ถูกต้อง')))) . '</p>';
                }
            }
            ?>
        </div>

        
    </div>

    <?php include 'template/footer.php' ?>

    <script>
        // Modal Functions
        function openModal() {
            document.getElementById('copyModal').classList.add('active');
            document.getElementById('linkInput').select();
        }

        function closeModal() {
            document.getElementById('copyModal').classList.remove('active');
        }

        function copyFromModal() {
            const input = document.getElementById('linkInput');
            const button = document.querySelector('.copy-link-btn-mini');
            const lang = "<?= $lang ?>";
            
            input.select();
            input.setSelectionRange(0, 99999);
            
            navigator.clipboard.writeText(input.value).then(function() {
                button.classList.add('copied');
                button.innerHTML = '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 9L7 13L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="6" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M12 6V4C12 2.89543 11.1046 2 10 2H4C2.89543 2 2 2.89543 2 4V10C2 11.1046 2.89543 12 4 12H6" stroke="currentColor" stroke-width="1.5"/></svg>';
                }, 2000);
            });
        }

        // Close modal on overlay click
        document.getElementById('copyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Comment Form Handler
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
            const loginAlertMsg = lang === 'cn' ? "请登录后发表评论。" : (lang === 'en' ? "Please log in to post a comment." : (lang === 'jp' ? "コメントを投稿するにはログインしてください。" : (lang === 'kr' ? "댓글을 남기려면 로그인해주세요." : "กรุณาเข้าสู่ระบบก่อนแสดงความคิดเห็น")));
            const roleAlertMsg = lang === 'cn' ? "必须以查看者身份登录才能发表评论。" : (lang === 'en' ? "You must be logged in as a viewer to post a comment." : (lang === 'jp' ? "コメントを投稿するには、ビューアーとしてログインする必要があります。" : (lang === 'kr' ? "댓글을 남기려면 뷰어 계정으로 로그인해야 합니다." : "ต้องเข้าสู่ระบบในฐานะ viewer เท่านั้น")));
            const errorAlertMsg = lang === 'cn' ? "身份验证期间发生错误。" : (lang === 'en' ? "An error occurred during authentication." : (lang === 'jp' ? "認証中にエラーが発生しました。" : (lang === 'kr' ? "인증 중 오류가 발생했습니다." : "เกิดข้อผิดพลาดในการยืนยันตัวตน")));
            const successAlertMsg = lang === 'cn' ? "评论已成功保存。" : (lang === 'en' ? "Comment saved successfully!" : (lang === 'jp' ? "コメントが正常に保存されました。" : (lang === 'kr' ? "댓글이 성공적으로 저장되었습니다." : "บันทึกความคิดเห็นเรียบร้อยแล้ว")));
            const failAlertMsg = lang === 'cn' ? "发生错误：" : (lang === 'en' ? "An error occurred: " : (lang === 'jp' ? "エラーが発生しました：" : (lang === 'kr' ? "오류가 발생했습니다: " : "เกิดข้อผิดพลาด: ")));

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
                                    const successMsg = document.getElementById('successMessage');
                                    successMsg.textContent = successAlertMsg;
                                    successMsg.style.display = 'block';
                                    document.getElementById("commentText").value = '';
                                    setTimeout(() => {
                                        successMsg.style.display = 'none';
                                    }, 3000);
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
    </script>
</body>

</html>