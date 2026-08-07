<?php
/**
 * AJAX Endpoint: Create Booking
 */

require_once __DIR__ . '/../includes/booking-functions.php';

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method', [], 405);
}

// CSRF Protection
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
if (!is_array($cart_items)) {
    $cart_items = [];
}
$payment_method = sanitize_input($_POST['payment_method'] ?? 'bank_transfer');

// Server-side validation
$errors = [];

if (empty($customer_name) || strlen($customer_name) < 2) {
    $errors[] = 'Please enter your full name.';
}

if (!validate_uk_mobile($mobile)) {
    $errors[] = 'Please enter a valid UK mobile number (e.g. +44 7700 900000 or 07700900000).';
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
    $errors[] = 'Please enter a valid UK postcode (e.g. SW1A 1AA).';
}

$total_qty = 0;
foreach ($cart_items as $item) {
    $total_qty += (int)($item['quantity'] ?? 0);
}

if (empty($cart_items) || $total_qty < 1) {
    $errors[] = 'Your shopping cart is empty. Please select products to book.';
} else if ($total_qty > 20) {
    $errors[] = 'You can book a maximum of 20 items in a single order.';
}

if (!in_array($payment_method, ['paypal', 'bank_transfer'])) {
    $errors[] = 'Please select a valid payment method.';
}

if (!empty($errors)) {
    json_response(false, implode('<br>', $errors), ['errors' => $errors], 422);
}

// Generate unique reference first so filename matches reference
$booking_ref = generate_unique_booking_reference();

// Receipt upload is mandatory
$proof_image_path = null;
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    json_response(false, 'Payment proof screenshot or receipt photo is mandatory. Please upload one to complete your booking.', [], 422);
}
$proof_image_path = save_uploaded_payment_receipt($_FILES['payment_proof'], $booking_ref);
if (!$proof_image_path) {
    json_response(false, 'Failed to process receipt image. Please upload a valid image file (JPG, PNG, or WEBP).', [], 422);
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
    'payment_method' => $payment_method,
    'payment_reference' => sanitize_input($_POST['payment_reference'] ?? ''),
    'payment_proof_image' => $proof_image_path
];

$booking = create_new_booking($booking_data);

// Store in PHP Session for success screen protection
$_SESSION['last_booking'] = $booking;
$_SESSION['booking_created_at'] = time();

json_response(true, 'Booking created successfully', [
    'booking_reference' => $booking['booking_reference'],
    'total_amount' => number_format($booking['total_amount'], 2),
    'payment_method' => $booking['payment_method'],
    'redirect_url' => 'success.php?ref=' . urlencode($booking['booking_reference'])
]);
