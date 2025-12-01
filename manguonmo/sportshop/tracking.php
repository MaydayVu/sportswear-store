<?php
session_start();
include "config.php";

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

// Xử lý tìm kiếm đơn hàng
if (isset($_POST['search_order'])) {
    $order_id = (int)$_POST['order_id'];
    $phone = trim($_POST['phone']);
    
    if ($order_id > 0 && !empty($phone)) {
        header("Location: tracking.php?order_id=" . $order_id . "&phone=" . urlencode($phone));
        exit;
    } else {
        $error = "Vui lòng nhập đầy đủ mã đơn hàng và số điện thoại!";
    }
}

// Lấy thông tin đơn hàng nếu có order_id và phone
$order = null;
$order_details = [];
$tracking_history = [];

if ($order_id > 0 && !empty($phone)) {
    $order_sql = "SELECT o.*, u.fullname as customer_name, u.email as customer_email 
                  FROM orders o 
                  LEFT JOIN users u ON o.user_id = u.id 
                  WHERE o.id = ? AND (o.phone = ? OR u.phone = ?)";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("iss", $order_id, $phone, $phone);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();
    
    if ($order) {
        // Lấy chi tiết đơn hàng
        $details_sql = "SELECT od.*, p.name, p.image, p.brand, ps.size 
                        FROM order_details od 
                        JOIN products p ON p.id = od.product_id 
                        LEFT JOIN product_sizes ps ON ps.id = od.size_id 
                        WHERE od.order_id = ?";
        $details_stmt = $conn->prepare($details_sql);
        $details_stmt->bind_param("i", $order_id);
        $details_stmt->execute();
        $order_details = $details_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Tạo lịch sử tracking dựa trên trạng thái
        $tracking_history = generateTrackingHistory($order);
    } else {
        $error = "Không tìm thấy đơn hàng với mã #$order_id và số điện thoại $phone";
    }
}

// Hàm tạo lịch sử tracking
function generateTrackingHistory($order) {
    $history = [];
    $created_time = strtotime($order['created_at']);
    
    // Đơn hàng được tạo
    $history[] = [
        'status' => 'ordered',
        'title' => 'Đơn hàng đã được đặt',
        'description' => 'Đơn hàng đã được xác nhận và đang chờ xử lý',
        'time' => date('d/m/Y H:i', $created_time),
        'completed' => true,
        'icon' => 'fas fa-shopping-cart'
    ];
    
    // Dựa vào trạng thái hiện tại để tạo timeline
    $status_timeline = [
        'pending' => [
            'status' => 'processing',
            'title' => 'Đang xử lý',
            'description' => 'Đơn hàng đang được xử lý bởi nhân viên',
            'completed' => false,
            'icon' => 'fas fa-cog'
        ],
        'paid' => [
            'status' => 'paid',
            'title' => 'Đã thanh toán',
            'description' => 'Đơn hàng đã được thanh toán thành công',
            'completed' => true,
            'icon' => 'fas fa-credit-card'
        ],
        'shipping' => [
            'status' => 'shipping',
            'title' => 'Đang giao hàng',
            'description' => 'Đơn hàng đang được vận chuyển đến bạn',
            'completed' => false,
            'icon' => 'fas fa-shipping-fast'
        ],
        'completed' => [
            'status' => 'completed',
            'title' => 'Giao hàng thành công',
            'description' => 'Đơn hàng đã được giao thành công',
            'completed' => true,
            'icon' => 'fas fa-check-circle'
        ],
        'cancel' => [
            'status' => 'cancelled',
            'title' => 'Đơn hàng đã hủy',
            'description' => 'Đơn hàng đã được hủy',
            'completed' => true,
            'icon' => 'fas fa-times-circle'
        ]
    ];
    
    // Thêm các bước vào timeline dựa trên trạng thái
    $current_status = $order['status'];
    $status_order = ['pending', 'paid', 'shipping', 'completed'];
    
    foreach ($status_order as $status) {
        if (isset($status_timeline[$status])) {
            $step = $status_timeline[$status];
            $step['completed'] = array_search($status, $status_order) <= array_search($current_status, $status_order);
            
            // Nếu là trạng thái hiện tại và chưa hoàn thành, thêm thời gian ước tính
            if ($status === $current_status && !$step['completed'] && $status !== 'completed') {
                $step['description'] .= ' - Dự kiến: ' . date('d/m/Y', strtotime('+3 days', $created_time));
            }
            
            $history[] = $step;
            
            if ($status === $current_status) {
                break;
            }
        }
    }
    
    // Nếu đơn hàng bị hủy
    if ($current_status === 'cancel') {
        $history[] = $status_timeline['cancel'];
    }
    
    return $history;
}

// Hàm lấy mô tả trạng thái
function getStatusDescription($status) {
    $descriptions = [
        'pending' => 'Đơn hàng đang chờ xử lý',
        'paid' => 'Đơn hàng đã được thanh toán',
        'shipping' => 'Đơn hàng đang được vận chuyển',
        'completed' => 'Đơn hàng đã giao thành công',
        'cancel' => 'Đơn hàng đã bị hủy'
    ];
    
    return $descriptions[$status] ?? 'Đang cập nhật trạng thái';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi Đơn hàng - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #fff;
            --accent-color: #e4002b;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --gray-light: #f5f5f5;
            --gray-medium: #767676;
        }

        .tracking-page {
            padding: 60px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .tracking-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .tracking-card {
            background: var(--secondary-color);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tracking-header {
            background: var(--primary-color);
            color: var(--secondary-color);
            padding: 30px;
            text-align: center;
        }

        .tracking-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .tracking-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .search-section {
            padding: 40px;
            background: var(--gray-light);
        }

        .search-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
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
            color: var(--secondary-color);
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-track:hover {
            background: #333;
            transform: translateY(-2px);
        }

        .order-info-section {
            padding: 40px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-light);
        }

        .order-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1ecf1; color: #0c5460; }
        .status-shipping { background: #d4edda; color: #155724; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancel { background: #f8d7da; color: #721c24; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .info-card {
            background: var(--gray-light);
            padding: 20px;
            border-radius: 10px;
        }

        .info-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray-medium);
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--primary-color);
            font-weight: 500;
        }

        /* Tracking Timeline */
        .tracking-timeline {
            position: relative;
            padding: 40px 0;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
        }

        .timeline-icon.completed {
            background: var(--success-color);
            color: white;
        }

        .timeline-icon.current {
            background: var(--primary-color);
            color: white;
            animation: pulse 2s infinite;
        }

        .timeline-icon.pending {
            background: var(--gray-light);
            color: var(--gray-medium);
        }

        .timeline-content {
            flex: 1;
            padding-top: 5px;
        }

        .timeline-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .timeline-description {
            color: var(--gray-medium);
            margin-bottom: 5px;
        }

        .timeline-time {
            font-size: 0.9rem;
            color: var(--gray-medium);
            font-weight: 500;
        }

        .timeline-connector {
            position: absolute;
            left: 25px;
            top: 50px;
            bottom: -30px;
            width: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .timeline-item:last-child .timeline-connector {
            display: none;
        }

        .timeline-item.completed .timeline-connector {
            background: var(--success-color);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Order Items */
        .order-items {
            margin-top: 40px;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 1px solid var(--gray-light);
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .order-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .item-meta {
            color: var(--gray-medium);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .item-price {
            font-weight: 700;
            color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .contact-support {
            background: var(--gray-light);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-top: 40px;
        }

        .support-phone {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 10px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .tracking-header h1 {
                font-size: 2rem;
            }
            
            .order-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .timeline-item {
                flex-direction: column;
            }
            
            .timeline-icon {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .timeline-connector {
                left: 25px;
                top: 50px;
                bottom: -30px;
            }
        }

        @media (max-width: 576px) {
            .tracking-page {
                padding: 30px 0;
            }
            
            .search-section, .order-info-section {
                padding: 20px;
            }
            
            .order-item {
                flex-direction: column;
                text-align: center;
            }
            
            .item-image {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }

        .security-note {
            background: #e8f5e8;
            color: #2d5016;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="tracking-page">
        <div class="tracking-container">
            <div class="tracking-card">
                <!-- Header -->
                <div class="tracking-header">
                    <h1><i class="fas fa-truck me-2"></i>Theo dõi Đơn hàng</h1>
                    <p>Kiểm tra trạng thái đơn hàng của bạn một cách dễ dàng</p>
                </div>

                <!-- Search Section -->
                <div class="search-section">
                    <div class="search-form">
                        <h3 class="text-center mb-4">Tra cứu đơn hàng</h3>
                        
                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Mã đơn hàng</label>
                                        <input type="number" class="form-control" name="order_id" 
                                               value="<?= $order_id ?>" 
                                               placeholder="Nhập mã đơn hàng" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?= htmlspecialchars($phone) ?>" 
                                               placeholder="Nhập số điện thoại đặt hàng" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="search_order" class="btn-track">
                                <i class="fas fa-search me-2"></i>Tra cứu đơn hàng
                            </button>
                        </form>

                        <div class="security-note">
                            <i class="fas fa-shield-alt me-2"></i>
                            Thông tin đơn hàng được bảo mật an toàn
                        </div>
                    </div>
                </div>

                <!-- Order Information -->
                <?php if ($order): ?>
                <div class="order-info-section">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div class="order-number">
                            <i class="fas fa-receipt me-2"></i>Đơn hàng #<?= $order['id'] ?>
                        </div>
                        <div class="order-status status-<?= $order['status'] ?>">
                            <?= getStatusDescription($order['status']) ?>
                        </div>
                    </div>

                    <!-- Order Information Grid -->
                    <div class="info-grid">
                        <div class="info-card">
                            <h4><i class="fas fa-user"></i>Thông tin khách hàng</h4>
                            <div class="info-item">
                                <span class="info-label">Họ tên:</span>
                                <span class="info-value"><?= htmlspecialchars($order['fullname'] ?? $order['customer_name'] ?? 'N/A') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?= htmlspecialchars($order['email'] ?? $order['customer_email'] ?? 'N/A') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">SĐT:</span>
                                <span class="info-value"><?= htmlspecialchars($order['phone'] ?? 'N/A') ?></span>
                            </div>
                        </div>

                        <div class="info-card">
                            <h4><i class="fas fa-map-marker-alt"></i>Địa chỉ giao hàng</h4>
                            <div class="info-item">
                                <span class="info-value"><?= htmlspecialchars($order['address'] ?? 'N/A') ?></span>
                            </div>
                            <?php if (!empty($order['note'])): ?>
                            <div class="info-item">
                                <span class="info-label">Ghi chú:</span>
                                <span class="info-value"><?= htmlspecialchars($order['note']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="info-card">
                            <h4><i class="fas fa-credit-card"></i>Thông tin thanh toán</h4>
                            <div class="info-item">
                                <span class="info-label">Phương thức:</span>
                                <span class="info-value">
                                    <?= $order['payment_method'] == 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản' ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tổng tiền:</span>
                                <span class="info-value text-success fw-bold"><?= number_format($order['total']) ?>₫</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ngày đặt:</span>
                                <span class="info-value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tracking Timeline -->
                    <h4 class="mb-4"><i class="fas fa-road me-2"></i>Hành trình đơn hàng</h4>
                    <div class="tracking-timeline">
                        <?php foreach ($tracking_history as $index => $step): ?>
                        <div class="timeline-item <?= $step['completed'] ? 'completed' : '' ?>">
                            <div class="timeline-icon <?= $step['completed'] ? 'completed' : ($index === array_key_last($tracking_history) ? 'current' : 'pending') ?>">
                                <i class="<?= $step['icon'] ?>"></i>
                            </div>
                            <div class="timeline-connector"></div>
                            <div class="timeline-content">
                                <div class="timeline-title"><?= $step['title'] ?></div>
                                <div class="timeline-description"><?= $step['description'] ?></div>
                                <?php if (!empty($step['time'])): ?>
                                <div class="timeline-time"><i class="fas fa-clock me-1"></i><?= $step['time'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Items -->
                    <div class="order-items">
                        <h4 class="mb-4"><i class="fas fa-boxes me-2"></i>Sản phẩm đã đặt</h4>
                        <?php foreach ($order_details as $item): ?>
                        <div class="order-item">
                            <img src="assets/images/products/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="item-image"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7huqNwPC90ZXh0Pjwvc3ZnPg=='">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-meta">
                                    Thương hiệu: <?= htmlspecialchars($item['brand'] ?? 'N/A') ?> | 
                                    Size: <?= $item['size'] ?? '-' ?> | 
                                    Số lượng: <?= $item['qty'] ?>
                                </div>
                                <div class="item-price"><?= number_format($item['price']) ?>₫</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Contact Support -->
                    <div class="contact-support">
                        <h5><i class="fas fa-headset me-2"></i>Cần hỗ trợ?</h5>
                        <p>Nếu bạn có bất kỳ câu hỏi nào về đơn hàng, đừng ngần ngại liên hệ với chúng tôi</p>
                        <div class="support-phone">
                            <i class="fas fa-phone me-2"></i>1900 1234
                        </div>
                        <p class="text-muted">Hotline hỗ trợ: 8:00 - 22:00 hàng ngày</p>
                    </div>
                </div>
                <?php elseif ($order_id > 0): ?>
                <!-- Empty State when order not found -->
                <div class="order-info-section">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h4>Không tìm thấy đơn hàng</h4>
                        <p class="text-muted mb-4">Vui lòng kiểm tra lại mã đơn hàng và số điện thoại</p>
                        <a href="tracking.php" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Tra cứu lại
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto format phone number
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.querySelector('input[name="phone"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            }

            // Scroll to order info if order is found
            <?php if ($order): ?>
            setTimeout(() => {
                document.querySelector('.order-info-section').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 500);
            <?php endif; ?>

            // Add animation to timeline
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 200);
            });
        });

        // Print order function
        function printOrder() {
            window.print();
        }
    </script>
</body>
</html>