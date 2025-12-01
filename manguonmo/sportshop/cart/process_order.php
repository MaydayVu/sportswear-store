<?php
session_start();
include "../config.php";

// Kiểm tra reCAPTCHA
function verifyRecaptcha($secretKey, $response) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result)->success;
}

// reCAPTCHA secret key (sử dụng test key cho development)
$recaptcha_secret = "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe";
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

// Kiểm tra honeypot field (anti-bot)
if (!empty($_POST['website'])) {
    die('Spam detected!');
}

// Xác thực reCAPTCHA
if (!$recaptcha_response || !verifyRecaptcha($recaptcha_secret, $recaptcha_response)) {
    $_SESSION['error'] = "Vui lòng hoàn thành xác thực bảo mật!";
    header("Location: checkout.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$session_id = session_id();

// Lấy thông tin từ form
$fullname = $conn->real_escape_string($_POST['fullname']);
$phone = $conn->real_escape_string($_POST['phone']);
$email = $conn->real_escape_string($_POST['email']);
$address = $conn->real_escape_string($_POST['address']);
$payment_method = $conn->real_escape_string($_POST['payment_method']);
$note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';

// Validate input
if (empty($fullname) || empty($phone) || empty($email) || empty($address)) {
    $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    header("Location: checkout.php");
    exit;
}

// Validate phone number
if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
    $_SESSION['error'] = "Số điện thoại không hợp lệ!";
    header("Location: checkout.php");
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Email không hợp lệ!";
    header("Location: checkout.php");
    exit;
}

// Lấy giỏ hàng với thông tin size từ product_sizes
$stmt = $conn->prepare("
    SELECT c.id as cart_id, c.qty, c.price, c.size_id,
           p.id as product_id, p.name, p.image,
           ps.size
    FROM carts c
    JOIN products p ON p.id = c.product_id
    LEFT JOIN product_sizes ps ON ps.id = c.size_id
    WHERE c.session_id = ?
");
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    $_SESSION['error'] = "Giỏ hàng của bạn đang trống!";
    header("Location: cart.php");
    exit;
}

// Kiểm tra tồn kho trước khi đặt hàng
foreach ($cart_items as $item) {
    $stock_check_sql = "SELECT quantity FROM product_sizes WHERE id = ?";
    $stock_stmt = $conn->prepare($stock_check_sql);
    $stock_stmt->bind_param("i", $item['size_id']);
    $stock_stmt->execute();
    $stock_result = $stock_stmt->get_result()->fetch_assoc();
    
    if (!$stock_result || $stock_result['quantity'] < $item['qty']) {
        $_SESSION['error'] = "Sản phẩm " . $item['name'] . " không đủ số lượng trong kho!";
        header("Location: cart.php");
        exit;
    }
}

// Tính tổng tiền
$total = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['qty'];
    $total_items += $item['qty'];
}

$shipping_fee = $total >= 500000 ? 0 : 30000;
$final_total = $total + $shipping_fee;

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Tạo đơn hàng với thông tin giao hàng
    $order_sql = "INSERT INTO orders (user_id, total, payment_method, status, fullname, phone, email, address, note, created_at) 
                  VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW())";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("idssssss", $user_id, $final_total, $payment_method, $fullname, $phone, $email, $address, $note);
    $order_stmt->execute();
    $order_id = $conn->insert_id;

    // Thêm chi tiết đơn hàng với size_id
    $detail_sql = "INSERT INTO order_details (order_id, product_id, price, qty, size, size_id) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $detail_stmt = $conn->prepare($detail_sql);

    foreach ($cart_items as $item) {
        $detail_stmt->bind_param("iidisi", $order_id, $item['product_id'], $item['price'], $item['qty'], $item['size'], $item['size_id']);
        $detail_stmt->execute();
        
        // Cập nhật số lượng tồn kho
        $update_stock_sql = "UPDATE product_sizes SET quantity = quantity - ? WHERE id = ? AND quantity >= ?";
        $update_stmt = $conn->prepare($update_stock_sql);
        $update_stmt->bind_param("iii", $item['qty'], $item['size_id'], $item['qty']);
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows === 0) {
            throw new Exception("Không đủ số lượng cho sản phẩm: " . $item['name']);
        }
    }

    // Xóa giỏ hàng
    $delete_cart_sql = "DELETE FROM carts WHERE session_id = ?";
    $delete_stmt = $conn->prepare($delete_cart_sql);
    $delete_stmt->bind_param("s", $session_id);
    $delete_stmt->execute();

    // Commit transaction
    $conn->commit();

    // Gửi email xác nhận (có thể thêm sau)
    // sendOrderConfirmationEmail($email, $order_id, $fullname, $final_total);

} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollback();
    $_SESSION['error'] = "Có lỗi xảy ra khi đặt hàng: " . $e->getMessage();
    header("Location: checkout.php");
    exit;
}

// Tạo mã QR thanh toán (mô phỏng)
function generateQRData($order_id, $amount) {
    $qr_data = [
        'order_id' => $order_id,
        'amount' => $amount,
        'merchant' => 'Sport Fashion',
        'account' => 'SPORTFASHION_' . $order_id,
        'timestamp' => time()
    ];
    return base64_encode(json_encode($qr_data));
}

$qr_data = generateQRData($order_id, $final_total);
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
    
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #fff;
            --accent-color: #e4002b;
            --success-color: #27ae60;
            --warning-color: #f39c12;
        }

        .payment-page {
            padding: 40px 0;
            background: #f8f9fa;
            min-height: 80vh;
        }

        .payment-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .payment-card {
            background: var(--secondary-color);
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .order-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .order-number {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 600;
            color: var(--primary-color);
        }

        .amount-display {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin: 30px 0;
        }

        .amount-label {
            font-size: 1.1rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .amount-value {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .qr-section {
            text-align: center;
            padding: 30px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            margin: 30px 0;
            background: var(--secondary-color);
        }

        .qr-code {
            width: 200px;
            height: 200px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #333;
        }

        .qr-instructions {
            text-align: left;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .instruction-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .step-number {
            background: var(--warning-color);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .countdown {
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--accent-color);
            margin: 20px 0;
        }

        .success-message {
            text-align: center;
            padding: 40px;
            background: var(--secondary-color);
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            margin: 30px 0;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 20px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-primary-custom {
            background: var(--primary-color);
            color: var(--secondary-color);
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary-custom:hover {
            background: #333;
            color: var(--secondary-color);
        }

        .btn-secondary-custom {
            background: #6c757d;
            color: var(--secondary-color);
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary-custom:hover {
            background: #5a6268;
            color: var(--secondary-color);
        }

        .timer {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
            margin: 10px 0;
        }

        .security-badge {
            background: var(--success-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin: 10px 0;
        }

        @media (max-width: 768px) {
            .payment-header h1 {
                font-size: 2rem;
            }
            
            .amount-value {
                font-size: 2rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "../includes/header.php"; ?>

    <div class="payment-page">
        <div class="container">
            <div class="payment-container">
                <?php if ($payment_method === 'cod'): ?>
                    <!-- Thanh toán khi nhận hàng -->
                    <div class="payment-card">
                        <div class="payment-header">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h1>Đặt hàng thành công!</h1>
                            <p class="text-muted">Cảm ơn bạn đã đặt hàng tại Sport Fashion</p>
                            <div class="security-badge">
                                <i class="fas fa-shield-alt"></i>
                                Đã xác thực bảo mật
                            </div>
                        </div>

                        <div class="order-info">
                            <div class="order-number">
                                <i class="fas fa-receipt me-2"></i>Mã đơn hàng: #<?= $order_id ?>
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Họ và tên</span>
                                    <span class="info-value"><?= htmlspecialchars($fullname) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Số điện thoại</span>
                                    <span class="info-value"><?= htmlspecialchars($phone) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?= htmlspecialchars($email) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Địa chỉ giao hàng</span>
                                    <span class="info-value"><?= htmlspecialchars($address) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phương thức</span>
                                    <span class="info-value">Thanh toán khi nhận hàng</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Trạng thái</span>
                                    <span class="info-value text-success">Chờ xác nhận</span>
                                </div>
                            </div>

                            <div class="amount-display">
                                <div class="amount-label">Tổng thanh toán</div>
                                <div class="amount-value"><?= number_format($final_total) ?>₫</div>
                                <div class="text-white mt-2">
                                    <i class="fas fa-money-bill-wave me-2"></i>Thanh toán khi nhận hàng
                                </div>
                            </div>
                        </div>

                        <div class="qr-instructions">
                            <h5><i class="fas fa-info-circle me-2"></i>Thông tin đơn hàng</h5>
                            <div class="instruction-step">
                                <div class="step-number">1</div>
                                <div>Đơn hàng của bạn đã được xác nhận và đang được xử lý</div>
                            </div>
                            <div class="instruction-step">
                                <div class="step-number">2</div>
                                <div>Nhân viên sẽ liên hệ với bạn trong vòng 24h để xác nhận đơn hàng</div>
                            </div>
                            <div class="instruction-step">
                                <div class="step-number">3</div>
                                <div>Bạn sẽ thanh toán khi nhận được hàng</div>
                            </div>
                            <div class="instruction-step">
                                <div class="step-number">4</div>
                                <div>Thời gian giao hàng dự kiến: 2-5 ngày làm việc</div>
                            </div>
                        </div>

                        <div class="btn-group">
                            <a href="../index.php" class="btn-secondary-custom">
                                <i class="fas fa-home me-2"></i>Về trang chủ
                            </a>
                            <a href="../order_tracking.php?order_id=<?= $order_id ?>" class="btn-primary-custom">
                                <i class="fas fa-truck me-2"></i>Theo dõi đơn hàng
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Thanh toán QR/Ví điện tử -->
                    <div class="payment-card">
                        <div class="payment-header">
                            <h1><i class="fas fa-qrcode me-2"></i>Thanh toán qua QR Code</h1>
                            <p class="text-muted">Quét mã QR để hoàn tất thanh toán</p>
                            <div class="security-badge">
                                <i class="fas fa-shield-alt"></i>
                                Đã xác thực bảo mật
                            </div>
                        </div>

                        <div class="order-info">
                            <div class="order-number">
                                <i class="fas fa-receipt me-2"></i>Mã đơn hàng: #<?= $order_id ?>
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Phương thức</span>
                                    <span class="info-value">
                                        <?php
                                        $method_names = [
                                            'momo' => 'Ví MoMo',
                                            'bank' => 'Chuyển khoản ngân hàng'
                                        ];
                                        echo $method_names[$payment_method] ?? 'QR Code';
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Thời hạn</span>
                                    <span class="info-value">15 phút</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Trạng thái</span>
                                    <span class="info-value text-warning">Chờ thanh toán</span>
                                </div>
                            </div>

                            <div class="amount-display">
                                <div class="amount-label">Số tiền thanh toán</div>
                                <div class="amount-value"><?= number_format($final_total) ?>₫</div>
                            </div>
                        </div>

                        <div class="qr-section">
                            <div class="qr-code">
                                <?php if ($payment_method === 'momo'): ?>
                                    <i class="fab fa-cc-paypal"></i>
                                <?php elseif ($payment_method === 'bank'): ?>
                                    <i class="fas fa-university"></i>
                                <?php else: ?>
                                    <i class="fas fa-qrcode"></i>
                                <?php endif; ?>
                            </div>
                            <h5>Quét mã QR để thanh toán</h5>
                            <p class="text-muted">Sử dụng app ngân hàng hoặc ví điện tử để quét mã</p>
                            
                            <div class="countdown">
                                <div>Thời gian còn lại:</div>
                                <div class="timer" id="countdownTimer">15:00</div>
                            </div>
                        </div>

                        <div class="qr-instructions">
                            <h5><i class="fas fa-mobile-alt me-2"></i>Hướng dẫn thanh toán</h5>
                            <div class="instruction-step">
                                <div class="step-number">1</div>
                                <div>Mở ứng dụng ngân hàng hoặc ví điện tử trên điện thoại</div>
                            </div>
                            <div class="instruction-step">
                                <div class="step-number">2</div>
                                <div>Chọn tính năng "Quét mã QR" trong ứng dụng</div>
                            </div>
                            <div class="instruction-step">
                                <div class="step-number">3</div>
                                <div>Quét mã QR code ở trên và xác nhận thanh toán</div>
                            </div>
                            <div class="instruction-step">
                                <div class="step-number">4</div>
                                <div>Hệ thống sẽ tự động xác nhận khi thanh toán thành công</div>
                            </div>
                        </div>

                        <!-- Mô phỏng thanh toán thành công (cho demo) -->
                        <div class="text-center mt-4">
                            <button onclick="simulatePaymentSuccess()" class="btn-primary-custom">
                                <i class="fas fa-check me-2"></i>Mô phỏng thanh toán thành công
                            </button>
                        </div>

                        <div id="successMessage" style="display: none;">
                            <div class="success-message">
                                <div class="success-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h2>Thanh toán thành công!</h2>
                                <p class="text-muted">Cảm ơn bạn đã mua hàng tại Sport Fashion</p>
                                <div class="btn-group">
                                    <a href="../index.php" class="btn-secondary-custom">
                                        <i class="fas fa-home me-2"></i>Về trang chủ
                                    </a>
                                    <a href="../order_tracking.php?order_id=<?= $order_id ?>" class="btn-primary-custom">
                                        <i class="fas fa-truck me-2"></i>Theo dõi đơn hàng
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer for QR payment
        function startCountdown(duration) {
            let timer = duration, minutes, seconds;
            const countdownElement = document.getElementById('countdownTimer');
            
            const interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                countdownElement.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    countdownElement.textContent = "Hết hạn";
                    countdownElement.style.color = "var(--accent-color)";
                    alert('Mã QR đã hết hạn. Vui lòng tạo đơn hàng mới.');
                }
            }, 1000);
        }

        // Start 15-minute countdown for QR payments
        <?php if ($payment_method !== 'cod'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            startCountdown(15 * 60); // 15 minutes
        });
        <?php endif; ?>

        // Simulate successful payment (for demo purposes)
        function simulatePaymentSuccess() {
            // Update order status in database
            fetch('update_payment_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=<?= $order_id ?>&status=paid'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('successMessage').style.display = 'block';
                    document.querySelector('.qr-section').style.opacity = '0.5';
                    document.querySelector('.btn-primary-custom').style.display = 'none';
                    
                    // Scroll to success message
                    document.getElementById('successMessage').scrollIntoView({ 
                        behavior: 'smooth' 
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái thanh toán.');
            });
        }

        // Check payment status periodically
        function checkPaymentStatus() {
            fetch('check_payment.php?order_id=<?= $order_id ?>')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'paid') {
                    document.getElementById('successMessage').style.display = 'block';
                    document.querySelector('.qr-section').style.opacity = '0.5';
                }
            });
        }

        // Check payment status every 10 seconds (for demo)
        <?php if ($payment_method !== 'cod'): ?>
        setInterval(checkPaymentStatus, 10000);
        <?php endif; ?>
    </script>
</body>
</html>