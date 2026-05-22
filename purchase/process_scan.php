<?php
/**
 * process_scan.php
 * - TXT/CSV: FREE - reads file directly with PHP, no API needed
 * - Image/PDF: Needs Anthropic API key (set USE_MOCK=false + add key)
 */

session_start();
require '../vendor/autoload.php';
include("../config/db.php");

header('Content-Type: application/json');

// CONFIG - only needed for image/PDF scanning
$USE_MOCK      = true;
$ANTHROPIC_KEY = 'sk-ant-YOUR-KEY-HERE';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']); exit;
}

if (!isset($_FILES['invoice'])) {
    echo json_encode(['error' => 'No file uploaded']); exit;
}

$supplier_id = (int)($_POST['supplier_id'] ?? 0);

if ($supplier_id <= 0) {
    echo json_encode(['error' => 'No supplier selected. Please select a supplier first.']); exit;
}

// STEP 1: Load ONLY this supplier's products
$products = [];
$result = $conn->query("
    SELECT DISTINCT p.id, c.name as category, b.name as brand,
           v.value as variation, p.color, p.price
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN brands b ON p.brand_id = b.id
    JOIN variations v ON p.variation_id = v.id
    JOIN supplier_category_brands scb
         ON scb.category_id = p.category_id
         AND scb.brand_id = p.brand_id
    WHERE scb.supplier_id = $supplier_id
");

while ($row = $result->fetch_assoc()) {
    $description = trim($row['category'] . ' ' . $row['brand'] . ' ' . $row['variation']);
    if ($row['color']) $description .= ' ' . $row['color'];
    $products[] = [
        'id'          => $row['id'],
        'description' => $description,
        'category'    => $row['category'],
        'brand'       => $row['brand'],
        'variation'   => $row['variation'],
        'color'       => $row['color'],
        'price'       => $row['price'],
    ];
}

if (empty($products)) {
    echo json_encode(['error' => 'This supplier has no products linked. Go to Admin > Suppliers to assign products.']); exit;
}

// STEP 2: Get extracted items based on file type
$file     = $_FILES['invoice'];
$fileName = strtolower($file['name']);
$ext      = pathinfo($fileName, PATHINFO_EXTENSION);

if (in_array($ext, ['txt', 'csv'])) {

    $extracted_items = [];
    $lines = file($file['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $quantity   = 1;
        $unit_price = 0;
        $itemText   = $line;

        // Supported formats (price is always last, optional):
        // "master red dabbi, 20, 200"       → name, qty, price
        // "master red dabbi, 20"            → name, qty
        // "master red dabbi - 20 - 200"     → name, qty, price
        // "master red dabbi 20 200"         → name, qty, price (numbers at end)
        // "master red dabbi (20) 200"       → name, qty, price
        // "20 master red dabbi"             → qty, name

        // Split by comma first (most common clean format)
        $parts = array_map('trim', explode(',', $line));

        if (count($parts) >= 3) {
            // "name, qty, price"
            $itemText   = $parts[0];
            $quantity   = (int)$parts[1];
            $unit_price = (float)$parts[2];

        } elseif (count($parts) === 2) {
            // "name, qty"
            $itemText = $parts[0];
            $quantity = (int)$parts[1];

            // Check if qty part also has a price: "20 200" or "20, 200"
            $qtyParts = array_map('trim', preg_split('/[\s,]+/', $parts[1]));
            if (count($qtyParts) === 2 && is_numeric($qtyParts[0]) && is_numeric($qtyParts[1])) {
                $quantity   = (int)$qtyParts[0];
                $unit_price = (float)$qtyParts[1];
            }

        } else {
            // No commas — try other separators or patterns

            // "item - qty - price" or "item | qty | price"
            if (preg_match('/^(.+?)[\s]*[\-\|][\s]*(\d+)[\s]*[\-\|][\s]*(\d+(?:\.\d+)?)\s*$/', $line, $m)) {
                $itemText   = trim($m[1]);
                $quantity   = (int)$m[2];
                $unit_price = (float)$m[3];

            // "item qty price" — two numbers at end
            } elseif (preg_match('/^(.+?)\s+(\d+)\s+(\d+(?:\.\d+)?)\s*$/', $line, $m)) {
                $itemText   = trim($m[1]);
                $quantity   = (int)$m[2];
                $unit_price = (float)$m[3];

            // "item (qty) price"
            } elseif (preg_match('/^(.+?)\s*\((\d+)\)\s*(\d+(?:\.\d+)?)\s*$/', $line, $m)) {
                $itemText   = trim($m[1]);
                $quantity   = (int)$m[2];
                $unit_price = (float)$m[3];

            // "item - qty" or "item : qty"
            } elseif (preg_match('/^(.+?)[\s]*[\-\|:][\s]*(\d+)\s*$/', $line, $m)) {
                $itemText = trim($m[1]);
                $quantity = (int)$m[2];

            // "item (qty)"
            } elseif (preg_match('/^(.+?)\s*\((\d+)\)\s*$/', $line, $m)) {
                $itemText = trim($m[1]);
                $quantity = (int)$m[2];

            // "qty item" (number at start)
            } elseif (preg_match('/^(\d+)\s+(.+)$/', $line, $m)) {
                $quantity = (int)$m[1];
                $itemText = trim($m[2]);

            // "item qty" (single number at end)
            } elseif (preg_match('/^(.+?)\s+(\d+)$/', $line, $m)) {
                $itemText = trim($m[1]);
                $quantity = (int)$m[2];
            }
        }

        if ($quantity < 1) $quantity = 1;
        if ($unit_price < 0) $unit_price = 0;

        if (!empty($itemText)) {
            $extracted_items[] = [
                'text'       => $itemText,
                'quantity'   => $quantity,
                'unit_price' => $unit_price
            ];
        }
    }

    if (empty($extracted_items)) {
        echo json_encode(['error' => 'Could not read any items. Format each line as: "product name, quantity, price" e.g. "master red dabbi, 20, 200"']); exit;
    }

// ============================================================
// FREE METHOD: XLSX/XLS - no API needed, read with ZipArchive
// ============================================================
} elseif (in_array($ext, ['xlsx', 'xls'])) {

    $extracted_items = [];
    $zip = new ZipArchive();

    if ($zip->open($file['tmp_name']) !== true) {
        echo json_encode(['error' => 'Could not read Excel file. Try saving as CSV instead.']); exit;
    }

    $strings = [];
    if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        $dom = new DOMDocument();
        @$dom->loadXML($xml);
        foreach ($dom->getElementsByTagName('t') as $t) {
            $strings[] = $t->nodeValue;
        }
    }

    if (($xml = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
        $dom = new DOMDocument();
        @$dom->loadXML($xml);

        foreach ($dom->getElementsByTagName('row') as $rowNode) {
            $cells = [];
            foreach ($rowNode->getElementsByTagName('c') as $cell) {
                $t = $cell->getAttribute('t');
                $v = $cell->getElementsByTagName('v')->item(0);
                if ($v) {
                    $cells[] = ($t === 's') ? ($strings[(int)$v->nodeValue] ?? '') : $v->nodeValue;
                } else {
                    $cells[] = '';
                }
            }

            // Skip empty rows or header rows (no numbers)
            if (empty($cells)) continue;

            // Try col 0 = product, col 1 = qty
            $itemText = trim($cells[0] ?? '');
            $quantity = 1;

            if (isset($cells[1]) && is_numeric($cells[1]) && (int)$cells[1] > 0) {
                $quantity = (int)$cells[1];
            } elseif (isset($cells[2]) && is_numeric($cells[2]) && (int)$cells[2] > 0) {
                $quantity = (int)$cells[2]; // try col 2 as fallback
            }

            // Skip header rows or empty product names
            if (empty($itemText) || !preg_match('/[a-zA-Z]/', $itemText)) continue;

            $extracted_items[] = [
                'text'     => $itemText,
                'quantity' => $quantity
            ];
        }
    }
    $zip->close();

    if (empty($extracted_items)) {
        echo json_encode(['error' => 'Could not read items from Excel. Make sure Column A = product name, Column B = quantity.']); exit;
    }

// ============================================================
// IMAGE / PDF / FREE PARSER PROCESSING
// ============================================================
} else {
// === PDF HANDLING (INTEGRATED FIX) ===
if ($ext === 'pdf') {
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($file['tmp_name']);
        $text = $pdf->getText();

        // THE CRITICAL FIX: 
        // This regex removes newlines found inside double quotes
        // e.g., "master dabbi - Black\n" becomes "master dabbi - Black"
        $text = preg_replace_callback('/"([^"]+)"/', function($matches) {
            return '"' . str_replace(["\n", "\r"], "", $matches[1]) . '"';
        }, $text);

        $rawLines = explode("\n", $text);
        $extracted_items = [];

        foreach ($rawLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Clean up standard PDF CSV debris
            $line = str_replace(['"', 'Rs '], '', $line);
            
            // Standardize splitting
            $parts = array_map('trim', explode(',', $line));
            
            // Now your row logic works correctly:
            if (count($parts) >= 2) {
                $extracted_items[] = [
                    'text'       => $parts[0],
                    'quantity'   => (int)($parts[1] ?? 1),
                    'unit_price' => (float)($parts[2] ?? 0)
                ];
            }
        }
    } catch (\Throwable $e) {
        echo json_encode(['error' => 'PDF Extraction Error: ' . $e->getMessage()]);
        exit;
    }
}else {
        // === IMAGE HANDLING (MOCK OR API) ===
        if ($USE_MOCK) {
            sleep(2);
            $extracted_items = [];
            $mockProducts = array_slice($products, 0, 4);
            foreach ($mockProducts as $p) {
                $extracted_items[] = [
                    'text'     => $p['description'],
                    'quantity' => rand(2, 15), 'unit_price' => 0
                ];
            }
            $extracted_items[] = ['text' => 'unknown product xyz', 'quantity' => 2, 'unit_price' => 0];

        } else {
            // Real Anthropic API for images
            $fileData = base64_encode(file_get_contents($file['tmp_name']));
            $mimeType = $file['type'];

            $productList = implode("\n", array_map(function($p) {
                return "- ID:{$p['id']} | {$p['description']} | Price: Rs {$p['price']}";
            }, $products));

            $prompt = "You are a smart invoice reader for a paint/spray POS system in Pakistan.\n\nExtract ALL items from this invoice image. For each item return the product name as written and the quantity.\n\nSupplier products for reference:\n$productList\n\nReturn ONLY valid JSON:\n{\"items\": [{\"text\": \"item name\", \"quantity\": 5}]}";

            $requestBody = [
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 1000,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $fileData]],
                        ['type' => 'text',  'text'   => $prompt]
                    ]
                ]]
            ];

            $ch = curl_init('https://api.anthropic.com/v1/messages');
            // ... (keep your existing curl_setopt curl array settings here) ...
            $response     = curl_exec($ch);
            curl_close($ch);
            $responseData = json_decode($response, true);

            if (!isset($responseData['content'][0]['text'])) {
                echo json_encode(['error' => 'AI could not read the invoice. Please try again.']); exit;
            }

            $text   = preg_replace('/```json|```/', '', $responseData['content'][0]['text']);
            $parsed = json_decode(trim($text), true);

            if (!$parsed || !isset($parsed['items'])) {
                echo json_encode(['error' => 'Could not parse AI response. Please try again.']); exit;
            }

            $extracted_items = $parsed['items'];
        }
    }
}

// STEP 3: Fuzzy match extracted items to supplier's products
function matchProduct($text, $products) {
    $text = strtolower(trim($text));

    // 1. Perfect Exact Match check
    foreach ($products as $p) {
        if (strtolower($p['description']) === $text) {
            return ['product' => $p, 'type' => 'exact'];
        }
    }

    // 2. Typos & Spelling Mistake Tolerant Fuzzy Matching
    $words = preg_split('/[\s,\-]+/', $text);
    $words = array_filter($words, function($w) { return strlen($w) > 2; }); 
    
    $bestScore = 0;
    $bestMatch = null;

    foreach ($products as $p) {
        $pdesc = strtolower($p['description']);
        $pWords = preg_split('/[\s,\-]+/', $pdesc);
        
        $matchedWordsCount = 0;
        
        foreach ($words as $scannedWord) {
            foreach ($pWords as $dbWord) {
                // Calculate similarity percentage between the two words
                similar_text($scannedWord, $dbWord, $percent);
                
                // If the word matches by more than 75% (e.g., "whiteee" vs "white" is ~83%)
                if ($percent >= 75 || strpos($dbWord, $scannedWord) !== false || strpos($scannedWord, $dbWord) !== false) {
                    $matchedWordsCount++;
                    break; // Word found, move to next scanned word
                }
            }
        }
        
        if ($matchedWordsCount > $bestScore) {
            $bestScore = $matchedWordsCount;
            $bestMatch = $p;
        }
    }

    $totalWordsCount = count($words);
    
    // REQUIREMENT: Every descriptive word must match or be a typo of an existing word.
    // "paint master dabbi whiteee" -> matches 4/4 words (Whiteee matches White). Passes!
    // "paint master dabbi brown"   -> matches 3/4 words (Brown matches nothing). Blocked!
    if ($totalWordsCount > 0 && $bestScore >= $totalWordsCount && $bestMatch) {
        return ['product' => $bestMatch, 'type' => 'fuzzy'];
    }

    return ['product' => null, 'type' => 'none'];
}

// STEP 4: Build final result


$finalItems = [];
foreach ($extracted_items as $item) {
    $match = matchProduct($item['text'], $products);

    if ($match['type'] === 'none') {
        $finalItems[] = [
            'scanned_text'        => $item['text'],
            'matched_description' => null,
            'product_id'          => null,
            'quantity'            => $item['quantity'],
            'unit_price'          => 0,
            'match_type'          => 'none',
        ];
    } else {
        $p = $match['product'];
        $finalItems[] = [
            'scanned_text'        => $item['text'],
            'matched_description' => $p['description'],
            'product_id'          => $p['id'],
            'quantity'            => $item['quantity'],
            'unit_price'          => isset($item['unit_price']) && $item['unit_price'] > 0 ? $item['unit_price'] : $p['price'],
            'match_type'          => $match['type'],
        ];
    }
}

echo json_encode(['items' => $finalItems]);