<?php
require_once __DIR__ . '/config/config.php';

echo "=== Sasto Hub Database & Auth Setup ===\n\n";

// Check users table exists
echo "1. Checking users table structure...\n";
$users = $database->fetchAll("DESCRIBE users");
if ($users) {
    echo "✓ Users table exists with " . count($users) . " columns\n";
    $columns = array_column($users, 'Field');
    echo "  Columns: " . implode(", ", $columns) . "\n";
} else {
    echo "✗ Users table not found\n";
    exit;
}

// Check required columns
$requiredColumns = ['id', 'email', 'password', 'username', 'first_name', 'last_name', 'user_type', 'status'];
$missingColumns = [];
foreach ($requiredColumns as $col) {
    if (!in_array($col, $columns)) {
        $missingColumns[] = $col;
    }
}

if ($missingColumns) {
    echo "✗ Missing columns: " . implode(", ", $missingColumns) . "\n";
    exit;
}

echo "✓ All required columns present\n\n";

// Create test users
echo "2. Creating test users...\n";

$testUsers = [
    [
        'username' => 'customer',
        'email' => 'customer@test.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'user_type' => 'customer',
        'status' => 'active'
    ],
    [
        'username' => 'vendor',
        'email' => 'vendor@test.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'first_name' => 'Test',
        'last_name' => 'Vendor',
        'user_type' => 'vendor',
        'status' => 'active'
    ],
    [
        'username' => 'admin',
        'email' => 'admin@test.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'first_name' => 'Admin',
        'last_name' => 'User',
        'user_type' => 'admin',
        'status' => 'active'
    ]
];

foreach ($testUsers as $user) {
    // Check if user exists
    $existing = $database->fetchOne(
        "SELECT id FROM users WHERE email = ?",
        [$user['email']]
    );
    
    if ($existing) {
        // Update existing user
        $database->update('users', 
            [
                'password' => $user['password'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ],
            'email = ?',
            [$user['email']]
        );
        echo "✓ Updated: " . $user['email'] . "\n";
    } else {
        // Insert new user
        $database->insert('users', $user);
        echo "✓ Created: " . $user['email'] . "\n";
    }
}

echo "\n✓ Test users setup completed!\n";
echo "\n=== Test Credentials ===\n";
echo "Customer: customer@test.com / password\n";
echo "Vendor:   vendor@test.com / password\n";
echo "Admin:    admin@test.com / password\n\n";
?>
