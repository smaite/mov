<?php
require_once __DIR__ . '/config/config.php';

echo "=== Sasto Hub Authentication Test ===\n\n";

// Test 1: Login with existing user
echo "Test 1: Login with existing customer credentials\n";
echo "Email: customer@test.com\n";
echo "Password: password\n\n";

$email = 'customer@test.com';
$password = 'password';

global $database;

// Get user
$user = $database->fetchOne(
    "SELECT * FROM users WHERE email = ?", 
    [$email]
);

if (!$user) {
    echo "❌ User not found\n";
} else {
    echo "✓ User found: " . $user['first_name'] . " " . $user['last_name'] . "\n";
    echo "✓ User type: " . $user['user_type'] . "\n";
    echo "✓ Status: " . $user['status'] . "\n";
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        echo "✓ Password verification: SUCCESS\n";
        echo "✓ Login would succeed\n";
    } else {
        echo "❌ Password verification: FAILED\n";
    }
}

echo "\n---\n";

// Test 2: Try invalid password
echo "\nTest 2: Login with wrong password\n";
echo "Email: customer@test.com\n";
echo "Password: wrongpassword\n\n";

if (password_verify('wrongpassword', $user['password'])) {
    echo "❌ Password verification: FAILED - should not verify\n";
} else {
    echo "✓ Password verification: CORRECTLY REJECTED\n";
}

echo "\n---\n";

// Test 3: Check all test users
echo "\nTest 3: All test users\n\n";

$testEmails = ['customer@test.com', 'vendor@test.com'];

foreach ($testEmails as $testEmail) {
    $testUser = $database->fetchOne(
        "SELECT id, username, email, first_name, last_name, user_type, status, password FROM users WHERE email = ?", 
        [$testEmail]
    );
    
    if ($testUser) {
        echo "✓ " . $testUser['user_type'] . ": " . $testEmail . "\n";
        echo "  Username: " . $testUser['username'] . "\n";
        echo "  Status: " . $testUser['status'] . "\n";
        
        // Verify password
        if (!empty($testUser['password']) && password_verify('password', $testUser['password'])) {
            echo "  Password: ✓ CORRECT\n";
        } else {
            echo "  Password: ❌ CHECK FAILED OR EMPTY\n";
        }
    } else {
        echo "❌ " . $testEmail . " NOT FOUND\n";
    }
    echo "\n";
}

echo "=== Test Complete ===\n";
?>
