<?php
/**
 * VK Logistics - Single-Page Admin Management Dashboard
 * Theme: Festive Royal Maroon (#4A0B17) & Gold (#D4AF37)
 */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/booking-functions.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    admin_logout();
    header('Location: admin.php');
    exit;
}

$is_logged_in     = is_admin_logged_in();
$csrf_token       = get_csrf_token();
$settings         = get_all_settings();
$dash_data          = get_dashboard_data_array();
$stats              = $dash_data['stats'];
$initial_bookings   = $dash_data['bookings'];

$db_conn            = Database::getConnection();
$catalog_categories = $db_conn ? $db_conn->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll() : [];
$catalog_products   = $db_conn ? $db_conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK Logistics | Admin Management Portal</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
</head>
<body class="admin-body">

    <!-- MEDIUM FLOATING BOX REFERENCE DESIGN -->
    <div class="saleskip-auth-overlay" id="admin-login-screen" style="<?php echo $is_logged_in ? 'display:none;' : 'display:flex;'; ?>">
        <div class="saleskip-medium-card">
            <!-- Left Hero Pane (52% Width) -->
            <div class="saleskip-hero-pane">
                <!-- Rotated Wireframe Frames -->
                <div class="saleskip-wireframe-container">
                    <div class="wireframe-rect wireframe-rect-1"></div>
                    <div class="wireframe-rect wireframe-rect-2"></div>
                    <div class="wireframe-rect wireframe-rect-3"></div>
                    <div class="wireframe-rect wireframe-rect-4"></div>
                </div>

                <!-- Top Asterisk / Burst Icon -->
                <div class="saleskip-asterisk-icon">
                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="2" x2="12" y2="22"></line>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                        <line x1="4.93" y1="19.07" x2="19.07" y2="4.93"></line>
                    </svg>
                </div>

                <!-- Hero Content -->
                <div class="saleskip-hero-content">
                    <h1 class="saleskip-text-hello">Hello</h1>
                    <h1 class="saleskip-text-brand">
                        <span>Ganesh Kit!</span>
                        <span class="saleskip-wave-hand">&#128075;</span>
                    </h1>
                    <p class="saleskip-text-body">UK Ganesh Idol Booking &amp; Logistics Portal. Manage customer orders, track delivery dispatches, and update store settings effortlessly.</p>
                </div>

                <!-- Copyright Footer -->
                <div class="saleskip-copyright">
                    &copy; 2026 VK Logistics. All rights reserved.
                </div>
            </div>

            <!-- Right Form Pane (48% Width) -->
            <div class="saleskip-form-pane">
                <h2 class="saleskip-brand-top">VK Logistics</h2>

                <div class="saleskip-form-wrapper">
                    <h3 class="saleskip-title-welcome">Welcome Back!</h3>
                    <p class="saleskip-sub-text">Enter your password to access your dashboard.</p>

                    <form id="admin-login-form">
                        <div class="saleskip-field-group" style="position: relative;">
                            <input type="password" id="admin-passcode-input" class="saleskip-underline-input" placeholder="Password" required autofocus autocomplete="current-password" style="padding-right: 42px !important;">
                            <button type="button" id="toggle-password-btn" class="password-eye-toggle" title="Toggle password visibility" style="position: absolute; right: 8px; bottom: 8px; background: none; border: none; cursor: pointer; color: #64748B; padding: 4px; display: flex; align-items: center; justify-content: center; transition: color 0.2s;">
                                <svg id="eye-icon-show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg id="eye-icon-hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        
                        <button type="submit" class="saleskip-btn-primary" id="btn-login-submit">Login Now</button>
                    </form>

                    <div id="login-error-msg" style="color: #DC2626; font-size: 0.85rem; font-weight: 600; margin-top: 12px; text-align: center; display: none;"></div>

                    <div class="saleskip-bottom-link">
                        Default Password: <strong>admin123</strong>
                    </div>
                </div>

                <div style="font-size: 0.75rem; color: #94A3B8;">
                    VK Logistics UK Console &bull; Protected Access
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN ADMIN DASHBOARD -->
    <div id="admin-main-app" style="<?php echo $is_logged_in ? 'display:block;' : 'display:none;'; ?>">
        
        <!-- Clean Simple Header -->
        <header class="simple-header">
            <div class="simple-header-container">
                <div class="simple-brand">
                    <h1>VK Logistics Admin</h1>
                </div>
                <div class="simple-actions">
                    <a href="index.php" target="_blank" class="btn-simple-site">View Site &#8599;</a>
                    <button type="button" class="btn-simple-logout" id="btn-admin-logout">Logout</button>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="admin-main-container" style="max-width: 1440px; margin: 0 auto; padding: 24px 20px 60px 20px;">
            
            <!-- Dynamic Summary Cards Top Bar -->
            <div class="admin-kpi-grid" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; margin-bottom: 24px;">
                <div class="admin-kpi-card gold" style="border-left: 4px solid #D4AF37;">
                    <div class="admin-kpi-icon">&#128197;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-today-orders" style="font-size: 1.6rem; font-weight: 800; color: #0F172A; margin: 0;"><?php echo $stats['today_orders']; ?></h3>
                        <p style="font-size: 0.82rem; font-weight: 700; color: #64748B; margin-top: 4px;">Today Orders</p>
                    </div>
                </div>
                <div class="admin-kpi-card saffron" style="border-left: 4px solid #F59E0B;">
                    <div class="admin-kpi-icon">&#127991;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-total-categories" style="font-size: 1.6rem; font-weight: 800; color: #0F172A; margin: 0;"><?php echo $stats['total_categories']; ?></h3>
                        <p style="font-size: 0.82rem; font-weight: 700; color: #64748B; margin-top: 4px;">Total Categories</p>
                    </div>
                </div>
                <div class="admin-kpi-card saffron" style="border-left: 4px solid #8B5CF6;">
                    <div class="admin-kpi-icon">&#128717;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-total-products" style="font-size: 1.6rem; font-weight: 800; color: #0F172A; margin: 0;"><?php echo $stats['total_products']; ?></h3>
                        <p style="font-size: 0.82rem; font-weight: 700; color: #64748B; margin-top: 4px;">Total Products</p>
                    </div>
                </div>
                <div class="admin-kpi-card green" style="border-left: 4px solid #10B981;">
                    <div class="admin-kpi-icon">&#128176;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-total-revenue" style="font-size: 1.6rem; font-weight: 800; color: #0F172A; margin: 0;">&pound;<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p style="font-size: 0.82rem; font-weight: 700; color: #64748B; margin-top: 4px;">Total Revenue</p>
                    </div>
                </div>
                <div class="admin-kpi-card gold" style="border-left: 4px solid #3B82F6;">
                    <div class="admin-kpi-icon">&#128230;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-total-bookings" style="font-size: 1.6rem; font-weight: 800; color: #0F172A; margin: 0;"><?php echo $stats['total_bookings']; ?></h3>
                        <p style="font-size: 0.82rem; font-weight: 700; color: #64748B; margin-top: 4px;">Total Bookings</p>
                    </div>
                </div>
                <div class="admin-kpi-card saffron" style="border-left: 4px solid #EF4444;">
                    <div class="admin-kpi-icon">&#9203;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-pending-count" style="font-size: 1.6rem; font-weight: 800; color: #0F172A; margin: 0;"><?php echo $stats['pending_count']; ?></h3>
                        <p style="font-size: 0.82rem; font-weight: 700; color: #64748B; margin-top: 4px;">Pending Verify</p>
                    </div>
                </div>
            </div>

            <!-- Single Page Tabs Nav -->
            <div class="admin-tab-nav">
                <button type="button" class="admin-tab-btn active" data-tab="tab-bookings" onclick="switchAdminTab('tab-bookings', this)">
                    &#128221; Bookings Management
                </button>
                <button type="button" class="admin-tab-btn" data-tab="tab-upi" onclick="switchAdminTab('tab-upi', this)">
                    📱 UPI &amp; Bank Payments
                </button>
                <button type="button" class="admin-tab-btn" data-tab="tab-export" onclick="switchAdminTab('tab-export', this)">
                    📄 PDF &amp; CSV Reports
                </button>
                <button type="button" class="admin-tab-btn" data-tab="tab-catalog" onclick="switchAdminTab('tab-catalog', this)">
                    🛍️ Products &amp; Categories
                </button>
            </div>

            <!-- TAB 1: BOOKINGS MANAGEMENT -->
            <div class="admin-tab-content admin-panel-card" id="tab-bookings">
                <div class="admin-toolbar">
                    <div class="admin-search-box">
                        <span class="admin-search-icon">&#128269;</span>
                        <input type="text" id="booking-search-input" class="admin-search-input" placeholder="Search by reference, customer name, mobile, email, postcode...">
                    </div>
                    <div class="admin-filter-group">
                        <button type="button" class="btn-filter active" data-status="ALL">All</button>
                        <button type="button" class="btn-filter" data-status="PAID">Paid</button>
                        <button type="button" class="btn-filter" data-status="PAYMENT VERIFICATION PENDING">Pending</button>
                        <button type="button" class="btn-filter" data-status="SHIPPED">Shipped</button>
                        <button type="button" class="btn-filter" data-status="CANCELLED">Cancelled</button>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="white-space:nowrap; text-align:center; width: 40px;">#</th>
                                <th style="white-space:nowrap; text-align:left;">Booking Ref / Date</th>
                                <th style="text-align:left;">Customer &amp; Contact</th>
                                <th style="text-align:left;">Delivery Area</th>
                                <th style="white-space:nowrap; text-align:center;">Qty</th>
                                <th style="white-space:nowrap; text-align:right;">Total (£)</th>
                                <th style="white-space:nowrap; text-align:center;">Payment Details</th>
                                <th style="white-space:nowrap; text-align:center;">Fulfillment</th>
                                <th style="white-space:nowrap; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookings-table-body">
                            <?php if (empty($initial_bookings)): ?>
                                <tr>
                                    <td colspan="9" style="text-align:center; padding: 30px; color: var(--color-text-muted);">
                                        No bookings found in database.
                                    </td>
                                </tr>
                            <?php else: 
                                $serialNo = count($initial_bookings);
                                foreach ($initial_bookings as $b):
                                    $pStatusDisplay = $b['payment_status'];
                                    if ($pStatusDisplay === 'PAYMENT VERIFICATION PENDING') {
                                        $pStatusDisplay = 'PENDING VERIFY';
                                    }

                                    $payBadge = '<span class="status-pill status-pending" title="' . escape_output($b['payment_status']) . '">' . escape_output($pStatusDisplay) . '</span>';
                                    if ($b['payment_status'] === 'PAID') {
                                        $payBadge = '<span class="status-pill status-paid">PAID</span>';
                                    } elseif ($b['payment_status'] === 'FAILED' || $b['payment_status'] === 'CANCELLED') {
                                        $payBadge = '<span class="status-pill status-cancelled">' . escape_output($b['payment_status']) . '</span>';
                                    }

                                    $bBadge = '<span class="status-pill status-pending">' . escape_output($b['booking_status'] ?? 'CONFIRMED') . '</span>';
                                    if (($b['booking_status'] ?? '') === 'SHIPPED' || ($b['booking_status'] ?? '') === 'DELIVERED') {
                                        $bBadge = '<span class="status-pill status-shipped">' . escape_output($b['booking_status']) . '</span>';
                                    }

                                    $receiptCell = '';
                                    if (!empty($b['payment_proof_image'])) {
                                        $receiptCell = '<div style="margin-top:5px;"><button type="button" class="btn-view-receipt btn-open-hd-modal" data-img="' . escape_output($b['payment_proof_image']) . '" data-ref="' . escape_output($b['booking_reference']) . '" style="background:#FEF3C7 !important; border:1px solid #F59E0B !important; color:#B45309 !important; font-size:0.7rem !important; font-weight:800 !important; padding:2px 6px !important; border-radius:4px !important; cursor:pointer;">📷 Receipt</button></div>';
                                    }
                            ?>
                                <tr>
                                    <td style="text-align:center; font-weight:700; color:#475569; vertical-align:top; padding-top:12px;"><?php echo $serialNo--; ?></td>
                                    <td style="white-space:nowrap; vertical-align:top; padding-top:12px;">
                                        <strong style="color:#4A0B17; font-size:0.86rem; font-family:monospace; letter-spacing:-0.2px; display:block;"><?php echo escape_output($b['booking_reference']); ?></strong>
                                        <span style="font-size:0.72rem; color:#64748B; font-weight:600; display:block; margin-top:2px;"><?php echo substr($b['created_at'] ?? '', 0, 10); ?></span>
                                    </td>
                                    <td style="min-width:130px; vertical-align:top; padding-top:12px;">
                                        <div><strong style="color:#0F172A; font-size:0.88rem;"><?php echo escape_output($b['customer_name']); ?></strong></div>
                                        <div style="font-size:0.78rem; color:#475569; font-weight:600; margin-top:2px;"><?php echo escape_output($b['mobile']); ?></div>
                                    </td>
                                    <td style="max-width:160px; font-size:0.82rem; color:#334155; line-height:1.35; vertical-align:top; padding-top:12px;">
                                        <?php echo escape_output($b['city']); ?>, <strong style="color:#0F172A;"><?php echo escape_output($b['postcode']); ?></strong>
                                    </td>
                                    <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;"><strong style="color:#0F172A; font-size:0.95rem;"><?php echo $b['quantity']; ?></strong></td>
                                    <td style="text-align:right; white-space:nowrap; vertical-align:top; padding-top:12px;"><strong style="color:#0F172A; font-size:0.95rem;">&pound;<?php echo number_format((float)$b['total_amount'], 2); ?></strong></td>
                                    <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;">
                                        <div style="font-size:0.75rem; font-weight:800; color:#475569; margin-bottom:3px;">
                                            <?php echo ($b['payment_method'] === 'paypal' ? 'PayPal' : ($b['payment_method'] === 'bank_transfer' ? 'Bank' : escape_output($b['payment_method']))); ?>
                                        </div>
                                        <?php echo $payBadge; ?>
                                        <?php echo $receiptCell; ?>
                                    </td>
                                    <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;"><?php echo $bBadge; ?></td>
                                    <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px; display: flex; gap: 4px; justify-content: center; align-items: center; border: none;">
                                        <button type="button" class="btn-action-sm btn-view-items" 
                                            data-ref="<?php echo escape_output($b['booking_reference']); ?>"
                                            style="background:#D97706 !important; color:#fff !important; border-color:#D97706 !important; padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                            📦 Items (<?php echo isset($b['items']) ? count($b['items']) : $b['quantity']; ?>)
                                        </button>
                                        <button type="button" class="btn-action-sm btn-view-booking" 
                                            data-ref="<?php echo escape_output($b['booking_reference']); ?>"
                                            style="background:#0F172A !important; color:#fff !important; border-color:#0F172A !important; padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                            View 👁️
                                        </button>
                                        <button type="button" class="btn-action-sm btn-edit-booking" 
                                            data-ref="<?php echo escape_output($b['booking_reference']); ?>" 
                                            data-pstat="<?php echo escape_output($b['payment_status']); ?>" 
                                            data-bstat="<?php echo escape_output($b['booking_status'] ?? 'CONFIRMED'); ?>"
                                            data-pmeth="<?php echo escape_output($b['payment_method'] ?? 'bank_transfer'); ?>"
                                            data-pref="<?php echo escape_output($b['payment_reference'] ?? $b['paypal_transaction_id'] ?? ''); ?>"
                                            style="padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                            Edit ✏️
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB UPI: UPI & BANK PAYMENT SETTINGS & VERIFICATION PANEL -->
            <div class="admin-tab-content admin-panel-card" id="tab-upi" style="display:none; padding: 28px;">
                
                <!-- Section Top Header & Mode Badge Banner -->
                <div style="background: linear-gradient(135deg, #4A0B17 0%, #7A1228 100%); color: #FFFFFF; border-radius: 14px; padding: 24px; margin-bottom: 28px; box-shadow: 0 10px 25px rgba(74, 11, 23, 0.2); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                            <span style="font-size: 1.8rem;">📱</span>
                            <h2 style="font-size: 1.5rem; color: #FFFFFF; margin: 0; font-weight: 800; font-family: 'Outfit', sans-serif;">UPI &amp; Bank Payment Gateway Configuration</h2>
                        </div>
                        <p style="color: rgba(255, 255, 255, 0.85); font-size: 0.9rem; margin: 0; max-width: 720px; line-height: 1.4;">
                            Manage Admin UPI details, Direct Bank Transfer credentials, support helpline phone, and verify customer payment screenshots.
                        </p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px; background: rgba(255, 255, 255, 0.15); padding: 10px 18px; border-radius: 10px; backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.2);">
                        <span style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #FFFFFF;">Gateways:</span>
                        <?php $upi_sett = get_upi_settings(); ?>
                        <span id="upi-status-badge" style="<?php echo ($upi_sett['upi_enabled'] ?? '1') === '1' ? 'background: #10B981; color: #FFFFFF;' : 'background: #EF4444; color: #FFFFFF;'; ?> padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                            <?php echo ($upi_sett['upi_enabled'] ?? '1') === '1' ? '🟢 UPI ACTIVE' : '🔴 UPI OFF'; ?>
                        </span>
                        <span id="bank-status-badge" style="<?php echo ($upi_sett['bank_enabled'] ?? '1') === '1' ? 'background: #3B82F6; color: #FFFFFF;' : 'background: #EF4444; color: #FFFFFF;'; ?> padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;">
                            <?php echo ($upi_sett['bank_enabled'] ?? '1') === '1' ? '🟢 BANK ACTIVE' : '🔴 BANK OFF'; ?>
                        </span>
                    </div>
                </div>

                <!-- ADMIN UPI & BANK PAYMENT SETTINGS FORM -->
                <form id="admin-upi-settings-form" enctype="multipart/form-data" style="margin-bottom: 36px;">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                    <input type="hidden" name="current_qr_image" value="<?php echo escape_output($upi_sett['upi_qr_image'] ?? 'assets/images/upi_qr_default.png'); ?>">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                        
                        <!-- Card 1: UPI Credentials & QR Image -->
                        <div style="background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 14px; padding: 22px;">
                            <h3 style="color: #4A0B17; font-size: 1.15rem; margin-top: 0; margin-bottom: 18px; font-weight: 800; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                📱 UPI ID &amp; QR Code Configuration
                            </h3>

                            <!-- UPI ID Field -->
                            <div class="admin-field-group" style="margin-bottom: 18px;">
                                <label for="upi_id" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">Admin UPI ID <span class="req">*</span></label>
                                <input type="text" id="upi_id" name="upi_id" value="<?php echo escape_output($upi_sett['upi_id'] ?? 'vklogistics@upi'); ?>" placeholder="e.g. vklogistics@upi" required style="width: 100%; padding: 11px 14px; border: 2px solid #D97706; border-radius: 8px; font-weight: 800; font-size: 1rem; color: #4A0B17;">
                                <small style="color: #64748B; font-size: 0.78rem; margin-top: 4px; display: block;">Copied by customers paying via Google Pay, PhonePe, Paytm, etc.</small>
                            </div>

                            <!-- Account Holder Name -->
                            <div class="admin-field-group" style="margin-bottom: 18px;">
                                <label for="account_name" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">UPI Account Holder Name <span class="req">*</span></label>
                                <input type="text" id="account_name" name="account_name" value="<?php echo escape_output($upi_sett['upi_account_name'] ?? 'VK LOGISTICS LTD'); ?>" placeholder="e.g. VK LOGISTICS LTD" required style="width: 100%; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                            </div>

                            <!-- QR Code Image Upload -->
                            <div class="admin-field-group" style="margin-bottom: 18px;">
                                <label for="upi_qr_file" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">QR Code Image Upload</label>
                                <div style="display: flex; align-items: center; gap: 16px; margin-top: 6px;">
                                    <img id="admin-upi-qr-preview" src="<?php echo escape_output($upi_sett['upi_qr_image'] ?? 'assets/images/upi_qr_default.png'); ?>" alt="UPI QR Code" style="width: 90px; height: 90px; object-fit: contain; border: 2px solid #D4AF37; border-radius: 10px; background: #FFF; padding: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    <div style="flex: 1;">
                                        <input type="file" id="upi_qr_file" name="upi_qr_file" accept="image/jpeg,image/png,image/webp" style="display: block; width: 100%; font-size: 0.85rem;">
                                        <small style="color: #64748B; font-size: 0.76rem; margin-top: 6px; display: block;">JPG, PNG, or WEBP (Max 10 MB).</small>
                                    </div>
                                </div>
                            </div>

                            <!-- UPI Instructions -->
                            <div class="admin-field-group" style="margin-bottom: 18px;">
                                <label for="instructions" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">UPI Payment Instructions</label>
                                <textarea id="instructions" name="instructions" rows="3" style="width: 100%; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.88rem; line-height: 1.45; resize: vertical;"><?php echo escape_output($upi_sett['upi_instructions'] ?? ''); ?></textarea>
                            </div>

                            <!-- Enable / Disable Checkbox -->
                            <div style="background: #FFF; border: 1.5px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; display: flex; align-items: center; gap: 12px;">
                                <input type="checkbox" id="is_enabled" name="is_enabled" value="1" <?php echo ($upi_sett['upi_enabled'] ?? '1') === '1' ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: #10B981; cursor: pointer;">
                                <label for="is_enabled" style="font-weight: 700; color: #0F172A; font-size: 0.9rem; cursor: pointer; margin: 0;">
                                    Enable UPI Payment at Checkout
                                </label>
                            </div>
                        </div>

                        <!-- Card 2: Direct Bank Transfer Details -->
                        <div style="background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 14px; padding: 22px;">
                            <h3 style="color: #4A0B17; font-size: 1.15rem; margin-top: 0; margin-bottom: 18px; font-weight: 800; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                🏛️ Direct Bank Transfer Details
                            </h3>

                            <!-- Bank Name -->
                            <div class="admin-field-group" style="margin-bottom: 14px;">
                                <label for="bank_name" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name" value="<?php echo escape_output($upi_sett['bank_name'] ?? 'Barclays Bank UK'); ?>" placeholder="e.g. Barclays Bank UK" style="width: 100%; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                            </div>

                            <!-- Bank Account Name -->
                            <div class="admin-field-group" style="margin-bottom: 14px;">
                                <label for="bank_account_name" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">Account Holder Name</label>
                                <input type="text" id="bank_account_name" name="bank_account_name" value="<?php echo escape_output($upi_sett['bank_account_name'] ?? 'VK LOGISTICS LTD'); ?>" placeholder="e.g. VK LOGISTICS LTD" style="width: 100%; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                            </div>

                            <!-- UK Sort Code -->
                            <div class="admin-field-group" style="margin-bottom: 14px;">
                                <label for="bank_sort_code" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">Sort Code / IFSC</label>
                                <input type="text" id="bank_sort_code" name="bank_sort_code" value="<?php echo escape_output($upi_sett['bank_sort_code'] ?? '20-45-77'); ?>" placeholder="e.g. 20-45-77" style="width: 100%; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                            </div>

                            <!-- Account Number -->
                            <div class="admin-field-group" style="margin-bottom: 14px;">
                                <label for="bank_account_number" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">Account Number</label>
                                <input type="text" id="bank_account_number" name="bank_account_number" value="<?php echo escape_output($upi_sett['bank_account_number'] ?? '83920144'); ?>" placeholder="e.g. 83920144" style="width: 100%; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                            </div>

                            <!-- Bank Instructions -->
                            <div class="admin-field-group" style="margin-bottom: 14px;">
                                <label for="bank_instructions" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">Bank Transfer Instructions</label>
                                <textarea id="bank_instructions" name="bank_instructions" rows="2" style="width: 100%; padding: 8px 12px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.85rem; resize: vertical;"><?php echo escape_output($upi_sett['bank_instructions'] ?? ''); ?></textarea>
                            </div>

                            <!-- Enable / Disable Checkbox -->
                            <div style="background: #FFF; border: 1.5px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; display: flex; align-items: center; gap: 12px;">
                                <input type="checkbox" id="bank_enabled" name="bank_enabled" value="1" <?php echo ($upi_sett['bank_enabled'] ?? '1') === '1' ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: #3B82F6; cursor: pointer;">
                                <label for="bank_enabled" style="font-weight: 700; color: #0F172A; font-size: 0.9rem; cursor: pointer; margin: 0;">
                                    Enable Bank Transfer at Checkout
                                </label>
                            </div>
                        </div>

                        <!-- Card 3: Support Phone & Passkey -->
                        <div style="background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 14px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="color: #4A0B17; font-size: 1.15rem; margin-top: 0; margin-bottom: 18px; font-weight: 800; border-bottom: 2px solid #E2E8F0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                    📞 Helpline &amp; Passkey Security
                                </h3>

                                <div class="admin-field-group" style="margin-bottom: 18px;">
                                    <label for="support_phone" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">UK Customer Support Helpline</label>
                                    <input type="text" id="support_phone" name="support_phone" value="<?php echo escape_output($upi_sett['support_phone'] ?? '+44 7700 900888'); ?>" placeholder="e.g. +44 7700 900888" style="width: 100%; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-weight: 700;">
                                    <small style="color: #64748B; font-size: 0.78rem; margin-top: 4px; display: block;">Displays on site header &amp; customer receipts.</small>
                                </div>

                                <div class="admin-field-group" style="margin-bottom: 18px;">
                                    <label for="admin_password" style="font-weight: 700; color: #0F172A; font-size: 0.9rem;">Update Admin Passkey</label>
                                    <input type="password" id="admin_password" name="admin_password" placeholder="Leave blank to keep current passkey" style="width: 100%; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: 8px;">
                                    <small style="color: #64748B; font-size: 0.78rem; margin-top: 4px; display: block;">Default passkey is <strong>admin123</strong>.</small>
                                </div>
                            </div>

                            <div style="margin-top: 24px; text-align: right;">
                                <button type="submit" id="btn-save-upi-settings" style="background: linear-gradient(135deg, #D97706 0%, #B45309 100%); color: #FFFFFF; border: none; padding: 14px 28px; border-radius: 10px; font-weight: 800; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); width: 100%;">
                                    💾 Save All Payment &amp; Gateway Settings
                                </button>
                            </div>
                        </div>

                    </div>
                </form>

                <!-- ADMIN PAYMENT VERIFICATION PANEL -->
                <div style="background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 14px; padding: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 2px solid #F1F5F9; padding-bottom: 12px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h3 style="color: #4A0B17; font-size: 1.25rem; margin: 0; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                                <span>🔍</span> Orders &rarr; Payment Verification Panel (UPI &amp; Bank Transfer)
                            </h3>
                            <p style="color: #64748B; font-size: 0.85rem; margin: 4px 0 0 0;">Review uploaded payment screenshots, approve payments, or request re-upload</p>
                        </div>

                        <div style="display: flex; gap: 10px; align-items: center;">
                            <button type="button" id="btn-filter-upi-pending" class="btn-secondary" style="background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; padding: 8px 16px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; cursor: pointer;">
                                🟡 Show Pending Verify Only
                            </button>
                            <button type="button" id="btn-refresh-upi-table" style="background: #F8FAFC; border: 1px solid #CBD5E1; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; color: #475569;">
                                🔄 Refresh Table
                            </button>
                        </div>
                    </div>

                    <!-- Payment Verification Table -->
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="white-space:nowrap; text-align:center; width: 40px;">#</th>
                                    <th style="white-space:nowrap; text-align:left;">Order ID / Date</th>
                                    <th style="text-align:left;">User Name &amp; Contact</th>
                                    <th style="white-space:nowrap; text-align:right;">Amount</th>
                                    <th style="white-space:nowrap; text-align:center;">Uploaded Screenshot</th>
                                    <th style="white-space:nowrap; text-align:center;">Payment Method</th>
                                    <th style="white-space:nowrap; text-align:center;">Payment Status</th>
                                    <th style="white-space:nowrap; text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="upi-verification-table-body">
                                <tr>
                                    <td colspan="8" style="text-align:center; padding: 30px; color: #64748B;">
                                        Loading payment verification records...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- TAB 3: PDF & CSV REPORTS -->
            <div class="admin-tab-content admin-panel-card" id="tab-export" style="display:none;">
                <div style="padding: 24px 20px;">
                    <div style="text-align:center; max-width: 600px; margin: 0 auto 32px;">
                        <h2 style="font-size: 1.6rem; color: #3B0612; margin-bottom: 8px; font-weight:800;">Export &amp; Download Reports</h2>
                        <p style="color: #64748B; font-size: 0.95rem; line-height: 1.5; margin: 0;">
                            Generate high-resolution PDF reports or export formatted datasets directly into Google Sheets &amp; Microsoft Excel.
                        </p>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; max-width: 960px; margin: 0 auto;">
                        
                        <!-- PDF Report Option Card -->
                        <div style="background: #FFFFFF; border: 2px solid #D4AF37; border-radius: 16px; padding: 32px 24px; text-align: center; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="font-size: 3rem; margin-bottom: 14px;">📄</div>
                                <h3 style="font-size: 1.25rem; color: #3B0612; margin: 0 0 10px 0; font-weight: 800;">Ultra HD PDF Report</h3>
                                <p style="font-size: 0.88rem; color: #64748B; line-height: 1.5; margin-bottom: 24px;">
                                    Generates a high-resolution, beautifully formatted PDF document with executive KPI summary metrics, VK Logistics branding, structured table, and official signature block.
                                </p>
                            </div>
                            <a href="export-pdf.php" target="_blank" class="btn-gold" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 24px; font-size: 1rem; text-decoration: none; border-radius: 8px;">
                                🖨️ Open / Download PDF Report
                            </a>
                        </div>

                        <!-- Google Sheets / Excel Option Card -->
                        <div style="background: #FFFFFF; border: 2px solid #10B981; border-radius: 16px; padding: 32px 24px; text-align: center; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="font-size: 3rem; margin-bottom: 14px;">📊</div>
                                <h3 style="font-size: 1.25rem; color: #065F46; margin: 0 0 10px 0; font-weight: 800;">Google Sheets &amp; Excel Reports</h3>
                                <p style="font-size: 0.88rem; color: #64748B; line-height: 1.5; margin-bottom: 20px;">
                                    View real-time booking data in Google Sheets Web Table format, download UTF-8 CSV, or copy all data to Google Sheets in 1 click.
                                </p>
                            </div>
                            <a href="export-html-sheet.php" target="_blank" style="background: #065F46; color: #FFFFFF; text-decoration: none; padding: 14px 24px; border-radius: 8px; font-weight: 800; font-size: 1rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                                📊 Open Google Sheets &amp; Excel Table ↗
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- TAB 4: PRODUCTS & CATEGORIES -->
            <div class="admin-tab-content admin-panel-card" id="tab-catalog" style="display:none; padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #E2E8F0; padding-bottom: 12px; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 style="font-size: 1.6rem; color: #4A0B17; margin: 0; font-weight:800;">Catalog Management</h2>
                        <p style="color: #64748B; font-size: 0.88rem; margin: 4px 0 0 0;">Manage your store categories, products, and pricing.</p>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn-gold" id="btn-add-category" style="padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; border: 1px solid #D4AF37;">
                            📁 Add Category
                        </button>
                        <button type="button" id="btn-add-addon" style="background:#9D174D; color:#fff; border:1px solid #9D174D; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                            🍫 Add Festive Add-On
                        </button>
                        <button type="button" class="btn-modal-save" id="btn-add-product" style="background:#4A0B17; color:#fff; border:1px solid #4A0B17; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                            🛒 Add Product / Item
                        </button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
                    <!-- Festive Cart Add-Ons Status Panel -->
                    <div style="background: linear-gradient(135deg, #FFFDF5 0%, #FEF3C7 100%); border: 1.5px solid #FCD34D; border-radius: 12px; padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; border-bottom: 1px dashed #F59E0B; padding-bottom: 10px;">
                            <h3 style="color: #92400E; font-size: 1.15rem; margin: 0; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                                🎁 Festive Cart Add-Ons Status &amp; Live Controls
                            </h3>
                            <span style="background: #D97706; color: #FFF; font-size: 0.75rem; font-weight: 800; padding: 4px 10px; border-radius: 20px;">
                                ✅ Active in Customer Cart
                            </span>
                        </div>
                        <p style="color: #78350F; font-size: 0.86rem; margin: 0 0 16px 0; line-height: 1.4;">
                            These add-ons are displayed inside the shopping cart. You can edit their title, price, description, or image icon directly here.
                        </p>
                        <div id="admin-addons-status-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                            <!-- Dynamically populated by renderAddonsPanel() -->
                        </div>
                    </div>

                    <!-- Categories Management -->
                    <div style="background:#FFF; border:1px solid #E2E8F0; border-radius:12px; padding:20px;">
                        <h3 style="color:#4A0B17; font-size:1.15rem; margin-top:0; margin-bottom:15px; border-bottom:1px dashed #CBD5E1; padding-bottom:8px;">📁 Shop Categories</h3>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">ID</th>
                                        <th style="width: 80px; text-align: center;">Image</th>
                                        <th style="text-align: left;">Category Name</th>
                                        <th style="text-align: left;">Short Description</th>
                                        <th style="width: 150px; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-categories-table-body">
                                    <?php
                                    $main_cats = array_filter($catalog_categories, function($c) {
                                        return strpos($c['name'], 'Add-On') === false;
                                    });
                                    if (empty($main_cats)):
                                    ?>
                                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#64748B;">No shop categories found.</td></tr>
                                    <?php else: foreach ($main_cats as $c):
                                        $c_img = !empty($c['image_path']) ? $c['image_path'] : 'assets/images/ganesh_hero.png';
                                        $c_desc = !empty($c['description']) ? escape_output($c['description']) : 'Festive handcrafted items & idol collection.';
                                    ?>
                                        <tr>
                                            <td style="text-align:center; font-weight:700; color:#475569; vertical-align:middle; width:50px;"><?php echo $c['id']; ?></td>
                                            <td style="text-align:center; vertical-align:middle; width:80px; padding:10px;">
                                                <img src="<?php echo escape_output($c_img); ?>" title="<?php echo escape_output($c['name']); ?>" class="btn-open-hd-modal" data-img="<?php echo escape_output($c_img); ?>" data-ref="<?php echo escape_output($c['name']); ?>" style="width:60px; height:60px; object-fit:cover; border-radius:10px; border:2px solid #D4AF37; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.08); display:inline-block;">
                                            </td>
                                            <td style="vertical-align:middle;">
                                                <strong style="color:#4A0B17; font-size:0.92rem; display:block;"><?php echo escape_output($c['name']); ?></strong>
                                            </td>
                                            <td style="font-size:0.86rem; color:#475569; vertical-align:middle; max-width:380px;">
                                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:380px;" title="<?php echo $c_desc; ?>">
                                                    <?php echo $c_desc; ?>
                                                </div>
                                            </td>
                                            <td style="text-align:center; vertical-align:middle; width:150px;">
                                                <button type="button" class="btn-action-sm btn-edit-category" data-id="<?php echo $c['id']; ?>" style="padding: 5px 10px; font-size:0.75rem; cursor:pointer;">Edit ✏️</button>
                                                <button type="button" class="btn-action-sm btn-delete-category" data-id="<?php echo $c['id']; ?>" style="padding: 5px 10px; font-size:0.75rem; background:#EF4444; border-color:#EF4444; color:#fff; cursor:pointer;">Delete 🗑️</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Products Management -->
                    <div style="background:#FFF; border:1px solid #E2E8F0; border-radius:12px; padding:20px;">
                        <h3 style="color:#4A0B17; font-size:1.15rem; margin-top:0; margin-bottom:15px; border-bottom:1px dashed #CBD5E1; padding-bottom:8px;">🛒 Products Catalog</h3>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 80px; text-align: center;">Image</th>
                                        <th style="text-align: left;">Product Name / Category</th>
                                        <th style="text-align: left;">Description</th>
                                        <th style="width: 100px; text-align: right;">Price</th>
                                        <th style="width: 150px; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-products-table-body">
                                    <?php
                                    $shop_prods = array_filter($catalog_products, function($p) {
                                        return $p['id'] != 7 && $p['id'] != 8 && (empty($p['category_name']) || strpos($p['category_name'], 'Add-On') === false);
                                    });
                                    if (empty($shop_prods)):
                                    ?>
                                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#64748B;">No products found.</td></tr>
                                    <?php else: foreach ($shop_prods as $p):
                                        $img1 = $p['image_path'] ?: 'assets/images/ganesh_hero.png';
                                    ?>
                                        <tr>
                                            <td style="text-align:center; vertical-align:middle; width:90px; padding:12px;">
                                                <img src="<?php echo escape_output($img1); ?>" title="<?php echo escape_output($p['name']); ?>" class="btn-open-hd-modal" data-img="<?php echo escape_output($img1); ?>" data-ref="<?php echo escape_output($p['name']); ?>" style="width:64px; height:64px; object-fit:cover; border-radius:10px; border:2px solid #D4AF37; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.08); display:inline-block;">
                                            </td>
                                            <td style="vertical-align:middle;">
                                                <strong style="color:#0F172A; font-size:0.9rem; display:block;"><?php echo escape_output($p['name']); ?></strong>
                                                <span style="font-size:0.75rem; color:#64748B; font-weight:700; text-transform:uppercase;"><?php echo escape_output($p['category_name'] ?? 'Shop Product'); ?></span>
                                            </td>
                                            <td style="font-size:0.8rem; color:#475569; vertical-align:middle; max-width:250px;">
                                                <?php echo escape_output($p['description'] ?? ''); ?>
                                            </td>
                                            <td style="text-align:right; font-weight:800; color:#4A0B17; vertical-align:middle;">
                                                &pound;<?php echo number_format((float)$p['price'], 2); ?>
                                            </td>
                                            <td style="text-align:center; vertical-align:middle;">
                                                <button type="button" class="btn-action-sm btn-edit-product" data-id="<?php echo $p['id']; ?>" style="padding: 4px 8px; font-size:0.72rem; cursor:pointer;">Edit ✏️</button>
                                                <button type="button" class="btn-action-sm btn-delete-product" data-id="<?php echo $p['id']; ?>" style="padding: 4px 8px; font-size:0.72rem; background:#EF4444; border-color:#EF4444; color:#fff; cursor:pointer;">Delete 🗑️</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- VIEW DETAILS MODAL -->
    <div class="admin-modal-overlay" id="view-details-modal" style="display:none;">
        <div class="admin-modal-card" style="max-width: 600px; width: 90%;">
            <div class="admin-modal-header">
                <h3 class="admin-modal-title" id="view-modal-ref-title">Booking Details</h3>
                <button type="button" class="admin-modal-close" id="view-modal-close-btn" aria-label="Close modal">&times;</button>
            </div>
            <div class="admin-modal-body" id="view-modal-body-content" style="padding: 20px; font-size: 0.9rem; line-height: 1.6; max-height: 70vh; overflow-y: auto;">
                <!-- Content injected dynamically -->
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="btn-modal-cancel" id="view-modal-close-action-btn">Close</button>
            </div>
        </div>
    </div>

    <!-- UPDATE STATUS MODAL -->
    <div class="admin-modal-overlay" id="update-status-modal" style="display:none;">
        <div class="admin-modal-card">
            <div class="admin-modal-header">
                <h3 class="admin-modal-title" id="modal-ref-title">Update Booking</h3>
                <button type="button" class="admin-modal-close" id="modal-close-btn" aria-label="Close modal">&times;</button>
            </div>
            
            <form id="update-status-form">
                <input type="hidden" id="modal-booking-ref" name="booking_reference">
                <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">

                <div class="admin-field-group">
                    <label for="modal-payment-status">Payment Status</label>
                    <select id="modal-payment-status" name="payment_status">
                        <option value="PAID">PAID</option>
                        <option value="PAYMENT VERIFICATION PENDING">PAYMENT VERIFICATION PENDING</option>
                        <option value="FAILED">FAILED</option>
                        <option value="CANCELLED">CANCELLED</option>
                    </select>
                </div>

                <div class="admin-field-group">
                    <label for="modal-booking-status">Fulfillment / Booking Status</label>
                    <select id="modal-booking-status" name="booking_status">
                        <option value="CONFIRMED">CONFIRMED</option>
                        <option value="PROCESSING">PROCESSING</option>
                        <option value="SHIPPED">SHIPPED</option>
                        <option value="DELIVERED">DELIVERED</option>
                        <option value="CANCELLED">CANCELLED</option>
                    </select>
                </div>

                <div class="admin-field-group">
                    <label for="modal-payment-ref" id="modal-payment-ref-label">Payment Txn / Bank Reference</label>
                    <input type="text" id="modal-payment-ref" name="payment_reference" placeholder="Enter bank reference or txn ID">
                </div>

                <div class="admin-modal-footer">
                    <button type="button" class="btn-modal-cancel" id="modal-cancel-btn">Cancel</button>
                    <button type="submit" class="btn-modal-save">Save Changes &#10003;</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ULTRA HD PAYMENT RECEIPT LIGHTBOX MODAL -->
    <div id="payment-proof-modal" class="hd-lightbox-overlay" style="display:none;">
        <div class="hd-lightbox-card">
            <div class="hd-lightbox-header">
                <div class="hd-lightbox-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D4AF37" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <span>Payment Proof Receipt &bull; <strong id="hd-modal-ref">VKG-2026-000001</strong></span>
                </div>
                <button type="button" id="btn-close-hd-modal" style="background:none; border:none; color:#F1F5F9; font-size:1.8rem; cursor:pointer; font-weight:800;" title="Close Modal">&times;</button>
            </div>
            <div class="hd-lightbox-body">
                <img id="hd-modal-img" src="" alt="Payment Receipt Photo" title="Click to zoom in / out">
            </div>
            <div class="hd-lightbox-footer">
                <div style="color:#94A3B8; font-size:0.8rem; font-weight:600;">
                    🔍 Click photo to zoom in / out
                </div>
                <div style="display:flex; gap:10px;">
                    <a id="hd-modal-download" href="" download class="btn-hd-action">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Download HD Photo
                    </a>
                    <a id="hd-modal-open-new" href="" target="_blank" class="btn-hd-action">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        Open Full Resolution ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- CATEGORY MODAL -->
    <div class="admin-modal-overlay" id="category-modal" style="display:none; z-index:1100;">
        <div class="admin-modal-card" style="max-width: 400px; width: 90%;">
            <div class="admin-modal-header">
                <h3 class="admin-modal-title" id="category-modal-title">Add Category</h3>
                <button type="button" class="admin-modal-close" id="category-modal-close-btn">&times;</button>
            </div>
            <form id="category-form">
                <input type="hidden" id="category-id" name="id" value="0">
                <div class="admin-modal-body" style="padding: 20px;">
                    <div class="admin-field-group">
                        <label for="category-name" style="display:block; margin-bottom:6px; font-weight:600; font-size:0.9rem;">Category Name</label>
                        <input type="text" id="category-name" name="name" placeholder="e.g. Eco Ganesh Statues" required style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:6px; box-sizing:border-box;">
                    </div>
                    <div class="admin-field-group" style="margin-top: 15px;">
                        <label for="category-description" style="display:block; margin-bottom:6px; font-weight:600; font-size:0.9rem;">Short Description</label>
                        <textarea id="category-description" name="description" placeholder="Brief overview of items in this category..." rows="3" style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:6px; box-sizing:border-box; resize:vertical;"></textarea>
                    </div>
                    <div class="admin-field-group" style="margin-top: 15px;">
                        <label for="category-image-file" style="display:block; margin-bottom:6px; font-weight:600; font-size:0.9rem;">Category Image</label>
                        <input type="hidden" id="category-current-image" name="current_image_path" value="">
                        <input type="file" id="category-image-file" name="category_image" accept="image/*" style="width:100%; padding:8px; border:1px solid #CBD5E1; border-radius:6px; box-sizing:border-box; background:#F8FAFC;">
                        <small style="font-size:0.75rem; color:#64748B; display:block; margin-top:4px;">recommended ratio: 1:1 square (e.g. 1000x1000 px, max 10mb)</small>
                        <div id="category-image-preview-box" style="margin-top: 10px; display: none; align-items: center; gap: 10px; background: #F1F5F9; padding: 8px 12px; border-radius: 6px;">
                            <img id="category-image-preview-img" src="" alt="Category Image" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #CBD5E1;">
                            <span style="font-size: 0.8rem; color: #475569; font-weight: 600;">Current Image</span>
                        </div>
                    </div>
                </div>
                <div class="admin-modal-footer">
                    <button type="button" class="btn-modal-cancel" id="category-modal-cancel-btn">Cancel</button>
                    <button type="submit" class="btn-modal-save">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PRODUCT MODAL (3 IMAGES PER PRODUCT) -->
    <div class="admin-modal-overlay" id="product-modal" style="display:none; z-index:1100;">
        <div class="admin-modal-card" style="max-width: 550px; width: 92%;">
            <div class="admin-modal-header">
                <h3 class="admin-modal-title" id="product-modal-title">Add / Edit Product (3 Images)</h3>
                <button type="button" class="admin-modal-close" id="product-modal-close-btn">&times;</button>
            </div>
            <form id="product-form" enctype="multipart/form-data">
                <input type="hidden" id="product-id" name="id" value="0">
                <input type="hidden" id="product-current-image" name="current_image_path" value="">
                <input type="hidden" id="product-current-image-2" name="current_image_path_2" value="">
                <input type="hidden" id="product-current-image-3" name="current_image_path_3" value="">

                <div class="admin-modal-body" style="padding: 20px; max-height:70vh; overflow-y:auto; display:flex; flex-direction:column; gap:16px; box-sizing:border-box;">
                    <div class="admin-field-group" id="addon-status-toggle-group" style="display:none; background:#FFFDF5; border:1px solid #FCD34D; padding:12px; border-radius:8px;">
                        <label style="display:flex; align-items:center; gap:8px; font-weight:700; color:#92400E; cursor:pointer; font-size:0.88rem; margin:0;">
                            <input type="checkbox" id="product-addon-enabled" name="addon_enabled" value="1" checked style="width:18px; height:18px; cursor:pointer;">
                            <span>Enable this Festive Add-On in Customer Shopping Cart</span>
                        </label>
                    </div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div class="admin-field-group">
                            <label for="product-category" style="font-weight:700;">Category</label>
                            <select id="product-category" name="category_id" required style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:6px; box-sizing:border-box;">
                                <!-- Dynamic category list -->
                            </select>
                        </div>
                        <div class="admin-field-group">
                            <label for="product-price" style="font-weight:700;">Price (£)</label>
                            <input type="number" id="product-price" name="price" step="0.01" min="0.00" placeholder="14.99" required style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:6px; box-sizing:border-box;">
                        </div>
                    </div>

                    <div class="admin-field-group">
                        <label for="product-name" style="font-weight:700;">Product Title / Name</label>
                        <input type="text" id="product-name" name="name" placeholder="e.g. Ganesh Statue / Vinayaka Vigraha" required style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:6px; box-sizing:border-box;">
                    </div>

                    <div class="admin-field-group">
                        <label for="product-description" style="font-weight:700;">Description</label>
                        <textarea id="product-description" name="description" rows="3" placeholder="Enter full product specifications and details..." style="width:100%; padding:10px; border:1px solid #CBD5E1; border-radius:6px; font-family:inherit; resize:vertical; box-sizing:border-box;"></textarea>
                    </div>

                    <!-- UNLIMITED PRODUCT IMAGES GALLERY SECTION -->
                    <div style="background:#F8FAFC; border:1.5px dashed #CBD5E1; padding:18px; border-radius:12px; margin-top:4px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
                            <div>
                                <h4 style="margin:0 0 4px 0; color:#4A0B17; font-size:1rem; font-weight:800; display:flex; align-items:center; gap:6px;">
                                    📸 Unlimited Product Image Gallery
                                </h4>
                                <span style="font-size:0.78rem; color:#64748B;">Upload unlimited product photos. First image is featured. Reorder or remove anytime.</span>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <button type="button" id="btn-add-product-gallery-images" style="background:#0F172A; color:#FFFFFF; border:none; padding:8px 14px; border-radius:8px; font-size:0.82rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s;">
                                    ➕ Add Images
                                </button>
                            </div>
                        </div>

                        <!-- Hidden File Input for Unlimited Multiple Image Uploads -->
                        <input type="file" id="product-gallery-input" name="product_gallery_files[]" multiple accept="image/*" style="display:none;">

                        <!-- Dynamic Image Thumbnail Cards Grid -->
                        <div id="product-gallery-preview-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(110px, 1fr)); gap:12px; min-height:80px; padding:12px; background:#FFFFFF; border:1.5px solid #E2E8F0; border-radius:10px;">
                            <!-- Dynamically filled by JS -->
                        </div>

                        <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center;">
                            <span id="product-gallery-count-badge" style="font-size:0.78rem; color:#475569; font-weight:700;">0 Images Selected</span>
                            <button type="button" id="btn-upload-more-gallery-images" style="background:#EFF6FF; color:#1D4ED8; border:1px solid #93C5FD; padding:6px 12px; border-radius:6px; font-size:0.78rem; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                                📤 Upload More Images
                            </button>
                        </div>
                    </div>
                </div>
                <div class="admin-modal-footer">
                    <button type="button" class="btn-modal-cancel" id="product-modal-cancel-btn">Cancel</button>
                    <button type="submit" class="btn-modal-save" style="background:#4A0B17; color:#FFFFFF; font-weight:800;">Save Product &amp; 3 Photos</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT APP LOGIC -->
    <script>
        // Global Admin State & Functions
        const csrfToken = "<?php echo escape_output($csrf_token); ?>";
        let activeStatusFilter = 'ALL';
        let currentSearchQuery = '';
        let loadedBookings = <?php echo json_encode($initial_bookings); ?>;
        let catalogCategories = <?php echo json_encode($catalog_categories); ?>;
        let catalogProducts = <?php echo json_encode($catalog_products); ?>;
        let adminSettings = <?php echo json_encode($settings); ?>;

        // Fail-Safe Tab Switching Function (Globally Accessible)
        function switchAdminTab(tabId, btn) {
            const tabBtns = document.querySelectorAll('.admin-tab-btn');
            const tabContents = document.querySelectorAll('.admin-tab-content');

            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.style.display = 'none');

            if (btn) {
                btn.classList.add('active');
            } else {
                const activeBtn = document.querySelector(`.admin-tab-btn[data-tab="${tabId}"]`);
                if (activeBtn) activeBtn.classList.add('active');
            }

            const targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.style.display = 'block';
            }

            if (tabId === 'tab-catalog') {
                if (typeof window.loadCatalogData === 'function') window.loadCatalogData();
            } else if (tabId === 'tab-upi') {
                if (typeof window.loadUpiVerificationTable === 'function') window.loadUpiVerificationTable();
            } else if (tabId === 'tab-bookings') {
                if (typeof window.loadDashboardData === 'function') window.loadDashboardData();
            }
        }
        window.switchAdminTab = switchAdminTab;

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.admin-tab-btn');
            if (btn) {
                e.preventDefault();
                const tabId = btn.getAttribute('data-tab');
                if (tabId) {
                    switchAdminTab(tabId, btn);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {

            // Login Form Submission
            const loginForm = document.getElementById('admin-login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const pass = document.getElementById('admin-passcode-input').value;
                    const errBox = document.getElementById('login-error-msg');
                    const btn = document.getElementById('btn-login-submit');
                    
                    errBox.style.display = 'none';
                    btn.disabled = true;
                    btn.textContent = 'Authenticating...';

                    fetch('ajax/admin-actions.php?action=login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'password=' + encodeURIComponent(pass)
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.textContent = 'Unlock Dashboard';
                        if (data.success) {
                            document.getElementById('admin-login-screen').style.display = 'none';
                            document.getElementById('admin-main-app').style.display = 'block';
                            loadDashboardData();
                        } else {
                            errBox.textContent = data.message || 'Invalid passkey';
                            errBox.style.display = 'block';
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.textContent = 'Unlock Dashboard';
                        errBox.textContent = 'Server connection error';
                        errBox.style.display = 'block';
                    });
                });
            }

            // Password Eye Icon Toggle Listener
            const togglePassBtn = document.getElementById('toggle-password-btn');
            if (togglePassBtn) {
                togglePassBtn.addEventListener('click', function() {
                    const passInput = document.getElementById('admin-passcode-input');
                    const eyeShow = document.getElementById('eye-icon-show');
                    const eyeHide = document.getElementById('eye-icon-hide');
                    if (passInput && eyeShow && eyeHide) {
                        if (passInput.type === 'password') {
                            passInput.type = 'text';
                            eyeShow.style.display = 'none';
                            eyeHide.style.display = 'block';
                        } else {
                            passInput.type = 'password';
                            eyeShow.style.display = 'block';
                            eyeHide.style.display = 'none';
                        }
                    }
                });
            }

            // Logout Buttons Event Listeners
            function performAdminLogout() {
                fetch('ajax/admin-actions.php?action=logout', { method: 'POST' })
                .then(res => res.json())
                .then(() => {
                    window.location.href = 'admin.php?action=logout';
                })
                .catch(() => {
                    window.location.href = 'admin.php?action=logout';
                });
            }

            document.addEventListener('click', function(e) {
                let logoutBtn = e.target.closest('#btn-admin-logout, .btn-simple-logout, .btn-admin-logout, [data-action="logout"], .logout-btn');
                if (logoutBtn) {
                    e.preventDefault();
                    performAdminLogout();
                }
            });

            // Fetch & Render Dashboard Data
            function loadDashboardData() {
                const url = `ajax/admin-actions.php?action=get_dashboard_data&search=${encodeURIComponent(currentSearchQuery)}&status=${encodeURIComponent(activeStatusFilter)}`;
                
                fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    loadedBookings = data.bookings || [];

                    // Render Stats Dynamically
                    const stats = data.stats;
                    if (document.getElementById('stat-today-orders') && stats.today_orders !== undefined) {
                        document.getElementById('stat-today-orders').textContent = stats.today_orders;
                    }
                    if (document.getElementById('stat-total-categories') && stats.total_categories !== undefined) {
                        document.getElementById('stat-total-categories').textContent = stats.total_categories;
                    }
                    if (document.getElementById('stat-total-products') && stats.total_products !== undefined) {
                        document.getElementById('stat-total-products').textContent = stats.total_products;
                    }
                    if (document.getElementById('stat-total-bookings') && stats.total_bookings !== undefined) {
                        document.getElementById('stat-total-bookings').textContent = stats.total_bookings;
                    }
                    if (document.getElementById('stat-total-revenue') && stats.total_revenue !== undefined) {
                        document.getElementById('stat-total-revenue').textContent = '£' + parseFloat(stats.total_revenue).toFixed(2);
                    }
                    if (document.getElementById('stat-pending-count') && stats.pending_count !== undefined) {
                        document.getElementById('stat-pending-count').textContent = stats.pending_count;
                    }

                    // Render Table
                    const tbody = document.getElementById('bookings-table-body');
                    tbody.innerHTML = '';

                    if (!data.bookings || data.bookings.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:30px; color: var(--color-text-muted);">No bookings found matching your search.</td></tr>`;
                        return;
                    }

                    let serialNo = data.bookings.length;
                    data.bookings.forEach(b => {
                        const tr = document.createElement('tr');
                        
                        let pStatusDisplay = b.payment_status;
                        if (pStatusDisplay === 'PAYMENT VERIFICATION PENDING') {
                            pStatusDisplay = 'PENDING VERIFY';
                        }

                        let payBadge = `<span class="status-pill status-pending" title="${escapeHtml(b.payment_status)}">${pStatusDisplay}</span>`;
                        if (b.payment_status === 'PAID') {
                            payBadge = `<span class="status-pill status-paid">PAID</span>`;
                        } else if (b.payment_status === 'FAILED' || b.payment_status === 'CANCELLED') {
                            payBadge = `<span class="status-pill status-cancelled">${b.payment_status}</span>`;
                        }

                        let bBadge = `<span class="status-pill status-pending">${b.booking_status || 'CONFIRMED'}</span>`;
                        if (b.booking_status === 'SHIPPED' || b.booking_status === 'DELIVERED') {
                            bBadge = `<span class="status-pill status-shipped">${b.booking_status}</span>`;
                        }

                        let receiptCell = '';
                        if (b.payment_proof_image) {
                            receiptCell = `<div style="margin-top:5px;"><button type="button" class="btn-view-receipt btn-open-hd-modal" data-img="${escapeHtml(b.payment_proof_image)}" data-ref="${escapeHtml(b.booking_reference)}" style="background:#FEF3C7 !important; border:1px solid #F59E0B !important; color:#B45309 !important; font-size:0.7rem !important; font-weight:800 !important; padding:2px 6px !important; border-radius:4px !important; cursor:pointer;">📷 Receipt</button></div>`;
                        }

                        tr.innerHTML = `
                            <td style="text-align:center; font-weight:700; color:#475569; vertical-align:top; padding-top:12px;">${serialNo--}</td>
                            <td style="white-space:nowrap; vertical-align:top; padding-top:12px;">
                                <strong style="color:#4A0B17; font-size:0.86rem; font-family:monospace; letter-spacing:-0.2px; display:block;">${b.booking_reference}</strong>
                                <span style="font-size:0.72rem; color:#64748B; font-weight:600; display:block; margin-top:2px;">${(b.created_at || '').substring(0, 10)}</span>
                            </td>
                            <td style="min-width:130px; vertical-align:top; padding-top:12px;">
                                <div><strong style="color:#0F172A; font-size:0.88rem;">${escapeHtml(b.customer_name)}</strong></div>
                                <div style="font-size:0.78rem; color:#475569; font-weight:600; margin-top:2px;">${escapeHtml(b.mobile)}</div>
                            </td>
                            <td style="max-width:160px; font-size:0.82rem; color:#334155; line-height:1.35; vertical-align:top; padding-top:12px;">
                                ${escapeHtml(b.city)}, <strong style="color:#0F172A;">${escapeHtml(b.postcode)}</strong>
                            </td>
                            <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;"><strong style="color:#0F172A; font-size:0.95rem;">${b.quantity}</strong></td>
                            <td style="text-align:right; white-space:nowrap; vertical-align:top; padding-top:12px;"><strong style="color:#0F172A; font-size:0.95rem;">&pound;${parseFloat(b.total_amount).toFixed(2)}</strong></td>
                            <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;">
                                <div style="font-size:0.75rem; font-weight:800; color:#475569; margin-bottom:3px;">
                                    ${b.payment_method === 'paypal' ? 'PayPal' : (b.payment_method === 'bank_transfer' ? 'Bank' : escapeHtml(b.payment_method))}
                                </div>
                                ${payBadge}
                                ${receiptCell}
                            </td>
                            <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;">${bBadge}</td>
                            <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px; display: flex; gap: 4px; justify-content: center; align-items: center; border: none;">
                                <button type="button" class="btn-action-sm btn-view-items" 
                                    data-ref="${b.booking_reference}"
                                    style="background:#D97706 !important; color:#fff !important; border-color:#D97706 !important; padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                    📦 Items (${b.items ? b.items.length : b.quantity})
                                </button>
                                <button type="button" class="btn-action-sm btn-view-booking" 
                                    data-ref="${b.booking_reference}"
                                    style="background:#0F172A !important; color:#fff !important; border-color:#0F172A !important; padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                    View 👁️
                                </button>
                                <button type="button" class="btn-action-sm btn-edit-booking" 
                                    data-ref="${b.booking_reference}" 
                                    data-pstat="${b.payment_status}" 
                                    data-bstat="${b.booking_status || 'CONFIRMED'}"
                                    data-pmeth="${b.payment_method || 'bank_transfer'}"
                                    data-pref="${b.payment_reference || b.paypal_transaction_id || ''}"
                                    style="padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                    Edit ✏️
                                </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Attach HD Lightbox Modal Handlers
                    document.querySelectorAll('.btn-open-hd-modal').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const imgSrc = this.getAttribute('data-img');
                            const ref = this.getAttribute('data-ref');
                            document.getElementById('hd-modal-ref').textContent = ref;
                            const imgEl = document.getElementById('hd-modal-img');
                            imgEl.src = imgSrc;
                            imgEl.classList.remove('zoomed');
                            document.getElementById('hd-modal-download').href = imgSrc;
                            document.getElementById('hd-modal-open-new').href = imgSrc;
                            document.getElementById('payment-proof-modal').style.display = 'flex';
                        });
                    });

                    // Attach Edit Handlers
                    document.querySelectorAll('.btn-edit-booking').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const ref = this.getAttribute('data-ref');
                            const pstat = this.getAttribute('data-pstat');
                            const bstat = this.getAttribute('data-bstat');
                            const pmeth = this.getAttribute('data-pmeth');
                            const pref = this.getAttribute('data-pref');

                            document.getElementById('modal-ref-title').textContent = 'Update Booking ' + ref;
                            document.getElementById('modal-booking-ref').value = ref;
                            document.getElementById('modal-payment-status').value = pstat;
                            document.getElementById('modal-booking-status').value = bstat;
                            document.getElementById('modal-payment-ref').value = pref;

                            // Adjust reference input label & placeholder dynamically
                            const labelEl = document.getElementById('modal-payment-ref-label');
                            const inputEl = document.getElementById('modal-payment-ref');
                            if (pmeth === 'paypal') {
                                labelEl.textContent = 'PayPal Transaction ID / Reference';
                                inputEl.placeholder = 'Enter PayPal transaction ID';
                            } else {
                                labelEl.textContent = 'Bank Transfer Reference / Name';
                                inputEl.placeholder = 'Enter bank reference or name';
                            }

                            document.getElementById('update-status-modal').style.display = 'flex';
                        });
                    });

                    // Attach View Handlers
                    document.querySelectorAll('.btn-view-booking').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const ref = this.getAttribute('data-ref');
                            viewBookingDetails(ref);
                        });
                    });
                });
            }
            window.loadDashboardData = loadDashboardData;

            // Global Click Event Delegation for HD Lightbox Modal Open
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-open-hd-modal');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const imgSrc = btn.getAttribute('data-img');
                    const ref = btn.getAttribute('data-ref');
                    
                    const refEl = document.getElementById('hd-modal-ref');
                    const imgEl = document.getElementById('hd-modal-img');
                    const dlEl = document.getElementById('hd-modal-download');
                    const openEl = document.getElementById('hd-modal-open-new');
                    const modalEl = document.getElementById('payment-proof-modal');

                    if (refEl) refEl.textContent = ref;
                    if (imgEl) {
                        imgEl.src = imgSrc;
                        imgEl.classList.remove('zoomed');
                    }
                    if (dlEl) dlEl.href = imgSrc;
                    if (openEl) openEl.href = imgSrc;
                    if (modalEl) {
                        modalEl.style.display = 'flex';
                    }
                }
            });

            // Ultra HD Lightbox Close & Zoom Listeners
            const btnCloseHdModal = document.getElementById('btn-close-hd-modal');
            const hdModalOverlay = document.getElementById('payment-proof-modal');
            const hdModalImg = document.getElementById('hd-modal-img');

            if (btnCloseHdModal && hdModalOverlay) {
                btnCloseHdModal.addEventListener('click', function() {
                    hdModalOverlay.style.display = 'none';
                });
            }
            if (hdModalImg) {
                hdModalImg.addEventListener('click', function() {
                    this.classList.toggle('zoomed');
                });
            }

            // Search Bar Filter
            const searchInput = document.getElementById('booking-search-input');
            if (searchInput) {
                let timer;
                searchInput.addEventListener('input', function() {
                    clearTimeout(timer);
                    currentSearchQuery = this.value.trim();
                    timer = setTimeout(loadDashboardData, 300);
                });
            }

            // Status Filter Buttons
            document.querySelectorAll('.btn-filter').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    activeStatusFilter = this.getAttribute('data-status');
                    loadDashboardData();
                });
            });

            // Update Status Modal Close
            const closeModal = () => document.getElementById('update-status-modal').style.display = 'none';
            document.getElementById('modal-close-btn').addEventListener('click', closeModal);
            document.getElementById('modal-cancel-btn').addEventListener('click', closeModal);

            // Update Status Form Submit
            document.getElementById('update-status-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('ajax/admin-actions.php?action=update_booking_status', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    closeModal();
                    if (data.success) {
                        loadDashboardData();
                    } else {
                        alert(data.message || 'Error updating status');
                    }
                });
            });

            // Toggle PayPal Secret Visibility
            const btnTogglePaypalSecret = document.getElementById('toggle-paypal-secret-btn');
            const inputPaypalSecret = document.getElementById('setting_paypal_client_secret');
            if (btnTogglePaypalSecret && inputPaypalSecret) {
                btnTogglePaypalSecret.addEventListener('click', function() {
                    if (inputPaypalSecret.type === 'password') {
                        inputPaypalSecret.type = 'text';
                        btnTogglePaypalSecret.textContent = '🔒';
                    } else {
                        inputPaypalSecret.type = 'password';
                        btnTogglePaypalSecret.textContent = '👁️';
                    }
                });
            }

            // Save Settings Form
            document.getElementById('admin-settings-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('btn-save-settings');
                btn.disabled = true;
                btn.textContent = 'Saving Settings...';

                const formData = new FormData(this);

                fetch('ajax/admin-actions.php?action=save_settings', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '&#128190; Save All Settings';
                    alert(data.message || 'Settings saved successfully');
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '&#128190; Save All Settings';
                    alert('Error saving settings');
                });
            });

            // --- UPI SETTINGS & VERIFICATION JS HANDLERS ---
            
            // Live Preview of Uploaded QR Code Image
            const qrFileInput = document.getElementById('upi_qr_file');
            if (qrFileInput) {
                qrFileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const prev = document.getElementById('admin-upi-qr-preview');
                            if (prev) prev.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Save UPI Settings Form Submission
            const upiSettingsForm = document.getElementById('admin-upi-settings-form');
            if (upiSettingsForm) {
                upiSettingsForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btn-save-upi-settings');
                    btn.disabled = true;
                    btn.textContent = '💾 Saving UPI Settings...';

                    const formData = new FormData(this);

                    fetch('ajax/admin-actions.php?action=save_upi_settings', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.textContent = '💾 Save UPI Settings';
                        if (data.success) {
                            alert('✅ UPI Payment Settings saved successfully!');
                            const badge = document.getElementById('upi-status-badge');
                            if (badge) {
                                const isEnabled = document.getElementById('is_enabled').checked;
                                badge.style.background = isEnabled ? '#10B981' : '#EF4444';
                                badge.textContent = isEnabled ? '🟢 ENABLED & ACTIVE' : '🔴 DISABLED';
                            }
                        } else {
                            alert('❌ ' + (data.message || 'Failed to save UPI settings.'));
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.textContent = '💾 Save UPI Settings';
                        alert('❌ Network error while saving UPI settings.');
                    });
                });
            }

            // Load & Render UPI Verification Table
            function loadUpiVerificationTable(filterPendingOnly = false) {
                const tbody = document.getElementById('upi-verification-table-body');
                if (!tbody) return;

                let upiBookings = loadedBookings.filter(b => b.payment_method === 'upi' || b.payment_screenshot || b.payment_proof_image || b.payment_status === 'PAYMENT VERIFICATION PENDING');

                if (filterPendingOnly) {
                    upiBookings = upiBookings.filter(b => b.payment_status === 'PAYMENT VERIFICATION PENDING' || b.booking_status === 'PENDING');
                }

                tbody.innerHTML = '';

                if (upiBookings.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color:#64748B;">No UPI verification orders found.</td></tr>`;
                    return;
                }

                let serial = upiBookings.length;
                upiBookings.forEach(b => {
                    const tr = document.createElement('tr');

                    let pStatus = b.payment_status || 'PAYMENT VERIFICATION PENDING';
                    let pBadge = `<span class="status-pill status-pending">PENDING VERIFY</span>`;

                    if (pStatus === 'PAID') {
                        pBadge = `<span class="status-pill status-paid">PAID</span>`;
                    } else if (pStatus === 'REJECTED') {
                        pBadge = `<span class="status-pill status-cancelled">REJECTED</span>`;
                    } else if (pStatus === 'RE-UPLOAD REQUESTED') {
                        pBadge = `<span class="status-pill status-processing" style="background:#FEF3C7; color:#92400E;">RE-UPLOAD REQ</span>`;
                    }

                    const screenshot = b.payment_screenshot || b.payment_proof_image || '';
                    let imgHtml = `<span style="color:#94A3B8; font-size:0.8rem;">No Image</span>`;

                    if (screenshot) {
                        imgHtml = `
                            <div style="position:relative; display:inline-block; cursor:pointer;" onclick="openScreenshotLightbox('${escapeHtml(screenshot)}', '${escapeHtml(b.booking_reference)}', '${escapeHtml(b.customer_name)}')">
                                <img src="${escapeHtml(screenshot)}" alt="Receipt" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1.5px solid #D4AF37; box-shadow:0 2px 6px rgba(0,0,0,0.15);">
                                <span style="position:absolute; bottom:-4px; right:-4px; background:#4A0B17; color:#FFF; font-size:0.65rem; padding:1px 4px; border-radius:4px; font-weight:700;">🔍 View</span>
                            </div>
                        `;
                    }

                    tr.innerHTML = `
                        <td style="text-align:center; font-weight:700; color:#475569; vertical-align:middle;">${serial--}</td>
                        <td style="white-space:nowrap; vertical-align:middle;">
                            <strong style="color:#4A0B17; font-size:0.88rem; font-family:monospace;">${escapeHtml(b.booking_reference)}</strong>
                            <span style="font-size:0.72rem; color:#64748B; display:block; margin-top:2px;">${(b.created_at || '').substring(0, 16)}</span>
                        </td>
                        <td style="vertical-align:middle;">
                            <div><strong style="color:#0F172A; font-size:0.88rem;">${escapeHtml(b.customer_name)}</strong></div>
                            <div style="font-size:0.78rem; color:#64748B;">📱 ${escapeHtml(b.mobile)} | ✉️ ${escapeHtml(b.email)}</div>
                        </td>
                        <td style="text-align:right; font-weight:800; color:#0F172A; vertical-align:middle;">
                            &pound;${parseFloat(b.total_amount).toFixed(2)}
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                            ${imgHtml}
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                            ${(b.payment_method === 'bank_transfer') ? '<span style="background:#DBEAFE; color:#1E40AF; font-size:0.75rem; font-weight:800; padding:4px 8px; border-radius:6px;">🏛️ BANK TRANSFER</span>' : '<span style="background:#FEF3C7; color:#92400E; font-size:0.75rem; font-weight:800; padding:4px 8px; border-radius:6px;">📱 UPI / QR</span>'}
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                            ${pBadge}
                        </td>
                        <td style="text-align:center; vertical-align:middle; white-space:nowrap;">
                            <button type="button" onclick="verifyUpiPayment('${escapeHtml(b.booking_reference)}', 'approve')" style="background:#10B981; color:#FFF; border:none; padding:5px 10px; border-radius:6px; font-size:0.78rem; font-weight:800; cursor:pointer; margin-right:4px;" title="Approve Payment">
                                ✅ Approve
                            </button>
                            <button type="button" onclick="verifyUpiPayment('${escapeHtml(b.booking_reference)}', 'reject')" style="background:#EF4444; color:#FFF; border:none; padding:5px 10px; border-radius:6px; font-size:0.78rem; font-weight:800; cursor:pointer; margin-right:4px;" title="Reject Payment">
                                ❌ Reject
                            </button>
                            <button type="button" onclick="verifyUpiPayment('${escapeHtml(b.booking_reference)}', 'request_reupload')" style="background:#F59E0B; color:#FFF; border:none; padding:5px 10px; border-radius:6px; font-size:0.78rem; font-weight:800; cursor:pointer;" title="Request Re-upload">
                                🔄 Re-upload
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            window.loadUpiVerificationTable = loadUpiVerificationTable;

            // Admin Verification Action Handler
            function verifyUpiPayment(ref, status) {
                let reason = '';
                if (status === 'reject') {
                    reason = prompt('Enter rejection reason for customer (optional):', 'Payment screenshot not clear or mismatch in transaction details');
                    if (reason === null) return;
                } else if (status === 'request_reupload') {
                    reason = prompt('Enter message for customer explaining re-upload request:', 'Please upload a clearer screenshot showing transaction reference number.');
                    if (reason === null) return;
                }

                const formData = new FormData();
                formData.append('ref', ref);
                formData.append('status', status);
                formData.append('reason', reason || '');
                formData.append('csrf_token', csrfToken);

                fetch('ajax/admin-actions.php?action=verify_upi_payment', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Booking payment status updated successfully!');
                        loadDashboardData();
                        setTimeout(loadUpiVerificationTable, 400);
                    } else {
                        alert('❌ ' + (data.message || 'Failed to update payment status.'));
                    }
                })
                .catch(err => {
                    alert('❌ Connection error while updating payment status.');
                });
            }

            window.verifyUpiPayment = verifyUpiPayment;

            // Filter Pending Buttons
            const btnFilterPending = document.getElementById('btn-filter-upi-pending');
            if (btnFilterPending) {
                let isFiltered = false;
                btnFilterPending.addEventListener('click', function() {
                    isFiltered = !isFiltered;
                    this.textContent = isFiltered ? '📋 Show All UPI Orders' : '🟡 Show Pending Verify Only';
                    loadUpiVerificationTable(isFiltered);
                });
            }

            const btnRefreshUpiTable = document.getElementById('btn-refresh-upi-table');
            if (btnRefreshUpiTable) {
                btnRefreshUpiTable.addEventListener('click', function() {
                    loadDashboardData();
                    setTimeout(loadUpiVerificationTable, 400);
                });
            }

            // Lightbox Modal Functions
            function openScreenshotLightbox(imgUrl, ref, name) {
                let modal = document.getElementById('screenshot-lightbox-modal');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'screenshot-lightbox-modal';
                    modal.style.cssText = 'display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,0.85); backdrop-filter:blur(8px); justify-content:center; align-items:center; padding:20px;';
                    modal.innerHTML = `
                        <div style="background:#FFF; border-radius:16px; max-width:600px; width:100%; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4);">
                            <div style="padding:16px 20px; background:#4A0B17; color:#FFF; display:flex; justify-content:space-between; align-items:center;">
                                <h3 id="lightbox-modal-title" style="margin:0; font-size:1.1rem; font-weight:800; color:#FFF;">Payment Screenshot</h3>
                                <button type="button" onclick="closeScreenshotLightbox()" style="background:none; border:none; color:#FFF; font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
                            </div>
                            <div style="padding:20px; overflow-y:auto; text-align:center; background:#F8FAFC;">
                                <img id="lightbox-modal-img" src="" alt="Screenshot" style="max-width:100%; max-height:60vh; border-radius:10px; border:2px solid #E2E8F0; object-fit:contain;">
                                <div id="lightbox-modal-info" style="margin-top:14px; font-size:0.9rem; color:#475569; font-weight:600;"></div>
                            </div>
                            <div style="padding:14px 20px; background:#FFF; border-top:1px solid #E2E8F0; text-align:right;">
                                <button type="button" onclick="closeScreenshotLightbox()" style="background:#64748B; color:#FFF; border:none; padding:8px 18px; border-radius:8px; font-weight:700; cursor:pointer;">Close</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                }
                document.getElementById('lightbox-modal-img').src = imgUrl;
                document.getElementById('lightbox-modal-title').textContent = 'Payment Screenshot: ' + ref;
                document.getElementById('lightbox-modal-info').textContent = 'Customer: ' + name + ' | Ref: ' + ref;
                modal.style.display = 'flex';
            }

            function closeScreenshotLightbox() {
                const modal = document.getElementById('screenshot-lightbox-modal');
                if (modal) modal.style.display = 'none';
            }

            window.openScreenshotLightbox = openScreenshotLightbox;
            window.closeScreenshotLightbox = closeScreenshotLightbox;

            // Live Enterprise Telemetry Clock Widget
            function updateHeaderClock() {
                const clockEl = document.getElementById('header-live-clock');
                if (clockEl) {
                    const now = new Date();
                    clockEl.textContent = now.toLocaleTimeString('en-GB', { hour12: false }) + ' BST';
                }
            }
            setInterval(updateHeaderClock, 1000);
            updateHeaderClock();

            // Always run initial data load on admin dashboard load
            loadDashboardData();
            loadCatalogData();

            // Close view details modal
            const viewModalCloseBtn = document.getElementById('view-modal-close-btn');
            const viewModalCloseActionBtn = document.getElementById('view-modal-close-action-btn');
            if (viewModalCloseBtn) viewModalCloseBtn.addEventListener('click', closeViewModal);
            if (viewModalCloseActionBtn) viewModalCloseActionBtn.addEventListener('click', closeViewModal);
            
            function closeViewModal() {
                const modal = document.getElementById('view-details-modal');
                if (modal) modal.style.display = 'none';
            }

            function viewBookingDetails(ref) {
                const b = loadedBookings.find(x => x.booking_reference === ref);
                if (!b) return;

                document.getElementById('view-modal-ref-title').textContent = 'Booking Details: ' + ref;

                let pStatusDisplay = b.payment_status;
                if (pStatusDisplay === 'PAYMENT VERIFICATION PENDING') {
                    pStatusDisplay = 'PENDING VERIFY';
                }

                let receiptLink = '<span style="color:#64748B; font-style:italic;">No Proof Uploaded</span>';
                if (b.payment_proof_image) {
                    receiptLink = `<a href="${escapeHtml(b.payment_proof_image)}" target="_blank" style="color:#4A0B17; font-weight:700; text-decoration:underline;">View Uploaded Photo</a>`;
                }

                let address2Text = b.address_line_2 ? `, ${escapeHtml(b.address_line_2)}` : '';

                const html = `
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; border-bottom:1px solid #E2E8F0; padding-bottom:14px;">
                        <div>
                            <div style="font-size:0.75rem; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Booking Date</div>
                            <div style="font-weight:700; color:#0F172A;">${escapeHtml(b.created_at || '')}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Fulfillment Status</div>
                            <div style="font-weight:700; color:#4A0B17;">${escapeHtml(b.booking_status || 'CONFIRMED')}</div>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; border-bottom:1px solid #E2E8F0; padding-bottom:14px;">
                        <h4 style="margin:0 0 10px 0; color:#4A0B17; font-size:0.95rem; border-left:3px solid #D4AF37; padding-left:8px;">Customer Information</h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Full Name</span>
                                <strong style="color:#0F172A;">${escapeHtml(b.customer_name)}</strong>
                            </div>
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">UK Mobile</span>
                                <strong style="color:#0F172A;">${escapeHtml(b.mobile)}</strong>
                            </div>
                            <div style="grid-column:span 2;">
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Email Address</span>
                                <strong style="color:#0F172A;">${escapeHtml(b.email)}</strong>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; border-bottom:1px solid #E2E8F0; padding-bottom:14px;">
                        <h4 style="margin:0 0 10px 0; color:#4A0B17; font-size:0.95rem; border-left:3px solid #D4AF37; padding-left:8px;">Delivery Destination</h4>
                        <div>
                            <span style="font-size:0.78rem; color:#64748B; display:block;">Shipping Address</span>
                            <strong style="color:#0F172A; display:block; line-height:1.4;">
                                ${escapeHtml(b.address_line_1)}${address2Text}<br>
                                ${escapeHtml(b.city)}${b.county ? ', ' + escapeHtml(b.county) : ''}<br>
                                <span style="font-family:monospace; font-size:0.95rem; letter-spacing:0.2px;">${escapeHtml(b.postcode)}</span>, ${escapeHtml(b.country || 'United Kingdom')}
                            </strong>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; border-bottom:1px solid #E2E8F0; padding-bottom:14px;">
                        <h4 style="margin:0 0 10px 0; color:#4A0B17; font-size:0.95rem; border-left:3px solid #D4AF37; padding-left:8px;">Payment & Pricing</h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Payment Method</span>
                                <strong style="color:#0F172A; text-transform:capitalize;">${escapeHtml(b.payment_method).replace('_', ' ')}</strong>
                            </div>
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Payment Status</span>
                                <strong style="color:#4A0B17;">${escapeHtml(b.payment_status)}</strong>
                            </div>
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Payment Reference</span>
                                <strong style="color:#0F172A; font-family:monospace;">${escapeHtml(b.payment_reference || b.paypal_transaction_id || 'None')}</strong>
                            </div>
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Receipt Image</span>
                                <strong>${receiptLink}</strong>
                            </div>
                        </div>

                        <!-- Booking Items List -->
                        <div style="margin-top:10px; margin-bottom:12px; border:1px solid #CBD5E1; border-radius:8px; overflow:hidden;">
                            <div style="background:#4A0B17; color:#FFFFFF; font-weight:800; font-size:0.8rem; padding:8px 12px; display:flex; justify-content:space-between; align-items:center;">
                                <span>📦 BOOKED ITEMS IN ORDER (${b.items ? b.items.length : 1} UNIQUE ITEM(S))</span>
                                <span>TOTAL QTY: ${b.quantity}</span>
                            </div>
                            <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
                                <thead>
                                    <tr style="background:#F8FAFC; border-bottom:1px solid #E2E8F0; text-transform:uppercase; font-size:0.7rem;">
                                        <th style="padding:8px 10px; text-align:left; color:#475569;">Item / Image</th>
                                        <th style="padding:8px; text-align:center; color:#475569; width:50px;">Qty</th>
                                        <th style="padding:8px; text-align:right; color:#475569; width:80px;">Unit Price</th>
                                        <th style="padding:8px 10px; text-align:right; color:#475569; width:85px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${b.items && b.items.length > 0 ? b.items.map(item => {
                                        let img = item.image_path || 'assets/images/ganesh_hero.png';
                                        if (!item.image_path) {
                                            if (item.product_name.includes('Wrapping') || item.product_name.includes('Add-On 1')) img = 'assets/images/rakhi_rudraksha.png';
                                            else if (item.product_name.includes('Chocolate') || item.product_name.includes('Sweet') || item.product_name.includes('Add-On 2')) img = 'assets/images/rakhi_peacock.png';
                                        }
                                        let badge = '<span style="background:#E0F2FE; color:#0369A1; font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:4px;">PRODUCT</span>';
                                        if (item.product_name.includes('Wrapping') || item.product_name.includes('Add-On 1')) badge = '<span style="background:#FEF3C7; color:#92400E; font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:4px;">🎁 ADD-ON 1</span>';
                                        else if (item.product_name.includes('Chocolate') || item.product_name.includes('Sweet') || item.product_name.includes('Add-On 2')) badge = '<span style="background:#FCE7F3; color:#9D174D; font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:4px;">🍫 ADD-ON 2</span>';
                                        let itemSub = (item.quantity * parseFloat(item.price)).toFixed(2);

                                        return `
                                        <tr style="border-bottom:1px solid #F1F5F9;">
                                            <td style="padding:8px 10px; color:#0F172A;">
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <img src="${escapeHtml(img)}" style="width:36px; height:36px; object-fit:cover; border-radius:6px; border:1px solid #CBD5E1;">
                                                    <div>
                                                        ${badge}
                                                        <strong style="color:#0F172A; font-size:0.83rem;">${escapeHtml(item.product_name)}</strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="padding:8px; text-align:center; color:#0F172A; font-weight:800;">${item.quantity}</td>
                                            <td style="padding:8px; text-align:right; color:#475569;">&pound;${parseFloat(item.price).toFixed(2)}</td>
                                            <td style="padding:8px 10px; text-align:right; color:#4A0B17; font-weight:800;">&pound;${itemSub}</td>
                                        </tr>
                                    `}).join('') : `
                                        <tr style="border-bottom:1px solid #F1F5F9;">
                                            <td style="padding:8px 10px; color:#0F172A;">
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <img src="assets/images/ganesh_hero.png" style="width:36px; height:36px; object-fit:cover; border-radius:6px;">
                                                    <div><strong style="color:#0F172A;">Ganesh Statue / Vinayaka Vigraha</strong></div>
                                                </div>
                                            </td>
                                            <td style="padding:8px; text-align:center; color:#0F172A; font-weight:800;">${b.quantity}</td>
                                            <td style="padding:8px; text-align:right; color:#475569;">&pound;${parseFloat(b.unit_price).toFixed(2)}</td>
                                            <td style="padding:8px 10px; text-align:right; color:#4A0B17; font-weight:800;">&pound;${(b.quantity * parseFloat(b.unit_price)).toFixed(2)}</td>
                                        </tr>
                                    `}
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="background:#FFFDF5; border:1px solid #FEF3C7; border-radius:8px; padding:12px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:0.85rem;">
                                <span>Items Subtotal</span>
                                <strong>&pound;${parseFloat(b.subtotal || (b.quantity * b.unit_price)).toFixed(2)}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:0.85rem; padding-bottom:6px; border-bottom:1px dashed #E2E8F0;">
                                <span>Shipping Fee</span>
                                <strong>&pound;${parseFloat(b.shipping_charge).toFixed(2)}</strong>
                            </div>
<div style="display:flex; justify-content:space-between; font-size:1.05rem; font-weight:800; color:#4A0B17;">
                                <span>Total Paid/Payable</span>
                                <strong>&pound;${parseFloat(b.total_amount).toFixed(2)}</strong>
                            </div>
                        </div>
                    </div>
                `;
 
                document.getElementById('view-modal-body-content').innerHTML = html;
                document.getElementById('view-details-modal').style.display = 'flex';
            }

            // Catalog Management functions
            let catalogCategories = [];
            let catalogProducts = [];
            let adminSettings = {};

            function loadCatalogData() {
                fetch('ajax/admin-actions.php?action=admin_get_categories_products')
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    catalogCategories = data.categories || [];
                    catalogProducts = data.products || [];
                    adminSettings = data.settings || {};

                    renderCategoriesTable();
                    renderProductsTable();
                    renderAddonsPanel();
                    populateCategoryDropdown();
                });
            }
            window.loadCatalogData = loadCatalogData;

            function renderAddonsPanel() {
                const container = document.getElementById('admin-addons-status-cards');
                if (!container) return;
                container.innerHTML = '';

                let addon1 = catalogProducts.find(p => p.id == 7 || p.name.includes('Wrapping') || p.name.includes('Add-On 1'));
                let addon2 = catalogProducts.find(p => p.id == 8 || p.name.includes('Chocolate') || p.name.includes('Sweet') || p.name.includes('Sweets') || p.name.includes('Add-On 2'));

                let isGwEnabled = (adminSettings.gift_wrap_enabled !== '0' && adminSettings.enable_gift_wrap !== '0');
                let isCbEnabled = (adminSettings.choc_box_enabled !== '0' && adminSettings.enable_choc_box !== '0');

                let addons = [
                    {
                        key: 'Add-On 1',
                        addonKey: 'gift_wrap',
                        badge: '🎁 ADD-ON 1',
                        defaultName: '🎁 Add-On 1: Festive Gift Wrapping & Card',
                        defaultDesc: 'Luxury golden gift wrap with customized festive greeting card',
                        defaultPrice: 1.99,
                        defaultImg: 'assets/images/rakhi_rudraksha.png',
                        isEnabled: isGwEnabled,
                        data: addon1
                    },
                    {
                        key: 'Add-On 2',
                        addonKey: 'choc_box',
                        badge: '🍫 ADD-ON 2',
                        defaultName: '🍫 Add-On 2: Premium Chocolate & Sweets Box',
                        defaultDesc: 'Luxury assorted Cadbury chocolates & dry fruit sweets box',
                        defaultPrice: 3.99,
                        defaultImg: 'assets/images/rakhi_peacock.png',
                        isEnabled: isCbEnabled,
                        data: addon2
                    }
                ];

                addons.forEach(item => {
                    let p = item.data;
                    let name = p ? p.name : item.defaultName;
                    let price = p ? parseFloat(p.price).toFixed(2) : item.defaultPrice.toFixed(2);
                    let img = p && p.image_path ? p.image_path : item.defaultImg;
                    let isEnabled = item.isEnabled;

                    let card = document.createElement('div');
                    card.style.cssText = `background:#FFFFFF; border:1.5px solid ${isEnabled ? '#FCD34D' : '#CBD5E1'}; border-radius:10px; padding:14px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04);`;
                    card.innerHTML = `
                        <img src="${escapeHtml(img)}" style="width:54px; height:54px; object-fit:cover; border-radius:8px; border:1px solid #E2E8F0;">
                        <div style="flex-grow:1;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="background:#FEF3C7; color:#92400E; font-size:0.7rem; font-weight:800; padding:2px 8px; border-radius:4px; text-transform:uppercase;">${item.badge}</span>
                                ${isEnabled ? 
                                    '<span style="background:#D1FAE5; color:#065F46; font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:12px;">🟢 Active in Cart</span>' : 
                                    '<span style="background:#FEE2E2; color:#991B1B; font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:12px;">🔴 Disabled in Cart</span>'
                                }
                            </div>
                            <strong style="color:#1E293B; font-size:0.92rem; display:block; margin-top:4px;">${escapeHtml(name)}</strong>
                            <div style="color:#4A0B17; font-weight:800; font-size:0.9rem; margin-top:2px;">&pound;${price}</div>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <button type="button" class="btn-toggle-addon" data-addon="${item.addonKey}" data-status="${isEnabled ? '0' : '1'}" style="background:${isEnabled ? '#EF4444' : '#10B981'}; color:#fff; border:none; padding:6px 12px; border-radius:6px; font-weight:700; font-size:0.75rem; cursor:pointer;">
                                ${isEnabled ? 'Disable 🔴' : 'Enable 🟢'}
                            </button>
                            ${p ? `<button type="button" class="btn-action-sm btn-edit-product" data-id="${p.id}" style="padding: 6px 12px; font-weight:700; cursor:pointer;">Edit ✏️</button>` : `<button type="button" class="btn-gold" id="btn-add-addon" style="padding:6px 12px; font-size:0.75rem; font-weight:700;">+ Create</button>`}
                        </div>
                    `;
                    container.appendChild(card);
                });
            }

            function renderCategoriesTable() {
                const tbody = document.getElementById('admin-categories-table-body');
                if (!tbody) return;
                tbody.innerHTML = '';
                
                // Exclude Add-On category from main categories table (Add-Ons have dedicated status panel)
                let mainCategories = catalogCategories.filter(cat => !cat.name.includes('Add-On'));

                if (mainCategories.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:15px; color:#64748B;">No shop categories found.</td></tr>';
                    return;
                }

                mainCategories.forEach(cat => {
                    const tr = document.createElement('tr');
                    const imgPath = cat.image_path || 'assets/images/ganesh_hero.png';
                    const descText = cat.description ? escapeHtml(cat.description) : 'Festive handcrafted items & idol collection.';
                    
                    tr.innerHTML = `
                        <td style="text-align:center; font-weight:700; color:#475569; vertical-align:middle; width:50px;">${cat.id}</td>
                        <td style="text-align:center; vertical-align:middle; width:80px; padding:10px;">
                            <img src="${escapeHtml(imgPath)}" title="${escapeHtml(cat.name)}" class="btn-open-hd-modal" data-img="${escapeHtml(imgPath)}" data-ref="${escapeHtml(cat.name)}" style="width:60px; height:60px; object-fit:cover; border-radius:10px; border:2px solid #D4AF37; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.08); display:inline-block;">
                        </td>
                        <td style="vertical-align:middle;">
                            <strong style="color:#4A0B17; font-size:0.92rem; display:block;">${escapeHtml(cat.name)}</strong>
                        </td>
                        <td style="font-size:0.86rem; color:#475569; vertical-align:middle; max-width:380px;">
                            <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:380px;" title="${descText}">
                                ${descText}
                            </div>
                        </td>
                        <td style="text-align:center; vertical-align:middle; width:150px;">
                            <button type="button" class="btn-action-sm btn-edit-category" data-id="${cat.id}" style="padding: 5px 10px; font-size:0.75rem; cursor:pointer;">Edit ✏️</button>
                            <button type="button" class="btn-action-sm btn-delete-category" data-id="${cat.id}" style="padding: 5px 10px; font-size:0.75rem; background:#EF4444; border-color:#EF4444; color:#fff; cursor:pointer;">Delete 🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function renderProductsTable() {
                const tbody = document.getElementById('admin-products-table-body');
                if (!tbody) return;
                tbody.innerHTML = '';

                // Exclude Add-On items from main products table (Add-Ons have dedicated status panel & belong in Cart)
                let shopProducts = catalogProducts.filter(p => p.id != 7 && p.id != 8 && (!p.category_name || !p.category_name.includes('Add-On')));

                if (shopProducts.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#64748B;">No products found.</td></tr>';
                    return;
                }

                shopProducts.forEach(p => {
                    const tr = document.createElement('tr');
                    const img1 = p.image_path || 'assets/images/ganesh_hero.png';

                    tr.innerHTML = `
                        <td style="text-align:center; vertical-align:middle; width:90px; padding:12px;">
                            <img src="${escapeHtml(img1)}" title="${escapeHtml(p.name)}" class="btn-open-hd-modal" data-img="${escapeHtml(img1)}" data-ref="${escapeHtml(p.name)}" style="width:64px; height:64px; object-fit:cover; border-radius:10px; border:2px solid #D4AF37; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.08); display:inline-block;">
                        </td>
                        <td style="vertical-align:middle;">
                            <strong style="color:#0F172A; font-size:0.9rem; display:block;">${escapeHtml(p.name)}</strong>
                            <span style="font-size:0.75rem; color:#64748B; font-weight:700; text-transform:uppercase;">${escapeHtml(p.category_name)}</span>
                        </td>
                        <td style="font-size:0.8rem; color:#475569; vertical-align:middle; max-width:250px;">
                            ${escapeHtml(p.description || '')}
                        </td>
                        <td style="text-align:right; font-weight:800; color:#4A0B17; vertical-align:middle;">
                            &pound;${parseFloat(p.price).toFixed(2)}
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                            <button type="button" class="btn-action-sm btn-edit-product" data-id="${p.id}" style="padding: 4px 8px; font-size:0.72rem; cursor:pointer;">Edit ✏️</button>
                            <button type="button" class="btn-action-sm btn-delete-product" data-id="${p.id}" style="padding: 4px 8px; font-size:0.72rem; background:#EF4444; border-color:#EF4444; color:#fff; cursor:pointer;">Delete 🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function populateCategoryDropdown() {
                const select = document.getElementById('product-category');
                if (!select) return;
                select.innerHTML = '';
                catalogCategories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.name;
                    select.appendChild(opt);
                });
            }

            // Pure Vanilla JS Global Event Delegation for ALL Catalog & Booking Buttons
            document.addEventListener('click', function(e) {
                // View Booked Items / View Booking Button
                let btnViewItems = e.target.closest('.btn-view-items') || e.target.closest('.btn-view-booking');
                if (btnViewItems) {
                    e.preventDefault();
                    let ref = btnViewItems.getAttribute('data-ref');
                    if (ref) {
                        viewBookingDetails(ref);
                        document.getElementById('view-details-modal').style.display = 'flex';
                    }
                    return;
                }
                // 0. Toggle Addon Status Button
                let btnToggleAddon = e.target.closest('.btn-toggle-addon');
                if (btnToggleAddon) {
                    e.preventDefault();
                    let addonKey = btnToggleAddon.getAttribute('data-addon');
                    let newStatus = btnToggleAddon.getAttribute('data-status');
                    let fd = new FormData();
                    fd.append('addon', addonKey);
                    fd.append('status', newStatus);
                    fd.append('csrf_token', csrfToken);
                    fetch('ajax/admin-actions.php?action=toggle_addon', {
                        method: 'POST',
                        body: fd
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            loadCatalogData();
                        } else {
                            alert(res.message || 'Error toggling add-on status');
                        }
                    });
                    return;
                }

                // 1. Add Category Button
                let btnAddCat = e.target.closest('#btn-add-category');
                if (btnAddCat) {
                    e.preventDefault();
                    document.getElementById('category-id').value = '0';
                    document.getElementById('category-name').value = '';
                    document.getElementById('category-description').value = '';
                    document.getElementById('category-current-image').value = '';
                    document.getElementById('category-image-file').value = '';
                    document.getElementById('category-image-preview-box').style.display = 'none';
                    document.getElementById('category-modal-title').textContent = 'Add Category';
                    document.getElementById('category-modal').style.display = 'flex';
                    return;
                }

                // 2. Add Product Button (Resets Unlimited Gallery)
                let btnAddProd = e.target.closest('#btn-add-product');
                if (btnAddProd) {
                    e.preventDefault();
                    document.getElementById('product-id').value = '0';
                    document.getElementById('product-name').value = '';
                    document.getElementById('product-price').value = '';
                    document.getElementById('product-description').value = '';
                    
                    productGalleryItems = [];
                    renderProductGalleryPreview();
                    
                    document.getElementById('addon-status-toggle-group').style.display = 'none';
                    populateCategoryDropdown();
                    document.getElementById('product-modal-title').textContent = 'Add Product (Unlimited Photos)';
                    document.getElementById('product-modal').style.display = 'flex';
                    return;
                }

                // 3. Add Festive Add-On Shortcut Button
                let btnAddAddon = e.target.closest('#btn-add-addon');
                if (btnAddAddon) {
                    e.preventDefault();
                    document.getElementById('product-id').value = '0';
                    document.getElementById('product-name').value = '🍫 Add-On 2: Premium Chocolate & Sweets Box';
                    document.getElementById('product-price').value = '3.99';
                    document.getElementById('product-description').value = 'Luxury assorted Cadbury chocolates & dry fruit sweets box';
                    
                    productGalleryItems = [
                        { type: 'existing', url: 'assets/images/rakhi_peacock.png', file: null },
                        { type: 'existing', url: 'assets/images/rakhi_rudraksha.png', file: null },
                        { type: 'existing', url: 'assets/images/ganesh_product_2.png', file: null }
                    ];
                    renderProductGalleryPreview();
                    
                    populateCategoryDropdown();
                    const select = document.getElementById('product-category');
                    const addonCat = catalogCategories.find(c => c.name.includes('Add-On'));
                    if (addonCat && select) {
                        select.value = addonCat.id;
                    }
                    
                    document.getElementById('addon-status-toggle-group').style.display = 'block';
                    document.getElementById('product-addon-enabled').checked = true;
                    document.getElementById('product-modal-title').textContent = 'Add Festive Add-On Product';
                    document.getElementById('product-modal').style.display = 'flex';
                    return;
                }

                // 4. Edit Product Button (Fills Unlimited Gallery Images and Previews)
                let btnEditProd = e.target.closest('.btn-edit-product');
                if (btnEditProd) {
                    e.preventDefault();
                    const id = parseInt(btnEditProd.getAttribute('data-id'));
                    const p = catalogProducts.find(x => x.id == id || parseInt(x.id) === id);
                    if (!p) {
                        alert('Product data not found.');
                        return;
                    }

                    populateCategoryDropdown();

                    document.getElementById('product-id').value = p.id;
                    document.getElementById('product-category').value = p.category_id;
                    document.getElementById('product-name').value = p.name;
                    document.getElementById('product-price').value = p.price;
                    document.getElementById('product-description').value = p.description || '';
                    
                    // Parse Unlimited Gallery Images
                    productGalleryItems = [];
                    if (p.gallery_images) {
                        try {
                            let parsed = typeof p.gallery_images === 'string' ? JSON.parse(p.gallery_images) : p.gallery_images;
                            if (Array.isArray(parsed)) {
                                parsed.forEach(imgUrl => {
                                    if (imgUrl) productGalleryItems.push({ type: 'existing', url: imgUrl, file: null });
                                });
                            }
                        } catch (err) {}
                    }
                    if (productGalleryItems.length === 0) {
                        [p.image_path, p.image_path_2, p.image_path_3].forEach(imgUrl => {
                            if (imgUrl) productGalleryItems.push({ type: 'existing', url: imgUrl, file: null });
                        });
                    }
                    renderProductGalleryPreview();
                    
                    let isAddon = p.id == 7 || p.id == 8 || (p.name && (p.name.includes('Wrapping') || p.name.includes('Chocolate') || p.name.includes('Add-On')));
                    let toggleGroup = document.getElementById('addon-status-toggle-group');
                    if (isAddon && toggleGroup) {
                        toggleGroup.style.display = 'block';
                        let isEnabled = true;
                        if (p.id == 7 || (p.name && p.name.includes('Wrapping'))) {
                            isEnabled = (adminSettings.gift_wrap_enabled !== '0' && adminSettings.enable_gift_wrap !== '0');
                        } else {
                            isEnabled = (adminSettings.choc_box_enabled !== '0' && adminSettings.enable_choc_box !== '0');
                        }
                        document.getElementById('product-addon-enabled').checked = isEnabled;
                    } else if (toggleGroup) {
                        toggleGroup.style.display = 'none';
                    }
                    
                    document.getElementById('product-modal-title').textContent = 'Edit Product (Unlimited Gallery)';
                    document.getElementById('product-modal').style.display = 'flex';
                    return;
                }

                // 5. Edit Category Button
                let btnEditCat = e.target.closest('.btn-edit-category');
                if (btnEditCat) {
                    e.preventDefault();
                    const id = parseInt(btnEditCat.getAttribute('data-id'));
                    const cat = catalogCategories.find(c => c.id == id || parseInt(c.id) === id);
                    if (!cat) return;
                    
                    document.getElementById('category-id').value = cat.id;
                    document.getElementById('category-name').value = cat.name || '';
                    document.getElementById('category-description').value = cat.description || '';
                    document.getElementById('category-current-image').value = cat.image_path || '';
                    document.getElementById('category-image-file').value = '';
                    
                    const prevBox = document.getElementById('category-image-preview-box');
                    const prevImg = document.getElementById('category-image-preview-img');
                    if (cat.image_path && prevBox && prevImg) {
                        prevImg.src = cat.image_path;
                        prevBox.style.display = 'flex';
                    } else if (prevBox) {
                        prevBox.style.display = 'none';
                    }

                    document.getElementById('category-modal-title').textContent = 'Edit Category';
                    document.getElementById('category-modal').style.display = 'flex';
                    return;
                }

                // 6. Delete Product Button
                let btnDelProd = e.target.closest('.btn-delete-product');
                if (btnDelProd) {
                    e.preventDefault();
                    const id = btnDelProd.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this product?')) {
                        let fd = new FormData();
                        fd.append('id', id);
                        fd.append('csrf_token', csrfToken);
                        fetch('ajax/admin-actions.php?action=delete_product', {
                            method: 'POST',
                            body: fd
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                loadCatalogData();
                            } else {
                                alert(res.message);
                            }
                        });
                    }
                    return;
                }

                // 7. Delete Category Button
                let btnDelCat = e.target.closest('.btn-delete-category');
                if (btnDelCat) {
                    e.preventDefault();
                    const id = btnDelCat.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this category? All products in it will also be deleted.')) {
                        let fd = new FormData();
                        fd.append('id', id);
                        fd.append('csrf_token', csrfToken);
                        fetch('ajax/admin-actions.php?action=delete_category', {
                            method: 'POST',
                            body: fd
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                loadCatalogData();
                            } else {
                                alert(res.message);
                            }
                        });
                    }
                    return;
                }

                // 8. Close Category Modal
                if (e.target.closest('#category-modal-close-btn') || e.target.closest('#category-modal-cancel-btn')) {
                    e.preventDefault();
                    document.getElementById('category-modal').style.display = 'none';
                    return;
                }

                // 9. Close Product Modal
                if (e.target.closest('#product-modal-close-btn') || e.target.closest('#product-modal-cancel-btn')) {
                    e.preventDefault();
                    document.getElementById('product-modal').style.display = 'none';
                    return;
                }
            });

            // UNLIMITED PRODUCT GALLERY STATE MANAGEMENT & REORDER CONTROLLER
            let productGalleryItems = []; // items: { type: 'existing'|'new', url: string, file: File|null }

            function renderProductGalleryPreview() {
                const grid = document.getElementById('product-gallery-preview-grid');
                const badge = document.getElementById('product-gallery-count-badge');
                if (!grid) return;

                grid.innerHTML = '';
                if (badge) badge.textContent = `${productGalleryItems.length} Image(s) Selected`;

                if (productGalleryItems.length === 0) {
                    grid.innerHTML = `
                        <div style="grid-column:1/-1; text-align:center; padding:24px 12px; color:#94A3B8;">
                            <div style="font-size:1.6rem; margin-bottom:4px;">🖼️</div>
                            <div style="font-weight:700; font-size:0.85rem; color:#64748B;">No Images Selected Yet</div>
                            <div style="font-size:0.75rem; color:#94A3B8;">Click <strong>+ Add Images</strong> above to select unlimited product photos</div>
                        </div>
                    `;
                    return;
                }

                productGalleryItems.forEach((item, index) => {
                    const card = document.createElement('div');
                    card.style.cssText = 'position:relative; background:#F8FAFC; border:1.5px solid #CBD5E1; border-radius:8px; padding:6px; display:flex; flex-direction:column; align-items:center; gap:4px; box-sizing:border-box;';

                    const img = document.createElement('img');
                    img.src = item.url;
                    img.style.cssText = 'width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:6px; border:1px solid #E2E8F0; display:block;';

                    const badgeSpan = document.createElement('span');
                    badgeSpan.style.cssText = 'font-size:0.68rem; font-weight:800; padding:2px 6px; border-radius:4px; margin-top:2px; text-align:center; width:100%; box-sizing:border-box;';
                    if (index === 0) {
                        badgeSpan.style.background = '#FEF3C7';
                        badgeSpan.style.color = '#B45309';
                        badgeSpan.textContent = '⭐ Main Hero';
                    } else {
                        badgeSpan.style.background = '#E2E8F0';
                        badgeSpan.style.color = '#334155';
                        badgeSpan.textContent = `#${index + 1} Gallery`;
                    }

                    // Action Controls: Reorder Left, Reorder Right, Delete
                    const ctrlRow = document.createElement('div');
                    ctrlRow.style.cssText = 'display:flex; justify-content:space-between; width:100%; margin-top:4px; gap:2px;';

                    const btnLeft = document.createElement('button');
                    btnLeft.type = 'button';
                    btnLeft.innerHTML = '‹';
                    btnLeft.title = 'Move Left';
                    btnLeft.disabled = index === 0;
                    btnLeft.style.cssText = 'padding:2px 6px; font-size:0.75rem; font-weight:800; border:1px solid #CBD5E1; background:#FFF; border-radius:4px; cursor:pointer; opacity:' + (index === 0 ? '0.4' : '1');
                    btnLeft.onclick = (e) => {
                        e.preventDefault();
                        if (index > 0) {
                            let temp = productGalleryItems[index];
                            productGalleryItems[index] = productGalleryItems[index - 1];
                            productGalleryItems[index - 1] = temp;
                            renderProductGalleryPreview();
                        }
                    };

                    const btnRight = document.createElement('button');
                    btnRight.type = 'button';
                    btnRight.innerHTML = '›';
                    btnRight.title = 'Move Right';
                    btnRight.disabled = index === productGalleryItems.length - 1;
                    btnRight.style.cssText = 'padding:2px 6px; font-size:0.75rem; font-weight:800; border:1px solid #CBD5E1; background:#FFF; border-radius:4px; cursor:pointer; opacity:' + (index === productGalleryItems.length - 1 ? '0.4' : '1');
                    btnRight.onclick = (e) => {
                        e.preventDefault();
                        if (index < productGalleryItems.length - 1) {
                            let temp = productGalleryItems[index];
                            productGalleryItems[index] = productGalleryItems[index + 1];
                            productGalleryItems[index + 1] = temp;
                            renderProductGalleryPreview();
                        }
                    };

                    const btnDel = document.createElement('button');
                    btnDel.type = 'button';
                    btnDel.innerHTML = '✕';
                    btnDel.title = 'Remove Image';
                    btnDel.style.cssText = 'padding:2px 6px; font-size:0.75rem; font-weight:800; border:1px solid #FCA5A5; background:#FEF2F2; color:#DC2626; border-radius:4px; cursor:pointer;';
                    btnDel.onclick = (e) => {
                        e.preventDefault();
                        productGalleryItems.splice(index, 1);
                        renderProductGalleryPreview();
                    };

                    ctrlRow.appendChild(btnLeft);
                    ctrlRow.appendChild(btnRight);
                    ctrlRow.appendChild(btnDel);

                    card.appendChild(img);
                    card.appendChild(badgeSpan);
                    card.appendChild(ctrlRow);

                    grid.appendChild(card);
                });
            }

            // File selection event handlers for + Add Images & Upload More Images
            const galleryInput = document.getElementById('product-gallery-input');
            const btnAddGalleryImages = document.getElementById('btn-add-product-gallery-images');
            const btnUploadMoreGalleryImages = document.getElementById('btn-upload-more-gallery-images');

            if (btnAddGalleryImages && galleryInput) {
                btnAddGalleryImages.addEventListener('click', () => galleryInput.click());
            }
            if (btnUploadMoreGalleryImages && galleryInput) {
                btnUploadMoreGalleryImages.addEventListener('click', () => galleryInput.click());
            }

            if (galleryInput) {
                galleryInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files || []);
                    files.forEach(file => {
                        const url = URL.createObjectURL(file);
                        productGalleryItems.push({
                            type: 'new',
                            url: url,
                            file: file
                        });
                    });
                    renderProductGalleryPreview();
                    galleryInput.value = '';
                });
            }

            // Form submissions
            const closeCategoryModal = () => document.getElementById('category-modal').style.display = 'none';
            const closeProductModal = () => document.getElementById('product-modal').style.display = 'none';

            document.getElementById('category-form').addEventListener('submit', function(e) {
                e.preventDefault();
                let fd = new FormData(this);
                fd.append('csrf_token', csrfToken);
                
                fetch('ajax/admin-actions.php?action=save_category', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        closeCategoryModal();
                        loadCatalogData();
                    } else {
                        alert(res.message);
                    }
                });
            });

            document.getElementById('product-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                let fd = new FormData();
                fd.append('id', document.getElementById('product-id').value);
                fd.append('category_id', document.getElementById('product-category').value);
                fd.append('name', document.getElementById('product-name').value);
                fd.append('price', document.getElementById('product-price').value);
                fd.append('description', document.getElementById('product-description').value);
                
                let addonEnabledEl = document.getElementById('product-addon-enabled');
                if (addonEnabledEl && addonEnabledEl.checked) {
                    fd.append('addon_enabled', '1');
                } else if (addonEnabledEl) {
                    fd.append('addon_enabled', '0');
                }
                
                fd.append('csrf_token', csrfToken);

                // Append existing image paths & new File objects in exact sequence order
                productGalleryItems.forEach((item, idx) => {
                    if (item.type === 'existing') {
                        fd.append('existing_gallery_images[]', item.url);
                    } else if (item.type === 'new' && item.file) {
                        fd.append('product_gallery_files[]', item.file);
                    }
                });

                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving Product Gallery...';
                }

                fetch('ajax/admin-actions.php?action=save_product', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(res => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Save Product & Photos';
                    }
                    if (res.success) {
                        closeProductModal();
                        loadCatalogData();
                    } else {
                        alert(res.message || 'Error saving product');
                    }
                })
                .catch(err => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Save Product & Photos';
                    }
                    alert('Network error saving product.');
                });
            });

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
        });
    </script>
</body>
</html>
