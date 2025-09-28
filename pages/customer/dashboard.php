<?php
$pageTitle = 'My Account';
$pageDescription = 'Manage your account and orders';

if (!isLoggedIn()) {
    redirectTo('?page=login');
}

// Handle different customer sections
$section = $_GET['section'] ?? 'dashboard';

if ($section === 'vendor-status') {
    include __DIR__ . '/vendor-status.php';
    return;
}

global $database;

// Get user stats
$stats = [
    'total_orders' => $database->count('orders', 'user_id = ?', [$_SESSION['user_id']]),
    'pending_orders' => $database->count('orders', 'user_id = ? AND status IN (?, ?)', [$_SESSION['user_id'], 'pending', 'confirmed']),
    'wishlist_count' => $database->count('wishlists', 'user_id = ?', [$_SESSION['user_id']]),
    'cart_count' => $database->count('cart', 'user_id = ?', [$_SESSION['user_id']])
];

// Recent orders
$recentOrders = $database->fetchAll("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$_SESSION['user_id']]);

// Check if user has vendor application
$vendorApplication = $database->fetchOne("SELECT * FROM vendors WHERE user_id = ?", [$_SESSION['user_id']]);
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                <div class="mb-4 lg:mb-0">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">My Account</h1>
                    <p class="text-gray-600">Manage your profile, orders, and preferences</p>
                </div>
                
                <!-- Become Vendor CTA -->
                <?php if (!$vendorApplication && $_SESSION['user_type'] !== 'vendor'): ?>
                <div>
                    <a href="?page=become-vendor" class="inline-flex items-center bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-3 rounded-lg font-semibold hover:from-orange-600 hover:to-red-600 transition-all duration-200 shadow-lg">
                        <i class="fas fa-store mr-2"></i>Become a Vendor
                    </a>
                </div>
                <?php elseif ($vendorApplication): ?>
                <div>
                    <a href="?page=customer&section=vendor-status" class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 shadow-lg">
                        <i class="fas fa-eye mr-2"></i>Check Vendor Status
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- User Info Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-6">
                        <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                        <div class="flex items-center mt-2">
                            <span class="text-sm font-medium text-blue-600 bg-blue-100 px-2 py-1 rounded-full mr-2">
                                <?php echo ucfirst($_SESSION['user_type']); ?>
                            </span>
                            <?php if ($vendorApplication): ?>
                                <span class="text-sm font-medium <?php echo $_SESSION['status'] === 'active' ? 'text-green-600 bg-green-100' : ($_SESSION['status'] === 'rejected' ? 'text-red-600 bg-red-100' : 'text-yellow-600 bg-yellow-100'); ?> px-2 py-1 rounded-full">
                                    Vendor: <?php echo ucfirst($_SESSION['status']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['total_orders']; ?></h3>
                    <p class="text-gray-600 text-sm">Total Orders</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['pending_orders']; ?></h3>
                    <p class="text-gray-600 text-sm">Pending Orders</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-heart text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['wishlist_count']; ?></h3>
                    <p class="text-gray-600 text-sm">Wishlist Items</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-1"><?php echo $stats['cart_count']; ?></h3>
                    <p class="text-gray-600 text-sm">Cart Items</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Quick Actions</h3>
                    <div class="space-y-4">
                        <a href="?page=orders" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-shopping-bag text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">View Orders</h4>
                                <p class="text-gray-600 text-sm">Track your order history and status</p>
                            </div>
                        </a>
                        
                        <a href="?page=wishlist" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-heart text-red-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">My Wishlist</h4>
                                <p class="text-gray-600 text-sm">Items you've saved for later</p>
                            </div>
                        </a>
                        
                        <a href="?page=profile" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-user text-green-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Edit Profile</h4>
                                <p class="text-gray-600 text-sm">Update your personal information</p>
                            </div>
                        </a>
                        
                        <?php if (!$vendorApplication): ?>
                        <a href="?page=become-vendor" class="flex items-center p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg hover:from-orange-100 hover:to-red-100 transition-colors border border-orange-200">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-store text-orange-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Become a Vendor</h4>
                                <p class="text-gray-600 text-sm">Start selling on Sasto Hub today!</p>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Recent Orders</h3>
                    
                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-shopping-bag text-gray-300 text-4xl mb-4"></i>
                            <p class="text-gray-500">No orders yet</p>
                            <a href="?page=products" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium mt-2">
                                <i class="fas fa-shopping-cart mr-2"></i>Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-gray-900">Order #<?php echo $order['id']; ?></span>
                                        <span class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-bold text-gray-900"><?php echo formatPrice($order['total_amount']); ?></span>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            <?php 
                                            switch($order['status']) {
                                                case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                                case 'shipped': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'confirmed': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'pending': echo 'bg-gray-100 text-gray-800'; break;
                                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center">
                                <a href="?page=orders" class="text-blue-600 hover:text-blue-800 font-medium">
                                    View All Orders <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
