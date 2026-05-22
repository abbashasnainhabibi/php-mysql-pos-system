<?php
include("config/db.php");

$category_id = intval($_GET['category_id']);
$data = [];

$sql = "
SELECT DISTINCT b.id, b.name
FROM brands b
INNER JOIN products p ON p.brand_id = b.id
WHERE p.category_id = $category_id
";

$q = $conn->query($sql);
while ($row = $q->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
