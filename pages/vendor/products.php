
<?php
$pageTitle = 'My Products';
$pageDescription = 'Manage your products';

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

$success = '';
$error = '';

// Get categories
$categories = $database->fetchAll("SELECT * FROM categories ORDER BY name");

// Get product statistics
$stats = [
    'total_products' => $database->count('products', 'vendor_id = ?', [$vendor['id']]),
    'active_products' => $database->count('products', 'vendor_id = ? AND status = ?', [$vendor['id'], 'active']),
    'pending_products' => $database->count('products', 'vendor_id = ? AND status = ?', [$vendor['id'], 'pending']),
    'rejected_products' => $database->count('products', 'vendor_id = ? AND status = ?', [$vendor['id'], 'rejected']),
    'low_stock' => $database->count('products', 'vendor_id = ? AND stock_quantity <= min_stock_level AND status = ?', [$vendor['id'], 'active'])
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_product') {
            $name = trim($_POST['name'] ?? '');
            $shortDescription = trim($_POST['short_description'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $salePrice = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
            $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
            $categoryId = intval($_POST['category_id'] ?? 0);
            $sku = trim($_POST['sku'] ?? '');
            $weight = floatval($_POST['weight'] ?? 0);
            $dimensions = trim($_POST['dimensions'] ?? '');
            $minStockLevel = intval($_POST['min_stock_level'] ?? 5);
            $tags = trim($_POST['tags'] ?? '');
            $brand = trim($_POST['brand'] ?? '');
           
            if (empty($name) || $price <= 0 || $stockQuantity < 0 || $categoryId <= 0) {
                $error = 'Please fill all required fields with valid values.';
            } else {
                // Generate unique slug from product name
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                $slug = preg_replace('/-+/', '-', $slug);
                $slug = trim($slug, '-');
                
                // Check if slug already exists and make it unique
                $originalSlug = $slug;
                $counter = 1;
                while ($database->fetchOne("SELECT id FROM products WHERE slug = ? AND vendor_id != ?", [$slug, $vendor['id']])) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                // Check if SKU already exists
                $existingSKU = $database->fetchOne("SELECT id FROM products WHERE sku = ? AND vendor_id != ?", [$sku, $vendor['id']]);
                if ($existingSKU) {
                    $error = 'SKU already exists. Please use a unique SKU.';
                } else {
                    $productId = $database->insert('products', [
                        'vendor_id' => $vendor['id'],
                        'category_id' => $categoryId,
                        'name' => $name,
                        'short_description' => $shortDescription,
                        'description' => $description,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'sku' => $sku,
                        'slug' => $slug,
                        'stock_quantity' => $stockQuantity,
                        'min_stock_level' => $minStockLevel,
                        'weight' => $weight,
                        'dimensions' => $dimensions,
                        'tags' => $tags,
                        'brand' => $brand,
                        'status' => 'pending', // Products need admin approval
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($productId) {
                        // Handle image uploads
                        if (!empty($_FILES['images']['name'][0])) {
                            $uploadDir = 'uploads/products/';
                            if (!file_exists($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            
                            $imageCount = 0;
                            foreach ($_FILES['images']['name'] as $index => $imageName) {
                                if (!empty($imageName) && $imageCount < 5) {
                                    $imageFile = $_FILES['images'];
                                    $extension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
                                    
                                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp']) && $imageFile['size'][$index] <= 5 * 1024 * 1024) {
                                        $newFileName = uniqid() . '_' . time() . '.' . $extension;
                                        $uploadPath = $uploadDir . $newFileName;
                                        
                                        if (move_uploaded_file($imageFile['tmp_name'][$index], $uploadPath)) {
                                            $database->insert('product_images', [
                                                'product_id' => $productId,
                                                'image_url' => $uploadPath,
                                                'is_primary' => $imageCount === 0 ? 1 : 0,
                                                'sort_order' => $imageCount + 1
                                            ]);
                                            $imageCount++;
                                        }
                                    }
                                }
                            }
                        }
                        
                        $success = 'Product added successfully! It will be live after admin approval.';
                        header('Location: ?page=vendor-products');
                        exit();
                    } else {
                        $error = 'Failed to add product. Please try again.';
                    }
                }
            }
        }
        
        if ($action === 'update_product') {
            $productId = intval($_POST['product_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $shortDescription = trim($_POST['short_description'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $salePrice = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
            $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
            $categoryId = intval($_POST['category_id'] ?? 0);
            $sku = trim($_POST['sku'] ?? '');
            $weight = floatval($_POST['weight'] ?? 0);
            $dimensions = trim($_POST['dimensions'] ?? '');
            $minStockLevel = intval($_POST['min_stock_level'] ?? 5);
            $tags = trim($_POST['tags'] ?? '');
            $brand = trim($_POST['brand'] ?? '');
            
            if (empty($name) || $price <= 0 || $stockQuantity < 0 || $categoryId <= 0) {
                $error = 'Please fill all required fields with valid values.';
            } else {
                // Generate unique slug from product name
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                $slug = preg_replace('/-+/', '-', $slug);
                $slug = trim($slug, '-');
                
                // Check if slug already exists (excluding current product)
                $originalSlug = $slug;
                $counter = 1;
                while ($database->fetchOne("SELECT id FROM products WHERE slug = ? AND vendor_id != ? AND id != ?", [$slug, $vendor['id'], $productId])) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                // Check if SKU already exists (excluding current product)
                $existingSKU = $database->fetchOne("SELECT id FROM products WHERE sku = ? AND vendor_id != ? AND id != ?", [$sku, $vendor['id'], $productId]);
                if ($existingSKU) {
                    $error = 'SKU already exists. Please use a unique SKU.';
                } else {
                    $updated = $database->update('products', [
                        'category_id' => $categoryId,
                        'name' => $name,
                        'short_description' => $shortDescription,
                        'description' => $description,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'sku' => $sku,
                        'slug' => $slug,
                        'stock_quantity' => $stockQuantity,
                        'min_stock_level' => $minStockLevel,
                        'weight' => $weight,
                        'dimensions' => $dimensions,
                        'tags' => $tags,
                        'brand' => $brand,
                        'status' => 'pending', // Re-approval needed after edit
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ? AND vendor_id = ?', [$productId, $vendor['id']]);
                    
                    if ($updated) {
                        $success = 'Product updated successfully! Changes will be live after admin approval.';
                        header('Location: ?page=vendor-products');
                        exit();
                    } else {
                        $error = 'Failed to update product. Please try again.';
                    }
                }
            }
        }
        
        if ($action === 'delete_product') {
            $productId = intval($_POST['product_id'] ?? 0);
            
            $deleted = $database->delete('products', 'id = ? AND vendor_id = ?', [$productId, $vendor['id']]);
            
            if ($deleted) {
                $success = 'Product deleted successfully!';
            } else {
                $error = 'Failed to delete product.';
            }
        }
    }
}

// Get products
$products = $database->fetchAll("
    SELECT p.*, 
           c.name as category_name,
           pi.image_url,
           CASE 
               WHEN p.sale_price IS NOT NULL AND p.sale_price < p.price 
               THEN p.sale_price 
               ELSE p.price 
           END as current_price
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.vendor_id = ?
    ORDER BY p.created_at DESC
", [$vendor['id']]);

// Check if editing a product
$editingProduct = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editingProduct = $database->fetchOne("SELECT * FROM products WHERE id = ? AND vendor_id = ?", [$editId, $vendor['id']]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom styles for vendor system */
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar-overlay {
            backdrop-filter: blur(4px);
        }
        
        /* Ensure proper mobile behavior */
        @media (max-width: 1024px) {
            .vendor-sidebar {
                position: fixed;
                top: 64px;
                bottom: 0;
                left: 0;
                z-index: 40;
                width: 288px;
                transform: translateX(-100%);
            }
            
            .vendor-sidebar.open {
                transform: translateX(0);
            }
            
            .vendor-content {
                margin-left: 0;
                padding-top: 64px;
            }
        }
        
        @media (min-width: 1025px) {
            .vendor-sidebar {
                position: relative;
                width: 288px;
                transform: translateX(0) !important;
            }
            
            .vendor-content {
                margin-left: 288px;
                padding-top: 0;
            }
        }
        
        @media (min-width: 1025px) {
            .mobile-header {
                display: none;
            }
        }
        
        @media (max-width: 1024px) {
            .mobile-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 50;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-50 min-h-screen">
    <!-- Mobile Header -->
    <div class="mobile-header bg-white shadow-lg border-b px-4 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-white text-sm"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-800">My Products</h1>
            </div>
            <button onclick="toggleVendorSidebar()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                <i class="fas fa-bars text-gray-600"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Modern Vendor Sidebar -->
        <div id="vendor-sidebar" class="vendor-sidebar bg-white shadow-2xl border-r border-gray-200 sidebar-transition">
            
            <!-- Vendor Profile Header -->
            <div class="p-6 bg-gradient-to-r from-orange-500 to-red-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center">
                            <?php if ($vendor['shop_logo']): ?>
                                <img src="<?php echo htmlspecialchars($vendor['shop_logo']); ?>" 
                                     alt="Shop Logo" class="w-full h-full object-cover rounded-xl">
                            <?php else: ?>
                                <i class="fas fa-store text-white text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white"><?php echo htmlspecialchars($vendor['shop_name']); ?></h2>
                            <div class="flex items-center text-orange-100 text-sm">
                                <?php if ($_SESSION['status'] === 'active'): ?>
                                    <i class="fas fa-check-circle mr-1"></i>Active Store
                                <?php else: ?>
                                    <i class="fas fa-clock mr-1"></i><?php echo ucfirst($_SESSION['status']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <button onclick="toggleVendorSidebar()" class="lg:hidden p-1 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <div class="px-3 py-2">
                    <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Dashboard</span>
                </div>
                
                <a href="?page=vendor" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-tachometer-alt text-sm"></i>
                    </div>
                    <span class="font-medium">Overview</span>
                </a>
                
                <a href="?page=vendor-products" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                        <i class="fas fa-box text-sm"></i>
                    </div>
                    <span class="font-medium">Products</span>
                    <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                </a>
                
                <a href="?page=vendor-orders" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-shopping-cart text-sm"></i>
                    </div>
                    <span class="font-medium">Orders</span>
                </a>
                
                <a href="?page=vendor-analytics" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    <span class="font-medium">Analytics</span>
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <div class="px-3 py-2">
                        <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Account</span>
                    </div>
                    
                    <a href="?page=vendor-profile" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <span class="font-medium">Profile</span>
                    </a>
                    
                    <a href="?page=logout" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-red-600 hover:text-red-700 hover:bg-red-50">
                        <div class="w-9 h-9 rounded-lg bg-red-100 group-hover:bg-red-200 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </div>
                        <span class="font-medium">Logout</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Sidebar Overlay for Mobile -->
        <div id="vendor-overlay" class="fixed inset-0 bg-black/50 z-30 lg:hidden sidebar-overlay" onclick="toggleVendorSidebar()"></div>

        <!-- Main Content Area -->
        <div class="vendor-content flex-1">
            <div class="p-6 lg:p-8 max-w-7xl mx-auto">
                
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-box text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Product Management</h1>
                                <p class="text-gray-600 mt-1">Manage your product catalog</p>
                            </div>
                        </div>
                        <div class="mt-4 lg:mt-0">
                            <a href="?page=vendor-products&action=add" 
                               class="inline-flex items-center bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-3 rounded-2xl font-semibold hover:shadow-lg transition-all duration-200">
                                <i class="fas fa-plus mr-2"></i>Add New Product
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-6 rounded-2xl shadow-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-2xl mr-3"></i>
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-2xl shadow-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl shadow-lg">
                                <i class="fas fa-box text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['total_products']; ?></p>
                            <p class="text-sm text-gray-600">Total Products</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl shadow-lg">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['active_products']; ?></p>
                            <p class="text-sm text-gray-600">Active</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-2xl shadow-lg">
                                <i class="fas fa-clock text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['pending_products']; ?></p>
                            <p class="text-sm text-gray-600">Pending</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-red-500 to-red-600 rounded-2xl shadow-lg">
                                <i class="fas fa-times-circle text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['rejected_products']; ?></p>
                            <p class="text-sm text-gray-600">Rejected</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl shadow-lg">
                                <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['low_stock']; ?></p>
                            <p class="text-sm text-gray-600">Low Stock</p>
                        </div>
                    </div>
                </div>

                <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $editingProduct): ?>
                    <!-- Modern Add/Edit Product Form -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-8">
                        <div class="p-8">
                            <div class="flex items-center space-x-4 mb-8">
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-<?php echo $editingProduct ? 'edit' : 'plus'; ?> text-white text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        <?php echo $editingProduct ? 'Edit Product' : 'Add New Product'; ?>
                                    </h2>
                                    <p class="text-gray-600">Fill in product details below</p>
                                </div>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="<?php echo $editingProduct ? 'update_product' : 'add_product'; ?>">
                                <?php if ($editingProduct): ?>
                                    <input type="hidden" name="product_id" value="<?php echo $editingProduct['id']; ?>">
                                <?php endif; ?>
                                
                                <!-- Basic Information Section -->
                                <div class="bg-gray-50 rounded-2xl p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                                        Basic Information
                                    </h3>
                                    
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Product Name -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Product Name *</label>
                                            <input type="text" name="name" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? htmlspecialchars($editingProduct['name']) : ''; ?>"
                                                   placeholder="Enter product name">
                                        </div>
                                        
                                        <!-- SKU -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">SKU *</label>
                                            <input type="text" name="sku" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? htmlspecialchars($editingProduct['sku']) : ''; ?>"
                                                   placeholder="Enter unique SKU">
                                        </div>
                                        
                                        <!-- Category -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                                            <select name="category_id" required
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200">
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['id']; ?>" 
                                                            <?php echo ($editingProduct && $editingProduct['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Brand -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Brand</label>
                                            <input type="text" name="brand"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? htmlspecialchars($editingProduct['brand']) : ''; ?>"
                                                   placeholder="Enter brand name">
                                        </div>
                                    </div>
                                    
                                    <!-- Short Description -->
                                    <div class="mt-6">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Short Description</label>
                                        <input type="text" name="short_description"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200"
                                               value="<?php echo $editingProduct ? htmlspecialchars($editingProduct['short_description']) : ''; ?>"
                                               placeholder="Brief product description for listings">
                                    </div>
                                    
                                    <!-- Description -->
                                    <div class="mt-6">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Description</label>
                                        <textarea name="description" rows="4"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200"
                                                  placeholder="Detailed product description..."><?php echo $editingProduct ? htmlspecialchars($editingProduct['description']) : ''; ?></textarea>
                                    </div>
                                </div>
                                
                                <!-- Pricing Section -->
                                <div class="bg-green-50 rounded-2xl p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                        <i class="fas fa-dollar-sign mr-2 text-green-500"></i>
                                        Pricing & Inventory
                                    </h3>
                                    
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        <!-- Price -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Regular Price (Rs.) *</label>
                                            <input type="number" name="price" step="0.01" min="0" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? $editingProduct['price'] : ''; ?>"
                                                   placeholder="0.00">
                                        </div>
                                        
                                        <!-- Sale Price -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sale Price (Rs.)</label>
                                            <input type="number" name="sale_price" step="0.01" min="0"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? $editingProduct['sale_price'] : ''; ?>"
                                                   placeholder="Optional sale price">
                                        </div>
                                        
                                        <!-- Stock Quantity -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Stock Quantity *</label>
                                            <input type="number" name="stock_quantity" min="0" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? $editingProduct['stock_quantity'] : ''; ?>"
                                                   placeholder="0">
                                        </div>
                                        
                                        <!-- Min Stock Level -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Low Stock Alert Level</label>
                                            <input type="number" name="min_stock_level" min="0"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? $editingProduct['min_stock_level'] : '5'; ?>"
                                                   placeholder="5">
                                        </div>
                                        
                                        <!-- Weight -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Weight (kg)</label>
                                            <input type="number" name="weight" step="0.01" min="0"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? $editingProduct['weight'] : ''; ?>"
                                                   placeholder="0.00">
                                        </div>
                                        
                                        <!-- Dimensions -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Dimensions (L×W×H cm)</label>
                                            <input type="text" name="dimensions"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                                   value="<?php echo $editingProduct ? htmlspecialchars($editingProduct['dimensions']) : ''; ?>"
                                                   placeholder="e.g., 20×15×10">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Images Section -->
                                <div class="bg-blue-50 rounded-2xl p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                        <i class="fas fa-images mr-2 text-blue-500"></i>
                                        Product Images
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Images (Max 5 images, 5MB each)</label>
                                            <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-blue-400 transition-colors duration-200">
                                                <div class="space-y-2 text-center">
                                                    <div class="mx-auto h-12 w-12 text-gray-400">
                                                        <i class="fas fa-cloud-upload-alt text-4xl"></i>
                                                    </div>
                                                    <div class="flex text-sm text-gray-600">
                                                        <label for="images" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                            <span class="px-3 py-2">Upload images</span>
                                                            <input id="images" name="images[]" type="file" multiple accept="image/*" class="sr-only" onchange="updateImagePreview(this)">
                                                        </label>
                                                        <p class="pl-1">or drag and drop</p>
                                                    </div>
                                                    <p class="text-xs text-gray-500">PNG, JPG, WEBP up to 5MB each</p>
                                                </div>
                                            </div>
                                            <div id="image-preview" class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-4 hidden"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- SEO & Tags Section -->
                                <div class="bg-purple-50 rounded-2xl p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                        <i class="fas fa-tags mr-2 text-purple-500"></i>
                                        SEO & Tags
                                    </h3>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tags (comma separated)</label>
                                        <input type="text" name="tags"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                                               value="<?php echo $editingProduct ? htmlspecialchars($editingProduct['tags']) : ''; ?>"
                                               placeholder="electronics, smartphone, mobile, latest">
                                        <p class="text-sm text-gray-500 mt-1">Add relevant tags to help customers find your product</p>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 pt-8 border-t border-gray-200">
                                    <button type="submit" 
                                            class="flex-1 bg-gradient-to-r from-orange-500 to-red-500 text-white py-4 px-8 rounded-2xl font-semibold text-lg hover:shadow-lg transition-all duration-200 flex items-center justify-center">
                                        <i class="fas fa-save mr-2"></i>
                                        <?php echo $editingProduct ? 'Update Product' : 'Add Product'; ?>
                                    </button>
                                    <a href="?page=vendor-products" 
                                       class="flex-1 bg-gray-600 text-white py-4 px-8 rounded-2xl font-semibold text-lg text-center hover:bg-gray-700 transition-all duration-200 flex items-center justify-center">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Products List -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
                        <?php if (empty($products)): ?>
                            <div class="p-12 text-center">
                                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-3xl flex items-center justify-center">
                                    <i class="fas fa-box text-4xl text-gray-400"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Products Yet</h3>
                                <p class="text-gray-500 mb-6">Start building your catalog by adding your first product</p>
                                <a href="?page=vendor-products&action=add" 
                                   class="inline-flex items-center bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-3 rounded-2xl font-semibold hover:shadow-lg transition-all duration-200">
                                    <i class="fas fa-plus mr-2"></i>Add Your First Product
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <?php foreach ($products as $product): ?>
                                        <div class="bg-gray-50 rounded-2xl p-6 hover:shadow-lg transition-shadow duration-300">
                                            <!-- Product Image -->
                                            <div class="aspect-square bg-white rounded-xl mb-4 overflow-hidden">
                                                <?php if ($product['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                         class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                        <i class="fas fa-image text-6xl"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Product Info -->
                                            <div class="space-y-2">
                                                <h3 class="font-bold text-gray-900 text-lg line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                                
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xl font-bold text-orange-600">Rs. <?php echo number_format($product['current_price'], 2); ?></span>
                                                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                                        <span class="text-sm text-gray-500 line-through">Rs. <?php echo number_format($product['price'], 2); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-600">Stock: <?php echo $product['stock_quantity']; ?></span>
                                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                                        <?php echo $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                                  ($product['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                                        <?php echo ucfirst($product['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <?php if ($product['status'] === 'rejected' && !empty($product['rejection_reason'])): ?>
                                                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mt-3">
                                                        <p class="text-sm text-red-800">
                                                            <strong>Rejection Reason:</strong><br>
                                                            <?php echo htmlspecialchars($product['rejection_reason']); ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Actions -->
                                                <div class="flex space-x-2 pt-4">
                                                    <a href="?page=vendor-products&edit=<?php echo $product['id']; ?>" 
                                                       class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-xl text-center text-sm font-semibold hover:bg-blue-700 transition-colors">
                                                        <i class="fas fa-edit mr-1"></i>Edit
                                                    </a>
                                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                                            class="flex-1 bg-red-600 text-white py-2 px-4 rounded-xl text-sm font-semibold hover:bg-red-700 transition-colors">
                                                        <i class="fas fa-trash mr-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function toggleVendorSidebar() {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        
        sidebar.classList.toggle('open');
        overlay.classList.toggle('hidden');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        const toggleButton = event.target.closest('[onclick="toggleVendorSidebar()"]');
        
        if (!toggleButton && !sidebar.contains(event.target) && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        }
    });

    // Initialize sidebar state based on screen size
    function initializeSidebar() {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        
        if (window.innerWidth <= 1024) {
            // Mobile: hide sidebar by default
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        } else {
            // Desktop: always show sidebar
            sidebar.classList.add('open');
            overlay.classList.add('hidden');
        }
    }

    // Handle window resize
    window.addEventListener('resize', initializeSidebar);

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initializeSidebar);

    function updateImagePreview(input) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        
        if (input.files && input.files.length > 0) {
            preview.classList.remove('hidden');
            
            Array.from(input.files).forEach((file, index) => {
                if (index < 5) { // Max 5 images
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border">
                            <div class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cursor-pointer" onclick="removeImage(${index})">×</div>
                        `;
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                }
            });
        } else {
            preview.classList.add('hidden');
        }
    }

    function removeImage(index) {
        // This is a simplified version - in a real app you'd need to properly handle file removal
        console.log('Remove image at index:', index);
    }

    function deleteProduct(productId) {
