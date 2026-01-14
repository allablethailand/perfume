<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');
require_once(__DIR__ . '/../../../../lib/base_directory.php');
require_once(__DIR__ . '/../../../../lib/connect.php');
require_once(__DIR__ . '/../../../../inc/getFunctions.php');
require_once(__DIR__ . '/../../../../lib/permissions.php');

global $base_path;
global $base_path_admin;
global $conn;

$arrPermiss = checkPermissions($_SESSION);
$allowedPermiss_id = (isset($arrPermiss) && is_array($arrPermiss) && isset($arrPermiss['permiss_id']))
    ? explode(',', $arrPermiss['permiss_id'])
    : [];

$allowedPermiss = (isset($arrPermiss) && is_array($arrPermiss) && isset($arrPermiss['permissions']))
    ? explode(',', $arrPermiss['permissions'])
    : [];


$permissionsMap = array_combine($allowedPermiss, $allowedPermiss_id);

function insertIntoDatabase($conn, $table, $columns, $values)
{

    $placeholders = implode(', ', array_fill(0, count($values), '?'));

    $query = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ($placeholders)";

    $stmt = $conn->prepare($query);

    $types = str_repeat('s', count($values));
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        return 1;
    } else {
        return 0;
    }
}

function updateInDatabase($conn, $table, $columns, $values, $whereClause, $whereValues)
{

    $setPart = implode(', ', array_map(function ($col) {
        return "$col = ?";
    }, $columns));

    $query = "UPDATE $table SET $setPart WHERE $whereClause";

    $stmt = $conn->prepare($query);

    // Bind parameters
    $types = str_repeat('s', count($values)) . str_repeat('s', count($whereValues));
    $stmt->bind_param($types, ...array_merge($values, $whereValues));

    if ($stmt->execute()) {
        return 1;
    } else {
        return 0;
    }
}

function handleFileUpload($files)
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    $uploadResults = [];

    if (isset($files['name']) && is_array($files['name'])) {
        foreach ($files['name'] as $key => $fileName) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $fileTmpPath = $files['tmp_name'][$key];
                $fileSize = $files['size'][$key];
                $fileType = $files['type'][$key];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                if (in_array($fileExtension, $allowedExtensions) && $fileSize <= $maxFileSize) {
                    $uploadFileDir = '../../../../public/news_img/';
                    $destFilePath = $uploadFileDir . $fileName;

                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }

                    if (move_uploaded_file($fileTmpPath, $destFilePath)) {
                        $uploadResults[] = [
                            'success' => true,
                            'fileName' => $fileName,
                            'fileSize' => $fileSize,
                            'fileType' => $fileType,
                            'filePath' => $destFilePath
                        ];
                    } else {
                        $uploadResults[] = [
                            'success' => false,
                            'fileName' => $fileName,
                            'error' => 'Error occurred while moving the uploaded file.'
                        ];
                    }
                } else {
                    $uploadResults[] = [
                        'success' => false,
                        'fileName' => $fileName,
                        'error' => 'Invalid file type or file size exceeds limit.'
                    ];
                }
            } else {
                $uploadResults[] = [
                    'success' => false,
                    'fileName' => $fileName,
                    'error' => 'No file uploaded or there was an upload error.'
                ];
            }
        }
    } else {
        $uploadResults[] = [
            'success' => false,
            'error' => 'No files were uploaded.'
        ];
    }

    return $uploadResults;
}


$response = array('status' => 'error', 'message' => '');


try {

    if (isset($_POST['action']) && $_POST['action'] == 'getData_menu') {
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $searchValue = isset($_POST['search']['value']) ? $conn->real_escape_string($_POST['search']['value']) : '';

        $orderIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
        $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

        $columns = ['menu_order'];

        $whereClause = "del = 0";

        if (!empty($searchValue)) {
            $whereClause .= " AND (menu_label LIKE '%$searchValue%')";
        }

        $orderBy = $columns[$orderIndex] . " " . $orderDir;

        $dataQuery = "SELECT menu_id, 
                    parent_id,
                    menu_icon,
                    menu_label,
                    menu_link,
                    menu_order,
                    del,
                    '' as spc_icon
                    FROM ml_menus 
                    WHERE $whereClause
                    ORDER BY $orderBy
                    LIMIT $start, $length";

        $dataResult = $conn->query($dataQuery);
        $data = [];
        while ($row = $dataResult->fetch_assoc()) {
            $row['arrPermiss'] = $permissionsMap;
            $row['spc_icon'] = htmlspecialchars($row["menu_icon"]);
            $data[] = $row;
        }

        $Index = 'menu_id';
        $totalRecords = getTotalRecords($conn, 'ml_menus', $Index);
        $totalFiltered = getFilteredRecordsCount($conn, 'ml_menus', $whereClause, $Index);

        $response = [
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ];
    } else if (isset($_POST['action']) && $_POST['action'] == 'getMainMenu') {
        $searchTerm = isset($_POST['search']) ? $_POST['search'] : '';
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $perPage = 10;

        $getMenuQuery = "SELECT menu_id, menu_label FROM `ml_menus` WHERE parent_id = 0";
        $getMenuResult = mysqli_query($conn, $getMenuQuery);

        $allOptions = [];

        while ($row = mysqli_fetch_assoc($getMenuResult)) {
            $allOptions[] = [
                'id' => $row['menu_id'], // ชื่อคีย์ที่ส่งกลับ
                'text' => $row['menu_label']
            ];
        }

        $allOptions[] = [
            'id' => 0, // แก้ไขชื่อคีย์ที่เป็น 'id'
            'text' => 'Is main'
        ];

        $filteredOptions = array_filter($allOptions, function ($option) use ($searchTerm) {
            return stripos($option['text'], $searchTerm) !== false;
        });

        $totalCount = count($filteredOptions);
        $offset = ($page - 1) * $perPage;
        $paginatedOptions = array_slice($filteredOptions, $offset, $perPage);

        $response = [
            'items' => array_values($paginatedOptions),
            'total_count' => $totalCount
        ];
    } else if (isset($_POST['action']) && $_POST['action'] == 'upDateSortMenu') {

        $menuArray = $_POST['menuArray'];
        if (!empty($menuArray)) {

            $stmt = $conn->prepare("UPDATE ml_menus 
            SET menu_order = ?
            WHERE menu_id = ?");

            if (!$stmt) {
                throw new Exception("Prepare statement failed: " . $conn->error);
            }

            foreach ($menuArray as $news_array) {

                $menu_id = $news_array['id'];
                $menu_order = $news_array['newOrder'];

                $stmt->bind_param(
                    "ii",
                    $menu_order,
                    $menu_id
                );

                if (!$stmt->execute()) {
                    throw new Exception("Execute statement failed: " . $stmt->error);
                }
            }

            $response = array('status' => 'success', 'message' => 'successfully updated the rearrangement.');
        }
    } else if (isset($_POST['action']) && $_POST['action'] == 'saveMenu') {

        $stmt = $conn->prepare("INSERT INTO ml_menus 
                (parent_id, menu_icon, menu_label, menu_link, date_create) 
                VALUES (?, ?, ?, ?, ?)");

        $set_icon = $_POST['set_icon'] ?? '';
        $set_menu_name = $_POST['set_menu_name'];
        $set_menu_main = $_POST['set_menu_main'];
        $set_menu_path = $_POST['set_menu_path'];


        $current_date = date('Y-m-d H:i:s');

        $stmt->bind_param(
            "sssss",
            $set_menu_main,
            $set_icon,
            $set_menu_name,
            $set_menu_path,
            $current_date
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $last_inserted_id = $conn->insert_id;

        $response = array('status' => 'success', 'message' => 'Data saved successfully.');
    } else if (isset($_POST['action']) && $_POST['action'] == 'saveUpdateMenu') {

        $menu_id = $_POST['menu_id'];
        $menu_icon = $_POST['icon'];
        $menu_name = $_POST['menu_name'];
        $menu_path = $_POST['menu_path'];
        $menu_main = $_POST['menu_main'];

        $stmt = $conn->prepare("UPDATE ml_menus 
        SET parent_id = ?, 
            menu_icon = ?, 
            menu_label = ?, 
            menu_link = ? 
        WHERE menu_id = ?");
    
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
    
        $stmt->bind_param(
            "isssi", // i: integer, s: string
            $menu_main,
            $menu_icon,
            $menu_name,
            $menu_path,
            $menu_id
        );
    
        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $response = array('status' => 'success', 'message' => '');


    } else if (isset($_POST['action']) && $_POST['action'] == 'delMenu') {

        $menu_id = $_POST['menu_id'];
        $del = 1;

        $stmt = $conn->prepare("UPDATE ml_menus 
        SET del = ?
        WHERE menu_id = ?");

        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param(
            "ii",
            $del,
            $menu_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
        

        $response = array('status' => 'success', 'message' => '');
    }


} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();

echo json_encode($response);
