<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/cors.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

global $database;

switch ($action) {
    case 'toggle':
        if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit();
        }
        
        $productId = intval($input['product_id'] ?? 0);
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            exit();
        }
        
        // Check if product exists
        $product = $database->fetchOne("SELECT id FROM products WHERE id = ? AND status = 'active'", [$productId]);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit();
        }
        
        // Check if already in wishlist
        $existingItem = $database->fetchOne(
            "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?", 
            [$_SESSION['user_id'], $productId]
        );
        
        if ($existingItem) {
            // Remove from wishlist
            $database->delete('wishlist', 'id = ?', [$existingItem['id']]);
            $message = 'Removed from wishlist';
        } else {
            // Add to wishlist
            $database->insert('wishlist', [
                'user_id' => $_SESSION['user_id'],
                'product_id' => $productId
            ]);
            $message = 'Added to wishlist';
        }
        
        // Get updated wishlist count
        $wishlistCount = $database->count('wishlist', 'user_id = ?', [$_SESSION['user_id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'wishlist_count' => $wishlistCount,
            'in_wishlist' => !$existingItem
        ]);
        break;
        
    case 'remove':
        if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit();
        }
        
        $productId = intval($input['product_id'] ?? 0);
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            exit();
        }
        
        $database->delete('wishlist', 'user_id = ? AND product_id = ?', [$_SESSION['user_id'], $productId]);
        
        $wishlistCount = $database->count('wishlist', 'user_id = ?', [$_SESSION['user_id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Removed from wishlist',
            'wishlist_count' => $wishlistCount
        ]);
        break;
        
    case 'count':
        $wishlistCount = $database->count('wishlist', 'user_id = ?', [$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'count' => $wishlistCount]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
