<?php if (!$rows): ?><p class="notice">No products available.</p><?php else: ?>
<div class="table-scroll"><table><thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price (RM)</th><th>Stock</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e($row['product_id']) ?></td><td><?= e($row['product_name']) ?></td><td><?= e($row['category']) ?></td><td><?= number_format((float)$row['price'], 2) ?></td><td><?= e($row['stock_quantity']) ?></td><td class="actions"><a href="edit_product.php?id=<?= (int)$row['product_id'] ?>">Edit</a> <a class="danger" href="delete_product.php?id=<?= (int)$row['product_id'] ?>">Delete</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
