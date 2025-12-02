<?php
session_start();
include "config.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trung tâm trợ giúp - Sport Fashion</title>
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

        .help-page {
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

        .help-search {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            text-align: center;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input {
            border-radius: 25px;
            padding: 15px 25px;
            font-size: 1.1rem;
            border: 2px solid var(--border-color);
        }

        .search-input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(228, 0, 43, 0.25);
        }

        .btn-search {
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 25px;
            padding: 15px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-search:hover {
            background: #c40023;
            color: white;
        }

        /* Help Categories */
        .help-categories {
            margin-bottom: 50px;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .category-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            border-color: var(--accent-color);
        }

        .category-icon {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .category-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .category-desc {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .btn-category {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-category:hover {
            background: var(--accent-color);
            color: white;
        }

        /* FAQ Section */
        .faq-section {
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 40px;
            color: var(--primary-color);
        }

        .faq-categories {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .faq-category-btn {
            background: white;
            border: 2px solid var(--border-color);
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            color: #495057;
            transition: all 0.3s;
            cursor: pointer;
        }

        .faq-category-btn.active,
        .faq-category-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .faq-list {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: white;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
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
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .faq-answer.show {
            padding: 25px;
            max-height: 500px;
        }

        .faq-answer-content {
            line-height: 1.6;
            color: #495057;
        }

        .faq-answer-content ol,
        .faq-answer-content ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .faq-answer-content li {
            margin-bottom: 8px;
        }

        /* Contact Section */
        .contact-section {
            background: white;
            border-radius: 15px;
            padding: 50px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 50px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .contact-info {
            padding: 30px;
            background: var(--light-bg);
            border-radius: 12px;
        }

        .contact-method {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .contact-method:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .contact-details h4 {
            margin: 0 0 5px 0;
            font-weight: 700;
            color: var(--primary-color);
        }

        .contact-details p {
            margin: 0;
            color: #666;
        }

        .contact-form .form-group {
            margin-bottom: 20px;
        }

        .contact-form label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .contact-form input,
        .contact-form select,
        .contact-form textarea {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .contact-form input:focus,
        .contact-form select:focus,
        .contact-form textarea:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(228, 0, 43, 0.25);
        }

        .btn-submit {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            background: #c40023;
            color: white;
        }

        /* Quick Links */
        .quick-links {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .link-item {
            text-align: center;
            padding: 20px;
            background: var(--light-bg);
            border-radius: 8px;
            transition: all 0.3s;
        }

        .link-item:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .link-item:hover a {
            color: white;
        }

        .link-item a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .help-search {
                padding: 30px 20px;
            }

            .search-input {
                font-size: 1rem;
                padding: 12px 20px;
            }

            .category-grid {
                grid-template-columns: 1fr;
            }

            .contact-section {
                padding: 30px 20px;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .faq-question {
                padding: 15px 20px;
                font-size: 1rem;
            }

            .quick-links {
                padding: 30px 20px;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 40px 0;
            }

            .faq-categories {
                flex-direction: column;
                align-items: center;
            }

            .faq-category-btn {
                width: 200px;
                text-align: center;
            }

            .links-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="help-page">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">💬 Trung tâm trợ giúp</h1>
                <p class="page-subtitle">
                    Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7
                </p>
            </div>
        </div>

        <div class="container">
            <!-- Search Section -->
            <div class="help-search">
                <div class="search-container">
                    <h3 class="mb-4">Bạn cần hỗ trợ điều gì?</h3>
                    <div class="input-group">
                        <input type="text" class="form-control search-input" placeholder="Tìm kiếm câu hỏi thường gặp, hướng dẫn...">
                        <button class="btn btn-search" type="button">
                            <i class="fas fa-search me-2"></i>Tìm kiếm
                        </button>
                    </div>
                    <p class="text-muted mt-3 mb-0">Ví dụ: "đổi trả sản phẩm", "kiểm tra đơn hàng", "thanh toán"</p>
                </div>
            </div>

            <!-- Help Categories -->
            <div class="help-categories">
                <h2 class="section-title">Chúng tôi có thể giúp gì cho bạn?</h2>
                <div class="category-grid">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <h3 class="category-title">Đặt hàng & Thanh toán</h3>
                        <p class="category-desc">
                            Hướng dẫn đặt hàng, phương thức thanh toán và xử lý đơn hàng
                        </p>
                        <a href="#ordering" class="btn-category">Tìm hiểu thêm</a>
                    </div>

                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h3 class="category-title">Vận chuyển & Giao hàng</h3>
                        <p class="category-desc">
                            Thông tin vận chuyển, thời gian giao hàng và phí vận chuyển
                        </p>
                        <a href="#shipping" class="btn-category">Tìm hiểu thêm</a>
                    </div>

                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h3 class="category-title">Đổi trả & Hoàn tiền</h3>
                        <p class="category-desc">
                            Chính sách đổi trả, hoàn tiền và bảo hành sản phẩm
                        </p>
                        <a href="#returns" class="btn-category">Tìm hiểu thêm</a>
                    </div>

                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3 class="category-title">Tài khoản & Bảo mật</h3>
                        <p class="category-desc">
                            Quản lý tài khoản, bảo mật thông tin và quyền riêng tư
                        </p>
                        <a href="#account" class="btn-category">Tìm hiểu thêm</a>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <h2 class="section-title">Câu hỏi thường gặp</h2>
                
                <div class="faq-categories">
                    <button class="faq-category-btn active" data-category="all">Tất cả</button>
                    <button class="faq-category-btn" data-category="ordering">Đặt hàng</button>
                    <button class="faq-category-btn" data-category="shipping">Vận chuyển</button>
                    <button class="faq-category-btn" data-category="returns">Đổi trả</button>
                    <button class="faq-category-btn" data-category="account">Tài khoản</button>
                </div>

                <div class="faq-list">
                    <!-- Ordering FAQs -->
                    <div class="faq-item" data-category="ordering">
                        <button class="faq-question">
                            Làm thế nào để đặt hàng?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Để đặt hàng, bạn có thể làm theo các bước sau:</p>
                                <ol>
                                    <li>Tìm sản phẩm bạn muốn mua</li>
                                    <li>Chọn size và số lượng phù hợp</li>
                                    <li>Nhấn "Thêm vào giỏ hàng"</li>
                                    <li>Đi đến trang giỏ hàng và nhấn "Thanh toán"</li>
                                    <li>Điền thông tin giao hàng và chọn phương thức thanh toán</li>
                                    <li>Xác nhận đơn hàng</li>
                                </ol>
                                <p>Bạn sẽ nhận được email xác nhận đơn hàng trong vòng 5 phút.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-category="ordering">
                        <button class="faq-question">
                            Các phương thức thanh toán được chấp nhận?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Chúng tôi chấp nhận các phương thức thanh toán sau:</p>
                                <ul>
                                    <li>Thanh toán khi nhận hàng (COD)</li>
                                    <li>Chuyển khoản ngân hàng</li>
                                    <li>Thẻ tín dụng/ghi nợ (Visa, MasterCard)</li>
                                    <li>Ví điện tử (Momo, ZaloPay, VNPay)</li>
                                    <li>Internet Banking</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping FAQs -->
                    <div class="faq-item" data-category="shipping">
                        <button class="faq-question">
                            Thời gian giao hàng trong bao lâu?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Thời gian giao hàng phụ thuộc vào khu vực của bạn:</p>
                                <ul>
                                    <li><strong>Hà Nội, TP.HCM:</strong> 1-2 ngày làm việc</li>
                                    <li><strong>Các tỉnh thành lân cận:</strong> 2-3 ngày làm việc</li>
                                    <li><strong>Miền Trung, Miền Nam:</strong> 3-5 ngày làm việc</li>
                                    <li><strong>Vùng sâu, vùng xa:</strong> 5-7 ngày làm việc</li>
                                </ul>
                                <p>Đơn hàng sẽ được xử lý trong vòng 24 giờ sau khi xác nhận thanh toán.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-category="shipping">
                        <button class="faq-question">
                            Phí vận chuyển được tính như thế nào?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Phí vận chuyển được tính dựa trên:</p>
                                <ul>
                                    <li><strong>Khu vực giao hàng:</strong> 
                                        <br>- Nội thành: 20.000₫
                                        <br>- Ngoại thành: 25.000₫ - 40.000₫
                                    </li>
                                    <li><strong>Giá trị đơn hàng:</strong> Miễn phí vận chuyển cho đơn hàng từ 500.000₫</li>
                                    <li><strong>Trọng lượng:</strong> Áp dụng cho đơn hàng có trọng lượng lớn</li>
                                </ul>
                                <p>Phí vận chuyển chính xác sẽ được hiển thị khi bạn tiến hành thanh toán.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Returns FAQs -->
                    <div class="faq-item" data-category="returns">
                        <button class="faq-question">
                            Chính sách đổi trả như thế nào?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Chúng tôi có chính sách đổi trả linh hoạt:</p>
                                <ul>
                                    <li><strong>Thời gian đổi trả:</strong> 30 ngày kể từ ngày nhận hàng</li>
                                    <li><strong>Điều kiện đổi trả:</strong>
                                        <br>- Sản phẩm còn nguyên tem, nhãn mác
                                        <br>- Chưa qua sử dụng
                                        <br>- Còn đầy đủ hộp, phụ kiện đi kèm
                                    </li>
                                    <li><strong>Miễn phí đổi trả:</strong> Trong trường hợp lỗi từ nhà sản xuất</li>
                                    <li><strong>Phí đổi trả:</strong> 20.000₫ nếu đổi do không vừa ý</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Account FAQs -->
                    <div class="faq-item" data-category="account">
                        <button class="faq-question">
                            Làm thế nào để đổi mật khẩu?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Để đổi mật khẩu tài khoản, làm theo các bước:</p>
                                <ol>
                                    <li>Đăng nhập vào tài khoản của bạn</li>
                                    <li>Nhấn vào tên tài khoản ở góc trên bên phải</li>
                                    <li>Chọn "Thông tin tài khoản"</li>
                                    <li>Nhấn "Đổi mật khẩu"</li>
                                    <li>Nhập mật khẩu cũ và mật khẩu mới</li>
                                    <li>Xác nhận thay đổi</li>
                                </ol>
                                <p>Nếu quên mật khẩu, nhấn "Quên mật khẩu" ở trang đăng nhập.</p>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-category="account">
                        <button class="faq-question">
                            Làm thế nào để theo dõi đơn hàng?
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <p>Bạn có thể theo dõi đơn hàng bằng nhiều cách:</p>
                                <ul>
                                    <li><strong>Trên website:</strong> Đăng nhập → Đơn hàng của tôi</li>
                                    <li><strong>Qua email:</strong> Kiểm tra email cập nhật tự động</li>
                                    <li><strong>Hotline:</strong> Gọi 1900 1234 với mã đơn hàng</li>
                                    <li><strong>Zalo OA:</strong> Kết nối với Zalo Official Account</li>
                                </ul>
                                <p>Mỗi đơn hàng sẽ có mã theo dõi riêng để bạn dễ dàng kiểm tra trạng thái.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="contact-section">
                <h2 class="section-title mb-5">Liên hệ với chúng tôi</h2>
                
                <div class="contact-grid">
                    <div class="contact-info">
                        <h3 class="mb-4">Thông tin liên hệ</h3>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Hotline hỗ trợ</h4>
                                <p>1900 1234</p>
                                <small class="text-muted">7:00 - 22:00 (Thứ 2 - Chủ nhật)</small>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Email hỗ trợ</h4>
                                <p>support@sportshop.com</p>
                                <small class="text-muted">Phản hồi trong 24h</small>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Chat trực tuyến</h4>
                                <p>Hỗ trợ 24/7</p>
                                <small class="text-muted">Trả lời ngay lập tức</small>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Văn phòng</h4>
                                <p>Số 123, Đường ABC, Quận XYZ, Hà Nội</p>
                                <small class="text-muted">8:00 - 17:00 (Thứ 2 - Thứ 6)</small>
                            </div>
                        </div>
                    </div>

                    <div class="contact-form">
                        <h3 class="mb-4">Gửi yêu cầu hỗ trợ</h3>
                        <form id="supportForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fullname">Họ và tên *</label>
                                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="phone">Số điện thoại</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>

                            <div class="form-group">
                                <label for="category">Danh mục hỗ trợ *</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Chọn danh mục</option>
                                    <option value="ordering">Đặt hàng & Thanh toán</option>
                                    <option value="shipping">Vận chuyển & Giao hàng</option>
                                    <option value="returns">Đổi trả & Hoàn tiền</option>
                                    <option value="account">Tài khoản & Bảo mật</option>
                                    <option value="product">Sản phẩm & Chất lượng</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="subject">Tiêu đề *</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>

                            <div class="form-group">
                                <label for="message">Nội dung *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>

                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="quick-links">
                <h3 class="text-center mb-4">Liên kết nhanh</h3>
                <div class="links-grid">
                    <div class="link-item">
                        <a href="shipping.php">
                            <i class="fas fa-shipping-fast me-2"></i>Chính sách vận chuyển
                        </a>
                    </div>
                    <div class="link-item">
                        <a href="return_policy.php">
                            <i class="fas fa-exchange-alt me-2"></i>Chính sách đổi trả
                        </a>
                    </div>
                    <div class="link-item">
                        <a href="privacy.php">
                            <i class="fas fa-shield-alt me-2"></i>Chính sách bảo mật
                        </a>
                    </div>
                    <div class="link-item">
                        <a href="terms.php">
                            <i class="fas fa-file-contract me-2"></i>Điều khoản sử dụng
                        </a>
                    </div>
                    <div class="link-item">
                        <a href="about.php">
                            <i class="fas fa-info-circle me-2"></i>Về chúng tôi
                        </a>
                    </div>
                    <div class="link-item">
                        <a href="stores.php">
                            <i class="fas fa-store me-2"></i>Hệ thống cửa hàng
                        </a>
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
            const faqCategoryBtns = document.querySelectorAll('.faq-category-btn');
            
            // FAQ toggle
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

            // FAQ category filter
            faqCategoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const category = this.getAttribute('data-category');
                    
                    // Update active button
                    faqCategoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter FAQ items
                    const faqItems = document.querySelectorAll('.faq-item');
                    faqItems.forEach(item => {
                        if (category === 'all' || item.getAttribute('data-category') === category) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });

            // Search functionality
            const searchInput = document.querySelector('.search-input');
            const searchBtn = document.querySelector('.btn-search');
            
            function performSearch() {
                const query = searchInput.value.toLowerCase().trim();
                if (query === '') return;
                
                const faqItems = document.querySelectorAll('.faq-item');
                let foundResults = false;
                
                faqItems.forEach(item => {
                    const question = item.querySelector('.faq-question').textContent.toLowerCase();
                    const answer = item.querySelector('.faq-answer-content').textContent.toLowerCase();
                    
                    if (question.includes(query) || answer.includes(query)) {
                        item.style.display = 'block';
                        item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        foundResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                if (!foundResults) {
                    alert('Không tìm thấy kết quả phù hợp với từ khóa: "' + query + '"');
                }
            }
            
            searchBtn.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });

            // Support form handling
            const supportForm = document.getElementById('supportForm');
            supportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Simulate form submission
                const formData = new FormData(this);
                const formObject = Object.fromEntries(formData);
                
                // Here you would typically send the data to a server
                console.log('Support request:', formObject);
                
                // Show success message
                alert('Cảm ơn bạn! Yêu cầu hỗ trợ đã được gửi thành công. Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất.');
                supportForm.reset();
            });

            // Smooth scroll for category links
            document.querySelectorAll('.btn-category').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    
                    if (targetSection) {
                        // Activate corresponding FAQ category
                        const category = targetId.replace('#', '');
                        const categoryBtn = document.querySelector(`[data-category="${category}"]`);
                        if (categoryBtn) {
                            categoryBtn.click();
                        }
                        
                        targetSection.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });
    </script>
</body>
</html>