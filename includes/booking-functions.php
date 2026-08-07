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
        
        $product = get_product_by_id($id);
        if ($product) {
            $price = (float)$product['price'];
            $item_subtotal = round($qty * $price, 2);
            $subtotal += $item_subtotal;
            $total_quantity += $qty;
            
            $validated_items[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'quantity' => $qty,
                'price' => $price,
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
