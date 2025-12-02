<?php
session_start();
include "../config.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: /manguonmo/sportshop/index.php");
    exit();
}

// Xử lý filter date
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Thống kê tổng quan
$total_revenue = $conn->query("
    SELECT SUM(od.price * od.qty) as total_revenue
    FROM order_details od
    INNER JOIN orders o ON od.order_id = o.id
    WHERE o.status IN ('paid', 'shipping', 'completed')
    AND o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
")->fetch_assoc()['total_revenue'] ?? 0;

$total_orders = $conn->query("
    SELECT COUNT(*) as total_orders
    FROM orders
    WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
")->fetch_assoc()['total_orders'] ?? 0;

$total_customers = $conn->query("
    SELECT COUNT(DISTINCT user_id) as total_customers
    FROM orders
    WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
")->fetch_assoc()['total_customers'] ?? 0;

$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Thống kê theo môn thể thao
$sport_revenue = $conn->query("
    SELECT p.sport_type, SUM(od.price * od.qty) as revenue, COUNT(od.id) as order_count
    FROM order_details od
    INNER JOIN products p ON od.product_id = p.id
    INNER JOIN orders o ON od.order_id = o.id
    WHERE o.status IN ('paid', 'shipping', 'completed')
    AND o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    AND p.sport_type != 'none'
    GROUP BY p.sport_type
    ORDER BY revenue DESC
");

// Sản phẩm bán chạy
$best_selling_products = $conn->query("
    SELECT p.id, p.name, p.image, c.name as category_name,
           SUM(od.qty) as total_sold, SUM(od.price * od.qty) as revenue
    FROM order_details od
    INNER JOIN products p ON od.product_id = p.id
    INNER JOIN categories c ON p.category_id = c.id
    INNER JOIN orders o ON od.order_id = o.id
    WHERE o.status IN ('paid', 'shipping', 'completed')
    AND o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
");

// Phân tích khách hàng
$customer_analysis = $conn->query("
    SELECT u.id, u.fullname, u.email, u.created_at,
           COUNT(o.id) as order_count,
           SUM(o.total) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'user'
    AND (o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59' OR o.id IS NULL)
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 15
");

// Theo dõi tồn kho
$inventory_analysis = $conn->query("
    SELECT p.id, p.name, p.image, c.name as category_name,
           ps.size, ps.quantity,
           (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) as total_quantity
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_sizes ps ON p.id = ps.product_id
    WHERE ps.quantity > 0
    ORDER BY total_quantity ASC, ps.quantity ASC
");

// Cảnh báo hàng sắp hết (dưới 10 sản phẩm)
$low_stock_alerts = $conn->query("
    SELECT p.id, p.name, p.image, c.name as category_name,
           ps.size, ps.quantity
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    INNER JOIN product_sizes ps ON p.id = ps.product_id
    WHERE ps.quantity > 0 AND ps.quantity <= 10
    ORDER BY ps.quantity ASC
");

// Thống kê doanh thu theo tháng (cho biểu đồ)
$monthly_revenue = $conn->query("
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') as month,
        SUM(od.price * od.qty) as revenue
    FROM orders o
    INNER JOIN order_details od ON o.id = od.order_id
    WHERE o.status IN ('paid', 'shipping', 'completed')
    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month ASC
");

// Chuẩn bị dữ liệu cho biểu đồ
$chart_labels = [];
$chart_data = [];
while ($row = $monthly_revenue->fetch_assoc()) {
    $chart_labels[] = date('M Y', strtotime($row['month']));
    $chart_data[] = $row['revenue'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê & Báo cáo - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-card.orders {
            border-left-color: var(--primary-color);
        }

        .stat-card.customers {
            border-left-color: var(--warning-color);
        }

        .stat-card.avg-order {
            border-left-color: var(--danger-color);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Charts */
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            height: 400px;
        }

        /* Tables */
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
        }

        .table-header h3 {
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
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
        }

        /* Badges */
        .sport-badge {
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .low-stock {
            background: var(--danger-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .medium-stock {
            background: var(--warning-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .high-stock {
            background: var(--success-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Alert Card */
        .alert-card {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .alert-card.danger {
            background: #f8d7da;
            border-color: #f5c6cb;
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
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .chart-container {
                height: 300px;
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
            <a href="products.php">
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
            <a href="reports.php" class="active">
                <i class="fas fa-chart-bar"></i>
                <span>Thống kê báo cáo</span>
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
            <h1>📊 Thống kê & Báo cáo</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION["fullname"], 0, 1)); ?>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Loại báo cáo</label>
                    <select class="form-select" name="report_type">
                        <option value="overview" <?= $report_type == 'overview' ? 'selected' : '' ?>>Tổng quan</option>
                        <option value="sports" <?= $report_type == 'sports' ? 'selected' : '' ?>>Theo môn thể thao</option>
                        <option value="products" <?= $report_type == 'products' ? 'selected' : '' ?>>Sản phẩm bán chạy</option>
                        <option value="customers" <?= $report_type == 'customers' ? 'selected' : '' ?>>Phân tích khách hàng</option>
                        <option value="inventory" <?= $report_type == 'inventory' ? 'selected' : '' ?>>Quản lý tồn kho</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Lọc dữ liệu
                    </button>
                </div>
            </form>
        </div>

        <!-- Overview Stats -->
        <?php if ($report_type == 'overview'): ?>
        <div class="stats-grid">
            <div class="stat-card revenue">
                <div class="stat-icon text-success">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-number text-success"><?= number_format($total_revenue) ?>₫</div>
                <div class="stat-label">Tổng doanh thu</div>
            </div>
            
            <div class="stat-card orders">
                <div class="stat-icon text-primary">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number text-primary"><?= number_format($total_orders) ?></div>
                <div class="stat-label">Tổng đơn hàng</div>
            </div>
            
            <div class="stat-card customers">
                <div class="stat-icon text-warning">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number text-warning"><?= number_format($total_customers) ?></div>
                <div class="stat-label">Khách hàng</div>
            </div>
            
            <div class="stat-card avg-order">
                <div class="stat-icon text-danger">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number text-danger"><?= number_format($avg_order_value) ?>₫</div>
                <div class="stat-label">Giá trị đơn trung bình</div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="chart-container">
            <canvas id="revenueChart"></canvas>
        </div>
        <?php endif; ?>

        <!-- Sports Revenue Report -->
        <?php if ($report_type == 'sports' && $sport_revenue->num_rows > 0): ?>
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-running me-2"></i>Doanh thu theo môn thể thao</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Môn thể thao</th>
                            <th>Doanh thu</th>
                            <th>Số đơn hàng</th>
                            <th>Tỷ lệ</th>
                            <th>Doanh thu trung bình</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_sport_revenue = 0;
                        $sport_revenue->data_seek(0);
                        while($sport = $sport_revenue->fetch_assoc()): 
                            $total_sport_revenue += $sport['revenue'];
                        ?>
                        <tr>
                            <td>
                                <span class="sport-badge"><?= $sport['sport_type'] ?></span>
                            </td>
                            <td><strong><?= number_format($sport['revenue']) ?>₫</strong></td>
                            <td><?= number_format($sport['order_count']) ?></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $total_revenue > 0 ? ($sport['revenue'] / $total_revenue * 100) : 0 ?>%">
                                        <?= $total_revenue > 0 ? number_format($sport['revenue'] / $total_revenue * 100, 1) : 0 ?>%
                                    </div>
                                </div>
                            </td>
                            <td><?= number_format($sport['order_count'] > 0 ? $sport['revenue'] / $sport['order_count'] : 0) ?>₫</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td><strong>Tổng cộng</strong></td>
                            <td><strong><?= number_format($total_sport_revenue) ?>₫</strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Best Selling Products -->
        <?php if ($report_type == 'products' && $best_selling_products->num_rows > 0): ?>
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-fire me-2"></i>Top 10 sản phẩm bán chạy</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Đã bán</th>
                            <th>Doanh thu</th>
                            <th>Giá trung bình</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = $best_selling_products->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; margin-right: 10px;"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7EkOG6o2ggc+G6o24gcGjhuqdtPC90ZXh0Pjwvc3ZnPg=='">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                        <small class="text-muted">ID: #<?= $product['id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td><span class="badge bg-primary"><?= number_format($product['total_sold']) ?></span></td>
                            <td><strong><?= number_format($product['revenue']) ?>₫</strong></td>
                            <td><?= number_format($product['total_sold'] > 0 ? $product['revenue'] / $product['total_sold'] : 0) ?>₫</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Customer Analysis -->
        <?php if ($report_type == 'customers' && $customer_analysis->num_rows > 0): ?>
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-users me-2"></i>Phân tích khách hàng</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Email</th>
                            <th>Ngày tham gia</th>
                            <th>Số đơn hàng</th>
                            <th>Tổng chi tiêu</th>
                            <th>Đơn hàng cuối</th>
                            <th>Giá trị TB/đơn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($customer = $customer_analysis->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($customer['fullname']) ?></div>
                                <small class="text-muted">ID: #<?= $customer['id'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($customer['email']) ?></td>
                            <td><?= date('d/m/Y', strtotime($customer['created_at'])) ?></td>
                            <td><span class="badge bg-primary"><?= number_format($customer['order_count']) ?></span></td>
                            <td><strong><?= number_format($customer['total_spent'] ?? 0) ?>₫</strong></td>
                            <td>
                                <?php if ($customer['last_order_date']): ?>
                                    <?= date('d/m/Y', strtotime($customer['last_order_date'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Chưa có đơn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($customer['order_count'] > 0): ?>
                                    <?= number_format(($customer['total_spent'] ?? 0) / $customer['order_count']) ?>₫
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Inventory Management -->
        <?php if ($report_type == 'inventory'): ?>
        <!-- Low Stock Alerts -->
        <?php if ($low_stock_alerts->num_rows > 0): ?>
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Cảnh báo hàng sắp hết</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Size</th>
                            <th>Số lượng</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($alert = $low_stock_alerts->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../assets/images/products/<?= htmlspecialchars($alert['image']) ?>" 
                                         alt="<?= htmlspecialchars($alert['name']) ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; margin-right: 10px;"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7EkOG6o2ggc+G6o24gcGjhuqdtPC90ZXh0Pjwvc3ZnPg=='">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($alert['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($alert['category_name']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><strong><?= $alert['size'] ?></strong></td>
                            <td>
                                <span class="badge bg-danger"><?= $alert['quantity'] ?></span>
                            </td>
                            <td>
                                <span class="low-stock">SẮP HẾT HÀNG</span>
                            </td>
                            <td>
                                <a href="products.php?edit=<?= $alert['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit me-1"></i>Cập nhật
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Inventory Analysis -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="fas fa-boxes me-2"></i>Phân tích tồn kho</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Size</th>
                            <th>Số lượng</th>
                            <th>Tổng tồn kho</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($inventory = $inventory_analysis->fetch_assoc()): 
                            $stock_status = '';
                            if ($inventory['quantity'] <= 10) {
                                $stock_status = 'low-stock';
                            } elseif ($inventory['quantity'] <= 50) {
                                $stock_status = 'medium-stock';
                            } else {
                                $stock_status = 'high-stock';
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../assets/images/products/<?= htmlspecialchars($inventory['image']) ?>" 
                                         alt="<?= htmlspecialchars($inventory['name']) ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; margin-right: 10px;"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7EkOG6o2ggc+G6o24gcGjhuqdtPC90ZXh0Pjwvc3ZnPg=='">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($inventory['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($inventory['category_name']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><strong><?= $inventory['size'] ?></strong></td>
                            <td>
                                <span class="badge bg-secondary"><?= $inventory['quantity'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?= $inventory['total_quantity'] ?></span>
                            </td>
                            <td>
                                <span class="<?= $stock_status ?>">
                                    <?php 
                                    if ($inventory['quantity'] <= 10) echo 'THẤP';
                                    elseif ($inventory['quantity'] <= 50) echo 'TRUNG BÌNH';
                                    else echo 'CAO';
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        <?php if ($report_type == 'overview'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        label: 'Doanh thu theo tháng',
                        data: <?= json_encode($chart_data) ?>,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN', { 
                                        style: 'currency', 
                                        currency: 'VND' 
                                    }).format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN', { 
                                        style: 'currency', 
                                        currency: 'VND' 
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        });
        <?php endif; ?>

        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.createElement('button');
            menuToggle.innerHTML = '☰';
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
$conn->close();
?>