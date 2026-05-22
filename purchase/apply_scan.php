<?php
/**
 * apply_scan.php
 * Receives confirmed scanned items via POST JSON,
 * adds them to $_SESSION['purchase_items'] just like add_purchase_item.php does.
 */

session_start();
include("../config/db.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['items']) || !is_array($input['items'])) {
    echo json_encode(['error' => 'No items received']);
    exit;
}

$supplier_id = (int)($input['supplier_id'] ?? 0);

if (!isset($_SESSION['purchase_items'])) {
    $_SESSION['purchase_items'] = [];
}

$added = 0;

foreach ($input['items'] as $item) {
    $product_id = (int)($item['product_id'] ?? 0);
    $quantity   = (int)($item['quantity'] ?? 1);
    $unit_price = (float)($item['unit_price'] ?? 0);
    $description = trim($item['description'] ?? '');

    if ($product_id <= 0 || $quantity <= 0 || empty($description)) {
        continue; // skip invalid
    }

    // Check if same product already in session → update quantity
    $found = false;
    foreach ($_SESSION['purchase_items'] as &$existing) {
        if ((int)$existing['product_id'] === $product_id) {
            $existing['quantity']   += $quantity;
            $existing['total']       = $existing['quantity'] * $existing['unit_price'];
            $found = true;
            break;
        }
    }
    unset($existing);

    if (!$found) {
        $_SESSION['purchase_items'][] = [
            'product_id'  => $product_id,
            'description' => $description,
            'quantity'    => $quantity,
            'unit_price'  => $unit_price,
            'total'       => $quantity * $unit_price,
        ];
    }

    $added++;
}

if ($added === 0) {
    echo json_encode(['error' => 'No valid items were added']);
    exit;
}

echo json_encode(['success' => true, 'added' => $added]);