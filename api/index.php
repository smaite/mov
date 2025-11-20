<?php
/**
 * Sasto Hub Mobile API Endpoint
 * Returns JSON data for Flutter app
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

// Get user ID from session or token
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} elseif (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
}

try {
    switch ($action) {
        // Home & Discovery
        case 'home':
            $response = getHomeData($conn);
            break;
            
        case 'products':
            $categoryId = $_GET['category'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $sortBy = $_GET['sort'] ?? 'created_at';
            $order = $_GET['order'] ?? 'DESC';
            $response = getProducts($conn, $categoryId, $page, $limit, $sortBy, $order);
            break;
            
        case 'product':
            $productId = $_GET['id'] ?? null;
            if (!$productId) {
                throw new Exception('Product ID is required');
            }
            $response = getProductDetails($conn, $productId, $userId);
            break;
            
        case 'search':
            $query = $_GET['q'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $response = searchProducts($conn, $query, $page, $limit);
            break;
            
        case 'categories':
            $response = getCategories($conn);
            break;
            
        // User Authentication
        case 'login':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $response = loginUser($conn, $email, $password);
            break;
            
        case 'register':
            $userData = json_decode(file_get_contents('php://input'), true);
            $response = registerUser($conn, $userData);
            break;
            
        case 'logout':
            $response = logoutUser();
            break;
            
        case 'profile':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $response = getUserProfile($conn, $userId);
            break;
            
        case 'update_profile':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $profileData = json_decode(file_get_contents('php://input'), true);
            $response = updateUserProfile($conn, $userId, $profileData);
            break;
            
        // Shopping Cart
        case 'cart':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            if ($method === 'GET') {
                $response = getCart($conn, $userId);
            } elseif ($method === 'POST') {
                $cartData = json_decode(file_get_contents('php://input'), true);
                $response = addToCart($conn, $userId, $cartData);
            } elseif ($method === 'PUT') {
                $cartData = json_decode(file_get_contents('php://input'), true);
                $response = updateCartItem($conn, $userId, $cartData);
            } elseif ($method === 'DELETE') {
                $productId = $_GET['product_id'] ?? null;
                $response = removeFromCart($conn, $userId, $productId);
            }
            break;
            
        case 'clear_cart':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $response = clearCart($conn, $userId);
            break;
            
        // Wishlist
        case 'wishlist':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            if ($method === 'GET') {
                $response = getWishlist($conn, $userId);
            } elseif ($method === 'POST') {
                $wishlistData = json_decode(file_get_contents('php://input'), true);
                $response = addToWishlist($conn, $userId, $wishlistData);
            } elseif ($method === 'DELETE') {
                $productId = $_GET['product_id'] ?? null;
                $response = removeFromWishlist($conn, $userId, $productId);
            }
            break;
            
        // Orders
        case 'orders':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $response = getOrders($conn, $userId);
            break;
            
        case 'order':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $orderId = $_GET['id'] ?? null;
            if (!$orderId) {
                throw new Exception('Order ID is required');
            }
            $response = getOrderDetails($conn, $userId, $orderId);
            break;
            
        case 'create_order':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $orderData = json_decode(file_get_contents('php://input'), true);
            $response = createOrder($conn, $userId, $orderData);
            break;
            
        // Reviews
        case 'reviews':
            $productId = $_GET['product_id'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $response = getReviews($conn, $productId, $page, $limit);
            break;
            
        case 'add_review':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $reviewData = json_decode(file_get_contents('php://input'), true);
            $response = addReview($conn, $userId, $reviewData);
            break;
            
        // Vendor specific
        case 'vendor_products':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $response = getVendorProducts($conn, $userId, $page, $limit);
            break;
            
        case 'vendor_orders':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $response = getVendorOrders($conn, $userId, $page, $limit);
            break;
            
        case 'vendor_analytics':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $response = getVendorAnalytics($conn, $userId);
            break;
            
        // Notifications
        case 'notifications':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $response = getNotifications($conn, $userId);
            break;
            
        case 'mark_notification_read':
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            $notificationId = $_GET['id'] ?? null;
            $response = markNotificationRead($conn, $userId, $notificationId);
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

// ==================== HOME & DISCOVERY FUNCTIONS ====================

/**
 * Get home data (featured products, categories, hero banners)
 */
function getHomeData($conn) {
    $data = [
        'success' => true,
        'featured_products' => [],
        'categories' => [],
        'hero_banners' => [],
        'flash_sale' => null,
        'deal_of_day' => null
    ];
    
    // Get featured products
    $sql = "SELECT p.*, u.username as vendor_name, c.name as category_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM products p 
            LEFT JOIN users u ON p.vendor_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' AND p.featured = 1
            ORDER BY p.created_at DESC 
            LIMIT 10";
    
    $stmt = $conn->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['featured_products'][] = formatProduct($row);
    }
    
    // Get categories
    $data['categories'] = getCategories($conn)['categories'];
    
    // Get hero banners
    $heroSql = "SELECT * FROM hero_sections WHERE is_active = 1 ORDER BY position ASC";
    $heroStmt = $conn->query($heroSql);
    while ($hero = $heroStmt->fetch(PDO::FETCH_ASSOC)) {
        $data['hero_banners'][] = [
            'id' => (string)$hero['id'],
            'title' => $hero['title'],
            'subtitle' => $hero['subtitle'],
            'description' => $hero['description'],
            'button_text' => $hero['button_text'],
            'button_link' => $hero['button_link'],
            'image_url' => $hero['image_url'],
            'background_color' => $hero['background_color'],
            'text_color' => $hero['text_color']
        ];
    }
    
    // Get flash sale products
    $flashSql = "SELECT p.*, u.username as vendor_name, c.name as category_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM products p 
            LEFT JOIN users u ON p.vendor_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' AND p.sale_price IS NOT NULL AND p.sale_price < p.price
            ORDER BY p.created_at DESC 
            LIMIT 5";
    
    $flashStmt = $conn->query($flashSql);
    while ($flash = $flashStmt->fetch(PDO::FETCH_ASSOC)) {
        $data['flash_sale']['products'][] = formatProduct($flash);
    }
    $data['flash_sale']['title'] = 'Flash Sale!';
    $data['flash_sale']['subtitle'] = 'Limited time offers';
    $data['flash_sale']['end_time'] = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    return $data;
}

/**
 * Get products with pagination and sorting
 */
function getProducts($conn, $categoryId = null, $page = 1, $limit = 20, $sortBy = 'created_at', $order = 'DESC') {
    $data = [
        'success' => true,
        'products' => [],
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => 0,
            'total_pages' => 0
        ]
    ];
    
    $offset = ($page - 1) * $limit;
    
    // Build base query
    $sql = "SELECT p.*, u.username as vendor_name, c.name as category_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                   (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count,
                   (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating
            FROM products p 
            LEFT JOIN users u ON p.vendor_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active'";
    
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND p.category_id = :category_id";
        $params[':category_id'] = $categoryId;
    }
    
    // Add sorting
    $allowedSorts = ['name', 'price', 'rating', 'created_at', 'sale_price'];
    $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
    $order = in_array(strtoupper($order), ['ASC', 'DESC']) ? strtoupper($order) : 'DESC';
    
    $sql .= " ORDER BY p.{$sortBy} {$order}";
    
    // Get total count
    $countSql = str_replace("p.*, u.username as vendor_name, c.name as category_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                   (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count,
                   (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating", 
                   "COUNT(*)", $sql);
    
    $countStmt = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetchColumn();
    
    // Add pagination
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['products'][] = formatProduct($row);
    }
    
    $data['pagination']['total'] = (int)$total;
    $data['pagination']['total_pages'] = ceil($total / $limit);
    
    return $data;
}

/**
 * Get single product details with reviews
 */
function getProductDetails($conn, $productId, $userId = null) {
    $sql = "SELECT p.*, u.username as vendor_name, u.email as vendor_email, c.name as category_name,
                   (SELECT GROUP_CONCAT(image_url ORDER BY sort_order, is_primary DESC) FROM product_images WHERE product_id = p.id) as images,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                   (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count,
                   (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating
            FROM products p 
            LEFT JOIN users u ON p.vendor_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = :id AND p.status = 'active'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Check if in wishlist
    $inWishlist = false;
    if ($userId) {
        $wishlistSql = "SELECT COUNT(*) FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
        $wishlistStmt = $conn->prepare($wishlistSql);
        $wishlistStmt->bindParam(':user_id', $userId);
        $wishlistStmt->bindParam(':product_id', $productId);
        $wishlistStmt->execute();
        $inWishlist = $wishlistStmt->fetchColumn() > 0;
    }
    
    // Get product reviews
    $reviewsSql = "SELECT r.*, u.first_name, u.last_name 
                    FROM reviews r 
                    LEFT JOIN users u ON r.user_id = u.id 
                    WHERE r.product_id = :product_id AND r.status = 'approved'
                    ORDER BY r.created_at DESC 
                    LIMIT 10";
    
    $reviewsStmt = $conn->prepare($reviewsSql);
    $reviewsStmt->bindParam(':product_id', $productId);
    $reviewsStmt->execute();
    
    $reviews = [];
    while ($review = $reviewsStmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = [
            'id' => (string)$review['id'],
            'user_name' => $review['first_name'] . ' ' . $review['last_name'],
            'rating' => (int)$review['rating'],
            'title' => $review['title'],
            'comment' => $review['comment'],
            'created_at' => $review['created_at'],
            'helpful_count' => (int)$review['helpful_count']
        ];
    }
    
    $formattedProduct = formatProduct($product);
    $formattedProduct['in_wishlist'] = $inWishlist;
    $formattedProduct['reviews'] = $reviews;
    
    return [
        'success' => true,
        'product' => $formattedProduct
    ];
}

/**
 * Search products with pagination
 */
function searchProducts($conn, $query, $page = 1, $limit = 20) {
    $data = [
        'success' => true,
        'products' => [],
        'query' => $query,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => 0,
            'total_pages' => 0
        ]
    ];
    
    if (empty($query)) {
        return $data;
    }
    
    $offset = ($page - 1) * $limit;
    $searchTerm = "%{$query}%";
    
    $sql = "SELECT p.*, u.username as vendor_name, c.name as category_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                   (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count,
                   (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') as avg_rating
            FROM products p 
            LEFT JOIN users u ON p.vendor_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' 
            AND (p.name LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':search', $searchTerm);
    $stmt->bindParam(':limit', $limit);
    $stmt->bindParam(':offset', $offset);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['products'][] = formatProduct($row);
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM products p 
                 WHERE p.status = 'active' 
                 AND (p.name LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)";
    
    $countStmt = $conn->prepare($countSql);
    $countStmt->bindParam(':search', $searchTerm);
    $countStmt->execute();
    $total = $countStmt->fetchColumn();
    
    $data['pagination']['total'] = (int)$total;
    $data['pagination']['total_pages'] = ceil($total / $limit);
    
    return $data;
}

/**
 * Get all categories with product counts
 */
function getCategories($conn) {
    $data = [
        'success' => true,
        'categories' => []
    ];
    
    $sql = "SELECT c.*, COUNT(p.id) as product_count
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            WHERE c.is_active = 1
            GROUP BY c.id 
            ORDER BY c.sort_order, c.name";
    
    $stmt = $conn->query($sql);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['categories'][] = [
            'id' => (string)$row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'description' => $row['description'] ?? '',
            'image' => $row['image'] ?? null,
            'product_count' => (int)$row['product_count'],
            'parent_id' => $row['parent_id'],
            'sort_order' => (int)$row['sort_order']
        ];
    }
    
    return $data;
}

// ==================== AUTHENTICATION FUNCTIONS ====================

/**
 * Login user
 */
function loginUser($conn, $email, $password) {
    $sql = "SELECT * FROM users WHERE email = :email AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    if (!password_verify($password, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['status'] = $user['status'];
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => (string)$user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'user_type' => $user['user_type'],
            'profile_image' => $user['profile_image']
        ]
    ];
}

/**
 * Register new user
 */
function registerUser($conn, $userData) {
    // Check if email already exists
    $checkSql = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(':email', $userData['email']);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn()) {
        return [
            'success' => false,
            'message' => 'Email already exists'
        ];
    }
    
    // Hash password
    $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, country, user_type, status, created_at) 
            VALUES (:username, :email, :password, :first_name, :last_name, :phone, :address, :city, :country, :user_type, 'active', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $userData['username']);
    $stmt->bindParam(':email', $userData['email']);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':first_name', $userData['first_name']);
    $stmt->bindParam(':last_name', $userData['last_name']);
    $stmt->bindParam(':phone', $userData['phone'] ?? '');
    $stmt->bindParam(':address', $userData['address'] ?? '');
    $stmt->bindParam(':city', $userData['city'] ?? '');
    $stmt->bindParam(':country', $userData['country'] ?? '');
    $stmt->bindParam(':user_type', $userData['user_type'] ?? 'customer');
    
    if ($stmt->execute()) {
        $userId = $conn->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => (string)$userId
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Registration failed'
        ];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    return [
        'success' => true,
        'message' => 'Logged out successfully'
    ];
}

// ==================== USER PROFILE FUNCTIONS ====================

/**
 * Get user profile
 */
function getUserProfile($conn, $userId) {
    $sql = "SELECT id, username, email, first_name, last_name, phone, address, city, country, 
                    user_type, status, profile_image, created_at, updated_at
            FROM users WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    return [
        'success' => true,
        'user' => [
            'id' => (string)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'phone' => $user['phone'],
            'address' => $user['address'],
            'city' => $user['city'],
            'country' => $user['country'],
            'user_type' => $user['user_type'],
            'status' => $user['status'],
            'profile_image' => $user['profile_image'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at']
        ]
    ];
}

/**
 * Update user profile
 */
function updateUserProfile($conn, $userId, $profileData) {
    $allowedFields = ['first_name', 'last_name', 'phone', 'address', 'city', 'country'];
    $updates = [];
    $params = [':id' => $userId];
    
    foreach ($allowedFields as $field) {
        if (isset($profileData[$field])) {
            $updates[] = "{$field} = :{$field}";
            $params[":{$field}"] = $profileData[$field];
        }
    }
    
    if (empty($updates)) {
        return [
            'success' => false,
            'message' => 'No valid fields to update'
        ];
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Profile updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Profile update failed'
        ];
    }
}

// ==================== CART FUNCTIONS ====================

/**
 * Get user cart
 */
function getCart($conn, $userId) {
    $sql = "SELECT c.*, p.name, p.price, p.sale_price,
                   (SELECT image_url FROM product_images WHERE product_id = c.product_id AND is_primary = 1 LIMIT 1) as product_image
            FROM cart c 
            LEFT JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $items = [];
    $total = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $price = $row['sale_price'] ?? $row['price'];
        $itemTotal = $price * $row['quantity'];
        $total += $itemTotal;
        
        $items[] = [
            'id' => (string)$row['id'],
            'product_id' => (string)$row['product_id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'sale_price' => $row['sale_price'] ? (float)$row['sale_price'] : null,
            'quantity' => (int)$row['quantity'],
            'item_total' => $itemTotal,
            'product_image' => $row['product_image']
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
function addToCart($conn, $userId, $cartData) {
    $productId = $cartData['product_id'];
    $quantity = $cartData['quantity'] ?? 1;
    
    // Check if product exists and is active
    $productSql = "SELECT id, stock_quantity FROM products WHERE id = :id AND status = 'active'";
    $productStmt = $conn->prepare($productSql);
    $productStmt->bindParam(':id', $productId);
    $productStmt->execute();
    
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        return [
            'success' => false,
            'message' => 'Product not found'
        ];
    }
    
    if ($product['stock_quantity'] < $quantity) {
        return [
            'success' => false,
            'message' => 'Insufficient stock'
        ];
    }
    
    // Check if item already in cart
    $existingSql = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
    $existingStmt = $conn->prepare($existingSql);
    $existingStmt->bindParam(':user_id', $userId);
    $existingStmt->bindParam(':product_id', $productId);
    $existingStmt->execute();
    
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update quantity
        $newQuantity = $existing['quantity'] + $quantity;
        $updateSql = "UPDATE cart SET quantity = :quantity, updated_at = NOW() 
                      WHERE user_id = :user_id AND product_id = :product_id";
        
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bindParam(':quantity', $newQuantity);
        $updateStmt->bindParam(':user_id', $userId);
        $updateStmt->bindParam(':product_id', $productId);
        
        if ($updateStmt->execute()) {
            return [
                'success' => true,
                'message' => 'Cart updated successfully',
                'quantity' => $newQuantity
            ];
        }
    } else {
        // Add new item
        $insertSql = "INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) 
                      VALUES (:user_id, :product_id, :quantity, NOW(), NOW())";
        
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bindParam(':user_id', $userId);
        $insertStmt->bindParam(':product_id', $productId);
        $insertStmt->bindParam(':quantity', $quantity);
        
        if ($insertStmt->execute()) {
            return [
                'success' => true,
                'message' => 'Added to cart successfully'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Failed to add to cart'
    ];
}

/**
 * Update cart item quantity
 */
function updateCartItem($conn, $userId, $cartData) {
    $productId = $cartData['product_id'];
    $quantity = $cartData['quantity'];
    
    $sql = "UPDATE cart SET quantity = :quantity, updated_at = NOW() 
              WHERE user_id = :user_id AND product_id = :product_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':product_id', $productId);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Cart updated successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update cart'
        ];
    }
}

/**
 * Remove item from cart
 */
function removeFromCart($conn, $userId, $productId) {
    $sql = "DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':product_id', $productId);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Item removed from cart'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to remove item'
        ];
    }
}

/**
 * Clear cart
 */
function clearCart($conn, $userId) {
    $sql = "DELETE FROM cart WHERE user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Cart cleared successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to clear cart'
        ];
    }
}

// ==================== WISHLIST FUNCTIONS ====================

/**
 * Get user wishlist
 */
function getWishlist($conn, $userId) {
    $sql = "SELECT w.*, p.name, p.price, p.sale_price,
                   (SELECT image_url FROM product_images WHERE product_id = w.product_id AND is_primary = 1 LIMIT 1) as product_image
            FROM wishlist w 
            LEFT JOIN products p ON w.product_id = p.id 
            WHERE w.user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $items = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = [
            'id' => (string)$row['id'],
            'product_id' => (string)$row['product_id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'sale_price' => $row['sale_price'] ? (float)$row['sale_price'] : null,
            'product_image' => $row['product_image'],
            'created_at' => $row['created_at']
        ];
    }
    
    return [
        'success' => true,
        'items' => $items,
        'total_items' => count($items)
    ];
}

/**
 * Add item to wishlist
 */
function addToWishlist($conn, $userId, $wishlistData) {
    $productId = $wishlistData['product_id'];
    
    // Check if already in wishlist
    $existingSql = "SELECT COUNT(*) FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
    $existingStmt = $conn->prepare($existingSql);
    $existingStmt->bindParam(':user_id', $userId);
    $existingStmt->bindParam(':product_id', $productId);
    $existingStmt->execute();
    
    if ($existingStmt->fetchColumn() > 0) {
        return [
            'success' => false,
            'message' => 'Product already in wishlist'
        ];
    }
    
    $sql = "INSERT INTO wishlist (user_id, product_id, created_at) 
              VALUES (:user_id, :product_id, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':product_id', $productId);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Added to wishlist successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add to wishlist'
        ];
    }
}

/**
 * Remove item from wishlist
 */
function removeFromWishlist($conn, $userId, $productId) {
    $sql = "DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':product_id', $productId);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Item removed from wishlist'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to remove item'
        ];
    }
}

// ==================== ORDER FUNCTIONS ====================

/**
 * Get user orders
 */
function getOrders($conn, $userId) {
    $sql = "SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.user_id = :user_id 
            GROUP BY o.id 
            ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $orders = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $orders[] = [
            'id' => (string)$row['id'],
            'order_number' => $row['order_number'],
            'total_amount' => (float)$row['total_amount'],
            'status' => $row['status'],
            'payment_status' => $row['payment_status'],
            'payment_method' => $row['payment_method'],
            'item_count' => (int)$row['item_count'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    return [
        'success' => true,
        'orders' => $orders,
        'total_orders' => count($orders)
    ];
}

/**
 * Get order details
 */
function getOrderDetails($conn, $userId, $orderId) {
    $sql = "SELECT o.*, oi.product_id, oi.quantity, oi.price as item_price, oi.total,
                   p.name as product_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as product_image,
                   u.first_name, u.last_name, u.email as user_email
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = :id AND o.user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $orderId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items
    $itemsSql = "SELECT oi.*, p.name as product_name,
                  (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as product_image
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = :order_id";
    
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bindParam(':order_id', $orderId);
    $itemsStmt->execute();
    
    $items = [];
    
    while ($item = $itemsStmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = [
            'product_id' => (string)$item['product_id'],
            'product_name' => $item['product_name'],
            'product_image' => $item['product_image'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['item_price'],
            'total' => (float)$item['total']
        ];
    }
    
    return [
        'success' => true,
        'order' => [
            'id' => (string)$order['id'],
            'order_number' => $order['order_number'],
            'total_amount' => (float)$order['total_amount'],
            'shipping_amount' => (float)$order['shipping_amount'],
            'tax_amount' => (float)$order['tax_amount'],
            'discount_amount' => (float)$order['discount_amount'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'payment_method' => $order['payment_method'],
            'shipping_address' => $order['shipping_address'],
            'billing_address' => $order['billing_address'],
            'notes' => $order['notes'],
            'items' => $items,
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at']
        ]
    ];
}

/**
 * Create new order
 */
function createOrder($conn, $userId, $orderData) {
    try {
        $conn->beginTransaction();
        
        // Generate order number
        $orderNumber = 'ORD' . date('YmdHis') . rand(1000, 9999);
        
        // Insert order
        $orderSql = "INSERT INTO orders (user_id, order_number, total_amount, shipping_amount, tax_amount, 
                         discount_amount, status, payment_status, payment_method, shipping_address, 
                         billing_address, notes, created_at) 
                         VALUES (:user_id, :order_number, :total_amount, :shipping_amount, :tax_amount,
                         :discount_amount, 'pending', 'pending', :payment_method, :shipping_address,
                         :billing_address, :notes, NOW())";
        
        $orderStmt = $conn->prepare($orderSql);
        $orderStmt->bindParam(':user_id', $userId);
        $orderStmt->bindParam(':order_number', $orderNumber);
        $orderStmt->bindParam(':total_amount', $orderData['total_amount']);
        $orderStmt->bindParam(':shipping_amount', $orderData['shipping_amount'] ?? 0);
        $orderStmt->bindParam(':tax_amount', $orderData['tax_amount'] ?? 0);
        $orderStmt->bindParam(':discount_amount', $orderData['discount_amount'] ?? 0);
        $orderStmt->bindParam(':payment_method', $orderData['payment_method']);
        $orderStmt->bindParam(':shipping_address', $orderData['shipping_address']);
        $orderStmt->bindParam(':billing_address', $orderData['billing_address'] ?? '');
        $orderStmt->bindParam(':notes', $orderData['notes'] ?? '');
        
        $orderStmt->execute();
        $orderId = $conn->lastInsertId();
        
        // Insert order items
        foreach ($orderData['items'] as $item) {
            $itemSql = "INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price, total, created_at) 
                           VALUES (:order_id, :product_id, :vendor_id, :quantity, :price, :total, NOW())";
            
            $itemStmt = $conn->prepare($itemSql);
            $itemStmt->bindParam(':order_id', $orderId);
            $itemStmt->bindParam(':product_id', $item['product_id']);
            $itemStmt->bindParam(':vendor_id', $item['vendor_id']);
            $itemStmt->bindParam(':quantity', $item['quantity']);
            $itemStmt->bindParam(':price', $item['price']);
            $itemStmt->bindParam(':total', $item['total']);
            $itemStmt->execute();
            
            // Update product stock
            $stockSql = "UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id";
            $stockStmt = $conn->prepare($stockSql);
            $stockStmt->bindParam(':quantity', $item['quantity']);
            $stockStmt->bindParam(':product_id', $item['product_id']);
            $stockStmt->execute();
        }
        
        // Clear cart
        $clearCartSql = "DELETE FROM cart WHERE user_id = :user_id";
        $clearCartStmt = $conn->prepare($clearCartSql);
        $clearCartStmt->bindParam(':user_id', $userId);
        $clearCartStmt->execute();
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => (string)$orderId,
            'order_number' => $orderNumber
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Order creation failed: ' . $e->getMessage()
        ];
    }
}

// ==================== REVIEW FUNCTIONS ====================

/**
 * Get product reviews
 */
function getReviews($conn, $productId, $page = 1, $limit = 10) {
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT r.*, u.first_name, u.last_name, u.profile_image
            FROM reviews r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = :product_id AND r.status = 'approved'
            ORDER BY r.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':product_id', $productId);
    $stmt->bindParam(':limit', $limit);
    $stmt->bindParam(':offset', $offset);
    $stmt->execute();
    
    $reviews = [];
    
    while ($review = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = [
            'id' => (string)$review['id'],
            'user_name' => $review['first_name'] . ' ' . $review['last_name'],
            'profile_image' => $review['profile_image'],
            'rating' => (int)$review['rating'],
            'title' => $review['title'],
            'comment' => $review['comment'],
            'helpful_count' => (int)$review['helpful_count'],
            'created_at' => $review['created_at']
        ];
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM reviews WHERE product_id = :product_id AND status = 'approved'";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bindParam(':product_id', $productId);
    $countStmt->execute();
    $total = $countStmt->fetchColumn();
    
    return [
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ];
}

/**
 * Add product review
 */
function addReview($conn, $userId, $reviewData) {
    $productId = $reviewData['product_id'];
    $rating = $reviewData['rating'];
    $title = $reviewData['title'] ?? '';
    $comment = $reviewData['comment'] ?? '';
    
    // Check if user has purchased the product
    $purchaseSql = "SELECT COUNT(*) FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE oi.product_id = :product_id AND o.user_id = :user_id AND o.payment_status = 'paid'";
    
    $purchaseStmt = $conn->prepare($purchaseSql);
    $purchaseStmt->bindParam(':product_id', $productId);
    $purchaseStmt->bindParam(':user_id', $userId);
    $purchaseStmt->execute();
    
    if ($purchaseStmt->fetchColumn() == 0) {
        return [
            'success' => false,
            'message' => 'You must purchase this product before reviewing'
        ];
    }
    
    // Check if already reviewed
    $existingSql = "SELECT COUNT(*) FROM reviews WHERE product_id = :product_id AND user_id = :user_id";
    $existingStmt = $conn->prepare($existingSql);
    $existingStmt->bindParam(':product_id', $productId);
    $existingStmt->bindParam(':user_id', $userId);
    $existingStmt->execute();
    
    if ($existingStmt->fetchColumn() > 0) {
        return [
            'success' => false,
            'message' => 'You have already reviewed this product'
        ];
    }
    
    $sql = "INSERT INTO reviews (product_id, user_id, rating, title, comment, status, created_at) 
              VALUES (:product_id, :user_id, :rating, :title, :comment, 'pending', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':product_id', $productId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':comment', $comment);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Review submitted successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to submit review'
        ];
    }
}

// ==================== VENDOR FUNCTIONS ====================

/**
 * Get vendor products
 */
function getVendorProducts($conn, $userId, $page = 1, $limit = 20) {
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.*, c.name as category_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.vendor_id = :vendor_id
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':vendor_id', $userId);
    $stmt->bindParam(':limit', $limit);
    $stmt->bindParam(':offset', $offset);
    $stmt->execute();
    
    $products = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = formatProduct($row);
    }
    
    return [
        'success' => true,
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => count($products),
            'total_pages' => ceil(count($products) / $limit)
        ]
    ];
}

/**
 * Get vendor orders
 */
function getVendorOrders($conn, $userId, $page = 1, $limit = 20) {
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE oi.vendor_id = :vendor_id 
            GROUP BY o.id 
            ORDER BY o.created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':vendor_id', $userId);
    $stmt->bindParam(':limit', $limit);
    $stmt->bindParam(':offset', $offset);
    $stmt->execute();
    
    $orders = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $orders[] = [
            'id' => (string)$row['id'],
            'order_number' => $row['order_number'],
            'total_amount' => (float)$row['total_amount'],
            'status' => $row['status'],
            'payment_status' => $row['payment_status'],
            'item_count' => (int)$row['item_count'],
            'created_at' => $row['created_at']
        ];
    }
    
    return [
        'success' => true,
        'orders' => $orders,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => count($orders),
            'total_pages' => ceil(count($orders) / $limit)
        ]
    ];
}

/**
 * Get vendor analytics
 */
function getVendorAnalytics($conn, $userId) {
    // Get basic stats
    $statsSql = "SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    COALESCE(SUM(o.total_amount), 0) as total_revenue,
                    COUNT(DISTINCT p.id) as total_products,
                    COALESCE(AVG(r.rating), 0) as avg_rating
                FROM vendors v
                LEFT JOIN products p ON v.user_id = p.vendor_id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
                LEFT JOIN reviews r ON p.id = r.product_id AND r.status = 'approved'
                WHERE v.user_id = :vendor_id";
    
    $statsStmt = $conn->prepare($statsSql);
    $statsStmt->bindParam(':vendor_id', $userId);
    $statsStmt->execute();
    
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent orders
    $recentSql = "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at
                  FROM orders o 
                  LEFT JOIN order_items oi ON o.id = oi.order_id 
                  WHERE oi.vendor_id = :vendor_id 
                  ORDER BY o.created_at DESC 
                  LIMIT 5";
    
    $recentStmt = $conn->prepare($recentSql);
    $recentStmt->bindParam(':vendor_id', $userId);
    $recentStmt->execute();
    
    $recentOrders = [];
    
    while ($order = $recentStmt->fetch(PDO::FETCH_ASSOC)) {
        $recentOrders[] = [
            'id' => (string)$order['id'],
            'order_number' => $order['order_number'],
            'total_amount' => (float)$order['total_amount'],
            'status' => $order['status'],
            'created_at' => $order['created_at']
        ];
    }
    
    return [
        'success' => true,
        'stats' => [
            'total_orders' => (int)$stats['total_orders'],
            'total_revenue' => (float)$stats['total_revenue'],
            'total_products' => (int)$stats['total_products'],
            'avg_rating' => (float)$stats['avg_rating']
        ],
        'recent_orders' => $recentOrders
    ];
}

// ==================== NOTIFICATION FUNCTIONS ====================

/**
 * Get user notifications
 */
function getNotifications($conn, $userId) {
    $sql = "SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $notifications = [];
    
    while ($notification = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notifications[] = [
            'id' => (string)$notification['id'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'is_read' => (bool)$notification['is_read'],
            'created_at' => $notification['created_at']
        ];
    }
    
    return [
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => count(array_filter($notifications, function($n) { return !$n['is_read']; }))
    ];
}

/**
 * Mark notification as read
 */
function markNotificationRead($conn, $userId, $notificationId) {
    $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
              WHERE user_id = :user_id AND id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':id', $notificationId);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Notification marked as read'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to mark notification as read'
        ];
    }
}

// ==================== UTILITY FUNCTIONS ====================

/**
 * Format product data for API response
 */
function formatProduct($row) {
    return [
        'id' => (string)$row['id'],
        'name' => $row['name'],
        'description' => $row['description'] ?? '',
        'short_description' => $row['short_description'] ?? '',
        'price' => (float)$row['price'],
        'sale_price' => isset($row['sale_price']) ? (float)$row['sale_price'] : null,
        'sku' => $row['sku'],
        'stock_quantity' => (int)$row['stock_quantity'],
        'min_stock_level' => (int)($row['min_stock_level'] ?? 5),
        'weight' => $row['weight'] ? (float)$row['weight'] : null,
        'dimensions' => $row['dimensions'] ?? '',
        'status' => $row['status'],
        'featured' => (bool)($row['featured'] ?? false),
        'rating' => isset($row['avg_rating']) ? (float)$row['avg_rating'] : 0.0,
        'review_count' => isset($row['review_count']) ? (int)$row['review_count'] : 0,
        'total_sales' => (int)($row['total_sales'] ?? 0),
        'category_id' => (string)($row['category_id'] ?? ''),
        'category_name' => $row['category_name'] ?? '',
        'vendor_id' => (string)($row['vendor_id'] ?? ''),
        'vendor_name' => $row['vendor_name'] ?? '',
        'primary_image' => $row['primary_image'] ?? 'placeholder.jpg',
        'images' => isset($row['images']) ? explode(',', $row['images']) : [],
        'tags' => $row['tags'] ? explode(',', $row['tags']) : [],
        'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s'),
        'on_sale' => isset($row['sale_price']) && $row['sale_price'] < $row['price'],
        'discount_percentage' => isset($row['sale_price']) && $row['sale_price'] < $row['price'] 
            ? round((($row['price'] - $row['sale_price']) / $row['price']) * 100, 1) 
            : 0
    ];
}
?>