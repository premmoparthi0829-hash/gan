<?php
/**
 * VK Logistics - Web Spreadsheet View for Google Sheets & Excel
 */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/booking-functions.php';

// Check admin session
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

$db = Database::getConnection();
$bookings = [];

if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM bookings ORDER BY id DESC");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        log_system_error("Web Sheet query error: " . $e->getMessage());
    }
} elseif (isset($_SESSION['last_booking'])) {
    $bookings[] = $_SESSION['last_booking'];
}

$total_count = count($bookings);
$total_rev = 0.0;
$paid_count = 0;

foreach ($bookings as $b) {
    $amt = (float)($b['total_amount'] ?? 0);
    $total_rev += $amt;
    if (strtoupper($b['payment_status'] ?? '') === 'PAID') {
        $paid_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK Logistics Bookings - Google Sheets Web View</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #F1F5F9;
            color: #0F172A;
            margin: 0;
            padding: 0;
        }

        /* Google Sheets Style Header Toolbar */
        .sheets-toolbar {
            background: #0F5132;
            color: #FFFFFF;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .sheets-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sheets-title h1 {
            font-size: 1.2rem;
            font-weight: 800;
            margin: 0;
            color: #FFFFFF;
        }

        .sheets-badge {
            background: #D1FAE5;
            color: #065F46;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
        }

        .btn-tb {
            background: #FFFFFF;
            color: #0F5132;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-tb:hover {
            background: #D1FAE5;
        }

        .btn-tb-dark {
            background: #0B291A;
            color: #FFFFFF;
        }

        /* Container & Table */
        .sheets-container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .sheets-card {
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
            border: 1px solid #CBD5E1;
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .web-sheet-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
            text-align: left;
        }

        .web-sheet-table th {
            background: #10B981 !important;
            color: #FFFFFF !important;
            font-weight: 800;
            text-transform: uppercase;
            padding: 12px 10px;
            border-bottom: 2px solid #065F46;
            white-space: nowrap;
            letter-spacing: 0.3px;
            font-size: 0.78rem;
        }

        .web-sheet-table td {
            padding: 10px;
            border-bottom: 1px solid #E2E8F0;
            border-right: 1px solid #F1F5F9;
            color: #1E293B;
            vertical-align: middle;
        }

        .web-sheet-table tr:nth-child(even) {
            background: #F8FAFC;
        }

        .web-sheet-table tr:hover {
            background: #ECFDF5;
        }

        .status-paid {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #34D399;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.72rem;
        }

        .status-pending {
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FBBF24;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.72rem;
        }

        /* Summary Footer */
        .sheets-footer-bar {
            background: #F8FAFC;
            border-top: 1px solid #CBD5E1;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #475569;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <!-- Header Toolbar -->
    <div class="sheets-toolbar">
        <div class="sheets-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            <h1>VK Logistics — Google Sheets Web View</h1>
            <span class="sheets-badge"><?php echo $total_count; ?> Records Loaded</span>
        </div>
        <div class="toolbar-actions">
            <a href="ajax/admin-actions.php?action=export_csv" class="btn-tb">
                📥 Download CSV File
            </a>
            <button type="button" class="btn-tb btn-tb-dark" id="btn-copy-tsv">
                📋 Copy Data to Paste in Google Sheets
            </button>
            <a href="admin.php" class="btn-tb" style="background:transparent; color:#FFFFFF; border:1px solid rgba(255,255,255,0.4);">
                ← Back to Admin
            </a>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="sheets-container">
        <div class="sheets-card">
            <div class="table-responsive">
                <table class="web-sheet-table" id="sheet-data-table">
                    <thead>
                        <tr>
                            <th>Booking Ref</th>
                            <th>Customer Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Postcode</th>
                            <th style="text-align:center;">Qty</th>
                            <th style="text-align:right;">Total (£)</th>
                            <th style="text-align:center;">Method</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:center;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <?php $is_paid = (strtoupper($b['payment_status'] ?? '') === 'PAID'); ?>
                            <tr>
                                <td style="font-family:monospace; font-weight:800; color:#0F5132;"><?php echo escape_output($b['booking_reference']); ?></td>
                                <td style="font-weight:700;"><?php echo escape_output($b['customer_name']); ?></td>
                                <td><?php echo escape_output($b['mobile']); ?></td>
                                <td><?php echo escape_output($b['email']); ?></td>
                                <td><?php echo escape_output($b['address_line_1']); ?></td>
                                <td><?php echo escape_output($b['city']); ?></td>
                                <td style="font-family:monospace; font-weight:700;"><?php echo escape_output($b['postcode']); ?></td>
                                <td style="text-align:center; font-weight:800;"><?php echo (int)$b['quantity']; ?></td>
                                <td style="text-align:right; font-weight:800; color:#059669;">£<?php echo number_format((float)$b['total_amount'], 2); ?></td>
                                <td style="text-align:center; text-transform:capitalize;"><?php echo str_replace('_', ' ', $b['payment_method']); ?></td>
                                <td style="text-align:center;">
                                    <span class="<?php echo $is_paid ? 'status-paid' : 'status-pending'; ?>">
                                        <?php echo $is_paid ? 'PAID' : 'PENDING VERIFY'; ?>
                                    </span>
                                </td>
                                <td style="text-align:center; font-size:0.8rem; color:#64748B;"><?php echo substr($b['created_at'] ?? '', 0, 10); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="sheets-footer-bar">
                <div>
                    Total Bookings: <strong><?php echo $total_count; ?></strong> &bull; Total Revenue: <strong style="color:#059669;">£<?php echo number_format($total_rev, 2); ?> GBP</strong>
                </div>
                <div>
                    Paid Orders: <strong><?php echo $paid_count; ?></strong> &bull; Pending Verifications: <strong><?php echo ($total_count - $paid_count); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('btn-copy-tsv').addEventListener('click', function() {
            const btn = this;
            fetch('ajax/admin-actions.php?action=export_tsv')
                .then(res => res.text())
                .then(tsvText => {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(tsvText).then(() => {
                            btn.textContent = '✓ Copied to Clipboard!';
                            alert('📊 Booking dataset copied to clipboard!\n\nNow open your Google Sheet and press Cmd+V (or Ctrl+V) inside Cell A1 to paste all rows into columns instantly!');
                            window.open('https://sheets.new', '_blank');
                            setTimeout(() => { btn.innerHTML = '📋 Copy Data to Paste in Google Sheets'; }, 3000);
                        });
                    }
                });
        });
    </script>
</body>
</html>
