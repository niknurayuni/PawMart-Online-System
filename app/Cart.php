<?php
declare(strict_types=1);

/** Session-backed cart with stock-limited quantity updates. */
final class Cart
{
    public function add(int $productId, int $quantity, int $stock): void
    {
        if ($productId <= 0 || $stock <= 0) throw new InvalidArgumentException('This product is out of stock.');
        $_SESSION['cart'][$productId] = min($stock, (int)($_SESSION['cart'][$productId] ?? 0) + max(1, $quantity));
    }

    public function update(int $productId, int $quantity, int $stock): void
    {
        if ($quantity <= 0 || $stock <= 0) unset($_SESSION['cart'][$productId]);
        else $_SESSION['cart'][$productId] = min($quantity, $stock);
    }

    public function items(): array { return $_SESSION['cart'] ?? []; }
    public function count(): int { return array_sum($this->items()); }
    public function clear(): void { unset($_SESSION['cart']); }
}
