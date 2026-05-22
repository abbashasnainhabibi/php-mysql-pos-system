<?php
session_start();
include("config/db.php");

// Priority: Database > URL parameters
$customer_name = '';
$customer_phone = '';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $i = $conn->query("SELECT * FROM invoices WHERE id=$id")->fetch_assoc();
    $saved = (int) $i['saved'];
    $it = $conn->query("SELECT * FROM invoice_items WHERE invoice_id=$id");
    
    // Get customer info from database if saved
    if ($saved === 1) {
        $customer_name = $i['customer_name'] ?? '';
        $customer_phone = $i['customer_phone'] ?? '';
    } else {
        // For draft invoices, use URL params
        $customer_name = isset($_GET['c_name']) ? htmlspecialchars($_GET['c_name']) : '';
        $customer_phone = isset($_GET['c_phone']) ? htmlspecialchars($_GET['c_phone']) : '';
    }
} else {
    // New invoice - get from URL params
    $customer_name = isset($_GET['c_name']) ? htmlspecialchars($_GET['c_name']) : '';
    $customer_phone = isset($_GET['c_phone']) ? htmlspecialchars($_GET['c_phone']) : '';
    
    $saved = 0;
    $i = [
        'invoice_number' => 'DRAFT-' . date('Ymd-His'),
        'invoice_date' => date('Y-m-d H:i:s'),
        'total' => 0,
        'amount_paid' => 0,
        'balance' => 0
    ];
    $it = $_SESSION['invoice_items'] ?? [];
    foreach ($it as $item) {
        $i['total'] += $item['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?= $i['invoice_number'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        body { background: var(--bg); font-family: 'Inter', sans-serif; margin: 0; padding: 40px 20px; color: var(--text-dark); }
        .page-wrapper { max-width: 900px; margin: 0 auto; }

        .invoice-card { 
            background: var(--card-bg); border-radius: 20px; padding: 50px; 
            box-shadow: var(--shadow); border: 1px solid var(--border); 
            position: relative; overflow: hidden; margin-bottom: 30px;
        }

        .status-badge {
            position: absolute; top: 20px; right: -35px;
            background: <?= $saved === 1 ? '#10b981' : '#f59e0b' ?>;
            color: white; padding: 5px 40px; transform: rotate(45deg);
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
        }

        .invoice-header { 
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 2px solid var(--bg); padding-bottom: 30px; margin-bottom: 40px; 
        }

        .company h1 { margin: 0; font-size: 1.8rem; color: var(--primary); letter-spacing: -1px; }
        .company span { color: var(--text-light); font-size: 0.9rem; }

        .customer-display-area { margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--border); }
        .bill-to-title { font-size: 0.7rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; margin-bottom: 5px; display: block; }

        .invoice-meta { text-align: right; }
        .invoice-meta h2 { margin: 0 0 10px 0; font-size: 24px; color: var(--primary); font-weight: 800; }
        .invoice-meta p { margin: 2px 0; font-size: 0.9rem; color: var(--text-dark); }

        .invoice-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .invoice-table th { text-align: left; padding: 15px 10px; border-bottom: 2px solid var(--bg); color: var(--text-light); font-size: 0.75rem; text-transform: uppercase; }
        .invoice-table td { padding: 15px 10px; border-bottom: 1px solid var(--bg); font-size: 0.95rem; }

        .qty-input { border: 1px solid var(--border); border-radius: 6px; padding: 5px; text-align: center; width: 60px; font-weight: 400; }
        .qty-input[readonly] { border: none; background: transparent; cursor: default; }

        .invoice-footer-grid { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; }
        
        .payment-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            border-radius: 12px;
            color: white;
            min-width: 300px;
        }
        
        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .payment-row:last-child {
            border-bottom: 2px solid rgba(255,255,255,0.4);
            margin-top: 5px;
            padding-top: 10px;
        }
        
        .payment-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .payment-value {
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .balance-value {
            font-size: 1.5rem;
            font-weight: 800;
        }

        /* Form for Inputting Customer info */
        .customer-form { 
            background: #fff; padding: 20px; border-radius: 15px; border: 1px solid var(--border); 
            margin-bottom: 20px;
        }
        .customer-form-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .form-input-group { flex: 1; min-width: 200px; }
        .form-input-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-light); margin-bottom: 5px; }
        .form-input-group input, .form-input-group select { 
            width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; 
            font-size: 0.95rem; font-family: 'Inter', sans-serif;
        }
        .form-input-group input:focus, .form-input-group select:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        #custom_name_input { display: none; }

        .payment-input-section {
            background: #f0fdf4;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #86efac;
            margin-top: 15px;
        }
        
        .payment-input-section h4 {
            margin: 0 0 15px 0;
            color: var(--success);
            font-size: 1rem;
        }

        .btn { 
            padding: 12px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; 
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; 
            border: 1px solid var(--border); font-size: 0.9rem;
        }
        .btn-save { background: #10b981; color: white; border: none; }
        .btn-print { background: var(--primary); color: white; border: none; }
        .btn-pdf { background: #dc2626; color: white; border: none; }
        .btn-outline { background: white; color: var(--text-dark); }
        .btn-update-payment { background: var(--warning); color: white; border: none; margin-top: 10px; }

        .no-print { display: flex; gap: 10px; flex-wrap: wrap; }
        
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
        
        /* Compact print styles */
        @media print { 
            .no-print, .customer-form, .status-badge, .remove-link, .btn-update-payment { display: none !important; } 
            body { 
                padding: 0; 
                background: white;
                font-size: 11px;
            } 
            .invoice-card { 
                box-shadow: none; 
                border: none; 
                padding: 15px;
                border-radius: 0;
            }
            .company h1 { 
                font-size: 1.3rem; 
                margin-bottom: 3px;
            }
            .company span { 
                font-size: 0.75rem; 
            }
            .invoice-header {
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
            .invoice-meta h2 { 
                font-size: 18px; 
            }
            .invoice-meta p { 
                font-size: 0.75rem;
                margin: 1px 0;
            }
            .customer-display-area {
                margin-top: 10px;
                padding-top: 8px;
            }
            .bill-to-title {
                font-size: 0.6rem;
            }
            #disp_name {
                font-size: 0.85rem !important;
            }
            #disp_phone {
                font-size: 0.7rem !important;
            }
            .invoice-table { 
                margin-top: 15px;
                font-size: 0.8rem;
            }
            .invoice-table th { 
                padding: 8px 5px;
                font-size: 0.65rem;
            }
            .invoice-table td { 
                padding: 8px 5px;
                font-size: 0.8rem;
            }
            .invoice-footer-grid {
                margin-top: 20px;
            }
            .note {
                font-size: 0.7rem;
            }
            .payment-summary {
            padding: 15px;
            min-width: 250px;
            background: white !important;        
            border: 2px solid #000 !important;   
            color: #000 !important;              
            }
            .payment-row {
                margin-bottom: 6px;
                padding-bottom: 6px;
            }
            payment-label {
            font-size: 0.7rem;
            color: #000 !important;              
            opacity: 1 !important;               
            }

          .payment-value {
            font-size: 0.9rem;
            color: #000 !important;              
            }

           .balance-value {
            font-size: 1.1rem;
            color: #000 !important;             
            font-weight: 500 !important;
            }
            .payment-status-badge {
                font-size: 0.65rem;
                padding: 3px 8px;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <?php if ($saved === 0): ?>
    <div class="customer-form no-print">
        <div class="customer-form-row">
            <div class="form-input-group">
                <label>CUSTOMER TYPE *</label>
                <select id="customer_type" onchange="toggleCustomerInput()">
                    <option value="walkin" <?= $customer_name === 'Walk-in Customer' ? 'selected' : '' ?>>Walk-in Customer</option>
                    <option value="custom" <?= $customer_name !== 'Walk-in Customer' && $customer_name !== '' ? 'selected' : '' ?>>Custom Name</option>
                </select>
            </div>
            <div class="form-input-group" id="custom_name_input" style="<?= $customer_name !== 'Walk-in Customer' && $customer_name !== '' ? 'display: block;' : 'display: none;' ?>">
                <label>CUSTOMER NAME *</label>
                <input type="text" id="cust_name_input" value="<?= $customer_name !== 'Walk-in Customer' ? $customer_name : '' ?>" placeholder="Enter customer name">
            </div>
            <div class="form-input-group">
                <label>PHONE (OPTIONAL)</label>
                <input type="text" id="cust_phone_input" value="<?= $customer_phone ?>" placeholder="Enter phone number">
            </div>
            <button onclick="applyCustomer()" class="btn btn-outline" style="background: var(--primary); color: white; border: none;">Apply to Invoice</button>
        </div>
        
        <div class="payment-input-section">
            <h4>💰 Payment Information</h4>
            <div class="customer-form-row">
                <div class="form-input-group">
                    <label>AMOUNT RECEIVED (Rs)</label>
                    <input type="number" id="amount_paid_input" min="0" step="0.01" value="0" placeholder="0.00" onchange="calculateBalance()">
                </div>
                <div class="form-input-group">
                    <label>TOTAL AMOUNT</label>
                    <input type="text" id="total_display" value="Rs <?= number_format($i['total'], 2) ?>" readonly style="background: #f1f5f9; font-weight: 700;">
                </div>
                <div class="form-input-group">
                    <label>BALANCE / REMAINING</label>
                    <input type="text" id="balance_display" value="Rs <?= number_format($i['total'], 2) ?>" readonly style="background: #fee2e2; font-weight: 700; color: #991b1b;">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="invoice-card" id="invoiceContent">
        <div class="status-badge"><?= $saved === 1 ? 'SAVED' : 'DRAFT' ?></div>

        <div class="invoice-header">
            <div class="company">
                <h1>Paint Business</h1>
                <span>Quality Paints & Hardware</span>
                
                <div id="customer_display" class="customer-display-area" style="<?= empty($customer_name) ? 'display: none;' : '' ?>">
                    <span class="bill-to-title">Bill To:</span>
                    <div id="disp_name" style="font-weight: 700; font-size: 1rem;"><?= $customer_name ?></div>
                    <div id="disp_phone" style="color: var(--text-light); font-size: 0.85rem;"><?= $customer_phone ? $customer_phone : '' ?></div>
                </div>
            </div>
            <div class="invoice-meta">
                <h2>INVOICE</h2>
                <p><strong>No:</strong> <?= $i['invoice_number'] ?></p>
                <p><strong>Date:</strong> <?= date('M d, Y', strtotime($i['invoice_date'])) ?></p>
                
                <?php if ($saved === 1): 
                    $payment_status = $i['payment_status'] ?? 'unpaid';
                    $status_class = 'status-' . $payment_status;
                    $status_text = ucfirst($payment_status);
                    if ($payment_status === 'partial') $status_text = 'Partially Paid';
                ?>
                    <span class="payment-status-badge <?= $status_class ?>"><?= $status_text ?></span>
                <?php endif; ?>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align:center">Qty</th>
                    <th style="text-align:right">Price</th>
                    <th style="text-align:right">Total</th>
                    <th class="no-print"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($_GET['id'])): 
                    $rowIndex = 0;
                    while ($r = $it->fetch_assoc()): ?>
                    <tr data-product-id="<?= $r['product_id'] ?>" data-invoice-id="<?= $id ?>">
                        <td style="font-weight:600"><?= $r['description'] ?></td>
                        <td style="text-align:center">
                            <input type="number" class="qty-input" value="<?= $r['quantity'] ?>" 
                                   data-index="<?= $rowIndex ?>" data-old-value="<?= $r['quantity'] ?>" 
                                   <?= $saved === 1 ? 'readonly' : '' ?>>
                        </td>
                        <td style="text-align:right">Rs <?= number_format($r['price'], 2) ?></td>
                        <td class="item-total" style="text-align:right; font-weight:700">Rs <?= number_format($r['total'], 2) ?></td>
                        <td class="no-print" style="text-align:right">
                            <?php if ($saved === 0): ?>
                               <a href="remove_invoice_item.php?id=<?= $r['id'] ?>&invoice_id=<?= $id ?>&c_name=<?= urlencode($customer_name) ?>&c_phone=<?= urlencode($customer_phone) ?>" class="remove-link" style="color:red; text-decoration:none;">Remove</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php $rowIndex++; endwhile; ?>
                <?php else: 
                    foreach ($it as $k => $r): ?>
                    <tr>
                        <td style="font-weight:600"><?= $r['description'] ?></td>
                        <td style="text-align:center">
                             <input type="number" class="qty-input" value="<?= $r['quantity'] ?>" data-index="<?= $k ?>">
                        </td>
                        <td style="text-align:right">Rs <?= number_format($r['price'], 2) ?></td>
                        <td class="item-total" style="text-align:right; font-weight:700">Rs <?= number_format($r['total'], 2) ?></td>
                        <td class="no-print" style="text-align:right">
                           <a href="remove_item.php?i=<?= $k ?>&from=view&c_name=<?= urlencode($customer_name) ?>&c_phone=<?= urlencode($customer_phone) ?>" class="remove-link" style="color:red; text-decoration:none;">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="invoice-footer-grid">
            <div class="note">
                Generated by computer, no signature needed.<br>
                <strong>Thank you for your business!</strong>
            </div>
            <div class="payment-summary">
                <div class="payment-row">
                    <span class="payment-label">Total Amount:</span>
                    <span class="payment-value" id="invoice_total">Rs <?= number_format($i['total'], 2) ?></span>
                </div>
                <div class="payment-row">
                    <span class="payment-label">Amount Paid:</span>
                    <span class="payment-value" id="invoice_paid">Rs <?= number_format($i['amount_paid'] ?? 0, 2) ?></span>
                </div>
                <div class="payment-row">
                    <span class="payment-label">Balance:</span>
                    <span class="balance-value" id="invoice_balance">Rs <?= number_format($i['balance'] ?? $i['total'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="invoice-actions no-print">
        <?php if ($saved === 0): ?>
            <a href="#" onclick="goToAddMore(); return false;" class="btn btn-outline">➕ Add More</a>
            <button onclick="saveWithCustomer()" class="btn btn-save" id="saveBtn">💾 Save to Database</button>
        <?php else: ?>
            <a href="index.php" class="btn btn-outline">← POS</a>
            <button onclick="window.print()" class="btn btn-print">🖨️ Print</button>
            <button onclick="downloadPDF()" class="btn btn-pdf">📄 Download PDF</button>
            
            <?php if ($i['payment_status'] !== 'paid'): ?>
                <button onclick="showUpdatePayment()" class="btn btn-update-payment">💵 Update Payment</button>
            <?php endif; ?>
            
            <a href="index.php" class="btn btn-outline" style="background: var(--primary); color:white; border:none;">Create New</a>
        <?php endif; ?>
    </div>
    
    <?php if ($saved === 1 && $i['payment_status'] !== 'paid'): ?>
    <div id="updatePaymentForm" style="display: none;" class="customer-form no-print">
        <h3 style="margin-top: 0; color: var(--primary);">💰 Add Payment</h3>
        <div class="customer-form-row">
            <div class="form-input-group">
                <label>CURRENT BALANCE</label>
                <input type="text" value="Rs <?= number_format($i['balance'], 2) ?>" readonly style="background: #fee2e2; font-weight: 700;">
            </div>
            <div class="form-input-group">
                <label>ADDITIONAL PAYMENT (Rs)</label>
                <input type="number" id="additional_payment" min="0" max="<?= $i['balance'] ?>" step="0.01" placeholder="0.00">
            </div>
            <button onclick="submitAdditionalPayment()" class="btn btn-save">Submit Payment</button>
            <button onclick="document.getElementById('updatePaymentForm').style.display='none'" class="btn btn-outline">Cancel</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    const invoiceTotal = <?= $i['total'] ?>;
    const invoiceNumber = '<?= $i['invoice_number'] ?>';
    
    function downloadPDF() {
        const element = document.getElementById('invoiceContent');
        const opt = {
            margin: 0.5,
            filename: 'Invoice-' + invoiceNumber + '.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        
        // Show loading message
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ Generating PDF...';
        btn.disabled = true;
        
        html2pdf().set(opt).from(element).save().then(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
    
    function calculateBalance() {
        const amountPaid = parseFloat(document.getElementById('amount_paid_input').value) || 0;
        const balance = invoiceTotal - amountPaid;
        
        // Validate payment doesn't exceed total
        if (amountPaid > invoiceTotal) {
            alert('⚠️ Amount paid cannot exceed total amount!');
            document.getElementById('amount_paid_input').value = invoiceTotal;
            document.getElementById('balance_display').value = 'Rs 0.00';
            document.getElementById('invoice_balance').textContent = 'Rs 0.00';
            document.getElementById('invoice_paid').textContent = 'Rs ' + invoiceTotal.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            return;
        }
        
        // Update balance display in the form
        document.getElementById('balance_display').value = 'Rs ' + balance.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Update balance in the payment summary box (on the invoice card)
        document.getElementById('invoice_balance').textContent = 'Rs ' + balance.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Update amount paid in the payment summary box
        document.getElementById('invoice_paid').textContent = 'Rs ' + amountPaid.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Update color based on balance
        const balanceInput = document.getElementById('balance_display');
        if (balance === 0) {
            balanceInput.style.background = '#d1fae5';
            balanceInput.style.color = '#065f46';
        } else if (balance < invoiceTotal) {
            balanceInput.style.background = '#fef3c7';
            balanceInput.style.color = '#92400e';
        } else {
            balanceInput.style.background = '#fee2e2';
            balanceInput.style.color = '#991b1b';
        }
    }
    
    function toggleCustomerInput() {
        const customerType = document.getElementById('customer_type').value;
        const customNameInput = document.getElementById('custom_name_input');
        
        if (customerType === 'custom') {
            customNameInput.style.display = 'block';
        } else {
            customNameInput.style.display = 'none';
        }
    }

    function updateAllLinks(name, phone) {
        const encodedName = encodeURIComponent(name);
        const encodedPhone = encodeURIComponent(phone);
        const params = 'c_name=' + encodedName + '&c_phone=' + encodedPhone;

        document.querySelectorAll('a[href*="remove_"]').forEach(link => {
            const href = link.getAttribute('href');
            if (href.includes('?')) {
                const baseUrl = href.split('?')[0];
                const existingParams = href.split('?')[1];
                const paramsArray = existingParams.split('&').filter(p => !p.startsWith('c_name=') && !p.startsWith('c_phone='));
                link.href = baseUrl + '?' + paramsArray.join('&') + '&' + params;
            } else {
                link.href = href + '?' + params;
            }
        });
    }

    function applyCustomer() {
        const customerType = document.getElementById('customer_type').value;
        let name = '';
        
        if (customerType === 'walkin') {
            name = 'Walk-in Customer';
        } else {
            name = document.getElementById('cust_name_input').value.trim();
            if (name === "") {
                alert("⚠️ Please enter a customer name.");
                document.getElementById('cust_name_input').focus();
                return;
            }
        }
        
        const phone = document.getElementById('cust_phone_input').value.trim();
        const displayBox = document.getElementById('customer_display');

        document.getElementById('disp_name').innerText = name;
        document.getElementById('disp_phone').innerText = phone;
        displayBox.style.display = "block";

        updateAllLinks(name, phone);
    }

    function goToAddMore() {
        const customerType = document.getElementById('customer_type').value;
        let name = '';
        
        if (customerType === 'walkin') {
            name = 'Walk-in Customer';
        } else {
            name = document.getElementById('cust_name_input').value.trim();
        }
        
        const phone = document.getElementById('cust_phone_input').value.trim();
        
        const encodedName = encodeURIComponent(name);
        const encodedPhone = encodeURIComponent(phone);
        window.location.href = 'index.php?c_name=' + encodedName + '&c_phone=' + encodedPhone;
    }

    function saveWithCustomer() {
        const customerType = document.getElementById('customer_type').value;
        let name = '';
        
        if (customerType === 'walkin') {
            name = 'Walk-in Customer';
        } else {
            name = document.getElementById('cust_name_input').value.trim();
            if (name === "") {
                alert("⚠️ Customer name is required before saving!");
                document.getElementById('cust_name_input').focus();
                return;
            }
        }
        
        const phone = document.getElementById('cust_phone_input').value.trim();
        const amountPaid = parseFloat(document.getElementById('amount_paid_input').value) || 0;
        
        // Validate payment
        if (amountPaid > invoiceTotal) {
            alert('⚠️ Amount paid cannot exceed total amount!');
            return;
        }
        
        document.getElementById('disp_name').innerText = name;
        document.getElementById('disp_phone').innerText = phone;
        document.getElementById('customer_display').style.display = "block";
        updateAllLinks(name, phone);
        
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.innerText = "Saving...";
        saveBtn.style.pointerEvents = "none";

        let url = "save_invoice_to_db.php<?= isset($id) ? '?id=' . $id : '' ?>";
        url += (url.includes('?') ? '&' : '?') + "c_name=" + encodeURIComponent(name) + "&c_phone=" + encodeURIComponent(phone) + "&amount_paid=" + amountPaid;
        
        window.location.href = url;
    }
    
    function showUpdatePayment() {
        document.getElementById('updatePaymentForm').style.display = 'block';
        document.getElementById('updatePaymentForm').scrollIntoView({ behavior: 'smooth' });
    }
    
    function submitAdditionalPayment() {
        const additionalPayment = parseFloat(document.getElementById('additional_payment').value) || 0;
        
        if (additionalPayment <= 0) {
            alert('⚠️ Please enter a valid payment amount!');
            return;
        }
        
        const currentBalance = <?= $i['balance'] ?? $i['total'] ?>;
        
        if (additionalPayment > currentBalance) {
            alert('⚠️ Payment amount cannot exceed remaining balance!');
            return;
        }
        
        if (confirm('Add payment of Rs ' + additionalPayment.toFixed(2) + '?')) {
            window.location.href = 'update_payment.php?invoice_id=<?= $id ?? 0 ?>&amount=' + additionalPayment;
        }
    }

    window.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($customer_name) && $saved === 0): ?>
        const name = '<?= addslashes($customer_name) ?>';
        const phone = '<?= addslashes($customer_phone) ?>';
        if (name) {
            updateAllLinks(name, phone);
        }
        <?php endif; ?>
    });

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('qty-input')) {
            updateQuantity(e.target);
        }
    });

    function updateQuantity(input) {
        const index = input.dataset.index;
        const newQty = parseInt(input.value);
        const oldQty = parseInt(input.dataset.oldValue || 0);

        if (newQty <= 0) { input.value = oldQty; return; }

        const row = input.closest('tr');
        const productId = row.dataset.productId;
        const invoiceId = row.dataset.invoiceId;

        fetch('update_quantity.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ index: index, quantity: newQty, product_id: productId, invoice_id: invoiceId, oldQty: oldQty })
        })
        .then(r => r.json())
        .then(d => {
            if (d.error) {
                alert(d.error);
                input.value = d.oldQty;
            } else {
                input.dataset.oldValue = newQty;
                row.querySelector('.item-total').textContent = 'Rs ' + d.newTotal;
                
                // Update all total displays
                const newGrandTotal = parseFloat(d.newGrandTotal);
                document.getElementById('invoice_total').textContent = 'Rs ' + newGrandTotal.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('total_display').value = 'Rs ' + newGrandTotal.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                // Update balance (total - amount paid)
                const amountPaid = parseFloat(document.getElementById('amount_paid_input').value) || 0;
                const newBalance = newGrandTotal - amountPaid;
                document.getElementById('balance_display').value = 'Rs ' + newBalance.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('invoice_balance').textContent = 'Rs ' + newBalance.toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                // Update global invoiceTotal variable
                invoiceTotal = newGrandTotal;
                
                // Update balance color
                calculateBalance();
            }
        });
    }

</script>

<?php if (isset($_GET['saved'])): ?>
    <script> alert("✅ Invoice saved successfully!"); </script>
<?php endif; ?>

<?php if (isset($_GET['payment_updated'])): ?>
    <script> alert("✅ Payment updated successfully!"); </script>
<?php endif; ?>

</body>
</html>