<?php
session_start();
include("config/db.php");

// Capture customer info from URL
$c_name = isset($_GET['c_name']) ? trim($_GET['c_name']) : '';
$c_phone = isset($_GET['c_phone']) ? trim($_GET['c_phone']) : '';
$amount_paid = isset($_GET['amount_paid']) ? floatval($_GET['amount_paid']) : 0;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get current total to calculate balance
    $invoice = $conn->query("SELECT total FROM invoices WHERE id=$id")->fetch_assoc();
    $total = $invoice['total'];
    $balance = $total - $amount_paid;
    
    // Determine payment status
    if ($amount_paid == 0) {
        $payment_status = 'unpaid';
    } elseif ($balance > 0) {
        $payment_status = 'partial';
    } else {
        $payment_status = 'paid';
    }
    
    // Update invoice with customer info, payment info and set saved=1
    $stmt = $conn->prepare("UPDATE invoices SET saved = 1, customer_name = ?, customer_phone = ?, amount_paid = ?, balance = ?, payment_status = ? WHERE id = ? AND saved = 0");
    $stmt->bind_param("ssddsi", $c_name, $c_phone, $amount_paid, $balance, $payment_status, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: invoice_view.php?id=$id&saved=1");
    exit;
} else {
    // No ID, save from session
    if (empty($_SESSION['invoice_items'])) {
        header("Location: index.php");
        exit;
    }

    $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    $invoice_date = date('Y-m-d H:i:s');
    $total = 0;
    foreach ($_SESSION['invoice_items'] as $item) {
        $total += $item['total'];
    }

    // Calculate balance
    $balance = $total - $amount_paid;
    
    // Determine payment status
    if ($amount_paid == 0) {
        $payment_status = 'unpaid';
    } elseif ($balance > 0) {
        $payment_status = 'partial';
    } else {
        $payment_status = 'paid';
    }

    // Insert invoice with saved=1, customer info and payment info
    $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, invoice_date, total, saved, customer_name, customer_phone, amount_paid, balance, payment_status) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssdds", $invoice_number, $invoice_date, $total, $c_name, $c_phone, $amount_paid, $balance, $payment_status);
    $stmt->execute();
    $invoice_id = $conn->insert_id;
    $stmt->close();

    // Insert items
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
        // Update stock
        if ($product_id) {
            $conn->query("UPDATE products SET stock = stock - $quantity WHERE id = $product_id");
        }
    }
    
    // Clear session
    unset($_SESSION['invoice_items']);
    header("Location: invoice_view.php?id=$invoice_id&saved=1");
    exit;
}
?>