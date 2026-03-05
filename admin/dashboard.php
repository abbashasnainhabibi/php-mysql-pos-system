<?php 
include("../config/db.php");
// Set Pakistan Timezone for accurate current-day stats
date_default_timezone_set('Asia/Karachi');
$conn->query("SET time_zone = '+05:00'");

include("layout/header.php");
include("layout/sidebar.php");

// Fetch Data for Stats
$total_cats  = $conn->query("SELECT COUNT(*) c FROM categories")->fetch_assoc()['c'];
$total_brands = $conn->query("SELECT COUNT(*) c FROM brands")->fetch_assoc()['c'];
$total_prods  = $conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];

// Today's Sales Stats with Payment Tracking
$today_date = date('Y-m-d');
$today_sales = $conn->query("
    SELECT 
        SUM(total) as rev, 
        SUM(amount_paid) as received,
        SUM(balance) as remaining,
        COUNT(*) as inv 
    FROM invoices 
    WHERE DATE(invoice_date) = '$today_date' AND saved = 1
")->fetch_assoc();

$today_revenue = $today_sales['rev'] ?? 0;
$today_received = $today_sales['received'] ?? 0;
$today_remaining = $today_sales['remaining'] ?? 0;
$today_invoices = $today_sales['inv'] ?? 0;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="content">
    <div class="header" style="margin-bottom: 25px;">
        <h2 style="font-weight: 700; color: #1e293b;">Dashboard Overview</h2>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <div class="card" style="border: none; border-left: 5px solid #10b981; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 600;">Today's Revenue</span>
                    <h3 style="margin: 5px 0; font-size: 1.8rem; color: #0f172a;">Rs <?= number_format($today_revenue) ?></h3>
                </div>
                <div style="background: #dcfce7; color: #10b981; padding: 12px; border-radius: 12px;">
                    <i class="fa fa-wallet fa-lg"></i>
                </div>
            </div>
        </div>

        <div class="card" style="border: none; border-left: 5px solid #059669; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 600;">Today's Received</span>
                    <h3 style="margin: 5px 0; font-size: 1.8rem; color: #059669;">Rs <?= number_format($today_received) ?></h3>
                </div>
                <div style="background: #d1fae5; color: #059669; padding: 12px; border-radius: 12px;">
                    <i class="fa fa-money-bill-wave fa-lg"></i>
                </div>
            </div>
        </div>

        <div class="card" style="border: none; border-left: 5px solid #dc2626; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 600;">Today's Remaining</span>
                    <h3 style="margin: 5px 0; font-size: 1.8rem; color: #dc2626;">Rs <?= number_format($today_remaining) ?></h3>
                </div>
                <div style="background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px;">
                    <i class="fa fa-hand-holding-dollar fa-lg"></i>
                </div>
            </div>
        </div>

        <div class="card" style="border: none; border-left: 5px solid #3b82f6; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 600;">Today's Invoices</span>
                    <h3 style="margin: 5px 0; font-size: 1.8rem; color: #0f172a;"><?= $today_invoices ?></h3>
                </div>
                <div style="background: #dbeafe; color: #3b82f6; padding: 12px; border-radius: 12px;">
                    <i class="fa fa-file-invoice fa-lg"></i>
                </div>
            </div>
        </div>

        <div class="card" style="border: none; border-left: 5px solid #8b5cf6; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 600;">Total Products</span>
                    <h3 style="margin: 5px 0; font-size: 1.8rem; color: #0f172a;"><?= $total_prods ?></h3>
                </div>
                <div style="background: #f5f3ff; color: #8b5cf6; padding: 12px; border-radius: 12px;">
                    <i class="fa fa-boxes-stacked fa-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <h3 style="font-size: 18px; margin-bottom: 15px; color: #334155;">Inventory Summary</h3>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        
        <a href="categories.php" style="text-decoration: none; color: inherit;">
            <div class="card" style="padding: 15px; transition: transform 0.2s; cursor: pointer; border: 1px solid #e2e8f0;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fa fa-tags" style="color: #64748b;"></i> Categories: <b><?= $total_cats ?></b>
            </div>
        </a>

        <a href="brands.php" style="text-decoration: none; color: inherit;">
            <div class="card" style="padding: 15px; transition: transform 0.2s; cursor: pointer; border: 1px solid #e2e8f0;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fa fa-certificate" style="color: #64748b;"></i> Brands: <b><?= $total_brands ?></b>
            </div>
        </a>

        <div class="card" style="padding: 15px; border: 1px solid #e2e8f0; background: #f8fafc;">
            <i class="fa fa-clock" style="color: #64748b;"></i> Status: <span style="color: #10b981; font-weight: 600;">Online</span>
        </div>
    </div>
</div>

<style>
    /* Styling for the content area specifically for the modern look */
    .content {
        background: #f1f5f9; /* Light grey/blue background for the page */
        min-height: 100vh;
        padding: 30px;
    }
    .card {
        padding: 20px;
        border-radius: 12px;
    }
</style>

<?php include("layout/footer.php"); ?>