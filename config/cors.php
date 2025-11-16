<?php
/**
 * CORS (Cross-Origin Resource Sharing) Configuration
 * This file handles CORS headers to allow Flutter app and other clients to access the API
 */

// Allow requests from Flutter app (localhost and production)
$allowedOrigins = [
    'http://localhost:8080',
    'http://localhost:3000',
    'http://127.0.0.1:8080',
    'http://127.0.0.1:3000',
    'https://sastohub.com',
    'https://www.sastohub.com'
];

// Get the origin of the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Check if the origin is allowed
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Allow all origins in development mode (remove in production)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        header("Access-Control-Allow-Origin: *");
    }
}

// Allow credentials (cookies, authorization headers, etc.)
header("Access-Control-Allow-Credentials: true");

// Allow specific HTTP methods
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, Accept");

// Cache preflight request for 1 hour
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>