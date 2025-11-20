<?php
$pageTitle = 'My Orders';
$pageDescription = 'Manage your orders';

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

// Get filter
$filter = $_GET['filter'] ?? 'all';
$whereClause = "WHERE oi.vendor_id = ?";
$params = [$vendor['id']];

switch ($filter) {
    case 'pending':
        $whereClause .= " AND o.status IN ('pending', 'confirmed')";
        break;
    case 'processing':
        $whereClause .= " AND o.status = 'processing'";
        break;
    case 'shipped':
        $whereClause .= " AND o.status = 'shipped'";
        break;
    case 'delivered':
        $whereClause .= " AND o.status = 'delivered'";
        break;
    case 'cancelled':
        $whereClause .= " AND o.status = 'cancelled'";
        break;
}

// Get orders
$orders = $database->fetchAll("
    SELECT DISTINCT o.*, u.first_name, u.last_name, u.email,
           (SELECT SUM(oi2.total) FROM order_items oi2 WHERE oi2.order_id = o.id AND oi2.vendor_id = ?) as vendor_amount,
           (SELECT COUNT(*) FROM order_items oi3 WHERE oi3.order_id = o.id AND oi3.vendor_id = ?) as item_count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.user_id = u.id
    {$whereClause}
    ORDER BY o.created_at DESC
", array_merge([$vendor['id'], $vendor['id']], $params));

// Get counts
$counts = [
    'all' => $database->fetchOne("SELECT COUNT(DISTINCT oi.order_id) as count FROM order_items oi WHERE oi.vendor_id = ?", [$vendor['id']])['count'],
    'pending' => $database->fetchOne("SELECT COUNT(DISTINCT oi.order_id) as count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.vendor_id = ? AND o.status IN ('pending', 'confirmed')", [$vendor['id']])['count'],
    'processing' => $database->fetchOne("SELECT COUNT(DISTINCT oi.order_id) as count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.vendor_id = ? AND o.status = 'processing'", [$vendor['id']])['count'],
    'shipped' => $database->fetchOne("SELECT COUNT(DISTINCT oi.order_id) as count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.vendor_id = ? AND o.status = 'shipped'", [$vendor['id']])['count'],
    'delivered' => $database->fetchOne("SELECT COUNT(DISTINCT oi.order_id) as count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.vendor_id = ? AND o.status = 'delivered'", [$vendor['id']])['count'],
    'cancelled' => $database->fetchOne("SELECT COUNT(DISTINCT oi.order_id) as count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.vendor_id = ? AND o.status = 'cancelled'", [$vendor['id']])['count']
];
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
                    <i class="fas fa-shopping-cart text-white text-sm"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-800">My Orders</h1>
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
                
                <a href="?page=vendor-products" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-box text-sm"></i>
                    </div>
                    <span class="font-medium">Products</span>
                </a>
                
                <a href="?page=vendor-orders" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                        <i class="fas fa-shopping-cart text-sm"></i>
                    </div>
                    <span class="font-medium">Orders</span>
                    <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
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
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-shopping-cart text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Order Management</h1>
                            <p class="text-gray-600 mt-1">Manage your customer orders</p>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="bg-white rounded-2xl shadow-sm mb-6 overflow-hidden">
                    <div class="flex flex-wrap border-b border-gray-200">
                        <a href="?page=vendor-orders&filter=all" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'all' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            All Orders (<?php echo $counts['all']; ?>)
                        </a>
                        <a href="?page=vendor-orders&filter=pending" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'pending' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Pending (<?php echo $counts['pending']; ?>)
                        </a>
                        <a href="?page=vendor-orders&filter=processing" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'processing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Processing (<?php echo $counts['processing']; ?>)
                        </a>
                        <a href="?page=vendor-orders&filter=shipped" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'shipped' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Shipped (<?php echo $counts['shipped']; ?>)
                        </a>
                        <a href="?page=vendor-orders&filter=delivered" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'delivered' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Delivered (<?php echo $counts['delivered']; ?>)
                        </a>
                        <a href="?page=vendor-orders&filter=cancelled" 
                           class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'cancelled' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                            Cancelled (<?php echo $counts['cancelled']; ?>)
                        </a>
                    </div>
                </div>

                <!-- Orders List -->
                <div class="bg-white rounded-2xl shadow overflow-hidden">
                    <?php if (empty($orders)): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Orders Found</h3>
                            <p class="text-gray-500">No orders match the current filter.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Your Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">#<?php echo $order['order_number']; ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['email']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo $order['item_count']; ?> item(s)</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo formatPrice($order['vendor_amount']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    <?php 
                                                    switch($order['status']) {
                                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'processing': echo 'bg-purple-100 text-purple-800'; break;
                                                        case 'shipped': echo 'bg-indigo-100 text-indigo-800'; break;
                                                        case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                                        case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo timeAgo($order['created_at']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="?page=order&id=<?php echo $order['id']; ?>" class="text-orange-600 hover:text-orange-800">
                                                    <i class="fas fa-eye mr-1"></i>View Details
                                                </a>
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
    </script>
</body>
</html>