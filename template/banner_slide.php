<?php
// Static images and videos for hero banner
$imagesItems = [
    [
        'type' => 'video',
        'src' => 'public/ai_videos/video_696db72785bea_1768797991.mp4',
        'duration' => 14000 // 14 seconds in milliseconds
    ],
    [
        'type' => 'video',
        'src' => 'public/ai_videos/video_696de657cc980_1768810071.mp4',
        'duration' => 17000 // 14 seconds in milliseconds
    ],
    [
        'type' => 'video',
        'src' => 'public/ai_videos/video_696de647ce56d_1768810055.mp4',
        'duration' => 16000 // 14 seconds in milliseconds
    ],

    [
        'type' => 'image',
        'src' => 'public/product_images/696089dc2fa56_1767934428.jpg',
        'duration' => 5000 // 5 seconds for images
    ],
    [
        'type' => 'image',
        'src' => 'public/product_images/69606aab3b72e_1767926443.jpg',
        'duration' => 5000
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
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    .hero-content.show {
        opacity: 1;
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

    /* ========================================
       PROGRESS BAR NAVIGATION (Gentle Monster Style)
       ======================================== */
    
    .hero-nav {
        position: absolute;
        bottom: 60px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
        z-index: 10;
        align-items: center;
    }

    .hero-dot {
        position: relative;
        width: 50px;
        height: 2px;
        background: rgba(255, 255, 255, 0.25);
        cursor: pointer;
        border: none;
        padding: 0;
        overflow: hidden;
    }

    .hero-dot-progress {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 0;
        background: rgba(255, 255, 255, 0.9);
        transition: width linear;
    }

    .hero-dot.active .hero-dot-progress {
        animation: progressBar linear forwards;
    }

    @keyframes progressBar {
        from {
            width: 0%;
        }
        to {
            width: 100%;
        }
    }

    .hero-dot:hover {
        background: rgba(255, 255, 255, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 48px;
        }

        .hero-nav {
            bottom: 40px;
        }

        .hero-dot {
            width: 35px;
        }
    }
</style>

<!-- HERO BANNER -->
<section class="hero">
    <div class="hero-slider">
        <?php foreach ($imagesItems as $index => $item): ?>
            <div class="hero-slide <?= ($index === 0) ? 'active' : '' ?>" 
                 data-type="<?= $item['type'] ?>"
                 data-duration="<?= $item['duration'] ?>">
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
                        preload="auto"
                        <?= ($index === 0) ? 'autoplay' : '' ?>>
                        <source src="<?= $item['src'] ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="hero-content <?= ($imagesItems[0]['type'] === 'image') ? 'show' : '' ?>">
        <p class="hero-subtitle"><?= ht('subtitle', $lang) ?></p>
        <h1 class="hero-title"><?= ht('title_line1', $lang) ?><br><?= ht('title_line2', $lang) ?></h1>
        <p class="hero-description">
            <?= ht('description', $lang) ?>
        </p>
       <a href="?product&lang=<?= $lang ?>" class="hero-cta"><?= ht('cta', $lang) ?></a>
    </div>

    <div class="hero-nav">
        <?php foreach ($imagesItems as $index => $item): ?>
            <button class="hero-dot <?= ($index === 0) ? 'active' : '' ?>" data-index="<?= $index ?>">
                <div class="hero-dot-progress"></div>
            </button>
        <?php endforeach; ?>
    </div>
</section>

<script>
(function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    const heroContent = document.querySelector('.hero-content');
    const totalSlides = slides.length;
    let autoSlideInterval;
    let isPaused = false;
    let currentVideo = null;

    function resetProgress() {
        dots.forEach(dot => {
            const progress = dot.querySelector('.hero-dot-progress');
            progress.style.animation = 'none';
            progress.style.width = '0%';
        });
    }

    function updateContentVisibility(slideType) {
        if (slideType === 'image') {
            heroContent.classList.add('show');
        } else {
            heroContent.classList.remove('show');
        }
    }

    function showSlide(index) {
        // Clear any existing interval
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
            autoSlideInterval = null;
        }
        
        // Stop all videos and reset
        slides.forEach(slide => {
            const video = slide.querySelector('.hero-video');
            if (video) {
                video.pause();
                video.currentTime = 0;
                video.onended = null;
            }
            slide.classList.remove('active');
        });
        
        // Reset all progress bars
        resetProgress();
        dots.forEach(dot => dot.classList.remove('active'));
        
        // Show current slide
        slides[index].classList.add('active');
        dots[index].classList.add('active');
        
        // Update content visibility based on slide type
        const slideType = slides[index].dataset.type;
        updateContentVisibility(slideType);
        
        // Get duration from data attribute
        const duration = parseInt(slides[index].dataset.duration);
        
        // Animate progress bar
        const progress = dots[index].querySelector('.hero-dot-progress');
        progress.style.animation = 'none';
        void progress.offsetWidth;
        progress.style.animation = `progressBar ${duration}ms linear forwards`;
        
        // Check if current slide is video or image
        const video = slides[index].querySelector('.hero-video');
        
        if (video) {
            // Handle VIDEO
            currentVideo = video;
            
            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.catch(e => {
                    console.log('Video autoplay prevented:', e);
                    autoSlideInterval = setTimeout(nextSlide, duration);
                });
            }
            
            let videoTransitioned = false;
            
            video.onended = () => {
                if (!isPaused && !videoTransitioned) {
                    videoTransitioned = true;
                    nextSlide();
                }
            };
            
            video.ontimeupdate = () => {
                if (video.duration - video.currentTime < 0.5 && !videoTransitioned && !isPaused) {
                    videoTransitioned = true;
                    nextSlide();
                }
            };
            
            autoSlideInterval = setTimeout(() => {
                if (!videoTransitioned && !isPaused) {
                    videoTransitioned = true;
                    nextSlide();
                }
            }, duration + 500);
            
        } else {
            // Handle IMAGE
            autoSlideInterval = setTimeout(nextSlide, duration);
        }
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    // Initialize first slide
    showSlide(currentSlide);

    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
        });
    });

    // Handle video loading errors
    document.querySelectorAll('.hero-video').forEach(video => {
        video.addEventListener('error', (e) => {
            console.error('Video loading error:', e);
        });
        
        video.addEventListener('loadeddata', () => {
            console.log('Video loaded successfully');
        });
    });
})();
</script>