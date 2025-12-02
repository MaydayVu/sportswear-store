<?php
session_start();
include "config.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #000;
            --accent-color: #e4002b;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }

        .contact-page {
            padding: 80px 0 60px;
            background: var(--light-bg);
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 60px;
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

        .contact-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .contact-info {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 40px;
            height: 100%;
        }

        .contact-form {
            padding: 40px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 30px;
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .info-content h4 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: white;
        }

        .info-content p {
            margin: 0;
            opacity: 0.9;
            line-height: 1.5;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .social-link {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .social-link:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-submit:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
        }

        .map-section {
            margin-top: 60px;
        }

        .map-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            height: 400px;
        }

        .map-placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .faq-section {
            margin-top: 60px;
        }

        .faq-item {
            background: white;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .faq-question {
            padding: 20px 25px;
            background: white;
            border: none;
            width: 100%;
            text-align: left;
            font-weight: 600;
            color: var(--primary-color);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
            font-size: 1.1rem;
        }

        .faq-question:hover {
            background: #f8f9fa;
        }

        .faq-answer {
            padding: 0 25px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .faq-answer.show {
            padding: 25px;
            max-height: 500px;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align: center;
        }

        .contact-hours {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .hours-table {
            width: 100%;
        }

        .hours-table tr {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .hours-table tr:last-child {
            border-bottom: none;
        }

        .hours-table td {
            padding: 8px 0;
        }

        .hours-table td:first-child {
            font-weight: 600;
        }

        .hours-table td:last-child {
            text-align: right;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .contact-info,
            .contact-form {
                padding: 30px 25px;
            }

            .info-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .social-links {
                justify-content: center;
            }

            .map-container {
                height: 300px;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 40px 0;
            }

            .contact-page {
                padding: 60px 0 40px;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="contact-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">Liên hệ với chúng tôi</h1>
                <p class="page-subtitle">
                    Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn
                </p>
            </div>
        </div>

        <div class="container">
            <!-- Main Contact Content -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <h3 class="mb-4">Thông tin liên hệ</h3>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>Địa chỉ</h4>
                                <p>
                                    Số 123, Đường ABC, Phường XYZ<br>
                                    Quận 1, TP. Hồ Chí Minh
                                </p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>Điện thoại</h4>
                                <p>
                                    Hotline: <strong>1900 1234</strong><br>
                                    Hỗ trợ: <strong>028 1234 5678</strong>
                                </p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <h4>Email</h4>
                                <p>
                                    CSKH: <strong>cskh@sportshop.com</strong><br>
                                    Hợp tác: <strong>partner@sportshop.com</strong>
                                </p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h4>Thời gian làm việc</h4>
                                <div class="contact-hours">
                                    <table class="hours-table">
                                        <tr>
                                            <td>Thứ 2 - Thứ 6</td>
                                            <td>7:00 - 22:00</td>
                                        </tr>
                                        <tr>
                                            <td>Thứ 7</td>
                                            <td>8:00 - 21:00</td>
                                        </tr>
                                        <tr>
                                            <td>Chủ nhật</td>
                                            <td>8:00 - 20:00</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="social-links">
                            <a href="#" class="social-link" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" title="Zalo">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="#" class="social-link" title="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="contact-form">
                        <h3 class="mb-4">Gửi tin nhắn cho chúng tôi</h3>
                        <form id="contactForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Họ và tên *</label>
                                        <input type="text" class="form-control" required placeholder="Nhập họ và tên của bạn">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" required placeholder="Nhập email của bạn">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" placeholder="Nhập số điện thoại của bạn">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Chủ đề *</label>
                                <select class="form-control" required>
                                    <option value="">Chọn chủ đề</option>
                                    <option value="support">Hỗ trợ sản phẩm</option>
                                    <option value="order">Đơn hàng & Giao hàng</option>
                                    <option value="return">Đổi trả & Bảo hành</option>
                                    <option value="cooperation">Hợp tác kinh doanh</option>
                                    <option value="feedback">Góp ý & Phản hồi</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Nội dung *</label>
                                <textarea class="form-control" required placeholder="Mô tả chi tiết vấn đề của bạn..." rows="5"></textarea>
                            </div>

                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>Gửi tin nhắn
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="map-section">
                <h2 class="section-title">Vị trí cửa hàng</h2>
                <div class="map-container">
                    <div class="map-placeholder">
                        <div class="text-center">
                            <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                            <div>Bản đồ cửa hàng Sport Fashion</div>
                            <small class="opacity-75">(Tích hợp Google Maps)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <h2 class="section-title">Câu hỏi thường gặp</h2>
                <div class="faq-list">
                    <div class="faq-item">
                        <button class="faq-question">
                            Làm thế nào để theo dõi đơn hàng của tôi?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Bạn có thể theo dõi đơn hàng bằng các cách sau:</p>
                            <ul>
                                <li>Truy cập trang "Đơn hàng của tôi" sau khi đăng nhập</li>
                                <li>Kiểm tra email xác nhận đơn hàng (chúng tôi sẽ gửi cập nhật tự động)</li>
                                <li>Liên hệ hotline 1900 1234 với mã đơn hàng</li>
                                <li>Sử dụng tính năng tra cứu đơn hàng trên website</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            Thời gian giao hàng trong bao lâu?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Thời gian giao hàng phụ thuộc vào khu vực của bạn:</p>
                            <ul>
                                <li><strong>Nội thành Hà Nội, TP.HCM:</strong> 1-2 ngày làm việc</li>
                                <li><strong>Các tỉnh lân cận:</strong> 2-3 ngày làm việc</li>
                                <li><strong>Miền Trung:</strong> 3-5 ngày làm việc</li>
                                <li><strong>Miền Nam & Miền Bắc:</strong> 2-4 ngày làm việc</li>
                                <li><strong>Vùng sâu, vùng xa:</strong> 5-7 ngày làm việc</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            Chính sách đổi trả như thế nào?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Chúng tôi có chính sách đổi trả linh hoạt:</p>
                            <ul>
                                <li><strong>Thời gian đổi trả:</strong> 30 ngày kể từ ngày nhận hàng</li>
                                <li><strong>Điều kiện:</strong> Sản phẩm còn nguyên tem mác, chưa qua sử dụng</li>
                                <li><strong>Miễn phí đổi trả:</strong> Trong trường hợp sản phẩm lỗi</li>
                                <li><strong>Chi phí:</strong> Khách hàng chịu phí vận chuyển nếu đổi trả do không vừa ý</li>
                                <li><strong>Thời gian xử lý:</strong> 3-5 ngày làm việc sau khi nhận được sản phẩm</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            Có những hình thức thanh toán nào?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Chúng tôi chấp nhận các hình thức thanh toán sau:</p>
                            <ul>
                                <li><strong>Thanh toán khi nhận hàng (COD)</strong></li>
                                <li><strong>Chuyển khoản ngân hàng</strong></li>
                                <li><strong>Thẻ tín dụng/ghi nợ (Visa, MasterCard)</strong></li>
                                <li><strong>Ví điện tử (Momo, ZaloPay, VNPay)</strong></li>
                                <li><strong>Trả góp qua thẻ tín dụng</strong></li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question">
                            Làm thế nào để trở thành thành viên?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Để trở thành thành viên của Sport Fashion:</p>
                            <ul>
                                <li><strong>Đăng ký trực tuyến:</strong> Tạo tài khoản trên website</li>
                                <li><strong>Tích điểm:</strong> 1 điểm = 1.000đ, tích lũy không giới hạn</li>
                                <li><strong>Hạng thành viên:</strong>
                                    <ul>
                                        <li>Standard: Từ 0 điểm</li>
                                        <li>Silver: Từ 1.000 điểm (giảm giá 5%)</li>
                                        <li>Gold: Từ 3.000 điểm (giảm giá 10%)</li>
                                        <li>Platinum: Từ 7.000 điểm (giảm giá 15%)</li>
                                    </ul>
                                </li>
                                <li><strong>Ưu đãi đặc biệt:</strong> Nhận thông báo sớm về khuyến mãi, sinh nhật, sự kiện</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // FAQ Accordion
        document.addEventListener('DOMContentLoaded', function() {
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling;
                    const icon = this.querySelector('i');
                    
                    // Toggle current answer
                    answer.classList.toggle('show');
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
                    
                    // Close other answers
                    faqQuestions.forEach(otherQuestion => {
                        if (otherQuestion !== question) {
                            const otherAnswer = otherQuestion.nextElementSibling;
                            const otherIcon = otherQuestion.querySelector('i');
                            otherAnswer.classList.remove('show');
                            otherIcon.classList.remove('fa-chevron-up');
                            otherIcon.classList.add('fa-chevron-down');
                        }
                    });
                });
            });

            // Contact Form Handling
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Hiển thị thông báo thành công
                    alert('Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.');
                    
                    // Reset form
                    contactForm.reset();
                });
            }

            // Smooth scroll for anchor links
            const anchorLinks = document.querySelectorAll('a[href^="#"]');
            anchorLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>