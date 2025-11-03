<?php
$pageTitle = 'Admin Dashboard';
$pageDescription = 'Admin control panel for Sasto Hub';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo '<script>window.location.href = "?page=login";</script>';
    exit();
}

// Handle different admin sections
$section = $_GET['section'] ?? 'dashboard';

if ($section === 'vendors') {
    include __DIR__ . '/vendors.php';
    return;
} elseif ($section === 'products') {
    include __DIR__ . '/products.php';
    return;
} elseif ($section === 'categories') {
    include __DIR__ . '/categories.php';
    return;
} elseif ($section === 'users') {
    include __DIR__ . '/users.php';
    return;
}

global $database;

// Get statistics
$stats = [
    'total_users' => $database->count('users'),
    'total_vendors' => $database->count('users', 'user_type = ?', ['vendor']),
    'total_customers' => $database->count('users', 'user_type = ?', ['customer']),
    'pending_vendors' => $database->count('users', 'user_type = ? AND status = ?', ['vendor', 'pending']),
    'active_vendors' => $database->count('users', 'user_type = ? AND status = ?', ['vendor', 'active']),
    'total_products' => $database->count('products'),
    'pending_products' => $database->count('products', 'status = ?', ['pending']),
    'active_products' => $database->count('products', 'status = ?', ['active']),
    'rejected_products' => $database->count('products', 'status = ?', ['rejected']),
    'total_categories' => $database->count('categories'),
    'active_categories' => $database->count('categories', 'is_active = ?', [1]),
    'total_orders' => $database->count('orders')
];

// Recent activities
$recentVendors = $database->fetchAll("
    SELECT u.*, v.shop_name 
    FROM users u 
    LEFT JOIN vendors v ON u.id = v.user_id 
    WHERE u.user_type = 'vendor' 
    ORDER BY u.created_at DESC 
    LIMIT 5
");

$recentProducts = $database->fetchAll("
    SELECT p.*, v.shop_name, u.first_name, u.last_name,
           pi.image_url
    FROM products p
    JOIN vendors v ON p.vendor_id = v.id
    JOIN users u ON v.user_id = u.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    ORDER BY p.created_at DESC
    LIMIT 5
");
?>

<!-- Modern Admin Dashboard -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50" style="padding-top: 80px;">
    
    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-16 left-0 right-0 bg-white shadow-lg border-b px-4 py-4 z-30">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tachometer-alt text-white text-sm"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-800">Admin Dashboard</h1>
            </div>
            <button onclick="toggleAdminSidebar()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                <i class="fas fa-bars text-gray-600"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Modern Admin Sidebar -->
        <div id="admin-sidebar" class="fixed top-16 bottom-0 left-0 z-20 w-72 bg-white shadow-2xl transform -translate-x-full transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:h-auto border-r border-gray-200">
            
            <!-- Admin Profile Header -->
            <div class="p-6 bg-gradient-to-r from-blue-500 to-indigo-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-user-shield text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Admin Panel</h2>
                            <div class="flex items-center text-blue-100 text-sm">
                                <i class="fas fa-check-circle mr-1"></i>Administrator
                            </div>
                        </div>
                    </div>
                    <button onclick="toggleAdminSidebar()" class="lg:hidden p-1 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <div class="px-3 py-2">
                    <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Dashboard</span>
                </div>
                
                <a href="?page=admin" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-white bg-gradient-to-r from-blue-500 to-indigo-500 shadow-lg">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                        <i class="fas fa-tachometer-alt text-sm"></i>
                    </div>
                    <span class="font-medium">Overview</span>
                    <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <div class="px-3 py-2">
                        <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Management</span>
                    </div>
                    
                    <a href="?page=admin&section=vendors" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-store text-sm"></i>
                        </div>
                        <span class="font-medium">Vendors</span>
                        <?php if ($stats['pending_vendors'] > 0): ?>
                            <div class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['pending_vendors']; ?></div>
                        <?php endif; ?>
                    </a>
                    
                    <a href="?page=admin&section=products" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-box text-sm"></i>
                        </div>
                        <span class="font-medium">Products</span>
                        <?php if ($stats['pending_products'] > 0): ?>
                            <div class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['pending_products']; ?></div>
                        <?php endif; ?>
                    </a>
                    
                    <a href="?page=admin&section=categories" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-tags text-sm"></i>
                        </div>
                        <span class="font-medium">Categories</span>
                    </a>
                    
                    <a href="?page=admin&section=users" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-users text-sm"></i>
                        </div>
                        <span class="font-medium">Users</span>
                    </a>
                </div>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <div class="px-3 py-2">
                        <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Account</span>
                    </div>
                    
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
        <div id="admin-overlay" class="fixed inset-0 bg-black/50 z-10 lg:hidden hidden backdrop-blur-sm" onclick="toggleAdminSidebar()"></div>

        <!-- Main Content Area -->
        <div class="flex-1 lg:ml-0 pt-20 lg:pt-0">
            <div class="p-6 lg:p-8 max-w-7xl mx-auto">
                
                <!-- Welcome Header -->
                <div class="mb-8">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 rounded-3xl p-8 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-4xl font-bold mb-2">Welcome back, Admin!</h1>
                                <p class="text-blue-100 text-lg">Here's what's happening with your platform today.</p>
                            </div>
                            <div class="hidden lg:block">
                                <div class="w-24 h-24 bg-white/20 rounded-3xl flex items-center justify-center">
                                    <i class="fas fa-chart-line text-4xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Users -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl shadow-lg">
                                <i class="fas fa-users text-white text-2xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($stats['total_users']); ?></p>
                            <p class="text-sm text-gray-600">Total Users</p>
                            <p class="text-xs text-blue-600 mt-2">
                                <?php echo $stats['total_customers']; ?> customers • <?php echo $stats['total_vendors']; ?> vendors
                            </p>
                        </div>
                    </div>

                    <!-- Pending Vendors -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-2xl shadow-lg">
                                <i class="fas fa-clock text-white text-2xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo $stats['pending_vendors']; ?></p>
                            <p class="text-sm text-gray-600">Pending Vendors</p>
                            <p class="text-xs text-green-600 mt-2"><?php echo $stats['active_vendors']; ?> active vendors</p>
                        </div>
                    </div>

                    <!-- Total Products -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl shadow-lg">
                                <i class="fas fa-box text-white text-2xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($stats['total_products']); ?></p>
                            <p class="text-sm text-gray-600">Total Products</p>
                            <p class="text-xs text-yellow-600 mt-2"><?php echo $stats['pending_products']; ?> pending approval</p>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl shadow-lg">
                                <i class="fas fa-tags text-white text-2xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo $stats['total_categories']; ?></p>
                            <p class="text-sm text-gray-600">Categories</p>
                            <p class="text-xs text-green-600 mt-2"><?php echo $stats['active_categories']; ?> active</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="?page=admin&section=vendors&filter=pending" 
                           class="p-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-user-check text-2xl mb-2"></i>
                            <p class="font-semibold">Review Vendors</p>
                            <p class="text-sm opacity-90"><?php echo $stats['pending_vendors']; ?> pending</p>
                        </a>
                        
                        <a href="?page=admin&section=products&filter=pending" 
                           class="p-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-box-open text-2xl mb-2"></i>
                            <p class="font-semibold">Review Products</p>
                            <p class="text-sm opacity-90"><?php echo $stats['pending_products']; ?> pending</p>
                        </a>
                        
                        <a href="?page=admin&section=categories&action=add" 
                           class="p-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-plus-circle text-2xl mb-2"></i>
                            <p class="font-semibold">Add Category</p>
                            <p class="text-sm opacity-90">Create new</p>
                        </a>
                        
                        <a href="?page=admin&section=users" 
                           class="p-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-users-cog text-2xl mb-2"></i>
                            <p class="font-semibold">Manage Users</p>
                            <p class="text-sm opacity-90">View all</p>
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Vendors -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-900">Recent Vendor Applications</h3>
                                <a href="?page=admin&section=vendors" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">View All</a>
                            </div>
                        </div>
                        <div class="p-6">
                            <?php if (empty($recentVendors)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-store text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">No vendor applications yet</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recentVendors as $vendor): ?>
                                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                                                    <i class="fas fa-store text-orange-600"></i>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($vendor['shop_name'] ?: ($vendor['first_name'] . ' ' . $vendor['last_name'])); ?></p>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($vendor['email']); ?></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo 
                                                    $vendor['status'] === 'active' ? 'bg-green-100 text-green-800' :
                                                    ($vendor['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                                ?>">
                                                    <?php echo ucfirst($vendor['status']); ?>
                                                </span>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($vendor['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Products -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-900">Recent Product Submissions</h3>
                                <a href="?page=admin&section=products" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">View All</a>
                            </div>
                        </div>
                        <div class="p-6">
                            <?php if (empty($recentProducts)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-box text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">No product submissions yet</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recentProducts as $product): ?>
                                        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl">
                                            <div class="w-12 h-12 bg-white rounded-lg overflow-hidden">
                                                <?php if ($product['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                         class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($product['name']); ?></p>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($product['shop_name']); ?></p>
                                                <p class="text-xs text-gray-500">Rs. <?php echo number_format($product['price'], 2); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo 
                                                    $product['status'] === 'active' ? 'bg-green-100 text-green-800' :
                                                    ($product['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                                ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M j', strtotime($product['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-overlay');
    
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>