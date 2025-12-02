<?php
session_start();
include "../config.php";

header('Content-Type: application/json');

// Hàm gửi email (giả lập - trong thực tế cần tích hợp email service)
function sendEmail($to, $subject, $message) {
    // Trong môi trường production, sử dụng PHPMailer hoặc mail() function
    // Ở đây chúng ta giả lập gửi email thành công
    error_log("Sending email to: $to - Subject: $subject - Message: $message");
    return true;
    
    // Code thực tế để gửi email:
    /*
    $headers = "From: no-reply@sportshop.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $message, $headers);
    */
}

// Hàm tạo mã xác nhận ngẫu nhiên
function generateVerificationCode() {
    return sprintf("%06d", mt_rand(1, 999999));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'send_code':
        $email = trim($_POST['email']);
        
        // Kiểm tra email có tồn tại không
        $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Email không tồn tại trong hệ thống.']);
            exit;
        }
        
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        
        // Tạo mã xác nhận
        $verification_code = generateVerificationCode();
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Lưu mã xác nhận vào database
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $email, $verification_code, $expires_at);
        
        if ($stmt->execute()) {
            // Gửi email (giả lập)
            $subject = "Mã xác nhận đặt lại mật khẩu - Sport Fashion";
            $message = "
                <h2>Đặt lại mật khẩu</h2>
                <p>Xin chào {$user['fullname']},</p>
                <p>Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                <p><strong>Mã xác nhận của bạn là: {$verification_code}</strong></p>
                <p>Mã này sẽ hết hạn sau 15 phút.</p>
                <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                <br>
                <p>Trân trọng,<br>Đội ngũ Sport Fashion</p>
            ";
            
            if (sendEmail($email, $subject, $message)) {
                $_SESSION['reset_email'] = $email;
                echo json_encode(['success' => true, 'message' => 'Mã xác nhận đã được gửi đến email của bạn.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại sau.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại.']);
        }
        break;
        
    case 'verify_code':
        $email = trim($_POST['email']);
        $code = trim($_POST['code']);
        
        // Kiểm tra mã xác nhận
        $stmt = $conn->prepare("
            SELECT pr.id, pr.user_id 
            FROM password_resets pr 
            WHERE pr.email = ? AND pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
            ORDER BY pr.created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $reset_data = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $reset_data['user_id'];
            $_SESSION['reset_verified'] = true;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mã xác nhận không đúng hoặc đã hết hạn.']);
        }
        break;
        
    case 'resend_code':
        $email = trim($_POST['email']);
        
        // Đánh dấu mã cũ là đã sử dụng
        $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        // Gửi lại mã mới (sử dụng lại code từ case 'send_code')
        $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            $verification_code = generateVerificationCode();
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $email, $verification_code, $expires_at);
            
            if ($stmt->execute()) {
                $subject = "Mã xác nhận mới - Sport Fashion";
                $message = "
                    <h2>Mã xác nhận mới</h2>
                    <p>Xin chào {$user['fullname']},</p>
                    <p>Dưới đây là mã xác nhận mới của bạn:</p>
                    <p><strong>Mã xác nhận: {$verification_code}</strong></p>
                    <p>Mã này sẽ hết hạn sau 15 phút.</p>
                    <br>
                    <p>Trân trọng,<br>Đội ngũ Sport Fashion</p>
                ";
                
                if (sendEmail($email, $subject, $message)) {
                    echo json_encode(['success' => true, 'message' => 'Mã xác nhận mới đã được gửi.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại sau.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Email không tồn tại.']);
        }
        break;
        
    case 'reset_password':
        $email = trim($_POST['email']);
        $new_password = $_POST['new_password'];
        
        // Kiểm tra xác nhận
        if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
            echo json_encode(['success' => false, 'message' => 'Phiên làm việc không hợp lệ.']);
            exit;
        }
        
        // Cập nhật mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Đánh dấu mã reset là đã sử dụng
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // Xóa session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_verified']);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi đặt lại mật khẩu.']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        break;
}

$conn->close();
?>