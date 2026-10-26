<?php csrfField(); ?>
<label>Product name<input name="product_name" required maxlength="100" value="<?= e($data['product_name'] ?? '') ?>"></label>
<label>Category<select name="category" required><option value="">Choose category</option><?php foreach (categories() as $category): ?><option value="<?= e($category) ?>" <?= ($data['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
<label>Description<textarea name="description" maxlength="5000" rows="3"><?= e($data['description'] ?? '') ?></textarea></label>
<div class="grid"><label>Price (RM)<input type="number" name="price" min="0" max="99999999.99" step="0.01" required value="<?= e($data['price'] ?? '') ?>"></label><label>Stock quantity<input type="number" name="stock_quantity" min="0" max="2147483647" step="1" required value="<?= e($data['stock_quantity'] ?? '') ?>"></label></div>
