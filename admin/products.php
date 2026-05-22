<?php
include("../config/db.php");
include("layout/header.php");
include("layout/sidebar.php");

/* ================= PHP LOGIC ================= */

// Add new product
if(isset($_POST['add'])){
    $category_id  = (int)$_POST['category_id'];
    $brand_id     = (int)$_POST['brand_id'];
    $variation_id = (int)$_POST['variation_id'];
    $color        = isset($_POST['color']) && !empty(trim($_POST['color'])) ? $conn->real_escape_string(trim($_POST['color'])) : null;
    $price        = (float)$_POST['price'];
    $stock        = (int)$_POST['stock'];

    // Check for duplicate
    $duplicate_check = "SELECT id FROM products WHERE category_id=$category_id AND brand_id=$brand_id AND variation_id=$variation_id";
    if ($color) {
        $duplicate_check .= " AND color='$color'";
    } else {
        $duplicate_check .= " AND color IS NULL";
    }
    
    $existing = $conn->query($duplicate_check);
    if ($existing->num_rows > 0) {
        header("Location: products.php?msg=duplicate");
        exit;
    }

    if ($color) {
        $conn->query("INSERT INTO products (category_id, brand_id, variation_id, color, price, stock) VALUES ($category_id, $brand_id, $variation_id, '$color', $price, $stock)");
    } else {
        $conn->query("INSERT INTO products (category_id, brand_id, variation_id, price, stock) VALUES ($category_id, $brand_id, $variation_id, $price, $stock)");
    }
    header("Location: products.php?msg=added");
    exit;
}

// Edit product (Update)
if(isset($_POST['update'])){
    $id    = (int)$_POST['id'];
    $color = isset($_POST['color']) && !empty(trim($_POST['color'])) ? $conn->real_escape_string(trim($_POST['color'])) : null;
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    
    if ($color) {
        $conn->query("UPDATE products SET color='$color', price=$price, stock=$stock WHERE id=$id");
    } else {
        $conn->query("UPDATE products SET color=NULL, price=$price, stock=$stock WHERE id=$id");
    }
    header("Location: products.php?msg=updated");
    exit;
}

// Delete product
if(isset($_GET['del'])){
    $id = (int)$_GET['del'];
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: products.php?msg=deleted");
    exit;
}
?>

<div class="content">
    <div class="header">
        <h2>Inventory Management</h2>
    </div>

    <div id="toast" class="toast"></div>

    <div class="card">
        <div class="section-wrapper">
            <h3 class="section-title">📦 Add New Product</h3>
            <form method="POST" class="add-form-grid">
                <select name="category_id" id="category" required>
                    <option value="">Select Category</option>
                    <?php
                    $c=$conn->query("SELECT * FROM categories ORDER BY name");
                    while($r=$c->fetch_assoc()){ echo "<option value='{$r['id']}'>{$r['name']}</option>"; }
                    ?>
                </select>
                <select name="brand_id" id="brand" required>
                    <option value="">Select Brand</option>
                </select>
                <select name="variation_id" id="variation" required>
                    <option value="">Select Variation</option>
                </select>
                
                <!-- Color input (hidden by default, shown only for Oil Paint) -->
                <input type="text" name="color" id="color_input" placeholder="Color (e.g., Red, Blue)" style="display: none;">
                
                <input type="number" step="0.01" name="price" placeholder="Price" required>
                <input type="number" name="stock" placeholder="Stock" required>
                <button class="btn btn-primary" name="add">Add Product</button>
            </form>
        </div>

        <hr class="section-divider">

        <div class="section-wrapper">
            <h3 class="section-title">🔍 Search & Filter Inventory</h3>
            <form method="GET" class="search-form">
                <select name="cat_f" id="filter_category">
                    <option value="">All Categories</option>
                    <?php
                    $cats = $conn->query("SELECT * FROM categories ORDER BY name");
                    while($c = $cats->fetch_assoc()){
                        $sel = (isset($_GET['cat_f']) && $_GET['cat_f'] == $c['id']) ? 'selected' : '';
                        echo "<option value='{$c['id']}' $sel>{$c['name']}</option>";
                    }
                    ?>
                </select>

                <select name="brand_f" id="filter_brand">
                    <option value="">All Brands</option>
                </select>

                <select name="var_f" id="filter_variation">
                    <option value="">All Variations</option>
                </select>

                <!-- Color filter - only shown when needed -->
                <input type="text" name="color_f" id="color_filter_input" placeholder="Color..." 
                       value="<?= $_GET['color_f'] ?? '' ?>" 
                       style="<?php
                           // Check if current category filter is Oil Paint
                           $showColorFilter = false;
                           if(isset($_GET['cat_f']) && $_GET['cat_f'] > 0){
                               $catId = (int)$_GET['cat_f'];
                               $catResult = $conn->query("SELECT name FROM categories WHERE id = $catId");
                               if($catResult && $catRow = $catResult->fetch_assoc()){
                                   $catName = strtolower($catRow['name']);
                                   $showColorFilter = (strpos($catName, 'oil paint') !== false || strpos($catName, 'paint') !== false);
                               }
                           }
                           echo $showColorFilter ? '' : 'display: none;';
                       ?>">

                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="products.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <br>

        <table>
            <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Variation</th>
                <th>Color</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Action</th>
            </tr>
            <?php
            $cat_f = (int)($_GET['cat_f'] ?? 0);
            $brand_f = (int)($_GET['brand_f'] ?? 0);
            $var_f = (int)($_GET['var_f'] ?? 0);
            $color_f = $_GET['color_f'] ?? '';

            $q = "SELECT p.*, c.name AS category, b.name AS brand, v.value AS variation 
                  FROM products p 
                  JOIN categories c ON p.category_id=c.id 
                  JOIN brands b ON p.brand_id=b.id 
                  JOIN variations v ON p.variation_id=v.id WHERE 1";
            if($cat_f > 0) $q .= " AND p.category_id = $cat_f";
            if($brand_f > 0) $q .= " AND p.brand_id = $brand_f";
            if($var_f > 0) $q .= " AND p.variation_id = $var_f";
            if(!empty($color_f)) {
                $color_escaped = $conn->real_escape_string($color_f);
                $q .= " AND p.color LIKE '%$color_escaped%'";
            }
            $q .= " ORDER BY p.id DESC";
            $r = $conn->query($q);

            if($r->num_rows > 0){
                while($row=$r->fetch_assoc()){ ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><span class="badge-ui"><?= htmlspecialchars($row['category']) ?></span></td>
                    <td><?= htmlspecialchars($row['brand']) ?></td>
                    <td><?= htmlspecialchars($row['variation']) ?></td>
                    <td>
                        <?php if ($row['color']): ?>
                            <span style="background: #fef3c7; padding: 3px 8px; border-radius: 4px; font-size: 12px; color: #92400e; font-weight: 600;">
                                <?= htmlspecialchars($row['color']) ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #999; font-size: 12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><strong>Rs.<?= number_format($row['price'], 2) ?></strong></td>
                    <td><?= $row['stock'] ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" onclick='openEditModal(<?= json_encode($row) ?>)'>Edit</button>
                        <a class="btn btn-danger btn-sm" href="#" onclick="deleteProduct(<?= $row['id'] ?>)">Delete</a>
                    </td>
                </tr>
                <?php }
            } else { echo "<tr><td colspan='8' style='text-align:center;'>No products found.</td></tr>"; } ?>
        </table>
    </div>
</div>

<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <h3>✏️ Edit Product</h3>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Category</label>
                <input type="text" id="edit_display_cat" readonly class="read-only-input">
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" id="edit_display_brand" readonly class="read-only-input">
            </div>
            <div class="form-group">
                <label>Variation</label>
                <input type="text" id="edit_display_var" readonly class="read-only-input">
            </div>

            <div class="form-group" id="edit_color_group">
                <label>Color (optional)</label>
                <input type="text" name="color" id="edit_color" placeholder="Leave empty if not applicable">
                <small style="color: #666; font-size: 11px;">Only for products like Oil Paint with color variants</small>
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

            <div class="form-group">
                <label>Price (Rs.)</label>
                <input type="number" step="0.01" name="price" id="edit_price" required>
            </div>
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock" id="edit_stock" required>
            </div>

            <div style="margin-top:20px; display: flex; gap: 10px;">
                <button type="submit" name="update" class="btn btn-primary" style="flex: 2;">Update Product</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toast notification
function showToast(message, isError = false) {
    const toast = document.getElementById("toast");
    toast.innerText = message;
    toast.style.backgroundColor = isError ? "#e74c3c" : "#2ecc71"; 
    toast.className = "toast show";
    setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
}

window.onload = function() {
    const params = new URLSearchParams(window.location.search);
    if (params.has('msg')) {
        const msg = params.get('msg');
        if (msg === 'added') showToast("✅ Product added to inventory!");
        if (msg === 'updated') showToast("💾 Product updated!");
        if (msg === 'deleted') showToast("🗑️ Product removed!", true);
        if (msg === 'duplicate') showToast("⚠️ This product already exists!", true);
        window.history.replaceState({}, document.title, "products.php");
    }
};

// Modal Logic
function openEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_display_cat').value = data.category;
    document.getElementById('edit_display_brand').value = data.brand;
    document.getElementById('edit_display_var').value = data.variation;
    document.getElementById('edit_color').value = data.color || '';
    document.getElementById('edit_price').value = data.price;
    document.getElementById('edit_stock').value = data.stock;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

// Delete logic
function deleteProduct(id) {
    if (prompt("Enter password:") === "123") { window.location.href = "?del=" + id; } 
    else { showToast("❌ Incorrect password!", true); }
}

// ============================================================
// ADD FORM - Dependent Dropdowns
// ============================================================
document.getElementById('category').addEventListener('change', function() {
    const catId = this.value;
    
    // Reset brand and variation
    document.getElementById('brand').innerHTML = '<option value="">Select Brand</option>';
    document.getElementById('variation').innerHTML = '<option value="">Select Variation</option>';
    
    if (!catId) {
        document.getElementById('color_input').style.display = 'none';
        document.getElementById('color_input').required = false;
        return;
    }
    
    // Check if this category needs colors (Oil Paint detection)
    fetch('check_if_oilpaint.php?category_id=' + catId)
        .then(res => res.json())
        .then(data => {
            const colorInput = document.getElementById('color_input');
            if (data.is_oilpaint) {
                colorInput.style.display = 'block';
                colorInput.required = true;
            } else {
                colorInput.style.display = 'none';
                colorInput.required = false;
                colorInput.value = '';
            }
        })
        .catch(err => console.error('Color check error:', err));
    
    // Fetch brands for this category
    fetch('get_brands.php?category_id=' + catId)
        .then(res => res.json())
        .then(data => {
            const brandSelect = document.getElementById('brand');
            data.forEach(brand => {
                const opt = document.createElement('option');
                opt.value = brand.id;
                opt.textContent = brand.name;
                brandSelect.appendChild(opt);
            });
        })
        .catch(err => console.error('Brand fetch error:', err));
});

document.getElementById('brand').addEventListener('change', function() {
    const catId = document.getElementById('category').value;
    const brandId = this.value;
    
    // Reset variation
    document.getElementById('variation').innerHTML = '<option value="">Select Variation</option>';
    
    if (!catId || !brandId) return;
    
    // Fetch variations for this category + brand combination
    fetch(`get_variations.php?category_id=${catId}&brand_id=${brandId}`)
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            const varSelect = document.getElementById('variation');
            
            if (!data || data.length === 0) {
                varSelect.innerHTML = '<option value="">No variations found - Please add variations for this category first</option>';
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
            document.getElementById('variation').innerHTML = '<option value="">Error loading variations</option>';
        });
});

// ============================================================
// FILTER SECTION - Dependent Dropdowns
// ============================================================
document.getElementById('filter_category').addEventListener('change', function() {
    const catId = this.value;
    const brandSelect = document.getElementById('filter_brand');
    const varSelect = document.getElementById('filter_variation');
    const colorInput = document.getElementById('color_filter_input');
    
    // Reset brand and variation dropdowns completely
    brandSelect.innerHTML = '<option value="">All Brands</option>';
    varSelect.innerHTML = '<option value="">All Variations</option>';
    
    if (!catId) {
        colorInput.style.display = 'none';
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
                colorInput.style.display = 'block';
            } else {
                colorInput.style.display = 'none';
                colorInput.value = '';
            }
        })
        .catch(err => {
            console.error('Color check error:', err);
            colorInput.style.display = 'none';
        });
});

document.getElementById('filter_brand').addEventListener('change', function() {
    const catId = document.getElementById('filter_category').value;
    const brandId = this.value;
    const varSelect = document.getElementById('filter_variation');
    
    // Reset variation dropdown
    varSelect.innerHTML = '<option value="">All Variations</option>';
    
    if (!catId || !brandId) return;
    
    // Fetch variations for this category + brand
    fetch(`get_variations.php?category_id=${catId}&brand_id=${brandId}`)
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
    const catId = params.get('cat_f');
    const brandId = params.get('brand_f');
    const varId = params.get('var_f');
    
    if (catId) {
        // Load brands for the selected category
        fetch('get_brands.php?category_id=' + catId)
            .then(res => res.json())
            .then(data => {
                const brandSelect = document.getElementById('filter_brand');
                data.forEach(brand => {
                    const opt = document.createElement('option');
                    opt.value = brand.id;
                    opt.textContent = brand.name;
                    if (brand.id == brandId) opt.selected = true;
                    brandSelect.appendChild(opt);
                });
                
                // If brand is selected, load variations
                if (brandId) {
                    return fetch(`get_variations.php?category_id=${catId}&brand_id=${brandId}`);
                }
            })
            .then(res => res ? res.json() : null)
            .then(data => {
                if (data) {
                    const varSelect = document.getElementById('filter_variation');
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
</script>

<style>
/* New Modal Style */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 3000; }
.modal-box { background: white; padding: 30px; border-radius: 12px; width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px; }
.form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }

/* Rest of your existing styles */
.search-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.search-form select, .search-form input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; min-width: 150px; }
.toast { visibility: hidden; min-width: 280px; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 2000; left: 50%; bottom: 30px; transform: translateX(-50%); box-shadow: 0 5px 15px rgba(0,0,0,0.3); font-weight: 600; }
.toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
.add-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
.add-form-grid input, .add-form-grid select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
.section-wrapper { padding: 5px 0; }
.section-title { font-size: 16px; margin-bottom: 12px; color: #333; font-weight: 600; }
.section-divider { border: 0; border-top: 1px solid #eee; margin: 15px 0; }
.badge-ui { background: #f0f4f8; padding: 3px 8px; border-radius: 4px; font-size: 12px; }
@keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
@keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
.read-only-input {
    background-color: #f9f9f9;
    color: #666;
    border: 1px solid #eee !important;
    cursor: not-allowed;
    font-size: 13px;
    font-weight: 500;
}
</style>

<?php include("layout/footer.php"); ?>