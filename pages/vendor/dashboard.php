<?php
$pageTitle = 'Vendor Dashboard';
$pageDescription = 'Manage your products and orders';

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    ?>
    <script>window.location.href = "?page=login";</script>
    <?php
    exit();
}

global $database;

// Get vendor info
$vendor = $database->fetchOne("SELECT * FROM vendors WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$vendor) {
    ?>
    <script>window.location.href = "?page=register&type=vendor";</script>
    <?php
    exit();
}

// Check if vendor is verified
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'active') {
    include __DIR__ . '/verification-pending.php';
    return;
}

// Handle different vendor sections
$section = $_GET['section'] ?? 'dashboard';

switch ($section) {
    case 'products':
        include __DIR__ . '/products.php';
        return;
    case 'orders':
        include __DIR__ . '/orders.php';
        return;
    case 'analytics':
        include __DIR__ . '/analytics.php';
        return;
    case 'profile':
        include __DIR__ . '/profile.php';
        return;
}

// Get vendor statistics
$stats = [
    'total_products' => $database->count('products', 'vendor_id = ?', [$vendor['id']]),
    'active_products' => $database->count('products', 'vendor_id = ? AND status = ?', [$vendor['id'], 'active']),
    'total_orders' => $database->count('order_items', 'vendor_id = ?', [$vendor['id']]),
    'pending_orders' => $database->fetchOne("
        SELECT COUNT(DISTINCT oi.order_id) as count 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE oi.vendor_id = ? AND o.status IN ('pending', 'confirmed')
    ", [$vendor['id']])['count'],
    'total_revenue' => $database->fetchOne("
        SELECT SUM(oi.total) as revenue 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE oi.vendor_id = ? AND o.payment_status = 'paid'
    ", [$vendor['id']])['revenue'] ?? 0
];

// Recent orders
$recentOrders = $database->fetchAll("
    SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
           u.first_name, u.last_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.user_id = u.id
    WHERE oi.vendor_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
", [$vendor['id']]);
?>

<!-- Modern Vendor Dashboard -->
<div class="min-h-screen bg-gradient-to-br from-orange-50 to-red-50" style="padding-top: 80px;">
    
    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-16 left-0 right-0 bg-white shadow-lg border-b px-4 py-4 z-30">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-store text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($vendor['shop_name']); ?></h1>
                    <p class="text-xs text-gray-500">Vendor Dashboard</p>
                </div>
            </div>
            <button onclick="toggleVendorSidebar()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                <i class="fas fa-bars text-gray-600"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Modern Vendor Sidebar -->
        <div id="vendor-sidebar" class="fixed top-16 bottom-0 left-0 z-20 w-72 bg-white shadow-2xl transform -translate-x-full transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:h-auto border-r border-gray-200">
            
            <!-- Vendor Profile Header -->
            <div class="p-6 bg-gradient-to-r from-orange-500 to-red-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-store text-white text-xl"></i>
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
                
                <a href="?page=vendor" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                        <i class="fas fa-tachometer-alt text-sm"></i>
                    </div>
                    <span class="font-medium">Overview</span>
                    <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                </a>
                
                <a href="?page=vendor&section=products" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-box text-sm"></i>
                    </div>
                    <span class="font-medium">Products</span>
                </a>
                
                <a href="?page=vendor&section=orders" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-shopping-cart text-sm"></i>
                    </div>
                    <span class="font-medium">Orders</span>
                </a>
                
                <a href="?page=vendor&section=analytics" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    <span class="font-medium">Analytics</span>
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <div class="px-3 py-2">
                        <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Account</span>
                    </div>
                    
                    <a href="?page=vendor&section=profile" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
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
        <div id="vendor-overlay" class="fixed inset-0 bg-black/50 z-10 lg:hidden hidden backdrop-blur-sm" onclick="toggleVendorSidebar()"></div>

        <!-- Main Content Area -->
        <div class="flex-1 lg:ml-0 pt-20 lg:pt-0">
            <div class="p-6 lg:p-8 max-w-7xl mx-auto">
                
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-store text-white text-xl"></i>
                            </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Vendor Dashboard</h1>
                            <p class="text-gray-600 mt-1">Welcome back to your store management</p>
                            </div>
                        </div>
                    </div>

                <!-- Modern Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Products -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl shadow-lg">
                                <i class="fas fa-box text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-2 py-1 rounded-full">PRODUCTS</span>
                            </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($stats['total_products']); ?></p>
                            <p class="text-sm text-gray-600">Total Products</p>
                        </div>
                    </div>

                    <!-- Active Products -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl shadow-lg">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-green-600 bg-green-100 px-2 py-1 rounded-full">ACTIVE</span>
                            </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($stats['active_products']); ?></p>
                            <p class="text-sm text-gray-600">Active Products</p>
                        </div>
                    </div>

                    <!-- Total Orders -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl shadow-lg">
                                <i class="fas fa-shopping-cart text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-purple-600 bg-purple-100 px-2 py-1 rounded-full">ORDERS</span>
                            </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($stats['total_orders']); ?></p>
                            <p class="text-sm text-gray-600">Total Orders</p>
                        </div>
                    </div>

                    <!-- Total Revenue -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-2xl shadow-lg">
                                <i class="fas fa-dollar-sign text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-yellow-600 bg-yellow-100 px-2 py-1 rounded-full">REVENUE</span>
                            </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo formatPrice($stats['total_revenue']); ?></p>
                            <p class="text-sm text-gray-600">Total Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders and Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Recent Orders -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Recent Orders</h3>
                            <a href="?page=vendor&section=orders" class="text-orange-600 hover:text-orange-700 font-medium text-sm">View All</a>
                    </div>

                        <?php if (empty($recentOrders)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">No orders yet</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                            <div>
                                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($order['order_number']); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-gray-900"><?php echo formatPrice($order['total_amount']); ?></p>
                                            <span class="text-xs px-2 py-1 rounded-full <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-4">
                        <a href="?page=vendor&section=products&action=add" 
                               class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-2xl hover:border-orange-500 hover:bg-orange-50 transition-all duration-200 group">
                                <div class="w-12 h-12 bg-orange-100 group-hover:bg-orange-200 rounded-2xl flex items-center justify-center mb-3">
                                    <i class="fas fa-plus text-orange-600 text-xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-orange-600">Add Product</span>
                        </a>
                        
                        <a href="?page=vendor&section=orders" 
                               class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-2xl hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 group">
                                <div class="w-12 h-12 bg-blue-100 group-hover:bg-blue-200 rounded-2xl flex items-center justify-center mb-3">
                                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-600">View Orders</span>
                        </a>
                        
                        <a href="?page=vendor&section=analytics" 
                               class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-2xl hover:border-green-500 hover:bg-green-50 transition-all duration-200 group">
                                <div class="w-12 h-12 bg-green-100 group-hover:bg-green-200 rounded-2xl flex items-center justify-center mb-3">
                                    <i class="fas fa-chart-line text-green-600 text-xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-green-600">Analytics</span>
                        </a>
                        
                        <a href="?page=vendor&section=profile" 
                               class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-2xl hover:border-purple-500 hover:bg-purple-50 transition-all duration-200 group">
                                <div class="w-12 h-12 bg-purple-100 group-hover:bg-purple-200 rounded-2xl flex items-center justify-center mb-3">
                                    <i class="fas fa-user text-purple-600 text-xl"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-purple-600">Profile</span>
                            </a>
                        </div>
                    </div>
                </div>
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
