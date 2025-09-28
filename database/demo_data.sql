-- Demo data for Sasto Hub E-commerce Platform
-- This file contains sample data for testing the platform

USE sasto_hub;

-- Insert demo users (password is 'password' for all)
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, country, user_type, status) VALUES
('customer1', 'customer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '+977-9841234567', 'Thamel, Kathmandu', 'Kathmandu', 'Nepal', 'customer', 'active'),
('vendor1', 'vendor@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ram', 'Sharma', '+977-9851234567', 'Bhaktapur', 'Bhaktapur', 'Nepal', 'vendor', 'active'),
('vendor2', 'vendor2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sita', 'Patel', '+977-9861234567', 'Lalitpur', 'Lalitpur', 'Nepal', 'vendor', 'active'),
('customer2', 'jane@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '+977-9871234567', 'Pokhara', 'Pokhara', 'Nepal', 'customer', 'active');

-- Insert vendor details
INSERT INTO vendors (user_id, shop_name, shop_description, commission_rate, is_verified) VALUES
(2, 'TechHub Nepal', 'Leading electronics and gadgets store in Nepal', 8.00, TRUE),
(3, 'Fashion Forward', 'Trendy clothes and accessories for all ages', 10.00, TRUE);

-- Insert more categories
INSERT INTO categories (name, slug, description, sort_order) VALUES
('Smartphones', 'smartphones', 'Latest smartphones and mobile devices', 1),
('Laptops', 'laptops', 'Laptops and computers for work and gaming', 2),
('Clothing', 'clothing', 'Fashionable clothing for men and women', 1),
('Accessories', 'accessories', 'Fashion accessories and jewelry', 2),
('Home Appliances', 'home-appliances', 'Kitchen and home appliances', 1),
('Books & Education', 'books-education', 'Educational books and materials', 1);

-- Get category IDs for products
SET @electronics_id = (SELECT id FROM categories WHERE slug = 'electronics');
SET @fashion_id = (SELECT id FROM categories WHERE slug = 'fashion');
SET @smartphones_id = (SELECT id FROM categories WHERE slug = 'smartphones');
SET @laptops_id = (SELECT id FROM categories WHERE slug = 'laptops');
SET @clothing_id = (SELECT id FROM categories WHERE slug = 'clothing');
SET @books_id = (SELECT id FROM categories WHERE slug = 'books-education');

-- Update category parent relationships
UPDATE categories SET parent_id = @electronics_id WHERE slug IN ('smartphones', 'laptops');
UPDATE categories SET parent_id = @fashion_id WHERE slug IN ('clothing', 'accessories');

-- Get vendor IDs
SET @vendor1_id = (SELECT id FROM vendors WHERE shop_name = 'TechHub Nepal');
SET @vendor2_id = (SELECT id FROM vendors WHERE shop_name = 'Fashion Forward');

-- Insert demo products
INSERT INTO products (vendor_id, category_id, name, slug, description, short_description, sku, price, sale_price, stock_quantity, featured, rating, total_reviews, total_sales) VALUES
-- Electronics by TechHub Nepal
(@vendor1_id, @smartphones_id, 'iPhone 14 Pro Max', 'iphone-14-pro-max', 'Latest iPhone with advanced camera system and A16 Bionic chip. Experience the most advanced iPhone ever with ProRAW photography, Cinematic mode, and all-day battery life.', 'Latest iPhone with advanced camera and A16 Bionic chip', 'IPHONE14PM001', 180000.00, 165000.00, 15, 1, 4.8, 24, 8),
(@vendor1_id, @smartphones_id, 'Samsung Galaxy S23 Ultra', 'samsung-galaxy-s23-ultra', 'Premium Android smartphone with S Pen, exceptional camera capabilities, and powerful performance for productivity and creativity.', 'Premium Android smartphone with S Pen and exceptional camera', 'SAMS23U001', 150000.00, NULL, 12, 1, 4.6, 18, 12),
(@vendor1_id, @laptops_id, 'MacBook Air M2', 'macbook-air-m2', 'Incredibly thin and light laptop powered by M2 chip. Perfect for students and professionals who need portability without compromising performance.', 'Thin and light laptop powered by M2 chip', 'MBAIRM2001', 140000.00, 135000.00, 8, 1, 4.9, 32, 15),
(@vendor1_id, @laptops_id, 'Dell XPS 13', 'dell-xps-13', 'Premium ultrabook with InfinityEdge display and latest Intel processors. Ideal for business professionals and content creators.', 'Premium ultrabook with InfinityEdge display', 'DELLXPS13001', 120000.00, NULL, 10, 0, 4.5, 16, 6),
(@vendor1_id, @electronics_id, 'Sony WH-1000XM4 Headphones', 'sony-wh-1000xm4', 'Industry-leading noise canceling wireless headphones with exceptional sound quality and 30-hour battery life.', 'Industry-leading noise canceling wireless headphones', 'SONYWH001', 35000.00, 32000.00, 25, 1, 4.7, 45, 32),

-- Fashion by Fashion Forward
(@vendor2_id, @clothing_id, 'Premium Cotton T-Shirt', 'premium-cotton-tshirt', 'High-quality 100% cotton t-shirt available in multiple colors. Comfortable, durable, and perfect for casual wear.', 'High-quality 100% cotton t-shirt in multiple colors', 'COTTEETS001', 1200.00, 950.00, 50, 0, 4.3, 28, 45),
(@vendor2_id, @clothing_id, 'Denim Jacket Classic', 'denim-jacket-classic', 'Timeless denim jacket made from premium denim fabric. A wardrobe essential that never goes out of style.', 'Timeless denim jacket made from premium fabric', 'DENIMJAC001', 4500.00, NULL, 20, 1, 4.4, 15, 18),
(@vendor2_id, @fashion_id, 'Leather Handbag', 'leather-handbag', 'Elegant genuine leather handbag perfect for office and casual occasions. Features multiple compartments and durable construction.', 'Elegant genuine leather handbag for all occasions', 'LEATHBAG001', 6500.00, 5800.00, 15, 1, 4.6, 22, 12),
(@vendor2_id, @clothing_id, 'Formal Shirt White', 'formal-shirt-white', 'Classic white formal shirt made from premium cotton blend. Perfect for office wear and formal occasions.', 'Classic white formal shirt for office wear', 'FORMSH001', 2200.00, NULL, 30, 0, 4.2, 19, 25),

-- Books and General Items
(@vendor1_id, @books_id, 'Programming Book - Python', 'programming-book-python', 'Comprehensive guide to Python programming for beginners and intermediate developers. Includes practical examples and exercises.', 'Comprehensive Python programming guide with examples', 'PYTHONBOOK001', 2500.00, 2200.00, 40, 0, 4.5, 35, 28);

-- Insert product images (using placeholder paths)
INSERT INTO product_images (product_id, image_url, alt_text, is_primary) VALUES
(1, '/uploads/products/iphone14pm.jpg', 'iPhone 14 Pro Max', 1),
(2, '/uploads/products/galaxy-s23.jpg', 'Samsung Galaxy S23 Ultra', 1),
(3, '/uploads/products/macbook-air.jpg', 'MacBook Air M2', 1),
(4, '/uploads/products/dell-xps13.jpg', 'Dell XPS 13', 1),
(5, '/uploads/products/sony-headphones.jpg', 'Sony WH-1000XM4', 1),
(6, '/uploads/products/cotton-tshirt.jpg', 'Premium Cotton T-Shirt', 1),
(7, '/uploads/products/denim-jacket.jpg', 'Denim Jacket', 1),
(8, '/uploads/products/leather-handbag.jpg', 'Leather Handbag', 1),
(9, '/uploads/products/formal-shirt.jpg', 'Formal Shirt White', 1),
(10, '/uploads/products/python-book.jpg', 'Python Programming Book', 1);

-- Insert product attributes
INSERT INTO product_attributes (product_id, attribute_name, attribute_value, sort_order) VALUES
-- iPhone 14 Pro Max
(1, 'Display', '6.7-inch Super Retina XDR', 1),
(1, 'Processor', 'A16 Bionic chip', 2),
(1, 'Storage', '128GB, 256GB, 512GB, 1TB', 3),
(1, 'Camera', 'Pro camera system with 48MP Main', 4),
(1, 'Battery', 'Up to 29 hours video playback', 5),

-- Samsung Galaxy S23 Ultra
(2, 'Display', '6.8-inch Dynamic AMOLED 2X', 1),
(2, 'Processor', 'Snapdragon 8 Gen 2', 2),
(2, 'Storage', '256GB, 512GB, 1TB', 3),
(2, 'Camera', '200MP main camera with AI', 4),
(2, 'S Pen', 'Built-in S Pen included', 5),

-- MacBook Air M2
(3, 'Processor', 'Apple M2 chip', 1),
(3, 'Display', '13.6-inch Liquid Retina', 2),
(3, 'Memory', '8GB, 16GB, 24GB unified memory', 3),
(3, 'Storage', '256GB, 512GB, 1TB, 2TB SSD', 4),
(3, 'Battery', 'Up to 18 hours', 5),

-- Cotton T-Shirt
(6, 'Material', '100% Premium Cotton', 1),
(6, 'Sizes', 'S, M, L, XL, XXL', 2),
(6, 'Colors', 'White, Black, Navy, Gray', 3),
(6, 'Care', 'Machine washable', 4);

-- Insert some demo reviews
INSERT INTO reviews (product_id, user_id, rating, title, comment, is_verified, status) VALUES
(1, 1, 5, 'Amazing phone!', 'The camera quality is incredible and the battery lasts all day. Highly recommended!', 1, 'approved'),
(1, 4, 4, 'Good but expensive', 'Great phone with excellent features but quite pricey. Worth it if you can afford it.', 1, 'approved'),
(3, 1, 5, 'Perfect for work', 'Lightweight, fast, and great battery life. Perfect for my daily work needs.', 1, 'approved'),
(5, 4, 5, 'Best noise canceling', 'These headphones are amazing for travel and work from home. Sound quality is top-notch.', 1, 'approved'),
(6, 1, 4, 'Comfortable and good quality', 'Nice fabric quality and fits well. Good value for money.', 1, 'approved');

-- Insert some demo cart items (for customer1)
INSERT INTO cart (user_id, product_id, quantity) VALUES
(1, 6, 2),
(1, 10, 1);

-- Insert some demo orders
INSERT INTO orders (user_id, order_number, total_amount, shipping_amount, tax_amount, status, payment_status, payment_method, shipping_address, billing_address) VALUES
(1, 'SH20241001000001', 37600.00, 100.00, 4588.46, 'delivered', 'paid', 'cod', 'Thamel, Kathmandu, Nepal', 'Thamel, Kathmandu, Nepal'),
(4, 'SH20241002000002', 6760.00, 0.00, 871.54, 'shipped', 'paid', 'bank_transfer', 'Pokhara, Nepal', 'Pokhara, Nepal');

-- Insert order items
INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price, total) VALUES
(1, 5, 1, 1, 32000.00, 32000.00),
(2, 8, 2, 1, 5800.00, 5800.00);

-- Update vendor ratings and sales
UPDATE vendors SET rating = 4.7, total_sales = 180000.00 WHERE id = 1;
UPDATE vendors SET rating = 4.5, total_sales = 85000.00 WHERE id = 2;

-- Insert some coupons
INSERT INTO coupons (code, description, discount_type, discount_value, minimum_amount, usage_limit, start_date, end_date, is_active) VALUES
('WELCOME10', 'Welcome discount for new customers', 'percentage', 10.00, 1000.00, 100, '2024-01-01', '2024-12-31', 1),
('SAVE500', 'Save Rs. 500 on orders over Rs. 5000', 'fixed', 500.00, 5000.00, 50, '2024-01-01', '2024-12-31', 1),
('FREESHIP', 'Free shipping on any order', 'fixed', 100.00, 0.00, 200, '2024-01-01', '2024-12-31', 1);

-- Insert notifications
INSERT INTO notifications (user_id, title, message, type) VALUES
(1, 'Order Delivered', 'Your order #SH20241001000001 has been delivered successfully!', 'success'),
(1, 'Welcome to Sasto Hub', 'Thank you for joining Sasto Hub. Enjoy shopping with us!', 'info'),
(4, 'Order Shipped', 'Your order #SH20241002000002 has been shipped and is on the way!', 'info');

-- Update settings
UPDATE settings SET setting_value = 'Sasto Hub - Your one-stop shop for everything' WHERE setting_key = 'site_description';

-- Add more sample products to make the platform look fuller
INSERT INTO products (vendor_id, category_id, name, slug, description, short_description, sku, price, sale_price, stock_quantity, featured, rating, total_reviews) VALUES
(@vendor1_id, @smartphones_id, 'Google Pixel 7', 'google-pixel-7', 'Pure Google experience with advanced AI photography', 'Pure Google experience with AI photography', 'PIXEL7001', 75000.00, 68000.00, 18, 0, 4.4, 12),
(@vendor1_id, @electronics_id, 'iPad Air 5th Gen', 'ipad-air-5', 'Powerful and versatile iPad with M1 chip', 'Powerful iPad with M1 chip for creativity', 'IPADAIR5001', 85000.00, NULL, 22, 1, 4.6, 28),
(@vendor2_id, @clothing_id, 'Casual Jeans', 'casual-jeans', 'Comfortable and stylish jeans for everyday wear', 'Comfortable casual jeans for daily wear', 'JEANS001', 3500.00, 2800.00, 35, 0, 4.2, 15),
(@vendor2_id, @fashion_id, 'Sports Watch', 'sports-watch', 'Digital sports watch with multiple features', 'Feature-rich digital sports watch', 'SWATCH001', 4200.00, NULL, 28, 0, 4.1, 9);

-- Add corresponding product images
INSERT INTO product_images (product_id, image_url, alt_text, is_primary) VALUES
(11, '/uploads/products/pixel-7.jpg', 'Google Pixel 7', 1),
(12, '/uploads/products/ipad-air.jpg', 'iPad Air 5th Generation', 1),
(13, '/uploads/products/casual-jeans.jpg', 'Casual Jeans', 1),
(14, '/uploads/products/sports-watch.jpg', 'Sports Watch', 1);
