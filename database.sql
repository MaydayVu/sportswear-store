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

-- ======================================
-- DỮ LIỆU MẪU
-- ======================================

-- Tạo tài khoản admin
INSERT INTO users (fullname, email, phone, password, role)
VALUES 
(
    'Quản Trị Viên',
    'admin@sportshop.com',
    '0123456789',
    SHA2('Admin@123', 256),
    'admin'
),
(
    'Nguyễn Văn An',
    'nguyenvanan@example.com',
    '0912345678',
    SHA2('User@123', 256),
    'user'
);