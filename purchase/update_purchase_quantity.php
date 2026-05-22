<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['purchase_items'])) {
    echo json_encode(['error' => 'No purchase items in session']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$index = (int)$data['index'];
$newQty = (int)$data['quantity'];

if (!isset($_SESSION['purchase_items'][$index])) {
    echo json_encode(['error' => 'Invalid item index']);
    exit;
}

if ($newQty < 1) {
    echo json_encode(['error' => 'Quantity must be at least 1']);
    exit;
}

// Update quantity and recalculate total
$_SESSION['purchase_items'][$index]['quantity'] = $newQty;
$_SESSION['purchase_items'][$index]['total'] = $newQty * $_SESSION['purchase_items'][$index]['unit_price'];

// Calculate new grand total
$grandTotal = 0;
foreach ($_SESSION['purchase_items'] as $item) {
    $grandTotal += $item['total'];
}

echo json_encode([
    'success' => true,
    'newTotal' => number_format($_SESSION['purchase_items'][$index]['total'], 2),
    'newGrandTotal' => number_format($grandTotal, 2)
]);