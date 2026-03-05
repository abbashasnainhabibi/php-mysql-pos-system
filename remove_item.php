<?php
session_start();

$i = $_GET['i'] ?? null;
$from = $_GET['from'] ?? 'index'; // Default to index if not specified

// Capture customer info from URL
$c_name = isset($_GET['c_name']) ? $_GET['c_name'] : '';
$c_phone = isset($_GET['c_phone']) ? $_GET['c_phone'] : '';

// Build params string
$params = '?c_name=' . urlencode($c_name) . '&c_phone=' . urlencode($c_phone);

if ($i !== null) {
    unset($_SESSION['invoice_items'][$i]);
    $_SESSION['invoice_items'] = array_values($_SESSION['invoice_items']);
}

// REDIRECTION LOGIC
if (empty($_SESSION['invoice_items'])) {
    header("Location: index.php" . $params);
} elseif ($from === 'view') {
    // If we clicked remove inside the invoice view, stay there
    header("Location: invoice_view.php" . $params);
} else {
    // If we clicked remove on the POS (index) page, stay there
    header("Location: index.php" . $params);
}
exit;