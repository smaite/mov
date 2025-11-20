<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Shop the best products at the lowest prices'; ?>">
    
    <!-- Tailwind CSS -->
    <script src="assets\js\tailwind.js"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/modern-ui.css">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/theme.css">
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ff6b35',
                        secondary: '#004643',
                        accent: '#f9bc60',
                        light: '#e16162',
                        dark: '#0d1321'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-secondary text-white text-sm">
        <div class="container mx-auto px-4 py-2 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <span><i class="fas fa-phone mr-1"></i> +977-1-4000000</span>
                <span><i class="fas fa-envelope mr-1"></i> info@sastohub.com</span>
            </div>
            <div class="flex items-center space-x-4">
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?php echo $_SESSION['first_name']; ?>!</span>
                    <?php if (isVendor()): ?>
                        <a href="?page=vendor" class="hover:text-accent">Vendor Dashboard</a>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <a href="?page=admin" class="hover:text-accent">Admin Panel</a>
                    <?php endif; ?>
                    <a href="?page=logout" class="hover:text-accent">Logout</a>
                <?php else: ?>
                    <a href="?page=login" class="hover:text-accent">Login</a>
                    <a href="?page=register" class="hover:text-accent">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="container mx-auto px-4">
            <!-- Mobile Header -->
            <div class="lg:hidden flex items-center justify-between py-3">
                <!-- Mobile Menu Button -->
                <button onclick="toggleMobileMenu()" class="text-gray-700 hover:text-primary p-2">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Mobile Logo -->
                <a href="<?php echo SITE_URL; ?>" class="text-2xl font-bold text-primary">
                    <i class="fas fa-shopping-bag mr-1"></i>Sasto Hub
                </a>
                
                <!-- Mobile Cart -->
                <a href="?page=cart" class="relative text-gray-700 hover:text-primary p-2">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <span class="absolute -top-1 -right-1 bg-primary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" id="mobile-cart-count">
                        <?php
                        if (isLoggedIn()) {
                            global $database;
                            $cartCount = $database->count('cart', 'user_id = ?', [$_SESSION['user_id']]);
                            echo $cartCount;
                        } else {
                            echo '0';
                        }
                        ?>
                    </span>
                </a>
            </div>
            
            <!-- Mobile Search Bar -->
            <div class="lg:hidden pb-3">
                <form action="?page=search" method="GET" class="relative">
                    <input type="hidden" name="page" value="search">
                    <input type="text" name="q" placeholder="Search products..." 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Desktop Header -->
            <div class="hidden lg:flex items-center justify-between py-3">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo SITE_URL; ?>" class="flex items-center group">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-3 group-hover:scale-105 transition-transform duration-200">
                            <i class="fas fa-shopping-bag text-white text-lg"></i>
                        </div>
                        <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                            Sasto Hub
                        </span>
                    </a>
                </div>

                <!-- Modern Search Bar -->
                <div class="flex-1 max-w-2xl mx-8">
                    <form action="?page=search" method="GET" class="relative group">
                        <input type="hidden" name="page" value="search">
                        <div class="relative">
                            <input type="text" name="q" placeholder="Search for products, brands and more..." 
                                   class="w-full px-5 py-3.5 pl-12 border border-gray-300 rounded-xl bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 text-gray-700"
                                   value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                                <i class="fas fa-search text-lg"></i>
                            </div>
                            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-6 py-2 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 font-medium shadow-md">
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Wishlist -->
                    <?php if (isLoggedIn()): ?>
                        <a href="?page=wishlist" class="relative p-3 text-gray-600 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-200 group">
                            <i class="fas fa-heart text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium" id="wishlist-count">0</span>
                            <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                Wishlist
                            </div>
                        </a>
                    <?php endif; ?>

                    <!-- Shopping Cart -->
                    <a href="?page=cart" class="relative p-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all duration-200 group">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium" id="cart-count">
                            <?php
                            if (isLoggedIn()) {
                                global $database;
                                $cartCount = $database->count('cart', 'user_id = ?', [$_SESSION['user_id']]);
                                echo $cartCount;
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                        <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                            Cart
                        </div>
                    </a>

                    <!-- User Account -->
                    <?php if (isLoggedIn()): ?>
                        <div class="relative group">
                            <button class="flex items-center p-2 text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all duration-200">
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-semibold mr-2">
                                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                                </div>
                                <span class="hidden md:block font-medium"><?php echo $_SESSION['first_name']; ?></span>
                                <i class="fas fa-chevron-down ml-2 text-xs"></i>
                            </button>
                            <!-- Modern Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div class="p-3 border-b border-gray-100">
                                    <p class="font-semibold text-gray-800"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></p>
                                    <p class="text-sm text-gray-500"><?php echo $_SESSION['email']; ?></p>
                                </div>
                                <div class="py-2">
                                    <a href="?page=customer" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-user mr-3 text-gray-400"></i>My Account
                                    </a>
                                    <a href="?page=orders" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-shopping-bag mr-3 text-gray-400"></i>My Orders
                                    </a>
                                    <a href="?page=wishlist" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-heart mr-3 text-gray-400"></i>Wishlist
                                    </a>
                                    <?php if (isVendor()): ?>
                                        <div class="border-t border-gray-100 my-2"></div>
                                        <a href="?page=vendor" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <i class="fas fa-store mr-3 text-gray-400"></i>Vendor Dashboard
                                        </a>
                                    <?php endif; ?>
                                    <?php if (isAdmin()): ?>
                                        <a href="?page=admin" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <i class="fas fa-cog mr-3 text-gray-400"></i>Admin Panel
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="border-t border-gray-100 py-2">
                                    <a href="?page=logout" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <i class="fas fa-sign-out-alt mr-3"></i>Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center space-x-2 ml-4">
                            <a href="?page=login" class="px-6 py-2.5 text-gray-600 font-medium hover:text-blue-600 transition-colors">
                                Login
                            </a>
                            <a href="?page=register" class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 shadow-md">
                                Sign Up
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modern Navigation Menu -->
            <nav class="hidden lg:block border-t border-gray-100 py-2">
                <div class="flex items-center justify-between">
                    <!-- Categories Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center bg-gradient-to-r from-gray-700 to-gray-800 text-white px-5 py-2.5 rounded-lg hover:from-gray-800 hover:to-gray-900 transition-all duration-200 shadow-sm">
                            <i class="fas fa-th-large mr-2"></i>
                            Categories
                            <i class="fas fa-chevron-down ml-2 text-sm"></i>
                        </button>
                        <div class="absolute left-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="p-3">
                                <h3 class="text-sm font-semibold text-gray-800 mb-3 px-2">Shop by Category</h3>
                                <div class="grid grid-cols-2 gap-1">
                                    <?php
                                    global $database;
                                    $categories = $database->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, name LIMIT 8");
                                    foreach ($categories as $category):
                                    ?>
                                        <a href="?page=category&slug=<?php echo $category['slug']; ?>" 
                                           class="flex items-center px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 group">
                                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3 group-hover:scale-105 transition-transform">
                                                <i class="fas fa-tag text-white text-xs"></i>
                                            </div>
                                            <span class="font-medium"><?php echo htmlspecialchars($category['name']); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="border-t border-gray-100 mt-3 pt-3">
                                    <a href="?page=products" class="block px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg font-medium transition-colors">
                                        <i class="fas fa-arrow-right mr-2"></i>View All Categories
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Navigation -->
                    <div class="flex items-center space-x-1">
                        <a href="<?php echo SITE_URL; ?>" class="px-4 py-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 font-medium rounded-lg transition-all duration-200">
                            <i class="fas fa-home mr-2 text-sm"></i>Home
                        </a>
                        <a href="?page=products" class="px-4 py-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 font-medium rounded-lg transition-all duration-200">
                            <i class="fas fa-shopping-bag mr-2 text-sm"></i>All Products
                        </a>
                        <a href="?page=products&featured=1" class="px-4 py-2 text-gray-700 hover:text-amber-600 hover:bg-amber-50 font-medium rounded-lg transition-all duration-200">
                            <i class="fas fa-star mr-2 text-sm"></i>Featured
                        </a>
                        <a href="?page=products&sale=1" class="px-4 py-2 text-gray-700 hover:text-red-600 hover:bg-red-50 font-medium rounded-lg transition-all duration-200">
                            <i class="fas fa-fire mr-2 text-sm"></i>Sale
                        </a>
                        <a href="#" class="px-4 py-2 text-gray-700 hover:text-green-600 hover:bg-green-50 font-medium rounded-lg transition-all duration-200">
                            <i class="fas fa-headset mr-2 text-sm"></i>Support
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Mobile Navigation Menu -->
            <div id="mobile-menu" class="fixed inset-0 z-50 lg:hidden transform translate-x-full transition-transform duration-300 ease-in-out">
                <div class="flex">
                    <!-- Overlay -->
                    <div class="flex-shrink-0 w-20" onclick="toggleMobileMenu()"></div>
                    
                    <!-- Menu Panel -->
                    <div class="relative flex flex-col w-80 h-full bg-white shadow-xl">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-800">Menu</h2>
                            <button onclick="toggleMobileMenu()" class="text-gray-600 hover:text-gray-800">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- User Info -->
                        <?php if (isLoggedIn()): ?>
                            <div class="p-6 border-b border-gray-200 bg-gray-50">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white text-lg">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="ml-4">
                                        <p class="font-semibold text-gray-800"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></p>
                                        <p class="text-sm text-gray-600"><?php echo $_SESSION['email']; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Menu Items -->
                        <div class="flex-1 overflow-y-auto">
                            <div class="p-4">
                                <!-- Main Navigation -->
                                <div class="space-y-2 mb-6">
                                    <a href="<?php echo SITE_URL; ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                                        <i class="fas fa-home mr-3 text-primary"></i>Home
                                    </a>
                                    <a href="?page=products" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                                        <i class="fas fa-box mr-3 text-primary"></i>All Products
                                    </a>
                                    <a href="?page=products&featured=1" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                                        <i class="fas fa-star mr-3 text-primary"></i>Featured Products
                                    </a>
                                    <a href="?page=products&sale=1" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                                        <i class="fas fa-fire mr-3 text-primary"></i>Sale Products
                                    </a>
                                </div>
                                
                                <!-- Categories -->
                                <div class="mb-6">
                                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3 px-4">Categories</h3>
                                    <div class="space-y-1">
                                        <?php
                                        $mobileCategories = $database->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, name LIMIT 8");
                                        foreach ($mobileCategories as $category):
                                        ?>
                                            <a href="?page=category&slug=<?php echo $category['slug']; ?>" 
                                               class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                                                <i class="fas fa-tag mr-3 text-sm"></i>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- User Actions -->
                                <?php if (isLoggedIn()): ?>
                                    <div class="mb-6">
                                        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3 px-4">My Account</h3>
                                        <div class="space-y-1">
                                            <a href="?page=profile" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                                                <i class="fas fa-user mr-3"></i>My Profile
                                            </a>
                                            <a href="?page=orders" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                                                <i class="fas fa-shopping-bag mr-3"></i>My Orders
                                            </a>
                                            <a href="?page=wishlist" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                                                <i class="fas fa-heart mr-3"></i>Wishlist
                                            </a>
                                            <?php if (isVendor()): ?>
                                                <a href="?page=vendor" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                                                    <i class="fas fa-store mr-3"></i>Vendor Dashboard
                                                </a>
                                            <?php endif; ?>
                                            <?php if (isAdmin()): ?>
                                                <a href="?page=admin" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                                                    <i class="fas fa-cog mr-3"></i>Admin Panel
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="p-4 border-t border-gray-200">
                            <?php if (isLoggedIn()): ?>
                                <a href="?page=logout" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg">
                                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                                </a>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <a href="?page=login" class="block w-full bg-primary text-white text-center py-3 rounded-lg font-semibold">
                                        Login
                                    </a>
                                    <a href="?page=register" class="block w-full border border-primary text-primary text-center py-3 rounded-lg font-semibold">
                                        Register
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-h-screen">
