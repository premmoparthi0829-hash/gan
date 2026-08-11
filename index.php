<?php
/**
 * VK Logistics - Ganesh Statue Booking Website
 * Main Landing & Booking Page
 */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/functions.php';

$db = Database::getConnection();
$categories = [];
$products = [];
if ($db) {
    try {
        $categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
        $products = $db->query("SELECT * FROM products ORDER BY id ASC")->fetchAll();
    } catch (Exception $e) {
        log_system_error("Failed to load catalog in index.php: " . $e->getMessage());
    }
}

$settings = get_all_settings();
$unit_price = (float) ($settings['unit_price'] ?? 14.99);
$shipping_charge = (float) ($settings['shipping_charge'] ?? 3.99);
$paypal_client_id = $settings['paypal_client_id'] ?? 'sb';
$paypal_email = escape_output($settings['paypal_email'] ?? 'payments@vklogistics.co.uk');
$paypal_id = escape_output($settings['paypal_id'] ?? 'premmoparthi@paypal');
$paypal_acc_name = escape_output($settings['paypal_account_name'] ?? 'VK LOGISTICS LTD');
$csrf_token = get_csrf_token();
$phone = escape_output($settings['support_phone'] ?? '+44 7700 900888');
$bank_name = escape_output($settings['bank_name'] ?? 'Barclays Bank UK');
$bank_acc_name = escape_output($settings['bank_account_name'] ?? 'VK LOGISTICS LTD');
$bank_sort = escape_output($settings['bank_sort_code'] ?? '20-45-77');
$bank_acc_num = escape_output($settings['bank_account_number'] ?? '83920144');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VK Logistics | Ganesh Statue Booking UK</title>
    <meta name="description"
        content="Book your Ganesh Statue / Vinayaka Vigraha for Ganesh Chaturthi with VK Logistics. Doorstep delivery anywhere in the United Kingdom.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/animations.css?v=<?php echo time(); ?>">


</head>

<body>

    <!-- Festive Top Bar -->
    <div class="top-festive-bar">
        &#127800; <span class="highlight">VK Logistics Festive Express</span> &mdash; Handcrafted Ganesh Idols &amp; Designer Rakhis Delivered Across the <span class="highlight">UK</span>
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
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="button" class="btn-header-cart" id="header-cart-trigger">
                        🛒 Cart <span class="header-cart-badge" id="nav-cart-badge">0</span>
                    </button>
                    <a href="#" class="btn-gold scroll-to-booking">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        Book Now
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════
         MAIN E-COMMERCE STORE FRONT
         ═══════════════════════════════════════════ -->
    <main class="shop-main" id="shop-catalog">
        <div class="container">

            <!-- ── STEP 1: Shop Our Collections (100% Identical Product Card Design View) ── -->
            <div id="catalog-step-categories">
                <div class="shop-hero-text">
                    <h1 class="shop-page-title">Shop Our <span>Collections</span></h1>
                </div>

                <div class="cat-2col-grid">
                    <?php foreach ($categories as $cat):
                        $cat_products = array_filter($products, fn($p) => $p['category_id'] == $cat['id']);
                        $count = count($cat_products);
                        $min_price = 14.99;
                        if ($count > 0) {
                            $prices = array_column($cat_products, 'price');
                            $min_price = min($prices);
                        }

                        // Dynamic Price badge logic
                        if (stripos($cat['name'], 'rakhi') !== false) {
                            if ($min_price == 14.99) $min_price = 9.99;
                        } elseif (stripos($cat['name'], 'pooja') !== false || stripos($cat['name'], 'kit') !== false) {
                            if ($min_price == 14.99) $min_price = 9.99;
                        } elseif (stripos($cat['name'], 'decor') !== false || stripos($cat['name'], 'thali') !== false) {
                            if ($min_price == 14.99) $min_price = 19.99;
                        }

                        // Image fallback
                        $cat_img = !empty($cat['image_path']) ? $cat['image_path'] : '';
                        if (empty($cat_img)) {
                            foreach ($cat_products as $p) {
                                if (!empty($p['image_path'])) {
                                    $cat_img = $p['image_path'];
                                    break;
                                }
                            }
                        }

                        // Default description
                        $cat_desc = !empty($cat['description']) ? $cat['description'] : '';
                        if (empty($cat_desc)) {
                            if (stripos($cat['name'], 'ganesh') !== false) {
                                $cat_desc = 'Handcrafted eco-friendly clay Ganesh statue with complete Mukut & ornaments kit.';
                            } elseif (stripos($cat['name'], 'rakhi') !== false) {
                                $cat_desc = 'Exquisite designer handcrafted rakhis for brothers and family celebrations.';
                            } elseif (stripos($cat['name'], 'pooja') !== false) {
                                $cat_desc = 'Sacred eco-friendly visarjan buckets, organic turmeric, kumkum & puja kits.';
                            } else {
                                $cat_desc = 'Traditional handcrafted puja thalis, brass diyas & festive decorations.';
                            }
                        }
                        ?>
                        <div class="product-card-item cat-clean-card" data-cat-id="<?php echo $cat['id']; ?>"
                            data-cat-name="<?php echo escape_output($cat['name']); ?>" role="button" tabindex="0"
                            aria-label="Browse <?php echo escape_output($cat['name']); ?>" style="cursor:pointer;">

                            <div class="prod-img-wrap">
                                <?php if ($cat_img): ?>
                                    <img src="<?php echo escape_output($cat_img); ?>"
                                        alt="<?php echo escape_output($cat['name']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="cat-clean-img-placeholder" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:3rem; position:absolute; top:0; left:0;">🎁</div>
                                <?php endif; ?>
                            </div>

                            <div class="prod-details">
                                <h3 class="prod-name"><?php echo escape_output($cat['name']); ?></h3>
                                <p class="prod-desc"><?php echo escape_output($cat_desc); ?></p>
                                <div class="prod-actions-row">
                                    <span class="btn-shop-collection btn-gold" style="width:100%; text-align:center; display:block;">
                                        Shop Collection &rarr;
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── STEP 2: Products for chosen category ── -->
            <div id="catalog-step-products" style="display:none;">

                <!-- Back + breadcrumb -->
                <div class="cat-products-breadcrumb">
                    <button type="button" id="btn-back-to-categories">&larr; All Categories</button>
                    <span class="cat-products-heading-label" id="active-cat-label"></span>
                </div>

                <h2 class="section-title" id="cat-products-title" style="margin-top:16px;"></h2>
                <p class="section-subtitle" id="cat-products-subtitle"></p>

                <!-- Per-category product panes -->
                <?php foreach ($categories as $cat):
                    $cat_products = array_filter($products, fn($p) => $p['category_id'] == $cat['id']);
                    if (stripos($cat['name'], 'ganesh') !== false) {
                        $accent = '#E85D04';
                    } elseif (stripos($cat['name'], 'rakhi') !== false) {
                        $accent = '#9B1D8A';
                    } else {
                        $accent = '#4A0B17';
                    }
                    ?>
                    <div class="products-grid cat-products-pane" id="products-pane-<?php echo $cat['id']; ?>"
                        style="display:none;">
                        <?php foreach ($cat_products as $prod): ?>
                            <div class="product-card-item"
                                data-id="<?php echo $prod['id']; ?>"
                                data-name="<?php echo escape_output($prod['name']); ?>"
                                data-price="<?php echo $prod['price']; ?>"
                                data-desc="<?php echo escape_output($prod['description']); ?>"
                                data-img="<?php echo escape_output($prod['image_path']); ?>"
                                data-cat="<?php echo escape_output($cat['name']); ?>">
                                <div class="prod-img-wrap" style="cursor:pointer;" title="Click to view product details">
                                    <img src="<?php echo escape_output($prod['image_path']); ?>"
                                        alt="<?php echo escape_output($prod['name']); ?>" loading="lazy">
                                    <span class="prod-price-badge">&pound;<?php echo number_format($prod['price'], 2); ?></span>
                                </div>
                                <div class="prod-details">
                                    <h3 class="prod-name"><?php echo escape_output($prod['name']); ?></h3>
                                    <p class="prod-desc"><?php echo escape_output($prod['description']); ?></p>
                                    <div class="prod-actions-row">
                                        <button type="button" class="btn-add-to-cart btn-gold" data-id="<?php echo $prod['id']; ?>"
                                            data-name="<?php echo escape_output($prod['name']); ?>"
                                            data-price="<?php echo $prod['price']; ?>"
                                            data-img="<?php echo escape_output($prod['image_path']); ?>">
                                            🛒 Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

            </div><!-- /catalog-step-products -->

        </div>
    </main>



    <!-- PREMIUM BOOKING MODAL - MULTI STEP -->
    <div class="bm-overlay" id="booking-modal-overlay">
        <div class="bm-panel" id="booking-modal">

            <!-- Left: Decorative Festive Panel -->
            <div class="bm-left">
                <div class="bm-left-inner">
                    <div class="bm-deco-top"></div>
                    <div class="bm-festival-tag">&#127800; Festive Booking 2026</div>

                    <div class="bm-checkout-cart-items-wrapper"
                        style="width:100%; margin-top:20px; margin-bottom:20px;">
                        <h4
                            style="color:#D4AF37; font-family:'Cinzel', serif; font-size:1.1rem; margin-bottom:12px; border-bottom:1px solid rgba(212,175,55,0.3); padding-bottom:6px;">
                            Your Order Items</h4>
                        <div id="checkout-cart-items-list"
                            style="display:flex; flex-direction:column; gap:10px; max-height:220px; overflow-y:auto; padding-right:5px;">
                            <!-- Dynamic list of cart items in modal left pane -->
                        </div>
                    </div>

                    <ul class="bm-trust-list">
                        <li>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            UK Doorstep Delivery
                        </li>
                        <li>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Unique Booking Reference
                        </li>
                        <li>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
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
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
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

                            <!-- Cart Items Summary in Step 1 -->
                            <div class="bm-qty-picker" style="border-bottom:none; margin-bottom:15px;">
                                <div class="bm-qty-label-row">
                                    <span class="bm-qty-label">Order Items Review</span>
                                    <span class="bm-qty-hint" id="checkout-total-items-text">Total Items: 0</span>
                                </div>
                                <div id="step1-cart-items-table"
                                    style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:12px; margin-top:8px; display:flex; flex-direction:column; gap:8px; max-height:180px; overflow-y:auto;">
                                    <!-- List of items to verify -->
                                </div>
                                <div style="display:flex; flex-direction:column; gap:6px; margin-top:12px; padding-top:12px; border-top:1px dashed #CBD5E1; font-size:0.88rem;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <span style="color:#475569; font-weight:600;">Items Subtotal:</span>
                                        <strong style="color:#0F172A;" id="step1-grand-total">&pound;0.00</strong>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <span style="color:#475569; font-weight:600;">🚚 UK Doorstep Delivery Fee:</span>
                                        <strong style="color:#0070BA;" class="display-shipping">&pound;<?php echo number_format($shipping_charge, 2); ?></strong>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px dashed #E2E8F0; padding-top:6px; margin-top:2px;">
                                        <span style="color:#4A0B17; font-weight:800; font-size:0.95rem;">Estimated Total Payable:</span>
                                        <strong style="font-size:1.15rem; color:#4A0B17; font-weight:800;" class="display-total">&pound;0.00</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="bm-fields">
                                <div class="bm-field full">
                                    <label for="customer_name">Full Name <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <input type="text" id="customer_name" placeholder="e.g. Rajesh Patel" required>
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="mobile">UK Mobile Number <span class="req">*</span></label>
                                    <div class="bm-phone-group">
                                        <div class="bm-country-box">
                                            <span class="country-flag">🇬🇧</span>
                                            <span
                                                style="font-size:0.9rem; font-weight:800; color:var(--color-maroon); padding-right:4px;">+44</span>
                                            <input type="hidden" id="country_code" value="+44">
                                        </div>
                                        <div class="bm-input-wrap" style="flex:1;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                                <path
                                                    d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 012 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z">
                                                </path>
                                            </svg>
                                            <input type="tel" id="mobile" placeholder="7700 900888" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="email">Email <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <path
                                                d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                            </path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                        <input type="email" id="email" placeholder="rajesh@example.co.uk" required>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="bm-next-btn" id="step1-next">
                                Continue to Delivery Address
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
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
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                        </svg>
                                        <input type="text" id="address_line_1"
                                            placeholder="House number and street name" required>
                                    </div>
                                </div>
                                <div class="bm-field full">
                                    <label for="address_line_2">Address Line 2 <span
                                            style="color:var(--color-text-muted);font-weight:400;">(Optional)</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <line x1="8" y1="6" x2="21" y2="6"></line>
                                            <line x1="8" y1="12" x2="21" y2="12"></line>
                                            <line x1="8" y1="18" x2="21" y2="18"></line>
                                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                        </svg>
                                        <input type="text" id="address_line_2"
                                            placeholder="Flat, apartment, suite, etc.">
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="city">Town / City <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="2" y1="12" x2="22" y2="12"></line>
                                            <path
                                                d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z">
                                            </path>
                                        </svg>
                                        <input type="text" id="city" placeholder="e.g. London, Birmingham" required>
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="postcode">Postcode <span class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <input type="text" id="postcode" placeholder="e.g. SW1A 1AA" required>
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="county">County <span
                                            style="color:var(--color-text-muted);font-weight:400;">(Optional)</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                                        </svg>
                                        <input type="text" id="county" placeholder="e.g. Greater London">
                                    </div>
                                </div>
                                <div class="bm-field">
                                    <label for="country">Country</label>
                                    <div class="bm-input-wrap locked">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0110 0v4"></path>
                                        </svg>
                                        <input type="text" id="country" value="United Kingdom" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="bm-btn-row">
                                <button type="button" class="bm-back-btn" id="step2-back">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5" stroke-linecap="round">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                    Back
                                </button>
                                <button type="button" class="bm-next-btn" id="step2-next">
                                    Review &amp; Pay
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5" stroke-linecap="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
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
                                <div id="checkout-final-items-list"
                                    style="display:flex; flex-direction:column; gap:6px; margin-bottom:10px;">
                                    <!-- Dynamic list of items -->
                                </div>
                                <div class="bm-summary-row"
                                    style="margin-top:8px; padding-top:8px; border-top:1px dashed rgba(74, 11, 23, 0.1);">
                                    <span>UK Shipping</span>
                                    <span
                                        class="display-shipping">&pound;<?php echo number_format($shipping_charge, 2); ?></span>
                                </div>
                                <div class="bm-summary-row total">
                                    <span>Total Payable</span>
                                    <span class="display-total">&pound;0.00</span>
                                </div>
                            </div>

                            <!-- Payment Tabs -->
                            <div class="bm-pay-tabs">
                                <button type="button" class="bm-pay-tab active" id="pay-tab-bank" data-tab="bank-tab">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="6" width="20" height="14" rx="2"></rect>
                                        <path d="M2 10h20"></path>
                                    </svg>
                                    Bank Transfer
                                </button>
                                <button type="button" class="bm-pay-tab" id="pay-tab-paypal" data-tab="paypal-tab">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="1" y="4" width="22" height="16" rx="2"></rect>
                                        <line x1="1" y1="10" x2="23" y2="10"></line>
                                    </svg>
                                    PayPal
                                </button>
                            </div>

                            <!-- Bank Tab -->
                            <div class="bm-pay-panel active" id="bank-tab">
                                <div class="bm-bank-box">
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Account Name</span>
                                        <span id="bank-acc-name-display"
                                            class="bm-bank-val"><?php echo $bank_acc_name; ?></span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Bank</span>
                                        <span id="bank-name-display"
                                            class="bm-bank-val"><?php echo $bank_name; ?></span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Sort Code</span>
                                        <span id="bank-sort-display"
                                            class="bm-bank-val bm-mono"><?php echo $bank_sort; ?></span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Account No.</span>
                                        <span id="bank-num-display"
                                            class="bm-bank-val bm-mono"><?php echo $bank_acc_num; ?></span>
                                    </div>
                                </div>
                                <div class="bm-field" style="margin-top:14px;">
                                    <label for="payment_reference">Your Payment Reference <span
                                            class="req">*</span></label>
                                    <div class="bm-input-wrap">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        <input type="text" id="payment_reference"
                                            placeholder="Your name or bank reference">
                                    </div>
                                </div>
                                <div class="bm-field" style="margin-top:14px;">
                                    <label for="payment_proof_file">Upload Payment Receipt Photo <span
                                            class="req">*</span></label>
                                    <div class="bm-upload-box" id="receipt-upload-zone">
                                        <input type="file" id="payment_proof_file"
                                            accept="image/jpeg,image/png,image/webp,image/heic" style="display:none;">
                                        <div class="upload-drop-content" id="upload-idle-state">
                                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D4AF37"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                            <div class="upload-text"><strong>Click to select photo</strong> or drag
                                                &amp; drop receipt here</div>
                                            <div class="upload-sub">Supports Ultra HD JPG, PNG, WEBP (Up to 10MB)</div>
                                        </div>
                                        <div class="upload-preview-content" id="upload-preview-state"
                                            style="display:none;">
                                            <img id="receipt-img-preview" src="" alt="Receipt Photo Preview">
                                            <div class="upload-file-info">
                                                <span id="upload-file-name">receipt.jpg</span>
                                                <button type="button" id="btn-remove-receipt"
                                                    class="btn-remove-file">&times; Remove Photo</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="bm-submit-btn" id="btn-submit-bank"
                                    style="margin-top:16px;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.5" stroke-linecap="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Confirm Bank Transfer Booking
                                </button>
                            </div>

                            <!-- PayPal Tab — Live Checkout -->
                            <div class="bm-pay-panel" id="paypal-tab">

                                <!-- Info strip -->
                                <div class="bm-bank-box" style="margin-bottom:18px;">
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Payment Method</span>
                                        <span class="bm-bank-val">PayPal Live &amp; Express Checkout</span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Currency</span>
                                        <span class="bm-bank-val"><?php echo escape_output($settings['currency_code'] ?? 'GBP'); ?> (&pound;)</span>
                                    </div>
                                    <div class="bm-bank-row">
                                        <span class="bm-bank-key">Merchant</span>
                                        <span class="bm-bank-val"><?php echo $paypal_acc_name; ?></span>
                                    </div>
                                </div>

                                <!-- Live PayPal Smart Buttons Container -->
                                <div id="paypal-button-container" style="min-height:48px; margin-bottom:12px;"></div>

                                <!-- High Visibility Direct PayPal Checkout Button -->
                                <button type="button" class="bm-submit-btn" id="btn-submit-paypal" style="background: linear-gradient(135deg, #FFC439 0%, #FFB703 100%); color: #003087; font-weight: 800; font-size: 1.05rem; border: none; box-shadow: 0 4px 14px rgba(255, 196, 57, 0.4); display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .761-.645h6.637c3.15 0 5.48.665 6.435 2.1.84 1.258.825 2.92.008 4.777-.923 2.1-2.736 3.49-5.184 3.49H10.15a.77.77 0 0 0-.76.645l-.76 4.814a.641.641 0 0 1-.633.536z"/></svg>
                                    Pay Now with PayPal
                                </button>

                                <p style="text-align:center; font-size:0.78rem; color:#64748B; margin-top:12px;">
                                    🔒 You will be securely redirected to PayPal's encrypted payment checkout. No card details are stored on this site.
                                </p>

                            </div>

                            <button type="button" class="bm-back-btn" id="step3-back" style="margin-top:12px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                                Back to Address
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- PRODUCT INFO QUICK VIEW MODAL -->
    <div class="prod-modal-overlay" id="product-info-modal-overlay">
        <div class="prod-modal-card" id="product-info-modal">
            <button type="button" class="prod-modal-close" id="btn-close-prod-modal" aria-label="Close">&times;</button>
            <div class="prod-modal-grid">
                <div class="prod-modal-media">
                    <img id="pmodal-img" src="" alt="Product Image Preview">
                    <span class="pmodal-price-tag" id="pmodal-price">&pound;0.00</span>
                </div>
                <div class="prod-modal-info">
                    <div>
                        <span class="pmodal-badge" id="pmodal-category">Festive Item</span>
                        <h2 class="pmodal-title" id="pmodal-name">Product Name</h2>
                        <div class="pmodal-desc-box">
                            <h4>Product Description &amp; Details</h4>
                            <p id="pmodal-desc">Detailed product description...</p>
                        </div>
                        <div class="pmodal-highlights">
                            <div class="pm-highlight-item">
                                <span class="pm-icon">🌱</span> 100% Eco-Friendly &amp; Handcrafted
                            </div>
                            <div class="pm-highlight-item">
                                <span class="pm-icon">🚚</span> Doorstep UK Delivery Guaranteed
                            </div>
                            <div class="pm-highlight-item">
                                <span class="pm-icon">🛡️</span> Breakage-Safe Protective Packaging
                            </div>
                        </div>
                    </div>
                    <div class="pmodal-actions">
                        <button type="button" class="btn-gold pmodal-add-btn" id="pmodal-add-cart-btn">
                            🛒 Add to Cart &bull; <span id="pmodal-btn-price">&pound;0.00</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FLOATING CART BUTTON -->
    <button type="button" class="floating-cart-toggle" id="cart-toggle-btn" aria-label="View Shopping Cart">
        <span class="cart-icon">🛒</span>
        <span class="cart-count-badge" id="cart-total-badge">0</span>
    </button>

    <!-- SHOPPING CART SIDEBAR -->
    <div class="cart-sidebar-overlay" id="cart-overlay"></div>
    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h3>Your Festive Cart</h3>
            <button type="button" class="cart-close-btn" id="cart-close-btn">&times;</button>
        </div>
        <div class="cart-items-container" id="cart-items-list">
            <!-- Dynamic Cart Items -->
            <div class="cart-empty-state">
                <span style="font-size:3rem; display:block; margin-bottom:12px;">🛒</span>
                Your cart is empty.<br>Add items from the catalog above!
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-summary-row">
                <span>Subtotal:</span>
                <span id="cart-subtotal-val">&pound;0.00</span>
            </div>
            <div class="cart-summary-row">
                <span>UK Shipping:</span>
                <span id="cart-shipping-val">&pound;<?php echo number_format($shipping_charge, 2); ?></span>
            </div>
            <div class="cart-summary-row total">
                <span>Total Payable:</span>
                <span id="cart-total-val">&pound;<?php echo number_format($shipping_charge, 2); ?></span>
            </div>
            <button type="button" class="cart-checkout-btn" id="cart-checkout-btn" disabled>
                Proceed to Booking Checkout
            </button>
        </div>
    </div>

    <!-- TRACK ORDER MODAL -->
    <div class="track-modal-overlay" id="track-modal-overlay">
        <div class="track-modal-card">
            <button type="button" class="track-modal-close" id="btn-close-track-modal" aria-label="Close">&times;</button>
            <h3 class="track-modal-title">Track Your Booking Status</h3>
            <p class="track-modal-sub">Enter your unique Booking Reference (e.g. VKG-2026-000101) to check real-time status.</p>
            <div class="track-input-group">
                <input type="text" id="track-ref-input" placeholder="VKG-2026-XXXXXX" uppercase>
                <button type="button" class="btn-gold" id="btn-search-tracking">Search Status</button>
            </div>
            <div id="track-results-box" style="display:none; margin-top:20px;"></div>
        </div>
    </div>

    <!-- Minimal Bottom Bar -->
    <div class="bottom-bar-minimal">
        &copy; 2026 VK Logistics &mdash; Ganesh Chaturthi UK Delivery &nbsp;|&nbsp;
        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>"
            id="footer-phone-link"><?php echo $phone; ?></a>
    </div>

    <!-- JavaScript -->
    <script>
        window.VK_PAYPAL_CONFIG = {
            mode: "<?php echo escape_output($settings['paypal_mode'] ?? 'sandbox'); ?>",
            clientId: "<?php echo escape_output($settings['paypal_client_id'] ?? 'sb'); ?>",
            currency: "<?php echo escape_output($settings['currency_code'] ?? 'GBP'); ?>",
            status: "<?php echo escape_output($settings['paypal_status'] ?? 'enabled'); ?>"
        };
    </script>
    <!-- PayPal JS SDK — loads live or sandbox button asynchronously for ultra-fast page load -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo urlencode($paypal_client_id); ?>&currency=<?php echo urlencode($settings['currency_code'] ?? 'GBP'); ?>&intent=capture" data-namespace="paypal" async defer></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/paypal-integration.js?v=<?php echo time(); ?>"></script>
</body>

</html>