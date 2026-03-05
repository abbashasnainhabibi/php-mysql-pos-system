<?php
session_start();
include("config/db.php");

if (isset($_GET['id']) && isset($_GET['invoice_id'])) {
    $item_id = intval($_GET['id']);
    $invoice_id = intval($_GET['invoice_id']);
   
    // Capture customer info from URL
    $c_name = isset($_GET['c_name']) ? $_GET['c_name'] : '';
    $c_phone = isset($_GET['c_phone']) ? $_GET['c_phone'] : '';
    
    // Build params string
    $params = '?c_name=' . urlencode($c_name) . '&c_phone=' . urlencode($c_phone);

    // Get item details before deleting (to restore stock)
    $item = $conn->query("SELECT * FROM invoice_items WHERE id=$item_id")->fetch_assoc();
    
    if ($item) {
        // Restore stock if product exists
        if ($item['product_id']) {
            $conn->query("UPDATE products SET stock = stock + {$item['quantity']} WHERE id = {$item['product_id']}");
        }
        
        // Delete the item
        $conn->query("DELETE FROM invoice_items WHERE id=$item_id");
        
        // Recalculate invoice total
        $result = $conn->query("SELECT SUM(total) as new_total FROM invoice_items WHERE invoice_id=$invoice_id");
        $row = $result->fetch_assoc();
        $new_total = $row['new_total'] ?? 0;
        
        $conn->query("UPDATE invoices SET total=$new_total WHERE id=$invoice_id");
    }

    // Check if any items remain
    $result = $conn->query("SELECT COUNT(*) as count FROM invoice_items WHERE invoice_id=$invoice_id");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // No items left, go back to index
        header("Location: index.php" . $params);
    } else {
        // Items remain, stay on invoice view
        header("Location: invoice_view.php?id=$invoice_id&" . ltrim($params, '?'));
    }
} else {
    header("Location: index.php");
}
exit;