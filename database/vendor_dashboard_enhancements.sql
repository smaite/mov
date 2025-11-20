-- Enhanced Database Schema for Vendor Dashboard System Rebuild
-- This file contains additional tables and enhancements for the modern vendor dashboard

-- Vendor Analytics Table for detailed reporting
CREATE TABLE vendor_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    date DATE NOT NULL,
    total_views INT DEFAULT 0,
    product_views INT DEFAULT 0,
    profile_views INT DEFAULT 0,
    orders_count INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0.00,
    unique_visitors INT DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_order_value DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vendor_date (vendor_id, date)
);

-- Vendor Activity Logs for audit trail
CREATE TABLE vendor_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    user_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'product_create', 'product_update', 'product_delete', 'order_update', 'profile_update', 'settings_change') NOT NULL,
    activity_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_vendor_activity (vendor_id, created_at),
    INDEX idx_activity_type (activity_type)
);

-- Enhanced Inventory Management
CREATE TABLE vendor_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    product_id INT NOT NULL,
    current_stock INT NOT NULL DEFAULT 0,
    reserved_stock INT DEFAULT 0,
    available_stock INT GENERATED ALWAYS AS (current_stock - reserved_stock) STORED,
    reorder_point INT DEFAULT 0,
    reorder_quantity INT DEFAULT 0,
    cost_price DECIMAL(10,2),
    supplier_info TEXT,
    last_restocked_at TIMESTAMP NULL,
    next_restock_date DATE NULL,
    stock_alerts_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vendor_product (vendor_id, product_id)
);

-- Stock Movement History
CREATE TABLE stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    product_id INT NOT NULL,
    movement_type ENUM('purchase', 'sale', 'adjustment', 'return', 'damaged', 'expired') NOT NULL,
    quantity_change INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    reference_id INT NULL, -- order_id or adjustment_id
    reference_type VARCHAR(50) NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_vendor_product_date (vendor_id, product_id, created_at),
    INDEX idx_movement_type (movement_type)
);

-- Enhanced Notification System
CREATE TABLE vendor_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    notification_type ENUM('order', 'stock', 'review', 'payment', 'system', 'promotion', 'security') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(500) NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    metadata JSON,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_vendor_unread (vendor_id, is_read, created_at),
    INDEX idx_notification_type (notification_type),
    INDEX idx_priority (priority)
);

-- Vendor Subscription/Tier Management
CREATE TABLE vendor_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    plan_type ENUM('basic', 'premium', 'enterprise') DEFAULT 'basic',
    features JSON,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    billing_cycle ENUM('monthly', 'yearly') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    status ENUM('active', 'expired', 'cancelled', 'suspended') DEFAULT 'active',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_vendor_status (vendor_id, status),
    INDEX idx_end_date (end_date)
);

-- Bulk Operations Log
CREATE TABLE bulk_operations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    operation_type ENUM('product_import', 'product_export', 'price_update', 'stock_update', 'status_update') NOT NULL,
    file_name VARCHAR(255) NULL,
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    success_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_log TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_vendor_status (vendor_id, status),
    INDEX idx_operation_type (operation_type)
);

-- Vendor API Tokens for mobile app integration
CREATE TABLE vendor_api_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    token_name VARCHAR(100) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    permissions JSON,
    last_used_at TIMESTAMP NULL,
    last_used_ip VARCHAR(45) NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token_hash (token_hash),
    INDEX idx_vendor_active (vendor_id, is_active)
);

-- Vendor Dashboard Settings
CREATE TABLE vendor_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value JSON,
    category ENUM('dashboard', 'notifications', 'security', 'integrations', 'appearance') DEFAULT 'dashboard',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vendor_setting (vendor_id, setting_key),
    INDEX idx_category (category)
);

-- Product Performance Metrics
CREATE TABLE product_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    product_id INT NOT NULL,
    date DATE NOT NULL,
    views INT DEFAULT 0,
    clicks INT DEFAULT 0,
    orders INT DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0.00,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    bounce_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_time_on_page INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_date (product_id, date),
    INDEX idx_vendor_date (vendor_id, date)
);

-- Enhanced Reviews with vendor responses
ALTER TABLE reviews ADD COLUMN vendor_response TEXT NULL;
ALTER TABLE reviews ADD COLUMN vendor_response_date TIMESTAMP NULL;
ALTER TABLE reviews ADD COLUMN vendor_response_by INT NULL;
ALTER TABLE reviews ADD FOREIGN KEY (vendor_response_by) REFERENCES users(id) ON DELETE SET NULL;

-- Enhanced Products table with SEO and advanced features
ALTER TABLE products ADD COLUMN seo_keywords TEXT NULL;
ALTER TABLE products ADD COLUMN video_url VARCHAR(500) NULL;
ALTER TABLE products ADD COLUMN is_digital BOOLEAN DEFAULT FALSE;
ALTER TABLE products ADD COLUMN download_file VARCHAR(255) NULL;
ALTER TABLE products ADD COLUMN license_key VARCHAR(100) NULL;
ALTER TABLE products ADD COLUMN warranty_period INT DEFAULT 0;
ALTER TABLE products ADD COLUMN return_policy TEXT NULL;
ALTER TABLE products ADD COLUMN shipping_class ENUM('standard', 'express', 'overnight', 'digital') DEFAULT 'standard';
ALTER TABLE products ADD COLUMN bulk_discount_min_qty INT DEFAULT 0;
ALTER TABLE products ADD COLUMN bulk_discount_percentage DECIMAL(5,2) DEFAULT 0.00;

-- Enhanced Orders for vendor-specific tracking
ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(100) NULL;
ALTER TABLE orders ADD COLUMN estimated_delivery_date DATE NULL;
ALTER TABLE orders ADD COLUMN delivery_instructions TEXT NULL;

-- Vendor Performance Metrics Summary
CREATE TABLE vendor_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    month_year VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    total_products INT DEFAULT 0,
    active_products INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    total_revenue DECIMAL(12,2) DEFAULT 0.00,
    avg_order_value DECIMAL(10,2) DEFAULT 0.00,
    customer_satisfaction DECIMAL(3,2) DEFAULT 0.00,
    response_time_hours DECIMAL(5,2) DEFAULT 0.00,
    return_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vendor_month (vendor_id, month_year),
    INDEX idx_month_year (month_year)
);

-- Indexes for better performance
CREATE INDEX idx_vendors_user_status ON vendors(user_id, application_date);
CREATE INDEX idx_products_vendor_status ON products(vendor_id, status, created_at);
CREATE INDEX idx_orders_status_date ON orders(status, created_at);
CREATE INDEX idx_order_items_vendor_date ON order_items(vendor_id, created_at);
CREATE INDEX idx_reviews_product_status ON reviews(product_id, status, created_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read, created_at);

-- Insert default vendor settings for existing vendors
INSERT IGNORE INTO vendor_settings (vendor_id, setting_key, setting_value, category)
SELECT 
    v.id,
    'dashboard_layout',
    JSON_OBJECT('theme', 'light', 'sidebar_collapsed', false, 'show_tips', true),
    'dashboard'
FROM vendors v;

INSERT IGNORE INTO vendor_settings (vendor_id, setting_key, setting_value, category)
SELECT 
    v.id,
    'notification_preferences',
    JSON_OBJECT('email_orders', true, 'email_reviews', true, 'email_stock_alerts', true, 'push_notifications', true),
    'notifications'
FROM vendors v;

-- Create stored procedures for analytics
DELIMITER $$

CREATE PROCEDURE UpdateVendorDailyAnalytics(IN vendor_id_param INT, IN date_param DATE)
BEGIN
    DECLARE total_orders INT DEFAULT 0;
    DECLARE total_revenue DECIMAL(12,2) DEFAULT 0.00;
    DECLARE unique_customers INT DEFAULT 0;
    
    -- Calculate daily metrics
    SELECT COUNT(DISTINCT oi.order_id), COALESCE(SUM(oi.total), 0)
    INTO total_orders, total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.vendor_id = vendor_id_param 
    AND DATE(o.created_at) = date_param
    AND o.payment_status = 'paid';
    
    -- Insert or update analytics
    INSERT INTO vendor_analytics (vendor_id, date, orders_count, revenue)
    VALUES (vendor_id_param, date_param, total_orders, total_revenue)
    ON DUPLICATE KEY UPDATE
        orders_count = total_orders,
        revenue = total_revenue,
        updated_at = CURRENT_TIMESTAMP;
END$$

CREATE PROCEDURE UpdateProductPerformance(IN product_id_param INT, IN date_param DATE)
BEGIN
    DECLARE vendor_id_val INT;
    
    SELECT vendor_id INTO vendor_id_val FROM products WHERE id = product_id_param;
    
    INSERT IGNORE INTO product_performance (vendor_id, product_id, date)
    VALUES (vendor_id_val, product_id_param, date_param);
END$$

DELIMITER ;
