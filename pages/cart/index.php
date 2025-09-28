<?php
$pageTitle = 'Shopping Cart';
$pageDescription = 'Review your cart items before checkout';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirectTo('?page=login&redirect=' . urlencode('?page=cart'));
}

global $database;

// Get cart items
$cartItems = $database->fetchAll("
    SELECT c.*, p.name, p.price, p.sale_price, p.stock_quantity, p.slug,
           pi.image_url, v.shop_name, cat.name as category_name,
           CASE 
               WHEN p.sale_price IS NOT NULL AND p.sale_price < p.price 
               THEN p.sale_price 
               ELSE p.price 
           END as current_price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.created_at DESC
", [$_SESSION['user_id']]);

// Calculate totals
$subtotal = 0;
$totalItems = 0;
$outOfStockItems = [];

foreach ($cartItems as $item) {
    if ($item['stock_quantity'] < $item['quantity']) {
        $outOfStockItems[] = $item;
    } else {
        $subtotal += $item['current_price'] * $item['quantity'];
        $totalItems += $item['quantity'];
    }
}

// Get shipping cost (could be dynamic based on location/weight)
$shippingCost = $subtotal >= 1000 ? 0 : 100; // Free shipping over Rs. 1000

// Calculate tax (13% in Nepal)
$taxRate = 13;
$taxAmount = ($subtotal * $taxRate) / 100;

// Total
$total = $subtotal + $shippingCost + $taxAmount;

// Handle cart updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit();
    }
    
    $action = $_POST['action'];
    $cartId = intval($_POST['cart_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    
    switch ($action) {
        case 'update':
            if ($cartId > 0) {
                // Verify cart item belongs to user
                $cartItem = $database->fetchOne(
                    "SELECT c.*, p.stock_quantity FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                     WHERE c.id = ? AND c.user_id = ?", 
                    [$cartId, $_SESSION['user_id']]
                );
                
                if ($cartItem) {
                    if ($quantity <= 0) {
                        // Remove item
                        $database->delete('cart', 'id = ?', [$cartId]);
                        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
                    } elseif ($quantity <= $cartItem['stock_quantity']) {
                        // Update quantity
                        $database->update('cart', ['quantity' => $quantity], 'id = ?', [$cartId]);
                        echo json_encode(['success' => true, 'message' => 'Cart updated']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                }
            }
            break;
            
        case 'remove':
            if ($cartId > 0) {
                $database->delete('cart', 'id = ? AND user_id = ?', [$cartId, $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            }
            break;
            
        case 'clear':
            $database->delete('cart', 'user_id = ?', [$_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Cart cleared']);
            break;
    }
    exit();
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <nav class="text-sm breadcrumbs mb-6">
        <ol class="flex items-center space-x-2 text-gray-500">
            <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li class="text-gray-800">Shopping Cart</li>
        </ol>
    </nav>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Shopping Cart</h1>
        <span class="text-gray-600"><?php echo $totalItems; ?> item(s)</span>
    </div>

    <?php if (empty($cartItems)): ?>
        <!-- Empty Cart -->
        <div class="text-center py-16">
            <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-6"></i>
            <h2 class="text-2xl font-semibold text-gray-600 mb-4">Your cart is empty</h2>
            <p class="text-gray-500 mb-8">Looks like you haven't added anything to your cart yet.</p>
            <a href="?page=products" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200">
                Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <!-- Out of Stock Warning -->
                <?php if (!empty($outOfStockItems)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                            <span class="font-semibold text-red-800">Some items in your cart are out of stock or have limited availability</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Cart Items</h2>
                            <button onclick="clearCart()" class="text-red-500 hover:text-red-700 text-sm">
                                <i class="fas fa-trash mr-1"></i>Clear Cart
                            </button>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="p-6 cart-item" data-cart-id="<?php echo $item['id']; ?>">
                                <div class="flex items-start space-x-4">
                                    <!-- Product Image -->
                                    <div class="flex-shrink-0">
                                        <a href="?page=product&id=<?php echo $item['product_id']; ?>">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?php echo SITE_URL . $item['image_url']; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     class="w-20 h-20 object-cover rounded-lg">
                                            <?php else: ?>
                                                <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                    </div>

                                    <!-- Product Details -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <h3 class="font-semibold text-gray-800 mb-1">
                                                    <a href="?page=product&id=<?php echo $item['product_id']; ?>" 
                                                       class="hover:text-primary">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </h3>
                                                <div class="text-sm text-gray-500 space-y-1">
                                                    <p>Category: <?php echo htmlspecialchars($item['category_name']); ?></p>
                                                    <p>Sold by: <?php echo htmlspecialchars($item['shop_name']); ?></p>
                                                </div>
                                            </div>
                                            
                                            <!-- Remove Button -->
                                            <button onclick="removeFromCart(<?php echo $item['id']; ?>)" 
                                                    class="text-gray-400 hover:text-red-500 ml-4">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <!-- Price and Quantity -->
                                        <div class="flex items-center justify-between mt-4">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-lg font-bold text-primary">
                                                    <?php echo formatPrice($item['current_price']); ?>
                                                </span>
                                                <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                                    <span class="text-sm text-gray-500 line-through">
                                                        <?php echo formatPrice($item['price']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Quantity Controls -->
                                            <div class="flex items-center">
                                                <div class="flex items-center border border-gray-300 rounded-md">
                                                    <button onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)" 
                                                            class="px-3 py-1 hover:bg-gray-100" 
                                                            <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <span class="px-4 py-1 border-l border-r border-gray-300 quantity-display">
                                                        <?php echo $item['quantity']; ?>
                                                    </span>
                                                    <button onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)" 
                                                            class="px-3 py-1 hover:bg-gray-100"
                                                            <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                
                                                <span class="ml-3 text-sm text-gray-600">
                                                    = <?php echo formatPrice($item['current_price'] * $item['quantity']); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Stock Status -->
                                        <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                                            <div class="mt-2 text-sm text-red-600">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Only <?php echo $item['stock_quantity']; ?> in stock
                                            </div>
                                        <?php elseif ($item['stock_quantity'] <= 5): ?>
                                            <div class="mt-2 text-sm text-orange-600">
                                                <i class="fas fa-clock mr-1"></i>
                                                Only <?php echo $item['stock_quantity']; ?> left in stock
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Continue Shopping -->
                <div class="mt-6">
                    <a href="?page=products" class="text-primary hover:text-opacity-80 font-semibold">
                        <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                    <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal (<?php echo $totalItems; ?> items):</span>
                            <span id="subtotal-amount"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span>Shipping:</span>
                            <span class="<?php echo $shippingCost == 0 ? 'text-green-600' : ''; ?>">
                                <?php echo $shippingCost == 0 ? 'FREE' : formatPrice($shippingCost); ?>
                            </span>
                        </div>
                        
                        <?php if ($shippingCost > 0): ?>
                            <div class="text-xs text-gray-500">
                                Free shipping on orders over Rs. 1,000
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between">
                            <span>Tax (<?php echo $taxRate; ?>%):</span>
                            <span id="tax-amount"><?php echo formatPrice($taxAmount); ?></span>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-3">
                            <div class="flex justify-between font-semibold text-lg">
                                <span>Total:</span>
                                <span class="text-primary" id="total-amount"><?php echo formatPrice($total); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <button onclick="proceedToCheckout()" 
                            class="w-full bg-primary text-white py-3 px-4 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200 mt-6 <?php echo !empty($outOfStockItems) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                            <?php echo !empty($outOfStockItems) ? 'disabled' : ''; ?>>
                        <i class="fas fa-lock mr-2"></i>Proceed to Checkout
                    </button>
                    
                    <?php if (!empty($outOfStockItems)): ?>
                        <p class="text-xs text-red-600 mt-2 text-center">
                            Please remove out of stock items to continue
                        </p>
                    <?php endif; ?>

                    <!-- Security Badges -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-center space-x-4 text-gray-400">
                            <i class="fas fa-shield-alt"></i>
                            <i class="fas fa-lock"></i>
                            <i class="fab fa-cc-visa"></i>
                            <i class="fab fa-cc-mastercard"></i>
                        </div>
                        <p class="text-xs text-gray-500 text-center mt-2">Secure Payment</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(cartId, newQuantity) {
    if (newQuantity < 1) {
        removeFromCart(cartId);
        return;
    }

    fetch('?page=cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&cart_id=${cartId}&quantity=${newQuantity}&csrf_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Refresh to update totals
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating cart', 'error');
    });
}

function removeFromCart(cartId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }

    fetch('?page=cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&cart_id=${cartId}&csrf_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error removing item', 'error');
    });
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your cart?')) {
        return;
    }

    fetch('?page=cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=clear&csrf_token=${csrfToken}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error clearing cart', 'error');
    });
}

function proceedToCheckout() {
    <?php if (!empty($outOfStockItems)): ?>
        showNotification('Please remove out of stock items before proceeding', 'error');
        return;
    <?php endif; ?>
    
    window.location.href = '?page=checkout';
}
</script>
