<?php
$pageTitle = 'My Orders';
$pageDescription = 'View and track your orders';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirectTo('?page=login&redirect=' . urlencode('?page=orders'));
}

global $database;

$action = $_GET['action'] ?? 'list';
$orderId = intval($_GET['id'] ?? 0);
$success = $_GET['success'] ?? '';

if ($action === 'view' && $orderId > 0) {
    // View single order
    $order = $database->fetchOne("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ?
    ", [$orderId, $_SESSION['user_id']]);
    
    if (!$order) {
        redirectTo('?page=orders');
    }
    
    // Get order items
    $orderItems = $database->fetchAll("
        SELECT oi.*, p.name, p.slug, pi.image_url, v.shop_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN vendors v ON oi.vendor_id = v.id
        WHERE oi.order_id = ?
        ORDER BY v.shop_name, p.name
    ", [$orderId]);
    
    // Group items by vendor
    $itemsByVendor = [];
    foreach ($orderItems as $item) {
        $vendorName = $item['shop_name'] ?: 'Unknown Vendor';
        if (!isset($itemsByVendor[$vendorName])) {
            $itemsByVendor[$vendorName] = [];
        }
        $itemsByVendor[$vendorName][] = $item;
    }
    
    $pageTitle = 'Order #' . $order['order_number'];
} else {
    // List orders
    $page = max(1, intval($_GET['page_num'] ?? 1));
    $status = $_GET['status'] ?? '';
    
    // Build WHERE clause
    $whereConditions = ["user_id = ?"];
    $params = [$_SESSION['user_id']];
    
    if ($status) {
        $whereConditions[] = "status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Pagination
    $offset = ($page - 1) * ORDERS_PER_PAGE;
    
    // Get total count
    $totalResult = $database->fetchOne(
        "SELECT COUNT(*) as total FROM orders WHERE {$whereClause}", 
        $params
    );
    $totalOrders = $totalResult['total'];
    $totalPages = ceil($totalOrders / ORDERS_PER_PAGE);
    
    // Get orders
    $orders = $database->fetchAll("
        SELECT o.*, COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE {$whereClause}
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT " . ORDERS_PER_PAGE . " OFFSET {$offset}
    ", $params);
}
?>

<?php if ($action === 'view' && $orderId > 0): ?>
    <!-- Single Order View -->
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumbs -->
        <nav class="text-sm breadcrumbs mb-6">
            <ol class="flex items-center space-x-2 text-gray-500">
                <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
                <li><i class="fas fa-chevron-right"></i></li>
                <li><a href="?page=orders" class="hover:text-primary">My Orders</a></li>
                <li><i class="fas fa-chevron-right"></i></li>
                <li class="text-gray-800">Order #<?php echo $order['order_number']; ?></li>
            </ol>
        </nav>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span class="font-semibold text-green-800">Order placed successfully!</span>
                </div>
                <p class="text-green-700 mt-1">Thank you for your order. We'll send you updates via email.</p>
            </div>
        <?php endif; ?>

        <!-- Order Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Order #<?php echo $order['order_number']; ?></h1>
                    <p class="text-gray-600">Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="mt-4 md:mt-0">
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
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
                </div>
            </div>
            
            <!-- Order Progress -->
            <div class="mt-6">
                <div class="flex items-center justify-between text-sm">
                    <div class="flex flex-col items-center <?php echo in_array($order['status'], ['pending', 'confirmed', 'processing', 'shipped', 'delivered']) ? 'text-primary' : 'text-gray-400'; ?>">
                        <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-2 <?php echo in_array($order['status'], ['pending', 'confirmed', 'processing', 'shipped', 'delivered']) ? 'border-primary bg-primary text-white' : 'border-gray-300'; ?>">
                            <i class="fas fa-shopping-cart text-xs"></i>
                        </div>
                        <span>Ordered</span>
                    </div>
                    
                    <div class="flex-1 h-0.5 mx-2 <?php echo in_array($order['status'], ['confirmed', 'processing', 'shipped', 'delivered']) ? 'bg-primary' : 'bg-gray-300'; ?>"></div>
                    
                    <div class="flex flex-col items-center <?php echo in_array($order['status'], ['confirmed', 'processing', 'shipped', 'delivered']) ? 'text-primary' : 'text-gray-400'; ?>">
                        <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-2 <?php echo in_array($order['status'], ['confirmed', 'processing', 'shipped', 'delivered']) ? 'border-primary bg-primary text-white' : 'border-gray-300'; ?>">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                        <span>Confirmed</span>
                    </div>
                    
                    <div class="flex-1 h-0.5 mx-2 <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'bg-primary' : 'bg-gray-300'; ?>"></div>
                    
                    <div class="flex flex-col items-center <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'text-primary' : 'text-gray-400'; ?>">
                        <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-2 <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'border-primary bg-primary text-white' : 'border-gray-300'; ?>">
                            <i class="fas fa-box text-xs"></i>
                        </div>
                        <span>Processing</span>
                    </div>
                    
                    <div class="flex-1 h-0.5 mx-2 <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'bg-primary' : 'bg-gray-300'; ?>"></div>
                    
                    <div class="flex flex-col items-center <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'text-primary' : 'text-gray-400'; ?>">
                        <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-2 <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'border-primary bg-primary text-white' : 'border-gray-300'; ?>">
                            <i class="fas fa-truck text-xs"></i>
                        </div>
                        <span>Shipped</span>
                    </div>
                    
                    <div class="flex-1 h-0.5 mx-2 <?php echo $order['status'] === 'delivered' ? 'bg-primary' : 'bg-gray-300'; ?>"></div>
                    
                    <div class="flex flex-col items-center <?php echo $order['status'] === 'delivered' ? 'text-primary' : 'text-gray-400'; ?>">
                        <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-2 <?php echo $order['status'] === 'delivered' ? 'border-primary bg-primary text-white' : 'border-gray-300'; ?>">
                            <i class="fas fa-home text-xs"></i>
                        </div>
                        <span>Delivered</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Order Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold">Order Items</h2>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <?php foreach ($itemsByVendor as $vendorName => $items): ?>
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <i class="fas fa-store text-primary mr-2"></i>
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($vendorName); ?></span>
                                </div>
                                
                                <div class="space-y-4">
                                    <?php foreach ($items as $item): ?>
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-shrink-0">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?php echo SITE_URL . $item['image_url']; ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         class="w-16 h-16 object-cover rounded-lg">
                                                <?php else: ?>
                                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-800">
                                                    <a href="?page=product&id=<?php echo $item['product_id']; ?>" 
                                                       class="hover:text-primary">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </h3>
                                                <div class="text-sm text-gray-500 mt-1">
                                                    <span>Qty: <?php echo $item['quantity']; ?></span>
                                                    <span class="mx-2">•</span>
                                                    <span><?php echo formatPrice($item['price']); ?> each</span>
                                                </div>
                                            </div>
                                            
                                            <div class="text-right">
                                                <span class="font-semibold text-gray-800">
                                                    <?php echo formatPrice($item['total']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary & Details -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Order Summary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span><?php echo formatPrice($order['total_amount'] - $order['shipping_amount'] - $order['tax_amount']); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span>Shipping:</span>
                            <span><?php echo $order['shipping_amount'] > 0 ? formatPrice($order['shipping_amount']) : 'FREE'; ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span>Tax:</span>
                            <span><?php echo formatPrice($order['tax_amount']); ?></span>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-2">
                            <div class="flex justify-between font-semibold text-lg">
                                <span>Total:</span>
                                <span class="text-primary"><?php echo formatPrice($order['total_amount']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Shipping Information</h2>
                    
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">Address:</span>
                            <p class="text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                        
                        <div>
                            <span class="font-medium text-gray-700">Payment Method:</span>
                            <p class="text-gray-600 mt-1">
                                <?php 
                                switch($order['payment_method']) {
                                    case 'cod': echo 'Cash on Delivery'; break;
                                    case 'bank_transfer': echo 'Bank Transfer'; break;
                                    case 'online': echo 'Online Payment'; break;
                                    default: echo ucfirst($order['payment_method']);
                                }
                                ?>
                            </p>
                        </div>
                        
                        <div>
                            <span class="font-medium text-gray-700">Payment Status:</span>
                            <span class="inline-block mt-1 px-2 py-1 rounded text-xs font-semibold
                                <?php 
                                switch($order['payment_status']) {
                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'paid': echo 'bg-green-100 text-green-800'; break;
                                    case 'failed': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Notes:</span>
                                <p class="text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Actions</h2>
                    
                    <div class="space-y-3">
                        <?php if ($order['status'] === 'pending'): ?>
                            <button onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                                    class="w-full bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 transition duration-200">
                                <i class="fas fa-times mr-2"></i>Cancel Order
                            </button>
                        <?php endif; ?>
                        
                        <a href="?page=orders" 
                           class="block w-full bg-gray-100 text-gray-800 py-2 px-4 rounded-md hover:bg-gray-200 transition duration-200 text-center">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                        </a>
                        
                        <button onclick="window.print()" 
                                class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-opacity-90 transition duration-200">
                            <i class="fas fa-print mr-2"></i>Print Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Orders List -->
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumbs -->
        <nav class="text-sm breadcrumbs mb-6">
            <ol class="flex items-center space-x-2 text-gray-500">
                <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
                <li><i class="fas fa-chevron-right"></i></li>
                <li class="text-gray-800">My Orders</li>
            </ol>
        </nav>

        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Orders</h1>
            <span class="text-gray-600"><?php echo number_format($totalOrders); ?> orders</span>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6">
                    <a href="?page=orders" 
                       class="py-4 border-b-2 font-semibold <?php echo !$status ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        All Orders
                    </a>
                    <a href="?page=orders&status=pending" 
                       class="py-4 border-b-2 font-semibold <?php echo $status === 'pending' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Pending
                    </a>
                    <a href="?page=orders&status=confirmed" 
                       class="py-4 border-b-2 font-semibold <?php echo $status === 'confirmed' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Confirmed
                    </a>
                    <a href="?page=orders&status=shipped" 
                       class="py-4 border-b-2 font-semibold <?php echo $status === 'shipped' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Shipped
                    </a>
                    <a href="?page=orders&status=delivered" 
                       class="py-4 border-b-2 font-semibold <?php echo $status === 'delivered' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'; ?>">
                        Delivered
                    </a>
                </nav>
            </div>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="text-center py-16">
                <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-6"></i>
                <h2 class="text-2xl font-semibold text-gray-600 mb-4">No orders found</h2>
                <p class="text-gray-500 mb-8">You haven't placed any orders yet.</p>
                <a href="?page=products" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                            <div class="mb-4 md:mb-0">
                                <div class="flex items-center space-x-4 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        Order #<?php echo $order['order_number']; ?>
                                    </h3>
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
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
                                </div>
                                
                                <div class="text-sm text-gray-600 space-y-1">
                                    <p>Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                    <p><?php echo $order['item_count']; ?> item(s) • Total: <span class="font-semibold text-primary"><?php echo formatPrice($order['total_amount']); ?></span></p>
                                    <p>Payment: <span class="capitalize"><?php echo str_replace('_', ' ', $order['payment_method']); ?></span></p>
                                </div>
                            </div>
                            
                            <div class="flex space-x-3">
                                <a href="?page=orders&action=view&id=<?php echo $order['id']; ?>" 
                                   class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition duration-200">
                                    <i class="fas fa-eye mr-2"></i>View Details
                                </a>
                                
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <button onclick="showReviewModal(<?php echo $order['id']; ?>)" 
                                            class="bg-secondary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition duration-200">
                                        <i class="fas fa-star mr-2"></i>Review
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-8">
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo buildOrdersPaginationUrl($page - 1); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            $class = $i === $page ? 'bg-primary text-white' : 'border border-gray-300 hover:bg-gray-50';
                            echo '<a href="' . buildOrdersPaginationUrl($i) . '" class="px-3 py-2 rounded-md ' . $class . '">' . $i . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo buildOrdersPaginationUrl($page + 1); ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    // TODO: Implement order cancellation
    showNotification('Order cancellation feature will be available soon', 'info');
}

function showReviewModal(orderId) {
    // TODO: Implement review modal
    showNotification('Review feature will be available soon', 'info');
}
</script>

<?php
function buildOrdersPaginationUrl($pageNum) {
    $params = $_GET;
    $params['page_num'] = $pageNum;
    unset($params['action'], $params['id'], $params['success']);
    return '?' . http_build_query($params);
}
?>
