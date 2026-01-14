<?php
require_once('lib/connect.php');
global $conn;

$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    } else {
        unset($_SESSION['lang']);
    }
} else {
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang'];
    }
}

$texts = [
    'video_library' => [
        'th' => 'คลังวิดีโอทั้งหมด',
        'en' => 'All Videos',
        'cn' => '所有视频',
        'jp' => 'すべての動画',
        'kr' => '모든 동영상'
    ],
    'search_placeholder' => [
        'th' => 'ค้นหาวิดีโอ...',
        'en' => 'Search videos...',
        'cn' => '搜索视频...',
        'jp' => '動画を検索...',
        'kr' => '동영상 검색...'
    ],
    'no_videos_found' => [
        'th' => 'ไม่พบวิดีโอในขณะนี้',
        'en' => 'No videos found at this time',
        'cn' => '目前没有视频',
        'jp' => '現在動画が見つかりません',
        'kr' => '현재 동영상이 없습니다'
    ],
    'error_db_connect' => [
        'th' => 'ERROR: ไม่สามารถเชื่อมต่อฐานข้อมูลได้',
        'en' => 'ERROR: Cannot connect to the database',
        'cn' => '错误: 无法连接到数据库',
        'jp' => 'エラー: データベースに接続できません',
        'kr' => '오류: 데이터베이스에 연결할 수 없습니다'
    ],
    'error_db_query' => [
        'th' => 'ERROR: ข้อผิดพลาดในการเรียกข้อมูลจากฐานข้อมูล',
        'en' => 'ERROR: Error fetching data from the database',
        'cn' => '错误: 从数据库获取数据出错',
        'jp' => 'エラー: データベースからのデータ取得エラー',
        'kr' => '오류: 데이터베이스에서 데이터를 가져오는 중 오류가 발생했습니다'
    ]
];

function getTextByLang($key)
{
    global $texts, $lang;
    return $texts[$key][$lang] ?? $texts[$key]['th'];
}

?>
<!DOCTYPE html>
<html>
<?php include 'template/header.php' ?>
<head>
    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="app/css/news_.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            display: inline-block;
            margin: 0 5px;
            padding: 0px 10px;
            text-decoration: none;
            color: #555;
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s;
        }

        .pagination a:hover {
            background-color: #f1f1f1;
            color: #ffa719;
        }

        .pagination a.active {
            background-color: #ffa719;
            color: white;
            border: 1px solid #ffa719;
        }

        .pagination a[disabled] {
            color: #ccc;
            pointer-events: none;
            border-color: #ccc;
        }

        .btn-search {
            border: none;
            background-color: #ffa719;
            color: #ffffff;
            border-radius: 0px 10px 10px 0px;
        }

        a {
            color: #ffff;
        }

        .header-top a {
            color: #ffffff;
            text-align: center;
            padding: 8px;
            text-decoration: none;
            font-size: 14px;
            line-height: 25px;
        }

        #navbar-menu {
            background-color: white;
            position: relative;
            z-index: 999;
            border-bottom: 1px solid #ddd;
            overflow: visible;
            background-color: #ff9900;
        }

        .container {
            position: relative;
            overflow: visible;
        }


        .over-menu {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 6px 0;
            overflow: visible;
        }

        .over-menu a {
            text-decoration: none;
            padding: 10px 15px;
            color: #333;
            font-weight: 500;
            position: relative;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            min-width: 180px;
            max-width: 220px;
            border-radius: 4px;
        }

        .dropdown-show {
            display: flex;
            flex-direction: column;
        }

        .dropdown-item {
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
        }

        .dropdown-item:hover {
            background-color: #f0f0f0;
        }

        .dropbtn {
            cursor: pointer;
        }

        .card-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4em;
            max-height: calc(1.4em * 2);
        }
        a {
            color: #fff;
        }
    </style>

</head>
<?php


include 'template/navbar_slide.php';
if (!$conn) {
    die(getTextByLang('error_db_connect') . ": " . mysqli_connect_error());
}

$sql = "SELECT youtube_id, title, description FROM videos ORDER BY created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die(getTextByLang('error_db_query') . ": " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">

<head>
    <link rel="icon" type="image/x-icon" href="../../../public/img/q-removebg-preview1.png">
    <link rel="icon" href="../../../public/img/q-removebg-preview1.png" type="image/png">
    <script>
        function filterVideos() {
            const input = document.getElementById('videoSearchInput');
            const filter = input.value.toLowerCase();
            const cards = document.querySelectorAll('.video-card');

            cards.forEach(card => {
                const title = card.querySelector('.card-title').innerText.toLowerCase();
                const description = card.querySelector('.card-text').innerText.toLowerCase();

                if (title.includes(filter) || description.includes(filter)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
    <?php include 'inc_head.php' ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <ul id="flag-dropdown-list" class="flag-dropdown" style="left: 74%;">
    </ul>
    <div class="container" style="max-width: 90%;">
        <div class="row align-items-center" style="padding: 40px 0;">
            <div class="col-md-6">
                <h2 style="font-size: 28px; font-weight: bold; margin: 0; color: #555;">
                    <?= getTextByLang('video_library') ?>
                </h2>
            </div>
            <div class="col-md-6 text-end">
                <div
                    style="display: inline-flex; align-items: center; border: 1px solid #ccc; border-radius: 4px; overflow: hidden;">
                    <input type="text" id="videoSearchInput" placeholder="<?= getTextByLang('search_placeholder') ?>"
                        onkeyup="filterVideos()"
                        style="padding: 8px 12px; font-size: 16px; border: none; outline: none; width: 250px;">
                    <button type="button" onclick="filterVideos()"
                        style="background-color: #f37021; border: none; padding: 8px 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-search" style="color: white; font-size: 18px;"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row">
            <?php
            if ($result->num_rows > 0):
                while ($v = $result->fetch_assoc()):
                    ?>
                    <div class="col-md-3 mb-4 video-card">
                        <div class="card h-100">
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($v['youtube_id']) ?>"
                                    title="<?= htmlspecialchars($v['title']) ?>" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                                </iframe>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($v['title']) ?></h6>
                                <p class="card-text"><?= htmlspecialchars($v['description']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            else:
                ?>
                <div class="col-12 text-center">
                    <p class="alert alert-info">
                        <?= getTextByLang('no_videos_found') ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'template/footer.php' ?>
    <script src="index_.js?v=<?php echo time(); ?>"></script>
</body>

</html>