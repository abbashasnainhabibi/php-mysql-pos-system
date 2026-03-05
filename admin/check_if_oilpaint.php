<?php
include("../config/db.php");

$category_id = intval($_GET['category_id']);

// Get category name
$result = $conn->query("SELECT name FROM categories WHERE id = $category_id");
$category = $result->fetch_assoc();
$cat_name = strtolower($category['name']);

// Check if this is Oil Paint category
// Adjust this condition to match your exact category name
$is_oilpaint = (
    strpos($cat_name, 'oil paint') !== false ||
    strpos($cat_name, 'oilpaint') !== false ||
    strpos($cat_name, 'paint') !== false // Remove this line if you have other paint categories
);

// Alternatively, you can check by category ID if you know it:
// $is_oilpaint = ($category_id == 3); // Replace 3 with your Oil Paint category ID

header('Content-Type: application/json');
echo json_encode(['is_oilpaint' => $is_oilpaint]);