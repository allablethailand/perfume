<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";

// Test database
require_once('lib/connect.php');
if ($conn) {
    echo "Database: Connected ✓<br>";
} else {
    echo "Database: Failed ✗<br>";
}

// Test files
$files = [
    'lib/connect.php',
    'lib/send_mail.php',
    'vendor/autoload.php',
    'app/actions/otp_confirm_email.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "$file: Found ✓<br>";
    } else {
        echo "$file: NOT FOUND ✗<br>";
    }
}