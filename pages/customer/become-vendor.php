<?php
$pageTitle = 'Become a Vendor';
$pageDescription = 'Upgrade your account to start selling';

if (!isLoggedIn()) {
    redirectTo('?page=login&redirect=become-vendor');
}

if (isVendor()) {
    redirectTo('?page=vendor');
}

global $database;

$success = '';
$error = '';

// Check if user already has a pending vendor application
$existingApplication = $database->fetchOne("SELECT * FROM vendors WHERE user_id = ?", [$_SESSION['user_id']]);
if ($existingApplication) {
    redirectTo('?page=customer&section=vendor-status');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $shopName = trim($_POST['shop_name'] ?? '');
        $shopDescription = trim($_POST['shop_description'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $businessLicense = trim($_POST['business_license'] ?? '');
        
        if (empty($shopName) || empty($phone)) {
            $error = 'Please provide shop name and phone number';
        } else {
            // Handle file uploads
            $uploadDir = '/uploads/vendor-documents/' . $_SESSION['user_id'] . '/';
            $uploadPath = ROOT_PATH . $uploadDir;
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $businessLicenseFile = '';
            $citizenshipFile = '';
            $panCardFile = '';
            $otherDocuments = [];
            
            // Upload business license
            if (isset($_FILES['business_license_file']) && $_FILES['business_license_file']['size'] > 0) {
                $fileName = 'business_license_' . time() . '_' . $_FILES['business_license_file']['name'];
                $filePath = $uploadPath . $fileName;
                if (move_uploaded_file($_FILES['business_license_file']['tmp_name'], $filePath)) {
                    $businessLicenseFile = $uploadDir . $fileName;
                }
            }
            
            // Upload citizenship
            if (isset($_FILES['citizenship_file']) && $_FILES['citizenship_file']['size'] > 0) {
                $fileName = 'citizenship_' . time() . '_' . $_FILES['citizenship_file']['name'];
                $filePath = $uploadPath . $fileName;
                if (move_uploaded_file($_FILES['citizenship_file']['tmp_name'], $filePath)) {
                    $citizenshipFile = $uploadDir . $fileName;
                }
            }
            
            // Upload PAN card
            if (isset($_FILES['pan_card_file']) && $_FILES['pan_card_file']['size'] > 0) {
                $fileName = 'pan_card_' . time() . '_' . $_FILES['pan_card_file']['name'];
                $filePath = $uploadPath . $fileName;
                if (move_uploaded_file($_FILES['pan_card_file']['tmp_name'], $filePath)) {
                    $panCardFile = $uploadDir . $fileName;
                }
            }
            
            // Handle multiple other documents
            if (isset($_FILES['other_documents']) && is_array($_FILES['other_documents']['name'])) {
                for ($i = 0; $i < count($_FILES['other_documents']['name']); $i++) {
                    if ($_FILES['other_documents']['size'][$i] > 0) {
                        $fileName = 'other_' . time() . '_' . $i . '_' . $_FILES['other_documents']['name'][$i];
                        $filePath = $uploadPath . $fileName;
                        if (move_uploaded_file($_FILES['other_documents']['tmp_name'][$i], $filePath)) {
                            $otherDocuments[] = $uploadDir . $fileName;
                        }
                    }
                }
            }
            
            // Create vendor application
            $vendorId = $database->insert('vendors', [
                'user_id' => $_SESSION['user_id'],
                'shop_name' => $shopName,
                'shop_description' => $shopDescription,
                'phone' => $phone,
                'address' => $address,
                'business_license' => $businessLicense,
                'business_license_file' => $businessLicenseFile,
                'citizenship_file' => $citizenshipFile,
                'pan_card_file' => $panCardFile,
                'other_documents' => json_encode($otherDocuments),
                'application_date' => date('Y-m-d H:i:s')
            ]);
            
            if ($vendorId) {
                // Update user type to vendor but keep status as pending
                $database->update('users', [
                    'user_type' => 'vendor',
                    'status' => 'pending'
                ], 'id = ?', [$_SESSION['user_id']]);
                
                $success = 'Your vendor application has been submitted successfully! We will review your application and notify you within 2-3 business days.';
            } else {
                $error = 'Failed to submit application. Please try again.';
            }
        }
    }
}
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
                    Upgrade your account to start selling on Sasto Hub. Join thousands of successful vendors today!
                </p>
            </div>
            
            <!-- Current User Info -->
            <div class="bg-blue-50 rounded-xl p-6 mb-8">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white text-xl mr-4">
                        <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">
                            <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                        <p class="text-sm text-blue-600">Current Account: Customer → Upgrading to Vendor</p>
                    </div>
                </div>
            </div>
            
            <!-- Benefits -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-teal-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">Reach Millions</h3>
                    <p class="text-gray-600 text-sm">Access to our growing customer base across Nepal</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">Grow Your Business</h3>
                    <p class="text-gray-600 text-sm">Powerful tools to manage and scale your operations</p>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-handshake text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">Trusted Platform</h3>
                    <p class="text-gray-600 text-sm">Secure payments and reliable support system</p>
                </div>
            </div>
            
            <!-- Application Form -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 p-6 text-white text-center">
                    <h2 class="text-2xl font-bold mb-2">Vendor Application</h2>
                    <p class="text-orange-100">Provide your business details and required documents</p>
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
                            <a href="?page=customer&section=vendor-status" class="inline-flex items-center bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200 mr-4">
                                <i class="fas fa-eye mr-2"></i>Check Status
                            </a>
                            <a href="<?php echo SITE_URL; ?>" class="inline-flex items-center border border-gray-300 text-gray-700 px-8 py-3 rounded-lg font-semibold hover:bg-gray-50 transition duration-200">
                                <i class="fas fa-home mr-2"></i>Continue Shopping
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
                            
                            <!-- Business Information -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-store text-orange-500 mr-2"></i>
                                    Business Information
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Shop Name *</label>
                                        <input type="text" name="shop_name" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                               placeholder="Enter your shop/business name">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Business Phone *</label>
                                        <input type="tel" name="phone" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                               placeholder="+977-98XXXXXXXX">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Business License Number</label>
                                        <input type="text" name="business_license"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                               placeholder="License number (if available)">
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Business Address</label>
                                        <textarea name="address" rows="3"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                                  placeholder="Your business address"></textarea>
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Shop Description</label>
                                        <textarea name="shop_description" rows="4"
                                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                                  placeholder="Describe your business, products, and what makes you unique..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Required Documents -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-file-upload text-blue-500 mr-2"></i>
                                    Required Documents
                                </h3>
                                
                                <div class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Business License Document -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Business License Document
                                                <span class="text-gray-500">(Optional)</span>
                                            </label>
                                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-gray-400 transition-colors">
                                                <input type="file" name="business_license_file" accept=".pdf,.jpg,.jpeg,.png" 
                                                       class="hidden" id="business_license_file">
                                                <label for="business_license_file" class="cursor-pointer">
                                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                                    <p class="text-sm text-gray-600">Click to upload or drag and drop</p>
                                                    <p class="text-xs text-gray-500">PDF, JPG, PNG up to 5MB</p>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Citizenship Document -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Citizenship Certificate *
                                                <span class="text-red-500">Required</span>
                                            </label>
                                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-gray-400 transition-colors">
                                                <input type="file" name="citizenship_file" accept=".pdf,.jpg,.jpeg,.png" required
                                                       class="hidden" id="citizenship_file">
                                                <label for="citizenship_file" class="cursor-pointer">
                                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                                    <p class="text-sm text-gray-600">Click to upload or drag and drop</p>
                                                    <p class="text-xs text-gray-500">PDF, JPG, PNG up to 5MB</p>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- PAN Card -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                PAN Card
                                                <span class="text-gray-500">(Recommended)</span>
                                            </label>
                                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-gray-400 transition-colors">
                                                <input type="file" name="pan_card_file" accept=".pdf,.jpg,.jpeg,.png"
                                                       class="hidden" id="pan_card_file">
                                                <label for="pan_card_file" class="cursor-pointer">
                                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                                    <p class="text-sm text-gray-600">Click to upload or drag and drop</p>
                                                    <p class="text-xs text-gray-500">PDF, JPG, PNG up to 5MB</p>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Other Documents -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Additional Documents
                                                <span class="text-gray-500">(Optional)</span>
                                            </label>
                                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-gray-400 transition-colors">
                                                <input type="file" name="other_documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple
                                                       class="hidden" id="other_documents">
                                                <label for="other_documents" class="cursor-pointer">
                                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                                    <p class="text-sm text-gray-600">Click to upload multiple files</p>
                                                    <p class="text-xs text-gray-500">Tax certificates, bank statements, etc.</p>
                                                </label>
                                            </div>
                                        </div>
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
                                        I understand that my application will be reviewed by the admin team and I will be notified of the approval status within 2-3 business days. 
                                        I can continue using my account for shopping while my vendor application is being processed.
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" 
                                        class="w-full md:w-auto bg-gradient-to-r from-orange-500 to-red-500 text-white px-12 py-4 rounded-lg font-bold text-lg hover:from-orange-600 hover:to-red-600 transition-all duration-200 shadow-lg hover:shadow-xl">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Vendor Application
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// File upload preview
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const label = this.parentNode.querySelector('label');
            const files = this.files;
            
            if (files.length > 0) {
                if (files.length === 1) {
                    label.querySelector('p').textContent = files[0].name;
                } else {
                    label.querySelector('p').textContent = `${files.length} files selected`;
                }
                label.classList.add('border-green-400', 'bg-green-50');
            }
        });
    });
});
</script>
