<?php
$pageTitle = 'My Wishlist';
$pageDescription = 'Your saved favorite products';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirectTo('?page=login&redirect=' . urlencode('?page=wishlist'));
}

global $database;

// Get wishlist items
$wishlistItems = $database->fetchAll("
    SELECT w.*, p.name, p.price, p.sale_price, p.stock_quantity, p.slug,
           pi.image_url, v.shop_name, c.name as category_name,
           CASE 
               WHEN p.sale_price IS NOT NULL AND p.sale_price < p.price 
               THEN p.sale_price 
               ELSE p.price 
           END as current_price
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ? AND p.status = 'active'
    ORDER BY w.created_at DESC
", [$_SESSION['user_id']]);
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <nav class="text-sm breadcrumbs mb-6">
        <ol class="flex items-center space-x-2 text-gray-500">
            <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li class="text-gray-800">My Wishlist</li>
        </ol>
    </nav>

    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-800">My Wishlist</h1>
        <span class="text-gray-600"><?php echo count($wishlistItems); ?> item(s)</span>
    </div>

    <?php if (empty($wishlistItems)): ?>
        <!-- Empty Wishlist -->
        <div class="text-center py-16">
            <i class="fas fa-heart text-6xl text-gray-300 mb-6"></i>
            <h2 class="text-2xl font-semibold text-gray-600 mb-4">Your wishlist is empty</h2>
            <p class="text-gray-500 mb-8">Save your favorite products to your wishlist for easy access later.</p>
            <a href="?page=products" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200">
                Browse Products
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden group relative">
                    <!-- Remove from Wishlist Button -->
                    <button onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)" 
                            class="absolute top-2 right-2 z-10 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition duration-200 opacity-0 group-hover:opacity-100">
                        <i class="fas fa-times text-sm"></i>
                    </button>

                    <!-- Product Image -->
                    <div class="relative">
                        <a href="?page=product&id=<?php echo $item['product_id']; ?>">
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo SITE_URL . $item['image_url']; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="w-full h-48 object-cover group-hover:scale-105 transition duration-200">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-3xl"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Sale Badge -->
                        <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                            <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-semibold">
                                <?php 
                                $discount = round((($item['price'] - $item['sale_price']) / $item['price']) * 100);
                                echo "-{$discount}%";
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Stock Status -->
                        <?php if ($item['stock_quantity'] <= 0): ?>
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                <span class="bg-red-500 text-white px-3 py-1 rounded-md font-semibold">Out of Stock</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="p-4">
                        <!-- Category & Vendor -->
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                            <?php if ($item['category_name']): ?>
                                <span class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($item['category_name']); ?></span>
                            <?php endif; ?>
                            <?php if ($item['shop_name']): ?>
                                <span><?php echo htmlspecialchars($item['shop_name']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Product Name -->
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                            <a href="?page=product&id=<?php echo $item['product_id']; ?>" 
                               class="hover:text-primary transition duration-200">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        </h3>
                        
                        <!-- Price -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                    <span class="text-lg font-bold text-primary"><?php echo formatPrice($item['sale_price']); ?></span>
                                    <span class="text-sm text-gray-500 line-through"><?php echo formatPrice($item['price']); ?></span>
                                <?php else: ?>
                                    <span class="text-lg font-bold text-primary"><?php echo formatPrice($item['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Added Date -->
                        <div class="text-xs text-gray-500 mb-3">
                            Added <?php echo timeAgo($item['created_at']); ?>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex space-x-2">
                            <button onclick="addToCart(<?php echo $item['product_id']; ?>)" 
                                    class="flex-1 bg-primary text-white py-2 rounded-md font-semibold hover:bg-opacity-90 transition duration-200 <?php echo $item['stock_quantity'] <= 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                    <?php echo $item['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                <?php if ($item['stock_quantity'] <= 0): ?>
                                    <i class="fas fa-times-circle mr-2"></i>Out of Stock
                                <?php else: ?>
                                    <i class="fas fa-cart-plus mr-2"></i>Add to Cart
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Clear Wishlist -->
        <div class="mt-8 text-center">
            <button onclick="clearWishlist()" class="text-red-500 hover:text-red-700 font-semibold">
                <i class="fas fa-trash mr-2"></i>Clear All Wishlist Items
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(productId) {
    if (!confirm('Remove this item from your wishlist?')) {
        return;
    }
    
    fetch('?ajax=wishlist&action=remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            csrf_token: csrfToken
        })
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

function clearWishlist() {
    if (!confirm('Are you sure you want to clear your entire wishlist?')) {
        return;
    }
    
    // TODO: Implement clear wishlist functionality
    showNotification('Clear wishlist feature will be available soon', 'info');
}

// Add line-clamp utility
const style = document.createElement('style');
style.textContent = `
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
`;
document.head.appendChild(style);
</script>
