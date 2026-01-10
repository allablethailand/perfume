<?php

$shop = [
    [
        'image' => '',
        'title' => 'Origami Platforms',
        'description' => '',
        'header' => ''
    ],
    [
        'image' => '',
        'title' => 'Origami AI',
        'description' => '',
        'header' => ''
    ]
];
?>

<div class="owl-carousel">
    <?php foreach ($shop as $item): ?>
        <div class="item">
            <div class="custom-card">

                <div class="custom-card-header">
                    <h5><?php echo $item['header']; ?></h5>
                </div>

                <div class="custom-card-img overflow">
                  <div class="zoom">
                    <img class="img-fluid" src="<?php echo $item['image']; ?>" alt="shop Image">
                  </div>
                </div>

                <div class="custom-card-body">
                    <p class="custom-card-title"><?php echo $item['title']; ?></p>
                    <!-- <p class="custom-card-text"><?php echo $item['description']; ?></p> -->
                </div>

            </div>
        </div>
    <?php endforeach; ?>
</div>
