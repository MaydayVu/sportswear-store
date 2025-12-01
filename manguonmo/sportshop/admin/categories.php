<?php
session_start();
include "../config.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: /manguonmo/sportshop/index.php");
    exit();
}

// Hàm tạo slug
function create_slug($string) {
    $slug = preg_replace('/[^a-zA-Z0-9 -]/', '', $string);
    $slug = strtolower(str_replace(' ', '-', $slug));
    $slug = preg_replace('/-+/', '-', $slug);
    return $slug;
}

// Hàm upload ảnh
function upload_image($file, $upload_dir, $prefix = 'event_') {
    if ($file['error'] === 0) {
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . time() . "." . $file_extension;
        $target_file = $upload_dir . $filename;
        
        // Kiểm tra định dạng ảnh
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            return ['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)'];
        }
        
        // Kiểm tra kích thước file (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Kích thước file không được vượt quá 5MB'];
        }
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return ['success' => true, 'filename' => $filename];
        }
    }
    return ['success' => false, 'message' => 'Có lỗi xảy ra khi upload ảnh'];
}

// Xử lý thêm danh mục
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = create_slug($name);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $event_description = trim($_POST['event_description']);
    $display_order = intval($_POST['display_order']);
    $event_start_date = !empty($_POST['event_start_date']) ? $_POST['event_start_date'] : null;
    $event_end_date = !empty($_POST['event_end_date']) ? $_POST['event_end_date'] : null;
    
    // Xử lý upload ảnh sự kiện
    $event_image = '';
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === 0) {
        $upload_dir = "../assets/images/categories/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $upload_result = upload_image($_FILES['event_image'], $upload_dir);
        if ($upload_result['success']) {
            $event_image = $upload_result['filename'];
        } else {
            $_SESSION['message'] = $upload_result['message'];
            $_SESSION['message_type'] = "error";
        }
    }

    // Kiểm tra trùng tên
    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['message'] = "Tên danh mục đã tồn tại!";
        $_SESSION['message_type'] = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, is_featured, event_image, event_start_date, event_end_date, event_description, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissssi", $name, $slug, $is_featured, $event_image, $event_start_date, $event_end_date, $event_description, $display_order);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Thêm danh mục thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Có lỗi xảy ra khi thêm danh mục!";
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Xử lý sửa danh mục
if (isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $slug = create_slug($name);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $event_description = trim($_POST['event_description']);
    $display_order = intval($_POST['display_order']);
    $event_start_date = !empty($_POST['event_start_date']) ? $_POST['event_start_date'] : null;
    $event_end_date = !empty($_POST['event_end_date']) ? $_POST['event_end_date'] : null;
    
    // Lấy thông tin danh mục hiện tại
    $current_stmt = $conn->prepare("SELECT event_image FROM categories WHERE id = ?");
    $current_stmt->bind_param("i", $id);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();
    $current_category = $current_result->fetch_assoc();
    $current_stmt->close();
    
    $event_image = $current_category['event_image'];
    
    // Xử lý upload ảnh mới
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === 0) {
        $upload_dir = "../assets/images/categories/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $upload_result = upload_image($_FILES['event_image'], $upload_dir);
        if ($upload_result['success']) {
            // Xóa ảnh cũ nếu có
            if ($event_image && file_exists($upload_dir . $event_image)) {
                unlink($upload_dir . $event_image);
            }
            $event_image = $upload_result['filename'];
        } else {
            $_SESSION['message'] = $upload_result['message'];
            $_SESSION['message_type'] = "error";
        }
    }
    
    // Xử lý xóa ảnh nếu được chọn
    if (isset($_POST['remove_event_image']) && $_POST['remove_event_image'] == 1) {
        if ($event_image && file_exists("../assets/images/categories/" . $event_image)) {
            unlink("../assets/images/categories/" . $event_image);
        }
        $event_image = '';
    }

    // Kiểm tra trùng tên (trừ danh mục hiện tại)
    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $check_stmt->bind_param("si", $name, $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['message'] = "Tên danh mục đã tồn tại!";
        $_SESSION['message_type'] = "error";
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, is_featured = ?, event_image = ?, event_start_date = ?, event_end_date = ?, event_description = ?, display_order = ? WHERE id = ?");
        $stmt->bind_param("ssissssii", $name, $slug, $is_featured, $event_image, $event_start_date, $event_end_date, $event_description, $display_order, $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Cập nhật danh mục thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Có lỗi xảy ra khi cập nhật danh mục!";
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Xử lý xóa danh mục
if (isset($_GET['delete_id'])) {
    $category_id = $_GET['delete_id'];
    
    // Kiểm tra xem danh mục có sản phẩm không
    $check_products = $conn->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ?");
    $check_products->bind_param("i", $category_id);
    $check_products->execute();
    $result = $check_products->get_result();
    $product_count = $result->fetch_assoc()['product_count'];
    $check_products->close();
    
    if ($product_count > 0) {
        $_SESSION['message'] = "Không thể xóa danh mục vì có $product_count sản phẩm đang thuộc danh mục này!";
        $_SESSION['message_type'] = "error";
    } else {
        // Lấy thông tin ảnh để xóa
        $image_stmt = $conn->prepare("SELECT event_image FROM categories WHERE id = ?");
        $image_stmt->bind_param("i", $category_id);
        $image_stmt->execute();
        $image_result = $image_stmt->get_result();
        $category_data = $image_result->fetch_assoc();
        $image_stmt->close();
        
        // Xóa ảnh nếu có
        if ($category_data['event_image'] && file_exists("../assets/images/categories/" . $category_data['event_image'])) {
            unlink("../assets/images/categories/" . $category_data['event_image']);
        }
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Xóa danh mục thành công!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Có lỗi xảy ra khi xóa danh mục!";
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
}

// Lấy danh sách danh mục
$categories_result = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.display_order ASC, c.name ASC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Danh mục - Sport Fashion</title>
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

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 25px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
            border-radius: 12px 12px 0 0 !important;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        /* Tables */
        .table {
            margin: 0;
        }

        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: var(--dark-color);
            padding: 15px;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
        }

        /* Action Buttons */
        .btn-action {
            padding: 6px 12px;
            margin: 2px;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-edit {
            background: var(--warning-color);
            color: white;
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-action:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }

        /* Badge */
        .product-badge {
            background: var(--primary-color);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .featured-badge {
            background: var(--warning-color);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Forms */
        .form-container {
            max-width: 500px;
        }

        .event-image-preview {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }

        .featured-section {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
            <a href="categories.php" class="active">
                <i class="fas fa-folder"></i>
                <span>Quản lý danh mục</span>
            </a>
            <a href="products.php">
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
            <h1>📁 Quản lý Danh mục</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION["fullname"], 0, 1)); ?>
                </div>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- Thông báo -->
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        endif; ?>

        <div class="row">
            <!-- Form Thêm/Sửa Danh mục -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-<?php echo isset($_GET['edit']) ? 'edit' : 'plus'; ?> me-2"></i>
                            <?php echo isset($_GET['edit']) ? 'Sửa Danh mục' : 'Thêm Danh mục'; ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $edit_category = null;
                        if (isset($_GET['edit'])) {
                            $edit_id = $_GET['edit'];
                            $edit_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
                            $edit_stmt->bind_param("i", $edit_id);
                            $edit_stmt->execute();
                            $edit_result = $edit_stmt->get_result();
                            $edit_category = $edit_result->fetch_assoc();
                            $edit_stmt->close();
                        }
                        ?>
                        
                        <form method="POST" class="form-container" enctype="multipart/form-data">
                            <?php if ($edit_category): ?>
                                <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       name="name" 
                                       value="<?php echo $edit_category ? htmlspecialchars($edit_category['name']) : ''; ?>" 
                                       required 
                                       placeholder="Nhập tên danh mục">
                            </div>

                            <!-- Phần cấu hình sự kiện nổi bật -->
                            <div class="featured-section">
                                <h6 class="mb-3"><i class="fas fa-star text-warning me-2"></i>Cấu hình sự kiện nổi bật</h6>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" 
                                               <?php echo ($edit_category && $edit_category['is_featured']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_featured">
                                            <strong>Hiển thị trên trang chủ</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Kích hoạt để hiển thị danh mục này nổi bật trên trang chủ</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Ảnh sự kiện</label>
                                    <input type="file" class="form-control" name="event_image" accept="image/*">
                                    <?php if ($edit_category && $edit_category['event_image']): ?>
                                        <div class="mt-2">
                                            <img src="../assets/images/categories/<?php echo htmlspecialchars($edit_category['event_image']); ?>" 
                                                 alt="Ảnh sự kiện" class="event-image-preview">
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="remove_event_image" id="remove_event_image" value="1">
                                                <label class="form-check-label text-danger" for="remove_event_image">
                                                    <i class="fas fa-trash me-1"></i>Xóa ảnh hiện tại
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">Kích thước đề xuất: 400x200px (tỷ lệ 2:1)</small>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Ngày bắt đầu</label>
                                        <input type="date" class="form-control" name="event_start_date" 
                                               value="<?php echo $edit_category ? $edit_category['event_start_date'] : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ngày kết thúc</label>
                                        <input type="date" class="form-control" name="event_end_date" 
                                               value="<?php echo $edit_category ? $edit_category['event_end_date'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Mô tả sự kiện</label>
                                    <textarea class="form-control" name="event_description" rows="2" 
                                              placeholder="Mô tả ngắn về sự kiện..."><?php echo $edit_category ? htmlspecialchars($edit_category['event_description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Thứ tự hiển thị</label>
                                    <input type="number" class="form-control" name="display_order" 
                                           value="<?php echo $edit_category ? $edit_category['display_order'] : '0'; ?>" min="0">
                                    <small class="text-muted">Số càng nhỏ hiển thị càng trước</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Slug (tự động)</label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?php echo $edit_category ? htmlspecialchars($edit_category['slug']) : ''; ?>" 
                                       disabled 
                                       style="background-color: #f8f9fa;">
                                <small class="text-muted">Slug sẽ được tạo tự động từ tên danh mục</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <?php if ($edit_category): ?>
                                    <button type="submit" name="edit_category" class="btn btn-warning">
                                        <i class="fas fa-save me-2"></i>Cập nhật
                                    </button>
                                    <a href="categories.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Hủy
                                    </a>
                                <?php else: ?>
                                    <button type="submit" name="add_category" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Thêm danh mục
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách Danh mục -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list me-2"></i>Danh sách Danh mục</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên danh mục</th>
                                        <th>Slug</th>
                                        <th>Số sản phẩm</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($categories_result->num_rows > 0): ?>
                                        <?php while($category = $categories_result->fetch_assoc()): 
                                            $is_active_event = true;
                                            if ($category['event_start_date'] && $category['event_start_date'] > date('Y-m-d')) {
                                                $is_active_event = false;
                                            }
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $category['id']; ?></strong></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></div>
                                                <?php if ($category['is_featured']): ?>
                                                    <small class="text-success">
                                                        <i class="fas fa-star me-1"></i>Danh mục nổi bật
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($category['slug']); ?></code>
                                            </td>
                                            <td>
                                                <span class="product-badge">
                                                    <?php echo $category['product_count']; ?> sản phẩm
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($category['is_featured']): ?>
                                                    <span class="featured-badge">
                                                        <i class="fas fa-star me-1"></i>Nổi bật
                                                    </span>
                                                    <?php if (!$is_active_event): ?>
                                                        <br><small class="text-warning">Sắp diễn ra</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Thường</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?edit=<?php echo $category['id']; ?>" class="btn-action btn-edit">
                                                    <i class="fas fa-edit"></i> Sửa
                                                </a>
                                                <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                                    <i class="fas fa-trash"></i> Xóa
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-folder-open fa-3x mb-3"></i><br>
                                                Chưa có danh mục nào
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Thống kê -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie me-2"></i>Thống kê</h3>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h4 class="text-primary"><?php echo $categories_result->num_rows; ?></h4>
                                    <p class="text-muted mb-0">Tổng số danh mục</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h4 class="text-success">
                                        <?php
                                        $total_products = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
                                        echo $total_products;
                                        ?>
                                    </h4>
                                    <p class="text-muted mb-0">Tổng số sản phẩm</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h4 class="text-warning">
                                        <?php
                                        $featured_count = $conn->query("SELECT COUNT(*) as total FROM categories WHERE is_featured = 1")->fetch_assoc()['total'];
                                        echo $featured_count;
                                        ?>
                                    </h4>
                                    <p class="text-muted mb-0">Danh mục nổi bật</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h4 class="text-info">
                                        <?php
                                        $active_events = $conn->query("SELECT COUNT(*) as total FROM categories WHERE is_featured = 1 AND (event_end_date IS NULL OR event_end_date >= CURDATE())")->fetch_assoc()['total'];
                                        echo $active_events;
                                        ?>
                                    </h4>
                                    <p class="text-muted mb-0">Sự kiện đang diễn ra</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(categoryId, categoryName) {
            if (confirm(`Bạn có chắc chắn muốn xóa danh mục "${categoryName}"?`)) {
                window.location.href = '?delete_id=' + categoryId;
            }
        }

        // Auto generate slug from name
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.querySelector('input[name="name"]');
            const slugInput = document.querySelector('input[disabled]');
            
            if (nameInput && slugInput) {
                nameInput.addEventListener('input', function() {
                    const slug = this.value
                        .toLowerCase()
                        .replace(/[^a-z0-9 -]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .trim();
                    slugInput.value = slug;
                });
            }

            // Preview image before upload
            const eventImageInput = document.querySelector('input[name="event_image"]');
            if (eventImageInput) {
                eventImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            // Remove existing preview if any
                            const existingPreview = document.querySelector('.event-image-preview');
                            if (existingPreview) {
                                existingPreview.src = e.target.result;
                            } else {
                                // Create new preview
                                const preview = document.createElement('img');
                                preview.src = e.target.result;
                                preview.className = 'event-image-preview mt-2';
                                preview.alt = 'Preview';
                                eventImageInput.parentNode.appendChild(preview);
                            }
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
        });

        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.createElement('button');
            menuToggle.innerHTML = '☰';
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
    </script>
</body>
</html>
<?php
$conn->close();
?>