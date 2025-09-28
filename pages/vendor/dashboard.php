<?php
$pageTitle = 'Vendor Dashboard';
$pageDescription = 'Manage your products and orders';

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
    SELECT DISTINCT o.*, u.first_name, u.last_name, u.email,
           (SELECT SUM(oi2.total) FROM order_items oi2 WHERE oi2.order_id = o.id AND oi2.vendor_id = ?) as vendor_amount
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.user_id = u.id
    WHERE oi.vendor_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10
", [$vendor['id'], $vendor['id']]);

// Low stock products
$lowStockProducts = $database->fetchAll("
    SELECT * FROM products 
    WHERE vendor_id = ? AND stock_quantity <= min_stock_level AND status = 'active'
    ORDER BY stock_quantity ASC
    LIMIT 10
", [$vendor['id']]);
?>

<div class="min-h-screen bg-gray-50">
    <!-- Mobile Header -->
    <div class="lg:hidden bg-white shadow-sm border-b px-4 py-3">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Vendor Dashboard</h1>
            <button onclick="toggleVendorSidebar()" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Sidebar -->
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
                        <?php if ($vendor['is_verified']): ?>
                            <i class="fas fa-check-circle text-green-400 mr-1"></i>Verified
                        <?php else: ?>
                            <i class="fas fa-clock text-yellow-400 mr-1"></i>Pending
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <nav class="mt-6">
                <a href="?page=vendor" class="flex items-center px-6 py-3 text-white bg-primary">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="?page=vendor&section=products" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-box mr-3"></i>My Products
                </a>
                <a href="?page=vendor&section=orders" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-shopping-cart mr-3"></i>Orders
                </a>
                <a href="?page=vendor&section=analytics" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-chart-bar mr-3"></i>Analytics
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
                <!-- Header (Desktop) -->
                <div class="hidden lg:block mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Vendor Dashboard</h1>
                    <p class="text-gray-600">Welcome back to <?php echo htmlspecialchars($vendor['shop_name']); ?>!</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-full">
                                <i class="fas fa-box text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Products</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_products']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-full">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Active</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['active_products']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-full">
                                <i class="fas fa-shopping-cart text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Orders</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_orders']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-full">
                                <i class="fas fa-clock text-orange-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending_orders']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6 col-span-2 lg:col-span-1">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-full">
                                <i class="fas fa-dollar-sign text-yellow-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Revenue</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo formatPrice($stats['total_revenue']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8">
                    <!-- Recent Orders -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-4 lg:p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Recent Orders</h3>
                        </div>
                        <div class="overflow-hidden">
                            <?php if (empty($recentOrders)): ?>
                                <div class="p-6 text-center text-gray-500">
                                    <i class="fas fa-shopping-cart text-4xl mb-4"></i>
                                    <p>No orders yet</p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 hidden lg:table-header-group">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr class="lg:table-row block border-b lg:border-0 mb-4 lg:mb-0">
                                                    <td class="px-4 lg:px-6 py-3 lg:py-4 block lg:table-cell">
                                                        <div class="lg:hidden text-sm font-medium text-gray-500 mb-1">Order:</div>
                                                        <div class="text-sm font-medium text-gray-900">#<?php echo $order['order_number']; ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                                    </td>
                                                    <td class="px-4 lg:px-6 py-3 lg:py-4 block lg:table-cell">
                                                        <div class="lg:hidden text-sm font-medium text-gray-500 mb-1">Customer:</div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['email']); ?></div>
                                                    </td>
                                                    <td class="px-4 lg:px-6 py-3 lg:py-4 block lg:table-cell">
                                                        <div class="lg:hidden text-sm font-medium text-gray-500 mb-1">Your Amount:</div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo formatPrice($order['vendor_amount']); ?></div>
                                                    </td>
                                                    <td class="px-4 lg:px-6 py-3 lg:py-4 block lg:table-cell">
                                                        <div class="lg:hidden text-sm font-medium text-gray-500 mb-1">Status:</div>
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                            <?php 
                                                            switch($order['status']) {
                                                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                                case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
                                                                case 'shipped': echo 'bg-indigo-100 text-indigo-800'; break;
                                                                case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                                default: echo 'bg-gray-100 text-gray-800';
                                                            }
                                                            ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-4 lg:p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Low Stock Alert</h3>
                        </div>
                        <div class="p-4 lg:p-6">
                            <?php if (empty($lowStockProducts)): ?>
                                <div class="text-center text-gray-500">
                                    <i class="fas fa-check-circle text-4xl mb-4"></i>
                                    <p>All products have sufficient stock</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <div class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <div>
                                                <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <p class="text-sm text-gray-600">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                                                <p class="text-sm text-red-600">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Only <?php echo $product['stock_quantity']; ?> left
                                                </p>
                                            </div>
                                            <a href="?page=vendor&section=products&action=edit&id=<?php echo $product['id']; ?>" 
                                               class="bg-primary text-white px-3 py-1 rounded text-sm hover:bg-opacity-90">
                                                Update Stock
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-8 bg-white rounded-lg shadow p-4 lg:p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="?page=vendor&section=products&action=add" 
                           class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition duration-200">
                            <i class="fas fa-plus text-2xl text-gray-400 mb-2"></i>
                            <span class="text-sm font-medium text-gray-600">Add Product</span>
                        </a>
                        
                        <a href="?page=vendor&section=orders" 
                           class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition duration-200">
                            <i class="fas fa-shopping-cart text-2xl text-gray-400 mb-2"></i>
                            <span class="text-sm font-medium text-gray-600">View Orders</span>
                        </a>
                        
                        <a href="?page=vendor&section=analytics" 
                           class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition duration-200">
                            <i class="fas fa-chart-bar text-2xl text-gray-400 mb-2"></i>
                            <span class="text-sm font-medium text-gray-600">Analytics</span>
                        </a>
                        
                        <a href="?page=vendor&section=profile" 
                           class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition duration-200">
                            <i class="fas fa-store text-2xl text-gray-400 mb-2"></i>
                            <span class="text-sm font-medium text-gray-600">Shop Settings</span>
                        </a>
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

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('vendor-sidebar');
    const overlay = document.getElementById('vendor-overlay');
    const toggleButton = event.target.closest('[onclick="toggleVendorSidebar()"]');
    
    if (!toggleButton && !sidebar.contains(event.target) && !sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
});
</script>
