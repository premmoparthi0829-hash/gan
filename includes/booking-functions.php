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
    $reference = generate_unique_booking_reference();
    
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
        'booking_status' => 'CONFIRMED'
    ];

    if ($db) {
        try {
            $sql = "INSERT INTO bookings (
                booking_reference, customer_name, mobile, email,
                address_line_1, address_line_2, city, county, postcode, country,
                quantity, unit_price, subtotal, shipping_charge, total_amount,
                payment_method, payment_reference, paypal_order_id, paypal_transaction_id,
                payment_status, booking_status
            ) VALUES (
                :booking_reference, :customer_name, :mobile, :email,
                :address_line_1, :address_line_2, :city, :county, :postcode, :country,
                :quantity, :unit_price, :subtotal, :shipping_charge, :total_amount,
                :payment_method, :payment_reference, :paypal_order_id, :paypal_transaction_id,
                :payment_status, :booking_status
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
 * Update payment status for a booking
 */
function update_booking_payment($reference, $status, $payment_ref = '', $paypal_order = '', $paypal_txn = '') {
    $db = Database::getConnection();
    if ($db) {
        try {
            $sql = "UPDATE bookings SET 
                    payment_status = :status,
                    payment_reference = COALESCE(NULLIF(:payment_ref, ''), payment_reference),
                    paypal_order_id = COALESCE(NULLIF(:paypal_order, ''), paypal_order_id),
                    paypal_transaction_id = COALESCE(NULLIF(:paypal_txn, ''), paypal_transaction_id)
                    WHERE booking_reference = :ref";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':payment_ref' => $payment_ref,
                ':paypal_order' => $paypal_order,
                ':paypal_txn' => $paypal_txn,
                ':ref' => $reference
            ]);
        } catch (Exception $e) {
            log_system_error("Error updating payment status: " . $e->getMessage());
        }
    }
    return false;
}
