<?php
include("../config/db.php");

$category_id = intval($_GET['category_id']);
$supplier_id = intval($_GET['supplier_id']);

$data = [];

// Get brands that this supplier supplies for this category
$sql = "
SELECT DISTINCT b.id, b.name
FROM brands b
INNER JOIN supplier_category_brands scb ON b.id = scb.brand_id
WHERE scb.category_id = $category_id
AND scb.supplier_id = $supplier_id
ORDER BY b.name
";

$q = $conn->query($sql);
while ($row = $q->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
