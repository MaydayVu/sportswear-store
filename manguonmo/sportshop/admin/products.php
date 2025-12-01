<?php
session_start();
include "../config.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Xử lý tìm kiếm và lọc
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sport_filter = isset($_GET['sport_type']) ? $_GET['sport_type'] : '';

// Xây dựng query
$query_where = "WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query_where .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($category_filter)) {
    $query_where .= " AND p.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if (!empty($status_filter)) {
    if ($status_filter === 'instock') {
        $query_where .= " AND (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) > 0";
    } elseif ($status_filter === 'outstock') {
        $query_where .= " AND (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) = 0";
    } elseif ($status_filter === 'featured') {
        $query_where .= " AND p.featured = 1";
    }
}

if (!empty($sport_filter)) {
    $query_where .= " AND p.sport_type = ?";
    $params[] = $sport_filter;
    $types .= 's';
}

// Lấy danh sách danh mục cho filter
$categories = $conn->query("SELECT id, name FROM categories");

// Lấy tổng số sản phẩm
$count_query = "SELECT COUNT(*) as total FROM products p $query_where";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_products / $limit);

// Lấy sản phẩm
$query = "
    SELECT p.*, c.name AS category_name,
           (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) as total_quantity,
           (SELECT GROUP_CONCAT(size SEPARATOR ', ') FROM product_sizes WHERE product_id = p.id) as available_sizes
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $query_where
    ORDER BY p.id DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();

// Xử lý thông báo thành công
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Sản phẩm đã được thêm thành công!';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản Phẩm - Admin Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--dark-color);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 0;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #b8c7ce;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-color);
        }

        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-color);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s;
        }

        /* Header */
        .admin-header {
            background: white;
            padding: 20px 30px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .logout-btn {
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Action Header */
        .action-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .btn-primary-custom {
            background: var(--primary-color);
            border: none;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary-custom {
            background: #6c757d;
            border: none;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-secondary-custom:hover {
            background: #5a6268;
            color: white;
        }

        /* Search and Filter */
        .search-filter-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 25px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        /* Table */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 25px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
            border-radius: 12px 12px 0 0 !important;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .table {
            margin: 0;
        }

        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: var(--dark-color);
            padding: 15px;
            white-space: nowrap;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: #eee;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        .featured-badge {
            background: var(--warning-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .btn-action {
            padding: 8px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            border: none;
            font-weight: 600;
        }

        .btn-edit {
            background: var(--primary-color);
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
            color: white;
            transform: translateY(-1px);
        }

        .btn-view {
            background: var(--success-color);
            color: white;
        }

        .btn-view:hover {
            background: #219a52;
            color: white;
            transform: translateY(-1px);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-instock {
            background: #d4edda;
            color: #155724;
        }

        .badge-outstock {
            background: #f8d7da;
            color: #721c24;
        }

        .discount-badge {
            background: var(--danger-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .sizes-list {
            max-width: 150px;
        }

        .size-tag {
            display: inline-block;
            background: #e9ecef;
            padding: 2px 6px;
            margin: 1px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Gender Badge Styles */
        .badge-male {
            background: #3498db !important;
            color: white !important;
        }

        .badge-female {
            background: #e74c3c !important;
            color: white !important;
        }

        .badge-unisex {
            background: #9b59b6 !important;
            color: white !important;
        }

        /* Sport Type Badges */
        .sport-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin: 1px;
        }

        .sport-none { background: #95a5a6; color: white; }
        .sport-football { background: #e74c3c; color: white; }
        .sport-running { background: #3498db; color: white; }
        .sport-basketball { background: #e67e22; color: white; }
        .sport-training { background: #9b59b6; color: white; }
        .sport-motosport { background: #34495e; color: white; }
        .sport-court_sports { background: #27ae60; color: white; }

        /* Brand Style */
        .brand-text {
            font-weight: 600;
            color: var(--dark-color);
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin: 20px 0;
        }

        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: 1px solid #dee2e6;
            color: var(--dark-color);
            font-weight: 600;
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header h2,
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 80px;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🏢 ADMIN PANEL</h2>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Trang quản trị</span>
            </a>
            <a href="categories.php">
                <i class="fas fa-folder"></i>
                <span>Quản lý danh mục</span>
            </a>
            <a href="products.php" class="active">
                <i class="fas fa-shopping-bag"></i>
                <span>Quản lý sản phẩm</span>
            </a>
            <a href="blog.php">
                <i class="fas fa-blog"></i>
                <span>Quản lý bài viết</span>
            </a>
            <a href="orders.php">
                <i class="fas fa-shipping-fast"></i>
                <span>Quản lý đơn hàng</span>
            </a>
            <a href="../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- HEADER -->
        <div class="admin-header">
            <h1><i class="fas fa-shopping-bag me-2"></i>Quản lý Sản Phẩm</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION["fullname"], 0, 1)); ?>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- ACTION HEADER -->
        <div class="action-header">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_products; ?></span>
                    <span class="stat-label">Tổng sản phẩm</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">
                        <?php 
                        $featured_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE featured = 1")->fetch_assoc()['count'];
                        echo $featured_count;
                        ?>
                    </span>
                    <span class="stat-label">Sản phẩm nổi bật</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">
                        <?php 
                        $outstock_count = $conn->query("SELECT COUNT(*) as count FROM products p WHERE (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) = 0")->fetch_assoc()['count'];
                        echo $outstock_count;
                        ?>
                    </span>
                    <span class="stat-label">Hết hàng</span>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="dashboard.php" class="btn-secondary-custom">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                    <a href="product_add.php" class="btn-primary-custom ms-2">
                        <i class="fas fa-plus-circle"></i> Thêm sản phẩm mới
                    </a>
                </div>
                <div class="text-muted fw-semibold">
                    <i class="fas fa-filter me-1"></i> Đang hiển thị: <?php echo $products_result->num_rows; ?> sản phẩm
                </div>
            </div>
        </div>

        <!-- Hiển thị thông báo thành công -->
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- SEARCH AND FILTER -->
        <div class="search-filter-card">
            <form method="GET" class="filter-row">
                <div>
                    <label class="form-label fw-semibold">Tìm kiếm</label>
                    <input type="text" class="form-control" name="search" placeholder="Tìm theo tên hoặc thương hiệu..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <label class="form-label fw-semibold">Danh mục</label>
                    <select class="form-select" name="category">
                        <option value="">Tất cả danh mục</option>
                        <?php 
                        $categories->data_seek(0); // Reset pointer để dùng lại
                        while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label fw-semibold">Loại thể thao</label>
                    <select class="form-select" name="sport_type">
                        <option value="">Tất cả</option>
                        <option value="none" <?= $sport_filter == 'none' ? 'selected' : '' ?>>Không có</option>
                        <option value="football" <?= $sport_filter == 'football' ? 'selected' : '' ?>>Bóng đá</option>
                        <option value="running" <?= $sport_filter == 'running' ? 'selected' : '' ?>>Chạy bộ</option>
                        <option value="basketball" <?= $sport_filter == 'basketball' ? 'selected' : '' ?>>Bóng rổ</option>
                        <option value="training" <?= $sport_filter == 'training' ? 'selected' : '' ?>>Tập luyện</option>
                        <option value="motosport" <?= $sport_filter == 'motosport' ? 'selected' : '' ?>>Motosport</option>
                        <option value="court_sports" <?= $sport_filter == 'court_sports' ? 'selected' : '' ?>>Thể thao sân</option>
                    </select>
                </div>
                <div>
                    <label class="form-label fw-semibold">Trạng thái</label>
                    <select class="form-select" name="status">
                        <option value="">Tất cả</option>
                        <option value="instock" <?= $status_filter == 'instock' ? 'selected' : '' ?>>Còn hàng</option>
                        <option value="outstock" <?= $status_filter == 'outstock' ? 'selected' : '' ?>>Hết hàng</option>
                        <option value="featured" <?= $status_filter == 'featured' ? 'selected' : '' ?>>Nổi bật</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary h-100">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- PRODUCTS TABLE -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list me-2"></i>Danh sách sản phẩm</h3>
                <div class="text-muted">
                    Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($products_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="70">ID</th>
                                    <th>Sản phẩm</th>
                                    <th width="120">Giá</th>
                                    <th width="120">Danh mục</th>
                                    <th width="120">Thương hiệu</th>
                                    <th width="100">Giới tính</th>
                                    <th width="120">Thể thao</th>
                                    <th width="80">Giảm giá</th>
                                    <th width="120">Kích thước</th>
                                    <th width="100">Tồn kho</th>
                                    <th width="80">Ảnh</th>
                                    <th width="100">Trạng thái</th>
                                    <th width="180">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $products_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= $product["id"] ?></strong></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($product["name"]) ?></div>
                                        <small class="text-muted">
                                            <?php 
                                            $description = $product["description"] ?? '';
                                            echo htmlspecialchars(substr($description, 0, 50));
                                            if (strlen($description) > 50) echo '...';
                                            ?>
                                        </small>
                                        <?php if ($product["featured"]): ?>
                                            <span class="featured-badge ms-2">Nổi bật</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-success"><?= number_format($product["price"]) ?>₫</td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($product["category_name"]) ?></span>
                                    </td>
                                    <td>
                                        <span class="brand-text"><?= htmlspecialchars($product["brand"] ?? 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $genderClass = [
                                            'nam' => 'badge-male',
                                            'nu' => 'badge-female',
                                            'unisex' => 'badge-unisex'
                                        ];
                                        $genderText = [
                                            'nam' => 'Nam',
                                            'nu' => 'Nữ', 
                                            'unisex' => 'Unisex'
                                        ];
                                        ?>
                                        <span class="badge <?= $genderClass[$product["gender"]] ?? 'badge-unisex' ?>">
                                            <?= $genderText[$product["gender"]] ?? $product["gender"] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $sportClass = [
                                            'none' => 'sport-none',
                                            'football' => 'sport-football',
                                            'running' => 'sport-running',
                                            'basketball' => 'sport-basketball',
                                            'training' => 'sport-training',
                                            'motosport' => 'sport-motosport',
                                            'court_sports' => 'sport-court_sports'
                                        ];
                                        $sportText = [
                                            'none' => 'Không',
                                            'football' => 'Bóng đá',
                                            'running' => 'Chạy bộ',
                                            'basketball' => 'Bóng rổ',
                                            'training' => 'Tập luyện',
                                            'motosport' => 'Motosport',
                                            'court_sports' => 'Sân'
                                        ];
                                        $sportType = $product["sport_type"] ?? 'none';
                                        ?>
                                        <span class="sport-badge <?= $sportClass[$sportType] ?? 'sport-none' ?>">
                                            <?= $sportText[$sportType] ?? 'Không' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product["discount_percent"] > 0): ?>
                                            <span class="discount-badge">-<?= $product["discount_percent"] ?>%</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="sizes-list">
                                        <?php if (!empty($product["available_sizes"])): ?>
                                            <?php 
                                            $sizes = explode(', ', $product["available_sizes"]);
                                            foreach (array_slice($sizes, 0, 3) as $size): 
                                            ?>
                                                <span class="size-tag"><?= $size ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($sizes) > 3): ?>
                                                <span class="size-tag">+<?= count($sizes) - 3 ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold <?= $product["total_quantity"] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $product["total_quantity"] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="../assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                                 alt="<?= htmlspecialchars($product["name"]) ?>" 
                                                 class="product-img"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7huqNwPC90ZXh0Pjwvc3ZnPg=='">
                                        <?php else: ?>
                                            <div class="product-img bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product["total_quantity"] > 0): ?>
                                            <span class="status-badge badge-instock">
                                                <i class="fas fa-check-circle me-1"></i>Còn hàng
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge badge-outstock">
                                                <i class="fas fa-times-circle me-1"></i>Hết hàng
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="product_edit.php?id=<?= $product['id'] ?>" 
                                               class="btn-action btn-edit">
                                                <i class="fas fa-edit"></i> Sửa
                                            </a>
                                            <a href="product_view.php?id=<?= $product['id'] ?>" 
                                               class="btn-action btn-view">
                                                <i class="fas fa-eye"></i> Xem
                                            </a>
                                            <a href="product_delete.php?id=<?= $product['id'] ?>" 
                                               class="btn-action btn-delete"
                                               onclick="return confirmDelete('<?= addslashes($product["name"]) ?>')">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination-container p-3 border-top">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sport_type=<?= $sport_filter ?>&status=<?= $status_filter ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sport_type=<?= $sport_filter ?>&status=<?= $status_filter ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sport_type=<?= $sport_filter ?>&status=<?= $status_filter ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-cube"></i>
                        <h4>Không tìm thấy sản phẩm</h4>
                        <p class="text-muted mb-4">Hãy thử thay đổi bộ lọc hoặc thêm sản phẩm mới!</p>
                        <a href="product_add.php" class="btn-primary-custom me-2">
                            <i class="fas fa-plus"></i> Thêm sản phẩm mới
                        </a>
                        <a href="products.php" class="btn-secondary-custom">
                            <i class="fas fa-times"></i> Xóa bộ lọc
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function để xác nhận xóa sản phẩm
        function confirmDelete(productName) {
            return confirm('Bạn có chắc chắn muốn xóa sản phẩm "' + productName + '" không?\n\nHành động này không thể hoàn tác!');
        }

        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.createElement('button');
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            menuToggle.style.cssText = `
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: var(--dark-color);
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 5px;
                font-size: 1.2rem;
                display: none;
            `;
            
            document.body.appendChild(menuToggle);
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            function checkWidth() {
                if (window.innerWidth <= 768) {
                    menuToggle.style.display = 'block';
                } else {
                    menuToggle.style.display = 'none';
                    sidebar.classList.remove('active');
                }
            }
            
            checkWidth();
            window.addEventListener('resize', checkWidth);
        });
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>