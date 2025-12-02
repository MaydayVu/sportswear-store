<?php
session_start();
include "config.php";

// Tối ưu query - chỉ lấy các field cần thiết 
$featured_products = $conn->query("
    SELECT p.id, p.name, p.price, p.discount_percent, p.image,
           c.name AS category_name,
           (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) as total_quantity
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.featured = 1 
    AND (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) > 0 
    ORDER BY p.created_at DESC 
    LIMIT 8
");

// Lấy sản phẩm mới với ít field hơn 
$new_products = $conn->query("
    SELECT p.id, p.name, p.price, p.discount_percent, p.image,
           c.name AS category_name,
           (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) as total_quantity
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) > 0 
    ORDER BY p.created_at DESC 
    LIMIT 8
");

// Lấy danh mục thường 
$categories = $conn->query("
    SELECT id, name, slug, event_image 
    FROM categories 
    WHERE is_featured = 0 
    ORDER BY name ASC 
    LIMIT 6
");

// Lấy danh mục sự kiện nổi bật
$featured_categories = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    WHERE c.is_featured = 1 
    AND (c.event_end_date IS NULL OR c.event_end_date >= CURDATE())
    GROUP BY c.id 
    ORDER BY c.display_order ASC, c.name ASC
    LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sport Fashion - Thời trang thể thao</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS được tách riêng và tối ưu -->
    <style>
        :root {
            --primary-black: #000;
            --primary-white: #fff;
            --accent-red: #e4002b;
            --gray-light: #f5f5f5;
            --gray-medium: #767676;
            --gold-color: #ffd700;
            --event-color: #ff6b35;
        }

        /* Chỉ các style cần thiết cho above-the-fold content */
        .hero-section {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: var(--primary-white);
            padding: 80px 0;
            text-align: center;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .btn-hero {
            background: var(--primary-white);
            color: var(--primary-black);
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-hero:hover {
            background: var(--accent-red);
            color: var(--primary-white);
            transform: translateY(-2px);
        }

        /* Style cho Featured Categories */
        .featured-categories-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
        }

        .event-countdown {
            background: var(--event-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }

        .event-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--gold-color);
            color: #000;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 700;
            z-index: 2;
        }

        .coming-soon-badge {
            background: #6c757d;
            color: white;
        }

        .last-day-badge {
            background: var(--accent-red);
            color: white;
        }

        .featured-category-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            position: relative;
            color: #333;
        }

        .featured-category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }

        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .event-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .event-content {
            padding: 25px;
        }

        .event-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #000;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .event-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        .event-dates {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--event-color);
        }

        .event-date-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #555;
        }

        .event-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .event-product-count {
            background: #e9ecef;
            color: #495057;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-event {
            display: block;
            width: 100%;
            padding: 12px;
            background: #000;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s ease;
            border: 2px solid #000;
        }

        .btn-event:hover {
            background: var(--event-color);
            border-color: var(--event-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-preview {
            background: transparent;
            color: #000;
            border: 2px solid #000;
        }

        .btn-preview:hover {
            background: #000;
            color: white;
        }

        .event-status {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }

        .status-active {
            background: #28a745;
            color: white;
        }

        .status-upcoming {
            background: #ffc107;
            color: #000;
        }

        .status-ending {
            background: #dc3545;
            color: white;
        }

        /* Style cho Category Section */
        .category-section {
            padding: 60px 0;
            background: var(--gray-light);
        }

        .section-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
            color: var(--primary-black);
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .category-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 300px;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .category-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .category-card:hover .category-image {
            transform: scale(1.05);
        }

        .placeholder-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
        }

        .placeholder-content {
            text-align: center;
            z-index: 2;
        }

        .placeholder-text {
            font-weight: bold;
            font-size: 1.3rem;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            padding: 25px 20px;
            transform: translateY(100%);
            transition: all 0.3s ease;
        }

        .category-card:hover .category-overlay {
            transform: translateY(0);
        }

        .category-overlay h3 {
            margin: 0 0 10px 0;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .category-arrow {
            font-size: 1.2rem;
            font-weight: bold;
            transition: transform 0.3s ease;
            display: inline-block;
        }

        .category-card:hover .category-arrow {
            transform: translateX(5px);
        }

        .category-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
    
    <!-- Defer loading của CSS nặng -->
    <link rel="stylesheet" href="assets/css/style.css" media="print" onload="this.media='all'">
</head>
<body>
    <?php include "includes/header.php"; ?>

    <!-- Hero Section - Hiển thị đầu tiên -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>SPORT FASHION</h1>
                <p>Khám phá bộ sưu tập mới nhất</p>
                <a href="#products" class="btn-hero">Mua sắm ngay</a>
            </div>
        </div>
    </section>

    <!-- Featured Categories Section - Sự kiện nổi bật -->
    <?php if ($featured_categories->num_rows > 0): ?>
    <section class="featured-categories-section">
        <div class="container">
            <h2 class="section-title text-white mb-4">🔥 Sự kiện nổi bật</h2>
            <p class="text-center text-light mb-5 opacity-75">Khám phá các chương trình ưu đãi đặc biệt và sự kiện độc quyền</p>
            
            <div class="row g-4">
                <?php while ($category = $featured_categories->fetch_assoc()): 
                    $is_active_event = true;
                    $is_ending_soon = false;
                    $is_upcoming = false;
                    
                    // Kiểm tra trạng thái sự kiện
                    if ($category['event_start_date'] && $category['event_start_date'] > date('Y-m-d')) {
                        $is_active_event = false;
                        $is_upcoming = true;
                    } elseif ($category['event_end_date']) {
                        $days_remaining = floor((strtotime($category['event_end_date']) - time()) / (60 * 60 * 24));
                        if ($days_remaining <= 3 && $days_remaining >= 0) {
                            $is_ending_soon = true;
                        }
                    }
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="featured-category-card">
                        <!-- Badge trạng thái sự kiện -->
                        <?php if ($is_upcoming): ?>
                            <span class="event-badge coming-soon-badge">
                                <i class="fas fa-clock me-1"></i>Sắp diễn ra
                            </span>
                        <?php elseif ($is_ending_soon): ?>
                            <span class="event-badge last-day-badge">
                                <i class="fas fa-bolt me-1"></i>Sắp kết thúc
                            </span>
                        <?php else: ?>
                            <span class="event-badge">
                                <i class="fas fa-star me-1"></i>ĐANG DIỄN RA
                            </span>
                        <?php endif; ?>
                        
                        <!-- Ảnh sự kiện -->
                        <?php if ($category['event_image']): ?>
                            <img src="assets/images/categories/<?= htmlspecialchars($category['event_image']) ?>" 
                                 alt="<?= htmlspecialchars($category['name']) ?>" 
                                 class="event-image">
                        <?php else: ?>
                            <div class="event-placeholder">
                                <i class="fas fa-calendar-star"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="event-content">
                            <h3 class="event-title"><?= htmlspecialchars($category['name']) ?></h3>
                            
                            <?php if ($category['event_description']): ?>
                                <p class="event-description"><?= htmlspecialchars($category['event_description']) ?></p>
                            <?php endif; ?>
                            
                            <!-- Ngày sự kiện -->
                            <?php if ($category['event_start_date'] || $category['event_end_date']): ?>
                                <div class="event-dates">
                                    <?php if ($category['event_start_date']): ?>
                                        <div class="event-date-item">
                                            <i class="fas fa-play-circle text-success"></i>
                                            <span>Bắt đầu: <?= date('d/m/Y', strtotime($category['event_start_date'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($category['event_end_date']): ?>
                                        <div class="event-date-item">
                                            <i class="fas fa-flag-checkered text-danger"></i>
                                            <span>Kết thúc: <?= date('d/m/Y', strtotime($category['event_end_date'])) ?></span>
                                        </div>
                                        
                                        <!-- Đếm ngược (nếu sắp kết thúc) -->
                                        <?php if ($is_ending_soon): ?>
                                            <div class="event-date-item mt-2">
                                                <i class="fas fa-hourglass-half text-warning"></i>
                                                <span class="fw-bold">Còn <?= $days_remaining ?> ngày</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="event-stats">
                                <span class="event-product-count">
                                    <i class="fas fa-cube me-1"></i>
                                    <?= $category['product_count'] ?> sản phẩm
                                </span>
                                
                                <span class="event-status <?= $is_upcoming ? 'status-upcoming' : ($is_ending_soon ? 'status-ending' : 'status-active') ?>">
                                    <?= $is_upcoming ? 'Sắp diễn ra' : ($is_ending_soon ? 'Sắp kết thúc' : 'Đang diễn ra') ?>
                                </span>
                            </div>
                            
                            <a href="products.php?category=<?= $category['id'] ?>" 
                               class="btn-event <?= $is_upcoming ? 'btn-preview' : '' ?>">
                                <i class="fas fa-<?= $is_upcoming ? 'eye' : 'shopping-cart' ?> me-2"></i>
                                <?= $is_upcoming ? 'Xem trước' : 'Mua ngay' ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Call to Action -->
            <div class="text-center mt-5">
                <a href="categories.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-list me-2"></i>Xem tất cả danh mục
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Danh mục thường - ĐÃ CẬP NHẬT HIỂN THỊ ẢNH -->
    <section class="category-section">
        <div class="container">
            <h2 class="section-title">Danh mục sản phẩm</h2>
            <div class="category-grid">
                <?php while($category = $categories->fetch_assoc()): ?>
                <div class="category-card">
                    <?php if ($category['event_image']): ?>
                        <!-- Hiển thị ảnh từ database nếu có -->
                        <img src="assets/images/categories/<?= htmlspecialchars($category['event_image']) ?>" 
                             alt="<?= htmlspecialchars($category['name']) ?>" 
                             class="category-image lazy"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                    <?php else: ?>
                        <!-- Placeholder với icon đẹp hơn -->
                        <div class="category-image placeholder-image">
                            <div class="placeholder-content">
                                <?php
                                // Icon tương ứng với từng danh mục
                                $icons = [
                                    'áo' => 'tshirt',
                                    'quần' => 'user',
                                    'giày' => 'shoe-prints', 
                                    'phụ kiện' => 'glasses'
                                ];
                                $icon = 'tag';
                                $category_lower = strtolower($category['name']);
                                foreach ($icons as $key => $value) {
                                    if (strpos($category_lower, $key) !== false) {
                                        $icon = $value;
                                        break;
                                    }
                                }
                                ?>
                                <i class="fas fa-<?= $icon ?> fa-3x mb-3"></i>
                                <div class="placeholder-text"><?= htmlspecialchars($category['name']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="category-overlay">
                        <h3><?= htmlspecialchars($category['name']) ?></h3>
                        <a href="products.php?category=<?= $category['id'] ?>" class="category-link">
                            Khám phá <span class="category-arrow">→</span>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Nút xem thêm -->
            <div class="text-center mt-4">
                <a href="categories.php" class="btn btn-outline-dark btn-lg">
                    <i class="fas fa-th-large me-2"></i>Xem tất cả danh mục
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="products" class="products-section">
        <div class="container">
            <h2 class="section-title">Sản phẩm nổi bật</h2>
            <?php if ($featured_products && $featured_products->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($product = $featured_products->fetch_assoc()): 
                        $current_price = $product['price'];
                        $has_discount = $product['discount_percent'] > 0;
                        if ($has_discount) {
                            $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                        }
                    ?>
                    <div class="product-card">
                        <!-- Lazy loading cho ảnh -->
                        <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="product-image lazy"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-category">
                                <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                            </div>
                            <div class="product-price">
                                <span class="current-price"><?= number_format($current_price) ?>₫</span>
                                <?php if ($has_discount): ?>
                                    <span class="original-price text-muted text-decoration-line-through">
                                        <?= number_format($product['price']) ?>₫
                                    </span>
                                    <span class="discount-badge">-<?= $product['discount_percent'] ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-stock">
                                <small class="<?= $product['total_quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <i class="fas <?= $product['total_quantity'] > 0 ? 'fa-check' : 'fa-times' ?> me-1"></i>
                                    <?= $product['total_quantity'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                                </small>
                            </div>
                            <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-product">
                                Xem chi tiết
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="text-muted">Chưa có sản phẩm nổi bật</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Các section khác được load sau -->
    <div id="remaining-sections">
        <!-- Banner Section -->
        <section class="banner-section">
            <div class="container">
                <div class="banner-content">
                    <h2>MEMBER DAYS</h2>
                    <p>Ưu đãi đặc biệt dành cho thành viên</p>
                    <a href="auth/register.php" class="btn-hero">Đăng ký thành viên</a>
                </div>
            </div>
        </section>

        <!-- New Arrivals -->
        <section class="products-section">
            <div class="container">
                <h2 class="section-title">Hàng mới về</h2>
                <?php if ($new_products && $new_products->num_rows > 0): ?>
                    <div class="products-grid">
                        <?php 
                        // Reset pointer để lặp lại
                        $new_products->data_seek(0);
                        while ($product = $new_products->fetch_assoc()): 
                            $current_price = $product['price'];
                            $has_discount = $product['discount_percent'] > 0;
                            if ($has_discount) {
                                $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                            }
                        ?>
                        <div class="product-card">
                            <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="product-image lazy"
                                 loading="lazy"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgFmaWxsPSIjNjY2IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+xJDhuqNoIHPhuqNuIHBhuqdtPC90ZXh0Pjwvc3ZnPg=='">
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="product-category">
                                    <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                                </div>
                                <div class="product-price">
                                    <span class="current-price"><?= number_format($current_price) ?>₫</span>
                                    <?php if ($has_discount): ?>
                                        <span class="original-price text-muted text-decoration-line-through">
                                            <?= number_format($product['price']) ?>₫
                                        </span>
                                        <span class="discount-badge">-<?= $product['discount_percent'] ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-stock">
                                    <small class="<?= $product['total_quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <i class="fas <?= $product['total_quantity'] > 0 ? 'fa-check' : 'fa-times' ?> me-1"></i>
                                        <?= $product['total_quantity'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                                    </small>
                                </div>
                                <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-product">
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <p class="text-muted">Chưa có sản phẩm mới</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="container">
                <div class="features-grid">
                    <div class="feature-item">
                        <i class="fas fa-shipping-fast"></i>
                        <h3>Miễn phí vận chuyển</h3>
                        <p>Cho đơn hàng từ 500.000₫</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-exchange-alt"></i>
                        <h3>Đổi trả dễ dàng</h3>
                        <p>Trong vòng 30 ngày</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Bảo hành chính hãng</h3>
                        <p>Cam kết 100%</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include "includes/footer.php"; ?>

    <!-- Đặt JS ở cuối body -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Load remaining sections sau khi trang đã load
        document.addEventListener('DOMContentLoaded', function() {
            // Thêm CSS đầy đủ sau khi load
            const fullStyles = `
                .products-section { padding: 60px 0; }
                .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
                .product-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s ease; }
                .product-card:hover { transform: translateY(-5px); }
                .product-image { width: 100%; height: 250px; object-fit: cover; }
                .product-info { padding: 20px; }
                .product-name { font-weight: 700; margin-bottom: 8px; font-size: 1.1rem; color: var(--primary-black); }
                .product-category { margin-bottom: 10px; }
                .product-price { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
                .current-price { font-weight: 700; font-size: 1.2rem; color: var(--accent-red); }
                .original-price { font-size: 0.9rem; }
                .discount-badge { background: var(--accent-red); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
                .product-stock { margin-bottom: 15px; }
                .btn-product { display: block; padding: 12px; background: var(--primary-black); color: white; text-align: center; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.3s ease; }
                .btn-product:hover { background: var(--accent-red); color: white; }
                .banner-section { padding: 80px 0; background: linear-gradient(135deg, var(--primary-black) 0%, #333 100%); color: white; text-align: center; }
                .banner-content h2 { font-size: 2.5rem; margin-bottom: 1rem; }
                .features-section { padding: 80px 0; background: var(--gray-light); }
                .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; text-align: center; }
                .feature-item { padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
                .feature-item i { font-size: 3rem; margin-bottom: 20px; color: var(--accent-red); }
                .feature-item h3 { font-size: 1.3rem; margin-bottom: 10px; color: var(--primary-black); }
                .feature-item p { color: var(--gray-medium); margin: 0; }

                /* Featured Categories Styles */
                .featured-categories-section .section-title { color: white !important; }
            `;
            
            const styleSheet = document.createElement('style');
            styleSheet.textContent = fullStyles;
            document.head.appendChild(styleSheet);

            // Lazy load images
            const lazyImages = document.querySelectorAll('.lazy');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                        }
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            lazyImages.forEach(img => imageObserver.observe(img));

            // Animation cho featured categories
            const featuredCards = document.querySelectorAll('.featured-category-card');
            featuredCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.2}s`;
            });
        });
    </script>
</body>
</html>