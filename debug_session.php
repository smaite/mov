<?php
session_start();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .session-info { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .key { color: #e91e63; font-weight: bold; }
        .value { color: #2196f3; }
        h1 { color: #333; }
        pre { background: #263238; color: #aed581; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="session-info">
        <h1>🔍 Session Debug Information</h1>
        
        <h2>Current Session Variables:</h2>
        <pre><?php print_r($_SESSION); ?></pre>
        
        <h2>Authentication Status:</h2>
        <p><span class="key">Logged In:</span> <span class="value"><?php echo isset($_SESSION['user_id']) ? 'YES' : 'NO'; ?></span></p>
        <p><span class="key">User ID:</span> <span class="value"><?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></span></p>
        <p><span class="key">User Type:</span> <span class="value"><?php echo $_SESSION['user_type'] ?? 'NOT SET'; ?></span></p>
        <p><span class="key">Username:</span> <span class="value"><?php echo $_SESSION['username'] ?? 'NOT SET'; ?></span></p>
        <p><span class="key">Email:</span> <span class="value"><?php echo $_SESSION['email'] ?? 'NOT SET'; ?></span></p>
        <p><span class="key">Status:</span> <span class="value"><?php echo $_SESSION['status'] ?? 'NOT SET'; ?></span></p>
        
        <h2>Session Info:</h2>
        <p><span class="key">Session ID:</span> <span class="value"><?php echo session_id(); ?></span></p>
        <p><span class="key">Session Name:</span> <span class="value"><?php echo session_name(); ?></span></p>
        
        <h2>Actions:</h2>
        <p>
            <a href="?page=vendor" style="color: #ff6b35; text-decoration: underline;">Go to Vendor Dashboard</a> | 
            <a href="?page=login" style="color: #ff6b35; text-decoration: underline;">Go to Login</a> | 
            <a href="<?php echo SITE_URL; ?>" style="color: #ff6b35; text-decoration: underline;">Go to Home</a>
        </p>
        
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'vendor'): ?>
            <div style="margin-top: 20px; padding: 15px; background: #4caf50; color: white; border-radius: 4px;">
                ✓ You ARE recognized as a VENDOR in the session!
            </div>
        <?php else: ?>
            <div style="margin-top: 20px; padding: 15px; background: #ff5722; color: white; border-radius: 4px;">
                ✗ You are NOT recognized as a vendor. Current type: <?php echo $_SESSION['user_type'] ?? 'NOT SET'; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>