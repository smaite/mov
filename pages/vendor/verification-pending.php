<?php
$pageTitle = 'Verification Pending';
$pageDescription = 'Your vendor account is pending verification';

global $database;
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'rejected'): ?>
            <div class="bg-gradient-to-r from-red-500 to-red-600 p-6 text-center">
                <div class="w-20 h-20 mx-auto mb-4 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-times text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Application Rejected</h1>
                <p class="text-white/90">Your vendor application was not approved</p>
            </div>
        <?php else: ?>
            <div class="bg-gradient-to-r from-yellow-400 to-orange-500 p-6 text-center">
                <div class="w-20 h-20 mx-auto mb-4 bg-white/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Verification Pending</h1>
                <p class="text-white/90">Your vendor account is under review</p>
            </div>
        <?php endif; ?>
        
        <!-- Content -->
        <div class="p-6">
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'rejected'): ?>
                <?php 
                // Get rejection reason
                $userId = $_SESSION['user_id'];
                $userInfo = $database->fetchOne("SELECT rejection_reason FROM users WHERE id = ?", [$userId]);
                $rejectionReason = $userInfo['rejection_reason'] ?? 'No reason provided.';
                ?>
                
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-3">We're Sorry!</h2>
                    <p class="text-gray-600 mb-4">Our admin team has reviewed your application and it has been rejected.</p>
                    
                    <div class="bg-red-50 border border-red-100 rounded-lg p-4 mb-6">
                        <p class="font-medium text-gray-800 mb-2">Reason for rejection:</p>
                        <p class="text-gray-700"><?php echo htmlspecialchars($rejectionReason); ?></p>
                    </div>
                </div>
                
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex flex-col space-y-3">
                        <a href="?page=contact" class="flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90">
                            <i class="fas fa-envelope mr-2"></i>Contact Support
                        </a>
                        <a href="?page=home" class="flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-home mr-2"></i>Return to Homepage
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-3">Thank You for Joining!</h2>
                    <p class="text-gray-600 mb-4">Your vendor application is currently being reviewed by our admin team.</p>
                    
                    <div class="flex justify-center items-center space-x-2">
                        <div class="animate-pulse flex">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                            <div class="w-2 h-2 bg-yellow-500 rounded-full mx-1"></div>
                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                        </div>
                        <span class="text-sm text-yellow-700 font-medium">Status: Pending Review</span>
                    </div>
                </div>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                    <p class="font-medium text-gray-800 mb-2">What happens next?</p>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                                <span class="text-xs text-blue-800 font-bold">1</span>
                            </div>
                            <p class="ml-2 text-sm text-gray-600">Our team reviews your application and documents</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                                <span class="text-xs text-blue-800 font-bold">2</span>
                            </div>
                            <p class="ml-2 text-sm text-gray-600">You'll receive an email when your account is approved</p>
                        </li>
                        <li class="flex items-start">
                            <div class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center mt-0.5">
                                <span class="text-xs text-blue-800 font-bold">3</span>
                            </div>
                            <p class="ml-2 text-sm text-gray-600">Once approved, you can start adding products and selling</p>
                        </li>
                    </ul>
                </div>
                
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex flex-col space-y-3">
                        <a href="?page=home" class="flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90">
                            <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
                        </a>
                        <a href="?page=contact" class="flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-question-circle mr-2"></i>Have Questions?
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>