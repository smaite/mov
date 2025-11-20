
<?php
$pageTitle = 'Analytics';
$pageDescription = 'View your sales analytics';

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

// Get time period
$period = $_GET['period'] ?? '30days';

$dateFilter = match($period) {
    '7days' => "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    '30days' => "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    '90days' => "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
    'year' => "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)",
    'all' => "",
    default => "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
};

// Get analytics data
$stats = [
    'total_revenue' => $database->fetchOne("
        SELECT COALESCE(SUM(oi.total), 0) as revenue
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.vendor_id = ? AND o.payment_status = 'paid' {$dateFilter}
    ", [$vendor['id']])['revenue'],
    
    'total_orders' => $database->fetchOne("
        SELECT COUNT(DISTINCT oi.order_id) as count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.vendor_id = ? {$dateFilter}
    ", [$vendor['id']])['count'],
    
    'total_items_sold' => $database->fetchOne("
        SELECT COALESCE(SUM(oi.quantity), 0) as total
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.vendor_id = ? AND o.payment_status = 'paid' {$dateFilter}
    ", [$vendor['id']])['total'],
    
    'avg_order_value' => $database->fetchOne("
        SELECT COALESCE(AVG(oi_sum.total), 0) as avg_value
        FROM (
            SELECT SUM(oi.total) as total
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.vendor_id = ? {$dateFilter}
            GROUP BY oi.order_id
        ) as oi_sum
    ", [$vendor['id']])['avg_value']
];

// Top selling products
$topProducts = $database->fetchAll("
    SELECT p.id, p.name, p.price, p.sku,
           SUM(oi.quantity) as total_sold,
           SUM(oi.total) as total_revenue,
           pi.image_url
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE oi.vendor_id = ? AND o.payment_status = 'paid' {$dateFilter}
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
", [$vendor['id']]);

// Recent orders
$recentOrders = $database->fetchAll("
    SELECT DISTINCT o.id, o.order_number, o.total_amount, o.status, o.created_at,
           u.first_name, u.last_name,
           (SELECT SUM(oi2.total) FROM order_items oi2 WHERE oi2.order_id = o.id AND oi2.vendor_id = ?) as vendor_amount
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.user_id = u.id
    WHERE oi.vendor_id = ? {$dateFilter}
    ORDER BY o.created_at DESC
    LIMIT 10
", [$vendor['id'], $vendor['id']]);

// Monthly revenue chart data (last 12 months)
$monthlyRevenue = $database->fetchAll("
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') as month,
        COALESCE(SUM(oi.total), 0) as revenue,
        COUNT(DISTINCT oi.order_id) as orders
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.vendor_id = ? AND o.payment_status = 'paid' 
          AND o.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month DESC
", [$vendor['id']]);
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
                    <i class="fas fa-chart-line text-white text-sm"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-800">Analytics</h1>
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
                
                <a href="?page=vendor-orders" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-shopping-cart text-sm"></i>
                    </div>
                    <span class="font-medium">Orders</span>
                </a>
                
                <a href="?page=vendor-analytics" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    <span class="font-medium">Analytics</span>
                    <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
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
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-chart-line text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Sales Analytics</h1>
                                <p class="text-gray-600 mt-1">Track your store performance and sales</p>
                            </div>
                        </div>
                        
                        <!-- Time Period Filter -->
                        <div class="mt-4 lg:mt-0">
                            <select onchange="window.location.href='?page=vendor-analytics&period=' + this.value" 
                                    class="px-4 py-2 border border-gray-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                                <option value="7days" <?php echo $period === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="30days" <?php echo $period === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="90days" <?php echo $period === '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                                <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                                <option value="all" <?php echo $period === 'all' ? 'selected' : ''; ?>>All Time</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Analytics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Revenue -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl shadow-lg">
                                <i class="fas fa-dollar-sign text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-green-600 bg-green-100 px-2 py-1 rounded-full">REVENUE</span>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1">Rs. <?php echo number_format($stats['total_revenue'], 2); ?></p>
                            <p class="text-sm text-gray-600">Total Revenue</p>
                        </div>
                    </div>

                    <!-- Total Orders -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl shadow-lg">
                                <i class="fas fa-shopping-cart text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-2 py-1 rounded-full">ORDERS</span>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($stats['total_orders']); ?></p>
                            <p class="text-sm text-gray-600">Total Orders</p>
                        </div>
                    </div>

                    <!-- Items Sold -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl shadow-lg">
                                <i class="fas fa-box text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-purple-600 bg-purple-100 px-2 py-1 rounded-full">ITEMS</span>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($stats['total_items_sold']); ?></p>
                            <p class="text-sm text-gray-600">Items Sold</p>
                        </div>
                    </div>

                    <!-- Average Order Value -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-2xl shadow-lg">
                                <i class="fas fa-chart-bar text-white text-xl"></i>
                            </div>
                            <span class="text-xs font-semibold text-yellow-600 bg-yellow-100 px-2 py-1 rounded-full">AOV</span>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-gray-900 mb-1">Rs. <?php echo number_format($stats['avg_order_value'], 2); ?></p>
                            <p class="text-sm text-gray-600">Avg Order Value</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Top Selling Products -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <i class="fas fa-trophy mr-2 text-yellow-500"></i>
                                Top Selling Products
                            </h3>
                        </div>
                        <div class="p-6">
                            <?php if (empty($topProducts)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-chart-bar text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">No sales data available</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($topProducts as $index => $product): ?>
                                        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            </div>
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
                                                <p class="text-sm text-gray-600"><?php echo $product['total_sold']; ?> sold • Rs. <?php echo number_format($product['total_revenue'], 2); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                <i class="fas fa-shopping-cart mr-2 text-blue-500"></i>
                                Recent Orders
                            </h3>
                        </div>
                        <div class="p-6">
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
                                                <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-gray-900">Rs. <?php echo number_format($order['vendor_amount'], 2); ?></p>
                                                <span class="text-xs px-2 py-1 rounded-full <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Chart -->
                <?php if (!empty($monthlyRevenue)): ?>
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-green-500"></i>
                            Monthly Revenue Trend
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                            <?php foreach (array_reverse($monthlyRevenue) as $month): ?>
                                <div class="text-center">
                                    <div class="bg-gradient-to-t from-orange-500 to-red-500 rounded-lg mb-2" 
                                         style="height: <?php echo min(100, ($month['revenue'] / max(array_column($monthlyRevenue, 'revenue'))) * 100); ?>px;">
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900">Rs. <?php echo number_format($month['revenue'], 0); ?></p>
                                    <p class="text-xs text-gray-600"><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
function exportAnalytics() {
    const period = '<?php echo $period; ?>';
    window.open(`?page=api&endpoint=export_analytics&period=${period}&vendor_id=<?php echo $vendor['id']; ?>`, '_blank');
}

// Refresh analytics data
function refreshAnalytics() {
    location.reload();
}

// Initialize tooltips and interactive elements
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to chart bars
    const chartBars = document.querySelectorAll('.bg-gradient-to-t');
    chartBars.forEach(bar => {
        bar.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        bar.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});
</script>