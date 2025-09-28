<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Shop the best products at the lowest prices'; ?>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
    
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
    <header class="bg-white shadow-md sticky top-0 z-50">
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
            <div class="hidden lg:flex items-center justify-between py-4">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo SITE_URL; ?>" class="text-3xl font-bold text-primary">
                        <i class="fas fa-shopping-bag mr-2"></i>Sasto Hub
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl mx-8">
                    <form action="?page=search" method="GET" class="relative">
                        <input type="hidden" name="page" value="search">
                        <input type="text" name="q" placeholder="Search for products..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center space-x-6">
                    <!-- Wishlist -->
                    <?php if (isLoggedIn()): ?>
                        <a href="?page=wishlist" class="relative text-gray-700 hover:text-primary">
                            <i class="fas fa-heart text-xl"></i>
                            <span class="absolute -top-2 -right-2 bg-primary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" id="wishlist-count">0</span>
                        </a>
                    <?php endif; ?>

                    <!-- Shopping Cart -->
                    <a href="?page=cart" class="relative text-gray-700 hover:text-primary">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span class="absolute -top-2 -right-2 bg-primary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" id="cart-count">
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

                    <!-- User Account -->
                    <?php if (isLoggedIn()): ?>
                        <div class="relative group">
                            <a href="?page=profile" class="flex items-center text-gray-700 hover:text-primary">
                                <i class="fas fa-user text-xl mr-2"></i>
                                <span class="hidden md:block"><?php echo $_SESSION['first_name']; ?></span>
                            </a>
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div class="py-1">
                                    <a href="?page=profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                                    <a href="?page=orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Orders</a>
                                    <a href="?page=wishlist" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Wishlist</a>
                                    <?php if (isVendor()): ?>
                                        <div class="border-t border-gray-100"></div>
                                        <a href="?page=vendor" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Vendor Dashboard</a>
                                    <?php endif; ?>
                                    <div class="border-t border-gray-100"></div>
                                    <a href="?page=logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="?page=login" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Desktop Navigation Menu -->
            <nav class="hidden lg:block border-t border-gray-200 py-3">
                <div class="flex items-center justify-between">
                    <!-- Categories Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90">
                            <i class="fas fa-bars mr-2"></i>
                            All Categories
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        <div class="absolute left-0 mt-2 w-64 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="py-2">
                                <?php
                                global $database;
                                $categories = $database->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, name LIMIT 10");
                                foreach ($categories as $category):
                                ?>
                                    <a href="?page=category&slug=<?php echo $category['slug']; ?>" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-tag mr-3 text-primary"></i>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                                <div class="border-t border-gray-100 mt-2"></div>
                                <a href="?page=products" class="block px-4 py-2 text-sm text-primary hover:bg-gray-100">
                                    View All Categories
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Main Navigation -->
                    <div class="flex items-center space-x-8">
                        <a href="<?php echo SITE_URL; ?>" class="text-gray-700 hover:text-primary font-medium">Home</a>
                        <a href="?page=products" class="text-gray-700 hover:text-primary font-medium">Products</a>
                        <a href="?page=products&featured=1" class="text-gray-700 hover:text-primary font-medium">Featured</a>
                        <a href="?page=products&sale=1" class="text-gray-700 hover:text-primary font-medium">Sale</a>
                        <a href="#" class="text-gray-700 hover:text-primary font-medium">Contact</a>
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
