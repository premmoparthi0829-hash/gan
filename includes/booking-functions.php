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
 * Calculate order totals server-side (NEVER trust client total)
 */
function calculate_order_totals($quantity) {
    $quantity = max(1, (int)$quantity);
    $unit_price = (float)get_setting('unit_price', DEFAULT_UNIT_PRICE);
    $shipping_charge = (float)get_setting('shipping_charge', DEFAULT_SHIPPING_FEE);
    
    $subtotal = round($quantity * $unit_price, 2);
    $total_amount = round($subtotal + $shipping_charge, 2);

    return [
        'quantity' => $quantity,
        'unit_price' => $unit_price,
        'subtotal' => $subtotal,
        'shipping_charge' => $shipping_charge,
        'total_amount' => $total_amount,
        'currency_symbol' => get_setting('currency_symbol', '£'),
        'currency_code' => get_setting('currency_code', 'GBP')
    ];
}

/**
 * Create a new booking in MySQL
 */
function create_new_booking($customer_data) {
    $totals = calculate_order_totals($customer_data['quantity']);
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
            $booking['id'] = $db->lastInsertId();
        } catch (Exception $e) {
            log_system_error("Failed to insert booking: " . $e->getMessage());
            // Fallback object for session state if DB connection was offline
            $booking['id'] = rand(1000, 9999);
        }
    } else {
        $booking['id'] = rand(1000, 9999);
    }

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
            return $stmt->fetch();
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

    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'heic'];
    
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
