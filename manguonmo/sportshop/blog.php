<?php
session_start();
include "config.php";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Thể Thao | Sport Fashion</title>
    <link rel="stylesheet" href="/manguonmo/sportshop/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* === BLOG PROFESSIONAL STYLES === */
        .blog-page {
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* Hero Section */
        .blog-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .blog-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.1"><polygon points="1000,100 1000,0 0,100"></polygon></svg>');
            background-size: cover;
        }

        .blog-hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .blog-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }

        .blog-hero p {
            font-size: 1.3rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Blog Container */
        .blog-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px;
        }

        /* Blog Grid */
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 40px;
            margin-bottom: 80px;
        }

        /* Blog Card */
        .blog-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .blog-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .blog-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 2;
        }

        .blog-image-container {
            position: relative;
            overflow: hidden;
            height: 240px;
        }

        .blog-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .blog-card:hover .blog-image {
            transform: scale(1.05);
        }

        .blog-category {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255,255,255,0.95);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #667eea;
            backdrop-filter: blur(10px);
        }

        .blog-content {
            padding: 30px;
        }

        .blog-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }

        .blog-author {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .author-avatar {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .blog-date {
            color: #7f8c8d;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .blog-title {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 15px;
            color: #2c3e50;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .blog-excerpt {
            color: #5d6d7e;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .blog-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 8px 0;
        }

        .blog-link:hover {
            color: #764ba2;
            gap: 12px;
        }

        /* Featured Post */
        .featured-post {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        .featured-post .blog-card {
            display: contents;
        }

        .featured-post .blog-image-container {
            height: 400px;
            border-radius: 16px;
        }

        .featured-post .blog-content {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .featured-post .blog-title {
            font-size: 2.2rem;
            margin-bottom: 20px;
        }

        .featured-post .blog-excerpt {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 60px;
        }

        .pagination a, .pagination span {
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-decoration: none;
            color: #5d6d7e;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 50px;
            text-align: center;
        }

        .pagination a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .pagination .prev-next {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Empty State */
        .blog-empty {
            text-align: center;
            padding: 100px 20px;
            color: #7f8c8d;
        }

        .blog-empty i {
            font-size: 5rem;
            margin-bottom: 30px;
            color: #bdc3c7;
            opacity: 0.5;
        }

        .blog-empty h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .blog-empty p {
            font-size: 1.1rem;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Newsletter Section */
        .newsletter-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-top: 80px;
            border-radius: 20px;
        }

        .newsletter-content {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .newsletter h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .newsletter p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .newsletter-form {
            display: flex;
            gap: 15px;
            max-width: 400px;
            margin: 0 auto;
        }

        .newsletter-input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            outline: none;
        }

        .newsletter-btn {
            padding: 15px 30px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .newsletter-btn:hover {
            background: #34495e;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .blog-grid {
                grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
                gap: 30px;
            }
            
            .featured-post {
                grid-template-columns: 1fr;
            }
            
            .featured-post .blog-image-container {
                height: 300px;
            }
        }

        @media (max-width: 768px) {
            .blog-hero {
                padding: 80px 0 60px;
            }
            
            .blog-hero h1 {
                font-size: 2.5rem;
            }
            
            .blog-hero p {
                font-size: 1.1rem;
            }
            
            .hero-stats {
                gap: 30px;
            }
            
            .blog-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .blog-container {
                padding: 60px 20px;
            }
            
            .featured-post .blog-title {
                font-size: 1.8rem;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
            .blog-hero h1 {
                font-size: 2rem;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 20px;
            }
            
            .blog-content {
                padding: 25px;
            }
            
            .featured-post .blog-content {
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body class="blog-page">
    <?php include "includes/header.php"; ?>

    <!-- Blog Hero Section -->
    <section class="blog-hero">
        <div class="blog-hero-content">
            <h1>📝 Blog Thể Thao</h1>
            <p>Khám phá những xu hướng mới nhất, mẹo tập luyện và câu chuyện đằng sau thế giới thể thao đầy cảm hứng</p>
            
            <?php
            $total_posts = $conn->query("SELECT COUNT(*) as total FROM blog")->fetch_assoc()['total'];
            $total_authors = $conn->query("SELECT COUNT(DISTINCT author_id) as total FROM blog")->fetch_assoc()['total'];
            ?>
            
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_posts; ?></span>
                    <span class="stat-label">Bài viết</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_authors; ?></span>
                    <span class="stat-label">Tác giả</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Cập nhật</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Content -->
    <div class="blog-container">
        <?php
        // Phân trang
        $limit = 9;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Đếm tổng số bài viết
        $count_result = $conn->query("SELECT COUNT(*) as total FROM blog");
        $total_records = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $limit);

        // Lấy danh sách bài viết
        $query = "SELECT b.*, u.fullname as author_name 
                FROM blog b 
                LEFT JOIN users u ON b.author_id = u.id 
                ORDER BY b.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $blog_result = $stmt->get_result();
        $posts = [];
        while($post = $blog_result->fetch_assoc()) {
            $posts[] = $post;
        }
        ?>

        <?php if (!empty($posts)): ?>
            <div class="blog-grid">
                <?php foreach($posts as $index => $blog): ?>
                    <?php if ($index === 0): ?>
                        <!-- Featured Post -->
                        <article class="featured-post">
                            <div class="blog-image-container">
                                <?php if ($blog['thumbnail']): ?>
                                    <img src="/manguonmo/sportshop/assets/images/blog/<?php echo htmlspecialchars($blog['thumbnail']); ?>" 
                                        alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                                        class="blog-image"
                                        onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAwIiBoZWlnaHQ9IjQwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIyNCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPuG6o3A8L3RleHQ+PC9zdmc+'">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);display:flex;align-items:center;justify-content:center;color:white;">
                                        <i class="fas fa-newspaper" style="font-size:4rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="blog-category">NỔI BẬT</div>
                            </div>
                            <div class="blog-content">
                                <div class="blog-meta">
                                    <span class="blog-author">
                                        <span class="author-avatar"><?php echo strtoupper(substr($blog['author_name'], 0, 1)); ?></span>
                                        <?php echo htmlspecialchars($blog['author_name']); ?>
                                    </span>
                                    <span class="blog-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($blog['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <h2 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h2>
                                
                                <p class="blog-excerpt">
                                    <?php 
                                    $content = strip_tags($blog['content']);
                                    echo strlen($content) > 200 ? substr($content, 0, 200) . '...' : $content;
                                    ?>
                                </p>
                                
                                <a href="/manguonmo/sportshop/blog-detail.php?id=<?php echo $blog['id']; ?>" class="blog-link">
                                    Đọc bài viết <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php else: ?>
                        <!-- Regular Posts -->
                        <article class="blog-card">
                            <div class="blog-image-container">
                                <?php if ($blog['thumbnail']): ?>
                                    <img src="/manguonmo/sportshop/assets/images/blog/<?php echo htmlspecialchars($blog['thumbnail']); ?>" 
                                        alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                                        class="blog-image"
                                        onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjI0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxOCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPuG6o3A8L3RleHQ+PC9zdmc+'">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:#f8f9fa;display:flex;align-items:center;justify-content:center;color:#666;">
                                        <i class="fas fa-image" style="font-size:2rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="blog-content">
                                <div class="blog-meta">
                                    <span class="blog-author">
                                        <span class="author-avatar"><?php echo strtoupper(substr($blog['author_name'], 0, 1)); ?></span>
                                        <?php echo htmlspecialchars($blog['author_name']); ?>
                                    </span>
                                    <span class="blog-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($blog['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <h2 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h2>
                                
                                <p class="blog-excerpt">
                                    <?php 
                                    $content = strip_tags($blog['content']);
                                    echo strlen($content) > 120 ? substr($content, 0, 120) . '...' : $content;
                                    ?>
                                </p>
                                
                                <a href="/manguonmo/sportshop/blog-detail.php?id=<?php echo $blog['id']; ?>" class="blog-link">
                                    Đọc thêm <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="prev-next">
                            <i class="fas fa-chevron-left"></i> Trước
                        </a>
                    <?php endif; ?>

                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="prev-next">
                            Sau <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Newsletter Section -->
            <section class="newsletter-section">
                <div class="newsletter-content">
                    <h2>📧 Đừng bỏ lỡ bài viết mới</h2>
                    <p>Đăng ký nhận bản tin để cập nhật những bài viết mới nhất về thể thao và fitness</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Nhập email của bạn..." class="newsletter-input" required>
                        <button type="submit" class="newsletter-btn">Đăng ký</button>
                    </form>
                </div>
            </section>

        <?php else: ?>
            <div class="blog-empty">
                <i class="fas fa-file-alt"></i>
                <h3>Chưa có bài viết nào</h3>
                <p>Chúng tôi đang chuẩn bị những nội dung hấp dẫn cho bạn. Hãy quay lại sau nhé!</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include "includes/footer.php"; ?>

    <script>
        // Xử lý newsletter form
        document.addEventListener('DOMContentLoaded', function() {
            const newsletterForm = document.querySelector('.newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = this.querySelector('input[type="email"]').value;
                    if (email) {
                        alert('Cảm ơn bạn đã đăng ký nhận bản tin!');
                        this.reset();
                    }
                });
            }

            // Add loading animation to cards
            const cards = document.querySelectorAll('.blog-card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
            });

            // Animate cards on load
            setTimeout(() => {
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.transition = 'all 0.6s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 100);
                });
            }, 300);
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