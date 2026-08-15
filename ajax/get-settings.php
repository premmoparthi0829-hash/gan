<?php
/**
 * AJAX Endpoint: Get Dynamic Product Settings & Catalog
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../includes/functions.php';

$settings = get_all_settings();
$settings['csrf_token'] = get_csrf_token();

$db = Database::getConnection();
$categories = [];
$products = [];
if ($db) {
    try {
        $categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
        $products = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC")->fetchAll();
        if (!empty($products)) {
            foreach ($products as &$p) {
                $p['addons'] = get_product_addons($p['id']);
                $p['reusable_addons'] = get_product_reusable_addons($p['id'], true);
            }
            unset($p);
        }
    } catch (Exception $e) {
        log_system_error("Failed to load catalog in get-settings.php: " . $e->getMessage());
    }
}

json_response(true, 'Settings & catalog loaded successfully', [
    'settings' => $settings,
    'categories' => $categories,
    'products' => $products
]);
