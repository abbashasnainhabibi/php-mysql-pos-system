<?php
include("../config/db.php");

$category_id = intval($_GET['category_id']);
$brand_id = intval($_GET['brand_id']);
$variation_id = intval($_GET['variation_id']);

$result = $conn->query("
    SELECT id FROM products 
    WHERE category_id = $category_id 
    AND brand_id = $brand_id 
    AND variation_id = $variation_id 
    AND color IS NULL
    LIMIT 1
");

$product = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['product_id' => $product['id'] ?? 0]);
?>