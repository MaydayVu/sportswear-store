<?php
echo password_hash("Admin@123", PASSWORD_DEFAULT);
?>

-- ======================================
-- TẠO DATABASE
-- ======================================
CREATE DATABASE IF NOT EXISTS sportshop
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sportshop;

-- ======================================
-- BẢNG NGƯỜI DÙNG (USERS)
-- ======================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================================
-- BẢNG DANH MỤC SẢN PHẨM
-- ======================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    is_featured TINYINT DEFAULT 0,
    event_image VARCHAR(255),
    event_start_date DATE,
    event_end_date DATE,
    event_description TEXT,
    display_order INT DEFAULT 0
);

-- ======================================
-- BẢNG SẢN PHẨM (products)
-- ======================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    brand VARCHAR(150),
    category_id INT,
    gender ENUM('nam','nu','unisex'),
    sport_type ENUM('none', 'football', 'running', 'basketball', 'training', 'motosport', 'court_sports') DEFAULT 'none',
    price DECIMAL(10,2) NOT NULL,
    discount_percent INT DEFAULT 0,
    description TEXT,
    material VARCHAR(150),
    featured TINYINT DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- ======================================
-- BẢNG KÍCH THƯỚC SẢN PHẨM (product_sizes)
-- ======================================
CREATE TABLE product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(20) NOT NULL,
    quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size)
);

-- ======================================
-- BẢNG GIỎ HÀNG (carts)
-- ======================================
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    qty INT DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (size_id) REFERENCES product_sizes(id)
);

-- ======================================
-- BẢNG ĐƠN HÀNG
-- ======================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(100),
    status ENUM('pending','paid','shipping','completed','cancel') DEFAULT 'pending',
    fullname VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ======================================
-- BẢNG CHI TIẾT ĐƠN HÀNG
-- ======================================
CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    qty INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (size_id) REFERENCES product_sizes(id)
);

-- ======================================
-- BẢNG BLOG
-- ======================================
CREATE TABLE blog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200),
    content TEXT NOT NULL,
    thumbnail VARCHAR(255),
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- ======================================
-- DỮ LIỆU MẪU
-- ======================================

-- Tạo tài khoản admin
INSERT INTO users (fullname, email, phone, password, role)
VALUES (
    'Quản Trị Viên',
    'admin@sportshop.com',
    '0123456789',
    SHA2('Admin@123', 256),
    'admin'
);




















































-- Test
-- ======================================
-- TẠO DATABASE
-- ======================================
CREATE DATABASE IF NOT EXISTS sportshop
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sportshop;

-- ======================================
-- BẢNG NGƯỜI DÙNG (USERS)
-- ======================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo tài khoản admin
INSERT INTO users (fullname, email, password, role)
VALUES (
    'Quản Trị Viên',
    'admin@sportshop.com',
    -- Mật khẩu Admin@123 (SHA2-256)
    SHA2('Admin@123', 256),
    'admin'
);

-- ======================================
-- BẢNG DANH MỤC SẢN PHẨM
-- ======================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(200) UNIQUE
);

INSERT INTO categories (name, slug) VALUES
('Quần', 'quan'),
('Áo', 'ao'),
('Giày', 'giay'),
('Phụ kiện', 'phu-kien');

-- ======================================
-- BẢNG SẢN PHẨM (products)
-- ======================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    brand VARCHAR(150),            -- Thương hiệu
    category_id INT,               -- Quần / Áo / Giày / Phụ kiện
    gender ENUM('nam','nu','unisex'),
    price DECIMAL(10,2) NOT NULL,
    discount_percent INT DEFAULT 0,
    description TEXT,
    material VARCHAR(150),
    size ENUM('S','M','L','XL'),
    quantity INT DEFAULT 0,
    featured TINYINT DEFAULT 0,     -- Sản phẩm nổi bật
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- ======================================
-- BẢNG GIỎ HÀNG (carts)
-- DÙNG session_id (theo code PHP bạn cung cấp)
-- ======================================
CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    qty INT DEFAULT 1,
    size VARCHAR(20),
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ======================================
-- BẢNG ĐƠN HÀNG
-- ======================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(100),
    status ENUM('pending','paid','shipping','completed','cancel') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Chi tiết đơn hàng
CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    qty INT NOT NULL,
    size VARCHAR(20),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ======================================
-- BẢNG BLOG (theo admin panel)
-- ======================================
CREATE TABLE blog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200),
    content TEXT NOT NULL,
    thumbnail VARCHAR(255),
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- ======================================
-- BẢNG KÍCH THƯỚC SẢN PHẨM (product_sizes)
-- ======================================
CREATE TABLE product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(20) NOT NULL,
    quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size)
);

-- Xóa cột size cũ trong bảng products
ALTER TABLE products DROP COLUMN size;
ALTER TABLE products DROP COLUMN quantity;

-- Cập nhật bảng order_details để tham chiếu đến product_sizes
ALTER TABLE order_details ADD COLUMN size_id INT;
ALTER TABLE order_details ADD FOREIGN KEY (size_id) REFERENCES product_sizes(id);

-- Cập nhật bảng carts để tham chiếu đến product_sizes
ALTER TABLE carts ADD COLUMN size_id INT;
ALTER TABLE carts ADD FOREIGN KEY (size_id) REFERENCES product_sizes(id);

ALTER TABLE products ADD COLUMN sport_type ENUM('none', 'football', 'running', 'basketball', 'training', 'motosport', 'court_sports') DEFAULT 'none';

-- Thêm các cột thông tin giao hàng vào bảng orders
ALTER TABLE orders 
ADD COLUMN fullname VARCHAR(100),
ADD COLUMN phone VARCHAR(20),
ADD COLUMN email VARCHAR(150),
ADD COLUMN address TEXT,
ADD COLUMN note TEXT;

-- Thêm cột phone vào bảng users nếu chưa có
ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email;

-- Thêm các cột mới cho danh mục sự kiện
ALTER TABLE categories 
ADD COLUMN is_featured TINYINT DEFAULT 0,
ADD COLUMN event_image VARCHAR(255),
ADD COLUMN event_start_date DATE,
ADD COLUMN event_end_date DATE,
ADD COLUMN event_description TEXT,
ADD COLUMN display_order INT DEFAULT 0;

-- Tạo bảng wishlist
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

