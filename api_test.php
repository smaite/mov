<?php
/**
 * Simple API Test Script
 * Tests key API endpoints without browser dependencies
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "=== Sasto Hub API Test ===\n\n";

// Test database connection
echo "1. Testing Database Connection:\n";
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection: SUCCESS\n";
} catch (Exception $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

// Test API endpoints directly
echo "\n2. Testing API Endpoints:\n";

$baseUrl = SITE_URL . 'api/';
$testEndpoints = [
    'home' => '?action=home',
    'categories' => '?action=categories',
    'products' => '?action=products',
    'search' => '?action=search&q=test'
];

foreach ($testEndpoints as $name => $params) {
    echo "Testing {$name} endpoint...\n";
    
    $url = $baseUrl . $params;
    
    // Use file_get_contents as alternative to curl
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n",
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success'] === true) {
            echo "✅ {$name}: SUCCESS\n";
            
            // Show some sample data
            switch ($name) {
                case 'home':
                    if (isset($data['featured_products'])) {
                        echo "   - Found " . count($data['featured_products']) . " featured products\n";
                    }
                    if (isset($data['categories'])) {
                        echo "   - Found " . count($data['categories']) . " categories\n";
                    }
                    break;
                case 'categories':
                    if (isset($data['categories'])) {
                        echo "   - Found " . count($data['categories']) . " categories\n";
                    }
                    break;
                case 'products':
                    if (isset($data['products'])) {
                        echo "   - Found " . count($data['products']) . " products\n";
                    }
                    break;
                case 'search':
                    if (isset($data['products'])) {
                        echo "   - Found " . count($data['products']) . " search results\n";
                    }
                    break;
            }
        } else {
            echo "❌ {$name}: INVALID RESPONSE\n";
            if (isset($data['error'])) {
                echo "   Error: " . $data['error'] . "\n";
            }
        }
    } else {
        echo "❌ {$name}: FAILED TO CONNECT\n";
    }
    echo "\n";
}

// Test Google OAuth configuration
echo "3. Testing Google OAuth Configuration:\n";

if (defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID)) {
    echo "✅ Google Client ID: CONFIGURED\n";
} else {
    echo "❌ Google Client ID: NOT CONFIGURED\n";
}

if (defined('GOOGLE_CLIENT_SECRET') && !empty(GOOGLE_CLIENT_SECRET)) {
    echo "✅ Google Client Secret: CONFIGURED\n";
} else {
    echo "❌ Google Client Secret: NOT CONFIGURED\n";
}

if (defined('GOOGLE_REDIRECT_URI') && !empty(GOOGLE_REDIRECT_URI)) {
    echo "✅ Google Redirect URI: CONFIGURED\n";
} else {
    echo "❌ Google Redirect URI: NOT CONFIGURED\n";
}

// Test theme files
echo "\n4. Testing Theme System:\n";

$themeCssPath = __DIR__ . '/assets/css/theme.css';
if (file_exists($themeCssPath)) {
    echo "✅ Website theme CSS: EXISTS\n";
} else {
    echo "❌ Website theme CSS: NOT FOUND\n";
}

$flutterThemePath = __DIR__ . '/andoird/lib/app/theme.dart';
if (file_exists($flutterThemePath)) {
    echo "✅ Flutter theme: EXISTS\n";
} else {
    echo "❌ Flutter theme: NOT FOUND\n";
}

// Test key database tables
echo "\n5. Testing Database Schema:\n";

$keyTables = ['users', 'products', 'categories', 'orders', 'cart', 'wishlist'];
foreach ($keyTables as $table) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '{$table}': EXISTS\n";
        } else {
            echo "❌ Table '{$table}': MISSING\n";
        }
    } catch (Exception $e) {
        echo "❌ Table '{$table}': ERROR - " . $e->getMessage() . "\n";
    }
}

// Test sample data
echo "\n6. Testing Sample Data:\n";

try {
    // Test users
    $userStmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $userCount = $userStmt->fetchColumn();
    echo "✅ Users: {$userCount} records\n";
    
    // Test products
    $productStmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $productCount = $productStmt->fetchColumn();
    echo "✅ Active Products: {$productCount} records\n";
    
    // Test categories
    $categoryStmt = $conn->query("SELECT COUNT(*) as count FROM categories WHERE is_active = 1");
    $categoryCount = $categoryStmt->fetchColumn();
    echo "✅ Active Categories: {$categoryCount} records\n";
    
} catch (Exception $e) {
    echo "❌ Sample Data Test: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "This test script verifies the basic functionality of your Sasto Hub API.\n";
echo "For full integration testing, please:\n";
echo "1. Configure Google OAuth with actual credentials\n";
echo "2. Test the mobile app with the API endpoints\n";
echo "3. Verify authentication flows work correctly\n";
echo "4. Test all CRUD operations through the app\n";

echo "\nAPI Base URL: " . $baseUrl . "\n";
echo "Website URL: " . SITE_URL . "\n";
?>