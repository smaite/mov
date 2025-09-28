<?php
// Product card component
// Expects $product variable with product data

// Prevent direct access
if (!defined('SITE_URL')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed');
}

// Check if $product is defined
if (!isset($product)) {
    return;
}
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden product-card group h-full flex flex-col">
    <!-- Product Image -->
    <div class="relative image-zoom flex-shrink-0">
        <a href="?page=product&id=<?php echo $product['id']; ?>">
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?php echo SITE_URL . $product['image_url']; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     class="w-full h-40 sm:h-48 object-cover">
            <?php else: ?>
                <div class="w-full h-40 sm:h-48 bg-gray-200 flex items-center justify-center image-placeholder">
                    <i class="fas fa-image text-gray-400 text-2xl sm:text-3xl"></i>
                </div>
            <?php endif; ?>
        </a>
        
        <!-- Sale Badge -->
        <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
            <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-semibold">
                <?php 
                $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                echo "-{$discount}%";
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Featured Badge -->
        <?php if ($product['featured']): ?>
            <div class="absolute top-2 right-2 bg-accent text-gray-800 px-2 py-1 rounded-md text-xs font-semibold">
                <i class="fas fa-star mr-1"></i>Featured
            </div>
        <?php endif; ?>
        
        <!-- Wishlist & Quick Actions -->
        <div class="absolute top-2 right-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 lg:opacity-100 transition-opacity duration-300">
            <?php if (!isset($product['featured']) || !$product['featured']): ?>
                <button onclick="toggleWishlist(<?php echo $product['id']; ?>)" 
                        class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 shadow-md transition duration-200 touch-manipulation"
                        data-product-id="<?php echo $product['id']; ?>">
                    <i class="fas fa-heart text-sm"></i>
                </button>
            <?php endif; ?>
            <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                    class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center hover:bg-opacity-90 shadow-md transition duration-200 touch-manipulation">
                <i class="fas fa-shopping-cart text-sm"></i>
            </button>
        </div>
        
        <!-- Stock Status -->
        <?php if ($product['stock_quantity'] <= 0): ?>
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <span class="bg-red-500 text-white px-3 py-1 rounded-md font-semibold">Out of Stock</span>
            </div>
        <?php elseif ($product['stock_quantity'] <= $product['min_stock_level']): ?>
            <div class="absolute bottom-2 left-2 bg-orange-500 text-white px-2 py-1 rounded-md text-xs font-semibold">
                Low Stock
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Product Info -->
    <div class="p-3 sm:p-4 flex-1 flex flex-col">
        <!-- Category & Vendor -->
        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
            <?php if (!empty($product['category_name'])): ?>
                <span class="bg-gray-100 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($product['category_name']); ?></span>
            <?php endif; ?>
            <?php if (!empty($product['shop_name'])): ?>
                <span class="truncate ml-2"><?php echo htmlspecialchars($product['shop_name']); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Product Name -->
        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2 flex-grow">
            <a href="?page=product&id=<?php echo $product['id']; ?>" 
               class="hover:text-primary transition duration-200 text-sm sm:text-base">
                <?php echo htmlspecialchars($product['name']); ?>
            </a>
        </h3>
        
        <!-- Rating -->
        <?php if ($product['rating'] > 0): ?>
            <div class="flex items-center mb-2">
                <div class="flex text-yellow-400 text-xs sm:text-sm">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $product['rating'] ? '' : 'text-gray-300'; ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="text-xs text-gray-500 ml-1">(<?php echo $product['total_reviews']; ?>)</span>
            </div>
        <?php endif; ?>
        
        <!-- Price -->
        <div class="mb-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-2">
                <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                    <span class="text-lg font-bold text-primary"><?php echo formatPrice($product['sale_price']); ?></span>
                    <span class="text-sm text-gray-500 line-through"><?php echo formatPrice($product['price']); ?></span>
                <?php else: ?>
                    <span class="text-lg font-bold text-primary"><?php echo formatPrice($product['price']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add to Cart Button -->
        <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                class="w-full bg-primary text-white py-2 sm:py-3 rounded-md font-semibold hover:bg-opacity-90 transition duration-200 text-sm sm:text-base touch-manipulation <?php echo $product['stock_quantity'] <= 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
            <?php if ($product['stock_quantity'] <= 0): ?>
                <i class="fas fa-times-circle mr-2"></i>Out of Stock
            <?php else: ?>
                <i class="fas fa-cart-plus mr-2"></i>Add to Cart
            <?php endif; ?>
        </button>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
