<?php
require_once __DIR__ . '/../../config/config.php';

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Delete remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Start new session for flash message
session_start();
$_SESSION['flash_message'] = 'You have been successfully logged out.';
$_SESSION['flash_type'] = 'success';

// Redirect to home page
redirectTo('?page=login');
?>
