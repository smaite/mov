<?php
$productId = intval($_GET['id'] ?? 0);

if ($productId <= 0) {
    redirectTo('?page=products');
}

global $database;

// Get product details
$product = $database->fetchOne("
    SELECT p.*, v.shop_name, v.rating as vendor_rating,
           c.name as category_name, c.slug as category_slug
    FROM products p 
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.status = 'active'
", [$productId]);

if (!$product) {
    redirectTo('?page=404');
}

// Get product images
$images = $database->fetchAll(
    "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, is_primary DESC", 
    [$productId]
);

// Get product attributes
$attributes = $database->fetchAll(
    "SELECT * FROM product_attributes WHERE product_id = ? ORDER BY sort_order", 
    [$productId]
);

// Get reviews
$reviews = $database->fetchAll("
    SELECT r.*, u.first_name, u.last_name, u.profile_image
    FROM reviews r 
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
", [$productId]);

// Get related products
$relatedProducts = $database->fetchAll("
    SELECT p.*, pi.image_url, v.shop_name, c.name as category_name
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
    ORDER BY p.rating DESC, p.total_sales DESC
    LIMIT 8
", [$product['category_id'], $productId]);

// Check if in user's wishlist
$inWishlist = false;
if (isLoggedIn()) {
    $wishlistCheck = $database->fetchOne(
        "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?", 
        [$_SESSION['user_id'], $productId]
    );
    $inWishlist = $wishlistCheck !== false;
}

$pageTitle = $product['name'];
$pageDescription = $product['short_description'] ?: substr(strip_tags($product['description']), 0, 160);

// Calculate discount percentage
$discountPercentage = 0;
if ($product['sale_price'] && $product['sale_price'] < $product['price']) {
    $discountPercentage = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
}

// Calculate current price
$currentPrice = $product['sale_price'] && $product['sale_price'] < $product['price'] ? $product['sale_price'] : $product['price'];
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <nav class="text-sm breadcrumbs mb-6">
        <ol class="flex items-center space-x-2 text-gray-500">
            <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li><a href="?page=products" class="hover:text-primary">Products</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li><a href="?page=category&slug=<?php echo $product['category_slug']; ?>" class="hover:text-primary"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li class="text-gray-800"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-12">
        <!-- Product Images -->
        <div>
            <div class="mb-4">
                <div id="main-image" class="relative rounded-lg overflow-hidden bg-gray-100">
                    <?php if (!empty($images)): ?>
                        <img src="<?php echo SITE_URL . $images[0]['image_url']; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="w-full h-96 object-cover">
                    <?php else: ?>
                        <div class="w-full h-96 flex items-center justify-center bg-gray-200">
                            <i class="fas fa-image text-gray-400 text-6xl"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        <?php if ($discountPercentage > 0): ?>
                            <span class="bg-red-500 text-white px-3 py-1 rounded-md font-semibold">
                                -<?php echo $discountPercentage; ?>%
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($product['featured']): ?>
                            <span class="bg-accent text-gray-800 px-3 py-1 rounded-md font-semibold">
                                <i class="fas fa-star mr-1"></i>Featured
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stock Status -->
                    <?php if ($product['stock_quantity'] <= 0): ?>
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                            <span class="bg-red-500 text-white px-6 py-3 rounded-lg font-semibold text-lg">Out of Stock</span>
                        </div>
                    <?php elseif ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                        <div class="absolute bottom-4 left-4">
                            <span class="bg-orange-500 text-white px-3 py-1 rounded-md font-semibold">
                                Only <?php echo $product['stock_quantity']; ?> left!
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Thumbnail Images -->
            <?php if (count($images) > 1): ?>
                <div class="flex space-x-2 overflow-x-auto">
                    <?php foreach ($images as $index => $image): ?>
                        <button onclick="changeMainImage('<?php echo SITE_URL . $image['image_url']; ?>')"
                                class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 hover:border-primary transition duration-200">
                            <img src="<?php echo SITE_URL . $image['image_url']; ?>" 
                                 alt="<?php echo htmlspecialchars($image['alt_text'] ?: $product['name']); ?>"
                                 class="w-full h-full object-cover">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div>
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </span>
                    <span class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <!-- Rating -->
                <?php if ($product['rating'] > 0): ?>
                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400 mr-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $product['rating'] ? '' : 'text-gray-300'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-sm text-gray-600">
                            <?php echo number_format($product['rating'], 1); ?> 
                            (<?php echo $product['total_reviews']; ?> reviews)
                        </span>
                    </div>
                <?php endif; ?>
                
                <!-- Price -->
                <div class="mb-6">
                    <div class="flex items-center space-x-3">
                        <span class="text-3xl font-bold text-primary"><?php echo formatPrice($currentPrice); ?></span>
                        <?php if ($discountPercentage > 0): ?>
                            <span class="text-xl text-gray-500 line-through"><?php echo formatPrice($product['price']); ?></span>
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm font-semibold">
                                Save <?php echo formatPrice($product['price'] - $currentPrice); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Short Description -->
                <?php if ($product['short_description']): ?>
                    <div class="mb-6">
                        <p class="text-gray-600 text-lg"><?php echo htmlspecialchars($product['short_description']); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Vendor Info -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Sold by</p>
                            <div class="flex items-center">
                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['shop_name']); ?></span>
                            </div>
                            <?php if ($product['vendor_rating'] > 0): ?>
                                <div class="flex items-center mt-1">
                                    <div class="flex text-yellow-400 text-sm mr-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $product['vendor_rating'] ? '' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-xs text-gray-500"><?php echo number_format($product['vendor_rating'], 1); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="?page=vendor&id=<?php echo $product['vendor_id']; ?>" 
                           class="text-primary hover:text-opacity-80 text-sm font-semibold">
                            View Shop
                        </a>
                    </div>
                </div>
                
                <!-- Quantity & Actions -->
                <div class="mb-6">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="flex items-center">
                            <label class="text-sm font-semibold text-gray-700 mr-3">Quantity:</label>
                            <div class="flex items-center border border-gray-300 rounded-md">
                                <button onclick="changeQuantity(-1)" 
                                        class="px-3 py-2 hover:bg-gray-100" 
                                        <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="quantity" value="1" min="1" 
                                       max="<?php echo $product['stock_quantity']; ?>"
                                       class="w-16 px-3 py-2 text-center border-0 focus:outline-none"
                                       <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                <button onclick="changeQuantity(1)" 
                                        class="px-3 py-2 hover:bg-gray-100"
                                        <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                <?php echo $product['stock_quantity']; ?> in stock
                            <?php else: ?>
                                <i class="fas fa-times-circle text-red-500 mr-1"></i>
                                Out of stock
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button onclick="addToCart(<?php echo $productId; ?>, document.getElementById('quantity').value)" 
                                class="flex-1 bg-primary text-white py-3 px-6 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200 <?php echo $product['stock_quantity'] <= 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus mr-2"></i>
                            <?php echo $product['stock_quantity'] <= 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                        </button>
                        
                        <?php if (isLoggedIn()): ?>
                            <button onclick="toggleWishlist(<?php echo $productId; ?>)" 
                                    class="px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200 <?php echo $inWishlist ? 'text-red-500 border-red-300 bg-red-50' : 'text-gray-600'; ?>"
                                    data-product-id="<?php echo $productId; ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Features -->
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center">
                        <i class="fas fa-shipping-fast text-primary mr-2"></i>
                        <span>Fast Delivery</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-undo text-primary mr-2"></i>
                        <span>Easy Returns</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-primary mr-2"></i>
                        <span>Secure Payment</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-headset text-primary mr-2"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Tabs -->
    <div class="bg-white rounded-lg shadow-md mb-12">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6">
                <button onclick="showTab('description')" 
                        class="py-4 border-b-2 border-primary text-primary font-semibold tab-button active" 
                        data-tab="description">
                    Description
                </button>
                <?php if (!empty($attributes)): ?>
                    <button onclick="showTab('specifications')" 
                            class="py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold tab-button" 
                            data-tab="specifications">
                        Specifications
                    </button>
                <?php endif; ?>
                <button onclick="showTab('reviews')" 
                        class="py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold tab-button" 
                        data-tab="reviews">
                    Reviews (<?php echo count($reviews); ?>)
                </button>
            </nav>
        </div>
        
        <div class="p-6">
            <!-- Description Tab -->
            <div id="description-tab" class="tab-content">
                <div class="prose max-w-none">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
            
            <!-- Specifications Tab -->
            <?php if (!empty($attributes)): ?>
                <div id="specifications-tab" class="tab-content hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($attributes as $attribute): ?>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($attribute['attribute_name']); ?>:</span>
                                <span class="text-gray-600"><?php echo htmlspecialchars($attribute['attribute_value']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Reviews Tab -->
            <div id="reviews-tab" class="tab-content hidden">
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-600 mb-2">No Reviews Yet</h3>
                        <p class="text-gray-500">Be the first to review this product!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($reviews as $review): ?>
                            <div class="border-b border-gray-100 pb-6">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <?php if ($review['profile_image']): ?>
                                            <img src="<?php echo SITE_URL . $review['profile_image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($review['first_name']); ?>"
                                                 class="w-10 h-10 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-2">
                                            <div>
                                                <span class="font-semibold text-gray-800">
                                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                                </span>
                                                <?php if ($review['is_verified']): ?>
                                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded ml-2">
                                                        Verified Purchase
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex text-yellow-400">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-gray-300'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($review['title']): ?>
                                            <h4 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($review['title']); ?></h4>
                                        <?php endif; ?>
                                        
                                        <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($review['comment']); ?></p>
                                        
                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                            <span><?php echo timeAgo($review['created_at']); ?></span>
                                            <?php if ($review['helpful_count'] > 0): ?>
                                                <span><?php echo $review['helpful_count']; ?> people found this helpful</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Related Products</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <?php renderProductCard($relatedProduct); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
function changeMainImage(imageUrl) {
    document.querySelector('#main-image img').src = imageUrl;
}

function changeQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    const newValue = Math.max(1, Math.min(<?php echo $product['stock_quantity']; ?>, currentValue + change));
    quantityInput.value = newValue;
}

function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-primary', 'text-primary');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-tab').classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
    activeButton.classList.add('active', 'border-primary', 'text-primary');
    activeButton.classList.remove('border-transparent', 'text-gray-500');
}
</script>
