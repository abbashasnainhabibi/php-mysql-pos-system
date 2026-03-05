<?php
include("../config/db.php");
include("layout/header.php");
include("layout/sidebar.php");

/* ================= PHP LOGIC ================= */

/* ADD CATEGORY */
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $conn->query("INSERT INTO categories(name) VALUES('$name')");
    header("Location: categories.php?msg=added");
    exit;
}

/* UPDATE CATEGORY */
if (isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $name = trim($_POST['name']);
    $conn->query("UPDATE categories SET name='$name' WHERE id=$id");
    header("Location: categories.php?msg=updated");
    exit;
}

/* DELETE CATEGORY */
if (isset($_GET['del'])) {
    $conn->query("DELETE FROM categories WHERE id=" . (int) $_GET['del']);
    header("Location: categories.php?msg=deleted");
    exit;
}
?>

<div class="content">
    <div class="header">
        <h2>Category Management</h2>
    </div>

    <div id="toast" class="toast"></div>

    <div class="card">

        <div class="section-wrapper">
            <h3 class="section-title">✨ Add New Category</h3>
            <form method="POST" class="add-form">
                <input type="text" name="name" placeholder="Category name" required>
                <button class="btn btn-primary" name="add">Add Category</button>
            </form>
        </div>

        <hr class="section-divider">

        <div class="section-wrapper">
            <h3 class="section-title">🔍 Search Categories</h3>
            <form method="GET" class="search-form">
                <input type="text" name="cat_search" placeholder="Search category name..." 
                       value="<?= isset($_GET['cat_search']) ? htmlspecialchars($_GET['cat_search']) : '' ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="categories.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <br>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Action</th>
            </tr>

            <?php
            $catSearch = isset($_GET['cat_search']) ? trim($_GET['cat_search']) : '';
            
            $q = "SELECT * FROM categories WHERE 1";
            
            if (!empty($catSearch)) {
                $searchEscaped = $conn->real_escape_string($catSearch);
                // Fuzzy search logic (Partial spelling & Sounds like)
                $q .= " AND (name LIKE '%$searchEscaped%' OR name SOUNDS LIKE '$searchEscaped')";
            }
            
            $q .= " ORDER BY id DESC";
            $cats = $conn->query($q);

            if($cats->num_rows > 0){
                while ($c = $cats->fetch_assoc()) {
                ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="#"
                           onclick="openEditModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>')">
                            Edit
                        </a>
                        <a class="btn btn-danger btn-sm" href="#" onclick="deleteCategory(<?= $c['id'] ?>)">
                            Delete
                        </a>
                    </td>
                </tr>
                <?php 
                } 
            } else {
                echo "<tr><td colspan='3' style='text-align:center;'>No categories found.</td></tr>";
            }
            ?>
        </table>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>✏️ Update Category</h3>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            <div class="modal-actions">
                <button type="submit" name="edit" class="btn btn-primary">💾 Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// 1. Toast Notification Logic
function showToast(message, isError = false) {
    const toast = document.getElementById("toast");
    toast.innerText = message;
    toast.style.backgroundColor = isError ? "#e74c3c" : "#2ecc71"; // Red for error/delete, Green for success
    toast.className = "toast show";
    setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
}

// 2. Handle URL Messages on Load
window.onload = function() {
    const params = new URLSearchParams(window.location.search);
    if (params.has('msg')) {
        const msg = params.get('msg');
        if (msg === 'added') showToast("✅ Category added successfully!");
        if (msg === 'updated') showToast("💾 Category updated successfully!");
        if (msg === 'deleted') showToast("🗑️ Category deleted successfully!", true); // Red for delete
        
        // Clean URL
        window.history.replaceState({}, document.title, "categories.php");
    }
};

// 3. Delete Logic
function deleteCategory(id) {
    const password = prompt("Enter password to delete category:");
    if (password === "123") {
        window.location.href = "?del=" + id;
    } else if (password !== null) {
        showToast("❌ Incorrect password!", true);
    }
}

// 4. Modal Logic
function openEditModal(id, name) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('editModal').style.display = 'flex';
    document.getElementById('edit_name').focus();
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<style>
/* Toast Notification */
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

/* UI Layout */
.section-wrapper { padding: 5px 0; }
.section-title { font-size: 16px; margin-bottom: 10px; color: #333; font-weight: 600; }
.section-divider { border: 0; border-top: 1px solid #eee; margin: 15px 0; }

.add-form, .search-form { display: flex; gap: 10px; }
.add-form input, .search-form input {
    padding: 9px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    flex: 1;
    max-width: 300px;
}

/* Modal Styling */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 999;
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: #fff;
    padding: 25px;
    width: 400px;
    border-radius: 10px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
@keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
@keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }

.form-group { margin-bottom: 15px; }
.form-group label { font-weight: 600; display: block; margin-bottom: 5px; }
.form-group input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; }
.modal-actions { display: flex; gap: 10px; margin-top: 20px; }
</style>

<?php include("layout/footer.php"); ?>