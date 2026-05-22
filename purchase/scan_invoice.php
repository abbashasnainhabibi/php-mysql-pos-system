<?php
session_start();
include("../config/db.php");

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$supplier_name = '';

// Block access if no supplier selected - must come via purchase_pos.php
if ($supplier_id <= 0) {
    header('Location: purchase_pos.php?error=select_supplier');
    exit;
}

$s = $conn->query("SELECT name FROM suppliers WHERE id=$supplier_id")->fetch_assoc();
if (!$s) {
    header('Location: purchase_pos.php?error=invalid_supplier');
    exit;
}
$supplier_name = $s['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Invoice Scanner</title>
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
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); margin: 0; padding-bottom: 60px; color: var(--text-dark); }

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
            max-width: 800px;
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

        .nav-links button:hover { border-color: var(--primary); color: var(--primary); }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .card h2 {
            margin: 0 0 8px 0;
            font-size: 1.3rem;
            color: var(--text-dark);
        }

        .card p {
            margin: 0 0 25px 0;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Upload Zone */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 50px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fafbff;
            position: relative;
        }

        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--primary);
            background: #eef2ff;
        }

        .upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-icon { font-size: 3rem; margin-bottom: 15px; }

        .upload-zone h3 {
            margin: 0 0 8px 0;
            font-size: 1.1rem;
            color: var(--text-dark);
        }

        .upload-zone p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .file-selected {
            display: none;
            background: #eef2ff;
            border: 2px solid var(--primary);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-top: 15px;
        }

        .file-selected .file-name {
            font-weight: 700;
            color: var(--primary);
            font-size: 1rem;
        }

        .scan-btn {
            width: 100%;
            padding: 18px;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.2s ease;
        }

        .scan-btn:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .scan-btn:disabled { background: var(--border); cursor: not-allowed; transform: none; }

        /* Loading State */
        #loadingState {
            display: none;
            text-align: center;
            padding: 40px 0;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        #loadingState p {
            color: var(--text-light);
            font-size: 1rem;
            margin: 0;
        }

        /* Results Table */
        #resultsSection { display: none; }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .results-table th {
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid var(--bg);
            color: var(--text-light);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .results-table td {
            padding: 15px 12px;
            border-bottom: 1px solid var(--bg);
            font-size: 0.95rem;
        }

        .match-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .match-exact { background: #d1fae5; color: #065f46; }
        .match-fuzzy { background: #fef3c7; color: #92400e; }
        .match-none  { background: #fee2e2; color: #991b1b; }

        .qty-edit {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 6px 10px;
            width: 70px;
            font-weight: 600;
            text-align: center;
        }

        .price-edit {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 6px 10px;
            width: 90px;
            font-weight: 600;
        }

        .confirm-btn {
            width: 100%;
            padding: 18px;
            border-radius: 12px;
            border: none;
            background: var(--accent);
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.2s ease;
        }

        .confirm-btn:hover { background: #059669; }

        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 15px 20px;
            color: #1e40af;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        /* Mock banner */
        .mock-banner {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 0.85rem;
            color: #065f46;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .remove-row { color: var(--danger); cursor: pointer; font-size: 1.2rem; background: none; border: none; }
    </style>
</head>
<body>

<header>
    <div>🤖 AI Invoice Scanner <span style="font-weight:300; opacity:0.7; font-size:1rem;">— <?= htmlspecialchars($supplier_name) ?></span></div>
    <div style="font-size: 0.9rem; font-weight:400;"><?= date('D, M d Y') ?></div>
</header>

<div class="nav-links">
    <button onclick="window.location='purchase_pos.php?supplier_id=<?= $supplier_id ?>'">← Back to Purchase POS</button>
</div>

<div class="container">

    <div class="mock-banner">
        ✅ <strong>TXT &amp; CSV files are FREE</strong> — upload a text or CSV file and it works instantly with no API key! 🖼️ For image/PDF scanning, add your Anthropic API key in <code>process_scan.php</code>.
    </div>

    <!-- Upload Card -->
    <div class="card" id="uploadCard">
        <h2>📄 Upload Invoice or Order List</h2>
        <p>Upload a photo, scan, or PDF of a handwritten or printed order list. AI will read it and match items to your products automatically.</p>

        <div class="upload-zone" id="uploadZone">
            <input type="file" id="fileInput" accept="image/*,.pdf,.txt,.csv,.xlsx,.xls" onchange="fileSelected(this)">
            <div class="upload-icon">📁</div>
            <h3>Click to upload or drag & drop</h3>
            <p>Supports JPG, PNG, PDF, TXT, CSV, Excel (.xlsx/.xls) • Handwritten or printed • English or Urdu</p>
        </div>

        <!-- Supported formats badges -->
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:15px;">
            <span style="background:#eef2ff; color:#4f46e5; padding:4px 12px; border-radius:20px; font-size:0.78rem; font-weight:600;">🖼️ Image (JPG/PNG)</span>
            <span style="background:#eef2ff; color:#4f46e5; padding:4px 12px; border-radius:20px; font-size:0.78rem; font-weight:600;">📄 PDF</span>
            <span style="background:#eef2ff; color:#4f46e5; padding:4px 12px; border-radius:20px; font-size:0.78rem; font-weight:600;">📝 TXT</span>
            <span style="background:#eef2ff; color:#4f46e5; padding:4px 12px; border-radius:20px; font-size:0.78rem; font-weight:600;">📊 CSV</span>
            <span style="background:#eef2ff; color:#4f46e5; padding:4px 12px; border-radius:20px; font-size:0.78rem; font-weight:600;">📗 Excel (.xlsx/.xls)</span>
            <span style="background:#d1fae5; color:#065f46; padding:4px 12px; border-radius:20px; font-size:0.78rem; font-weight:600;">✅ Google Sheets → export as CSV/Excel</span>
        </div>

        <div class="file-selected" id="fileSelectedBox">
            <div style="font-size: 2rem; margin-bottom: 8px;">✅</div>
            <div class="file-name" id="fileNameDisplay"></div>
            <div style="color: var(--text-light); font-size: 0.85rem; margin-top: 5px;">Ready to scan</div>
        </div>

        <button class="scan-btn" id="scanBtn" onclick="startScan()" disabled>
            🔍 Scan Invoice
        </button>
    </div>

    <!-- Loading -->
    <div class="card" id="loadingState">
        <div class="spinner"></div>
        <p>AI is reading your invoice and matching products...</p>
        <p style="font-size: 0.8rem; margin-top: 10px;">This usually takes 2–5 seconds</p>
    </div>

    <!-- Results -->
    <div id="resultsSection">
        <div class="card">
            <h2>✅ Items Found</h2>
            <p>Review the matched items below. You can edit quantities and prices before adding to your purchase invoice.</p>

            <div class="alert-info">
                💡 Items with <strong>yellow</strong> badges were fuzzy-matched — double check they are correct. Items with <strong>red</strong> badges were not found in your system and will be skipped.
            </div>

            <table class="results-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Scanned Text</th>
                        <th>Matched Product</th>
                        <th>Match</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="resultsBody"></tbody>
            </table>

            <button class="confirm-btn" onclick="confirmItems()">
                ✅ Add Items to Purchase Invoice →
            </button>
        </div>
    </div>

</div>

<script>
    const supplierId = <?= $supplier_id ?>;

    function fileSelected(input) {
        if (input.files && input.files[0]) {
            document.getElementById('fileNameDisplay').textContent = input.files[0].name;
            document.getElementById('fileSelectedBox').style.display = 'block';
            document.getElementById('scanBtn').disabled = false;
        }
    }

    // Drag & drop logic
    const zone = document.getElementById('uploadZone');
    if (zone) {
        zone.addEventListener('dragover', e => { 
            e.preventDefault(); 
            zone.classList.add('dragover'); 
        });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file) {
                document.getElementById('fileInput').files = e.dataTransfer.files;
                fileSelected(document.getElementById('fileInput'));
            }
        });
    }

 function startScan() {
        const file = document.getElementById('fileInput').files[0];
        if (!file) return;

        // Hide upload panel, show loading animation
        document.getElementById('uploadCard').style.display = 'none';
        document.getElementById('loadingState').style.display = 'block';

        const formData = new FormData();
        formData.append('invoice', file);
        formData.append('supplier_id', supplierId);

        fetch('process_scan.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingState').style.display = 'none';

            if (data.error) {
                alert('Error: ' + data.error);
                document.getElementById('uploadCard').style.display = 'block';
                return;
            }

            // STRICT FILTER: Remove 'none' matches AND anything without a valid product ID
            const matchedOnly = data.items.filter(item => {
                return item.match_type !== 'none' && 
                       item.product_id !== null && 
                       item.product_id !== undefined && 
                       item.product_id !== '';
            });

            if (matchedOnly.length === 0) {
                alert('No products from this file match this supplier\'s catalog.');
                document.getElementById('uploadCard').style.display = 'block';
                return;
            }

            // Render only the strictly successful supplier matches
            renderResults(matchedOnly);
            document.getElementById('resultsSection').style.display = 'block';
        })
        .catch(err => {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('uploadCard').style.display = 'block';
            alert('Something went wrong. Please check your browser console.');
            console.error(err);
        });
    }

    function renderResults(items) {
        const tbody = document.getElementById('resultsBody');
        tbody.innerHTML = '';

        items.forEach((item, idx) => {
            const matchClass = item.match_type === 'exact' ? 'match-exact' :
                               item.match_type === 'fuzzy' ? 'match-fuzzy' : 'match-none';
            const matchLabel = item.match_type === 'exact' ? '✓ Exact' :
                               item.match_type === 'fuzzy' ? '~ Fuzzy' : '✗ Not Found';

            tbody.innerHTML += `
                <tr id="row_${idx}" ${item.match_type === 'none' ? 'style="opacity:0.5"' : ''}>
                    <td><small style="color:#94a3b8">${idx + 1}</small></td>
                    <td style="font-weight:500">${item.scanned_text}</td>
                    <td style="font-size:0.85rem; color:#475569">${item.matched_description || '—'}</td>
                    <td><span class="match-badge ${matchClass}">${matchLabel}</span></td>
                    <td><input type="number" class="qty-edit" value="${item.quantity}" min="1" id="qty_${idx}" ${item.match_type === 'none' ? 'disabled' : ''}></td>
                    <td><input type="number" class="price-edit" value="${item.unit_price}" step="0.01" min="0" id="price_${idx}" ${item.match_type === 'none' ? 'disabled' : ''}></td>
                    <td>
                        ${item.match_type !== 'none' ? `<button class="remove-row" onclick="removeRow(${idx})" title="Remove">×</button>` : ''}
                    </td>
                    <input type="hidden" id="pid_${idx}" value="${item.product_id}">
                    <input type="hidden" id="desc_${idx}" value="${item.matched_description}">
                    <input type="hidden" id="mtype_${idx}" value="${item.match_type}">
                </tr>
            `;
        });

        window.totalItems = items.length;
    }

    function removeRow(idx) {
        const row = document.getElementById('row_' + idx);
        if (row) row.remove();
    }

    function confirmItems() {
        const items = [];
        
        for (let i = 0; i < window.totalItems; i++) {
            const row = document.getElementById('row_' + i);
            if (!row) continue; 

            const matchType = document.getElementById('mtype_' + i).value;
            if (matchType === 'none') continue; 

            const productId = parseInt(document.getElementById('pid_' + i).value);
            const description = document.getElementById('desc_' + i).value;
            const quantity = parseInt(document.getElementById('qty_' + i).value);
            const unitPrice = parseFloat(document.getElementById('price_' + i).value);

            if (productId > 0 && quantity > 0) {
                items.push({
                    product_id: productId,
                    description: description,
                    quantity: quantity,
                    unit_price: unitPrice
                });
            }
        }

        if (items.length === 0) {
            alert('No valid matched products to add.');
            return;
        }

        fetch('apply_scan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                supplier_id: supplierId,
                items: items
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                alert('Error adding items: ' + data.error);
            } else if (data.success) {
                alert(`Successfully added ${data.added} items!`);
                window.location.href = 'purchase_pos.php?supplier_id=' + supplierId;
            }
        })
        .catch(err => {
            alert('Server error processing request.');
            console.error(err);
        });
    }
</script>
</body>
</html>