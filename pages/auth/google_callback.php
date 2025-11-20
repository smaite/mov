<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../includes/google_oauth.php';

// Get the code or credential from Google
$code = $_GET['code'] ?? null;
$credential = $_POST['credential'] ?? $_GET['credential'] ?? null; // JWT credential from Google Sign-In button
$type = $_GET['type'] ?? 'customer';

// Handle the callback
$result = handleGoogleCallback($code, $credential);

if (isset($result['error'])) {
    // Error occurred
    $error = $result['error'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Authentication Error - <?php echo SITE_NAME; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#ff6b35',
                            secondary: '#004643',
                            accent: '#f9bc60'
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Authentication Failed</h2>
                <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($error); ?></p>
                <div class="space-y-3">
                    <a href="?page=login" class="block w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-opacity-90 font-semibold transition duration-200">
                        Try Again
                    </a>
                    <a href="<?php echo SITE_URL; ?>" class="block w-full bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 font-semibold transition duration-200">
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Success - redirect based on result
if ($result['success'] === 'login') {
    $userType = $result['user_type'];
    
    if ($userType === 'admin') {
        redirectTo('?page=admin');
    } elseif ($userType === 'vendor') {
        redirectTo('?page=vendor');
    } else {
        redirectTo('');
    }
} elseif ($result['success'] === 'register') {
    // New user registration successful
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome - <?php echo SITE_NAME; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#ff6b35',
                            secondary: '#004643',
                            accent: '#f9bc60'
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-4xl text-green-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Welcome!</h2>
                <p class="text-gray-600 mb-6">Your account has been created successfully using Google Sign-In.</p>
                <div class="space-y-3">
                    <a href="<?php echo SITE_URL; ?>" class="block w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-opacity-90 font-semibold transition duration-200">
                        Continue to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
