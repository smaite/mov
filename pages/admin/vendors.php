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

// Get filter first (before any redirects) - default to 'all' to show all vendors
$filter = $_GET['filter'] ?? 'all';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        $userId = intval($_POST['user_id'] ?? 0);
        
        try {
            if ($action === 'approve_vendor' && $userId > 0) {
                // First check if user exists
                $userExists = $database->fetchOne("SELECT id, email, user_type, status FROM users WHERE id = ?", [$userId]);
                
                if (!$userExists) {
                    $error = '❌ User not found with ID: ' . $userId;
                } elseif ($userExists['user_type'] !== 'vendor') {
                    $error = '❌ User ID ' . $userId . ' is not a vendor (type: ' . $userExists['user_type'] . ')';
                } else {
                    // Try to update
                    $result = $database->update('users', ['status' => 'active'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
                    
                    if ($result !== false && $result > 0) {
                        // Verify the update worked
                        $updatedUser = $database->fetchOne("SELECT status FROM users WHERE id = ?", [$userId]);
                        $success = '✅ Vendor approved successfully! 
                                    <br><strong>Email:</strong> ' . htmlspecialchars($userExists['email']) . '
                                    <br><strong>Status:</strong> ' . $userExists['status'] . ' → active
                                    <br><strong>Rows updated:</strong> ' . $result;
                    } elseif ($result === 0) {
                        $error = '❌ No rows updated. User may already be active or conditions not met.
                                  <br><strong>User ID:</strong> ' . $userId . '
                                  <br><strong>Current status:</strong> ' . $userExists['status'] . '
                                  <br><strong>User type:</strong> ' . $userExists['user_type'] . '
                                  <br><strong>Email:</strong> ' . $userExists['email'];
                    } else {
                        $error = '❌ Database update returned false. Check database connection and query.';
                        
                        // Show detailed error if available
                        if (isset($_SESSION['last_db_error'])) {
                            $error .= '<br><br><strong>Debug Info:</strong><br><pre style="background:#f4f4f4;padding:10px;overflow:auto;font-size:11px;">' . 
                                     htmlspecialchars($_SESSION['last_db_error']) . '</pre>';
                            unset($_SESSION['last_db_error']);
                        }
                        
                        $error .= '<br><br><strong>User Info:</strong> ID=' . $userId . ', Type=' . $userExists['user_type'] . ', Status=' . $userExists['status'] . ', Email=' . $userExists['email'];
                    }
                }
            } elseif ($action === 'reject_vendor' && $userId > 0) {
                $rejectionReason = trim($_POST['rejection_reason'] ?? '');
                if (empty($rejectionReason)) {
                    $error = '❌ Please provide a rejection reason';
                } else {
                    $result = $database->update('users', [
                        'status' => 'rejected',
                        'rejection_reason' => $rejectionReason
                    ], 'id = ? AND user_type = ?', [$userId, 'vendor']);
                    if ($result) {
                        $success = '✅ Vendor rejected successfully!';
                    } else {
                        $error = '❌ Failed to reject vendor. User ID: ' . $userId;
                    }
                }
            } elseif ($action === 'suspend_vendor' && $userId > 0) {
                $result = $database->update('users', ['status' => 'inactive'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
                if ($result) {
                    $success = '✅ Vendor suspended successfully!';
                } else {
                    $error = '❌ Failed to suspend vendor. User ID: ' . $userId;
                }
            } elseif ($action === 'reactivate_vendor' && $userId > 0) {
                $result = $database->update('users', ['status' => 'active'], 'id = ? AND user_type = ?', [$userId, 'vendor']);
                if ($result) {
                    $success = '✅ Vendor reactivated successfully!';
                } else {
                    $error = '❌ Failed to reactivate vendor. User ID: ' . $userId;
                }
            } else {
                $error = '❌ Invalid action or user ID. Action: ' . htmlspecialchars($action) . ', User ID: ' . $userId;
            }
            
            // Redirect to same page to prevent form resubmission
            if ($success) {
                $_SESSION['vendor_success'] = $success;
                // Use JavaScript redirect if headers already sent
                if (!headers_sent()) {
                    header('Location: ?page=admin&section=vendors&filter=' . urlencode($filter));
                    exit;
                } else {
                    echo '<script>window.location.href = "?page=admin&section=vendors&filter=' . urlencode($filter) . '";</script>';
                    echo '<noscript><meta http-equiv="refresh" content="0;url=?page=admin&section=vendors&filter=' . urlencode($filter) . '"></noscript>';
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = '❌ Database error: ' . $e->getMessage();
        }
    }
}

// Get filter first (needed for redirect)
$filter = $_GET['filter'] ?? 'all';

// Check for success message from redirect
if (isset($_SESSION['vendor_success'])) {
    $success = $_SESSION['vendor_success'];
    unset($_SESSION['vendor_success']);
}
// Build WHERE clause - ONLY filter by status if not 'all'
if ($filter === 'all') {
    $whereClause = "WHERE u.user_type = 'vendor'";
} else {
    $whereClause = "WHERE u.user_type = 'vendor' AND u.status = ?";
    $params = [$filter];
}

// Override params if filter is 'all'
if ($filter === 'all') {
    $params = [];
}

// Get vendors - using LEFT JOIN to show all vendor users even without complete profiles
$vendors = $database->fetchAll("
    SELECT u.*, v.id as vendor_id, v.shop_name, v.shop_description, v.phone as vendor_phone, v.address as vendor_address,
           v.business_license, v.business_license_file, v.citizenship_file, v.pan_card_file,
           v.other_documents, v.application_date, u.rejection_reason,
           COALESCE((SELECT COUNT(*) FROM products p WHERE p.vendor_id = v.id), 0) as total_products,
           COALESCE((SELECT COUNT(*) FROM products p WHERE p.vendor_id = v.id AND p.status = 'active'), 0) as active_products
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
                <a href="?page=admin&section=orders" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-shopping-cart mr-3"></i>Orders
                </a>
                <a href="?page=admin&section=users" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-users mr-3"></i>Customers
                </a>
                <a href="?page=admin&section=settings" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-cog mr-3"></i>Settings
                </a>
                <a href="?page=logout" class="flex items-center px-6 py-3 text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                </a>
            </nav>
        </div>

        <!-- Sidebar Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-50 z-40 lg:hidden hidden" onclick="toggleAdminSidebar()"></div>

        <!-- Content Area -->
        <div class="flex-1 p-4 lg:p-8">
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-6 rounded-lg shadow-lg mb-6 animate-pulse">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-3xl mr-4"></i>
                        <div>
                            <p class="font-bold text-xl"><?php echo $success; ?></p>
                            <p class="text-sm mt-1">The vendor status has been updated successfully.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-lg shadow-lg mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-3xl mr-4"></i>
                        <div>
                            <p class="font-bold text-xl"><?php echo $error; ?></p>
                            <p class="text-sm mt-1">Please review the error and try again.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-2">Vendor Management</h1>
                <p class="text-gray-600">Manage vendor registrations and accounts</p>
            </div>

            <!-- Filter Tabs -->
            <div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
                <div class="flex flex-wrap border-b border-gray-200">
                    <a href="?page=admin&section=vendors" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'all' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
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
                        <div class="mt-6">
                            <a href="?page=admin&section=vendors&filter=all" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-list mr-2"></i>View All Vendors
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($vendors as $vendor): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-full flex items-center justify-center">
                                                    <?php if ($vendor['profile_image']): ?>
                                                        <img src="<?php echo SITE_URL . '/' . $vendor['profile_image']; ?>" alt="Profile" class="h-10 w-10 rounded-full">
                                                    <?php else: ?>
                                                        <i class="fas fa-user text-gray-400"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <?php if ($vendor['vendor_id']): ?>
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['shop_name'] ?: 'No shop name'); ?></div>
                                                <?php else: ?>
                                                    <div class="text-sm font-medium text-orange-600">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>Incomplete Profile
                                                    </div>
                                                    <div class="text-xs text-gray-500">No vendor details submitted</div>
                                                <?php endif; ?>
                                                <?php if ($vendor['shop_description']): ?>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($vendor['shop_description'], 0, 50) . '...'); ?></div>
                                                <?php endif; ?>
                                                <?php if ($vendor['vendor_address']): ?>
                                                    <div class="text-xs text-gray-400"><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($vendor['vendor_address']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($vendor['business_license']): ?>
                                                    <div class="text-xs text-blue-600"><i class="fas fa-certificate mr-1"></i>License: <?php echo htmlspecialchars($vendor['business_license']); ?></div>
                                                <?php endif; ?>
                                                <!-- Documents -->
                                                <div class="mt-2 flex space-x-2">
                                                    <?php if ($vendor['citizenship_file']): ?>
                                                        <a href="<?php echo SITE_URL . $vendor['citizenship_file']; ?>" target="_blank" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                                            <i class="fas fa-id-card mr-1"></i>Citizenship
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($vendor['business_license_file']): ?>
                                                        <a href="<?php echo SITE_URL . $vendor['business_license_file']; ?>" target="_blank" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                            <i class="fas fa-certificate mr-1"></i>License
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($vendor['pan_card_file']): ?>
                                                        <a href="<?php echo SITE_URL . $vendor['pan_card_file']; ?>" target="_blank" class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">
                                                            <i class="fas fa-credit-card mr-1"></i>PAN
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
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
                                                <!-- View Details Button -->
                                                <button type="button" onclick="showVendorDetails(<?php echo htmlspecialchars(json_encode($vendor)); ?>)" 
                                                        class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                                    <i class="fas fa-eye mr-1"></i>Details
                                                </button>
                                                
                                                <?php if ($vendor['status'] === 'pending'): ?>
                                                    <form method="POST" class="inline" onsubmit="const btn = this.querySelector('button'); btn.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Processing...'; btn.disabled=true; return confirm('Approve <?php echo htmlspecialchars($vendor['shop_name']); ?> as a vendor?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="action" value="approve_vendor">
                                                        <input type="hidden" name="user_id" value="<?php echo $vendor['id']; ?>">
                                                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105"> 
                                                            <i class="fas fa-check mr-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <button type="button" onclick="showRejectModal(<?php echo $vendor['id']; ?>, '<?php echo htmlspecialchars($vendor['shop_name'], ENT_QUOTES); ?>')" 
                                                            class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105 ml-2">
                                                        <i class="fas fa-times mr-1"></i>Reject
                                                    </button>
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
                                                    <!-- Show rejection reason if rejected -->
                                                    <?php if($vendor['status'] === 'rejected' && !empty($vendor['rejection_reason'])): ?>
                                                        <div class="mt-2 bg-red-50 p-2 border border-red-100 rounded-md text-sm">
                                                            <p class="font-medium text-gray-700">Rejection reason:</p>
                                                            <p class="text-gray-600"><?php echo htmlspecialchars($vendor['rejection_reason']); ?></p>
                                                        </div>
                                                    <?php endif; ?>
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

<script>
    function toggleAdminSidebar() {
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    // Rejection modal functionality
    function showRejectModal(userId, shopName) {
        document.getElementById('reject-vendor-id').value = userId;
        document.getElementById('reject-vendor-name').textContent = shopName;
        document.getElementById('rejection-modal').classList.remove('hidden');
    }
    
    function closeRejectModal() {
        document.getElementById('rejection-modal').classList.add('hidden');
        document.getElementById('rejection-reason').value = '';
    }
    
    // Vendor details modal
    function showVendorDetails(vendor) {
        const modal = document.getElementById('vendor-details-modal');
        
        // Populate modal with vendor data
        document.getElementById('detail-name').textContent = vendor.first_name + ' ' + vendor.last_name;
        document.getElementById('detail-email').textContent = vendor.email;
        document.getElementById('detail-phone').textContent = vendor.phone || 'N/A';
        document.getElementById('detail-user-address').textContent = vendor.address || 'N/A';
        
        document.getElementById('detail-shop-name').textContent = vendor.shop_name || 'N/A';
        document.getElementById('detail-shop-desc').textContent = vendor.shop_description || 'N/A';
        document.getElementById('detail-vendor-phone').textContent = vendor.vendor_phone || 'N/A';
        document.getElementById('detail-vendor-address').textContent = vendor.vendor_address || 'N/A';
        document.getElementById('detail-license').textContent = vendor.business_license || 'N/A';
        
        document.getElementById('detail-status').textContent = vendor.status.charAt(0).toUpperCase() + vendor.status.slice(1);
        document.getElementById('detail-status').className = 'inline-flex px-3 py-1 text-sm font-semibold rounded-full ' + 
            (vendor.status === 'active' ? 'bg-green-100 text-green-800' : 
             vendor.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
             vendor.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800');
        
        document.getElementById('detail-date').textContent = new Date(vendor.created_at).toLocaleDateString();
        document.getElementById('detail-products').textContent = vendor.total_products;
        
        // Documents
        const docsContainer = document.getElementById('detail-documents');
        docsContainer.innerHTML = '';
        
        if (vendor.business_license_file) {
            docsContainer.innerHTML += `<a href="${vendor.business_license_file}" target="_blank" class="inline-flex items-center bg-blue-100 text-blue-800 px-3 py-2 rounded-lg hover:bg-blue-200 transition-colors mr-2 mb-2">
                <i class="fas fa-file-certificate mr-2"></i>Business License
            </a>`;
        }
        if (vendor.citizenship_file) {
            docsContainer.innerHTML += `<a href="${vendor.citizenship_file}" target="_blank" class="inline-flex items-center bg-green-100 text-green-800 px-3 py-2 rounded-lg hover:bg-green-200 transition-colors mr-2 mb-2">
                <i class="fas fa-id-card mr-2"></i>Citizenship
            </a>`;
        }
        if (vendor.pan_card_file) {
            docsContainer.innerHTML += `<a href="${vendor.pan_card_file}" target="_blank" class="inline-flex items-center bg-purple-100 text-purple-800 px-3 py-2 rounded-lg hover:bg-purple-200 transition-colors mr-2 mb-2">
                <i class="fas fa-credit-card mr-2"></i>PAN Card
            </a>`;
        }
        if (!vendor.business_license_file && !vendor.citizenship_file && !vendor.pan_card_file) {
            docsContainer.innerHTML = '<span class="text-gray-500">No documents uploaded</span>';
        }
        
        // Rejection reason
        const rejectionSection = document.getElementById('detail-rejection-section');
        if (vendor.status === 'rejected' && vendor.rejection_reason) {
            rejectionSection.classList.remove('hidden');
            document.getElementById('detail-rejection-reason').textContent = vendor.rejection_reason;
        } else {
            rejectionSection.classList.add('hidden');
        }
        
        modal.classList.remove('hidden');
    }
    
    function closeVendorDetails() {
        document.getElementById('vendor-details-modal').classList.add('hidden');
    }
</script>

<!-- Rejection Modal -->
<div id="rejection-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-2xl">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Reject Vendor</h3>
        <p class="mb-4">You are about to reject <strong id="reject-vendor-name"></strong>. Please provide a reason for rejection:</p>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="reject_vendor">
            <input type="hidden" id="reject-vendor-id" name="user_id" value="">
            
            <div class="mb-4">
                <label for="rejection-reason" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea id="rejection-reason" name="rejection_reason" rows="4" 
                          class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary focus:border-transparent"
                          placeholder="Example: Missing required documents" required></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" 
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Vendor Details Modal -->
<div id="vendor-details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden overflow-y-auto p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full shadow-2xl my-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between rounded-t-lg">
            <h3 class="text-xl font-bold text-white">Vendor Details</h3>
            <button onclick="closeVendorDetails()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-6 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user text-blue-600 mr-2"></i>Personal Information
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Full Name</p>
                            <p id="detail-name" class="text-sm font-medium text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Email</p>
                            <p id="detail-email" class="text-sm font-medium text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Phone</p>
                            <p id="detail-phone" class="text-sm font-medium text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Address</p>
                            <p id="detail-user-address" class="text-sm font-medium text-gray-900"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Business Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-store text-orange-600 mr-2"></i>Business Information
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Shop Name</p>
                            <p id="detail-shop-name" class="text-sm font-medium text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Description</p>
                            <p id="detail-shop-desc" class="text-sm text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Business Phone</p>
                            <p id="detail-vendor-phone" class="text-sm font-medium text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Business Address</p>
                            <p id="detail-vendor-address" class="text-sm font-medium text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Business License</p>
                            <p id="detail-license" class="text-sm font-medium text-gray-900"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Status & Stats -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-green-600 mr-2"></i>Status & Statistics
                    </h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <span id="detail-status" class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"></span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Registration Date</p>
                            <p id="detail-date" class="text-sm font-medium text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Products</p>
                            <p id="detail-products" class="text-sm font-medium text-gray-900"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Documents -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-file-alt text-purple-600 mr-2"></i>Uploaded Documents
                    </h4>
                    <div id="detail-documents" class="flex flex-wrap gap-2">
                        <!-- Documents will be populated here -->
                    </div>
                </div>
            </div>
            
            <!-- Rejection Reason (if rejected) -->
            <div id="detail-rejection-section" class="mt-6 bg-red-50 border-l-4 border-red-500 p-4 rounded hidden">
                <h4 class="text-lg font-semibold text-red-800 mb-2 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Rejection Reason
                </h4>
                <p id="detail-rejection-reason" class="text-red-700"></p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="border-t border-gray-200 px-6 py-4 flex justify-end">
            <button onclick="closeVendorDetails()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                Close
            </button>
        </div>
    </div>
</div>
</body>
</html>