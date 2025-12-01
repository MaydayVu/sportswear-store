<?php
session_start();
include "config.php";

// Xử lý filter
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$brand_filter = isset($_GET['brand']) ? $_GET['brand'] : '';
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';
$sport_filter = isset($_GET['sport']) ? $_GET['sport'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Lấy tất cả danh mục
$categories_result = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC
");

// Lấy tất cả brand từ products
$brands_result = $conn->query("
    SELECT DISTINCT brand, COUNT(*) as product_count 
    FROM products 
    WHERE brand IS NOT NULL AND brand != ''
    GROUP BY brand 
    ORDER BY product_count DESC
");

// Xây dựng query cho sản phẩm
$where_conditions = [];
$params = [];
$types = "";

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if (!empty($brand_filter)) {
    $where_conditions[] = "p.brand = ?";
    $params[] = $brand_filter;
    $types .= "s";
}

if (!empty($gender_filter)) {
    $where_conditions[] = "p.gender = ?";
    $params[] = $gender_filter;
    $types .= "s";
}

if (!empty($sport_filter) && $sport_filter != 'none') {
    $where_conditions[] = "p.sport_type = ?";
    $params[] = $sport_filter;
    $types .= "s";
}

// Chỉ hiển thị sản phẩm có số lượng > 0
$where_conditions[] = "(SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) > 0";

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Sắp xếp
$order_sql = "ORDER BY ";
switch ($sort_by) {
    case 'price_asc':
        $order_sql .= "p.price ASC";
        break;
    case 'price_desc':
        $order_sql .= "p.price DESC";
        break;
    case 'name':
        $order_sql .= "p.name ASC";
        break;
    case 'discount':
        $order_sql .= "p.discount_percent DESC";
        break;
    default:
        $order_sql .= "p.created_at DESC";
        break;
}

// Lấy tổng số sản phẩm
$count_sql = "
    SELECT COUNT(*) as total 
    FROM products p 
    $where_sql
";

$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];
$count_stmt->close();

// Phân trang
$products_per_page = 12;
$total_pages = ceil($total_products / $products_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;

// Query chính để lấy sản phẩm
$products_sql = "
    SELECT p.*, c.name AS category_name,
           (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) as total_quantity
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $where_sql 
    $order_sql 
    LIMIT ? OFFSET ?
";

$params[] = $products_per_page;
$params[] = $offset;
$types .= "ii";

$products_stmt = $conn->prepare($products_sql);
if (!empty($params)) {
    $products_stmt->bind_param($types, ...$params);
}
$products_stmt->execute();
$products_result = $products_stmt->get_result();

// Lấy thông tin danh mục hiện tại nếu có filter
$current_category = null;
if ($category_filter > 0) {
    $category_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $category_stmt->bind_param("i", $category_filter);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result();
    $current_category = $category_result->fetch_assoc();
    $category_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
        if ($current_category) {
            echo htmlspecialchars($current_category['name']) . ' - ';
        }
        ?>Danh mục sản phẩm - Sport Fashion
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #000;
            --accent-color: #e4002b;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }

        .categories-page {
            padding: 80px 0 40px;
            background: var(--light-bg);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Sidebar Filters */
        .filter-sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 30px;
            position: sticky;
            top: 100px;
        }

        .filter-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary-color);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .filter-item {
            margin-bottom: 8px;
        }

        .filter-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            color: #495057;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .filter-link:hover {
            background: #f8f9fa;
            color: var(--primary-color);
        }

        .filter-link.active {
            background: var(--primary-color);
            color: white;
        }

        .product-count {
            background: #e9ecef;
            color: #6c757d;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .filter-link.active .product-count {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* Main Content */
        .products-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .results-count {
            font-weight: 600;
            color: #495057;
        }

        .sort-select {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px 12px;
            background: white;
            color: #495057;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
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
            background: var(--accent-color);
            color: white;
            padding: 6px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(228, 0, 43, 0.3);
        }

        .featured-badge {
            background: var(--primary-color);
            color: white;
            padding: 6px 10px;
            border-radius: 15px;
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
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 10px;
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
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--accent-color);
        }

        .original-price {
            font-size: 0.9rem;
            color: #999;
            text-decoration: line-through;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }

        .product-brand {
            font-size: 0.8rem;
            color: #666;
            font-weight: 600;
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

        .btn-view-detail {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-view-detail:hover {
            background: var(--accent-color);
            color: white;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 40px;
        }

        .page-link {
            border: 1px solid var(--border-color);
            color: #495057;
            padding: 8px 16px;
            margin: 0 2px;
            border-radius: 6px;
        }

        .page-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .empty-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        /* Mobile Filters */
        .mobile-filters-btn {
            display: none;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        @media (max-width: 991.98px) {
            .mobile-filters-btn {
                display: block;
            }

            .filter-sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 300px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
                overflow-y: auto;
            }

            .filter-sidebar.active {
                left: 0;
            }

            .filter-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
            }

            .filter-overlay.active {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .products-header {
                flex-direction: column;
                align-items: stretch;
            }

            .sort-select {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 40px 0;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="categories-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">
                    <?php 
                    if ($current_category) {
                        echo htmlspecialchars($current_category['name']);
                    } else {
                        echo 'Tất cả danh mục';
                    }
                    ?>
                </h1>
                <p class="page-subtitle">
                    <?php 
                    if ($current_category && $current_category['event_description']) {
                        echo htmlspecialchars($current_category['event_description']);
                    } else {
                        echo 'Khám phá bộ sưu tập đa dạng các sản phẩm thể thao';
                    }
                    ?>
                </p>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <!-- Sidebar Filters -->
                <div class="col-lg-3">
                    <button class="mobile-filters-btn w-100" id="mobileFiltersBtn">
                        <i class="fas fa-filter me-2"></i>Bộ lọc
                    </button>

                    <div class="filter-overlay" id="filterOverlay"></div>
                    
                    <div class="filter-sidebar" id="filterSidebar">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Bộ lọc</h5>
                            <button class="btn-close d-lg-none" id="closeFilters"></button>
                        </div>

                        <!-- Categories Filter -->
                        <div class="filter-section">
                            <h6 class="filter-title">
                                Danh mục
                                <span class="product-count"><?= $categories_result->num_rows ?></span>
                            </h6>
                            <ul class="filter-list">
                                <li class="filter-item">
                                    <a href="categories.php" 
                                       class="filter-link <?= !$category_filter ? 'active' : '' ?>">
                                        Tất cả danh mục
                                        <span class="product-count"><?= $total_products ?></span>
                                    </a>
                                </li>
                                <?php 
                                $categories_result->data_seek(0);
                                while($category = $categories_result->fetch_assoc()): 
                                ?>
                                <li class="filter-item">
                                    <a href="categories.php?category=<?= $category['id'] ?>" 
                                       class="filter-link <?= $category_filter == $category['id'] ? 'active' : '' ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                        <span class="product-count"><?= $category['product_count'] ?></span>
                                    </a>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>

                        <!-- Brands Filter -->
                        <div class="filter-section">
                            <h6 class="filter-title">Thương hiệu</h6>
                            <ul class="filter-list">
                                <li class="filter-item">
                                    <a href="<?= remove_query_param('brand') ?>" 
                                       class="filter-link <?= empty($brand_filter) ? 'active' : '' ?>">
                                        Tất cả thương hiệu
                                    </a>
                                </li>
                                <?php 
                                $brands_result->data_seek(0);
                                while($brand = $brands_result->fetch_assoc()): 
                                ?>
                                <li class="filter-item">
                                    <a href="<?= add_query_param('brand', $brand['brand']) ?>" 
                                       class="filter-link <?= $brand_filter == $brand['brand'] ? 'active' : '' ?>">
                                        <?= htmlspecialchars($brand['brand']) ?>
                                        <span class="product-count"><?= $brand['product_count'] ?></span>
                                    </a>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>

                        <!-- Gender Filter -->
                        <div class="filter-section">
                            <h6 class="filter-title">Giới tính</h6>
                            <ul class="filter-list">
                                <li class="filter-item">
                                    <a href="<?= remove_query_param('gender') ?>" 
                                       class="filter-link <?= empty($gender_filter) ? 'active' : '' ?>">
                                        Tất cả
                                    </a>
                                </li>
                                <li class="filter-item">
                                    <a href="<?= add_query_param('gender', 'nam') ?>" 
                                       class="filter-link <?= $gender_filter == 'nam' ? 'active' : '' ?>">
                                        Nam
                                    </a>
                                </li>
                                <li class="filter-item">
                                    <a href="<?= add_query_param('gender', 'nu') ?>" 
                                       class="filter-link <?= $gender_filter == 'nu' ? 'active' : '' ?>">
                                        Nữ
                                    </a>
                                </li>
                                <li class="filter-item">
                                    <a href="<?= add_query_param('gender', 'unisex') ?>" 
                                       class="filter-link <?= $gender_filter == 'unisex' ? 'active' : '' ?>">
                                        Unisex
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Sport Type Filter -->
                        <div class="filter-section">
                            <h6 class="filter-title">Môn thể thao</h6>
                            <ul class="filter-list">
                                <li class="filter-item">
                                    <a href="<?= remove_query_param('sport') ?>" 
                                       class="filter-link <?= empty($sport_filter) ? 'active' : '' ?>">
                                        Tất cả môn
                                    </a>
                                </li>
                                <li class="filter-item">
                                    <a href="<?= add_query_param('sport', 'football') ?>" 
                                       class="filter-link <?= $sport_filter == 'football' ? 'active' : '' ?>">
                                        Bóng đá
                                    </a>
                                </li>
                                <li class="filter-item">
                                    <a href="<?= add_query_param('sport', 'running') ?>" 
                                       class="filter-link <?= $sport_filter == 'running' ? 'active' : '' ?>">
                                        Chạy bộ
                                    </a>
                                </li>
                                <li class="filter-item">
                                    <a href="<?= add_query_param('sport', 'basketball') ?>" 
                                       class="filter-link <?= $sport_filter == 'basketball' ? 'active' : '' ?>">
                                        Bóng rổ
                                    </a>
                                </li>
                                <li class="filter-item">
                                    <a href="<?= add_query_param('sport', 'training') ?>" 
                                       class="filter-link <?= $sport_filter == 'training' ? 'active' : '' ?>">
                                        Tập luyện
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Clear Filters -->
                        <?php if ($category_filter || $brand_filter || $gender_filter || $sport_filter): ?>
                        <div class="filter-section">
                            <a href="categories.php" class="btn btn-outline-dark w-100">
                                <i class="fas fa-times me-2"></i>Xóa bộ lọc
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-lg-9">
                    <!-- Products Header -->
                    <div class="products-header">
                        <div class="results-count">
                            <?= $total_products ?> sản phẩm
                            <?php 
                            if ($category_filter || $brand_filter || $gender_filter || $sport_filter) {
                                echo 'phù hợp';
                            }
                            ?>
                        </div>
                        
                        <div>
                            <select class="sort-select" id="sortSelect">
                                <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                                <option value="price_asc" <?= $sort_by == 'price_asc' ? 'selected' : '' ?>>Giá: Thấp đến cao</option>
                                <option value="price_desc" <?= $sort_by == 'price_desc' ? 'selected' : '' ?>>Giá: Cao đến thấp</option>
                                <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Tên A-Z</option>
                                <option value="discount" <?= $sort_by == 'discount' ? 'selected' : '' ?>>Khuyến mãi tốt nhất</option>
                            </select>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <?php if ($products_result->num_rows > 0): ?>
                        <div class="products-grid">
                            <?php while ($product = $products_result->fetch_assoc()): 
                                $current_price = $product['price'];
                                $has_discount = $product['discount_percent'] > 0;
                                if ($has_discount) {
                                    $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                                }
                            ?>
                            <div class="product-card">
                                <div class="product-badges">
                                    <?php if ($has_discount): ?>
                                        <div class="discount-badge">-<?= $product['discount_percent'] ?>%</div>
                                    <?php endif; ?>
                                    <?php if ($product['featured']): ?>
                                        <div class="featured-badge">NỔI BẬT</div>
                                    <?php endif; ?>
                                </div>

                                <div class="product-image-container">
                                    <a href="product_detail.php?id=<?= $product['id'] ?>">
                                        <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="product-image lazy"
                                             loading="lazy"
                                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwIiBoZWlnaHQ9IjI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
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
                                        <?php if ($has_discount): ?>
                                            <span class="original-price"><?= number_format($product['price']) ?>₫</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-meta">
                                        <?php if ($product['brand']): ?>
                                            <span class="product-brand"><?= htmlspecialchars($product['brand']) ?></span>
                                        <?php endif; ?>
                                        <span class="product-gender">
                                            <?= $product['gender'] == 'nam' ? 'Nam' : ($product['gender'] == 'nu' ? 'Nữ' : 'Unisex') ?>
                                        </span>
                                    </div>

                                    <div class="stock-status <?= $product['total_quantity'] > 0 ? 'stock-in' : 'stock-out' ?>">
                                        <i class="fas fa-<?= $product['total_quantity'] > 0 ? 'check' : 'times' ?> me-1"></i>
                                        <?= $product['total_quantity'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                                    </div>

                                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-view-detail">
                                        <i class="fas fa-shopping-cart me-2"></i>Xem chi tiết
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= add_query_param('page', $current_page - 1) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= add_query_param('page', $i) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php elseif ($i == $current_page - 3 || $i == $current_page + 3): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= add_query_param('page', $current_page + 1) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>Không tìm thấy sản phẩm</h3>
                            <p class="text-muted mb-4">Không có sản phẩm nào phù hợp với bộ lọc của bạn.</p>
                            <a href="categories.php" class="btn btn-dark">
                                <i class="fas fa-undo me-2"></i>Xem tất cả sản phẩm
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile filters toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileFiltersBtn = document.getElementById('mobileFiltersBtn');
            const filterSidebar = document.getElementById('filterSidebar');
            const filterOverlay = document.getElementById('filterOverlay');
            const closeFilters = document.getElementById('closeFilters');

            function openFilters() {
                filterSidebar.classList.add('active');
                filterOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeFiltersFunc() {
                filterSidebar.classList.remove('active');
                filterOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            mobileFiltersBtn.addEventListener('click', openFilters);
            closeFilters.addEventListener('click', closeFiltersFunc);
            filterOverlay.addEventListener('click', closeFiltersFunc);

            // Sort select change
            const sortSelect = document.getElementById('sortSelect');
            sortSelect.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('sort', this.value);
                url.searchParams.delete('page'); // Reset to page 1 when sorting
                window.location.href = url.toString();
            });

            // Lazy load images
            const lazyImages = document.querySelectorAll('.lazy');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            lazyImages.forEach(img => imageObserver.observe(img));
        });
    </script>
</body>
</html>

<?php
// Helper functions for URL manipulation
function add_query_param($key, $value) {
    $url = "categories.php?" . http_build_query(array_merge($_GET, [$key => $value, 'page' => 1]));
    return htmlspecialchars($url);
}

function remove_query_param($key) {
    $params = $_GET;
    unset($params[$key]);
    unset($params['page']);
    $url = "categories.php?" . http_build_query($params);
    return htmlspecialchars($url);
}

$conn->close();
?>