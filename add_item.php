<?php
session_start();
include("config/db.php");

// Capture customer info from URL
$c_name = isset($_GET['c_name']) ? $_GET['c_name'] : '';
$c_phone = isset($_GET['c_phone']) ? $_GET['c_phone'] : '';

// Get and validate POST data
$brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
$variation_id = isset($_POST['variation_id']) ? (int)$_POST['variation_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$color = isset($_POST['color']) ? trim($_POST['color']) : null;

// Build params for redirect
$params = '?c_name=' . urlencode($c_name) . '&c_phone=' . urlencode($c_phone);

if ($brand_id <= 0 || $variation_id <= 0 || $quantity <= 0) {
    die("Error: Invalid input data. Please select a brand, variation, and quantity.");
}

// Build query based on whether color is provided
if ($color) {
    $query = "SELECT p.id, p.price, p.stock, CONCAT(b.name, ' - ', v.value, ' - ', p.color) AS description 
              FROM products p 
              JOIN brands b ON p.brand_id = b.id 
              JOIN variations v ON p.variation_id = v.id 
              WHERE p.brand_id = $brand_id AND p.variation_id = $variation_id AND p.color = '" . $conn->real_escape_string($color) . "'";
} else {
    $query = "SELECT p.id, p.price, p.stock, CONCAT(b.name, ' - ', v.value) AS description 
              FROM products p 
              JOIN brands b ON p.brand_id = b.id 
              JOIN variations v ON p.variation_id = v.id 
              WHERE p.brand_id = $brand_id AND p.variation_id = $variation_id AND p.color IS NULL";
}

$result = $conn->query($query);

if (!$result || $result->num_rows == 0) {
    die("Error: Product not found.");
}

$product = $result->fetch_assoc();

// Check stock
if ($quantity > $product['stock']) {
    die("Error: Quantity exceeds stock ({$product['stock']}).");
}

// Add to session
$_SESSION['invoice_items'][] = [
    'product_id' => $product['id'],
    'description' => $product['description'],
    'quantity' => $quantity,
    'price' => $product['price'],
    'total' => $quantity * $product['price']
];

// Redirect back to index with customer info preserved
header("Location: index.php" . $params);
exit;
?>