<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$action = $_GET['action'] ?? 'login';

if ($action === 'login') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password required']);
        exit;
    }
    
    global $database;
    
    // Get user by email
    $user = $database->fetchOne(
        "SELECT * FROM users WHERE email = ?", 
        [$email]
    );
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }
    
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Account is ' . htmlspecialchars($user['status'])]);
        exit;
    }
    
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }
    
    // Login successful
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['status'] = $user['status'];
    $_SESSION['profile_image'] = $user['profile_image'];
    
    // Update last login
    $database->update('users', 
        ['last_login' => date('Y-m-d H:i:s')], 
        'id = ?', 
        [$user['id']]
    );
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'user_type' => $user['user_type']
        ]
    ]);
    
} elseif ($action === 'register') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email']);
        exit;
    }
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters']);
        exit;
    }
    
    global $database;
    
    // Check if exists
    $existingUser = $database->fetchOne(
        "SELECT id FROM users WHERE email = ? OR username = ?", 
        [$email, $username]
    );
    
    if ($existingUser) {
        http_response_code(409);
        echo json_encode(['error' => 'Email or username already exists']);
        exit;
    }
    
    // Create user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $userData = [
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'user_type' => 'customer',
        'status' => 'active'
    ];
    
    $userId = $database->insert('users', $userData);
    
    if (!$userId) {
        http_response_code(500);
        echo json_encode(['error' => 'Registration failed']);
        exit;
    }
    
    // Auto login
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['user_type'] = 'customer';
    $_SESSION['status'] = 'active';
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'user_type' => 'customer'
        ]
    ]);
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
?>
