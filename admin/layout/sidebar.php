<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<?php
// Helper to detect current page for active class
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="logo">
        <i class="fa fa-paint-roller"></i>
        <span>Paint POS</span>
    </div>

    <!-- <div class="menu-label">Main Menu</div> -->
    
    <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fa fa-th-large"></i> Dashboard
    </a>
    
    <a href="categories.php" class="<?= $current_page == 'categories.php' ? 'active' : '' ?>">
        <i class="fa fa-tags"></i> Categories
    </a>
    
    <a href="brands.php" class="<?= $current_page == 'brands.php' ? 'active' : '' ?>">
        <i class="fa fa-copyright"></i> Brands
    </a>
    
    <a href="variations.php" class="<?= $current_page == 'variations.php' ? 'active' : '' ?>">
        <i class="fa fa-layer-group"></i> Variations
    </a>
    
    <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>">
        <i class="fa fa-boxes-stacked"></i> Products
    </a>
     <a href="stock_report.php" class="<?= $current_page == 'suppliers.php' ? 'active' : '' ?>">
        <i class="fa fa-chart-bar"></i> Stock Report
    </a>
    <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
        <i class="fa fa-chart-bar"></i> Sales Reports
    </a>
  <a href="suppliers.php" class="<?= $current_page == 'suppliers.php' ? 'active' : '' ?>">
        <i class="fa fa-chart-bar"></i> Supplier
    </a>
    
    <div class="menu-divider"></div>

    <a href="../index.php" class="exit-link">
        <i class="fa fa-sign-out-alt"></i> Exit to POS
    </a>
</div>
<style>
  /* --- Global Base Styles --- */
* {
    box-sizing: border-box;
}

/* --- Responsive Table Wrapper --- */
/* IMPORTANT: Wrap your <table> tags in <div class="table-responsive">...</div> */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 1rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap; /* Prevents text from wrapping awkwardly on tiny screens */
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    font-size: 14px; /* Slightly smaller for better fit */
}

th {
    background: #f8fafc;
    font-weight: 600;
    color: #475569;
}

/* --- Responsive Buttons --- */
.btn {
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
    text-decoration: none;
}

.btn-primary { background: #2563eb; color: white; }
.btn-primary:hover { background: #1d4ed8; }

.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { background: #dc2626; }

/* --- Media Queries for Mobile --- */
@media (max-width: 768px) {
    .content {
        padding: 15px; /* Reduce main padding */
    }
    
    th, td {
        padding: 10px 8px; /* Tighter padding on mobile */
        font-size: 13px;
    }

    /* Make action buttons stack or take full width in small forms */
    .btn {
        width: auto;
        padding: 10px 16px; /* Larger touch target for thumbs */
    }
    
    /* If you have multiple buttons in a cell, add a gap */
    td .btn {
        margin-bottom: 4px;
    }
}  
</style>