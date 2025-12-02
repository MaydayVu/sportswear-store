<?php
session_start();
include "config.php";

$order = null;
$order_details = [];
$error = '';
$success = '';

// Xử lý tìm kiếm đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_order'])) {
    $order_code = trim($_POST['order_code']);
    $phone = trim($_POST['phone']);
    
    if (empty($order_code) || empty($phone)) {
        $error = 'Vui lòng nhập mã đơn hàng và số điện thoại';
    } else {
        // Tìm đơn hàng theo mã và số điện thoại
        $stmt = $conn->prepare("
            SELECT o.*, u.fullname as customer_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ? AND o.phone = ?
        ");
        $stmt->bind_param("is", $order_code, $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            
            // Lấy chi tiết đơn hàng
            $details_stmt = $conn->prepare("
                SELECT od.*, p.name as product_name, p.image, ps.size 
                FROM order_details od 
                JOIN products p ON od.product_id = p.id 
                JOIN product_sizes ps ON od.size_id = ps.id 
                WHERE od.order_id = ?
            ");
            $details_stmt->bind_param("i", $order['id']);
            $details_stmt->execute();
            $order_details = $details_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $success = 'Tìm thấy đơn hàng #' . $order['id'];
        } else {
            $error = 'Không tìm thấy đơn hàng. Vui lòng kiểm tra lại mã đơn hàng và số điện thoại.';
        }
    }
}

// Nếu user đã đăng nhập, lấy danh sách đơn hàng của họ
$user_orders = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_orders_stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(od.id) as item_count,
               SUM(od.qty * od.price) as subtotal
        FROM orders o 
        LEFT JOIN order_details od ON o.id = od.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $user_orders_stmt->bind_param("i", $user_id);
    $user_orders_stmt->execute();
    $user_orders = $user_orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi đơn hàng - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #000;
            --accent-color: #e4002b;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
        }

        .tracking-page {
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

        .tracking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 20px 25px;
            border-bottom: none;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .tracking-form {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.1);
        }

        .btn-track {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-track:hover {
            background: #333;
            transform: translateY(-2px);
        }

        /* Order Status */
        .order-status {
            padding: 30px;
            border-bottom: 1px solid #e9ecef;
        }

        .status-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 30px 0;
        }

        .status-timeline::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .status-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .status-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .status-step.active .status-icon {
            background: var(--success-color);
            color: white;
        }

        .status-step.completed .status-icon {
            background: var(--success-color);
            color: white;
        }

        .status-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            color: #6c757d;
        }

        .status-step.active .status-label {
            color: var(--primary-color);
        }

        .status-step.completed .status-label {
            color: var(--success-color);
        }

        /* Order Details */
        .order-details {
            padding: 30px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .detail-value {
            color: #6c757d;
        }

        .product-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .product-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .product-price {
            font-weight: 600;
            color: var(--primary-color);
        }

        /* User Orders */
        .user-orders {
            margin-top: 40px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid var(--primary-color);
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .order-id {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .order-status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1ecf1; color: #0c5460; }
        .status-shipping { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancel { background: #f8d7da; color: #721c24; }

        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .meta-value {
            font-weight: 600;
            color: #333;
        }

        .btn-view-order {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-view-order:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .status-timeline {
                flex-direction: column;
                gap: 20px;
            }

            .status-timeline::before {
                display: none;
            }

            .status-step {
                flex-direction: row;
                text-align: left;
            }

            .status-icon {
                margin-right: 15px;
                margin-bottom: 0;
            }

            .order-meta {
                grid-template-columns: 1fr;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            .tracking-form {
                padding: 20px;
            }

            .order-details {
                padding: 20px;
            }

            .page-header {
                padding: 40px 0;
            }
        }

        .help-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            margin-top: 40px;
        }

        .help-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f8f9fa;
        }

        .help-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .help-icon {
            width: 40px;
            height: 40px;
            background: var(--light-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .help-content h4 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
            color: #333;
        }

        .help-content p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="tracking-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">Theo dõi đơn hàng</h1>
                <p class="page-subtitle">Kiểm tra trạng thái đơn hàng của bạn một cách dễ dàng</p>
            </div>
        </div>

        <div class="container">
            <!-- Tracking Form -->
            <div class="tracking-card">
                <div class="card-header">
                    <h3><i class="fas fa-search-location me-2"></i>Tra cứu đơn hàng</h3>
                </div>
                <div class="tracking-form">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?= $success ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Mã đơn hàng *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="order_code" 
                                           value="<?= isset($_POST['order_code']) ? htmlspecialchars($_POST['order_code']) : '' ?>" 
                                           placeholder="Nhập mã đơn hàng (ví dụ: 12345)" 
                                           required>
                                    <small class="form-text text-muted">Mã đơn hàng được gửi trong email xác nhận</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Số điện thoại *</label>
                                    <input type="tel" 
                                           class="form-control" 
                                           name="phone" 
                                           value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" 
                                           placeholder="Nhập số điện thoại đặt hàng" 
                                           required>
                                    <small class="form-text text-muted">Số điện thoại bạn đã sử dụng khi đặt hàng</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="track_order" class="btn-track">
                            <i class="fas fa-search me-2"></i>Tra cứu đơn hàng
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Status -->
            <?php if ($order): ?>
            <div class="tracking-card">
                <div class="card-header">
                    <h3><i class="fas fa-shipping-fast me-2"></i>Trạng thái đơn hàng #<?= $order['id'] ?></h3>
                </div>
                
                <!-- Status Timeline -->
                <div class="order-status">
                    <div class="status-timeline">
                        <?php
                        $statuses = [
                            'pending' => ['icon' => 'clock', 'label' => 'Chờ xác nhận'],
                            'paid' => ['icon' => 'credit-card', 'label' => 'Đã thanh toán'],
                            'shipping' => ['icon' => 'shipping-fast', 'label' => 'Đang giao hàng'],
                            'completed' => ['icon' => 'check-circle', 'label' => 'Hoàn thành']
                        ];
                        
                        $current_status = $order['status'];
                        $status_index = array_keys($statuses);
                        $current_index = array_search($current_status, $status_index);
                        
                        foreach ($statuses as $status => $info):
                            $step_class = '';
                            if ($status === $current_status) {
                                $step_class = 'active';
                            } elseif (array_search($status, $status_index) < $current_index) {
                                $step_class = 'completed';
                            }
                        ?>
                        <div class="status-step <?= $step_class ?>">
                            <div class="status-icon">
                                <i class="fas fa-<?= $info['icon'] ?>"></i>
                            </div>
                            <div class="status-label"><?= $info['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center">
                        <div class="current-status">
                            <strong>Trạng thái hiện tại:</strong>
                            <span class="badge status-<?= $order['status'] ?> order-status-badge">
                                <?= getStatusText($order['status']) ?>
                            </span>
                        </div>
                        <?php if ($order['status'] === 'shipping'): ?>
                            <p class="text-muted mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Đơn hàng đang trên đường giao đến bạn. Vui lòng giữ liên lạc.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="order-details">
                    <h4 class="mb-4">Chi tiết đơn hàng</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Thông tin giao hàng</h5>
                            <div class="detail-row">
                                <span class="detail-label">Người nhận:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['fullname']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Số điện thoại:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['phone']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['email']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Địa chỉ:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['address']) ?></span>
                            </div>
                            <?php if (!empty($order['note'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">Ghi chú:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['note']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Thông tin đơn hàng</h5>
                            <div class="detail-row">
                                <span class="detail-label">Mã đơn hàng:</span>
                                <span class="detail-value">#<?= $order['id'] ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Ngày đặt:</span>
                                <span class="detail-value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phương thức thanh toán:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['payment_method'] ?? 'Chưa xác định') ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Tổng tiền:</span>
                                <span class="detail-value" style="font-weight: 700; color: var(--accent-color);">
                                    <?= number_format($order['total']) ?>₫
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h5 class="mt-4 mb-3">Sản phẩm đã đặt</h5>
                    <?php foreach ($order_details as $item): ?>
                    <div class="product-item">
                        <img src="assets/images/products/<?= htmlspecialchars($item['image']) ?>" 
                             alt="<?= htmlspecialchars($item['product_name']) ?>" 
                             class="product-image"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iOCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="product-meta">
                                Size: <?= htmlspecialchars($item['size']) ?> | 
                                Số lượng: <?= $item['qty'] ?>
                            </div>
                        </div>
                        <div class="product-price"><?= number_format($item['price']) ?>₫</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- User's Recent Orders (if logged in) -->
            <?php if (isset($_SESSION['user_id']) && !empty($user_orders)): ?>
            <div class="user-orders">
                <h3 class="mb-4">Đơn hàng gần đây của bạn</h3>
                <?php foreach ($user_orders as $user_order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">Đơn hàng #<?= $user_order['id'] ?></div>
                        <span class="order-status-badge status-<?= $user_order['status'] ?>">
                            <?= getStatusText($user_order['status']) ?>
                        </span>
                    </div>
                    
                    <div class="order-meta">
                        <div class="meta-item">
                            <span class="meta-label">Ngày đặt</span>
                            <span class="meta-value"><?= date('d/m/Y', strtotime($user_order['created_at'])) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Số sản phẩm</span>
                            <span class="meta-value"><?= $user_order['item_count'] ?> sản phẩm</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Tổng tiền</span>
                            <span class="meta-value"><?= number_format($user_order['subtotal']) ?>₫</span>
                        </div>
                    </div>
                    
                    <a href="order_tracking.php?order_code=<?= $user_order['id'] ?>&phone=<?= urlencode($user_order['phone']) ?>" 
                       class="btn-view-order">
                        <i class="fas fa-eye me-1"></i>Xem chi tiết
                    </a>
                </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-3">
                    <a href="user_orders.php" class="btn btn-outline-dark">
                        <i class="fas fa-list me-2"></i>Xem tất cả đơn hàng
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Help Section -->
            <div class="help-section">
                <h3 class="mb-4">Cần hỗ trợ?</h3>
                <div class="help-item">
                    <div class="help-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="help-content">
                        <h4>Không tìm thấy đơn hàng?</h4>
                        <p>Vui lòng kiểm tra lại mã đơn hàng và số điện thoại. Mã đơn hàng được gửi trong email xác nhận đặt hàng.</p>
                    </div>
                </div>
                
                <div class="help-item">
                    <div class="help-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="help-content">
                        <h4>Đơn hàng đang xử lý?</h4>
                        <p>Đơn hàng thường được xử lý trong vòng 24 giờ. Nếu quá thời gian này, vui lòng liên hệ hỗ trợ.</p>
                    </div>
                </div>
                
                <div class="help-item">
                    <div class="help-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="help-content">
                        <h4>Liên hệ hỗ trợ</h4>
                        <p>Hotline: <strong>1900 1234</strong> (7:00 - 22:00) | Email: <strong>support@sportshop.com</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on search input
        document.addEventListener('DOMContentLoaded', function() {
            const orderCodeInput = document.querySelector('input[name="order_code"]');
            if (orderCodeInput) {
                orderCodeInput.focus();
            }

            // Smooth scrolling for order details
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('order_code') && document.querySelector('.order-status')) {
                document.querySelector('.order-status').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const orderCode = document.querySelector('input[name="order_code"]').value.trim();
            const phone = document.querySelector('input[name="phone"]').value.trim();
            
            if (!orderCode || !phone) {
                e.preventDefault();
                alert('Vui lòng nhập đầy đủ mã đơn hàng và số điện thoại.');
                return false;
            }
            
            if (!/^\d+$/.test(orderCode)) {
                e.preventDefault();
                alert('Mã đơn hàng phải là số.');
                return false;
            }
        });
    </script>
</body>
</html>

<?php
// Helper function to get status text
function getStatusText($status) {
    $statuses = [
        'pending' => 'Chờ xác nhận',
        'paid' => 'Đã thanh toán',
        'shipping' => 'Đang giao hàng',
        'completed' => 'Hoàn thành',
        'cancel' => 'Đã hủy'
    ];
    return $statuses[$status] ?? 'Không xác định';
}

$conn->close();
?>