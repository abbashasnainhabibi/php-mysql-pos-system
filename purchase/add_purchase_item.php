<?php
session_start();
include("../config/db.php");

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$unit_price = (float)$_POST['unit_price'];

// Get product details
$product = $conn->query("
    SELECT CONCAT(b.name, ' - ', v.value, IFNULL(CONCAT(' - ', p.color), '')) AS description
    FROM products p
    JOIN brands b ON p.brand_id = b.id
    JOIN variations v ON p.variation_id = v.id
    WHERE p.id = $product_id
")->fetch_assoc();

$_SESSION['purchase_items'][] = [
    'product_id' => $product_id,
    'description' => $product['description'],
    'quantity' => $quantity,
    'unit_price' => $unit_price,
    'total' => $quantity * $unit_price
];

header("Location: purchase_pos.php?supplier_id=$supplier_id");
exit;
?>