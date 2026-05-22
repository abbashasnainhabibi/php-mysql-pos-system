# Point of Sale (POS) System

A comprehensive point-of-sale system for wholesale and retail businesses, featuring sales invoicing, purchase management, and real-time inventory tracking built with PHP and MySQL.

## 🛒 Overview

The POS System is a professional-grade solution designed for wholesale and retail operations. It manages the complete sales process from product selection to invoice generation, with a parallel purchase order system for vendor management and inventory replenishment. Real-time stock synchronization ensures accurate inventory at all times.

## ✨ Features

### Sales Panel (Customer Invoicing)

#### Product Selection
- **Category-Based Navigation**
  - View all product categories (e.g., Brushes for paint business)
  - Select specific category to browse products
  - Expandable category system

- **Brand Selection**
  - View brands within selected category
  - Multiple brand options per category
  - Brand-specific filtering

- **Variation Selection**
  - Select product variations (e.g., brush sizes: 0.5", 1", 1.5")
  - Dimension and specification options
  - Variation availability checking

- **Real-Time Quantity Validation**
  - Check stock availability
  - Quantity input field
  - Database synchronization
  - Prevents overselling
  - Error message if quantity exceeds stock
  - Cannot add unavailable quantities to bill

#### Bill Building
- **Current Bill View**
  - Display items added to current bill
  - Show item details (name, price, quantity)
  - Running bill total
  - Real-time price calculations
  - Remove items from bill
  - Modify quantities in bill
  - Add additional products

- **Generate Invoice**
  - Click "Complete and Generate Bill"
  - Move to invoice finalization page

#### Invoice Finalization
- **Customer Information**
  - Select walk-in customer or existing customer name
  - Enter customer phone number
  - Optional invoice message/note

- **Product Confirmation**
  - Display all selected products
  - Show product category (read-only)
  - Display product price (read-only)
  - Cannot edit product details
  - Can remove products
  - Can adjust quantities

- **Invoice Details**
  - Quantity field for final order amount
  - Delivery address field
  - Payment method selection:
    - Credit Card
    - Cash on Delivery
    - Check
    - Other payment types

- **Save Invoice**
  - Click "Place Order" to save
  - Invoice stored in database with timestamp
  - Linked to customer record

#### Invoice Management
- **Invoice Viewing**
  - View all generated invoices
  - Display customer name and contact
  - Show all products in invoice
  - Display invoice date and time

- **Payment Status Tracking**
  - Unpaid status (no payment received)
  - Partially Paid status (partial payment received)
  - Fully Paid status (complete payment received)

- **Payment Management**
  - Click "Update Payment"
  - View total outstanding amount
  - Enter payment amount received
  - Click "Submit Payment"
  - Payment added to invoice total
  - Status updates automatically
  - Remaining balance calculated

- **Invoice Actions**
  - Print invoice
  - Download as PDF
  - View invoice details
  - Create new invoice

#### Invoice History & Analytics
- **View Invoices Panel**
  - List all invoices with status
  - Count unpaid invoices
  - Count partially paid invoices
  - Count fully paid invoices
  - Display total balance due
  - All data synchronized in real-time

- **Search & Filter**
  - Search by invoice number
  - Filter by customer name
  - Filter by date (specific date)
  - Filter by month
  - Filter by year
  - Combined filtering options
  - Quick search functionality

### Admin Panel

#### Dashboard
- **Overview Metrics**
  - Today's revenue (daily sales total)
  - Total revenue (all-time sales)
  - Amount received today
  - Remaining unpaid invoices count
  - Total products in inventory
  - Additional KPIs and metrics

- **Quick Access**
  - Navigate to all management sections
  - View recent transactions
  - Access reports

#### Category Management
- **Create Categories**
  - Add new product categories
  - Category name and description

- **Edit Categories**
  - Modify category information
  - Update category details

- **Delete Categories**
  - Remove unused categories

#### Brand Management
- **Create Brands**
  - Add new brands
  - Assign to categories

- **Edit Brands**
  - Modify brand information

- **Delete Brands**
  - Remove brands

#### Variation Management
- **Create Variations**
  - Add product variations (sizes, colors, etc.)
  - Assign to brands and categories

- **Edit Variations**
  - Modify variation details

- **Delete Variations**
  - Remove variations

#### Product Management
- **Product Creation Logic**
  - Category + Brand + Variation = Unique Product
  - Prevents duplicate product combinations
  - Hierarchical relationships maintained
  - All relationships synchronized

- **Create Products**
  - Select category
  - Select brand
  - Select variation
  - System prevents duplicate combinations
  - Product stored in database

#### Supplier Management
- **Add Suppliers**
  - Click "Add Supplier"
  - Enter company name
  - Enter contact person name
  - Enter phone number
  - Enter email address
  - Enter physical address
  - Save supplier information

- **Supplier Details**
  - Company name
  - Contact person
  - Phone number
  - Email address
  - Address
  - Supplier ID

#### Supplier-Linked Products
- **Product Assignment to Suppliers**
  - After creating supplier, go to Products section
  - Select supplier
  - Select product categories to assign
  - Select brands within category
  - Link products to supplier
  - Only selected products appear for this supplier

- **Supplier-Specific Inventory**
  - When selecting supplier in Purchase POS
  - Only shows categories and brands assigned to supplier
  - Inventory managed per supplier

#### Reports
- **Stock Report Page**
  - View all product inventory levels
  - Current stock quantities
  - Stock status (available, low, out-of-stock)
  - Last updated timestamp

- **Sales Report Page**
  - Historical sales data
  - Sales by date range
  - Revenue analysis
  - Product sales performance
  - Customer purchase patterns

#### Additional Admin Pages
- **User Management**
- **Settings and Configuration**
- **System Preferences**

### Purchase Panel (Vendor Management)

#### Supplier Selection
- **View Suppliers**
  - Click "Supplier" in purchase panel
  - View all created suppliers

- **Select Supplier**
  - Click on supplier name
  - View supplier details
  - Access supplier's product categories

#### Supplier Product Management
- **View Supplier Products**
  - After selecting supplier, view only their assigned categories
  - View brand options for category
  - All products linked to selected supplier

#### Stock Entry
- **Enter Stock Quantities**
  - Input received stock amounts
  - Quantity field for each product
  - Real-time validation
  - Save to database
  - Inventory updates automatically

- **Stock Synchronization**
  - Updates main inventory
  - Reflects in sales panel
  - Available for sales immediately

#### Purchase Invoices
- **View Purchase Invoices**
  - List all supplier invoices
  - Display invoice details
  - Show invoice date

- **Invoice Status**
  - Paid invoices
  - Unpaid invoices
  - Partially paid invoices

- **Invoice Filtering**
  - Filter by status
  - Filter by supplier
  - Filter by date range
  - Search functionality

## 🛠 Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP (version 7.0 or higher)
- **Database**: MySQL
- **Database Management**: PhpMyAdmin
- **Server**: Apache
- **Version Control**: Git

## 📋 Prerequisites

Before you begin, ensure you have the following installed:
- PHP 7.0 or higher
- MySQL Server
- Apache Web Server
- PhpMyAdmin
- Git
- Web browser

## 🚀 Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/pos-system.git
cd pos-system
```

### 2. Database Setup

#### Using PhpMyAdmin:
- Open PhpMyAdmin
- Create database: `pos_system_db`
- Import `database/pos_system.sql`

#### Using MySQL Command Line:
```bash
mysql -u root -p < database/pos_system.sql
```

### 3. Configure Database
- Open `config/database.php`
- Update credentials:
```php
  define('DB_HOST', 'localhost');
  define('DB_USER', 'root');
  define('DB_PASS', 'your_password');
  define('DB_NAME', 'pos_system_db');
```

### 4. Configure Server
- Place project in Apache root directory:
  - Windows: `C:\xampp\htdocs\pos-system`
  - Linux: `/var/www/html/pos-system`
  - Mac: `/Library/WebServer/Documents/pos-system`

### 5. Set Folder Permissions
```bash
chmod 755 uploads/
chmod 755 logs/
chmod 755 temp/
```

### 6. Start Services
- Start Apache Server
- Start MySQL Server

### 7. Access Application
http://localhost/pos-system/

### 8. Default Login Credentials
- Email: `admin@pos.com`
- Password: `Admin@123` (Change on first login)

## 📖 Usage Guide

### For Salespeople (Sales Panel)

#### Creating a Sales Invoice
1. **Login to POS System**
   - Enter email and password
   - Click "Login"

2. **Access Sales Panel**
   - Select "Sales POS" from main menu
   - View available categories

3. **Select Products**
   - Click on category (e.g., "Brushes")
   - Select brand (e.g., "Brand A")
   - Choose variation (e.g., "1 inch")
   - Verify available quantity
   - Enter quantity to sell (validated against stock)
   - Click "Add to Bill"

4. **Review Current Bill**
   - View items in current bill
   - Check quantities and prices
   - Remove items if needed
   - Modify quantities

5. **Generate Invoice**
   - Click "Complete and Generate Bill"
   - Proceed to invoice details page

6. **Finalize Invoice**
   - Enter or select customer name
   - Enter phone number (optional)
   - Review product list
   - Verify prices cannot be edited
   - Enter delivery address
   - Select payment method
   - Enter final quantities
   - Click "Place Order"

7. **Invoice Confirmation**
   - Receive order confirmation
   - View all invoice details
   - Note invoice number for records

8. **Manage Invoice Payments**
   - Go to "View Invoices"
   - Click invoice to view status
   - If "Unpaid": Click "Update Payment"
   - Enter payment amount received
   - Click "Submit Payment"
   - Status updates to "Partially Paid" or "Fully Paid"
   - View remaining balance

9. **Print/Download Invoice**
   - Click "Print" button
   - Or click "Download PDF"
   - Keep records for accounting

### For Admin (Admin Panel)

#### Initial Setup
1. **Login to Admin Panel**
   - Use admin credentials

2. **View Dashboard**
   - Check today's revenue
   - Monitor total revenue
   - See outstanding invoices
   - View total products

3. **Create Categories**
   - Go to "Categories"
   - Click "Add Category"
   - Enter category name
   - Save

4. **Create Brands**
   - Go to "Brands"
   - Click "Add Brand"
   - Enter brand name
   - Select category
   - Save

5. **Create Variations**
   - Go to "Variations"
   - Click "Add Variation"
   - Enter variation name (e.g., "1 inch", "2 inch")
   - Assign to brand and category
   - Save

6. **Create Products**
   - Go to "Products"
   - System shows: Category + Brand + Variation = Product
   - Select combination
   - Product is automatically created
   - Avoid duplicate combinations

7. **Add Suppliers**
   - Go to "Suppliers"
   - Click "Add Supplier"
   - Enter company name
   - Enter contact person
   - Enter phone and email
   - Enter address
   - Save

8. **Link Supplier Products**
   - Go to "Products"
   - Select supplier from dropdown
   - Choose categories they supply
   - Choose brands they supply
   - Link products to supplier

#### Daily Operations
1. **Monitor Sales**
   - Check dashboard metrics
   - Review sales reports
   - Track daily revenue

2. **Manage Inventory**
   - View stock levels
   - Monitor low-stock items
   - Place purchase orders

3. **Review Reports**
   - Check stock report
   - Review sales report
   - Analyze trends

4. **Update Purchase Invoices**
   - Receive goods from suppliers
   - Update received quantities
   - Track purchase history

## 🔐 Security Features

- User authentication and password hashing
- Role-based access control
- Session management
- Input validation and sanitization
- SQL injection prevention
- Data encryption
- Audit logging

## 📁 Project Structure
pos-system/
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── navigation.php
├── pages/
│   ├── dashboard.php
│   ├── sales_pos.php
│   ├── purchase_pos.php
│   ├── invoices.php
│   └── reports.php
├── admin/
│   ├── dashboard.php
│   ├── categories.php
│   ├── brands.php
│   ├── variations.php
│   ├── products.php
│   ├── suppliers.php
│   ├── stock_report.php
│   └── sales_report.php
├── auth/
│   ├── login.php
│   └── logout.php
├── css/
│   └── style.css
├── js/
│   └── pos_script.js
├── database/
│   └── pos_system.sql
└── README.md

## 🧪 Testing Checklist

- [ ] Create sample categories, brands, variations
- [ ] Create test products
- [ ] Add supplier with products
- [ ] Make sample sales invoice
- [ ] Update payment on invoice
- [ ] Verify stock deduction
- [ ] Check purchase panel functionality
- [ ] Review reports and filters
- [ ] Test invoice printing
- [ ] Verify all calculations

## 📊 Key Metrics

- **Daily Revenue**: Sum of all sales today
- **Total Revenue**: Cumulative sales since inception
- **Amount Received**: Total payments collected
- **Outstanding Balance**: Total unpaid invoice amounts
- **Product Count**: Total products in system
- **Low Stock Items**: Products below minimum threshold

## 📦 Backup & Maintenance

### Database Backup:
```bash
mysqldump -u root -p pos_system_db > backup/pos_system_backup.sql
```

### Database Restore:
```bash
mysql -u root -p pos_system_db < backup/pos_system_backup.sql
```

## 🐛 Troubleshooting

- **Stock Not Updating**: Verify database connection and triggers
- **Invoice Not Saving**: Check database permissions
- **Payment Calculation Error**: Verify numeric data types in database
- **PDF Download Issues**: Ensure temp folder has write permissions

## 📝 License

Proprietary - All rights reserved.

## 👨‍💼 Author

Abbas

---

**Note**: Portfolio project demonstrating complete POS system development for wholesale/retail operations.
