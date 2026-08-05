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
$quantity       = (int)($_POST['quantity'] ?? 1);
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

if ($quantity < 1 || $quantity > 20) {
    $errors[] = 'Please select a valid quantity between 1 and 20.';
}

if (!in_array($payment_method, ['paypal', 'bank_transfer'])) {
    $errors[] = 'Please select a valid payment method.';
}

if (!empty($errors)) {
    json_response(false, implode('<br>', $errors), ['errors' => $errors], 422);
}

// Data is valid. Create booking via backend
$booking_data = [
    'customer_name' => $customer_name,
    'mobile' => $mobile,
    'email' => $email,
    'address_line_1' => $address_line_1,
    'address_line_2' => $address_line_2,
    'city' => $city,
    'county' => $county,
    'postcode' => $postcode,
    'quantity' => $quantity,
    'payment_method' => $payment_method,
    'payment_reference' => sanitize_input($_POST['payment_reference'] ?? '')
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
