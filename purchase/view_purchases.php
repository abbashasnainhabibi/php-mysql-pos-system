<?php
include("../config/db.php");

// Get filter parameters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : 'invoice_number';
$supplier_filter = isset($_GET['supplier']) ? (int)$_GET['supplier'] : 0;
$payment_filter = isset($_GET['payment_status']) ? $_GET['payment_status'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT pi.*, s.name as supplier_name 
          FROM purchase_invoices pi 
          JOIN suppliers s ON pi.supplier_id = s.id 
          WHERE pi.saved = 1";

// Apply search filters
if ($search_term) {
    switch ($search_type) {
        case 'invoice_number':
            $query .= " AND pi.invoice_number LIKE '%" . $conn->real_escape_string($search_term) . "%'";
            break;
        case 'supplier_name':
            $query .= " AND s.name LIKE '%" . $conn->real_escape_string($search_term) . "%'";
            break;
    }
}

// Apply supplier filter
if ($supplier_filter > 0) {
    $query .= " AND pi.supplier_id = $supplier_filter";
}

// Apply payment status filter
if ($payment_filter !== 'all') {
    $query .= " AND pi.payment_status = '" . $conn->real_escape_string($payment_filter) . "'";
}

// Apply date range filter
if (!empty($date_from)) {
    $query .= " AND DATE(pi.invoice_date) >= '" . $conn->real_escape_string($date_from) . "'";
}
if (!empty($date_to)) {
    $query .= " AND DATE(pi.invoice_date) <= '" . $conn->real_escape_string($date_to) . "'";
}

$query .= " ORDER BY pi.invoice_date DESC";
$result = $conn->query($query);

// Calculate summary statistics
$stats_query = "SELECT 
    COUNT(*) as total_purchases,
    SUM(total) as total_amount,
    SUM(amount_paid) as total_paid,
    SUM(balance) as total_outstanding,
    SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count,
    SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) as partial_count,
    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN payment_status = 'unpaid' THEN balance ELSE 0 END) as unpaid_amount,
    SUM(CASE WHEN payment_status = 'partial' THEN balance ELSE 0 END) as partial_amount
    FROM purchase_invoices WHERE saved = 1";

// Apply same filters to stats
if ($supplier_filter > 0) {
    $stats_query .= " AND supplier_id = $supplier_filter";
}
if ($payment_filter !== 'all') {
    $stats_query .= " AND payment_status = '" . $conn->real_escape_string($payment_filter) . "'";
}
if (!empty($date_from)) {
    $stats_query .= " AND DATE(invoice_date) >= '" . $conn->real_escape_string($date_from) . "'";
}
if (!empty($date_to)) {
    $stats_query .= " AND DATE(invoice_date) <= '" . $conn->real_escape_string($date_to) . "'";
}

$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoices | Stock Intake Records</title>
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
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 1.5rem 2rem; font-weight: 700; font-size: 1.5rem;
            color: var(--primary);
            display: flex; justify-content: space-between; align-items: center;
        }

        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }

        .action-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-block;
            font-size: 0.9rem;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: white; color: var(--text-dark); border: 1px solid var(--border); }
        .btn-secondary:hover { background: #f8fafc; }

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
        .stat-card.primary { border-left: 4px solid var(--primary); }

        /* Search Card */
        .search-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            margin-bottom: 25px;
        }
        
        .search-card h2 {
            font-size: 1.1rem;
            margin-top: 0;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        select, input[type="text"], input[type="date"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            font-size: 0.95rem;
            background: #fff;
        }
        
        select:focus, input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        /* Table */
        .table-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8fafc;
            text-align: left;
            padding: 18px 20px;
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        
        td {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: #f8fafc;
        }

        .supplier-info {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-top: 3px;
        }

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

        .btn-view {
            background: #f5f3ff;
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
        }
        .btn-view:hover {
            background: var(--primary);
            color: white;
        }

        .balance-amount {
            font-weight: 700;
            color: var(--danger);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .active-filters {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .filter-tag {
            display: inline-block;
            background: white;
            padding: 3px 10px;
            border-radius: 12px;
            margin-right: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<header>
    <div> Purchase Invoices <span style="font-weight:300; opacity:0.7">Stock Intake</span></div>
    <div style="font-size: 0.9rem; font-weight:400;">Records Management</div>
</header>

<div class="container">
    <div class="action-bar">
        <a href="purchase_pos.php" class="btn btn-primary">+ New Purchase</a>
        <a href="../index.php" class="btn btn-secondary">← Sales POS</a>
        <a href="../admin/suppliers.php" class="btn btn-secondary">🏢 Manage Suppliers</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <script>alert('✅ Purchase invoice saved successfully!');</script>
    <?php endif; ?>

    <!-- Active Filters Display -->
    <?php
    $active_filters = [];
    if ($payment_filter !== 'all') $active_filters[] = "Payment: " . ucfirst($payment_filter);
    if ($supplier_filter > 0) {
        $sup = $conn->query("SELECT name FROM suppliers WHERE id=$supplier_filter")->fetch_assoc();
        $active_filters[] = "Supplier: " . $sup['name'];
    }
    if (!empty($date_from)) $active_filters[] = "From: " . date('M d, Y', strtotime($date_from));
    if (!empty($date_to)) $active_filters[] = "To: " . date('M d, Y', strtotime($date_to));
    if ($search_term) $active_filters[] = "Search: " . htmlspecialchars($search_term);
    
    if (!empty($active_filters)):
    ?>
    <div class="active-filters">
        <strong>🔍 Active Filters:</strong>
        <?php foreach ($active_filters as $filter): ?>
            <span class="filter-tag"><?= $filter ?></span>
        <?php endforeach; ?>
        <a href="view_purchases.php" style="color: var(--primary); text-decoration: none; font-weight: 700; margin-left: 10px;">Clear All</a>
    </div>
    <?php endif; ?>

     <!-- Summary Statistics -->
    <div class="stats-grid">
        <?php if ($payment_filter === 'all'): ?>
            <!-- Show breakdown when viewing all payment statuses -->
            <div class="stat-card unpaid">
                <div class="stat-label">Unpaid Invoices</div>
                <div class="stat-value"><?= $stats['unpaid_count'] ?></div>
            </div>
            
            <div class="stat-card partial">
                <div class="stat-label">Partially Paid</div>
                <div class="stat-value"><?= $stats['partial_count'] ?></div>
            </div>
            
            <div class="stat-card paid">
                <div class="stat-label">Fully Paid</div>
                <div class="stat-value"><?= $stats['paid_count'] ?></div>
            </div>
            
            <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                <div class="stat-label">Total Purchasing</div>
                <div class="stat-value" style="color: #3b82f6;">Rs <?= number_format($stats['total_amount'], 2) ?></div>
            </div>
            
            <div class="stat-card" style="border-left: 4px solid var(--success);">
                <div class="stat-label">Total Paid</div>
                <div class="stat-value" style="color: var(--success);">Rs <?= number_format($stats['total_paid'], 2) ?></div>
            </div>
            
            <div class="stat-card" style="border-left: 4px solid var(--danger);">
                <div class="stat-label">Total Remaining</div>
                <div class="stat-value" style="color: var(--danger);">Rs <?= number_format($stats['total_outstanding'], 2) ?></div>
            </div>
        <?php else: ?>
            <!-- Show financial breakdown when filtering by specific payment status -->
            <div class="stat-card primary">
                <div class="stat-label"><?= ucfirst($payment_filter) ?> Invoices</div>
                <div class="stat-value"><?= $stats['total_purchases'] ?></div>
            </div>
            
            <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                <div class="stat-label">Total Purchase Amount</div>
                <div class="stat-value" style="color: #3b82f6;">Rs <?= number_format($stats['total_amount'], 2) ?></div>
            </div>
            
            <div class="stat-card" style="border-left: 4px solid var(--success);">
                <div class="stat-label">Amount Paid</div>
                <div class="stat-value" style="color: var(--success);">Rs <?= number_format($stats['total_paid'], 2) ?></div>
            </div>
            
            <div class="stat-card" style="border-left: 4px solid var(--danger);">
                <div class="stat-label">Balance Remaining</div>
                <div class="stat-value" style="color: var(--danger);">Rs <?= number_format($stats['total_outstanding'], 2) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search & Filter -->
    <div class="search-card">
        <h2>🔍 Search & Filter Purchases</h2>
        <form method="GET" action="view_purchases.php">
            <div class="filter-grid">
                <div class="form-group">
                    <label>Search By</label>
                    <select name="type">
                        <option value="invoice_number" <?= $search_type == 'invoice_number' ? 'selected' : '' ?>>Invoice Number</option>
                        <option value="supplier_name" <?= $search_type == 'supplier_name' ? 'selected' : '' ?>>Supplier Name</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Search Term</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>" placeholder="Enter search term...">
                </div>
                
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier">
                        <option value="0">All Suppliers</option>
                        <?php
                        $suppliers = $conn->query("SELECT id, name FROM suppliers ORDER BY name");
                        while($s = $suppliers->fetch_assoc()) {
                            $sel = ($supplier_filter == $s['id']) ? 'selected' : '';
                            echo "<option value='{$s['id']}' $sel>{$s['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="payment_status">
                        <option value="all" <?= $payment_filter == 'all' ? 'selected' : '' ?>>All Payments</option>
                        <option value="unpaid" <?= $payment_filter == 'unpaid' ? 'selected' : '' ?>>Unpaid Only</option>
                        <option value="partial" <?= $payment_filter == 'partial' ? 'selected' : '' ?>>Partially Paid</option>
                        <option value="paid" <?= $payment_filter == 'paid' ? 'selected' : '' ?>>Fully Paid</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= $date_from ?>">
                </div>
                
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= $date_to ?>">
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                <a href="view_purchases.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>

    <!-- Purchases Table -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Supplier</th>
                    <th>Date</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">Paid</th>
                    <th style="text-align: right;">Balance</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--text-dark);">#<?= htmlspecialchars($row['invoice_number']) ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['supplier_name']) ?></div>
                            <?php if (!empty($row['notes'])): ?>
                                <div class="supplier-info">📝 <?= htmlspecialchars(substr($row['notes'], 0, 50)) ?>...</div>
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
                            <a href="purchase_invoice_view.php?id=<?= $row['id'] ?>" class="btn-view">View Details</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-light);">
                        <?= !empty($search_term) || $supplier_filter > 0 || $payment_filter !== 'all' || !empty($date_from) || !empty($date_to)
                            ? '🔍 No purchase invoices found matching your filters.'
                            : '📦 No purchase invoices found. Create your first purchase!' ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>