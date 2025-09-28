<?php
$pageTitle = 'Home';
$pageDescription = 'Shop the best products at the lowest prices - Sasto Hub';

global $database;

// Get featured products
$featuredProducts = $database->fetchAll("
    SELECT p.*, pi.image_url, v.shop_name, c.name as category_name
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active' AND p.featured = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
");

// Get categories with product count
$categories = $database->fetchAll("
    SELECT c.*, COUNT(p.id) as product_count,
           (SELECT pi.image_url FROM product_images pi 
            JOIN products p2 ON pi.product_id = p2.id 
            WHERE p2.category_id = c.id AND pi.is_primary = 1 
            LIMIT 1) as sample_image
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    WHERE c.parent_id IS NULL AND c.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.sort_order, c.name 
    LIMIT 8
");

// Get latest products
$latestProducts = $database->fetchAll("
    SELECT p.*, pi.image_url, v.shop_name, c.name as category_name
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active' 
    ORDER BY p.created_at DESC 
    LIMIT 12
");

// Get sale products
$saleProducts = $database->fetchAll("
    SELECT p.*, pi.image_url, v.shop_name, c.name as category_name,
           ROUND(((p.price - p.sale_price) / p.price) * 100) as discount_percentage
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active' AND p.sale_price IS NOT NULL AND p.sale_price < p.price
    ORDER BY discount_percentage DESC 
    LIMIT 8
");
?>

<!-- Custom Hero Banners -->
<?php include __DIR__ . '/../includes/custom-hero.php'; ?>

<!-- Modern Categories Section -->
<section class="py-12 lg:py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">Discover thousands of products across all categories</p>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 lg:gap-6">
            <?php foreach ($categories as $category): ?>
                <a href="?page=category&slug=<?php echo $category['slug']; ?>" 
                   class="group bg-white rounded-2xl p-6 text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <i class="fas fa-tag"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2 text-sm lg:text-base group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full inline-block"><?php echo number_format($category['product_count']); ?> items</p>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-12">
            <a href="?page=products" class="inline-flex items-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                View All Categories
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<?php if (!empty($featuredProducts)): ?>
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Featured Products</h2>
                <p class="text-gray-600">Hand-picked products just for you</p>
            </div>
            <a href="?page=products&featured=1" class="hidden lg:inline-flex items-center text-blue-600 font-semibold hover:text-blue-800 transition-colors">
                View All
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 lg:gap-6">
            <?php foreach ($featuredProducts as $product): ?>
                <?php renderProductCard($product); ?>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-12 lg:hidden">
            <a href="?page=products&featured=1" class="inline-flex items-center bg-gradient-to-r from-amber-500 to-orange-500 text-white px-8 py-3 rounded-xl font-semibold hover:from-amber-600 hover:to-orange-600 transition-all duration-200 shadow-lg">
                View All Featured
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Hot Deals Section -->
<?php if (!empty($saleProducts)): ?>
<section class="py-16 bg-gradient-to-br from-red-50 to-pink-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2 flex items-center">
                    <div class="bg-gradient-to-r from-red-500 to-pink-500 text-white p-2 rounded-xl mr-3">
                        <i class="fas fa-fire"></i>
                    </div>
                    Hot Deals
                </h2>
                <p class="text-gray-600">Limited time offers you can't miss</p>
            </div>
            <a href="?page=products&sale=1" class="hidden lg:inline-flex items-center text-red-600 font-semibold hover:text-red-800 transition-colors">
                View All
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 lg:gap-6">
            <?php foreach ($saleProducts as $product): ?>
                <?php renderProductCard($product); ?>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-12 lg:hidden">
            <a href="?page=products&sale=1" class="inline-flex items-center bg-gradient-to-r from-red-500 to-pink-500 text-white px-8 py-3 rounded-xl font-semibold hover:from-red-600 hover:to-pink-600 transition-all duration-200 shadow-lg">
                Shop All Deals
                <i class="fas fa-fire ml-2"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest Products Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2 flex items-center">
                    <div class="bg-gradient-to-r from-green-500 to-teal-500 text-white p-2 rounded-xl mr-3">
                        <i class="fas fa-sparkles"></i>
                    </div>
                    New Arrivals
                </h2>
                <p class="text-gray-600">Fresh products just landed</p>
            </div>
            <a href="?page=products" class="hidden lg:inline-flex items-center text-green-600 font-semibold hover:text-green-800 transition-colors">
                View All
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 lg:gap-6">
            <?php foreach ($latestProducts as $product): ?>
                <?php renderProductCard($product); ?>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-12 lg:hidden">
            <a href="?page=products" class="inline-flex items-center bg-gradient-to-r from-green-500 to-teal-500 text-white px-8 py-3 rounded-xl font-semibold hover:from-green-600 hover:to-teal-600 transition-all duration-200 shadow-lg">
                Explore All Products
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-16 bg-secondary text-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Why Choose Sasto Hub?</h2>
            <p class="text-gray-300 text-lg">We provide the best shopping experience</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary rounded-full flex items-center justify-center text-2xl">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Fast Delivery</h3>
                <p class="text-gray-300">Quick and reliable delivery to your doorstep</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary rounded-full flex items-center justify-center text-2xl">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Secure Payment</h3>
                <p class="text-gray-300">Your payment information is safe and secure</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary rounded-full flex items-center justify-center text-2xl">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">24/7 Support</h3>
                <p class="text-gray-300">We're here to help you anytime, anywhere</p>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="py-16 bg-accent">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Stay Updated</h2>
        <p class="text-gray-700 mb-8 max-w-2xl mx-auto">
            Subscribe to our newsletter and be the first to know about new products, special offers, and exclusive deals.
        </p>
        
        <form class="max-w-md mx-auto flex gap-2">
            <input type="email" placeholder="Enter your email address" 
                   class="flex-1 px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            <button type="submit" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-300">
                Subscribe
            </button>
        </form>
    </div>
</section>
