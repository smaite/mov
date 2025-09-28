<?php
// Global configuration for Sasto Hub

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Site configuration
define('SITE_NAME', 'Sasto Hub');
define('SITE_URL', 'http://localhost/mov');
define('SITE_EMAIL', 'info@sastohub.com');

// Directory paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', SITE_URL . '/assets');

// Upload directories
define('PRODUCT_IMAGES_PATH', '/uploads/products');
define('USER_IMAGES_PATH', '/uploads/users');
define('VENDOR_IMAGES_PATH', '/uploads/vendors');

// Create upload directories if they don't exist
$uploadDirs = [
    ROOT_PATH . '/uploads',
    ROOT_PATH . '/uploads/products',
    ROOT_PATH . '/uploads/users',
    ROOT_PATH . '/uploads/vendors'
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Security settings
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Image settings
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Include database configuration
require_once __DIR__ . '/database.php';

// Utility functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirectTo($url) {
    if (!headers_sent()) {
        header("Location: " . SITE_URL . $url);
        exit();
    } else {
        echo "<script>window.location.href = '" . SITE_URL . $url . "';</script>";
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

function isAdmin() {
    return getUserType() === 'admin';
}

function isVendor() {
    return getUserType() === 'vendor';
}

function isCustomer() {
    return getUserType() === 'customer';
}

function formatPrice($price) {
    return 'Rs. ' . number_format($price, 2);
}

// Time ago function
function timeAgo($datetime, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    
    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uploadImage($file, $directory, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    $fileSize = $file['size'];
    $fileName = $file['name'];
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Check file size
    if ($fileSize > MAX_IMAGE_SIZE) {
        return false;
    }
    
    // Check file type
    if (!in_array($fileType, $allowedTypes)) {
        return false;
    }
    
    // Generate unique filename
    $newFileName = uniqid() . '.' . $fileType;
    $uploadPath = ROOT_PATH . $directory . '/' . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $directory . '/' . $newFileName;
    }
    
    return false;
}


// CSRF Protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Helper function to include components with proper path resolution
function includeComponent($componentPath, $data = []) {
    $fullPath = ROOT_PATH . '/' . ltrim($componentPath, '/');
    
    if (file_exists($fullPath)) {
        // Extract data variables for the component
        if (!empty($data)) {
            extract($data);
        }
        include $fullPath;
    } else {
        error_log("Component not found: " . $fullPath);
        echo "<!-- Component not found: " . htmlspecialchars($componentPath) . " -->";
    }
}

// Alternative function for product card specifically
function renderProductCard($product) {
    $productCardPath = ROOT_PATH . '/includes/product-card.php';
    
    if (file_exists($productCardPath)) {
        include $productCardPath;
    } else {
        echo "<!-- Product card template not found -->";
    }
}
?>
