<?php
include("config/db.php");

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : 'invoice_number';
$payment_filter = isset($_GET['payment_status']) ? $_GET['payment_status'] : 'all';

$query = "SELECT * FROM invoices WHERE saved = 1";

// Apply search filters
if ($search_term) {
    switch ($search_type) {
        case 'invoice_number':
            $query .= " AND invoice_number LIKE '%" . $conn->real_escape_string($search_term) . "%'";
            break;
        case 'customer_name':
            $query .= " AND customer_name LIKE '%" . $conn->real_escape_string($search_term) . "%'";
            break;
        case 'customer_phone':
            $query .= " AND customer_phone LIKE '%" . $conn->real_escape_string($search_term) . "%'";
            break;
        case 'date':
            $query .= " AND DATE(invoice_date) = '" . $conn->real_escape_string($search_term) . "'";
            break;
        case 'month':
            $query .= " AND DATE_FORMAT(invoice_date, '%Y-%m') = '" . $conn->real_escape_string($search_term) . "'";
            break;
        case 'year':
            $query .= " AND YEAR(invoice_date) = '" . $conn->real_escape_string($search_term) . "'";
            break;
    }
}

// Apply payment status filter
if ($payment_filter !== 'all') {
    $query .= " AND payment_status = '" . $conn->real_escape_string($payment_filter) . "'";
}

$query .= " ORDER BY invoice_date DESC";
$result = $conn->query($query);

// Calculate summary statistics
$stats_query = "SELECT 
    COUNT(*) as total_invoices,
    SUM(total) as total_amount,
    SUM(amount_paid) as total_paid,
    SUM(balance) as total_balance,
    SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count,
    SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) as partial_count,
    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count
    FROM invoices WHERE saved = 1";
$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Invoices | Paint POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text-dark); margin: 0; padding-bottom: 40px; }

        header { 
            background: #fff; border-bottom: 1px solid var(--border);
            padding: 1.5rem 2rem; font-weight: 700; font-size: 1.5rem; color: var(--primary);
            display: flex; justify-content: space-between; align-items: center;
        }

        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .stat-card.unpaid { border-left: 4px solid var(--danger); }
        .stat-card.partial { border-left: 4px solid var(--warning); }
        .stat-card.paid { border-left: 4px solid var(--success); }

        /* Search Card Styling */
        .search-card {
            background: var(--card-bg); padding: 25px; border-radius: 16px;
            border: 1px solid var(--border); box-shadow: var(--shadow);
            margin-bottom: 25px;
        }
        
        .search-card h2 { font-size: 1.1rem; margin-top: 0; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px; }

        .form-row { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }

        select, input[type="text"] {
            padding: 12px 15px; border: 1px solid var(--border); border-radius: 10px;
            outline: none; font-size: 0.95rem; background: #fff;
        }
        
        input[type="text"] { flex-grow: 1; min-width: 200px; }
        select:focus, input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }

        /* Table Styling */
        .table-card {
            background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border);
            box-shadow: var(--shadow); overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        th { 
            background: #f1f5f9; text-align: left; padding: 18px 20px; 
            font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px;
        }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8fafc; }

        .customer-info { color: var(--text-light); font-size: 0.85rem; margin-top: 3px; }

        .payment-status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }

        /* Buttons */
        .btn {
            padding: 12px 24px; border-radius: 10px; text-decoration: none;
            font-weight: 600; font-size: 0.9rem; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: white; color: var(--text-dark); border: 1px solid var(--border); }
        .btn-secondary:hover { background: #f1f5f9; }
        
        .btn-view { 
            background: #eef2ff; color: var(--primary); padding: 8px 16px; 
            border-radius: 8px; font-size: 0.85rem; font-weight: 700;
        }
        .btn-view:hover { background: var(--primary); color: white; }

        .actions { margin-bottom: 20px; }
        
        .balance-amount {
            font-weight: 700;
            color: var(--danger);
        }
    </style>
</head>
<body>

<header>
    <div>Paint POS <span style="font-weight:300; opacity:0.7">Invoices</span></div>
    <div style="font-size: 0.9rem; font-weight:400; color: var(--text-light)">Records Management</div>
</header>

<div class="container">
    <div class="actions">
        <a href="index.php" class="btn btn-secondary">← Back to POS</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <script>alert('Invoice saved successfully!');</script>
    <?php endif; ?>

    <!-- Summary Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Invoices</div>
            <div class="stat-value"><?= $stats['total_invoices'] ?></div>
        </div>
        <div class="stat-card unpaid">
            <div class="stat-label">Unpaid</div>
            <div class="stat-value" style="color: var(--danger)"><?= $stats['unpaid_count'] ?></div>
        </div>
        <div class="stat-card partial">
            <div class="stat-label">Partially Paid</div>
            <div class="stat-value" style="color: var(--warning)"><?= $stats['partial_count'] ?></div>
        </div>
        <div class="stat-card paid">
            <div class="stat-label">Fully Paid</div>
            <div class="stat-value" style="color: var(--success)"><?= $stats['paid_count'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Balance Due</div>
            <div class="stat-value" style="color: var(--danger); font-size: 1.5rem;">Rs <?= number_format($stats['total_balance'], 2) ?></div>
        </div>
    </div>

    <div class="search-card">
        <h2>🔍 Search & Filter Invoices</h2>
        <form method="GET" action="view_invoices.php" class="form-row">
            <select name="type">
                <option value="invoice_number" <?= $search_type == 'invoice_number' ? 'selected' : '' ?>>Invoice Number</option>
                <option value="customer_name" <?= $search_type == 'customer_name' ? 'selected' : '' ?>>Customer Name</option>
                <option value="customer_phone" <?= $search_type == 'customer_phone' ? 'selected' : '' ?>>Customer Phone</option>
                <option value="date" <?= $search_type == 'date' ? 'selected' : '' ?>>Date (YYYY-MM-DD)</option>
                <option value="month" <?= $search_type == 'month' ? 'selected' : '' ?>>Month (YYYY-MM)</option>
                <option value="year" <?= $search_type == 'year' ? 'selected' : '' ?>>Year (YYYY)</option>
            </select>
            
            <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>" placeholder="Enter search term...">
            
            <select name="payment_status">
                <option value="all" <?= $payment_filter == 'all' ? 'selected' : '' ?>>All Payments</option>
                <option value="unpaid" <?= $payment_filter == 'unpaid' ? 'selected' : '' ?>>Unpaid Only</option>
                <option value="partial" <?= $payment_filter == 'partial' ? 'selected' : '' ?>>Partially Paid</option>
                <option value="paid" <?= $payment_filter == 'paid' ? 'selected' : '' ?>>Fully Paid</option>
            </select>
            
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="view_invoices.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">Paid</th>
                    <th style="text-align: right;">Balance</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight: 700; color: var(--text-dark);">#<?=$row['invoice_number']?></td>
                    <td>
                        <?php if (!empty($row['customer_name'])): ?>
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['customer_name']) ?></div>
                            <?php if (!empty($row['customer_phone'])): ?>
                                <div class="customer-info">📞 <?= htmlspecialchars($row['customer_phone']) ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--text-light); font-style: italic;">No customer info</span>
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--text-light);"><?= date('M d, Y', strtotime($row['invoice_date'])) ?></td>
                    <td style="text-align: right; font-weight: 700;">Rs <?= number_format($row['total'], 2) ?></td>
                    <td style="text-align: right; color: var(--success); font-weight: 600;">Rs <?= number_format($row['amount_paid'], 2) ?></td>
                    <td style="text-align: right;" class="<?= $row['balance'] > 0 ? 'balance-amount' : '' ?>">
                        Rs <?= number_format($row['balance'], 2) ?>
                    </td>
                    <td>
                        <?php 
                        $status = $row['payment_status'];
                        $status_text = ucfirst($status);
                        if ($status === 'partial') $status_text = 'Partial';
                        ?>
                        <span class="payment-status-badge status-<?= $status ?>"><?= $status_text ?></span>
                    </td>
                    <td style="text-align: right;">
                        <a href="invoice_view.php?id=<?=$row['id']?>" class="btn btn-view">View Details</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($result->num_rows == 0): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-light);">
                        <?= $search_term || $payment_filter !== 'all' ? 'No invoices found matching your search/filter.' : 'No invoices found.' ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>