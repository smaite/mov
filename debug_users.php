<?php
require_once __DIR__ . '/config/config.php';

// Simple debug page to check what users exist in database
if (!isset($_GET['debug']) || $_GET['debug'] !== 'yes') {
    die('Add ?debug=yes to URL to view debug info');
}

global $database;

echo "<h2>Database Users Debug</h2>";
echo "<style>body{font-family:Arial;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>";

try {
    // Check all users in database
    $users = $database->fetchAll("SELECT id, username, email, first_name, last_name, user_type, status, created_at FROM users ORDER BY id");
    
    echo "<h3>All Users in Database (" . count($users) . " total):</h3>";
    
    if (empty($users)) {
        echo "<p style='color:red;'>❌ NO USERS FOUND! Demo data might not be imported.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Type</th><th>Status</th><th>Created</th></tr>";
        foreach ($users as $user) {
            $statusColor = $user['status'] === 'active' ? 'green' : ($user['status'] === 'pending' ? 'orange' : 'red');
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['user_type']}</td>";
            echo "<td style='color:{$statusColor};font-weight:bold;'>{$user['status']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test password verification for demo accounts
    echo "<h3>Password Hash Test:</h3>";
    $testEmails = ['admin@sastohub.com', 'customer@test.com', 'vendor@test.com', 'vendor2@test.com', 'jane@test.com'];
    
    foreach ($testEmails as $email) {
        $user = $database->fetchOne("SELECT email, password, status FROM users WHERE email = ?", [$email]);
        if ($user) {
            $hashTest = password_verify('password', $user['password']);
            $hashColor = $hashTest ? 'green' : 'red';
            $hashStatus = $hashTest ? '✅ VALID' : '❌ INVALID';
            echo "<p><strong>{$email}</strong>: Status = <span style='color:" . ($user['status'] === 'active' ? 'green' : 'red') . "'>{$user['status']}</span>, Password = <span style='color:{$hashColor}'>{$hashStatus}</span></p>";
        } else {
            echo "<p><strong>{$email}</strong>: <span style='color:red'>❌ NOT FOUND</span></p>";
        }
    }

    // Check vendors
    echo "<h3>Vendors:</h3>";
    $vendors = $database->fetchAll("SELECT v.id, u.email, v.shop_name, v.is_verified, u.status FROM vendors v JOIN users u ON v.user_id = u.id");
    if (empty($vendors)) {
        echo "<p style='color:red;'>❌ NO VENDORS FOUND</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Email</th><th>Shop Name</th><th>User Status</th><th>Verified</th></tr>";
        foreach ($vendors as $vendor) {
            echo "<tr>";
            echo "<td>{$vendor['id']}</td>";
            echo "<td>{$vendor['email']}</td>";
            echo "<td>{$vendor['shop_name']}</td>";
            echo "<td>{$vendor['status']}</td>";
            echo "<td>" . ($vendor['is_verified'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If no users found: Re-import demo_data.sql</li>";  
echo "<li>If users found but status not 'active': Check why status is wrong</li>";
echo "<li>If password hash invalid: Password hash in database is corrupted</li>";
echo "<li>Try login again with detailed error messages</li>";
echo "</ol>";

echo "<p><a href='?page=login'>← Back to Login</a></p>";
?>
