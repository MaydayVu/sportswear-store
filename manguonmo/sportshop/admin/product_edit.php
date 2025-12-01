<?php
// Hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "../config.php";

// Nếu chưa login admin → đá về index
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

// Lấy ID sản phẩm
$id = (int)$_GET['id'];

// Lấy thông tin sản phẩm
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$prod = $result->fetch_assoc();

if (!$prod) {
    die("Sản phẩm không tồn tại!");
}

// Lấy danh mục
$cats = $conn->query("SELECT id, name FROM categories");
if (!$cats) {
    die("Lỗi SQL categories: " . $conn->error);
}

// Lấy các size hiện có của sản phẩm
$sizes_result = $conn->query("SELECT * FROM product_sizes WHERE product_id = $id ORDER BY size");

// Khi cập nhật
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["submit"])) {
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        $name       = $_POST["ten"];
        $brand      = $_POST["thuonghieu"];
        $category   = (int)$_POST["phanloai"]; 
        $price      = (float)$_POST["gia"];
        $gender     = $_POST["gioitinh"];
        $sport_type = $_POST["thethao"];
        $discount   = (int)$_POST["giamgia"];
        $desc       = $_POST["mota"];
        $material   = $_POST["chatlieu"];
        $featured   = isset($_POST["noibat"]) ? 1 : 0;

        // Upload ảnh
        $imageName = $prod['image']; // Giữ ảnh cũ nếu không upload mới
        
        if (!empty($_FILES["image"]["name"])) {
            if (!is_dir("../assets/images/products")) {
                mkdir("../assets/images/products", 0777, true);
            }

            $imageName = time() . "_" . basename($_FILES["image"]["name"]);
            $uploadPath = "../assets/images/products/" . $imageName;

            // Kiểm tra loại file
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedTypes)) {
                throw new Exception("Chỉ chấp nhận file ảnh JPG, JPEG, PNG, GIF, WEBP");
            }

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $uploadPath)) {
                // Xóa ảnh cũ nếu có
                if (!empty($prod['image']) && file_exists("../assets/images/products/" . $prod['image'])) {
                    unlink("../assets/images/products/" . $prod['image']);
                }
            } else {
                throw new Exception("Không thể upload ảnh!");
            }
        }

        // Cập nhật sản phẩm
        $sql = "
            UPDATE products 
            SET name = ?, brand = ?, category_id = ?, gender = ?, sport_type = ?, price = ?, 
                discount_percent = ?, description = ?, material = ?, 
                featured = ?, image = ?
            WHERE id = ?
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Lỗi prepare: " . $conn->error);
        }

        $stmt->bind_param(
            "ssisssdssisi",
            $name,          // string
            $brand,         // string  
            $category,      // int
            $gender,        // string
            $sport_type,    // string
            $price,         // double
            $discount,      // int
            $desc,          // string
            $material,      // string
            $featured,      // int
            $imageName,     // string
            $id             // int
        );

        if (!$stmt->execute()) {
            throw new Exception("Lỗi execute: " . $stmt->error);
        }
        $stmt->close();

        // Xóa các size cũ
        $delete_sizes = $conn->prepare("DELETE FROM product_sizes WHERE product_id = ?");
        $delete_sizes->bind_param("i", $id);
        if (!$delete_sizes->execute()) {
            throw new Exception("Lỗi xóa size cũ: " . $delete_sizes->error);
        }
        $delete_sizes->close();

        // Thêm các size mới
        if (isset($_POST["sizes"]) && is_array($_POST["sizes"])) {
            $size_stmt = $conn->prepare("INSERT INTO product_sizes (product_id, size, quantity) VALUES (?, ?, ?)");
            
            foreach ($_POST["sizes"] as $size_data) {
                $size_parts = explode("|", $size_data);
                $size_name = $size_parts[0];
                $size_quantity = (int)$size_parts[1];
                
                $size_stmt->bind_param("isi", $id, $size_name, $size_quantity);
                if (!$size_stmt->execute()) {
                    throw new Exception("Lỗi thêm size: " . $size_stmt->error);
                }
            }
            $size_stmt->close();
        } else {
            throw new Exception("Vui lòng chọn ít nhất một kích thước");
        }

        // Commit transaction
        $conn->commit();
        header("Location: products.php?success=edit");
        exit();

    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa Sản Phẩm - Admin Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--dark-color);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 0;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #b8c7ce;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-color);
        }

        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-color);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s;
        }

        /* Header */
        .admin-header {
            background: white;
            padding: 20px 30px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .logout-btn {
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.1);
        }

        /* Buttons */
        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 15px;
        }

        .btn-back:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }

        /* Size Management */
        .size-management {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .size-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .size-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .size-select {
            min-width: 120px;
        }

        .quantity-input {
            max-width: 130px;
        }

        .btn-add-size {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-size:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .btn-remove-size {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .btn-remove-size:hover {
            background: #c0392b;
            transform: scale(1.05);
        }

        .size-list {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 15px;
        }

        .no-sizes {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }

        /* Sport Type Badges */
        .sport-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin: 2px;
        }

        .sport-none { background: #95a5a6; color: white; }
        .sport-football { background: #e74c3c; color: white; }
        .sport-running { background: #3498db; color: white; }
        .sport-basketball { background: #e67e22; color: white; }
        .sport-training { background: #9b59b6; color: white; }
        .sport-motosport { background: #34495e; color: white; }
        .sport-court_sports { background: #27ae60; color: white; }

        /* Form Check */
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        /* Alert */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Image Preview */
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header h2,
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .form-container {
                padding: 20px;
            }
            
            .size-item {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            
            .size-select, .quantity-input {
                min-width: auto;
                max-width: none;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .form-container {
                padding: 15px;
            }
            
            .btn-back, .btn-submit {
                width: 100%;
                justify-content: center;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🏢 ADMIN PANEL</h2>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Trang quản trị</span>
            </a>
            <a href="categories.php">
                <i class="fas fa-folder"></i>
                <span>Quản lý danh mục</span>
            </a>
            <a href="products.php" class="active">
                <i class="fas fa-shopping-bag"></i>
                <span>Quản lý sản phẩm</span>
            </a>
            <a href="blog.php">
                <i class="fas fa-blog"></i>
                <span>Quản lý bài viết</span>
            </a>
            <a href="orders.php">
                <i class="fas fa-shipping-fast"></i>
                <span>Quản lý đơn hàng</span>
            </a>
            <a href="../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- HEADER -->
        <div class="admin-header">
            <h1><i class="fas fa-edit me-2"></i>Chỉnh sửa Sản Phẩm</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION["fullname"], 0, 1)); ?>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- Hiển thị lỗi -->
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <!-- Thông tin cơ bản -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>Thông tin cơ bản
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ten" value="<?= htmlspecialchars($prod['name']) ?>" required placeholder="Nhập tên sản phẩm">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Thương hiệu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="thuonghieu" value="<?= htmlspecialchars($prod['brand'] ?? '') ?>" required placeholder="Nhập thương hiệu">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-select" name="phanloai" required>
                                <option value="">Chọn danh mục</option>
                                <?php 
                                $cats->data_seek(0); // Reset pointer
                                while($cat = $cats->fetch_assoc()): 
                                ?>
                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $prod['category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giới tính <span class="text-danger">*</span></label>
                            <select class="form-select" name="gioitinh" required>
                                <option value="nam" <?= $prod['gender'] == 'nam' ? 'selected' : '' ?>>Nam</option>
                                <option value="nu" <?= $prod['gender'] == 'nu' ? 'selected' : '' ?>>Nữ</option>
                                <option value="unisex" <?= $prod['gender'] == 'unisex' ? 'selected' : '' ?>>Unisex</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Loại thể thao</label>
                            <select class="form-select" name="thethao">
                                <option value="none" <?= ($prod['sport_type'] ?? 'none') == 'none' ? 'selected' : '' ?>>Không có</option>
                                <option value="football" <?= ($prod['sport_type'] ?? 'none') == 'football' ? 'selected' : '' ?>>Bóng đá</option>
                                <option value="running" <?= ($prod['sport_type'] ?? 'none') == 'running' ? 'selected' : '' ?>>Chạy bộ</option>
                                <option value="basketball" <?= ($prod['sport_type'] ?? 'none') == 'basketball' ? 'selected' : '' ?>>Bóng rổ</option>
                                <option value="training" <?= ($prod['sport_type'] ?? 'none') == 'training' ? 'selected' : '' ?>>Tập luyện</option>
                                <option value="motosport" <?= ($prod['sport_type'] ?? 'none') == 'motosport' ? 'selected' : '' ?>>Motosport</option>
                                <option value="court_sports" <?= ($prod['sport_type'] ?? 'none') == 'court_sports' ? 'selected' : '' ?>>Thể thao trên sân</option>
                            </select>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <span class="sport-badge sport-none"><i class="fas fa-times"></i> Không có</span>
                                    <span class="sport-badge sport-football"><i class="fas fa-futbol"></i> Bóng đá</span>
                                    <span class="sport-badge sport-running"><i class="fas fa-running"></i> Chạy bộ</span>
                                    <span class="sport-badge sport-basketball"><i class="fas fa-basketball-ball"></i> Bóng rổ</span>
                                    <span class="sport-badge sport-training"><i class="fas fa-dumbbell"></i> Tập luyện</span>
                                    <span class="sport-badge sport-motosport"><i class="fas fa-motorcycle"></i> Motosport</span>
                                    <span class="sport-badge sport-court_sports"><i class="fas fa-table-tennis"></i> Sân</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Giá và khuyến mãi -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-tag"></i>Giá và khuyến mãi
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="gia" step="1000" value="<?= $prod['price'] ?>" required placeholder="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giảm giá (%)</label>
                            <input type="number" class="form-control" name="giamgia" value="<?= $prod['discount_percent'] ?>" min="0" max="100" placeholder="0">
                        </div>
                    </div>
                </div>

                <!-- Quản lý Size -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-ruler-combined"></i>Quản lý Kích thước & Số lượng
                    </h3>
                    
                    <div class="size-management">
                        <div class="mb-3">
                            <button type="button" class="btn-add-size" id="addSizeBtn">
                                <i class="fas fa-plus-circle"></i> Thêm kích thước
                            </button>
                            <small class="text-muted ms-2">Thêm tất cả các size có sẵn cho sản phẩm</small>
                        </div>

                        <div class="size-list" id="sizeList">
                            <?php if ($sizes_result->num_rows > 0): ?>
                                <?php while($size = $sizes_result->fetch_assoc()): ?>
                                    <div class="size-item">
                                        <div class="flex-grow-1">
                                            <select class="form-select size-select" name="size_names[]" required>
                                                <option value="">Chọn size</option>
                                                <?php 
                                                $availableSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45'];
                                                foreach($availableSizes as $availSize): 
                                                ?>
                                                    <option value="<?= $availSize ?>" <?= $availSize == $size['size'] ? 'selected' : '' ?>>
                                                        Size <?= $availSize ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <input type="number" class="form-control quantity-input" name="size_quantities[]" 
                                                   min="0" value="<?= $size['quantity'] ?>" placeholder="Số lượng" required>
                                        </div>
                                        <div>
                                            <button type="button" class="btn-remove-size" onclick="removeSizeItem(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" name="sizes[]" value="<?= $size['size'] ?>|<?= $size['quantity'] ?>">
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-sizes" id="noSizesMessage">
                                    <i class="fas fa-ruler-horizontal fa-2x mb-3"></i>
                                    <div>Chưa có kích thước nào được thêm</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Thông tin chi tiết -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-edit"></i>Thông tin chi tiết
                    </h3>
                    
                    <div class="mb-3">
                        <label class="form-label">Mô tả sản phẩm</label>
                        <textarea class="form-control" name="mota" rows="4" placeholder="Mô tả chi tiết về sản phẩm..."><?= htmlspecialchars($prod['description']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Chất liệu</label>
                            <input type="text" class="form-control" name="chatlieu" value="<?= htmlspecialchars($prod['material'] ?? '') ?>" placeholder="VD: Cotton 100%, Polyester...">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <div class="mb-2">
                                <?php if ($prod['image']): ?>
                                    <img src="../assets/images/products/<?= $prod['image'] ?>" class="image-preview" id="currentImage">
                                <?php else: ?>
                                    <div class="image-preview bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image text-muted fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control" name="image" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Chấp nhận: JPG, JPEG, PNG, GIF, WEBP (Tối đa 5MB)</small>
                        </div>
                    </div>
                </div>

                <!-- Tùy chọn -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-cog"></i>Tùy chọn
                    </h3>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="noibat" value="1" id="featuredCheck" <?= $prod['featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="featuredCheck">
                            <i class="fas fa-star me-2"></i>Đánh dấu là sản phẩm nổi bật
                        </label>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex justify-content-between align-items-center pt-4 border-top">
                    <div>
                        <a href="products.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                    <div>
                        <button type="submit" name="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Cập nhật sản phẩm
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Các size có sẵn
        const availableSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45'];

        let sizeCounter = 0;

        document.addEventListener('DOMContentLoaded', function() {
            const addSizeBtn = document.getElementById('addSizeBtn');
            const sizeList = document.getElementById('sizeList');
            const noSizesMessage = document.getElementById('noSizesMessage');

            // Thêm size mới
            addSizeBtn.addEventListener('click', function() {
                addSizeItem();
            });

            function addSizeItem(size = '', quantity = 0) {
                // Ẩn thông báo không có size
                if (noSizesMessage) noSizesMessage.style.display = 'none';

                const sizeId = `size_${sizeCounter++}`;
                
                const sizeItem = document.createElement('div');
                sizeItem.className = 'size-item';
                sizeItem.innerHTML = `
                    <div class="flex-grow-1">
                        <select class="form-select size-select" name="size_names[]" required>
                            <option value="">Chọn size</option>
                            ${availableSizes.map(s => `<option value="${s}" ${s === size ? 'selected' : ''}>Size ${s}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <input type="number" class="form-control quantity-input" name="size_quantities[]" 
                               min="0" value="${quantity}" placeholder="Số lượng" required>
                    </div>
                    <div>
                        <button type="button" class="btn-remove-size" onclick="removeSizeItem(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <input type="hidden" name="sizes[]" value="${size}|${quantity}">
                `;

                sizeList.appendChild(sizeItem);

                // Thêm event listeners cho select và input
                const select = sizeItem.querySelector('select');
                const input = sizeItem.querySelector('input[type="number"]');
                const hidden = sizeItem.querySelector('input[type="hidden"]');

                function updateHiddenField() {
                    hidden.value = `${select.value}|${input.value}`;
                }

                select.addEventListener('change', updateHiddenField);
                input.addEventListener('input', updateHiddenField);
            }

            // Mobile menu toggle
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.createElement('button');
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            menuToggle.style.cssText = `
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: var(--dark-color);
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 5px;
                font-size: 1.2rem;
                display: none;
            `;
            
            document.body.appendChild(menuToggle);
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            function checkWidth() {
                if (window.innerWidth <= 768) {
                    menuToggle.style.display = 'block';
                } else {
                    menuToggle.style.display = 'none';
                    sidebar.classList.remove('active');
                }
            }
            
            checkWidth();
            window.addEventListener('resize', checkWidth);
        });

        // Hàm xóa size item
        function removeSizeItem(button) {
            const sizeItem = button.closest('.size-item');
            const sizeList = document.getElementById('sizeList');
            
            sizeItem.remove();

            // Hiển thị thông báo nếu không còn size nào
            if (sizeList.children.length === 1 && document.getElementById('noSizesMessage')) {
                document.getElementById('noSizesMessage').style.display = 'block';
            }
        }

        // Preview image khi chọn file mới
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentImage = document.getElementById('currentImage');
                    if (currentImage) {
                        currentImage.src = e.target.result;
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Validate form trước khi submit
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const sizeItems = document.querySelectorAll('.size-item');
            if (sizeItems.length === 0) {
                e.preventDefault();
                alert('Vui lòng thêm ít nhất một kích thước!');
                return false;
            }

            // Kiểm tra trùng size
            const selectedSizes = new Set();
            let hasDuplicate = false;

            document.querySelectorAll('select[name="size_names[]"]').forEach(select => {
                if (select.value) {
                    if (selectedSizes.has(select.value)) {
                        hasDuplicate = true;
                        select.style.borderColor = 'var(--danger-color)';
                    } else {
                        selectedSizes.add(select.value);
                        select.style.borderColor = '';
                    }
                }
            });

            if (hasDuplicate) {
                e.preventDefault();
                alert('Không được chọn trùng kích thước! Vui lòng kiểm tra lại.');
                return false;
            }
        });
    </script>
</body>
</html>