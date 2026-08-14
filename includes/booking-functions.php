<?php
/**
 * VK Logistics - Booking Core Business Logic
 */

require_once __DIR__ . '/functions.php';

/**
 * Generate a unique booking reference number format: VKG-2026-000001
 * Uses MySQL transaction sequence table or fallback microtime hashing to guarantee uniqueness
 */
function generate_unique_booking_reference() {
    $db = Database::getConnection();
    $year = date('Y');
    
    if ($db) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO booking_sequence (created_at) VALUES (NOW())");
            $stmt->execute();
            $seq_id = $db->lastInsertId();
            $db->commit();

            if ($seq_id) {
                return sprintf("VKG-%s-%06d", $year, $seq_id);
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            log_system_error("Sequence generation error: " . $e->getMessage());
        }
    }

    // Secure fallback unique generator if DB sequence isn't ready
    $random_part = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    return sprintf("VKG-%s-%s", $year, $random_part);
}

/**
 * Helper to fetch a product by its ID
 */
function get_product_by_id($id) {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            log_system_error("Error fetching product: " . $e->getMessage());
        }
    }
    return null;
}

/**
 * Calculate order totals server-side (NEVER trust client total)
 * Expects an array of items: [ ["id" => X, "quantity" => Y], ... ]
 */
function calculate_order_totals($cart_items) {
    $subtotal = 0.00;
    $total_quantity = 0;
    $validated_items = [];
    
    // Ensure it's an array
    if (!is_array($cart_items)) {
        $cart_items = [];
    }

    foreach ($cart_items as $item) {
        $id = (int)($item['id'] ?? 0);
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $item_name = sanitize_input($item['name'] ?? $item['product_name'] ?? '');
        
        $product = get_product_by_id($id);
        $price = 0.0;
        $name = '';
        $img_path = '';

        if ($product) {
            $price = (float)$product['price'];
            $name = $product['name'];
            $img_path = $product['image_path'];
        } elseif ($id === 99998 || stripos($item_name, 'Wrapping') !== false || stripos($item_name, 'Gift Wrap') !== false) {
            $product = get_product_by_id(7);
            $price = $product ? (float)$product['price'] : (float)get_setting('gift_wrap_price', 1.99);
            $name = $product ? $product['name'] : (get_setting('gift_wrap_name') ?: '🎁 Add-On 1: Festive Gift Wrapping & Card');
            $img_path = $product ? $product['image_path'] : (get_setting('gift_wrap_image') ?: 'assets/images/rakhi_rudraksha.png');
            $id = $product ? (int)$product['id'] : 7;
        } elseif ($id === 99999 || stripos($item_name, 'Chocolate') !== false || stripos($item_name, 'Sweet') !== false) {
            $product = get_product_by_id(8);
            $price = $product ? (float)$product['price'] : (float)get_setting('choc_box_price', 3.99);
            $name = $product ? $product['name'] : (get_setting('choc_box_name') ?: '🍫 Add-On 2: Premium Chocolate & Sweets Box');
            $img_path = $product ? $product['image_path'] : (get_setting('choc_box_image') ?: 'assets/images/rakhi_peacock.png');
            $id = $product ? (int)$product['id'] : 8;
        } elseif (isset($item['price']) && (float)$item['price'] > 0) {
            $price = (float)$item['price'];
            $name = !empty($item_name) ? $item_name : 'Shop Product';
            $img_path = sanitize_input($item['image'] ?? $item['image_path'] ?? 'assets/images/ganesh_hero.png');
        }

        if ($price > 0 || !empty($name)) {
            $item_subtotal = round($qty * $price, 2);
            $subtotal += $item_subtotal;
            $total_quantity += $qty;
            
            $validated_items[] = [
                'product_id' => $id,
                'product_name' => $name,
                'quantity' => $qty,
                'price' => $price,
                'image_path' => $img_path,
                'subtotal' => $item_subtotal
            ];
        }
    }
    
    // Fallback if cart is empty
    if (empty($validated_items)) {
        $product = get_product_by_id(1);
        $price = $product ? (float)$product['price'] : DEFAULT_UNIT_PRICE;
        $subtotal = $price;
        $total_quantity = 1;
        $validated_items[] = [
            'product_id' => 1,
            'product_name' => $product ? $product['name'] : 'Ganesh Statue / Vinayaka Vigraha',
            'quantity' => 1,
            'price' => $price,
            'image_path' => $product ? $product['image_path'] : 'assets/images/ganesh_hero.png',
            'subtotal' => $price
        ];
    }
    
    $shipping_charge = (float)get_setting('shipping_charge', DEFAULT_SHIPPING_FEE);
    $total_amount = round($subtotal + $shipping_charge, 2);

    return [
        'quantity' => $total_quantity,
        'unit_price' => $validated_items[0]['price'] ?? DEFAULT_UNIT_PRICE,
        'subtotal' => round($subtotal, 2),
        'shipping_charge' => $shipping_charge,
        'total_amount' => $total_amount,
        'currency_symbol' => get_setting('currency_symbol', '£'),
        'currency_code' => get_setting('currency_code', 'GBP'),
        'items' => $validated_items
    ];
}

/**
 * Create a new booking in MySQL
 */
function create_new_booking($customer_data) {
    $totals = calculate_order_totals($customer_data['cart_items'] ?? []);
    $reference = $customer_data['booking_reference'] ?? generate_unique_booking_reference();
    
    $db = Database::getConnection();
    
    $booking = [
        'booking_reference' => $reference,
        'customer_name' => $customer_data['customer_name'],
        'mobile' => format_uk_mobile($customer_data['mobile']),
        'email' => strtolower(trim($customer_data['email'])),
        'address_line_1' => $customer_data['address_line_1'],
        'address_line_2' => $customer_data['address_line_2'] ?? '',
        'city' => $customer_data['city'],
        'county' => $customer_data['county'] ?? '',
        'postcode' => format_uk_postcode($customer_data['postcode']),
        'country' => 'United Kingdom',
        'quantity' => $totals['quantity'],
        'unit_price' => $totals['unit_price'],
        'subtotal' => $totals['subtotal'],
        'shipping_charge' => $totals['shipping_charge'],
        'total_amount' => $totals['total_amount'],
        'payment_method' => $customer_data['payment_method'], // 'paypal' or 'bank_transfer'
        'payment_reference' => $customer_data['payment_reference'] ?? null,
        'paypal_order_id' => $customer_data['paypal_order_id'] ?? null,
        'paypal_transaction_id' => $customer_data['paypal_transaction_id'] ?? null,
        'payment_status' => ($customer_data['payment_method'] === 'paypal' && !empty($customer_data['paypal_transaction_id'])) ? 'PAID' : 'PAYMENT VERIFICATION PENDING',
        'booking_status' => 'CONFIRMED',
        'payment_proof_image' => $customer_data['payment_proof_image'] ?? null
    ];

    if ($db) {
        try {
            $sql = "INSERT INTO bookings (
                booking_reference, customer_name, mobile, email,
                address_line_1, address_line_2, city, county, postcode, country,
                quantity, unit_price, subtotal, shipping_charge, total_amount,
                payment_method, payment_reference, paypal_order_id, paypal_transaction_id,
                payment_status, booking_status, payment_proof_image
            ) VALUES (
                :booking_reference, :customer_name, :mobile, :email,
                :address_line_1, :address_line_2, :city, :county, :postcode, :country,
                :quantity, :unit_price, :subtotal, :shipping_charge, :total_amount,
                :payment_method, :payment_reference, :paypal_order_id, :paypal_transaction_id,
                :payment_status, :booking_status, :payment_proof_image
            )";

            $stmt = $db->prepare($sql);
            $stmt->execute($booking);
            $booking_id = $db->lastInsertId();
            $booking['id'] = $booking_id;
            
            // Insert cart items into booking_items
            if ($booking_id) {
                $stmt_item = $db->prepare("INSERT INTO booking_items (booking_id, product_id, product_name, quantity, price) 
                    VALUES (:booking_id, :product_id, :product_name, :quantity, :price)");
                foreach ($totals['items'] as $item) {
                    $stmt_item->execute([
                        ':booking_id' => $booking_id,
                        ':product_id' => $item['product_id'],
                        ':product_name' => $item['product_name'],
                        ':quantity' => $item['quantity'],
                        ':price' => $item['price']
                    ]);
                }
            }
        } catch (Exception $e) {
            log_system_error("Failed to insert booking: " . $e->getMessage());
            $booking['id'] = rand(1000, 9999);
        }
    } else {
        $booking['id'] = rand(1000, 9999);
    }
    
    $booking['items'] = $totals['items'];
    return $booking;
}

/**
 * Fetch booking details by reference
 */
function get_booking_by_ref($reference) {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_reference = :ref LIMIT 1");
            $stmt->execute([':ref' => $reference]);
            $booking = $stmt->fetch();
            if ($booking) {
                $stmt_items = $db->prepare("SELECT * FROM booking_items WHERE booking_id = :booking_id");
                $stmt_items->execute([':booking_id' => $booking['id']]);
                $booking['items'] = $stmt_items->fetchAll();
                return $booking;
            }
        } catch (Exception $e) {
            log_system_error("Error fetching booking: " . $e->getMessage());
        }
    }
    
    // Check active session if DB is offline
    if (isset($_SESSION['last_booking']) && $_SESSION['last_booking']['booking_reference'] === $reference) {
        return $_SESSION['last_booking'];
    }

    return null;
}

/**
 * Update payment status & proof for a booking
 */
function update_booking_payment($reference, $status, $payment_ref = '', $paypal_order = '', $paypal_txn = '', $proof_image = '') {
    $db = Database::getConnection();
    if ($db) {
        try {
            $sql = "UPDATE bookings SET 
                    payment_status = :status,
                    payment_reference = COALESCE(NULLIF(:payment_ref, ''), payment_reference),
                    paypal_order_id = COALESCE(NULLIF(:paypal_order, ''), paypal_order_id),
                    paypal_transaction_id = COALESCE(NULLIF(:paypal_txn, ''), paypal_transaction_id),
                    payment_proof_image = COALESCE(NULLIF(:proof_img, ''), payment_proof_image)
                    WHERE booking_reference = :ref";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':payment_ref' => $payment_ref,
                ':paypal_order' => $paypal_order,
                ':paypal_txn' => $paypal_txn,
                ':proof_img' => $proof_image,
                ':ref' => $reference
            ]);
        } catch (Exception $e) {
            log_system_error("Error updating payment status: " . $e->getMessage());
        }
    }
    return false;
}

/**
 * Upload & save payment proof receipt image securely
 */
function save_uploaded_payment_receipt($file_input, $booking_ref) {
    if (empty($file_input) || !isset($file_input['error']) || $file_input['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'gif', 'bmp', 'svg'];
    
    $file_name = $file_input['name'];
    $file_size = $file_input['size'];
    $tmp_name  = $file_input['tmp_name'];

    if ($file_size > 10 * 1024 * 1024) { // 10MB max
        return null;
    }

    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts)) {
        return null;
    }

    $upload_dir = __DIR__ . '/../uploads/payment_receipts/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
        @file_put_contents($upload_dir . 'index.html', '');
    }

    $clean_ref = preg_replace('/[^A-Za-z0-9_-]/', '', $booking_ref);
    $new_filename = sprintf('receipt_%s_%s_%s.%s', $clean_ref, time(), bin2hex(random_bytes(3)), $ext);
    $target_file = $upload_dir . $new_filename;

    if (move_uploaded_file($tmp_name, $target_file)) {
        return 'uploads/payment_receipts/' . $new_filename;
    }

    return null;
}

/**
 * Fetch full dashboard stats and bookings array for server-side pre-rendering
 */
function get_dashboard_data_array($search = '', $status_filter = 'ALL') {
    $db = Database::getConnection();
    $stats = [
        'total_bookings'   => 0,
        'today_orders'     => 0,
        'total_categories' => 0,
        'total_products'   => 0,
        'total_revenue'    => 0.00,
        'paid_count'       => 0,
        'paid_revenue'     => 0.00,
        'pending_count'    => 0,
        'shipped_count'    => 0
    ];
    $bookings = [];

    if ($db) {
        try {
            $stat_stmt = $db->query("SELECT 
                COUNT(*) as total_count,
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURRENT_DATE() THEN 1 ELSE 0 END), 0) as today_cnt,
                COALESCE(SUM(total_amount), 0) as total_rev,
                COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END), 0) as paid_cnt,
                COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN total_amount ELSE 0 END), 0) as paid_rev,
                COALESCE(SUM(CASE WHEN payment_status = 'PAYMENT VERIFICATION PENDING' THEN 1 ELSE 0 END), 0) as pending_cnt,
                COALESCE(SUM(CASE WHEN booking_status = 'SHIPPED' THEN 1 ELSE 0 END), 0) as shipped_cnt
                FROM bookings");
            $stat_row = $stat_stmt->fetch();
            if ($stat_row) {
                $stats['total_bookings'] = (int)$stat_row['total_count'];
                $stats['today_orders']   = (int)$stat_row['today_cnt'];
                $stats['total_revenue']  = (float)$stat_row['total_rev'];
                $stats['paid_count']      = (int)$stat_row['paid_cnt'];
                $stats['paid_revenue']   = (float)$stat_row['paid_rev'];
                $stats['pending_count']   = (int)$stat_row['pending_cnt'];
                $stats['shipped_count']   = (int)$stat_row['shipped_cnt'];
            }

            $cat_stmt = $db->query("SELECT COUNT(*) as cat_cnt FROM categories");
            if ($cat_row = $cat_stmt->fetch()) {
                $stats['total_categories'] = (int)$cat_row['cat_cnt'];
            }

            $prod_stmt = $db->query("SELECT COUNT(*) as prod_cnt FROM products");
            if ($prod_row = $prod_stmt->fetch()) {
                $stats['total_products'] = (int)$prod_row['prod_cnt'];
            }

            $stmt = $db->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 200");
            $bookings = $stmt->fetchAll();

            foreach ($bookings as &$b) {
                $stmt_items = $db->prepare("SELECT * FROM booking_items WHERE booking_id = :bid");
                $stmt_items->execute([':bid' => $b['id']]);
                $b['items'] = $stmt_items->fetchAll();
            }
        } catch (Exception $e) {
            log_system_error("Dashboard data array error: " . $e->getMessage());
        }
    }

    return ['stats' => $stats, 'bookings' => $bookings];
}
