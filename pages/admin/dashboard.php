<?php
$pageTitle = 'Admin Dashboard';
$pageDescription = 'Admin control panel for Sasto Hub';

// Redirect if not admin
if (!isAdmin()) {
    redirectTo('?page=login');
}

// Handle different admin sections
$section = $_GET['section'] ?? 'dashboard';

global $database;

// Get dashboard statistics
$stats = [
    'total_users' => $database->count('users'),
    'total_vendors' => $database->count('vendors'),
    'total_products' => $database->count('products'),
    'total_orders' => $database->count('orders'),
    'pending_orders' => $database->count('orders', 'status = ?', ['pending']),
    'total_revenue' => $database->fetchOne("SELECT SUM(total_amount) as revenue FROM orders WHERE payment_status = 'paid'")['revenue'] ?? 0
];

// Recent orders
$recentOrders = $database->fetchAll("
    SELECT o.*, u.first_name, u.last_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Pending vendor approvals
$pendingVendors = $database->fetchAll("
    SELECT v.*, u.first_name, u.last_name, u.email, u.created_at
    FROM vendors v
    JOIN users u ON v.user_id = u.id
    WHERE u.status = 'pending'
    ORDER BY u.created_at DESC
");

// Handle vendor approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'];
        $userId = intval($_POST['user_id'] ?? 0);
        
        if ($action === 'approve_vendor' && $userId > 0) {
            $database->update('users', ['status' => 'active'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
            $success = 'Vendor approved successfully';
        } elseif ($action === 'reject_vendor' && $userId > 0) {
            $database->update('users', ['status' => 'inactive'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
            $success = 'Vendor rejected';
        }
        
        // Refresh data
        $pendingVendors = $database->fetchAll("
            SELECT v.*, u.first_name, u.last_name, u.email, u.created_at
            FROM vendors v
            JOIN users u ON v.user_id = u.id
            WHERE u.status = 'pending'
            ORDER BY u.created_at DESC
        ");
    }
}
?>

<div class="min-h-screen bg-gray-50">
    <!-- Mobile Header -->
    <div class="lg:hidden bg-white shadow-sm border-b px-4 py-3">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Admin Dashboard</h1>
            <button onclick="toggleSidebar()" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Sidebar -->
        <div id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-secondary transform -translate-x-full transition-transform lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex items-center justify-between p-6 border-b border-gray-600">
                <h2 class="text-xl font-bold text-white">Admin Panel</h2>
                <button onclick="toggleSidebar()" class="lg:hidden text-white hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="mt-6">
                <div class="px-6 py-3">
                    <span class="text-xs uppercase text-gray-400 font-semibold">Main</span>
                </div>
                <a href="?page=admin" class="flex items-center px-6 py-3 text-white bg-primary">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="?page=admin&section=users" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-users mr-3"></i>Users
                </a>
                <a href="?page=admin&section=vendors" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-store mr-3"></i>Vendors
                </a>
                <a href="?page=admin&section=products" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-box mr-3"></i>Products
                </a>
                <a href="?page=admin&section=orders" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-shopping-cart mr-3"></i>Orders
                </a>
                <a href="?page=admin&section=categories" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-tags mr-3"></i>Categories
                </a>
                
                <div class="px-6 py-3 mt-6">
                    <span class="text-xs uppercase text-gray-400 font-semibold">Settings</span>
                </div>
                <a href="?page=admin&section=settings" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-cog mr-3"></i>Site Settings
                </a>
                <a href="?page=logout" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                </a>
            </nav>
        </div>

        <!-- Sidebar Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-0">
            <div class="p-4 lg:p-8">
                <!-- Header (Desktop) -->
                <div class="hidden lg:block mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                    <p class="text-gray-600">Welcome back, <?php echo $_SESSION['first_name']; ?>!</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 lg:gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-full">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Users</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-full">
                                <i class="fas fa-store text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Vendors</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_vendors']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-full">
                                <i class="fas fa-box text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Products</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_products']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-full">
                                <i class="fas fa-shopping-cart text-orange-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Orders</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_orders']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-full">
                                <i class="fas fa-clock text-red-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-lg lg:text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending_orders']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 lg:p-6">
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
                                                        <div class="lg:hidden text-sm font-medium text-gray-500 mb-1">Amount:</div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo formatPrice($order['total_amount']); ?></div>
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

                    <!-- Pending Vendor Approvals -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-4 lg:p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800">Pending Vendor Approvals</h3>
                        </div>
                        <div class="p-4 lg:p-6">
                            <?php if (empty($pendingVendors)): ?>
                                <div class="text-center text-gray-500">
                                    <i class="fas fa-check-circle text-4xl mb-4"></i>
                                    <p>No pending approvals</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($pendingVendors as $vendor): ?>
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                                <div class="mb-3 lg:mb-0">
                                                    <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($vendor['shop_name']); ?></h4>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></p>
                                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></p>
                                                    <p class="text-xs text-gray-400">Applied <?php echo timeAgo($vendor['created_at']); ?></p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="action" value="approve_vendor">
                                                        <input type="hidden" name="user_id" value="<?php echo $vendor['user_id']; ?>">
                                                        <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                                                            <i class="fas fa-check mr-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="action" value="reject_vendor">
                                                        <input type="hidden" name="user_id" value="<?php echo $vendor['user_id']; ?>">
                                                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                                                            <i class="fas fa-times mr-1"></i>Reject
                                                        </button>
                                                    </form>
                                                </div>
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
function toggleSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggleButton = event.target.closest('[onclick="toggleSidebar()"]');
    
    if (!toggleButton && !sidebar.contains(event.target) && !sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
});
</script>
