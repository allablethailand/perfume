<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Companion Management</title>

    <link rel="icon" type="image/x-icon" href="../../../public/product_images/695e0bf362d49_1767771123.jpg">
    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">
    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>
    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>
    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>
    <link href='../css/index_.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <link href='css/ai-companion.css?v=<?php echo time(); ?>' rel='stylesheet'>
</head>

<?php 
include '../check_permission.php';

global $conn;

$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    }
} else {
    if (isset($_SESSION['lang'])) {
        $lang = $_SESSION['lang'];
    }
}

include '../template/header.php';
?>

<body>
    <div class="content-sticky">
        <div class="container-fluid">
            <div class="page-header">
                <h4>
                    <i class="fas fa-robot"></i>
                    AI Companion Management
                </h4>
                <button type='button' id='btnAddAI' class='btn btn-primary'>
                    <i class='fas fa-plus-circle'></i>
                    Add New AI Companion
                </button>
            </div>

            <div class="card">
                <div class="card-body">
                    <table id="td_list_project" class="table table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="8%">AI Avatar</th>
                                <th width="12%">AI Code</th>
                                <th width="15%">AI Name</th>
                                <th width="15%">Serial Number</th>
                                <th width="10%">Users</th>
                                <th width="10%">Status</th>
                                <th width="12%">Created</th>
                                <th width="13%">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <script src='../js/index_.js?v=<?php echo time(); ?>'></script>
    <script src='js/ai-companion.js?v=<?php echo time(); ?>'></script>
</body>
</html>