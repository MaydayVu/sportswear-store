<?php
session_start();
include "config.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chính sách vận chuyển - Sport Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .shipping-page {
            padding: 80px 0 60px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 60px 0;
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

        .shipping-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .shipping-nav {
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            padding: 0;
        }

        .nav-pills .nav-link {
            padding: 20px 25px;
            border-radius: 0;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-pills .nav-link i {
            width: 20px;
            text-align: center;
        }

        .nav-pills .nav-link:hover {
            background: #e9ecef;
            color: #000;
        }

        .nav-pills .nav-link.active {
            background: #000;
            color: white;
            border-color: #000;
        }

        .shipping-info {
            padding: 40px;
        }

        .info-section {
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #000;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 4px solid #000;
            margin-bottom: 20px;
        }

        .feature-icon {
            font-size: 2rem;
            color: #000;
            margin-bottom: 15px;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #000;
        }

        .pricing-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }

        .table-header {
            background: #000;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .table-body {
            padding: 0;
        }

        .table-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:nth-child(even) {
            background: #f8f9fa;
        }

        .area-name {
            font-weight: 600;
            color: #495057;
        }

        .delivery-time {
            color: #666;
            font-size: 0.9rem;
        }

        .shipping-fee {
            font-weight: 700;
            color: #000;
        }

        .free-shipping {
            color: #28a745;
            font-weight: 600;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #000;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -38px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #000;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #000;
        }

        .timeline-title {
            font-weight: 700;
            margin-bottom: 10px;
            color: #000;
        }

        .faq-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .faq-question {
            padding: 20px;
            background: #f8f9fa;
            border: none;
            width: 100%;
            text-align: left;
            font-weight: 600;
            color: #000;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .faq-question:hover {
            background: #e9ecef;
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s;
        }

        .faq-answer.show {
            padding: 20px;
            max-height: 500px;
        }

        .contact-info {
            background: linear-gradient(135deg, #000 0%, #333 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
        }

        .contact-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #fff;
        }

        .contact-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .contact-phone {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .contact-email {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .badge-new {
            background: #e4002b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .shipping-info {
                padding: 25px;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .nav-pills .nav-link {
                padding: 15px 20px;
            }

            .contact-phone {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/header.php"; ?>

    <div class="shipping-page">
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">Chính sách vận chuyển</h1>
                <p class="page-subtitle">
                    Giao hàng nhanh chóng - Đảm bảo chất lượng - Phục vụ tận tâm
                </p>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="shipping-content">
                        <div class="shipping-nav">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#v-pills-shipping" type="button">
                                    <i class="fas fa-shipping-fast"></i>
                                    Vận chuyển
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#v-pills-delivery" type="button">
                                    <i class="fas fa-truck"></i>
                                    Giao hàng
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#v-pills-tracking" type="button">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Theo dõi đơn hàng
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#v-pills-faq" type="button">
                                    <i class="fas fa-question-circle"></i>
                                    Câu hỏi thường gặp
                                </button>
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#v-pills-contact" type="button">
                                    <i class="fas fa-headset"></i>
                                    Hỗ trợ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="shipping-content">
                        <div class="shipping-info">
                            <div class="tab-content" id="v-pills-tabContent">
                                <!-- Tab Vận chuyển -->
                                <div class="tab-pane fade show active" id="v-pills-shipping">
                                    <div class="info-section">
                                        <h2 class="section-title">Chính sách vận chuyển</h2>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <div class="feature-card">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-rocket"></i>
                                                    </div>
                                                    <h4 class="feature-title">Giao hàng siêu tốc</h4>
                                                    <p class="mb-0">Giao hàng trong 2 giờ tại nội thành Hà Nội và TP.HCM với đơn hàng từ 500.000₫</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div class="feature-card">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-truck"></i>
                                                    </div>
                                                    <h4 class="feature-title">Miễn phí vận chuyển</h4>
                                                    <p class="mb-0">Miễn phí vận chuyển toàn quốc với đơn hàng từ 1.000.000₫</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div class="feature-card">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-shield-alt"></i>
                                                    </div>
                                                    <h4 class="feature-title">Đảm bảo an toàn</h4>
                                                    <p class="mb-0">Đóng gói cẩn thận, đảm bảo sản phẩm nguyên vẹn khi đến tay khách hàng</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div class="feature-card">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </div>
                                                    <h4 class="feature-title">Đổi trả dễ dàng</h4>
                                                    <p class="mb-0">Đổi trả trong 30 ngày nếu sản phẩm không vừa ý</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-section">
                                        <h3 class="section-title">Bảng phí vận chuyển</h3>
                                        <div class="pricing-table">
                                            <div class="table-header">
                                                <h4 class="mb-0">PHÍ VẬN CHUYỂN THEO KHU VỰC</h4>
                                            </div>
                                            <div class="table-body">
                                                <div class="table-row">
                                                    <div class="area-name">Nội thành Hà Nội, TP.HCM</div>
                                                    <div class="delivery-time">1-2 ngày</div>
                                                    <div class="shipping-fee">20.000₫</div>
                                                </div>
                                                <div class="table-row">
                                                    <div class="area-name">Các tỉnh lân cận</div>
                                                    <div class="delivery-time">2-3 ngày</div>
                                                    <div class="shipping-fee">25.000₫</div>
                                                </div>
                                                <div class="table-row">
                                                    <div class="area-name">Miền Bắc</div>
                                                    <div class="delivery-time">3-4 ngày</div>
                                                    <div class="shipping-fee">30.000₫</div>
                                                </div>
                                                <div class="table-row">
                                                    <div class="area-name">Miền Trung</div>
                                                    <div class="delivery-time">4-5 ngày</div>
                                                    <div class="shipping-fee">35.000₫</div>
                                                </div>
                                                <div class="table-row">
                                                    <div class="area-name">Miền Nam</div>
                                                    <div class="delivery-time">3-4 ngày</div>
                                                    <div class="shipping-fee">30.000₫</div>
                                                </div>
                                                <div class="table-row">
                                                    <div class="area-name">Vùng sâu vùng xa</div>
                                                    <div class="delivery-time">5-7 ngày</div>
                                                    <div class="shipping-fee">40.000₫</div>
                                                </div>
                                                <div class="table-row" style="background: #e8f5e8;">
                                                    <div class="area-name">Đơn hàng từ 1.000.000₫</div>
                                                    <div class="delivery-time">Toàn quốc</div>
                                                    <div class="free-shipping">MIỄN PHÍ</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Giao hàng -->
                                <div class="tab-pane fade" id="v-pills-delivery">
                                    <div class="info-section">
                                        <h2 class="section-title">Quy trình giao hàng</h2>
                                        
                                        <div class="timeline">
                                            <div class="timeline-item">
                                                <h4 class="timeline-title">Bước 1: Đặt hàng thành công</h4>
                                                <p>Sau khi đặt hàng, hệ thống sẽ gửi email xác nhận đơn hàng đến bạn trong vòng 5 phút.</p>
                                            </div>
                                            <div class="timeline-item">
                                                <h4 class="timeline-title">Bước 2: Xác nhận đơn hàng</h4>
                                                <p>Nhân viên chúng tôi sẽ liên hệ xác nhận đơn hàng trong vòng 1 giờ làm việc (8:00 - 18:00).</p>
                                            </div>
                                            <div class="timeline-item">
                                                <h4 class="timeline-title">Bước 3: Đóng gói & Xuất kho</h4>
                                                <p>Đơn hàng được đóng gói cẩn thận và xuất kho trong vòng 24 giờ sau khi xác nhận.</p>
                                            </div>
                                            <div class="timeline-item">
                                                <h4 class="timeline-title">Bước 4: Vận chuyển</h4>
                                                <p>Đơn hàng được chuyển đến đơn vị vận chuyển và cập nhật trạng thái liên tục.</p>
                                            </div>
                                            <div class="timeline-item">
                                                <h4 class="timeline-title">Bước 5: Giao hàng & Nhận hàng</h4>
                                                <p>Nhân viên giao hàng sẽ liên hệ trước 30 phút. Vui lòng kiểm tra sản phẩm trước khi nhận.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-section">
                                        <h3 class="section-title">Thời gian giao hàng dự kiến</h3>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="feature-card">
                                                    <h4 class="feature-title">Giao hàng tiêu chuẩn</h4>
                                                    <ul>
                                                        <li>Hà Nội, TP.HCM: 1-2 ngày</li>
                                                        <li>Tỉnh thành lân cận: 2-3 ngày</li>
                                                        <li>Miền xa: 3-7 ngày</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="feature-card">
                                                    <h4 class="feature-title">Giao hàng nhanh <span class="badge-new">MỚI</span></h4>
                                                    <ul>
                                                        <li>Nội thành: 2-4 giờ</li>
                                                        <li>Ngoại thành: 24 giờ</li>
                                                        <li>Phí: 40.000₫</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Theo dõi đơn hàng -->
                                <div class="tab-pane fade" id="v-pills-tracking">
                                    <div class="info-section">
                                        <h2 class="section-title">Theo dõi đơn hàng</h2>
                                        
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="feature-card">
                                                    <h4 class="feature-title">Cách theo dõi đơn hàng</h4>
                                                    <p>Bạn có thể theo dõi trạng thái đơn hàng theo các cách sau:</p>
                                                    <ol>
                                                        <li><strong>Qua email:</strong> Hệ thống sẽ gửi email cập nhật tự động</li>
                                                        <li><strong>Trên website:</strong> Đăng nhập và vào mục "Đơn hàng của tôi"</li>
                                                        <li><strong>Qua ứng dụng:</strong> Tải app Sport Fashion để theo dõi trực tiếp</li>
                                                        <li><strong>Hotline:</strong> Gọi 1900 1234 để được hỗ trợ</li>
                                                    </ol>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="feature-card text-center">
                                                    <div class="feature-icon">
                                                        <i class="fas fa-qrcode"></i>
                                                    </div>
                                                    <h4 class="feature-title">Quét mã QR</h4>
                                                    <p class="mb-3">Quét mã QR trong email xác nhận để theo dõi đơn hàng</p>
                                                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                                                        <div style="width: 150px; height: 150px; background: #ddd; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: #666;">
                                                            Mã QR
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-section">
                                        <h3 class="section-title">Các trạng thái đơn hàng</h3>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="d-flex align-items-center p-3 border rounded">
                                                    <i class="fas fa-clock text-warning me-3 fs-4"></i>
                                                    <div>
                                                        <h6 class="mb-1">Chờ xác nhận</h6>
                                                        <small class="text-muted">Đơn hàng đang chờ xác nhận</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="d-flex align-items-center p-3 border rounded">
                                                    <i class="fas fa-check text-primary me-3 fs-4"></i>
                                                    <div>
                                                        <h6 class="mb-1">Đã xác nhận</h6>
                                                        <small class="text-muted">Đơn hàng đã được xác nhận</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="d-flex align-items-center p-3 border rounded">
                                                    <i class="fas fa-box text-info me-3 fs-4"></i>
                                                    <div>
                                                        <h6 class="mb-1">Đang giao hàng</h6>
                                                        <small class="text-muted">Đơn hàng đang trên đường giao</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="d-flex align-items-center p-3 border rounded">
                                                    <i class="fas fa-check-circle text-success me-3 fs-4"></i>
                                                    <div>
                                                        <h6 class="mb-1">Giao hàng thành công</h6>
                                                        <small class="text-muted">Đơn hàng đã giao thành công</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab FAQ -->
                                <div class="tab-pane fade" id="v-pills-faq">
                                    <div class="info-section">
                                        <h2 class="section-title">Câu hỏi thường gặp</h2>
                                        
                                        <div class="faq-list">
                                            <div class="faq-item">
                                                <button class="faq-question">
                                                    Thời gian giao hàng trong bao lâu?
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <div class="faq-answer">
                                                    <p>Thời gian giao hàng phụ thuộc vào khu vực của bạn:</p>
                                                    <ul>
                                                        <li>Nội thành Hà Nội, TP.HCM: 1-2 ngày</li>
                                                        <li>Các tỉnh lân cận: 2-3 ngày</li>
                                                        <li>Miền xa: 3-7 ngày</li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="faq-item">
                                                <button class="faq-question">
                                                    Làm thế nào để được miễn phí vận chuyển?
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <div class="faq-answer">
                                                    <p>Bạn sẽ được miễn phí vận chuyển khi:</p>
                                                    <ul>
                                                        <li>Đơn hàng có giá trị từ 1.000.000₫ trở lên</li>
                                                        <li>Là khách hàng thân thiết (cấp độ Gold trở lên)</li>
                                                        <li>Trong các chương trình khuyến mãi đặc biệt</li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="faq-item">
                                                <button class="faq-question">
                                                    Tôi có thể thay đổi địa chỉ giao hàng sau khi đặt hàng?
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <div class="faq-answer">
                                                    <p>Có, bạn có thể thay đổi địa chỉ giao hàng trong vòng 1 giờ sau khi đặt hàng bằng cách:</p>
                                                    <ul>
                                                        <li>Gọi hotline 1900 1234</li>
                                                        <li>Chat với nhân viên hỗ trợ</li>
                                                        <li>Gửi email đến support@sportshop.com</li>
                                                    </ul>
                                                    <p class="mb-0"><strong>Lưu ý:</strong> Sau khi đơn hàng đã được xác nhận, việc thay đổi địa chỉ có thể phát sinh phí.</p>
                                                </div>
                                            </div>

                                            <div class="faq-item">
                                                <button class="faq-question">
                                                    Làm thế nào để theo dõi đơn hàng?
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <div class="faq-answer">
                                                    <p>Bạn có thể theo dõi đơn hàng bằng nhiều cách:</p>
                                                    <ul>
                                                        <li><strong>Online:</strong> Đăng nhập tài khoản → Đơn hàng của tôi</li>
                                                        <li><strong>Email:</strong> Kiểm tra email cập nhật tự động</li>
                                                        <li><strong>Hotline:</strong> Gọi 1900 1234 với mã đơn hàng</li>
                                                        <li><strong>App:</strong> Tải ứng dụng Sport Fashion</li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="faq-item">
                                                <button class="faq-question">
                                                    Tôi có phải trả thêm phí khi nhận hàng không?
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <div class="faq-answer">
                                                    <p>Không, giá trị hiển thị khi đặt hàng là tổng số tiền bạn phải thanh toán, trừ các trường hợp:</p>
                                                    <ul>
                                                        <li>Thay đổi địa chỉ giao hàng sau khi đặt</li>
                                                        <li>Yêu cầu giao hàng ngoài giờ hành chính</li>
                                                        <li>Giao hàng đến vùng đảo, vùng sâu vùng xa</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Hỗ trợ -->
                                <div class="tab-pane fade" id="v-pills-contact">
                                    <div class="info-section">
                                        <h2 class="section-title">Hỗ trợ khách hàng</h2>
                                        
                                        <div class="row mb-5">
                                            <div class="col-md-6 mb-4">
                                                <div class="contact-info">
                                                    <div class="contact-icon">
                                                        <i class="fas fa-phone-alt"></i>
                                                    </div>
                                                    <h3 class="contact-title">Hotline hỗ trợ</h3>
                                                    <div class="contact-phone">1900 1234</div>
                                                    <p class="contact-email">support@sportshop.com</p>
                                                    <p>Thời gian làm việc: 7:00 - 22:00 (T2 - CN)</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div class="contact-info">
                                                    <div class="contact-icon">
                                                        <i class="fas fa-comments"></i>
                                                    </div>
                                                    <h3 class="contact-title">Chat trực tuyến</h3>
                                                    <div class="contact-phone">24/7</div>
                                                    <p class="contact-email">Chat ngay trên website</p>
                                                    <p>Hỗ trợ nhanh chóng, giải đáp mọi thắc mắc</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="feature-card">
                                            <h4 class="feature-title">Thông tin liên hệ khác</h4>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-map-marker-alt me-2"></i>Văn phòng Hà Nội</h6>
                                                    <p class="ms-4 mb-3">Số 123, đường ABC, Quận XYZ, Hà Nội</p>
                                                    
                                                    <h6><i class="fas fa-map-marker-alt me-2"></i>Văn phòng TP.HCM</h6>
                                                    <p class="ms-4 mb-3">Số 456, đường DEF, Quận UVW, TP.HCM</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-envelope me-2"></i>Email</h6>
                                                    <p class="ms-4 mb-3">info@sportshop.com</p>
                                                    
                                                    <h6><i class="fas fa-globe me-2"></i>Website</h6>
                                                    <p class="ms-4 mb-0">www.sportshop.com</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

            // Smooth scroll for anchor links
            const navPills = document.querySelectorAll('.nav-pills .nav-link');
            navPills.forEach(pill => {
                pill.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('data-bs-target'));
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