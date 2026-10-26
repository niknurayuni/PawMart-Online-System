-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2026 at 06:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pawmart`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `category`, `description`, `price`, `stock_quantity`) VALUES
(1, 'Adult Cat Dry Food 1 kg', 'Pet Food', 'Dry food for adult cats', 25.90, 30),
(2, 'Adult Dog Dry Food 2 kg', 'Pet Food', 'Dry food for adult dogs', 45.00, 20),
(3, 'Tuna Wet Cat Food 85 g', 'Pet Food', 'Tuna-flavoured wet cat food', 3.50, 60),
(4, 'Chicken Dog Treats 100 g', 'Pet Treats', 'Chicken-flavoured dog treats', 12.90, 25),
(5, 'Cat Teaser Wand', 'Pet Toys', 'Interactive wand toy for cats', 8.90, 35),
(6, 'Rubber Dog Ball', 'Pet Toys', 'Rubber ball for supervised play', 10.50, 30),
(7, 'Adjustable Pet Collar', 'Accessories', 'Adjustable collar for pets', 15.90, 20),
(8, 'Dog Walking Leash', 'Accessories', 'Leash for daily walks', 19.90, 15),
(9, 'Stainless Steel Feeding Bowl', 'Feeding Supplies', 'Reusable bowl for food or water', 14.50, 25),
(10, 'Pet Shampoo 250 ml', 'Grooming Supplies', 'Shampoo designed for pets', 22.90, 18),
(11, 'Pet Grooming Brush', 'Grooming Supplies', 'Brush for routine coat grooming', 13.90, 22),
(12, 'Pet Carrier', 'Travel Supplies', 'Carrier for transporting small pets', 65.00, 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
