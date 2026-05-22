<?php
session_start();

$i = $_GET['i'] ?? null;
$supplier_id = $_GET['supplier_id'] ?? 0;
$source = $_GET['source'] ?? 'pos'; // 'pos' or 'invoice'

if ($i !== null) {
    unset($_SESSION['purchase_items'][$i]);
    $_SESSION['purchase_items'] = array_values($_SESSION['purchase_items']);
}

// Redirect based on source
if ($source === 'invoice') {
    header("Location: purchase_invoice_view.php?supplier_id=$supplier_id");
} else {
    header("Location: purchase_pos.php?supplier_id=$supplier_id");
}
exit;
?>