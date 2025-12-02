<?php
session_start();
include "config.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuyển dụng - Sport Fashion</title>
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

        .careers-page {
            padding: 80px 0 60px;
            background: var(--light-bg);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #333 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 60px;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .page-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Hero Section */
        .career-hero {
            padding: 60px 0;
            background: white;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }

        .stat-item {
            text-align: center;
            padding: 30px 20px;
            background: var(--light-bg);
            border-radius: 12px;
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 600;
        }

        /* Why Join Us Section */
        .why-join-section {
            padding: 80px 0;
            background: var(--light-bg);
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            color: var(--primary-color);
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .benefit-card {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid var(--accent-color);
        }

        .benefit-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .benefit-icon {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .benefit-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .benefit-description {
            color: #666;
            line-height: 1.6;
        }

        /* Job Openings Section */
        .jobs-section {
            padding: 80px 0;
            background: white;
        }

        .job-filters {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .jobs-grid {
            display: grid;
            gap: 25px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            justify-content: between;
            align-items: center;
            gap: 30px;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-left-color: var(--accent-color);
        }

        .job-info {
            flex: 1;
        }

        .job-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .job-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .job-location,
        .job-type,
        .job-department {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #666;
            font-size: 0.9rem;
        }

        .job-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0;
        }

        .job-actions {
            flex-shrink: 0;
        }

        .btn-apply {
            background: var(--accent-color);
            color: white;
            padding: 12px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-apply:hover {
            background: #c40023;
            color: white;
            transform: translateY(-2px);
        }

        /* Culture Section */
        .culture-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #2d2d2d 100%);
            color: white;
        }

        .culture-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .culture-item {
            padding: 30px 20px;
        }

        .culture-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--accent-color);
        }

        .culture-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .culture-description {
            color: #ccc;
            line-height: 1.6;
        }

        /* Application Process */
        .process-section {
            padding: 80px 0;
            background: var(--light-bg);
        }

        .process-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            max-width: 900px;
            margin: 0 auto;
        }

        .process-steps::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 0;
            right: 0;
            height: 2px;
            background: #ddd;
            z-index: 1;
        }

        .process-step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background: white;
            border: 3px solid var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
            margin: 0 auto 20px;
            position: relative;
        }

        .step-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .step-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--primary-color) 0%, #333 100%);
            color: white;
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-cta {
            background: var(--accent-color);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-cta:hover {
            background: #c40023;
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.2rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .job-card {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .job-meta {
                justify-content: center;
            }

            .process-steps {
                flex-direction: column;
                gap: 40px;
            }

            .process-steps::before {
                display: none;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 20px;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 60px 0;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .job-filters {
                flex-direction: column;
                align-items: center;
            }

            .filter-btn {
                width: 200px;
            }

            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="careers-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">Join Our Team</h1>
                <p class="page-subtitle">
                    Cùng chúng tôi xây dựng tương lai của thời trang thể thao
                </p>
            </div>
        </div>

        <!-- Hero Section -->
        <section class="career-hero">
            <div class="container">
                <div class="hero-content">
                    <h2 class="hero-title">Xây dựng sự nghiệp tại Sport Fashion</h2>
                    <p class="hero-subtitle">
                        Chúng tôi không chỉ bán quần áo thể thao - chúng tôi truyền cảm hứng cho lối sống năng động. 
                        Tham gia đội ngũ của chúng tôi và cùng tạo ra sự khác biệt trong ngành thời trang thể thao.
                    </p>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number">50+</div>
                            <div class="stat-label">Nhân viên</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">15</div>
                            <div class="stat-label">Cửa hàng</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">5</div>
                            <div class="stat-label">Thành phố</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">2025</div>
                            <div class="stat-label">Năm thành lập</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Join Us Section -->
        <section class="why-join-section">
            <div class="container">
                <h2 class="section-title">Tại sao nên làm việc tại Sport Fashion?</h2>
                
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="benefit-title">Phát triển sự nghiệp</h3>
                        <p class="benefit-description">
                            Cơ hội thăng tiến rõ ràng với lộ trình phát triển nghề nghiệp được cá nhân hóa.
                        </p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 class="benefit-title">Đào tạo liên tục</h3>
                        <p class="benefit-description">
                            Chương trình đào tạo toàn diện từ sản phẩm, kỹ năng bán hàng đến quản lý.
                        </p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="benefit-title">Môi trường năng động</h3>
                        <p class="benefit-description">
                            Làm việc trong môi trường trẻ trung, sáng tạo cùng những người đam mê thể thao.
                        </p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h3 class="benefit-title">Thu nhập hấp dẫn</h3>
                        <p class="benefit-description">
                            Lương cạnh tranh, thưởng hiệu suất và nhiều chế độ phúc lợi hấp dẫn.
                        </p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <h3 class="benefit-title">Sản phẩm miễn phí</h3>
                        <p class="benefit-description">
                            Nhận sản phẩm mới nhất của Sport Fashion và hưởng ưu đãi đặc biệt cho nhân viên.
                        </p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h3 class="benefit-title">Cân bằng cuộc sống</h3>
                        <p class="benefit-description">
                            Lịch làm việc linh hoạt, ngày nghỉ phép và các hoạt động team building thường xuyên.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Job Openings Section -->
        <section class="jobs-section">
            <div class="container">
                <h2 class="section-title">Vị trí tuyển dụng</h2>
                
                <div class="job-filters">
                    <button class="filter-btn active" data-filter="all">Tất cả</button>
                    <button class="filter-btn" data-filter="retail">Bán hàng</button>
                    <button class="filter-btn" data-filter="management">Quản lý</button>
                    <button class="filter-btn" data-filter="marketing">Marketing</button>
                    <button class="filter-btn" data-filter="warehouse">Kho vận</button>
                </div>
                
                <div class="jobs-grid">
                    <!-- Job 1 -->
                    <div class="job-card" data-category="retail">
                        <div class="job-info">
                            <h3 class="job-title">Nhân viên bán hàng</h3>
                            <div class="job-meta">
                                <span class="job-location">
                                    <i class="fas fa-map-marker-alt"></i>Hà Nội
                                </span>
                                <span class="job-type">
                                    <i class="fas fa-clock"></i>Toàn thời gian
                                </span>
                                <span class="job-department">
                                    <i class="fas fa-building"></i>Bán hàng
                                </span>
                            </div>
                            <p class="job-description">
                                Tư vấn và hỗ trợ khách hàng lựa chọn sản phẩm phù hợp. Đảm bảo trải nghiệm mua sắm tốt nhất.
                            </p>
                        </div>
                        <div class="job-actions">
                            <a href="#apply" class="btn-apply">Ứng tuyển ngay</a>
                        </div>
                    </div>
                    
                    <!-- Job 2 -->
                    <div class="job-card" data-category="management">
                        <div class="job-info">
                            <h3 class="job-title">Quản lý cửa hàng</h3>
                            <div class="job-meta">
                                <span class="job-location">
                                    <i class="fas fa-map-marker-alt"></i>TP.HCM
                                </span>
                                <span class="job-type">
                                    <i class="fas fa-clock"></i>Toàn thời gian
                                </span>
                                <span class="job-department">
                                    <i class="fas fa-building"></i>Quản lý
                                </span>
                            </div>
                            <p class="job-description">
                                Quản lý vận hành cửa hàng, đào tạo nhân viên và đảm bảo đạt chỉ tiêu doanh thu.
                            </p>
                        </div>
                        <div class="job-actions">
                            <a href="#apply" class="btn-apply">Ứng tuyển ngay</a>
                        </div>
                    </div>
                    
                    <!-- Job 3 -->
                    <div class="job-card" data-category="marketing">
                        <div class="job-info">
                            <h3 class="job-title">Chuyên viên Marketing</h3>
                            <div class="job-meta">
                                <span class="job-location">
                                    <i class="fas fa-map-marker-alt"></i>Hà Nội
                                </span>
                                <span class="job-type">
                                    <i class="fas fa-clock"></i>Toàn thời gian
                                </span>
                                <span class="job-department">
                                    <i class="fas fa-building"></i>Marketing
                                </span>
                            </div>
                            <p class="job-description">
                                Lập kế hoạch và triển khai chiến dịch marketing, quản lý mạng xã hội và nội dung.
                            </p>
                        </div>
                        <div class="job-actions">
                            <a href="#apply" class="btn-apply">Ứng tuyển ngay</a>
                        </div>
                    </div>
                    
                    <!-- Job 4 -->
                    <div class="job-card" data-category="warehouse">
                        <div class="job-info">
                            <h3 class="job-title">Nhân viên kho</h3>
                            <div class="job-meta">
                                <span class="job-location">
                                    <i class="fas fa-map-marker-alt"></i>Bình Dương
                                </span>
                                <span class="job-type">
                                    <i class="fas fa-clock"></i>Toàn thời gian
                                </span>
                                <span class="job-department">
                                    <i class="fas fa-building"></i>Kho vận
                                </span>
                            </div>
                            <p class="job-description">
                                Quản lý xuất nhập kho, kiểm tra chất lượng sản phẩm và hỗ trợ đóng gói đơn hàng.
                            </p>
                        </div>
                        <div class="job-actions">
                            <a href="#apply" class="btn-apply">Ứng tuyển ngay</a>
                        </div>
                    </div>
                    
                    <!-- Job 5 -->
                    <div class="job-card" data-category="retail">
                        <div class="job-info">
                            <h3 class="job-title">Trưởng nhóm bán hàng</h3>
                            <div class="job-meta">
                                <span class="job-location">
                                    <i class="fas fa-map-marker-alt"></i>Đà Nẵng
                                </span>
                                <span class="job-type">
                                    <i class="fas fa-clock"></i>Toàn thời gian
                                </span>
                                <span class="job-department">
                                    <i class="fas fa-building"></i>Bán hàng
                                </span>
                            </div>
                            <p class="job-description">
                                Hỗ trợ quản lý cửa hàng, hướng dẫn nhân viên và đảm bảo chất lượng dịch vụ.
                            </p>
                        </div>
                        <div class="job-actions">
                            <a href="#apply" class="btn-apply">Ứng tuyển ngay</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Culture Section -->
        <section class="culture-section">
            <div class="container">
                <h2 class="section-title text-white">Văn hóa Sport Fashion</h2>
                
                <div class="culture-grid">
                    <div class="culture-item">
                        <div class="culture-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="culture-title">Tinh thần đồng đội</h3>
                        <p class="culture-description">
                            Chúng tôi tin rằng thành công đến từ sự hợp tác và hỗ trợ lẫn nhau.
                        </p>
                    </div>
                    
                    <div class="culture-item">
                        <div class="culture-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="culture-title">Định hướng kết quả</h3>
                        <p class="culture-description">
                            Tập trung vào mục tiêu và cam kết đạt được kết quả xuất sắc.
                        </p>
                    </div>
                    
                    <div class="culture-item">
                        <div class="culture-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3 class="culture-title">Đổi mới sáng tạo</h3>
                        <p class="culture-description">
                            Khuyến khích ý tưởng mới và không ngừng cải tiến.
                        </p>
                    </div>
                    
                    <div class="culture-item">
                        <div class="culture-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h3 class="culture-title">Đam mê phục vụ</h3>
                        <p class="culture-description">
                            Luôn đặt khách hàng làm trung tâm trong mọi hoạt động.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Application Process -->
        <section class="process-section">
            <div class="container">
                <h2 class="section-title">Quy trình ứng tuyển</h2>
                
                <div class="process-steps">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <h4 class="step-title">Nộp hồ sơ</h4>
                        <p class="step-description">
                            Điền form ứng tuyển trực tuyến hoặc gửi CV đến email của chúng tôi.
                        </p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <h4 class="step-title">Phỏng vấn điện thoại</h4>
                        <p class="step-description">
                            Trao đổi ngắn để tìm hiểu kinh nghiệm và mong muốn của ứng viên.
                        </p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <h4 class="step-title">Phỏng vấn trực tiếp</h4>
                        <p class="step-description">
                            Gặp gỡ trực tiếp với quản lý bộ phận và nhân sự.
                        </p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <h4 class="step-title">Nhận đề nghị</h4>
                        <p class="step-description">
                            Nhận thư mời làm việc và tham gia vào đội ngũ Sport Fashion.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <h2 class="cta-title">Sẵn sàng gia nhập đội ngũ của chúng tôi?</h2>
                <p class="cta-subtitle">
                    Hãy trở thành một phần của Sport Fashion và cùng chúng tôi tạo ra những trải nghiệm mua sắm tuyệt vời.
                </p>
                <a href="mailto:careers@sportshop.com" class="btn-cta">
                    <i class="fas fa-paper-plane me-2"></i>Gửi hồ sơ ngay
                </a>
            </div>
        </section>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Job Filtering
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const jobCards = document.querySelectorAll('.job-card');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const filterValue = this.getAttribute('data-filter');
                    
                    jobCards.forEach(card => {
                        if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
            
            // Smooth scroll for apply buttons
            document.querySelectorAll('.btn-apply').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('apply').scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>