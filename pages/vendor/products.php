<?php
$pageTitle = 'My Products';
$pageDescription = 'Manage your products';

// Redirect if not vendor
if (!isVendor()) {
    redirectTo('?page=login');
}

global $database;

// Get vendor info
$vendor = $database->fetchOne("SELECT * FROM vendors WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$vendor) {
    redirectTo('?page=register&type=vendor');
}

// Check if vendor is verified
if ($_SESSION['status'] !== 'active') {
    include __DIR__ . '/verification-pending.php';
    return;
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_product') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $salePrice = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
            $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
            $categoryId = intval($_POST['category_id'] ?? 0);
            $sku = trim($_POST['sku'] ?? '');
            $weight = floatval($_POST['weight'] ?? 0);
            $minStockLevel = intval($_POST['min_stock_level'] ?? 5);
            
            if (empty($name) || $price <= 0 || $stockQuantity < 0 || $categoryId <= 0) {
                $error = 'Please fill all required fields with valid values';
            } else {
                // Check if SKU already exists
                $existingSKU = $database->fetchOne("SELECT id FROM products WHERE sku = ? AND vendor_id != ?", [$sku, $vendor['id']]);
                if ($existingSKU) {
                    $error = 'SKU already exists. Please use a unique SKU.';
                } else {
                    $productId = $database->insert('products', [
                        'vendor_id' => $vendor['id'],
                        'category_id' => $categoryId,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'sku' => $sku,
                        'stock_quantity' => $stockQuantity,
                        'min_stock_level' => $minStockLevel,
                        'weight' => $weight,
                        'status' => 'pending', // Products need admin approval
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($productId) {
                        $success = 'Product added successfully! It will be live after admin approval.';
                    } else {
                        $error = 'Failed to add product. Please try again.';
                    }
                }
            }
        }
        
        if ($action === 'update_product') {
            $productId = intval($_POST['product_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $salePrice = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
            $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
            $categoryId = intval($_POST['category_id'] ?? 0);
            
            if (empty($name) || $price <= 0 || $stockQuantity < 0 || $categoryId <= 0) {
                $error = 'Please fill all required fields with valid values';
            } else {
                $updated = $database->update('products', [
                    'category_id' => $categoryId,
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'stock_quantity' => $stockQuantity,
                    'status' => 'pending', // Re-approval needed after edit
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ? AND vendor_id = ?', [$productId, $vendor['id']]);
                
                if ($updated) {
                    $success = 'Product updated successfully! Changes will be live after admin approval.';
                } else {
                    $error = 'Failed to update product. Please try again.';
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

// Get vendor's products
$products = $database->fetchAll("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.vendor_id = ?
    ORDER BY p.created_at DESC
", [$vendor['id']]);

// Get categories for dropdown
$categories = $database->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// Get product for editing
$editingProduct = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editingProduct = $database->fetchOne("SELECT * FROM products WHERE id = ? AND vendor_id = ?", [intval($_GET['edit']), $vendor['id']]);
}
?>

<div class="min-h-screen bg-gray-50">
    <!-- Mobile Header -->
    <div class="lg:hidden bg-white shadow-sm border-b px-4 py-3">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">My Products</h1>
            <button onclick="toggleVendorSidebar()" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Vendor Sidebar -->
        <div id="vendor-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-secondary transform -translate-x-full transition-transform lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex items-center justify-between p-6 border-b border-gray-600">
                <h2 class="text-xl font-bold text-white">Vendor Panel</h2>
                <button onclick="toggleVendorSidebar()" class="lg:hidden text-white hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-6 border-b border-gray-600">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-3 bg-primary rounded-full flex items-center justify-center text-white text-2xl">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 class="text-white font-semibold"><?php echo htmlspecialchars($vendor['shop_name']); ?></h3>
                    <p class="text-gray-300 text-sm">
                        <?php if ($_SESSION['status'] === 'active'): ?>
                            <i class="fas fa-check-circle text-green-400 mr-1"></i>Verified
                        <?php else: ?>
                            <i class="fas fa-clock text-yellow-400 mr-1"></i>Pending Approval
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <nav class="mt-6">
                <a href="?page=vendor" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="?page=vendor&section=products" class="flex items-center px-6 py-3 text-white bg-primary">
                    <i class="fas fa-box mr-3"></i>My Products
                </a>
                <a href="?page=vendor&section=orders" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-shopping-cart mr-3"></i>Orders
                </a>
                <a href="?page=vendor&section=profile" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-user mr-3"></i>Shop Profile
                </a>
                <a href="?page=logout" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                </a>
            </nav>
        </div>

        <!-- Sidebar Overlay -->
        <div id="vendor-overlay" class="fixed inset-0 bg-black opacity-50 z-40 lg:hidden hidden" onclick="toggleVendorSidebar()"></div>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-0">
            <div class="p-4 lg:p-8">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-2">Product Management</h1>
                        <p class="text-gray-600">Manage your product catalog</p>
                    </div>
                    <div class="mt-4 lg:mt-0">
                        <a href="?page=vendor&section=products&action=add" 
                           class="inline-flex items-center bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200">
                            <i class="fas fa-plus mr-2"></i>Add New Product
                        </a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $editingProduct): ?>
                    <!-- Add/Edit Product Form -->
                    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-6">
                            <?php echo $editingProduct ? 'Edit Product' : 'Add New Product'; ?>
                        </h2>
                        
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="<?php echo $editingProduct ? 'update_product' : 'add_product'; ?>">
                            <?php if ($editingProduct): ?>
                                <input type="hidden" name="product_id" value="<?php echo $editingProduct['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Product Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                                    <input type="text" name="name" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo $editingProduct ? htmlspecialchars($editingProduct['name']) : ''; ?>"
                                           placeholder="Enter product name">
                                </div>
                                
                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                                    <select name="category_id" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo ($editingProduct && $editingProduct['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" rows="4"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                          placeholder="Describe your product..."><?php echo $editingProduct ? htmlspecialchars($editingProduct['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Price -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Price (Rs.) *</label>
                                    <input type="number" name="price" step="0.01" min="0" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo $editingProduct ? $editingProduct['price'] : ''; ?>"
                                           placeholder="0.00">
                                </div>
                                
                                <!-- Sale Price -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sale Price (Rs.)</label>
                                    <input type="number" name="sale_price" step="0.01" min="0"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo $editingProduct ? $editingProduct['sale_price'] : ''; ?>"
                                           placeholder="0.00">
                                </div>
                                
                                <!-- Stock Quantity -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                                    <input type="number" name="stock_quantity" min="0" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo $editingProduct ? $editingProduct['stock_quantity'] : ''; ?>"
                                           placeholder="0">
                                </div>
                            </div>
                            
                            <?php if (!$editingProduct): ?>
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- SKU -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
                                    <input type="text" name="sku" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           placeholder="Enter unique SKU">
                                </div>
                                
                                <!-- Weight -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                                    <input type="number" name="weight" step="0.01" min="0"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           placeholder="0.00">
                                </div>
                                
                                <!-- Min Stock Level -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Min Stock Level</label>
                                    <input type="number" name="min_stock_level" min="1" value="5"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t">
                                <button type="submit" 
                                        class="flex-1 bg-primary text-white py-3 px-6 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200">
                                    <i class="fas fa-save mr-2"></i><?php echo $editingProduct ? 'Update Product' : 'Add Product'; ?>
                                </button>
                                <a href="?page=vendor&section=products" 
                                   class="flex-1 bg-gray-600 text-white py-3 px-6 rounded-lg font-semibold text-center hover:bg-gray-700 transition duration-200">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Products List -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-800">Your Products</h2>
                            <p class="text-gray-600 mt-1">
                                Total: <?php echo count($products); ?> products
                                <?php 
                                $pending = array_filter($products, function($p) { return $p['status'] === 'pending'; });
                                if (count($pending) > 0): 
                                ?>
                                    | <span class="text-orange-600"><?php echo count($pending); ?> pending approval</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if (empty($products)): ?>
                            <div class="p-12 text-center">
                                <i class="fas fa-box text-6xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Products Yet</h3>
                                <p class="text-gray-500 mb-6">Start by adding your first product to your store</p>
                                <a href="?page=vendor&section=products&action=add" 
                                   class="inline-flex items-center bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200">
                                    <i class="fas fa-plus mr-2"></i>Add First Product
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($products as $product): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                                        <div class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo formatPrice($product['price']); ?>
                                                    <?php if ($product['sale_price']): ?>
                                                        <br><span class="text-xs text-red-600"><?php echo formatPrice($product['sale_price']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="text-sm font-medium <?php echo $product['stock_quantity'] <= $product['min_stock_level'] ? 'text-red-600' : 'text-gray-900'; ?>">
                                                        <?php echo $product['stock_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                        <?php 
                                                        switch($product['status']) {
                                                            case 'active': echo 'bg-green-100 text-green-800'; break;
                                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                            case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                            case 'inactive': echo 'bg-gray-100 text-gray-800'; break;
                                                            default: echo 'bg-gray-100 text-gray-800';
                                                        }
                                                        ?>">
                                                        <?php echo ucfirst($product['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div class="flex items-center justify-end space-x-2">
                                                        <a href="?page=vendor&section=products&edit=<?php echo $product['id']; ?>" 
                                                           class="text-blue-600 hover:text-blue-900">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="delete_product">
                                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleVendorSidebar() {
    const sidebar = document.getElementById('vendor-sidebar');
    const overlay = document.getElementById('vendor-overlay');
    
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>
