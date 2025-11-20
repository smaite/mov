<?php
$pageTitle = 'Shop Profile';
$pageDescription = 'Manage your shop information';

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    header('Location: ?page=login');
    exit();
}

global $database;

// Get vendor info
$vendor = $database->fetchOne("SELECT v.*, u.email, u.first_name, u.last_name, u.phone as user_phone 
    FROM vendors v 
    JOIN users u ON v.user_id = u.id 
    WHERE v.user_id = ?", [$_SESSION['user_id']]);
    
if (!$vendor) {
    header('Location: ?page=register&type=vendor');
    exit();
}

// Check if vendor is verified
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'active') {
    include __DIR__ . '/verification-pending.php';
    return;
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
    <style>
        /* Custom styles for vendor system */
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar-overlay {
            backdrop-filter: blur(4px);
        }
        
        /* Ensure proper mobile behavior */
        @media (max-width: 1024px) {
            .vendor-sidebar {
                position: fixed;
                top: 64px;
                bottom: 0;
                left: 0;
                z-index: 40;
                width: 288px;
                transform: translateX(-100%);
            }
            
            .vendor-sidebar.open {
                transform: translateX(0);
            }
            
            .vendor-content {
                margin-left: 0;
                padding-top: 64px;
            }
        }
        
        @media (min-width: 1025px) {
            .vendor-sidebar {
                position: relative;
                width: 288px;
                transform: translateX(0) !important;
            }
            
            .vendor-content {
                margin-left: 288px;
                padding-top: 0;
            }
        }
        
        @media (min-width: 1025px) {
            .mobile-header {
                display: none;
            }
        }
        
        @media (max-width: 1024px) {
            .mobile-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 50;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-50 min-h-screen">
    <!-- Mobile Header -->
    <div class="mobile-header bg-white shadow-lg border-b px-4 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user text-white text-sm"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-800">Shop Profile</h1>
            </div>
            <button onclick="toggleVendorSidebar()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                <i class="fas fa-bars text-gray-600"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Modern Vendor Sidebar -->
        <div id="vendor-sidebar" class="vendor-sidebar bg-white shadow-2xl border-r border-gray-200 sidebar-transition">
            
            <!-- Vendor Profile Header -->
            <div class="p-6 bg-gradient-to-r from-orange-500 to-red-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center">
                            <?php if ($vendor['shop_logo']): ?>
                                <img src="<?php echo htmlspecialchars($vendor['shop_logo']); ?>" 
                                     alt="Shop Logo" class="w-full h-full object-cover rounded-xl">
                            <?php else: ?>
                                <i class="fas fa-store text-white text-xl"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white"><?php echo htmlspecialchars($vendor['shop_name']); ?></h2>
                            <div class="flex items-center text-orange-100 text-sm">
                                <?php if ($_SESSION['status'] === 'active'): ?>
                                    <i class="fas fa-check-circle mr-1"></i>Active Store
                                <?php else: ?>
                                    <i class="fas fa-clock mr-1"></i><?php echo ucfirst($_SESSION['status']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <button onclick="toggleVendorSidebar()" class="lg:hidden p-1 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <div class="px-3 py-2">
                    <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Dashboard</span>
                </div>
                
                <a href="?page=vendor" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-tachometer-alt text-sm"></i>
                    </div>
                    <span class="font-medium">Overview</span>
                </a>
                
                <a href="?page=vendor-products" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-box text-sm"></i>
                    </div>
                    <span class="font-medium">Products</span>
                </a>
                
                <a href="?page=vendor-orders" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-shopping-cart text-sm"></i>
                    </div>
                    <span class="font-medium">Orders</span>
                </a>
                
                <a href="?page=vendor-analytics" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-orange-600 hover:bg-orange-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-orange-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    <span class="font-medium">Analytics</span>
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <div class="px-3 py-2">
                        <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Account</span>
                    </div>
                    
                    <a href="?page=vendor-profile" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg">
                        <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <span class="font-medium">Profile</span>
                        <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                    </a>
                    
                    <a href="?page=logout" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-red-600 hover:text-red-700 hover:bg-red-50">
                        <div class="w-9 h-9 rounded-lg bg-red-100 group-hover:bg-red-200 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </div>
                        <span class="font-medium">Logout</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Sidebar Overlay for Mobile -->
        <div id="vendor-overlay" class="fixed inset-0 bg-black/50 z-30 lg:hidden sidebar-overlay" onclick="toggleVendorSidebar()"></div>

        <!-- Main Content Area -->
        <div class="vendor-content flex-1">
            <div class="p-6 lg:p-8 max-w-4xl mx-auto">
                
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Shop Profile</h1>
                            <p class="text-gray-600 mt-1">Manage your shop information and settings</p>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-6 rounded-2xl shadow-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-2xl mr-3"></i>
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-2xl shadow-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Shop Info Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-8">
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
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Shop Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="shop_name" required
                                           value="<?php echo htmlspecialchars($vendor['shop_name']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                </div>

                                <!-- Shop Description -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Shop Description
                                    </label>
                                    <textarea name="shop_description" rows="4"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"><?php echo htmlspecialchars($vendor['shop_description'] ?? ''); ?></textarea>
                                    <p class="mt-1 text-sm text-gray-500">Tell customers about your shop</p>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Shop Phone
                                    </label>
                                    <input type="tel" name="phone"
                                           value="<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                </div>

                                <!-- Business License -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Business License Number
                                    </label>
                                    <input type="text" name="business_license"
                                           value="<?php echo htmlspecialchars($vendor['business_license'] ?? ''); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                </div>

                                <!-- Address -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Shop Address
                                    </label>
                                    <textarea name="address" rows="3"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"><?php echo htmlspecialchars($vendor['address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" 
                                        class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-3 rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                                    <i class="fas fa-save mr-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Info Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Account Information</h2>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                                <div class="text-gray-900"><?php echo htmlspecialchars($vendor['email']); ?></div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Account Name</label>
                                <div class="text-gray-900"><?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Member Since</label>
                                <div class="text-gray-900"><?php echo date('F j, Y', strtotime($vendor['created_at'])); ?></div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Commission Rate</label>
                                <div class="text-gray-900"><?php echo $vendor['commission_rate']; ?>%</div>
                            </div>

                            <?php if ($vendor['application_date']): ?>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Application Date</label>
                                    <div class="text-gray-900"><?php echo date('F j, Y', strtotime($vendor['application_date'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Total Sales</label>
                                <div class="text-gray-900"><?php echo formatPrice($vendor['total_sales']); ?></div>
                            </div>
                        </div>

                        <?php if ($vendor['citizenship_file'] || $vendor['business_license_file'] || $vendor['pan_card_file']): ?>
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Uploaded Documents</h3>
                                <div class="flex flex-wrap gap-2">
                                    <?php if ($vendor['citizenship_file']): ?>
                                        <a href="<?php echo SITE_URL . $vendor['citizenship_file']; ?>" target="_blank" 
                                           class="inline-flex items-center px-3 py-2 bg-green-100 text-green-800 rounded-xl text-sm hover:bg-green-200">
                                            <i class="fas fa-id-card mr-2"></i>Citizenship
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($vendor['business_license_file']): ?>
                                        <a href="<?php echo SITE_URL . $vendor['business_license_file']; ?>" target="_blank" 
                                           class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-800 rounded-xl text-sm hover:bg-blue-200">
                                            <i class="fas fa-certificate mr-2"></i>Business License
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($vendor['pan_card_file']): ?>
                                        <a href="<?php echo SITE_URL . $vendor['pan_card_file']; ?>" target="_blank" 
                                           class="inline-flex items-center px-3 py-2 bg-purple-100 text-purple-800 rounded-xl text-sm hover:bg-purple-200">
                                            <i class="fas fa-credit-card mr-2"></i>PAN Card
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Rating</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format($vendor['rating'], 1); ?> 
                                    <i class="fas fa-star text-yellow-500 text-lg"></i>
                                </p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-2xl">
                                <i class="fas fa-star text-yellow-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Revenue</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo formatPrice($vendor['total_sales']); ?></p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-2xl">
                                <i class="fas fa-dollar-sign text-green-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Commission</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $vendor['commission_rate']; ?>%</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-2xl">
                                <i class="fas fa-percentage text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleVendorSidebar() {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        
        sidebar.classList.toggle('open');
        overlay.classList.toggle('hidden');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        const toggleButton = event.target.closest('[onclick="toggleVendorSidebar()"]');
        
        if (!toggleButton && !sidebar.contains(event.target) && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        }
    });

    // Initialize sidebar state based on screen size
    function initializeSidebar() {
        const sidebar = document.getElementById('vendor-sidebar');
        const overlay = document.getElementById('vendor-overlay');
        
        if (window.innerWidth <= 1024) {
            // Mobile: hide sidebar by default
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        } else {
            // Desktop: always show sidebar
            sidebar.classList.add('open');
            overlay.classList.add('hidden');
        }
    }

    // Handle window resize
    window.addEventListener('resize', initializeSidebar);

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initializeSidebar);
    </script>
</body>
</html>