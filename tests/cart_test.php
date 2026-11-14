<?php
declare(strict_types=1);
require __DIR__ . '/../app/Cart.php';
$_SESSION = [];
$cart = new Cart();
$cart->add(1, 2, 5);
assert($cart->count() === 2);
$cart->add(1, 9, 5);
assert($cart->count() === 5);
$cart->update(1, 3, 5);
assert($cart->items()[1] === 3);
$cart->update(1, 0, 5);
assert($cart->count() === 0);
$rejected = false;
try { $cart->add(2, 1, 0); } catch (InvalidArgumentException $e) { $rejected = true; }
assert($rejected);
echo "Cart tests passed.\n";
