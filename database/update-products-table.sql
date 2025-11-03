-- Update products table with missing columns
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS short_description VARCHAR(255) AFTER description,
ADD COLUMN IF NOT EXISTS dimensions VARCHAR(100) AFTER weight,
ADD COLUMN IF NOT EXISTS tags TEXT AFTER status,
ADD COLUMN IF NOT EXISTS brand VARCHAR(100) AFTER category_id;

-- Create product_images table if not exists
CREATE TABLE IF NOT EXISTS product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Update categories table if needed (make sure it exists)
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Insert default categories if they don't exist
INSERT IGNORE INTO categories (name, slug, description, sort_order) VALUES
('Electronics', 'electronics', 'Electronic devices and accessories', 1),
('Fashion', 'fashion', 'Clothing, shoes and accessories', 2),
('Home & Garden', 'home-garden', 'Home decor and gardening supplies', 3),
('Sports & Outdoors', 'sports-outdoors', 'Sports equipment and outdoor gear', 4),
('Books & Media', 'books-media', 'Books, movies, music and games', 5),
('Health & Beauty', 'health-beauty', 'Health products and beauty items', 6),
('Toys & Games', 'toys-games', 'Toys and games for all ages', 7),
('Automotive', 'automotive', 'Car parts and automotive accessories', 8),
('Food & Beverages', 'food-beverages', 'Food items and beverages', 9),
('Other', 'other', 'Miscellaneous items', 10);
