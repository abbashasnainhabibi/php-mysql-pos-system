<?php
include("../config/db.php");
include("layout/header.php");
include("layout/sidebar.php");

date_default_timezone_set('Asia/Karachi'); 
$conn->query("SET time_zone = '+05:00'");

$filter_date = $_GET['filter_date'] ?? '';
$cat_id      = $_GET['cat_id'] ?? '';
$brand_id    = $_GET['brand_id'] ?? '';
$var_id      = $_GET['var_id'] ?? '';
$color_filter = $_GET['color_filter'] ?? '';

$conditions = ["i.saved = 1"];
if (!empty($filter_date)) $conditions[] = "DATE(i.invoice_date) = '$filter_date'";
if (!empty($cat_id))      $conditions[] = "p.category_id = '$cat_id'";
if (!empty($brand_id))    $conditions[] = "p.brand_id = '$brand_id'";
if (!empty($var_id))      $conditions[] = "p.variation_id = '$var_id'";
if (!empty($color_filter)) {
    $color_escaped = $conn->real_escape_string($color_filter);
    $conditions[] = "p.color LIKE '%$color_escaped%'";
}

$where_clause = implode(" AND ", $conditions);

// Query for revenue and items (from invoice_items)
$summary_query = "
    SELECT 
        SUM(ii.total) as display_total, 
        SUM(ii.quantity) as total_qty, 
        COUNT(DISTINCT i.id) as inv_count
    FROM invoice_items ii
    JOIN invoices i ON ii.invoice_id = i.id
    JOIN products p ON ii.product_id = p.id
    WHERE $where_clause";
$summary = $conn->query($summary_query)->fetch_assoc();

// Only get payment data when NOT filtering by category, brand, variation, or color
$show_payment_data = empty($cat_id) && empty($brand_id) && empty($var_id) && empty($color_filter);

if ($show_payment_data) {
    $payment_conditions = ["saved = 1"];
    if (!empty($filter_date)) {
        $payment_conditions[] = "DATE(invoice_date) = '$filter_date'";
    }
    $payment_where = implode(" AND ", $payment_conditions);
    
    $payment_query = "
        SELECT 
            SUM(amount_paid) as total_received,
            SUM(balance) as total_remaining
        FROM invoices 
        WHERE $payment_where";
    $payment_data = $conn->query($payment_query)->fetch_assoc();
    
    $summary['total_received'] = $payment_data['total_received'] ?? 0;
    $summary['total_remaining'] = $payment_data['total_remaining'] ?? 0;
} else {
    $summary['total_received'] = 0;
    $summary['total_remaining'] = 0;
}

// Updated query to include color in product sales
$product_sales = $conn->query("
    SELECT p.id, c.name as cat_name, b.name as brand_name, v.value as var_name, p.color,
           SUM(ii.quantity) as qty, SUM(ii.total) as rev
    FROM invoice_items ii
    JOIN invoices i ON ii.invoice_id = i.id
    JOIN products p ON ii.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN brands b ON p.brand_id = b.id
    JOIN variations v ON p.variation_id = v.id
    WHERE $where_clause
    GROUP BY p.id ORDER BY rev DESC");

$cat_chart_data = $conn->query("
    SELECT c.name, SUM(ii.total) as total 
    FROM invoice_items ii 
    JOIN products p ON ii.product_id = p.id 
    JOIN categories c ON p.category_id = c.id 
    JOIN invoices i ON ii.invoice_id = i.id
    WHERE $where_clause GROUP BY c.id ORDER BY total DESC LIMIT 5");
$c_labels = []; $c_values = [];
while($r = $cat_chart_data->fetch_assoc()){ $c_labels[] = $r['name']; $c_values[] = $r['total']; }

$trend_query = $conn->query("
    SELECT DATE(invoice_date) as d, SUM(total) as t 
    FROM invoices WHERE saved = 1 GROUP BY DATE(invoice_date) ORDER BY d DESC LIMIT 7");
$t_labels = []; $t_values = [];
while($r = $trend_query->fetch_assoc()){
    $t_labels[] = date('d M', strtotime($r['d']));
    $t_values[] = $r['t'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Responsive Grid Utilities */
    .report-grid {
        display: grid;
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stats-grid { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    
    /* Chart area: 2 columns on desktop, 1 on mobile */
    .charts-grid { grid-template-columns: 2fr 1fr; }

    /* Filter Form Adjustments */
    .filter-container {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    .filter-item { flex: 1; min-width: 180px; }
    
    /* Table scroll for mobile */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Mobile specific overrides */
    @media (max-width: 992px) {
        .charts-grid { grid-template-columns: 1fr; }
    }
    
    @media (max-width: 600px) {
        .filter-item { min-width: 100%; }
        .filter-actions { width: 100%; display: flex; gap: 10px; }
        .filter-actions button, .filter-actions a { flex: 1; text-align: center; }
        .content { padding: 15px; }
    }
    
    .color-badge {
        background: #fef3c7;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        color: #92400e;
        font-weight: 600;
        margin-left: 5px;
    }
</style>

<div class="content">
    <div class="header" style="margin-bottom: 20px;">
        <h2><i class="fa fa-chart-line"></i> Sales Reports</h2>
    </div>

    <div class="card" style="margin-bottom: 25px; border-top: 4px solid #4f46e5;">
        <form method="GET" id="filterForm" class="filter-container">
            <div class="filter-item">
                <label style="font-size: 12px; font-weight: bold; color: #555; display:block; margin-bottom:5px;">Filter Date</label>
                <input type="date" name="filter_date" value="<?= $filter_date ?>" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
            </div>

            <div class="filter-item">
                <label style="font-size: 12px; font-weight: bold; color: #555; display:block; margin-bottom:5px;">Category</label>
                <select name="cat_id" id="cat_id" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
                    <option value="">All Categories</option>
                    <?php 
                    $cats = $conn->query("SELECT * FROM categories ORDER BY name");
                    while($c = $cats->fetch_assoc()) {
                        $sel = ($cat_id == $c['id']) ? 'selected' : '';
                        echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="filter-item">
                <label style="font-size: 12px; font-weight: bold; color: #555; display:block; margin-bottom:5px;">Brand</label>
                <select name="brand_id" id="brand_id" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
                    <option value="">All Brands</option>
                </select>
            </div>

            <div class="filter-item">
                <label style="font-size: 12px; font-weight: bold; color: #555; display:block; margin-bottom:5px;">Variation</label>
                <select name="var_id" id="var_id" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
                    <option value="">All Variations</option>
                </select>
            </div>

            <div class="filter-item" id="color_filter_container" style="<?php
                $showColorFilter = false;
                if(!empty($cat_id)){
                    $catResult = $conn->query("SELECT name FROM categories WHERE id = $cat_id");
                    if($catResult && $catRow = $catResult->fetch_assoc()){
                        $catName = strtolower($catRow['name']);
                        $showColorFilter = (strpos($catName, 'oil paint') !== false || strpos($catName, 'paint') !== false);
                    }
                }
                echo $showColorFilter ? '' : 'display: none;';
            ?>">
                <label style="font-size: 12px; font-weight: bold; color: #555; display:block; margin-bottom:5px;">Color</label>
                <input type="text" name="color_filter" id="color_filter_input" placeholder="Color..." value="<?= $color_filter ?>" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
            </div>

            <div class="filter-actions">
                <button type="submit" style="background: #4f46e5; color: white; border: none; padding: 11px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <i class="fa fa-filter"></i> Apply
                </button>
                <a href="reports.php" style="background: #f3f4f6; color: #374151; padding: 11px 15px; border-radius: 6px; text-decoration: none; font-size: 13px; display:inline-block;">Clear</a>
            </div>
        </form>
    </div>

    <div class="report-grid stats-grid">
        <div class="card" style="border-left: 5px solid #10b981;">
            <span style="color: #666; font-size: 12px; font-weight: 600;">TOTAL REVENUE</span><br>
            <b style="font-size: 1.6rem; color: #10b981;">Rs <?= number_format($summary['display_total'] ?? 0, 2) ?></b>
        </div>
        
        <?php if ($show_payment_data): ?>
        <div class="card" style="border-left: 5px solid #059669;">
            <span style="color: #666; font-size: 12px; font-weight: 600;">TOTAL RECEIVED</span><br>
            <b style="font-size: 1.6rem; color: #059669;">Rs <?= number_format($summary['total_received'] ?? 0, 2) ?></b>
        </div>
        
        <div class="card" style="border-left: 5px solid #dc2626;">
            <span style="color: #666; font-size: 12px; font-weight: 600;">TOTAL REMAINING</span><br>
            <b style="font-size: 1.6rem; color: #dc2626;">Rs <?= number_format($summary['total_remaining'] ?? 0, 2) ?></b>
        </div>
        <?php endif; ?>
        
        <div class="card" style="border-left: 5px solid #3b82f6;">
            <span style="color: #666; font-size: 12px; font-weight: 600;">ITEMS SOLD</span><br>
            <b style="font-size: 1.6rem; color: #3b82f6;"><?= number_format($summary['total_qty'] ?? 0) ?></b>
        </div>
        
        <div class="card" style="border-left: 5px solid #f59e0b;">
            <span style="color: #666; font-size: 12px; font-weight: 600;">INVOICES</span><br>
            <b style="font-size: 1.6rem; color: #f59e0b;"><?= $summary['inv_count'] ?></b>
        </div>
    </div>

    <div class="report-grid charts-grid">
        <div class="card">
            <h4 style="margin-bottom: 15px;"><i class="fa fa-chart-line"></i> Revenue Trend</h4>
            <div style="height: 250px;"><canvas id="trendChart"></canvas></div>
        </div>
        <div class="card">
            <h4 style="margin-bottom: 15px;"><i class="fa fa-chart-pie"></i> Top Categories</h4>
            <div style="height: 250px;"><canvas id="catChart"></canvas></div>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 15px; border-bottom: 1px solid #eee;">
            <h4 style="margin: 0;">📦 Product Performance</h4>
        </div>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 12px;">PRODUCT</th>
                        <th style="padding: 12px;">SOLD</th>
                        <th style="padding: 12px; text-align: right;">REVENUE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($product_sales->num_rows > 0): ?>
                        <?php while($row = $product_sales->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px;">
                                <strong><?= $row['brand_name'] ?></strong>
                                <?php if ($row['color']): ?>
                                    <span class="color-badge"><?= htmlspecialchars($row['color']) ?></span>
                                <?php endif; ?>
                                <br>
                                <small style="color:#666"><?= $row['cat_name'] ?> (<?= $row['var_name'] ?>)</small>
                            </td>
                            <td style="padding: 12px;"><?= number_format($row['qty']) ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: 700; color: #4f46e5;">
                                Rs <?= number_format($row['rev'], 2) ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; padding: 40px; color: #999;">No data found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ============================================================
// Dependent Dropdowns for Filters
// ============================================================
document.getElementById('cat_id').addEventListener('change', function() {
    const catId = this.value;
    const brandSelect = document.getElementById('brand_id');
    const varSelect = document.getElementById('var_id');
    const colorContainer = document.getElementById('color_filter_container');
    const colorInput = document.getElementById('color_filter_input');
    
    // Reset dependent dropdowns
    brandSelect.innerHTML = '<option value="">All Brands</option>';
    varSelect.innerHTML = '<option value="">All Variations</option>';
    
    if (!catId) {
        colorContainer.style.display = 'none';
        colorInput.value = '';
        return;
    }
    
    // Fetch brands for selected category
    fetch('get_brands.php?category_id=' + catId)
        .then(res => res.json())
        .then(data => {
            data.forEach(brand => {
                const opt = document.createElement('option');
                opt.value = brand.id;
                opt.textContent = brand.name;
                brandSelect.appendChild(opt);
            });
        })
        .catch(err => console.error('Brand fetch error:', err));
    
    // Check if this category needs color filter
    fetch('check_if_oilpaint.php?category_id=' + catId)
        .then(res => res.json())
        .then(data => {
            if (data.is_oilpaint) {
                colorContainer.style.display = 'block';
            } else {
                colorContainer.style.display = 'none';
                colorInput.value = '';
            }
        })
        .catch(err => console.error('Color check error:', err));
});

document.getElementById('brand_id').addEventListener('change', function() {
    const catId = document.getElementById('cat_id').value;
    const brandId = this.value;
    const varSelect = document.getElementById('var_id');
    
    // Reset variation dropdown
    varSelect.innerHTML = '<option value="">All Variations</option>';
    
    if (!catId || !brandId) return;
    
    // Fetch variations - use a different endpoint that shows ALL variations (not just unused ones)
    fetch(`get_all_variations.php?category_id=${catId}&brand_id=${brandId}`)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                varSelect.innerHTML = '<option value="">No variations available</option>';
                return;
            }
            
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.value;
                varSelect.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Variation fetch error:', err);
            varSelect.innerHTML = '<option value="">Error loading variations</option>';
        });
});

// ============================================================
// On page load, restore filters if URL has parameters
// ============================================================
window.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const catId = params.get('cat_id');
    const brandId = params.get('brand_id');
    const varId = params.get('var_id');
    
    if (catId) {
        // Load brands for the selected category
        fetch('get_brands.php?category_id=' + catId)
            .then(res => res.json())
            .then(data => {
                const brandSelect = document.getElementById('brand_id');
                data.forEach(brand => {
                    const opt = document.createElement('option');
                    opt.value = brand.id;
                    opt.textContent = brand.name;
                    if (brand.id == brandId) opt.selected = true;
                    brandSelect.appendChild(opt);
                });
                
                // If brand is selected, load variations
                if (brandId) {
                    return fetch(`get_all_variations.php?category_id=${catId}&brand_id=${brandId}`);
                }
            })
            .then(res => res ? res.json() : null)
            .then(data => {
                if (data) {
                    const varSelect = document.getElementById('var_id');
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.value;
                        if (item.id == varId) opt.selected = true;
                        varSelect.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Filter restore error:', err));
    }
});

// Line Chart
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_reverse($t_labels)) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode(array_reverse($t_values)) ?>,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true, tension: 0.3
        }]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

// Pie Chart
new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($c_labels) ?>,
        datasets: [{
            data: <?= json_encode($c_values) ?>,
            backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
        }]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
});
</script>

<?php include("layout/footer.php"); ?>