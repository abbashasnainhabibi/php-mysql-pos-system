<?php
session_start();
include("../config/db.php");

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$saved = 0;
$purchase = null;
$items = [];

if (isset($_GET['id'])) {
    // Viewing saved purchase invoice
    $id = (int)$_GET['id'];
    $purchase = $conn->query("SELECT * FROM purchase_invoices WHERE id=$id")->fetch_assoc();
    
    if (!$purchase) {
        die("Purchase invoice not found");
    }
    
    $supplier_id = $purchase['supplier_id'];
    $items = $conn->query("SELECT * FROM purchase_items WHERE purchase_invoice_id=$id");
    $saved = (int)$purchase['saved'];
} else {
    // New purchase from session
    if (empty($_SESSION['purchase_items'])) {
        header("Location: purchase_pos.php?supplier_id=$supplier_id");
        exit;
    }
    
    $items = $_SESSION['purchase_items'];
    $total = 0;
    foreach ($items as $item) {
        $total += $item['total'];
    }
    
    $purchase = [
        'invoice_number' => 'PURCHASE-' . date('Ymd-His'),
        'invoice_date' => date('Y-m-d H:i:s'),
        'total' => $total,
        'amount_paid' => 0,
        'balance' => $total
    ];
}

// Get supplier info
if ($supplier_id <= 0) {
    die("Invalid supplier. Please select a supplier.");
}

$supplier = $conn->query("SELECT * FROM suppliers WHERE id=$supplier_id")->fetch_assoc();

if (!$supplier) {
    die("Supplier not found. Please go back and select a valid supplier.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchase Invoice - <?= $purchase['invoice_number'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; padding: 40px 20px; margin: 0; }
        .invoice-card { 
            max-width: 900px; margin: 0 auto; background: white; 
            padding: 50px; border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .header { 
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 2px solid #f1f5f9; padding-bottom: 30px; margin-bottom: 30px; 
        }
        .company h1 { margin: 0; font-size: 1.8rem; color: #4f46e5; letter-spacing: -1px; }
        .company p { color: #64748b; margin: 5px 0; }
        
        .invoice-meta { text-align: right; }
        .invoice-meta p { margin: 5px 0; font-size: 0.9rem; }
        
        .payment-status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        
        .supplier-info { 
            background: #eef2ff; padding: 20px; border-radius: 12px; 
            margin: 20px 0; border-left: 4px solid #4f46e5; 
        }
        .supplier-info strong { color: #4338ca; }
        .supplier-info div { margin: 5px 0; }
        
        table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        th { 
            text-align: left; padding: 15px 10px; border-bottom: 2px solid #f1f5f9; 
            color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;
        }
        td { padding: 15px 10px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        
        .total-box { 
            background: #1e293b;
            color: white; padding: 25px; border-radius: 12px; 
            text-align: right; margin-top: 30px; 
        }
        .total-box .label { font-size: 0.9rem; opacity: 0.8; margin-bottom: 10px; }
        .total-box .amount { font-size: 2rem; font-weight: 800; }
        
        .payment-summary {
            background: #eef2ff;
            border: 2px solid #4f46e5;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .payment-summary h4 {
            margin-top: 0;
            color: #4f46e5;
            font-size: 1.1rem;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9d5ff;
        }
        .payment-row:last-child {
            border-bottom: 2px solid #7c3aed;
            font-weight: 700;
        }
        
        .btn { 
            padding: 12px 24px; border-radius: 10px; text-decoration: none; 
            display: inline-block; margin: 5px; font-weight: 600; 
            border: none; cursor: pointer; font-size: 0.95rem;
        }
        .btn-save { background: #10b981; color: white; }
        .btn-save:hover { background: #059669; }
        .btn-print { background: #4f46e5; color: white; }
        .btn-print:hover { background: #4338ca; }
        .btn-outline { background: white; color: #4f46e5; border: 2px solid #4f46e5; }
        .btn-outline:hover { background: #eef2ff; }
        .btn-update-payment { background: #f59e0b; color: white; }
        .btn-update-payment:hover { background: #d97706; }
        
        .payment-form { 
            background: #f0fdf4; border: 2px solid #86efac; 
            border-radius: 12px; padding: 25px; margin: 20px 0; 
        }
        .payment-form h4 { margin-top: 0; color: #10b981; font-size: 1.1rem; }
        .payment-form label { font-weight: 600; display: inline-block; margin-right: 10px; }
        .payment-form input { 
            width: 220px; padding: 12px; border: 1px solid #ddd; 
            border-radius: 8px; margin-right: 10px; font-size: 1rem;
        }
        
        .update-payment-section {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
        }
        .update-payment-section h4 { margin-top: 0; color: #92400e; }
        
        .qty-input-editable {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 10px;
            font-weight: 600;
            text-align: center;
            width: 70px;
            outline: none;
            transition: all 0.2s ease;
        }
        
        .qty-input-editable:hover {
            border-color: #4f46e5;
        }
        
        .qty-input-editable:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .remove-link {
            text-decoration: none;
            color: #ef4444;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            display: inline-block;
            width: 30px;
            height: 30px;
            text-align: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .remove-link:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .actions { margin-top: 30px; display: flex; gap: 10px; flex-wrap: wrap; }
        
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .invoice-card { box-shadow: none; padding: 20px; }
            
            /* Style payment summary for print */
            .payment-summary {
                background: white !important;
                border: 2px solid #000 !important;
                page-break-inside: avoid;
            }
            .payment-summary h4 {
                color: #000 !important;
            }
            .payment-row {
                border-bottom: 1px solid #ccc !important;
            }
            .payment-row:last-child {
                border-bottom: 2px solid #000 !important;
            }
            
            /* Hide the total box on print since we're showing payment summary */
            .total-box { 
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="invoice-card">
    <div class="header">
        <div class="company">
            <h1> PURCHASE INVOICE</h1>
            <p>Stock Intake Record</p>
        </div>
        <div class="invoice-meta">
            <p><strong>Invoice #:</strong> <?= htmlspecialchars($purchase['invoice_number']) ?></p>
            <p><strong>Date:</strong> <?= date('M d, Y', strtotime($purchase['invoice_date'])) ?></p>
            
            <?php if ($saved === 1): 
                $payment_status = $purchase['payment_status'] ?? 'unpaid';
                $status_class = 'status-' . $payment_status;
                $status_text = ucfirst($payment_status);
                if ($payment_status === 'partial') $status_text = 'Partially Paid';
            ?>
                <span class="payment-status-badge <?= $status_class ?>"><?= $status_text ?></span>
            <?php else: ?>
                <span class="payment-status-badge" style="background: #fef3c7; color: #92400e;">DRAFT</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="supplier-info">
        <div style="font-size: 1.2rem; font-weight: 700; color: #5b21b6; margin-bottom: 10px;">
            Supplier Details
        </div>
        <div><strong>Company:</strong> <?= htmlspecialchars($supplier['name']) ?></div>
        <?php if (!empty($supplier['contact_person'])): ?>
        <div><strong>Contact Person:</strong> <?= htmlspecialchars($supplier['contact_person']) ?></div>
        <?php endif; ?>
        <?php if (!empty($supplier['phone'])): ?>
        <div><strong>Phone:</strong> <?= htmlspecialchars($supplier['phone']) ?></div>
        <?php endif; ?>
        <?php if (!empty($supplier['email'])): ?>
        <div><strong>Email:</strong> <?= htmlspecialchars($supplier['email']) ?></div>
        <?php endif; ?>
        <?php if (!empty($supplier['address'])): ?>
        <div><strong>Address:</strong> <?= htmlspecialchars($supplier['address']) ?></div>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Description</th>
                <th style="text-align:center; width: 100px;">Quantity</th>
                <th style="text-align:right; width: 120px;">Unit Price</th>
                <th style="text-align:right; width: 120px;">Total</th>
                <?php if ($saved === 0): ?>
                <th style="width: 50px;"></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($_GET['id'])): ?>
                <?php 
                $hasItems = false;
                while($item = $items->fetch_assoc()): 
                    $hasItems = true;
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($item['description']) ?></td>
                    <td style="text-align:center"><?= $item['quantity'] ?></td>
                    <td style="text-align:right">Rs <?= number_format($item['unit_price'], 2) ?></td>
                    <td style="text-align:right; font-weight: 700; color: #4f46e5;">Rs <?= number_format($item['total'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$hasItems): ?>
                <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 40px;">No items in this purchase</td></tr>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach($items as $k => $item): ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($item['description']) ?></td>
                    <td style="text-align:center">
                        <input type="number" 
                               class="qty-input-editable" 
                               value="<?= $item['quantity'] ?>" 
                               min="1" 
                               data-index="<?= $k ?>"
                               data-old-value="<?= $item['quantity'] ?>">
                    </td>
                    <td style="text-align:right">Rs <?= number_format($item['unit_price'], 2) ?></td>
                    <td style="text-align:right; font-weight: 700; color: #4f46e5;" class="item-total">Rs <?= number_format($item['total'], 2) ?></td>
                    <td style="text-align: center;">
                        <a href="remove_purchase_item.php?i=<?= $k ?>&supplier_id=<?= $supplier_id ?>&source=invoice" 
                           onclick="return confirm('Remove this item from purchase?')"
                           class="remove-link">×</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($items)): ?>
                <tr><td colspan="5" style="text-align: center; color: #64748b; padding: 40px;">No items yet. <a href="purchase_pos.php?supplier_id=<?= $supplier_id ?>" style="color: #4f46e5; font-weight: 600;">Add items</a></td></tr>
                <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($saved === 1): ?>
    <!-- Payment Summary for Saved Purchases - REMOVED no-print class -->
    <div class="payment-summary">
        <h4>💰 Payment Summary</h4>
        <div class="payment-row">
            <span>Total Amount:</span>
            <span style="font-weight: 700;">Rs <?= number_format($purchase['total'], 2) ?></span>
        </div>
        <div class="payment-row">
            <span>Amount Paid:</span>
            <span style="font-weight: 700; color: #10b981;">Rs <?= number_format($purchase['amount_paid'], 2) ?></span>
        </div>
        <div class="payment-row">
            <span>Balance Remaining:</span>
            <span style="font-weight: 700; color: <?= $purchase['balance'] > 0 ? '#dc2626' : '#10b981' ?>;">
                Rs <?= number_format($purchase['balance'], 2) ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <div class="total-box">
        <div class="label">TOTAL PURCHASE AMOUNT</div>
        <div class="amount">Rs <?= number_format($purchase['total'], 2) ?></div>
    </div>

    <?php if ($saved === 0): ?>
    <!-- Initial Payment Form (Draft Purchase) -->
    <div class="payment-form no-print">
        <h4>💰 Payment Information</h4>
        <form method="post" action="save_purchase_to_db.php?supplier_id=<?= $supplier_id ?>" onsubmit="return confirm('Save this purchase? Stock will be updated.');">
            <label>Amount Paid (Rs):</label>
            <input type="number" name="amount_paid" step="0.01" min="0" max="<?= $purchase['total'] ?>" value="0" required placeholder="0.00">
            <button type="submit" class="btn btn-save">💾 Save Purchase</button>
        </form>
        <p style="margin-top: 15px; font-size: 0.85rem; color: #64748b;">
            <strong>Note:</strong> When you save, product stock will be automatically increased.
        </p>
    </div>
    <?php endif; ?>

  <?php if ($saved == 1 && isset($purchase['payment_status']) && $purchase['payment_status'] !== 'paid'): ?>
<!-- Update Payment Section (Saved Purchase with Balance) -->
<div id="updatePaymentSection" style="display: none;" class="update-payment-section no-print">
    <h4>💵 Add Additional Payment</h4>
    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
        <div>
            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #92400e; margin-bottom: 5px;">
                Current Balance
            </label>
            <input type="text" value="Rs <?= number_format($purchase['balance'], 2) ?>" readonly 
                   style="background: #fee2e2; font-weight: 700; color: #991b1b; padding: 10px; border: none; border-radius: 6px; width: 180px;">
        </div>
        <div>
            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #92400e; margin-bottom: 5px;">
                Payment Amount (Rs)
            </label>
            <input type="number" id="additional_payment" min="0" max="<?= $purchase['balance'] ?>" step="0.01" placeholder="0.00" 
                   style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 180px;">
        </div>
        <div style="margin-top: 20px;">
            <button onclick="submitPayment()" class="btn btn-save">✅ Submit Payment</button>
            <button onclick="cancelPayment()" class="btn btn-outline">Cancel</button>
        </div>
    </div>
</div>
<?php endif; ?>

    <div class="actions no-print">
        <?php if ($saved === 0): ?>
            <a href="purchase_pos.php?supplier_id=<?= $supplier_id ?>" class="btn btn-outline">← Add More Items</a>
        <?php else: ?>
            <a href="purchase_pos.php" class="btn btn-outline">Create New Purchase</a>
            <button onclick="window.print()" class="btn btn-print">🖨️ Print Invoice</button>
            
            <?php if ($purchase['payment_status'] !== 'paid'): ?>
                <button onclick="showUpdatePayment()" class="btn btn-update-payment">💵 Update Payment</button>
            <?php endif; ?>
        <?php endif; ?>
        <a href="view_purchases.php" class="btn btn-outline">📋 View All Purchases</a>
    </div>
</div>

<script>
function showUpdatePayment() {
    const section = document.getElementById('updatePaymentSection');
    if (!section) {
        alert('⚠️ Update payment section not available!');
        return;
    }
    section.style.display = 'block';
    section.scrollIntoView({ behavior: 'smooth' });
}

function cancelPayment() {
    const section = document.getElementById('updatePaymentSection');
    if (section) {
        section.style.display = 'none';
    }
    const input = document.getElementById('additional_payment');
    if (input) {
        input.value = '';
    }
}

function submitPayment() {
    const amount = parseFloat(document.getElementById('additional_payment').value) || 0;
    const maxBalance = <?= $purchase['balance'] ?? 0 ?>;
    
    if (amount <= 0) {
        alert('⚠️ Please enter a valid payment amount!');
        return;
    }
    
    if (amount > maxBalance) {
        alert('⚠️ Payment amount cannot exceed remaining balance!');
        return;
    }
    
    if (confirm('Add payment of Rs ' + amount.toFixed(2) + '?')) {
        window.location.href = 'update_purchase_payment.php?purchase_id=<?= $id ?? 0 ?>&amount=' + amount;
    }
}

// Handle quantity changes for draft purchases
<?php if ($saved === 0): ?>
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input-editable')) {
        updatePurchaseQuantity(e.target);
    }
});

function updatePurchaseQuantity(input) {
    const index = input.dataset.index;
    const newQty = parseInt(input.value);
    const oldQty = parseInt(input.dataset.oldValue);
    
    if (newQty <= 0 || isNaN(newQty)) {
        input.value = oldQty;
        return;
    }
    
    fetch('update_purchase_quantity.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ index: index, quantity: newQty })
    })
    .then(r => r.json())
    .then(d => {
        if (d.error) {
            alert(d.error);
            input.value = oldQty;
        } else {
            input.dataset.oldValue = newQty;
            const row = input.closest('tr');
            row.querySelector('.item-total').textContent = 'Rs ' + d.newTotal;
            document.querySelector('.total-box .amount').textContent = 'Rs ' + d.newGrandTotal;
        }
    })
    .catch(err => {
        console.error('Error updating quantity:', err);
        alert('Error updating quantity. Please try again.');
        input.value = oldQty;
    });
}
<?php endif; ?>
</script>

<?php if (isset($_GET['saved']) && $_GET['saved'] == 1): ?>
    <script> 
        alert("✅ Purchase saved successfully!\n\n📦 Stock has been updated automatically."); 
    </script>
<?php endif; ?>

<?php if (isset($_GET['payment_updated'])): ?>
    <script> alert("✅ Payment updated successfully!"); </script>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <script>
        const error = '<?= $_GET['error'] ?>';
        if (error === 'invalid_amount') alert('⚠️ Invalid payment amount!');
        if (error === 'payment_exceeds_balance') alert('⚠️ Payment amount exceeds remaining balance!');
        if (error === 'update_failed') alert('❌ Failed to update payment. Please try again.');
    </script>
<?php endif; ?>

</body>
</html>