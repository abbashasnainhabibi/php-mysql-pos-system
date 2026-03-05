<?php
include("../config/db.php");
include("layout/header.php");
include("layout/sidebar.php");

/* ================= PHP LOGIC ================= */

/* ADD BRAND */
if(isset($_POST['add'])){
    $category_id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $conn->query("INSERT INTO brands(category_id, name) VALUES($category_id, '$name')");
    header("Location: brands.php?msg=added");
    exit;
}

/* UPDATE BRAND */
if(isset($_POST['edit'])){
    $id   = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $conn->query("UPDATE brands SET name='$name' WHERE id=$id");
    header("Location: brands.php?msg=updated");
    exit;
}

/* DELETE BRAND */
if(isset($_GET['del'])){
    $conn->query("DELETE FROM brands WHERE id=".(int)$_GET['del']);
    header("Location: brands.php?msg=deleted");
    exit;
}
?>

<div class="content">
    <div class="header">
        <h2>Brands Management</h2>
    </div>

    <div id="toast" class="toast"></div>

    <div class="card">
        
        <div class="section-wrapper">
            <h3 class="section-title">✨ Add New Brand</h3>
            <form method="POST" class="add-form">
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php
                    $cats = $conn->query("SELECT * FROM categories ORDER BY name");
                    while($c = $cats->fetch_assoc()){
                        echo "<option value='{$c['id']}'>{$c['name']}</option>";
                    }
                    ?>
                </select>
                <input type="text" name="name" placeholder="Brand name" required>
                <button class="btn btn-primary" name="add">Add Brand</button>
            </form>
        </div>

        <hr class="section-divider">

        <div class="section-wrapper">
            <h3 class="section-title">🔍 Search & Filter</h3>
            <form method="GET" class="search-form">
                <select name="category_filter">
                    <option value="">All Categories</option>
                    <?php
                    $cats = $conn->query("SELECT * FROM categories ORDER BY name");
                    while($c = $cats->fetch_assoc()){
                        $selected = (isset($_GET['category_filter']) && $_GET['category_filter']==$c['id']) ? 'selected' : '';
                        echo "<option value='{$c['id']}' $selected>{$c['name']}</option>";
                    }
                    ?>
                </select>

                <input type="text" name="brand_search" placeholder="Search brand name..." 
                       value="<?= isset($_GET['brand_search']) ? htmlspecialchars($_GET['brand_search']) : '' ?>">

                <button type="submit" class="btn btn-primary">Search</button>
                <a href="brands.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <br>

        <table>
            <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Action</th>
            </tr>

            <?php
            $categoryFilter = isset($_GET['category_filter']) ? (int)$_GET['category_filter'] : 0;
            $brandSearch    = isset($_GET['brand_search']) ? trim($_GET['brand_search']) : '';

            $q = "SELECT brands.id, brands.name AS brand, categories.name AS category 
                  FROM brands 
                  JOIN categories ON brands.category_id = categories.id 
                  WHERE 1";

            if ($categoryFilter > 0) {
                $q .= " AND brands.category_id = $categoryFilter";
            }

            if (!empty($brandSearch)) {
                $brandSearchEscaped = $conn->real_escape_string($brandSearch);
                // Fuzzy search logic (handles typos like 'aqya')
                $q .= " AND (brands.name LIKE '%$brandSearchEscaped%' OR brands.name SOUNDS LIKE '$brandSearchEscaped')";
            }

            $q .= " ORDER BY categories.name, brands.name";
            $r = $conn->query($q);

            if($r->num_rows > 0){
                while($b = $r->fetch_assoc()){
                ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><span class="cat-badge"><?= htmlspecialchars($b['category']) ?></span></td>
                    <td><?= htmlspecialchars($b['brand']) ?></td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="#" onclick="openEditBrandModal(<?= $b['id'] ?>, '<?= htmlspecialchars($b['category'], ENT_QUOTES) ?>', '<?= htmlspecialchars($b['brand'], ENT_QUOTES) ?>')">Edit</a>
                        <a class="btn btn-danger btn-sm" href="#" onclick="deleteBrand(<?= $b['id'] ?>)">Delete</a>
                    </td>
                </tr>
                <?php 
                } 
            } else {
                echo "<tr><td colspan='4' style='text-align:center;'>No brands found.</td></tr>";
            }
            ?>
        </table>
    </div>
</div>

<div id="editBrandModal" class="modal">
    <div class="modal-content">
        <h3>✏️ Update Brand</h3>
        <form method="POST">
            <input type="hidden" name="id" id="edit_brand_id">
            <div class="form-group">
                <label>Category</label>
                <input type="text" id="edit_category_name" readonly style="background:#f4f4f4; color:#888;">
            </div>
            <div class="form-group">
                <label>Brand Name</label>
                <input type="text" name="name" id="edit_brand_name" required>
            </div>
            <div class="modal-actions">
                <button type="submit" name="edit" class="btn btn-primary">💾 Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditBrandModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// 1. Toast Notification Logic
function showToast(message, isError = false) {
    const toast = document.getElementById("toast");
    toast.innerText = message;
    toast.style.backgroundColor = isError ? "#e74c3c" : "#2ecc71"; // Red for error, Green for success
    toast.className = "toast show";
    setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
}

// 2. Handle URL Messages on Load
window.onload = function() {
    const params = new URLSearchParams(window.location.search);
    if (params.has('msg')) {
        const msg = params.get('msg');
        if (msg === 'added') showToast("✅ Brand added successfully!");
        if (msg === 'updated') showToast("💾 Brand updated successfully!");
        if (msg === 'deleted') showToast("🗑️ Brand deleted successfully!",true);
        
        // Remove msg from URL without refreshing
        window.history.replaceState({}, document.title, "brands.php");
    }
};

// 3. Delete Logic
function deleteBrand(id) {
    const password = prompt("Enter password to delete Brand:");
    if (password === "123") {
        window.location.href = "?del=" + id;
    } else if (password !== null) {
        showToast("❌ Incorrect password!", true);
    }
}

// 4. Modal Logic
function openEditBrandModal(id, cat, name) {
    document.getElementById('edit_brand_id').value = id;
    document.getElementById('edit_category_name').value = cat;
    document.getElementById('edit_brand_name').value = name;
    document.getElementById('editBrandModal').style.display = 'flex';
}

function closeEditBrandModal() {
    document.getElementById('editBrandModal').style.display = 'none';
}
</script>

<style>
/* Toast Design */
.toast {
    visibility: hidden;
    min-width: 280px;
    color: #fff;
    text-align: center;
    border-radius: 8px;
    padding: 16px;
    position: fixed;
    z-index: 2000;
    left: 50%;
    bottom: 30px;
    transform: translateX(-50%);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    font-weight: 600;
}
.toast.show {
    visibility: visible;
    animation: fadein 0.5s, fadeout 0.5s 2.5s;
}

/* Sections & Layout */
.section-wrapper { padding: 5px 0; }
.section-title { font-size: 16px; margin-bottom: 10px; color: #444; }
.section-divider { border: 0; border-top: 1px solid #eee; margin: 15px 0; }
.cat-badge { background: #f0f2f5; padding: 3px 8px; border-radius: 4px; font-size: 12px; color: #666; }

/* Forms */
.add-form, .search-form { display: flex; gap: 10px; flex-wrap: wrap; }
.add-form input, .add-form select, .search-form input, .search-form select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

/* Modals */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: white;
    padding: 25px;
    border-radius: 10px;
    width: 400px;
}
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
.modal-actions { display: flex; gap: 10px; margin-top: 20px; }

/* Animations */
@keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
@keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
</style>

<?php include("layout/footer.php"); ?>