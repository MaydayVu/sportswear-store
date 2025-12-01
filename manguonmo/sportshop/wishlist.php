<?php
session_start();
include "config.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=wishlist");
    exit();
}

$user_id = $_SESSION['user_id'];

// Xử lý thêm/xóa sản phẩm khỏi wishlist
if (isset($_POST['action'])) {
    $product_id = intval($_POST['product_id']);
    
    if ($_POST['action'] == 'add') {
        // Kiểm tra xem sản phẩm đã có trong wishlist chưa
        $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $insert_stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $user_id, $product_id);
            $insert_stmt->execute();
            $insert_stmt->close();
            
            $_SESSION['success'] = "Đã thêm sản phẩm vào danh sách yêu thích!";
        } else {
            $_SESSION['info'] = "Sản phẩm đã có trong danh sách yêu thích!";
        }
        $check_stmt->close();
        
    } elseif ($_POST['action'] == 'remove') {
        $delete_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete_stmt->bind_param("ii", $user_id, $product_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        $_SESSION['success'] = "Đã xóa sản phẩm khỏi danh sách yêu thích!";
    }
    
    header("Location: wishlist.php");
    exit();
}

// Lấy danh sách sản phẩm trong wishlist
$wishlist_stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name, w.id AS wishlist_id 
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE w.user_id = ? 
    ORDER BY w.created_at DESC
");
$wishlist_stmt->bind_param("i", $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();

// Đếm tổng số sản phẩm trong wishlist
$total_items = $wishlist_result->num_rows;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách yêu thích - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .wishlist-page {
            padding: 80px 0 60px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .wishlist-stats {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .wishlist-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .wishlist-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .wishlist-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #000;
            margin: 0;
        }

        .empty-wishlist {
            text-align: center;
            padding: 80px 40px;
            color: #666;
        }

        .empty-icon {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 25px;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .empty-text {
            font-size: 1rem;
            margin-bottom: 30px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-explore {
            background: #000;
            color: white;
            padding: 12px 35px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-explore:hover {
            background: #333;
            color: white;
            transform: translateY(-2px);
        }

        /* Products Grid */
        .products-grid {
            padding: 30px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            margin-bottom: 25px;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .product-badges {
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            z-index: 2;
            display: flex;
            justify-content: space-between;
        }

        .discount-badge {
            background: #e4002b;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .wishlist-badge {
            background: rgba(0,0,0,0.8);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .wishlist-badge:hover {
            background: #e4002b;
            transform: scale(1.1);
        }

        .product-image-container {
            position: relative;
            overflow: hidden;
            background: #f8f9fa;
        }

        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 12px;
            color: #000;
        }

        .product-name a {
            color: inherit;
            text-decoration: none;
        }

        .product-name a:hover {
            color: #e4002b;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .current-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e4002b;
        }

        .original-price {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .product-size {
            background: #f8f9fa;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #666;
        }

        .product-gender {
            font-size: 0.8rem;
            color: #666;
            text-transform: capitalize;
        }

        .stock-status {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .stock-in {
            color: #28a745;
        }

        .stock-out {
            color: #dc3545;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .btn-add-cart {
            flex: 1;
            background: #000;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-add-cart:hover {
            background: #333;
            color: white;
        }

        .btn-add-cart:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .btn-remove {
            width: 45px;
            background: #f8f9fa;
            color: #666;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .btn-remove:hover {
            background: #e4002b;
            color: white;
            border-color: #e4002b;
        }

        .share-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-top: 40px;
            text-align: center;
        }

        .share-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #000;
        }

        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .share-btn {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .share-facebook {
            background: #3b5998;
            color: white;
        }

        .share-twitter {
            background: #1da1f2;
            color: white;
        }

        .share-pinterest {
            background: #bd081c;
            color: white;
        }

        .share-link {
            background: #000;
            color: white;
        }

        .share-btn:hover {
            transform: translateY(-2px);
            color: white;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .products-grid {
                padding: 20px;
            }

            .product-card {
                margin-bottom: 20px;
            }

            .product-actions {
                flex-direction: column;
            }

            .btn-remove {
                width: 100%;
                padding: 10px;
            }

            .wishlist-header {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="wishlist-page">
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">Danh sách yêu thích</h1>
                <p class="page-subtitle">Lưu trữ những sản phẩm bạn yêu thích</p>
            </div>
        </div>

        <div class="container">
            <!-- Thông báo -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?= $_SESSION['info'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['info']); ?>
            <?php endif; ?>

            <!-- Thống kê -->
            <div class="wishlist-stats">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number"><?= $total_items ?></div>
                            <div class="stat-label">Sản phẩm</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php
                                $total_value = 0;
                                $wishlist_result->data_seek(0);
                                while ($product = $wishlist_result->fetch_assoc()) {
                                    $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                                    $total_value += $current_price;
                                }
                                echo number_format($total_value);
                                ?>₫
                            </div>
                            <div class="stat-label">Tổng giá trị</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php
                                $discounted_items = 0;
                                $wishlist_result->data_seek(0);
                                while ($product = $wishlist_result->fetch_assoc()) {
                                    if ($product['discount_percent'] > 0) {
                                        $discounted_items++;
                                    }
                                }
                                echo $discounted_items;
                                ?>
                            </div>
                            <div class="stat-label">Đang giảm giá</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php
                                $available_items = 0;
                                $wishlist_result->data_seek(0);
                                while ($product = $wishlist_result->fetch_assoc()) {
                                    $quantity_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM product_sizes WHERE product_id = ?");
                                    $quantity_stmt->bind_param("i", $product['id']);
                                    $quantity_stmt->execute();
                                    $quantity_result = $quantity_stmt->get_result();
                                    $total_quantity = $quantity_result->fetch_assoc()['total'] ?? 0;
                                    $quantity_stmt->close();
                                    
                                    if ($total_quantity > 0) {
                                        $available_items++;
                                    }
                                }
                                echo $available_items;
                                ?>
                            </div>
                            <div class="stat-label">Còn hàng</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wishlist-content">
                <div class="wishlist-header">
                    <h2 class="wishlist-title">Sản phẩm yêu thích của bạn</h2>
                    <div class="wishlist-actions">
                        <?php if ($total_items > 0): ?>
                            <form method="POST" action="wishlist.php" class="d-inline">
                                <input type="hidden" name="action" value="clear_all">
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa tất cả sản phẩm khỏi danh sách yêu thích?')">
                                    <i class="fas fa-trash me-1"></i>Xóa tất cả
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($total_items > 0): ?>
                    <div class="products-grid">
                        <div class="row">
                            <?php 
                            $wishlist_result->data_seek(0);
                            while ($product = $wishlist_result->fetch_assoc()): 
                                $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                                
                                // Lấy tổng số lượng từ product_sizes
                                $quantity_stmt = $conn->prepare("SELECT SUM(quantity) as total_quantity FROM product_sizes WHERE product_id = ?");
                                $quantity_stmt->bind_param("i", $product['id']);
                                $quantity_stmt->execute();
                                $quantity_result = $quantity_stmt->get_result();
                                $total_quantity = $quantity_result->fetch_assoc()['total_quantity'] ?? 0;
                                $quantity_stmt->close();
                                
                                // Lấy các size có sẵn
                                $sizes_stmt = $conn->prepare("SELECT size FROM product_sizes WHERE product_id = ? AND quantity > 0 ORDER BY size");
                                $sizes_stmt->bind_param("i", $product['id']);
                                $sizes_stmt->execute();
                                $sizes_result = $sizes_stmt->get_result();
                                $available_sizes = [];
                                while ($size = $sizes_result->fetch_assoc()) {
                                    $available_sizes[] = $size['size'];
                                }
                                $sizes_stmt->close();
                                
                                $is_out_of_stock = $total_quantity <= 0;
                            ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="product-card">
                                    <div class="product-badges">
                                        <?php if ($product['discount_percent'] > 0): ?>
                                            <div class="discount-badge">-<?= $product['discount_percent'] ?>%</div>
                                        <?php endif; ?>
                                        <form method="POST" action="wishlist.php" class="d-inline">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="wishlist-badge border-0">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="product-image-container">
                                        <a href="product_detail.php?id=<?= $product['id'] ?>">
                                            <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                                 class="product-image"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                                        </a>
                                    </div>

                                    <div class="product-info">
                                        <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                                        <h3 class="product-name">
                                            <a href="product_detail.php?id=<?= $product['id'] ?>">
                                                <?= htmlspecialchars($product['name']) ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="product-price">
                                            <span class="current-price"><?= number_format($current_price) ?>₫</span>
                                            <?php if ($product['discount_percent'] > 0): ?>
                                                <span class="original-price"><?= number_format($product['price']) ?>₫</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="stock-status <?= $is_out_of_stock ? 'stock-out' : 'stock-in' ?>">
                                            <i class="fas fa-<?= $is_out_of_stock ? 'times' : 'check' ?> me-1"></i>
                                            <?= $is_out_of_stock ? 'Tạm hết hàng' : 'Còn hàng (' . $total_quantity . ')' ?>
                                        </div>

                                        <div class="product-meta">
                                            <?php if (!empty($available_sizes)): ?>
                                                <span class="product-size">Size: <?= implode(', ', $available_sizes) ?></span>
                                            <?php else: ?>
                                                <span class="product-size">Size: Đang cập nhật</span>
                                            <?php endif; ?>
                                            <span class="product-gender">
                                                <?= $product['gender'] == 'nam' ? 'Nam' : ($product['gender'] == 'nu' ? 'Nữ' : 'Unisex') ?>
                                            </span>
                                        </div>

                                        <div class="product-actions">
                                            <a href="product_detail.php?id=<?= $product['id'] ?>" 
                                               class="btn-add-cart <?= $is_out_of_stock ? 'disabled' : '' ?>"
                                               <?= $is_out_of_stock ? 'style="pointer-events: none; opacity: 0.6;"' : '' ?>>
                                                <i class="fas fa-<?= $is_out_of_stock ? 'eye' : 'shopping-cart' ?> me-1"></i>
                                                <?= $is_out_of_stock ? 'Xem chi tiết' : 'Thêm vào giỏ' ?>
                                            </a>
                                            <form method="POST" action="wishlist.php" class="d-inline">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <button type="submit" class="btn-remove">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Share Section -->
                    <div class="share-section">
                        <h4 class="share-title">Chia sẻ danh sách yêu thích</h4>
                        <div class="share-buttons">
                            <a href="#" class="share-btn share-facebook">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <a href="#" class="share-btn share-twitter">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                            <a href="#" class="share-btn share-pinterest">
                                <i class="fab fa-pinterest"></i> Pinterest
                            </a>
                            <a href="#" class="share-btn share-link" onclick="copyWishlistLink()">
                                <i class="fas fa-link"></i> Sao chép link
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="empty-wishlist">
                        <div class="empty-icon">
                            <i class="far fa-heart"></i>
                        </div>
                        <h3 class="empty-title">Danh sách yêu thích trống</h3>
                        <p class="empty-text">
                            Bạn chưa có sản phẩm nào trong danh sách yêu thích. Hãy khám phá cửa hàng và thêm những sản phẩm bạn yêu thích!
                        </p>
                        <a href="products.php" class="btn-explore">
                            <i class="fas fa-shopping-bag me-2"></i>Khám phá sản phẩm
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Copy wishlist link
        function copyWishlistLink() {
            const tempInput = document.createElement('input');
            tempInput.value = window.location.href;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            alert('Đã sao chép link danh sách yêu thích!');
        }

        // Add to cart from wishlist
        function addToCartFromWishlist(productId) {
            // Gửi AJAX request để thêm vào giỏ hàng
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&qty=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cập nhật số lượng giỏ hàng trong header
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Hiển thị thông báo
                    alert('Đã thêm sản phẩm vào giỏ hàng!');
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm vào giỏ hàng!');
            });
        }

        // Smooth animation for product cards
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>