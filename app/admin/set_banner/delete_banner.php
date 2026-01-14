<?php
require_once('../../../lib/connect.php');

$id = $_GET['id'] ?? 0;
$conn->query("DELETE FROM banner WHERE id = $id");

header("Location: list_banner.php");
exit;
