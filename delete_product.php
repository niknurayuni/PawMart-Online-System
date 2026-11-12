<?php
require __DIR__ . '/bootstrap.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$data = $id ? $products->find($id) : null; $error = '';
if ($data && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    try { $products->delete($id); redirectWithMessage('Product deleted successfully. Remaining products are displayed below.'); }
    catch (PDOException $ex) { $error = 'Unable to delete. Please try again.'; }
}
require __DIR__ . '/header.php';
?>
<h1>Delete Product</h1><section class="card form-card">
<?php if (!$data): http_response_code(404); ?><p class="error">Product not found.</p><a href="products.php">Back to products</a>
<?php else: ?><p class="notice">Are you sure you want to delete this product?</p>
<?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<dl><?php foreach (['product_id'=>'ID','product_name'=>'Name','category'=>'Category','description'=>'Description','price'=>'Price (RM)','stock_quantity'=>'Stock'] as $field=>$label): ?><dt><?= e($label) ?></dt><dd><?= e($data[$field]) ?></dd><?php endforeach; ?></dl>
<form method="post"><?php csrfField(); ?><div class="actions"><button class="delete">Confirm Delete</button><a class="button secondary" href="products.php">Cancel</a></div></form>
<?php endif; ?></section><?php require __DIR__ . '/footer.php'; ?>
