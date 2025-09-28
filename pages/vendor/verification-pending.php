<?php
$pageTitle = 'Verification Pending';
$pageDescription = 'Your vendor account is pending verification';
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-yellow-400 to-orange-500 p-6 text-center">
            <div class="w-20 h-20 mx-auto mb-4 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Verification Pending</h1>
            <p class="text-white/90">Your vendor account is under review</p>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="text-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-3">Thank You for Joining!</h2>
                <p class="text-gray-600 leading-relaxed">
                    We're reviewing your vendor application. Our team will verify your details and activate your account within 24-48 hours.
                </p>
            </div>
            
            <!-- Status Steps -->
            <div class="space-y-4 mb-6">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-800">Application Submitted</p>
                        <p class="text-xs text-gray-500">Your vendor registration is complete</p>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center text-white text-sm">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-800">Under Review</p>
                        <p class="text-xs text-gray-500">Our team is verifying your information</p>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-gray-500 text-sm">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Approval Pending</p>
                        <p class="text-xs text-gray-400">Account activation in progress</p>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <h3 class="text-sm font-semibold text-blue-800 mb-2">Need Help?</h3>
                <p class="text-xs text-blue-700 mb-2">Contact our support team if you have any questions:</p>
                <div class="space-y-1">
                    <p class="text-xs text-blue-600">
                        <i class="fas fa-envelope mr-2"></i>
                        vendor@sastohub.com
                    </p>
                    <p class="text-xs text-blue-600">
                        <i class="fas fa-phone mr-2"></i>
                        +977-1-4000000
                    </p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="space-y-3">
                <a href="<?php echo SITE_URL; ?>" 
                   class="block w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-center py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-indigo-700 transition duration-200">
                    <i class="fas fa-home mr-2"></i>Continue Shopping
                </a>
                
                <a href="?page=logout" 
                   class="block w-full border border-gray-300 text-gray-700 text-center py-3 rounded-lg font-semibold hover:bg-gray-50 transition duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
            
            <!-- Footer Note -->
            <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-500">
                    You will receive an email notification once your account is approved.
                </p>
            </div>
        </div>
    </div>
</div>
