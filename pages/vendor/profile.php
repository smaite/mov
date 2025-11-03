<?php
$pageTitle = 'Shop Profile';
$pageDescription = 'Manage your shop information';

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    echo '<script>window.location.href = "?page=login";</script>';
    exit();
}

global $database;

// Get vendor info
$vendor = $database->fetchOne("SELECT v.*, u.email, u.first_name, u.last_name, u.phone as user_phone 
    FROM vendors v 
    JOIN users u ON v.user_id = u.id 
    WHERE v.user_id = ?", [$_SESSION['user_id']]);
    
if (!$vendor) {
    echo '<script>window.location.href = "?page=register&type=vendor";</script>';
    exit();
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $shopName = trim($_POST['shop_name'] ?? '');
            $shopDescription = trim($_POST['shop_description'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $businessLicense = trim($_POST['business_license'] ?? '');
            
            if (empty($shopName)) {
                $error = 'Shop name is required';
            } else {
                $result = $database->update('vendors', [
                    'shop_name' => $shopName,
                    'shop_description' => $shopDescription,
                    'phone' => $phone,
                    'address' => $address,
                    'business_license' => $businessLicense
                ], 'id = ?', [$vendor['id']]);
                
                if ($result !== false) {
                    $success = '✅ Profile updated successfully!';
                    // Refresh vendor data
                    $vendor = $database->fetchOne("SELECT v.*, u.email, u.first_name, u.last_name, u.phone as user_phone 
                        FROM vendors v 
                        JOIN users u ON v.user_id = u.id 
                        WHERE v.user_id = ?", [$_SESSION['user_id']]);
                } else {
                    $error = '❌ Failed to update profile';
                }
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
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="?page=vendor" class="text-primary hover:text-opacity-80 font-semibold">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Shop Profile</h1>
                <p class="text-gray-600">Manage your shop information and settings</p>
            </div>

            <!-- Messages -->
            <?php if ($success): ?>
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-xl mr-3"></i>
                        <p class="font-medium"><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-xl mr-3"></i>
                        <p class="font-medium"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Shop Info Card -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Shop Information</h2>
                        <div class="flex items-center space-x-2">
                            <?php if ($_SESSION['status'] === 'active'): ?>
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1"></i>Pending Verification
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Shop Name -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Shop Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="shop_name" required
                                       value="<?php echo htmlspecialchars($vendor['shop_name']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>

                            <!-- Shop Description -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Shop Description
                                </label>
                                <textarea name="shop_description" rows="4"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($vendor['shop_description'] ?? ''); ?></textarea>
                                <p class="mt-1 text-sm text-gray-500">Tell customers about your shop</p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Shop Phone
                                </label>
                                <input type="tel" name="phone"
                                       value="<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>

                            <!-- Business License -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Business License Number
                                </label>
                                <input type="text" name="business_license"
                                       value="<?php echo htmlspecialchars($vendor['business_license'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Shop Address
                                </label>
                                <textarea name="address" rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($vendor['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="bg-primary text-white px-6 py-2 rounded-lg font-semibold hover:bg-opacity-90 transition duration-200">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Info Card -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Account Information</h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($vendor['email']); ?></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                            <div class="text-gray-900"><?php echo date('F j, Y', strtotime($vendor['created_at'])); ?></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate</label>
                            <div class="text-gray-900"><?php echo $vendor['commission_rate']; ?>%</div>
                        </div>

                        <?php if ($vendor['application_date']): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Application Date</label>
                                <div class="text-gray-900"><?php echo date('F j, Y', strtotime($vendor['application_date'])); ?></div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Sales</label>
                            <div class="text-gray-900"><?php echo formatPrice($vendor['total_sales']); ?></div>
                        </div>
                    </div>

                    <?php if ($vendor['citizenship_file'] || $vendor['business_license_file'] || $vendor['pan_card_file']): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Uploaded Documents</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php if ($vendor['citizenship_file']): ?>
                                    <a href="<?php echo SITE_URL . $vendor['citizenship_file']; ?>" target="_blank" 
                                       class="inline-flex items-center px-3 py-2 bg-green-100 text-green-800 rounded-lg text-sm hover:bg-green-200">
                                        <i class="fas fa-id-card mr-2"></i>Citizenship
                                    </a>
                                <?php endif; ?>
                                <?php if ($vendor['business_license_file']): ?>
                                    <a href="<?php echo SITE_URL . $vendor['business_license_file']; ?>" target="_blank" 
                                       class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-800 rounded-lg text-sm hover:bg-blue-200">
                                        <i class="fas fa-certificate mr-2"></i>Business License
                                    </a>
                                <?php endif; ?>
                                <?php if ($vendor['pan_card_file']): ?>
                                    <a href="<?php echo SITE_URL . $vendor['pan_card_file']; ?>" target="_blank" 
                                       class="inline-flex items-center px-3 py-2 bg-purple-100 text-purple-800 rounded-lg text-sm hover:bg-purple-200">
                                        <i class="fas fa-credit-card mr-2"></i>PAN Card
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Rating</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo number_format($vendor['rating'], 1); ?> 
                                <i class="fas fa-star text-yellow-500 text-lg"></i>
                            </p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-star text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Revenue</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo formatPrice($vendor['total_sales']); ?></p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-dollar-sign text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Commission</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $vendor['commission_rate']; ?>%</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-percentage text-blue-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
