<?php
session_start();
include "config.php";

// Kiểm tra nếu giỏ hàng trống
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Lấy thông tin user nếu đã đăng nhập
$user = null;
if (isset($_SESSION['user_id'])) {
    $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
    $user_stmt->close();
}

// Xử lý thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $note = trim($_POST['note']);
    $payment_method = $_POST['payment_method'];
    
    // Validate dữ liệu
    $errors = [];
    
    if (empty($fullname)) {
        $errors[] = "Vui lòng nhập họ tên";
    }
    
    if (empty($phone)) {
        $errors[] = "Vui lòng nhập số điện thoại";
    } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = "Số điện thoại không hợp lệ";
    }
    
    if (empty($email)) {
        $errors[] = "Vui lòng nhập email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    
    if (empty($address)) {
        $errors[] = "Vui lòng nhập địa chỉ giao hàng";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Vui lòng chọn phương thức thanh toán";
    }
    
    // Kiểm tra tồn kho
    $out_of_stock_items = [];
    foreach ($_SESSION['cart'] as $item) {
        $size_stmt = $conn->prepare("SELECT quantity FROM product_sizes WHERE id = ?");
        $size_stmt->bind_param("i", $item['size_id']);
        $size_stmt->execute();
        $size_result = $size_stmt->get_result();
        $size_data = $size_result->fetch_assoc();
        $size_stmt->close();
        
        if ($size_data['quantity'] < $item['qty']) {
            $product_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
            $product_stmt->bind_param("i", $item['product_id']);
            $product_stmt->execute();
            $product_result = $product_stmt->get_result();
            $product_data = $product_result->fetch_assoc();
            $product_stmt->close();
            
            $out_of_stock_items[] = $product_data['name'];
        }
    }
    
    if (!empty($out_of_stock_items)) {
        $errors[] = "Một số sản phẩm đã hết hàng: " . implode(', ', $out_of_stock_items);
    }
    
    // Nếu không có lỗi, tiến hành tạo đơn hàng
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Tính tổng tiền
            $total = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['qty'];
            }
            
            // Tạo đơn hàng
            $order_stmt = $conn->prepare("
                INSERT INTO orders (user_id, total, payment_method, fullname, phone, email, address, note) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $user_id = $user ? $user['id'] : NULL;
            $order_stmt->bind_param("idssssss", $user_id, $total, $payment_method, $fullname, $phone, $email, $address, $note);
            $order_stmt->execute();
            $order_id = $conn->insert_id;
            $order_stmt->close();
            
            // Thêm chi tiết đơn hàng
            $detail_stmt = $conn->prepare("
                INSERT INTO order_details (order_id, product_id, size_id, price, qty) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($_SESSION['cart'] as $item) {
                $detail_stmt->bind_param("iiidi", $order_id, $item['product_id'], $item['size_id'], $item['price'], $item['qty']);
                $detail_stmt->execute();
                
                // Cập nhật tồn kho
                $update_stmt = $conn->prepare("
                    UPDATE product_sizes 
                    SET quantity = quantity - ? 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("ii", $item['qty'], $item['size_id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            $detail_stmt->close();
            
            $conn->commit();
            
            // Xóa giỏ hàng
            unset($_SESSION['cart']);
            
            // Chuyển hướng đến trang cảm ơn
            $_SESSION['order_id'] = $order_id;
            header("Location: order_success.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Có lỗi xảy ra khi đặt hàng. Vui lòng thử lại.";
        }
    }
}

// Lấy thông tin giỏ hàng chi tiết
$cart_items = [];
$subtotal = 0;
$total = 0;
$shipping_fee = 0;

foreach ($_SESSION['cart'] as $item) {
    $stmt = $conn->prepare("
        SELECT p.id, p.name, p.image, p.price, p.discount_percent, ps.size, c.name as category_name
        FROM products p 
        JOIN product_sizes ps ON p.id = ps.product_id 
        JOIN categories c ON p.category_id = c.id
        WHERE ps.id = ?
    ");
    $stmt->bind_param("i", $item['size_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($product = $result->fetch_assoc()) {
        $current_price = $product['price'];
        if ($product['discount_percent'] > 0) {
            $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
        }
        
        $item_total = $current_price * $item['qty'];
        $subtotal += $item_total;
        
        $cart_items[] = [
            'product_id' => $product['id'],
            'name' => $product['name'],
            'image' => $product['image'],
            'size' => $product['size'],
            'price' => $current_price,
            'qty' => $item['qty'],
            'total' => $item_total,
            'category_name' => $product['category_name']
        ];
    }
    $stmt->close();
}

// Tính phí vận chuyển (miễn phí cho đơn > 500,000đ)
if ($subtotal >= 500000) {
    $shipping_fee = 0;
} else {
    $shipping_fee = 30000;
}

$total = $subtotal + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Sport Fashion</title>
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

        .payment-page {
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

        .checkout-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .checkout-header {
            background: var(--primary-color);
            color: white;
            padding: 20px 30px;
        }

        .checkout-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .checkout-body {
            padding: 30px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(228, 0, 43, 0.1);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-method {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: var(--accent-color);
        }

        .payment-method.selected {
            border-color: var(--accent-color);
            background: rgba(228, 0, 43, 0.05);
        }

        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .order-summary {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 25px;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 15px;
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
            font-size: 0.9rem;
            color: #666;
        }

        .item-price {
            font-weight: 700;
            color: var(--accent-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .free-shipping {
            color: #28a745;
            font-weight: 600;
        }

        .btn-checkout {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-checkout:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
        }

        .login-prompt {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .checkout-body {
                padding: 20px;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="payment-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">Thanh toán</h1>
                <p class="lead mb-0">Hoàn tất đơn hàng của bạn</p>
            </div>
        </div>

        <div class="container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Có lỗi xảy ra:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$user): ?>
                <div class="login-prompt">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><i class="fas fa-info-circle me-2"></i>Bạn đã có tài khoản?</strong>
                            <p class="mb-0">Đăng nhập để tích lũy điểm và theo dõi đơn hàng dễ dàng hơn.</p>
                        </div>
                        <a href="auth/login.php?redirect=payment" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" id="paymentForm">
                <div class="row">
                    <!-- Thông tin giao hàng & Thanh toán -->
                    <div class="col-lg-8">
                        <div class="checkout-container mb-4">
                            <div class="checkout-header">
                                <h2><i class="fas fa-shipping-fast me-2"></i>Thông tin giao hàng</h2>
                            </div>
                            <div class="checkout-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="fullname" 
                                                   value="<?= $user ? htmlspecialchars($user['fullname']) : (isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '') ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?= $user ? htmlspecialchars($user['phone']) : (isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '') ?>" 
                                                   required pattern="[0-9]{10,11}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?= $user ? htmlspecialchars($user['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '') ?>" 
                                           required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="address" rows="3" required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Ghi chú (tùy chọn)</label>
                                    <textarea class="form-control" name="note" rows="2" placeholder="Ghi chú về đơn hàng..."><?= isset($_POST['note']) ? htmlspecialchars($_POST['note']) : '' ?></textarea>
                                </div>

                                <h3 class="section-title mt-5">Phương thức thanh toán</h3>
                                
                                <div class="payment-methods">
                                    <div class="payment-method" onclick="selectPaymentMethod('cod')">
                                        <div class="payment-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" value="cod" id="cod" required>
                                            <label class="form-check-label fw-bold" for="cod">
                                                Thanh toán khi nhận hàng
                                            </label>
                                        </div>
                                        <small class="text-muted">(COD)</small>
                                    </div>

                                    <div class="payment-method" onclick="selectPaymentMethod('banking')">
                                        <div class="payment-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" value="banking" id="banking">
                                            <label class="form-check-label fw-bold" for="banking">
                                                Chuyển khoản ngân hàng
                                            </label>
                                        </div>
                                        <small class="text-muted">(Internet Banking)</small>
                                    </div>

                                    <div class="payment-method" onclick="selectPaymentMethod('momo')">
                                        <div class="payment-icon">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" value="momo" id="momo">
                                            <label class="form-check-label fw-bold" for="momo">
                                                Ví điện tử MoMo
                                            </label>
                                        </div>
                                        <small class="text-muted">(QR Code)</small>
                                    </div>
                                </div>

                                <!-- Thông tin chuyển khoản (hiển thị khi chọn) -->
                                <div id="bankingInfo" class="alert alert-info" style="display: none;">
                                    <h6><i class="fas fa-info-circle me-2"></i>Thông tin chuyển khoản:</h6>
                                    <p class="mb-1"><strong>Ngân hàng:</strong> Techcombank</p>
                                    <p class="mb-1"><strong>Số tài khoản:</strong> 1903 6666 8888</p>
                                    <p class="mb-1"><strong>Chủ tài khoản:</strong> SPORT FASHION COMPANY</p>
                                    <p class="mb-0"><strong>Nội dung:</strong> [Mã đơn hàng] - [Số điện thoại]</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tóm tắt đơn hàng -->
                    <div class="col-lg-4">
                        <div class="checkout-container sticky-top" style="top: 100px;">
                            <div class="checkout-header">
                                <h2><i class="fas fa-receipt me-2"></i>Đơn hàng của bạn</h2>
                            </div>
                            <div class="checkout-body">
                                <div class="order-summary">
                                    <?php foreach ($cart_items as $item): ?>
                                    <div class="order-item">
                                        <img src="assets/images/products/<?= htmlspecialchars($item['image']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>" 
                                             class="item-image"
                                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7EkOG6o2ggc+G6o24gcGjhuqdtPC90ZXh0Pjwvc3ZnPg=='">
                                        <div class="item-details">
                                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="item-meta">
                                                Size: <?= htmlspecialchars($item['size']) ?> | 
                                                Số lượng: <?= $item['qty'] ?>
                                            </div>
                                        </div>
                                        <div class="item-price"><?= number_format($item['total']) ?>₫</div>
                                    </div>
                                    <?php endforeach; ?>

                                    <div class="summary-row">
                                        <span>Tạm tính:</span>
                                        <span><?= number_format($subtotal) ?>₫</span>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <span>Phí vận chuyển:</span>
                                        <span class="<?= $shipping_fee == 0 ? 'free-shipping' : '' ?>">
                                            <?= $shipping_fee == 0 ? 'MIỄN PHÍ' : number_format($shipping_fee) . '₫' ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($shipping_fee == 0): ?>
                                        <div class="summary-row">
                                            <span class="free-shipping">
                                                <i class="fas fa-check-circle me-1"></i>Đã miễn phí vận chuyển
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="summary-row">
                                        <span>Tổng cộng:</span>
                                        <span><?= number_format($total) ?>₫</span>
                                    </div>
                                </div>

                                <button type="submit" name="place_order" class="btn-checkout mt-4">
                                    <i class="fas fa-lock me-2"></i>ĐẶT HÀNG NGAY
                                </button>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Thông tin của bạn được bảo mật an toàn
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPaymentMethod(method) {
            // Bỏ chọn tất cả
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Chọn phương thức được click
            const selectedElement = event.currentTarget;
            selectedElement.classList.add('selected');
            
            // Check radio button tương ứng
            const radio = selectedElement.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Hiển thị thông tin chuyển khoản nếu cần
            const bankingInfo = document.getElementById('bankingInfo');
            if (method === 'banking') {
                bankingInfo.style.display = 'block';
            } else {
                bankingInfo.style.display = 'none';
            }
        }

        // Tự động chọn COD mặc định
        document.addEventListener('DOMContentLoaded', function() {
            const codMethod = document.querySelector('.payment-method');
            if (codMethod) {
                selectPaymentMethod.call(codMethod, 'cod');
            }
        });

        // Validate form trước khi submit
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Vui lòng chọn phương thức thanh toán');
                return false;
            }
        });
    </script>
</body>
</html>