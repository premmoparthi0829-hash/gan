<?php
/**
 * VK Logistics - Ganesh Statue Booking Website
 * Main Landing & Booking Page
 */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/functions.php';

$settings        = get_all_settings();
$unit_price      = (float)($settings['unit_price']      ?? 14.99);
$shipping_charge = (float)($settings['shipping_charge'] ?? 3.99);
$paypal_client_id = $settings['paypal_client_id'] ?? 'sb';
$paypal_email     = escape_output($settings['paypal_email']    ?? 'payments@vklogistics.co.uk');
$paypal_acc_name  = escape_output($settings['paypal_account_name'] ?? 'VK LOGISTICS LTD');
$csrf_token       = get_csrf_token();
$phone            = escape_output($settings['support_phone'] ?? '+44 7700 900888');
$bank_name        = escape_output($settings['bank_name']         ?? 'Barclays Bank UK');
$bank_acc_name    = escape_output($settings['bank_account_name'] ?? 'VK LOGISTICS LTD');
$bank_sort        = escape_output($settings['bank_sort_code']    ?? '20-45-77');
$bank_acc_num     = escape_output($settings['bank_account_number'] ?? '83920144');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK Logistics | Ganesh Statue Booking UK</title>
    <meta name="description" content="Book your Ganesh Statue / Vinayaka Vigraha for Ganesh Chaturthi with VK Logistics. Doorstep delivery anywhere in the United Kingdom.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">


</head>
<body>

    <!-- Festive Top Bar -->
    <div class="top-festive-bar">
        &#127800; <span class="highlight">Ganesh Chaturthi Special Delivery</span>
        &mdash; Deliveries available exclusively across the <span class="highlight">UK</span>
    </div>

    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="header-queries-box">
                    <span class="queries-label">For any queries call:</span>
                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>" id="header-phone-link" class="header-phone-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.63A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"></path></svg>
                        <span id="header-phone-text"><?php echo $phone; ?></span>
                    </a>
                </div>
                <a href="#" class="btn-gold scroll-to-booking">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    Book Now
                </a>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero-section" id="hero">
        <div class="container">
            <div class="hero-grid">

                <!-- Left: Text Content -->
                <div class="hero-content">
                    <span class="hero-tag">&#10024; Ganesh Chaturthi 2026 Special</span>
                    <h1 class="hero-title">Bring Home <span>Bappa</span> This Ganesh Chaturthi</h1>
                    <p class="hero-subtitle">
                        Welcome <strong>Lord Ganesha</strong> into your home with joy &amp; devotion. Handcrafted eco-friendly Ganesh statues carefully packaged and delivered safely to your doorstep anywhere across the United Kingdom.
                    </p>
                    <!-- Luxury Hero Booking Card -->
                    <div class="hero-booking-card">
                        <div class="hbc-badge-row">
                            <span class="hbc-offer-tag">&#10024; Festive Offer</span>
                            <span class="hbc-uk-tag">UK Doorstep Delivery</span>
                        </div>
                        <div class="hbc-price-row">
                            <div class="hbc-price-main">
                                <span class="hbc-currency">&pound;</span><span id="hbc-price-val" class="display-unit-price"><?php echo number_format($unit_price, 2); ?></span>
                            </div>
                            <div class="hbc-price-details">
                                <div class="hbc-shipping">+ &pound;<span id="hbc-shipping-val" class="display-shipping"><?php echo number_format($shipping_charge, 2); ?></span> UK Shipping</div>
                                <div class="hbc-unit-sub">Complete Statue Package</div>
                            </div>
                        </div>
                        <a href="#" class="hbc-btn scroll-to-booking">
                            &#127983; BOOK YOUR GANESH NOW
                        </a>
                    </div>

                    <div class="hero-trust-list">
                        <div class="trust-item">
                            <div class="check-icon">&#10003;</div> Eco Clay Ganesh Idol
                        </div>
                        <div class="trust-item">
                            <div class="check-icon">&#10003;</div> Mukut &amp; Ornaments Kit
                        </div>
                        <div class="trust-item">
                            <div class="check-icon">&#10003;</div> Safe Protective Box
                        </div>
                        <div class="trust-item">
                            <div class="check-icon">&#10003;</div> Express UK Delivery
                        </div>
                    </div>
                </div>

                <!-- Right: Image Slider -->
                <div class="hero-visual">
                    <div class="hero-card-frame" id="hero-slider-container">
                        <div class="hero-slider-wrapper">
                            <div class="hero-slide active">
                                <img src="assets/images/ganesh_hero.png" alt="Ganesh Statue - Festive Shrine View">
                            </div>
                            <div class="hero-slide">
                                <img src="assets/images/ganesh_product_1.png" alt="Ganesh Statue - Front View">
                            </div>
                            <div class="hero-slide">
                                <img src="assets/images/ganesh_product_2.png" alt="Ganesh Statue - Gold Ornaments Detail">
                            </div>
                            <div class="hero-slide">
                                <img src="assets/images/ganesh_product_3.png" alt="Ganesh Statue - Mandap Shrine Angle">
                            </div>
                            <div class="hero-slide">
                                <img src="assets/images/ganesh_product_4.png" alt="Ganesh Statue - Satin Pedestal View">
                            </div>
                        </div>

                        <!-- Prev / Next Arrows -->
                        <button type="button" class="slider-arrow prev" id="slider-prev" aria-label="Previous Slide">&#8249;</button>
                        <button type="button" class="slider-arrow next" id="slider-next" aria-label="Next Slide">&#8250;</button>

                        <!-- Pagination Dots -->
                        <div class="slider-dots" id="slider-dots">
                            <span class="slider-dot active" data-index="0"></span>
                            <span class="slider-dot" data-index="1"></span>
                            <span class="slider-dot" data-index="2"></span>
                            <span class="slider-dot" data-index="3"></span>
                            <span class="slider-dot" data-index="4"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- PREMIUM BOOKING MODAL - MULTI STEP -->
    <div class="bm-overlay" id="booking-modal-overlay">
        <div class="bm-panel" id="booking-modal">

            <!-- Left: Decorative Festive Panel -->
            <div class="bm-left">
                <div class="bm-left-inner">
                    <div class="bm-deco-top"></div>
                    <div class="bm-festival-tag">&#127800; Ganesh Chaturthi 2026</div>

                    <div class="bm-product-card">
                        <img src="assets/images/ganesh_hero.png" alt="Ganesh Statue" class="bm-product-img">
                        <div class="bm-product-info">
                            <div id="bm-product-name-display" class="bm-product-name"><?php echo escape_output($settings['product_name'] ?? 'Ganesh Statue'); ?></div>
                            <div class="bm-product-price">&pound;<span class="display-unit-price"><?php echo number_format($unit_price, 2); ?></span> <span>+ shipping</span></div>
                        </div>
                    </div>

                    <ul class="bm-trust-list">
                        <li>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            UK Doorstep Delivery
                        </li>
                        <li>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Unique Booking Reference
                        </li>
                        <li>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Safe Packaging Guaranteed
                        </li>
                    </ul>

                    <div class="bm-left-footer">
                        &#127800; Celebrating Faith &amp; Devotion
                    </div>
                    <div class="bm-deco-bottom"></div>
                </div>
            </div>

            <!-- Right: Booking Form -->
            <div class="bm-right">

                <!-- Right Header -->
                <div class="bm-right-header">
                    <div class="bm-steps" id="bm-steps">
                        <div class="bm-step active" data-step="1">
                            <div class="bm-step-circle">1</div>
                            <div class="bm-step-label">Details</div>
                        </div>
                        <div class="bm-step-line"></div>
                        <div class="bm-step" data-step="2">
                            <div class="bm-step-circle">2</div>
                            <div class="bm-step-label">Address</div>
                        </div>
                        <div class="bm-step-line"></div>
                        <div class="bm-step" data-step="3">
                            <div class="bm-step-circle">3</div>
                            <div class="bm-step-label">Payment</div>
                        </div>
                    </div>
                    <button class="bm-close-btn" id="modal-close-btn" aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <!-- Form Area -->
                <div class="bm-form-area">
                    <form id="main-booking-form">
                        <input type="hidden" id="csrf_token" value="<?php echo escape_output($csrf_token); ?>">
                        <input type="hidden" id="form-quantity" value="1">
                        <input type="hidden" id="payment_method" value="bank_transfer">

                        <!-- STEP 1: Personal Details -->
                        <div class="bm-step-panel active" id="step-panel-1">
                            <div class="bm-step-heading">
                                <div class="bm-step-num-badge">1</div>
                                <div>
                                    <div class="bm-step-title">Personal Details</div>
                                    <div class="bm-step-sub">Tell us who you are</div>
                                </div>
                            </div>

                            <!-- Quantity Picker -->
                            <div class="bm-qty-picker">
                                <div class="bm-qty-label-row">
                                    <span class="bm-qty-label">Quantity</span>
                                    <span class="bm-qty-hint">Max 20 statues</span>
                                </div>
                                <div class="bm-qty-row">
                                    <button type="button" class="qty-btn minus bm-qty-btn">&minus;</button>
                                    <input type="number" id="quantity-input" class="bm-qty-input" value="1" min="1" max="20" readonly>
                                    <button type="button" class="qty-btn plus bm-qty-btn">&plus;</button>
                                    <div class="bm-price-pill">
                                        <span id="calc-breakdown">1 &times; &pound;<?php echo number_format($unit_price, 2); ?></span>
                                        <strong id="calc-grand-total">&pound;<?php echo number_format($unit_price + $shipping_charge, 2); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="bm-fields">
                                <div class="bm-field full">
                                    <label for="customer_name">Full Name <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <input type="text" id="customer_name" placeholder="e.g. Rajesh Patel" required>
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="mobile">UK Mobile Number <span class="req">*</span></label>
                                    <div class="bm-phone-group">
                                        <div class="bm-country-box">
                                            <span class="country-flag">🇬🇧</span>
                                            <span style="font-size:0.9rem; font-weight:800; color:var(--color-maroon); padding-right:4px;">+44</span>
                                            <input type="hidden" id="country_code" value="+44">
                                        </div>
                                        <div class="bm-input-wrap" style="flex:1;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 012 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"></path></svg>
                                            <input type="tel" id="mobile" placeholder="7700 900888" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="email">Email <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                        <input type="email" id="email" placeholder="rajesh@example.co.uk" required>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="bm-next-btn" id="step1-next">
                                Continue to Delivery Address
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        </div>

                        <!-- STEP 2: Delivery Address -->
                        <div class="bm-step-panel" id="step-panel-2">
                            <div class="bm-step-heading">
                                <div class="bm-step-num-badge">2</div>
                                <div>
                                    <div class="bm-step-title">UK Delivery Address</div>
                                    <div class="bm-step-sub">Where should we deliver?</div>
                                </div>
                            </div>

                            <div class="bm-fields">
                                <div class="bm-field full">
                                    <label for="address_line_1">Address Line 1 <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                        <input type="text" id="address_line_1" placeholder="House number and street name" required>
                                    </div>
                                </div>
                                <div class="bm-field full">
                                    <label for="address_line_2">Address Line 2 <span style="color:var(--color-text-muted);font-weight:400;">(Optional)</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                                        <input type="text" id="address_line_2" placeholder="Flat, apartment, suite, etc.">
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="city">Town / City <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"></path></svg>
                                        <input type="text" id="city" placeholder="e.g. London, Birmingham" required>
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="postcode">Postcode <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                        <input type="text" id="postcode" placeholder="e.g. SW1A 1AA" required>
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="county">County <span style="color:var(--color-text-muted);font-weight:400;">(Optional)</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                                        <input type="text" id="county" placeholder="e.g. Greater London">
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="country">Country</label>
                                    <div class="bm-input-wrap locked">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0110 0v4"></path></svg>
                                        <input type="text" id="country" value="United Kingdom" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="bm-btn-row">
                                <button type="button" class="bm-back-btn" id="step2-back">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                    Back
                                </button>
                                <button type="button" class="bm-next-btn" id="step2-next">
                                    Review &amp; Pay
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 3: Payment -->
                        <div class="bm-step-panel" id="step-panel-3">
                            <div class="bm-step-heading">
                                <div class="bm-step-num-badge">3</div>
                                <div>
                                    <div class="bm-step-title">Order Summary &amp; Payment</div>
                                    <div class="bm-step-sub">Review and complete your booking</div>
                                </div>
                            </div>

                            <!-- Summary Card -->
                            <div class="bm-summary-card">
                                <div class="bm-summary-row">
                                    <span>Ganesh Statue &times; <strong class="display-qty">1</strong></span>
                                    <span class="display-subtotal">&pound;<?php echo number_format($unit_price, 2); ?></span>
                                </div>
                                <div class="bm-summary-row">
                                    <span>UK Shipping</span>
                                    <span class="display-shipping">&pound;<?php echo number_format($shipping_charge, 2); ?></span>
                                </div>
                                <div class="bm-summary-row total">
                                    <span>Total Payable</span>
                                    <span class="display-total">&pound;<?php echo number_format($unit_price + $shipping_charge, 2); ?></span>
                                </div>
                            </div>

                            <!-- Payment Tabs -->
                            <div class="bm-pay-tabs">
                                <button type="button" class="bm-pay-tab active" id="pay-tab-bank" data-tab="bank-tab">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="14" rx="2"></rect><path d="M2 10h20"></path></svg>
                                    Bank Transfer
                                </button>
                                <button type="button" class="bm-pay-tab" id="pay-tab-paypal" data-tab="paypal-tab">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                    PayPal
                                </button>
                            </div>

                            <!-- Bank Tab -->
                            <div class="bm-pay-panel active" id="bank-tab">
                                <div class="bm-bank-box">
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Account Name</span>
                                        <span id="bank-acc-name-display" class="bm-bank-val"><?php echo $bank_acc_name; ?></span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Bank</span>
                                        <span id="bank-name-display" class="bm-bank-val"><?php echo $bank_name; ?></span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Sort Code</span>
                                        <span id="bank-sort-display" class="bm-bank-val bm-mono"><?php echo $bank_sort; ?></span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Account No.</span>
                                        <span id="bank-num-display" class="bm-bank-val bm-mono"><?php echo $bank_acc_num; ?></span>
                                    </div>
                                </div>
                                <div class="bm-field" style="margin-top:14px;">
                                    <label for="payment_reference">Your Payment Reference <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        <input type="text" id="payment_reference" placeholder="Your name or bank reference">
                                    </div>
                                </div>
                                <div class="bm-field" style="margin-top:14px;">
                                    <label for="payment_proof_file">Upload Payment Receipt Photo <span class="req">*</span></label>
                                    <div class="bm-upload-box" id="receipt-upload-zone">
                                        <input type="file" id="payment_proof_file" accept="image/jpeg,image/png,image/webp,image/heic" style="display:none;">
                                        <div class="upload-drop-content" id="upload-idle-state">
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D4AF37" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                            <div class="upload-text"><strong>Click to select photo</strong> or drag &amp; drop receipt here</div>
                                            <div class="upload-sub">Supports Ultra HD JPG, PNG, WEBP (Up to 10MB)</div>
                                        </div>
                                        <div class="upload-preview-content" id="upload-preview-state" style="display:none;">
                                            <img id="receipt-img-preview" src="" alt="Receipt Photo Preview">
                                            <div class="upload-file-info">
                                                <span id="upload-file-name">receipt.jpg</span>
                                                <button type="button" id="btn-remove-receipt" class="btn-remove-file">&times; Remove Photo</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="bm-submit-btn" id="btn-submit-bank" style="margin-top:16px;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Confirm Bank Transfer Booking
                                </button>
                            </div>

                            <!-- PayPal Tab -->
                            <div class="bm-pay-panel" id="paypal-tab">

                                <!-- PayPal Info Box (mirrors Bank Transfer layout) -->
                                <div class="bm-bank-box">
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Payment Method</span>
                                        <span class="bm-bank-val">PayPal Transfer</span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Send Payment To</span>
                                        <span class="bm-bank-val bm-mono" style="display:flex; align-items:center; gap:6px;">
                                            <span id="paypal-email-display"><?php echo $paypal_email; ?></span>
                                            <button type="button" class="btn-copy-mini" id="btn-copy-paypal-email" title="Copy PayPal Email" style="background:transparent; border:none; color:var(--color-maroon-light); cursor:pointer; padding:2px; display:inline-flex; align-items:center; transition:color 0.2s; outline:none;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                            </button>
                                        </span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Account Name</span>
                                        <span class="bm-bank-val" id="paypal-acc-name-display"><?php echo $paypal_acc_name; ?></span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Currency</span>
                                        <span class="bm-bank-val">GBP (£)</span>
                                    </div>
                                </div>

                                <!-- PayPal Reference Field -->
                                <div class="bm-field" style="margin-top:14px;">
                                    <label for="paypal_reference">Your PayPal Transaction ID <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        <input type="text" id="paypal_reference" placeholder="PayPal transaction ID or reference">
                                    </div>
                                </div>

                                <!-- PayPal Receipt Upload -->
                                <div class="bm-field" style="margin-top:14px;">
                                    <label for="paypal_proof_file">Upload PayPal Payment Screenshot <span class="req">*</span></label>
                                    <div class="bm-upload-box" id="paypal-upload-zone">
                                        <input type="file" id="paypal_proof_file" accept="image/jpeg,image/png,image/webp,image/heic" style="display:none;">
                                        <div class="upload-drop-content" id="paypal-upload-idle">
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D4AF37" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                            <div class="upload-text"><strong>Click to select screenshot</strong> or drag &amp; drop here</div>
                                            <div class="upload-sub">Supports Ultra HD JPG, PNG, WEBP (Up to 10MB)</div>
                                        </div>
                                        <div class="upload-preview-content" id="paypal-upload-preview" style="display:none;">
                                            <img id="paypal-img-preview" src="" alt="PayPal Receipt Preview">
                                            <div class="upload-file-info">
                                                <span id="paypal-file-name">screenshot.jpg</span>
                                                <button type="button" id="btn-remove-paypal-receipt" class="btn-remove-file">&times; Remove Photo</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="bm-submit-btn" id="btn-submit-paypal" style="margin-top:16px;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Confirm PayPal Payment Booking
                                </button>
                            </div>

                            <button type="button" class="bm-back-btn" id="step3-back" style="margin-top:12px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                Back to Address
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>




    <!-- Minimal Bottom Bar -->
    <div class="bottom-bar-minimal">
        &copy; 2026 VK Logistics &mdash; Ganesh Chaturthi UK Delivery &nbsp;|&nbsp;
        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>" id="footer-phone-link"><?php echo $phone; ?></a>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/paypal-integration.js"></script>
</body>
</html>
