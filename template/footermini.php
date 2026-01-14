<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

// Minimal Footer Translations
$user_footer_translations = [
    'copyright' => [
        'th' => '© 2025 Trandar Innovation.',
        'en' => '© 2025 Trandar Innovation.',
        'cn' => '© 2025 Trandar Innovation.',
        'jp' => '© 2025 Trandar Innovation.',
        'kr' => '© 2025 Trandar Innovation.'
    ],
    'rights' => [
        'th' => 'สงวนลิขสิทธิ์',
        'en' => 'All Rights Reserved',
        'cn' => '保留所有权利',
        'jp' => '全著作権所有',
        'kr' => '모든 권리 보유'
    ],
    'privacy' => [
        'th' => 'นโยบายความเป็นส่วนตัว',
        'en' => 'Privacy Policy',
        'cn' => '隐私政策',
        'jp' => 'プライバシーポリシー',
        'kr' => '개인정보 처리방침'
    ],
    'terms' => [
        'th' => 'ข้อกำหนดและเงื่อนไข',
        'en' => 'Terms & Conditions',
        'cn' => '条款和条件',
        'jp' => '利用規約',
        'kr' => '이용약관'
    ],
    'made_with' => [
        'th' => 'สร้างด้วยความใส่ใจ',
        'en' => 'Made with Care',
        'cn' => '用心制作',
        'jp' => '心を込めて',
        'kr' => '정성껏 제작'
    ]
];

function uft($key, $lang) {
    global $user_footer_translations;
    return $user_footer_translations[$key][$lang] ?? $user_footer_translations[$key]['en'];
}
?>

<style>
    /* ========================================
       USER FOOTER - MINIMAL & CLEAN
       ======================================== */
    
    .user-footer {
        background: #fafafa;
        border-top: 1px solid #e5e5e5;
        padding: 40px 0;
        margin-top: 100px;
    }

    .user-footer-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 40px;
    }

    /* Footer Content Layout */
    .user-footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 30px;
    }

    /* Brand Section */
    .user-footer-brand {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .user-footer-logo {
        font-family: 'Playfair Display', serif;
        font-size: 24px;
        font-weight: 600;
        letter-spacing: 0.1em;
        color: #1a1a1a;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .user-footer-logo:hover {
        color: #ffa719;
    }

    .user-footer-divider {
        width: 1px;
        height: 30px;
        background: #e5e5e5;
    }

    .user-footer-copyright {
        font-size: 13px;
        color: #999;
        font-weight: 400;
    }

    /* Links Section */
    .user-footer-links {
        display: flex;
        gap: 30px;
        align-items: center;
    }

    .user-footer-link {
        font-size: 13px;
        color: #666;
        text-decoration: none;
        transition: color 0.3s ease;
        font-weight: 400;
    }

    .user-footer-link:hover {
        color: #1a1a1a;
    }

    /* Made With Section */
    .user-footer-made {
        font-size: 13px;
        color: #999;
        font-weight: 400;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-footer-heart {
        color: #ffa719;
        font-size: 14px;
        display: inline-block;
        animation: heartbeat 1.5s infinite;
    }

    @keyframes heartbeat {
        0%, 100% { transform: scale(1); }
        10%, 30% { transform: scale(1.15); }
        20%, 40% { transform: scale(1); }
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .user-footer-content {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .user-footer-brand {
            flex-direction: column;
        }

        .user-footer-divider {
            width: 50px;
            height: 1px;
        }

        .user-footer-links {
            flex-wrap: wrap;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .user-footer {
            padding: 30px 0;
            margin-top: 60px;
        }

        .user-footer-container {
            padding: 0 20px;
        }

        .user-footer-logo {
            font-size: 20px;
        }

        .user-footer-links {
            gap: 20px;
            flex-direction: column;
        }

        .user-footer-copyright,
        .user-footer-link,
        .user-footer-made {
            font-size: 12px;
        }
    }

    /* Alternative Style - Even More Minimal */
    .user-footer.minimal-style {
        background: #ffffff;
        border-top: 1px solid #f0f0f0;
        padding: 30px 0;
    }

    .user-footer.minimal-style .user-footer-content {
        justify-content: center;
        text-align: center;
    }

    .user-footer.minimal-style .user-footer-brand {
        flex-direction: row;
        gap: 15px;
    }

    /* Dark Mode Alternative */
    .user-footer.dark-mode {
        background: #1a1a1a;
        border-top: 1px solid #333;
    }

    .user-footer.dark-mode .user-footer-logo {
        color: #ffffff;
    }

    .user-footer.dark-mode .user-footer-logo:hover {
        color: #ffa719;
    }

    .user-footer.dark-mode .user-footer-copyright,
    .user-footer.dark-mode .user-footer-made {
        color: #666;
    }

    .user-footer.dark-mode .user-footer-link {
        color: #999;
    }

    .user-footer.dark-mode .user-footer-link:hover {
        color: #ffffff;
    }

    .user-footer.dark-mode .user-footer-divider {
        background: #333;
    }
</style>

<!-- USER FOOTER - MINIMAL VERSION -->
<footer class="user-footer">
    <div class="user-footer-container">
        <div class="user-footer-content">
            <!-- Brand & Copyright -->
            <div class="user-footer-brand">
                <a href="?index&lang=<?= $lang ?>" class="user-footer-logo">
                    PERFUME
                </a>
                <div class="user-footer-divider"></div>
                <p class="user-footer-copyright">
                    <?= uft('copyright', $lang) ?> · <?= uft('rights', $lang) ?>
                </p>
            </div>

            <!-- Quick Links -->
            <div class="user-footer-links">
                <a href="?privacy&lang=<?= $lang ?>" class="user-footer-link"><?= uft('privacy', $lang) ?></a>
                <a href="?termofuse&lang=<?= $lang ?>" class="user-footer-link"><?= uft('terms', $lang) ?></a>
            </div>

            <!-- Made With -->
            <div class="user-footer-made">
                <span><?= uft('made_with', $lang) ?></span>
                <span class="user-footer-heart">♥</span>
            </div>
        </div>
    </div>
</footer>

<!-- Google Fonts (ถ้ายังไม่มี) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">