<?php
// ดึงข่าวล่าสุด
$newsList = [];
$sql = "SELECT news_id, subject_news, subject_news_en, subject_news_cn, subject_news_jp, subject_news_kr 
        FROM dn_news WHERE del = 0 ORDER BY date_create DESC LIMIT 5";
if (isset($conn)) {
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $newsList[] = $row;
        }
    }
}
?>

<style>
    /* ============================================
       NEWS SECTION - LUXURY MINIMALIST DESIGN
       ============================================ */
    
    .luxury-news-section {
        position: relative;
        background: white;
        border-bottom: 1px solid rgba(10, 10, 10, 0.05);
        overflow: hidden;
    }

    .news-ticker-container {
        display: flex;
        align-items: center;
        padding: 15px 60px;
        gap: 30px;
    }

    /* News Label */
    .news-label {
        flex: 0 0 auto;
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: var(--luxury-black);
        padding-right: 30px;
        border-right: 1px solid rgba(10, 10, 10, 0.1);
        position: relative;
    }

    .news-label::before {
        content: '';
        position: absolute;
        left: -10px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background: var(--luxury-black);
        border-radius: 50%;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }
        50% {
            opacity: 0.5;
            transform: translateY(-50%) scale(1.2);
        }
    }

    /* News Ticker */
    .news-ticker-wrapper {
        flex: 1;
        overflow: hidden;
        position: relative;
    }

    .news-ticker {
        display: flex;
        gap: 80px;
        animation: scroll-news 40s linear infinite;
        white-space: nowrap;
    }

    .news-ticker:hover {
        animation-play-state: paused;
    }

    @keyframes scroll-news {
        0% {
            transform: translateX(0);
        }
        100% {
            transform: translateX(-50%);
        }
    }

    .news-item {
        display: inline-flex;
        align-items: center;
        gap: 15px;
        color: var(--luxury-gray);
        text-decoration: none;
        font-family: 'Montserrat', sans-serif;
        font-size: 12px;
        font-weight: 300;
        letter-spacing: 0.05em;
        transition: color 0.3s ease;
    }

    .news-item:hover {
        color: var(--luxury-black);
    }

    .news-item::before {
        content: '';
        width: 4px;
        height: 4px;
        background: var(--luxury-gray);
        border-radius: 50%;
        transition: background 0.3s ease;
    }

    .news-item:hover::before {
        background: var(--luxury-black);
    }

    /* News Controls */
    .news-controls {
        flex: 0 0 auto;
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .news-control-btn {
        width: 30px;
        height: 30px;
        border: 1px solid rgba(10, 10, 10, 0.1);
        background: transparent;
        color: var(--luxury-gray);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 12px;
    }

    .news-control-btn:hover {
        border-color: var(--luxury-black);
        color: var(--luxury-black);
        background: rgba(10, 10, 10, 0.02);
    }

    .news-view-all {
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 400;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--luxury-black);
        text-decoration: none;
        padding: 8px 20px;
        border: 1px solid rgba(10, 10, 10, 0.2);
        transition: all 0.3s ease;
    }

    .news-view-all:hover {
        background: var(--luxury-black);
        color: white;
    }

    /* ============================================
       NEWS GRID - FEATURED SECTION
       ============================================ */
    
    .news-featured-section {
        padding: 80px 60px;
        background: white;
    }

    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .section-subtitle {
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 400;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: var(--luxury-gray);
        margin-bottom: 15px;
    }

    .section-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 42px;
        font-weight: 400;
        letter-spacing: 0.02em;
        color: var(--luxury-black);
        margin-bottom: 20px;
    }

    .section-description {
        font-family: 'Montserrat', sans-serif;
        font-size: 14px;
        font-weight: 300;
        line-height: 1.8;
        color: var(--luxury-gray);
        max-width: 600px;
        margin: 0 auto;
    }

    /* News Grid */
    .news-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px;
    }

    .news-card {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: transform 0.4s var(--transition-smooth);
    }

    .news-card:hover {
        transform: translateY(-10px);
    }

    .news-card-image {
        position: relative;
        padding-top: 133.33%; /* 3:4 ratio */
        overflow: hidden;
        margin-bottom: 25px;
        background: #f5f5f5;
    }

    .news-card-image img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s var(--transition-smooth);
    }

    .news-card:hover .news-card-image img {
        transform: scale(1.05);
    }

    .news-card-category {
        font-family: 'Montserrat', sans-serif;
        font-size: 10px;
        font-weight: 500;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: var(--luxury-gray);
        margin-bottom: 12px;
    }

    .news-card-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 24px;
        font-weight: 500;
        line-height: 1.4;
        color: var(--luxury-black);
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-card-excerpt {
        font-family: 'Montserrat', sans-serif;
        font-size: 13px;
        font-weight: 300;
        line-height: 1.7;
        color: var(--luxury-gray);
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-card-meta {
        display: flex;
        align-items: center;
        gap: 20px;
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 300;
        letter-spacing: 0.05em;
        color: var(--luxury-gray);
    }

    .news-card-date::before {
        content: '•';
        margin-right: 8px;
    }

    .news-card-readmore {
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        font-weight: 400;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--luxury-black);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 20px;
        transition: gap 0.3s ease;
    }

    .news-card:hover .news-card-readmore {
        gap: 12px;
    }

    .news-card-readmore::after {
        content: '→';
        font-size: 14px;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .news-featured-section {
            padding: 60px 40px;
        }

        .news-ticker-container {
            padding: 15px 40px;
        }

        .news-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 35px;
        }
    }

    @media (max-width: 768px) {
        .news-featured-section {
            padding: 50px 20px;
        }

        .news-ticker-container {
            padding: 12px 20px;
            gap: 20px;
        }

        .news-label {
            font-size: 10px;
            padding-right: 15px;
        }

        .news-ticker {
            gap: 50px;
        }

        .news-item {
            font-size: 11px;
        }

        .news-controls {
            display: none;
        }

        .section-title {
            font-size: 32px;
        }

        .news-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
    }

    @media (max-width: 480px) {
        .section-title {
            font-size: 28px;
        }

        .news-card-title {
            font-size: 20px;
        }
    }
</style>

<!-- NEWS TICKER SECTION -->
<section class="luxury-news-section">
    <div class="news-ticker-container">
        <div class="news-label">
            Latest News
        </div>
        
        <div class="news-ticker-wrapper">
            <div class="news-ticker" id="newsTicker">
                <?php 
                // แสดงข่าว 2 รอบเพื่อให้เลื่อนได้ต่อเนื่อง
                for ($i = 0; $i < 2; $i++): 
                    foreach ($newsList as $news): 
                ?>
                    <a href="?news&id=<?= $news['news_id'] ?>" class="news-item">
                        <?= htmlspecialchars($news['subject_news']) ?>
                    </a>
                <?php 
                    endforeach;
                endfor;
                ?>
            </div>
        </div>

        <div class="news-controls">
            <button class="news-control-btn" id="pauseNews" aria-label="Pause news ticker">
                <i class="fas fa-pause"></i>
            </button>
            <a href="?news" class="news-view-all">View All</a>
        </div>
    </div>
</section>

<!-- NEWS FEATURED SECTION (Optional - สำหรับแสดงข่าวเด่น) -->
<section class="news-featured-section">
    <div class="section-header">
        <p class="section-subtitle">Discover</p>
        <h2 class="section-title">Latest Stories</h2>
        <p class="section-description">
            Explore our latest articles, insights, and updates from the world of luxury design
        </p>
    </div>

    <div class="news-grid">
        <?php 
        // ดึงข่าวเด่น 3 รายการ
        $featuredNews = array_slice($newsList, 0, 3);
        foreach ($featuredNews as $news): 
        ?>
            <article class="news-card">
                <a href="?news&id=<?= $news['news_id'] ?>">
                    <div class="news-card-image">
                        <img src="public/img/news-placeholder.jpg" 
                             alt="<?= htmlspecialchars($news['subject_news']) ?>" 
                             loading="lazy"
                             width="400" 
                             height="533">
                    </div>
                    <div class="news-card-content">
                        <p class="news-card-category">News</p>
                        <h3 class="news-card-title">
                            <?= htmlspecialchars($news['subject_news']) ?>
                        </h3>
                        <p class="news-card-excerpt">
                            Discover the latest insights and trends in luxury design and innovation...
                        </p>
                        <div class="news-card-meta">
                            <span class="news-card-author">perfume</span>
                            <span class="news-card-date"><?= date('M d, Y') ?></span>
                        </div>
                        <span class="news-card-readmore">Read More</span>
                    </div>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<script>
// News Ticker Control
const newsTicker = document.getElementById('newsTicker');
const pauseNewsBtn = document.getElementById('pauseNews');
let isPaused = false;

if (pauseNewsBtn) {
    pauseNewsBtn.addEventListener('click', () => {
        isPaused = !isPaused;
        
        if (isPaused) {
            newsTicker.style.animationPlayState = 'paused';
            pauseNewsBtn.innerHTML = '<i class="fas fa-play"></i>';
        } else {
            newsTicker.style.animationPlayState = 'running';
            pauseNewsBtn.innerHTML = '<i class="fas fa-pause"></i>';
        }
    });
}
</script>