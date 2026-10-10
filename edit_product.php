<?php
require __DIR__ . '/bootstrap.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$data = $id ? $products->find($id) : null; $errors = [];
if ($data && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf(); [$input, $errors] = validateProduct($_POST);
    $data = array_merge($data, $input);
    if (!$errors) {
        try { $products->update($id, $input); redirectWithMessage('Product updated successfully. Review the updated values below.'); }
        catch (PDOException $ex) { $errors[] = 'Unable to update. Please try again.'; }
    }
}
require __DIR__ . '/header.php';
?>
<h1>Edit Product</h1><section class="card form-card">
<?php if (!$data): http_response_code(404); ?><p class="error">Product not found.</p><a href="products.php">Back to products</a>
<?php else: ?><p>Product ID: <strong><?= (int)$id ?></strong></p>
<?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
<form method="post"><?php require __DIR__ . '/form.php'; ?><div class="actions"><button>Save Changes</button><a class="button secondary" href="products.php">Cancel</a></div></form>
<?php endif; ?></section><?php require __DIR__ . '/footer.php'; ?>
