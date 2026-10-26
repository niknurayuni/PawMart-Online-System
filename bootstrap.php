<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Product.php';
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
function csrfField(): void { echo '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'; }
function checkCsrf(): void {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403); exit('Invalid form token. Reload the page and try again.');
    }
}
function redirectWithMessage(string $message): void {
    $_SESSION['message'] = $message;
    header('Location: products.php'); exit;
}
function validateProduct(array $input): array {
    $data = [
        'product_name' => trim((string)($input['product_name'] ?? '')),
        'category' => trim((string)($input['category'] ?? '')),
        'description' => trim((string)($input['description'] ?? '')),
        'price' => trim((string)($input['price'] ?? '')),
        'stock_quantity' => trim((string)($input['stock_quantity'] ?? ''))
    ];
    $errors = [];
    if ($data['product_name'] === '' || strlen($data['product_name']) > 100) $errors[] = 'Enter a product name of up to 100 bytes.';
    if (!in_array($data['category'], categories(), true)) $errors[] = 'Choose a valid category.';
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $data['price'])) $errors[] = 'Enter a non-negative price with up to two decimal places.';
    if (filter_var($data['stock_quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]) === false) $errors[] = 'Stock must be a non-negative whole number.';
    if (strlen($data['description']) > 5000) $errors[] = 'Keep the description below 5000 bytes.';
    return [$data, $errors];
}
function categories(): array { return ['Pet Food','Pet Treats','Pet Toys','Accessories','Feeding Supplies','Grooming Supplies','Travel Supplies']; }
try { $products = new Product(database()); }
catch (PDOException $ex) {
    http_response_code(503);
    exit('Database unavailable. Import database.sql and check the local settings in db.php.');
}
