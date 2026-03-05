<?php
session_start();
include("../config/db.php");

$supplier_id = (int)$_GET['supplier_id'];
$amount_paid = (float)$_POST['amount_paid'];

// Calculate total from session
$total = 0;
foreach ($_SESSION['purchase_items'] as $item) {
    $total += $item['total'];
}

$balance = $total - $amount_paid;

// Determine payment status
if ($amount_paid == 0) {
    $payment_status = 'unpaid';
} elseif ($balance > 0) {
    $payment_status = 'partial';
} else {
    $payment_status = 'paid';
}

// Generate invoice number
$invoice_number = 'PUR-' . date('Ymd') . '-' . rand(1000, 9999);
$invoice_date = date('Y-m-d H:i:s');

// Insert purchase invoice
$stmt = $conn->prepare("INSERT INTO purchase_invoices (invoice_number, supplier_id, invoice_date, total, amount_paid, balance, payment_status, saved) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
$stmt->bind_param("sisddds", $invoice_number, $supplier_id, $invoice_date, $total, $amount_paid, $balance, $payment_status);
$stmt->execute();
$purchase_id = $conn->insert_id;
$stmt->close();

// Insert items and UPDATE STOCK
foreach ($_SESSION['purchase_items'] as $item) {
    $product_id = $item['product_id'];
    $description = $item['description'];
    $quantity = $item['quantity'];
    $unit_price = $item['unit_price'];
    $item_total = $item['total'];
    
    // Insert purchase item
    $stmt = $conn->prepare("INSERT INTO purchase_items (purchase_invoice_id, product_id, description, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisddd", $purchase_id, $product_id, $description, $quantity, $unit_price, $item_total);
    $stmt->execute();
    $stmt->close();
    
    // **UPDATE STOCK - ADD QUANTITY**
    $conn->query("UPDATE products SET stock = stock + $quantity WHERE id = $product_id");
}

// Clear session
unset($_SESSION['purchase_items']);

header("Location: purchase_invoice_view.php?id=$purchase_id&saved=1");
exit;
?>