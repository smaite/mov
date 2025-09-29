<?php
$pageTitle = 'My Orders';
$pageDescription = 'Manage your orders';

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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">My Orders</h1>
                <p class="text-gray-600">Manage your customer orders</p>
            </div>

            <!-- Filter Tabs -->
            <div class="bg-white rounded-lg shadow-sm mb-6 overflow-hidden">
                <div class="flex flex-wrap border-b border-gray-200">
                    <a href="?page=vendor&section=orders&filter=all" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'all' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        All Orders (<?php echo $counts['all']; ?>)
                    </a>
                    <a href="?page=vendor&section=orders&filter=pending" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'pending' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Pending (<?php echo $counts['pending']; ?>)
                    </a>
                    <a href="?page=vendor&section=orders&filter=processing" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'processing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Processing (<?php echo $counts['processing']; ?>)
                    </a>
                    <a href="?page=vendor&section=orders&filter=shipped" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'shipped' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Shipped (<?php echo $counts['shipped']; ?>)
                    </a>
                    <a href="?page=vendor&section=orders&filter=delivered" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'delivered' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Delivered (<?php echo $counts['delivered']; ?>)
                    </a>
                    <a href="?page=vendor&section=orders&filter=cancelled" 
                       class="px-6 py-3 text-sm font-medium border-b-2 <?php echo $filter === 'cancelled' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Cancelled (<?php echo $counts['cancelled']; ?>)
                    </a>
                </div>
            </div>

            <!-- Orders List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
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
                                            <a href="?page=order&id=<?php echo $order['id']; ?>" class="text-primary hover:text-opacity-80">
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
</body>
</html>
