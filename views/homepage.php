<?php
require_once('lib/connect.php');
global $conn;

// Start session for language handling
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Translation arrays
$translations = [
    'latest' => [
        'th' => 'ข่าวล่าสุด',
        'en' => 'Latest',
        'cn' => '最新消息',
        'jp' => '最新情報',
        'kr' => '최신 소식'
    ],
    'news_1' => [
        'th' => 'เปิดตัวคอลเลคชั่นน้ำหอม AI Companion 2024',
        'en' => 'New AI Companion Perfume Collection Launch 2024',
        'cn' => '2024 年 AI 伴侣香水系列新品发布',
        'jp' => 'AI コンパニオン フレグランス コレクション 2024 発売',
        'kr' => 'AI 컴패니언 향수 컬렉션 2024 출시'
    ],
    'news_2' => [
        'th' => 'ได้รับรางวัลนวัตกรรมดีไซน์ยอดเยี่ยมที่ Bangkok Design Week',
        'en' => 'Awarded Best Design Innovation at Bangkok Design Week',
        'cn' => '荣获曼谷设计周最佳设计创新奖',
        'jp' => 'バンコクデザインウィークで最優秀デザインイノベーション賞を受賞',
        'kr' => '방콕 디자인 위크에서 최우수 디자인 혁신상 수상'
    ],
    'news_3' => [
        'th' => 'น้ำหอมที่มี AI เป็นเพื่อนในทุกขวด - แต่ละขวดไม่ซ้ำใคร',
        'en' => 'Perfumes with AI Companion in Every Bottle - Each One Unique',
        'cn' => '每瓶香水都有 AI 伴侣 - 每瓶独一无二',
        'jp' => '全てのボトルに AI コンパニオンが - 各ボトルはユニーク',
        'kr' => '모든 병에 AI 컴패니언 포함 - 각각 고유함'
    ],
    'news_4' => [
        'th' => 'เปิดโชว์รูมใหม่ที่สุขุมวิท',
        'en' => 'Opening New Showroom in Sukhumvit',
        'cn' => '素坤逸新展厅开业',
        'jp' => 'スクンビットに新ショールームをオープン',
        'kr' => '수쿰윗에 새로운 쇼룸 오픈'
    ],
    'our_collection' => [
        'th' => 'คอลเลคชั่นของเรา',
        'en' => 'Our Collection',
        'cn' => '我们的系列',
        'jp' => '私たちのコレクション',
        'kr' => '우리의 컬렉션'
    ],
    'premium_products' => [
        'th' => 'น้ำหอมพรีเมียม',
        'en' => 'Premium Perfumes',
        'cn' => '高级香水',
        'jp' => 'プレミアムフレグランス',
        'kr' => '프리미엄 향수'
    ],
    'view_all' => [
        'th' => 'ดูทั้งหมด',
        'en' => 'View All Products',
        'cn' => '查看所有产品',
        'jp' => 'すべての製品を見る',
        'kr' => '모든 제품 보기'
    ],
    'featured_project' => [
        'th' => 'โปรเจคพิเศษ',
        'en' => 'Featured Project',
        'cn' => '特色项目',
        'jp' => '注目のプロジェクト',
        'kr' => '주요 프로젝트'
    ],
    'transforming_title' => [
        'th' => 'การเปลี่ยนแปลงประสบการณ์น้ำหอมด้วย AI',
        'en' => 'Transforming Fragrance Experience Through AI',
        'cn' => '通过 AI 改变香水体验',
        'jp' => 'AI による香りの体験の変革',
        'kr' => 'AI를 통한 향수 경험의 변화'
    ],
    'transforming_desc' => [
        'th' => 'ค้นพบว่าเราได้ปฏิวัติการออกแบบน้ำหอมด้วยเทคโนโลยี AI ที่ทันสมัย โปรเจคล่าสุดของเราแสดงให้เห็นการผสมผสานที่สมบูรณ์แบบระหว่างกลิ่นหอมและ AI ที่เป็นเพื่อนคู่ใจในทุกขวด สร้างประสบการณ์ที่ไม่เหมือนใคร',
        'en' => 'Discover how we\'ve revolutionized perfume design with modern AI technology. Our latest project showcases the perfect blend of fragrance and AI companionship in every bottle, creating unique experiences where scent meets intelligence.',
        'cn' => '探索我们如何通过现代 AI 技术革新香水设计。我们的最新项目展示了香气与 AI 伴侣的完美融合，在每个瓶中创造独特的体验。',
        'jp' => '最新の AI テクノロジーで香水デザインを革新した方法をご覧ください。最新プロジェクトでは、すべてのボトルに香りと AI コンパニオンの完璧な融合を実現し、独自の体験を生み出しています。',
        'kr' => '현대 AI 기술로 향수 디자인을 혁신한 방법을 알아보세요. 최신 프로젝트는 모든 병에 향기와 AI 컴패니언의 완벽한 조화를 보여주며 독특한 경험을 만들어냅니다.'
    ],
    'view_project' => [
        'th' => 'ดูโปรเจค',
        'en' => 'View Project',
        'cn' => '查看项目',
        'jp' => 'プロジェクトを見る',
        'kr' => '프로젝트 보기'
    ],
    'insights' => [
        'th' => 'บทความ',
        'en' => 'Insights',
        'cn' => '见解',
        'jp' => 'インサイト',
        'kr' => '인사이트'
    ],
    'latest_stories' => [
        'th' => 'เรื่องราวล่าสุด',
        'en' => 'Latest Stories',
        'cn' => '最新故事',
        'jp' => '最新ストーリー',
        'kr' => '최신 스토리'
    ],
    'cat_design' => [
        'th' => 'ดีไซน์',
        'en' => 'Design',
        'cn' => '设计',
        'jp' => 'デザイン',
        'kr' => '디자인'
    ],
    'story_1_title' => [
        'th' => 'อนาคตของการออกแบบน้ำหอมด้วย AI',
        'en' => 'The Future of AI-Powered Perfume Design',
        'cn' => 'AI 驱动香水设计的未来',
        'jp' => 'AI を活用した香水デザインの未来',
        'kr' => 'AI 기반 향수 디자인의 미래'
    ],
    'story_1_desc' => [
        'th' => 'สำรวจแนวทางนวัตกรรมในการผสมผสานเทคโนโลยี AI เข้ากับศิลปะการสร้างน้ำหอม และการมี AI เป็นเพื่อนที่เข้าใจคุณในทุกขวด',
        'en' => 'Exploring innovative approaches to blending AI technology with the art of perfume creation and having an AI companion that understands you in every bottle.',
        'cn' => '探索将 AI 技术与香水创作艺术相结合的创新方法，以及在每个瓶中拥有一个了解您的 AI 伴侣。',
        'jp' => 'AI テクノロジーと香水創作の芸術を融合させる革新的なアプローチと、各ボトルにあなたを理解する AI コンパニオンを持つことを探求します。',
        'kr' => 'AI 기술과 향수 창작 예술을 결합하는 혁신적인 접근 방식과 모든 병에서 당신을 이해하는 AI 컴패니언을 갖는 것을 탐구합니다.'
    ],
    'cat_innovation' => [
        'th' => 'นวัตกรรม',
        'en' => 'Innovation',
        'cn' => '创新',
        'jp' => 'イノベーション',
        'kr' => '혁신'
    ],
    'story_2_title' => [
        'th' => 'AI Companion - เพื่อนที่เข้าใจคุณในทุกขวด',
        'en' => 'AI Companion - A Friend Who Understands You in Every Bottle',
        'cn' => 'AI 伴侣 - 每瓶中都能理解你的朋友',
        'jp' => 'AI コンパニオン - すべてのボトルにあなたを理解する友達',
        'kr' => 'AI 컴패니언 - 모든 병에서 당신을 이해하는 친구'
    ],
    'story_2_desc' => [
        'th' => 'แต่ละขวดมีเอกลักษณ์เฉพาะตัว AI ที่ถูกสร้างขึ้นมาเพื่อเข้าใจและปรับตัวตามบุคลิกของคุณ ไม่มีสองขวดที่เหมือนกัน',
        'en' => 'Each bottle has a unique AI personality created to understand and adapt to your character. No two bottles are the same.',
        'cn' => '每个瓶子都有独特的 AI 个性，旨在理解并适应您的性格。没有两个瓶子是相同的。',
        'jp' => '各ボトルには、あなたのキャラクターを理解し適応するために作成された独自の AI パーソナリティがあります。同じボトルは 2 つとありません。',
        'kr' => '각 병에는 당신의 성격을 이해하고 적응하도록 만들어진 고유한 AI 개성이 있습니다. 같은 병은 두 개가 없습니다.'
    ],
    'cat_spotlight' => [
        'th' => 'ไฮไลท์',
        'en' => 'Spotlight',
        'cn' => '焦点',
        'jp' => 'スポットライト',
        'kr' => '스포트라이트'
    ],
    'story_3_title' => [
        'th' => 'น้ำหอมที่ได้รับรางวัลระดับสากล',
        'en' => 'Award-Winning Perfume Design',
        'cn' => '屡获殊荣的香水设计',
        'jp' => '受賞歴のある香水デザイン',
        'kr' => '수상 경력이 있는 향수 디자인'
    ],
    'story_3_desc' => [
        'th' => 'เบื้องหลังโปรเจคล่าสุดของเราที่คว้ารางวัล International Fragrance Award ด้วยการผสมผสาน AI และกลิ่นหอมอย่างลงตัว',
        'en' => 'Behind the scenes of our recent project that won the International Fragrance Award with perfect AI and scent integration.',
        'cn' => '我们最近的项目幕后花絮，凭借完美的 AI 和香气融合赢得了国际香水奖。',
        'jp' => '完璧な AI と香りの統合で国際フレグランス賞を受賞した最近のプロジェクトの舞台裏。',
        'kr' => '완벽한 AI와 향기 통합으로 국제 향수상을 수상한 최근 프로젝트의 비하인드 스토리.'
    ]
];

// Helper function
function tt($key, $lang) {
    global $translations;
    return $translations[$key][$lang] ?? $translations[$key]['en'];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
     <?php include 'template/header.php'; ?>
    
 
    
</head>
<body>

   

    <?php include 'template/banner_slide.php'; ?>

    <!-- NEWS TICKER -->
    <section class="news-ticker-section">
        <div class="ticker-wrapper">
            <div class="ticker-label"><?= tt('latest', $lang) ?></div>
            <div class="ticker-content">
                <div class="ticker-track">
                    <a href="#" class="ticker-item"><?= tt('news_1', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_2', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_3', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_4', $lang) ?></a>
                    <!-- Duplicate for seamless loop -->
                    <a href="#" class="ticker-item"><?= tt('news_1', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_2', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_3', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_4', $lang) ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- PRODUCTS SECTION -->
<section class="products-section">
    <div class="section-header">
        <p class="section-subtitle"><?= tt('our_collection', $lang) ?></p>
        <h2 class="section-title"><?= tt('premium_products', $lang) ?></h2>
    </div>

    <div class="products-grid">
        <?php
        // Fetch products from database
        $name_col = "name_" . $lang;
        
        $products_query = "
            SELECT 
                p.product_id,
                p.{$name_col} as product_name,
                p.price,
                p.vat_percentage,
                ROUND(p.price * (1 + p.vat_percentage / 100), 2) as price_with_vat,
                pi.api_path as image_path
            FROM products p
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1 AND pi.del = 0
            WHERE p.status = 1 AND p.del = 0
            ORDER BY p.created_at DESC
            LIMIT 8
        ";
        
        $products_result = $conn->query($products_query);
        
        if ($products_result && $products_result->num_rows > 0) {
            while ($product = $products_result->fetch_assoc()) {
                $product_id_encoded = urlencode(base64_encode($product['product_id']));
                $product_link = "?product_detail&id=" . $product_id_encoded . "&lang=" . $lang;
                $image = $product['image_path'] ?? 'https://images.unsplash.com/photo-1600607687644-c7171b42498f?w=400&h=533&fit=crop';
                ?>
                <a href="<?= htmlspecialchars($product_link) ?>" class="product-card">
                    <div class="product-image-wrapper">
                        <img src="<?= htmlspecialchars($image) ?>" 
                             alt="<?= htmlspecialchars($product['product_name']) ?>" 
                             class="product-image"
                             loading="lazy"
                             width="400" 
                             height="533">
                        <div class="product-price-overlay">
                            ฿<?= number_format($product['price_with_vat'], 2) ?>
                        </div>
                    </div>
                    <h3 class="product-name"><?= htmlspecialchars($product['product_name']) ?></h3>
                </a>
                <?php
            }
        } else {
            // Fallback if no products
            ?>
            <a href="#" class="product-card">
                <div class="product-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1600607687644-c7171b42498f?w=400&h=533&fit=crop" 
                         alt="Coming Soon" 
                         class="product-image"
                         loading="lazy">
                </div>
                <h3 class="product-name">Coming Soon</h3>
            </a>
            <?php
        }
        ?>
    </div>
    
    <div style="text-align: center; margin-top: 40px;">
        <a href="?product&lang=<?= $lang ?>" class="view-all-btn">
            <?= tt('view_all', $lang) ?>
        </a>
    </div>
</section>

<style>
/* Product Card Enhancements */
.product-card {
    position: relative;
    text-decoration: none;
    color: inherit;
    display: block;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-card:hover {
    transform: translateY(-8px);
}

.product-image-wrapper {
    position: relative;
    overflow: hidden;
    aspect-ratio: 3/4;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-card:hover .product-image {
    transform: scale(1.08);
}

/* Price Overlay */
.product-price-overlay {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 12px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 16px;
    color: #000;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.product-card:hover .product-price-overlay {
    background: rgba(0, 0, 0, 0.9);
    color: #fff;
    transform: scale(1.05);
}

.product-name {
    margin-top: 15px;
    font-size: 16px;
    font-weight: 500;
    text-align: center;
    color: #1a1a1a;
}

.view-all-btn {
    display: inline-block;
    padding: 15px 50px;
    background: #000;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    border-radius: 30px;
}

.view-all-btn:hover {
    background: #333;
    transform: scale(1.05);
}
</style>

    <!-- FEATURED CONTENT -->
    <section class="featured-section">
        <div class="featured-image">
            <img src="http://localhost/origami_website/perfume//public/product_images/69606aab39ce7_1767926443.jpg" 
                 alt="Featured Project"
                 loading="lazy"
                 width="960" 
                 height="800">
        </div>
        <div class="featured-content">
            <p class="featured-label"><?= tt('featured_project', $lang) ?></p>
            <h2 class="featured-title"><?= tt('transforming_title', $lang) ?></h2>
            <p class="featured-text">
                <?= tt('transforming_desc', $lang) ?>
            </p>
            <a href="?about" class="featured-link"><?= tt('view_project', $lang) ?></a>
        </div>
    </section>

    <!-- NEWS SECTION -->
    <section class="news-section">
        <div class="section-header">
            <p class="section-subtitle"><?= tt('insights', $lang) ?></p>
            <h2 class="section-title"><?= tt('latest_stories', $lang) ?></h2>
        </div>

        <div class="news-grid">
            <article class="news-card">
                <a href="#">
                    <div class="news-image">
                        <img src="http://localhost/origami_website/perfume//public/product_images/69606aab5081a_1767926443.jpg" 
                             alt="News Article"
                             loading="lazy"
                             width="400" 
                             height="533">
                    </div>
                    <p class="news-category"><?= tt('cat_design', $lang) ?></p>
                    <h3 class="news-title"><?= tt('story_1_title', $lang) ?></h3>
                    <p class="news-excerpt">
                        <?= tt('story_1_desc', $lang) ?>
                    </p>
                    <p class="news-meta">December 30, 2024</p>
                </a>
            </article>

            <article class="news-card">
                <a href="#">
                    <div class="news-image">
                        <img src="http://localhost/origami_website/perfume//public/product_images/696089dc34f0a_1767934428.jpg" 
                             alt="News Article"
                             loading="lazy"
                             width="400" 
                             height="533">
                    </div>
                    <p class="news-category"><?= tt('cat_innovation', $lang) ?></p>
                    <h3 class="news-title"><?= tt('story_2_title', $lang) ?></h3>
                    <p class="news-excerpt">
                        <?= tt('story_2_desc', $lang) ?>
                    </p>
                    <p class="news-meta">December 28, 2024</p>
                </a>
            </article>

            <article class="news-card">
                <a href="#">
                    <div class="news-image">
                        <img src="http://localhost/origami_website/perfume//public/product_images/69606aab47d80_1767926443.jpg" 
                             alt="News Article"
                             loading="lazy"
                             width="400" 
                             height="533">
                    </div>
                    <p class="news-category"><?= tt('cat_spotlight', $lang) ?></p>
                    <h3 class="news-title"><?= tt('story_3_title', $lang) ?></h3>
                    <p class="news-excerpt">
                        <?= tt('story_3_desc', $lang) ?>
                    </p>
                    <p class="news-meta">December 25, 2024</p>
                </a>
            </article>
        </div>
    </section>

    <!-- Include Footer -->
    <?php include 'template/footer.php'; ?>



</body>
</html>