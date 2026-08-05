<?php
/**
 * AJAX Endpoint: Get Dynamic Product Settings & Config
 */

require_once __DIR__ . '/../includes/functions.php';

$settings = get_all_settings();
$settings['csrf_token'] = get_csrf_token();

json_response(true, 'Settings loaded successfully', ['settings' => $settings]);
