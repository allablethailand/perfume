<?php
// require_once('../lib/connect.php');
global $conn;

$sql = "SELECT youtube_id, title, description FROM videos WHERE show_on_homepage = 1 ORDER BY created_at DESC LIMIT 4";
$result = $conn->query($sql);
?>
<div class="row">
<?php 
if ($result->num_rows > 0) { 
    while ($row = $result->fetch_assoc()): 
        $youtube_id = htmlspecialchars($row['youtube_id']);
        $thumbnail_url = "https://img.youtube.com/vi/{$youtube_id}/hqdefault.jpg";
?>
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="ratio ratio-16x9 youtube-container" 
                 data-id="<?= $youtube_id ?>" 
                 data-title="<?= htmlspecialchars($row['title']) ?>"
                 style="background-image: url('<?= $thumbnail_url ?>'); cursor: pointer;">
                
                <img 
                    src="<?= $thumbnail_url ?>" 
                    alt="Play Video: <?= htmlspecialchars($row['title']) ?>"
                    class="youtube-placeholder"
                    loading="lazy"
                    style="width:100%; height:100%; object-fit: cover;"
                >
                <div class="youtube-play-button"></div>
                
                </div>
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                <p class="card-text text-truncate"><?= htmlspecialchars($row['description']) ?></p>
            </div>
        </div>
    </div>
<?php 
    endwhile; 
} else {
}
?>
</div>

<style>
.youtube-container {
    position: relative;
    background-size: cover;
    background-position: center;
}
.youtube-placeholder {
    transition: opacity 0.3s;
}
.youtube-container.loaded .youtube-placeholder {
    opacity: 0;
    pointer-events: none;
}
.youtube-play-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 68px;
    height: 48px;
    background: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 68 48'><path fill='%23f00' d='M66.52 7.74c-.78-2.93-2.49-5.41-5.64-6.28C55.8 0 34 0 34 0S12.2 0 7.12 1.46c-3.15.87-4.86 3.35-5.64 6.28C0 14.1 0 24 0 24s0 9.9 1.48 16.26c.78 2.93 2.49 5.41 5.64 6.28C12.2 48 34 48 34 48s21.8 0 26.88-1.46c3.15-.87 4.86-3.35 5.64-6.28C68 33.9 68 24 68 24s0-9.9-1.48-16.26z' /><path fill='%23fff' d='M45 24 27 15v18z' /></svg>") no-repeat center;
    pointer-events: none;
    opacity: 0.8;
    transition: opacity 0.3s;
}
.youtube-container:hover .youtube-play-button {
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const youtubeContainers = document.querySelectorAll('.youtube-container');
    youtubeContainers.forEach(container => {
        container.addEventListener('click', function() {
            if (this.classList.contains('loaded')) return; // ป้องกันการโหลดซ้ำ

            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            
            // สร้าง iframe
            const iframe = document.createElement('iframe');
            iframe.setAttribute('src', `https://www.youtube.com/embed/${id}?autoplay=1`); // เพิ่ม autoplay
            iframe.setAttribute('title', title);
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
            iframe.setAttribute('allowfullscreen', 'true');
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            this.innerHTML = '';
            this.appendChild(iframe);
            this.classList.add('loaded');
        });
    });
});
</script>