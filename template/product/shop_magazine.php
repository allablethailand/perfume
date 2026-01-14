<?php
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'th']) ? $_GET['lang'] : 'th';
$lang_suffix = ($lang === 'en') ? '_en' : '';
$subject_col = "subject_shop" . $lang_suffix;
$description_col = "description_shop" . $lang_suffix;
$content_col = "content_shop" . $lang_suffix;

$magazineArray = [];

$sql = "SELECT 
    dn.shop_id, 
    dn.`$subject_col` AS subject_shop, 
    dn.`$description_col` AS description_shop,
    dn.`$content_col` AS content_shop, 
    dn.date_create,
    GROUP_CONCAT(dnc.file_name) AS file_name,
    GROUP_CONCAT(dnc.api_path) AS pic_path
FROM 
    dn_shop dn
LEFT JOIN 
    dn_shop_doc dnc ON dn.shop_id = dnc.shop_id AND dnc.status = '1' AND dnc.del = '0'
WHERE 
    dn.del = '0'
GROUP BY 
    dn.shop_id
ORDER BY dn.date_create DESC
LIMIT 5";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $content = $row['content_shop'];
        $image = '';
        $iframe = '';

        if (preg_match('/<iframe.*?src=["\'](.*?)["\'].*?>/i', $content, $matches)) {
            $iframe = isset($matches[1]) ? $matches[1] : '';
        } else {
            $paths = explode(',', $row['pic_path']);
            $image = isset($paths[0]) ? $paths[0] : '';
        }

        $magazineArray[] = [
            'id' => $row['shop_id'],
            'image' => $image,
            'category' => '',
            'date_time' => $row['date_create'],
            'title' => $row['subject_shop'],
            'description' => $row['description_shop'],
            'iframe' => $iframe,
            'url' => 'shop_detail.php?id=' . urlencode(base64_encode($row['shop_id'])) . '&lang=' . $lang,
        ];
    }
}
?>

<div class="row">
    <?php if (count($magazineArray) > 0): ?>
        <div class="col-12 col-md-6">
            <div class="overflow">
                <div class="image-wrapper zoom">
                    <div class="caption-top">
                        <a href="<?php echo $magazineArray[0]['url'] ?>" class="line-clamp" style="font-size: 18px;">
                            <?php echo htmlspecialchars($magazineArray[0]['title']); ?>
                        </a>
                    </div>
                    <?php if (!empty($magazineArray[0]['iframe'])): ?>
                        <iframe frameborder="0" src="<?php echo htmlspecialchars($magazineArray[0]['iframe']); ?>" width="100%" height="100%" class="note-video-clip"></iframe>
                    <?php elseif (!empty($magazineArray[0]['image'])): ?>
                        <img class="img-fluid" src="<?php echo htmlspecialchars($magazineArray[0]['image']); ?>" alt="<?php echo htmlspecialchars($magazineArray[0]['title']); ?>">
                    <?php else: ?>
                        <div class="placeholder-image" style="background-color: #f0f0f0; height: 100%;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (count($magazineArray) > 1): ?>
            <div class="col-12 col-md-6">
                <div class="row">
                    <?php foreach (array_slice($magazineArray, 1) as $mgzArr): ?>
                        <div class="col-6 pb-1 pt-0 pr-1 mt-1">
                            <div class="overflow">
                                <div class="image-wrapper zoom">
                                    <div class="caption-top">
                                        <a href="<?php echo $mgzArr['url'] ?>" class="line-clamp">
                                            <?php echo htmlspecialchars($mgzArr['title']); ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($mgzArr['iframe'])): ?>
                                        <iframe frameborder="0" src="<?php echo htmlspecialchars($mgzArr['iframe']); ?>" width="100%" height="100%" class="note-video-clip"></iframe>
                                    <?php elseif (!empty($mgzArr['image'])): ?>
                                        <img class="img-fluid" src="<?php echo htmlspecialchars($mgzArr['image']); ?>" alt="<?php echo htmlspecialchars($mgzArr['title']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image" style="background-color: #f0f0f0; height: 100%;"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="col-12">
            <p><?= ($lang === 'en') ? 'No shop available.' : 'ไม่มีสินค้าที่พร้อมใช้งาน'; ?></p>
        </div>
    <?php endif; ?>
</div>