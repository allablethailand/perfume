<?php
/**
 * Test File Paths
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Path Test</h1>";

$files = [
    __DIR__ . '/../../lib/connect.php',
    __DIR__ . '/../../lib/jwt_helper.php',
    __DIR__ . '/../../lib/aimodelmanager.php',
    __DIR__ . '/../../lib/AIModelManager.php',
    __DIR__ . '/../../lib/devrev_manager.php',
    __DIR__ . '/../../lib/DevRevManager.php',
    __DIR__ . '/../../lib/send_mail.php',
];

foreach ($files as $file) {
    $exists = file_exists($file);
    $status = $exists ? '✅ EXISTS' : '❌ NOT FOUND';
    $realpath = $exists ? realpath($file) : 'N/A';
    
    echo "<p><strong>{$status}</strong><br>";
    echo "Looking for: {$file}<br>";
    echo "Real path: {$realpath}</p>";
    echo "<hr>";
}

echo "<h2>Current Directory</h2>";
echo "<p>" . __DIR__ . "</p>";

echo "<h2>Lib Directory Contents</h2>";
$lib_dir = __DIR__ . '/../../lib';
if (is_dir($lib_dir)) {
    $files = scandir($lib_dir);
    echo "<pre>";
    print_r($files);
    echo "</pre>";
} else {
    echo "<p>❌ Directory not found: {$lib_dir}</p>";
}
?>