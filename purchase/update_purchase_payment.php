<?php
session_start();
include("../config/db.php");

if (!isset($_GET['purchase_id']) || !isset($_GET['amount'])) {
    header("Location: view_purchases.php");
    exit;
}

$purchase_id = intval($_GET['purchase_id']);
$additional_payment = floatval($_GET['amount']);

// Validate payment amount
if ($additional_payment <= 0) {
    header("Location: purchase_invoice_view.php?id=$purchase_id&error=invalid_amount");
    exit;
}

// Get current purchase data
$result = $conn->query("SELECT * FROM purchase_invoices WHERE id = $purchase_id");
if ($result->num_rows === 0) {
    header("Location: view_purchases.php");
    exit;
}

$purchase = $result->fetch_assoc();
$current_paid = floatval($purchase['amount_paid']);
$current_balance = floatval($purchase['balance']);
$total = floatval($purchase['total']);

// Validate payment doesn't exceed balance
if ($additional_payment > $current_balance) {
    header("Location: purchase_invoice_view.php?id=$purchase_id&error=payment_exceeds_balance");
    exit;
}

// Calculate new amounts
$new_amount_paid = $current_paid + $additional_payment;
$new_balance = $total - $new_amount_paid;

// Ensure no negative balance due to floating point precision
if ($new_balance < 0.01) {
    $new_balance = 0;
}

// Determine new payment status
if ($new_balance <= 0) {
    $payment_status = 'paid';
} elseif ($new_amount_paid > 0) {
    $payment_status = 'partial';
} else {
    $payment_status = 'unpaid';
}

// Update the purchase invoice
$stmt = $conn->prepare("UPDATE purchase_invoices SET amount_paid = ?, balance = ?, payment_status = ? WHERE id = ?");
$stmt->bind_param("ddsi", $new_amount_paid, $new_balance, $payment_status, $purchase_id);

if ($stmt->execute()) {
    // Optional: Record payment in purchase_payments table for history
    $payment_date = date('Y-m-d H:i:s');
    $payment_stmt = $conn->prepare("INSERT INTO purchase_payments (purchase_invoice_id, amount, payment_date, payment_method, notes) VALUES (?, ?, ?, 'Cash', 'Additional payment')");
    $payment_stmt->bind_param("ids", $purchase_id, $additional_payment, $payment_date);
    $payment_stmt->execute();
    $payment_stmt->close();
    
    $stmt->close();
    header("Location: purchase_invoice_view.php?id=$purchase_id&payment_updated=1");
} else {
    $stmt->close();
    header("Location: purchase_invoice_view.php?id=$purchase_id&error=update_failed");
}
exit;
?>