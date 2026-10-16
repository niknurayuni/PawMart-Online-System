-- Import once into MySQL. Existing tables are not deleted.
CREATE DATABASE IF NOT EXISTS pawmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pawmart;
CREATE TABLE IF NOT EXISTS products (
 product_id INT AUTO_INCREMENT PRIMARY KEY,
 product_name VARCHAR(100) NOT NULL,
 category VARCHAR(50) NOT NULL,
 description TEXT,
 price DECIMAL(10,2) NOT NULL,
 stock_quantity INT NOT NULL DEFAULT 0
);
-- Fictional sample products/prices. Re-importing adds another sample set.
INSERT INTO products (product_name,category,description,price,stock_quantity) VALUES
('Adult Cat Dry Food 1 kg','Pet Food','Dry food for adult cats',25.90,30),
('Adult Dog Dry Food 2 kg','Pet Food','Dry food for adult dogs',45.00,20),
('Tuna Wet Cat Food 85 g','Pet Food','Tuna-flavoured wet cat food',3.50,60),
('Chicken Dog Treats 100 g','Pet Treats','Chicken-flavoured dog treats',12.90,25),
('Cat Teaser Wand','Pet Toys','Interactive wand toy for cats',8.90,35),
('Rubber Dog Ball','Pet Toys','Rubber ball for supervised play',10.50,30),
('Adjustable Pet Collar','Accessories','Adjustable collar for pets',15.90,20),
('Dog Walking Leash','Accessories','Leash for daily walks',19.90,15),
('Stainless Steel Feeding Bowl','Feeding Supplies','Reusable bowl for food or water',14.50,25),
('Pet Shampoo 250 ml','Grooming Supplies','Shampoo designed for pets',22.90,18),
('Pet Grooming Brush','Grooming Supplies','Brush for routine coat grooming',13.90,22),
('Pet Carrier','Travel Supplies','Carrier for transporting small pets',65.00,10);
