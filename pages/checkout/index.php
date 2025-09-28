<?php
$pageTitle = 'Checkout';
$pageDescription = 'Complete your order - Secure checkout';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirectTo('?page=login&redirect=' . urlencode('?page=checkout'));
}

global $database;

// Get cart items
$cartItems = $database->fetchAll("
    SELECT c.*, p.name, p.price, p.sale_price, p.stock_quantity, p.weight,
           pi.image_url, v.shop_name, v.id as vendor_id,
           CASE 
               WHEN p.sale_price IS NOT NULL AND p.sale_price < p.price 
               THEN p.sale_price 
               ELSE p.price 
           END as current_price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN vendors v ON p.vendor_id = v.id
    WHERE c.user_id = ? AND p.status = 'active' AND p.stock_quantity >= c.quantity
    ORDER BY v.shop_name, p.name
", [$_SESSION['user_id']]);

// Redirect to cart if no items or stock issues
if (empty($cartItems)) {
    redirectTo('?page=cart');
}

// Get user details
$user = $database->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

// Calculate totals
$subtotal = 0;
$totalItems = 0;
$totalWeight = 0;

foreach ($cartItems as $item) {
    $subtotal += $item['current_price'] * $item['quantity'];
    $totalItems += $item['quantity'];
    $totalWeight += ($item['weight'] ?? 0) * $item['quantity'];
}

// Calculate shipping (could be more sophisticated)
$shippingCost = $subtotal >= 1000 ? 0 : 100;

// Calculate tax
$taxRate = 13;
$taxAmount = ($subtotal * $taxRate) / 100;

// Total
$total = $subtotal + $shippingCost + $taxAmount;

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Validate form data
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $postalCode = sanitizeInput($_POST['postal_code'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? '';
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        // Use billing address for shipping if different address not specified
        $shippingAddress = $address . ', ' . $city . ', ' . $postalCode;
        $billingAddress = $shippingAddress; // For now, same as shipping
        
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($address) || empty($city)) {
            $errors[] = 'Please fill in all required fields.';
        }
        
        if (empty($paymentMethod)) {
            $errors[] = 'Please select a payment method.';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Verify cart items are still available
        foreach ($cartItems as $item) {
            $currentStock = $database->fetchOne(
                "SELECT stock_quantity FROM products WHERE id = ?", 
                [$item['product_id']]
            );
            
            if (!$currentStock || $currentStock['stock_quantity'] < $item['quantity']) {
                $errors[] = "Product '{$item['name']}' is no longer available in the requested quantity.";
            }
        }
        
        if (empty($errors)) {
            try {
                // Start transaction
                $database->getConnection()->beginTransaction();
                
                // Generate order number
                $orderNumber = 'SH' . date('Ymd') . sprintf('%06d', rand(1, 999999));
                
                // Create order
                $orderData = [
                    'user_id' => $_SESSION['user_id'],
                    'order_number' => $orderNumber,
                    'total_amount' => $total,
                    'shipping_amount' => $shippingCost,
                    'tax_amount' => $taxAmount,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => $paymentMethod,
                    'shipping_address' => $shippingAddress,
                    'billing_address' => $billingAddress,
                    'notes' => $notes
                ];
                
                $orderId = $database->insert('orders', $orderData);
                
                if (!$orderId) {
                    throw new Exception('Failed to create order');
                }
                
                // Create order items and update stock
                foreach ($cartItems as $item) {
                    // Insert order item
                    $orderItemData = [
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'vendor_id' => $item['vendor_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['current_price'],
                        'total' => $item['current_price'] * $item['quantity']
                    ];
                    
                    $database->insert('order_items', $orderItemData);
                    
                    // Update product stock and sales
                    $database->query(
                        "UPDATE products SET stock_quantity = stock_quantity - ?, total_sales = total_sales + ? WHERE id = ?",
                        [$item['quantity'], $item['quantity'], $item['product_id']]
                    );
                }
                
                // Clear cart
                $database->delete('cart', 'user_id = ?', [$_SESSION['user_id']]);
                
                // Update user information if changed
                if ($firstName !== $user['first_name'] || $lastName !== $user['last_name'] || 
                    $phone !== $user['phone'] || $address !== $user['address'] || $city !== $user['city']) {
                    
                    $database->update('users', [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $phone,
                        'address' => $address,
                        'city' => $city
                    ], 'id = ?', [$_SESSION['user_id']]);
                }
                
                // Commit transaction
                $database->getConnection()->commit();
                
                // Redirect to order confirmation
                redirectTo('?page=orders&action=view&id=' . $orderId . '&success=1');
                
            } catch (Exception $e) {
                // Rollback transaction
                $database->getConnection()->rollback();
                $errors[] = 'Failed to process order. Please try again.';
                error_log("Order processing error: " . $e->getMessage());
            }
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <nav class="text-sm breadcrumbs mb-6">
        <ol class="flex items-center space-x-2 text-gray-500">
            <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li><a href="?page=cart" class="hover:text-primary">Cart</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li class="text-gray-800">Checkout</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Checkout</h1>
        <div class="flex items-center space-x-4 text-sm">
            <div class="flex items-center text-gray-400">
                <i class="fas fa-shopping-cart mr-1"></i>
                <span>Cart</span>
            </div>
            <i class="fas fa-chevron-right text-gray-400"></i>
            <div class="flex items-center text-primary">
                <i class="fas fa-credit-card mr-1"></i>
                <span>Checkout</span>
            </div>
            <i class="fas fa-chevron-right text-gray-400"></i>
            <div class="flex items-center text-gray-400">
                <i class="fas fa-check-circle mr-1"></i>
                <span>Complete</span>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-center mb-2">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                <span class="font-semibold text-red-800">Please fix the following errors:</span>
            </div>
            <ul class="list-disc list-inside text-red-700 text-sm">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="checkout-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Billing Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Billing Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required
                                   value="<?php echo htmlspecialchars($firstName ?? $user['first_name']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?php echo htmlspecialchars($lastName ?? $user['last_name']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($email ?? $user['email']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?php echo htmlspecialchars($phone ?? $user['phone']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Shipping Address</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                            <textarea id="address" name="address" rows="3" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                      placeholder="Enter your full address"><?php echo htmlspecialchars($address ?? $user['address']); ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                <input type="text" id="city" name="city" required
                                       value="<?php echo htmlspecialchars($city ?? $user['city']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code"
                                       value="<?php echo htmlspecialchars($postalCode ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Payment Method</h2>
                    
                    <div class="space-y-3">
                        <label class="flex items-center p-3 border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="payment_method" value="cod" class="mr-3" checked>
                            <div class="flex items-center">
                                <i class="fas fa-money-bill-wave text-green-500 mr-3"></i>
                                <div>
                                    <span class="font-medium">Cash on Delivery</span>
                                    <p class="text-sm text-gray-500">Pay when you receive your order</p>
                                </div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="payment_method" value="bank_transfer" class="mr-3">
                            <div class="flex items-center">
                                <i class="fas fa-university text-blue-500 mr-3"></i>
                                <div>
                                    <span class="font-medium">Bank Transfer</span>
                                    <p class="text-sm text-gray-500">Transfer to our bank account</p>
                                </div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer opacity-50">
                            <input type="radio" name="payment_method" value="online" class="mr-3" disabled>
                            <div class="flex items-center">
                                <i class="fas fa-credit-card text-primary mr-3"></i>
                                <div>
                                    <span class="font-medium">Online Payment</span>
                                    <p class="text-sm text-gray-500">Coming soon - Credit/Debit Card</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold mb-4">Order Notes (Optional)</h2>
                    <textarea name="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                              placeholder="Any special instructions for your order..."><?php echo htmlspecialchars($notes ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                    <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                    
                    <!-- Order Items -->
                    <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="flex items-center space-x-3 py-2 border-b border-gray-100">
                                <div class="flex-shrink-0">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?php echo SITE_URL . $item['image_url']; ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="w-12 h-12 object-cover rounded">
                                    <?php else: ?>
                                        <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400 text-sm"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate"><?php echo htmlspecialchars($item['name']); ?></p>
                                    <p class="text-xs text-gray-500">Qty: <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="text-sm font-medium text-gray-800">
                                    <?php echo formatPrice($item['current_price'] * $item['quantity']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Totals -->
                    <div class="space-y-2 text-sm mb-6">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span>Shipping:</span>
                            <span class="<?php echo $shippingCost == 0 ? 'text-green-600' : ''; ?>">
                                <?php echo $shippingCost == 0 ? 'FREE' : formatPrice($shippingCost); ?>
                            </span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span>Tax (<?php echo $taxRate; ?>%):</span>
                            <span><?php echo formatPrice($taxAmount); ?></span>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-2">
                            <div class="flex justify-between font-semibold text-lg">
                                <span>Total:</span>
                                <span class="text-primary"><?php echo formatPrice($total); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Place Order Button -->
                    <button type="submit" 
                            class="w-full bg-primary text-white py-3 px-4 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200 mb-4">
                        <i class="fas fa-lock mr-2"></i>Place Order
                    </button>
                    
                    <!-- Security Info -->
                    <div class="text-center">
                        <div class="flex items-center justify-center space-x-2 text-gray-400 text-sm mb-2">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Checkout</span>
                        </div>
                        <p class="text-xs text-gray-500">Your information is protected by 256-bit SSL encryption</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Form validation
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    const requiredFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city'];
    let isValid = true;
    
    requiredFields.forEach(function(fieldName) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('border-red-500');
        } else {
            field.classList.remove('border-red-500');
        }
    });
    
    // Check payment method
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethod) {
        isValid = false;
        showNotification('Please select a payment method', 'error');
    }
    
    if (!isValid) {
        e.preventDefault();
        showNotification('Please fill in all required fields', 'error');
    }
});

// Auto-fill city based on postal code (can be enhanced with API)
document.getElementById('postal_code').addEventListener('blur', function() {
    // This is where you could integrate with a postal code API
});
</script>
