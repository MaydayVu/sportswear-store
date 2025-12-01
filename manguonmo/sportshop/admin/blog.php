<?php
session_start();
include "../config.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: /manguonmo/sportshop/index.php");
    exit();
}

// Xử lý thêm bài viết
if (isset($_POST['add_blog'])) {
    $title = trim($_POST['title']);
    $slug = create_slug($title);
    $content = $_POST['content'];
    $author_id = $_SESSION['user_id'];
    
    // Xử lý upload thumbnail
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
        $upload_dir = '../../assets/images/blog/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $file_name = 'blog_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        // Kiểm tra định dạng file
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $file_path)) {
                $thumbnail = $file_name;
            }
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO blog (title, slug, content, thumbnail, author_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $title, $slug, $content, $thumbnail, $author_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Thêm bài viết thành công!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Có lỗi xảy ra khi thêm bài viết!";
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
}

// Xử lý sửa bài viết
if (isset($_POST['edit_blog'])) {
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $slug = create_slug($title);
    $content = $_POST['content'];
    
    // Lấy thông tin bài viết hiện tại
    $current_blog = $conn->query("SELECT thumbnail FROM blog WHERE id = $id")->fetch_assoc();
    $thumbnail = $current_blog['thumbnail'];
    
    // Xử lý upload thumbnail mới
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
        $upload_dir = '../../assets/images/blog/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $file_name = 'blog_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $file_path)) {
                // Xóa thumbnail cũ nếu tồn tại
                if ($thumbnail && file_exists($upload_dir . $thumbnail)) {
                    unlink($upload_dir . $thumbnail);
                }
                $thumbnail = $file_name;
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE blog SET title = ?, slug = ?, content = ?, thumbnail = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $slug, $content, $thumbnail, $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Cập nhật bài viết thành công!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Có lỗi xảy ra khi cập nhật bài viết!";
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
}

// Xử lý xóa bài viết
if (isset($_GET['delete_id'])) {
    $blog_id = $_GET['delete_id'];
    
    // Lấy thông tin thumbnail để xóa file
    $blog = $conn->query("SELECT thumbnail FROM blog WHERE id = $blog_id")->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM blog WHERE id = ?");
    $stmt->bind_param("i", $blog_id);
    
    if ($stmt->execute()) {
        // Xóa file thumbnail nếu tồn tại
        if ($blog['thumbnail'] && file_exists('../../assets/images/blog/' . $blog['thumbnail'])) {
            unlink('../../assets/images/blog/' . $blog['thumbnail']);
        }
        
        $_SESSION['message'] = "Xóa bài viết thành công!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Có lỗi xảy ra khi xóa bài viết!";
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
}

// Hàm tạo slug
function create_slug($string) {
    $slug = preg_replace('/[^a-zA-Z0-9 -]/', '', $string);
    $slug = strtolower(str_replace(' ', '-', $slug));
    $slug = preg_replace('/-+/', '-', $slug);
    return $slug;
}

// Lấy danh sách bài viết với phân trang
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số bài viết
if (!empty($search)) {
    $search_term = "%$search%";
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM blog WHERE title LIKE ?");
    $count_stmt->bind_param("s", $search_term);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query("SELECT COUNT(*) as total FROM blog");
    $total_records = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Lấy danh sách bài viết
if (!empty($search)) {
    $query = "SELECT b.*, u.fullname as author_name 
              FROM blog b 
              LEFT JOIN users u ON b.author_id = u.id 
              WHERE b.title LIKE ? 
              ORDER BY b.created_at DESC 
              LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $search_term, $limit, $offset);
    $stmt->execute();
    $blog_result = $stmt->get_result();
} else {
    $query = "SELECT b.*, u.fullname as author_name 
              FROM blog b 
              LEFT JOIN users u ON b.author_id = u.id 
              ORDER BY b.created_at DESC 
              LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $blog_result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Blog - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    
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

        /* Blog Image */
        .blog-thumbnail {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        /* Content Preview */
        .content-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Summernote Customization */
        .note-editor {
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .note-editor .note-toolbar {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
        }

        /* File Upload */
        .file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
        }

        .file-upload:hover {
            border-color: var(--primary-color);
            background: #e3f2fd;
        }

        .file-upload-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            display: none;
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
            <a href="categories.php">
                <i class="fas fa-folder"></i>
                <span>Quản lý danh mục</span>
            </a>
            <a href="products.php">
                <i class="fas fa-shopping-bag"></i>
                <span>Quản lý sản phẩm</span>
            </a>
            <a href="blog.php" class="active">
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
            <h1>📝 Quản lý Blog</h1>
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

        <!-- Search Box -->
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Tìm kiếm bài viết theo tiêu đề..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBlogModal">
                            <i class="fas fa-plus me-2"></i>Viết bài mới
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách Bài viết -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list me-2"></i>Danh sách Bài viết (<?php echo $total_records; ?> bài)</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Tiêu đề</th>
                                <th>Tác giả</th>
                                <th>Ngày đăng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($blog_result->num_rows > 0): ?>
                                <?php while($blog = $blog_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($blog['thumbnail']): ?>
                                            <img src="../../assets/images/blog/<?php echo htmlspecialchars($blog['thumbnail']); ?>" 
                                                 alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                                                 class="blog-thumbnail"
                                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj7huqNwPC90ZXh0Pjwvc3ZnPg=='">
                                        <?php else: ?>
                                            <div class="blog-thumbnail d-flex align-items-center justify-content-center bg-light text-muted">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($blog['title']); ?></div>
                                        <div class="content-preview"><?php echo strip_tags(htmlspecialchars(substr($blog['content'], 0, 100))); ?>...</div>
                                        <small class="text-muted">Slug: <?php echo htmlspecialchars($blog['slug']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($blog['author_name']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($blog['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#editBlogModal<?php echo $blog['id']; ?>">
                                            <i class="fas fa-edit"></i> Sửa
                                        </button>
                                        <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $blog['id']; ?>, '<?php echo htmlspecialchars(addslashes($blog['title'])); ?>')">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Sửa Bài viết -->
                                <div class="modal fade" id="editBlogModal<?php echo $blog['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Sửa Bài viết</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="id" value="<?php echo $blog['id']; ?>">
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <div class="mb-3">
                                                                <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($blog['title']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Nội dung <span class="text-danger">*</span></label>
                                                                <textarea class="form-control summernote" name="content" rows="15" required><?php echo htmlspecialchars($blog['content']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label">Hình ảnh đại diện</label>
                                                                <div class="file-upload">
                                                                    <input type="file" class="form-control" name="thumbnail" accept="image/*" onchange="previewImage(this, 'editPreview<?php echo $blog['id']; ?>')">
                                                                    <?php if ($blog['thumbnail']): ?>
                                                                        <img id="editPreview<?php echo $blog['id']; ?>" src="../../assets/images/blog/<?php echo htmlspecialchars($blog['thumbnail']); ?>" class="file-upload-preview mt-3" style="display: block;">
                                                                    <?php else: ?>
                                                                        <img id="editPreview<?php echo $blog['id']; ?>" class="file-upload-preview mt-3">
                                                                        <div class="mt-3">
                                                                            <i class="fas fa-cloud-upload-alt fa-2x text-muted"></i>
                                                                            <p class="text-muted mb-0">Chọn hình ảnh</p>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Thông tin</label>
                                                                <div class="p-3 bg-light rounded">
                                                                    <p><strong>Tác giả:</strong> <?php echo htmlspecialchars($blog['author_name']); ?></p>
                                                                    <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($blog['created_at'])); ?></p>
                                                                    <p><strong>Slug:</strong> <?php echo htmlspecialchars($blog['slug']); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" name="edit_blog" class="btn btn-primary">Cập nhật</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-file-alt fa-3x mb-3"></i><br>
                                        <?php echo empty($search) ? 'Chưa có bài viết nào' : 'Không tìm thấy bài viết phù hợp'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center mt-4">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Modal Thêm Bài viết -->
    <div class="modal fade" id="addBlogModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Viết Bài viết Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" required placeholder="Nhập tiêu đề bài viết">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nội dung <span class="text-danger">*</span></label>
                                    <textarea class="form-control summernote" name="content" rows="15" required placeholder="Nhập nội dung bài viết"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Hình ảnh đại diện</label>
                                    <div class="file-upload">
                                        <input type="file" class="form-control" name="thumbnail" accept="image/*" onchange="previewImage(this, 'addPreview')">
                                        <img id="addPreview" class="file-upload-preview mt-3">
                                        <div class="mt-3">
                                            <i class="fas fa-cloud-upload-alt fa-2x text-muted"></i>
                                            <p class="text-muted mb-0">Chọn hình ảnh</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Lưu ý</label>
                                    <div class="p-3 bg-light rounded">
                                        <p class="small text-muted mb-1">• Tiêu đề nên hấp dẫn và mô tả đúng nội dung</p>
                                        <p class="small text-muted mb-1">• Sử dụng hình ảnh chất lượng cao</p>
                                        <p class="small text-muted mb-1">• Slug sẽ được tạo tự động từ tiêu đề</p>
                                        <p class="small text-muted">• Tác giả: <?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="add_blog" class="btn btn-success">Đăng bài</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        // Khởi tạo Summernote
        $(document).ready(function() {
            $('.summernote').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Helvetica', 'Impact', 'Tahoma', 'Times New Roman', 'Verdana', 'Roboto'],
                fontNamesIgnoreCheck: ['Roboto']
            });
        });

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                preview.parentElement.querySelector('.fa-cloud-upload-alt').style.display = 'none';
                preview.parentElement.querySelector('p').style.display = 'none';
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        }

        function confirmDelete(blogId, blogTitle) {
            if (confirm(`Bạn có chắc chắn muốn xóa bài viết "${blogTitle}"?`)) {
                window.location.href = '?delete_id=' + blogId;
            }
        }

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
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>