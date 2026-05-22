<?php
include("../config/db.php");

$category_id = intval($_GET['category_id']);
$brand_id = intval($_GET['brand_id']);

// METHOD 1: Check if category name contains "Oil Paint" or "Paint"
$cat_result = $conn->query("SELECT name FROM categories WHERE id = $category_id");
$cat_name = $cat_result->fetch_assoc()['name'];

// Check if this category needs colors (based on name)
$needs_color_by_name = (stripos($cat_name, 'Oil Paint') !== false || stripos($cat_name, 'oil paint') !== false);

// METHOD 2: Check if any products already exist with colors for this category+brand
$existing_result = $conn->query("
    SELECT COUNT(*) as has_colors 
    FROM products 
    WHERE category_id = $category_id 
    AND brand_id = $brand_id 
    AND color IS NOT NULL
    LIMIT 1
");
$existing_row = $existing_result->fetch_assoc();
$needs_color_existing = $existing_row['has_colors'] > 0;

// If EITHER condition is true, show color field
$needs_color = $needs_color_by_name || $needs_color_existing;

header('Content-Type: application/json');
echo json_encode(['needs_color' => $needs_color]);