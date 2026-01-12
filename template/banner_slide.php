<?php
// Static images and videos for hero banner
$imagesItems = [
    [
        'type' => 'image',
        'src' => 'http://localhost/origami_website/perfume//public/product_images/696089dc2fa56_1767934428.jpg'
    ],
    [
        'type' => 'video',
        'src' => 'http://localhost/origami_website/perfume//public/product_images/freepik_ultrarealistic_cinematic_commercial_video_based_on_kling.mp4'
    ],
    [
        'type' => 'image',
        'src' => 'http://localhost/origami_website/perfume//public/product_images/69606aab3b72e_1767926443.jpg'
    ],
    [
        'type' => 'image',
        'src' => 'http://localhost/origami_website/perfume//public/product_images/695df1d5afe5d_1767764437.jpg'
    ],
];

// Preload first image for LCP optimization
if (!empty($imagesItems) && $imagesItems[0]['type'] === 'image') {
    echo '<link rel="preload" as="image" href="' . $imagesItems[0]['src'] . '">'; 
}

// Get language from session
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

// Translation arrays
$hero_translations = [
    'subtitle' => [
        'th' => 'น้ำหอมพรีเมียมพร้อม AI Companion',
        'en' => 'Luxury Fragrances with AI Companion',
        'cn' => '带有 AI 伴侣的奢华香水',
        'jp' => 'AI コンパニオン付きラグジュアリーフレグランス',
        'kr' => 'AI 컴패니언과 함께하는 럭셔리 향수'
    ],
    'title_line1' => [
        'th' => 'กลิ่นหอมที่มี',
        'en' => 'Where Scent',
        'cn' => '香气遇见',
        'jp' => '香りが出会う',
        'kr' => '향기가 만나는'
    ],
    'title_line2' => [
        'th' => 'AI เป็นเพื่อน',
        'en' => 'Meets AI',
        'cn' => 'AI',
        'jp' => 'AI',
        'kr' => 'AI'
    ],
    'description' => [
        'th' => 'สัมผัสประสบการณ์น้ำหอมที่มาพร้อม AI Companion เฉพาะตัว แต่ละขวดมีเอกลักษณ์ไม่ซ้ำใคร AI ที่เข้าใจและปรับตัวตามบุคลิกของคุณ เปลี่ยนประสบการณ์น้ำหอมของคุณให้กลายเป็นมิตรภาพที่แท้จริง',
        'en' => 'Experience perfumes with unique AI Companions. Each bottle features a distinct AI personality that understands and adapts to your character. Transform your fragrance experience into genuine companionship.',
        'cn' => '体验带有独特 AI 伴侣的香水。每个瓶子都有一个独特的 AI 个性，能够理解并适应您的性格。将您的香水体验转变为真正的陪伴。',
        'jp' => 'ユニークな AI コンパニオンを備えた香水を体験してください。各ボトルには、あなたのキャラクターを理解し適応する独自の AI パーソナリティがあります。香りの体験を真の友情に変えましょう。',
        'kr' => '독특한 AI 컴패니언이 있는 향수를 경험하세요. 각 병에는 당신의 성격을 이해하고 적응하는 독특한 AI 개성이 있습니다. 향수 경험을 진정한 동반자 관계로 바꾸세요.'
    ],
    'cta' => [
        'th' => 'สำรวจคอลเลคชั่น',
        'en' => 'Explore Collection',
        'cn' => '探索系列',
        'jp' => 'コレクションを探す',
        'kr' => '컬렉션 탐색'
    ]
];

function ht($key, $lang) {
    global $hero_translations;
    return $hero_translations[$key][$lang] ?? $hero_translations[$key]['en'];
}
?>

<!-- Banner Styles -->
<style>
    :root {
        --luxury-black: #000000;
        --transition: cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ========================================
       HERO BANNER - FULL SCREEN
       ======================================== */
    
    .hero {
        height: 130vh;
        position: relative;
        overflow: hidden;
        background: var(--luxury-black);
        margin-top: 5em;
    }

    .hero-slider {
        height: 100%;
        position: relative;
    }

    .hero-slide {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 1.5s var(--transition);
    }

    .hero-slide.active {
        opacity: 1;
    }

    .hero-image,
    .hero-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: brightness(0.7);
    }

    .hero-video {
        pointer-events: none;
    }

    .hero-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: white;
        z-index: 10;
    }

    .hero-subtitle {
        font-size: 14px;
        font-weight: 300;
        letter-spacing: 0.3em;
        text-transform: uppercase;
        margin-bottom: 30px;
        opacity: 0;
        animation: fadeInUp 1s ease 0.5s forwards;
    }

    .hero-title {
        font-family: 'Playfair Display', serif;
        font-size: 72px;
        font-weight: 400;
        letter-spacing: 0.05em;
        line-height: 1.2;
        margin-bottom: 40px;
        opacity: 0;
        animation: fadeInUp 1s ease 0.8s forwards;
    }

    .hero-description {
        font-size: 16px;
        font-weight: 300;
        letter-spacing: 0.05em;
        max-width: 600px;
        margin: 0 auto 50px;
        line-height: 1.8;
        opacity: 0;
        animation: fadeInUp 1s ease 1.1s forwards;
    }

    .hero-cta {
        display: inline-block;
        padding: 18px 50px;
        border: 1px solid white;
        color: white;
        text-decoration: none;
        font-size: 12px;
        font-weight: 400;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        transition: all 0.4s ease;
        opacity: 0;
        animation: fadeInUp 1s ease 1.4s forwards;
    }

    .hero-cta:hover {
        background: white;
        color: var(--luxury-black);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-nav {
        position: absolute;
        bottom: 60px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 15px;
        z-index: 10;
    }

    .hero-dot {
        width: 40px;
        height: 2px;
        background: rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.4s ease;
        border: none;
        padding: 0;
    }

    .hero-dot.active {
        background: white;
    }

    .hero-counter {
        position: absolute;
        bottom: 60px;
        right: 80px;
        color: white;
        font-size: 14px;
        font-weight: 300;
        letter-spacing: 0.1em;
        z-index: 10;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 48px;
        }

        .hero-counter {
            right: 40px;
            bottom: 40px;
        }

        .hero-nav {
            bottom: 40px;
        }
    }
</style>

<!-- HERO BANNER -->
<section class="hero">
    <div class="hero-slider">
        <?php foreach ($imagesItems as $index => $item): ?>
            <div class="hero-slide <?= ($index === 0) ? 'active' : '' ?>" data-type="<?= $item['type'] ?>">
                <?php if ($item['type'] === 'image'): ?>
                    <?php
                        $loading_attribute = ($index === 0) ? 'loading="eager"' : 'loading="lazy"';
                        $width_attribute = 'width="1920"'; 
                        $height_attribute = 'height="1080"';
                        $alt_text = "Hero Slide " . ($index + 1);
                    ?>
                    <img src="<?= $item['src'] ?>" 
                         alt="<?= $alt_text ?>" 
                         class="hero-image" 
                         <?= $width_attribute ?> 
                         <?= $height_attribute ?>
                         <?= $loading_attribute ?>>
                <?php else: ?>
                    <video 
                        class="hero-video" 
                        muted 
                        loop 
                        playsinline
                        <?= ($index === 0) ? 'autoplay' : '' ?>>
                        <source src="<?= $item['src'] ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="hero-content">
        <p class="hero-subtitle"><?= ht('subtitle', $lang) ?></p>
        <h1 class="hero-title"><?= ht('title_line1', $lang) ?><br><?= ht('title_line2', $lang) ?></h1>
        <p class="hero-description">
            <?= ht('description', $lang) ?>
        </p>
        <a href="#" class="hero-cta"><?= ht('cta', $lang) ?></a>
    </div>

    <div class="hero-nav">
        <?php foreach ($imagesItems as $index => $item): ?>
            <span class="hero-dot <?= ($index === 0) ? 'active' : '' ?>"></span>
        <?php endforeach; ?>
    </div>

    <div class="hero-counter">
        <span class="current">01</span> / <span class="total"><?= str_pad(count($imagesItems), 2, '0', STR_PAD_LEFT) ?></span>
    </div>
</section>

<script>
(function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    const totalSlides = slides.length;
    let autoSlideInterval;

    function showSlide(index) {
        // Pause all videos
        slides.forEach(slide => {
            const video = slide.querySelector('.hero-video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
            slide.classList.remove('active');
        });
        
        dots.forEach(dot => dot.classList.remove('active'));
        
        // Show current slide
        slides[index].classList.add('active');
        dots[index].classList.add('active');
        
        // Play video if current slide is video
        const currentVideo = slides[index].querySelector('.hero-video');
        if (currentVideo) {
            currentVideo.play().catch(e => console.log('Video autoplay failed:', e));
        }
        
        document.querySelector('.current').textContent = String(index + 1).padStart(2, '0');
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    function startAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
        }
        autoSlideInterval = setInterval(nextSlide, 5000);
    }

    // Start auto slide
    startAutoSlide();

    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
            startAutoSlide(); // Restart auto slide on manual navigation
        });
    });

    // Pause auto-slide when user interacts
    document.querySelector('.hero-slider').addEventListener('mouseenter', () => {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
        }
    });

    document.querySelector('.hero-slider').addEventListener('mouseleave', () => {
        startAutoSlide();
    });
})();
</script>