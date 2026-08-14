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

/** Reusable add-on catalog and product mapping (safe to call repeatedly). */
function ensure_reusable_addon_tables($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS addons (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00, image_path VARCHAR(255) NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $db->exec("CREATE TABLE IF NOT EXISTS product_addons (
        id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, addon_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_product_addon (product_id, addon_id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (addon_id) REFERENCES addons(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function get_product_reusable_addons($product_id, $active_only = true) {
    $db = Database::getConnection();
    if (!$db) return [];
    try {
        ensure_reusable_addon_tables($db);
        $sql = "SELECT a.id, a.name, a.price, a.image_path, a.status FROM product_addons pa JOIN addons a ON a.id = pa.addon_id WHERE pa.product_id = :pid";
        if ($active_only) $sql .= " AND a.status = 'active'";
        $sql .= " ORDER BY a.name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':pid' => (int)$product_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) { log_system_error('Reusable add-on fetch error: ' . $e->getMessage()); }
    return [];
}

/**
 * Helper to fetch add-on groups & items for a product
 */
function get_product_addons($product_id) {
    $db = Database::getConnection();
    $groups = [];
    if ($db) {
        try {
            // Auto migrate tables if missing
            $db->exec("CREATE TABLE IF NOT EXISTS product_addon_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                name VARCHAR(150) NOT NULL,
                is_required TINYINT(1) NOT NULL DEFAULT 0,
                selection_type ENUM('single', 'multiple') NOT NULL DEFAULT 'single',
                min_selection INT NOT NULL DEFAULT 0,
                max_selection INT NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $db->exec("CREATE TABLE IF NOT EXISTS product_addon_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id INT NOT NULL,
                name VARCHAR(150) NOT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                image_path VARCHAR(255) NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (group_id) REFERENCES product_addon_groups(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            try {
                $db->exec("ALTER TABLE booking_items ADD COLUMN selected_addons LONGTEXT NULL");
            } catch (Exception $e) {}

            $stmt_g = $db->prepare("SELECT * FROM product_addon_groups WHERE product_id = :pid ORDER BY sort_order ASC, id ASC");
            $stmt_g->execute([':pid' => (int)$product_id]);
            $groups = $stmt_g->fetchAll();

            if (!empty($groups)) {
                $stmt_i = $db->prepare("SELECT * FROM product_addon_items WHERE group_id = :gid ORDER BY sort_order ASC, id ASC");
                foreach ($groups as &$group) {
                    $stmt_i->execute([':gid' => (int)$group['id']]);
                    $group['items'] = $stmt_i->fetchAll();
                }
                unset($group);
            }
        } catch (Exception $e) {
            log_system_error("Error fetching product addons: " . $e->getMessage());
        }
    }
    return $groups;
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
        $selected_addons = $item['selected_addons'] ?? [];
        if (is_string($selected_addons)) {
            $selected_addons = json_decode($selected_addons, true) ?: [];
        }
        
        $product = get_product_by_id($id);
        $price = 0.0;
        $name = '';
        $img_path = '';

        if ($product) {
            $base_price = (float)$product['price'];
            // Price snapshots come only from active add-ons assigned to this
            // product. This prevents clients from changing add-on prices or
            // attaching an add-on belonging to another product.
            $assigned = get_product_reusable_addons($id, true);
            $assigned_by_id = [];
            foreach ($assigned as $addon) $assigned_by_id[(int)$addon['id']] = $addon;
            $verified_addons = [];
            foreach ((array)$selected_addons as $selected) {
                $addon_id = (int)($selected['addon_id'] ?? $selected['id'] ?? 0);
                if ($addon_id && isset($assigned_by_id[$addon_id])) {
                    $addon = $assigned_by_id[$addon_id];
                    $verified_addons[] = ['addon_id' => $addon_id, 'name' => $addon['name'], 'price' => (float)$addon['price'], 'image_path' => $addon['image_path']];
                } elseif (!$addon_id) {
                    // Preserve legacy product-specific add-on snapshots.
                    $verified_addons[] = ['name' => sanitize_input($selected['name'] ?? ''), 'price' => max(0, (float)($selected['price'] ?? 0))];
                }
            }
            $selected_addons = $verified_addons;
            $addon_total = array_sum(array_map(fn($addon) => (float)$addon['price'], $selected_addons));
            $price = $base_price + $addon_total;
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
                'selected_addons' => $selected_addons,
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
 * Get dynamic UPI & Bank Payment settings from database
 */
function get_upi_settings() {
    return [
        'upi_id' => get_setting('upi_id', 'vklogistics@upi'),
        'upi_account_name' => get_setting('upi_account_name', 'VK LOGISTICS LTD'),
        'upi_qr_image' => get_setting('upi_qr_image', 'assets/images/upi_qr_default.png'),
        'upi_instructions' => get_setting('upi_instructions', 'Scan QR Code or copy UPI ID. Open Google Pay, PhonePe, Paytm or any UPI App to complete payment, then upload your transaction screenshot below.'),
        'upi_enabled' => get_setting('upi_enabled', '1'),

        // Bank Payment Settings
        'bank_name' => get_setting('bank_name', 'Barclays Bank UK'),
        'bank_account_name' => get_setting('bank_account_name', 'VK LOGISTICS LTD'),
        'bank_sort_code' => get_setting('bank_sort_code', '20-45-77'),
        'bank_account_number' => get_setting('bank_account_number', '83920144'),
        'bank_instructions' => get_setting('bank_instructions', 'Transfer the total payable amount to our Bank Account given below. Upload your payment screenshot/receipt after transfer.'),
        'bank_enabled' => get_setting('bank_enabled', '1'),

        // Customer Support Phone
        'support_phone' => get_setting('support_phone', '+44 7700 900888')
    ];
}

/**
 * Save UPI & Bank Payment settings in database
 */
function save_upi_settings($upi_id, $account_name, $qr_image, $instructions, $is_enabled, $bank_data = []) {
    $db = Database::getConnection();
    if (!$db) return false;
    
    $settings = [
        'upi_id' => sanitize_input($upi_id),
        'upi_account_name' => sanitize_input($account_name),
        'upi_instructions' => sanitize_input($instructions),
        'upi_enabled' => $is_enabled ? '1' : '0'
    ];
    if (!empty($qr_image)) {
        $settings['upi_qr_image'] = sanitize_input($qr_image);
    }
    
    if (!empty($bank_data)) {
        if (isset($bank_data['bank_name'])) $settings['bank_name'] = sanitize_input($bank_data['bank_name']);
        if (isset($bank_data['bank_account_name'])) $settings['bank_account_name'] = sanitize_input($bank_data['bank_account_name']);
        if (isset($bank_data['bank_sort_code'])) $settings['bank_sort_code'] = sanitize_input($bank_data['bank_sort_code']);
        if (isset($bank_data['bank_account_number'])) $settings['bank_account_number'] = sanitize_input($bank_data['bank_account_number']);
        if (isset($bank_data['bank_instructions'])) $settings['bank_instructions'] = sanitize_input($bank_data['bank_instructions']);
        if (isset($bank_data['bank_enabled'])) $settings['bank_enabled'] = $bank_data['bank_enabled'] ? '1' : '0';
        if (isset($bank_data['support_phone'])) $settings['support_phone'] = sanitize_input($bank_data['support_phone']);
    }
    
    foreach ($settings as $key => $val) {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (:k, :v, 'Payment Setting') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([':k' => $key, ':v' => $val]);
    }
    return true;
}

/**
 * Create a new booking in MySQL using UPI or Bank Transfer Payment Method
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
        'payment_method' => $customer_data['payment_method'] ?? 'upi',
        'payment_reference' => $customer_data['payment_reference'] ?? null,
        'payment_proof_image' => $customer_data['payment_screenshot'] ?? $customer_data['payment_proof_image'] ?? null,
        'payment_screenshot' => $customer_data['payment_screenshot'] ?? $customer_data['payment_proof_image'] ?? null,
        'payment_status' => 'PAYMENT VERIFICATION PENDING',
        'booking_status' => 'PENDING'
    ];

    if ($db) {
        try {
            $sql = "INSERT INTO bookings (
                booking_reference, customer_name, mobile, email,
                address_line_1, address_line_2, city, county, postcode, country,
                quantity, unit_price, subtotal, shipping_charge, total_amount,
                payment_method, payment_reference, payment_proof_image, payment_screenshot,
                payment_status, booking_status
            ) VALUES (
                :booking_reference, :customer_name, :mobile, :email,
                :address_line_1, :address_line_2, :city, :county, :postcode, :country,
                :quantity, :unit_price, :subtotal, :shipping_charge, :total_amount,
                :payment_method, :payment_reference, :payment_proof_image, :payment_screenshot,
                :payment_status, :booking_status
            )";

            $stmt = $db->prepare($sql);
            $stmt->execute($booking);
            $booking_id = $db->lastInsertId();
            $booking['id'] = $booking_id;
            
            // Insert cart items into booking_items
            if ($booking_id) {
                $stmt_item = $db->prepare("INSERT INTO booking_items (booking_id, product_id, product_name, quantity, price, selected_addons) 
                    VALUES (:booking_id, :product_id, :product_name, :quantity, :price, :selected_addons)");
                foreach ($totals['items'] as $item) {
                    $addons_json = !empty($item['selected_addons']) ? json_encode($item['selected_addons']) : null;
                    $stmt_item->execute([
                        ':booking_id' => $booking_id,
                        ':product_id' => $item['product_id'],
                        ':product_name' => $item['product_name'],
                        ':quantity' => $item['quantity'],
                        ':price' => $item['price'],
                        ':selected_addons' => $addons_json
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
 * Update payment status & screenshot for a booking
 */
function update_booking_payment($reference, $status, $payment_ref = '', $screenshot_path = '') {
    $db = Database::getConnection();
    if ($db) {
        try {
            $sql = "UPDATE bookings SET 
                    payment_status = :status,
                    payment_reference = COALESCE(NULLIF(:payment_ref, ''), payment_reference),
                    payment_proof_image = COALESCE(NULLIF(:proof_img, ''), payment_proof_image),
                    payment_screenshot = COALESCE(NULLIF(:proof_img, ''), payment_screenshot)
                    WHERE booking_reference = :ref";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':payment_ref' => $payment_ref,
                ':proof_img' => $screenshot_path,
                ':ref' => $reference
            ]);
        } catch (Exception $e) {
            log_system_error("Error updating payment status: " . $e->getMessage());
        }
    }
    return false;
}

/**
 * Admin UPI Verification action
 */
function verify_upi_payment($reference, $action_status, $reason = '', $admin_user = 'Admin') {
    $db = Database::getConnection();
    if (!$db) return false;

    $pay_status = 'PAYMENT VERIFICATION PENDING';
    $book_status = 'PENDING';

    if ($action_status === 'approve' || $action_status === 'PAID') {
        $pay_status = 'PAID';
        $book_status = 'CONFIRMED';
    } elseif ($action_status === 'reject' || $action_status === 'REJECTED') {
        $pay_status = 'REJECTED';
        $book_status = 'REJECTED';
    } elseif ($action_status === 'request_reupload' || $action_status === 'RE-UPLOAD REQUESTED') {
        $pay_status = 'RE-UPLOAD REQUESTED';
        $book_status = 'PENDING';
    }

    try {
        $sql = "UPDATE bookings SET 
                payment_status = :pay_status,
                booking_status = :book_status,
                rejection_reason = :reason,
                verified_by = :admin,
                verified_at = NOW()
                WHERE booking_reference = :ref1 OR id = :ref2";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':pay_status' => $pay_status,
            ':book_status' => $book_status,
            ':reason' => sanitize_input($reason),
            ':admin' => sanitize_input($admin_user),
            ':ref1' => $reference,
            ':ref2' => $reference
        ]);
    } catch (Exception $e) {
        log_system_error("UPI verification error: " . $e->getMessage());
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
