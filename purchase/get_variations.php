<?php
include("../config/db.php");

$category_id = intval($_GET['category_id']);
$brand_id    = intval($_GET['brand_id']);

$data = [];

// Check if this category has products with colors (like oil paint)
$colorCheck = $conn->query("
SELECT COUNT(*) as has_colors
FROM products 
WHERE category_id = $category_id 
AND brand_id = $brand_id 
AND color IS NOT NULL
LIMIT 1
")->fetch_assoc();

$hasColors = $colorCheck['has_colors'] > 0;

if ($hasColors) {
    // For categories with colors (oil paint), return only distinct variations
    // Don't show price/stock at this level since they vary by color
    $sql = "
    SELECT DISTINCT v.id, v.value
    FROM products p
    INNER JOIN variations v ON v.id = p.variation_id
    WHERE p.category_id = $category_id
    AND p.brand_id = $brand_id
    AND p.color IS NOT NULL
    ORDER BY v.value
    ";
} else {
    // For regular products, show price and stock
    $sql = "
    SELECT v.id, v.value, p.stock, p.price
    FROM products p
    INNER JOIN variations v ON v.id = p.variation_id
    WHERE p.category_id = $category_id
    AND p.brand_id = $brand_id
    AND p.color IS NULL
    ";
}

$q = $conn->query($sql);
while ($row = $q->fetch_assoc()) {
    $row['has_colors'] = $hasColors;
    $data[] = $row;
}

echo json_encode($data);