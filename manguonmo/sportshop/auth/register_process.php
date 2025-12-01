<?php
session_start();
include "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST["fullname"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $password_confirm = $_POST["password_confirm"];

    // Kiểm tra trùng mật khẩu
    if ($password !== $password_confirm) {
        $_SESSION["error"] = "Mật khẩu nhập lại không khớp!";
        header("Location: register.php");
        exit();
    }

    // Kiểm tra email đã tồn tại?
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION["error"] = "Email đã tồn tại!";
        header("Location: register.php");
        exit();
    }

    // Hash mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Thêm vào database
    $stmt = $conn->prepare("
        INSERT INTO users (fullname, email, password, role)
        VALUES (?, ?, ?, 'user')
    ");
    $stmt->bind_param("sss", $fullname, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION["success"] = "Đăng ký thành công! Hãy đăng nhập.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION["error"] = "Có lỗi xảy ra, vui lòng thử lại!";
        header("Location: register.php");
        exit();
    }
}
?>
