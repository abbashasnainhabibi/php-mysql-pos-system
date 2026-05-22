<?php
include("../config/db.php");
include("layout/header.php");
include("layout/sidebar.php");

/* ================= PHP LOGIC ================= */

/* ADD SUPPLIER */
if(isset($_POST['add'])){
    $name = $conn->real_escape_string(trim($_POST['name']));
    $contact_person = $conn->real_escape_string(trim($_POST['contact_person']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $address = $conn->real_escape_string(trim($_POST['address']));
    
    $conn->query("INSERT INTO suppliers(name, contact_person, phone, email, address) VALUES('$name', '$contact_person', '$phone', '$email', '$address')");
    header("Location: suppliers.php?msg=added");
    exit;
}

/* UPDATE SUPPLIER */
if(isset($_POST['edit'])){
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string(trim($_POST['name']));
    $contact_person = $conn->real_escape_string(trim($_POST['contact_person']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $address = $conn->real_escape_string(trim($_POST['address']));
    
    $conn->query("UPDATE suppliers SET name='$name', contact_person='$contact_person', phone='$phone', email='$email', address='$address' WHERE id=$id");
    header("Location: suppliers.php?msg=updated");
    exit;
}

/* ADD CATEGORY+BRAND LINK */
if(isset($_POST['add_link'])){
    $supplier_id = (int)$_POST['supplier_id'];
    $category_id = (int)$_POST['category_id'];
    $brand_id = (int)$_POST['brand_id'];
    
    // Check if already exists
    $check = $conn->query("SELECT id FROM supplier_category_brands WHERE supplier_id=$supplier_id AND category_id=$category_id AND brand_id=$brand_id");
    if($check->num_rows == 0){
        $conn->query("INSERT INTO supplier_category_brands(supplier_id, category_id, brand_id) VALUES($supplier_id, $category_id, $brand_id)");
    }
    header("Location: suppliers.php?manage=$supplier_id&msg=link_added");
    exit;
}

/* DELETE CATEGORY+BRAND LINK */
if(isset($_GET['del_link'])){
    $link_id = (int)$_GET['del_link'];
    $supplier_id = (int)$_GET['supplier_id'];
    $conn->query("DELETE FROM supplier_category_brands WHERE id=$link_id");
    header("Location: suppliers.php?manage=$supplier_id&msg=link_deleted");
    exit;
}

/* DELETE SUPPLIER */
if(isset($_GET['del'])){
    $id = (int)$_GET['del'];
    $check = $conn->query("SELECT COUNT(*) as count FROM purchase_invoices WHERE supplier_id=$id")->fetch_assoc();
    if($check['count'] > 0){
        header("Location: suppliers.php?msg=has_purchases");
        exit;
    }
    $conn->query("DELETE FROM suppliers WHERE id=$id");
    header("Location: suppliers.php?msg=deleted");
    exit;
}
?>

<div class="content">
    <div class="header">
        <h2>📦 Suppliers Management</h2>
    </div>

    <div id="toast" class="toast"></div>

    <?php if(isset($_GET['manage'])): 
        $supplier_id = (int)$_GET['manage'];
        $supplier = $conn->query("SELECT * FROM suppliers WHERE id=$supplier_id")->fetch_assoc();
    ?>
    
    <!-- MANAGE SUPPLIER PRODUCTS -->
    <div class="card" style="background: #fef3c7; border-left: 4px solid #f59e0b;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #92400e;">🔗 Manage Products for: <?= htmlspecialchars($supplier['name']) ?></h3>
            <a href="suppliers.php" class="btn btn-secondary">← Back to Suppliers</a>
        </div>

        <div class="section-wrapper">
            <h4 style="color: #92400e; font-size: 0.95rem; margin-bottom: 15px;">➕ Add Category + Brand Link</h4>
            <form method="POST" class="add-form-grid">
                <input type="hidden" name="supplier_id" value="<?= $supplier_id ?>">
                <select name="category_id" id="link_category" required>
                    <option value="">Select Category</option>
                    <?php
                    $cats = $conn->query("SELECT * FROM categories ORDER BY name");
                    while($c = $cats->fetch_assoc()){
                        echo "<option value='{$c['id']}'>{$c['name']}</option>";
                    }
                    ?>
                </select>
                <select name="brand_id" id="link_brand" required>
                    <option value="">Select Brand</option>
                </select>
                <button class="btn btn-primary" name="add_link">Add Link</button>
            </form>
        </div>

        <hr class="section-divider">

        <div class="section-wrapper">
            <h4 style="color: #92400e; font-size: 0.95rem; margin-bottom: 15px;">📋 Current Product Links</h4>
            <table>
                <tr>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Action</th>
                </tr>
                <?php
                $links = $conn->query("
                    SELECT scb.id, c.name as category, b.name as brand
                    FROM supplier_category_brands scb
                    JOIN categories c ON scb.category_id = c.id
                    JOIN brands b ON scb.brand_id = b.id
                    WHERE scb.supplier_id = $supplier_id
                    ORDER BY c.name, b.name
                ");
                
                if($links->num_rows > 0){
                    while($link = $links->fetch_assoc()){
                ?>
                <tr>
                    <td><span class="badge-ui" style="background: #dbeafe; color: #1e40af;"><?= htmlspecialchars($link['category']) ?></span></td>
                    <td><span class="badge-ui" style="background: #fce7f3; color: #9f1239;"><?= htmlspecialchars($link['brand']) ?></span></td>
                    <td>
                        <a href="?del_link=<?= $link['id'] ?>&supplier_id=<?= $supplier_id ?>" 
                           onclick="return confirm('Remove this link?')" 
                           class="btn btn-danger btn-sm">Remove</a>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; color: #92400e;'>No products linked yet. Add category+brand combinations above.</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <?php else: ?>

    <!-- REGULAR SUPPLIERS LIST -->
    <div class="card">
        <div class="section-wrapper">
            <h3 class="section-title">✨ Add New Supplier</h3>
            <form method="POST" class="add-form-grid">
                <input type="text" name="name" placeholder="Company Name *" required>
                <input type="text" name="contact_person" placeholder="Contact Person">
                <input type="text" name="phone" placeholder="Phone Number">
                <input type="email" name="email" placeholder="Email Address">
                <textarea name="address" placeholder="Address" style="grid-column: 1 / -1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; resize: vertical; min-height: 60px;"></textarea>
                <button class="btn btn-primary" name="add" style="grid-column: 1 / -1;">Add Supplier</button>
            </form>
        </div>

        <hr class="section-divider">

        <div class="section-wrapper">
            <h3 class="section-title">🔍 Search Suppliers</h3>
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search by name, contact, phone..." 
                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="suppliers.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <br>

        <table>
            <tr>
                <th>ID</th>
                <th>Company Name</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th>Products</th>
                <th>Outstanding Balance</th>
                <th>Action</th>
            </tr>

            <?php
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            
            $q = "SELECT s.*, 
                  COALESCE(SUM(CASE WHEN pi.payment_status != 'paid' THEN pi.balance ELSE 0 END), 0) as outstanding,
                  COUNT(DISTINCT scb.id) as product_links
                  FROM suppliers s
                  LEFT JOIN purchase_invoices pi ON s.id = pi.supplier_id
                  LEFT JOIN supplier_category_brands scb ON s.id = scb.supplier_id
                  WHERE 1";
            
            if (!empty($search)) {
                $searchEscaped = $conn->real_escape_string($search);
                $q .= " AND (s.name LIKE '%$searchEscaped%' 
                        OR s.contact_person LIKE '%$searchEscaped%' 
                        OR s.phone LIKE '%$searchEscaped%')";
            }
            
            $q .= " GROUP BY s.id ORDER BY s.name";
            $r = $conn->query($q);

            if($r->num_rows > 0){
                while($s = $r->fetch_assoc()){
                ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['contact_person']) ?></td>
                    <td><?= htmlspecialchars($s['phone']) ?></td>
                    <td>
                        <a href="?manage=<?= $s['id'] ?>" class="btn btn-primary btn-sm">
                            🔗 Manage (<?= $s['product_links'] ?>)
                        </a>
                    </td>
                    <td style="font-weight: 700; color: <?= $s['outstanding'] > 0 ? '#dc2626' : '#10b981' ?>;">
                        Rs <?= number_format($s['outstanding'], 2) ?>
                    </td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="#" onclick='openEditModal(<?= json_encode($s) ?>)'>Edit</a>
                        <a class="btn btn-danger btn-sm" href="#" onclick="deleteSupplier(<?= $s['id'] ?>)">Delete</a>
                    </td>
                </tr>
                <?php 
                } 
            } else {
                echo "<tr><td colspan='7' style='text-align:center;'>No suppliers found.</td></tr>";
            }
            ?>
        </table>
    </div>

    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <h3>✏️ Edit Supplier</h3>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Company Name *</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            
            <div class="form-group">
                <label>Contact Person</label>
                <input type="text" name="contact_person" id="edit_contact_person">
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email">
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" id="edit_address" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; resize: vertical;"></textarea>
            </div>

            <div style="margin-top:20px; display: flex; gap: 10px;">
                <button type="submit" name="edit" class="btn btn-primary" style="flex: 2;">💾 Update</button>
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
        if (msg === 'added') showToast("✅ Supplier added successfully!");
        if (msg === 'updated') showToast("💾 Supplier updated successfully!");
        if (msg === 'deleted') showToast("🗑️ Supplier deleted successfully!", true);
        if (msg === 'has_purchases') showToast("⚠️ Cannot delete supplier with existing purchases!", true);
        if (msg === 'link_added') showToast("✅ Product link added!");
        if (msg === 'link_deleted') showToast("🗑️ Product link removed!", true);
        
        // Remove msg but keep manage parameter
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('msg');
        window.history.replaceState({}, document.title, newUrl);
    }
};

// Modal Logic
function openEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_contact_person').value = data.contact_person || '';
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_address').value = data.address || '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Delete logic
function deleteSupplier(id) {
    if (prompt("Enter password to delete:") === "123") {
        window.location.href = "?del=" + id;
    } else {
        showToast("❌ Incorrect password!", true);
    }
}

// Dynamic brand loading for product links
document.getElementById('link_category')?.addEventListener('change', function() {
    const categoryId = this.value;
    const brandSelect = document.getElementById('link_brand');
    brandSelect.innerHTML = '<option value="">Select Brand</option>';
    
    if(categoryId) {
        fetch('../get_brands.php?category_id=' + categoryId)
            .then(r => r.json())
            .then(data => {
                data.forEach(brand => {
                    brandSelect.innerHTML += `<option value="${brand.id}">${brand.name}</option>`;
                });
            });
    }
});
</script>

<style>
/* Modal Style */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 3000; }
.modal-box { background: white; padding: 30px; border-radius: 12px; width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px; }
.form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }

/* Toast */
.toast { visibility: hidden; min-width: 280px; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 2000; left: 50%; bottom: 30px; transform: translateX(-50%); box-shadow: 0 5px 15px rgba(0,0,0,0.3); font-weight: 600; }
.toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }

/* Forms */
.add-form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.add-form-grid input, .add-form-grid textarea, .add-form-grid select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
.search-form { display: flex; gap: 10px; flex-wrap: wrap; }
.search-form input { flex: 1; min-width: 200px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }

.section-wrapper { padding: 5px 0; }
.section-title { font-size: 16px; margin-bottom: 12px; color: #333; font-weight: 600; }
.section-divider { border: 0; border-top: 1px solid #eee; margin: 15px 0; }

.badge-ui { background: #f0f4f8; padding: 5px 10px; border-radius: 4px; font-size: 12px; display: inline-block; }

@keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
@keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
</style>

<?php include("layout/footer.php"); ?>