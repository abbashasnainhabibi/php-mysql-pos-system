<?php
session_start();
include("../config/db.php");

// Preserve supplier info from URL
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$supplier_name = '';

if ($supplier_id > 0) {
    $supplier = $conn->query("SELECT name FROM suppliers WHERE id=$supplier_id")->fetch_assoc();
    $supplier_name = $supplier['name'] ?? '';
}

if (!isset($_SESSION['purchase_items'])) {
    $_SESSION['purchase_items'] = [];
}

$categories = $conn->query("SELECT * FROM categories");
$total = 0;
foreach ($_SESSION['purchase_items'] as $i) {
    $total += $i['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Purchase</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --accent: #10b981;
            --danger: #ef4444;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        * {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }

        body {
            background-color: var(--bg);
            color: var(--text-dark);
            margin: 0;
            padding-bottom: 40px;
        }

        header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 1.5rem 2rem;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
            margin: 20px auto;
            max-width: 1400px;
            padding: 0 20px;
        }

        .nav-links button {
            border: 1px solid var(--border);
            background: #fff;
            cursor: pointer;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            color: var(--text-dark);
            box-shadow: var(--shadow);
        }

        .nav-links button:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .container.pos-layout {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        h3 {
            font-size: 1.1rem;
            margin-top: 0;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 30px;
        }

        .select-card {
            padding: 15px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fff;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80px;
        }

        .select-card:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .select-card.active {
            border-color: var(--primary);
            background: #eef2ff;
            color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary);
        }

        .select-card small {
            font-weight: 400;
            color: var(--text-light);
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid var(--bg);
            color: var(--text-light);
            font-size: 0.85rem;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--bg);
        }

        .qty-input {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 5px 8px;
            font-weight: 600;
            text-align: center;
            outline: none;
            width: 60px;
            transition: all 0.2s ease;
        }

        .qty-input:hover {
            border-color: var(--primary);
        }

        .qty-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .total-box {
            background: var(--text-dark);
            color: #fff;
            padding: 20px;
            border-radius: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: right;
            margin-top: auto;
        }

        .save-btn {
            width: 100%;
            padding: 18px;
            border-radius: 12px;
            border: none;
            background: var(--accent);
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 15px;
        }

        .save-btn:hover {
            background: #059669;
        }

        #addBtn {
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: white;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
        }

        #addBtn:disabled {
            background: var(--border);
            cursor: not-allowed;
        }

        .hidden {
            display: none;
        }

        .variation-form-controls {
            margin-top: 20px;
            padding: 20px;
            background: var(--bg);
            border-radius: 12px;
        }

        .variation-form-controls input {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
            width: 120px;
            margin-right: 10px;
        }

        .item-total {
            font-weight: 700;
            color: var(--primary);
        }

        .remove-link {
            text-decoration: none;
            color: var(--danger);
            font-size: 1.2rem;
        }

        .supplier-selector {
            background: white;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .supplier-selector-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: block;
        }

        .supplier-selector select {
            width: 100%;
            padding: 14px 18px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 1rem;
            font-weight: 500;
            background: white;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .supplier-selector select:hover {
            border-color: var(--primary);
        }
        
        .supplier-selector select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .supplier-selected-badge {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .supplier-selected-badge strong {
            color: var(--text-dark);
            font-size: 1rem;
        }
        
        .change-supplier-btn {
            padding: 6px 16px;
            background: white;
            color: var(--primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .change-supplier-btn:hover {
            border-color: var(--primary);
            background: #eef2ff;
        }
    </style>
</head>
<body>

    <header>
        <div> Purchase Invoice <span style="font-weight:300; opacity:0.7">Stock Intake</span></div>
        <div style="font-size: 0.9rem; font-weight:400;"><?= date('D, M d Y') ?></div>
    </header>

    <div class="nav-links">
        <button onclick="window.location='view_purchases.php'">📋 View Purchases</button>
        <button onclick="window.location='../admin/suppliers.php'">👥 Manage Suppliers</button>
        <button onclick="window.location='../index.php'">← Back to Sales POS</button>
    </div>

    <div class="container pos-layout">
        <div class="card">
            <?php if ($supplier_id == 0): ?>
            <div class="supplier-selector">
                <label class="supplier-selector-label">Select Supplier</label>
                <select onchange="window.location='purchase_pos.php?supplier_id=' + this.value" autofocus>
                    <option value="0">Choose a supplier...</option>
                    <?php
                    $suppliers = $conn->query("SELECT * FROM suppliers ORDER BY name");
                    while($s = $suppliers->fetch_assoc()) {
                        echo "<option value='{$s['id']}'>{$s['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="empty-state">
                Please select a supplier to continue
            </div>
            <?php else: ?>
            
            <div class="supplier-selected-badge">
                <strong>Supplier: <?= htmlspecialchars($supplier_name) ?></strong>
                <a href="purchase_pos.php" class="change-supplier-btn">Change</a>
            </div>

            <h3>1. Select Category</h3>
            <div class="grid">
                <?php
                // Only show categories that this supplier supplies
                $categories = $conn->query("
                    SELECT DISTINCT c.id, c.name
                    FROM categories c
                    JOIN supplier_category_brands scb ON c.id = scb.category_id
                    WHERE scb.supplier_id = $supplier_id
                    ORDER BY c.name
                ");
                
                if($categories->num_rows == 0) {
                    echo "<p style='grid-column: 1/-1; text-align:center; color: var(--text-light); padding: 20px;'>⚠️ No products linked to this supplier yet.<br><a href='admin/suppliers.php?manage=$supplier_id' style='color: var(--primary); font-weight: 600;'>Click here to manage supplier products</a></p>";
                } else {
                    while ($c = $categories->fetch_assoc()) { ?>
                        <div class="select-card category-card" onclick="selectCategory(this,<?= $c['id'] ?>)">
                            <?= $c['name'] ?>
                        </div>
                    <?php }
                }
                ?>
            </div>

            <div id="brandBox" class="hidden">
                <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">
                <h3>2. Choose Brand</h3>
                <div id="brands" class="grid"></div>
            </div>

            <div id="variationBox" class="hidden">
                <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">
                <h3>3. Variation</h3>
                <div id="variations" class="grid"></div>
            </div>

            <div id="colorBox" class="hidden">
                <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">
                <h3>4. Select Color</h3>
                <div id="colors" class="grid"></div>
            </div>

            <div id="quantityBox" class="hidden">
                <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">
                <h3 id="quantityStepNumber">4. Quantity & Price</h3>
                <form method="post" action="add_purchase_item.php?supplier_id=<?= $supplier_id ?>">
                    <input type="hidden" name="brand_id" id="brand_id">
                    <input type="hidden" name="variation_id" id="variation_id">
                    <input type="hidden" name="color" id="color_value">
                    <input type="hidden" name="product_id" id="product_id">

                    <div class="variation-form-controls">
                        <label style="font-weight:600; margin-right: 10px;">Quantity:</label>
                        <input type="number" name="quantity" id="quantity_input" value="1" min="1" required>
                        
                        <label style="font-weight:600; margin-right: 10px; margin-left: 20px;">Unit Price (Rs):</label>
                        <input type="number" name="unit_price" id="unit_price_input" step="0.01" min="0" required>
                        
                        <button id="addBtn" disabled>➕ Add to Purchase</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="card bill-panel">
            <h3>Purchase Invoice</h3>

            <div style="overflow-x: auto; flex-grow: 1;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['purchase_items'] as $k => $i) { ?>
                            <tr>
                                <td><small style="color:var(--text-light)"><?= $k + 1 ?></small></td>
                                <td style="font-weight: 500; font-size: 0.9rem;"><?= $i['description'] ?></td>
                                <td>
                                    <input type="number" 
                                           class="qty-input" 
                                           value="<?= $i['quantity'] ?>" 
                                           min="1" 
                                           data-index="<?= $k ?>"
                                           data-old-value="<?= $i['quantity'] ?>">
                                </td>
                                <td>Rs <?= number_format($i['unit_price'], 2) ?></td>
                                <td class="item-total">Rs <?= number_format($i['total'], 0) ?></td>
                                <td style="text-align: right;">
                                    <a href="remove_purchase_item.php?i=<?= $k ?>&supplier_id=<?= $supplier_id ?>"
                                        onclick="return confirm('Remove this item?')"
                                        class="remove-link">×</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php if (count($_SESSION['purchase_items']) > 0): ?>
                <div class="total-box">
                    <div style="font-size: 0.8rem; font-weight: 400; opacity: 0.8; margin-bottom: 5px;">TOTAL AMOUNT</div>
                    Rs <?= number_format($total, 2) ?>
                </div>
                <button class="save-btn"
                    onclick="window.location='purchase_invoice_view.php?supplier_id=<?= $supplier_id ?>'">Complete Purchase →</button>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 0;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">📦</div>
                    <p style="color: var(--text-light);">No items added yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let hasColors = false;
        const supplierId = <?= $supplier_id ?>;

        function clear(cls) {
            document.querySelectorAll('.' + cls).forEach(e => e.classList.remove('active'))
        }

        function selectCategory(el, id) {
            clear('category-card');
            el.classList.add('active');

            fetch('get_supplier_brands.php?category_id=' + id + '&supplier_id=' + supplierId)
                .then(r => r.json())
                .then(d => {
                    let brandsBox = document.getElementById('brands');
                    brandsBox.innerHTML = '';
                    
                    if(d.length === 0) {
                        brandsBox.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color: #999;">No brands available for this category</p>';
                    } else {
                        d.forEach(b => {
                            brandsBox.innerHTML +=
                                `<div class="select-card brand-card" onclick="selectBrand(this,${b.id})">${b.name}</div>`;
                        });
                    }

                    document.getElementById('brandBox').classList.remove('hidden');
                    document.getElementById('variationBox').classList.add('hidden');
                    document.getElementById('colorBox').classList.add('hidden');
                    document.getElementById('quantityBox').classList.add('hidden');
                });

            window.selectedCategory = id;
        }

        function selectBrand(el, id) {
            clear('brand-card');
            el.classList.add('active');
            document.getElementById('brand_id').value = id;

            fetch('get_variations.php?category_id=' + window.selectedCategory + '&brand_id=' + id)
                .then(r => r.json())
                .then(d => {
                    let box = document.getElementById('variations');
                    box.innerHTML = '';

                    hasColors = d.length > 0 && d[0].has_colors;

                    d.forEach(v => {
                        box.innerHTML += `
                            <div class="select-card size-card" onclick="selectVar(this, ${v.id})"> 
                                <div style="font-weight: 700;">${v.value}</div>
                            </div>`;
                    });

                    document.getElementById('variationBox').classList.remove('hidden');
                    document.getElementById('colorBox').classList.add('hidden');
                    document.getElementById('quantityBox').classList.add('hidden');
                });
        }

        function selectVar(el, variationId) {
            clear('size-card');
            el.classList.add('active');
            document.getElementById('variation_id').value = variationId;

            if (hasColors) {
                fetch('get_colors.php?category_id=' + window.selectedCategory + '&brand_id=' + 
                      document.getElementById('brand_id').value + '&variation_id=' + variationId)
                    .then(r => r.json())
                    .then(d => {
                        let colorBox = document.getElementById('colors');
                        colorBox.innerHTML = '';

                        d.forEach(c => {
                            colorBox.innerHTML += `
                                <div class="select-card color-card" onclick="selectColor(this, '${c.color}', ${c.id})">
                                    <div style="font-weight: 700;">${c.color}</div>
                                </div>`;
                        });

                        document.getElementById('colorBox').classList.remove('hidden');
                        document.getElementById('quantityBox').classList.add('hidden');
                        document.getElementById('quantityStepNumber').textContent = '5. Quantity & Price';
                    });
            } else {
                // Get product ID for non-color products
                fetch(`get_product_id.php?category_id=${window.selectedCategory}&brand_id=${document.getElementById('brand_id').value}&variation_id=${variationId}`)
                    .then(r => r.json())
                    .then(d => {
                        document.getElementById('product_id').value = d.product_id;
                        document.getElementById('color_value').value = '';
                        document.getElementById('quantityBox').classList.remove('hidden');
                        document.getElementById('quantityStepNumber').textContent = '4. Quantity & Price';
                        document.getElementById('addBtn').disabled = false;
                    });
            }
        }

        function selectColor(el, color, productId) {
            clear('color-card');
            el.classList.add('active');
            document.getElementById('color_value').value = color;
            document.getElementById('product_id').value = productId;
            document.getElementById('quantityBox').classList.remove('hidden');
            document.getElementById('addBtn').disabled = false;
        }

        // Handle quantity updates in the bill panel
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('qty-input')) {
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
                    document.querySelector('.total-box').innerHTML = 
                        `<div style="font-size: 0.8rem; font-weight: 400; opacity: 0.8; margin-bottom: 5px;">TOTAL AMOUNT</div>Rs ${d.newGrandTotal}`;
                }
            })
            .catch(err => {
                console.error('Error updating quantity:', err);
                alert('Error updating quantity. Please try again.');
                input.value = oldQty;
            });
        }
    </script>
</body>
</html>