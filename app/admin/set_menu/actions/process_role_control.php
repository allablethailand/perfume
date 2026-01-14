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

    if (isset($_POST['action']) && $_POST['action'] == 'getMenu') {

        $stmt = $conn->prepare("SELECT menu_id, menu_label, menu_icon FROM `ml_menus` WHERE del = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $del = 0;
        $stmt->bind_param("i", $del);

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $menus = array();

        while ($row = $result->fetch_assoc()) {
            $menus[] = $row;
        }

        $response = array(
            'status' => 'success',
            'message' => '',
            'data' => $menus
        );
    } else if (isset($_POST['action']) && $_POST['action'] == 'getRole') {

        $stmt = $conn->prepare("SELECT role_id, role_type FROM mb_roles WHERE del = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $del = 0;
        $stmt->bind_param("i", $del);

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $roles = array();

        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }

        $response = array(
            'status' => 'success',
            'message' => '',
            'data' => $roles
        );
    } else if (isset($_POST['action']) && $_POST['action'] == 'getPermissions') {

        $stmt = $conn->prepare("SELECT permiss_id, permiss_type FROM mb_permissions WHERE del = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $del = 0;
        $stmt->bind_param("i", $del);

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $permiss = array();

        while ($row = $result->fetch_assoc()) {
            $permiss[] = $row;
        }

        $response = array(
            'status' => 'success',
            'message' => '',
            'data' => $permiss
        );
    } else if (isset($_POST['action']) && $_POST['action'] == 'getRolePermiss') {

        $stmt = $conn->prepare("SELECT role_id, permiss_id FROM acc_role_permissions WHERE del = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $del = 0;
        $stmt->bind_param("i", $del);

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $permiss = array();

        while ($row = $result->fetch_assoc()) {
            $permiss[] = $row;
        }

        $response = array(
            'status' => 'success',
            'message' => '',
            'data' => $permiss
        );
    } else if (isset($_POST['action']) && $_POST['action'] == 'getMenuPermiss') {

        $stmt = $conn->prepare("SELECT role_id, menu_id FROM acc_menu_permissions WHERE del = ?");
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $del = 0;
        $stmt->bind_param("i", $del);

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $permiss = array();

        while ($row = $result->fetch_assoc()) {
            $permiss[] = $row;
        }

        $response = array(
            'status' => 'success',
            'message' => '',
            'data' => $permiss
        );
    } else if (isset($_POST['action']) && $_POST['action'] == 'saveRoleControl') {

        $menus = isset($_POST['menus']) ? $_POST['menus'] : [];
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

        if (!empty($menus)) {
            $processedMenuIds = [];

            foreach ($menus as $menu) {

                $uniqueKeyMenu = $menu['role_id'] . '_' . $menu['menu_id'];

                if (in_array($uniqueKeyMenu, $processedMenuIds)) {
                    continue;
                }

                $processedMenuIds[] = $uniqueKeyMenu;

                $stmt = $conn->prepare("SELECT COUNT(id) as count FROM acc_menu_permissions WHERE role_id = ? AND menu_id = ?");

                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }

                $stmt->bind_param("ii", $menu['role_id'], $menu['menu_id']);
                $stmt->execute();

                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['count'] > 0) {
                    $delValue = $menu['isChecked'] ? 1 : 0;
                    $stmt = $conn->prepare("UPDATE acc_menu_permissions SET del = ? WHERE role_id = ? AND menu_id = ?");
                    $stmt->bind_param("iii", $delValue, $menu['role_id'], $menu['menu_id']);
                    $stmt->execute();

                    if ($stmt->error) {
                        throw new Exception("Execute statement failed: " . $stmt->error);
                    }
                } else {
                    $delValue = $menu['isChecked'] ? 1 : 0;
                    $stmt = $conn->prepare("INSERT INTO acc_menu_permissions (role_id, menu_id, del) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $menu['role_id'], $menu['menu_id'], $delValue);
                    $stmt->execute();

                    if ($stmt->error) {
                        throw new Exception("Execute statement failed: " . $stmt->error);
                    }
                }
            }
        }

        if (!empty($permissions)) {
            $processedPermissionIds = [];
            
            foreach ($permissions as $permission) {

                $uniqueKeyPermiss = $permission['role_id'] . '_' . $permission['permiss_id'];

                if (in_array($uniqueKeyPermiss, $processedPermissionIds)) {
                    continue;
                }

                $processedPermissionIds[] = $uniqueKeyPermiss;

                $stmt = $conn->prepare("SELECT COUNT(id) as count FROM acc_role_permissions WHERE role_id = ? AND permiss_id = ?");

                if (!$stmt) {
                    throw new Exception("Prepare statement failed: " . $conn->error);
                }

                $stmt->bind_param("ii", $permission['role_id'], $permission['permiss_id']);
                $stmt->execute();

                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['count'] > 0) {
                    $permissionValue = $permission['isChecked'] ? 1 : 0;
                    $stmt = $conn->prepare("UPDATE acc_role_permissions SET del = ? WHERE role_id = ? AND permiss_id = ?");
                    $stmt->bind_param("iii", $permissionValue, $permission['role_id'], $permission['permiss_id']);
                    $stmt->execute();

                    if ($stmt->error) {
                        throw new Exception("Execute statement failed: " . $stmt->error);
                    }
                } else {
                    $permissionValue = $permission['isChecked'] ? 1 : 0;
                    $stmt = $conn->prepare("INSERT INTO acc_role_permissions (role_id, permiss_id, del) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $permission['role_id'], $permission['permiss_id'], $permissionValue);
                    $stmt->execute();

                    if ($stmt->error) {
                        throw new Exception("Execute statement failed: " . $stmt->error);
                    }
                }
            }
        }

        $response = array(
            'status' => 'success',
            'message' => '',
        );
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
