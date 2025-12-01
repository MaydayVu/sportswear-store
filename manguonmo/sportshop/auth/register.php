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
    <title>Đăng ký</title>

    <!-- CSS CHUNG CỦA WEBSITE -->
    <link rel="stylesheet" href="/manguonmo/sportshop/assets/css/style.css">

    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f6f6f6;
            margin: 0;
            padding: 0;
        }

        /* REGISTER BOX */
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
        }

        .btn:hover {
            background: #555;
        }

        .auth-switch {
            margin-top: 12px;
            font-size: 14px;
        }

        .auth-switch a {
            color: #007bff;
        }

        .msg-error {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .msg-success {
            color: green;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

<!-- HEADER -->
<?php include "../includes/header.php"; ?>

<!-- REGISTER FORM -->
<div class="auth-container">

    <h2>Đăng ký tài khoản</h2>

    <!-- THÔNG BÁO -->
    <?php if (!empty($_SESSION["error"])): ?>
        <p class="msg-error"><?= $_SESSION["error"]; unset($_SESSION["error"]); ?></p>
    <?php endif; ?>

    <?php if (!empty($_SESSION["success"])): ?>
        <p class="msg-success"><?= $_SESSION["success"]; unset($_SESSION["success"]); ?></p>
    <?php endif; ?>

    <!-- FORM -->
    <form action="register_process.php" method="POST" class="auth-form">

        <label>Họ và tên:</label>
        <input type="text" name="fullname" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Mật khẩu:</label>
        <input type="password" name="password" required>

        <label>Nhập lại mật khẩu:</label>
        <input type="password" name="password_confirm" required>

        <button type="submit" class="btn">Tạo tài khoản</button>
    </form>

    <p class="auth-switch">
        Đã có tài khoản? <a href="login.php">Đăng nhập</a>
    </p>
</div>

<!-- FOOTER + SCRIPT -->
<?php include "../includes/footer.php"; ?>
<?php include "../includes/scripts.html"; ?>

</body>
</html>
