<?php
session_start();
include "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php?redirect=checkout");
    exit;
}

$user_id = $_SESSION['user_id'];
$session_id = session_id();

// Lấy thông tin user
$user_stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();

// Lấy giỏ hàng
$stmt = $conn->prepare("
    SELECT c.id as cid, c.qty, c.price, 
           p.name, p.image, p.id as pid,
           ps.size
    FROM carts c
    JOIN products p ON p.id = c.product_id
    LEFT JOIN product_sizes ps ON ps.id = c.size_id
    WHERE c.session_id = ?
");
$stmt->bind_param("s", $session_id);
$stmt->execute();
$res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);

if (empty($items)) {
    header("Location: cart.php");
    exit;
}

// Tính tổng tiền
$total = 0;
$total_items = 0;
foreach ($items as $it) {
    $total += $it['price'] * $it['qty'];
    $total_items += $it['qty'];
}

// Phí vận chuyển
$shipping_fee = $total >= 500000 ? 0 : 30000;
$final_total = $total + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- reCAPTCHA API -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #fff;
            --accent-color: #e4002b;
            --gray-light: #f5f5f5;
            --gray-medium: #767676;
            --success-color: #27ae60;
        }

        .checkout-page {
            padding: 40px 0;
            background: #f8f9fa;
            min-height: 80vh;
        }

        .checkout-header {
            margin-bottom: 40px;
        }

        .checkout-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .checkout-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .step.active .step-number {
            background: var(--primary-color);
            color: var(--secondary-color);
        }

        .step.completed .step-number {
            background: var(--success-color);
            color: var(--secondary-color);
        }

        .step-line {
            position: absolute;
            top: 20px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: #ddd;
            z-index: 1;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            align-items: start;
        }

        /* Checkout Form */
        .checkout-form {
            background: var(--secondary-color);
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--primary-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
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
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.1);
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--secondary-color);
        }

        .payment-method:hover {
            border-color: var(--primary-color);
        }

        .payment-method.selected {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }

        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .payment-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Order Summary */
        .order-summary {
            background: var(--secondary-color);
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 30px;
            position: sticky;
            top: 100px;
        }

        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
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

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 0;
        }

        .summary-label {
            color: var(--gray-medium);
        }

        .summary-value {
            font-weight: 600;
        }

        .summary-total {
            border-top: 2px solid #eee;
            padding-top: 20px;
            margin-top: 15px;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .shipping-note {
            background: #e8f5e8;
            color: #2d5016;
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 0.9rem;
            text-align: center;
        }

        .free-shipping {
            color: var(--success-color);
            font-weight: 600;
        }

        .btn-checkout {
            width: 100%;
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-checkout:hover {
            background: #333;
        }

        .btn-checkout:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* reCAPTCHA Section */
        .recaptcha-section {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .recaptcha-note {
            font-size: 0.9rem;
            color: var(--gray-medium);
            margin-bottom: 15px;
        }

        .g-recaptcha {
            display: inline-block;
            margin: 0 auto;
        }

        .recaptcha-error {
            color: var(--accent-color);
            font-size: 0.9rem;
            margin-top: 10px;
            display: none;
        }

        /* Security Section */
        .security-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .security-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .security-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .security-icon {
            color: var(--success-color);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .checkout-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .order-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .checkout-page {
                padding: 20px 0;
            }
            
            .checkout-header h1 {
                font-size: 2rem;
            }
            
            .checkout-steps {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            
            .step-line {
                display: none;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }

            .security-features {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .checkout-form, .order-summary {
                padding: 20px;
            }
            
            .checkout-header h1 {
                font-size: 1.8rem;
            }
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .human-verification {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }

        .verification-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include "../includes/header.php"; ?>

    <div class="checkout-page">
        <div class="container">
            <div class="checkout-header text-center">
                <h1><i class="fas fa-credit-card me-2"></i>Thanh toán</h1>
                <p class="text-muted">Hoàn tất đơn hàng của bạn</p>
            </div>

            <!-- Checkout Steps -->
            <div class="checkout-steps">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <div class="step-label">Giỏ hàng</div>
                </div>
                <div class="step-line"></div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-label">Thanh toán</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Hoàn tất</div>
                </div>
            </div>

            <div class="checkout-container">
                <!-- Checkout Form -->
                <div class="checkout-form">
                    <h2 class="section-title"><i class="fas fa-shipping-fast me-2"></i>Thông tin giao hàng</h2>
                    
                    <form id="checkoutForm" action="process_order.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Họ và tên *</label>
                            <input type="text" class="form-control" name="fullname" 
                                   value="<?= htmlspecialchars($user_info['fullname'] ?? '') ?>" 
                                   required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Số điện thoại *</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           pattern="[0-9]{10,11}" 
                                           title="Số điện thoại phải có 10-11 chữ số"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" 
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Địa chỉ giao hàng *</label>
                            <textarea class="form-control" name="address" rows="3" 
                                      placeholder="Số nhà, đường, phường/xã, quận/huyện, thành phố" required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ghi chú đơn hàng</label>
                            <textarea class="form-control" name="note" rows="2" 
                                      placeholder="Ghi chú về đơn hàng (tùy chọn)"></textarea>
                        </div>

                        <h2 class="section-title mt-4"><i class="fas fa-money-bill-wave me-2"></i>Phương thức thanh toán</h2>
                        
                        <div class="payment-methods">
                            <div class="payment-method selected" data-method="cod">
                                <div class="payment-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="payment-name">Thanh toán khi nhận hàng</div>
                            </div>
                            
                            <div class="payment-method" data-method="bank">
                                <div class="payment-icon">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="payment-name">Chuyển khoản ngân hàng</div>
                            </div>
                            
                            <div class="payment-method" data-method="momo">
                                <div class="payment-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="payment-name">Ví MoMo</div>
                            </div>
                        </div>
                        <input type="hidden" name="payment_method" id="paymentMethod" value="cod" required>

                        <!-- Human Verification Section -->
                        <div class="human-verification">
                            <div class="verification-title">
                                <i class="fas fa-user-shield me-2"></i>
                                Xác thực bảo mật
                            </div>
                            <p class="mb-3">Vui lòng xác nhận bạn không phải là robot</p>
                            
                            <div class="recaptcha-section">
                                <div class="recaptcha-note">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Click vào ô bên dưới để xác thực
                                </div>
                                <div class="g-recaptcha" 
                                     data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI" 
                                     data-callback="onRecaptchaSuccess"
                                     data-expired-callback="onRecaptchaExpired"
                                     data-error-callback="onRecaptchaError">
                                </div>
                                <div class="recaptcha-error" id="recaptchaError">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Vui lòng hoàn thành xác thực reCAPTCHA
                                </div>
                            </div>
                        </div>

                        <!-- Security Features -->
                        <div class="security-section">
                            <strong><i class="fas fa-shield-alt me-2"></i>Bảo mật đảm bảo</strong>
                            <div class="security-features">
                                <div class="security-feature">
                                    <i class="fas fa-lock security-icon"></i>
                                    <span>Mã hóa SSL 256-bit</span>
                                </div>
                                <div class="security-feature">
                                    <i class="fas fa-shield-check security-icon"></i>
                                    <span>Bảo vệ chống gian lận</span>
                                </div>
                                <div class="security-feature">
                                    <i class="fas fa-user-check security-icon"></i>
                                    <span>Xác thực 2 bước</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-checkout" id="submitBtn" disabled>
                            <i class="fas fa-lock me-2"></i>
                            Đặt hàng & Thanh toán
                        </button>
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h2 class="section-title"><i class="fas fa-receipt me-2"></i>Đơn hàng của bạn</h2>
                    
                    <div class="order-items">
                        <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <img src="../assets/images/products/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="item-image"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7huqNwPC90ZXh0Pjwvc3ZnPg=='">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-meta">
                                    Size: <?= $item['size'] ?: '-' ?> | Số lượng: <?= $item['qty'] ?>
                                </div>
                                <div class="item-price"><?= number_format($item['price']) ?>₫</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-section">
                        <div class="summary-row">
                            <span class="summary-label">Tổng tiền hàng (<?= $total_items ?> sản phẩm)</span>
                            <span class="summary-value"><?= number_format($total) ?>₫</span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Phí vận chuyển</span>
                            <span class="summary-value">
                                <?php if ($shipping_fee == 0): ?>
                                    <span class="free-shipping">Miễn phí</span>
                                <?php else: ?>
                                    <?= number_format($shipping_fee) ?>₫
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="summary-row summary-total">
                            <span>Tổng thanh toán</span>
                            <span><?= number_format($final_total) ?>₫</span>
                        </div>

                        <?php if ($shipping_fee == 0): ?>
                            <div class="shipping-note">
                                <i class="fas fa-check-circle me-2"></i>
                                Bạn được miễn phí vận chuyển
                            </div>
                        <?php else: ?>
                            <div class="shipping-note">
                                <i class="fas fa-info-circle me-2"></i>
                                Thêm <?= number_format(500000 - $total) ?>₫ để được miễn phí vận chuyển
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // reCAPTCHA callbacks
        function onRecaptchaSuccess(token) {
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('recaptchaError').style.display = 'none';
            console.log('reCAPTCHA verified successfully');
        }

        function onRecaptchaExpired() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('recaptchaError').style.display = 'block';
            document.getElementById('recaptchaError').textContent = 'Xác thực đã hết hạn. Vui lòng thử lại.';
        }

        function onRecaptchaError() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('recaptchaError').style.display = 'block';
            document.getElementById('recaptchaError').textContent = 'Lỗi xác thực. Vui lòng thử lại.';
        }

        // Payment method selection
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('.payment-method');
            const paymentMethodInput = document.getElementById('paymentMethod');
            
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    // Remove selected class from all methods
                    paymentMethods.forEach(m => m.classList.remove('selected'));
                    // Add selected class to clicked method
                    this.classList.add('selected');
                    // Update hidden input value
                    paymentMethodInput.value = this.dataset.method;
                });
            });

            // Form validation
            const form = document.getElementById('checkoutForm');
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;
                
                // Check required fields
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.style.borderColor = 'var(--accent-color)';
                    } else {
                        field.style.borderColor = '';
                    }
                });

                // Check reCAPTCHA
                const recaptchaResponse = grecaptcha.getResponse();
                if (!recaptchaResponse) {
                    valid = false;
                    document.getElementById('recaptchaError').style.display = 'block';
                    document.getElementById('recaptchaError').textContent = 'Vui lòng hoàn thành xác thực reCAPTCHA';
                }

                if (!valid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = form.querySelector('[style*="border-color"]') || 
                                     document.getElementById('recaptchaError');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    alert('Vui lòng kiểm tra lại thông tin và hoàn thành xác thực bảo mật!');
                } else {
                    // Show loading state
                    document.getElementById('submitBtn').disabled = true;
                    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
                    form.classList.add('loading');
                }
            });

            // Auto-fill address suggestion
            const addressField = document.querySelector('textarea[name="address"]');
            if (addressField && !addressField.value) {
                addressField.placeholder = 'VD: 123 Đường ABC, Phường XYZ, Quận 1, TP.HCM';
            }

            // Phone number validation
            const phoneField = document.querySelector('input[name="phone"]');
            if (phoneField) {
                phoneField.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length > 11) {
                        this.value = this.value.slice(0, 11);
                    }
                });
            }

            // Add anti-bot honeypot field
            const honeypot = document.createElement('input');
            honeypot.type = 'text';
            honeypot.name = 'website';
            honeypot.style.display = 'none';
            honeypot.className = 'hp-input';
            form.appendChild(honeypot);
        });

        // Additional anti-bot measures
        window.addEventListener('load', function() {
            // Detect rapid form submission
            let lastSubmitTime = 0;
            const form = document.getElementById('checkoutForm');
            
            form.addEventListener('submit', function() {
                const currentTime = new Date().getTime();
                if (currentTime - lastSubmitTime < 3000) { // 3 seconds minimum
                    alert('Vui lòng chờ một chút trước khi gửi lại!');
                    return false;
                }
                lastSubmitTime = currentTime;
            });

            // Check for automation tools
            if (window.phantom || window._phantom || window.callPhantom) {
                alert('Trình duyệt không được hỗ trợ!');
                window.location.href = '/';
            }
        });
    </script>
</body>
</html>