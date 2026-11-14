<?php
require __DIR__ . '/bootstrap.php';
$query = trim((string)($_GET['q'] ?? ''));
$all = $products->getAll();
$matches = $query !== '' ? $products->search($query) : [];
require __DIR__ . '/header.php';
?>
<div class="top"><div><h1>Manage products</h1><p>Add, find and maintain PawMart inventory.</p></div><a class="button" href="add_product.php">+ Add Product</a></div>
<?php if (isset($_SESSION['message'])): ?><p class="success"><?= e($_SESSION['message']) ?></p><?php unset($_SESSION['message']); endif; ?>
<section class="card"><h2>Search products</h2><form method="get" class="search"><input name="q" placeholder="Product name or exact ID" aria-label="Search products" value="<?= e($query) ?>"><button>Search</button><a class="button secondary" href="products.php">Clear Search</a></form>
<?php if ($query !== ''): ?><h3>Results for “<?= e($query) ?>”</h3><?php if (!$matches): ?><p class="notice">Product not found.</p><?php else: $rows = $matches; require __DIR__ . '/product_table.php'; endif; endif; ?></section>
<section class="card"><h2>All products <small>(<?= count($all) ?>)</small></h2><?php $rows = $all; require __DIR__ . '/product_table.php'; ?></section>
<?php require __DIR__ . '/footer.php'; ?>
