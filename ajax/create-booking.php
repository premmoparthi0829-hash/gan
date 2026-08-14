<?php
/**
 * AJAX Endpoint: Create Booking (UPI Payment System)
 */

require_once __DIR__ . '/../includes/booking-functions.php';

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method', [], 405);
}

// Action router (Create Booking or Re-upload Screenshot)
$action = sanitize_input($_POST['action'] ?? 'create_booking');

// Handle Payment Screenshot Re-upload by Customer
if ($action === 'reupload_screenshot') {
    $ref = sanitize_input($_POST['booking_reference'] ?? '');
    if (empty($ref)) {
        json_response(false, 'Booking Reference is required.', [], 422);
    }
    
    if (!isset($_FILES['payment_screenshot']) || $_FILES['payment_screenshot']['error'] !== UPLOAD_ERR_OK) {
        json_response(false, 'Please select a valid payment screenshot (JPG, PNG, or WEBP, max 10MB).', [], 422);
    }
    
    $screenshot_path = save_uploaded_payment_receipt($_FILES['payment_screenshot'], $ref);
    if (!$screenshot_path) {
        json_response(false, 'Failed to process screenshot image. Please upload a valid JPG, PNG, or WEBP image under 10MB.', [], 422);
    }
    
    $updated = update_booking_payment($ref, 'PAYMENT VERIFICATION PENDING', '', $screenshot_path);
    if ($updated) {
        // Also reset booking status to PENDING
        $db = Database::getConnection();
        if ($db) {
            $db->prepare("UPDATE bookings SET booking_status = 'PENDING' WHERE booking_reference = :ref")->execute([':ref' => $ref]);
        }
        json_response(true, 'Payment screenshot uploaded successfully! Verification pending.', [
            'booking_reference' => $ref,
            'screenshot_path' => $screenshot_path
        ]);
    } else {
        json_response(false, 'Failed to update order status. Please check your booking reference.', [], 500);
    }
}

// CSRF Protection for booking creation
$csrf = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf)) {
    json_response(false, 'Security token expired. Please refresh the page and try again.', [], 403);
}

// Sanitize inputs
$customer_name  = sanitize_input($_POST['customer_name'] ?? '');
$mobile         = sanitize_input($_POST['mobile'] ?? '');
$email          = sanitize_input($_POST['email'] ?? '');
$address_line_1 = sanitize_input($_POST['address_line_1'] ?? '');
$address_line_2 = sanitize_input($_POST['address_line_2'] ?? '');
$city           = sanitize_input($_POST['city'] ?? '');
$county         = sanitize_input($_POST['county'] ?? '');
$postcode       = sanitize_input($_POST['postcode'] ?? '');
$cart_raw = $_POST['cart'] ?? '[]';
$cart_items = json_decode($cart_raw, true);
if (!is_array($cart_items) || empty($cart_items)) {
    $qty = (int)($_POST['quantity'] ?? 1);
    if ($qty < 1) $qty = 1;
    $unit_price = (float)get_setting('unit_price', 14.99);
    $prod_name = get_setting('product_name', 'Ganesh Statue / Vinayaka Vigraha');
    $cart_items = [[
        'id' => 1,
        'name' => $prod_name,
        'product_name' => $prod_name,
        'price' => $unit_price,
        'quantity' => $qty
    ]];
}
$payment_method = 'upi';

// Server-side validation
$errors = [];

if (empty($customer_name) || strlen($customer_name) < 2) {
    $errors[] = 'Please enter your full name.';
}

if (!validate_uk_mobile($mobile)) {
    $errors[] = 'Please enter a valid mobile phone number.';
}

if (!validate_email($email)) {
    $errors[] = 'Please enter a valid email address.';
}

if (empty($address_line_1)) {
    $errors[] = 'Please enter your delivery street address.';
}

if (empty($city)) {
    $errors[] = 'Please enter your city / town.';
}

if (!validate_uk_postcode($postcode)) {
    $errors[] = 'Please enter a valid postcode.';
}

$total_qty = 0;
foreach ($cart_items as $item) {
    $total_qty += (int)($item['quantity'] ?? 0);
}

if ($total_qty > 50) {
    $errors[] = 'You can book a maximum of 50 items in a single order.';
}

if (!empty($errors)) {
    json_response(false, implode('<br>', $errors), ['errors' => $errors], 422);
}

// Generate unique reference
$booking_ref = generate_unique_booking_reference();

// Upload Payment Screenshot if provided
$proof_image_path = null;
if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
    $proof_image_path = save_uploaded_payment_receipt($_FILES['payment_screenshot'], $booking_ref);
}

// Data is valid. Create booking via backend
$booking_data = [
    'booking_reference' => $booking_ref,
    'customer_name' => $customer_name,
    'mobile' => $mobile,
    'email' => $email,
    'address_line_1' => $address_line_1,
    'address_line_2' => $address_line_2,
    'city' => $city,
    'county' => $county,
    'postcode' => $postcode,
    'cart_items' => $cart_items,
    'payment_method' => sanitize_input($_POST['payment_method'] ?? 'upi'),
    'payment_reference' => sanitize_input($_POST['payment_reference'] ?? ''),
    'payment_screenshot' => $proof_image_path
];

$booking = create_new_booking($booking_data);

// Store in PHP Session for success screen protection
$_SESSION['last_booking'] = $booking;
$_SESSION['booking_created_at'] = time();

json_response(true, 'Booking created successfully', [
    'booking_reference' => $booking['booking_reference'],
    'total_amount' => number_format($booking['total_amount'], 2),
    'payment_method' => $booking['payment_method'],
    'payment_status' => $booking['payment_status'],
    'redirect_url' => 'success.php?ref=' . urlencode($booking['booking_reference'])
]);
