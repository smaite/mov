<?php
require_once __DIR__ . '/../config/config.php';

// Migration script to add missing columns
$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) UNIQUE NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL"
];

foreach ($migrations as $sql) {
    try {
        $database->query($sql);
        echo "✓ Executed: " . $sql . "\n";
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
}

echo "\n✓ Database migration completed successfully!\n";
?>
