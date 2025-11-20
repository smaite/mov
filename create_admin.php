<?php
require_once __DIR__ . '/config/config.php';

global $database;

// Delete admin if it exists with wrong email
$database->delete('users', 'email = ?', ['admin@sastohub.com']);

// Insert admin user
$adminData = [
    'username' => 'admin',
    'email' => 'admin@test.com',
    'password' => password_hash('password', PASSWORD_DEFAULT),
    'first_name' => 'Admin',
    'last_name' => 'User',
    'user_type' => 'admin',
    'status' => 'active'
];

try {
    $id = $database->insert('users', $adminData);
    echo "✓ Admin user created with ID: $id\n";
} catch (Exception $e) {
    echo "Note: " . $e->getMessage() . "\n";
}

// List all users
echo "\n=== All Users ===\n";
$users = $database->fetchAll('SELECT id, email, username, user_type, status FROM users');
foreach ($users as $u) {
    echo $u['user_type'] . ': ' . $u['email'] . ' (status: ' . $u['status'] . ')' . PHP_EOL;
}
?>
