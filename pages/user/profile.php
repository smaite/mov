<?php
$pageTitle = 'My Profile';
$pageDescription = 'Manage your account information';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirectTo('?page=login&redirect=' . urlencode('?page=profile'));
}

global $database;

$success = '';
$errors = [];

// Get user data
$user = $database->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $country = sanitizeInput($_POST['country'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email)) {
            $errors[] = 'Please fill in all required fields.';
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Check if email is already taken by another user
        $existingUser = $database->fetchOne(
            "SELECT id FROM users WHERE email = ? AND id != ?", 
            [$email, $_SESSION['user_id']]
        );
        
        if ($existingUser) {
            $errors[] = 'Email address is already in use.';
        }
        
        // Handle password change
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required to change password.';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                $errors[] = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirmation do not match.';
            }
        }
        
        if (empty($errors)) {
            try {
                // Update user data
                $updateData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'city' => $city,
                    'country' => $country
                ];
                
                // Include password if changing
                if (!empty($newPassword)) {
                    $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                
                $database->update('users', $updateData, 'id = ?', [$_SESSION['user_id']]);
                
                // Update session data
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['email'] = $email;
                
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $user = $database->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
                
            } catch (Exception $e) {
                $errors[] = 'Failed to update profile. Please try again.';
                error_log("Profile update error: " . $e->getMessage());
            }
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <nav class="text-sm breadcrumbs mb-6">
        <ol class="flex items-center space-x-2 text-gray-500">
            <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li class="text-gray-800">My Profile</li>
        </ol>
    </nav>

    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span class="font-semibold text-green-800"><?php echo htmlspecialchars($success); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                    <span class="font-semibold text-red-800">Please fix the following errors:</span>
                </div>
                <ul class="list-disc list-inside text-red-700 text-sm">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 mx-auto mb-4 bg-gray-200 rounded-full flex items-center justify-center">
                            <?php if ($user['profile_image']): ?>
                                <img src="<?php echo SITE_URL . $user['profile_image']; ?>" 
                                     alt="Profile" class="w-24 h-24 rounded-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-gray-400 text-3xl"></i>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                            <?php echo ucfirst($user['user_type']); ?>
                        </span>
                    </div>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Member Since:</span>
                            <span class="font-medium"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-medium capitalize"><?php echo $user['status']; ?></span>
                        </div>
                        <?php if ($user['phone']): ?>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Phone:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($user['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-6">Update Profile Information</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Basic Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium mb-4">Basic Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" required
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" required
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                    <input type="email" id="email" name="email" required
                                           value="<?php echo htmlspecialchars($user['email']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" id="phone" name="phone"
                                           value="<?php echo htmlspecialchars($user['phone']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium mb-4">Address Information</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea id="address" name="address" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                              placeholder="Enter your full address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                        <input type="text" id="city" name="city"
                                               value="<?php echo htmlspecialchars($user['city']); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                        <input type="text" id="country" name="country"
                                               value="<?php echo htmlspecialchars($user['country']); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Change Password -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium mb-4">Change Password</h3>
                            <p class="text-sm text-gray-600 mb-4">Leave blank if you don't want to change your password.</p>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                    <input type="password" id="current_password" name="current_password"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                        <input type="password" id="new_password" name="new_password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex items-center justify-between">
                            <a href="?page=orders" class="text-gray-600 hover:text-primary">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                            </a>
                            <button type="submit" 
                                    class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
