<?php
session_start();
if (empty($_SESSION['invoice_items'])) {
    header("Location: index.php");
    exit;
}
header("Location: invoice_view.php");
?>
