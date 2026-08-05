<?php
/**
 * AJAX Endpoint: PayPal Order Initialization
 */

require_once __DIR__ . '/../includes/booking-functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method', [], 405);
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf)) {
    json_response(false, 'Security token expired.', [], 403);
}

$quantity = (int)($_POST['quantity'] ?? 1);
$totals = calculate_order_totals($quantity);

// Return structured PayPal payload for client SDK
json_response(true, 'PayPal Order Initialized', [
    'amount' => [
        'currency_code' => 'GBP',
        'value' => number_format($totals['total_amount'], 2, '.', ''),
        'breakdown' => [
            'item_total' => [
                'currency_code' => 'GBP',
                'value' => number_format($totals['subtotal'], 2, '.', '')
            ],
            'shipping' => [
                'currency_code' => 'GBP',
                'value' => number_format($totals['shipping_charge'], 2, '.', '')
            ]
        ]
    ],
    'items' => [
        [
            'name' => get_setting('product_name', 'Ganesh Statue / Vinayaka Vigraha'),
            'unit_amount' => [
                'currency_code' => 'GBP',
                'value' => number_format($totals['unit_price'], 2, '.', '')
            ],
            'quantity' => (string)$totals['quantity'],
            'category' => 'PHYSICAL_GOODS'
        ]
    ]
]);
