<?php
declare(strict_types=1);

final class CartService
{
    public function __construct(private Cart $cart, private ProductRepository $products)
    {
    }

    public function add(mixed $productId, mixed $requestedQuantity): void
    {
        $id = Input::integer($productId, 'product ID', 1);
        $quantity = Input::integer($requestedQuantity, 'quantity', 1);
        $product = $this->products->find($id);
        if (!$product || !$product['is_active'] || (int)$product['stock_quantity'] <= 0) {
            throw new InvalidArgumentException('That product is unavailable or out of stock.');
        }
        $stock = (int)$product['stock_quantity'];
        if ($quantity > $stock - ($this->cart->items()[$id] ?? 0)) {
            throw new InvalidArgumentException('The requested cart quantity exceeds the available stock.');
        }
        $this->cart->add($id, $quantity, $stock);
    }

    public function update(mixed $quantities): void
    {
        if (!is_array($quantities)) {
            throw new InvalidArgumentException('Enter valid cart quantities.');
        }
        $updates = [];
        foreach ($quantities as $id => $quantity) {
            $id = Input::integer($id, 'product ID', 1);
            $quantity = Input::integer($quantity, 'quantity', 0);
            if (!isset($this->cart->items()[$id])) {
                throw new InvalidArgumentException('That item is not in your cart.');
            }
            $product = $this->products->find($id);
            $stock = $product && $product['is_active'] ? (int)$product['stock_quantity'] : 0;
            if ($quantity > $stock) {
                throw new InvalidArgumentException('A quantity exceeds available stock. Enter zero to remove an item.');
            }
            $updates[$id] = [$quantity, $stock];
        }
        foreach ($updates as $id => [$quantity, $stock]) {
            $this->cart->update($id, $quantity, $stock);
        }
    }
}
