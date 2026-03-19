<?php
// ป้องกัน browser cache หน้า admin
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

require_once(__DIR__ . '/../../lib/connect.php');
require_once(__DIR__ . '/../../lib/base_directory.php');
require_once(__DIR__ . '/../../lib/permissions.php');
checkSession($_SESSION);


global $base_path;
global $base_path_admin;

echo '<script>
    window.base_path = "' . $base_path . '";
    window.base_path_admin = "' . $base_path_admin . '";
</script>';

?>

