<?php
$pageTitle = 'Product Management';
$pageDescription = 'Manage and approve products';

// Redirect if not admin
if (!isAdmin()) {
    redirectTo('?page=login');
}

global $database;

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        $productId = intval($_POST['product_id'] ?? 0);
        
        if ($action === 'approve_product' && $productId > 0) {
            $database->update('products', ['status' => 'active'], 'id = ?', [$productId]);
            $success = 'Product approved successfully!';
        } elseif ($action === 'reject_product' && $productId > 0) {
            $database->update('products', ['status' => 'rejected'], 'id = ?', [$productId]);
            $success = 'Product rejected';
        } elseif ($action === 'suspend_product' && $productId > 0) {
            $database->update('products', ['status' => 'inactive'], 'id = ?', [$productId]);
            $success = 'Product suspended';
        } elseif ($action === 'activate_product' && $productId > 0) {
            $database->update('products', ['status' => 'active'], 'id = ?', [$productId]);
            $success = 'Product activated';
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';
$whereClause = "";
$params = [];

switch ($filter) {
    case 'pending':
        $whereClause = "WHERE p.status = 'pending'";
        break;
    case 'active':
        $whereClause = "WHERE p.status = 'active'";
        break;
    case 'rejected':
        $whereClause = "WHERE p.status = 'rejected'";
        break;
    case 'inactive':
        $whereClause = "WHERE p.status = 'inactive'";
        break;
    case 'all':
    default:
        $whereClause = "";
        break;
}

// Get products
$products = $database->fetchAll("
    SELECT p.*, c.name as category_name, v.shop_name, 
           u.first_name, u.last_name, u.email,
           pi.image_url
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN users u ON v.user_id = u.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    {$whereClause}
    ORDER BY p.created_at DESC
    LIMIT 50
", $params);

// Get counts for filters
$counts = [
    'all' => $database->count('products'),
    'pending' => $database->count('products', "status = 'pending'"),
    'active' => $database->count('products', "status = 'active'"),
    'rejected' => $database->count('products', "status = 'rejected'"),
    'inactive' => $database->count('products', "status = 'inactive'")
];
?>

<div class="min-h-screen bg-gray-50">
    <!-- Mobile Header -->
    <div class="lg:hidden bg-white shadow-sm border-b px-4 py-3">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Product Management</h1>
            <button onclick="toggleAdminSidebar()" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Admin Sidebar -->
        <div id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-secondary transform -translate-x-full transition-transform lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex items-center justify-between p-6 border-b border-gray-600">
                <h2 class="text-xl font-bold text-white">Admin Panel</h2>
                <button onclick="toggleAdminSidebar()" class="lg:hidden text-white hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="mt-6">
                <div class="px-6 py-3">
                    <span class="text-xs uppercase text-gray-400 font-semibold">Main</span>
                </div>
                <a href="?page=admin" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="?page=admin&section=vendors" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-store mr-3"></i>Vendors
                </a>
                <a href="?page=admin&section=products" class="flex items-center px-6 py-3 text-white bg-primary">
                    <i class="fas fa-box mr-3"></i>Products
                </a>
                <a href="?page=admin&section=users" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-users mr-3"></i>Users
                </a>
                <a href="?page=admin&section=orders" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-shopping-cart mr-3"></i>Orders
                </a>
                <a href="?page=logout" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                </a>
            </nav>
        </div>

        <!-- Sidebar Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-50 z-40 lg:hidden hidden" onclick="toggleAdminSidebar()"></div>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-0">
            <div class="p-4 lg:p-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-2">Product Management</h1>
                    <p class="text-gray-600">Review and approve vendor products</p>
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

                <!-- Filter Tabs -->
                <div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
                    <div class="flex flex-wrap border-b border-gray-200">
                        <a href="?page=admin&section=products&filter=pending" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'pending' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Pending Review (<?php echo $counts['pending']; ?>)
                        </a>
                        <a href="?page=admin&section=products&filter=active" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'active' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Active (<?php echo $counts['active']; ?>)
                        </a>
                        <a href="?page=admin&section=products&filter=inactive" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'inactive' ? 'border-gray-500 text-gray-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Suspended (<?php echo $counts['inactive']; ?>)
                        </a>
                        <a href="?page=admin&section=products&filter=rejected" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'rejected' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Rejected (<?php echo $counts['rejected']; ?>)
                        </a>
                        <a href="?page=admin&section=products&filter=all" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            All (<?php echo $counts['all']; ?>)
                        </a>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($products as $product): ?>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-200">
                            <!-- Product Image -->
                            <div class="relative h-48 bg-gray-200">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo SITE_URL . $product['image_url']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <i class="fas fa-image text-gray-400 text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Status Badge -->
                                <div class="absolute top-3 left-3">
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
                                </div>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="p-4">
                                <div class="mb-2">
                                    <h3 class="font-semibold text-gray-900 text-sm line-clamp-2 mb-1">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>
                                    <p class="text-xs text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Price:</span>
                                        <span class="font-semibold"><?php echo formatPrice($product['price']); ?></span>
                                    </div>
                                    <?php if ($product['sale_price']): ?>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Sale:</span>
                                            <span class="font-semibold text-red-600"><?php echo formatPrice($product['sale_price']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Stock:</span>
                                        <span class="font-semibold"><?php echo $product['stock_quantity']; ?></span>
                                    </div>
                                </div>
                                
                                <!-- Vendor Info -->
                                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                    <div class="text-xs text-gray-500 mb-1">Vendor:</div>
                                    <div class="font-medium text-sm text-gray-900"><?php echo htmlspecialchars($product['shop_name'] ?: 'N/A'); ?></div>
                                    <div class="text-xs text-gray-600"><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex flex-wrap gap-2">
                                    <?php if ($product['status'] === 'pending'): ?>
                                        <form method="POST" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="approve_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="w-full bg-green-500 text-white px-3 py-2 rounded text-xs hover:bg-green-600 transition-colors"
                                                    onclick="return confirm('Approve this product?')">
                                                <i class="fas fa-check mr-1"></i>Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="flex-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="reject_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="w-full bg-red-500 text-white px-3 py-2 rounded text-xs hover:bg-red-600 transition-colors"
                                                    onclick="return confirm('Reject this product?')">
                                                <i class="fas fa-times mr-1"></i>Reject
                                            </button>
                                        </form>
                                    <?php elseif ($product['status'] === 'active'): ?>
                                        <form method="POST" class="w-full">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="suspend_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="w-full bg-orange-500 text-white px-3 py-2 rounded text-xs hover:bg-orange-600 transition-colors"
                                                    onclick="return confirm('Suspend this product?')">
                                                <i class="fas fa-pause mr-1"></i>Suspend
                                            </button>
                                        </form>
                                    <?php elseif ($product['status'] === 'inactive'): ?>
                                        <form method="POST" class="w-full">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="activate_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="w-full bg-green-500 text-white px-3 py-2 rounded text-xs hover:bg-green-600 transition-colors"
                                                    onclick="return confirm('Activate this product?')">
                                                <i class="fas fa-play mr-1"></i>Activate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Additional Info -->
                                <div class="mt-3 pt-3 border-t border-gray-200 text-xs text-gray-500">
                                    <div>Category: <?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?></div>
                                    <div>Added: <?php echo date('M j, Y', strtotime($product['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                        <i class="fas fa-box text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No Products Found</h3>
                        <p class="text-gray-500">No products match the current filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>
