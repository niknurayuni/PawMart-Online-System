<?php
require __DIR__ . '/bootstrap.php';
$data = []; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf(); [$data, $errors] = validateProduct($_POST);
    if (!$errors) {
        try { $products->create($data); redirectWithMessage('Product added successfully.'); }
        catch (PDOException $ex) { $errors[] = 'Unable to save. Please try again.'; }
    }
}
require __DIR__ . '/header.php';
?>
<h1>Add Product</h1><section class="card form-card">
<?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
<form method="post"><?php require __DIR__ . '/form.php'; ?><div class="actions"><button>Save Product</button><a class="button secondary" href="add_product.php">Clear</a><a href="products.php">Back to products</a></div></form></section>
<?php require __DIR__ . '/footer.php'; ?>
