-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2025 at 12:57 PM
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
-- Database: `prestige_perfumery`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `date_added` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Eau de Parfum'),
(2, 'Eau de Toilette'),
(3, 'Perfume Oil');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `full_name`, `contact_number`, `address`, `email`, `password`) VALUES
(1, 'nico barredo', '09930536452', 'taguig city', 'nicobarredo87@gmail.com', '$2y$10$sdOo4FqFO2YX3a3DSZKRD.TUFnvuZLVRxNx3So2IpyXMTv.4N2R/a');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `order_id`, `customer_id`, `message`, `is_read`, `date_created`) VALUES
(1, 1, 1, 'Order #1 has been placed successfully!', 1, '2025-11-01 13:51:15'),
(2, 2, 1, 'Order #2 has been placed successfully!', 1, '2025-11-01 13:53:26'),
(3, 2, 1, 'Order #2 status updated to \'On the Way\'.', 1, '2025-11-01 14:42:30'),
(4, 1, 1, 'Order #1 status updated to \'On the Way\'.', 1, '2025-11-01 14:42:35'),
(5, 3, 1, 'Order #3 has been placed successfully!', 1, '2025-11-01 14:43:18'),
(6, 2, 1, 'Order #2 status updated to \'Received\'.', 1, '2025-11-01 14:43:47'),
(7, 1, 1, 'Order #1 status updated to \'Received\'.', 1, '2025-11-01 14:54:22'),
(8, 4, 1, 'Order #4 has been placed successfully!', 1, '2025-11-01 17:25:06'),
(9, 5, 1, 'Order #5 has been placed successfully!', 1, '2025-11-01 17:39:17'),
(10, 5, 1, 'Order #5 status updated to \'On the Way\'.', 1, '2025-11-01 17:40:08'),
(11, 5, 1, 'Order #5 status updated to \'Received\'.', 1, '2025-11-01 18:15:21'),
(12, 6, 1, 'Order #6 has been placed successfully!', 1, '2025-11-01 19:18:56'),
(13, 6, 1, 'Order #6 status updated to \'Cancelled\'.', 1, '2025-11-01 19:19:37'),
(14, 7, 1, 'Order #7 has been placed successfully!', 1, '2025-11-01 19:20:31'),
(15, 8, 1, 'Order #8 has been placed successfully!', 1, '2025-11-01 19:27:18'),
(16, 8, 1, 'Order #8 status updated to \'Cancelled\'.', 1, '2025-11-01 19:29:04'),
(17, 9, 1, 'Order #9 has been placed successfully!', 1, '2025-11-01 19:34:29'),
(18, 10, 1, 'Order #10 has been placed successfully!', 1, '2025-11-01 19:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `order_status` varchar(20) DEFAULT 'Pending',
  `date_received` datetime DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'COD',
  `payment_reference` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `order_date`, `order_status`, `date_received`, `delivery_address`, `payment_method`, `payment_reference`) VALUES
(1, 1, '2025-11-01 13:51:15', 'Received', NULL, 'taguig city', 'Cash on Delivery', NULL),
(2, 1, '2025-11-01 13:53:26', 'Received', NULL, 'taguig city', 'Cash on Delivery', NULL),
(3, 1, '2025-11-01 14:43:18', 'Cancelled', NULL, 'taguig city', 'Cash on Delivery', NULL),
(4, 1, '2025-11-01 17:25:06', 'Cancelled', NULL, 'taguig city', 'Cash on Delivery', NULL),
(5, 1, '2025-11-01 17:39:17', 'Received', NULL, 'taguig city', 'Cash on Delivery', NULL),
(6, 1, '2025-11-01 19:18:56', 'Cancelled', NULL, 'taguig city', 'Cash on Delivery', NULL),
(7, 1, '2025-11-01 19:20:31', 'Cancelled', NULL, 'taguig city', 'Cash on Delivery', NULL),
(8, 1, '2025-11-01 19:27:18', 'Cancelled', NULL, 'taguig city', 'Cash on Delivery', NULL),
(9, 1, '2025-11-01 19:34:29', 'Cancelled', NULL, 'taguig city', 'Cash on Delivery', NULL),
(10, 1, '2025-11-01 19:36:24', 'Cancelled', NULL, 'taguig city', 'Cash on Delivery', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `order_detail_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`order_detail_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 11, 2, 2000.00),
(2, 1, 13, 1, 2900.00),
(4, 2, 17, 1, 7000.00),
(5, 3, 12, 1, 3500.00),
(6, 3, 14, 1, 3000.00),
(7, 3, 15, 1, 9500.00),
(8, 4, 8, 1, 2500.00),
(9, 4, 9, 1, 3200.00),
(10, 4, 12, 1, 3500.00),
(11, 5, 9, 1, 3200.00),
(12, 5, 12, 1, 3500.00),
(13, 5, 13, 1, 2900.00),
(14, 6, 17, 10, 7000.00),
(15, 7, 17, 5, 7000.00),
(16, 8, 14, 4, 3000.00),
(17, 9, 14, 4, 3000.00),
(18, 10, 11, 8, 2000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `variant` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `description`, `category_id`, `supplier_id`, `price`, `stock_quantity`, `image_path`, `variant`, `is_active`) VALUES
(8, 'Eclat', 'Fresh and elegant scent with citrus and floral notes.', 2, 1, 2500.00, 49, 'assets/images/perfumes/Eclat.png', '50ml', 1),
(9, 'Elixir', 'A luxurious fragrance with woody and oriental notes.', 1, 2, 3200.00, 38, 'assets/images/perfumes/Elixir.png', '50ml', 1),
(10, 'Noir', 'Intense and mysterious fragrance with dark accords.', 2, 3, 2800.00, 30, 'assets/images/perfumes/Noir.png', 'EDT', 1),
(11, 'Primus', 'Eternal Bloom is a captivating floral-oriental fragrance that awakens the senses with a luminous bouquet of fresh jasmine, delicate rose, and velvety peony. Hints of sweet pear and zesty bergamot add a sparkling freshness, while warm notes of vanilla, amber, and soft musk linger on the skin, creating an aura of elegance and sophistication. Perfect for those who want to leave a lasting impression, this perfume embodies timeless beauty and subtle allure.', 1, 4, 2000.00, 58, 'assets/images/perfumes/Primus.png', 'EDT', 1),
(12, 'Quartus', 'Bold and modern scent with spicy undertones.', 1, 5, 3500.00, 17, 'assets/images/perfumes/Quartus.png', 'EDP', 1),
(13, 'Quintus', 'Soft and romantic fragrance with floral notes.', 2, 6, 2900.00, 33, 'assets/images/perfumes/Quintus.png', '50ml', 1),
(14, 'Sucundus', 'Classic fragrance with woody and citrus blend.', 1, 7, 3000.00, 24, 'assets/images/perfumes/Sucundus.png', '50ml', 1),
(15, 'Zenith', 'WDSF FEFAD WFWF DSF SEF', 1, 1, 9500.00, 15, 'assets/images/perfumes/perfume_69048ff8b306a4.05205285.png', '50ml', 1),
(17, 'Odyssey', 'an enigmatic scent designed for the modern individual who commands attention. It opens with a crisp, magnetic freshness that transitions into a heart of rich, deep aromatic notes. The complex, woody base provides a long-lasting, sophisticated trail that speaks to unwavering confidence. Wearing PRESTIGE is more than just applying a fragrance; it\'s an affirmation of elegant power and refined taste.', 1, 9, 7000.00, 5, 'assets/images/perfumes/perfume_6904c78b1a1130.61554590.png', '50ml', 1);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `contact_number`, `address`, `is_active`) VALUES
(1, 'AromaLux Co.', 'Maria Santos', '09171234567', '123 Market Street, Manila', 1),
(2, 'Essence World', 'Juan Dela Cruz', '09281234567', '45 Aromatic Ave, Quezon City', 1),
(3, 'Perfume Masters', 'Ana Lopez', '09391234567', '78 Fragrance Blvd, Makati', 1),
(4, 'Scentopia', 'Carlos Reyes', '09451234567', '90 Perfume Rd, Taguig', 1),
(5, 'Luxury Scents Inc.', 'Isabel Gomez', '09561234567', '12 Elite St, Pasig', 1),
(6, 'Fragrance Hub', 'Ricardo Tan', '09671234567', '34 Aroma Lane, Mandaluyong', 1),
(7, 'Elite Perfumeries', 'Luz Villanueva', '09781234567', '56 Scent Plaza, Manila', 1),
(8, 'Divine Essence', 'Roberto Cruz', '09891234567', '67 Perfume Street, Quezon City', 1),
(9, 'Aromatic Touch', 'Sofia Ramos', '09901234567', '89 Fragrance Blvd, Makati', 1),
(10, 'Noble Scents', 'Miguel Fernandez', '09181234567', '101 Aroma Avenue, Taguig', 1);

-- --------------------------------------------------------

--
-- Table structure for table `supply_logs`
--

CREATE TABLE `supply_logs` (
  `supply_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `quantity_added` int(11) NOT NULL,
  `quantity_remaining` int(11) DEFAULT 0,
  `supplier_price` decimal(10,2) DEFAULT NULL,
  `supply_date` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supply_logs`
--

INSERT INTO `supply_logs` (`supply_id`, `product_id`, `supplier_id`, `quantity_added`, `quantity_remaining`, `supplier_price`, `supply_date`, `remarks`) VALUES
(1, 15, 1, 10, 10, 7500.00, '2025-10-31 00:00:00', 'First Supplies'),
(3, 17, 9, 10, 10, 6500.00, '2025-10-31 00:00:00', 'First Supplies'),
(5, 17, 9, 1, 10, 6500.00, '2025-11-01 00:00:00', 'Stock adjustment through product edit'),
(6, 15, 1, 1, 10, 9000.00, '2025-11-01 00:00:00', 'Stock adjustment through product edit'),
(7, 17, 9, 5, 15, NULL, '2025-11-01 18:11:54', 'Stock added via admin adjustment'),
(8, 15, 1, 5, 15, NULL, '2025-11-01 18:13:32', 'Stock added via admin adjustment'),
(9, 17, 9, 5, 20, 6500.00, '2025-11-01 19:05:36', 'Stock added via admin adjustment');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`) VALUES
(1, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_expenses_report`
-- (See below for the actual view)
--
CREATE TABLE `v_expenses_report` (
`supply_id` int(11)
,`product_name` varchar(100)
,`supplier_name` varchar(100)
,`quantity_added` int(11)
,`supplier_price` decimal(10,2)
,`total_expense` decimal(20,2)
,`supply_date` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_sales_report`
-- (See below for the actual view)
--
CREATE TABLE `v_sales_report` (
`order_id` int(11)
,`customer` varchar(100)
,`total_amount` decimal(42,2)
,`order_status` varchar(20)
,`payment_method` varchar(50)
,`order_date` datetime
);

-- --------------------------------------------------------

--
-- Structure for view `v_expenses_report`
--
DROP TABLE IF EXISTS `v_expenses_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_expenses_report`  AS SELECT `sl`.`supply_id` AS `supply_id`, `p`.`product_name` AS `product_name`, `s`.`supplier_name` AS `supplier_name`, `sl`.`quantity_added` AS `quantity_added`, `sl`.`supplier_price` AS `supplier_price`, `sl`.`quantity_added`* `sl`.`supplier_price` AS `total_expense`, `sl`.`supply_date` AS `supply_date` FROM ((`supply_logs` `sl` join `products` `p` on(`sl`.`product_id` = `p`.`product_id`)) join `suppliers` `s` on(`sl`.`supplier_id` = `s`.`supplier_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_sales_report`
--
DROP TABLE IF EXISTS `v_sales_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_sales_report`  AS SELECT `o`.`order_id` AS `order_id`, `c`.`full_name` AS `customer`, sum(`od`.`quantity` * `od`.`unit_price`) AS `total_amount`, `o`.`order_status` AS `order_status`, `o`.`payment_method` AS `payment_method`, `o`.`order_date` AS `order_date` FROM ((`orders` `o` join `customers` `c` on(`o`.`customer_id` = `c`.`customer_id`)) join `order_details` `od` on(`o`.`order_id` = `od`.`order_id`)) GROUP BY `o`.`order_id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`customer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`order_detail_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD UNIQUE KEY `supplier_name` (`supplier_name`);

--
-- Indexes for table `supply_logs`
--
ALTER TABLE `supply_logs`
  ADD PRIMARY KEY (`supply_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `order_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `supply_logs`
--
ALTER TABLE `supply_logs`
  MODIFY `supply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `supply_logs`
--
ALTER TABLE `supply_logs`
  ADD CONSTRAINT `supply_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supply_logs_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
