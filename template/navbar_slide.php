<?php
// require_once('../lib/connect.php');

$sql = "SELECT * FROM banner ORDER BY id ASC";
$result = $conn->query($sql);

$imagesItems = [];
while ($row = $result->fetch_assoc()) {
    $imagesItems[] = $row['image_path']; 
}

if (!empty($imagesItems)) {
    echo '<link rel="preload" as="image" href="' . $imagesItems[0] . '">'; 
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Montserrat:wght@200;300;400;500&display=swap');

    :root {
        --luxury-black: #0a0a0a;
        --luxury-white: #fafafa;
        --luxury-gray: #8a8a8a;
        --luxury-accent: #d4af37;
        --transition-smooth: cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Banner Container - Full Height Luxury Layout */
    .banner-section {
        position: relative;
        width: 100%;
        height: 100vh;
        min-height: 600px;
        max-height: 900px;
        overflow: hidden;
        background: var(--luxury-black);
    }

    .banner-container {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
    }

    /* Banner Item - Luxury Styling */
    .banner-carousel-item {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        transform: scale(1.05);
        transition: opacity 1.2s var(--transition-smooth), 
                    transform 1.2s var(--transition-smooth);
        will-change: opacity, transform;
    }

    .banner-carousel-item.active {
        opacity: 1;
        transform: scale(1);
        z-index: 1;
    }

    .banner-carousel-item.fade-out {
        opacity: 0;
        transform: scale(0.95);
    }

    /* Image Styling - Minimalist Approach */
    .banner-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        filter: brightness(0.85) contrast(1.1);
        transition: filter 0.6s ease;
    }

    .banner-carousel-item.active .banner-image {
        filter: brightness(0.9) contrast(1.05);
    }

    /* Overlay for Depth */
    .banner-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            to bottom,
            rgba(10, 10, 10, 0) 0%,
            rgba(10, 10, 10, 0.3) 100%
        );
        pointer-events: none;
        z-index: 2;
    }

    /* Navigation Controls - Luxury Minimal */
    .banner-control-prev,
    .banner-control-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 60px;
        height: 60px;
        background: rgba(250, 250, 250, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(250, 250, 250, 0.1);
        border-radius: 0;
        color: var(--luxury-white);
        font-size: 16px;
        font-weight: 200;
        cursor: pointer;
        z-index: 10;
        transition: all 0.4s var(--transition-smooth);
        opacity: 0;
        font-family: 'Montserrat', sans-serif;
    }

    .banner-section:hover .banner-control-prev,
    .banner-section:hover .banner-control-next {
        opacity: 1;
    }

    .banner-control-prev:hover,
    .banner-control-next:hover {
        background: rgba(250, 250, 250, 0.15);
        border-color: rgba(250, 250, 250, 0.3);
        transform: translateY(-50%) scale(1.05);
    }

    .banner-control-prev {
        left: 40px;
    }

    .banner-control-next {
        right: 40px;
    }

    /* Pagination - Minimalist Luxury */
    .banner-indicators {
        position: absolute;
        bottom: 60px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 12px;
        z-index: 10;
        padding: 20px 30px;
        background: rgba(10, 10, 10, 0.3);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(250, 250, 250, 0.1);
    }

    .banner-pagination {
        width: 40px;
        height: 2px;
        background: rgba(250, 250, 250, 0.3);
        border: none;
        cursor: pointer;
        transition: all 0.5s var(--transition-smooth);
        position: relative;
        overflow: hidden;
    }

    .banner-pagination::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 0;
        height: 100%;
        background: var(--luxury-white);
        transition: width 0.5s var(--transition-smooth);
    }

    .banner-pagination.active::before {
        width: 100%;
    }

    .banner-pagination:hover {
        background: rgba(250, 250, 250, 0.5);
    }

    /* Content Overlay - Optional (สำหรับใส่ข้อความ) */
    .banner-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: var(--luxury-white);
        z-index: 5;
        opacity: 0;
        transition: opacity 1s ease 0.3s;
    }

    .banner-carousel-item.active .banner-content {
        opacity: 1;
    }

    .banner-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: clamp(3rem, 6vw, 6rem);
        font-weight: 300;
        letter-spacing: 0.05em;
        margin-bottom: 20px;
        text-transform: uppercase;
    }

    .banner-subtitle {
        font-family: 'Montserrat', sans-serif;
        font-size: clamp(0.9rem, 1.2vw, 1.2rem);
        font-weight: 300;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: var(--luxury-gray);
    }

    /* Progress Bar - Auto-scroll indicator */
    .banner-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 1px;
        background: var(--luxury-white);
        z-index: 11;
        transform-origin: left;
        animation: progress 6s linear infinite;
    }

    @keyframes progress {
        from { width: 0; }
        to { width: 100%; }
    }

    /* Scroll Down Indicator */
    .scroll-indicator {
        position: absolute;
        bottom: 40px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        opacity: 0;
        animation: fadeInUp 1s ease 1.5s forwards;
    }

    .scroll-indicator-text {
        font-family: 'Montserrat', sans-serif;
        font-size: 10px;
        font-weight: 300;
        letter-spacing: 0.2em;
        color: var(--luxury-white);
        text-transform: uppercase;
        writing-mode: vertical-rl;
        text-orientation: mixed;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }

    /* Counter Display - Minimalist */
    .banner-counter {
        position: absolute;
        top: 50%;
        right: 60px;
        transform: translateY(-50%);
        font-family: 'Montserrat', sans-serif;
        font-size: 14px;
        font-weight: 200;
        letter-spacing: 0.15em;
        color: var(--luxury-white);
        z-index: 10;
        opacity: 0.6;
    }

    .current-slide {
        font-size: 32px;
        display: block;
        margin-bottom: 10px;
    }

    .total-slides {
        font-size: 14px;
        color: var(--luxury-gray);
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .banner-section {
            min-height: 500px;
            max-height: 700px;
        }

        .banner-control-prev,
        .banner-control-next {
            width: 50px;
            height: 50px;
            font-size: 14px;
        }

        .banner-control-prev {
            left: 20px;
        }

        .banner-control-next {
            right: 20px;
        }

        .banner-counter {
            right: 30px;
        }

        .banner-indicators {
            bottom: 40px;
            padding: 15px 20px;
        }
    }

    @media (max-width: 768px) {
        .banner-section {
            min-height: 400px;
            max-height: 600px;
        }

        .banner-control-prev,
        .banner-control-next {
            width: 40px;
            height: 40px;
            font-size: 12px;
        }

        .banner-control-prev {
            left: 15px;
        }

        .banner-control-next {
            right: 15px;
        }

        .banner-indicators {
            bottom: 30px;
            padding: 12px 16px;
            gap: 8px;
        }

        .banner-pagination {
            width: 30px;
        }

        .banner-counter {
            display: none;
        }

        .scroll-indicator {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .banner-section {
            min-height: 350px;
            max-height: 500px;
        }

        .banner-indicators {
            bottom: 20px;
            gap: 6px;
        }

        .banner-pagination {
            width: 25px;
        }
    }
</style>

<div class="banner-section">
    <div class="banner-container" id="bannerContainer">
        <?php foreach ($imagesItems as $index => $image): ?>
            <div class="banner-carousel-item <?= ($index === 0) ? 'active' : '' ?>" data-slide="<?= $index ?>">
                <?php
                    $loading_attribute = ($index === 0) ? 'loading="eager"' : 'loading="lazy"';
                    $width_attribute = 'width="1920"'; 
                    $height_attribute = 'height="1080"';
                    $alt_text = "Luxury Collection " . ($index + 1);
                ?>
                
                <img src="<?= $image ?>" 
                     alt="<?= $alt_text ?>" 
                     class="banner-image" 
                     <?= $width_attribute ?> 
                     <?= $height_attribute ?>
                     <?= $loading_attribute ?>>
                
                <div class="banner-overlay"></div>
                
                <!-- Optional: เพิ่ม Content overlay สำหรับแต่ละ slide -->
                <!-- 
                <div class="banner-content">
                    <h1 class="banner-title">Your Title Here</h1>
                    <p class="banner-subtitle">Your Subtitle</p>
                </div>
                -->
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Navigation Controls -->
    <button class="banner-control-prev" onclick="moveSlide(-1)" aria-label="Previous slide">
        &#10094;
    </button>
    <button class="banner-control-next" onclick="moveSlide(1)" aria-label="Next slide">
        &#10095;
    </button>

    <!-- Pagination Indicators -->
    <div class="banner-indicators">
        <?php foreach ($imagesItems as $index => $image): ?>
            <button class="banner-pagination <?= ($index === 0) ? 'active' : '' ?>" 
                    onclick="goToSlide(<?= $index ?>)" 
                    aria-label="Go to slide <?= $index + 1 ?>">
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Slide Counter -->
    <div class="banner-counter">
        <span class="current-slide" id="currentSlide">01</span>
        <span class="separator">/</span>
        <span class="total-slides" id="totalSlides"><?= str_pad(count($imagesItems), 2, '0', STR_PAD_LEFT) ?></span>
    </div>

    <!-- Progress Bar -->
    <div class="banner-progress" id="bannerProgress"></div>

    <!-- Scroll Indicator (Optional) -->
    <!-- 
    <div class="scroll-indicator">
        <span class="scroll-indicator-text">Scroll</span>
    </div>
    -->
</div>

<script>
let currentSlideIndex = 0;
let totalSlides = <?= count($imagesItems) ?>;
let autoplayInterval;
let isAnimating = false;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    startAutoplay();
    updateCounter();
});

// Move to specific slide
function goToSlide(index) {
    if (isAnimating || index === currentSlideIndex) return;
    
    isAnimating = true;
    const slides = document.querySelectorAll('.banner-carousel-item');
    const paginations = document.querySelectorAll('.banner-pagination');
    
    // Remove active class and add fade-out
    slides[currentSlideIndex].classList.remove('active');
    slides[currentSlideIndex].classList.add('fade-out');
    paginations[currentSlideIndex].classList.remove('active');
    
    setTimeout(() => {
        slides[currentSlideIndex].classList.remove('fade-out');
        
        // Set new slide
        currentSlideIndex = index;
        slides[currentSlideIndex].classList.add('active');
        paginations[currentSlideIndex].classList.add('active');
        
        updateCounter();
        resetAutoplay();
        
        setTimeout(() => {
            isAnimating = false;
        }, 100);
    }, 600);
}

// Move slide by direction
function moveSlide(direction) {
    let newIndex = currentSlideIndex + direction;
    
    if (newIndex >= totalSlides) {
        newIndex = 0;
    } else if (newIndex < 0) {
        newIndex = totalSlides - 1;
    }
    
    goToSlide(newIndex);
}

// Update counter display
function updateCounter() {
    const counterElement = document.getElementById('currentSlide');
    if (counterElement) {
        counterElement.textContent = String(currentSlideIndex + 1).padStart(2, '0');
    }
}

// Autoplay functionality
function startAutoplay() {
    autoplayInterval = setInterval(() => {
        moveSlide(1);
    }, 6000);
}

function resetAutoplay() {
    clearInterval(autoplayInterval);
    startAutoplay();
}

// Pause on hover
const bannerSection = document.querySelector('.banner-section');
if (bannerSection) {
    bannerSection.addEventListener('mouseenter', () => {
        clearInterval(autoplayInterval);
    });
    
    bannerSection.addEventListener('mouseleave', () => {
        startAutoplay();
    });
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
        moveSlide(-1);
    } else if (e.key === 'ArrowRight') {
        moveSlide(1);
    }
});

// Touch/Swipe support for mobile
let touchStartX = 0;
let touchEndX = 0;

bannerSection.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
}, false);

bannerSection.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
}, false);

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            moveSlide(1); // Swipe left
        } else {
            moveSlide(-1); // Swipe right
        }
    }
}

// Pause autoplay when tab is not visible
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(autoplayInterval);
    } else {
        startAutoplay();
    }
});
</script>