<?php
include("../config/db.php");

$category_id = intval($_GET['category_id']);
$brand_id    = intval($_GET['brand_id']);

$data = [];

// Get ALL variations that have been used in products for this category+brand combination
// This is different from get_variations.php which filters out already-used ones
$sql = "
    SELECT DISTINCT v.id, v.value
    FROM variations v
    INNER JOIN products p ON p.variation_id = v.id
    WHERE p.category_id = $category_id
    AND p.brand_id = $brand_id
    ORDER BY v.value
";

$q = $conn->query($sql);
while ($row = $q->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>