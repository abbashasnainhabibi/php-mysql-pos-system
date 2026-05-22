<?php
session_start();
include("config/db.php");

$data = json_decode(file_get_contents('php://input'), true);
$index = (int)$data['index'];
$newQty = (int)$data['quantity'];
$productId = isset($data['product_id']) ? (int)$data['product_id'] : null;
$invoiceId = isset($data['invoice_id']) ? (int)$data['invoice_id'] : null;

if ($newQty <= 0) {
    echo json_encode(['error' => 'Quantity must be at least 1.', 'oldQty' => isset($data['oldQty']) ? $data['oldQty'] : 1]);
    exit;
}

if ($invoiceId) {
    // Update saved invoice in database
    $stmt = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id");
    $stmt->bind_param("i", $invoiceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!isset($items[$index])) {
        echo json_encode(['error' => 'Item not found.']);
        exit;
    }

    $item = $items[$index];
    $oldQty = $item['quantity'];

    // Check stock if product_id exists
    if ($item['product_id']) {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = $result->fetch_assoc()['stock'];
        $stmt->close();

        if ($newQty > $stock) {
            echo json_encode(['error' => "Quantity exceeds stock ({$stock}).", 'oldQty' => $oldQty]);
            exit;
        }
    }

    // Update the item in database
    $newTotal = $newQty * $item['price'];
    $stmt = $conn->prepare("UPDATE invoice_items SET quantity = ?, total = ? WHERE id = ?");
    $stmt->bind_param("idi", $newQty, $newTotal, $item['id']);
    $stmt->execute();
    $stmt->close();

    // Recalculate invoice total
    $stmt = $conn->prepare("SELECT SUM(total) as grand_total FROM invoice_items WHERE invoice_id = ?");
    $stmt->bind_param("i", $invoiceId);
    $stmt->execute();
    $result = $stmt->get_result();
    $newGrandTotal = $result->fetch_assoc()['grand_total'];
    $stmt->close();

    // Update invoice total
    $stmt = $conn->prepare("UPDATE invoices SET total = ? WHERE id = ?");
    $stmt->bind_param("di", $newGrandTotal, $invoiceId);
    $stmt->execute();
    $stmt->close();

    // Format totals
    $newTotal = number_format($newTotal, 2, '.', '');
    $newGrandTotal = number_format($newGrandTotal, 2, '.', '');

    echo json_encode([
        'newTotal' => $newTotal,
        'newGrandTotal' => $newGrandTotal,
        'oldQty' => $oldQty
    ]);
} else {
    // Update session-based invoice (draft)
    if (!isset($_SESSION['invoice_items'][$index])) {
        echo json_encode(['error' => 'Item not found.']);
        exit;
    }

    $item = &$_SESSION['invoice_items'][$index];
    $oldQty = $item['quantity'];

    // Check stock if product_id exists
    if ($item['product_id']) {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = $result->fetch_assoc()['stock'];
        $stmt->close();

        if ($newQty > $stock) {
            echo json_encode(['error' => "Quantity exceeds stock ({$stock}).", 'oldQty' => $oldQty]);
            exit;
        }
    }

    // Update quantity and total
    $item['quantity'] = $newQty;
    $item['total'] = $newQty * $item['price'];

    // Recalculate grand total
    $newGrandTotal = 0;
    foreach ($_SESSION['invoice_items'] as $i) {
        $newGrandTotal += $i['total'];
    }

    // Format totals to avoid float precision issues
    $item['total'] = number_format($item['total'], 2, '.', '');
    $newGrandTotal = number_format($newGrandTotal, 2, '.', '');

    echo json_encode([
        'newTotal' => $item['total'],
        'newGrandTotal' => $newGrandTotal,
        'oldQty' => $oldQty
    ]);
}
?>
