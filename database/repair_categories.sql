-- Repairs the specific error: Table 'pawmart.categories' doesn't exist.
-- Safe to import more than once; it does not drop or replace existing data.
CREATE DATABASE IF NOT EXISTS pawmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pawmart;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (name)
VALUES ('Food'), ('Toys'), ('Accessories'), ('Health & Care');
