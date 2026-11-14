<?php
declare(strict_types=1);

require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/ProductRepository.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['error' => 'Use GET to list products.']);
    exit;
}

try {
    $repository = new ProductRepository(Database::connection());
    $products = [];
    foreach ($repository->search() as $row) {
        $products[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (string)$row['price'],
            'currency' => 'MYR',
            'stock_quantity' => (int)$row['stock_quantity'],
            'category' => $row['category_name'],
        ];
    }
    echo json_encode(['products' => $products], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (PDOException $exception) {
    error_log('PawMart product API database error: ' . $exception->getCode());
    http_response_code(503);
    echo json_encode(['error' => 'Products are temporarily unavailable.']);
} catch (Throwable $exception) {
    error_log('PawMart product API failed: ' . get_class($exception) . ' (' . $exception->getCode() . ')');
    http_response_code(500);
    echo json_encode(['error' => 'Could not load products.']);
}
