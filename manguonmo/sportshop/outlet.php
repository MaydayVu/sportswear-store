<?php
session_start();
include "config.php";

// Lấy sản phẩm giảm giá
$products = $conn->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.discount_percent > 0 
    ORDER BY p.discount_percent DESC, p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outlet - Giảm giá sốc - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #000;
            --accent-color: #e4002b;
            --sale-color: #ff4444;
            --light-bg: #f8f9fa;
        }

        .outlet-page {
            padding: 0;
            background: var(--light-bg);
        }

        /* Hero Section */
        .outlet-hero {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .outlet-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><text x="50%" y="50%" font-family="Arial" font-size="14" fill="white" text-anchor="middle" dominant-baseline="middle">SALE</text></svg>') repeat;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .hero-content .lead {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .sale-countdown {
            background: var(--accent-color);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(228, 0, 43, 0.3);
        }

        /* Stats Section */
        .stats-section {
            background: white;
            padding: 40px 0;
            border-bottom: 1px solid #eee;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Products Section */
        .products-section {
            padding: 60px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .section-subtitle {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .product-badges {
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            z-index: 2;
            display: flex;
            justify-content: space-between;
        }

        .discount-badge {
            background: var(--sale-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            box-shadow: 0 2px 10px rgba(255, 68, 68, 0.3);
        }

        .featured-badge {
            background: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .product-image-container {
            position: relative;
            overflow: hidden;
            background: #f8f9fa;
        }

        .product-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 25px;
        }

        .product-category {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .product-name a {
            color: inherit;
            text-decoration: none;
        }

        .product-name a:hover {
            color: var(--accent-color);
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .current-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent-color);
        }

        .original-price {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
        }

        .price-save {
            background: #fff0f0;
            color: var(--sale-color);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .product-size {
            background: #f8f9fa;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #666;
        }

        .product-gender {
            font-size: 0.8rem;
            color: #666;
            text-transform: capitalize;
        }

        .stock-status {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .stock-in {
            color: #28a745;
        }

        .stock-out {
            color: #dc3545;
        }

        .btn-quick-view {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-quick-view:hover {
            background: var(--accent-color);
            color: white;
        }

        .btn-quick-view:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }

        .empty-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #333 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-top: 60px;
        }

        .cta-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-cta {
            background: var(--accent-color);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-cta:hover {
            background: #ff2b2b;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(228, 0, 43, 0.3);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .outlet-hero {
                padding: 60px 0;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .section-title {
                font-size: 2rem;
            }

            .stat-number {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .product-image {
                height: 220px;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <!-- Hero Section -->
    <section class="outlet-hero">
        <div class="container">
            <div class="hero-content">
                <h1>OUTLET SALE</h1>
                <p class="lead">Giảm giá sốc - Ưu đãi đặc biệt cuối mùa</p>
                <div class="sale-countdown">
                    <i class="fas fa-bolt me-2"></i>KHUYẾN MÃI CÓ HẠN
                </div>
                <a href="#products" class="btn btn-light btn-lg">
                    <i class="fas fa-arrow-down me-2"></i>Xem ngay
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?= $products->num_rows ?></div>
                        <div class="stat-label">Sản phẩm sale</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">UP TO 70%</div>
                        <div class="stat-label">Giảm giá</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Hỗ trợ</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">✓</div>
                        <div class="stat-label">Chính hãng</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Sản phẩm khuyến mãi</h2>
                <p class="section-subtitle">
                    Khám phá các sản phẩm thể thao đang được giảm giá với mức ưu đãi tốt nhất
                </p>
            </div>

            <?php if ($products->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($product = $products->fetch_assoc()): 
                        $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                        $amount_saved = $product['price'] - $current_price;
                        
                        // Lấy tổng số lượng từ product_sizes
                        $quantity_stmt = $conn->prepare("
                            SELECT SUM(quantity) as total_quantity 
                            FROM product_sizes 
                            WHERE product_id = ?
                        ");
                        $quantity_stmt->bind_param("i", $product['id']);
                        $quantity_stmt->execute();
                        $quantity_result = $quantity_stmt->get_result();
                        $total_quantity = $quantity_result->fetch_assoc()['total_quantity'] ?? 0;
                        $quantity_stmt->close();
                        
                        // Lấy các size có sẵn
                        $sizes_stmt = $conn->prepare("
                            SELECT size 
                            FROM product_sizes 
                            WHERE product_id = ? AND quantity > 0 
                            ORDER BY size
                        ");
                        $sizes_stmt->bind_param("i", $product['id']);
                        $sizes_stmt->execute();
                        $sizes_result = $sizes_stmt->get_result();
                        $available_sizes = [];
                        while ($size = $sizes_result->fetch_assoc()) {
                            $available_sizes[] = $size['size'];
                        }
                        $sizes_stmt->close();
                        
                        $is_out_of_stock = $total_quantity <= 0;
                    ?>
                    <div class="product-card">
                        <div class="product-badges">
                            <div class="discount-badge">-<?= $product['discount_percent'] ?>%</div>
                            <?php if ($product['featured']): ?>
                                <div class="featured-badge">NỔI BẬT</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-image-container">
                            <a href="product_detail.php?id=<?= $product['id'] ?>">
                                <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="product-image"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjI4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                            </a>
                        </div>

                        <div class="product-info">
                            <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                            <h3 class="product-name">
                                <a href="product_detail.php?id=<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </h3>
                            
                            <div class="product-price">
                                <span class="current-price"><?= number_format($current_price) ?>₫</span>
                                <span class="original-price"><?= number_format($product['price']) ?>₫</span>
                                <span class="price-save">Tiết kiệm <?= number_format($amount_saved) ?>₫</span>
                            </div>

                            <div class="stock-status <?= $is_out_of_stock ? 'stock-out' : 'stock-in' ?>">
                                <i class="fas fa-<?= $is_out_of_stock ? 'times' : 'check' ?> me-1"></i>
                                <?= $is_out_of_stock ? 'Tạm hết hàng' : 'Còn hàng (' . $total_quantity . ')' ?>
                            </div>

                            <div class="product-meta">
                                <?php if (!empty($available_sizes)): ?>
                                    <span class="product-size">Size: <?= implode(', ', $available_sizes) ?></span>
                                <?php else: ?>
                                    <span class="product-size">Size: Đang cập nhật</span>
                                <?php endif; ?>
                                <span class="product-gender">
                                    <?= $product['gender'] == 'nam' ? 'Nam' : ($product['gender'] == 'nu' ? 'Nữ' : 'Unisex') ?>
                                </span>
                            </div>

                            <a href="product_detail.php?id=<?= $product['id'] ?>" 
                               class="btn-quick-view <?= $is_out_of_stock ? 'disabled' : '' ?>"
                               <?= $is_out_of_stock ? 'style="pointer-events: none; opacity: 0.6;"' : '' ?>>
                                <i class="fas fa-<?= $is_out_of_stock ? 'eye' : 'shopping-cart' ?> me-2"></i>
                                <?= $is_out_of_stock ? 'Xem chi tiết' : 'Thêm vào giỏ hàng' ?>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Hiện chưa có sản phẩm khuyến mãi</h3>
                    <p class="text-muted mb-4">Chúng tôi đang cập nhật các chương trình ưu đãi mới.</p>
                    <a href="products.php" class="btn btn-dark btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Xem tất cả sản phẩm
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h3 class="cta-title">Đừng bỏ lỡ cơ hội!</h3>
            <p class="cta-subtitle">
                Ưu đãi chỉ dành cho những khách hàng nhanh tay nhất. Mua sắm ngay để nhận giá tốt.
            </p>
            <a href="products.php" class="btn-cta">
                <i class="fas fa-bolt me-2"></i>MUA SẮM NGAY
            </a>
        </div>
    </section>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            const anchorLinks = document.querySelectorAll('a[href^="#"]');
            anchorLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add loading animation to product cards
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>