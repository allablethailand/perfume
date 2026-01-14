<?php

$isProtocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
$isFile = ($isProtocol === 'http') ? '.php' : '';

$navbarItems = [
    [
        'icon' => '',
        'text' => '',
        'translate' => 'Home',
        'link' => 'index' . $isFile
    ],
    [
        'icon' => '',
        'text' => '',
        'translate' => 'About_us',
        'link' => 'about' . $isFile
    ],
    [
        'icon' => '',
        'text' => 'บริการ',
        'translate' => 'Service_t',
        'link' => 'service' . $isFile
    ],
    [
        'icon' => '',
        'text' => 'สินค้า',
        'translate' => 'product',
        'link' => 'shop' . $isFile
    ],
     [
        'icon' => '',
        'text' => 'insul',
        'translate' => 'insul',
        'link' => '#',
        'isDropdown' => true,
        'id' => 'dropdown3',
    ],
    [
        'icon' => '',
        'text' => 'ผลงาน',
        'translate' => 'performance',
        'link' => 'project' . $isFile
    ],
    [
        'icon' => '',
        'text' => 'บทความ',
        'translate' => 'article',
        'link' => '#' ,
        'isDropdown' => true,
        'id' => 'dropdown4',
    ],
    // [
    //     'icon' => '',
    //     'text' => 'Origami Platform',
    //     'translate' => '',
    //     'link' => '#',
    //     'isDropdown' => true,
    //     'id' => 'dropdown1',
    // ],
    // [
    //     'icon' => '',
    //     'text' => 'Origami AI',
    //     'translate' => '',
    //     'link' => '#',
    //     'isDropdown' => true,
    //     'id' => 'dropdown2',
    // ],
    // [
    //     'icon' => '',
    //     'text' => 'Allable Cloud',
    //     'translate' => '',
    //     'link' => 'allable_cloud' . $isFile
    // ],
    [
        'icon' => '',
        'text' => '',
        'translate' => 'News',
        'link' => 'news' . $isFile
    ],
    // [
    //     'icon' => '',
    //     'text' => '',
    //     'translate' => 'About_us',
    //     'link' => 'about' . $isFile
    // ],
    [
        'icon' => '',
        'text' => 'Contact us',
        'translate' => 'Contact_us',
        'link' => 'contact' . $isFile
    ],
];

// Dropdown items array
$dropdownItems = [
    // [
    //     'id' => 'dropdown1',
    //     'items' => [
    //         [
    //             'icon' => '',
    //             'text' => 'Human Resource Management',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Corporation Relationship Management',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Project Management',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Expense Management',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Document Management',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Event Management',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Asset Management',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Helpdesk Service',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Learning Management System',
    //             'translate' => '',
    //             'link' => '#'
    //         ],
    //     ]
    // ],
    // [
    //     'id' => 'dropdown2',
    //     'items' => [
    //         [
    //             'icon' => '',
    //             'text' => 'DevRev',
    //             'translate' => '',
    //             'link' => 'origami_ai' . $isFile
    //         ],
    //         [
    //             'icon' => '',
    //             'text' => 'Pricing',
    //             'translate' => '',
    //             'link' => 'pricing' . $isFile
    //         ],
    //     ]
    // ],
    [
        'id' => 'dropdown3',
        'items' => [
            [
                'icon' => '',
                'text' => 'INSUL Software',
                'translate' => '',
                'link' => 'INSULSoftware' . $isFile
            ],
            [
                'icon' => '',
                'text' => 'Download',
                'translate' => '',
                'link' => 'Download' . $isFile
            ],
            [
                'icon' => '',
                'text' => 'Instructions',
                'translate' => '',
                'link' => 'Instructions' . $isFile
            ],
        ]
    ],
    [
        'id' => 'dropdown4',
        'items' => [
            [
                'icon' => '',
                'text' => 'บทความ',
                'translate' => '',
                'link' => 'Blog' . $isFile
            ],
            [
                'icon' => '',
                'text' => 'ดีไซน์และไอเดีย',
                'translate' => '',
                'link' => 'idia' . $isFile
            ],
            [
                'icon' => '',
                'text' => 'วีดีโอ',
                'translate' => '',
                'link' => 'Video' . $isFile
            ],
        ]
    ],
];

?>

<div id="navbar-menu" onmouseleave="closeAllDropdowns()">
    <div class="container">
        <div class="over-menu">
            <?php foreach ($navbarItems as $item): ?>
                <?php if (isset($item['isDropdown']) && $item['isDropdown']): ?>
                    <div class="dropdown">
                        <a
                            href="<?php echo $item['link']; ?>"
                            class="dropbtn"
                            onmouseenter="toggleDropdown('<?php echo $item['id']; ?>')">
                            <i class="<?php echo $item['icon']; ?>"></i>
                            <span data-translate="<?php echo $item['translate']; ?>" lang="th">
                                <?php echo $item['text']; ?>
                            </span>
                            <span class="dropdown-icon">
                                <i class="fas fa-caret-down"></i>
                            </span>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $item['link']; ?>"
                        <?php echo isset($item['target']) ? 'target="' . $item['target'] . '"' : ''; ?>
                        onmouseenter="toggleDropdown()">
                        <i class="<?php echo $item['icon']; ?>"></i>
                        <span data-translate="<?php echo $item['translate']; ?>" lang="th">
                            <?php echo $item['text']; ?>
                        </span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <?php foreach ($dropdownItems as $dropdown): ?>
        <div class="dropdown-content" id="<?php echo $dropdown['id']; ?>" style="display:none;">
            <div class="dropdown-show">
                <?php foreach ($dropdown['items'] as $dropdownItem): ?>
                    <a class="dropdown-item" href="<?php echo $dropdownItem['link']; ?>">
                        <i class="<?php echo $dropdownItem['icon']; ?>"></i>
                        <span data-translate="<?php echo $dropdownItem['translate']; ?>" lang="th">
                            <?php echo $dropdownItem['text']; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="navbar-news">
    <div class="container">
        <div class="news-ticker">
            <span class="text-ticker">
                <span class="blinking-icon"></span>
                Daily News
            </span>
            <marquee id="newsMarquee" scrollamount="4">
                <div id="newsMarquee-link">
                    <a href="news.php">perfume Acoustics หนึ่งในวัสดุจากกลุ่ม Harmony เปิดตัวที่ Acoustics Solution For WELL Standard ที่ Harmony Club ในงาน INNOVATORX FORUM 2023</a>
                </div>
            </marquee>
        </div>
    </div>
</div>