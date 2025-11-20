<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Import Google API Client classes
use Google\Client as Google_Client;
use Google\Service\Oauth2 as Google_Service_Oauth2;

/**
 * Initialize Google OAuth Client
 */
function getGoogleClient() {
    $client = new Google_Client([
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => SITE_URL . '?page=google_callback',
        'scope' => [
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile'
        ],
        'access_type' => 'online',
        'prompt' => 'consent'
    ]);
    
    return $client;
}

/**
 * Handle Google OAuth callback
 */
function handleGoogleCallback($code = null, $credential = null) {
    global $database;
    
    try {
        // Handle JWT credential (from Google Sign-In button)
        if ($credential) {
            $client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);
            $payload = $client->verifyIdToken($credential);
            
            if ($payload) {
                $email = $payload['email'];
                $firstName = $payload['given_name'] ?? '';
                $lastName = $payload['family_name'] ?? '';
                $googleId = $payload['sub'];
                $picture = $payload['picture'] ?? '';
                
                return processGoogleUser($email, $firstName, $lastName, $googleId, $picture);
            }
        }
        
        // Handle OAuth code (from traditional OAuth flow)
        if ($code) {
            $client = getGoogleClient();
            $token = $client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['access_token'])) {
                $oauth = new Google_Service_Oauth2($client);
                $userInfo = $oauth->userinfo->get();
                
                $email = $userInfo->getEmail();
                $firstName = $userInfo->getGivenName() ?? '';
                $lastName = $userInfo->getFamilyName() ?? '';
                $googleId = $userInfo->getId();
                $picture = $userInfo->getPicture() ?? '';
                
                return processGoogleUser($email, $firstName, $lastName, $googleId, $picture);
            }
        }
        
        return ['error' => 'Invalid Google response'];
        
    } catch (Exception $e) {
        error_log("Google OAuth Error: " . $e->getMessage());
        return ['error' => 'Authentication failed: ' . $e->getMessage()];
    }
}

/**
 * Process Google user data - login or register
 */
function processGoogleUser($email, $firstName, $lastName, $googleId, $picture) {
    global $database;
    
    try {
        // Check if user exists
        $existingUser = $database->fetchOne(
            "SELECT * FROM users WHERE email = ?", 
            [$email]
        );
        
        if ($existingUser) {
            // User exists - log them in
            if ($existingUser['status'] === 'active') {
                // Update session variables
                $_SESSION['user_id'] = $existingUser['id'];
                $_SESSION['username'] = $existingUser['username'];
                $_SESSION['email'] = $existingUser['email'];
                $_SESSION['first_name'] = $existingUser['first_name'];
                $_SESSION['last_name'] = $existingUser['last_name'];
                $_SESSION['user_type'] = $existingUser['user_type'];
                $_SESSION['status'] = $existingUser['status'];
                $_SESSION['profile_image'] = $existingUser['profile_image'];
                
                // Update last login and profile image if needed
                $database->update('users', 
                    ['last_login' => date('Y-m-d H:i:s'), 'profile_image' => $picture], 
                    'id = ?', 
                    [$existingUser['id']]
                );
                
                return ['success' => 'login', 'user_type' => $existingUser['user_type']];
            } else {
                return ['error' => 'Account is ' . $existingUser['status'] . '. Only active accounts can login.'];
            }
        } else {
            // New user - register them
            $username = generateUsernameFromEmail($email);
            
            $userData = [
                'username' => $username,
                'email' => $email,
                'password' => password_hash(uniqid() . time(), PASSWORD_DEFAULT), // Random password
                'first_name' => $firstName,
                'last_name' => $lastName,
                'user_type' => 'customer',
                'status' => 'active',
                'profile_image' => $picture,
                'google_id' => $googleId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = $database->insert('users', $userData);
            
            if ($userId) {
                // Set session for new user
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['user_type'] = 'customer';
                $_SESSION['status'] = 'active';
                $_SESSION['profile_image'] = $picture;
                
                return ['success' => 'register', 'user_type' => 'customer'];
            } else {
                return ['error' => 'Registration failed. Please try again.'];
            }
        }
    } catch (Exception $e) {
        error_log("Google User Processing Error: " . $e->getMessage());
        return ['error' => 'Authentication failed. Please try again.'];
    }
}

/**
 * Generate username from email
 */
function generateUsernameFromEmail($email) {
    $base = explode('@', $email)[0];
    $username = $base;
    $counter = 1;
    
    global $database;
    
    // Check if username exists, if so, add number
    while ($database->fetchOne("SELECT id FROM users WHERE username = ?", [$username])) {
        $username = $base . $counter;
        $counter++;
    }
    
    return $username;
}

/**
 * Get Google Sign-In configuration for frontend
 */
function getGoogleSignInConfig() {
    if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === 'your-google-client-id-here') {
        return null;
    }
    
    return [
        'client_id' => GOOGLE_CLIENT_ID,
        'callback' => 'handleGoogleSignIn'
    ];
}