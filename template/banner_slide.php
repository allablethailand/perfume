<?php
// Static images and videos for hero banner
$imagesItems = [
    [
        'type' => 'video',
        'src' => 'https://www.trandar.com/perfume//public/ai_videos/video_696dfb876e488_1768815495.mp4',
        'poster' => 'https://www.trandar.com/perfume//public/ai_videos/video_696dfb876e488_1768815495.mp4',
        'duration' => 14000
    ],
    [
        'type' => 'video',
        'src' => 'https://www.trandar.com/perfume//public/ai_videos/video_696df2e7d8de4_1768813287.mp4',
        'poster' => 'https://www.trandar.com/perfume//public/ai_videos/video_696df2e7d8de4_1768813287.mp4',
        'duration' => 17000
    ],
    [
        'type' => 'video',
        'src' => 'https://www.trandar.com/perfume//public/ai_videos/video_696df2e7d8de4_1768813287.mp4',
        'poster' => 'https://www.trandar.com/perfume//public/ai_videos/video_696df2e7d8de4_1768813287.mp4',
        'duration' => 16000
    ],
    [
        'type' => 'image',
        'src' => 'public/product_images/696089dc2fa56_1767934428.jpg',
        'duration' => 5000
    ],
    [
        'type' => 'image',
        'src' => 'public/product_images/69606aab3b72e_1767926443.jpg',
        'duration' => 5000
    ],
];

// Preload first video poster for LCP optimization
if (!empty($imagesItems) && $imagesItems[0]['type'] === 'video' && isset($imagesItems[0]['poster'])) {
    echo '<link rel="preload" as="image" href="' . $imagesItems[0]['poster'] . '">';
}
// Preload first image
if (!empty($imagesItems) && $imagesItems[0]['type'] === 'image') {
    echo '<link rel="preload" as="image" href="' . $imagesItems[0]['src'] . '">'; 
}

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

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

<style>
    :root {
        --luxury-black: #000000;
        --transition: cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hero {
        height: 110vh;
        position: relative;
        overflow: hidden;
        background: var(--luxury-black);
        /* ลบ white flash ตอนโหลด */
    }

    .hero-slider {
        height: 100%;
        position: relative;
        top: 0;
        background: var(--luxury-black); /* ป้องกัน white flash */
    }

    .hero-slide {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        visibility: hidden;
        /* ใช้ crossfade แทน fade in/out เพื่อความ smooth */
        transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        background: var(--luxury-black); /* ป้องกัน white flash */
    }

    .hero-slide.active {
        opacity: 1;
        visibility: visible;
        z-index: 2;
    }

    /* เตรียม slide ถัดไปให้พร้อม */
    .hero-slide.preparing {
        visibility: visible;
        z-index: 1;
    }

    /* ลบ skeleton loading ที่ทำให้วูบขาว */
    .hero-slide::before {
        display: none;
    }

    .hero-image,
    .hero-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: brightness(0.7);
        /* ลบ scale ที่ไม่จำเป็น */
        transform: translateZ(0); /* GPU acceleration */
    }

    .hero-video {
        pointer-events: none;
        /* Performance optimization สำหรับ video */
        will-change: auto; /* ใช้แค่ตอนจำเป็น */
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        transform: translate3d(0, 0, 0); /* Force GPU */
        /* ป้องกัน flickering */
        -webkit-transform: translate3d(0, 0, 0);
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }

    /* ให้ video เล่นราบรื่น 60fps */
    @media (prefers-reduced-motion: no-preference) {
        .hero-video {
            animation: smoothPlayback 0.1s linear infinite;
        }
    }

    @keyframes smoothPlayback {
        0%, 100% { transform: translate3d(0, 0, 0); }
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
        transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1);
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
        transition: background 0.3s ease;
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
        from { width: 0%; }
        to { width: 100%; }
    }

    .hero-dot:hover {
        background: rgba(255, 255, 255, 0.4);
    }

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

    /* ป้องกัน flash of white content */
    html {
        background: var(--luxury-black);
    }
</style>

<section class="hero">
    <div class="hero-slider">
        <?php foreach ($imagesItems as $index => $item): ?>
            <div class="hero-slide <?= ($index === 0) ? 'active' : '' ?>" 
                 data-type="<?= $item['type'] ?>"
                 data-duration="<?= $item['duration'] ?>">
                <?php if ($item['type'] === 'image'): ?>
                    <?php
                        $loading_attribute = ($index === 0) ? 'loading="eager"' : 'loading="lazy"';
                        $alt_text = "Hero Slide " . ($index + 1);
                    ?>
                    <img src="<?= $item['src'] ?>" 
                         alt="<?= $alt_text ?>" 
                         class="hero-image" 
                         width="1920" 
                         height="1080"
                         <?= $loading_attribute ?>>
                <?php else: ?>
                    <video 
                        class="hero-video" 
                        muted 
                        loop 
                        playsinline
                        preload="<?= ($index === 0) ? 'auto' : 'metadata' ?>"
                        <?= isset($item['poster']) ? 'poster="' . $item['poster'] . '"' : '' ?>
                        <?= ($index === 0) ? 'autoplay' : '' ?>
                        disablePictureInPicture>
                        <source src="<?= $item['src'] ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="hero-content <?= ($imagesItems[0]['type'] === 'image') ? 'show' : '' ?>">
        <p class="hero-subtitle"><?= ht('subtitle', $lang) ?></p>
        <h1 class="hero-title"><?= ht('title_line1', $lang) ?><br><?= ht('title_line2', $lang) ?></h1>
        <p class="hero-description"><?= ht('description', $lang) ?></p>
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
    let loadedVideos = new Set();
    let isTransitioning = false;

    // Preload และเตรียม video ให้พร้อมเล่น
    function preloadAndPrepareNext(index) {
        const nextIndex = (index + 1) % totalSlides;
        const nextSlide = slides[nextIndex];
        const video = nextSlide.querySelector('.hero-video');
        const image = nextSlide.querySelector('.hero-image');

        if (video && !loadedVideos.has(nextIndex)) {
            video.preload = 'auto';
            video.load();
            
            // เตรียม video ให้พร้อมเล่น (ลด lag)
            video.addEventListener('loadeddata', () => {
                loadedVideos.add(nextIndex);
                // Seek to start เพื่อให้ first frame พร้อม
                video.currentTime = 0;
            }, { once: true });
        } else if (image && image.loading === 'lazy') {
            const img = new Image();
            img.src = image.src;
        }
    }

    function resetProgress() {
        dots.forEach(dot => {
            const progress = dot.querySelector('.hero-dot-progress');
            progress.style.animation = 'none';
            progress.offsetHeight; // Force reflow
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

    function showSlide(index, immediate = false) {
        if (isTransitioning) return;
        isTransitioning = true;

        if (autoSlideInterval) {
            clearTimeout(autoSlideInterval);
            autoSlideInterval = null;
        }
        
        const previousIndex = currentSlide;
        const currentSlideElement = slides[index];
        const previousSlideElement = slides[previousIndex];
        
        // เตรียม slide ถัดไปก่อน transition
        currentSlideElement.classList.add('preparing');
        
        // Reset และเตรียม progress bar
        resetProgress();
        dots.forEach(dot => dot.classList.remove('active'));
        dots[index].classList.add('active');
        
        const slideType = currentSlideElement.dataset.type;
        updateContentVisibility(slideType);
        
        const duration = parseInt(currentSlideElement.dataset.duration);
        
        // จัดการ video
        const video = currentSlideElement.querySelector('.hero-video');
        const previousVideo = previousSlideElement.querySelector('.hero-video');
        
        // Pause previous video
        if (previousVideo && previousIndex !== index) {
            previousVideo.pause();
        }
        
        // เตรียม video ให้พร้อม
        if (video) {
            video.currentTime = 0;
            
            // Play video ก่อน transition เพื่อให้ first frame พร้อม
            const playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    // Video พร้อมแล้ว เริ่ม transition
                    performTransition();
                }).catch(e => {
                    console.log('Video play prevented:', e);
                    performTransition();
                });
            } else {
                performTransition();
            }
        } else {
            performTransition();
        }
        
        function performTransition() {
            // Crossfade transition
            slides.forEach((slide, i) => {
                if (i === index) {
                    slide.classList.add('active');
                } else if (i === previousIndex) {
                    // ค่อยๆ ซ่อน slide เก่า
                    setTimeout(() => {
                        slide.classList.remove('active');
                        slide.classList.remove('preparing');
                    }, 100);
                } else {
                    slide.classList.remove('active');
                    slide.classList.remove('preparing');
                }
            });
            
            // Animate progress bar
            const progress = dots[index].querySelector('.hero-dot-progress');
            progress.style.animation = 'none';
            progress.offsetHeight; // Force reflow
            progress.style.animation = `progressBar ${duration}ms linear forwards`;
            
            // Preload slide ถัดไป
            preloadAndPrepareNext(index);
            
            // Setup next transition
            if (video) {
                let videoEnded = false;
                
                video.onended = () => {
                    if (!isPaused && !videoEnded) {
                        videoEnded = true;
                        isTransitioning = false;
                        nextSlide();
                    }
                };
                
                // Fallback timeout
                autoSlideInterval = setTimeout(() => {
                    if (!videoEnded && !isPaused) {
                        videoEnded = true;
                        isTransitioning = false;
                        nextSlide();
                    }
                }, duration + 300);
                
            } else {
                // Image slide
                autoSlideInterval = setTimeout(() => {
                    isTransitioning = false;
                    if (!isPaused) nextSlide();
                }, duration);
            }
            
            // Reset transition lock after transition duration
            setTimeout(() => {
                isTransitioning = false;
            }, 1200); // Match CSS transition duration
        }
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    // Initialize - เตรียม video แรก
    const firstVideo = slides[0].querySelector('.hero-video');
    if (firstVideo) {
        firstVideo.addEventListener('canplay', () => {
            showSlide(0, true);
        }, { once: true });
        
        // Fallback if video doesn't load
        setTimeout(() => {
            showSlide(0, true);
        }, 1000);
    } else {
        showSlide(0, true);
    }

    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            if (!isTransitioning && currentSlide !== index) {
                currentSlide = index;
                showSlide(currentSlide);
            }
        });
    });

    // Pause on visibility change
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            isPaused = true;
            slides.forEach(slide => {
                const video = slide.querySelector('.hero-video');
                if (video) video.pause();
            });
            if (autoSlideInterval) clearTimeout(autoSlideInterval);
        } else {
            isPaused = false;
            isTransitioning = false;
            showSlide(currentSlide);
        }
    });

    // ป้องกัน memory leaks
    window.addEventListener('beforeunload', () => {
        slides.forEach(slide => {
            const video = slide.querySelector('.hero-video');
            if (video) {
                video.pause();
                video.src = '';
                video.load();
            }
        });
    });
})();
</script>