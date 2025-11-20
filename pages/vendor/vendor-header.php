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
                top: 64px; /* Account for header height */
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
                padding-top: 64px; /* Account for fixed header */
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
        
        /* Hide mobile header on desktop */
        @media (min-width: 1025px) {
            .mobile-header {
                display: none;
            }
        }
        
        /* Show mobile header only on mobile */
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
                    <i class="fas fa-store text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($vendor['shop_name']); ?></h1>
                    <p class="text-xs text-gray-500">Vendor Dashboard</p>
                </div>
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
                
                <a href="?page=vendor" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 <?php echo ($section ?? 'dashboard') === 'dashboard' ? 'text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'; ?>">
                    <div class="w-9 h-9 rounded-lg <?php echo ($section ?? 'dashboard') === 'dashboard' ? 'bg-white/20' : 'bg-gray-100 group-hover:bg-orange-100'; ?> flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-tachometer-alt text-sm"></i>
                    </div>
                    <span class="font-medium">Overview</span>
                    <?php if (($section ?? 'dashboard') === 'dashboard'): ?>
                        <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                    <?php endif; ?>
                </a>
                
                <a href="?page=vendor&section=products" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 <?php echo ($section ?? '') === 'products' ? 'text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'; ?>">
                    <div class="w-9 h-9 rounded-lg <?php echo ($section ?? '') === 'products' ? 'bg-white/20' : 'bg-gray-100 group-hover:bg-orange-100'; ?> flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-box text-sm"></i>
                    </div>
                    <span class="font-medium">Products</span>
                    <?php if (($section ?? '') === 'products'): ?>
                        <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                    <?php endif; ?>
                </a>
                
                <a href="?page=vendor&section=orders" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 <?php echo ($section ?? '') === 'orders' ? 'text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'; ?>">
                    <div class="w-9 h-9 rounded-lg <?php echo ($section ?? '') === 'orders' ? 'bg-white/20' : 'bg-gray-100 group-hover:bg-orange-100'; ?> flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-shopping-cart text-sm"></i>
                    </div>
                    <span class="font-medium">Orders</span>
                    <?php if (($section ?? '') === 'orders'): ?>
                        <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                    <?php endif; ?>
                </a>
                
                <a href="?page=vendor&section=analytics" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 <?php echo ($section ?? '') === 'analytics' ? 'text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'; ?>">
                    <div class="w-9 h-9 rounded-lg <?php echo ($section ?? '') === 'analytics' ? 'bg-white/20' : 'bg-gray-100 group-hover:bg-orange-100'; ?> flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    <span class="font-medium">Analytics</span>
                    <?php if (($section ?? '') === 'analytics'): ?>
                        <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                    <?php endif; ?>
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <div class="px-3 py-2">
                        <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Account</span>
                    </div>
                    
                    <a href="?page=vendor&section=profile" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 <?php echo ($section ?? '') === 'profile' ? 'text-white bg-gradient-to-r from-orange-500 to-red-500 shadow-lg' : 'text-gray-600 hover:text-orange-600 hover:bg-orange-50'; ?>">
                        <div class="w-9 h-9 rounded-lg <?php echo ($section ?? '') === 'profile' ? 'bg-white/20' : 'bg-gray-100 group-hover:bg-orange-100'; ?> flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <span class="font-medium">Profile</span>
                        <?php if (($section ?? '') === 'profile'): ?>
                            <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                        <?php endif; ?>
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
            <div class="p-6 lg:p-8 max-w-7xl mx-auto">