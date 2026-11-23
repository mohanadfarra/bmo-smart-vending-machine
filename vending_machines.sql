-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 23 avr. 2025 à 21:10
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `vending_machines`
--

-- --------------------------------------------------------

--
-- Structure de la table `managers`
--

CREATE TABLE `managers` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `role` enum('admin','manager') NOT NULL DEFAULT 'manager',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `managers`
--

INSERT INTO `managers` (`id`, `username`, `password_hash`, `email`, `last_login`, `role`, `created_at`) VALUES
(1, 'admin@gmail.com', '$2y$10$RkdYvRcgyI8SVHex6S7CDOrDfI4/g2AaH85Ne3ULRF3Vj3PY1NDUu', 'admin@example.com', '2025-04-23 17:18:24', 'admin', '2025-02-10 21:56:21');

-- --------------------------------------------------------

--
-- Structure de la table `manager_settings`
--

CREATE TABLE `manager_settings` (
  `id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `notify_low_stock` tinyint(1) DEFAULT 1,
  `notify_new_orders` tinyint(1) DEFAULT 1,
  `notify_maintenance` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_tnd` decimal(10,3) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `min_stock_alert` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_restock` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `machine_id`, `name`, `price_tnd`, `quantity`, `image_url`, `active`, `min_stock_alert`, `created_at`, `last_restock`) VALUES
(1, 1, 'Water', 1.000, 14, 'uploads/products/product_67bba934c9de8.jpg', 1, 5, '2025-02-10 23:34:22', '2025-04-09 17:25:54'),
(2, 1, 'Protein bar', 2.000, 14, 'uploads/products/product_67bb9a46cfaa6.jpg', 1, 4, '2025-02-23 21:59:34', '2025-04-09 17:25:47'),
(3, 1, 'Chips', 1.500, 15, 'uploads/products/product_67bbaa4bc0f8d.jpg', 1, 4, '2025-02-23 23:05:12', '2025-04-09 17:25:43'),
(4, 1, 'Soda', 3.000, 14, 'uploads/products/product_67bbaa114cd81.jpg', 1, 5, '2025-02-23 23:06:57', '2025-04-09 17:25:50');

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `total_amount` decimal(10,3) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'pending',
  `unique_code` varchar(10) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `payment_method` varchar(20) DEFAULT 'online',
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores transaction data. Payment methods: "online" (pay now), "machine" (pay at machine)';

--
-- Déchargement des données de la table `transactions`
--

INSERT INTO `transactions` (`id`, `machine_id`, `session_id`, `total_amount`, `status`, `unique_code`, `items`, `payment_method`, `customer_name`, `customer_phone`, `created_at`) VALUES
(1, 1, 'sess_67bddd2ae2a00', 1.500, 'failed', 'TJVCXX', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'online', NULL, NULL, '2025-02-25 15:09:30'),
(2, 1, 'sess_67bdef752cfed', 4.500, 'completed', 'HLKOW4', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"},{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'online', NULL, NULL, '2025-02-25 16:27:33'),
(3, 1, 'sess_67bdf4fce12e9', 3.500, 'completed', 'PY38Q0', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"},{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'online', NULL, NULL, '2025-02-25 16:51:08'),
(4, 1, 'sess_67be0bc432456', 12.500, 'completed', '629NO8', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":3,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"},{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":2,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"},{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"},{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'online', NULL, NULL, '2025-02-25 18:28:20'),
(5, 1, 'sess_67bf58574df0a', 7.000, 'completed', 'XW84VO', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":2,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"},{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'online', NULL, NULL, '2025-02-26 18:07:19'),
(6, 1, 'sess_67d20e972fae6', 4.500, 'completed', 'L4WDT8', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"},{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'online', NULL, NULL, '2025-03-12 22:45:43'),
(7, 1, 'sess_67d8915138f1e', 3.000, 'completed', 'ETNPLE', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":2,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'online', NULL, NULL, '2025-03-17 21:17:05'),
(8, 1, 'sess_67d97daf33218', 1.000, 'pending', '7RJ8ZP', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'online', NULL, NULL, '2025-03-18 14:05:35'),
(9, 1, 'sess_67d98451254ba', 1.000, 'pending', '939562', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'online', NULL, NULL, '2025-03-18 14:33:53'),
(10, 1, 'sess_67d98497df258', 1.000, 'pending', '028313', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'online', NULL, NULL, '2025-03-18 14:35:03'),
(11, 1, 'sess_67e1f7002ba75', 6.000, 'pending', '668550', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"},{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"},{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'online', NULL, NULL, '2025-03-25 00:21:20'),
(12, 1, 'sess_67e74fcaecad6', 1.500, 'pending', '1187JH', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'online', 'gggg', '777777777777777777', '2025-03-29 01:41:30'),
(13, 1, 'sess_67e74ff3a902b', 6.000, '', '8H8MGR', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":2,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 01:42:11'),
(14, 1, 'sess_67e75002335f1', 3.000, '', 'E9SFM3', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 01:42:26'),
(15, 1, 'sess_67e7514c87922', 3.000, '', '069319', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 01:47:56'),
(16, 1, 'sess_67e7516a14ea5', 2.000, 'pending', '490231', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'online', 'pmpmpmpm', '7777777777777777777', '2025-03-29 01:48:26'),
(17, 1, 'sess_67e753aa02df2', 1.000, '', '552116', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 01:58:02'),
(18, 1, 'sess_67e753fdd506b', 1.000, '', '763001', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 01:59:25'),
(19, 1, 'sess_67e754f68eb64', 1.000, '', '093535', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 02:03:34'),
(20, 1, 'sess_67e755ba33eb0', 1.500, '', '250626', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 02:06:50'),
(21, 1, 'sess_67e755cd1197c', 1.500, 'completed', '204565', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'online', 'mmmm', '4444444444444', '2025-03-29 02:07:09'),
(22, 1, 'sess_67e7585b7694c', 1.000, 'awaiting_payment', '979242', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 02:18:03'),
(23, 1, 'sess_67e75a729fdf3', 3.000, 'completed', '002667', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 02:26:58'),
(24, 1, 'sess_67e75add5d8fe', 3.000, 'awaiting_payment', '993294', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 02:28:45'),
(25, 1, 'sess_67e75c50418f0', 2.000, 'awaiting_payment', '318328', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-29 02:34:56'),
(26, 1, 'sess_67e75c601ae1c', 2.000, 'pending', '285360', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'online', 'mmml', '44444444444444444444', '2025-03-29 02:35:12'),
(27, 1, 'sess_67e75fd0d402a', 3.000, 'awaiting_payment', '961191', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-29 02:49:52'),
(28, 1, 'sess_67e75ff53759c', 3.000, 'awaiting_payment', '387084', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-29 02:50:29'),
(29, 1, 'sess_67e7604981b69', 3.000, 'completed', '619970', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'gggggg', '77777777777777', '2025-03-29 02:51:53'),
(30, 1, 'sess_67e890cdf0376', 1.000, 'awaiting_payment', '895922', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'hhh', '444444444', '2025-03-30 00:31:09'),
(31, 1, 'sess_67e8918d50df2', 3.000, 'awaiting_payment', '451959', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-30 00:34:21'),
(32, 1, 'sess_67e891b1064ca', 1.500, 'pending', '956961', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'online', 'gggg', '77777777777777', '2025-03-30 00:34:57'),
(33, 1, 'sess_67e893cd6c74b', 1.500, 'awaiting_payment', '071484', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-30 00:43:57'),
(34, 1, 'sess_67e8964f1fd35', 1.000, 'awaiting_payment', '246429', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-30 00:54:39'),
(35, 1, 'sess_67e896573a65b', 1.000, 'awaiting_payment', '790516', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-30 00:54:47'),
(36, 1, 'sess_67e8965f72042', 1.000, 'awaiting_payment', '303713', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-30 00:54:55'),
(37, 1, 'sess_67e896863d71a', 2.000, 'pending', '604988', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'online', 'gggg', '77777777777777', '2025-03-30 00:55:34'),
(38, 1, 'sess_67e896bfdd44c', 2.000, 'awaiting_payment', '949730', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-30 00:56:31'),
(39, 1, 'sess_67e896c97fa95', 3.000, 'awaiting_payment', '661810', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-30 00:56:41'),
(40, 1, 'sess_67e896e752d1f', 3.000, 'pending', '971059', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'online', 'gggg', '77777777777777', '2025-03-30 00:57:11'),
(41, 1, 'sess_67e8973dcf724', 4.000, 'awaiting_payment', '478845', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"},{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'Card Payment at Machine', 'N/A', '2025-03-30 00:58:37'),
(42, 1, 'sess_67e897767a082', 1.000, 'awaiting_payment', '037044', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-30 00:59:34'),
(43, 1, 'sess_67e89887d6f7b', 2.000, 'awaiting_payment', '837533', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-30 01:04:07'),
(44, 1, 'sess_67e898991835b', 1.000, 'awaiting_payment', '292724', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-30 01:04:25'),
(45, 1, 'sess_67e898b4f3aea', 3.000, 'pending', '044619', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'online', 'gggg', '77777777777777', '2025-03-30 01:04:52'),
(46, 1, 'sess_67e899a87af37', 2.000, 'awaiting_payment', '530396', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-30 01:08:56'),
(47, 1, 'sess_67e899bd8589e', 3.000, 'pending', '122966', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'online', 'gggg', '77777777777777', '2025-03-30 01:09:17'),
(48, 1, 'sess_67e89a2b1a6c3', 3.000, 'pending', '108932', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'online', 'gggg', '77777777777777', '2025-03-30 01:11:07'),
(49, 1, 'sess_67e89aace1c1c', 1.000, 'pending', '211324', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'online', 'gggg', '77777777777777', '2025-03-30 01:13:16'),
(50, 1, 'sess_67e89abf913be', 1.000, 'completed', '596030', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'machine', 'gggggg', '77777777777777', '2025-03-30 01:13:35'),
(51, 1, 'sess_67e978a5a3070', 1.500, 'awaiting_payment', '311983', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'machine', 'gggg', '77777777777777', '2025-03-30 17:00:21'),
(52, 1, 'sess_67e978b7a1a2d', 1.500, 'pending', '954365', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'online', 'gggg', '77777777777777', '2025-03-30 17:00:39'),
(53, 1, 'sess_67eed9ffc1c4a', 1.500, 'awaiting_payment', '572635', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"}]', 'machine', 'Ahmed Said', '54 356 224', '2025-04-03 18:57:03'),
(54, 1, 'sess_67eeda3956cdb', 3.500, 'awaiting_payment', '358521', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"},{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'machine', 'Ahmed Said', '54 356 224', '2025-04-03 18:58:01'),
(55, 1, 'sess_67f69e9c0ac3f', 4.500, 'awaiting_payment', '247335', '[{\"id\":3,\"name\":\"Chips\",\"price_tnd\":1.5,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa4bc0f8d.jpg\"},{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'Ahmed Said', '54 356 224', '2025-04-09 16:21:48'),
(56, 1, 'sess_67f69ef6bcc61', 1.000, 'completed', '601946', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'online', 'Ahmed Said', '54 356 224', '2025-04-09 16:23:18'),
(57, 1, 'sess_67fa8b539797b', 1.000, 'completed', '638175', '[{\"id\":1,\"name\":\"Water\",\"price_tnd\":1,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bba934c9de8.jpg\"}]', 'online', 'Ahmed Said', '54 356 224', '2025-04-12 15:48:35'),
(58, 1, 'sess_67fa8cbfa9055', 2.000, 'completed', '484898', '[{\"id\":2,\"name\":\"Protein bar\",\"price_tnd\":2,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bb9a46cfaa6.jpg\"}]', 'online', 'youssef zaim', '24 758 962', '2025-04-12 15:54:39'),
(59, 1, 'sess_67fa8d5c199f9', 3.000, 'completed', '764213', '[{\"id\":4,\"name\":\"Soda\",\"price_tnd\":3,\"quantity\":1,\"image_url\":\"uploads\\/products\\/product_67bbaa114cd81.jpg\"}]', 'machine', 'semi lasmer', '98 555 001', '2025-04-12 15:57:16');

-- --------------------------------------------------------

--
-- Structure de la table `vending_machines`
--

CREATE TABLE `vending_machines` (
  `id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `last_maintenance` datetime DEFAULT NULL,
  `last_restock` datetime DEFAULT NULL,
  `total_revenue` decimal(10,3) DEFAULT 0.000,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vending_machines`
--

INSERT INTO `vending_machines` (`id`, `location`, `qr_code`, `status`, `last_maintenance`, `last_restock`, `total_revenue`, `manager_id`, `created_at`) VALUES
(1, 'Lac 2', 'VM_67aa8cc995030', 'active', NULL, '2025-04-09 17:25:54', 0.000, 1, '2025-02-10 23:33:29'),
(2, 'Ennaser', 'VM_67bb7e7da72bb', 'inactive', NULL, NULL, 0.000, 1, '2025-02-23 20:01:01'),
(3, 'La Marsa', 'VM_67bb9995eb574', 'maintenance', NULL, NULL, 0.000, 1, '2025-02-23 21:56:37');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `managers`
--
ALTER TABLE `managers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `manager_settings`
--
ALTER TABLE `manager_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_machine` (`machine_id`);

--
-- Index pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code` (`unique_code`),
  ADD KEY `idx_transaction_machine` (`machine_id`),
  ADD KEY `idx_transaction_status` (`status`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_unique_code_created` (`unique_code`,`created_at`);

--
-- Index pour la table `vending_machines`
--
ALTER TABLE `vending_machines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `manager_id` (`manager_id`),
  ADD KEY `idx_machine_status` (`status`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `managers`
--
ALTER TABLE `managers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `manager_settings`
--
ALTER TABLE `manager_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT pour la table `vending_machines`
--
ALTER TABLE `vending_machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `manager_settings`
--
ALTER TABLE `manager_settings`
  ADD CONSTRAINT `manager_settings_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`);

--
-- Contraintes pour la table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `vending_machines` (`id`);

--
-- Contraintes pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `vending_machines` (`id`);

--
-- Contraintes pour la table `vending_machines`
--
ALTER TABLE `vending_machines`
  ADD CONSTRAINT `vending_machines_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `managers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
