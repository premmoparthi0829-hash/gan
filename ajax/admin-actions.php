<?php
/**
 * VK Logistics - Admin Actions AJAX Handler
 */

require_once __DIR__ . '/../includes/booking-functions.php';

// Action router
$action = sanitize_input($_REQUEST['action'] ?? '');

// Handle Login
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, 'Invalid request method', [], 405);
    }
    $password = $_POST['password'] ?? '';
    if (verify_admin_password($password)) {
        set_admin_logged_in(true);
        json_response(true, 'Authentication successful');
    } else {
        json_response(false, 'Invalid admin passkey', [], 401);
    }
}

// Handle Logout
if ($action === 'logout') {
    admin_logout();
    json_response(true, 'Logged out successfully');
}

// Check admin authentication for all subsequent actions
if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized. Please login.', ['logged_in' => false], 401);
}

// CSRF Validation for POST operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['update_booking_status', 'save_settings'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        json_response(false, 'Security token expired. Please refresh page.', [], 403);
    }
}

// 1. Fetch Dashboard Stats & Filtered Bookings
if ($action === 'get_dashboard_data') {
    $db = Database::getConnection();
    
    $search = sanitize_input($_GET['search'] ?? '');
    $status_filter = sanitize_input($_GET['status'] ?? 'ALL');

    $bookings = [];
    $stats = [
        'total_bookings' => 0,
        'total_revenue' => 0.00,
        'paid_count' => 0,
        'paid_revenue' => 0.00,
        'pending_count' => 0,
        'shipped_count' => 0
    ];

    if ($db) {
        try {
            // Aggregate stats
            $stat_stmt = $db->query("SELECT 
                COUNT(*) as total_count,
                COALESCE(SUM(total_amount), 0) as total_rev,
                COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END), 0) as paid_cnt,
                COALESCE(SUM(CASE WHEN payment_status = 'PAID' THEN total_amount ELSE 0 END), 0) as paid_rev,
                COALESCE(SUM(CASE WHEN payment_status = 'PAYMENT VERIFICATION PENDING' THEN 1 ELSE 0 END), 0) as pending_cnt,
                COALESCE(SUM(CASE WHEN booking_status = 'SHIPPED' THEN 1 ELSE 0 END), 0) as shipped_cnt
                FROM bookings");
            $stat_row = $stat_stmt->fetch();
            if ($stat_row) {
                $stats['total_bookings'] = (int)$stat_row['total_count'];
                $stats['total_revenue']  = (float)$stat_row['total_rev'];
                $stats['paid_count']      = (int)$stat_row['paid_cnt'];
                $stats['paid_revenue']   = (float)$stat_row['paid_rev'];
                $stats['pending_count']   = (int)$stat_row['pending_cnt'];
                $stats['shipped_count']   = (int)$stat_row['shipped_cnt'];
            }

            // Build query for bookings list
            $where = [];
            $params = [];

            if (!empty($search)) {
                $where[] = "(booking_reference LIKE :s OR customer_name LIKE :s OR email LIKE :s OR mobile LIKE :s OR postcode LIKE :s OR city LIKE :s)";
                $params[':s'] = "%$search%";
            }

            if ($status_filter !== 'ALL' && !empty($status_filter)) {
                if (in_array($status_filter, ['PAID', 'PAYMENT VERIFICATION PENDING', 'FAILED', 'CANCELLED'])) {
                    $where[] = "payment_status = :pstat";
                    $params[':pstat'] = $status_filter;
                } elseif (in_array($status_filter, ['CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED'])) {
                    $where[] = "booking_status = :bstat";
                    $params[':bstat'] = $status_filter;
                }
            }

            $sql = "SELECT * FROM bookings";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            $sql .= " ORDER BY id DESC LIMIT 200";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();
        } catch (Exception $e) {
            log_system_error("Admin get_dashboard_data error: " . $e->getMessage());
        }
    } else {
        // Session fallback if DB offline
        if (isset($_SESSION['last_booking'])) {
            $b = $_SESSION['last_booking'];
            $bookings[] = $b;
            $stats['total_bookings'] = 1;
            $stats['total_revenue'] = (float)$b['total_amount'];
            if ($b['payment_status'] === 'PAID') {
                $stats['paid_count'] = 1;
                $stats['paid_revenue'] = (float)$b['total_amount'];
            } else {
                $stats['pending_count'] = 1;
            }
        }
    }

    json_response(true, 'Data fetched successfully', [
        'stats' => $stats,
        'bookings' => $bookings,
        'count' => count($bookings)
    ]);
}

// 2. Update Booking Status (Payment or Fulfillment)
if ($action === 'update_booking_status') {
    $ref = sanitize_input($_POST['booking_reference'] ?? '');
    $payment_status = sanitize_input($_POST['payment_status'] ?? '');
    $booking_status = sanitize_input($_POST['booking_status'] ?? '');
    $payment_ref    = sanitize_input($_POST['payment_reference'] ?? '');

    if (empty($ref)) {
        json_response(false, 'Booking reference is required.', [], 422);
    }

    $db = Database::getConnection();
    if ($db) {
        try {
            $sql = "UPDATE bookings SET 
                    payment_status = COALESCE(NULLIF(:pstat, ''), payment_status),
                    booking_status = COALESCE(NULLIF(:bstat, ''), booking_status),
                    payment_reference = COALESCE(NULLIF(:pref, ''), payment_reference)
                    WHERE booking_reference = :ref";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':pstat' => $payment_status,
                ':bstat' => $booking_status,
                ':pref'  => $payment_ref,
                ':ref'   => $ref
            ]);
        } catch (Exception $e) {
            log_system_error("Admin update booking status error: " . $e->getMessage());
            json_response(false, 'Database error updating booking status: ' . $e->getMessage(), [], 500);
        }
    }

    // Session fallback
    if (isset($_SESSION['last_booking']) && $_SESSION['last_booking']['booking_reference'] === $ref) {
        if (!empty($payment_status)) $_SESSION['last_booking']['payment_status'] = $payment_status;
        if (!empty($booking_status)) $_SESSION['last_booking']['booking_status'] = $booking_status;
        if (!empty($payment_ref))    $_SESSION['last_booking']['payment_reference'] = $payment_ref;
    }

    json_response(true, 'Booking status updated successfully for ' . $ref);
}

// 3. Save Store Settings
if ($action === 'save_settings') {
    $allowed_keys = [
        'unit_price', 'shipping_charge', 'bank_name', 'bank_account_name',
        'bank_sort_code', 'bank_account_number', 'paypal_client_id', 'paypal_mode',
        'support_phone', 'support_email', 'admin_password'
    ];

    $db = Database::getConnection();
    $updated_count = 0;

    foreach ($allowed_keys as $key) {
        if (isset($_POST[$key])) {
            $val = sanitize_input($_POST[$key]);
            
            if ($db) {
                try {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
                        ON CONFLICT(setting_key) DO UPDATE SET setting_value = :v");
                    $stmt->execute([':k' => $key, ':v' => $val]);
                    $updated_count++;
                } catch (Exception $e) {
                    // Fallback query format for MySQL if ON CONFLICT fails
                    try {
                        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
                            ON DUPLICATE KEY UPDATE setting_value = :v");
                        $stmt->execute([':k' => $key, ':v' => $val]);
                        $updated_count++;
                    } catch (Exception $e2) {
                        log_system_error("Save setting error for '$key': " . $e2->getMessage());
                    }
                }
            }
        }
    }

    json_response(true, 'Settings updated successfully.');
}

// 4. Export Bookings CSV
if ($action === 'export_csv') {
    $db = Database::getConnection();
    $bookings = [];

    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM bookings ORDER BY id DESC");
            $bookings = $stmt->fetchAll();
        } catch (Exception $e) {
            log_system_error("Export CSV error: " . $e->getMessage());
        }
    } elseif (isset($_SESSION['last_booking'])) {
        $bookings[] = $_SESSION['last_booking'];
    }

    filename: header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=vk_logistics_bookings_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Booking Ref', 'Customer Name', 'Mobile', 'Email',
        'Address Line 1', 'Address Line 2', 'City', 'County', 'Postcode', 'Country',
        'Quantity', 'Unit Price (£)', 'Subtotal (£)', 'Shipping (£)', 'Total (£)',
        'Payment Method', 'Payment Ref / Txn ID', 'Payment Status', 'Booking Status', 'Date Created'
    ]);

    foreach ($bookings as $row) {
        fputcsv($output, [
            $row['booking_reference'] ?? '',
            $row['customer_name'] ?? '',
            $row['mobile'] ?? '',
            $row['email'] ?? '',
            $row['address_line_1'] ?? '',
            $row['address_line_2'] ?? '',
            $row['city'] ?? '',
            $row['county'] ?? '',
            $row['postcode'] ?? '',
            $row['country'] ?? 'United Kingdom',
            $row['quantity'] ?? 1,
            $row['unit_price'] ?? '14.99',
            $row['subtotal'] ?? '',
            $row['shipping_charge'] ?? '3.99',
            $row['total_amount'] ?? '',
            $row['payment_method'] ?? '',
            $row['payment_reference'] ?? $row['paypal_transaction_id'] ?? '',
            $row['payment_status'] ?? '',
            $row['booking_status'] ?? '',
            $row['created_at'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

json_response(false, 'Invalid admin action', [], 400);
