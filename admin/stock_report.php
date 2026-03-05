<?php
include("../config/db.php");
include("layout/header.php");
include("layout/sidebar.php");

/* ================= PHP LOGIC ================= */

// Get filter parameters
$categoryFilter = isset($_GET['category_filter']) ? (int)$_GET['category_filter'] : 0;
$brandFilter = isset($_GET['brand_filter']) ? (int)$_GET['brand_filter'] : 0;
$variationFilter = isset($_GET['variation_filter']) ? (int)$_GET['variation_filter'] : 0;
$stockFilter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query
$sql = "SELECT 
            p.id,
            p.stock,
            p.price,
            p.color,
            c.name AS category,
            b.name AS brand,
            v.value AS variation,
            CONCAT(c.name, ' - ', b.name, ' - ', v.value, 
                   CASE WHEN p.color IS NOT NULL THEN CONCAT(' (', p.color, ')') ELSE '' END) AS product_display
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN variations v ON p.variation_id = v.id
        WHERE 1=1";

// Apply filters
if ($categoryFilter > 0) {
    $sql .= " AND p.category_id = $categoryFilter";
}

if ($brandFilter > 0) {
    $sql .= " AND p.brand_id = $brandFilter";
}

if ($variationFilter > 0) {
    $sql .= " AND p.variation_id = $variationFilter";
}

if ($stockFilter === 'low') {
    $sql .= " AND p.stock < 10 AND p.stock > 0";
} elseif ($stockFilter === 'out') {
    $sql .= " AND p.stock = 0";
} elseif ($stockFilter === 'available') {
    $sql .= " AND p.stock >= 10";
}

if (!empty($searchQuery)) {
    $searchEscaped = $conn->real_escape_string($searchQuery);
    $sql .= " AND (p.color LIKE '%$searchEscaped%' 
                   OR c.name LIKE '%$searchEscaped%' 
                   OR b.name LIKE '%$searchEscaped%' 
                   OR v.value LIKE '%$searchEscaped%')";
}

$sql .= " ORDER BY p.stock ASC, c.name, b.name";

$result = $conn->query($sql);

// Calculate statistics
$stats_sql = "SELECT 
                COUNT(*) as total_products,
                SUM(stock) as total_stock,
                SUM(CASE WHEN stock < 10 AND stock > 0 THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock_count,
                SUM(stock * price) as total_value
              FROM products";

// Apply same filters to stats
$stats_where = [];
if ($categoryFilter > 0) $stats_where[] = "category_id = $categoryFilter";
if ($brandFilter > 0) $stats_where[] = "brand_id = $brandFilter";
if ($variationFilter > 0) $stats_where[] = "variation_id = $variationFilter";

if (!empty($stats_where)) {
    $stats_sql .= " WHERE " . implode(" AND ", $stats_where);
}

$stats = $conn->query($stats_sql)->fetch_assoc();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="content">
    <div class="header">
        <h2> Stock Report</h2>
    </div>

    <!-- Statistics Cards -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
        
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-icon" style="background: #dbeafe; color: #3b82f6;">
                <i class="fa fa-boxes-stacked"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total Products</span>
                <span class="stat-value"><?= number_format($stats['total_products']) ?></span>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-icon" style="background: #d1fae5; color: #10b981;">
                <i class="fa fa-warehouse"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Total Stock</span>
                <span class="stat-value"><?= number_format($stats['total_stock']) ?></span>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-icon" style="background: #fef3c7; color: #f59e0b;">
                <i class="fa fa-triangle-exclamation"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Low Stock Items</span>
                <span class="stat-value"><?= number_format($stats['low_stock_count']) ?></span>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #ef4444;">
            <div class="stat-icon" style="background: #fee2e2; color: #ef4444;">
                <i class="fa fa-circle-xmark"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Out of Stock</span>
                <span class="stat-value"><?= number_format($stats['out_of_stock_count']) ?></span>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="stat-icon" style="background: #f5f3ff; color: #8b5cf6;">
                <i class="fa fa-money-bill-trend-up"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Stock Value</span>
                <span class="stat-value">Rs <?= number_format($stats['total_value']) ?></span>
            </div>
        </div>
    </div>

    <div class="card">
        <!-- Filter Section -->
        <div class="section-wrapper">
            <h3 class="section-title">🔍 Filter & Search</h3>
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <select name="category_filter" id="category_filter" onchange="loadBrands()">
                        <option value="">All Categories</option>
                        <?php
                        $cats = $conn->query("SELECT * FROM categories ORDER BY name");
                        while($c = $cats->fetch_assoc()){
                            $selected = ($categoryFilter == $c['id']) ? 'selected' : '';
                            echo "<option value='{$c['id']}' $selected>{$c['name']}</option>";
                        }
                        ?>
                    </select>

                    <select name="brand_filter" id="brand_filter" onchange="loadVariations()">
                        <option value="">All Brands</option>
                        <?php
                        if ($categoryFilter > 0) {
                            $brands = $conn->query("SELECT * FROM brands WHERE category_id = $categoryFilter ORDER BY name");
                            while($b = $brands->fetch_assoc()){
                                $selected = ($brandFilter == $b['id']) ? 'selected' : '';
                                echo "<option value='{$b['id']}' $selected>{$b['name']}</option>";
                            }
                        }
                        ?>
                    </select>

                    <select name="variation_filter" id="variation_filter">
                        <option value="">All Variations</option>
                        <?php
                        if ($categoryFilter > 0) {
                            $variations = $conn->query("SELECT * FROM variations WHERE category_id = $categoryFilter ORDER BY value");
                            while($v = $variations->fetch_assoc()){
                                $selected = ($variationFilter == $v['id']) ? 'selected' : '';
                                echo "<option value='{$v['id']}' $selected>{$v['value']}</option>";
                            }
                        }
                        ?>
                    </select>

                    <select name="stock_filter">
                        <option value="">All Stock Levels</option>
                        <option value="available" <?= $stockFilter === 'available' ? 'selected' : '' ?>>Available (≥10)</option>
                        <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low Stock (<10)</option>
                        <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>

                    <input type="text" name="search" placeholder="Search category, brand, variation or color..." 
                           value="<?= htmlspecialchars($searchQuery) ?>">

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search"></i> Search
                    </button>
                    <a href="stock_report.php" class="btn btn-secondary">
                        <i class="fa fa-rotate-right"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <hr class="section-divider">

        <!-- Export Buttons -->
        <div style="margin-bottom: 15px; display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-success btn-sm">
                <i class="fa fa-print"></i> Print Report
            </button>
            <button onclick="exportToCSV()" class="btn btn-success btn-sm">
                <i class="fa fa-file-csv"></i> Export CSV
            </button>
        </div>

        <!-- Stock Table -->
        <div class="table-responsive">
            <table id="stockTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Variation</th>
                        <th>Color</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Total Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if($result->num_rows > 0){
                        while($row = $result->fetch_assoc()){
                            $stock = $row['stock'];
                            $stockClass = '';
                            $statusBadge = '';
                            
                            if ($stock == 0) {
                                $stockClass = 'stock-out';
                                $statusBadge = '<span class="badge badge-danger"><i class="fa fa-circle-xmark"></i> Out of Stock</span>';
                            } elseif ($stock < 10) {
                                $stockClass = 'stock-low';
                                $statusBadge = '<span class="badge badge-warning"><i class="fa fa-triangle-exclamation"></i> Low Stock</span>';
                            } else {
                                $stockClass = 'stock-good';
                                $statusBadge = '<span class="badge badge-success"><i class="fa fa-circle-check"></i> Available</span>';
                            }
                            
                            $totalValue = $stock * $row['price'];
                    ?>
                    <tr class="<?= $stockClass ?>">
                        <td><?= $row['id'] ?></td>
                        <td><strong><?= htmlspecialchars($row['product_display']) ?></strong></td>
                        <td><span class="cat-badge"><?= htmlspecialchars($row['category']) ?></span></td>
                        <td><?= htmlspecialchars($row['brand']) ?></td>
                        <td><?= htmlspecialchars($row['variation'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['color'] ?? '-') ?></td>
                        <td><strong class="stock-number"><?= number_format($stock) ?></strong></td>
                        <td>Rs <?= number_format($row['price']) ?></td>
                        <td>Rs <?= number_format($totalValue) ?></td>
                        <td><?= $statusBadge ?></td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='10' style='text-align:center; padding: 30px;'>No products found matching your filters.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Load brands based on selected category
function loadBrands() {
    const categoryId = document.getElementById('category_filter').value;
    const brandSelect = document.getElementById('brand_filter');
    const variationSelect = document.getElementById('variation_filter');
    
    brandSelect.innerHTML = '<option value="">All Brands</option>';
    variationSelect.innerHTML = '<option value="">All Variations</option>';
    
    if (categoryId) {
        fetch('get_brands.php?category_id=' + categoryId)
            .then(res => res.json())
            .then(data => {
                data.forEach(brand => {
                    brandSelect.innerHTML += `<option value="${brand.id}">${brand.name}</option>`;
                });
            });
        
        loadVariations();
    }
}

// Load variations based on selected category
function loadVariations() {
    const categoryId = document.getElementById('category_filter').value;
    const variationSelect = document.getElementById('variation_filter');
    
    variationSelect.innerHTML = '<option value="">All Variations</option>';
    
    if (categoryId) {
        fetch('get_all_variations.php?category_id=' + categoryId + '&brand_id=0')
            .then(res => res.json())
            .then(data => {
                // Get unique variations
                const uniqueVars = new Map();
                data.forEach(v => {
                    if (!uniqueVars.has(v.id)) {
                        uniqueVars.set(v.id, v);
                    }
                });
                
                uniqueVars.forEach(variation => {
                    variationSelect.innerHTML += `<option value="${variation.id}">${variation.value}</option>`;
                });
            });
    }
}

// Export to CSV
function exportToCSV() {
    const table = document.getElementById('stockTable');
    let csv = [];
    
    // Headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Data rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            let text = td.textContent.trim().replace(/,/g, '');
            row.push(text);
        });
        if (row.length > 0) {
            csv.push(row.join(','));
        }
    });
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'stock_report_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}
</script>

<style>
/* Statistics Cards */
.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-top: 2px;
}

/* Filter Form */
.filter-form {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    align-items: center;
}

.filter-row select,
.filter-row input[type="text"] {
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    font-size: 14px;
}

/* Table Styling */
.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

table th {
    background: #f1f5f9;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #334155;
    border-bottom: 2px solid #e2e8f0;
    font-size: 13px;
    text-transform: uppercase;
}

table td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

/* Stock Status Rows */
.stock-out {
    background: #fef2f2;
}

.stock-low {
    background: #fffbeb;
}

.stock-good {
    background: white;
}

.stock-number {
    font-size: 16px;
}

/* Badges */
.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background: #fee2e2;
    color: #991b1b;
}

.cat-badge {
    background: #f0f2f5;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

/* Utilities */
.section-wrapper {
    padding: 10px 0;
}

.section-title {
    font-size: 16px;
    margin-bottom: 15px;
    color: #334155;
    font-weight: 600;
}

.section-divider {
    border: 0;
    border-top: 1px solid #e2e8f0;
    margin: 20px 0;
}

/* Print Styles */
@media print {
    .filter-form,
    .btn,
    .header,
    .stat-card,
    .sidebar,
    .section-divider {
        display: none !important;
    }
    
    table {
        font-size: 11px;
    }
    
    .stock-out,
    .stock-low {
        background: white !important;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        flex-direction: row;
    }
}
</style>

<?php include("layout/footer.php"); ?>