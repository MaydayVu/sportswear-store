<?php
session_start();
include "config.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php?redirect=wishlist.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Xử lý thêm/xóa sản phẩm khỏi wishlist
if (isset($_GET['action']) && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $action = $_GET['action'];
    
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
            }
            $insert_stmt->close();
        } else {
            $_SESSION['message'] = "Sản phẩm đã có trong danh sách yêu thích!";
            $_SESSION['message_type'] = "warning";
        }
        $check_stmt->close();
        
    } elseif ($action === 'remove') {
        // Xóa khỏi wishlist
        $delete_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete_stmt->bind_param("ii", $user_id, $product_id);
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = "Đã xóa khỏi danh sách yêu thích!";
            $_SESSION['message_type'] = "success";
        }
        $delete_stmt->close();
    }
    
    // Quay lại trang trước đó
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'wishlist.php'));
    exit();
}

// Xử lý xóa nhiều sản phẩm
if (isset($_POST['remove_selected'])) {
    if (!empty($_POST['selected_items'])) {
        $placeholders = implode(',', array_fill(0, count($_POST['selected_items']), '?'));
        $types = str_repeat('i', count($_POST['selected_items']));
        
        $delete_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id IN ($placeholders)");
        $delete_stmt->bind_param("i" . $types, $user_id, ...$_POST['selected_items']);
        
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = "Đã xóa " . count($_POST['selected_items']) . " sản phẩm khỏi danh sách yêu thích!";
            $_SESSION['message_type'] = "success";
        }
        $delete_stmt->close();
    }
    
    header("Location: wishlist.php");
    exit();
}

// Xử lý xóa tất cả
if (isset($_POST['clear_all'])) {
    $delete_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ?");
    $delete_stmt->bind_param("i", $user_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['message'] = "Đã xóa tất cả sản phẩm khỏi danh sách yêu thích!";
        $_SESSION['message_type'] = "success";
    }
    $delete_stmt->close();
    
    header("Location: wishlist.php");
    exit();
}

// Lấy danh sách sản phẩm trong wishlist
$wishlist_query = "
    SELECT p.*, c.name AS category_name, w.created_at as added_date,
           (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) as total_quantity
    FROM wishlist w
    INNER JOIN products p ON w.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
";

$wishlist_stmt = $conn->prepare($wishlist_query);
$wishlist_stmt->bind_param("i", $user_id);
$wishlist_stmt->execute();
$wishlist_result = $wishlist_stmt->get_result();

// Đếm tổng số sản phẩm trong wishlist
$total_wishlist_items = $wishlist_result->num_rows;
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
        :root {
            --primary-color: #000;
            --accent-color: #e4002b;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
            --heart-color: #e4002b;
        }

        .wishlist-page {
            padding: 80px 0 60px;
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

        .wishlist-stats {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            text-align: center;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Wishlist Actions */
        .wishlist-actions {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-wishlist-action {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: white;
            color: #495057;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-wishlist-action:hover {
            background: #f8f9fa;
            color: var(--primary-color);
        }

        .btn-clear-all {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .btn-clear-all:hover {
            background: #c82333;
            color: white;
        }

        /* Wishlist Items */
        .wishlist-items {
            display: grid;
            gap: 20px;
        }

        .wishlist-item {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .wishlist-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            border-color: var(--accent-color);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .item-checkbox {
            margin-right: 15px;
        }

        .item-content {
            display: flex;
            gap: 20px;
            flex: 1;
        }

        .item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
        }

        .item-category {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .item-name a {
            color: inherit;
            text-decoration: none;
        }

        .item-name a:hover {
            color: var(--accent-color);
        }

        .item-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .item-brand {
            font-size: 0.9rem;
            color: #666;
            font-weight: 600;
        }

        .item-gender {
            font-size: 0.9rem;
            color: #666;
            text-transform: capitalize;
        }

        .item-added {
            font-size: 0.8rem;
            color: #999;
        }

        .item-price-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .current-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-color);
        }

        .original-price {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
        }

        .discount-badge {
            background: var(--accent-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }

        .btn-view {
            background: var(--primary-color);
            color: white;
        }

        .btn-view:hover {
            background: #333;
            color: white;
        }

        .btn-remove {
            background: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .btn-remove:hover {
            background: #dc3545;
            color: white;
        }

        .btn-add-cart {
            background: var(--accent-color);
            color: white;
        }

        .btn-add-cart:hover {
            background: #c40023;
            color: white;
        }

        .stock-status {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .stock-in {
            color: #28a745;
        }

        .stock-out {
            color: #dc3545;
        }

        /* Empty State */
        .empty-wishlist {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .empty-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-heart {
            color: var(--heart-color);
        }

        /* Bulk Actions Form */
        .bulk-actions-form {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: none;
        }

        .bulk-actions-form.active {
            display: block;
        }

        .bulk-actions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .selected-count {
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .item-content {
                flex-direction: column;
            }

            .item-image {
                width: 100%;
                height: 200px;
            }

            .wishlist-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons {
                justify-content: center;
            }

            .item-actions {
                flex-direction: column;
            }

            .btn-action {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 40px 0;
            }

            .wishlist-item {
                padding: 20px;
            }

            .item-header {
                flex-direction: column;
                gap: 10px;
            }

            .item-checkbox {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="wishlist-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">❤️ Danh sách yêu thích</h1>
                <p class="page-subtitle">
                    Lưu trữ các sản phẩm bạn yêu thích và mua sau
                </p>
            </div>
        </div>

        <div class="container">
            <!-- Thông báo -->
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'warning'; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            endif; ?>

            <!-- Wishlist Stats -->
            <div class="wishlist-stats">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= $total_wishlist_items ?></div>
                        <div class="stat-label">Sản phẩm yêu thích</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php
                            // Tính tổng giá trị wishlist
                            $total_value = 0;
                            $wishlist_result->data_seek(0);
                            while ($item = $wishlist_result->fetch_assoc()) {
                                $current_price = $item['price'];
                                if ($item['discount_percent'] > 0) {
                                    $current_price = $item['price'] * (1 - $item['discount_percent'] / 100);
                                }
                                $total_value += $current_price;
                            }
                            echo number_format($total_value);
                            ?>₫
                        </div>
                        <div class="stat-label">Tổng giá trị</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php
                            // Đếm số sản phẩm đang giảm giá
                            $discount_count = 0;
                            $wishlist_result->data_seek(0);
                            while ($item = $wishlist_result->fetch_assoc()) {
                                if ($item['discount_percent'] > 0) {
                                    $discount_count++;
                                }
                            }
                            echo $discount_count;
                            ?>
                        </div>
                        <div class="stat-label">Đang giảm giá</div>
                    </div>
                </div>
            </div>

            <?php if ($total_wishlist_items > 0): ?>
                <!-- Bulk Actions Form -->
                <form method="POST" class="bulk-actions-form" id="bulkActionsForm">
                    <div class="bulk-actions-header">
                        <div class="selected-count" id="selectedCount">Đã chọn 0 sản phẩm</div>
                        <div class="action-buttons">
                            <button type="submit" name="remove_selected" class="btn-wishlist-action btn-remove">
                                <i class="fas fa-trash me-1"></i>Xóa đã chọn
                            </button>
                            <button type="button" class="btn-wishlist-action" onclick="clearSelection()">
                                <i class="fas fa-times me-1"></i>Bỏ chọn
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Wishlist Actions -->
                <div class="wishlist-actions">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label" for="selectAll">
                            Chọn tất cả
                        </label>
                    </div>
                    <div class="action-buttons">
                        <button type="button" class="btn-wishlist-action" onclick="showBulkActions()">
                            <i class="fas fa-tasks me-1"></i>Thao tác hàng loạt
                        </button>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="clear_all" class="btn-wishlist-action btn-clear-all" 
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm khỏi danh sách yêu thích?')">
                                <i class="fas fa-trash-alt me-1"></i>Xóa tất cả
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Wishlist Items -->
                <form method="POST" id="wishlistForm">
                    <div class="wishlist-items">
                        <?php 
                        $wishlist_result->data_seek(0);
                        while ($item = $wishlist_result->fetch_assoc()): 
                            $current_price = $item['price'];
                            $has_discount = $item['discount_percent'] > 0;
                            if ($has_discount) {
                                $current_price = $item['price'] * (1 - $item['discount_percent'] / 100);
                            }
                            $added_date = date('d/m/Y', strtotime($item['added_date']));
                        ?>
                        <div class="wishlist-item">
                            <div class="item-header">
                                <div class="form-check item-checkbox">
                                    <input class="form-check-input item-checkbox" type="checkbox" 
                                           name="selected_items[]" value="<?= $item['id'] ?>" 
                                           onchange="updateSelection()">
                                </div>
                                <div class="item-content">
                                    <img src="assets/images/products/<?= htmlspecialchars($item['image']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                         class="item-image"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                                    <div class="item-details">
                                        <div class="item-category"><?= htmlspecialchars($item['category_name']) ?></div>
                                        <h3 class="item-name">
                                            <a href="product_detail.php?id=<?= $item['id'] ?>">
                                                <?= htmlspecialchars($item['name']) ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="item-meta">
                                            <?php if ($item['brand']): ?>
                                                <span class="item-brand"><?= htmlspecialchars($item['brand']) ?></span>
                                            <?php endif; ?>
                                            <span class="item-gender">
                                                <?= $item['gender'] == 'nam' ? 'Nam' : ($item['gender'] == 'nu' ? 'Nữ' : 'Unisex') ?>
                                            </span>
                                            <span class="item-added">
                                                <i class="far fa-clock me-1"></i>Thêm ngày <?= $added_date ?>
                                            </span>
                                        </div>

                                        <div class="stock-status <?= $item['total_quantity'] > 0 ? 'stock-in' : 'stock-out' ?>">
                                            <i class="fas fa-<?= $item['total_quantity'] > 0 ? 'check' : 'times' ?> me-1"></i>
                                            <?= $item['total_quantity'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                                        </div>

                                        <div class="item-price-section">
                                            <span class="current-price"><?= number_format($current_price) ?>₫</span>
                                            <?php if ($has_discount): ?>
                                                <span class="original-price"><?= number_format($item['price']) ?>₫</span>
                                                <span class="discount-badge">-<?= $item['discount_percent'] ?>%</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="item-actions">
                                            <a href="product_detail.php?id=<?= $item['id'] ?>" class="btn-action btn-view">
                                                <i class="fas fa-eye me-1"></i>Xem chi tiết
                                            </a>
                                            <?php if ($item['total_quantity'] > 0): ?>
                                                <a href="cart.php?action=add&product_id=<?= $item['id'] ?>" 
                                                   class="btn-action btn-add-cart">
                                                    <i class="fas fa-shopping-cart me-1"></i>Thêm vào giỏ
                                                </a>
                                            <?php endif; ?>
                                            <a href="wishlist.php?action=remove&product_id=<?= $item['id'] ?>" 
                                               class="btn-action btn-remove"
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi danh sách yêu thích?')">
                                                <i class="fas fa-trash me-1"></i>Xóa
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </form>

            <?php else: ?>
                <!-- Empty Wishlist -->
                <div class="empty-wishlist">
                    <div class="empty-icon">
                        <i class="fas fa-heart empty-heart"></i>
                    </div>
                    <h3>Danh sách yêu thích trống</h3>
                    <p class="text-muted mb-4">Bạn chưa có sản phẩm nào trong danh sách yêu thích.</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="products.php" class="btn btn-dark btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Tiếp tục mua sắm
                        </a>
                        <a href="categories.php" class="btn btn-outline-dark btn-lg">
                            <i class="fas fa-th-large me-2"></i>Khám phá danh mục
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelection();
        });

        // Update selection count
        function updateSelection() {
            const selectedItems = document.querySelectorAll('.item-checkbox:checked');
            const selectedCount = selectedItems.length;
            document.getElementById('selectedCount').textContent = `Đã chọn ${selectedCount} sản phẩm`;
            
            // Update select all checkbox
            const totalItems = document.querySelectorAll('.item-checkbox').length - 1; // Exclude selectAll itself
            document.getElementById('selectAll').checked = selectedCount === totalItems;
        }

        // Show bulk actions form
        function showBulkActions() {
            const selectedItems = document.querySelectorAll('.item-checkbox:checked');
            if (selectedItems.length > 0) {
                document.getElementById('bulkActionsForm').classList.add('active');
            } else {
                alert('Vui lòng chọn ít nhất một sản phẩm để thực hiện thao tác.');
            }
        }

        // Clear selection
        function clearSelection() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('bulkActionsForm').classList.remove('active');
            updateSelection();
        }

        // Auto-submit bulk actions form when remove selected is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const bulkForm = document.getElementById('bulkActionsForm');
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
                    if (selectedItems.length === 0) {
                        e.preventDefault();
                        alert('Vui lòng chọn ít nhất một sản phẩm để xóa.');
                        return false;
                    }
                    
                    if (!confirm(`Bạn có chắc chắn muốn xóa ${selectedItems.length} sản phẩm khỏi danh sách yêu thích?`)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });

        // Lazy load images
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = document.querySelectorAll('.item-image');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.src;
                        observer.unobserve(img);
                    }
                });
            });

            lazyImages.forEach(img => imageObserver.observe(img));
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>