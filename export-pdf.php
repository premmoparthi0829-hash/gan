<?php
/**
 * VK Logistics - High Resolution PDF & Print Report Generator
 */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/booking-functions.php';

// Check admin authentication
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

$db = Database::getConnection();
$bookings = [];

if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM bookings ORDER BY id DESC");
        $bookings = $stmt->fetchAll();
    } catch (Exception $e) {
        log_system_error("PDF export query error: " . $e->getMessage());
    }
} elseif (isset($_SESSION['last_booking'])) {
    $bookings[] = $_SESSION['last_booking'];
}

$total_bookings = count($bookings);
$total_revenue = 0.0;
$paid_revenue = 0.0;
$paid_count = 0;
$pending_count = 0;

foreach ($bookings as $b) {
    $amt = (float)($b['total_amount'] ?? 0);
    $total_revenue += $amt;
    $pstat = strtoupper($b['payment_status'] ?? '');
    if ($pstat === 'PAID') {
        $paid_revenue += $amt;
        $paid_count++;
    } else {
        $pending_count++;
    }
}

$generated_at = date('d M Y, H:i T');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK Logistics - Bookings &amp; Revenue Report (PDF)</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0F172A;
            background: #FFFFFF;
            margin: 0;
            padding: 20px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .pdf-header {
            background: #3B0612 !important;
            color: #FFFFFF !important;
            padding: 24px 30px;
            border-radius: 12px;
            border-bottom: 4px solid #D4AF37;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .pdf-brand h1 {
            color: #FFFFFF !important;
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0 0 4px 0;
            letter-spacing: 0.5px;
        }

        .pdf-brand p {
            color: #D4AF37 !important;
            font-size: 0.88rem;
            margin: 0;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .pdf-meta {
            text-align: right;
            font-size: 0.82rem;
            color: #E2E8F0;
            line-height: 1.5;
        }

        /* KPI Cards Summary Row */
        .pdf-kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .pdf-kpi-card {
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            padding: 16px;
            text-align: left;
        }

        .pdf-kpi-card.gold { border-left: 4px solid #D4AF37; }
        .pdf-kpi-card.green { border-left: 4px solid #10B981; }
        .pdf-kpi-card.amber { border-left: 4px solid #F59E0B; }
        .pdf-kpi-card.maroon { border-left: 4px solid #3B0612; }

        .pdf-kpi-val {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0F172A;
            margin-bottom: 4px;
        }

        .pdf-kpi-lbl {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Data Table */
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-bottom: 24px;
        }

        .pdf-table th {
            background: #3B0612 !important;
            color: #FFFFFF !important;
            font-weight: 800;
            text-transform: uppercase;
            padding: 10px 8px;
            border-bottom: 2px solid #D4AF37;
            font-size: 0.75rem;
            letter-spacing: 0.4px;
        }

        .pdf-table td {
            padding: 9px 8px;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: middle;
            color: #1E293B;
        }

        .pdf-table tbody tr:nth-child(even) {
            background: #F8FAFC !important;
        }

        .badge-paid {
            background: #D1FAE5 !important;
            color: #065F46 !important;
            border: 1px solid #34D399;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #FEF3C7 !important;
            color: #92400E !important;
            border: 1px solid #FBBF24;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .pdf-footer {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #CBD5E1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: #64748B;
        }

        .no-print-bar {
            background: #FEF3C7;
            border: 1.5px solid #F59E0B;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-print-now {
            background: #3B0612;
            color: #FFFFFF;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 0.9rem;
        }

        @media print {
            .no-print-bar { display: none !important; }
            body { padding: 0 !important; }
        }
    </style>
</head>
<body>

    <!-- Non-printable top action bar -->
    <div class="no-print-bar">
        <div style="font-size:0.9rem; color:#92400E; font-weight:700;">
            📄 Official PDF Report View • Ready to Save or Print
        </div>
        <div style="display:flex; gap:12px;">
            <button type="button" class="btn-print-now" onclick="window.print();">
                🖨️ Save as PDF / Print Report
            </button>
            <a href="admin.php" style="background:#FFFFFF; color:#0F172A; border:1px solid #CBD5E1; padding:9px 18px; border-radius:8px; font-weight:700; text-decoration:none; font-size:0.88rem;">
                Back to Admin
            </a>
        </div>
    </div>

    <!-- Official PDF Header -->
    <div class="pdf-header">
        <div class="pdf-brand">
            <h1>VK LOGISTICS UK</h1>
            <p>Official Ganesh Statue Bookings &amp; Revenue Report</p>
        </div>
        <div class="pdf-meta">
            <div>Report Date: <strong><?php echo $generated_at; ?></strong></div>
            <div>Generated By: <strong>Admin Operations</strong></div>
            <div>Status: <strong>Confidential / Verified</strong></div>
        </div>
    </div>

    <!-- Executive KPI Summary Cards -->
    <div class="pdf-kpi-row">
        <div class="pdf-kpi-card maroon">
            <div class="pdf-kpi-val"><?php echo $total_bookings; ?></div>
            <div class="pdf-kpi-lbl">Total Bookings Count</div>
        </div>
        <div class="pdf-kpi-card gold">
            <div class="pdf-kpi-val">£<?php echo number_format($total_revenue, 2); ?></div>
            <div class="pdf-kpi-lbl">Gross Revenue (£ GBP)</div>
        </div>
        <div class="pdf-kpi-card green">
            <div class="pdf-kpi-val">£<?php echo number_format($paid_revenue, 2); ?> (<?php echo $paid_count; ?>)</div>
            <div class="pdf-kpi-lbl">Paid Revenue</div>
        </div>
        <div class="pdf-kpi-card amber">
            <div class="pdf-kpi-val"><?php echo $pending_count; ?></div>
            <div class="pdf-kpi-lbl">Pending Verifications</div>
        </div>
    </div>

    <!-- Data Table -->
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="text-align:left;">Booking Ref</th>
                <th style="text-align:left;">Customer Name</th>
                <th style="text-align:left;">Mobile</th>
                <th style="text-align:left;">UK Delivery Address</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Total (£)</th>
                <th style="text-align:center;">Method</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <?php 
                    $pstat = strtoupper($b['payment_status'] ?? 'PENDING VERIFY');
                    $is_paid = ($pstat === 'PAID');
                ?>
                <tr>
                    <td style="font-family:monospace; font-weight:800; color:#3B0612; white-space:nowrap;"><?php echo escape_output($b['booking_reference']); ?></td>
                    <td style="font-weight:700; color:#0F172A;"><?php echo escape_output($b['customer_name']); ?></td>
                    <td style="white-space:nowrap;"><?php echo escape_output($b['mobile']); ?></td>
                    <td>
                        <?php echo escape_output($b['address_line_1']); ?>, 
                        <?php echo escape_output($b['city']); ?>, 
                        <strong style="font-family:monospace;"><?php echo escape_output($b['postcode']); ?></strong>
                    </td>
                    <td style="text-align:center; font-weight:800;"><?php echo (int)$b['quantity']; ?></td>
                    <td style="text-align:right; font-weight:800; color:#059669;">£<?php echo number_format((float)$b['total_amount'], 2); ?></td>
                    <td style="text-align:center; font-size:0.78rem; font-weight:600;"><?php echo ($b['payment_method'] === 'paypal') ? 'PayPal' : (($b['payment_method'] === 'bank_transfer') ? 'Bank Transfer' : escape_output(str_replace('_', ' ', $b['payment_method']))); ?></td>
                    <td style="text-align:center;">
                        <span class="<?php echo $is_paid ? 'badge-paid' : 'badge-pending'; ?>">
                            <?php echo $is_paid ? 'PAID' : 'PENDING VERIFY'; ?>
                        </span>
                    </td>
                    <td style="text-align:center; font-size:0.78rem; color:#475569; white-space:nowrap;"><?php echo substr($b['created_at'] ?? '', 0, 10); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- PDF Footer Signature & Stamp -->
    <div class="pdf-footer">
        <div>
            Official VK Logistics UK Operations Document &bull; Confidential &bull; Generated <?php echo $generated_at; ?>
        </div>
        <div style="font-weight:700; color:#3B0612;">
            Authorized Signature: _______________________
        </div>
    </div>

    <script>
        // Auto trigger print dialog on page load
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>

</body>
</html>
