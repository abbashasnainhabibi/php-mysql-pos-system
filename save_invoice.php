<?php 
include("config/db.php");
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Capture customer info (if provided via POST or GET)
$customer_name = '';
$customer_phone = '';

if (isset($_POST['customer_name'])) {
    $customer_name = trim($_POST['customer_name']);
}
if (isset($_POST['customer_phone'])) {
    $customer_phone = trim($_POST['customer_phone']);
}

// Capture payment information
$amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0;

// Generate invoice number if not provided 
date_default_timezone_set('Asia/Karachi');
$invoice_number = isset($_POST['invoice_number']) ? $_POST['invoice_number'] : 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
$invoice_date = date('Y-m-d H:i:s');

// Calculate total from session items
$total = 0;
foreach ($_SESSION['invoice_items'] as $item) {
    $total += $item['total'];
}

// Calculate balance
$balance = $total - $amount_paid;

// Validate payment doesn't exceed total
if ($amount_paid > $total) {
    $amount_paid = $total;
    $balance = 0;
}

// Determine payment status
if ($amount_paid == 0) {
    $payment_status = 'unpaid';
} elseif ($balance > 0) {
    $payment_status = 'partial';
} else {
    $payment_status = 'paid';
}

// Insert invoice with customer info, payment info and saved=1
$stmt = $conn->prepare("INSERT INTO invoices (invoice_number, invoice_date, total, customer_name, customer_phone, amount_paid, balance, payment_status, saved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
$stmt->bind_param("ssdssdds", $invoice_number, $invoice_date, $total, $customer_name, $customer_phone, $amount_paid, $balance, $payment_status);
$stmt->execute();
$invoice_id = $conn->insert_id;
$stmt->close();

// Insert items from session
foreach ($_SESSION['invoice_items'] as $item) {
    $product_id = $item['product_id'] ?: null;
    $description = $item['description'];
    $quantity = $item['quantity'];
    $price = $item['price'];
    $item_total = $item['total'];
    
    $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, product_id, description, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisddd", $invoice_id, $product_id, $description, $quantity, $price, $item_total);
    $stmt->execute();
    $stmt->close();
    
    // Update stock if product_id exists
    if ($product_id) {
        $conn->query("UPDATE products SET stock = stock - $quantity WHERE id = $product_id");
    }
}

// Clear session 
unset($_SESSION['invoice_items']);

header("Location: invoice_view.php?id=$invoice_id&saved=1");
?>