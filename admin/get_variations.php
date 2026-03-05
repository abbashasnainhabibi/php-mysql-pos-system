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
    // For categories with colors (oil paint)
    // Show all variations for this category (colors are separate, so same variation can have multiple colors)
    $sql = "
        SELECT v.id, v.value
        FROM variations v
        WHERE v.category_id = $category_id
        ORDER BY v.value
    ";
} else {
    // For regular products WITHOUT colors
    // Show only variations that are NOT already added for this category+brand combination
    $sql = "
        SELECT v.id, v.value
        FROM variations v
        WHERE v.category_id = $category_id
        AND v.id NOT IN (
            SELECT variation_id 
            FROM products 
            WHERE category_id = $category_id 
            AND brand_id = $brand_id
            AND color IS NULL
        )
        ORDER BY v.value
    ";
}

$q = $conn->query($sql);
while ($row = $q->fetch_assoc()) {
    $row['has_colors'] = $hasColors;
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>