<?php
require_once('lib/connect.php');
global $conn;
?>
<!DOCTYPE html>
<html>

<head>

    <?php include 'template/header.php' ?>
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
    </style>
</head>

<body>
    <ul id="flag-dropdown-list" class="flag-dropdown" style="left: 74%;">
    </ul>
    <?php include 'template/navbar_slide.php' ?>

    <div class="content-sticky" id="">
        <div class="container" style="max-width: 90%;">
            <div class="box-content">
                <div class="row">

                    <div class="">
                        <h2 style="font-size: 28px; font-weight: bold;" data-translate="blog" lang="th">Blog</h2>
                        <?php include 'template/Blog/content.php' ?>
                    </div>

                    <div class="col-md-3">

                    </div>

                </div>

            </div>
        </div>
    </div>

    <?php include 'template/footer.php' ?>

    <script src="index_.js?v=<?php echo time(); ?>"></script>
    <script src="app/js/news/news_.js?v=<?php echo time(); ?>"></script>

</body>

</html>