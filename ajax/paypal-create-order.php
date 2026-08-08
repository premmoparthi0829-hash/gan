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

$totals = calculate_order_totals($cart_items);
$curr_code = get_setting('currency_code', 'GBP');

// Return structured PayPal payload for client SDK
json_response(true, 'PayPal Order Initialized', [
    'amount' => [
        'currency_code' => $curr_code,
        'value' => number_format($totals['total_amount'], 2, '.', ''),
        'breakdown' => [
            'item_total' => [
                'currency_code' => $curr_code,
                'value' => number_format($totals['subtotal'], 2, '.', '')
            ],
            'shipping' => [
                'currency_code' => $curr_code,
                'value' => number_format($totals['shipping_charge'], 2, '.', '')
            ]
        ]
    ],
    'items' => array_map(function($item) use ($curr_code) {
        return [
            'name' => $item['product_name'] ?? 'Statue / Product',
            'unit_amount' => [
                'currency_code' => $curr_code,
                'value' => number_format($item['price'], 2, '.', '')
            ],
            'quantity' => (string)$item['quantity'],
            'category' => 'PHYSICAL_GOODS'
        ];
    }, $totals['items'])
]);
