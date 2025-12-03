<?php
session_start();
include "config.php";

// Lấy tham số filter
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sport = isset($_GET['sport']) ? $_GET['sport'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : 0;
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';

// Phân trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Xây dựng query
$sql = "SELECT DISTINCT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_sizes ps ON p.id = ps.product_id 
        WHERE (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) > 0";
$params = [];
$types = "";

if ($gender) {
    $sql .= " AND p.gender = ?";
    $params[] = $gender;
    $types .= "s";
}

if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if ($sport && $sport != 'all') {
    $sql .= " AND p.sport_type = ?";
    $params[] = $sport;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if ($brand) {
    $sql .= " AND p.brand = ?";
    $params[] = $brand;
    $types .= "s";
}

if ($price_min > 0) {
    $sql .= " AND p.price >= ?";
    $params[] = $price_min;
    $types .= "d";
}

if ($price_max > 0) {
    $sql .= " AND p.price <= ?";
    $params[] = $price_max;
    $types .= "d";
}

// Sắp xếp
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY (p.price * (1 - p.discount_percent/100)) ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY (p.price * (1 - p.discount_percent/100)) DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.name DESC";
        break;
    case 'discount':
        $sql .= " ORDER BY p.discount_percent DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY p.featured DESC, p.created_at DESC";
        break;
    default:
        $sql .= " ORDER BY p.featured DESC, p.created_at DESC";
        break;
}

// Query để đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(DISTINCT p.id) as total FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN product_sizes ps ON p.id = ps.product_id 
            WHERE " . substr($sql, strpos($sql, "WHERE") + 6);
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_products = $count_result['total'];
$total_pages = ceil($total_products / $limit);

// Thêm phân trang vào query chính
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Thực thi query
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Lấy danh mục cho filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Lấy danh sách brand
$brands_result = $conn->query("SELECT DISTINCT brand, COUNT(*) as count FROM products WHERE brand IS NOT NULL AND brand != '' GROUP BY brand ORDER BY count DESC");

// Sport types mapping
$sport_types = [
    'none' => 'Không có',
    'football' => 'Bóng đá',
    'running' => 'Chạy bộ', 
    'basketball' => 'Bóng rổ',
    'training' => 'Tập luyện',
    'motosport' => 'Motosport',
    'court_sports' => 'Thể thao sân'
];

// Sort options
$sort_options = [
    'newest' => 'Mới nhất',
    'popular' => 'Phổ biến',
    'price_asc' => 'Giá: Thấp đến cao',
    'price_desc' => 'Giá: Cao đến thấp',
    'name_asc' => 'Tên: A-Z',
    'name_desc' => 'Tên: Z-A',
    'discount' => 'Khuyến mãi tốt nhất'
];

// Xác định tiêu đề trang
$page_title = "Sản phẩm";
$page_description = "Tất cả sản phẩm của chúng tôi";

if ($gender) {
    $gender_text = $gender == 'nam' ? 'Nam' : ($gender == 'nu' ? 'Nữ' : 'Unisex');
    $page_title = "Thời trang " . $gender_text;
    $page_description = "Khám phá bộ sưu tập thời trang $gender_text mới nhất";
} elseif ($category_id) {
    $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_stmt->bind_param("i", $category_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $category = $cat_result->fetch_assoc();
    $page_title = $category ? $category['name'] : "Danh mục";
    $page_description = "Các sản phẩm " . strtolower($category['name'] ?? '') . " chất lượng cao";
} elseif ($sport && $sport != 'all') {
    $page_title = $sport_types[$sport] ?? "Thể thao";
    $page_description = "Đồ dùng thể thao " . strtolower($sport_types[$sport] ?? '') . " chuyên nghiệp";
} elseif ($search) {
    $page_title = "Tìm kiếm: " . htmlspecialchars($search);
    $page_description = "Kết quả tìm kiếm cho '" . htmlspecialchars($search) . "'";
} elseif ($brand) {
    $page_title = "Thương hiệu: " . htmlspecialchars($brand);
    $page_description = "Sản phẩm từ thương hiệu " . htmlspecialchars($brand);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #fff;
            --accent-color: #e4002b;
            --success-color: #27ae60;
            --gray-light: #f5f5f5;
            --gray-medium: #767676;
        }

        .products-page {
            padding: 40px 0;
            background: #f8f9fa;
            min-height: 70vh;
        }

        .page-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .page-description {
            color: var(--gray-medium);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .search-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .advanced-search {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .filter-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-label {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .filter-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            background: white;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }

        .price-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .price-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .products-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .results-count {
            color: var(--gray-medium);
            font-size: 1rem;
        }

        .sort-options {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .sort-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 8px 15px;
            background: white;
            min-width: 200px;
        }

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
            transition: all 0.3s;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-badges {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .featured-badge {
            background: var(--accent-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .sport-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            color: white;
        }

        .sport-none { background: #95a5a6; }
        .sport-football { background: #e74c3c; }
        .sport-running { background: #3498db; }
        .sport-basketball { background: #e67e22; }
        .sport-training { background: #9b59b6; }
        .sport-motosport { background: #34495e; }
        .sport-court_sports { background: #27ae60; }

        .product-info {
            padding: 20px;
        }

        .product-category {
            font-size: 0.8rem;
            color: var(--gray-medium);
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.4;
            height: 2.8em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-brand {
            font-size: 0.9rem;
            color: var(--gray-medium);
            margin-bottom: 8px;
            font-weight: 500;
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
            color: var(--primary-color);
        }

        .original-price {
            font-size: 1rem;
            color: var(--gray-medium);
            text-decoration: line-through;
        }

        .discount-badge {
            background: var(--accent-color);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .product-meta {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .meta-badge {
            background: var(--gray-light);
            color: var(--gray-medium);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .stock-info {
            font-size: 0.8rem;
            color: var(--gray-medium);
            margin-bottom: 15px;
        }

        .in-stock {
            color: var(--success-color);
            font-weight: 600;
        }

        .btn-view-detail {
            display: block;
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
        }

        .btn-view-detail:hover {
            background: #333;
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-medium);
        }

        .empty-state i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .pagination {
            justify-content: center;
            margin-top: 40px;
        }

        .page-link {
            border: 1px solid #dee2e6;
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

        .search-highlight {
            background: yellow;
            padding: 2px 4px;
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .filter-options {
                justify-content: center;
            }
            
            .products-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .sort-options {
                width: 100%;
            }
            
            .sort-select {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .advanced-search {
                padding: 15px;
            }
        }

        .mobile-filters-btn {
            display: none;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 20px;
            width: 100%;
        }

        @media (max-width: 991.98px) {
            .mobile-filters-btn {
                display: block;
            }
            
            .advanced-search {
                display: none;
            }
            
            .advanced-search.active {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 300px;
                height: 100vh;
                z-index: 1050;
                overflow-y: auto;
                background: white;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="products-page">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1><?= htmlspecialchars($page_title) ?></h1>
                <p class="page-description"><?= $page_description ?></p>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                            value="<?= htmlspecialchars($search) ?>" 
                            placeholder="Tìm kiếm sản phẩm theo tên, thương hiệu, mô tả...">
                        <button class="btn btn-dark" type="submit">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                    </div>
                </form>
            </div>

            <!-- Mobile Filters Button -->
            <button class="mobile-filters-btn" id="mobileFiltersBtn">
                <i class="fas fa-filter me-2"></i>Bộ lọc nâng cao
            </button>

            <!-- Advanced Search & Filters -->
            <div class="advanced-search" id="advancedSearch">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="filter-title mb-0">Bộ lọc nâng cao</h5>
                    <button class="btn-close d-lg-none" id="closeFilters"></button>
                </div>

                <div class="row">
                    <!-- Price Range -->
                    <form method="GET" class="price-filter-form">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="gender" value="<?= htmlspecialchars($gender) ?>">
                        <input type="hidden" name="category" value="<?= $category_id ?>">
                        <input type="hidden" name="sport" value="<?= htmlspecialchars($sport) ?>">
                        <input type="hidden" name="brand" value="<?= htmlspecialchars($brand) ?>">
                        <input type="hidden" name="page" value="1"> <!-- Thêm dòng này -->
                        
                        <div class="price-range">
                            <input type="number" name="price_min" class="price-input" 
                                placeholder="Từ" value="<?= $price_min > 0 ? $price_min : '' ?>">
                            <span>-</span>
                            <input type="number" name="price_max" class="price-input" 
                                placeholder="Đến" value="<?= $price_max > 0 ? $price_max : '' ?>">
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-dark mt-2 w-100">Áp dụng</button>
                    </form>
                    <!-- Brand Filter -->
                    <div class="filter-group">
                        <div class="filter-label">Thương hiệu</div>
                        <div class="filter-options">
                            <a href="<?= remove_filter_param('brand') ?>" 
                            class="filter-btn <?= !$brand ? 'active' : '' ?>">Tất cả</a>
                            <?php while($brand_row = $brands_result->fetch_assoc()): ?>
                                <a href="<?= add_filter_param('brand', $brand_row['brand']) ?>" 
                                class="filter-btn <?= $brand == $brand_row['brand'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($brand_row['brand']) ?>
                                    <span class="badge bg-light text-dark ms-1"><?= $brand_row['count'] ?></span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Gender Filter -->
                    <div class="filter-group">
                        <div class="filter-label">Giới tính</div>
                        <div class="filter-options">
                            <a href="<?= remove_filter_param('gender') ?>" 
                            class="filter-btn <?= !$gender ? 'active' : '' ?>">Tất cả</a>
                            <a href="<?= add_filter_param('gender', 'nam') ?>" 
                            class="filter-btn <?= $gender == 'nam' ? 'active' : '' ?>">Nam</a>
                            <a href="<?= add_filter_param('gender', 'nu') ?>" 
                            class="filter-btn <?= $gender == 'nu' ? 'active' : '' ?>">Nữ</a>
                            <a href="<?= add_filter_param('gender', 'unisex') ?>" 
                            class="filter-btn <?= $gender == 'unisex' ? 'active' : '' ?>">Unisex</a>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-group">
                        <div class="filter-label">Danh mục</div>
                        <div class="filter-options">
                            <a href="<?= remove_filter_param('category') ?>" 
                            class="filter-btn <?= !$category_id ? 'active' : '' ?>">Tất cả</a>
                            <?php 
                            $categories->data_seek(0);
                            while($cat = $categories->fetch_assoc()): ?>
                                <a href="<?= add_filter_param('category', $cat['id']) ?>" 
                                class="filter-btn <?= $category_id == $cat['id'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Sport Type Filter -->
                    <div class="filter-group">
                        <div class="filter-label">Loại thể thao</div>
                        <div class="filter-options">
                            <a href="<?= remove_filter_param('sport') ?>" 
                            class="filter-btn <?= !$sport || $sport == 'all' ? 'active' : '' ?>">Tất cả</a>
                            <?php foreach ($sport_types as $key => $name): ?>
                                <?php if ($key != 'none'): ?>
                                    <a href="<?= add_filter_param('sport', $key) ?>" 
                                    class="filter-btn <?= $sport == $key ? 'active' : '' ?>">
                                        <?= $name ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    <div class="text-center mt-4">
                        <a href="<?= get_clear_filters_url() ?>" class="btn btn-outline-danger">
                            <i class="fas fa-times me-2"></i>Xóa tất cả bộ lọc
                        </a>
                    </div>

            <!-- Products Header -->
            <div class="products-header">
                <div class="results-count">
                    <i class="fas fa-cube me-2"></i>
                    Tìm thấy <strong><?= $total_products ?></strong> sản phẩm
                    <?php if ($search): ?>
                        cho từ khóa "<strong><?= htmlspecialchars($search) ?></strong>"
                    <?php endif; ?>
                </div>
                
                <div class="sort-options">
                    <select class="sort-select" id="sortSelect">
                        <?php foreach ($sort_options as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $sort == $key ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if ($products->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($product = $products->fetch_assoc()): 
                        // Tính tổng số lượng tồn kho
                        $stock_sql = "SELECT SUM(quantity) as total_stock FROM product_sizes WHERE product_id = ?";
                        $stock_stmt = $conn->prepare($stock_sql);
                        $stock_stmt->bind_param("i", $product['id']);
                        $stock_stmt->execute();
                        $stock_result = $stock_stmt->get_result()->fetch_assoc();
                        $total_stock = $stock_result['total_stock'] ?? 0;

                        // Lấy các size có sẵn
                        $sizes_sql = "SELECT size FROM product_sizes WHERE product_id = ? AND quantity > 0 ORDER BY size";
                        $sizes_stmt = $conn->prepare($sizes_sql);
                        $sizes_stmt->bind_param("i", $product['id']);
                        $sizes_stmt->execute();
                        $sizes_result = $sizes_stmt->get_result();
                        $available_sizes = [];
                        while ($size = $sizes_result->fetch_assoc()) {
                            $available_sizes[] = $size['size'];
                        }

                        // Tính giá
                        $current_price = $product['price'];
                        $has_discount = $product['discount_percent'] > 0;
                        if ($has_discount) {
                            $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                        }

                        // Highlight search term
                        $product_name = htmlspecialchars($product['name']);
                        if ($search) {
                            $product_name = preg_replace("/(" . preg_quote($search, '/') . ")/i", '<span class="search-highlight">$1</span>', $product_name);
                        }
                    ?>
                    <div class="product-card">
                        <a href="product_detail.php?id=<?= $product['id'] ?>">
                            <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                alt="<?= htmlspecialchars($product['name']) ?>" 
                                class="product-image"
                                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwIiBoZWlnaHQ9IjI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                        </a>
                        
                        <!-- Product Badges -->
                        <div class="product-badges">
                            <?php if ($product['featured']): ?>
                                <span class="featured-badge">
                                    <i class="fas fa-star me-1"></i>Nổi bật
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($product['sport_type'] && $product['sport_type'] != 'none'): ?>
                                <span class="sport-badge sport-<?= $product['sport_type'] ?>">
                                    <i class="fas fa-<?= $product['sport_type'] == 'football' ? 'futbol' : ($product['sport_type'] == 'running' ? 'running' : ($product['sport_type'] == 'basketball' ? 'basketball-ball' : ($product['sport_type'] == 'training' ? 'dumbbell' : ($product['sport_type'] == 'motosport' ? 'motorcycle' : 'table-tennis')))) ?> me-1"></i>
                                    <?= $sport_types[$product['sport_type']] ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                            <h3 class="product-name">
                                <a href="product_detail.php?id=<?= $product['id'] ?>" class="text-dark text-decoration-none">
                                    <?= $product_name ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($product['brand'])): ?>
                                <div class="product-brand"><?= htmlspecialchars($product['brand']) ?></div>
                            <?php endif; ?>
                            
                            <div class="product-price">
                                <span class="current-price"><?= number_format($current_price) ?>₫</span>
                                <?php if ($has_discount): ?>
                                    <span class="original-price"><?= number_format($product['price']) ?>₫</span>
                                    <span class="discount-badge">-<?= $product['discount_percent'] ?>%</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-meta">
                                <span class="meta-badge">
                                    <i class="fas fa-<?= $product['gender'] == 'nam' ? 'mars' : ($product['gender'] == 'nu' ? 'venus' : 'neuter') ?> me-1"></i>
                                    <?= $product['gender'] == 'nam' ? 'Nam' : ($product['gender'] == 'nu' ? 'Nữ' : 'Unisex') ?>
                                </span>
                                
                                <?php if (!empty($available_sizes)): ?>
                                    <span class="meta-badge">
                                        <i class="fas fa-ruler me-1"></i>
                                        <?= implode(', ', array_slice($available_sizes, 0, 3)) ?>
                                        <?php if (count($available_sizes) > 3): ?>+<?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="stock-info">
                                <?php if ($total_stock > 0): ?>
                                    <span class="in-stock">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Còn <?= $total_stock ?> sản phẩm
                                    </span>
                                <?php else: ?>
                                    <span class="text-danger">
                                        <i class="fas fa-times-circle me-1"></i>
                                        Tạm hết hàng
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-view-detail">
                                <i class="fas fa-eye me-2"></i>Xem chi tiết
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= get_prev_page_url() ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= get_page_url($i) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= get_next_page_url() ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Không tìm thấy sản phẩm</h3>
                    <p class="text-muted mb-4">
                        <?php if ($search): ?>
                            Không tìm thấy sản phẩm nào phù hợp với từ khóa "<strong><?= htmlspecialchars($search) ?></strong>"
                        <?php else: ?>
                            Không có sản phẩm nào phù hợp với bộ lọc hiện tại
                        <?php endif; ?>
                    </p>
                    <a href="products.php" class="btn btn-dark">
                        <i class="fas fa-undo me-2"></i>Xem tất cả sản phẩm
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile filters toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileFiltersBtn = document.getElementById('mobileFiltersBtn');
            const advancedSearch = document.getElementById('advancedSearch');
            const closeFilters = document.getElementById('closeFilters');

            if (mobileFiltersBtn && advancedSearch) {
                mobileFiltersBtn.addEventListener('click', function() {
                    advancedSearch.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });

                closeFilters.addEventListener('click', function() {
                    advancedSearch.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            // Sort select change
            const sortSelect = document.getElementById('sortSelect');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('sort', this.value);
                    url.searchParams.delete('page');
                    window.location.href = url.toString();
                });
            }

            // Product card animations
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = `all 0.6s ease ${index * 0.1}s`;
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });
        });

        // Helper functions for URL manipulation
        function add_query_param(key, value) {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            url.searchParams.delete('page');
            return url.toString();
        }

        function remove_query_param(key) {
            const url = new URL(window.location.href);
            url.searchParams.delete(key);
            url.searchParams.delete('page');
            return url.toString();
        }
    </script>
    <?php

    function add_filter_param($key, $value) {
        $params = $_GET;
        $params[$key] = $value;
        $params['page'] = 1;
        $url = "products.php?" . http_build_query($params);
        return htmlspecialchars($url);
    }


    function remove_filter_param($key) {
        $params = $_GET;
        unset($params[$key]);
        $params['page'] = 1;
        $url = "products.php?" . http_build_query($params);
        return htmlspecialchars($url);
    }

    function get_page_url($page_number) {
        $params = $_GET;
        $params['page'] = max(1, intval($page_number));
        $url = "products.php?" . http_build_query($params);
        return htmlspecialchars($url);
    }

    function get_next_page_url() {
        $params = $_GET;
        $current_page = isset($params['page']) ? intval($params['page']) : 1;
        $params['page'] = $current_page + 1;
        $url = "products.php?" . http_build_query($params);
        return htmlspecialchars($url);
    }

    function get_prev_page_url() {
        $params = $_GET;
        $current_page = isset($params['page']) ? intval($params['page']) : 1;
        $params['page'] = max(1, $current_page - 1);
        $url = "products.php?" . http_build_query($params);
        return htmlspecialchars($url);
    }

    function get_clear_filters_url() {
        $url = "products.php";
        return htmlspecialchars($url);
    }

    $conn->close();
    ?>
</body>
</html>
