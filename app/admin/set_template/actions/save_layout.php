<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once(__DIR__ . '/../../../../lib/base_directory.php');

global $base_path_admin;
global $base_path;

$data = json_decode(file_get_contents('php://input'), true);
$htmlContent = $data['htmlContent'];

$inc_cdn = '';

$inc_cdn .= '
<link rel="icon" type="image/x-icon" href="../public/img/q-removebg-preview1.png">

    <link href="../../../inc/jquery/css/jquery-ui.css" rel="stylesheet">

    <script src="../../../inc/jquery/js/jquery-3.6.0.min.js"></script>
    <script src="../../../inc/jquery/js/jquery-ui.min.js"></script>

    <link href="../../../inc/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../../inc/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../../inc/bootstrap/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/fontawesome5-fullcss@1.1.0/css/all.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <link href="../../../inc/sweetalert2/css/sweetalert2.min.css" rel="stylesheet">
    <script src="../../../inc/sweetalert2/js/sweetalert2.all.min.js"></script>

    <link href="../../../inc/select2/css/select2.min.css" rel="stylesheet">
    <script src="../../../inc/select2/js/select2.min.js"></script>

    <link href="../../../inc/summernote/summernote-lite.min.css" rel="stylesheet">
    <script src="../../../inc/summernote/summernote-lite.min.js"></script>

    <link href="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.1.4/datatables.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-iconpicker/1.10.0/css/bootstrap-iconpicker.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-iconpicker/1.10.0/js/bootstrap-iconpicker.bundle.min.js"></script>


    <link href="../css/index_.css" rel="stylesheet">
';



$inc_cdn .= "
<style>

.controls {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px;
    background-color: #f0f0f0;
    /* flex: 0 0 200px;  */
    border-right: 2px solid #ddd;
}


.dropzone {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 15px;
    flex-grow: 1;
    overflow-y: auto;
    background-color: #fafafa;
    border: 2px dashed #ccc;
    border-radius: 8px;
}

.target-row {
    display: grid;
    gap: 10px;
    width: 100%;
    cursor: pointer; 
    min-height: 30px;
    background-color: #e0f7fa;
}

.target-cell {
    border: 1px solid #ddd;
    background-color: #e0f7fa;
    border-radius: 4px;
    padding: 8px;
    position: relative;
    transition: background-color 0.3s ease;
    height: 60px; 
    width: 100%; 
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden; 
}

.target-cell input,
.target-cell button,
.target-cell select,
.target-cell textarea {
    width: 100%;
    height: 100%; 
    box-sizing: border-box; 
    overflow: hidden;
}

.target-cell textarea {
    resize: none; 
}
.target-cell:hover {
    background-color: #b2ebf2;
}


.selected {
    background-color: #ffcc80;
    box-shadow: 1px 2px 4px rgba(255, 204, 128, 0.6);
    border-color: #ffa726;
}

.draggable {
    padding: 10px;
    border: 1px solid #ccc;
    background-color: #fff;
    border-radius: 4px;
}

.draggable:active {
    background-color: #ececec;
    cursor: grabbing;
}

</style>
";

$inc_script = "
<script src='../js/index_.js'></script>
";

//get conition
if(false){
$inc_script .="
<script>
    var '?';
    $(document).ready(function() {
        '?' = new DataTable('#?', {
            autoWidth: false,
            language: {
                decimal: '',
                emptyTable: 'No data available in table',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from MAX total entries)',
                loadingRecords: 'Loading...',
                search: 'Search:',
                zeroRecords: 'No matching records found',
                aria: {
                    orderable: 'Order by this column',
                    orderableReverse: 'Reverse order this column'
                }
            },
            processing: true,
            serverSide: true,
            ajax: {
                url: '?',
                method: 'POST',
                dataType: 'json',
                data: function(d) {
                    d.action = '?';
                },
                dataSrc: function(json) {
                    return json.data;
                }
            },
            ordering: false,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            drawCallback: function(settings) {
                var targetDivTable = $('div.dt-layout-row.dt-layout-table');
                if (targetDivTable.length) {
                    targetDivTable.addClass('tables-overflow').css({
                        display: 'block',
                        width: '100%'
                    });
                }

                var targetDivRow = $('dt-container dt-layout-row dt-empty-footer');
                if (targetDivRow.length) {
                    targetDivRow.css('width', '50%');
                }
            },
            initComplete: function(settings, json) {
                const headers = [
                    '?',
                    '?'
                ];
                cssResponsiveTable('?', headers);
            },
            rowCallback: function(row, data, index) {
                // Add any row-specific customization here
            }
        });
    });
</script>
";
}

$inc_header = "
<?php include '../template/header.php'?>
";

$inc_php = "
<?php
include('../../../lib/permissions.php');
include('../../../lib/base_directory.php');
// checkPermissions();
?>
";


$position = strpos($htmlContent, '<!DOCTYPE html>');
if ($position !== false) {
    $htmlContent = substr_replace($htmlContent, $inc_php, $position, 0);
}

$htmlContent = str_replace('</head>', $inc_cdn . '</head>', $htmlContent);

$htmlContent = str_replace('<body>', $inc_header . '<body>', $htmlContent);
$htmlContent = str_replace('</body>', $inc_script . '</body>', $htmlContent);

// print_r($htmlContent);
// exit;

$mainFolder = $data['mainFolder'];
$subFolder = $data['subFolder'];
$fileName = $data['fileName'] ? $data['fileName'] : 'layuot';

if ($mainFolder && !$subFolder) {
    $filePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $mainFolder . DIRECTORY_SEPARATOR . $fileName.'.php';
} elseif ($mainFolder && $subFolder) {
    $filePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $mainFolder . DIRECTORY_SEPARATOR . $subFolder . DIRECTORY_SEPARATOR . $fileName.'.php';
} else {
    echo "Invalid folder information.";
    exit;
}


if (file_put_contents($filePath, $htmlContent) !== false) {
    echo "File saved successfully to: " . $filePath;
} else {
    echo "Failed to save the file.";
}





// Construct the file URL based on the directory structure
// $fileUrl = '/downloads/' . $mainFolder . '/' . $subFolder . '/layout.html';

// // Return the URL of the saved file
// echo json_encode(['fileUrl' => $fileUrl]);

?>
