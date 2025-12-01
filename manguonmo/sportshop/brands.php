<?php
session_start();
include "config.php";

// Lấy tất cả brand từ products
$brands_result = $conn->query("
    SELECT DISTINCT brand, COUNT(*) as product_count 
    FROM products 
    WHERE brand IS NOT NULL AND brand != ''
    GROUP BY brand 
    ORDER BY product_count DESC
");

// Lấy sản phẩm theo brand nếu có filter
$selected_brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$products_result = null;

if ($selected_brand) {
    $products_stmt = $conn->prepare("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.brand = ? 
        ORDER BY p.featured DESC, p.created_at DESC
    ");
    $products_stmt->bind_param("s", $selected_brand);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $selected_brand ? htmlspecialchars($selected_brand) : 'Thương hiệu' ?> - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .brands-page {
            padding: 40px 0;
            background: #f8f9fa;
            min-height: 70vh;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 10px;
        }

        .brands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .brand-card {
            background: white;
            padding: 30px 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
        }

        .brand-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            color: inherit;
            border-color: #000;
        }

        .brand-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #000;
        }

        .brand-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .product-count {
            color: #666;
            font-size: 0.9rem;
        }

        .selected-brand {
            background: #000;
            color: white;
            border-color: #000;
        }

        .selected-brand .brand-icon {
            color: white;
        }

        .selected-brand .product-count {
            color: #ccc;
        }

        /* Products Grid Styles */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .product-name a {
            color: inherit;
            text-decoration: none;
        }

        .product-name a:hover {
            color: #e4002b;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .current-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #000;
        }

        .original-price {
            font-size: 1rem;
            color: #666;
            text-decoration: line-through;
        }

        .discount-badge {
            background: #e4002b;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .btn-view-detail {
            display: block;
            width: 100%;
            padding: 10px;
            background: #000;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-view-detail:hover {
            background: #333;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .size-badge {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }

        .sport-badge {
            background: #e9ecef;
            color: #495057;
        }

        @media (max-width: 768px) {
            .brands-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="brands-page">
        <div class="container">
            <div class="page-header">
                <h1>
                    <?= $selected_brand ? htmlspecialchars($selected_brand) : 'Thương hiệu' ?>
                </h1>
                <p class="text-muted">
                    <?= $selected_brand ? "Sản phẩm của " . htmlspecialchars($selected_brand) : "Khám phá các thương hiệu thể thao hàng đầu" ?>
                </p>
            </div>

            <!-- Brands Grid -->
            <div class="brands-grid">
                <a href="brands.php" class="brand-card <?= !$selected_brand ? 'selected-brand' : '' ?>">
                    <div class="brand-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="brand-name">Tất cả</div>
                    <div class="product-count"><?= $brands_result->num_rows ?> thương hiệu</div>
                </a>
                
                <?php 
                $brands_result->data_seek(0); // Reset pointer
                while($brand = $brands_result->fetch_assoc()): ?>
                    <a href="brands.php?brand=<?= urlencode($brand['brand']) ?>" 
                       class="brand-card <?= $selected_brand == $brand['brand'] ? 'selected-brand' : '' ?>">
                        <div class="brand-icon">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="brand-name"><?= htmlspecialchars($brand['brand']) ?></div>
                        <div class="product-count"><?= $brand['product_count'] ?> sản phẩm</div>
                    </a>
                <?php endwhile; ?>
            </div>

            <!-- Products of Selected Brand -->
            <?php if ($selected_brand): ?>
                <div class="mt-5">
                    <h2 class="mb-4">Sản phẩm của <?= htmlspecialchars($selected_brand) ?></h2>
                    
                    <?php if ($products_result && $products_result->num_rows > 0): ?>
                        <div class="products-grid">
                            <?php while ($product = $products_result->fetch_assoc()): 
                                $current_price = $product['price'];
                                $has_discount = $product['discount_percent'] > 0;
                                if ($has_discount) {
                                    $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                                }
                                
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
                            ?>
                            <div class="product-card">
                                <a href="product_detail.php?id=<?= $product['id'] ?>">
                                    <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="product-image"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwIiBoZWlnaHQ9IjI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                                </a>
                                <div class="product-info">
                                    <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                                    <h3 class="product-name">
                                        <a href="product_detail.php?id=<?= $product['id'] ?>">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h3>
                                    <div class="product-price">
                                        <span class="current-price"><?= number_format($current_price) ?>₫</span>
                                        <?php if ($has_discount): ?>
                                            <span class="original-price"><?= number_format($product['price']) ?>₫</span>
                                            <span class="discount-badge">-<?= $product['discount_percent'] ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2 mb-3 flex-wrap">
                                        <span class="badge bg-light text-dark">
                                            <?= $product['gender'] == 'nam' ? 'Nam' : ($product['gender'] == 'nu' ? 'Nữ' : 'Unisex') ?>
                                        </span>
                                        <?php if (!empty($available_sizes)): ?>
                                            <span class="badge size-badge">
                                                Size: <?= implode(', ', $available_sizes) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($product['sport_type'] && $product['sport_type'] != 'none'): ?>
                                            <span class="badge sport-badge">
                                                <?= ucfirst($product['sport_type']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-<?= $total_quantity > 0 ? 'success' : 'danger' ?>">
                                            <i class="fas fa-<?= $total_quantity > 0 ? 'check' : 'times' ?> me-1"></i>
                                            <?= $total_quantity > 0 ? 'Còn hàng (' . $total_quantity . ')' : 'Hết hàng' ?>
                                        </small>
                                    </div>
                                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-view-detail">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h4>Không tìm thấy sản phẩm</h4>
                            <p class="text-muted">Thương hiệu này hiện chưa có sản phẩm.</p>
                            <a href="brands.php" class="btn btn-dark mt-3">
                                <i class="fas fa-undo me-2"></i>Quay lại thương hiệu
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>