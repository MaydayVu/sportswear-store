<?php
session_start();
include "config.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Về Chúng Tôi - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #000;
            --accent-color: #e4002b;
            --light-bg: #f8f9fa;
            --dark-bg: #1a1a1a;
        }

        .about-page {
            padding-top: 80px;
        }

        /* Hero Section */
        .about-hero {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><text x="50%" y="50%" font-family="Arial" font-size="14" fill="white" text-anchor="middle" dominant-baseline="middle">SPORT FASHION</text></svg>') repeat;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .hero-content .lead {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Mission Section */
        .mission-section {
            padding: 80px 0;
            background: white;
        }

        .mission-card {
            background: var(--light-bg);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid var(--accent-color);
        }

        .mission-icon {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        /* Team Section */
        .team-section {
            padding: 80px 0;
            background: var(--light-bg);
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            color: var(--primary-color);
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto 60px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .team-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .member-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 30px auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .member-info {
            padding: 0 30px 30px;
        }

        .member-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .member-id {
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .member-role {
            color: #666;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .member-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .skill-tag {
            background: var(--light-bg);
            color: #495057;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .member-social {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-bg);
            color: #495057;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: var(--accent-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Values Section */
        .values-section {
            padding: 80px 0;
            background: white;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .value-card {
            text-align: center;
            padding: 30px;
        }

        .value-icon {
            width: 80px;
            height: 80px;
            background: var(--light-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--accent-color);
            font-size: 2rem;
        }

        .value-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .value-description {
            color: #666;
            line-height: 1.6;
        }

        /* Timeline Section */
        .timeline-section {
            padding: 80px 0;
            background: var(--light-bg);
        }

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--accent-color);
            transform: translateX(-50%);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 50px;
            width: 50%;
        }

        .timeline-item:nth-child(odd) {
            left: 0;
            padding-right: 40px;
            text-align: right;
        }

        .timeline-item:nth-child(even) {
            left: 50%;
            padding-left: 40px;
        }

        .timeline-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            position: relative;
        }

        .timeline-item:nth-child(odd) .timeline-content::after {
            content: '';
            position: absolute;
            right: -10px;
            top: 20px;
            width: 0;
            height: 0;
            border-left: 10px solid white;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }

        .timeline-item:nth-child(even) .timeline-content::after {
            content: '';
            position: absolute;
            left: -10px;
            top: 20px;
            width: 0;
            height: 0;
            border-right: 10px solid white;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
        }

        .timeline-marker {
            position: absolute;
            top: 20px;
            width: 20px;
            height: 20px;
            background: var(--accent-color);
            border: 4px solid white;
            border-radius: 50%;
            box-shadow: 0 0 0 3px var(--accent-color);
        }

        .timeline-item:nth-child(odd) .timeline-marker {
            right: -10px;
        }

        .timeline-item:nth-child(even) .timeline-marker {
            left: -10px;
        }

        .timeline-year {
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .timeline-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        /* Stats Section */
        .stats-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: white;
            text-align: center;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .btn-cta {
            background: var(--primary-color);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-block;
            border: 2px solid var(--primary-color);
        }

        .btn-cta:hover {
            background: transparent;
            color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .timeline::before {
                left: 20px;
            }

            .timeline-item {
                width: 100%;
                left: 0 !important;
                padding-left: 50px !important;
                padding-right: 0 !important;
                text-align: left !important;
            }

            .timeline-item:nth-child(odd) .timeline-content::after,
            .timeline-item:nth-child(even) .timeline-content::after {
                left: -10px;
                right: auto;
                border-right: 10px solid white;
                border-left: none;
            }

            .timeline-marker {
                left: 10px !important;
                right: auto !important;
            }

            .team-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .about-hero {
                padding: 60px 0;
            }

            .mission-section,
            .team-section,
            .values-section,
            .timeline-section,
            .stats-section,
            .cta-section {
                padding: 60px 0;
            }

            .hero-content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <div class="hero-content">
                <h1>Về Sport Fashion</h1>
                <p class="lead">Chúng tôi không chỉ bán hàng - chúng tôi truyền cảm hứng cho phong cách thể thao</p>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="mission-card">
                        <div class="mission-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h2>Sứ Mệnh Của Chúng Tôi</h2>
                        <p class="lead">
                            Mang đến cho cộng đồng yêu thể thao những sản phẩm chất lượng cao, 
                            kết hợp giữa công nghệ hiện đại và thiết kế thời trang. Chúng tôi tin rằng 
                            mỗi vận động viên xứng đáng có được trang phục tốt nhất để tỏa sáng.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <h2 class="section-title">Đội Ngũ Phát Triển</h2>
            <p class="section-subtitle">
                Gặp gỡ những thành viên sáng lập đã tạo nên Sport Fashion - 
                Một nhóm đam mê thể thao và công nghệ
            </p>

            <div class="team-grid">
                <!-- Member 1 -->
                <div class="team-card">
                    <div class="member-avatar">PG</div>
                    <div class="member-info">
                        <h3 class="member-name">Trần Ngọc Gia Phúc</h3>
                        <div class="member-id">MSSV: 64131849</div>
                        <div class="member-role">Team Leader & Full-stack Developer</div>
                        
                        <div class="member-skills">
                            <span class="skill-tag">PHP</span>
                            <span class="skill-tag">JavaScript</span>
                            <span class="skill-tag">MySQL</span>
                            <span class="skill-tag">Bootstrap</span>
                        </div>
                        
                        <p class="member-bio">
                            Chịu trách nhiệm chính về kiến trúc hệ thống và phát triển. 
                            Thiết kế giao diện và ý tưởng phát triển.
                        </p>
                        
                        <div class="member-social">
                            <a href="#" class="social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-github"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Member 2 -->
                <div class="team-card">
                    <div class="member-avatar">HV</div>
                    <div class="member-info">
                        <h3 class="member-name">Trần Lâm Hoàng Vũ</h3>
                        <div class="member-id">MSSV: 64133025</div>
                        <div class="member-role">Front-end Developer & UI/UX Designer</div>
                        
                        <div class="member-skills">
                            <span class="skill-tag">HTML/CSS</span>
                            <span class="skill-tag">JavaScript</span>
                            <span class="skill-tag">React</span>
                            <span class="skill-tag">Figma</span>
                        </div>
                        
                        <p class="member-bio">
                            Chuyên về trải nghiệm người dùng và giao diện. Luôn tìm kiếm 
                            những xu hướng thiết kế mới để tạo ra trải nghiệm tốt nhất.
                        </p>
                        
                        <div class="member-social">
                            <a href="#" class="social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-github"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-dribbble"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Member 3 -->
                <div class="team-card">
                    <div class="member-avatar">ĐQ</div>
                    <div class="member-info">
                        <h3 class="member-name">Huỳnh Quang Đức</h3>
                        <div class="member-id">MSSV: 63133733</div>
                        <div class="member-role">Back-end Developer & Database Architect</div>
                        
                        <div class="member-skills">
                            <span class="skill-tag">PHP</span>
                            <span class="skill-tag">MySQL</span>
                            <span class="skill-tag">API</span>
                            <span class="skill-tag">Security</span>
                        </div>
                        
                        <p class="member-bio">
                            Chuyên gia về database và bảo mật hệ thống. Đảm bảo dữ liệu 
                            được xử lý an toàn và hiệu quả.
                        </p>
                        
                        <div class="member-social">
                            <a href="#" class="social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-github"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-stack-overflow"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <h2 class="section-title">Giá Trị Cốt Lõi</h2>
            <p class="section-subtitle">
                Những nguyên tắc định hướng mọi hoạt động của chúng tôi
            </p>

            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <h3 class="value-title">Chất Lượng</h3>
                    <p class="value-description">
                        Cam kết mang đến sản phẩm chất lượng cao nhất, 
                        đáp ứng mọi tiêu chuẩn khắt khe về độ bền và hiệu suất.
                    </p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3 class="value-title">Đổi Mới</h3>
                    <p class="value-description">
                        Không ngừng nghiên cứu và phát triển các công nghệ mới 
                        để cải thiện trải nghiệm người dùng.
                    </p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="value-title">Cộng Đồng</h3>
                    <p class="value-description">
                        Xây dựng và hỗ trợ cộng đồng yêu thể thao, 
                        tạo ra sân chơi lành mạnh cho mọi người.
                    </p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 class="value-title">Đam Mê</h3>
                    <p class="value-description">
                        Làm việc với tất cả đam mê và nhiệt huyết, 
                        truyền cảm hứng thể thao đến mọi người.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section">
        <div class="container">
            <h2 class="section-title">Hành Trình Phát Triển</h2>
            <p class="section-subtitle">
                Những cột mốc quan trọng trong quá trình hình thành và phát triển
            </p>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2025</div>
                        <h4 class="timeline-title">Ý Tưởng Khởi Nguồn</h4>
                        <p>Nhóm được thành lập với ý tưởng tạo ra một nền tảng thương mại điện tử chuyên về thể thao.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2025</div>
                        <h4 class="timeline-title">Phát Triển Hệ Thống</h4>
                        <p>Bắt đầu phát triển website với công nghệ PHP, MySQL và các framework hiện đại.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2025</div>
                        <h4 class="timeline-title">Ra Mắt Phiên Bản Đầu Tiên</h4>
                        <p>Chính thức ra mắt Sport Fashion với đầy đủ tính năng cơ bản.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2100</div>
                        <h4 class="timeline-title">Mở Rộng Cộng Đồng</h4>
                        <p>Đạt được 10,000 người dùng đăng ký và mở rộng danh mục sản phẩm.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">3</div>
                        <div class="stat-label">Thành Viên</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Sản Phẩm</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Hài Lòng</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Hỗ Trợ</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Sẵn Sàng Trải Nghiệm?</h2>
                <p class="lead mb-4">
                    Khám phá bộ sưu tập sản phẩm thể thao đa dạng và chất lượng của chúng tôi
                </p>
                <a href="products.php" class="btn-cta">
                    <i class="fas fa-shopping-bag me-2"></i>Mua Sắm Ngay
                </a>
            </div>
        </div>
    </section>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Animate team cards
            const teamCards = document.querySelectorAll('.team-card');
            teamCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `all 0.6s ease ${index * 0.2}s`;
                observer.observe(card);
            });

            // Animate value cards
            const valueCards = document.querySelectorAll('.value-card');
            valueCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `all 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });

            // Animate timeline items
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(' + (index % 2 === 0 ? '-30px' : '30px') + ')';
                item.style.transition = `all 0.6s ease ${index * 0.2}s`;
                observer.observe(item);
            });
        });
    </script>
</body>
</html>