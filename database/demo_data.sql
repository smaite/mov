-- Demo data for Sasto Hub E-commerce Platform
-- This file contains sample data for testing the platform
-- IMPORTANT: All demo users have password: 'password'
-- Run this file AFTER schema.sql

USE sasto_hub;

-- Clear existing data to prevent foreign key constraint issues
SET FOREIGN_KEY_CHECKS = 0;

-- Delete in correct order (child tables first, then parent tables)
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM cart;
DELETE FROM wishlist;
DELETE FROM reviews;
DELETE FROM product_images;
DELETE FROM products;
DELETE FROM vendors;
DELETE FROM users;
DELETE FROM categories;
DELETE FROM settings;
DELETE FROM hero_sections;

-- Reset auto-increment counters
ALTER TABLE order_items AUTO_INCREMENT = 1;
ALTER TABLE orders AUTO_INCREMENT = 1;
ALTER TABLE cart AUTO_INCREMENT = 1;
ALTER TABLE wishlist AUTO_INCREMENT = 1;
ALTER TABLE reviews AUTO_INCREMENT = 1;
ALTER TABLE product_images AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;
ALTER TABLE vendors AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE settings AUTO_INCREMENT = 1;
ALTER TABLE hero_sections AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Insert demo users first (password is 'password' for all accounts)
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, country, user_type, status) VALUES
('admin', 'admin@sastohub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '+977-9800000000', 'Kathmandu', 'Kathmandu', 'Nepal', 'admin', 'active'),
('customer1', 'customer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '+977-9841234567', 'Thamel, Kathmandu', 'Kathmandu', 'Nepal', 'customer', 'active'),
('vendor1', 'vendor@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ram', 'Sharma', '+977-9851234567', 'Bhaktapur', 'Bhaktapur', 'Nepal', 'vendor', 'active'),
('vendor2', 'vendor2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sita', 'Patel', '+977-9861234567', 'Lalitpur', 'Lalitpur', 'Nepal', 'vendor', 'active'),
('customer2', 'jane@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '+977-9871234567', 'Pokhara', 'Pokhara', 'Nepal', 'customer', 'active'),
('vendor3', 'newvendor@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maya', 'Gurung', '+977-9876543210', 'Chitwan', 'Chitwan', 'Nepal', 'vendor', 'pending');

-- Insert categories (compatible with schema)
INSERT INTO categories (name, slug, description, image, parent_id, sort_order, is_active) VALUES
('Electronics', 'electronics', 'Electronic devices and gadgets', NULL, NULL, 1, TRUE),
('Fashion', 'fashion', 'Clothing and fashion accessories', NULL, NULL, 2, TRUE),
('Home & Living', 'home-living', 'Furniture and home decoration items', NULL, NULL, 3, TRUE),
('Sports & Fitness', 'sports-fitness', 'Sports equipment and fitness gear', NULL, NULL, 4, TRUE),
('Books & Media', 'books-media', 'Books, movies, music and educational content', NULL, NULL, 5, TRUE),
('Health & Beauty', 'health-beauty', 'Healthcare and beauty products', NULL, NULL, 6, TRUE);

-- Insert sub-categories
INSERT INTO categories (name, slug, description, image, parent_id, sort_order, is_active) VALUES
('Smartphones', 'smartphones', 'Latest smartphones and mobile devices', NULL, 1, 1, TRUE),
('Laptops', 'laptops', 'Laptops and computers for work and gaming', NULL, 1, 2, TRUE),
('Audio & Headphones', 'audio-headphones', 'Speakers, headphones and audio accessories', NULL, 1, 3, TRUE),
('Men Fashion', 'men-fashion', 'Clothing and accessories for men', NULL, 2, 1, TRUE),
('Women Fashion', 'women-fashion', 'Clothing and accessories for women', NULL, 2, 2, TRUE),
('Kids Fashion', 'kids-fashion', 'Clothing and accessories for children', NULL, 2, 3, TRUE);

-- Insert vendor details AFTER users (using correct user_ids)
-- user_id 3 = vendor1, user_id 4 = vendor2, user_id 6 = vendor3
INSERT INTO vendors (user_id, shop_name, shop_description, phone, address, business_license, business_license_file, citizenship_file, pan_card_file, other_documents, commission_rate, is_verified, application_date) VALUES
(3, 'TechHub Nepal', 'Leading electronics and gadgets store in Nepal. We specialize in laptops, smartphones, gaming accessories and tech gadgets.', '+977-9851234567', 'Ward No. 5, Bhaktapur Durbar Square, Bhaktapur', 'BL-2023-001', NULL, NULL, NULL, NULL, 8.00, TRUE, '2024-01-15 10:30:00'),
(4, 'Fashion Forward', 'Trendy clothes and accessories for all ages. From traditional wear to modern fashion, we have it all for men, women and children.', '+977-9861234567', 'Jawalakhel, Lalitpur-10', 'BL-2023-002', NULL, NULL, NULL, NULL, 10.00, TRUE, '2024-01-20 14:45:00'),
(6, 'Mountain Crafts', 'Authentic Nepali handicrafts and traditional items. Supporting local artisans and preserving our cultural heritage.', '+977-9876543210', 'Sauraha, Chitwan', 'BL-2024-003', NULL, NULL, NULL, '[]', 12.00, FALSE, NOW());

-- Insert demo products (using vendor table IDs, not user IDs)
-- vendor_id 1 = TechHub Nepal, vendor_id 2 = Fashion Forward, vendor_id 3 = Mountain Crafts  
-- Note: schema requires slug field, so generating slugs for products
INSERT INTO products (vendor_id, category_id, name, slug, description, price, sale_price, sku, stock_quantity, min_stock_level, weight, dimensions, status, featured, rating, total_reviews, total_sales, meta_title, meta_description) VALUES
(1, 7, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Latest iPhone with advanced Pro camera system, titanium design, and A17 Pro chip', 180000.00, 175000.00, 'IP15PM-256GB', 15, 5, 0.221, '159.9 x 76.7 x 8.25 mm', 'active', TRUE, 4.8, 2, 1, 'iPhone 15 Pro Max - Latest Apple Smartphone', 'Buy iPhone 15 Pro Max with advanced camera, A17 Pro chip, and titanium design'),
(1, 7, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Premium Android flagship with S Pen, 200MP camera, and Galaxy AI features', 140000.00, 135000.00, 'SGS24U-512GB', 20, 5, 0.232, '162.3 x 79.0 x 8.6 mm', 'active', TRUE, 4.7, 1, 1, 'Samsung Galaxy S24 Ultra - AI Smartphone', 'Samsung Galaxy S24 Ultra with S Pen, 200MP camera, Galaxy AI features'),
(1, 8, 'MacBook Air M3 13-inch', 'macbook-air-m3-13-inch', 'Ultra-thin laptop with M3 chip, all-day battery life, and stunning Retina display', 165000.00, 160000.00, 'MBA-M3-512GB', 10, 3, 1.24, '304.1 x 215 x 11.3 mm', 'active', TRUE, 4.9, 1, 1, 'MacBook Air M3 - Ultra-thin Laptop', 'Apple MacBook Air with M3 chip, 13-inch Retina display, 512GB storage'),
(1, 8, 'Dell XPS 13 Plus', 'dell-xps-13-plus', 'Premium ultrabook with 12th Gen Intel Core, OLED display option', 145000.00, NULL, 'DELL-XPS13P', 8, 2, 1.26, '295.3 x 199.04 x 15.28 mm', 'active', FALSE, 4.5, 0, 0, 'Dell XPS 13 Plus - Premium Ultrabook', 'Dell XPS 13 Plus ultrabook with Intel 12th Gen, OLED display'),
(1, 9, 'Sony WH-1000XM5', 'sony-wh-1000xm5', 'Industry-leading noise canceling wireless headphones', 35000.00, 32000.00, 'SONY-WH1000XM5', 25, 8, 0.25, '254 x 220 x 32 mm', 'active', TRUE, 4.6, 1, 1, 'Sony WH-1000XM5 - Noise Canceling Headphones', 'Sony WH-1000XM5 wireless headphones with industry-leading noise cancellation'),
(2, 10, 'Nike Air Max 270', 'nike-air-max-270', 'Comfortable lifestyle sneakers with Max Air unit', 12000.00, 10500.00, 'NIKE-AM270-42', 40, 10, 0.8, '28 x 18 x 12 cm', 'active', FALSE, 4.4, 1, 3, 'Nike Air Max 270 - Lifestyle Sneakers', 'Nike Air Max 270 sneakers with Max Air cushioning technology'),
(2, 10, 'Adidas Ultraboost 22', 'adidas-ultraboost-22', 'Premium running shoes with Boost midsole technology', 15000.00, 13500.00, 'ADI-UB22-43', 30, 8, 0.85, '29 x 19 x 13 cm', 'active', TRUE, 4.7, 0, 0, 'Adidas Ultraboost 22 - Premium Running Shoes', 'Adidas Ultraboost 22 with Boost technology for ultimate comfort'),
(2, 10, 'Levi''s 501 Original Jeans', 'levis-501-original-jeans', 'Classic straight-leg jeans, the original since 1873', 8500.00, 7500.00, 'LEVIS-501-32W', 50, 15, 0.6, 'W32 x L34', 'active', FALSE, 4.3, 0, 0, 'Levi''s 501 Original Jeans - Classic Denim', 'Levi''s 501 Original jeans, classic straight-leg fit since 1873'),
(2, 11, 'Zara Women Blazer', 'zara-women-blazer', 'Professional blazer perfect for office and formal occasions', 6500.00, 5800.00, 'ZARA-BLAZER-M', 20, 5, 0.4, 'Medium size', 'active', TRUE, 4.2, 0, 0, 'Zara Women Professional Blazer', 'Zara women''s blazer for professional and formal wear'),
(2, 12, 'H&M Kids Pajama Set', 'hm-kids-pajama-set', 'Comfortable cotton pajama set for children', 1800.00, 1500.00, 'HM-PAJAMA-8Y', 60, 20, 0.2, 'Age 8 years', 'active', FALSE, 4.0, 0, 0, 'H&M Kids Pajama Set - Cotton Sleepwear', 'Comfortable cotton pajama set for kids, soft and cozy'),
(3, 3, 'Wooden Coffee Table', 'wooden-coffee-table', 'Handcrafted wooden coffee table with traditional Nepali design', 25000.00, 22000.00, 'WCT-NEPALI', 8, 2, 15.0, '120 x 60 x 45 cm', 'pending', TRUE, 0.0, 0, 0, 'Handcrafted Wooden Coffee Table - Traditional Design', 'Beautiful wooden coffee table with traditional Nepali craftsmanship'),
(3, 4, 'Yoga Mat Eco-Friendly', 'yoga-mat-eco-friendly', 'Premium eco-friendly yoga mat made from natural rubber', 3500.00, 2800.00, 'YOGA-ECO-MAT', 80, 25, 2.2, '183 x 61 x 0.6 cm', 'active', FALSE, 4.1, 0, 0, 'Eco-Friendly Yoga Mat - Natural Rubber', 'Premium yoga mat made from eco-friendly natural rubber material');

-- Insert product images (using placeholder service for demo)
INSERT INTO product_images (product_id, image_url, alt_text, is_primary, sort_order) VALUES
(1, 'https://images.unsplash.com/photo-1592286062195-de36f8de6690?w=400&h=400&fit=crop', 'iPhone 15 Pro Max', TRUE, 1),
(2, 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=400&h=400&fit=crop', 'Samsung Galaxy S24 Ultra', TRUE, 1),
(3, 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=400&h=400&fit=crop', 'MacBook Air M3', TRUE, 1),
(4, 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400&h=400&fit=crop', 'Dell XPS 13 Plus', TRUE, 1),
(5, 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400&h=400&fit=crop', 'Sony WH-1000XM5 Headphones', TRUE, 1),
(6, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=400&fit=crop', 'Nike Air Max 270', TRUE, 1),
(7, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400&h=400&fit=crop', 'Adidas Ultraboost 22', TRUE, 1),
(8, 'https://images.unsplash.com/photo-1582552938357-32b906df40cb?w=400&h=400&fit=crop', 'Levi''s 501 Jeans', TRUE, 1),
(9, 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=400&h=400&fit=crop', 'Zara Women Blazer', TRUE, 1),
(10, 'https://images.unsplash.com/photo-1503944583220-79d8926ad5e2?w=400&h=400&fit=crop', 'H&M Kids Pajama Set', TRUE, 1),
(11, 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&h=400&fit=crop', 'Wooden Coffee Table', TRUE, 1),
(12, 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&h=400&fit=crop', 'Eco-Friendly Yoga Mat', TRUE, 1);

-- Insert sample orders (using correct user_ids and schema field names)
INSERT INTO orders (user_id, order_number, total_amount, shipping_amount, tax_amount, discount_amount, shipping_address, payment_method, payment_status, status, notes) VALUES
(2, 'TH2024001', 175500.00, 500.00, 0.00, 0.00, 'Thamel-16, Near Garden of Dreams, Kathmandu, Nepal', 'cash_on_delivery', 'pending', 'confirmed', 'Handle with care - Electronics'),
(5, 'PK2024002', 46300.00, 300.00, 0.00, 2000.00, 'Lakeside-6, Baidam, Pokhara, Nepal', 'esewa', 'paid', 'shipped', 'Express delivery requested'),
(2, 'KT2024003', 32250.00, 250.00, 0.00, 3000.00, 'Thamel-16, Near Garden of Dreams, Kathmandu, Nepal', 'khalti', 'paid', 'delivered', 'Customer satisfied');

-- Insert order items (using correct vendor_ids and schema field names)
INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price, total) VALUES
(1, 1, 1, 1, 175000.00, 175000.00),
(2, 6, 2, 3, 10500.00, 31500.00),
(2, 11, 3, 1, 22000.00, 22000.00),
(3, 5, 1, 1, 32000.00, 32000.00);

-- Insert product reviews (using correct table name "reviews" and schema field names)
INSERT INTO reviews (product_id, user_id, rating, title, comment, is_verified, helpful_count, status) VALUES
(1, 5, 5, 'Outstanding flagship phone!', 'Amazing phone! Camera quality is outstanding, A17 Pro chip performance is incredible, and battery life easily lasts all day. The titanium build feels premium and durable.', TRUE, 12, 'approved'),
(1, 2, 4, 'Great phone but pricey', 'Good phone with excellent features but quite expensive. The Pro camera system is definitely worth it for photography enthusiasts. Face ID works flawlessly.', FALSE, 8, 'approved'),
(3, 5, 5, 'Perfect laptop for professionals', 'Best laptop I have ever used! M3 chip is incredibly fast and handles everything I throw at it. Battery life is amazing - easily 15+ hours of work. The display is gorgeous!', TRUE, 15, 'approved'),
(6, 2, 4, 'Comfortable everyday sneakers', 'Very comfortable shoes perfect for daily wear and light running. The Air Max cushioning provides great support. Stylish design that goes with everything.', TRUE, 6, 'approved'),
(2, 5, 5, 'Samsung at its best', 'The S24 Ultra is a powerhouse! S Pen functionality is smooth, cameras are incredible especially in low light. Galaxy AI features are genuinely useful.', TRUE, 9, 'approved'),
(5, 2, 5, 'Best noise canceling headphones', 'Sony WH-1000XM5 are simply the best! Noise cancellation is industry-leading, sound quality is pristine, and they''re comfortable for long listening sessions.', TRUE, 18, 'approved');

-- Insert wishlist items (using correct table name "wishlist")
INSERT INTO wishlist (user_id, product_id) VALUES
(2, 3),
(2, 11),
(2, 2),
(5, 1),
(5, 5),
(5, 7);

-- Insert cart items (schema doesn't have added_at field)
INSERT INTO cart (user_id, product_id, quantity) VALUES
(2, 2, 1),
(2, 9, 2),
(5, 12, 1),
(5, 8, 1);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Sasto Hub', 'Website name'),
('site_description', 'Your one-stop shop for everything', 'Website description'),
('site_email', 'info@sastohub.com', 'Contact email'),
('site_phone', '+977-1-4000000', 'Contact phone'),
('currency', 'NPR', 'Default currency'),
('shipping_charge', '100', 'Default shipping charge'),
('tax_rate', '13', 'Tax rate percentage'),
('min_order_amount', '500', 'Minimum order amount for free shipping');

-- Insert default hero sections
INSERT INTO hero_sections (position, title, subtitle, description, button_text, button_link, image_url, background_color, text_color) VALUES
(1, 'Welcome to Sasto Hub', 'Your One-Stop Shopping Destination', 'Discover amazing products at unbeatable prices. Shop from thousands of vendors and enjoy fast delivery across Nepal.', 'Shop Now', '?page=products', '/assets/images/hero-placeholder.svg', '#ff6b35', '#ffffff'),
(2, 'Flash Sale', 'Up to 70% Off', 'Limited time offers on electronics, fashion, and home essentials. Don\'t miss out on these incredible deals!', 'View Deals', '?page=products&sale=1', '/assets/images/hero-placeholder.svg', '#e74c3c', '#ffffff'),
(3, 'Become a Vendor', 'Start Your Business Today', 'Join thousands of successful vendors on Sasto Hub. Easy setup, powerful tools, and millions of customers waiting.', 'Join Now', '?page=become-vendor', '/assets/images/hero-placeholder.svg', '#27ae60', '#ffffff');

-- ==========================================
-- IMPORTANT: LOGIN CREDENTIALS FOR TESTING
-- ==========================================
-- ADMIN ACCESS:
-- Email: admin@sastohub.com
-- Password: password
--
-- VENDOR ACCOUNTS:
-- Email: vendor@test.com | Password: password (Active Vendor - TechHub Nepal)
-- Email: vendor2@test.com | Password: password (Active Vendor - Fashion Forward)  
-- Email: newvendor@test.com | Password: password (Pending Vendor - Mountain Crafts)
--
-- CUSTOMER ACCOUNTS:
-- Email: customer@test.com | Password: password (John Doe)
-- Email: jane@test.com | Password: password (Jane Smith)
-- ==========================================
--
-- USER ID MAPPING FOR REFERENCE:
-- 1 = Admin User
-- 2 = John Doe (Customer)
-- 3 = Ram Sharma (Vendor - TechHub Nepal)
-- 4 = Sita Patel (Vendor - Fashion Forward) 
-- 5 = Jane Smith (Customer)
-- 6 = Maya Gurung (Pending Vendor - Mountain Crafts)
--
-- VENDOR ID MAPPING:
-- 1 = TechHub Nepal (user_id: 3)
-- 2 = Fashion Forward (user_id: 4)
-- 3 = Mountain Crafts (user_id: 6)
-- ==========================================
