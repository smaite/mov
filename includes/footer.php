    </main>

    <!-- Footer -->
    <footer class="bg-secondary text-white mt-16">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-xl font-bold mb-4 text-accent">Sasto Hub</h3>
                    <p class="text-gray-300 mb-4">Your one-stop destination for quality products at unbeatable prices. Shop with confidence and convenience.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-accent"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-300 hover:text-accent"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-300 hover:text-accent"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-300 hover:text-accent"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-accent">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="<?php echo SITE_URL; ?>" class="text-gray-300 hover:text-accent">Home</a></li>
                        <li><a href="?page=products" class="text-gray-300 hover:text-accent">Products</a></li>
                        <li><a href="?page=products&featured=1" class="text-gray-300 hover:text-accent">Featured Products</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-accent">About Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-accent">Contact</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-accent">Categories</h3>
                    <ul class="space-y-2">
                        <?php
                        global $database;
                        $footerCategories = $database->fetchAll("SELECT name, slug FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, name LIMIT 6");
                        foreach ($footerCategories as $category):
                        ?>
                            <li><a href="?page=category&slug=<?php echo $category['slug']; ?>" class="text-gray-300 hover:text-accent"><?php echo htmlspecialchars($category['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-accent">Customer Service</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-300 hover:text-accent">Help Center</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-accent">Returns & Exchanges</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-accent">Shipping Information</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-accent">Track Your Order</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-accent">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-accent">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <!-- Newsletter Signup -->
            <div class="border-t border-gray-600 mt-8 pt-8">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div class="mb-4 md:mb-0">
                        <h3 class="text-lg font-semibold text-accent mb-2">Subscribe to Our Newsletter</h3>
                        <p class="text-gray-300">Get the latest updates on new products and exclusive offers!</p>
                    </div>
                    <form class="flex w-full md:w-auto">
                        <input type="email" placeholder="Enter your email" 
                               class="px-4 py-2 w-full md:w-64 rounded-l-md border-none focus:outline-none focus:ring-2 focus:ring-accent text-gray-900">
                        <button type="submit" class="bg-primary hover:bg-opacity-90 px-6 py-2 rounded-r-md font-semibold">
                            Subscribe
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-gray-600 mt-8 pt-8 flex flex-col md:flex-row items-center justify-between">
                <p class="text-gray-300 text-sm">
                    &copy; <?php echo date('Y'); ?> Sasto Hub. All rights reserved.
                </p>
                <div class="flex items-center space-x-4 mt-4 md:mt-0">
                    <span class="text-gray-300 text-sm">Secure Payment:</span>
                    <div class="flex space-x-2">
                        <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                        <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                        <i class="fab fa-cc-paypal text-2xl text-blue-500"></i>
                        <i class="fab fa-cc-stripe text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Custom JavaScript -->
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }

        // CSRF token for AJAX requests
        const csrfToken = '<?php echo generateCSRFToken(); ?>';

        // Cart functionality
        function addToCart(productId, quantity = 1) {
            if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
                window.location.href = '?page=login';
                return;
            }

            fetch('?ajax=cart&action=add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCount(data.cart_count);
                    showNotification('Product added to cart!', 'success');
                } else {
                    showNotification(data.message || 'Error adding product to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error adding product to cart', 'error');
            });
        }

        // Update cart count
        function updateCartCount(count) {
            document.getElementById('cart-count').textContent = count;
        }

        // Wishlist functionality
        function toggleWishlist(productId) {
            if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
                window.location.href = '?page=login';
                return;
            }

            fetch('?ajax=wishlist&action=toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const button = document.querySelector(`[data-product-id="${productId}"]`);
                    if (button) {
                        button.classList.toggle('text-red-500');
                        button.classList.toggle('text-gray-400');
                    }
                    updateWishlistCount(data.wishlist_count);
                    showNotification(data.message, 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Update wishlist count
        function updateWishlistCount(count) {
            const wishlistCount = document.getElementById('wishlist-count');
            if (wishlistCount) {
                wishlistCount.textContent = count;
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-md shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Load wishlist count on page load
        <?php if (isLoggedIn()): ?>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('?ajax=wishlist&action=count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateWishlistCount(data.count);
                }
            });
        });
        <?php endif; ?>
    </script>

    <!-- Custom CSS -->
    <style>
        .group:hover .group-hover\:opacity-100 {
            opacity: 1;
        }
        .group:hover .group-hover\:visible {
            visibility: visible;
        }
        
        /* Smooth transitions */
        * {
            transition: all 0.2s ease;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #ff6b35;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #e55a2e;
        }
    </style>
</body>
</html>
