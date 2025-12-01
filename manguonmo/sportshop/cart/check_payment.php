<?php
session_start();
include "../config.php";

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order ID']);
    exit;
}

$order_id = (int)$_GET['order_id'];

try {
    $check_sql = "SELECT status FROM orders WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    echo json_encode(['status' => $result['status'] ?? 'pending']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>