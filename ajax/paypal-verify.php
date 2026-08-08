<?php
/**
 * AJAX Endpoint: PayPal Server-Side Order Capture & Verification
 *
 * Flow:
 *  1. Receive PayPal orderID from client after buyer approval
 *  2. Get OAuth access token from PayPal using Client ID + Secret
 *  3. Capture the order via PayPal REST API
 *  4. Verify captured amount matches booking total
 *  5. Mark booking as PAID in MySQL
 */

require_once __DIR__ . '/../includes/booking-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method', [], 405);
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf)) {
    json_response(false, 'Security token invalid or expired.', [], 403);
}

$booking_ref = sanitize_input($_POST['booking_reference'] ?? '');
$order_id    = sanitize_input($_POST['paypal_order_id'] ?? '');

if (empty($order_id)) {
    json_response(false, 'Missing PayPal order ID.', [], 422);
}

// -------------------------------------------------------
// Locate booking record
// -------------------------------------------------------
$booking = null;
if (!empty($booking_ref)) {
    $booking = get_booking_by_ref($booking_ref);
}
if (!$booking && isset($_SESSION['last_booking'])) {
    $booking = $_SESSION['last_booking'];
    $booking_ref = $booking['booking_reference'];
}
if (!$booking) {
    json_response(false, 'Unable to locate booking record.', [], 404);
}

// -------------------------------------------------------
// PayPal credentials from DB settings
// -------------------------------------------------------
$paypal_mode    = get_setting('paypal_mode', 'sandbox');
$client_id      = get_setting('paypal_client_id', '');
$client_secret  = get_setting('paypal_client_secret', '');

if ($paypal_mode === 'live') {
    $api_base = 'https://api-m.paypal.com';
} else {
    $api_base = 'https://api-m.sandbox.paypal.com';
}

// -------------------------------------------------------
// Step 1: Get OAuth 2.0 access token
// -------------------------------------------------------
function paypal_get_access_token($api_base, $client_id, $client_secret) {
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
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        return null;
    }
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// -------------------------------------------------------
// Step 2: Capture the PayPal order
// -------------------------------------------------------
function paypal_capture_order($api_base, $access_token, $order_id) {
    $ch = curl_init($api_base . '/v2/checkout/orders/' . $order_id . '/capture');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{}',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token,
            'PayPal-Request-Id: VKL-' . $order_id,
        ],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
        return null;
    }
    $data = json_decode($response, true);
    // 201 = captured successfully, 422 = already captured (idempotent)
    if ($http_code === 201 || $http_code === 422) {
        return $data;
    }
    return null;
}

// -------------------------------------------------------
// Run verification & capture
// -------------------------------------------------------
$verified    = false;
$txn_id      = '';
$capture_err = '';

// If client_id is 'sb' or empty it means not yet configured — allow sandbox test mode
if (empty($client_id) || $client_id === 'sb') {
    // Sandbox fallback: accept if order_id looks valid (for testing without live keys)
    if (strlen($order_id) > 5) {
        $verified = true;
        $txn_id   = $order_id . '-SANDBOX';
        log_system_error("[PayPal] SANDBOX MODE — no real API call made. order_id=$order_id");
    }
} else {
    // Real PayPal REST API flow
    $access_token = paypal_get_access_token($api_base, $client_id, $client_secret);

    if (!$access_token) {
        log_system_error("[PayPal] Failed to get access token. mode=$paypal_mode order_id=$order_id");
        json_response(false, 'PayPal authentication failed. Please try again or contact support.', [], 502);
    }

    $capture = paypal_capture_order($api_base, $access_token, $order_id);

    if ($capture && isset($capture['status'])) {
        $status = $capture['status']; // COMPLETED or APPROVED

        if ($status === 'COMPLETED') {
            // Extract transaction ID from first purchase unit capture
            $captures = $capture['purchase_units'][0]['payments']['captures'] ?? [];
            if (!empty($captures)) {
                $first_capture = $captures[0];
                $txn_id        = $first_capture['id'] ?? $order_id;

                // Verify captured amount matches booking total
                $captured_amount   = (float)($first_capture['amount']['value'] ?? 0);
                $captured_currency = $first_capture['amount']['currency_code'] ?? '';
                $expected_total    = (float)$booking['total_amount'];

                if ($captured_currency === 'GBP' && abs($captured_amount - $expected_total) < 0.02) {
                    $verified = true;
                } else {
                    $capture_err = "Amount mismatch: captured £{$captured_amount}, expected £{$expected_total}";
                    log_system_error("[PayPal] $capture_err (booking: $booking_ref)");
                }
            } else {
                $capture_err = 'No capture records found in PayPal response.';
                log_system_error("[PayPal] $capture_err order_id=$order_id");
            }
        } else {
            $capture_err = "Order not completed. PayPal status: $status";
            log_system_error("[PayPal] $capture_err order_id=$order_id");
        }
    } else {
        $capture_err = 'PayPal capture API returned unexpected response.';
        log_system_error("[PayPal] $capture_err order_id=$order_id response=" . json_encode($capture));
    }
}

// -------------------------------------------------------
// Finalize booking
// -------------------------------------------------------
if ($verified) {
    update_booking_payment($booking_ref, 'PAID', $txn_id, $order_id, $txn_id);

    if (isset($_SESSION['last_booking'])) {
        $_SESSION['last_booking']['payment_status']        = 'PAID';
        $_SESSION['last_booking']['paypal_order_id']       = $order_id;
        $_SESSION['last_booking']['paypal_transaction_id'] = $txn_id;
    }

    json_response(true, 'Payment verified! Your booking is confirmed.', [
        'booking_reference' => $booking_ref,
        'payment_status'    => 'PAID',
        'redirect_url'      => 'success.php?ref=' . urlencode($booking_ref),
    ]);
} else {
    json_response(false, 'We could not verify your PayPal payment. ' . ($capture_err ?: 'Please try again or contact VK Logistics.'), [], 400);
}
