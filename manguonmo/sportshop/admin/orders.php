<?php
session_start();
include "../config.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Xử lý các action
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $order_id = (int)$_GET['id'];
    
    switch ($action) {
        case 'update_status':
            if (isset($_POST['status'])) {
                $status = $conn->real_escape_string($_POST['status']);
                $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("si", $status, $order_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Cập nhật trạng thái đơn hàng thành công!";
                } else {
                    $_SESSION['error'] = "Lỗi khi cập nhật trạng thái!";
                }
            }
            break;
            
        case 'delete':
            // Bắt đầu transaction
            $conn->begin_transaction();
            try {
                // Lấy thông tin chi tiết đơn hàng để hoàn lại số lượng tồn kho
                $details_sql = "SELECT product_id, size_id, qty FROM order_details WHERE order_id = ?";
                $details_stmt = $conn->prepare($details_sql);
                $details_stmt->bind_param("i", $order_id);
                $details_stmt->execute();
                $details_result = $details_stmt->get_result();
                
                // Hoàn lại số lượng tồn kho
                while ($detail = $details_result->fetch_assoc()) {
                    if ($detail['size_id']) {
                        $restore_sql = "UPDATE product_sizes SET quantity = quantity + ? WHERE id = ?";
                        $restore_stmt = $conn->prepare($restore_sql);
                        $restore_stmt->bind_param("ii", $detail['qty'], $detail['size_id']);
                        $restore_stmt->execute();
                    }
                }
                
                // Xóa chi tiết đơn hàng
                $delete_details_sql = "DELETE FROM order_details WHERE order_id = ?";
                $delete_details_stmt = $conn->prepare($delete_details_sql);
                $delete_details_stmt->bind_param("i", $order_id);
                $delete_details_stmt->execute();
                
                // Xóa đơn hàng
                $delete_order_sql = "DELETE FROM orders WHERE id = ?";
                $delete_order_stmt = $conn->prepare($delete_order_sql);
                $delete_order_stmt->bind_param("i", $order_id);
                $delete_order_stmt->execute();
                
                $conn->commit();
                $_SESSION['success'] = "Xóa đơn hàng thành công!";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = "Lỗi khi xóa đơn hàng: " . $e->getMessage();
            }
            break;
    }
    
    header("Location: orders.php");
    exit();
}

// Xử lý tìm kiếm và lọc
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Xây dựng query
$query_where = "WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query_where .= " AND (o.id LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ssss';
}

if (!empty($status_filter)) {
    $query_where .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($date_from)) {
    $query_where .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $query_where .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Lấy tổng số đơn hàng
$count_query = "SELECT COUNT(*) as total FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                $query_where";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_orders / $limit);

// Lấy danh sách đơn hàng
$query = "
    SELECT o.*, 
           u.fullname as customer_name, 
           u.email as customer_email,
           u.phone as customer_phone,
           (SELECT COUNT(*) FROM order_details WHERE order_id = o.id) as item_count,
           (SELECT SUM(qty) FROM order_details WHERE order_id = o.id) as total_qty
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    $query_where
    ORDER BY o.created_at DESC
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
$orders_result = $stmt->get_result();

// Thống kê
$stats_sql = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'shipping' THEN 1 ELSE 0 END) as shipping_orders,
        SUM(CASE WHEN status = 'cancel' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(total) as total_revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - Admin Sport Fashion</title>
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card.revenue {
            border-left-color: var(--success-color);
        }

        .stat-card.pending {
            border-left-color: var(--warning-color);
        }

        .stat-card.cancelled {
            border-left-color: var(--danger-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Search and Filter */
        .search-filter-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 25px;
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

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-shipping {
            background: #d4edda;
            color: #155724;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancel {
            background: #f8d7da;
            color: #721c24;
        }

        /* Action Buttons */
        .btn-action {
            padding: 6px 10px;
            font-size: 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            border: none;
            font-weight: 600;
            margin: 2px;
        }

        .btn-view {
            background: var(--primary-color);
            color: white;
        }

        .btn-view:hover {
            background: #2980b9;
            color: white;
        }

        .btn-edit {
            background: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background: #e67e22;
            color: white;
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        /* Order Details */
        .order-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }

        .customer-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
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

        .order-items {
            max-height: 200px;
            overflow-y: auto;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .item-meta {
            font-size: 0.8rem;
            color: #6c757d;
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
            <a href="products.php">
                <i class="fas fa-shopping-bag"></i>
                <span>Quản lý sản phẩm</span>
            </a>
            <a href="orders.php" class="active">
                <i class="fas fa-shipping-fast"></i>
                <span>Quản lý đơn hàng</span>
            </a>
            <a href="blog.php">
                <i class="fas fa-blog"></i>
                <span>Quản lý bài viết</span>
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
            <h1><i class="fas fa-shipping-fast me-2"></i>Quản lý Đơn hàng</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION["fullname"], 0, 1)); ?>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- Hiển thị thông báo -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?= $stats['total_orders'] ?></span>
                <span class="stat-label">Tổng đơn hàng (30 ngày)</span>
            </div>
            <div class="stat-card revenue">
                <span class="stat-number"><?= number_format($stats['total_revenue'] ?? 0) ?>₫</span>
                <span class="stat-label">Doanh thu (30 ngày)</span>
            </div>
            <div class="stat-card pending">
                <span class="stat-number"><?= $stats['pending_orders'] ?></span>
                <span class="stat-label">Đơn chờ xử lý</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $stats['shipping_orders'] ?></span>
                <span class="stat-label">Đang giao hàng</span>
            </div>
            <div class="stat-card cancelled">
                <span class="stat-number"><?= $stats['cancelled_orders'] ?></span>
                <span class="stat-label">Đơn đã hủy</span>
            </div>
        </div>

        <!-- SEARCH AND FILTER -->
        <div class="search-filter-card">
            <form method="GET" class="filter-row">
                <div>
                    <label class="form-label fw-semibold">Tìm kiếm</label>
                    <input type="text" class="form-control" name="search" placeholder="Tìm theo mã đơn, tên, email, SĐT..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <label class="form-label fw-semibold">Trạng thái</label>
                    <select class="form-select" name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                        <option value="paid" <?= $status_filter == 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                        <option value="shipping" <?= $status_filter == 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                        <option value="cancel" <?= $status_filter == 'cancel' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                </div>
                <div>
                    <label class="form-label fw-semibold">Từ ngày</label>
                    <input type="date" class="form-control" name="date_from" value="<?= $date_from ?>">
                </div>
                <div>
                    <label class="form-label fw-semibold">Đến ngày</label>
                    <input type="date" class="form-control" name="date_to" value="<?= $date_to ?>">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary h-100">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- ORDERS TABLE -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list me-2"></i>Danh sách đơn hàng</h3>
                <div class="text-muted">
                    Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($orders_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="80">Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th width="120">Sản phẩm</th>
                                    <th width="120">Tổng tiền</th>
                                    <th width="120">Phương thức</th>
                                    <th width="120">Trạng thái</th>
                                    <th width="150">Ngày đặt</th>
                                    <th width="150">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): 
                                    // Lấy chi tiết đơn hàng
                                    $details_sql = "SELECT od.*, p.name, p.image, ps.size 
                                                   FROM order_details od 
                                                   JOIN products p ON p.id = od.product_id 
                                                   LEFT JOIN product_sizes ps ON ps.id = od.size_id 
                                                   WHERE od.order_id = ?";
                                    $details_stmt = $conn->prepare($details_sql);
                                    $details_stmt->bind_param("i", $order['id']);
                                    $details_stmt->execute();
                                    $order_details = $details_stmt->get_result();
                                ?>
                                <tr>
                                    <td><strong>#<?= $order["id"] ?></strong></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($order["customer_name"] ?? 'Khách vãng lai') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($order["customer_email"] ?? '') ?></small>
                                        <?php if (!empty($order["customer_phone"])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($order["customer_phone"]) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?= $order["item_count"] ?> sản phẩm</span>
                                        <br><small class="text-muted"><?= $order["total_qty"] ?> cái</small>
                                    </td>
                                    <td class="fw-bold text-success"><?= number_format($order["total"]) ?>₫</td>
                                    <td>
                                        <?php
                                        $payment_methods = [
                                            'cod' => 'COD',
                                            'bank' => 'Chuyển khoản',
                                            'momo' => 'MoMo'
                                        ];
                                        ?>
                                        <span class="badge bg-light text-dark"><?= $payment_methods[$order["payment_method"]] ?? $order["payment_method"] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_classes = [
                                            'pending' => 'badge-pending',
                                            'paid' => 'badge-paid',
                                            'shipping' => 'badge-shipping',
                                            'completed' => 'badge-completed',
                                            'cancel' => 'badge-cancel'
                                        ];
                                        $status_texts = [
                                            'pending' => 'Chờ xử lý',
                                            'paid' => 'Đã thanh toán',
                                            'shipping' => 'Đang giao',
                                            'completed' => 'Hoàn thành',
                                            'cancel' => 'Đã hủy'
                                        ];
                                        ?>
                                        <span class="status-badge <?= $status_classes[$order["status"]] ?>">
                                            <?= $status_texts[$order["status"]] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($order["created_at"])) ?></small>
                                        <br><small class="text-muted"><?= date('H:i', strtotime($order["created_at"])) ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="order_detail.php?id=<?= $order['id'] ?>" 
                                               class="btn-action btn-view" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <!-- Form cập nhật trạng thái -->
                                            <form method="POST" action="?action=update_status&id=<?= $order['id'] ?>" class="d-inline">
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 120px;">
                                                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                                    <option value="paid" <?= $order['status'] == 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                                                    <option value="shipping" <?= $order['status'] == 'shipping' ? 'selected' : '' ?>>Đang giao</option>
                                                    <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                                    <option value="cancel" <?= $order['status'] == 'cancel' ? 'selected' : '' ?>>Hủy</option>
                                                </select>
                                            </form>
                                            
                                            <a href="?action=delete&id=<?= $order['id'] ?>" 
                                               class="btn-action btn-delete" 
                                               title="Xóa đơn hàng"
                                               onclick="return confirm('Bạn có chắc muốn xóa đơn hàng #<?= $order['id'] ?>? Hành động này không thể hoàn tác!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Chi tiết đơn hàng (ẩn/hiện) -->
                                <tr class="order-details-row" style="display: none;">
                                    <td colspan="8">
                                        <div class="order-details">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-user me-2"></i>Thông tin khách hàng</h6>
                                                    <div class="customer-info">
                                                        <p><strong>Họ tên:</strong> <?= htmlspecialchars($order["fullname"] ?? $order["customer_name"] ?? 'N/A') ?></p>
                                                        <p><strong>Email:</strong> <?= htmlspecialchars($order["email"] ?? $order["customer_email"] ?? 'N/A') ?></p>
                                                        <p><strong>SĐT:</strong> <?= htmlspecialchars($order["phone"] ?? $order["customer_phone"] ?? 'N/A') ?></p>
                                                        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order["address"] ?? 'N/A') ?></p>
                                                        <?php if (!empty($order["note"])): ?>
                                                            <p><strong>Ghi chú:</strong> <?= htmlspecialchars($order["note"]) ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-box me-2"></i>Sản phẩm đã đặt</h6>
                                                    <div class="order-items">
                                                        <?php while ($detail = $order_details->fetch_assoc()): ?>
                                                            <div class="order-item">
                                                                <img src="../assets/images/products/<?= htmlspecialchars($detail['image']) ?>" 
                                                                     alt="<?= htmlspecialchars($detail['name']) ?>" 
                                                                     class="item-image"
                                                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7huqNwPC90ZXh0Pjwvc3ZnPg=='">
                                                                <div class="item-details">
                                                                    <div class="item-name"><?= htmlspecialchars($detail['name']) ?></div>
                                                                    <div class="item-meta">
                                                                        Size: <?= $detail['size'] ?? '-' ?> | 
                                                                        Số lượng: <?= $detail['qty'] ?> | 
                                                                        Giá: <?= number_format($detail['price']) ?>₫
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endwhile; ?>
                                                    </div>
                                                </div>
                                            </div>
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
                                        <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
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
                        <i class="fas fa-shopping-cart"></i>
                        <h4>Không tìm thấy đơn hàng</h4>
                        <p class="text-muted mb-4">Hãy thử thay đổi bộ lọc tìm kiếm!</p>
                        <a href="orders.php" class="btn btn-primary">
                            <i class="fas fa-times"></i> Xóa bộ lọc
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle hiển thị chi tiết đơn hàng
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.btn-view');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const orderRow = this.closest('tr');
                    const detailsRow = orderRow.nextElementSibling;
                    
                    if (detailsRow.style.display === 'none') {
                        detailsRow.style.display = 'table-row';
                        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        detailsRow.style.display = 'none';
                        this.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                });
            });

            // Mobile menu toggle
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

        // Xác nhận xóa đơn hàng
        function confirmDelete(orderId) {
            return confirm('Bạn có chắc muốn xóa đơn hàng #' + orderId + '? Hành động này không thể hoàn tác!');
        }
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>