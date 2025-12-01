<?php
session_start();
include "../config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$order_id = (int)$_POST['order_id'];
$status = $conn->real_escape_string($_POST['status']);

try {
    $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $status, $order_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
    } else {
        throw new Exception("Lỗi cập nhật: " . $update_stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>