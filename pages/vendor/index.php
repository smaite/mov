<?php
/**
 * Vendor Dashboard Router
 * Main entry point for all vendor functionality
 * Handles routing to different vendor sections
 */

$pageTitle = 'Vendor Dashboard';
$pageDescription = 'Manage your products and orders';

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    header('Location: ?page=login');
    exit();
}

global $database;

// Get vendor info
$vendor = $database->fetchOne("SELECT * FROM vendors WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$vendor) {
    header('Location: ?page=register&type=vendor');
    exit();
}

// Check if vendor is verified
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'active') {
    include __DIR__ . '/verification-pending.php';
    return;
}

// Handle different vendor sections
$section = $_GET['section'] ?? 'dashboard';

// Include common header and sidebar components
include __DIR__ . '/vendor-header.php';

switch ($section) {
    case 'products':
        include __DIR__ . '/products.php';
        break;
    case 'orders':
        include __DIR__ . '/orders.php';
        break;
    case 'analytics':
        include __DIR__ . '/analytics.php';
        break;
    case 'profile':
        include __DIR__ . '/profile.php';
        break;
    case 'dashboard':
    default:
        include __DIR__ . '/dashboard.php';
        break;
}

// Include common footer
include __DIR__ . '/vendor-footer.php';