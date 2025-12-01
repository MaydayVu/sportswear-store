<?php
session_start();
include "config.php";

// Lấy tham số filter
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sport = isset($_GET['sport']) ? $_GET['sport'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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
    $sql .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY p.featured DESC, p.created_at DESC";

// Thực thi query
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Lấy danh mục cho filter
$categories = $conn->query("SELECT * FROM categories");

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

        .filter-section {
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

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
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

        .results-count {
            color: var(--gray-medium);
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .sort-options {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }

        .sort-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 8px 15px;
            background: white;
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
            
            .sort-options {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-section {
                padding: 15px;
            }
        }

        .view-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .view-btn {
            padding: 8px 15px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .view-btn.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
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
                               placeholder="Tìm kiếm sản phẩm theo tên, thương hiệu...">
                        <button class="btn btn-dark" type="submit">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="filter-group">
                            <div class="filter-label">Giới tính</div>
                            <div class="filter-options">
                                <a href="products.php<?= $search ? '?search=' . urlencode($search) : '' ?>" 
                                   class="filter-btn <?= !$gender ? 'active' : '' ?>">Tất cả</a>
                                <a href="products.php?gender=nam<?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="filter-btn <?= $gender == 'nam' ? 'active' : '' ?>">Nam</a>
                                <a href="products.php?gender=nu<?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="filter-btn <?= $gender == 'nu' ? 'active' : '' ?>">Nữ</a>
                                <a href="products.php?gender=unisex<?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="filter-btn <?= $gender == 'unisex' ? 'active' : '' ?>">Unisex</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="filter-group">
                            <div class="filter-label">Danh mục</div>
                            <div class="filter-options">
                                <a href="products.php<?= $search ? '?search=' . urlencode($search) : '' ?>" 
                                   class="filter-btn <?= !$category_id ? 'active' : '' ?>">Tất cả</a>
                                <?php 
                                $categories->data_seek(0); // Reset pointer
                                while($cat = $categories->fetch_assoc()): ?>
                                    <a href="products.php?category=<?= $cat['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                       class="filter-btn <?= $category_id == $cat['id'] ? 'active' : '' ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="filter-group">
                            <div class="filter-label">Loại thể thao</div>
                            <div class="filter-options">
                                <a href="products.php<?= $search ? '?search=' . urlencode($search) : '' ?>" 
                                   class="filter-btn <?= !$sport || $sport == 'all' ? 'active' : '' ?>">Tất cả</a>
                                <?php foreach ($sport_types as $key => $name): ?>
                                    <?php if ($key != 'none'): ?>
                                        <a href="products.php?sport=<?= $key ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                           class="filter-btn <?= $sport == $key ? 'active' : '' ?>">
                                            <?= $name ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="filter-group">
                            <div class="filter-label">Xóa bộ lọc</div>
                            <div class="filter-options">
                                <a href="products.php" class="filter-btn" style="background: var(--accent-color); color: white; border-color: var(--accent-color);">
                                    <i class="fas fa-times me-1"></i>Xóa tất cả
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Count and Sort -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="results-count">
                    <i class="fas fa-cube me-2"></i>
                    Tìm thấy <strong><?= $products->num_rows ?></strong> sản phẩm
                    <?php if ($search): ?>
                        cho từ khóa "<strong><?= htmlspecialchars($search) ?></strong>"
                    <?php endif; ?>
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
                                    <?= htmlspecialchars($product['name']) ?>
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
        // Product card animations
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add to cart quick action
            const quickViewButtons = document.querySelectorAll('.btn-quick-view');
            quickViewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.getAttribute('data-product-id');
                    // Implement quick view functionality here
                    console.log('Quick view product:', productId);
                });
            });
        });

        // Filter persistence
        function updateUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSearch = urlParams.get('search') || '';
            
            // Update all filter links to include search parameter
            document.querySelectorAll('.filter-btn').forEach(btn => {
                const href = new URL(btn.href, window.location.origin);
                if (currentSearch) {
                    href.searchParams.set('search', currentSearch);
                }
                btn.href = href.toString();
            });
        }

        updateUrlParams();
    </script>
</body>
</html>