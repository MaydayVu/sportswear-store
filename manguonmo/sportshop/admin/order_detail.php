<?php
session_start();
include "../config.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Lấy thông tin đơn hàng
$order_sql = "SELECT o.*, u.fullname as customer_name, u.email as customer_email, u.phone as customer_phone 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              WHERE o.id = ?";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Đơn hàng không tồn tại!");
}

// Lấy chi tiết đơn hàng
$details_sql = "SELECT od.*, p.name, p.image, p.brand, ps.size 
                FROM order_details od 
                JOIN products p ON p.id = od.product_id 
                LEFT JOIN product_sizes ps ON ps.id = od.size_id 
                WHERE od.order_id = ?";
$details_stmt = $conn->prepare($details_sql);
$details_stmt->bind_param("i", $order_id);
$details_stmt->execute();
$order_details = $details_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Đơn hàng #<?= $order_id ?> - Admin Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-paid { background: #d1ecf1; color: #0c5460; }
        .badge-shipping { background: #d4edda; color: #155724; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-cancel { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>
    
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-file-invoice me-2"></i>Chi tiết Đơn hàng #<?= $order_id ?></h1>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Sản phẩm đã đặt</h5>
                    </div>
                    <div class="card-body">
                        <?php while ($item = $order_details->fetch_assoc()): ?>
                            <div class="d-flex align-items-center border-bottom pb-3 mb-3">
                                <img src="../assets/images/products/<?= $item['image'] ?>" 
                                     alt="<?= $item['name'] ?>" 
                                     class="rounded me-3" 
                                     width="80" height="80"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7huqNwPC90ZXh0Pjwvc3ZnPg=='">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                    <p class="text-muted mb-1"><?= $item['brand'] ?></p>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Size: <?= $item['size'] ?? '-' ?> | Số lượng: <?= $item['qty'] ?></small>
                                        <strong class="text-primary"><?= number_format($item['price']) ?>₫</strong>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Mã đơn:</strong> #<?= $order_id ?></p>
                        <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                        <p><strong>Trạng thái:</strong> 
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
                            <span class="status-badge <?= $status_classes[$order['status']] ?>">
                                <?= $status_texts[$order['status']] ?>
                            </span>
                        </p>
                        <p><strong>Phương thức:</strong> 
                            <?= $order['payment_method'] == 'cod' ? 'COD' : 'Chuyển khoản' ?>
                        </p>
                        <p><strong>Tổng tiền:</strong> 
                            <span class="text-success fw-bold"><?= number_format($order['total']) ?>₫</span>
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Thông tin khách hàng</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Họ tên:</strong> <?= htmlspecialchars($order['fullname'] ?? $order['customer_name'] ?? 'N/A') ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($order['email'] ?? $order['customer_email'] ?? 'N/A') ?></p>
                        <p><strong>SĐT:</strong> <?= htmlspecialchars($order['phone'] ?? $order['customer_phone'] ?? 'N/A') ?></p>
                        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address'] ?? 'N/A') ?></p>
                        <?php if (!empty($order['note'])): ?>
                            <p><strong>Ghi chú:</strong> <?= htmlspecialchars($order['note']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>