<?php
/**
 * Database Update Script for Google OAuth
 * This script adds the necessary columns for Google OAuth integration
 */

require_once __DIR__ . '/config/database.php';

echo "=== Google OAuth Database Update Script ===\n\n";

try {
    // Check if google_id column exists
    $checkColumn = $database->query("SHOW COLUMNS FROM users LIKE 'google_id'");
    
    if (empty($checkColumn)) {
        echo "Adding google_id column to users table...\n";
        $database->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(50) DEFAULT NULL AFTER profile_image");
        $database->query("ALTER TABLE users ADD INDEX idx_google_id (google_id)");
        echo "✓ google_id column added successfully\n\n";
    } else {
        echo "✓ google_id column already exists\n\n";
    }
    
    // Check if last_login column exists
    $checkLastLogin = $database->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    
    if (empty($checkLastLogin)) {
        echo "Adding last_login column to users table...\n";
        $database->query("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
        echo "✓ last_login column added successfully\n\n";
    } else {
        echo "✓ last_login column already exists\n\n";
    }
    
    echo "=== Database update completed successfully! ===\n";
    echo "\nYou can now use Google OAuth for login and registration.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease run the SQL manually from database/add_google_id_column.sql\n";
}
?>
