<?php
/**
 * Enhanced Sasto Hub Mobile API Endpoint
 * Comprehensive API with authentication, products, orders, reviews, wishlist
 */

require_once '../config/config.php';
require_once '../config/cors.php';
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'home';

// Enable session for authenticated endpoints
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    switch ($action) {
        // Authentication endpoints
        case 'login':
            requireAuth();
            $response = handleLogin($conn, $_POST);
            break;
            
        case 'register':
            requireAuth();
            $response = handleRegister($conn, $_POST);
            break;
            
        case 'logout':
            requireAuth();
            $response = handleLogout();
            break;
            
        case 'profile':
            requireAuth();
            $response = getProfile($conn);
            break;
            
        case 'update_profile':
            requireAuth();
            $response = updateProfile($conn, $_POST);
            break;
            
        // Product endpoints
        case 'home':
            $response = getHomeData($conn);
            break;
            
        case 'products':
            $categoryId = $_GET['category'] ?? null;
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $sort = $_GET['sort'] ?? 'latest';
            $response = getProducts($conn, $categoryId, $page, $limit, $sort);
            break;
            
        case 'product':
            $productId = $_GET['id'] ?? null;
            if (!$productId) {
                throw new Exception('Product ID is required');
            }
            $response = getProductDetails($conn, $productId);
            break;
            
        case 'search':
            $query = $_GET['q'] ?? '';
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $response = searchProducts($conn, $query, $page, $limit);
            break;
            
        case 'categories':
            $response = getCategories($conn);
            break;
            
        case 'reviews':
            $productId = $_GET['product_id'] ?? null;
            $page = $_GET['page'] ?? 1;
            $response = getProductReviews($conn, $productId, $page);
            break;
            
        case 'add_review':
            requireAuth();
            $response = addProductReview($conn, $_POST);
            break;
            
        // Cart endpoints
        case 'cart':
            requireAuth();
            $response = getCart($conn);
            break;
            
        case 'add_to_cart':
            requireAuth();
            $response = addToCart($conn, $_POST);
            break;
            
        case 'update_cart':
            requireAuth();
            $response = updateCartItem($conn, $_POST);
            break;
            
        case 'remove_from_cart':
            requireAuth();
            $response = removeFromCart($conn, $_POST);
            break;
            
        case 'clear_cart':
            requireAuth();
            $response = clearCart($conn);
            break;
            
        // Wishlist endpoints
        case 'wishlist':
            requireAuth();
            $response = getWishlist($conn);
            break;
            
        case 'toggle_wishlist':
            requireAuth();
            $response = toggleWishlist($conn, $_POST);
            break;
            
        // Order endpoints
        case 'orders':
            requireAuth();
            $page = $_GET['page'] ?? 1;
            $status = $_GET['status'] ?? null;
            $response = getOrders($conn, $page, $status);
            break;
            
        case 'order_details':
            requireAuth();
            $orderId = $_GET['id'] ?? null;
            $response = getOrderDetails($conn, $orderId);
            break;
            
        case 'create_order':
            requireAuth();
            $response = createOrder($conn, $_POST);
            break;
            
        // Notification endpoints
        case 'notifications':
            requireAuth();
            $response = getNotifications($conn);
            break;
            
        case 'mark_notification_read':
            requireAuth();
            $response = markNotificationRead($conn, $_POST);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Check if user is authenticated
 */
function requireAuth() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }
}

/**
 * Handle user login
 */
function handleLogin($conn, $data) {
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }
    
    $sql = "SELECT * FROM users WHERE email = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password');
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['user_type'] = $user['user_type'];
    
    return [
        'success' => true,
        'user' => formatUser($user)
    ];
}

/**
 * Handle user registration
 */
function handleRegister($conn, $data) {
    $firstName = sanitizeInput($data['first_name'] ?? '');
    $lastName = sanitizeInput($data['last_name'] ?? '');
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $phone = sanitizeInput($data['phone'] ?? '');
    $userType = sanitizeInput($data['user_type'] ?? 'customer');
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        throw new Exception('First name, last name, email, and password are required');
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Email already exists');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (first_name, last_name, email, password, phone, user_type, status, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $phone, $userType]);
    
    $userId = $conn->lastInsertId();
    
    return [
        'success' => true,
        'message' => 'Registration successful. Please wait for approval.',
        'user_id' => $userId
    ];
}

/**
 * Handle logout
 */
function handleLogout() {
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

/**
 * Get user profile
 */
function getProfile($conn) {
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    return [
        'success' => true,
        'user' => formatUser($user)
    ];
}

/**
 * Update user profile
 */
function updateProfile($conn, $data) {
    $userId = $_SESSION['user_id'];
    $firstName = sanitizeInput($data['first_name'] ?? '');
    $lastName = sanitizeInput($data['last_name'] ?? '');
    $phone = sanitizeInput($data['phone'] ?? '');
    $address = sanitizeInput($data['address'] ?? '');
    
    $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, updated_at = NOW() 
              WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$firstName, $lastName, $phone, $address, $userId]);
    
    return ['success' => true, 'message' => 'Profile updated successfully'];
}

/**
 * Get enhanced home data
 */
function getHomeData($conn) {
    $data = [
        'success' => true,
        'featured_products' => [],
        'categories' => [],
        'flash_sale' => [
            'active' => true,
            'end_time' => '2025-11-19 23:59:59',
            'discount' => 50
        ],
        'deal_of_day' => null
    ];
    
    // Get featured products with images
    $sql = "SELECT p.*, pi.image_url as primary_image,
                   (SELECT GROUP_CONCAT(pi2.image_url) FROM product_images pi2 WHERE pi2.product_id = p.id AND pi2.is_primary = 0) as additional_images
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN users u ON p.vendor_id = u.id 
            WHERE p.status = 'active' AND p.featured = 1
            ORDER BY p.created_at DESC 
            LIMIT 10";
    
    $stmt = $conn->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['featured_products'][] = formatProduct($row);
    }
    
    // Get categories with images
    $data['categories'] = getCategories($conn)['categories'];
    
    // Get deal of day
    $dealSql = "SELECT p.*, pi.image_url as primary_image
                  FROM products p 
                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                  WHERE p.status = 'active' AND p.sale_price IS NOT NULL
                  ORDER BY RAND() 
                  LIMIT 1";
    $dealStmt = $conn->query($dealSql);
    $dealProduct = $dealStmt->fetch(PDO::FETCH_ASSOC);
    if ($dealProduct) {
        $data['deal_of_day'] = formatProduct($dealProduct);
    }
    
    return $data;
}

/**
 * Get products with pagination and sorting
 */
function getProducts($conn, $categoryId = null, $page = 1, $limit = 20, $sort = 'latest') {
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.*, pi.image_url as primary_image,
                   (SELECT GROUP_CONCAT(pi2.image_url) FROM product_images pi2 WHERE pi2.product_id = p.id AND pi2.is_primary = 0) as additional_images
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN users u ON p.vendor_id = u.id 
            WHERE p.status = 'active'";
    
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND p.category_id = ?";
        $params[] = $categoryId;
    }
    
    // Add sorting
    switch ($sort) {
        case 'price_low':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY p.rating DESC";
            break;
        case 'name':
            $sql .= " ORDER BY p.name ASC";
            break;
        default:
            $sql .= " ORDER BY p.created_at DESC";
            break;
    }
    
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = formatProduct($row);
    }
    
    return [
        'success' => true,
        'products' => $products,
        'pagination' => [
            'current_page' => (int)$page,
            'per_page' => (int)$limit,
            'has_more' => count($products) === $limit
        ]
    ];
}

/**
 * Get enhanced product details
 */
function getProductDetails($conn, $productId) {
    $sql = "SELECT p.*, u.username as vendor_name, u.email as vendor_email,
                   pi.image_url as primary_image,
                   (SELECT GROUP_CONCAT(pi2.image_url) FROM product_images pi2 WHERE pi2.product_id = p.id AND pi2.is_primary = 0) as additional_images
            FROM products p 
            LEFT JOIN users u ON p.vendor_id = u.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.id = ? AND p.status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$productId]);
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Get product reviews
    $reviewSql = "SELECT r.*, u.first_name, u.last_name 
                  FROM reviews r 
                  LEFT JOIN users u ON r.user_id = u.id 
                  WHERE r.product_id = ? AND r.status = 'approved'
                  ORDER BY r.created_at DESC 
                  LIMIT 5";
    $reviewStmt = $conn->prepare($reviewSql);
    $reviewStmt->execute([$productId]);
    
    $reviews = [];
    while ($review = $reviewStmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = [
            'id' => $review['id'],
            'rating' => (int)$review['rating'],
            'title' => $review['title'],
            'comment' => $review['comment'],
            'user_name' => $review['first_name'] . ' ' . $review['last_name'],
            'created_at' => $review['created_at'],
            'helpful_count' => (int)$review['helpful_count']
        ];
    }
    
    // Get related products
    $relatedSql = "SELECT p.*, pi.image_url as primary_image
                    FROM products p 
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
                    ORDER BY RAND() 
                    LIMIT 6";
    $relatedStmt = $conn->prepare($relatedSql);
    $relatedStmt->execute([$product['category_id'], $productId]);
    
    $relatedProducts = [];
    while ($related = $relatedStmt->fetch(PDO::FETCH_ASSOC)) {
        $relatedProducts[] = formatProduct($related);
    }
    
    return [
        'success' => true,
        'product' => formatProduct($product),
        'reviews' => $reviews,
        'related_products' => $relatedProducts
    ];
}

/**
 * Search products with pagination
 */
function searchProducts($conn, $query, $page = 1, $limit = 20) {
    $offset = ($page - 1) * $limit;
    
    if (empty($query)) {
        return [
            'success' => true,
            'products' => [],
            'query' => $query,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'has_more' => false
            ]
        ];
    }
    
    $searchTerm = "%{$query}%";
    $sql = "SELECT p.*, pi.image_url as primary_image,
                   (SELECT GROUP_CONCAT(pi2.image_url) FROM product_images pi2 WHERE pi2.product_id = p.id AND pi2.is_primary = 0) as additional_images
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN users u ON p.vendor_id = u.id 
            WHERE p.status = 'active' 
            AND (p.name LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
    
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = formatProduct($row);
    }
    
    return [
        'success' => true,
        'products' => $products,
        'query' => $query,
        'pagination' => [
            'current_page' => (int)$page,
            'per_page' => (int)$limit,
            'has_more' => count($products) === $limit
        ]
    ];
}

/**
 * Get all categories
 */
function getCategories($conn) {
    $data = [
        'success' => true,
        'categories' => []
    ];
    
    $sql = "SELECT c.*, COUNT(p.id) as product_count
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            GROUP BY c.id 
            ORDER BY c.name ASC";
    $stmt = $conn->query($sql);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['categories'][] = [
            'id' => (string)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'image' => $row['image'] ?? null,
            'product_count' => (int)$row['product_count']
        ];
    }
    
    return $data;
}

/**
 * Get product reviews
 */
function getProductReviews($conn, $productId, $page = 1) {
    $offset = ($page - 1) * 10;
    
    $sql = "SELECT r.*, u.first_name, u.last_name 
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC 
            LIMIT 10 OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$productId, $offset]);
    
    $reviews = [];
    while ($review = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = [
            'id' => $review['id'],
            'rating' => (int)$review['rating'],
            'title' => $review['title'],
            'comment' => $review['comment'],
            'user_name' => $review['first_name'] . ' ' . $review['last_name'],
            'created_at' => $review['created_at'],
            'helpful_count' => (int)$review['helpful_count']
        ];
    }
    
    return [
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => (int)$page,
            'per_page' => 10,
            'has_more' => count($reviews) === 10
        ]
    ];
}

/**
 * Add product review
 */
function addProductReview($conn, $data) {
    $userId = $_SESSION['user_id'];
    $productId = sanitizeInput($data['product_id'] ?? '');
    $rating = sanitizeInput($data['rating'] ?? '');
    $title = sanitizeInput($data['title'] ?? '');
    $comment = sanitizeInput($data['comment'] ?? '');
    
    if (empty($productId) || empty($rating) || $rating < 1 || $rating > 5) {
        throw new Exception('Product ID, rating (1-5) are required');
    }
    
    // Check if user purchased the product
    $purchaseSql = "SELECT oi.id FROM order_items oi 
                     JOIN orders o ON oi.order_id = o.id 
                     WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'paid'";
    $purchaseStmt = $conn->prepare($purchaseSql);
    $purchaseStmt->execute([$userId, $productId]);
    
    if (!$purchaseStmt->fetch()) {
        throw new Exception('You must purchase this product before reviewing');
    }
    
    // Check if already reviewed
    $existingSql = "SELECT id FROM reviews WHERE user_id = ? AND product_id = ?";
    $existingStmt = $conn->prepare($existingSql);
    $existingStmt->execute([$userId, $productId]);
    
    if ($existingStmt->fetch()) {
        throw new Exception('You have already reviewed this product');
    }
    
    // Insert review
    $sql = "INSERT INTO reviews (product_id, user_id, rating, title, comment, status, created_at) 
              VALUES (?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$productId, $userId, $rating, $title, $comment]);
    
    return ['success' => true, 'message' => 'Review submitted for approval'];
}

/**
 * Get cart items
 */
function getCart($conn) {
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT c.*, p.name, p.price, pi.image_url 
            FROM cart c 
            LEFT JOIN products p ON c.product_id = p.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    
    $items = [];
    $total = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $itemTotal = $row['price'] * $row['quantity'];
        $total += $itemTotal;
        
        $items[] = [
            'id' => $row['id'],
            'product_id' => $row['product_id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'image' => $row['image_url'],
            'total' => $itemTotal
        ];
    }
    
    return [
        'success' => true,
        'items' => $items,
        'total_items' => count($items),
        'total_amount' => $total
    ];
}

/**
 * Add item to cart
 */
function addToCart($conn, $data) {
    $userId = $_SESSION['user_id'];
    $productId = sanitizeInput($data['product_id'] ?? '');
    $quantity = sanitizeInput($data['quantity'] ?? 1);
    
    if (empty($productId) || $quantity < 1) {
        throw new Exception('Product ID and quantity are required');
    }
    
    // Check if already in cart
    $sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $productId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $newQuantity = $existing['quantity'] + $quantity;
        $updateSql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$newQuantity, $existing['id']]);
    } else {
        $insertSql = "INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([$userId, $productId, $quantity]);
    }
    
    return ['success' => true, 'message' => 'Product added to cart'];
}

/**
 * Get wishlist
 */
function getWishlist($conn) {
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT w.*, p.name, p.price, pi.image_url 
            FROM wishlist w 
            LEFT JOIN products p ON w.product_id = p.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    
    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = [
            'id' => $row['id'],
            'product_id' => $row['product_id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'image' => $row['image_url'],
            'created_at' => $row['created_at']
        ];
    }
    
    return [
        'success' => true,
        'items' => $items
    ];
}

/**
 * Toggle wishlist item
 */
function toggleWishlist($conn, $data) {
    $userId = $_SESSION['user_id'];
    $productId = sanitizeInput($data['product_id'] ?? '');
    
    if (empty($productId)) {
        throw new Exception('Product ID is required');
    }
    
    // Check if exists
    $sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $productId]);
    
    if ($stmt->fetch()) {
        // Remove from wishlist
        $deleteSql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->execute([$userId, $productId]);
        
        return ['success' => true, 'message' => 'Removed from wishlist', 'in_wishlist' => false];
    } else {
        // Add to wishlist
        $insertSql = "INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([$userId, $productId]);
        
        return ['success' => true, 'message' => 'Added to wishlist', 'in_wishlist' => true];
    }
}

/**
 * Get user orders
 */
function getOrders($conn, $page = 1, $status = null) {
    $userId = $_SESSION['user_id'];
    $offset = ($page - 1) * 10;
    
    $sql = "SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.user_id = ?";
    
    $params = [$userId];
    
    if ($status) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY o.created_at DESC LIMIT 10 OFFSET ?";
    $params[] = $offset;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    $orders = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $orders[] = [
            'id' => $row['id'],
            'order_number' => $row['order_number'],
            'total_amount' => (float)$row['total_amount'],
            'status' => $row['status'],
            'payment_status' => $row['payment_status'],
            'created_at' => $row['created_at'],
            'item_count' => (int)$row['item_count']
        ];
    }
    
    return [
        'success' => true,
        'orders' => $orders,
        'pagination' => [
            'current_page' => (int)$page,
            'per_page' => 10,
            'has_more' => count($orders) === 10
        ]
    ];
}

/**
 * Get notifications
 */
function getNotifications($conn) {
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    
    $notifications = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notifications[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['type'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at']
        ];
    }
    
    return [
        'success' => true,
        'notifications' => $notifications
    ];
}

/**
 * Format user data
 */
function formatUser($user) {
    return [
        'id' => (string)$user['id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'user_type' => $user['user_type'],
        'profile_image' => $user['profile_image'],
        'created_at' => $user['created_at']
    ];
}

/**
 * Format product data for API response
 */
function formatProduct($row) {
    $primaryImage = $row['primary_image'] ?? null;
    $additionalImages = $row['additional_images'] ? explode(',', $row['additional_images']) : [];
    
    return [
        'id' => (string)$row['id'],
        'name' => $row['name'],
        'description' => $row['description'] ?? '',
        'price' => (float)$row['price'],
        'sale_price' => isset($row['sale_price']) ? (float)$row['sale_price'] : null,
        'image' => $primaryImage,
        'images' => array_filter([$primaryImage, ...$additionalImages]),
        'category' => (string)($row['category_id'] ?? ''),
        'category_name' => $row['category_name'] ?? '',
        'vendor_id' => (string)($row['vendor_id'] ?? ''),
        'vendor_name' => $row['vendor_name'] ?? '',
        'stock_quantity' => (int)($row['stock_quantity'] ?? 0),
        'rating' => isset($row['rating']) ? (float)$row['rating'] : 4.0,
        'review_count' => (int)($row['review_count'] ?? 0),
        'is_featured' => (bool)($row['is_featured'] ?? false),
        'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s')
    ];
}
?>