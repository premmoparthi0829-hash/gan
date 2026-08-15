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

// Public Order Tracking
if ($action === 'get_booking_details') {
    $ref = sanitize_input($_REQUEST['booking_reference'] ?? $_REQUEST['booking_ref'] ?? '');
    if (empty($ref)) {
        json_response(false, 'Booking Reference is required', [], 422);
    }
    $booking = get_booking_by_ref($ref);
    if ($booking) {
        json_response(true, 'Booking details fetched', ['booking' => $booking]);
    } else {
        json_response(false, 'Booking Reference not found', [], 404);
    }
}

// Check admin authentication for all subsequent actions
if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized. Please login.', ['logged_in' => false], 401);
}

// CSRF Validation for state-changing admin operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, [
    'update_booking_status', 'save_settings', 'toggle_addon',
    'save_category', 'delete_category', 'save_product', 'delete_product', 'save_addon', 'delete_addon', 'toggle_master_addon',
    'save_upi_settings', 'save_payment_settings'
], true)) {
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
    $settings = [];
    if ($db) {
        try {
            $categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
            $products = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC")->fetchAll();
            foreach ($products as &$p) {
                $p['addons'] = get_product_addons($p['id']);
                $p['reusable_addon_ids'] = array_map('intval', array_column(get_product_reusable_addons($p['id'], false), 'id'));
            }
            unset($p);
            $settings = get_all_settings();
        } catch (Exception $e) {
            log_system_error("Admin get categories/products error: " . $e->getMessage());
        }
    }
    json_response(true, 'Categories and products fetched', [
        'categories' => $categories,
        'products' => $products,
        'settings' => $settings
    ]);
}

// Reusable add-on catalog CRUD
if ($action === 'admin_get_addons') {
    $db = Database::getConnection(); 
    $addons = [];
    if ($db) { 
        try { 
            ensure_reusable_addon_tables($db); 
            $addons = $db->query("SELECT * FROM addons ORDER BY name ASC")->fetchAll(); 
        } catch (Exception $e) { 
            json_response(false, $e->getMessage(), [], 500); 
        } 
    }
    json_response(true, 'Add-ons fetched', ['addons' => $addons]);
}
if ($action === 'save_addon') {
    $db = Database::getConnection(); 
    $id = (int)($_POST['id'] ?? 0); 
    $name = sanitize_input($_POST['name'] ?? ''); 
    $price = max(0, (float)($_POST['price'] ?? 0)); 
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    if (!$db || $name === '') json_response(false, 'Add-on name is required.', [], 422);
    try { 
        ensure_reusable_addon_tables($db); 
        $image = sanitize_input($_POST['current_image_path'] ?? '');
        if (isset($_FILES['addon_image']) && $_FILES['addon_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['addon_image'];
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_exts) && $file['size'] <= 10 * 1024 * 1024) {
                $new_filename = 'master_addon_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target_dir = __DIR__ . '/../assets/images/';
                if (!is_dir($target_dir)) {
                    @mkdir($target_dir, 0755, true);
                }
                if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
                    $image = 'assets/images/' . $new_filename;
                }
            }
        }
        if ($id) {
            $s = $db->prepare('UPDATE addons SET name=:n,price=:p,status=:s,image_path=:i WHERE id=:id');
            $s->execute([':n'=>$name,':p'=>$price,':s'=>$status,':i'=>$image,':id'=>$id]);
        } else {
            $s = $db->prepare('INSERT INTO addons(name,price,status,image_path) VALUES(:n,:p,:s,:i)');
            $s->execute([':n'=>$name,':p'=>$price,':s'=>$status,':i'=>$image]);
        } 
        json_response(true, 'Add-on saved successfully.'); 
    } catch(Exception $e) {
        json_response(false, $e->getMessage(), [], 500);
    }
}
if ($action === 'delete_addon') { 
    $db = Database::getConnection(); 
    try { 
        ensure_reusable_addon_tables($db); 
        $db->prepare('DELETE FROM addons WHERE id=:id')->execute([':id' => (int)($_POST['id'] ?? 0)]); 
        json_response(true, 'Add-on deleted.'); 
    } catch (Exception $e) {
        json_response(false, $e->getMessage(), [], 500);
    } 
}
if ($action === 'toggle_master_addon') {
    $db = Database::getConnection();
    $id = (int)($_POST['id'] ?? 0);
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    if ($db && $id > 0) {
        try {
            ensure_reusable_addon_tables($db);
            $db->prepare("UPDATE addons SET status = :s WHERE id = :id")->execute([':s' => $status, ':id' => $id]);
            json_response(true, 'Add-on status updated.', ['status' => $status]);
        } catch (Exception $e) {
            json_response(false, $e->getMessage(), [], 500);
        }
    }
    json_response(false, 'Invalid add-on ID.', [], 422);
}

// 6b. Toggle Addon Status
if ($action === 'toggle_addon') {
    $addon = sanitize_input($_POST['addon'] ?? ''); // gift_wrap or choc_box
    $status = (int)($_POST['status'] ?? 1);
    $db = Database::getConnection();
    if ($db) {
        try {
            $key1 = ($addon === 'gift_wrap') ? 'gift_wrap_enabled' : 'choc_box_enabled';
            $key2 = ($addon === 'gift_wrap') ? 'enable_gift_wrap' : 'enable_choc_box';
            $val = $status ? '1' : '0';
            $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k1, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':k1' => $key1, ':v' => $val]);
            $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k2, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':k2' => $key2, ':v' => $val]);
            json_response(true, 'Add-on status updated successfully.', ['status' => $status]);
        } catch (Exception $e) {
            json_response(false, 'Error updating add-on status: ' . $e->getMessage(), [], 500);
        }
    }
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

// 9. Save Product (UNLIMITED Product Images Upload System)
if ($action === 'save_product') {
    $id = (int)($_POST['id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0.00);
    
    if (empty($name) || $category_id <= 0 || $price < 0) {
        json_response(false, 'Valid name, category, and price are required.', [], 422);
    }
    
    // Existing images passed from frontend (reordered/retained)
    $existing_images = [];
    if (isset($_POST['existing_gallery_images'])) {
        if (is_array($_POST['existing_gallery_images'])) {
            foreach ($_POST['existing_gallery_images'] as $img) {
                $clean_img = sanitize_input($img);
                if (!empty($clean_img)) $existing_images[] = $clean_img;
            }
        } elseif (is_string($_POST['existing_gallery_images'])) {
            $decoded = json_decode($_POST['existing_gallery_images'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $img) {
                    $clean_img = sanitize_input($img);
                    if (!empty($clean_img)) $existing_images[] = $clean_img;
                }
            }
        }
    } else {
        // Fallback for single image parameters
        $p1 = sanitize_input($_POST['current_image_path'] ?? '');
        $p2 = sanitize_input($_POST['current_image_path_2'] ?? '');
        $p3 = sanitize_input($_POST['current_image_path_3'] ?? '');
        if (!empty($p1)) $existing_images[] = $p1;
        if (!empty($p2)) $existing_images[] = $p2;
        if (!empty($p3)) $existing_images[] = $p3;
    }

    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    $target_dir = __DIR__ . '/../assets/images/';
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0755, true);
    }

    $new_uploaded_images = [];

    // Process multiple gallery file uploads (UNLIMITED files!)
    if (isset($_FILES['product_gallery_files']) && is_array($_FILES['product_gallery_files']['name'])) {
        $files = $_FILES['product_gallery_files'];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_exts) && $files['size'][$i] <= 20 * 1024 * 1024) {
                    $new_filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($files['tmp_name'][$i], $target_dir . $new_filename)) {
                        $new_uploaded_images[] = 'assets/images/' . $new_filename;
                    }
                }
            }
        }
    }

    // Process legacy single file uploads if present
    $single_upload_keys = ['product_image', 'product_image_2', 'product_image_3'];
    foreach ($single_upload_keys as $skey) {
        if (isset($_FILES[$skey]) && $_FILES[$skey]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$skey];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_exts) && $file['size'] <= 20 * 1024 * 1024) {
                $new_filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
                    $new_uploaded_images[] = 'assets/images/' . $new_filename;
                }
            }
        }
    }

    // Combine existing and newly uploaded images in exact sequence order
    $all_gallery_images = array_values(array_unique(array_merge($existing_images, $new_uploaded_images)));

    $image_path   = $all_gallery_images[0] ?? '';
    $image_path_2 = $all_gallery_images[1] ?? '';
    $image_path_3 = $all_gallery_images[2] ?? '';
    $gallery_json = json_encode($all_gallery_images);

    $db = Database::getConnection();
    if ($db) {
        try {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE products SET category_id = :category_id, name = :name, description = :description, price = :price, image_path = :image_path, image_path_2 = :image_path_2, image_path_3 = :image_path_3, gallery_images = :gallery_images WHERE id = :id");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':image_path' => $image_path,
                    ':image_path_2' => $image_path_2,
                    ':image_path_3' => $image_path_3,
                    ':gallery_images' => $gallery_json,
                    ':id' => $id
                ]);
            } else {
                $stmt = $db->prepare("INSERT INTO products (category_id, name, description, price, image_path, image_path_2, image_path_3, gallery_images) VALUES (:category_id, :name, :description, :price, :image_path, :image_path_2, :image_path_3, :gallery_images)");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':image_path' => $image_path,
                    ':image_path_2' => $image_path_2,
                    ':image_path_3' => $image_path_3,
                    ':gallery_images' => $gallery_json
                ]);
            }
            
            $target_prod_id = $id > 0 ? $id : (int)$db->lastInsertId();

            // Persist reusable add-on selections as a many-to-many relationship.
            // This is deliberately independent from the legacy add-on-group tables.
            if ($target_prod_id > 0 && isset($_POST['addon_ids'])) {
                ensure_reusable_addon_tables($db);
                $raw_ids = is_array($_POST['addon_ids']) ? $_POST['addon_ids'] : [$_POST['addon_ids']];
                $addon_ids = array_values(array_unique(array_filter(array_map('intval', $raw_ids), fn($value) => $value > 0)));
                $db->prepare('DELETE FROM product_addons WHERE product_id = :pid')->execute([':pid' => $target_prod_id]);
                if ($addon_ids) {
                    $valid = $db->prepare("SELECT id FROM addons WHERE id = :id AND status = 'active'");
                    $attach = $db->prepare('INSERT IGNORE INTO product_addons (product_id, addon_id) VALUES (:pid, :aid)');
                    foreach ($addon_ids as $addon_id) {
                        $valid->execute([':id' => $addon_id]);
                        if ($valid->fetch()) $attach->execute([':pid' => $target_prod_id, ':aid' => $addon_id]);
                    }
                }
            }

            // Save Add-on Groups and Items
            if ($target_prod_id > 0 && isset($_POST['addons'])) {
                $addons_raw = $_POST['addons'];
                $addons_list = is_array($addons_raw) ? $addons_raw : (json_decode($addons_raw, true) ?: []);
                
                // Delete old addon groups for this product (cascades to items)
                $db->prepare("DELETE FROM product_addon_groups WHERE product_id = :pid")->execute([':pid' => $target_prod_id]);
                
                $stmt_ig = $db->prepare("INSERT INTO product_addon_groups (product_id, name, is_required, selection_type, min_selection, max_selection, sort_order) VALUES (:pid, :name, :is_req, :sel_type, :min_sel, :max_sel, :sort_order)");
                $stmt_ii = $db->prepare("INSERT INTO product_addon_items (group_id, name, price, image_path, status, sort_order) VALUES (:gid, :name, :price, :img_path, :status, :sort_order)");
                
                foreach ($addons_list as $g_idx => $group) {
                    $g_name = sanitize_input($group['name'] ?? '');
                    if (empty($g_name)) continue;
                    $is_req = !empty($group['is_required']) ? 1 : 0;
                    $sel_type = ($group['selection_type'] ?? 'single') === 'multiple' ? 'multiple' : 'single';
                    $min_sel = max(0, (int)($group['min_selection'] ?? 0));
                    $max_sel = max(0, (int)($group['max_selection'] ?? 0));
                    $g_sort = (int)($group['sort_order'] ?? $g_idx);
                    
                    $stmt_ig->execute([
                        ':pid' => $target_prod_id,
                        ':name' => $g_name,
                        ':is_req' => $is_req,
                        ':sel_type' => $sel_type,
                        ':min_sel' => $min_sel,
                        ':max_sel' => $max_sel,
                        ':sort_order' => $g_sort
                    ]);
                    $group_id = (int)$db->lastInsertId();
                    
                    if ($group_id > 0 && !empty($group['items']) && is_array($group['items'])) {
                        foreach ($group['items'] as $i_idx => $item) {
                            $i_name = sanitize_input($item['name'] ?? '');
                            if (empty($i_name)) continue;
                            $i_price = max(0.0, (float)($item['price'] ?? 0.0));
                            $i_status = ($item['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
                            $i_sort = (int)($item['sort_order'] ?? $i_idx);
                            $i_img = sanitize_input($item['image_path'] ?? '');
                            
                            // Check file upload for addon item image
                            $file_key = "addon_item_img_{$g_idx}_{$i_idx}";
                            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                                $file = $_FILES[$file_key];
                                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                if (in_array($ext, $allowed_exts) && $file['size'] <= 10 * 1024 * 1024) {
                                    $new_filename = 'addon_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                                    if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
                                        $i_img = 'assets/images/' . $new_filename;
                                    }
                                }
                            }
                            
                            $stmt_ii->execute([
                                ':gid' => $group_id,
                                ':name' => $i_name,
                                ':price' => $i_price,
                                ':img_path' => $i_img,
                                ':status' => $i_status,
                                ':sort_order' => $i_sort
                            ]);
                        }
                    }
                }
            }

            // Auto-sync settings table if product is an Add-On
            $addon_enabled = isset($_POST['addon_enabled']) ? ($_POST['addon_enabled'] == '1' ? '1' : '0') : null;

            if (stripos($name, 'Gift Wrap') !== false || stripos($name, 'Wrapping') !== false || $id == 7) {
                $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gift_wrap_name', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $name]);
                $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gift_wrap_price', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => sprintf('%.2f', $price)]);
                if (!empty($description)) {
                    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gift_wrap_desc', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $description]);
                }
                if (!empty($image_path)) {
                    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gift_wrap_image', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $image_path]);
                }
                if ($addon_enabled !== null) {
                    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gift_wrap_enabled', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $addon_enabled]);
                    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('enable_gift_wrap', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $addon_enabled]);
                }
            } elseif (stripos($name, 'Chocolate') !== false || stripos($name, 'Sweet') !== false || $id == 8) {
                $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('choc_box_name', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $name]);
                $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('choc_box_price', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => sprintf('%.2f', $price)]);
                if (!empty($description)) {
                    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('choc_box_desc', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $description]);
                }
                if (!empty($image_path)) {
                    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('choc_box_image', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $image_path]);
                }
                if ($addon_enabled !== null) {
                    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('choc_box_enabled', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $addon_enabled]);
                    $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('enable_choc_box', :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([':v' => $addon_enabled]);
                }
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

// 11. Save UPI & Bank Payment Settings
if ($action === 'save_upi_settings' || $action === 'save_payment_settings') {
    $upi_id = sanitize_input($_POST['upi_id'] ?? '');
    $account_name = sanitize_input($_POST['account_name'] ?? '');
    $instructions = sanitize_input($_POST['instructions'] ?? '');
    $is_enabled = (int)($_POST['is_enabled'] ?? 1);
    
    if (empty($upi_id) || empty($account_name)) {
        json_response(false, 'UPI ID and Account Holder Name are required.', [], 422);
    }
    
    $qr_image_path = sanitize_input($_POST['current_qr_image'] ?? '');
    if (isset($_FILES['upi_qr_file']) && $_FILES['upi_qr_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['upi_qr_file'];
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_exts)) {
            json_response(false, 'Invalid QR code image format. Only JPG, PNG, and WEBP allowed.', [], 422);
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            json_response(false, 'QR code image must be smaller than 10MB.', [], 422);
        }
        $target_dir = __DIR__ . '/../uploads/upi_qr/';
        if (!is_dir($target_dir)) {
            @mkdir($target_dir, 0755, true);
        }
        $new_filename = 'upi_qr_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $target_dir . $new_filename)) {
            $qr_image_path = 'uploads/upi_qr/' . $new_filename;
        }
    }
    
    $bank_data = [
        'bank_name' => sanitize_input($_POST['bank_name'] ?? ''),
        'bank_account_name' => sanitize_input($_POST['bank_account_name'] ?? ''),
        'bank_sort_code' => sanitize_input($_POST['bank_sort_code'] ?? ''),
        'bank_account_number' => sanitize_input($_POST['bank_account_number'] ?? ''),
        'bank_instructions' => sanitize_input($_POST['bank_instructions'] ?? ''),
        'bank_enabled' => (int)($_POST['bank_enabled'] ?? 1),
        'support_phone' => sanitize_input($_POST['support_phone'] ?? '')
    ];

    // Admin Passkey update if provided
    $new_passcode = trim($_POST['admin_password'] ?? '');
    if (!empty($new_passcode)) {
        if (strlen($new_passcode) < 6) {
            json_response(false, 'New passkey must be at least 6 characters.', [], 422);
        }
        update_setting('admin_password', password_hash($new_passcode, PASSWORD_DEFAULT));
    }
    
    save_upi_settings($upi_id, $account_name, $qr_image_path, $instructions, $is_enabled, $bank_data);
    json_response(true, 'Payment Settings saved successfully.', [
        'upi_id' => $upi_id,
        'account_name' => $account_name,
        'qr_image' => $qr_image_path
    ]);
}

// 12. Get UPI Settings
if ($action === 'get_upi_settings') {
    $settings = get_upi_settings();
    json_response(true, 'UPI Settings loaded', $settings);
}

// 13. Verify UPI Payment (Approve / Reject / Request Re-upload)
if ($action === 'verify_upi_payment') {
    $ref = sanitize_input($_POST['ref'] ?? $_POST['id'] ?? '');
    $status = sanitize_input($_POST['status'] ?? ''); // approve, reject, request_reupload
    $reason = sanitize_input($_POST['reason'] ?? '');
    
    if (empty($ref) || empty($status)) {
        json_response(false, 'Booking Reference and Action Status are required.', [], 422);
    }
    
    $res = verify_upi_payment($ref, $status, $reason, 'Admin');
    if ($res) {
        json_response(true, 'UPI Payment status updated successfully.');
    } else {
        json_response(false, 'Failed to update UPI Payment status.', [], 500);
    }
}

json_response(false, 'Invalid admin action', [], 400);
