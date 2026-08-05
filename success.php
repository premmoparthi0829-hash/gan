<?php
/**
 * VK Logistics - Clean Booking Confirmation Page
 */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/booking-functions.php';

$ref = sanitize_input($_GET['ref'] ?? '');
$booking = null;

if (!empty($ref)) {
    $booking = get_booking_by_ref($ref);
}

if (!$booking && isset($_SESSION['last_booking'])) {
    $booking = $_SESSION['last_booking'];
    $ref = $booking['booking_reference'] ?? '';
}

$settings = get_all_settings();
$phone = escape_output($settings['support_phone'] ?? '+44 7700 900888');
$email = escape_output($settings['support_email'] ?? 'bappa@vklogistics.co.uk');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed | VK Logistics UK</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            background: #F8FAFC;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0F172A;
            margin: 0;
            padding: 0;
        }
        .success-header-banner {
            background: linear-gradient(135deg, #4A0B17 0%, #3B0612 100%);
            color: #FFFFFF;
            padding: 44px 20px 60px;
            text-align: center;
            border-bottom: 3px solid #D4AF37;
        }
        .success-header-banner h1 {
            color: #FFFFFF;
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 10px 0;
            letter-spacing: -0.5px;
        }
        .success-header-banner p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1rem;
            max-width: 580px;
            margin: 0 auto;
            line-height: 1.5;
        }
        .confirmation-card {
            max-width: 760px;
            margin: -36px auto 60px;
            background: #FFFFFF;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 12px 36px rgba(15, 23, 42, 0.08);
            padding: 36px 40px;
            box-sizing: border-box;
        }
        .ref-box {
            background: #FFFDF7;
            border: 2px dashed #D4AF37;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 32px;
        }
        .ref-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .ref-code {
            font-family: monospace;
            font-size: 1.75rem;
            font-weight: 800;
            color: #4A0B17;
            letter-spacing: 1px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
            text-align: left;
        }
        .details-group {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 20px;
        }
        .details-group h3 {
            font-size: 0.9rem;
            font-weight: 800;
            color: #4A0B17;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 14px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #CBD5E1;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
            margin-bottom: 8px;
            color: #334155;
        }
        .detail-item strong {
            color: #0F172A;
            font-weight: 700;
        }
        .btn-home {
            display: inline-block;
            background: #0F172A;
            color: #FFFFFF;
            padding: 13px 32px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        .btn-home:hover {
            background: #3B0612;
        }
        @media (max-width: 640px) {
            .confirmation-card { padding: 24px 20px; }
            .details-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Header Bar -->
    <header class="simple-header">
        <div class="simple-header-container">
            <div class="simple-brand">
                <a href="index.php" style="text-decoration:none;">
                    <h1 style="color:#FFFFFF; margin:0; font-size:1.3rem; font-weight:800;">VK LOGISTICS UK</h1>
                </a>
            </div>
            <div class="simple-actions">
                <a href="index.php" class="btn-simple-site">Book Another Idol</a>
            </div>
        </div>
    </header>

    <?php if ($booking): ?>

    <!-- Banner -->
    <div class="success-header-banner">
        <h1>Booking Confirmed</h1>
        <p>Thank you for shopping with VK Logistics! Your Ganesh Statue booking has been successfully placed.</p>
    </div>

    <!-- Main Confirmation Card -->
    <main style="padding: 0 16px;">
        <div class="confirmation-card">
            
            <!-- Reference Code -->
            <div class="ref-box">
                <div class="ref-label">Booking Reference Number</div>
                <div class="ref-code" id="booking-ref-code"><?php echo escape_output($booking['booking_reference']); ?></div>
                <button type="button" id="btn-copy-code" style="background:#FEF3C7; color:#92400E; border:1px solid #F59E0B; padding:6px 16px; border-radius:20px; font-size:0.8rem; font-weight:800; cursor:pointer; margin-top:10px;">
                    Copy Reference Code
                </button>
            </div>

            <!-- Details Grid -->
            <div class="details-grid">
                
                <!-- Customer & Delivery -->
                <div class="details-group">
                    <h3>Customer &amp; Delivery Details</h3>
                    <div class="detail-item">
                        <span>Full Name:</span>
                        <strong><?php echo escape_output($booking['customer_name']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>UK Mobile:</span>
                        <strong><?php echo escape_output($booking['mobile']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Email:</span>
                        <strong><?php echo escape_output($booking['email']); ?></strong>
                    </div>
                    <div class="detail-item" style="margin-top:12px; flex-direction:column; gap:4px;">
                        <span style="color:#64748B;">Delivery Address:</span>
                        <strong style="line-height:1.4;">
                            <?php echo escape_output($booking['address_line_1']); ?><br>
                            <?php if (!empty($booking['address_line_2'])): ?>
                                <?php echo escape_output($booking['address_line_2']); ?><br>
                            <?php endif; ?>
                            <?php echo escape_output($booking['city']); ?>, <?php echo escape_output($booking['postcode']); ?>, United Kingdom
                        </strong>
                    </div>
                </div>

                <!-- Payment & Order Summary -->
                <div class="details-group">
                    <h3>Order &amp; Payment Summary</h3>
                    <div class="detail-item">
                        <span>Quantity:</span>
                        <strong><?php echo (int)$booking['quantity']; ?> Statue(s)</strong>
                    </div>
                    <div class="detail-item">
                        <span>Total Amount:</span>
                        <strong style="color:#059669; font-size:1.05rem;">£<?php echo number_format($booking['total_amount'], 2); ?> GBP</strong>
                    </div>
                    <div class="detail-item">
                        <span>Payment Method:</span>
                        <strong style="text-transform:capitalize;"><?php echo str_replace('_', ' ', $booking['payment_method']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <span>Payment Status:</span>
                        <strong style="color:#B45309;"><?php echo escape_output($booking['payment_status']); ?></strong>
                    </div>
                </div>

            </div>

            <!-- Single Return Button -->
            <div style="text-align: center; margin-top: 10px;">
                <a href="index.php" class="btn-home">Return to Home</a>
            </div>

            <!-- Support Note -->
            <div style="margin-top:32px; font-size:0.82rem; color:#64748B; border-top:1px solid #E2E8F0; padding-top:18px; text-align:center;">
                Support Helpline: <strong><?php echo $phone; ?></strong> &bull; Email: <strong><?php echo $email; ?></strong>
            </div>

        </div>
    </main>

    <?php else: ?>

    <!-- Fallback Order Lookup -->
    <div class="success-header-banner">
        <h1>Booking Lookup</h1>
        <p>Enter your booking reference code below to view your details.</p>
    </div>

    <main style="padding: 0 16px;">
        <div class="confirmation-card" style="max-width:500px; margin-top:-20px;">
            <form action="success.php" method="GET">
                <div style="margin-bottom:20px; text-align:left;">
                    <label style="display:block; font-weight:700; margin-bottom:8px; color:#0F172A; font-size:0.9rem;">Booking Reference Code</label>
                    <input type="text" name="ref" placeholder="e.g. VKG-2026-52F7EF" required style="width:100%; padding:12px; border:2px solid #CBD5E1; border-radius:8px; font-size:1rem; font-family:monospace; box-sizing:border-box;">
                </div>
                <button type="submit" class="btn-home" style="width:100%; border:none; cursor:pointer;">
                    View Booking Details
                </button>
            </form>
        </div>
    </main>

    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnCopy = document.getElementById('btn-copy-code');
            if (btnCopy) {
                btnCopy.addEventListener('click', function() {
                    const code = document.getElementById('booking-ref-code').textContent;
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(code).then(() => {
                            btnCopy.textContent = 'Copied!';
                            setTimeout(() => { btnCopy.textContent = 'Copy Reference Code'; }, 2000);
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>
