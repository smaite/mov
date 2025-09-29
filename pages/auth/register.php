<?php
require_once __DIR__ . '/../../config/config.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirectTo('');
}

$error = '';
$success = '';
$userType = $_GET['type'] ?? 'customer'; // customer or vendor

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
    $userType = $_POST['user_type'] ?? 'customer';
    $agree = isset($_POST['agree']);
    
    // Vendor specific fields
    $shopName = sanitizeInput($_POST['shop_name'] ?? '');
    $shopDescription = sanitizeInput($_POST['shop_description'] ?? '');
    
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
    } elseif ($userType === 'vendor' && empty($shopName)) {
        $error = 'Shop name is required for vendor registration.';
    } elseif (!$agree) {
        $error = 'You must agree to the terms and conditions.';
    } else {
        global $database;
        
        // Check if email or username already exists
        $existingUser = $database->fetchOne(
            "SELECT id FROM users WHERE email = ? OR username = ?", 
            [$email, $username]
        );
        
        if ($existingUser) {
            $error = 'Email or username already exists.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                // Start transaction
                $database->getConnection()->beginTransaction();
                
                // Insert user
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
                    'user_type' => $userType,
                    'status' => $userType === 'vendor' ? 'pending' : 'active'
                ];
                
                $userId = $database->insert('users', $userData);
                
                if ($userId && $userType === 'vendor') {
                    // Insert vendor details
                    $vendorData = [
                        'user_id' => $userId,
                        'shop_name' => $shopName,
                        'shop_description' => $shopDescription
                    ];
                    
                    $database->insert('vendors', $vendorData);
                }
                
                // Commit transaction
                $database->getConnection()->commit();
                
                if ($userType === 'vendor') {
                    $success = 'Registration successful! Your vendor account is pending approval. You will be notified once approved.';
                } else {
                    $success = 'Registration successful! You can now login with your credentials.';
                    
                    // Auto login for customers
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['first_name'] = $firstName;
                    $_SESSION['last_name'] = $lastName;
                    $_SESSION['user_type'] = $userType;
                    $_SESSION['status'] = 'active'; // Regular customers are active by default
                    $_SESSION['profile_image'] = null;
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=" . SITE_URL);
                }
                
            } catch (Exception $e) {
                // Rollback transaction
                $database->getConnection()->rollback();
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
    <div class="max-w-2xl mx-auto px-4">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?php echo SITE_URL; ?>" class="text-4xl font-bold text-primary">
                <i class="fas fa-shopping-bag mr-2"></i>Sasto Hub
            </a>
            <p class="text-gray-600 mt-2">Create your account</p>
        </div>

        <!-- Registration Type Tabs -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="flex border-b">
                <button onclick="switchTab('customer')" 
                        class="flex-1 py-4 px-6 text-center font-semibold transition duration-200 <?php echo $userType === 'customer' ? 'text-primary border-b-2 border-primary' : 'text-gray-600 hover:text-primary'; ?>" 
                        id="customer-tab">
                    <i class="fas fa-user mr-2"></i>Customer
                </button>
                <button onclick="switchTab('vendor')" 
                        class="flex-1 py-4 px-6 text-center font-semibold transition duration-200 <?php echo $userType === 'vendor' ? 'text-primary border-b-2 border-primary' : 'text-gray-600 hover:text-primary'; ?>" 
                        id="vendor-tab">
                    <i class="fas fa-store mr-2"></i>Vendor
                </button>
            </div>
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
                <input type="hidden" name="user_type" value="<?php echo $userType; ?>" id="user_type_input">
                
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

                <!-- Vendor specific fields -->
                <div id="vendor-fields" class="<?php echo $userType === 'vendor' ? '' : 'hidden'; ?>">
                    <div class="border-t border-gray-200 pt-6 mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Vendor Information</h3>
                        
                        <div class="mb-4">
                            <label for="shop_name" class="block text-gray-700 text-sm font-bold mb-2">Shop Name *</label>
                            <input type="text" id="shop_name" name="shop_name"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Enter your shop name"
                                   value="<?php echo htmlspecialchars($shopName ?? ''); ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="shop_description" class="block text-gray-700 text-sm font-bold mb-2">Shop Description</label>
                            <textarea id="shop_description" name="shop_description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                      placeholder="Describe your shop and products"><?php echo htmlspecialchars($shopDescription ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="agree" required
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">
                            I agree to the <a href="#" class="text-primary hover:text-opacity-80">Terms and Conditions</a> 
                            and <a href="#" class="text-primary hover:text-opacity-80">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-primary text-white py-3 px-4 rounded-md hover:bg-opacity-90 font-semibold transition duration-200">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="?page=login" class="text-primary hover:text-opacity-80 font-semibold">Sign in</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            // Update active tab
            document.getElementById('customer-tab').className = 
                type === 'customer' 
                ? 'flex-1 py-4 px-6 text-center font-semibold transition duration-200 text-primary border-b-2 border-primary'
                : 'flex-1 py-4 px-6 text-center font-semibold transition duration-200 text-gray-600 hover:text-primary';
                
            document.getElementById('vendor-tab').className = 
                type === 'vendor' 
                ? 'flex-1 py-4 px-6 text-center font-semibold transition duration-200 text-primary border-b-2 border-primary'
                : 'flex-1 py-4 px-6 text-center font-semibold transition duration-200 text-gray-600 hover:text-primary';
                
            // Update hidden input
            document.getElementById('user_type_input').value = type;
            
            // Show/hide vendor fields
            const vendorFields = document.getElementById('vendor-fields');
            const shopNameInput = document.getElementById('shop_name');
            
            if (type === 'vendor') {
                vendorFields.classList.remove('hidden');
                shopNameInput.required = true;
            } else {
                vendorFields.classList.add('hidden');
                shopNameInput.required = false;
            }
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('type', type);
            window.history.replaceState({}, '', url);
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
    </script>
</body>
</html>
