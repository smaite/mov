<?php
$pageTitle = 'User Management';
$pageDescription = 'Manage platform users';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo '<script>window.location.href = "?page=login";</script>';
    exit();
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
        
        if ($action === 'update_user_status') {
            $userId = intval($_POST['user_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $rejectionReason = trim($_POST['rejection_reason'] ?? '');
            
            if ($userId <= 0 || !in_array($status, ['active', 'inactive', 'pending', 'rejected'])) {
                $error = 'Invalid user data.';
            } else {
                $updateData = ['status' => $status];
                if ($status === 'rejected' && !empty($rejectionReason)) {
                    $updateData['rejection_reason'] = $rejectionReason;
                }
                
                $updated = $database->update('users', $updateData, 'id = ?', [$userId]);
                
                if ($updated) {
                    $success = 'User status updated successfully!';
                } else {
                    $error = 'Failed to update user status.';
                }
            }
        }
        
        elseif ($action === 'delete_user') {
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($userId <= 0) {
                $error = 'Invalid user ID.';
            } elseif ($userId === $_SESSION['user_id']) {
                $error = 'You cannot delete your own account.';
            } else {
                $deleted = $database->delete('users', 'id = ?', [$userId]);
                if ($deleted) {
                    $success = 'User deleted successfully!';
                } else {
                    $error = 'Failed to delete user.';
                }
            }
        }
    }
}

// Get filter parameters
$userType = $_GET['type'] ?? 'all';
$userStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($userType !== 'all') {
    $whereConditions[] = 'u.user_type = ?';
    $params[] = $userType;
}

if ($userStatus !== 'all') {
    $whereConditions[] = 'u.status = ?';
    $params[] = $userStatus;
}

if (!empty($search)) {
    $whereConditions[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get users with vendor information
$users = $database->fetchAll("
    SELECT u.*, 
           v.shop_name, v.phone as vendor_phone,
           (SELECT COUNT(*) FROM products WHERE vendor_id = v.id) as product_count,
           (SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.vendor_id = v.id) as order_count
    FROM users u
    LEFT JOIN vendors v ON u.id = v.user_id
    {$whereClause}
    ORDER BY u.created_at DESC
", $params);

// Get user statistics
$userStats = [
    'total_users' => $database->count('users'),
    'customers' => $database->count('users', 'user_type = ?', ['customer']),
    'vendors' => $database->count('users', 'user_type = ?', ['vendor']),
    'admins' => $database->count('users', 'user_type = ?', ['admin']),
    'active_users' => $database->count('users', 'status = ?', ['active']),
    'pending_users' => $database->count('users', 'status = ?', ['pending'])
];
?>

<!-- Modern Admin Users Page -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50" style="padding-top: 80px;">
    
    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-16 left-0 right-0 bg-white shadow-lg border-b px-4 py-4 z-30">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-white text-sm"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-800">Users</h1>
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
                
                <a href="?page=admin" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-tachometer-alt text-sm"></i>
                    </div>
                    <span class="font-medium">Overview</span>
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
                    </a>
                    
                    <a href="?page=admin&section=products" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-box text-sm"></i>
                        </div>
                        <span class="font-medium">Products</span>
                    </a>
                    
                    <a href="?page=admin&section=categories" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-tags text-sm"></i>
                        </div>
                        <span class="font-medium">Categories</span>
                    </a>
                    
                    <a href="?page=admin&section=users" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-white bg-gradient-to-r from-blue-500 to-indigo-500 shadow-lg">
                        <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                            <i class="fas fa-users text-sm"></i>
                        </div>
                        <span class="font-medium">Users</span>
                        <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
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
                
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                                <p class="text-gray-600 mt-1">Manage platform users and their access</p>
                            </div>
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
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl shadow-lg">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $userStats['total_users']; ?></p>
                            <p class="text-sm text-gray-600">Total Users</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl shadow-lg">
                                <i class="fas fa-user text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $userStats['customers']; ?></p>
                            <p class="text-sm text-gray-600">Customers</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl shadow-lg">
                                <i class="fas fa-store text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $userStats['vendors']; ?></p>
                            <p class="text-sm text-gray-600">Vendors</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl shadow-lg">
                                <i class="fas fa-user-shield text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $userStats['admins']; ?></p>
                            <p class="text-sm text-gray-600">Admins</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl shadow-lg">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $userStats['active_users']; ?></p>
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
                            <p class="text-2xl font-bold text-gray-900 mb-1"><?php echo $userStats['pending_users']; ?></p>
                            <p class="text-sm text-gray-600">Pending</p>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
                    <form method="GET" class="space-y-4 lg:space-y-0 lg:flex lg:items-center lg:space-x-4">
                        <input type="hidden" name="page" value="admin">
                        <input type="hidden" name="section" value="users">
                        
                        <div class="flex-1">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search users by name or email..."
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <select name="type" class="px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?php echo $userType === 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="customer" <?php echo $userType === 'customer' ? 'selected' : ''; ?>>Customers</option>
                                <option value="vendor" <?php echo $userType === 'vendor' ? 'selected' : ''; ?>>Vendors</option>
                                <option value="admin" <?php echo $userType === 'admin' ? 'selected' : ''; ?>>Admins</option>
                            </select>
                        </div>
                        
                        <div>
                            <select name="status" class="px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?php echo $userStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $userStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $userStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="pending" <?php echo $userStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="rejected" <?php echo $userStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="bg-gradient-to-r from-blue-500 to-indigo-500 text-white px-6 py-3 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </form>
                </div>

                <!-- Users List -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
                    <?php if (empty($users)): ?>
                        <div class="p-12 text-center">
                            <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-3xl flex items-center justify-center">
                                <i class="fas fa-users text-4xl text-gray-400"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Users Found</h3>
                            <p class="text-gray-500">Try adjusting your search filters</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 rounded-full flex items-center justify-center <?php echo 
                                                $user['user_type'] === 'admin' ? 'bg-purple-100 text-purple-600' :
                                                ($user['user_type'] === 'vendor' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600');
                                            ?>">
                                                <i class="fas fa-<?php echo 
                                                    $user['user_type'] === 'admin' ? 'user-shield' :
                                                    ($user['user_type'] === 'vendor' ? 'store' : 'user');
                                                ?>"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                </h3>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                                                <?php if ($user['user_type'] === 'vendor' && $user['shop_name']): ?>
                                                    <p class="text-sm text-orange-600 font-medium"><?php echo htmlspecialchars($user['shop_name']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center space-x-4">
                                            <div class="text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo 
                                                    $user['user_type'] === 'admin' ? 'bg-purple-100 text-purple-800' :
                                                    ($user['user_type'] === 'vendor' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800');
                                                ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo 
                                                    $user['status'] === 'active' ? 'bg-green-100 text-green-800' :
                                                    ($user['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                     ($user['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'));
                                                ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($user['user_type'] === 'vendor'): ?>
                                                <div class="text-center text-sm text-gray-600">
                                                    <div><?php echo $user['product_count'] ?? 0; ?> products</div>
                                                    <div><?php echo $user['order_count'] ?? 0; ?> orders</div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center space-x-2">
                                                <button onclick="showUserStatusModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>', '<?php echo $user['status']; ?>')" 
                                                        class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                                            class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($user['status'] === 'rejected' && !empty($user['rejection_reason'])): ?>
                                        <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-3">
                                            <p class="text-sm text-red-800">
                                                <strong>Rejection Reason:</strong><br>
                                                <?php echo htmlspecialchars($user['rejection_reason']); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Status Update Modal -->
<div id="userStatusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">Update User Status</h3>
            <button onclick="closeUserStatusModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" id="userStatusForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update_user_status">
            <input type="hidden" name="user_id" id="modalUserId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">User</label>
                    <p id="modalUserName" class="text-gray-900"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select name="status" id="modalStatus" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleRejectionReason()">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <div id="rejectionReasonField" class="hidden">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rejection Reason</label>
                    <textarea name="rejection_reason" rows="3" placeholder="Please provide a reason for rejection..." 
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
            
            <div class="flex space-x-4 mt-6">
                <button type="submit" class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-500 text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                    Update Status
                </button>
                <button type="button" onclick="closeUserStatusModal()" class="flex-1 bg-gray-600 text-white py-3 px-6 rounded-xl font-semibold hover:bg-gray-700 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-overlay');
    
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

function showUserStatusModal(userId, userName, currentStatus) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('modalStatus').value = currentStatus;
    toggleRejectionReason();
    document.getElementById('userStatusModal').classList.remove('hidden');
}

function closeUserStatusModal() {
    document.getElementById('userStatusModal').classList.add('hidden');
}

function toggleRejectionReason() {
    const status = document.getElementById('modalStatus').value;
    const reasonField = document.getElementById('rejectionReasonField');
    
    if (status === 'rejected') {
        reasonField.classList.remove('hidden');
    } else {
        reasonField.classList.add('hidden');
    }
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone and will remove all associated data.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
