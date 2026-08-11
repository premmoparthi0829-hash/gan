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

            if (!empty($bookings)) {
                $booking_ids = array_column($bookings, 'id');
                $in_clause = implode(',', array_map('intval', $booking_ids));
                $items_stmt = $db->query("SELECT * FROM booking_items WHERE booking_id IN ($in_clause)");
                $all_items = $items_stmt->fetchAll();
                
                $items_by_booking = [];
                foreach ($all_items as $item) {
                    $items_by_booking[$item['booking_id']][] = $item;
                }
                
                foreach ($bookings as &$b) {
                    $b['items'] = $items_by_booking[$b['id']] ?? [];
                }
                unset($b);
            }
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
        'paypal_client_secret', 'paypal_email', 'paypal_account_name', 'paypal_id',
        'support_phone', 'support_email', 'admin_password'
    ];

    $db = Database::getConnection();
    $updated_count = 0;

    foreach ($allowed_keys as $key) {
        if (isset($_POST[$key])) {
            $val = sanitize_input($_POST[$key]);
            
            // Skip updating password if blank (leave blank to keep current passkey)
            if ($key === 'admin_password' && $val === '') {
                continue;
            }
            
            if ($db) {
                try {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmt->execute([':k' => $key, ':v' => $val]);
                    $updated_count++;
                } catch (Exception $e) {
                    log_system_error("Save setting error for '$key': " . $e->getMessage());
                }
            }
        }
    }

    json_response(true, 'Settings updated successfully.');
}

// 3b. Save Dedicated PayPal Settings
if ($action === 'save_paypal_settings') {
    $allowed_keys = [
        'paypal_client_id', 'paypal_mode', 'paypal_client_secret',
        'paypal_email', 'paypal_account_name', 'paypal_id',
        'currency_code', 'paypal_status', 'shipping_charge'
    ];

    $db = Database::getConnection();
    $updated_count = 0;

    foreach ($allowed_keys as $key) {
        if (isset($_POST[$key])) {
            $val = sanitize_input($_POST[$key]);
            
            // If secret is passed as '***' or empty string when updating, keep existing secret
            if ($key === 'paypal_client_secret' && ($val === '***' || $val === '')) {
                continue;
            }

            if ($db) {
                try {
                    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmt->execute([':k' => $key, ':v' => $val]);
                    $updated_count++;
                } catch (Exception $e) {
                    log_system_error("Save PayPal setting error for '$key': " . $e->getMessage());
                }
            }
        }
    }

    json_response(true, 'PayPal Live credentials and settings saved successfully.');
}

// 3c. Delete / Reset PayPal Credentials
if ($action === 'delete_paypal_credentials') {
    $db = Database::getConnection();
    if ($db) {
        try {
            $keys_to_clear = ['paypal_client_id', 'paypal_client_secret', 'paypal_mode'];
            foreach ($keys_to_clear as $k) {
                $val = ($k === 'paypal_mode') ? 'sandbox' : '';
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([':k' => $k, ':v' => $val]);
            }
            json_response(true, 'PayPal API credentials have been deleted/reset successfully.');
        } catch (Exception $e) {
            json_response(false, 'Error resetting credentials: ' . $e->getMessage(), [], 500);
        }
    }
    json_response(false, 'Database connection offline.', [], 500);
}

// 3d. Test PayPal API Connection (OAuth 2.0 Auth Check)
if ($action === 'test_paypal_credentials') {
    $mode          = sanitize_input($_POST['paypal_mode'] ?? get_setting('paypal_mode', 'sandbox'));
    $client_id     = sanitize_input($_POST['paypal_client_id'] ?? get_setting('paypal_client_id', ''));
    $client_secret = sanitize_input($_POST['paypal_client_secret'] ?? get_setting('paypal_client_secret', ''));

    // If client_secret in POST is blank or hidden, pull from DB settings
    if (empty($client_secret) || $client_secret === '***') {
        $client_secret = get_setting('paypal_client_secret', '');
    }

    if (empty($client_id)) {
        json_response(false, 'PayPal Client ID is missing. Please enter your Client ID.', [], 422);
    }
    if ($client_id === 'sb') {
        json_response(true, 'PayPal is currently in Sandbox Test Mode with mock ID "sb". Replace with real Live Client ID for Live production.');
    }
    if (empty($client_secret)) {
        json_response(false, 'PayPal Client Secret is required to test API connection.', [], 422);
    }

    $api_base = ($mode === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

    $ch = curl_init($api_base . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        CURLOPT_USERPWD        => $client_id . ':' . $client_secret,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Accept-Language: en_US',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        json_response(false, 'Network connection to PayPal failed: ' . $curl_err, [], 502);
    }

    $data = json_decode($response, true);

    if ($http_code === 200 && !empty($data['access_token'])) {
        $app_id     = $data['app_id'] ?? 'N/A';
        $expires_in = $data['expires_in'] ?? 32400;
        $scope      = $data['scope'] ?? 'standard';
        
        json_response(true, "✅ PayPal OAuth 2.0 Authentication SUCCESSFUL! Connection established to PayPal (" . strtoupper($mode) . " Mode).", [
            'mode'       => $mode,
            'app_id'     => $app_id,
            'expires_in' => $expires_in . ' seconds',
            'scope'      => $scope
        ]);
    } else {
        $error_desc = $data['error_description'] ?? $data['error'] ?? 'Invalid Client ID or Secret';
        json_response(false, "❌ PayPal OAuth Authentication Failed (HTTP $http_code): " . $error_desc, [
            'http_code' => $http_code,
            'raw'       => $data
        ], 400);
    }
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

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=vk_logistics_bookings_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM for Google Sheets & Excel compatibility
    fprintf($output, "\xEF\xBB\xBF");
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

// 5. Export TSV (Tab Separated Values for Google Sheets)
if ($action === 'export_tsv') {
    $db = Database::getConnection();
    $bookings = [];

    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM bookings ORDER BY id DESC");
            $bookings = $stmt->fetchAll();
        } catch (Exception $e) {
            log_system_error("Export TSV error: " . $e->getMessage());
        }
    } elseif (isset($_SESSION['last_booking'])) {
        $bookings[] = $_SESSION['last_booking'];
    }

    header('Content-Type: text/tab-separated-values; charset=utf-8');
    header('Content-Disposition: attachment; filename=vk_logistics_bookings_' . date('Ymd_His') . '.tsv');

    $output = fopen('php://output', 'w');
    fprintf($output, "\xEF\xBB\xBF");
    fputcsv($output, [
        'Booking Ref', 'Customer Name', 'Mobile', 'Email',
        'Address Line 1', 'Address Line 2', 'City', 'County', 'Postcode', 'Country',
        'Quantity', 'Unit Price (£)', 'Subtotal (£)', 'Shipping (£)', 'Total (£)',
        'Payment Method', 'Payment Ref / Txn ID', 'Payment Status', 'Booking Status', 'Date Created'
    ], "\t");

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
        ], "\t");
    }
    fclose($output);
    exit;
}

// 6. Admin Get Categories & Products
if ($action === 'admin_get_categories_products') {
    $db = Database::getConnection();
    $categories = [];
    $products = [];
    if ($db) {
        try {
            $categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
            $products = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC")->fetchAll();
        } catch (Exception $e) {
            log_system_error("Admin get categories/products error: " . $e->getMessage());
        }
    }
    json_response(true, 'Categories and products fetched', [
        'categories' => $categories,
        'products' => $products
    ]);
}

// 7. Save Category
if ($action === 'save_category') {
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    
    if (empty($name)) {
        json_response(false, 'Category name is required.', [], 422);
    }
    
    $image_path = sanitize_input($_POST['current_image_path'] ?? '');
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['category_image'];
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_exts) && $file['size'] <= 10 * 1024 * 1024) {
            $new_filename = 'cat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target_dir = __DIR__ . '/../assets/images/';
            if (!is_dir($target_dir)) {
                @mkdir($target_dir, 0755, true);
            }
            if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
                $image_path = 'assets/images/' . $new_filename;
            }
        }
    }
    
    $db = Database::getConnection();
    if ($db) {
        try {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE categories SET name = :name, description = :description, image_path = :image_path WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':image_path' => $image_path,
                    ':id' => $id
                ]);
            } else {
                $stmt = $db->prepare("INSERT INTO categories (name, description, image_path) VALUES (:name, :description, :image_path)");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':image_path' => $image_path
                ]);
            }
            json_response(true, 'Category saved successfully.');
        } catch (Exception $e) {
            json_response(false, 'Error saving category: ' . $e->getMessage(), [], 500);
        }
    }
}

// 8. Delete Category
if ($action === 'delete_category') {
    $id = (int)($_POST['id'] ?? 0);
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            json_response(true, 'Category deleted successfully.');
        } catch (Exception $e) {
            json_response(false, 'Error deleting category: ' . $e->getMessage(), [], 500);
        }
    }
}

// 9. Save Product
if ($action === 'save_product') {
    $id = (int)($_POST['id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0.00);
    
    if (empty($name) || $category_id <= 0 || $price < 0) {
        json_response(false, 'Valid name, category, and price are required.', [], 422);
    }
    
    $image_path = sanitize_input($_POST['current_image_path'] ?? '');
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_exts) && $file['size'] <= 10 * 1024 * 1024) {
            $new_filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target_dir = __DIR__ . '/../assets/images/';
            if (!is_dir($target_dir)) {
                @mkdir($target_dir, 0755, true);
            }
            if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
                $image_path = 'assets/images/' . $new_filename;
            }
        }
    }
    
    $db = Database::getConnection();
    if ($db) {
        try {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE products SET category_id = :category_id, name = :name, description = :description, price = :price, image_path = :image_path WHERE id = :id");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':image_path' => $image_path,
                    ':id' => $id
                ]);
            } else {
                $stmt = $db->prepare("INSERT INTO products (category_id, name, description, price, image_path) VALUES (:category_id, :name, :description, :price, :image_path)");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':image_path' => $image_path
                ]);
            }
            json_response(true, 'Product saved successfully.');
        } catch (Exception $e) {
            json_response(false, 'Error saving product: ' . $e->getMessage(), [], 500);
        }
    }
}

// 10. Delete Product
if ($action === 'delete_product') {
    $id = (int)($_POST['id'] ?? 0);
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            json_response(true, 'Product deleted successfully.');
        } catch (Exception $e) {
            json_response(false, 'Error deleting product: ' . $e->getMessage(), [], 500);
        }
    }
}

json_response(false, 'Invalid admin action', [], 400);
