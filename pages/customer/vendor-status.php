<?php
$pageTitle = 'Vendor Application Status';
$pageDescription = 'Check your vendor application status';

if (!isLoggedIn()) {
    redirectTo('?page=login');
}

global $database;

// Get vendor application
$vendorApplication = $database->fetchOne("SELECT * FROM vendors WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$vendorApplication) {
    redirectTo('?page=become-vendor');
}

$userStatus = $_SESSION['status'] ?? 'pending';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Vendor Application Status</h1>
                <p class="text-gray-600">Track the progress of your vendor application</p>
            </div>
            
            <!-- Status Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
                <!-- Header with Status -->
                <div class="<?php echo $userStatus === 'active' ? 'bg-gradient-to-r from-green-500 to-teal-500' : ($userStatus === 'rejected' ? 'bg-gradient-to-r from-red-500 to-pink-500' : 'bg-gradient-to-r from-yellow-400 to-orange-500'); ?> p-6 text-white text-center">
                    <div class="w-20 h-20 mx-auto mb-4 bg-white/20 rounded-full flex items-center justify-center">
                        <?php if ($userStatus === 'active'): ?>
                            <i class="fas fa-check text-white text-3xl"></i>
                        <?php elseif ($userStatus === 'rejected'): ?>
                            <i class="fas fa-times text-white text-3xl"></i>
                        <?php else: ?>
                            <i class="fas fa-clock text-white text-3xl"></i>
                        <?php endif; ?>
                    </div>
                    <h2 class="text-2xl font-bold mb-2">
                        <?php if ($userStatus === 'active'): ?>
                            Application Approved!
                        <?php elseif ($userStatus === 'rejected'): ?>
                            Application Rejected
                        <?php else: ?>
                            Application Under Review
                        <?php endif; ?>
                    </h2>
                    <p class="text-white/90">
                        <?php if ($userStatus === 'active'): ?>
                            Welcome to the Sasto Hub vendor family!
                        <?php elseif ($userStatus === 'rejected'): ?>
                            Your application needs revision
                        <?php else: ?>
                            We're reviewing your application
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <!-- Application Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3">Application Details</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shop Name:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($vendorApplication['shop_name']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($vendorApplication['phone'] ?: 'Not provided'); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Business License:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($vendorApplication['business_license'] ?: 'Not provided'); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Applied Date:</span>
                                    <span class="font-medium"><?php echo date('M j, Y', strtotime($vendorApplication['application_date'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3">Submitted Documents</h3>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <i class="fas fa-id-card text-gray-400 mr-2"></i>
                                    <span class="text-gray-600">Citizenship:</span>
                                    <span class="ml-2 <?php echo $vendorApplication['citizenship_file'] ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $vendorApplication['citizenship_file'] ? 'Submitted' : 'Missing'; ?>
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-certificate text-gray-400 mr-2"></i>
                                    <span class="text-gray-600">Business License:</span>
                                    <span class="ml-2 <?php echo $vendorApplication['business_license_file'] ? 'text-green-600' : 'text-gray-500'; ?>">
                                        <?php echo $vendorApplication['business_license_file'] ? 'Submitted' : 'Optional'; ?>
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-credit-card text-gray-400 mr-2"></i>
                                    <span class="text-gray-600">PAN Card:</span>
                                    <span class="ml-2 <?php echo $vendorApplication['pan_card_file'] ? 'text-green-600' : 'text-gray-500'; ?>">
                                        <?php echo $vendorApplication['pan_card_file'] ? 'Submitted' : 'Optional'; ?>
                                    </span>
                                </div>
                                <?php 
                                $otherDocs = json_decode($vendorApplication['other_documents'] ?: '[]', true);
                                if (!empty($otherDocs)):
                                ?>
                                <div class="flex items-center">
                                    <i class="fas fa-file-alt text-gray-400 mr-2"></i>
                                    <span class="text-gray-600">Additional Docs:</span>
                                    <span class="ml-2 text-green-600"><?php echo count($otherDocs); ?> files</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Timeline -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Application Progress</h3>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">Application Submitted</p>
                                    <p class="text-xs text-gray-500"><?php echo date('M j, Y \a\t g:i A', strtotime($vendorApplication['application_date'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="w-8 h-8 <?php echo $userStatus !== 'pending' ? 'bg-green-500' : 'bg-yellow-500'; ?> rounded-full flex items-center justify-center text-white text-sm">
                                    <?php if ($userStatus !== 'pending'): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <i class="fas fa-clock"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">
                                        <?php if ($userStatus === 'active'): ?>
                                            Application Approved
                                        <?php elseif ($userStatus === 'rejected'): ?>
                                            Application Reviewed
                                        <?php else: ?>
                                            Under Review
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php if ($userStatus !== 'pending'): ?>
                                            Review completed
                                        <?php else: ?>
                                            Our team is reviewing your documents
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if ($userStatus === 'active'): ?>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800">Account Activated</p>
                                    <p class="text-xs text-gray-500">You can now start selling</p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-gray-500 text-sm">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-500">Account Activation</p>
                                    <p class="text-xs text-gray-400">Pending approval</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($vendorApplication['shop_description']): ?>
                    <!-- Shop Description -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Shop Description</h3>
                        <p class="text-gray-600 leading-relaxed"><?php echo htmlspecialchars($vendorApplication['shop_description']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <?php if ($userStatus === 'active'): ?>
                            <a href="?page=vendor" class="flex-1 bg-gradient-to-r from-green-500 to-teal-500 text-white text-center py-3 px-6 rounded-lg font-semibold hover:from-green-600 hover:to-teal-600 transition duration-200">
                                <i class="fas fa-store mr-2"></i>Go to Vendor Dashboard
                            </a>
                            <a href="<?php echo SITE_URL; ?>" class="flex-1 border border-gray-300 text-gray-700 text-center py-3 px-6 rounded-lg font-semibold hover:bg-gray-50 transition duration-200">
                                <i class="fas fa-home mr-2"></i>Continue Shopping
                            </a>
                        <?php elseif ($userStatus === 'rejected'): ?>
                            <a href="?page=become-vendor" class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-center py-3 px-6 rounded-lg font-semibold hover:from-blue-600 hover:to-indigo-700 transition duration-200">
                                <i class="fas fa-edit mr-2"></i>Resubmit Application
                            </a>
                            <a href="<?php echo SITE_URL; ?>" class="flex-1 border border-gray-300 text-gray-700 text-center py-3 px-6 rounded-lg font-semibold hover:bg-gray-50 transition duration-200">
                                <i class="fas fa-home mr-2"></i>Continue Shopping
                            </a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>" class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-center py-3 px-6 rounded-lg font-semibold hover:from-blue-600 hover:to-indigo-700 transition duration-200">
                                <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
                            </a>
                            <div class="flex-1 bg-gray-100 text-gray-500 text-center py-3 px-6 rounded-lg font-semibold cursor-not-allowed">
                                <i class="fas fa-hourglass-half mr-2"></i>Waiting for Approval
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Contact Support -->
            <div class="bg-blue-50 rounded-lg p-6 text-center">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">Need Help?</h3>
                <p class="text-blue-700 mb-4">If you have questions about your application, our support team is here to help.</p>
                <div class="space-y-2">
                    <p class="text-sm text-blue-600">
                        <i class="fas fa-envelope mr-2"></i>
                        vendor-support@sastohub.com
                    </p>
                    <p class="text-sm text-blue-600">
                        <i class="fas fa-phone mr-2"></i>
                        +977-1-4000000 (9 AM - 6 PM)
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
