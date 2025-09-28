<?php
$pageTitle = 'Vendor Management';
$pageDescription = 'Manage vendor accounts and verification';

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
        $userId = intval($_POST['user_id'] ?? 0);
        
        if ($action === 'approve_vendor' && $userId > 0) {
            $database->update('users', ['status' => 'active'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
            $success = 'Vendor approved successfully!';
        } elseif ($action === 'reject_vendor' && $userId > 0) {
            $database->update('users', ['status' => 'rejected'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
            $success = 'Vendor rejected';
        } elseif ($action === 'suspend_vendor' && $userId > 0) {
            $database->update('users', ['status' => 'inactive'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
            $success = 'Vendor suspended';
        } elseif ($action === 'reactivate_vendor' && $userId > 0) {
            $database->update('users', ['status' => 'active'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
            $success = 'Vendor reactivated';
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$whereClause = "WHERE u.user_type = 'vendor'";
$params = [];

switch ($filter) {
    case 'pending':
        $whereClause .= " AND u.status = 'pending'";
        break;
    case 'active':
        $whereClause .= " AND u.status = 'active'";
        break;
    case 'rejected':
        $whereClause .= " AND u.status = 'rejected'";
        break;
    case 'inactive':
        $whereClause .= " AND u.status = 'inactive'";
        break;
}

// Get vendors
$vendors = $database->fetchAll("
    SELECT u.*, v.shop_name, v.shop_description, v.phone, v.address,
           (SELECT COUNT(*) FROM products p WHERE p.vendor_id = v.id) as total_products,
           (SELECT COUNT(*) FROM products p WHERE p.vendor_id = v.id AND p.status = 'active') as active_products
    FROM users u
    LEFT JOIN vendors v ON u.id = v.user_id
    {$whereClause}
    ORDER BY u.created_at DESC
", $params);

// Get counts for filters
$counts = [
    'all' => $database->count('users', "user_type = 'vendor'"),
    'pending' => $database->count('users', "user_type = 'vendor' AND status = 'pending'"),
    'active' => $database->count('users', "user_type = 'vendor' AND status = 'active'"),
    'rejected' => $database->count('users', "user_type = 'vendor' AND status = 'rejected'"),
    'inactive' => $database->count('users', "user_type = 'vendor' AND status = 'inactive'")
];
?>

<div class="min-h-screen bg-gray-50">
    <!-- Mobile Header -->
    <div class="lg:hidden bg-white shadow-sm border-b px-4 py-3">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">Vendor Management</h1>
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
                <a href="?page=admin&section=vendors" class="flex items-center px-6 py-3 text-white bg-primary">
                    <i class="fas fa-store mr-3"></i>Vendors
                </a>
                <a href="?page=admin&section=products" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
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
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-2">Vendor Management</h1>
                    <p class="text-gray-600">Manage vendor registrations and accounts</p>
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
                        <a href="?page=admin&section=vendors&filter=all" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            All Vendors (<?php echo $counts['all']; ?>)
                        </a>
                        <a href="?page=admin&section=vendors&filter=pending" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'pending' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Pending (<?php echo $counts['pending']; ?>)
                        </a>
                        <a href="?page=admin&section=vendors&filter=active" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'active' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Active (<?php echo $counts['active']; ?>)
                        </a>
                        <a href="?page=admin&section=vendors&filter=inactive" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'inactive' ? 'border-gray-500 text-gray-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Suspended (<?php echo $counts['inactive']; ?>)
                        </a>
                        <a href="?page=admin&section=vendors&filter=rejected" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'rejected' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Rejected (<?php echo $counts['rejected']; ?>)
                        </a>
                    </div>
                </div>

                <!-- Vendors List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <?php if (empty($vendors)): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-store text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Vendors Found</h3>
                            <p class="text-gray-500">No vendors match the current filter.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop Info</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($vendors as $vendor): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-lg mr-4">
                                                        <i class="fas fa-store"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></div>
                                                        <?php if ($vendor['phone']): ?>
                                                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($vendor['phone']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['shop_name'] ?: 'N/A'); ?></div>
                                                    <?php if ($vendor['shop_description']): ?>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($vendor['shop_description'], 0, 50) . '...'); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($vendor['address']): ?>
                                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($vendor['address']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-lg font-semibold"><?php echo $vendor['total_products']; ?></span>
                                                    <span class="text-gray-500">total</span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm text-green-600 font-medium"><?php echo $vendor['active_products']; ?></span>
                                                    <span class="text-xs text-gray-400">active</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    <?php 
                                                    switch($vendor['status']) {
                                                        case 'active': echo 'bg-green-100 text-green-800'; break;
                                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                        case 'inactive': echo 'bg-gray-100 text-gray-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($vendor['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('M j, Y', strtotime($vendor['created_at'])); ?>
                                                <div class="text-xs text-gray-500"><?php echo timeAgo($vendor['created_at']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <?php if ($vendor['status'] === 'pending'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="approve_vendor">
                                                            <input type="hidden" name="user_id" value="<?php echo $vendor['id']; ?>">
                                                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600" 
                                                                    onclick="return confirm('Approve this vendor?')">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="reject_vendor">
                                                            <input type="hidden" name="user_id" value="<?php echo $vendor['id']; ?>">
                                                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600"
                                                                    onclick="return confirm('Reject this vendor?')">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </form>
                                                    <?php elseif ($vendor['status'] === 'active'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="suspend_vendor">
                                                            <input type="hidden" name="user_id" value="<?php echo $vendor['id']; ?>">
                                                            <button type="submit" class="bg-orange-500 text-white px-3 py-1 rounded text-xs hover:bg-orange-600"
                                                                    onclick="return confirm('Suspend this vendor?')">
                                                                <i class="fas fa-pause"></i> Suspend
                                                            </button>
                                                        </form>
                                                    <?php elseif ($vendor['status'] === 'inactive'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="action" value="reactivate_vendor">
                                                            <input type="hidden" name="user_id" value="<?php echo $vendor['id']; ?>">
                                                            <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600"
                                                                    onclick="return confirm('Reactivate this vendor?')">
                                                                <i class="fas fa-play"></i> Activate
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
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
