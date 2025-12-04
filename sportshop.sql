-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 04, 2025 lúc 03:26 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `sportshop`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog`
--

CREATE TABLE `blog` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `blog`
--

INSERT INTO `blog` (`id`, `title`, `slug`, `content`, `thumbnail`, `author_id`, `created_at`) VALUES
(1, 'Cách chọn giày chạy bộ phù hợp', 'cach-chon-giay-chay-bo-phu-hop', 'Bài viết hướng dẫn chi tiết cách chọn giày chạy bộ dựa trên dáng chân, cự ly chạy và mặt đường...', 'blog_running_shoes.jpg', 1, '2025-12-01 18:23:25'),
(2, 'Xu hướng thời trang thể thao 2025', 'xu-huong-thoi-trang-the-thao-2025', 'Tổng hợp những xu hướng thời trang thể thao nổi bật trong năm 2024 từ các thương hiệu lớn...', 'blog_sport_fashion.jpg', 1, '2025-12-01 18:23:25'),
(3, 'Hướng dẫn bảo quản vợt cầu lông đúng cách', 'huong-dan-bao-quan-vot-cau-long-dung-cach', 'Các bước bảo quản vợt cầu lông để duy trì độ bền và hiệu suất thi đấu...', 'blog_badminton.jpg', 1, '2025-12-01 18:23:25'),
(4, 'Lợi ích của việc tập luyện với giày đúng chuyên môn', 'loi-ich-cua-viec-tap-luyen-voi-giay-dung-chuyen-mon', 'Phân tích sự khác biệt khi sử dụng giày chuyên dụng cho từng môn thể thao...', 'blog_training_shoes.jpg', 1, '2025-12-01 18:23:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_id` int(11) NOT NULL,
  `qty` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL COMMENT '1',
  `name` varchar(150) NOT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `is_featured` tinyint(4) DEFAULT 0,
  `event_image` varchar(255) DEFAULT NULL,
  `event_start_date` date DEFAULT NULL,
  `event_end_date` date DEFAULT NULL,
  `event_description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `is_featured`, `event_image`, `event_start_date`, `event_end_date`, `event_description`, `display_order`) VALUES
(1, 'Giày bóng đá', 'giay-bong-da', 1, 'event_nike_mercurial_vapor.jpg', NULL, NULL, 'Giày chuyên dụng cho bóng đá với công nghệ hiện đại', 1),
(2, 'Giày chạy bộ', 'giay-chay-bo', 1, 'event_nike_alphafly_3.jpg', NULL, NULL, 'Giày êm ái cho các runner', 2),
(3, 'Giày bóng rổ', 'giay-bong-ro', 1, 'event_nike_lebron_21.jpg', NULL, NULL, 'Giày hỗ trợ bật cao và di chuyển linh hoạt', 3),
(4, 'Giày tập gym', 'giay-tap-gym', 1, NULL, NULL, NULL, 'Giày ổn định cho tập tạ và cardio', 4),
(5, 'Giày thể thao sân', 'giay-the-thao-san', 0, NULL, NULL, NULL, 'Giày cho tennis, cầu lông, pickleball', 5),
(6, 'Giày motorsport', 'giay-motorsport', 0, NULL, NULL, NULL, 'Giày bảo hộ cho đua xe, mô tô', 6),
(7, 'Áo thể thao', 'ao-the-thao', 1, NULL, NULL, NULL, 'Áo thấm hút mồ hôi các môn thể thao', 7),
(8, 'Quần thể thao', 'quan-the-thao', 0, NULL, NULL, NULL, 'Quần co giãn thoải mái vận động', 8),
(10, 'Vợt tennis', 'vot-tennis', 1, NULL, NULL, NULL, 'Vợt tennis chính hãng Wilson, Babolat', 10),
(11, 'Vợt cầu lông', 'vot-cau-long', 0, NULL, NULL, NULL, 'Vợt cầu lông Yonex, Li-Ning, Victor', 11),
(12, 'Vợt pickleball', 'vot-pickleball', 0, NULL, NULL, NULL, 'Vợt pickleball Selkirk, Paddletek', 12),
(13, 'Mũ bảo hiểm', 'mu-bao-hiem', 0, NULL, NULL, NULL, 'Mũ bảo hiểm thể thao', 13),
(14, 'Găng tay thể thao', 'gang-tay-the-thao', 0, NULL, NULL, NULL, 'Găng tay bảo hộ, tập luyện', 14),
(15, 'Balo túi xách', 'balo-tui-xach', 0, NULL, NULL, NULL, 'Balo, túi đựng dụng cụ thể thao', 15),
(16, 'Tất thể thao', 'tat-the-thao', 0, NULL, NULL, NULL, 'Tất chuyên dụng các môn thể thao', 16),
(17, 'Bình nước', 'binh-nuoc', 0, NULL, NULL, NULL, 'Bình nước thể thao', 17),
(18, 'Băng đeo cổ tay', 'bang-deo-co-tay', 0, NULL, NULL, NULL, 'Băng đeo thấm mồ hôi', 18);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `status` enum('pending','paid','shipping','completed','cancel') DEFAULT 'pending',
  `fullname` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `payment_method`, `status`, `fullname`, `phone`, `email`, `address`, `note`, `created_at`) VALUES
(16, 4, 15055000.00, 'momo', 'pending', 'Nguyễn Văn An', '0312951321', 'nguyenvanan@gmail.com', 'TP HCM', '', '2025-12-04 02:25:46'),
(17, 4, 10255000.00, 'bank', 'paid', 'Nguyễn Văn An', '0312951321', 'nguyenvanan@gmail.com', 'TP HCM', '', '2025-12-04 02:26:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `size` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `size_id`, `price`, `qty`, `size`) VALUES
(1, 16, 7, 20, 4675000.00, 1, '40'),
(2, 16, 19, 105, 380000.00, 1, 'M'),
(3, 16, 1, 2, 4420000.00, 1, '40'),
(4, 16, 6, 15, 5580000.00, 1, '41'),
(5, 17, 6, 15, 5580000.00, 1, '41'),
(6, 17, 7, 20, 4675000.00, 1, '40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `brand` varchar(150) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `gender` enum('nam','nu','unisex') DEFAULT NULL,
  `sport_type` enum('none','football','running','basketball','training','motosport','court_sports') DEFAULT 'none',
  `price` decimal(10,2) NOT NULL,
  `discount_percent` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `material` varchar(150) DEFAULT NULL,
  `featured` tinyint(4) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `brand`, `category_id`, `gender`, `sport_type`, `price`, `discount_percent`, `description`, `material`, `featured`, `image`, `created_at`) VALUES
(1, 'Nike Mercurial Vapor 15 Elite', 'Nike', 1, 'nam', 'football', 5200000.00, 15, 'Giày bóng đá tốc độ cao với công nghệ Flyknit và bộ đế Speedplate', 'Flyknit, TPU', 1, 'nike_mercurial_vapor.jpg', '2025-12-02 06:38:42'),
(2, 'Adidas X Speedportal.1', 'Adidas', 1, 'nam', 'football', 4800000.00, 10, 'Giày bóng đá với công nghệ Carbitex Speedframe cho độ ổn định cao', 'Primeknit, Carbitex', 1, 'adidas_x_speedportal.jpg', '2025-12-02 06:38:42'),
(3, 'Puma Ultra Ultimate', 'Puma', 1, 'unisex', 'football', 4500000.00, 5, 'Giày bóng đá nhẹ nhất với công nghệ MATRYXEVO và GripControl Pro', 'MATRYXEVO, PWRTAPE', 0, 'puma_ultra_ultimate.jpg', '2025-12-02 06:38:42'),
(4, 'Áo đấu Real Madrid 2024', 'Adidas', 7, 'unisex', 'football', 1250000.00, 20, 'Áo đấu sân nhà Real Madrid mùa giải 2024/2025', 'Primegreen polyester', 1, 'real_madrid_jersey.jpg', '2025-12-02 06:38:42'),
(5, 'Áo tập bóng đá Nike Strike', 'Nike', 7, 'nam', 'football', 650000.00, 0, 'Áo tập bóng đá thấm hút mồ hôi Dri-FIT', 'Polyester Dri-FIT', 0, 'nike_strike_jersey.jpg', '2025-12-02 06:38:42'),
(6, 'Nike Alphafly 3', 'Nike', 2, 'unisex', 'running', 6200000.00, 10, 'Giày chạy đua với công nghệ ZoomX và tấm carbon Air Zoom', 'Atomknit, ZoomX, Carbon', 1, 'nike_alphafly_3.jpg', '2025-12-02 06:38:42'),
(7, 'Adidas Adizero Adios Pro 3', 'Adidas', 2, 'unisex', 'running', 5500000.00, 15, 'Giày chạy marathon với công nghệ Energyrods và Lightstrike Pro', 'Celermesh, Lightstrike Pro', 1, 'adidas_adios_pro_3.jpg', '2025-12-02 06:38:42'),
(8, 'Asics MetaSpeed Sky+', 'Asics', 2, 'nam', 'running', 5800000.00, 0, 'Giày chạy đua với công nghệ FF Blast Turbo và tấm carbon full-length', 'Engineered mesh, FF Blast Turbo', 0, 'asics_metaspeed_sky.jpg', '2025-12-02 06:38:42'),
(9, 'Quần short chạy bộ Nike Dri-FIT', 'Nike', 8, 'nam', 'running', 420000.00, 0, 'Quần short chạy bộ thoáng khí với túi đựng điện thoại', 'Polyester Dri-FIT', 0, 'nike_running_shorts.jpg', '2025-12-02 06:38:42'),
(10, 'Quần legging chạy bộ nữ Adidas', 'Adidas', 8, 'nu', 'running', 550000.00, 10, 'Quần legging chạy bộ nữ ôm sát, co giãn 4 chiều', 'Primeblue polyester', 0, 'adidas_running_leggings.jpg', '2025-12-02 06:38:42'),
(11, 'Nike LeBron 21 \"Gloria\"', 'Nike', 3, 'nam', 'basketball', 5100000.00, 20, 'Giày bóng rổ signature LeBron James với công nghệ Zoom Air và Cushlon', 'KnitPosite 2.0, Zoom Air', 1, 'nike_lebron_21.jpg', '2025-12-02 06:38:42'),
(12, 'Jordan Luka 2', 'Jordan', 3, 'unisex', 'basketball', 3800000.00, 10, 'Giày bóng rổ Luka Doncic với công nghệ Formula 23 foam', 'Engineered mesh, Formula 23', 1, 'jordan_luka_2.jpg', '2025-12-02 06:38:42'),
(13, 'Under Armour Curry 11 \"Game Day\"', 'Under Armour', 3, 'nam', 'basketball', 4600000.00, 5, 'Giày bóng rổ Stephen Curry với công nghệ UA Flow và carbon fiber plate', 'UA Warp, UA Flow', 0, 'ua_curry_11.jpg', '2025-12-02 06:38:42'),
(14, 'Áo bóng rổ NBA Swingman Lakers', 'Nike', 7, 'unisex', 'basketball', 1350000.00, 15, 'Áo bóng rổ Los Angeles Lakers phiên bản Swingman', 'Polyester dri-FIT', 1, 'lakers_jersey.jpg', '2025-12-02 06:38:42'),
(15, 'Quần short bóng rổ Jordan', 'Jordan', 8, 'nam', 'basketball', 680000.00, 0, 'Quần short bóng rổ với thiết kế iconic Jumpman', 'Polyester, spandex', 0, 'jordan_basketball_shorts.jpg', '2025-12-02 06:38:42'),
(16, 'Nike Metcon 9', 'Nike', 4, 'unisex', 'training', 3200000.00, 10, 'Giày tập CrossFit với đế ổn định và độ bám tốt', 'Mesh, React foam, rubber', 1, 'nike_metcon_9.jpg', '2025-12-02 06:38:42'),
(17, 'Reebok Nano X4', 'Reebok', 4, 'unisex', 'training', 2900000.00, 15, 'Giày tập đa năng với công nghệ Floatride Energy và Flexweave', 'Flexweave, Floatride Energy', 1, 'reebok_nano_x4.jpg', '2025-12-02 06:38:42'),
(18, 'Adidas Dropset 3 Trainer', 'Adidas', 4, 'nam', 'training', 2700000.00, 0, 'Giày tập tạ với đế rộng và hỗ trợ cổ chân', 'Mesh, Bounce foam', 0, 'adidas_dropset_3.jpg', '2025-12-02 06:38:42'),
(19, 'Áo tank top tập gym Nike Pro', 'Nike', 7, 'nam', 'training', 380000.00, 0, 'Áo tank top thoáng khí cho tập tạ', 'Polyester, spandex', 0, 'nike_pro_tank.jpg', '2025-12-02 06:38:42'),
(20, 'Set đồ tập nữ Adidas Training', 'Adidas', 7, 'nu', 'training', 850000.00, 10, 'Set áo và quần legging tập gym nữ', 'Primeblue polyester', 1, 'adidas_training_set.jpg', '2025-12-02 06:38:42'),
(21, 'Giày đua xe Alpinestars Tech-1K', 'Alpinestars', 6, 'nam', 'motosport', 4200000.00, 0, 'Giày đua xe thể thao với bảo vệ mắt cá chân', 'Microfiber, TPU protection', 1, 'alpinestars_tech_1k.jpg', '2025-12-02 06:38:42'),
(22, 'Giày motorsport Dainese Street Biker', 'Dainese', 6, 'unisex', 'motosport', 3800000.00, 10, 'Giày đi phượt và đô thị với thiết kế thể thao', 'Full grain leather', 0, 'dainese_street_biker.jpg', '2025-12-02 06:38:42'),
(23, 'Giày bảo hộ TCX Street Ace', 'TCX', 6, 'unisex', 'motosport', 3500000.00, 5, 'Giày bảo hộ phong cách casual với công nghệ Gore-Tex', 'Leather, Gore-Tex', 0, 'tcx_street_ace.jpg', '2025-12-02 06:38:42'),
(24, 'Mũ bảo hiểm Shoei X-Spirit 4', 'Shoei', 13, 'unisex', 'motosport', 12500000.00, 15, 'Mũ bảo hiểm đua xe cao cấp với công nghệ CWR-F2', 'AIM+ shell, CWR-F2 visor', 1, 'shoei_x_spirit_4.jpg', '2025-12-02 06:38:42'),
(25, 'Găng tay đua xe Alpinestars SMX-2', 'Alpinestars', 14, 'unisex', 'motosport', 1850000.00, 0, 'Găng tay đua xe với bảo vệ lòng bàn tay và đốt ngón tay', 'Kangaroo leather, carbon fiber', 0, 'alpinestars_smx_2.jpg', '2025-12-02 06:38:42'),
(26, 'Nike Court Air Zoom Vapor Pro 2', 'Nike', 5, 'unisex', 'court_sports', 3100000.00, 10, 'Giày tennis với công nghệ Zoom Air và độ ổn định cao', 'Mesh, Zoom Air', 1, 'nike_vapor_pro_2.jpg', '2025-12-02 06:38:43'),
(27, 'Adidas Adizero Cybersonic', 'Adidas', 5, 'unisex', 'court_sports', 2800000.00, 15, 'Giày tennis tốc độ với công nghệ Lightstrike', 'Celermesh, Lightstrike', 0, 'adidas_cybersonic.jpg', '2025-12-02 06:38:43'),
(28, 'Wilson Pro Staff RF97 v14', 'Wilson', 10, 'unisex', 'court_sports', 8200000.00, 0, 'Vợt tennis signature Roger Federer với công nghệ Braid 45', 'Graphite, braided carbon', 1, 'wilson_prostaff_rf97.jpg', '2025-12-02 06:38:43'),
(29, 'Babolat Pure Aero 2024', 'Babolat', 10, 'unisex', 'court_sports', 6500000.00, 10, 'Vợt tennis với công nghệ FSI Spin và SWX Pure Feel', 'Graphite, SWX', 0, 'babolat_pure_aero.jpg', '2025-12-02 06:38:43'),
(30, 'Yonex Astrox 100 ZZ', 'Yonex', 11, 'unisex', 'court_sports', 5200000.00, 15, 'Vợt cầu lông tấn công với công nghệ Namd và Rotational Generator System', 'HM Graphite, Namd, Tungsten', 1, 'yonex_astrox_100zz.jpg', '2025-12-02 06:38:43'),
(31, 'Li-Ning Turbo Charging 75C', 'Li-Ning', 11, 'unisex', 'court_sports', 3800000.00, 0, 'Vợt cầu lông cân bằng với công nghệ TB NANO và High Modulus Graphite', 'High Modulus Graphite', 0, 'lining_turbo_charging.jpg', '2025-12-02 06:38:43'),
(32, 'Selkirk Vanguard Power Air', 'Selkirk', 12, 'unisex', 'court_sports', 4200000.00, 10, 'Vợt pickleball với công nghệ Air CORE và Fiberflex Face', 'Polymer core, fiberglass face', 1, 'selkirk_vanguard.jpg', '2025-12-02 06:38:43'),
(33, 'Paddletek Tempest Wave Pro', 'Paddletek', 12, 'unisex', 'court_sports', 3500000.00, 5, 'Vợt pickleball với thiết kế wave và độ kiểm soát cao', 'Polymer core, graphite face', 0, 'paddletek_tempest.jpg', '2025-12-02 06:38:43'),
(34, 'Yonex Power Cushion 65 Z3', 'Yonex', 5, 'unisex', 'court_sports', 2600000.00, 0, 'Giày cầu lông với công nghệ Power Cushion+', 'Mesh, Power Cushion+', 0, 'yonex_power_cushion.jpg', '2025-12-02 06:38:43'),
(35, 'Mizuno Wave Claw 2', 'Mizuno', 5, 'nu', 'court_sports', 2400000.00, 10, 'Giày cầu lông nữ với công nghệ Wave và DynamotionFit', 'Mesh, Wave plate', 0, 'mizuno_wave_claw.jpg', '2025-12-02 06:38:43'),
(36, 'Tất bóng đá Nike Grip Crew', 'Nike', 16, 'unisex', 'none', 220000.00, 0, 'Tất bóng đá chống trượt với đệm dày ở gót và mũi chân', 'Cotton, polyester, silicone grip', 0, 'nike_grip_socks.jpg', '2025-12-02 06:38:43'),
(37, 'Tất chạy bộ Adidas Cushioned', 'Adidas', 16, 'unisex', 'none', 180000.00, 0, 'Tất chạy bộ đệm êm với công nghệ climacool', 'Climacool polyester', 0, 'adidas_cushioned_socks.jpg', '2025-12-02 06:38:43'),
(38, 'Bình nước Nike Elemental 1L', 'Nike', 17, 'unisex', 'none', 350000.00, 10, 'Bình nước 1 lít với ống hút tích hợp', 'BPA-free Tritan', 1, 'nike_elemental_bottle.jpg', '2025-12-02 06:38:43'),
(39, 'Bình nước thể thao CamelBak Chute', 'CamelBak', 17, 'unisex', 'none', 420000.00, 0, 'Bình nước 750ml với van mở một tay', 'Stainless steel', 0, 'camelbak_chute.jpg', '2025-12-02 06:38:43'),
(40, 'Balo thể thao Under Armour Undeniable 4.0', 'Under Armour', 15, 'unisex', 'none', 950000.00, 15, 'Balo 39L với ngăn đựng giày riêng biệt', 'Polyester, heatgear', 1, 'ua_undeniable_backpack.jpg', '2025-12-02 06:38:43'),
(41, 'Túi vợt tennis Babolat 12 Pack', 'Babolat', 15, 'unisex', 'none', 1850000.00, 0, 'Túi đựng 12 vợt tennis với ngăn laptop', 'Polyester, EVA padding', 0, 'babolat_tennis_bag.jpg', '2025-12-02 06:38:43'),
(42, 'Băng đeo cổ tay Nike Swoosh', 'Nike', 18, 'unisex', 'none', 120000.00, 0, 'Băng đeo cổ tay thấm mồ hôi với logo Nike', 'Cotton, terry cloth', 0, 'nike_swoosh_wristband.jpg', '2025-12-02 06:38:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_sizes`
--

CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(20) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_id`, `size`, `quantity`, `created_at`) VALUES
(1, 1, '39', 8, '2025-12-02 06:38:56'),
(2, 1, '40', 11, '2025-12-02 06:38:56'),
(3, 1, '41', 15, '2025-12-02 06:38:56'),
(4, 1, '42', 10, '2025-12-02 06:38:56'),
(5, 1, '43', 6, '2025-12-02 06:38:56'),
(6, 2, '40', 10, '2025-12-02 06:38:56'),
(7, 2, '41', 12, '2025-12-02 06:38:56'),
(8, 2, '42', 14, '2025-12-02 06:38:56'),
(9, 2, '43', 8, '2025-12-02 06:38:56'),
(10, 3, '39', 6, '2025-12-02 06:38:56'),
(11, 3, '40', 9, '2025-12-02 06:38:56'),
(12, 3, '41', 11, '2025-12-02 06:38:56'),
(13, 3, '42', 7, '2025-12-02 06:38:56'),
(14, 6, '40', 15, '2025-12-02 06:38:56'),
(15, 6, '41', 16, '2025-12-02 06:38:56'),
(16, 6, '42', 20, '2025-12-02 06:38:56'),
(17, 6, '43', 12, '2025-12-02 06:38:56'),
(18, 6, '44', 5, '2025-12-02 06:38:56'),
(19, 7, '39', 10, '2025-12-02 06:38:56'),
(20, 7, '40', 11, '2025-12-02 06:38:56'),
(21, 7, '41', 16, '2025-12-02 06:38:56'),
(22, 7, '42', 11, '2025-12-02 06:38:56'),
(23, 8, '40', 8, '2025-12-02 06:38:56'),
(24, 8, '41', 10, '2025-12-02 06:38:56'),
(25, 8, '42', 12, '2025-12-02 06:38:56'),
(26, 8, '43', 7, '2025-12-02 06:38:56'),
(27, 11, '41', 10, '2025-12-02 06:38:56'),
(28, 11, '42', 14, '2025-12-02 06:38:56'),
(29, 11, '43', 16, '2025-12-02 06:38:56'),
(30, 11, '44', 9, '2025-12-02 06:38:56'),
(31, 11, '45', 4, '2025-12-02 06:38:56'),
(32, 12, '40', 8, '2025-12-02 06:38:56'),
(33, 12, '41', 11, '2025-12-02 06:38:56'),
(34, 12, '42', 13, '2025-12-02 06:38:56'),
(35, 12, '43', 7, '2025-12-02 06:38:56'),
(36, 13, '41', 9, '2025-12-02 06:38:56'),
(37, 13, '42', 12, '2025-12-02 06:38:56'),
(38, 13, '43', 14, '2025-12-02 06:38:56'),
(39, 13, '44', 8, '2025-12-02 06:38:56'),
(40, 16, '39', 12, '2025-12-02 06:38:56'),
(41, 16, '40', 15, '2025-12-02 06:38:56'),
(42, 16, '41', 18, '2025-12-02 06:38:56'),
(43, 16, '42', 10, '2025-12-02 06:38:56'),
(44, 17, '40', 10, '2025-12-02 06:38:56'),
(45, 17, '41', 13, '2025-12-02 06:38:56'),
(46, 17, '42', 15, '2025-12-02 06:38:56'),
(47, 17, '43', 9, '2025-12-02 06:38:56'),
(48, 18, '39', 8, '2025-12-02 06:38:56'),
(49, 18, '40', 11, '2025-12-02 06:38:56'),
(50, 18, '41', 14, '2025-12-02 06:38:56'),
(51, 18, '42', 8, '2025-12-02 06:38:56'),
(52, 21, '41', 5, '2025-12-02 06:38:56'),
(53, 21, '42', 7, '2025-12-02 06:38:56'),
(54, 21, '43', 9, '2025-12-02 06:38:56'),
(55, 21, '44', 4, '2025-12-02 06:38:56'),
(56, 22, '40', 6, '2025-12-02 06:38:56'),
(57, 22, '41', 8, '2025-12-02 06:38:56'),
(58, 22, '42', 10, '2025-12-02 06:38:56'),
(59, 22, '43', 5, '2025-12-02 06:38:56'),
(60, 23, '39', 4, '2025-12-02 06:38:56'),
(61, 23, '40', 6, '2025-12-02 06:38:56'),
(62, 23, '41', 8, '2025-12-02 06:38:56'),
(63, 23, '42', 5, '2025-12-02 06:38:56'),
(64, 26, '40', 10, '2025-12-02 06:38:56'),
(65, 26, '41', 13, '2025-12-02 06:38:56'),
(66, 26, '42', 15, '2025-12-02 06:38:56'),
(67, 26, '43', 9, '2025-12-02 06:38:56'),
(68, 27, '39', 8, '2025-12-02 06:38:56'),
(69, 27, '40', 11, '2025-12-02 06:38:56'),
(70, 27, '41', 14, '2025-12-02 06:38:56'),
(71, 27, '42', 8, '2025-12-02 06:38:56'),
(72, 31, '38', 6, '2025-12-02 06:38:56'),
(73, 31, '39', 9, '2025-12-02 06:38:56'),
(74, 31, '40', 12, '2025-12-02 06:38:56'),
(75, 31, '41', 7, '2025-12-02 06:38:56'),
(76, 32, '37', 5, '2025-12-02 06:38:56'),
(77, 32, '38', 8, '2025-12-02 06:38:56'),
(78, 32, '39', 10, '2025-12-02 06:38:56'),
(79, 32, '40', 6, '2025-12-02 06:38:56'),
(80, 4, 'S', 20, '2025-12-02 06:38:56'),
(81, 4, 'M', 25, '2025-12-02 06:38:56'),
(82, 4, 'L', 18, '2025-12-02 06:38:56'),
(83, 4, 'XL', 12, '2025-12-02 06:38:56'),
(84, 5, 'M', 15, '2025-12-02 06:38:56'),
(85, 5, 'L', 20, '2025-12-02 06:38:56'),
(86, 5, 'XL', 15, '2025-12-02 06:38:56'),
(87, 5, 'XXL', 8, '2025-12-02 06:38:56'),
(88, 9, 'S', 12, '2025-12-02 06:38:56'),
(89, 9, 'M', 16, '2025-12-02 06:38:56'),
(90, 9, 'L', 14, '2025-12-02 06:38:56'),
(91, 9, 'XL', 10, '2025-12-02 06:38:56'),
(92, 10, 'XS', 8, '2025-12-02 06:38:56'),
(93, 10, 'S', 12, '2025-12-02 06:38:56'),
(94, 10, 'M', 15, '2025-12-02 06:38:56'),
(95, 10, 'L', 10, '2025-12-02 06:38:56'),
(96, 14, 'S', 18, '2025-12-02 06:38:56'),
(97, 14, 'M', 22, '2025-12-02 06:38:56'),
(98, 14, 'L', 20, '2025-12-02 06:38:56'),
(99, 14, 'XL', 15, '2025-12-02 06:38:56'),
(100, 15, 'M', 10, '2025-12-02 06:38:56'),
(101, 15, 'L', 14, '2025-12-02 06:38:56'),
(102, 15, 'XL', 12, '2025-12-02 06:38:56'),
(103, 15, 'XXL', 6, '2025-12-02 06:38:56'),
(104, 19, 'S', 8, '2025-12-02 06:38:56'),
(105, 19, 'M', 11, '2025-12-02 06:38:56'),
(106, 19, 'L', 10, '2025-12-02 06:38:56'),
(107, 19, 'XL', 6, '2025-12-02 06:38:56'),
(108, 20, 'XS', 6, '2025-12-02 06:38:56'),
(109, 20, 'S', 10, '2025-12-02 06:38:56'),
(110, 20, 'M', 12, '2025-12-02 06:38:56'),
(111, 20, 'L', 8, '2025-12-02 06:38:56'),
(112, 24, 'S (55-56cm)', 4, '2025-12-02 06:38:56'),
(113, 24, 'M (57-58cm)', 6, '2025-12-02 06:38:56'),
(114, 24, 'L (59-60cm)', 5, '2025-12-02 06:38:56'),
(115, 24, 'XL (61-62cm)', 3, '2025-12-02 06:38:56'),
(116, 25, 'S', 8, '2025-12-02 06:38:56'),
(117, 25, 'M', 12, '2025-12-02 06:38:56'),
(118, 25, 'L', 10, '2025-12-02 06:38:56'),
(119, 25, 'XL', 6, '2025-12-02 06:38:56'),
(120, 28, 'Grip 2 (4 1/4)', 5, '2025-12-02 06:38:56'),
(121, 28, 'Grip 3 (4 3/8)', 7, '2025-12-02 06:38:56'),
(122, 28, 'Grip 4 (4 1/2)', 4, '2025-12-02 06:38:56'),
(123, 29, 'Grip 2 (4 1/4)', 6, '2025-12-02 06:38:56'),
(124, 29, 'Grip 3 (4 3/8)', 8, '2025-12-02 06:38:56'),
(125, 29, 'Grip 4 (4 1/2)', 5, '2025-12-02 06:38:56'),
(126, 30, 'G3', 4, '2025-12-02 06:38:56'),
(127, 30, 'G4', 6, '2025-12-02 06:38:56'),
(128, 30, 'G5', 5, '2025-12-02 06:38:56'),
(129, 33, 'G4', 3, '2025-12-02 06:38:56'),
(130, 33, 'G5', 4, '2025-12-02 06:38:56'),
(131, 33, 'G6', 3, '2025-12-02 06:38:56'),
(132, 34, 'Standard Grip', 5, '2025-12-02 06:38:56'),
(133, 34, 'Large Grip', 4, '2025-12-02 06:38:56'),
(134, 35, 'Free Size', 30, '2025-12-02 06:38:56'),
(135, 36, 'Free Size', 25, '2025-12-02 06:38:56'),
(136, 37, '750ml', 20, '2025-12-02 06:38:56'),
(137, 37, '1L', 15, '2025-12-02 06:38:56'),
(138, 38, '500ml', 18, '2025-12-02 06:38:56'),
(139, 38, '750ml', 12, '2025-12-02 06:38:56'),
(140, 39, 'One Size', 8, '2025-12-02 06:38:56'),
(141, 40, 'One Size', 5, '2025-12-02 06:38:56'),
(142, 41, 'One Size', 25, '2025-12-02 06:38:56'),
(145, 42, 'M', 56, '2025-12-04 01:04:49'),
(146, 42, '30', 5, '2025-12-04 01:04:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Quản Trị Viên', 'admin@sportshop.com', '0123456789', '$2y$10$.ccu9MfvuAOgN1iftFXkU.AO02ZTcXl2YpWcL0Al9zqQrE0r5JKRC', 'admin', '2025-12-01 18:21:19'),
(4, 'Nguyễn Văn An', 'nguyenvanan@gmail.com', '0912345678', '$2y$10$yVSAWCKlBs4oSY4fRu4qou8d6KpOqK67yO5RJBNVH15q19Nw.btxi', 'user', '2025-12-03 06:57:03');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(1, 4, 19, '2025-12-03 10:27:31'),
(2, 4, 6, '2025-12-04 02:26:16');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Chỉ mục cho bảng `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `size_id` (`size_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `size_id` (`size_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_size` (`product_id`,`size`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `blog`
--
ALTER TABLE `blog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '1', AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT cho bảng `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `blog`
--
ALTER TABLE `blog`
  ADD CONSTRAINT `blog_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `product_sizes` (`id`);

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_details_ibfk_3` FOREIGN KEY (`size_id`) REFERENCES `product_sizes` (`id`);

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Các ràng buộc cho bảng `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
