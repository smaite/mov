<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../includes/google_oauth.php';

// If already logged in, redirect
if (isLoggedIn()) {
    $userType = getUserType();
    if ($userType === 'admin') {
        redirectTo('?page=admin');
    } elseif ($userType === 'vendor') {
        redirectTo('?page=vendor');
    } else {
        redirectTo('');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        global $database;
        
        // Get user by email
        $user = $database->fetchOne(
            "SELECT * FROM users WHERE email = ?", 
            [$email]
        );
        
        if (!$user) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $error = 'Your account is ' . htmlspecialchars($user['status']) . '. Only active accounts can login.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            // Password verified - set session variables
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
            
            // Set remember me cookie if requested
            if ($remember) {
                $cookieValue = base64_encode($user['id'] . ':' . hash('sha256', $user['password']));
                setcookie('remember_token', $cookieValue, time() + (86400 * 30), '/'); // 30 days
            }
            
            // Redirect based on user type
            if ($user['user_type'] === 'admin') {
                redirectTo('?page=admin');
            } elseif ($user['user_type'] === 'vendor') {
                redirectTo('?page=vendor');
            } else {
                $redirect = $_GET['redirect'] ?? '';
                redirectTo($redirect ?: '');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <script src="assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?php echo SITE_URL; ?>" class="text-4xl font-bold text-primary">
                <i class="fas fa-shopping-bag mr-2"></i>Sasto Hub
            </a>
            <p class="text-gray-600 mt-2">Sign in to your account</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-lg shadow-md p-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent pr-10"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye" id="password-toggle"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="text-sm text-primary hover:text-opacity-80">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" 
                        class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-opacity-90 font-semibold transition duration-200">
                    Sign In
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Or continue with</span>
                </div>
            </div>

            <!-- Google Sign-In Button -->
            <?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== 'your-google-client-id-here'): ?>
                <div data-state="closed">
                    <button class="inline-flex items-center justify-center relative shrink-0 can-focus select-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none disabled:drop-shadow-none font-base-bold border-0.5 relative overflow-hidden transition duration-100 backface-hidden h-11 rounded-[0.6rem] px-5 min-w-[6rem] active:scale-[0.985] whitespace-nowrap !text-base w-full gap-2 Button_secondary__x7x_y" type="button" data-testid="login-with-google" onclick="handleGoogleSignIn()">
                        <img alt="Google logo" loading="lazy" width="16" height="16" decoding="async" data-nimg="1" src="<?php echo SITE_URL; ?>assets/images/google.svg" style="color: transparent;">
                        Continue with Google
                    </button>
                </div>
            <?php else: ?>
                <div class="w-full flex items-center justify-center bg-gray-100 border border-gray-300 text-gray-500 font-medium py-3 px-4 rounded-lg cursor-not-allowed">
                    <img alt="Google logo" loading="lazy" width="16" height="16" decoding="async" src="<?php echo SITE_URL; ?>assets/images/google.svg" style="color: transparent;" class="mr-3">
                    Google Sign-In (Not Configured)
                </div>
                <p class="text-xs text-gray-500 text-center mt-2">
                    Google OAuth needs to be configured. See <a href="GOOGLE_OAUTH_SETUP.md" class="text-primary">setup guide</a>.
                </p>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Don't have an account?
                    <a href="?page=register" class="text-primary hover:text-opacity-80 font-semibold">Sign up</a>
                </p>
            </div>

        </div>

        <!-- Quick Login for Demo -->
        <!-- <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-blue-800 mb-2">Demo Accounts:</h3>
            <div class="text-xs text-blue-700 space-y-1">
                <p><strong>Admin:</strong> admin@sastohub.com / password</p>
                <p><strong>Customer:</strong> customer@test.com / password</p>
                <p><strong>Vendor:</strong> vendor@test.com / password</p>
            </div>
        </div> -->
    </div>

    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        }

        function handleGoogleSignIn(response) {
            if (response.credential) {
                // Create a form and submit the credential to the callback
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo SITE_URL; ?>?page=google_callback';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'credential';
                input.value = response.credential;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function initGoogleSignIn() {
            if (typeof google !== 'undefined' && google.accounts) {
                google.accounts.id.initialize({
                    client_id: "<?php echo GOOGLE_CLIENT_ID; ?>",
                    callback: handleGoogleSignIn,
                    auto_select: false,
                    context: "signin"
                });

                // Render the button
                google.accounts.id.renderButton(
                    document.getElementById("google-signin-btn"),
                    {
                        theme: "outline",
                        size: "large",
                        text: "signin_with",
                        shape: "rectangular",
                        logo_alignment: "left",
                        width: document.getElementById("google-signin-btn").offsetWidth
                    }
                );
            }
        }

        // Auto-fill demo credentials
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const demo = urlParams.get('demo');
            
            if (demo === 'admin') {
                document.getElementById('email').value = 'admin@sastohub.com';
                document.getElementById('password').value = 'password';
            } else if (demo === 'customer') {
                document.getElementById('email').value = 'customer@test.com';
                document.getElementById('password').value = 'password';
            } else if (demo === 'vendor') {
                document.getElementById('email').value = 'vendor@test.com';
                document.getElementById('password').value = 'password';
            }
            
            // Initialize Google Sign-In
            initGoogleSignIn();
        });
    </script>
</body>
</html>
