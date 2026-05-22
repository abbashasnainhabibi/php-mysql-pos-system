<?php
include("../config/db.php");

$category_id = intval($_GET['category_id']);
$data = [];

$q = $conn->query("SELECT id, name FROM brands WHERE category_id=$category_id ORDER BY name");
while($row = $q->fetch_assoc()) $data[] = $row;

header('Content-Type: application/json');
echo json_encode($data);
