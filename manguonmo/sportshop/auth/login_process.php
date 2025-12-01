<?php
session_start();
include __DIR__ . "/../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Chuẩn bị câu truy vấn an toàn
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Kiểm tra email tồn tại
    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // XÁC MINH MẬT KHẨU
        if (password_verify($password, $user['password'])) {

            // LƯU SESSION CHO NGƯỜI DÙNG
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["fullname"] = $user["fullname"];
            $_SESSION["role"] = $user["role"];

            // CHUYỂN HƯỚNG THEO QUYỀN
            if ($user["role"] === "admin") {
                header("Location: /manguonmo/sportshop/admin/dashboard.php");
                exit();
            }

            // Nếu không phải admin → chuyển về trang chủ người dùng
            header("Location: /manguonmo/sportshop/index.php");
            exit();

        } else {
            // Sai mật khẩu
            header("Location: login.php?error=wrong_password");
            exit();
        }

    } else {
        // Không tìm thấy email
        header("Location: login.php?error=user_not_found");
        exit();
    }
}
?>
