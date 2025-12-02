<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../config.php";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>

    <!-- CSS CHUNG CỦA WEBSITE -->
    <link rel="stylesheet" href="/manguonmo/sportshop/assets/css/style.css">

    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f6f6f6;
            margin: 0;
            padding: 0;
        }

        /* LOGIN BOX */
        .auth-container {
            width: 380px;
            margin: 80px auto;
            padding: 30px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .auth-container h2 {
            margin-bottom: 15px;
            font-size: 28px;
            font-weight: bold;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-align: left;
        }

        .auth-form label {
            font-size: 15px;
        }

        .auth-form input {
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        .btn {
            padding: 12px;
            background: black;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            margin-top: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #555;
        }

        .btn-forgot {
            background: transparent;
            color: #007bff;
            border: none;
            padding: 8px 0;
            font-size: 14px;
            cursor: pointer;
            text-decoration: underline;
        }

        .btn-forgot:hover {
            color: #0056b3;
        }

        .auth-switch {
            margin-top: 12px;
            font-size: 14px;
        }

        .auth-switch a {
            color: #007bff;
        }

        /* Forgot Password Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }

        .close:hover {
            color: #000;
        }

        .modal-title {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }

        .step.active {
            background: #007bff;
            color: white;
        }

        .step.completed {
            background: #28a745;
            color: white;
        }

        .step-text {
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            white-space: nowrap;
        }

        .resend-code {
            text-align: center;
            margin-top: 15px;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
        }

        .resend-btn:disabled {
            color: #6c757d;
            cursor: not-allowed;
        }

        .countdown {
            color: #6c757d;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>

<body>

<!-- HEADER + NAVBAR -->
<?php include "../includes/header.php"; ?>

<!-- LOGIN FORM -->
<div class="auth-container">
    <h2>Đăng nhập</h2>

    <!-- Hiển thị thông báo -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'error'; ?>">
            <?php echo $_SESSION['message']; ?>
        </div>
        <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        endif; 
    ?>

    <form action="login_process.php" method="POST" class="auth-form">
        <label>Email:</label>
        <input type="email" name="email" required value="<?php echo isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : ''; ?>">

        <label>Mật khẩu:</label>
        <input type="password" name="password" required>

        <button type="submit" class="btn">Đăng nhập</button>

        <button type="button" class="btn-forgot" onclick="openForgotPassword()">
            Quên mật khẩu?
        </button>
    </form>

    <p class="auth-switch">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
    </p>
</div>

<!-- FORGOT PASSWORD MODAL -->
<div id="forgotPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeForgotPassword()">&times;</span>
        
        <div id="step1">
            <h3 class="modal-title">Quên mật khẩu</h3>
            
            <div class="steps">
                <div class="step active">1</div>
                <div class="step">2</div>
                <div class="step">3</div>
            </div>
            
            <div class="alert alert-info">
                Nhập email của bạn để nhận mã xác nhận
            </div>
            
            <form id="forgotPasswordForm">
                <div class="form-group">
                    <label for="resetEmail">Email:</label>
                    <input type="email" id="resetEmail" name="email" required>
                </div>
                <button type="submit" class="btn">Gửi mã xác nhận</button>
            </form>
        </div>

        <div id="step2" style="display: none;">
            <h3 class="modal-title">Xác nhận mã</h3>
            
            <div class="steps">
                <div class="step completed">1</div>
                <div class="step active">2</div>
                <div class="step">3</div>
            </div>
            
            <div class="alert alert-info">
                Chúng tôi đã gửi mã xác nhận đến email của bạn
            </div>
            
            <form id="verifyCodeForm">
                <div class="form-group">
                    <label for="verificationCode">Mã xác nhận (6 chữ số):</label>
                    <input type="text" id="verificationCode" name="code" maxlength="6" required pattern="[0-9]{6}">
                </div>
                <button type="submit" class="btn">Xác nhận</button>
            </form>
            
            <div class="resend-code">
                <button type="button" class="resend-btn" id="resendBtn" onclick="resendCode()">Gửi lại mã</button>
                <div class="countdown" id="countdown">(Có thể gửi lại sau 60 giây)</div>
            </div>
        </div>

        <div id="step3" style="display: none;">
            <h3 class="modal-title">Đặt lại mật khẩu</h3>
            
            <div class="steps">
                <div class="step completed">1</div>
                <div class="step completed">2</div>
                <div class="step active">3</div>
            </div>
            
            <form id="resetPasswordForm">
                <div class="form-group">
                    <label for="newPassword">Mật khẩu mới:</label>
                    <input type="password" id="newPassword" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Xác nhận mật khẩu:</label>
                    <input type="password" id="confirmPassword" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" class="btn">Đặt lại mật khẩu</button>
            </form>
        </div>

        <div id="successStep" style="display: none;">
            <h3 class="modal-title">Thành công!</h3>
            <div class="alert alert-success">
                Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập bằng mật khẩu mới.
            </div>
            <button type="button" class="btn" onclick="closeForgotPassword()">Đóng</button>
        </div>
    </div>
</div>

<!-- FOOTER + SCRIPT -->
<?php include "../includes/footer.php"; ?>
<?php include "../includes/scripts.html"; ?>

<script>
    // Forgot Password Modal Functions
    function openForgotPassword() {
        document.getElementById('forgotPasswordModal').style.display = 'block';
        resetForgotPasswordForm();
    }

    function closeForgotPassword() {
        document.getElementById('forgotPasswordModal').style.display = 'none';
        resetForgotPasswordForm();
    }

    function resetForgotPasswordForm() {
        document.getElementById('step1').style.display = 'block';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'none';
        document.getElementById('successStep').style.display = 'none';
        document.getElementById('forgotPasswordForm').reset();
        document.getElementById('verifyCodeForm').reset();
        document.getElementById('resetPasswordForm').reset();
    }

    function showStep(stepNumber) {
        document.getElementById('step1').style.display = stepNumber === 1 ? 'block' : 'none';
        document.getElementById('step2').style.display = stepNumber === 2 ? 'block' : 'none';
        document.getElementById('step3').style.display = stepNumber === 3 ? 'block' : 'none';
        document.getElementById('successStep').style.display = stepNumber === 4 ? 'block' : 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('forgotPasswordModal');
        if (event.target === modal) {
            closeForgotPassword();
        }
    }

    // Forgot Password Form Handling
    document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('resetEmail').value;
        
        // Gửi yêu cầu đến server
        fetch('forgot_password_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=send_code&email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStep(2);
                startCountdown();
            } else {
                alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    });

    // Verify Code Form Handling
    document.getElementById('verifyCodeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('resetEmail').value;
        const code = document.getElementById('verificationCode').value;
        
        fetch('forgot_password_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=verify_code&email=' + encodeURIComponent(email) + '&code=' + encodeURIComponent(code)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStep(3);
            } else {
                alert(data.message || 'Mã xác nhận không đúng. Vui lòng thử lại.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    });

    // Reset Password Form Handling
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('resetEmail').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword !== confirmPassword) {
            alert('Mật khẩu xác nhận không khớp.');
            return;
        }
        
        if (newPassword.length < 6) {
            alert('Mật khẩu phải có ít nhất 6 ký tự.');
            return;
        }
        
        fetch('forgot_password_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=reset_password&email=' + encodeURIComponent(email) + '&new_password=' + encodeURIComponent(newPassword)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStep(4);
            } else {
                alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    });

    // Resend Code Function
    function resendCode() {
        const email = document.getElementById('resetEmail').value;
        const resendBtn = document.getElementById('resendBtn');
        
        resendBtn.disabled = true;
        startCountdown();
        
        fetch('forgot_password_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=resend_code&email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        });
    }

    // Countdown Timer
    function startCountdown() {
        const resendBtn = document.getElementById('resendBtn');
        const countdownElement = document.getElementById('countdown');
        let timeLeft = 60;
        
        resendBtn.disabled = true;
        
        const countdown = setInterval(() => {
            countdownElement.textContent = `(Có thể gửi lại sau ${timeLeft} giây)`;
            timeLeft--;
            
            if (timeLeft < 0) {
                clearInterval(countdown);
                resendBtn.disabled = false;
                countdownElement.textContent = '';
            }
        }, 1000);
    }

    // Auto-tab for verification code
    document.getElementById('verificationCode').addEventListener('input', function(e) {
        if (this.value.length === 6) {
            document.getElementById('verifyCodeForm').dispatchEvent(new Event('submit'));
        }
    });
</script>

</body>
</html>