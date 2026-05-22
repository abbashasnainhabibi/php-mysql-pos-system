<?php
include("../config/db.php");

$category_id = intval($_GET['category_id']);
$brand_id    = intval($_GET['brand_id']);
$variation_id = intval($_GET['variation_id']);

$data = [];

$sql = "
SELECT p.id, p.color, p.stock, p.price
FROM products p
WHERE p.category_id = $category_id
AND p.brand_id = $brand_id
AND p.variation_id = $variation_id
AND p.color IS NOT NULL
ORDER BY p.color
";

$q = $conn->query($sql);
while ($row = $q->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);