<?php
session_start();
// Sử dụng đường dẫn tuyệt đối để include
include __DIR__ . "/config.php";

// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin sản phẩm
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("Location: index.php");
    exit();
}

// Kiểm tra xem sản phẩm đã có trong wishlist chưa
$is_in_wishlist = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $wishlist_check_sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $wishlist_check_stmt = $conn->prepare($wishlist_check_sql);
    $wishlist_check_stmt->bind_param("ii", $user_id, $product_id);
    $wishlist_check_stmt->execute();
    $wishlist_result = $wishlist_check_stmt->get_result();
    $is_in_wishlist = $wishlist_result->num_rows > 0;
    $wishlist_check_stmt->close();
}

// Xử lý thêm/xóa khỏi wishlist
if (isset($_GET['wishlist_action'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = "Vui lòng đăng nhập để sử dụng tính năng yêu thích!";
        $_SESSION['message_type'] = "warning";
        header("Location: auth/login.php?redirect=product_detail.php?id=" . $product_id);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $action = $_GET['wishlist_action'];
    
    if ($action === 'add') {
        // Kiểm tra xem sản phẩm đã có trong wishlist chưa
        $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // Thêm vào wishlist
            $insert_stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $user_id, $product_id);
            if ($insert_stmt->execute()) {
                $_SESSION['message'] = "Đã thêm vào danh sách yêu thích!";
                $_SESSION['message_type'] = "success";
                $is_in_wishlist = true;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
        
    } elseif ($action === 'remove') {
        // Xóa khỏi wishlist
        $delete_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete_stmt->bind_param("ii", $user_id, $product_id);
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = "Đã xóa khỏi danh sách yêu thích!";
            $_SESSION['message_type'] = "success";
            $is_in_wishlist = false;
        }
        $delete_stmt->close();
    }
    
    // Quay lại trang sản phẩm
    header("Location: product_detail.php?id=" . $product_id);
    exit();
}

// Lấy các size có sẵn cho sản phẩm
$sizes_sql = "SELECT * FROM product_sizes WHERE product_id = ? AND quantity > 0 ORDER BY size";
$sizes_stmt = $conn->prepare($sizes_sql);
$sizes_stmt->bind_param("i", $product_id);
$sizes_stmt->execute();
$available_sizes = $sizes_stmt->get_result();

// Tính tổng số lượng tồn kho
$total_quantity_sql = "SELECT SUM(quantity) as total FROM product_sizes WHERE product_id = ?";
$total_stmt = $conn->prepare($total_quantity_sql);
$total_stmt->bind_param("i", $product_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total_quantity = $total_result['total'] ?? 0;

// Xử lý thêm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $size_id = (int)$_POST['size_id'];
    $quantity = (int)$_POST['quantity'];
    $session_id = session_id();
    
    // Lấy thông tin size và kiểm tra số lượng
    $size_info_sql = "SELECT * FROM product_sizes WHERE id = ? AND product_id = ?";
    $size_info_stmt = $conn->prepare($size_info_sql);
    $size_info_stmt->bind_param("ii", $size_id, $product_id);
    $size_info_stmt->execute();
    $size_info = $size_info_stmt->get_result()->fetch_assoc();
    
    if (!$size_info || $size_info['quantity'] < $quantity) {
        $error_message = "Số lượng trong kho không đủ!";
    } else {
        // Tính giá sau giảm giá
        $price = $product['price'];
        if ($product['discount_percent'] > 0) {
            $price = $product['price'] * (1 - $product['discount_percent'] / 100);
        }
        
        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        $check_sql = "SELECT id, qty FROM carts WHERE session_id = ? AND product_id = ? AND size_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sii", $session_id, $product_id, $size_id);
        $check_stmt->execute();
        $existing_item = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing_item) {
            // Cập nhật số lượng nếu đã có
            $new_qty = $existing_item['qty'] + $quantity;
            if ($new_qty <= $size_info['quantity']) {
                $update_sql = "UPDATE carts SET qty = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $new_qty, $existing_item['id']);
                $update_stmt->execute();
                $success_message = "Đã cập nhật giỏ hàng!";
            } else {
                $error_message = "Số lượng vượt quá tồn kho!";
            }
        } else {
            // Thêm mới vào giỏ hàng
            $insert_sql = "INSERT INTO carts (session_id, product_id, size_id, qty, price) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("siiid", $session_id, $product_id, $size_id, $quantity, $price);
            if ($insert_stmt->execute()) {
                $success_message = "Đã thêm vào giỏ hàng!";
            } else {
                $error_message = "Có lỗi xảy ra khi thêm vào giỏ hàng!";
            }
        }
    }
}

// Lấy các sản phẩm cùng danh mục
$related_sql = "SELECT p.*, 
                       (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) as total_quantity
                FROM products p 
                WHERE p.category_id = ? AND p.id != ? 
                AND (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) > 0 
                LIMIT 4";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("ii", $product['category_id'], $product_id);
$related_stmt->execute();
$related_products = $related_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Load CSS chính của website -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS riêng cho product detail */
        .product-hero {
            padding: 40px 0;
            background: #fff;
        }
        
        .product-gallery {
            position: relative;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            object-fit: contain;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid transparent;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .thumbnail.active {
            border-color: #000;
        }
        
        .product-info {
            padding-left: 40px;
        }
        
        .product-category {
            font-size: 14px;
            color: #767676;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .product-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .product-price {
            margin: 20px 0;
        }
        
        .current-price {
            font-size: 24px;
            font-weight: 700;
        }
        
        .original-price {
            font-size: 18px;
            color: #767676;
            text-decoration: line-through;
            margin-left: 10px;
        }
        
        .discount-badge {
            background: #e4002b;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            margin-left: 10px;
        }
        
        .size-selector {
            margin: 30px 0;
        }
        
        .size-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .size-title h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }
        
        .size-guide {
            color: #767676;
            text-decoration: underline;
            font-size: 14px;
            cursor: pointer;
        }
        
        .size-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
            gap: 10px;
        }
        
        .size-option {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #fff;
            position: relative;
        }
        
        .size-option:hover {
            border-color: #000;
        }
        
        .size-option.selected {
            border-color: #000;
            background: #000;
            color: #fff;
        }
        
        .size-option.disabled {
            color: #ccc;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        
        .size-quantity {
            font-size: 10px;
            color: #767676;
            position: absolute;
            bottom: 2px;
            right: 2px;
        }
        
        .quantity-selector {
            margin: 25px 0;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background: #fff;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 60px;
            height: 40px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 16px;
        }
        
        .add-to-cart-btn {
            width: 100%;
            padding: 16px;
            background: #000;
            color: #fff;
            border: none;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 15px;
        }
        
        .add-to-cart-btn:hover {
            background: #333;
        }
        
        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Wishlist Button Styles */
        .wishlist-section {
            margin-bottom: 20px;
        }

        .btn-wishlist {
            width: 100%;
            padding: 12px;
            border: 2px solid #000;
            background: transparent;
            color: #000;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-wishlist:hover {
            background: #000;
            color: #fff;
        }

        .btn-wishlist.in-wishlist {
            background: #e4002b;
            border-color: #e4002b;
            color: #fff;
        }

        .btn-wishlist.in-wishlist:hover {
            background: #c40023;
            border-color: #c40023;
        }

        .wishlist-icon {
            font-size: 16px;
        }
        
        .product-features {
            margin: 30px 0;
            padding: 20px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .feature-icon {
            margin-right: 10px;
            color: #767676;
        }
        
        .product-details {
            margin: 40px 0;
        }
        
        .detail-section {
            margin-bottom: 30px;
        }
        
        .detail-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .related-products {
            padding: 60px 0;
            background: #f5f5f5;
        }
        
        .stock-status {
            font-size: 14px;
            margin: 10px 0;
        }
        
        .in-stock {
            color: #27ae60;
        }
        
        .out-of-stock {
            color: #e4002b;
        }

        .related-product-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
            position: relative;
        }

        .related-product-card:hover {
            transform: translateY(-5px);
        }

        .related-product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .related-product-info {
            padding: 15px;
        }

        .sport-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin: 1px;
        }

        .sport-none { background: #95a5a6; color: white; }
        .sport-football { background: #e74c3c; color: white; }
        .sport-running { background: #3498db; color: white; }
        .sport-basketball { background: #e67e22; color: white; }
        .sport-training { background: #9b59b6; color: white; }
        .sport-motosport { background: #34495e; color: white; }
        .sport-court_sports { background: #27ae60; color: white; }

        /* Related product wishlist button */
        .related-wishlist-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: #666;
        }

        .related-wishlist-btn:hover {
            background: #fff;
            color: #e4002b;
        }

        .related-wishlist-btn.in-wishlist {
            color: #e4002b;
        }

        @media (max-width: 768px) {
            .product-info {
                padding-left: 0;
                margin-top: 30px;
            }
            
            .main-image {
                height: 400px;
            }
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Sử dụng đường dẫn chính xác cho include -->
    <?php include __DIR__ . "/includes/header.php"; ?>

    <div class="container-fluid product-hero">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb" style="font-size: 14px; margin-bottom: 20px;">
                <a href="index.php" style="color: #767676; text-decoration: none;">Trang chủ</a> >
                <a href="products.php?category=<?= $product['category_id'] ?>" style="color: #767676; text-decoration: none;"><?= htmlspecialchars($product['category_name']) ?></a> >
                <span style="color: #000;"><?= htmlspecialchars($product['name']) ?></span>
            </nav>

            <!-- Hiển thị thông báo -->
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'warning'; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            endif; ?>

            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Product Gallery -->
                <div class="col-md-6 product-gallery">
                    <img id="mainImage" src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" class="main-image"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                    
                    <div class="thumbnails">
                        <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                             alt="Main" class="thumbnail active"
                             onclick="changeImage(this.src)"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7huqNwPC90ZXh0Pjwvc3ZnPg=='">
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-md-6 product-info">
                    <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    
                    <?php if (!empty($product['brand'])): ?>
                    <div style="color: #767676; margin-bottom: 15px;">
                        Thương hiệu: <?= htmlspecialchars($product['brand']) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Loại thể thao -->
                    <?php if (!empty($product['sport_type']) && $product['sport_type'] != 'none'): ?>
                    <div style="margin-bottom: 15px;">
                        <?php 
                        $sportClass = [
                            'none' => 'sport-none',
                            'football' => 'sport-football',
                            'running' => 'sport-running',
                            'basketball' => 'sport-basketball',
                            'training' => 'sport-training',
                            'motosport' => 'sport-motosport',
                            'court_sports' => 'sport-court_sports'
                        ];
                        $sportText = [
                            'none' => 'Không',
                            'football' => 'Bóng đá',
                            'running' => 'Chạy bộ',
                            'basketball' => 'Bóng rổ',
                            'training' => 'Tập luyện',
                            'motosport' => 'Motosport',
                            'court_sports' => 'Thể thao sân'
                        ];
                        $sportType = $product['sport_type'];
                        ?>
                        <span class="sport-badge <?= $sportClass[$sportType] ?? 'sport-none' ?>">
                            <i class="fas fa-<?= $sportType == 'football' ? 'futbol' : ($sportType == 'running' ? 'running' : ($sportType == 'basketball' ? 'basketball-ball' : ($sportType == 'training' ? 'dumbbell' : ($sportType == 'motosport' ? 'motorcycle' : 'table-tennis')))) ?>"></i>
                            <?= $sportText[$sportType] ?? 'Không' ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="product-price">
                        <?php
                        $current_price = $product['price'];
                        $has_discount = $product['discount_percent'] > 0;
                        if ($has_discount) {
                            $current_price = $product['price'] * (1 - $product['discount_percent'] / 100);
                        }
                        ?>
                        <span class="current-price"><?= number_format($current_price) ?>₫</span>
                        <?php if ($has_discount): ?>
                            <span class="original-price"><?= number_format($product['price']) ?>₫</span>
                            <span class="discount-badge">-<?= $product['discount_percent'] ?>%</span>
                        <?php endif; ?>
                    </div>

                    <div class="stock-status <?= $total_quantity > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <?= $total_quantity > 0 ? '✓ Còn hàng' : '✗ Hết hàng' ?>
                    </div>

                    <!-- Wishlist Button -->
                    <div class="wishlist-section">
                        <?php if ($is_in_wishlist): ?>
                            <a href="product_detail.php?id=<?= $product_id ?>&wishlist_action=remove" 
                               class="btn-wishlist in-wishlist">
                                <i class="fas fa-heart wishlist-icon"></i>
                                Đã thích
                            </a>
                        <?php else: ?>
                            <a href="product_detail.php?id=<?= $product_id ?>&wishlist_action=add" 
                               class="btn-wishlist">
                                <i class="far fa-heart wishlist-icon"></i>
                                Thêm vào yêu thích
                            </a>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="">
                        <!-- Size Selector -->
                        <div class="size-selector">
                            <div class="size-title">
                                <h3>Kích cỡ</h3>
                                <span class="size-guide" onclick="showSizeGuide()">Hướng dẫn chọn size</span>
                            </div>
                            <div class="size-options" id="sizeOptions">
                                <?php if ($available_sizes->num_rows > 0): ?>
                                    <?php while($size = $available_sizes->fetch_assoc()): ?>
                                        <div class="size-option" 
                                             onclick="selectSize(<?= $size['id'] ?>, '<?= $size['size'] ?>', <?= $size['quantity'] ?>)"
                                             data-size-id="<?= $size['id'] ?>"
                                             data-quantity="<?= $size['quantity'] ?>">
                                            <?= $size['size'] ?>
                                            <span class="size-quantity"><?= $size['quantity'] ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-muted">Sản phẩm tạm thời hết hàng</div>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="size_id" id="selectedSizeId" required>
                            <div id="selectedSizeInfo" class="mt-2" style="font-size: 14px; color: #767676;"></div>
                        </div>

                        <!-- Quantity Selector -->
                        <div class="quantity-selector">
                            <h3>Số lượng</h3>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="1" 
                                       class="quantity-input" readonly>
                                <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                            </div>
                            <div id="quantityInfo" class="mt-1" style="font-size: 12px; color: #767676;"></div>
                        </div>

                        <!-- Add to Cart Button -->
                        <button type="submit" name="add_to_cart" class="add-to-cart-btn" id="addToCartBtn"
                                <?= $total_quantity <= 0 ? 'disabled' : '' ?>>
                            <?= $total_quantity > 0 ? 'Thêm vào giỏ hàng' : 'Hết hàng' ?>
                        </button>
                    </form>

                    <!-- Product Features -->
                    <div class="product-features">
                        <div class="feature-item">
                            <i class="fas fa-shipping-fast feature-icon"></i>
                            <span>Miễn phí vận chuyển cho đơn hàng từ 500.000₫</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-exchange-alt feature-icon"></i>
                            <span>Đổi trả trong 30 ngày</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-shield-alt feature-icon"></i>
                            <span>Bảo hành chính hãng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details -->
    <div class="container product-details">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="detail-section">
                    <h3 class="detail-title">Mô tả sản phẩm</h3>
                    <p><?= nl2br(htmlspecialchars($product['description'] ?: 'Đang cập nhật...')) ?></p>
                </div>

                <?php if (!empty($product['material'])): ?>
                <div class="detail-section">
                    <h3 class="detail-title">Chất liệu</h3>
                    <p><?= htmlspecialchars($product['material']) ?></p>
                </div>
                <?php endif; ?>

                <div class="detail-section">
                    <h3 class="detail-title">Thông tin sản phẩm</h3>
                    <ul>
                        <li>Danh mục: <?= htmlspecialchars($product['category_name']) ?></li>
                        <li>Giới tính: 
                            <?= $product['gender'] == 'nam' ? 'Nam' : ($product['gender'] == 'nu' ? 'Nữ' : 'Unisex') ?>
                        </li>
                        <?php if (!empty($product['sport_type']) && $product['sport_type'] != 'none'): ?>
                        <li>Loại thể thao: <?= $sportText[$product['sport_type']] ?? 'Không' ?></li>
                        <?php endif; ?>
                        <li>Tình trạng: <?= $total_quantity > 0 ? 'Còn hàng' : 'Hết hàng' ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if ($related_products->num_rows > 0): ?>
    <div class="related-products">
        <div class="container">
            <h2 class="section-title" style="text-align: center; font-size: 2rem; margin-bottom: 40px;">SẢN PHẨM LIÊN QUAN</h2>
            <div class="row">
                <?php while ($related = $related_products->fetch_assoc()): 
                    $related_price = $related['price'];
                    if ($related['discount_percent'] > 0) {
                        $related_price = $related['price'] * (1 - $related['discount_percent'] / 100);
                    }
                    
                    // Kiểm tra xem sản phẩm liên quan có trong wishlist không
                    $related_in_wishlist = false;
                    if (isset($_SESSION['user_id'])) {
                        $related_check_sql = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
                        $related_check_stmt = $conn->prepare($related_check_sql);
                        $related_check_stmt->bind_param("ii", $_SESSION['user_id'], $related['id']);
                        $related_check_stmt->execute();
                        $related_check_result = $related_check_stmt->get_result();
                        $related_in_wishlist = $related_check_result->num_rows > 0;
                        $related_check_stmt->close();
                    }
                ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="related-product-card">
                        <a href="product_detail.php?id=<?= $related['id'] ?>" class="text-decoration-none text-dark">
                            <img src="assets/images/products/<?= htmlspecialchars($related['image']) ?>" 
                                 alt="<?= htmlspecialchars($related['name']) ?>" class="related-product-image"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                        </a>
                        
                        <!-- Wishlist button for related product -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($related_in_wishlist): ?>
                                <button class="related-wishlist-btn in-wishlist" 
                                        onclick="window.location.href='product_detail.php?id=<?= $related['id'] ?>&wishlist_action=remove'">
                                    <i class="fas fa-heart"></i>
                                </button>
                            <?php else: ?>
                                <button class="related-wishlist-btn" 
                                        onclick="window.location.href='product_detail.php?id=<?= $related['id'] ?>&wishlist_action=add'">
                                    <i class="far fa-heart"></i>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="related-wishlist-btn" 
                                    onclick="window.location.href='auth/login.php?redirect=product_detail.php?id=<?= $related['id'] ?>'">
                                <i class="far fa-heart"></i>
                            </button>
                        <?php endif; ?>
                        
                        <div class="related-product-info">
                            <a href="product_detail.php?id=<?= $related['id'] ?>" class="text-decoration-none text-dark">
                                <div style="font-weight: 600; margin-bottom: 10px;"><?= htmlspecialchars($related['name']) ?></div>
                                <div style="font-weight: 700;"><?= number_format($related_price) ?>₫</div>
                                <?php if ($related['discount_percent'] > 0): ?>
                                    <small style="color: #767676; text-decoration: line-through;">
                                        <?= number_format($related['price']) ?>₫
                                    </small>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include __DIR__ . "/includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedSizeId = null;
        let selectedSizeName = null;
        let maxQuantity = 1;

        function changeImage(src) {
            document.getElementById('mainImage').src = src;
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function selectSize(sizeId, sizeName, quantity) {
            // Remove selected class from all size options
            document.querySelectorAll('.size-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked size option
            event.target.classList.add('selected');
            
            // Update selected size info
            selectedSizeId = sizeId;
            selectedSizeName = sizeName;
            maxQuantity = quantity;
            
            document.getElementById('selectedSizeId').value = sizeId;
            document.getElementById('selectedSizeInfo').innerHTML = 
                `Đã chọn: Size ${sizeName} (Còn ${quantity} sản phẩm)`;
            
            // Update quantity input
            const quantityInput = document.getElementById('quantity');
            quantityInput.value = 1;
            quantityInput.max = Math.min(quantity, 10); // Max 10 items per order
            
            // Update quantity info
            updateQuantityInfo();
            
            // Enable/disable add to cart button
            document.getElementById('addToCartBtn').disabled = quantity <= 0;
        }

        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let currentQuantity = parseInt(quantityInput.value);
            let newQuantity = currentQuantity + change;
            
            if (newQuantity >= 1 && newQuantity <= maxQuantity && newQuantity <= 10) {
                quantityInput.value = newQuantity;
                updateQuantityInfo();
            }
        }

        function updateQuantityInfo() {
            const quantityInput = document.getElementById('quantity');
            const currentQuantity = parseInt(quantityInput.value);
            document.getElementById('quantityInfo').innerHTML = 
                `Tối đa: ${Math.min(maxQuantity, 10)} sản phẩm`;
        }

        function showSizeGuide() {
            alert('Hướng dẫn chọn size:\n\n- S: Người có cân nặng 50-60kg, chiều cao 1m50-1m65\n- M: Người có cân nặng 60-70kg, chiều cao 1m65-1m75\n- L: Người có cân nặng 70-80kg, chiều cao 1m75-1m85\n- XL: Người có cân nặng trên 80kg, chiều cao trên 1m85');
        }

        // Validate form before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!selectedSizeId) {
                e.preventDefault();
                alert('Vui lòng chọn kích cỡ!');
                return false;
            }
            
            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity < 1 || quantity > maxQuantity) {
                e.preventDefault();
                alert('Số lượng không hợp lệ!');
                return false;
            }
        });

        // Auto select first available size
        document.addEventListener('DOMContentLoaded', function() {
            const firstSizeOption = document.querySelector('.size-option');
            if (firstSizeOption) {
                const sizeId = firstSizeOption.getAttribute('data-size-id');
                const sizeName = firstSizeOption.textContent.trim();
                const quantity = parseInt(firstSizeOption.getAttribute('data-quantity'));
                selectSize(sizeId, sizeName, quantity);
            }
        });
    </script>
</body>
</html>