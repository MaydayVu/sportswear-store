<?php
session_start();
include "config.php";

header('Content-Type: application/json');

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($searchTerm) || strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit;
}

// Tìm kiếm sản phẩm theo tên, brand, category
$sql = "
    SELECT 
        p.id,
        p.name,
        p.brand,
        p.price,
        p.discount_percent,
        p.image,
        c.name AS category_name,
        (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) as total_quantity
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE (p.name LIKE ? OR p.brand LIKE ? OR c.name LIKE ?)
    AND (SELECT SUM(quantity) FROM product_sizes WHERE product_id = p.id) > 0
    ORDER BY 
        CASE 
            WHEN p.name LIKE ? THEN 1
            WHEN p.brand LIKE ? THEN 2
            ELSE 3
        END,
        p.featured DESC,
        p.created_at DESC
    LIMIT 10
";

$searchParam = "%{$searchTerm}%";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", 
    $searchParam, 
    $searchParam, 
    $searchParam,
    $searchParam,
    $searchParam
);

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);

$stmt->close();
$conn->close();
?>