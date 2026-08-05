<?php
/**
 * VK Logistics - Single-Page Admin Management Dashboard
 * Theme: Festive Royal Maroon (#4A0B17) & Gold (#D4AF37)
 */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/booking-functions.php';

$is_logged_in = is_admin_logged_in();
$csrf_token   = get_csrf_token();
$settings     = get_all_settings();
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
            
            <!-- KPI Summary Cards -->
            <div class="admin-kpi-grid">
                <div class="admin-kpi-card gold">
                    <div class="admin-kpi-icon">&#128230;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-total-bookings">0</h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
                <div class="admin-kpi-card green">
                    <div class="admin-kpi-icon">&#128176;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-total-revenue">&pound;0.00</h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="admin-kpi-card green">
                    <div class="admin-kpi-icon">&#9989;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-paid-revenue">&pound;0.00</h3>
                        <p>Paid Revenue (<span id="stat-paid-count">0</span> Orders)</p>
                    </div>
                </div>
                <div class="admin-kpi-card saffron">
                    <div class="admin-kpi-icon">&#9203;</div>
                    <div class="admin-kpi-info">
                        <h3 id="stat-pending-count">0</h3>
                        <p>Verification Pending</p>
                    </div>
                </div>
            </div>

            <!-- Single Page Tabs Nav -->
            <div class="admin-tab-nav">
                <button type="button" class="admin-tab-btn active" data-tab="tab-bookings">
                    &#128221; Bookings Management
                </button>
                <button type="button" class="admin-tab-btn" data-tab="tab-settings">
                    &#9881; Store &amp; Pricing Settings
                </button>
                <button type="button" class="admin-tab-btn" data-tab="tab-export">
                    📄 PDF &amp; CSV Reports
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
                                <th style="white-space:nowrap; text-align:left;">Booking Ref</th>
                                <th style="text-align:left;">Customer &amp; Contact</th>
                                <th style="text-align:left;">Delivery Address</th>
                                <th style="white-space:nowrap; text-align:center;">Qty</th>
                                <th style="white-space:nowrap; text-align:right;">Total (£)</th>
                                <th style="white-space:nowrap; text-align:center;">Payment</th>
                                <th style="white-space:nowrap; text-align:center;">Status</th>
                                <th style="white-space:nowrap; text-align:center;">Date</th>
                                <th style="white-space:nowrap; text-align:center;">Receipt</th>
                                <th style="white-space:nowrap; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookings-table-body">
                            <tr>
                                <td colspan="9" style="text-align:center; padding: 30px; color: var(--color-text-muted);">
                                    Loading customer bookings...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: STORE & PRICING SETTINGS -->
            <div class="admin-tab-content admin-panel-card" id="tab-settings" style="display:none;">
                <form id="admin-settings-form">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                    
                    <div class="admin-settings-grid">
                        
                        <!-- Section: Pricing & Product -->
                        <div>
                            <h3 class="settings-section-title">&#127983; Product & Pricing Configuration</h3>
                            
                            <div class="admin-field-group">
                                <label for="setting_product_name">Product Name</label>
                                <input type="text" id="setting_product_name" name="product_name" value="<?php echo escape_output($settings['product_name']); ?>" required>
                            </div>

                            <div class="admin-field-group">
                                <label for="setting_unit_price">Unit Price (&pound; GBP)</label>
                                <input type="number" step="0.01" id="setting_unit_price" name="unit_price" value="<?php echo escape_output($settings['unit_price']); ?>" required>
                            </div>

                            <div class="admin-field-group">
                                <label for="setting_shipping_charge">UK Delivery Fee (&pound; GBP)</label>
                                <input type="number" step="0.01" id="setting_shipping_charge" name="shipping_charge" value="<?php echo escape_output($settings['shipping_charge']); ?>" required>
                            </div>
                        </div>

                        <!-- Section: Bank Account Transfers -->
                        <div>
                            <h3 class="settings-section-title">&#127974; Direct Bank Transfer Details</h3>

                            <div class="admin-field-group">
                                <label for="setting_bank_name">Bank Name</label>
                                <input type="text" id="setting_bank_name" name="bank_name" value="<?php echo escape_output($settings['bank_name']); ?>">
                            </div>

                            <div class="admin-field-group">
                                <label for="setting_bank_account_name">Account Holder Name</label>
                                <input type="text" id="setting_bank_account_name" name="bank_account_name" value="<?php echo escape_output($settings['bank_account_name']); ?>">
                            </div>

                            <div class="admin-field-group">
                                <label for="setting_bank_sort_code">UK Sort Code</label>
                                <input type="text" id="setting_bank_sort_code" name="bank_sort_code" value="<?php echo escape_output($settings['bank_sort_code']); ?>">
                            </div>

                            <div class="admin-field-group">
                                <label for="setting_bank_account_number">Account Number</label>
                                <input type="text" id="setting_bank_account_number" name="bank_account_number" value="<?php echo escape_output($settings['bank_account_number']); ?>">
                            </div>
                        </div>

                        <!-- Section: PayPal & Support -->
                        <div>
                            <h3 class="settings-section-title">&#128179; PayPal & Contact Support</h3>

                            <div class="admin-field-group">
                                <label for="setting_paypal_client_id">PayPal Client ID</label>
                                <input type="text" id="setting_paypal_client_id" name="paypal_client_id" value="<?php echo escape_output($settings['paypal_client_id']); ?>">
                            </div>

                            <div class="admin-field-group">
                                <label for="setting_paypal_mode">PayPal Mode</label>
                                <select id="setting_paypal_mode" name="paypal_mode">
                                    <option value="sandbox" <?php echo ($settings['paypal_mode'] === 'sandbox') ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                                    <option value="live" <?php echo ($settings['paypal_mode'] === 'live') ? 'selected' : ''; ?>>Live Production</option>
                                </select>
                            </div>

                            <div class="admin-field-group">
                                <label for="setting_support_phone">UK Customer Support Helpline</label>
                                <input type="text" id="setting_support_phone" name="support_phone" value="<?php echo escape_output($settings['support_phone']); ?>">
                            </div>

                            <div class="admin-field-group">
                                <label for="setting_admin_password">Admin Portal Passkey</label>
                                <input type="password" id="setting_admin_password" name="admin_password" placeholder="Leave blank to keep current passkey">
                            </div>
                        </div>

                    </div>

                    <div style="margin-top: 24px; text-align: right;">
                        <button type="submit" class="btn-gold" style="padding: 12px 28px; font-size: 1rem; border:none; cursor:pointer;" id="btn-save-settings">
                            &#128190; Save All Settings
                        </button>
                    </div>
                </form>
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

        </main>
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
                    <label for="modal-payment-ref">Payment Txn / Bank Reference</label>
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

    <!-- JAVASCRIPT APP LOGIC -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = "<?php echo escape_output($csrf_token); ?>";
            let activeStatusFilter = 'ALL';
            let currentSearchQuery = '';

            // Tab Switching Logic
            const tabBtns = document.querySelectorAll('.admin-tab-btn');
            const tabContents = document.querySelectorAll('.admin-tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.style.display = 'none');
                    
                    this.classList.add('active');
                    const target = this.getAttribute('data-tab');
                    document.getElementById(target).style.display = 'block';
                });
            });

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

            // Logout Button
            const logoutBtn = document.getElementById('btn-admin-logout');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function() {
                    fetch('ajax/admin-actions.php?action=logout')
                    .then(() => {
                        window.location.reload();
                    });
                });
            }

            // Fetch & Render Dashboard Data
            function loadDashboardData() {
                const url = `ajax/admin-actions.php?action=get_dashboard_data&search=${encodeURIComponent(currentSearchQuery)}&status=${encodeURIComponent(activeStatusFilter)}`;
                
                fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;

                    // Render Stats
                    const stats = data.stats;
                    document.getElementById('stat-total-bookings').textContent = stats.total_bookings;
                    document.getElementById('stat-total-revenue').textContent = '£' + parseFloat(stats.total_revenue).toFixed(2);
                    document.getElementById('stat-paid-revenue').textContent = '£' + parseFloat(stats.paid_revenue).toFixed(2);
                    document.getElementById('stat-paid-count').textContent = stats.paid_count;
                    document.getElementById('stat-pending-count').textContent = stats.pending_count;

                    // Render Table
                    const tbody = document.getElementById('bookings-table-body');
                    tbody.innerHTML = '';

                    if (!data.bookings || data.bookings.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:30px; color: var(--color-text-muted);">No bookings found matching your search.</td></tr>`;
                        return;
                    }

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

                        let receiptCell = `<span class="badge-no-receipt">No Photo</span>`;
                        if (b.payment_proof_image) {
                            receiptCell = `<button type="button" class="btn-view-receipt btn-open-hd-modal" data-img="${escapeHtml(b.payment_proof_image)}" data-ref="${escapeHtml(b.booking_reference)}">📷 View Photo</button>`;
                        }

                        tr.innerHTML = `
                            <td style="white-space:nowrap;"><strong style="color:#4A0B17; font-size:0.86rem; font-family:monospace; letter-spacing:-0.2px;">${b.booking_reference}</strong></td>
                            <td style="min-width:140px;">
                                <div><strong style="color:#0F172A; font-size:0.88rem;">${escapeHtml(b.customer_name)}</strong></div>
                                <div style="font-size:0.8rem; color:#334155; font-weight:600; margin-top:2px;">${escapeHtml(b.mobile)}</div>
                                <div style="font-size:0.78rem; color:#64748B;">${escapeHtml(b.email)}</div>
                            </td>
                            <td style="max-width:200px; font-size:0.82rem; color:#334155; line-height:1.35;">
                                ${escapeHtml(b.address_line_1)}, ${escapeHtml(b.city)}, <strong style="color:#0F172A;">${escapeHtml(b.postcode)}</strong>
                            </td>
                            <td style="text-align:center; white-space:nowrap;"><strong style="color:#0F172A; font-size:0.95rem;">${b.quantity}</strong></td>
                            <td style="text-align:right; white-space:nowrap;"><strong style="color:#0F172A; font-size:0.95rem;">&pound;${parseFloat(b.total_amount).toFixed(2)}</strong></td>
                            <td style="text-align:center; white-space:nowrap;">
                                <div style="text-transform:capitalize; font-size:0.78rem; font-weight:700; color:#334155; margin-bottom:3px;">${(b.payment_method || '').replace('_', ' ')}</div>
                                ${payBadge}
                            </td>
                            <td style="text-align:center; white-space:nowrap;">${bBadge}</td>
                            <td style="text-align:center; white-space:nowrap; font-size:0.82rem; color:#334155; font-weight:700;">${(b.created_at || '').substring(0, 10)}</td>
                            <td style="text-align:center; white-space:nowrap;">${receiptCell}</td>
                            <td style="text-align:center; white-space:nowrap;">
                                <button type="button" class="btn-action-sm btn-edit-booking" 
                                    data-ref="${b.booking_reference}" 
                                    data-pstat="${b.payment_status}" 
                                    data-bstat="${b.booking_status || 'CONFIRMED'}"
                                    data-pref="${b.payment_reference || b.paypal_transaction_id || ''}">
                                    Edit &#9998;
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
                            const pref = this.getAttribute('data-pref');

                            document.getElementById('modal-ref-title').textContent = 'Update Booking ' + ref;
                            document.getElementById('modal-booking-ref').value = ref;
                            document.getElementById('modal-payment-status').value = pstat;
                            document.getElementById('modal-booking-status').value = bstat;
                            document.getElementById('modal-payment-ref').value = pref;

                            document.getElementById('update-status-modal').style.display = 'flex';
                        });
                    });
                });
            }

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
                hdModalOverlay.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.style.display = 'none';
                    }
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

            // Initial load if authenticated
            if (<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
                loadDashboardData();
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
        });
    </script>
</body>
</html>
