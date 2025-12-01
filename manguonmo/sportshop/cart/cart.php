<?php
session_start();
include "../config.php";

$session_id = session_id();
$user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;

/* Lấy giỏ hàng theo session_id */
$stmt = $conn->prepare("
    SELECT c.id AS cart_id, c.qty, c.price,
           p.id AS product_id, p.name, p.image,
           ps.id AS size_id, ps.size, ps.quantity as stock
    FROM carts c
    JOIN products p ON p.id = c.product_id
    LEFT JOIN product_sizes ps ON ps.id = c.size_id
    WHERE c.session_id = ?
");
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

/* Tính tổng */
$total = 0;
$total_items = 0;
foreach ($cart_items as $it) {
    $total += $it["price"] * $it["qty"];
    $total_items += $it["qty"];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #fff;
            --accent-color: #e4002b;
            --gray-light: #f5f5f5;
            --gray-medium: #767676;
            --success-color: #27ae60;
        }

        .cart-page {
            padding: 40px 0;
            background: #f8f9fa;
            min-height: 70vh;
        }

        .cart-header {
            margin-bottom: 30px;
        }

        .cart-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .cart-header .item-count {
            color: var(--gray-medium);
            font-size: 1.1rem;
        }

        .cart-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            align-items: start;
        }

        /* Cart Items */
        .cart-items {
            background: var(--secondary-color);
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .cart-item {
            display: flex;
            padding: 25px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }

        .cart-item:hover {
            background: #fafafa;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .item-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            color: var(--gray-medium);
            font-size: 0.9rem;
        }

        .item-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .qty-btn:hover:not(:disabled) {
            background: var(--primary-color);
            color: var(--secondary-color);
        }

        .qty-btn:disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
        }

        .qty-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px;
            font-weight: 600;
        }

        .remove-btn {
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }

        .remove-btn:hover {
            color: #c00;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: var(--secondary-color);
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        }

        .empty-cart i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .empty-cart h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .empty-cart p {
            color: var(--gray-medium);
            margin-bottom: 30px;
        }

        .btn-continue {
            background: var(--primary-color);
            color: var(--secondary-color);
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-continue:hover {
            background: #333;
            color: var(--secondary-color);
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

        .summary-header {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--primary-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-checkout:hover {
            background: #333;
        }

        .btn-login {
            width: 100%;
            background: var(--accent-color);
            color: var(--secondary-color);
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            margin-top: 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            background: #c00;
            color: var(--secondary-color);
        }

        .shipping-note {
            background: #e8f5e8;
            color: #2d5016;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 0.9rem;
            text-align: center;
        }

        /* Loading and Messages */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .cart-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .order-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .cart-page {
                padding: 20px 0;
            }
            
            .cart-header h1 {
                font-size: 2rem;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .item-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .item-actions {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .cart-header h1 {
                font-size: 1.8rem;
            }
            
            .item-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .order-summary {
                padding: 20px;
            }
        }

        .stock-warning {
            color: var(--accent-color);
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .updating {
            position: relative;
        }

        .updating::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .size-badge {
            background: var(--gray-light);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include "../includes/header.php"; ?>

    <div class="cart-page">
        <div class="container">
            <!-- Messages Container -->
            <div id="messageContainer"></div>

            <div class="cart-header">
                <h1>Giỏ hàng của bạn</h1>
                <div class="item-count">
                    <?php if (!empty($cart_items)): ?>
                        <i class="fas fa-shopping-cart me-2"></i><span id="totalItems"><?= $total_items ?></span> sản phẩm
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Giỏ hàng của bạn đang trống</h2>
                    <p>Hãy khám phá các sản phẩm tuyệt vời và thêm vào giỏ hàng!</p>
                    <a href="../index.php" class="btn-continue">
                        <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-container">
                    <!-- Cart Items -->
                    <div class="cart-items" id="cartItems">
                        <?php foreach ($cart_items as $item): 
                            $max_qty = min(10, $item['stock']); // Giới hạn số lượng theo stock, tối đa 10
                            $is_out_of_stock = $item['stock'] <= 0;
                        ?>
                        <div class="cart-item <?= $is_out_of_stock ? 'opacity-50' : '' ?>" id="cartItem-<?= $item["cart_id"] ?>">
                            <img src="../assets/images/products/<?= htmlspecialchars($item["image"]) ?>" 
                                 alt="<?= htmlspecialchars($item["name"]) ?>" 
                                 class="item-image"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                            
                            <div class="item-details">
                                <h3 class="item-name"><?= htmlspecialchars($item["name"]) ?></h3>
                                
                                <div class="item-meta">
                                    <?php if (!empty($item["size"])): ?>
                                        <span>Size: <span class="size-badge"><?= $item["size"] ?></span></span>
                                    <?php endif; ?>
                                    <span>Mã: #<?= $item["product_id"] ?></span>
                                </div>
                                
                                <div class="item-price"><?= number_format($item["price"]) ?>₫</div>
                                
                                <div class="item-actions">
                                    <div class="quantity-control">
                                        <button type="button" class="qty-btn decrease-btn" 
                                                data-cart-id="<?= $item["cart_id"] ?>" 
                                                data-current-qty="<?= $item["qty"] ?>"
                                                <?= $item["qty"] <= 1 || $is_out_of_stock ? 'disabled' : '' ?>>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        
                                        <span class="qty-input" id="qty-<?= $item["cart_id"] ?>"><?= $item["qty"] ?></span>
                                        
                                        <button type="button" class="qty-btn increase-btn" 
                                                data-cart-id="<?= $item["cart_id"] ?>" 
                                                data-current-qty="<?= $item["qty"] ?>"
                                                data-max-qty="<?= $max_qty ?>"
                                                <?= $item["qty"] >= $max_qty || $is_out_of_stock ? 'disabled' : '' ?>>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    
                                    <button type="button" class="remove-btn remove-item-btn" 
                                            data-cart-id="<?= $item["cart_id"] ?>" 
                                            data-product-name="<?= htmlspecialchars($item["name"]) ?>">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </div>

                                <?php if ($is_out_of_stock): ?>
                                    <div class="stock-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Sản phẩm đã hết hàng
                                    </div>
                                <?php elseif ($item['qty'] > $item['stock']): ?>
                                    <div class="stock-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Chỉ còn <?= $item['stock'] ?> sản phẩm trong kho
                                    </div>
                                <?php elseif ($item['stock'] < 5): ?>
                                    <div class="stock-warning">
                                        <i class="fas fa-info-circle"></i>
                                        Chỉ còn <?= $item['stock'] ?> sản phẩm trong kho
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-header">Tóm tắt đơn hàng</div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Số lượng sản phẩm</span>
                            <span class="summary-value" id="summaryTotalItems"><?= $total_items ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Tổng tiền hàng</span>
                            <span class="summary-value" id="summarySubtotal"><?= number_format($total) ?>₫</span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Phí vận chuyển</span>
                            <span class="summary-value">
                                <span id="shippingFee"><?= $total >= 500000 ? 'Miễn phí' : '30.000₫' ?></span>
                            </span>
                        </div>
                        
                        <div class="summary-row summary-total">
                            <span>Tổng thanh toán</span>
                            <span id="summaryTotal"><?= number_format($total >= 500000 ? $total : $total + 30000) ?>₫</span>
                        </div>

                        <div class="shipping-note" id="shippingNote">
                            <?php if ($total >= 500000): ?>
                                <i class="fas fa-check-circle me-2"></i>
                                Bạn được miễn phí vận chuyển
                            <?php else: ?>
                                <i class="fas fa-info-circle me-2"></i>
                                Thêm <?= number_format(500000 - $total) ?>₫ để được miễn phí vận chuyển
                            <?php endif; ?>
                        </div>

                        <?php 
                        // Kiểm tra xem có sản phẩm nào hết hàng không
                        $has_out_of_stock = false;
                        foreach ($cart_items as $item) {
                            if ($item['stock'] <= 0) {
                                $has_out_of_stock = true;
                                break;
                            }
                        }
                        ?>

                        <?php if (!isset($_SESSION["user_id"])): ?>
                            <a href="../auth/login.php?redirect=cart" class="btn-login">
                                <i class="fas fa-sign-in-alt"></i>
                                Đăng nhập để thanh toán
                            </a>
                        <?php elseif ($has_out_of_stock): ?>
                            <button type="button" class="btn-checkout" disabled style="background: #ccc; cursor: not-allowed;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Vui lòng xóa sản phẩm hết hàng
                            </button>
                        <?php else: ?>
                            <form action="checkout.php" method="POST">
                                <input type="hidden" name="total" value="<?= $total >= 500000 ? $total : $total + 30000 ?>">
                                <button type="submit" class="btn-checkout">
                                    <i class="fas fa-credit-card"></i>
                                    Tiến hành thanh toán
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="mt-3 text-center">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hiển thị thông báo
        function showMessage(message, type = 'success') {
            const messageContainer = document.getElementById('messageContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas ${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            messageContainer.appendChild(alertDiv);
            
            // Tự động ẩn sau 5 giây
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Cập nhật số lượng
        async function updateQuantity(cartId, newQty) {
            const cartItem = document.getElementById(`cartItem-${cartId}`);
            cartItem.classList.add('loading');
            
            try {
                const response = await fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}&qty=${newQty}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Cập nhật hiển thị số lượng
                    document.getElementById(`qty-${cartId}`).textContent = newQty;
                    
                    // Cập nhật tổng tiền
                    updateCartSummary(data.cart_data);
                    
                    // Cập nhật trạng thái buttons
                    updateButtonStates(cartId, newQty, data.max_qty);
                    
                    // Cập nhật cảnh báo stock
                    updateStockWarnings(cartId, data.stock_info);
                    
                    showMessage('Đã cập nhật số lượng sản phẩm');
                } else {
                    showMessage(data.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Lỗi kết nối mạng', 'error');
            } finally {
                cartItem.classList.remove('loading');
            }
        }

        // Xóa sản phẩm
        async function removeItem(cartId, productName) {
            if (!confirm(`Bạn có chắc muốn xóa "${productName}" khỏi giỏ hàng?`)) {
                return;
            }
            
            const cartItem = document.getElementById(`cartItem-${cartId}`);
            cartItem.classList.add('loading');
            
            try {
                const response = await fetch('remove_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Xóa item khỏi DOM
                    cartItem.remove();
                    
                    // Cập nhật tổng tiền
                    updateCartSummary(data.cart_data);
                    
                    // Kiểm tra nếu giỏ hàng trống
                    checkEmptyCart();
                    
                    showMessage('Đã xóa sản phẩm khỏi giỏ hàng');
                } else {
                    showMessage(data.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Lỗi kết nối mạng', 'error');
            }
        }

        // Cập nhật tổng tiền
        function updateCartSummary(cartData) {
            if (!cartData) return;
            
            document.getElementById('totalItems').textContent = cartData.total_items;
            document.getElementById('summaryTotalItems').textContent = cartData.total_items;
            document.getElementById('summarySubtotal').textContent = cartData.subtotal_formatted;
            document.getElementById('summaryTotal').textContent = cartData.total_formatted;
            
            // Cập nhật phí vận chuyển
            const shippingFeeElement = document.getElementById('shippingFee');
            const shippingNoteElement = document.getElementById('shippingNote');
            
            if (cartData.shipping_fee === 0) {
                shippingFeeElement.textContent = 'Miễn phí';
                shippingNoteElement.innerHTML = '<i class="fas fa-check-circle me-2"></i>Bạn được miễn phí vận chuyển';
            } else {
                shippingFeeElement.textContent = '30.000₫';
                const amountNeeded = 500000 - cartData.subtotal;
                shippingNoteElement.innerHTML = `<i class="fas fa-info-circle me-2"></i>Thêm ${amountNeeded.toLocaleString()}₫ để được miễn phí vận chuyển`;
            }
        }

        // Cập nhật trạng thái buttons
        function updateButtonStates(cartId, currentQty, maxQty) {
            const decreaseBtn = document.querySelector(`.decrease-btn[data-cart-id="${cartId}"]`);
            const increaseBtn = document.querySelector(`.increase-btn[data-cart-id="${cartId}"]`);
            
            decreaseBtn.disabled = currentQty <= 1;
            increaseBtn.disabled = currentQty >= maxQty;
            
            // Cập nhật data attributes
            decreaseBtn.setAttribute('data-current-qty', currentQty);
            increaseBtn.setAttribute('data-current-qty', currentQty);
            increaseBtn.setAttribute('data-max-qty', maxQty);
        }

        // Cập nhật cảnh báo stock
        function updateStockWarnings(cartId, stockInfo) {
            const cartItem = document.getElementById(`cartItem-${cartId}`);
            let warningDiv = cartItem.querySelector('.stock-warning');
            
            if (!warningDiv) {
                warningDiv = document.createElement('div');
                warningDiv.className = 'stock-warning';
                cartItem.querySelector('.item-actions').after(warningDiv);
            }
            
            if (stockInfo.stock <= 0) {
                warningDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Sản phẩm đã hết hàng';
                cartItem.classList.add('opacity-50');
                // Disable buttons
                cartItem.querySelectorAll('.qty-btn').forEach(btn => btn.disabled = true);
            } else if (stockInfo.current_qty > stockInfo.stock) {
                warningDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Chỉ còn ${stockInfo.stock} sản phẩm trong kho`;
            } else if (stockInfo.stock < 5) {
                warningDiv.innerHTML = `<i class="fas fa-info-circle"></i> Chỉ còn ${stockInfo.stock} sản phẩm trong kho`;
            } else {
                warningDiv.innerHTML = '';
                cartItem.classList.remove('opacity-50');
            }
        }

        // Kiểm tra giỏ hàng trống
        function checkEmptyCart() {
            const cartItems = document.getElementById('cartItems');
            if (cartItems.children.length === 0) {
                // Reload page to show empty cart state
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý tăng số lượng
            document.querySelectorAll('.increase-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const cartId = this.dataset.cartId;
                    const currentQty = parseInt(this.dataset.currentQty);
                    const maxQty = parseInt(this.dataset.maxQty);
                    const newQty = Math.min(currentQty + 1, maxQty);
                    
                    updateQuantity(cartId, newQty);
                });
            });
            
            // Xử lý giảm số lượng
            document.querySelectorAll('.decrease-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const cartId = this.dataset.cartId;
                    const currentQty = parseInt(this.dataset.currentQty);
                    const newQty = Math.max(currentQty - 1, 1);
                    
                    updateQuantity(cartId, newQty);
                });
            });
            
            // Xử lý xóa sản phẩm
            document.querySelectorAll('.remove-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const cartId = this.dataset.cartId;
                    const productName = this.dataset.productName;
                    
                    removeItem(cartId, productName);
                });
            });
            
            // Xử lý lỗi ảnh
            const images = document.querySelectorAll('.item-image');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+';
                });
            });
        });
    </script>
</body>
</html>