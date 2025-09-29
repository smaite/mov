<?php
$pageTitle = 'Analytics';
$pageDescription = 'View your sales analytics';

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
           SUM(oi.total) as total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE oi.vendor_id = ? AND o.payment_status = 'paid' {$dateFilter}
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
", [$vendor['id']]);

// Sales by status
$salesByStatus = $database->fetchAll("
    SELECT o.status, 
           COUNT(DISTINCT oi.order_id) as order_count,
           SUM(oi.total) as total_amount
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.vendor_id = ? {$dateFilter}
    GROUP BY o.status
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
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="?page=vendor" class="text-primary hover:text-opacity-80 font-semibold">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Analytics</h1>
                    <p class="text-gray-600">Track your sales performance</p>
                </div>
                
                <!-- Period Selector -->
                <div class="flex space-x-2">
                    <a href="?page=vendor&section=analytics&period=7days" 
                       class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $period === '7days' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        7 Days
                    </a>
                    <a href="?page=vendor&section=analytics&period=30days" 
                       class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $period === '30days' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        30 Days
                    </a>
                    <a href="?page=vendor&section=analytics&period=90days" 
                       class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $period === '90days' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        90 Days
                    </a>
                    <a href="?page=vendor&section=analytics&period=year" 
                       class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $period === 'year' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        1 Year
                    </a>
                    <a href="?page=vendor&section=analytics&period=all" 
                       class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $period === 'all' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        All Time
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo formatPrice($stats['total_revenue']); ?></div>
                    <div class="text-sm text-gray-600">Total Revenue</div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="text-sm text-gray-600">Total Orders</div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="fas fa-box text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_items_sold']); ?></div>
                    <div class="text-sm text-gray-600">Items Sold</div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo formatPrice($stats['avg_order_value']); ?></div>
                    <div class="text-sm text-gray-600">Avg Order Value</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Top Products -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Top Selling Products</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($topProducts)): ?>
                            <div class="text-center text-gray-500 py-8">
                                <i class="fas fa-chart-bar text-4xl mb-4"></i>
                                <p>No sales data yet</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($topProducts as $product): ?>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h4>
                                            <p class="text-sm text-gray-600">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-gray-900"><?php echo number_format($product['total_sold']); ?> sold</div>
                                            <div class="text-sm text-green-600"><?php echo formatPrice($product['total_revenue']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sales by Status -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Orders by Status</h3>
                    </div>
                    <div class="p-6">
                        <?php if (empty($salesByStatus)): ?>
                            <div class="text-center text-gray-500 py-8">
                                <i class="fas fa-chart-pie text-4xl mb-4"></i>
                                <p>No orders yet</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($salesByStatus as $status): ?>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                                                <?php 
                                                switch($status['status']) {
                                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'processing': echo 'bg-purple-100 text-purple-800'; break;
                                                    case 'shipped': echo 'bg-indigo-100 text-indigo-800'; break;
                                                    case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($status['status']); ?>
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-gray-900"><?php echo number_format($status['order_count']); ?> orders</div>
                                            <div class="text-sm text-gray-600"><?php echo formatPrice($status['total_amount']); ?></div>
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
</body>
</html>
