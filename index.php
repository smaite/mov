<?php
require_once 'config/config.php';

// Simple routing system
$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? 'index';

// Define valid pages and their corresponding files
$validPages = [
    'home' => 'pages/home.php',
    'login' => 'pages/auth/login.php',
    'register' => 'pages/auth/register.php',
    'logout' => 'pages/auth/logout.php',
    'products' => 'pages/products/index.php',
    'product' => 'pages/products/details.php',
    'category' => 'pages/products/category.php',
    'search' => 'pages/products/search.php',
    'cart' => 'pages/cart/index.php',
    'checkout' => 'pages/checkout/index.php',
    'orders' => 'pages/orders/index.php',
    'profile' => 'pages/user/profile.php',
    'wishlist' => 'pages/user/wishlist.php',
    'vendor' => 'pages/vendor/dashboard.php',
    'admin' => 'pages/admin/dashboard.php'
];

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    $ajaxPage = $_GET['ajax'];
    $ajaxFile = "ajax/{$ajaxPage}.php";
    
    if (file_exists($ajaxFile)) {
        require_once $ajaxFile;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Ajax endpoint not found']);
    }
    exit();
}

// Check if page exists
if (!array_key_exists($page, $validPages) || !file_exists($validPages[$page])) {
    $page = '404';
    $pageFile = 'pages/404.php';
} else {
    $pageFile = $validPages[$page];
}

// Include header for non-auth pages
$noLayoutPages = ['login', 'register', 'logout'];
if (!in_array($page, $noLayoutPages)) {
    include 'includes/header.php';
}

// Include the requested page
include $pageFile;

// Include footer for non-auth pages
if (!in_array($page, $noLayoutPages)) {
    include 'includes/footer.php';
}
?>
