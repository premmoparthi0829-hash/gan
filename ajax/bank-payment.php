<?php
/**
 * AJAX Endpoint: Bank Transfer Payment Processing
 */

require_once __DIR__ . '/../includes/booking-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method', [], 405);
}

// CSRF Validation
$csrf = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf)) {
    json_response(false, 'Security token expired. Please refresh the page and try again.', [], 403);
}

$booking_ref = sanitize_input($_POST['booking_reference'] ?? '');
$payment_ref = sanitize_input($_POST['payment_reference'] ?? '');

if (empty($payment_ref)) {
    json_response(false, 'Please enter your Bank Transfer payment reference number.', [], 422);
}

$booking = null;
if (!empty($booking_ref)) {
    $booking = get_booking_by_ref($booking_ref);
}

if (!$booking && isset($_SESSION['last_booking'])) {
    $booking = $_SESSION['last_booking'];
    $booking_ref = $booking['booking_reference'];
}

if (!$booking) {
    json_response(false, 'Booking reference not found. Please create a booking first.', [], 404);
}

// Upload receipt photo if provided
$proof_image = '';
if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
    $uploaded_path = save_uploaded_payment_receipt($_FILES['payment_proof'], $booking_ref);
    if ($uploaded_path) {
        $proof_image = $uploaded_path;
    }
}

// Update booking record with Bank Reference, status & proof image
update_booking_payment($booking_ref, 'PAYMENT VERIFICATION PENDING', $payment_ref, '', '', $proof_image);

// Update active session
$_SESSION['last_booking']['payment_reference'] = $payment_ref;
$_SESSION['last_booking']['payment_status'] = 'PAYMENT VERIFICATION PENDING';
if (!empty($proof_image)) {
    $_SESSION['last_booking']['payment_proof_image'] = $proof_image;
}

json_response(true, 'Bank transfer details recorded. Your booking status is Payment Verification Pending.', [
    'booking_reference' => $booking_ref,
    'redirect_url' => 'success.php?ref=' . urlencode($booking_ref)
]);
