-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2026 at 04:59 AM
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
-- Database: `thltw_sonphuong_24f`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(100) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `address_detail` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `label`, `recipient_name`, `phone`, `province`, `district`, `ward`, `address_detail`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, 'Legacy', 'Nguyễn Văn An', '0901234567', NULL, NULL, NULL, '123 Lê Văn Việt, TP. Thủ Đức, TP.HCM', 1, '2026-10-05 08:33:36', '2026-10-05 08:33:36'),
(2, 2, 'Legacy', 'Trần Thị Bích', '0912345678', NULL, NULL, NULL, '456 Nguyễn Trãi, Quận 5, TP.HCM', 1, '2026-10-05 08:33:36', '2026-10-05 08:33:36'),
(3, 3, 'Legacy', 'Lê Hoàng Cường', '0923456789', NULL, NULL, NULL, '789 Điện Biên Phủ, Quận 3, TP.HCM', 1, '2026-10-05 08:33:36', '2026-10-05 08:33:36'),
(4, 4, 'Legacy', 'Phạm Thị Dung', '0934567890', NULL, NULL, NULL, '321 Cách Mạng Tháng 8, Quận 10, TP.HCM', 1, '2026-10-05 08:33:36', '2026-10-05 08:33:36'),
(5, 5, 'Legacy', 'Hoàng Văn Em', '0945678901', NULL, NULL, NULL, '654 Xô Viết Nghệ Tĩnh, Bình Thạnh, TP.HCM', 1, '2026-10-05 08:33:36', '2026-10-05 08:33:36'),
(6, 6, 'Legacy', 'Phạm Văn Tân', '0988111222', NULL, NULL, NULL, '12 Nguyễn Huệ, Quận 1, TP.HCM', 1, '2026-10-05 01:37:40', '2026-10-05 01:37:40'),
(7, 7, 'Legacy', 'Nguyễn Văn Test', '0911223344', NULL, NULL, NULL, 'Quận 1, TP.HCM', 1, '2026-10-05 07:16:41', '2026-10-05 07:16:41'),
(8, 12, 'az 123', 'hai', '0123456789', 'thủ đức', 'thủ đức', 'bình thọ', 'ml 123', 1, '2026-10-24 08:31:26', '2026-10-24 08:31:26'),
(9, 11, '12 tăng nhơn phú', 'phương', '7788112233', 'tptd', 'tnp', 'tnp', 'ok', 1, '2026-10-26 04:07:53', '2026-10-26 04:07:53'),
(10, 13, '123 dbv', 'dsd', '11312313', '21', '212', '122', 'ê2', 1, '2026-11-02 20:41:10', '2026-11-02 20:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `link_url` varchar(2048) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `description`, `image`, `link_url`, `button_text`, `status`, `sort_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'SỐNG THEO PHONG CÁCH CỦA CHÍNH BẠN', NULL, 'banners/ij863jopJ7ooLliaFq66pgxvxf4da5qQjQaDlB9W.jpg', '/products', 'KHÁM PHÁ', 'active', 2, '2026-11-01 23:48:22', '2026-11-01 23:51:46', NULL),
(2, 'PHONG CÁCH TẠO NÊN THƯƠNG HIỆU', NULL, 'banners/54TByvxNHELD8YfyEZqu11n9PKYfAgorY1j6S1yj.jpg', '/products?category', 'Xem Danh Mục', 'active', 1, '2026-11-01 23:51:40', '2026-11-01 23:52:32', NULL),
(3, 'MỖI NGÀY 1 PHONG CÁCH', NULL, 'banners/xsfg7vieH3kkrsfzICsIdVghIQaXl0CETfstm8RW.jpg', '/products', 'XEM SẢN PHẨM', 'active', 3, '2026-11-01 23:53:57', '2026-11-01 23:53:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `status`, `description`, `logo`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Nike', 'nike', 'active', 'Thương hiệu thời trang thể thao hàng đầu thế giới', 'brands/nike.png', NULL, NULL, NULL),
(2, 'Adidas', 'adidas', 'active', 'Thương hiệu giày và trang phục thể thao từ Đức', 'brands/adidas.png', NULL, NULL, NULL),
(3, 'Uniqlo', 'uniqlo', 'active', 'Thương hiệu thời trang tối giản và tiện ích từ Nhật Bản', 'brands/uniqlo.png', NULL, NULL, NULL),
(4, 'Apple', 'apple', 'active', 'Thương hiệu công nghệ hàng đầu với dòng sản phẩm cao cấp', 'brands/apple.png', NULL, NULL, NULL),
(5, 'Samsung', 'samsung', 'active', 'Tập đoàn công nghệ đa quốc gia sản xuất thiết bị điện tử', 'brands/samsung.png', NULL, NULL, NULL),
(6, 'Zara', 'zara', 'active', NULL, NULL, '2026-10-10 05:52:01', '2026-10-10 05:52:01', NULL),
(7, 'H&M', 'hm', 'active', NULL, NULL, '2026-10-10 05:52:01', '2026-10-10 05:52:01', NULL),
(9, '5the', '5the', 'active', 'ngan', '5232', '2026-10-14 06:05:47', '2026-10-14 06:05:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 12, '2026-10-24 08:30:14', '2026-10-24 08:30:14'),
(2, 11, '2026-10-26 04:06:59', '2026-10-26 04:06:59'),
(3, 9, '2026-10-26 05:52:29', '2026-10-26 05:52:29'),
(4, 13, '2026-11-02 20:35:53', '2026-11-02 20:35:53');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `product_variant_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_variant_id`, `quantity`, `created_at`, `updated_at`) VALUES
(4, 3, 18, 1, '2026-10-26 05:52:29', '2026-10-26 05:52:29');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `status`, `image`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, NULL, 'Quần Jean', 'quan-jean', 'active', NULL, 'Quần jean nam nữ các kiểu dáng ống suông, slimfit, rách gối cá tính.', '2026-10-05 07:33:49', '2026-10-05 07:33:49', NULL),
(4, NULL, 'Áo Khoác', 'ao-khoac', 'active', NULL, 'Áo khoác gió, áo khoác dù, áo hoodie giữ ấm mùa đông và chống nắng mùa hè.', '2026-10-05 07:33:49', '2026-10-05 07:33:49', NULL),
(5, NULL, 'Đồ Lót & Đồ Ngủ', 'do-lot-do-ngu', 'active', NULL, 'Đồ lót cotton mềm mại và đồ mặc nhà pijama thoải mái, dễ chịu.', '2026-10-05 07:33:49', '2026-10-05 07:33:49', NULL),
(6, NULL, 'Thời trang nam', 'thoi-trang-nam', 'active', NULL, 'Các sản phẩm quần áo và phụ kiện dành cho nam', '2026-10-05 06:48:20', '2026-10-05 06:48:20', NULL),
(7, NULL, 'Áo', 'ao', 'active', NULL, NULL, '2026-10-10 05:52:01', '2026-10-10 05:52:01', NULL),
(8, NULL, 'Quần', 'quan', 'active', NULL, NULL, '2026-10-10 05:52:01', '2026-10-10 05:52:01', NULL),
(9, NULL, 'Váy', 'vay', 'active', NULL, NULL, '2026-10-10 05:52:01', '2026-10-10 05:52:01', NULL),
(10, NULL, 'Phụ kiện', 'phu-kien', 'active', NULL, NULL, '2026-10-10 05:52:01', '2026-10-10 05:52:01', NULL),
(11, 7, 'ao lv1', 'ao-lv', 'active', NULL, 'ao moi', '2026-10-14 05:23:08', '2026-10-14 05:24:05', NULL),
(12, 8, 'Váy', 'quan-122', 'active', NULL, 'đ', '2026-10-14 06:03:52', '2026-10-14 06:04:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `colors`
--

CREATE TABLE `colors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `hex_code` varchar(7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `colors`
--

INSERT INTO `colors` (`id`, `name`, `hex_code`, `created_at`, `updated_at`) VALUES
(1, 'Đen', '#000000', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(2, 'Trắng', '#FFFFFF', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(3, 'Xám', '#808080', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(4, 'Xanh', '#2563EB', '2026-10-10 05:52:01', '2026-10-10 05:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `link_type` varchar(20) NOT NULL DEFAULT 'internal',
  `url` varchar(2048) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `name`, `link_type`, `url`, `sort_order`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'az', 'internal', '/az', 0, 'inactive', '2026-10-24 00:58:37', '2026-10-24 00:59:16', '2026-10-24 00:59:16'),
(2, 'Sản Phẩm', 'internal', '/products', 1, 'active', '2026-10-24 00:59:47', '2026-10-24 01:00:57', NULL),
(3, 'Thương Hiệu', 'internal', '/products?brand', 2, 'active', '2026-10-24 01:00:19', '2026-10-24 01:10:31', NULL),
(4, 'Danh Mục', 'internal', '/products?category', 3, 'active', '2026-10-24 01:00:38', '2026-10-24 01:11:00', NULL),
(5, 'Bài Viết', 'internal', '/posts', 4, 'active', '2026-10-24 01:02:39', '2026-10-24 01:02:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_10_05_072749_create_categories_table', 1),
(2, '2026_10_05_083142_create_users_table', 2),
(3, '2026_10_05_085946_create_products_table', 3),
(4, '2026_10_05_090918_create_orders_table', 4),
(5, '2026_10_05_142756_create_personal_access_tokens_table', 5),
(6, '2026_10_05_152400_create_brands_table', 6),
(7, '2026_10_05_152456_add_brand_id_to_products_table', 6),
(8, '2026_10_05_170620_create_sessions_table', 7),
(9, '2026_10_10_193001_expand_users_table_for_ecommerce', 8),
(10, '2026_10_10_193002_expand_categories_table_for_ecommerce', 8),
(11, '2026_10_10_193003_expand_brands_table_for_ecommerce', 8),
(12, '2026_10_10_193004_expand_products_table_for_ecommerce', 8),
(13, '2026_10_10_193005_create_sizes_table', 8),
(14, '2026_10_10_193006_create_colors_table', 8),
(15, '2026_10_10_193007_create_promotions_table', 8),
(16, '2026_10_10_193008_create_product_images_table', 8),
(17, '2026_10_10_193009_create_product_variants_table', 8),
(18, '2026_10_10_193010_create_addresses_table', 8),
(19, '2026_10_10_193011_create_carts_table', 8),
(20, '2026_10_10_193012_create_cart_items_table', 8),
(21, '2026_10_10_193013_expand_orders_table_for_ecommerce', 8),
(22, '2026_10_10_193014_create_promotion_products_table', 8),
(23, '2026_10_10_193015_create_order_items_table', 8),
(24, '2026_10_10_193016_create_payments_table', 8),
(25, '2026_10_10_193017_create_reviews_table', 8),
(26, '2026_10_10_193018_create_order_status_histories_table', 8),
(27, '2026_10_14_000001_add_soft_deletes_to_categories_and_brands', 9),
(28, '2026_10_19_000001_create_posts_table', 10),
(29, '2026_10_19_000002_create_menus_table', 11),
(30, '2026_10_26_000001_add_sale_price_to_product_variants_table', 12),
(31, '2026_11_02_000001_create_banners_table', 13),
(32, '2026_11_03_000001_create_password_reset_otps_table', 14);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `total` decimal(12,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Chờ xác nhận',
  `address` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_code` varchar(100) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `discount_amount` decimal(12,2) DEFAULT NULL,
  `shipping_fee` decimal(12,2) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  `shipping_address_detail` text DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `promotion_code` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `cancelled_reason` text DEFAULT NULL,
  `promotion_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `address`, `phone`, `created_at`, `updated_at`, `order_code`, `subtotal`, `discount_amount`, `shipping_fee`, `total_amount`, `payment_method`, `payment_status`, `shipping_address_detail`, `shipping_phone`, `promotion_code`, `note`, `cancelled_reason`, `promotion_id`) VALUES
(1, 12, 499000.00, 'Đã hủy', 'ml 123', '0123456789', '2026-10-24 08:31:35', '2026-10-24 09:01:22', 'SP-20261024-00000001', 499000.00, 0.00, 0.00, 499000.00, 'cod', 'unpaid', '{\"label\":\"az 123\",\"recipient_name\":\"hai\",\"phone\":\"0123456789\",\"province\":\"thủ đức\",\"district\":\"thủ đức\",\"ward\":\"bình thọ\",\"address_detail\":\"ml 123\"}', '0123456789', NULL, NULL, NULL, NULL),
(2, 12, 1198000.00, 'Đã giao hàng', 'ml 123', '0123456789', '2026-10-24 08:44:46', '2026-10-24 09:00:05', 'SP-20261024-00000002', 1198000.00, 0.00, 0.00, 1198000.00, 'cod', 'unpaid', '{\"label\":\"az 123\",\"recipient_name\":\"hai\",\"phone\":\"0123456789\",\"province\":\"thủ đức\",\"district\":\"thủ đức\",\"ward\":\"bình thọ\",\"address_detail\":\"ml 123\"}', '0123456789', NULL, NULL, NULL, NULL),
(3, 11, 5593000.00, 'Đã giao hàng', 'ok', '7788112233', '2026-10-26 04:08:07', '2026-10-26 04:09:11', 'SP-20261026-00000003', 5593000.00, 0.00, 0.00, 5593000.00, 'cod', 'unpaid', '{\"label\":\"12 tăng nhơn phú\",\"recipient_name\":\"phương\",\"phone\":\"7788112233\",\"province\":\"tptd\",\"district\":\"tnp\",\"ward\":\"tnp\",\"address_detail\":\"ok\"}', '7788112233', NULL, 'ok', NULL, NULL),
(4, 12, 499000.00, 'Đã giao hàng', 'ml 123', '0123456789', '2026-11-02 03:38:59', '2026-11-02 03:39:36', 'SP-20261102-00000004', 499000.00, 0.00, 0.00, 499000.00, 'cod', 'unpaid', '{\"label\":\"az 123\",\"recipient_name\":\"hai\",\"phone\":\"0123456789\",\"province\":\"thủ đức\",\"district\":\"thủ đức\",\"ward\":\"bình thọ\",\"address_detail\":\"ml 123\"}', '0123456789', NULL, NULL, NULL, NULL),
(5, 12, 258999.99, 'Chờ xác nhận', 'ml 123', '0123456789', '2026-11-02 04:26:11', '2026-11-02 04:26:11', 'SP-20261102-00000005', 258999.99, 0.00, 0.00, 258999.99, 'cod', 'unpaid', '{\"label\":\"az 123\",\"recipient_name\":\"hai\",\"phone\":\"0123456789\",\"province\":\"thủ đức\",\"district\":\"thủ đức\",\"ward\":\"bình thọ\",\"address_detail\":\"ml 123\"}', '0123456789', NULL, NULL, NULL, NULL),
(6, 12, 500000.00, 'Chờ xác nhận', 'ml 123', '0123456789', '2026-11-02 04:29:50', '2026-11-02 04:29:50', 'SP-20261102-00000006', 500000.00, 0.00, 0.00, 500000.00, 'cod', 'unpaid', '{\"label\":\"az 123\",\"recipient_name\":\"hai\",\"phone\":\"0123456789\",\"province\":\"thủ đức\",\"district\":\"thủ đức\",\"ward\":\"bình thọ\",\"address_detail\":\"ml 123\"}', '0123456789', NULL, NULL, NULL, NULL),
(7, 13, 1178000.00, 'Đã xác nhận', 'ê2', '11312313', '2026-11-02 20:41:20', '2026-11-02 20:41:50', 'SP-20261103-00000007', 1178000.00, 0.00, 0.00, 1178000.00, 'cod', 'unpaid', '{\"label\":\"123 dbv\",\"recipient_name\":\"dsd\",\"phone\":\"11312313\",\"province\":\"21\",\"district\":\"212\",\"ward\":\"122\",\"address_detail\":\"ê2\"}', '11312313', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_variant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_variant_id`, `product_name`, `sku`, `size`, `color`, `price`, `quantity`, `subtotal`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 'Váy midi xếp ly', 'DEMO-MIDI-S-BLK', 'S', 'Đen', 499000.00, 1, 499000.00, '2026-10-24 08:31:35', '2026-10-24 08:31:35'),
(2, 2, 5, 'Quần jean straight fit', 'DEMO-JEAN-S-BLK', 'S', 'Đen', 599000.00, 2, 1198000.00, '2026-10-24 08:44:46', '2026-10-24 08:44:46'),
(3, 3, 13, 'Áo khoác thể thao nhẹ', 'DEMO-JACKET-S-BLK', 'S', 'Đen', 799000.00, 7, 5593000.00, '2026-10-26 04:08:07', '2026-10-26 04:08:07'),
(4, 4, 9, 'Váy midi xếp ly', 'DEMO-MIDI-S-BLK', 'S', 'Đen', 499000.00, 1, 499000.00, '2026-11-02 03:38:59', '2026-11-02 03:38:59'),
(5, 5, 17, 'ao phao mua dong', 'sp-001', 'S', 'Đen', 258999.99, 1, 258999.99, '2026-11-02 04:26:11', '2026-11-02 04:26:11'),
(6, 6, 20, 'Bộ đồ ngủ pijama lụa', 'a02', 'M', 'Trắng', 500000.00, 1, 500000.00, '2026-11-02 04:29:50', '2026-11-02 04:29:50'),
(7, 7, 14, 'Áo khoác thể thao nhẹ', 'DEMO-JACKET-M-WHT', 'M', 'Trắng', 678000.00, 1, 678000.00, '2026-11-02 20:41:20', '2026-11-02 20:41:20'),
(8, 7, 19, 'Bộ đồ ngủ pijama lụa', 'a01', 'S', 'Đen', 500000.00, 1, 500000.00, '2026-11-02 20:41:20', '2026-11-02 20:41:20');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_histories`
--

CREATE TABLE `order_status_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_histories`
--

INSERT INTO `order_status_histories` (`id`, `order_id`, `changed_by`, `from_status`, `to_status`, `note`, `created_at`) VALUES
(1, 1, 12, NULL, 'Chờ xác nhận', 'Order created.', '2026-10-24 08:31:35'),
(2, 2, 12, NULL, 'Chờ xác nhận', 'Order created.', '2026-10-24 08:44:46'),
(3, 2, 9, 'Chờ xác nhận', 'Đã xác nhận', NULL, '2026-10-24 08:59:14'),
(4, 2, 9, 'Đã xác nhận', 'Đang chuẩn bị hàng', NULL, '2026-10-24 08:59:34'),
(5, 2, 9, 'Đang chuẩn bị hàng', 'Đang giao hàng', NULL, '2026-10-24 08:59:54'),
(6, 2, 9, 'Đang giao hàng', 'Đã giao hàng', NULL, '2026-10-24 09:00:05'),
(7, 1, 12, 'Chờ xác nhận', 'Đã hủy', NULL, '2026-10-24 09:01:22'),
(8, 3, 11, NULL, 'Chờ xác nhận', 'Order created.', '2026-10-26 04:08:07'),
(9, 3, 9, 'Chờ xác nhận', 'Đã xác nhận', NULL, '2026-10-26 04:08:19'),
(10, 3, 9, 'Đã xác nhận', 'Đang chuẩn bị hàng', NULL, '2026-10-26 04:09:06'),
(11, 3, 9, 'Đang chuẩn bị hàng', 'Đang giao hàng', NULL, '2026-10-26 04:09:09'),
(12, 3, 9, 'Đang giao hàng', 'Đã giao hàng', NULL, '2026-10-26 04:09:11'),
(13, 4, 12, NULL, 'Chờ xác nhận', 'Order created.', '2026-11-02 03:38:59'),
(14, 4, 9, 'Chờ xác nhận', 'Đã xác nhận', NULL, '2026-11-02 03:39:26'),
(15, 4, 9, 'Đã xác nhận', 'Đang chuẩn bị hàng', NULL, '2026-11-02 03:39:30'),
(16, 4, 9, 'Đang chuẩn bị hàng', 'Đang giao hàng', NULL, '2026-11-02 03:39:33'),
(17, 4, 9, 'Đang giao hàng', 'Đã giao hàng', NULL, '2026-11-02 03:39:36'),
(18, 5, 12, NULL, 'Chờ xác nhận', 'Order created.', '2026-11-02 04:26:11'),
(19, 6, 12, NULL, 'Chờ xác nhận', 'Order created.', '2026-11-02 04:29:50'),
(20, 7, 13, NULL, 'Chờ xác nhận', 'Order created.', '2026-11-02 20:41:20'),
(21, 7, 9, 'Chờ xác nhận', 'Đã xác nhận', NULL, '2026-11-02 20:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otps`
--

CREATE TABLE `password_reset_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `otp_hash` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `verified_token_hash` varchar(64) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_code` varchar(150) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `transaction_code`, `amount`, `status`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'cod', NULL, 499000.00, 'pending', NULL, '2026-10-24 08:31:35', '2026-10-24 08:31:35'),
(2, 2, 'cod', NULL, 1198000.00, 'pending', NULL, '2026-10-24 08:44:46', '2026-10-24 08:44:46'),
(3, 3, 'cod', NULL, 5593000.00, 'pending', NULL, '2026-10-26 04:08:07', '2026-10-26 04:08:07'),
(4, 4, 'cod', NULL, 499000.00, 'pending', NULL, '2026-11-02 03:38:59', '2026-11-02 03:38:59'),
(5, 5, 'cod', NULL, 258999.99, 'pending', NULL, '2026-11-02 04:26:11', '2026-11-02 04:26:11'),
(6, 6, 'cod', NULL, 500000.00, 'pending', NULL, '2026-11-02 04:29:50', '2026-11-02 04:29:50'),
(7, 7, 'cod', NULL, 1178000.00, 'pending', NULL, '2026-11-02 20:41:20', '2026-11-02 20:41:20');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, 'App\\Models\\User', 7, 'auth_token', '28411966bb55e0a0f2c8e31ce55302e2c03676148bc27ff3023aab7387f0b070', '[\"*\"]', '2026-10-05 07:48:04', NULL, '2026-10-05 07:32:30', '2026-10-05 07:48:04'),
(3, 'App\\Models\\User', 7, 'auth_token', '5bc6aa1b3a2472477786b30b259d9752462a1882dbaf4d4b4219250e966a8987', '[\"*\"]', '2026-10-05 07:51:36', NULL, '2026-10-05 07:50:15', '2026-10-05 07:51:36'),
(4, 'App\\Models\\User', 7, 'auth_token', '3b5ac141001579a30cf3ec30d1474cda10063b628eb71f78568f87ad8b282512', '[\"*\"]', NULL, NULL, '2026-10-05 07:51:05', '2026-10-05 07:51:05'),
(5, 'App\\Models\\User', 7, 'auth_token', 'eecdbd9de6583f3bfe4d8092ca976391e945f84964ed0810bd4b9f756ad78ba0', '[\"*\"]', '2026-10-05 08:48:05', NULL, '2026-10-05 07:51:51', '2026-10-05 08:48:05'),
(6, 'App\\Models\\User', 7, 'auth_token', 'a67c473f528b7e0ec7b4f1d12f3ae79063bd683a001b744664b4ca29084bd6f9', '[\"*\"]', NULL, NULL, '2026-10-05 08:49:47', '2026-10-05 08:49:47'),
(7, 'App\\Models\\User', 7, 'auth_token', 'e2f2964b4eb0cc4545864fb956db5acad2bc010e746f6bafd8c8f72acaf22d6f', '[\"*\"]', '2026-10-05 08:51:58', NULL, '2026-10-05 08:50:04', '2026-10-05 08:51:58'),
(8, 'App\\Models\\User', 7, 'auth_token', '68a1ef913d26db9cddcb5c8522be40b6429d4e5246963d57a80f004757f19a34', '[\"*\"]', '2026-10-05 08:56:04', NULL, '2026-10-05 08:55:24', '2026-10-05 08:56:04'),
(9, 'App\\Models\\User', 7, 'auth_token', '7e393b0e3d674909d68a845eaf6016e4cd7fd9e252b340c27ad9840d01f4e150', '[\"*\"]', NULL, NULL, '2026-10-05 09:28:40', '2026-10-05 09:28:40'),
(10, 'App\\Models\\User', 7, 'auth_token', 'f89ee3a6c73763c6430c56d0689320d436f2d2dcecf5903b27299b6b9e8f08fa', '[\"*\"]', '2026-10-05 09:33:29', NULL, '2026-10-05 09:32:19', '2026-10-05 09:33:29'),
(12, 'App\\Models\\User', 9, 'auth_token', '278e19d65e96f04689de2f7fe285d888eed05458ddea726cfe252a1298ceac62', '[\"*\"]', '2026-10-14 06:10:54', NULL, '2026-10-14 05:21:17', '2026-10-14 06:10:54'),
(13, 'App\\Models\\User', 9, 'auth_token', '66b5eb53716a1693e186f4584ee19c650addf138e507669198efa8db3e405493', '[\"*\"]', '2026-10-19 04:40:17', NULL, '2026-10-19 04:21:20', '2026-10-19 04:40:17'),
(15, 'App\\Models\\User', 9, 'auth_token', '465ab032f691c5d512286b3b450b0f65f8bb5e6b851a846b2d2074f160bd10bd', '[\"*\"]', '2026-10-19 05:16:55', NULL, '2026-10-19 05:04:31', '2026-10-19 05:16:55'),
(16, 'App\\Models\\User', 9, 'auth_token', '43308768b67b8b5c41205a9c8b8a6db63f83d20a18a0b9f4f5f8dc3104c3f8e2', '[\"*\"]', '2026-10-24 01:33:12', NULL, '2026-10-23 23:39:31', '2026-10-24 01:33:12'),
(18, 'App\\Models\\User', 12, 'auth_token', '877a548ab5b36fefbad625a83c81b989fa001275d3af3649b051b6213b10b324', '[\"*\"]', NULL, NULL, '2026-10-24 02:05:18', '2026-10-24 02:05:18'),
(19, 'App\\Models\\User', 12, 'auth_token', 'c9d20857c768cb51d8d5d7d56b8a4ef2374baaeafe46bd98069d5989d1fe8275', '[\"*\"]', '2026-10-24 09:01:27', NULL, '2026-10-24 08:19:59', '2026-10-24 09:01:27'),
(20, 'App\\Models\\User', 9, 'auth_token', '837c18b0fee4e0a956a23e290a237207291e55051564a8e844e89fded2022601', '[\"*\"]', '2026-10-24 09:01:33', NULL, '2026-10-24 08:32:18', '2026-10-24 09:01:33'),
(21, 'App\\Models\\User', 9, 'auth_token', '3e037d695afb7f7d91dcec471896715a388af2f3fc477a6b68a80d0e6c39cb65', '[\"*\"]', '2026-10-26 05:27:21', NULL, '2026-10-26 03:27:30', '2026-10-26 05:27:21'),
(22, 'App\\Models\\User', 11, 'auth_token', '3ceba0e120675709f17e327081a43df41a6b726c493f8ea93cf7f40962d8b5d0', '[\"*\"]', '2026-10-26 05:30:34', NULL, '2026-10-26 03:35:22', '2026-10-26 05:30:34'),
(23, 'App\\Models\\User', 9, 'auth_token', '5b77fd4e1670d0e61e135a9eeb98d92710604888992802811621947cf257129e', '[\"*\"]', '2026-10-26 05:56:02', NULL, '2026-10-26 05:29:17', '2026-10-26 05:56:02'),
(24, 'App\\Models\\User', 9, 'auth_token', 'ad4b81b4842ec7b0f13f2d345cc7ff85a4c9a4fa86a979be72dd6134b9b1e135', '[\"*\"]', '2026-10-26 05:52:29', NULL, '2026-10-26 05:52:08', '2026-10-26 05:52:29'),
(25, 'App\\Models\\User', 11, 'auth_token', '8c11b7e8e4711858535dca9f6cba920c0fc68d3900510bc401a609b5356a1e9e', '[\"*\"]', '2026-10-26 19:38:26', NULL, '2026-10-26 17:52:07', '2026-10-26 19:38:26'),
(26, 'App\\Models\\User', 9, 'auth_token', 'd05c48c5de4c12ac3f2e51b2aa53771168c2541156e79c749ae6c83de66785e0', '[\"*\"]', '2026-10-26 19:46:33', NULL, '2026-10-26 17:53:32', '2026-10-26 19:46:33'),
(27, 'App\\Models\\User', 9, 'auth_token', '4bbddd672443e669fe9a5856df21282d2fc254efc45f1302d8e8366aad9c5008', '[\"*\"]', '2026-10-26 20:19:32', NULL, '2026-10-26 20:17:38', '2026-10-26 20:19:32'),
(28, 'App\\Models\\User', 9, 'auth_token', 'e826eb92949c7546e784ea7802c4073370dd5e40ac212ecb1363b8ba58c76e6c', '[\"*\"]', '2026-11-01 23:57:03', NULL, '2026-11-01 23:29:07', '2026-11-01 23:57:03'),
(29, 'App\\Models\\User', 11, 'auth_token', '3b0d98241af8afa3e947164aff7a272d1d8ee90b9d6d2e03b07e9ad34abc0adb', '[\"*\"]', '2026-11-01 23:56:59', NULL, '2026-11-01 23:30:03', '2026-11-01 23:56:59'),
(30, 'App\\Models\\User', 12, 'auth_token', 'b86326c9ad274eb1f552e0df342b37ae72bd8c358b31727f4cd88e1c8ac3e1ab', '[\"*\"]', '2026-11-02 04:46:17', NULL, '2026-11-02 03:13:19', '2026-11-02 04:46:17'),
(31, 'App\\Models\\User', 9, 'auth_token', '3d1e77af4eccbf70252952dc4d446397b75ea53bc7b4924aa094ef405bc2327c', '[\"*\"]', '2026-11-02 04:46:18', NULL, '2026-11-02 03:14:01', '2026-11-02 04:46:18'),
(32, 'App\\Models\\User', 12, 'auth_token', '6b89c174db4a745d1c4ee30fa2f868c2cb08418a3136f022115713937ed8cfe2', '[\"*\"]', '2026-11-02 04:27:12', NULL, '2026-11-02 04:27:11', '2026-11-02 04:27:12'),
(33, 'App\\Models\\User', 9, 'auth_token', '5dfe33d18a7cff794b3213468258403db7b968511b1cb04c43ce6a869725b5d9', '[\"*\"]', '2026-11-02 20:47:26', NULL, '2026-11-02 19:25:37', '2026-11-02 20:47:26'),
(39, 'App\\Models\\User', 13, 'auth_token', '9c57ef74095208eb01b2fc89cd6e7185253748a60b1d9dd7c5f0b93dbd96b99e', '[\"*\"]', '2026-11-02 20:44:07', NULL, '2026-11-02 20:44:07', '2026-11-02 20:44:07'),
(40, 'App\\Models\\User', 9, 'auth_token', 'ce3e3b7dd1f5b08ae739f929a296d7bc92ba5c2f3c9337a2c1290cfa6ef665a6', '[\"*\"]', '2026-11-04 09:15:30', NULL, '2026-11-04 08:21:47', '2026-11-04 09:15:30'),
(41, 'App\\Models\\User', 12, 'auth_token', 'd279fab47baeefc15d87e09a8847e5fc9d256c4c4845639b3aeb309e5649243f', '[\"*\"]', NULL, NULL, '2026-11-04 08:22:04', '2026-11-04 08:22:04'),
(42, 'App\\Models\\User', 12, 'auth_token', 'e2e5cfe4032f378d6b9a83c68b191c2cd5d36a0a4e335ccb2e8d723e89070f11', '[\"*\"]', NULL, NULL, '2026-11-04 08:26:28', '2026-11-04 08:26:28'),
(43, 'App\\Models\\User', 12, 'auth_token', '9828dfccc1354106173f480505980abfb4374216ed048e6120c5a0695bcfadb3', '[\"*\"]', NULL, NULL, '2026-11-04 08:26:37', '2026-11-04 08:26:37'),
(44, 'App\\Models\\User', 12, 'auth_token', '9ecaa0d78346a7a0c03823b77d0db7aa7ecd40ae7a3cb810c1efb5cd23d67865', '[\"*\"]', NULL, NULL, '2026-11-04 08:26:40', '2026-11-04 08:26:40'),
(45, 'App\\Models\\User', 12, 'auth_token', '3fcfbf2b1055bae19332bf727358c57a0be3a84138ceb7c66e1b80f1c7ba63d2', '[\"*\"]', NULL, NULL, '2026-11-04 08:27:48', '2026-11-04 08:27:48'),
(46, 'App\\Models\\User', 12, 'auth_token', 'b52c79d57f9fdb83b4b2e4a7f5bd42b97a1ba6f6da7a5d7c63a08e4af36556de', '[\"*\"]', NULL, NULL, '2026-11-04 08:27:57', '2026-11-04 08:27:57'),
(47, 'App\\Models\\User', 12, 'auth_token', '391126181c8ac903e565237999d30cdc67753923e0e484543fa40c9f4859f753', '[\"*\"]', '2026-11-04 08:44:27', NULL, '2026-11-04 08:43:51', '2026-11-04 08:44:27'),
(48, 'App\\Models\\User', 9, 'auth_token', 'db79937e6be1e09c2f93d9dab487dfd94aa3d3502624e8b93143427d0d6be350', '[\"*\"]', '2026-11-04 08:56:49', NULL, '2026-11-04 08:45:31', '2026-11-04 08:56:49'),
(49, 'App\\Models\\User', 9, 'auth_token', '15e347b4e638d9ff75254dd80af8c2e1f8cd32c4eec8b69375254bbb73cdcea6', '[\"*\"]', NULL, NULL, '2026-11-04 09:00:04', '2026-11-04 09:00:04'),
(50, 'App\\Models\\User', 9, 'auth_token', '7ed13534e8c5d576ef8d69a91779b182dfc8470897bb6de33b01a8811a5ff065', '[\"*\"]', '2026-11-04 09:01:15', NULL, '2026-11-04 09:00:18', '2026-11-04 09:01:15'),
(51, 'App\\Models\\User', 9, 'auth_token', '93d2f22580b81b410c1895366a9bfb0affe6dd34dfe14d14b9dfd8dad19cb79e', '[\"*\"]', '2026-11-04 23:26:15', NULL, '2026-11-04 23:22:10', '2026-11-04 23:26:15'),
(52, 'App\\Models\\User', 9, 'auth_token', '03dc8cbe9d1583f18f064df597bd779c7647533a1a25c5a0ff437b4b2f39495b', '[\"*\"]', '2026-11-04 23:52:14', NULL, '2026-11-04 23:26:20', '2026-11-04 23:52:14'),
(53, 'App\\Models\\User', 9, 'auth_token', '6f27f1a2d715610a8a8b528aceb793ae5775bab2cb8322a699b0a597a2ff42a5', '[\"*\"]', '2026-11-05 00:29:05', NULL, '2026-11-04 23:27:35', '2026-11-05 00:29:05'),
(54, 'App\\Models\\User', 9, 'auth_token', 'dcda7f5a3904d003ccfee23edab2585082dcb3047cfed1651fe2d3ac025f3426', '[\"*\"]', '2026-11-04 23:54:51', NULL, '2026-11-04 23:52:56', '2026-11-04 23:54:51'),
(55, 'App\\Models\\User', 9, 'auth_token', '2b69e54a072d81b92c88ff60d65d9355d056991b5ebedbd8cac903dad87d3696', '[\"*\"]', '2026-11-05 03:30:26', NULL, '2026-11-05 03:04:04', '2026-11-05 03:30:26'),
(56, 'App\\Models\\User', 9, 'auth_token', 'adbf4e5cb1a4b3594be7bfc523965fbfae0c360d7b6672f427a92be5892eec00', '[\"*\"]', NULL, NULL, '2026-11-05 03:11:06', '2026-11-05 03:11:06'),
(57, 'App\\Models\\User', 9, 'auth_token', 'b18f0f812eae5df66125a4a03dfb4479abfc3e59cb3255f67bb7dab83ded4ed7', '[\"*\"]', NULL, NULL, '2026-11-05 03:18:49', '2026-11-05 03:18:49'),
(58, 'App\\Models\\User', 9, 'auth_token', '7ffc0b246a5dfb77b805ab3bf0ef8ed6ce847564967c09828de8521d828520f5', '[\"*\"]', '2026-11-05 03:25:25', NULL, '2026-11-05 03:20:21', '2026-11-05 03:25:25');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` varchar(1000) DEFAULT NULL,
  `content` longtext NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `excerpt`, `content`, `thumbnail`, `user_id`, `status`, `published_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'sale', 'sale', 'dđ', 'dd', 'posts/vPfLSFOpdMKtUiV7pZYjcPtbjnijbmVVfPPWsqBE.webp', 9, 'published', '2026-10-23 23:40:21', '2026-10-19 05:04:53', '2026-10-23 23:40:21', NULL),
(3, 'wua1', 'wua1', 'sss', 'a13', NULL, 9, 'published', '2026-10-19 12:05:00', '2026-10-19 05:05:21', '2026-10-19 05:05:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `slug`, `description`, `price`, `sale_price`, `status`, `image`, `quantity`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 4, NULL, 'Áo khoác gió chống nước', 'ao-khoac-gio-chong-nuoc', 'Áo khoác dù 2 lớp nhẹ nhàng, chống nước mưa nhẹ và giữ ấm tốt.', 450000.00, NULL, 'active', 'https://down-vn.img.susercontent.com/file/vn-11134207-7qukw-lk25s0q9f0n6e5', 20, '2026-10-05 09:00:32', '2026-10-26 20:19:32', NULL),
(5, 5, NULL, 'Bộ đồ ngủ pijama lụa', 'bo-do-ngu-pijama-lua', 'Bộ pijama mặc nhà chất liệu lụa mềm mại, sang trọng và thoáng mát.', 280000.00, NULL, 'active', 'pijama_lua.jpg', 40, '2026-10-05 09:00:32', '2026-11-04 09:01:15', '2026-11-04 09:01:15'),
(6, 7, 3, 'Áo thun basic cotton', 'demo-ao-thun-basic', 'Sản phẩm mẫu dùng để kiểm thử API.', 249000.00, 199000.00, 'active', 'https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mdmlzdfl3qng7e', 0, '2026-10-10 05:52:01', '2026-10-26 20:18:58', NULL),
(7, 8, 6, 'Quần jean straight fit', 'demo-quan-jean-straight', 'Sản phẩm mẫu dùng để kiểm thử API.', 599000.00, NULL, 'active', 'https://tse1.mm.bing.net/th/id/OIP.dZ1VOyVAYK-DnJT00CLDYgHaHa?r=0&pid=Api&h=220&P=0', 0, '2026-10-10 05:52:01', '2026-10-26 20:18:21', NULL),
(8, 9, 7, 'Váy midi xếp ly', 'demo-vay-midi', 'Sản phẩm mẫu dùng để kiểm thử API.', 499000.00, 449000.00, 'active', 'https://product.hstatic.net/200000000133/product/22aote048d-22acve041b-3_5419173113f94f25a5d7cb49bc275c31_master.jpg', 0, '2026-10-10 05:52:01', '2026-10-26 20:17:47', NULL),
(9, 7, 2, 'Áo khoác thể thao nhẹ', 'demo-ao-khoac-the-thao', 'Sản phẩm mẫu dùng để kiểm thử API.', 799000.00, 590000.00, 'active', 'https://cdn.vuahanghieu.com/unsafe/0x0/left/top/smart/filters:quality(90)/https://admin.vuahanghieu.com/upload/news/content/2025/03/ao-khoac-adidas-duc-2-jpg-1742009854-15032025103734.jpg', 0, '2026-10-10 05:52:01', '2026-10-26 05:29:35', NULL),
(11, 11, 2, 'Bộ đồ ngủ pijama lụa', 'anh-phuong', 'aaa', 123.00, NULL, 'active', 'https://down-vn.img.susercontent.com/file/vn-11134207-7qukw-lhbnqbm2dagia2', 5, '2026-10-14 06:02:09', '2026-10-26 19:46:33', NULL),
(12, 7, 1, 'ao phao mua dong', 'ao-phao-mua-dong', 'ao mua dong tre trung nang dong', 400000.00, NULL, 'active', 'https://bizweb.dktcdn.net/100/425/004/products/sportswear-windrunner-midweight-1709379552685.jpg?v=1709381505217', 20, '2026-10-26 04:16:49', '2026-10-26 05:23:33', NULL),
(13, 10, NULL, 'Sản phẩm kiểm thử Postman1', 'san-pham-kiem-thu-postman-20261104', 'Sản phẩm dùng để thực hành kiểm thử API bằng Postman', 360000.00, 300000.00, 'active', NULL, 10, '2026-11-04 08:47:35', '2026-11-04 23:42:01', NULL),
(14, 12, NULL, 'Sản phẩm kiểm thử Postman1', 'san-pham-kiem-thu-postman-202611045', 'Sản phẩm dùng để thực hành kiểm thử API bằng Postman1', 3500001.00, 3000001.00, 'active', NULL, 10, '2026-11-04 23:31:56', '2026-11-04 23:31:56', NULL),
(15, 12, NULL, 'Sản phẩm kiểm thử Postman1', 'san-pham-kiem-thu-postman-2026110451', 'Sản phẩm dùng để thực hành kiểm thử API bằng Postman1', 3500001.00, 3000001.00, 'active', NULL, 10, '2026-11-04 23:33:35', '2026-11-04 23:45:17', '2026-11-04 23:45:17'),
(16, 12, NULL, 'Sản phẩm kiểm thử Postman011', 'san-pham-kiem-thu-postman-1152026', 'Sản phẩm dùng để thực hành kiểm thử API bằng Postman111', 35000011.00, 30000011.00, 'active', NULL, 10, '2026-11-04 23:54:51', '2026-11-04 23:54:51', NULL),
(17, 12, NULL, 'Sản phẩm kiểm thử Postman01132', 'san-pham-kiem-thu-postman-11520263', 'Sản phẩm dùng để thực hành kiểm thử API bằng Postman111', 35000011.00, 30000011.00, 'active', NULL, 10, '2026-11-05 03:23:46', '2026-11-05 03:23:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `is_primary`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 4, 'ao_khoac_gio.jpg', 1, 0, '2026-10-05 09:00:32', '2026-10-05 09:00:32'),
(2, 5, 'pijama_lua.jpg', 1, 0, '2026-10-05 09:00:32', '2026-10-05 09:00:32'),
(3, 6, 'products/demo-ao-thun-basic.jpg', 1, 0, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(4, 6, 'products/alternate/demo-ao-thun-basic.jpg', 0, 1, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(5, 7, 'products/demo-quan-jean-straight.jpg', 1, 0, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(6, 7, 'products/alternate/demo-quan-jean-straight.jpg', 0, 1, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(7, 8, 'products/demo-vay-midi.jpg', 1, 0, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(8, 8, 'products/alternate/demo-vay-midi.jpg', 0, 1, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(9, 9, 'products/demo-ao-khoac-the-thao.jpg', 1, 0, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(10, 9, 'products/alternate/demo-ao-khoac-the-thao.jpg', 0, 1, '2026-10-10 05:52:01', '2026-10-10 05:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `size_id` bigint(20) UNSIGNED NOT NULL,
  `color_id` bigint(20) UNSIGNED NOT NULL,
  `sku` varchar(100) NOT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size_id`, `color_id`, `sku`, `price`, `sale_price`, `stock`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, 1, 1, 'DEMO-TSHIRT-S-BLK', 249000.00, NULL, 8, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(2, 6, 2, 2, 'DEMO-TSHIRT-M-WHT', 249000.00, NULL, 12, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(3, 6, 3, 3, 'DEMO-TSHIRT-L-GRY', 249000.00, NULL, 6, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(4, 6, 4, 4, 'DEMO-TSHIRT-XL-BLU', 249000.00, NULL, 4, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(5, 7, 1, 1, 'DEMO-JEAN-S-BLK', 599000.00, NULL, 6, 'active', '2026-10-10 05:52:01', '2026-10-24 08:44:46'),
(6, 7, 2, 2, 'DEMO-JEAN-M-WHT', 599000.00, NULL, 12, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(7, 7, 3, 3, 'DEMO-JEAN-L-GRY', 599000.00, NULL, 6, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(8, 7, 4, 4, 'DEMO-JEAN-XL-BLU', 599000.00, NULL, 4, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(9, 8, 1, 1, 'DEMO-MIDI-S-BLK', 499000.00, NULL, 7, 'active', '2026-10-10 05:52:01', '2026-11-02 03:38:59'),
(10, 8, 2, 2, 'DEMO-MIDI-M-WHT', 499000.00, NULL, 12, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(11, 8, 3, 3, 'DEMO-MIDI-L-GRY', 499000.00, NULL, 6, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(12, 8, 4, 4, 'DEMO-MIDI-XL-BLU', 499000.00, NULL, 4, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(13, 9, 1, 1, 'DEMO-JACKET-S-BLK', 799000.00, 598999.98, 15, 'active', '2026-10-10 05:52:01', '2026-10-26 05:30:16'),
(14, 9, 2, 2, 'DEMO-JACKET-M-WHT', 799000.00, 678000.00, 14, 'active', '2026-10-10 05:52:01', '2026-11-02 20:41:20'),
(15, 9, 3, 3, 'DEMO-JACKET-L-GRY', 799000.00, NULL, 6, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(16, 9, 4, 4, 'DEMO-JACKET-XL-BLU', 799000.00, NULL, 4, 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(17, 12, 1, 1, 'sp-001', 400000.00, 258999.99, 19, 'active', '2026-10-26 04:45:35', '2026-11-02 04:26:11'),
(18, 12, 2, 2, 'sp-002', 400000.00, 269000.00, 20, 'active', '2026-10-26 04:47:17', '2026-10-26 04:47:17'),
(19, 11, 1, 1, 'a01', 500000.00, NULL, 19, 'active', '2026-11-02 04:28:28', '2026-11-02 20:41:20'),
(20, 11, 2, 2, 'a02', 500000.00, NULL, 19, 'active', '2026-11-02 04:28:44', '2026-11-02 04:29:50'),
(21, 11, 3, 3, 'a03', 500000.00, NULL, 20, 'active', '2026-11-02 04:29:02', '2026-11-02 04:29:02'),
(22, 11, 4, 4, 'a04', 500000.00, NULL, 2, 'active', '2026-11-02 04:29:23', '2026-11-02 04:29:23');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` varchar(20) NOT NULL,
  `discount_value` decimal(12,2) NOT NULL,
  `min_order_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(12,2) DEFAULT NULL,
  `usage_limit` int(10) UNSIGNED DEFAULT NULL,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `name`, `description`, `discount_type`, `discount_value`, `min_order_value`, `max_discount_amount`, `usage_limit`, `used_count`, `starts_at`, `ends_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'Giảm 10% đơn hàng', 'Mã khuyến mãi mẫu dùng để kiểm thử API.', 'percent', 10.00, 300000.00, 100000.00, 500, 0, '2026-10-09 12:52:01', '2027-10-10 12:52:01', 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(2, 'FASHION50000', 'Giảm 50.000 VND', 'Mã khuyến mãi mẫu dùng để kiểm thử API.', 'fixed', 50000.00, 500000.00, NULL, 300, 0, '2026-10-09 12:52:01', '2027-10-10 12:52:01', 'active', '2026-10-10 05:52:01', '2026-10-10 05:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `promotion_products`
--

CREATE TABLE `promotion_products` (
  `promotion_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('5mXc0hP1DbcXByv9hLcRqFAK2bREtKwvWgkiKAmd', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.144.1 Chrome/150.0.7871.250 Electron/43.8.3 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRWpoYmFYanEzTUZTSG5WN1dEWTlGeFpwRkRRd0pvZkc3WjlmSE1LVSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793858826),
('cjkuP8lknIjqseDfd9EEwFGJv6tq5jCf0LN7Z0c2', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOWV0a1NZTWM1bnlMd1R5YUVGeFRoTGl0Tm1IUVZkaTVBMTlGVG41MCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793858832),
('fkfYPYuvJ3yQKAOZeiU0TOtyHMeK1cyEONsq3oo0', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.145.0 Chrome/150.0.7871.250 Electron/43.8.3 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiR3pwMnNPQkZsSG9FcE9IYnJ1UjFpZXVuaW9zeWtyZ0VScjdEQXlsTCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793872925),
('k7Bcw6mxO25uQVibH6wyWj5ff55cSLRAaNv7UPnG', NULL, '127.0.0.1', 'PostmanRuntime/2.11.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiMGQ0WmoxZ0puek1PYWVXcjBjWXNyWGFpajhtc0Fad2VYRkZNZlVqTyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793807410),
('KRHGvkSr66d9Jqmds1rpz8dDIFz3BUF8qW9or5eO', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNVJHM3VpV1YzOWM4cWdqSWJMVWc3cFE4ZENqWDFyY1hMNXl5ZTVqaCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793872931),
('rwRCr3nkueUlCcfzWmq64K5hnDSYNYL9fP4vmUO5', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNnRLMXNvZjJDRmVDQmpWOUc2SU5Oanc0dUQ5anpISkprcWxyRGJHRiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1793805693);

-- --------------------------------------------------------

--
-- Table structure for table `sizes`
--

CREATE TABLE `sizes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sizes`
--

INSERT INTO `sizes` (`id`, `name`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'S', 1, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(2, 'M', 2, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(3, 'L', 3, '2026-10-10 05:52:01', '2026-10-10 05:52:01'),
(4, 'XL', 4, '2026-10-10 05:52:01', '2026-10-10 05:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'customer',
  `avatar` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `avatar`, `status`, `phone`, `address`, `created_at`, `updated_at`, `remember_token`) VALUES
(1, 'Nguyễn Xuân Sơn', 'nguyenxuanson11072005@gmail.com', NULL, 's101078#', 'admin', NULL, 'active', '0901234567', '123 Lê Văn Việt, TP. Thủ Đức, TP.HCM', '2026-10-05 08:33:36', '2026-10-05 08:33:36', NULL),
(2, 'sondepzai', 'son@gmail.com', NULL, 'S101078#', 'customer', NULL, 'active', '0912345678', '456 Nguyễn Trãi, Quận 5, TP.HCM', '2026-10-05 08:33:36', '2026-10-05 08:33:36', NULL),
(3, 'Lê Hoàng Cường', 'cuong.le@gmail.com', NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', NULL, 'active', '0923456789', '789 Điện Biên Phủ, Quận 3, TP.HCM', '2026-10-05 08:33:36', '2026-10-05 08:33:36', NULL),
(4, 'Phạm Thị Dung', 'dung.pham@gmail.com', NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', NULL, 'active', '0934567890', '321 Cách Mạng Tháng 8, Quận 10, TP.HCM', '2026-10-05 08:33:36', '2026-10-05 08:33:36', NULL),
(5, 'Hoàng Văn Em', 'em.hoang@gmail.com', NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', NULL, 'active', '0945678901', '654 Xô Viết Nghệ Tĩnh, Bình Thạnh, TP.HCM', '2026-10-05 08:33:36', '2026-10-05 08:33:36', NULL),
(6, 'Phạm Văn Tân', 'tan.pham@gmail.com', NULL, '$2y$12$jI5yvamPcbJmF83RNTS1hOeQXaQhg3R6dgenR4D79sqPi.UtWRHEO', 'customer', NULL, 'active', '0988111222', '12 Nguyễn Huệ, Quận 1, TP.HCM', '2026-10-05 01:37:40', '2026-10-05 01:37:40', NULL),
(7, 'Nguyễn Văn Test', 'vantest@gmail.com', NULL, '$2y$12$PRrJkr7mMlBZ.CzYgUqUte5cZ1hYeT5gvOYZwTjeS0D7hBLgvYm.S', 'customer', NULL, 'active', '0911223344', 'Quận 1, TP.HCM', '2026-10-05 07:16:41', '2026-10-05 07:16:41', NULL),
(8, 'Test User', 'son1.test.2026@example.com', NULL, '$2y$12$QtnJO9rwnaAizTa5Dv7KoO9Gj6OnCo4FxnX8JyJdneIuuzYNYaaNy', 'customer', NULL, 'active', NULL, NULL, '2026-10-10 08:53:19', '2026-10-19 04:42:36', NULL),
(9, 'Son Phuong Development Admin', 'sonphuong@gmail.com', NULL, '$2y$12$0dFZ./ljydk9gSqZtASMueR.QtlegPsjoiJuU2.R4K2SvYvcep5NO', 'admin', NULL, 'active', NULL, NULL, '2026-10-14 05:20:43', '2026-10-14 05:20:43', NULL),
(10, 'phat', 'phat@gmail.com', NULL, '$2y$12$MsgnQzaTxgqNLsuyTzX0kOIyVF19FNrj0xHetNJGbIhXDYiwLXwBu', 'customer', NULL, 'active', NULL, NULL, '2026-10-19 04:43:21', '2026-10-19 04:43:21', NULL),
(11, 'hihi', 'h1@gmail.com', NULL, '$2y$12$bX7oJgGFa5RvMLbVAaRpEegAFwLWDhYp61RohsUQ1i4ZePHN0rDRa', 'customer', NULL, 'active', '1112223330', 'a1', '2026-10-23 23:56:54', '2026-11-01 23:30:39', NULL),
(12, 'hai', 'hai@gmail.com', NULL, '$2y$12$axQOUGEceYy/aGMG1KZCcuL4aCkv22HES4cLn2rRNT4pfbpef/HGK', 'customer', NULL, 'active', '0123456789', '111 q1 hcm', '2026-10-24 02:05:02', '2026-11-02 03:13:36', NULL),
(13, 'thái phương', 'thaiphuongnguyen969@gmail.com', NULL, '$2y$12$Xr8PqY7pJNvpkKn18uXlBuwXiJNP/FAIOROZMTbK2SuHVoV7uaYi2', 'customer', NULL, 'active', '0934215023', NULL, '2026-11-02 20:02:41', '2026-11-02 20:43:59', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `addresses_user_id_foreign` (`user_id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `banners_status_sort_order_index` (`status`,`sort_order`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brands_name_unique` (`name`),
  ADD UNIQUE KEY `brands_slug_unique` (`slug`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `carts_user_id_unique` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cart_items_cart_id_product_variant_id_unique` (`cart_id`,`product_variant_id`),
  ADD KEY `cart_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `colors_name_unique` (`name`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menus_status_sort_order_index` (`status`,`sort_order`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_code_unique` (`order_code`),
  ADD KEY `orders_user_id_foreign` (`user_id`),
  ADD KEY `orders_promotion_id_foreign` (`promotion_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexes for table `order_status_histories`
--
ALTER TABLE `order_status_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_status_histories_order_id_foreign` (`order_id`),
  ADD KEY `order_status_histories_changed_by_foreign` (`changed_by`);

--
-- Indexes for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `password_reset_otps_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `password_reset_otps_verified_token_hash_unique` (`verified_token_hash`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_transaction_code_unique` (`transaction_code`),
  ADD KEY `payments_order_id_foreign` (`order_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `posts_slug_unique` (`slug`),
  ADD KEY `posts_user_id_foreign` (`user_id`),
  ADD KEY `posts_status_published_at_index` (`status`,`published_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_unique` (`slug`),
  ADD KEY `products_category_id_foreign` (`category_id`),
  ADD KEY `products_brand_id_foreign` (`brand_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_variants_product_size_color_unique` (`product_id`,`size_id`,`color_id`),
  ADD UNIQUE KEY `product_variants_sku_unique` (`sku`),
  ADD KEY `product_variants_size_id_foreign` (`size_id`),
  ADD KEY `product_variants_color_id_foreign` (`color_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotions_code_unique` (`code`);

--
-- Indexes for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD UNIQUE KEY `promotion_products_promotion_product_unique` (`promotion_id`,`product_id`),
  ADD KEY `promotion_products_product_id_foreign` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reviews_order_item_id_unique` (`order_item_id`),
  ADD KEY `reviews_user_id_foreign` (`user_id`),
  ADD KEY `reviews_product_id_foreign` (`product_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `sizes`
--
ALTER TABLE `sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sizes_name_unique` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `colors`
--
ALTER TABLE `colors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_status_histories`
--
ALTER TABLE `order_status_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sizes`
--
ALTER TABLE `sizes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_status_histories`
--
ALTER TABLE `order_status_histories`
  ADD CONSTRAINT `order_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_status_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_otps`
--
ALTER TABLE `password_reset_otps`
  ADD CONSTRAINT `password_reset_otps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_color_id_foreign` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`),
  ADD CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_variants_size_id_foreign` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`);

--
-- Constraints for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD CONSTRAINT `promotion_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_products_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
