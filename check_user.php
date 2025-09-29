<?php
require_once __DIR__ . '/config/config.php';

if (!isset($_GET['id'])) {
    die('Add ?id=10 to URL');
}

$userId = intval($_GET['id']);
global $database;

echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;width:100%;margin:10px 0;} th,td{border:1px solid #ddd;padding:12px;text-align:left;} th{background:#4CAF50;color:white;}</style>";

echo "<h2>🔍 User ID: {$userId} Debug Information</h2>";

// Check user
$user = $database->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    echo "<p style='color:red;font-size:20px;'>❌ USER NOT FOUND with ID: {$userId}</p>";
    echo "<p>This user doesn't exist in the database. The vendor entry might be referencing a non-existent user.</p>";
} else {
    echo "<h3>✅ User Found</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($user as $key => $value) {
        $displayValue = $value === null ? '<em>NULL</em>' : htmlspecialchars($value);
        if ($key === 'password') $displayValue = substr($value, 0, 30) . '...';
        echo "<tr><td><strong>{$key}</strong></td><td>{$displayValue}</td></tr>";
    }
    echo "</table>";
    
    // Check if vendor exists
    $vendor = $database->fetchOne("SELECT * FROM vendors WHERE user_id = ?", [$userId]);
    
    if ($vendor) {
        echo "<h3>🏪 Vendor Information</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($vendor as $key => $value) {
            $displayValue = $value === null ? '<em>NULL</em>' : htmlspecialchars($value);
            echo "<tr><td><strong>{$key}</strong></td><td>{$displayValue}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ No vendor record found for this user</p>";
    }
    
    // Test update query
    echo "<h3>🧪 Test Update Query</h3>";
    $testUpdate = "UPDATE users SET status = 'active' WHERE id = {$userId} AND user_type = 'vendor'";
    echo "<code style='background:#f4f4f4;padding:10px;display:block;margin:10px 0;'>{$testUpdate}</code>";
    
    echo "<h3>📋 Issues Found:</h3>";
    $issues = [];
    if ($user['user_type'] !== 'vendor') {
        $issues[] = "❌ User type is '{$user['user_type']}' but should be 'vendor'";
    }
    if ($user['status'] === 'active') {
        $issues[] = "⚠️ User is already 'active' - no rows to update";
    }
    if (!$vendor) {
        $issues[] = "❌ No vendor record exists for this user";
    }
    
    if (empty($issues)) {
        echo "<p style='color:green;'>✅ No issues found! Update should work.</p>";
    } else {
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li style='color:red;font-size:16px;margin:5px 0;'>{$issue}</li>";
        }
        echo "</ul>";
    }
}

echo "<p><a href='?page=admin&section=vendors'>← Back to Admin Panel</a></p>";
?>
