<?php
session_start();
include("config/db.php");

$customer_name = isset($_GET['c_name']) ? htmlspecialchars($_GET['c_name']) : '';
$customer_phone = isset($_GET['c_phone']) ? htmlspecialchars($_GET['c_phone']) : '';

if (!isset($_SESSION['invoice_items'])) {
    $_SESSION['invoice_items'] = [];
}

if (isset($_GET['invoice_id'])) {
    $invoice_id = (int) $_GET['invoice_id'];
    $items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id=$invoice_id");
    $_SESSION['invoice_items'] = [];
    while ($row = $items->fetch_assoc()) {
        $_SESSION['invoice_items'][] = $row;
    }
}

$categories = $conn->query("SELECT * FROM categories");
$total = 0;
foreach ($_SESSION['invoice_items'] as $i) {
    $total += $i['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paint POS | Premium Edition</title>
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

        .bill-panel {
            display: flex;
            flex-direction: column;
            height: fit-content;
            position: sticky;
            top: 20px;
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
        }

        .qty-input:focus {
            border-color: var(--primary);
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
            width: 100px;
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
    </style>
</head>
<body>

    <header>
        <div>Paint POS <span style="font-weight:300; opacity:0.7">System</span></div>
        <div style="font-size: 0.9rem; font-weight:400; color: var(--text-light)"><?= date('D, M d Y') ?></div>
    </header>

    <div class="nav-links">
        <button onclick="window.location='view_invoices.php'">📋 View Invoices</button>
        <button onclick="window.location='admin/dashboard.php'">⚙️ Admin Panel</button>
        <button onclick="window.location='purchase/purchase_pos.php'">⚙️ purchase pos</button>

    </div>

    <div class="container pos-layout">
        <div class="card">
            <h3>1. Select Category</h3>
            <div class="grid">
                <?php while ($c = $categories->fetch_assoc()) { ?>
                    <div class="select-card category-card" onclick="selectCategory(this,<?= $c['id'] ?>)">
                        <?= $c['name'] ?>
                    </div>
                <?php } ?>
            </div>

            <div id="brandBox" class="hidden">
                <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">
                <h3>2. Choose Brand</h3>
                <div id="brands" class="grid"></div>
            </div>

            <div id="variationBox" class="hidden">
                <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">
                <h3>3. Select Variation</h3>
                <div id="variations" class="grid"></div>
            </div>

            <!-- NEW: Color Selection Box (only for products with colors) -->
            <div id="colorBox" class="hidden">
                <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">
                <h3>4. Select Color</h3>
                <div id="colors" class="grid"></div>
            </div>

            <div id="quantityBox" class="hidden">
                <hr style="border:0; border-top:1px solid var(--border); margin: 30px 0;">
                <h3 id="quantityStepNumber">4. Quantity</h3>
                <form method="post" action="add_item.php?c_name=<?= urlencode($customer_name) ?>&c_phone=<?= urlencode($customer_phone) ?>">
                    <input type="hidden" name="brand_id" id="brand_id">
                    <input type="hidden" name="variation_id" id="variation_id">
                    <input type="hidden" name="color" id="color_value">

                    <div class="variation-form-controls">
                        <label style="font-weight:600; margin-right: 10px;">Quantity:</label>
                        <input type="number" name="quantity" id="quantity_input" value="1" min="1" required>
                        <button id="addBtn" disabled>➕ Add to Bill</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card bill-panel">
            <h3>Current Bill</h3>

            <div style="overflow-x: auto; flex-grow: 1;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['invoice_items'] as $k => $i) { ?>
                            <tr data-index="<?= $k ?>">
                                <td><small style="color:var(--text-light)"><?= $k + 1 ?></small></td>
                                <td style="font-weight: 500; font-size: 0.9rem;"><?= $i['description'] ?></td>
                                <td>
                                    <input type="number" class="qty-input" value="<?= $i['quantity'] ?>" min="1"
                                        data-index="<?= $k ?>" data-old-value="<?= $i['quantity'] ?>" style="width: 50px;">
                                </td>
                                <td class="item-total">Rs <?= number_format($i['total'], 0) ?></td>
                                <td style="text-align: right;">
                                    <a href="remove_item.php?i=<?= $k ?>&from=index&c_name=<?= urlencode($customer_name) ?>&c_phone=<?= urlencode($customer_phone) ?>"
                                        onclick="return confirm('Remove this item from the bill?')"
                                        class="remove-link">×</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php if (count($_SESSION['invoice_items']) > 0): ?>
                <div class="total-box">
                    <div style="font-size: 0.8rem; font-weight: 400; opacity: 0.8; margin-bottom: 5px;">GRAND TOTAL</div>
                    Rs <?= number_format($total, 2) ?>
                </div>
                <button class="save-btn"
                    onclick="window.location='invoice_view.php?c_name=<?= urlencode($customer_name) ?>&c_phone=<?= urlencode($customer_phone) ?>'">Complete
                    & Generate Bill</button>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 0;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">🛒</div>
                    <p style="color: var(--text-light);">Your bill is empty.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let currentSelectedStock = 0;
        let hasColors = false; // Track if current product line has colors

        function clear(cls) {
            document.querySelectorAll('.' + cls).forEach(e => e.classList.remove('active'))
        }

        function selectCategory(el, id) {
            clear('category-card');
            el.classList.add('active');

            fetch('get_brands.php?category_id=' + id)
                .then(r => r.json())
                .then(d => {
                    let brandsBox = document.getElementById('brands');
                    brandsBox.innerHTML = '';
                    d.forEach(b => {
                        brandsBox.innerHTML +=
                            `<div class="select-card brand-card" onclick="selectBrand(this,${b.id})">${b.name}</div>`;
                    });

                    document.getElementById('brandBox').classList.remove('hidden');
                    document.getElementById('variationBox').classList.add('hidden');
                    document.getElementById('colorBox').classList.add('hidden');
                    document.getElementById('quantityBox').classList.add('hidden');
                    document.getElementById('variations').innerHTML = '';
                    document.getElementById('colors').innerHTML = '';
                    document.getElementById('brand_id').value = '';
                    document.getElementById('variation_id').value = '';
                    document.getElementById('color_value').value = '';
                    document.getElementById('addBtn').disabled = true;
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

                    // Check if products have colors
                    hasColors = d.length > 0 && d[0].has_colors;

                    d.forEach(v => {
                        if (hasColors) {
                            // For products with colors, don't show stock/price yet
                            box.innerHTML += `
                                <div class="select-card size-card" onclick="selectVar(this, ${v.id})"> 
                                    <div style="font-weight: 700;">${v.value}</div>
                                    <small>Select to see colors</small>
                                </div>`;
                        } else {
                            // For regular products, show stock and price
                            let disabled = v.stock <= 0 ? 'opacity:0.4;pointer-events:none;' : '';
                            let stockTxt = v.stock > 0 ? `Stock: ${v.stock}` : `<span style="color:red">Out of stock</span>`;
                            let price = v.price ? `Rs ${parseFloat(v.price).toLocaleString('en-PK', { minimumFractionDigits: 2 })}` : 'N/A';

                            box.innerHTML += `
                                <div class="select-card size-card"
                                     style="${disabled}"
                                     onclick="selectVar(this, ${v.id}, ${v.stock})"> 
                                    <div style="font-weight: 700; margin-bottom: 5px;">${v.value}</div>
                                    <small style="display: block; color: var(--primary); font-weight: 600; margin-bottom: 3px;">${price}</small>
                                    <small style="display: block;">${stockTxt}</small>
                                </div>`;
                        }
                    });

                    document.getElementById('variationBox').classList.remove('hidden');
                    document.getElementById('colorBox').classList.add('hidden');
                    document.getElementById('quantityBox').classList.add('hidden');
                });
        }

        function selectVar(el, variationId, stock = 0) {
            clear('size-card');
            el.classList.add('active');
            document.getElementById('variation_id').value = variationId;

            if (hasColors) {
                // Load colors for this variation
                fetch('get_colors.php?category_id=' + window.selectedCategory + '&brand_id=' + 
                      document.getElementById('brand_id').value + '&variation_id=' + variationId)
                    .then(r => r.json())
                    .then(d => {
                        let colorBox = document.getElementById('colors');
                        colorBox.innerHTML = '';

                        d.forEach(c => {
                            let disabled = c.stock <= 0 ? 'opacity:0.4;pointer-events:none;' : '';
                            let stockTxt = c.stock > 0 ? `Stock: ${c.stock}` : `<span style="color:red">Out of stock</span>`;
                            let price = c.price ? `Rs ${parseFloat(c.price).toLocaleString('en-PK', { minimumFractionDigits: 2 })}` : 'N/A';

                            colorBox.innerHTML += `
                                <div class="select-card color-card"
                                     style="${disabled}"
                                     onclick="selectColor(this, '${c.color}', ${c.stock})">
                                    <div style="font-weight: 700; margin-bottom: 5px;">${c.color}</div>
                                    <small style="display: block; color: var(--primary); font-weight: 600; margin-bottom: 3px;">${price}</small>
                                    <small style="display: block;">${stockTxt}</small>
                                </div>`;
                        });

                        document.getElementById('colorBox').classList.remove('hidden');
                        document.getElementById('quantityBox').classList.add('hidden');
                        document.getElementById('quantityStepNumber').textContent = '5. Quantity';
                    });
            } else {
                // No colors, go directly to quantity
                currentSelectedStock = stock;
                document.getElementById('color_value').value = '';
                document.getElementById('quantityBox').classList.remove('hidden');
                document.getElementById('quantityStepNumber').textContent = '4. Quantity';
                document.getElementById('addBtn').disabled = false;

                let qtyInput = document.getElementById('quantity_input');
                if (parseInt(qtyInput.value) > stock) {
                    qtyInput.value = stock;
                }
            }
        }

        function selectColor(el, color, stock) {
            clear('color-card');
            el.classList.add('active');
            document.getElementById('color_value').value = color;
            currentSelectedStock = stock;

            let qtyInput = document.getElementById('quantity_input');
            if (parseInt(qtyInput.value) > stock) {
                qtyInput.value = stock;
            }

            document.getElementById('quantityBox').classList.remove('hidden');
            document.getElementById('addBtn').disabled = false;
        }

        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('qty-input')) {
                updateQuantity(e.target);
            }
        });

        function updateQuantity(input) {
            const index = input.dataset.index;
            const newQty = parseInt(input.value);
            const oldQty = parseInt(input.dataset.oldValue);

            if (newQty <= 0) {
                input.value = oldQty;
                return;
            }

            fetch('update_quantity.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ index: index, quantity: newQty, oldQty: oldQty })
            })
                .then(r => r.json())
                .then(d => {
                    if (d.error) {
                        alert(d.error);
                        input.value = d.oldQty;
                    } else {
                        input.dataset.oldValue = newQty;
                        const row = input.closest('tr');
                        row.querySelector('.item-total').textContent = 'Rs ' + d.newTotal;
                        document.querySelector('.total-box').innerHTML = `<div style="font-size: 0.8rem; font-weight: 400; opacity: 0.8; margin-bottom: 5px;">GRAND TOTAL</div>Rs ` + d.newGrandTotal;
                    }
                })
                .catch(err => {
                    console.error('Error updating quantity:', err);
                    alert('Error updating quantity. Please try again.');
                });
        }

        document.getElementById('quantity_input').addEventListener('input', function () {
            let enteredQty = parseInt(this.value);

            if (enteredQty > currentSelectedStock) {
                alert("Only " + currentSelectedStock + " items available in stock.");
                this.value = currentSelectedStock;
            }

            if (enteredQty < 1 || isNaN(enteredQty)) {
                this.value = 1;
            }
        });
    </script>
</body>
</html>