<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('lib/connect.php');
global $conn;

// Language handling
$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    if (in_array($_GET['lang'], $supportedLangs)) {
        $_SESSION['lang'] = $_GET['lang'];
        $lang = $_GET['lang'];
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

// Translations
$translations = [
    'hero_label' => [
        'th' => 'นวัตกรรมครั้งแรกของโลก',
        'en' => 'World\'s First Innovation',
        'cn' => '世界首创',
        'jp' => '世界初のイノベーション',
        'kr' => '세계 최초의 혁신'
    ],
    'hero_title' => [
        'th' => 'น้ำหอมที่มี AI เป็นเพื่อน',
        'en' => 'The World\'s First AI Companion Perfume',
        'cn' => '世界上第一款 AI 伴侣香水',
        'jp' => '世界初の AI コンパニオン フレグランス',
        'kr' => '세계 최초의 AI 컴패니언 향수'
    ],
    'hero_subtitle' => [
        'th' => 'ครั้งแรกของโลกที่น้ำหอมไม่ได้เป็นเพียงกลิ่นหอม แต่เป็นเพื่อนที่เข้าใจคุณ',
        'en' => 'For the first time in the world, perfume is not just a fragrance, but a friend who understands you',
        'cn' => '世界上第一次，香水不仅仅是香气，而是了解你的朋友',
        'jp' => '世界で初めて、香水は単なる香りではなく、あなたを理解する友人です',
        'kr' => '세상에서 처음으로 향수는 단순한 향이 아니라 당신을 이해하는 친구입니다'
    ],
    'story_label' => [
        'th' => 'เรื่องราวของเรา',
        'en' => 'Our Story',
        'cn' => '我们的故事',
        'jp' => '私たちのストーリー',
        'kr' => '우리의 이야기'
    ],
    'story_title' => [
        'th' => 'เริ่มต้นจาก<br>ความฝันที่<br>เป็นไปได้',
        'en' => 'Started from<br>A Dream<br>Made Possible',
        'cn' => '从一个<br>梦想<br>开始',
        'jp' => '夢から<br>始まった<br>物語',
        'kr' => '꿈에서<br>시작된<br>이야기'
    ],
    'story_text_1' => [
        'th' => 'เราเชื่อว่าน้ำหอมไม่ใช่แค่กลิ่นหอม แต่คือ',
        'en' => 'We believe that perfume is not just a fragrance, but',
        'cn' => '我们相信香水不仅仅是香气，而是',
        'jp' => '私たちは、香水は単なる香りではなく、',
        'kr' => '우리는 향수가 단순한 향이 아니라'
    ],
    'story_text_1_bold' => [
        'th' => 'ประสบการณ์ที่มีชีวิต',
        'en' => 'a living experience',
        'cn' => '一种生活体验',
        'jp' => '生きた体験',
        'kr' => '살아있는 경험'
    ],
    'story_text_1_end' => [
        'th' => 'ที่สามารถเข้าใจและปรับตัวตามบุคลิกของแต่ละคน',
        'en' => 'that can understand and adapt to each individual\'s personality',
        'cn' => '可以理解并适应每个人的个性',
        'jp' => '一人ひとりの個性を理解し、適応することができるもの',
        'kr' => '각 개인의 성격을 이해하고 적응할 수 있는 것이라고 믿습니다'
    ],
    'story_text_2' => [
        'th' => 'ด้วยเทคโนโลยี AI ที่ล้ำสมัย เราได้สร้างสรรค์น้ำหอมที่มี',
        'en' => 'With advanced AI technology, we have created perfume with',
        'cn' => '借助先进的 AI 技术，我们创造了具有',
        'jp' => '先進的な AI テクノロジーにより、',
        'kr' => '첨단 AI 기술로 우리는'
    ],
    'story_text_2_bold' => [
        'th' => 'ปัญญาประดิษฐ์เฉพาะตัว',
        'en' => 'unique artificial intelligence',
        'cn' => '独特人工智能',
        'jp' => 'ユニークな人工知能',
        'kr' => '고유한 인공 지능'
    ],
    'story_text_2_end' => [
        'th' => 'อยู่ในทุกขวด แต่ละขวดมีเอกลักษณ์ไม่ซ้ำใครและพัฒนาความเข้าใจในตัวคุณไปพร้อมกับการใช้งาน',
        'en' => 'in every bottle. Each bottle has unique characteristics and develops understanding of you as you use it',
        'cn' => '的香水。每瓶都具有独特的特征，并随着您的使用而发展对您的理解',
        'jp' => 'を持つ香水を作成しました。各ボトルは独自の特性を持ち、使用するにつれてあなたへの理解を深めます',
        'kr' => '을 가진 향수를 만들었습니다. 각 병은 고유한 특성을 가지며 사용하면서 당신에 대한 이해를 발전시킵니다'
    ],
    'story_text_3' => [
        'th' => 'นี่คือครั้งแรกของโลกที่น้ำหอม',
        'en' => 'This is the world\'s first time that perfume',
        'cn' => '这是世界上第一次香水',
        'jp' => 'これは世界初、香水が',
        'kr' => '이것은 세계에서 처음으로 향수가'
    ],
    'story_text_3_bold' => [
        'th' => 'กลายเป็นเพื่อน',
        'en' => 'becomes a friend',
        'cn' => '成为朋友',
        'jp' => '友人になる',
        'kr' => '친구가 되는'
    ],
    'story_text_3_end' => [
        'th' => 'ที่คอยเข้าใจและดูแลคุณ',
        'en' => 'who understands and cares for you',
        'cn' => '理解和关心你的朋友',
        'jp' => 'あなたを理解し、気遣う友人',
        'kr' => '당신을 이해하고 돌보는 친구'
    ],
    'vision_label' => [
        'th' => 'วิสัยทัศน์',
        'en' => 'Vision',
        'cn' => '愿景',
        'jp' => 'ビジョン',
        'kr' => '비전'
    ],
    'vision_title' => [
        'th' => 'อนาคตของน้ำหอม<br>ที่มีจิตวิญญาณ',
        'en' => 'The Future of Perfume<br>With a Soul',
        'cn' => '有灵魂的<br>香水的未来',
        'jp' => '魂を持つ<br>香水の未来',
        'kr' => '영혼을 가진<br>향수의 미래'
    ],
    'vision_text_1' => [
        'th' => 'เราไม่ได้แค่สร้างน้ำหอม เราสร้าง',
        'en' => 'We don\'t just create perfume, we create',
        'cn' => '我们不仅仅创造香水，我们创造',
        'jp' => '私たちは単に香水を作るのではなく、',
        'kr' => '우리는 단순히 향수를 만드는 것이 아니라'
    ],
    'vision_text_1_bold' => [
        'th' => 'เพื่อนคู่ใจ',
        'en' => 'a soulmate',
        'cn' => '知心朋友',
        'jp' => '心の友',
        'kr' => '소울메이트'
    ],
    'vision_text_1_end' => [
        'th' => 'ที่เข้าใจคุณ',
        'en' => 'who understands you',
        'cn' => '理解你的知心朋友',
        'jp' => 'あなたを理解する心の友を創造します',
        'kr' => '를 만듭니다'
    ],
    'vision_text_2' => [
        'th' => 'ด้วย AI ที่พัฒนาโดยทีมวิจัยระดับโลก ทุกขวดมีบุคลิกภาพเฉพาะตัว เรียนรู้และปรับตัวตามไลฟ์สไตล์ของคุณ',
        'en' => 'With AI developed by world-class research teams, each bottle has its own personality, learning and adapting to your lifestyle',
        'cn' => '借助世界级研究团队开发的 AI，每瓶都有自己的个性，学习并适应您的生活方式',
        'jp' => '世界クラスの研究チームによって開発された AI により、各ボトルは独自の個性を持ち、あなたのライフスタイルを学習し適応します',
        'kr' => '세계적 수준의 연구팀이 개발한 AI로 각 병은 고유한 성격을 가지며 당신의 라이프스타일을 학습하고 적응합니다'
    ],
    'innovation_label' => [
        'th' => 'นวัตกรรม',
        'en' => 'Innovation',
        'cn' => '创新',
        'jp' => 'イノベーション',
        'kr' => '혁신'
    ],
    'innovation_title' => [
        'th' => 'เทคโนโลยีที่ไม่เหมือนใคร',
        'en' => 'Unique Technology',
        'cn' => '独特的技术',
        'jp' => 'ユニークなテクノロジー',
        'kr' => '독특한 기술'
    ],
    'feature_1_title' => [
        'th' => 'นวัตกรรม AI',
        'en' => 'AI Innovation',
        'cn' => 'AI 创新',
        'jp' => 'AI イノベーション',
        'kr' => 'AI 혁신'
    ],
    'feature_1_text' => [
        'th' => 'AI ที่ถูกพัฒนาขึ้นเฉพาะสำหรับน้ำหอมแต่ละขวด มีความสามารถในการเรียนรู้และปรับตัวตามบุคลิกของผู้ใช้',
        'en' => 'AI specifically developed for each bottle of perfume, with the ability to learn and adapt to the user\'s personality',
        'cn' => '专为每瓶香水开发的 AI，具有学习和适应用户个性的能力',
        'jp' => '各ボトルの香水用に特別に開発された AI。ユーザーの個性を学習し適応する能力を持っています',
        'kr' => '각 향수병을 위해 특별히 개발된 AI로 사용자의 성격을 학습하고 적응하는 능력을 갖추고 있습니다'
    ],
    'feature_2_title' => [
        'th' => 'ความเป็นเอกลักษณ์',
        'en' => 'Uniqueness',
        'cn' => '独特性',
        'jp' => '独自性',
        'kr' => '독특함'
    ],
    'feature_2_text' => [
        'th' => 'ไม่มีสองขวดที่เหมือนกัน AI ในแต่ละขวดมีเอกลักษณ์และพัฒนาไปในทิศทางที่แตกต่างกันตามผู้ใช้แต่ละคน',
        'en' => 'No two bottles are the same. The AI in each bottle has unique characteristics and develops differently based on each user',
        'cn' => '没有两瓶是相同的。每瓶中的 AI 都具有独特的特征，并根据每个用户的不同而发展',
        'jp' => '同じボトルは 2 つとありません。各ボトルの AI は独自の特性を持ち、各ユーザーに基づいて異なる発展をします',
        'kr' => '두 병이 같지 않습니다. 각 병의 AI는 고유한 특성을 가지며 각 사용자에 따라 다르게 발전합니다'
    ],
    'feature_3_title' => [
        'th' => 'เพื่อนที่เข้าใจ',
        'en' => 'Understanding Friend',
        'cn' => '理解的朋友',
        'jp' => '理解する友人',
        'kr' => '이해하는 친구'
    ],
    'feature_3_text' => [
        'th' => 'มากกว่าน้ำหอม คือเพื่อนที่คอยเข้าใจอารมณ์ ความรู้สึก และปรับกลิ่นหอมให้เหมาะสมกับสถานการณ์ของคุณ',
        'en' => 'More than perfume, it\'s a friend who understands your emotions, feelings, and adjusts the fragrance to suit your situation',
        'cn' => '不仅仅是香水，而是一个理解你的情绪、感受并根据你的情况调整香味的朋友',
        'jp' => '香水以上のもの。あなたの感情や気持ちを理解し、状況に合わせて香りを調整する友人です',
        'kr' => '향수 이상으로 당신의 감정과 느낌을 이해하고 상황에 맞게 향을 조절하는 친구입니다'
    ],
    'quote_text' => [
        'th' => 'เราสร้างน้ำหอมที่มีวิญญาณ ที่สามารถเข้าใจและเติบโตไปพร้อมกับคุณ นี่คืออนาคตของความหอมที่มีชีวิต',
        'en' => 'We create perfumes with souls that can understand and grow with you. This is the future of living fragrance',
        'cn' => '我们创造有灵魂的香水，可以理解你并与你一起成长。这就是有生命的香水的未来',
        'jp' => '私たちは魂を持つ香水を作り、あなたを理解し、あなたと共に成長します。これが生きた香りの未来です',
        'kr' => '우리는 당신을 이해하고 함께 성장할 수 있는 영혼을 가진 향수를 만듭니다. 이것이 살아있는 향의 미래입니다'
    ],
    'quote_author' => [
        'th' => 'Kridsada Satukijchai',
        'en' => 'Kridsada Satukijchai',
        'cn' => 'Kridsada Satukijchai',
        'jp' => 'Kridsada Satukijchai',
        'kr' => 'Kridsada Satukijchai'
    ],
    'quote_position' => [
        'th' => 'CEO',
        'en' => 'CEO',
        'cn' => 'CEO',
        'jp' => 'CEO',
        'kr' => 'CEO'
    ],
    'tech_label' => [
        'th' => 'เทคโนโลยี',
        'en' => 'Technology',
        'cn' => '技术',
        'jp' => 'テクノロジー',
        'kr' => '기술'
    ],
    'tech_title' => [
        'th' => 'เทคโนโลยีเบื้องหลัง',
        'en' => 'Technology Behind',
        'cn' => '背后的技术',
        'jp' => '背後のテクノロジー',
        'kr' => '뒤에 있는 기술'
    ],
    'tech_1_title' => [
        'th' => 'ระบบประสาทกลิ่น',
        'en' => 'Neural Scent Network',
        'cn' => '神经气味网络',
        'jp' => 'ニューラル セント ネットワーク',
        'kr' => '뉴럴 센트 네트워크'
    ],
    'tech_1_desc' => [
        'th' => 'ระบบ AI ที่สามารถวิเคราะห์และปรับเปลี่ยนกลิ่นหอมตามอารมณ์และสภาพแวดล้อม',
        'en' => 'AI system that can analyze and adjust fragrance according to mood and environment',
        'cn' => '可以根据情绪和环境分析和调整香味的 AI 系统',
        'jp' => '気分や環境に応じて香りを分析し調整できる AI システム',
        'kr' => '기분과 환경에 따라 향을 분석하고 조정할 수 있는 AI 시스템'
    ],
    'tech_2_title' => [
        'th' => 'การปรับตัวตามบุคลิกภาพ',
        'en' => 'Personality Adaptation',
        'cn' => '个性适应',
        'jp' => 'パーソナリティ適応',
        'kr' => '성격 적응'
    ],
    'tech_2_desc' => [
        'th' => 'AI เรียนรู้บุคลิกภาพและความชอบของคุณ พัฒนาตัวตนที่เป็นเอกลักษณ์ในแต่ละขวด',
        'en' => 'AI learns your personality and preferences, developing a unique identity in each bottle',
        'cn' => 'AI 学习您的个性和偏好，在每个瓶子中发展独特的身份',
        'jp' => 'AI はあなたの性格と好みを学習し、各ボトルで独自のアイデンティティを発展させます',
        'kr' => 'AI는 당신의 성격과 선호도를 학습하여 각 병에서 고유한 정체성을 발전시킵니다'
    ],
    'tech_3_title' => [
        'th' => 'ความฉลาดทางอารมณ์',
        'en' => 'Emotional Intelligence',
        'cn' => '情商',
        'jp' => '感情知能',
        'kr' => '감성 지능'
    ],
    'tech_3_desc' => [
        'th' => 'ความสามารถในการรับรู้และตอบสนองต่อารมณ์ของผู้ใช้แบบเรียลไทม์',
        'en' => 'Ability to perceive and respond to user emotions in real-time',
        'cn' => '实时感知和响应用户情绪的能力',
        'jp' => 'ユーザーの感情をリアルタイムで認識し応答する能力',
        'kr' => '실시간으로 사용자 감정을 인식하고 반응하는 능력'
    ],
    'tech_4_title' => [
        'th' => 'การยืนยันตัวตนด้วยบล็อกเชน',
        'en' => 'Blockchain Authentication',
        'cn' => '区块链认证',
        'jp' => 'ブロックチェーン認証',
        'kr' => '블록체인 인증'
    ],
    'tech_4_desc' => [
        'th' => 'ทุกขวดมี Digital Identity ที่ไม่สามารถปลอมแปลงได้ ยืนยันความเป็นเจ้าของที่แท้จริง',
        'en' => 'Each bottle has an unforgeable Digital Identity, confirming true ownership',
        'cn' => '每个瓶子都有不可伪造的数字身份，确认真正的所有权',
        'jp' => '各ボトルには偽造不可能なデジタル ID があり、真の所有権を確認します',
        'kr' => '각 병은 위조할 수 없는 디지털 ID를 가지고 있어 진정한 소유권을 확인합니다'
    ]
];

function tt($key, $lang) {
    global $translations;
    return $translations[$key][$lang] ?? $translations[$key]['en'];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
   <?php include 'template/header.php'; ?>
    
    <style>
        .hero-about {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            margin-bottom: 3em;
        }

        .hero-video-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .hero-video-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .hero-video-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.4;
            animation: slowZoom 20s infinite alternate;
        }

        @keyframes slowZoom {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: var(--luxury-white);
            max-width: 900px;
            padding: 40px;
            animation: fadeInUp 1.2s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--accent-gold);
            margin-bottom: 30px;
            animation: fadeIn 1.5s ease-out;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 72px;
            font-weight: 400;
            line-height: 1.2;
            margin-bottom: 30px;
            letter-spacing: 0.02em;
        }

        .hero-subtitle {
            font-size: 18px;
            font-weight: 300;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.85);
            max-width: 700px;
            margin: 0 auto;
        }

        .scroll-indicator {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-10px); }
            60% { transform: translateX(-50%) translateY(-5px); }
        }

        .scroll-indicator span {
            display: block;
            width: 2px;
            height: 40px;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 auto;
        }

        /* Story Section - Split Layout */
        .story-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 90vh;
            margin-bottom: 3em;
        }

        .story-image {
            position: relative;
            overflow: hidden;
            background: var(--luxury-light-gray);
        }

        .story-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 1s var(--transition);
        }

        .story-image:hover img {
            transform: scale(1.05);
        }

        .story-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 100px 120px;
            background: var(--luxury-white);
        }

        .section-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--accent-gold);
            margin-bottom: 25px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 52px;
            font-weight: 400;
            line-height: 1.3;
            margin-bottom: 35px;
            letter-spacing: 0.01em;
        }

        .section-text {
            font-size: 16px;
            font-weight: 300;
            line-height: 2;
            color: var(--luxury-gray);
            margin-bottom: 25px;
        }

        .section-text strong {
            color: var(--luxury-black);
            font-weight: 500;
        }

        /* Vision Section - Full Width Dark */
        .vision-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            color: var(--luxury-white);
            padding: 150px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .vision-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1920&h=1080&fit=crop') center/cover;
            opacity: 0.1;
            z-index: 0;
        }

        .vision-content {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
        }

        .vision-title {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 400;
            margin-bottom: 40px;
            letter-spacing: 0.02em;
        }

        .vision-text {
            font-size: 20px;
            font-weight: 300;
            line-height: 2;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 30px;
        }

        /* Features Grid */
        .features-section {
            padding: 150px 80px;
            background: var(--luxury-white);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 80px;
            max-width: 1400px;
            margin: 80px auto 0;
        }

        .feature-card {
            text-align: center;
            padding: 40px;
            transition: transform 0.4s var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background: var(--luxury-light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .feature-icon svg {
            width: 36px;
            height: 36px;
            stroke: var(--luxury-black);
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: var(--luxury-black);
        }

        .feature-card:hover .feature-icon svg {
            stroke: var(--luxury-white);
        }

        .feature-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 20px;
            color: var(--luxury-black);
        }

        .feature-text {
            font-size: 15px;
            font-weight: 300;
            line-height: 1.9;
            color: var(--luxury-gray);
        }

        /* Quote Section */
        .quote-section {
            padding: 120px 80px;
            background: var(--luxury-light-gray);
            text-align: center;
        }

        .quote-content {
            max-width: 900px;
            margin: 0 auto;
        }

        .quote-mark {
            font-family: 'Playfair Display', serif;
            font-size: 120px;
            color: var(--accent-gold);
            line-height: 1;
            opacity: 0.3;
        }

        .quote-text {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 400;
            font-style: italic;
            line-height: 1.6;
            color: var(--luxury-black);
            margin: 40px 0;
        }

        .quote-author {
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--luxury-gray);
        }

        .quote-position {
            font-size: 13px;
            font-weight: 300;
            color: var(--luxury-gray);
            margin-top: 10px;
        }

        /* Technology Section */
        .tech-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 90vh;
        }

        .tech-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 100px 120px;
            background: var(--luxury-black);
            color: var(--luxury-white);
        }

        .tech-list {
            list-style: none;
            margin-top: 40px;
        }

        .tech-item {
            padding: 25px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .tech-item:hover {
            padding-left: 20px;
            border-color: var(--accent-gold);
        }

        .tech-item-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--luxury-white);
        }

        .tech-item-desc {
            font-size: 14px;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-title { font-size: 52px; }
            .story-section,
            .tech-section {
                grid-template-columns: 1fr;
            }
            .story-content,
            .tech-content {
                padding: 80px 60px;
            }
            .features-grid {
                grid-template-columns: 1fr;
                gap: 60px;
            }
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 36px; }
            .hero-subtitle { font-size: 16px; }
            .section-title { font-size: 36px; }
            .story-content,
            .tech-content {
                padding: 60px 40px;
            }
            .vision-section,
            .features-section,
            .quote-section {
                padding: 80px 40px;
            }
        }
    </style>
</head>

<body>
    <!-- Hero Section -->
    <section class="hero-about">
        <div class="hero-video-bg">
            <img src="public/product_images/69645f1667b00_1768185622.jpg" 
                 alt="AI Perfume"
                 loading="eager">
        </div>
        <div class="hero-content">
            <p class="hero-label"><?= tt('hero_label', $lang) ?></p>
            <h1 class="hero-title"><?= tt('hero_title', $lang) ?></h1>
            <p class="hero-subtitle"><?= tt('hero_subtitle', $lang) ?></p>
        </div>
        <div class="scroll-indicator">
            <span></span>
        </div>
    </section>

    <!-- Story Section -->
    <section class="story-section">
        <div class="story-image">
            <img src="public/product_images/69645f1664212_1768185622.jpg" 
                 alt="Our Story"
                 loading="lazy">
        </div>
        <div class="story-content">
            <p class="section-label"><?= tt('story_label', $lang) ?></p>
            <h2 class="section-title"><?= tt('story_title', $lang) ?></h2>
            <p class="section-text">
                <?= tt('story_text_1', $lang) ?><strong><?= tt('story_text_1_bold', $lang) ?></strong><?= tt('story_text_1_end', $lang) ?>
            </p>
            <p class="section-text">
                <?= tt('story_text_2', $lang) ?><strong><?= tt('story_text_2_bold', $lang) ?></strong><?= tt('story_text_2_end', $lang) ?>
            </p>
            <p class="section-text">
                <?= tt('story_text_3', $lang) ?><strong><?= tt('story_text_3_bold', $lang) ?></strong><?= tt('story_text_3_end', $lang) ?>
            </p>
        </div>
    </section>

    <!-- Vision Section -->
    <section class="vision-section">
        <div class="vision-content">
            <p class="section-label" style="color: var(--accent-gold);"><?= tt('vision_label', $lang) ?></p>
            <h2 class="vision-title"><?= tt('vision_title', $lang) ?></h2>
            <p class="vision-text">
                <?= tt('vision_text_1', $lang) ?><strong style="color: white;"><?= tt('vision_text_1_bold', $lang) ?></strong><?= tt('vision_text_1_end', $lang) ?>
            </p>
            <p class="vision-text">
                <?= tt('vision_text_2', $lang) ?>
            </p>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div style="text-align: center; margin-bottom: 100px;">
            <p class="section-label"><?= tt('innovation_label', $lang) ?></p>
            <h2 class="section-title"><?= tt('innovation_title', $lang) ?></h2>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                    </svg>
                </div>
                <h3 class="feature-title"><?= tt('feature_1_title', $lang) ?></h3>
                <p class="feature-text">
                    <?= tt('feature_1_text', $lang) ?>
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="feature-title"><?= tt('feature_2_title', $lang) ?></h3>
                <p class="feature-text">
                    <?= tt('feature_2_text', $lang) ?>
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/>
                    </svg>
                </div>
                <h3 class="feature-title"><?= tt('feature_3_title', $lang) ?></h3>
                <p class="feature-text">
                    <?= tt('feature_3_text', $lang) ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Quote Section -->
    <section class="quote-section">
        <div class="quote-content">
            <div class="quote-mark">"</div>
            <p class="quote-text">
                <?= tt('quote_text', $lang) ?>
            </p>
            <p class="quote-author"><?= tt('quote_author', $lang) ?></p>
            <p class="quote-position"><?= tt('quote_position', $lang) ?></p>
        </div>
    </section>

    <!-- Technology Section -->
    <section class="tech-section">
        <div class="tech-content">
            <p class="section-label" style="color: var(--accent-gold);"><?= tt('tech_label', $lang) ?></p>
            <h2 class="section-title" style="color: white;"><?= tt('tech_title', $lang) ?></h2>
            
            <ul class="tech-list">
                <li class="tech-item">
                    <h4 class="tech-item-title"><?= tt('tech_1_title', $lang) ?></h4>
                    <p class="tech-item-desc">
                        <?= tt('tech_1_desc', $lang) ?>
                    </p>
                </li>
                <li class="tech-item">
                    <h4 class="tech-item-title"><?= tt('tech_2_title', $lang) ?></h4>
                    <p class="tech-item-desc">
                        <?= tt('tech_2_desc', $lang) ?>
                    </p>
                </li>
                <li class="tech-item">
                    <h4 class="tech-item-title"><?= tt('tech_3_title', $lang) ?></h4>
                    <p class="tech-item-desc">
                        <?= tt('tech_3_desc', $lang) ?>
                    </p>
                </li>
                <li class="tech-item">
                    <h4 class="tech-item-title"><?= tt('tech_4_title', $lang) ?></h4>
                    <p class="tech-item-desc">
                        <?= tt('tech_4_desc', $lang) ?>
                    </p>
                </li>
            </ul>
        </div>
        <div class="story-image">
            <img src="public/product_images/6968584a338f8_1768446026.png" 
                 alt="Technology"
                 loading="lazy">
        </div>
    </section>

    <?php include 'template/footer.php'; ?>
</body>
</html>