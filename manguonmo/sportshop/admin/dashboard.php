<?php
session_start();
include "../config.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: /manguonmo/sportshop/index.php");
    exit();
}

// Lấy số liệu thống kê
$categories_count = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$products_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Lấy đơn hàng mới nhất
$recent_orders = $conn->query("
    SELECT o.*, u.fullname 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Lấy sản phẩm bán chạy
$top_products = $conn->query("
    SELECT p.name, p.image, SUM(od.qty) as total_sold
    FROM order_details od
    JOIN products p ON od.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sport Fashion</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        }

        .stat-card.categories {
            border-left-color: var(--primary-color);
        }

        .stat-card.products {
            border-left-color: var(--success-color);
        }

        .stat-card.orders {
            border-left-color: var(--warning-color);
        }

        .stat-card.users {
            border-left-color: var(--danger-color);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        /* Tables */
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
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .card-body {
            padding: 0;
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

        .product-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-shipping {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 25px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 600;
            transition: all 0.3s;
        }

        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .action-btn i {
            margin-right: 8px;
            font-size: 1.2rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
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
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
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
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-header {
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
            <a href="dashboard.php" class="active">
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
            <h1>📊 Dashboard - Xin chào, <?php echo htmlspecialchars($_SESSION["fullname"]); ?>!</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION["fullname"], 0, 1)); ?>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stat-card categories">
                <div class="stat-icon" style="color: var(--primary-color);">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-number"><?php echo $categories_count; ?></div>
                <div class="stat-label">Danh mục</div>
            </div>

            <div class="stat-card products">
                <div class="stat-icon" style="color: var(--success-color);">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-number"><?php echo $products_count; ?></div>
                <div class="stat-label">Sản phẩm</div>
            </div>

            <div class="stat-card orders">
                <div class="stat-icon" style="color: var(--warning-color);">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="stat-number"><?php echo $orders_count; ?></div>
                <div class="stat-label">Đơn hàng</div>
            </div>

            <div class="stat-card users">
                <div class="stat-icon" style="color: var(--danger-color);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $users_count; ?></div>
                <div class="stat-label">Người dùng</div>
            </div>
        </div>

        <!-- CONTENT GRID -->
        <div class="content-grid">
            <!-- Left Column -->
            <div>
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock me-2"></i>Đơn hàng gần đây</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày đặt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_orders->num_rows > 0): ?>
                                        <?php while($order = $recent_orders->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['fullname']); ?></td>
                                            <td><?php echo number_format($order['total']); ?>₫</td>
                                            <td>
                                                <?php 
                                                $status_class = [
                                                    'pending' => 'status-pending',
                                                    'paid' => 'status-paid',
                                                    'shipping' => 'status-shipping',
                                                    'completed' => 'status-completed',
                                                    'cancel' => 'status-cancel'
                                                ];
                                                $status_text = [
                                                    'pending' => 'Chờ xử lý',
                                                    'paid' => 'Đã thanh toán',
                                                    'shipping' => 'Đang giao',
                                                    'completed' => 'Hoàn thành',
                                                    'cancel' => 'Đã hủy'
                                                ];
                                                ?>
                                                <span class="status-badge <?php echo $status_class[$order['status']] ?? 'status-pending'; ?>">
                                                    <?php echo $status_text[$order['status']] ?? 'Chờ xử lý'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                Chưa có đơn hàng nào
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line me-2"></i>Sản phẩm bán chạy</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Hình ảnh</th>
                                        <th>Đã bán</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($top_products->num_rows > 0): ?>
                                        <?php while($product = $top_products->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td>
                                                <img src="../../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     class="product-img"
                                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7huqNwPC90ZXh0Pjwvc3ZnPg=='">
                                            </td>
                                            <td><?php echo $product['total_sold']; ?> sản phẩm</td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                Chưa có dữ liệu bán hàng
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt me-2"></i>Thao tác nhanh</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="product_add.php" class="action-btn">
                                <i class="fas fa-plus"></i>
                                Thêm sản phẩm
                            </a>
                            <a href="categories.php" class="action-btn">
                                <i class="fas fa-folder-plus"></i>
                                Thêm danh mục
                            </a>
                            <a href="blog.php" class="action-btn">
                                <i class="fas fa-edit"></i>
                                Viết bài mới
                            </a>
                            <a href="orders.php" class="action-btn">
                                <i class="fas fa-list"></i>
                                Xem đơn hàng
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle me-2"></i>Thông tin hệ thống</h3>
                    </div>
                    <div class="card-body">
                        <div style="padding: 20px;">
                            <div style="margin-bottom: 15px;">
                                <strong>Phiên bản:</strong> 1.0.0
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>Ngày cập nhật:</strong> <?php echo date('d/m/Y'); ?>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>Người dùng:</strong> <?php echo htmlspecialchars($_SESSION["fullname"]); ?>
                            </div>
                            <div>
                                <strong>Vai trò:</strong> Quản trị viên
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
            
            // Hide menu toggle on desktop
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