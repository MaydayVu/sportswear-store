<?php
session_start();
include "../config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing cart ID']);
    exit;
}

$cart_id = (int)$_POST['cart_id'];
$session_id = session_id();

try {
    // Xóa sản phẩm khỏi giỏ hàng
    $delete_sql = "DELETE FROM carts WHERE id = ? AND session_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("is", $cart_id, $session_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Lỗi xóa sản phẩm: " . $delete_stmt->error);
    }
    
    // Lấy lại thông tin giỏ hàng sau khi xóa
    $cart_data = getCartData($conn, $session_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
        'cart_data' => $cart_data
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

function getCartData($conn, $session_id) {
    $sql = "
        SELECT 
            SUM(c.qty) as total_items,
            SUM(c.price * c.qty) as subtotal
        FROM carts c
        WHERE c.session_id = ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $subtotal = $result['subtotal'] ?? 0;
    $shipping_fee = $subtotal >= 500000 ? 0 : 30000;
    $total = $subtotal + $shipping_fee;
    
    return [
        'total_items' => $result['total_items'] ?? 0,
        'subtotal' => $subtotal,
        'subtotal_formatted' => number_format($subtotal) . '₫',
        'shipping_fee' => $shipping_fee,
        'total' => $total,
        'total_formatted' => number_format($total) . '₫'
    ];
}
?>