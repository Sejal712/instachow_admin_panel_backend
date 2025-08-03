-- Create database if not exists
CREATE DATABASE IF NOT EXISTS instachow;
USE instachow;

-- Create master_food_categories table
CREATE TABLE IF NOT EXISTS master_food_categories (
    maste_cat_food_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO master_food_categories (name, icon_url) VALUES 
('Fast Food', 'https://example.com/icons/fast-food.png'),
('Beverages', 'https://example.com/icons/beverages.png'),
('Desserts', 'https://example.com/icons/desserts.png'),
('Pizza', 'https://example.com/icons/pizza.png'),
('Chinese', 'https://example.com/icons/chinese.png');
