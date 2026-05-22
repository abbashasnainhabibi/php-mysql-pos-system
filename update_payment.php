<?php
session_start();
include("config/db.php");

if (!isset($_GET['invoice_id']) || !isset($_GET['amount'])) {
    header("Location: view_invoices.php");
    exit;
}

$invoice_id = intval($_GET['invoice_id']);
$additional_payment = floatval($_GET['amount']);

// Validate payment amount
if ($additional_payment <= 0) {
    header("Location: invoice_view.php?id=$invoice_id&error=invalid_amount");
    exit;
}

// Get current invoice data
$result = $conn->query("SELECT * FROM invoices WHERE id = $invoice_id");
if ($result->num_rows === 0) {
    header("Location: view_invoices.php");
    exit;
}

$invoice = $result->fetch_assoc();
$current_paid = floatval($invoice['amount_paid']);
$current_balance = floatval($invoice['balance']);
$total = floatval($invoice['total']);

// Validate payment doesn't exceed balance
if ($additional_payment > $current_balance) {
    header("Location: invoice_view.php?id=$invoice_id&error=payment_exceeds_balance");
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

// Update the invoice
$stmt = $conn->prepare("UPDATE invoices SET amount_paid = ?, balance = ?, payment_status = ? WHERE id = ?");
$stmt->bind_param("ddsi", $new_amount_paid, $new_balance, $payment_status, $invoice_id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: invoice_view.php?id=$invoice_id&payment_updated=1");
} else {
    $stmt->close();
    header("Location: invoice_view.php?id=$invoice_id&error=update_failed");
}
exit;
?>