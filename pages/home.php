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

<!-- Hero Section -->
<section class="bg-gradient-primary text-white py-20">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-5xl md:text-6xl font-bold mb-6 text-shadow">
            Welcome to <span class="text-accent">Sasto Hub</span>
        </h1>
        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
            Discover thousands of quality products at unbeatable prices. Shop from trusted vendors across the globe.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="?page=products" class="bg-white text-primary px-8 py-4 rounded-lg font-semibold text-lg hover:bg-opacity-90 transition duration-300">
                <i class="fas fa-shopping-bag mr-2"></i>Shop Now
            </a>
            <a href="?page=register&type=vendor" class="border-2 border-white text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-primary transition duration-300">
                <i class="fas fa-store mr-2"></i>Become a Vendor
            </a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Shop by Category</h2>
            <p class="text-gray-600 text-lg">Find exactly what you're looking for</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ($categories as $category): ?>
                <a href="?page=category&slug=<?php echo $category['slug']; ?>" 
                   class="group bg-gray-50 rounded-lg p-6 text-center hover:shadow-lg transition duration-300">
                    <div class="w-16 h-16 mx-auto mb-4 bg-primary rounded-full flex items-center justify-center text-white text-2xl group-hover:scale-110 transition duration-300">
                        <i class="fas fa-tag"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo $category['product_count']; ?> products</p>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="?page=products" class="text-primary font-semibold hover:text-opacity-80">
                View All Categories <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<?php if (!empty($featuredProducts)): ?>
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Featured Products</h2>
            <p class="text-gray-600 text-lg">Hand-picked products just for you</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featuredProducts as $product): ?>
                <?php include 'includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="?page=products&featured=1" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-300">
                View All Featured Products
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sale Products Section -->
<?php if (!empty($saleProducts)): ?>
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                <i class="fas fa-fire text-red-500 mr-2"></i>Hot Deals
            </h2>
            <p class="text-gray-600 text-lg">Limited time offers you can't miss</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($saleProducts as $product): ?>
                <?php include 'includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="?page=products&sale=1" class="bg-red-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-300">
                View All Sale Products
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest Products Section -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Latest Arrivals</h2>
            <p class="text-gray-600 text-lg">Discover the newest products</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6">
            <?php foreach ($latestProducts as $product): ?>
                <?php include 'includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="?page=products" class="bg-secondary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-300">
                View All Products
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
