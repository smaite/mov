<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../includes/google_oauth.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirectTo('');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $country = sanitizeInput($_POST['country'] ?? '');
    $agree = isset($_POST['agree']);
    
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($firstName) || empty($lastName) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$agree) {
        $error = 'You must agree to terms and conditions.';
    } else {
        global $database;
        
        // Check if email or username already exists
        $existingUser = $database->fetchOne(
            "SELECT id FROM users WHERE email = ? OR username = ?", 
            [$email, $username]
        );
        
        if ($existingUser) {
            $error = 'Email or username already exists. Please try different ones.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                // Insert user (customer only)
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'address' => $address,
                    'city' => $city,
                    'country' => $country,
                    'user_type' => 'customer',
                    'status' => 'active'
                ];
                
                $userId = $database->insert('users', $userData);
                
                if ($userId) {
                    $success = 'Registration successful! You can now login with your credentials.';
                    
                    // Auto login for customers
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['first_name'] = $firstName;
                    $_SESSION['last_name'] = $lastName;
                    $_SESSION['user_type'] = 'customer';
                    $_SESSION['status'] = 'active';
                    $_SESSION['profile_image'] = null;
                    
                    // Redirect after 2 seconds
                    echo "<script>setTimeout(function() { window.location.href = '" . SITE_URL . "'; }, 2000);</script>";
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                
            } catch (Exception $e) {
                $error = 'Registration failed. Please try again.';
                error_log("Registration error: " . $e->getMessage());
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
    <title>Register - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-md mx-auto px-4">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?php echo SITE_URL; ?>" class="text-4xl font-bold text-primary">
                <i class="fas fa-shopping-bag mr-2"></i>Sasto Hub
            </a>
            <p class="text-gray-600 mt-2">Create your customer account</p>
        </div>

        <!-- Registration Form -->
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

            <form method="POST" action="" id="registration-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter your first name"
                               value="<?php echo htmlspecialchars($firstName ?? ''); ?>">
                    </div>
                    <div>
                        <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter your last name"
                               value="<?php echo htmlspecialchars($lastName ?? ''); ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address *</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter your email"
                               value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    <div>
                        <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username *</label>
                        <input type="text" id="username" name="username" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Choose a username"
                               value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password *</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter your password">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Confirm your password">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter your phone number"
                               value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>
                    <div>
                        <label for="city" class="block text-gray-700 text-sm font-bold mb-2">City</label>
                        <input type="text" id="city" name="city"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter your city"
                               value="<?php echo htmlspecialchars($city ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="address" class="block text-gray-700 text-sm font-bold mb-2">Address</label>
                    <textarea id="address" name="address" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                              placeholder="Enter your full address"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="agree" required
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">
                            I agree to <a href="#" class="text-primary hover:text-opacity-80">Terms and Conditions</a> 
                            and <a href="#" class="text-primary hover:text-opacity-80">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-primary text-white py-3 px-4 rounded-md hover:bg-opacity-90 font-semibold transition duration-200">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Or register with</span>
                </div>
            </div>

            <!-- Google Sign-Up Button -->
            <?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== 'your-google-client-id-here'): ?>
                <div data-state="closed">
                    <button class="inline-flex items-center justify-center relative shrink-0 can-focus select-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none disabled:drop-shadow-none font-base-bold border-0.5 relative overflow-hidden transition duration-100 backface-hidden h-11 rounded-[0.6rem] px-5 min-w-[6rem] active:scale-[0.985] whitespace-nowrap !text-base w-full gap-2 Button_secondary__x7x_y" type="button" data-testid="signup-with-google" onclick="handleGoogleSignUp()">
                    <img alt="Google logo" loading="lazy" width="16" height="16" decoding="async" data-nimg="1" src="<?php echo SITE_URL; ?>assets/images/google.svg" style="color: transparent;" class="mr-3">
                    Sign up with Google
                </div>
            <?php else: ?>
                <div class="w-full flex items-center justify-center bg-gray-100 border border-gray-300 text-gray-500 font-medium py-3 px-4 rounded-lg cursor-not-allowed">
                    <img alt="Google logo" loading="lazy" width="16" height="16" decoding="async" src="<?php echo SITE_URL; ?>assets/images/google.svg" style="color: transparent;" class="mr-3">
                    Google Sign-Up (Not Configured)
                </div>
                <p class="text-xs text-gray-500 text-center mt-2">
                    Google OAuth needs to be configured. See <a href="GOOGLE_OAUTH_SETUP.md" class="text-primary">setup guide</a>.
                </p>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Already have an account?
                    <a href="?page=login" class="text-primary hover:text-opacity-80 font-semibold">Sign in</a>
                </p>
                <p class="text-gray-600 mt-2">
                    Want to become a vendor?
                    <a href="?page=vendor-register" class="text-primary hover:text-opacity-80 font-semibold">Apply here</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        function handleGoogleSignUp(response) {
            if (response.credential) {
                // Create a form and submit the credential to the callback
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo SITE_URL; ?>?page=google_callback&type=customer';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'credential';
                input.value = response.credential;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function initGoogleSignUp() {
            if (typeof google !== 'undefined' && google.accounts) {
                google.accounts.id.initialize({
                    client_id: "<?php echo GOOGLE_CLIENT_ID; ?>",
                    callback: handleGoogleSignUp,
                    auto_select: false,
                    context: "signup"
                });

                // Render button
                google.accounts.id.renderButton(
                    document.getElementById("google-signup-btn"),
                    {
                        theme: "outline",
                        size: "large",
                        text: "signup_with",
                        shape: "rectangular",
                        logo_alignment: "left",
                        width: document.getElementById("google-signup-btn").offsetWidth
                    }
                );
            }
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            // You can add visual feedback here
        });

        function getPasswordStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            return score;
        }

        // Form validation
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                e.preventDefault();
                alert('Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long!');
                return false;
            }
        });

        // Initialize Google Sign-Up on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== 'your-google-client-id-here'): ?>
                initGoogleSignUp();
            <?php endif; ?>
        });
    </script>
</body>
</html>
