<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit();
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

global $database;

switch ($action) {
    case 'add':
        $productId = intval($input['product_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 1);
        
        if ($productId <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            exit();
        }
        
        // Check if product exists and is active
        $product = $database->fetchOne("SELECT * FROM products WHERE id = ? AND status = 'active'", [$productId]);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit();
        }
        
        // Check stock
        if ($product['stock_quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit();
        }
        
        // Check if item already in cart
        $existingItem = $database->fetchOne(
            "SELECT * FROM cart WHERE user_id = ? AND product_id = ?", 
            [$_SESSION['user_id'], $productId]
        );
        
        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            if ($newQuantity > $product['stock_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more items than available stock']);
                exit();
            }
            
            $database->update('cart', 
                ['quantity' => $newQuantity], 
                'id = ?', 
                [$existingItem['id']]
            );
        } else {
            // Add new item
            $database->insert('cart', [
                'user_id' => $_SESSION['user_id'],
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }
        
        // Get updated cart count
        $cartCount = $database->count('cart', 'user_id = ?', [$_SESSION['user_id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart',
            'cart_count' => $cartCount
        ]);
        break;
        
    case 'update':
        $cartId = intval($input['cart_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 0);
        
        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit();
        }
        
        // Check if cart item belongs to user
        $cartItem = $database->fetchOne(
            "SELECT c.*, p.stock_quantity FROM cart c 
             JOIN products p ON c.product_id = p.id 
             WHERE c.id = ? AND c.user_id = ?", 
            [$cartId, $_SESSION['user_id']]
        );
        
        if (!$cartItem) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit();
        }
        
        if ($quantity <= 0) {
            // Remove item
            $database->delete('cart', 'id = ?', [$cartId]);
            $message = 'Item removed from cart';
        } else {
            // Check stock
            if ($quantity > $cartItem['stock_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit();
            }
            
            // Update quantity
            $database->update('cart', 
                ['quantity' => $quantity], 
                'id = ?', 
                [$cartId]
            );
            $message = 'Cart updated';
        }
        
        // Get updated cart count and total
        $cartCount = $database->count('cart', 'user_id = ?', [$_SESSION['user_id']]);
        $cartTotal = $database->fetchOne(
            "SELECT SUM(
                CASE 
                    WHEN p.sale_price IS NOT NULL AND p.sale_price < p.price 
                    THEN p.sale_price * c.quantity 
                    ELSE p.price * c.quantity 
                END
            ) as total
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?", 
            [$_SESSION['user_id']]
        );
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'cart_count' => $cartCount,
            'cart_total' => $cartTotal['total'] ?? 0
        ]);
        break;
        
    case 'remove':
        $productId = intval($input['product_id'] ?? 0);
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            exit();
        }
        
        $database->delete('cart', 'user_id = ? AND product_id = ?', [$_SESSION['user_id'], $productId]);
        
        $cartCount = $database->count('cart', 'user_id = ?', [$_SESSION['user_id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Item removed from cart',
            'cart_count' => $cartCount
        ]);
        break;
        
    case 'clear':
        $database->delete('cart', 'user_id = ?', [$_SESSION['user_id']]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cart cleared',
            'cart_count' => 0
        ]);
        break;
        
    case 'count':
        $cartCount = $database->count('cart', 'user_id = ?', [$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'count' => $cartCount]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
