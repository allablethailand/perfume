<?php
session_start();

/*
|--------------------------------------------------------------------------
| 1. Determine environment: Production vs Localhost
|--------------------------------------------------------------------------
*/
$host = $_SERVER['HTTP_HOST'];

// Default allowed root path
$allowedRoot = '/perfume/';
$allowedIndex = '/perfume/'; #/index.php

// If running on localhost (your dev URL)
if (strpos($host, 'localhost') !== false) {
    $allowedRoot = '/perfume/';
    $allowedIndex = '/perfume/';
}

/*
|--------------------------------------------------------------------------
| 2. SECURITY: Block fake-path spam URLs
|--------------------------------------------------------------------------
*/
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($requestPath !== $allowedRoot && $requestPath !== $allowedIndex) {
    error_log("Blocked SPAM URL: ".$_SERVER['REQUEST_URI']);
    header("HTTP/1.1 404 Not Found");
    exit("404 Not Found");
}

/*
|--------------------------------------------------------------------------
| 3. Extract Query Parameters Safely
|--------------------------------------------------------------------------
*/
$data = $_SERVER['REQUEST_URI'];
$params = [];
$fragment = '';

if (strpos($data, '?') !== false) {
    $parts = explode('?', $data, 2);
    parse_str($parts[1], $params);
}

if (!empty($params)) {
    $fragment = key($params);
}

/*
|--------------------------------------------------------------------------
| 4. SECURE: Whitelist allowed ROUTES
|--------------------------------------------------------------------------
*/
$allowedRoutes = [
    'about', 'Blog_detail', 'Blog', 'contact', 'Download',
    'idia_detail', 'idia', 'Instructions', 'INSULSoftware',
    'news_detail', 'news', 'otp_confirm', 'project_detail',
    'project', 'register', 'service', 'product_detail',
    'product', 'googleb6dd9f2aa59b820c.html', 'Video',
    'preview', 'orders','ai_activation','ai_questions', 'ai_chat','order_detail', 'payment', 'cart' ,'checkout','privacy','termofuse' , 'profile', 'lang'
];

if ($fragment !== '' && !in_array($fragment, $allowedRoutes)) {
    error_log("Blocked invalid fragment: $fragment");
    header("HTTP/1.1 404 Not Found");
    exit("404 Not Found");
}

/*
|--------------------------------------------------------------------------
| 5. Page Routing
|--------------------------------------------------------------------------
*/
switch($fragment) {

    case 'about': require __DIR__.'/views/about.php'; break;
    case 'Blog_detail': require __DIR__.'/views/Blog_detail.php'; break;
    case 'Blog': require __DIR__.'/views/Blog.php'; break;
    case 'contact': require __DIR__.'/views/contact.php'; break;
    case 'Download': require __DIR__.'/views/Download.php'; break;
    case 'idia_detail': require __DIR__.'/views/idia_detail.php'; break;
    case 'idia': require __DIR__.'/views/idia.php'; break;
    case 'Instructions': require __DIR__.'/views/Instructions.php'; break;
    case 'INSULSoftware': require __DIR__.'/views/INSULSoftware.php'; break;
    case 'news_detail': require __DIR__.'/views/news_detail.php'; break;
    case 'news': require __DIR__.'/views/news.php'; break;
    case 'otp_confirm': require __DIR__.'/views/otp_confirm.php'; break;
    case 'project_detail': require __DIR__.'/views/project_detail.php'; break;
    case 'project': require __DIR__.'/views/project.php'; break;
    case 'register': require __DIR__.'/views/register.php'; break;
    case 'service': require __DIR__.'/views/service.php'; break;
    case 'product_detail': require __DIR__.'/views/product_detail.php'; break;
    case 'product': require __DIR__.'/views/product.php'; break;
    case 'googleb6dd9f2aa59b820c.html': require __DIR__.'/googleb6dd9f2aa59b820c.html'; break;
    case 'Video': require __DIR__.'/views/Video.php'; break;
    case 'profile': require __DIR__.'/views/profile.php'; break;
    case 'cart': require __DIR__.'/views/cart.php'; break;
    case 'checkout': require __DIR__.'/views/checkout.php'; break;
    case 'orders': require __DIR__.'/views/orders.php'; break;
    case 'order_detail': require __DIR__.'/views/order_detail.php'; break;
    case 'payment': require __DIR__.'/views/payment.php'; break;
    case 'preview': require __DIR__.'/views/preview.html'; break;
    case 'privacy': require __DIR__.'/views/privacy.php'; break;
    case 'termofuse': require __DIR__.'/views/termofuse.php'; break;
    case 'ai_activation': require __DIR__.'/views/ai_activation.php'; break;
    case 'ai_questions': require __DIR__.'/views/ai_questions.php'; break;
    case 'ai_chat': require __DIR__.'/views/ai_chat.php'; break;
    default: require __DIR__.'/views/homepage.php'; break;
}
