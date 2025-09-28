<?php
// Simple test file to verify includes are working - DELETE AFTER TESTING

require_once 'config/config.php';

echo "<h2>Include Path Test</h2>";

// Test 1: Check if renderProductCard function exists
if (function_exists('renderProductCard')) {
    echo "✅ renderProductCard function is available<br>";
} else {
    echo "❌ renderProductCard function NOT available<br>";
}

// Test 2: Check if product-card.php exists
$productCardPath = ROOT_PATH . '/includes/product-card.php';
if (file_exists($productCardPath)) {
    echo "✅ product-card.php found at: " . $productCardPath . "<br>";
} else {
    echo "❌ product-card.php NOT found at: " . $productCardPath . "<br>";
}

// Test 3: Create a dummy product and test render function
$dummyProduct = [
    'id' => 1,
    'name' => 'Test Product',
    'price' => 1000.00,
    'sale_price' => null,
    'image_url' => null,
    'shop_name' => 'Test Shop',
    'category_name' => 'Test Category',
    'stock_quantity' => 10,
    'featured' => 0
];

echo "<h3>Test Product Card Render:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";

try {
    renderProductCard($dummyProduct);
    echo "</div>";
    echo "✅ Product card rendered successfully<br>";
} catch (Exception $e) {
    echo "</div>";
    echo "❌ Error rendering product card: " . $e->getMessage() . "<br>";
}

echo "<h3>Path Information:</h3>";
echo "ROOT_PATH: " . ROOT_PATH . "<br>";
echo "SITE_URL: " . SITE_URL . "<br>";
echo "Current directory: " . __DIR__ . "<br>";

echo "<h3>File Existence Check:</h3>";
$filesToCheck = [
    'config/config.php',
    'config/database.php', 
    'includes/product-card.php',
    'includes/header.php',
    'includes/footer.php'
];

foreach ($filesToCheck as $file) {
    $fullPath = ROOT_PATH . '/' . $file;
    $exists = file_exists($fullPath) ? '✅' : '❌';
    echo "{$exists} {$file} - {$fullPath}<br>";
}
?>
