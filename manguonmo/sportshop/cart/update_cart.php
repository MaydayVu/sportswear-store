<?php
session_start();
include "../config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['cart_id']) || !isset($_POST['qty'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$cart_id = (int)$_POST['cart_id'];
$new_qty = (int)$_POST['qty'];
$session_id = session_id();

try {
    // Lấy thông tin giỏ hàng và kiểm tra số lượng tồn kho từ product_sizes
    $check_sql = "
        SELECT c.*, ps.quantity as stock, ps.size, p.name, p.price, p.discount_percent
        FROM carts c
        JOIN product_sizes ps ON ps.id = c.size_id
        JOIN products p ON p.id = c.product_id
        WHERE c.id = ? AND c.session_id = ?
    ";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $cart_id, $session_id);
    $check_stmt->execute();
    $cart_item = $check_stmt->get_result()->fetch_assoc();
    
    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng']);
        exit;
    }
    
    // Kiểm tra số lượng tồn kho
    if ($new_qty > $cart_item['stock']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Số lượng vượt quá tồn kho. Chỉ còn ' . $cart_item['stock'] . ' sản phẩm'
        ]);
        exit;
    }
    
    // Giới hạn số lượng tối đa là 10
    if ($new_qty > 10) {
        echo json_encode(['success' => false, 'message' => 'Số lượng tối đa cho mỗi sản phẩm là 10']);
        exit;
    }
    
    // Tính giá sau giảm giá
    $price = $cart_item['price'];
    if ($cart_item['discount_percent'] > 0) {
        $price = $cart_item['price'] * (1 - $cart_item['discount_percent'] / 100);
    }
    
    // Cập nhật số lượng và giá
    $update_sql = "UPDATE carts SET qty = ?, price = ? WHERE id = ? AND session_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("idis", $new_qty, $price, $cart_id, $session_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Lỗi cập nhật giỏ hàng: " . $update_stmt->error);
    }
    
    // Lấy lại thông tin giỏ hàng sau khi cập nhật
    $cart_data = getCartData($conn, $session_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật số lượng',
        'cart_data' => $cart_data,
        'max_qty' => min(10, $cart_item['stock']),
        'stock_info' => [
            'stock' => $cart_item['stock'],
            'current_qty' => $new_qty
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

function getCartData($conn, $session_id) {
    $sql = "
        SELECT 
            SUM(c.qty) as total_items,
            SUM(c.price * c.qty) as subtotal,
            ps.quantity as stock
        FROM carts c
        LEFT JOIN product_sizes ps ON ps.id = c.size_id
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