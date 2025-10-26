<?php
$pageTitle = 'Become a Vendor';
$pageDescription = 'Join thousands of successful vendors on Sasto Hub';

if (isLoggedIn()) {
    redirectTo('?page=vendor');
}

global $database;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        // Get user data
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Get vendor data
        $shopName = trim($_POST['shop_name'] ?? '');
        $shopDescription = trim($_POST['shop_description'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $businessLicense = trim($_POST['business_license'] ?? '');
        
        // Handle file upload
        $businessRegDoc = null;
        if (isset($_FILES['business_reg_doc']) && $_FILES['business_reg_doc']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/vendor_documents/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['business_reg_doc']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = 'business_reg_' . uniqid() . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['business_reg_doc']['tmp_name'], $targetPath)) {
                    $businessRegDoc = $targetPath;
                }
            } else {
                $error = 'Invalid file type. Please upload JPG, PNG, PDF, or DOC files only.';
            }
        }
        
        // Validate user data
        if (empty($firstName) || empty($lastName) || !$email || empty($password)) {
            $error = 'Please fill all required fields';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } elseif (empty($shopName) || empty($phone)) {
            $error = 'Please provide shop name and phone number';
        } else {
            // Check if user already exists
            $existingUser = $database->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existingUser) {
                $error = 'An account with this email already exists';
            } else {
                // Create user account
                $userId = $database->insert('users', [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'user_type' => 'vendor',
                    'status' => 'pending', // Pending verification
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($userId) {
                    // Create vendor profile
                    $vendorData = [
                        'user_id' => $userId,
                        'shop_name' => $shopName,
                        'shop_description' => $shopDescription,
                        'phone' => $phone,
                        'address' => $address,
                        'business_license' => $businessLicense,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($businessRegDoc) {
                        $vendorData['business_license_file'] = $businessRegDoc;
                    }
                    
                    $vendorId = $database->insert('vendors', $vendorData);
                    
                    if ($vendorId) {
                        $success = 'Your vendor application has been submitted successfully! We will review your application and notify you within 24-48 hours.';
                    } else {
                        $database->delete('users', 'id = ?', [$userId]);
                        $error = 'Failed to create vendor profile. Please try again.';
                    }
                } else {
                    $error = 'Failed to create user account. Please try again.';
                }
            }
        }
    }
}

// Get categories for business type selection
$categories = $database->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name LIMIT 20");
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-orange-500 to-red-500 rounded-full mb-6">
                    <i class="fas fa-store text-white text-3xl"></i>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Become a Vendor</h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Join thousands of successful vendors on Sasto Hub. Start your online business today with our powerful tools and reach millions of customers.
                </p>
            </div>
            
            <!-- Features -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">Grow Your Sales</h3>
                    <p class="text-gray-600 text-sm">Reach millions of customers and grow your business with our platform</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tools text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">Powerful Tools</h3>
                    <p class="text-gray-600 text-sm">Get access to advanced analytics and management tools</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">24/7 Support</h3>
                    <p class="text-gray-600 text-sm">Our dedicated team is here to help you succeed</p>
                </div>
            </div>
            
            <!-- Registration Form -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 p-6 text-white text-center">
                    <h2 class="text-2xl font-bold mb-2">Start Your Journey</h2>
                    <p class="text-orange-100">Fill in your details to get started</p>
                </div>
                
                <div class="p-8">
                    <?php if ($success): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-2xl mr-3"></i>
                                <div>
                                    <h3 class="font-bold mb-1">Application Submitted!</h3>
                                    <p><?php echo htmlspecialchars($success); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <a href="?page=login" class="inline-flex items-center bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200">
                                <i class="fas fa-sign-in-alt mr-2"></i>Login to Your Account
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-xl mr-3"></i>
                                    <p><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" class="space-y-8">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Personal Information -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-user text-blue-500 mr-2"></i>
                                    Personal Information
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                        <input type="text" name="first_name" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                               placeholder="Enter your first name">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                                        <input type="text" name="last_name" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                               placeholder="Enter your last name">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                        <input type="email" name="email" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                               placeholder="your@email.com">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                        <input type="tel" name="phone" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                               placeholder="+977-98XXXXXXXX">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                                        <input type="password" name="password" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Create a secure password">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                                        <input type="password" name="confirm_password" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Confirm your password">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Business Information -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-store text-orange-500 mr-2"></i>
                                    Business Information
                                </h3>
                                
                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Shop Name *</label>
                                        <input type="text" name="shop_name" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                               value="<?php echo htmlspecialchars($_POST['shop_name'] ?? ''); ?>"
                                               placeholder="Enter your shop name">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Shop Description</label>
                                        <textarea name="shop_description" rows="4"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                                  placeholder="Tell customers about your business..."><?php echo htmlspecialchars($_POST['shop_description'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Address</label>
                                            <input type="text" name="address" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                                                   placeholder="Your business address">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Business License Number</label>
                                            <input type="text" name="business_license" required
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                                   value="<?php echo htmlspecialchars($_POST['business_license'] ?? ''); ?>"
                                                   placeholder="License number (if applicable)">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Business Registration Document
                                            <span class="text-gray-500 text-xs font-normal"></span>
                                        </label>
                                        <div class="relative">
                                            <input type="file" name="business_reg_doc" id="business_reg_doc" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                                   class="hidden" onchange="updateFileName(this)">
                                            <label for="business_reg_doc" class="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-orange-500 transition-colors">
                                                <div class="text-center">
                                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                                    <p class="text-sm text-gray-600">
                                                        <span class="font-medium text-orange-500">Click to upload</span> or drag and drop
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-1">PDF, DOC, PNG, JPG (MAX. 5MB)</p>
                                                    <p id="file-name" class="text-sm text-green-600 font-medium mt-2 hidden"></p>
                                                </div>
                                            </label>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Upload your business registration certificate, trade license, or any official business document
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="flex items-start">
                                    <input type="checkbox" id="terms" required class="mt-1 mr-3">
                                    <label for="terms" class="text-sm text-gray-700 leading-relaxed">
                                        I agree to the <a href="#" class="text-blue-600 hover:text-blue-800">Vendor Terms and Conditions</a> 
                                        and <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>. 
                                        I understand that my application will be reviewed and I will be notified of the approval status within 24-48 hours.
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" 
                                        class="w-full md:w-auto bg-gradient-to-r from-orange-500 to-red-500 text-white px-12 py-4 rounded-lg font-bold text-lg hover:from-orange-600 hover:to-red-600 transition-all duration-200 shadow-lg hover:shadow-xl">
                                    <i class="fas fa-rocket mr-2"></i>Submit Application
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="text-gray-600">
                                    Already have an account? 
                                    <a href="?page=login" class="text-blue-600 hover:text-blue-800 font-semibold">Login here</a>
                                </p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateFileName(input) {
    const fileNameDisplay = document.getElementById('file-name');
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2); // Convert to MB
        
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            input.value = '';
            fileNameDisplay.classList.add('hidden');
            return;
        }
        
        fileNameDisplay.textContent = `✓ ${fileName} (${fileSize} MB)`;
        fileNameDisplay.classList.remove('hidden');
    } else {
        fileNameDisplay.classList.add('hidden');
    }
}
</script>
