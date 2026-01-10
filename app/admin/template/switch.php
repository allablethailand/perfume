<?php
    session_start();
    $auth_url = $_SESSION['redirect_url'];
    $oid      = $_SESSION['oid'];
    if ($oid) {
        $redirectUrl = rtrim($auth_url, '/') . '/' . urlencode($oid);
        header("Location: $redirectUrl");
        exit();
    }