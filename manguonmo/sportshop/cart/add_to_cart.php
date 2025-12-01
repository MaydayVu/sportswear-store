<?php
// add_to_cart.php
session_start();
include "../config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$session_id = session_id();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$product_id = intval($_POST['product_id']);
$qty = max(1, intval($_POST['qty'] ?? 1));
$size = isset($_POST['size']) ? $_POST['size'] : null;

// Lấy giá hiện tại từ sản phẩm
$stmt = $conn->prepare("SELECT price FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $_SESSION['error'] = "Sản phẩm không tồn tại.";
    header("Location: index.php");
    exit;
}
$product = $res->fetch_assoc();
$price = $product['price'];

// Nếu đã có product cùng session+product+size -> update qty
$stmt = $conn->prepare("SELECT id, qty FROM carts WHERE session_id = ? AND product_id = ? AND size = ? LIMIT 1");
$stmt->bind_param("sis", $session_id, $product_id, $size);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $newQty = $row['qty'] + $qty;
    $up = $conn->prepare("UPDATE carts SET qty=? WHERE id=?");
    $up->bind_param("ii",$newQty, $row['id']);
    $up->execute();
} else {
    $ins = $conn->prepare("INSERT INTO carts (session_id, user_id, product_id, qty, price, size) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param("siidis", $session_id, $user_id, $product_id, $qty, $price, $size);
    $ins->execute();
}

header("Location: cart.php");
exit;
