<?php
declare(strict_types=1);

final class OrderService
{
    public function __construct(private PDO $db) {}

    /** Create an order and reserve its stock as one atomic operation. */
<<<<<<< HEAD
    public function place(int $customerId, array $cart, ?StripeCheckout $stripe = null): int
=======
    public function place(int $customerId, array $cart): int
>>>>>>> 4664628ecdfc668ce16b14d7cd5ea36b51f92ac3
    {
        if (!$cart) throw new RuntimeException('Your cart is empty.');
        $this->db->beginTransaction();
        try {
            $lines = []; $total = 0.0;
            $stock = $this->db->prepare('SELECT id,name,price,stock_quantity,is_active FROM products WHERE id=? FOR UPDATE');
<<<<<<< HEAD
            ksort($cart);
=======
>>>>>>> 4664628ecdfc668ce16b14d7cd5ea36b51f92ac3
            foreach ($cart as $id => $quantity) {
                $stock->execute([(int)$id]); $item = $stock->fetch();
                if (!$item || !$item['is_active'] || (int)$quantity <= 0 || (int)$item['stock_quantity'] < (int)$quantity) throw new RuntimeException('A cart item is no longer in stock. Update your cart and try again.');
                $item['quantity'] = (int)$quantity; $lines[] = $item; $total += (float)$item['price'] * (int)$quantity;
            }
            $order = $this->db->prepare("INSERT INTO orders (customer_id,total_amount,status) VALUES (?,?,'pending')");
            $order->execute([$customerId, $total]); $orderId = (int)$this->db->lastInsertId();
            $addItem = $this->db->prepare('INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity) VALUES (?,?,?,?,?)');
            $reduceStock = $this->db->prepare('UPDATE products SET stock_quantity=stock_quantity-? WHERE id=? AND stock_quantity>=?');
            foreach ($lines as $line) {
                $addItem->execute([$orderId, $line['id'], $line['name'], $line['price'], $line['quantity']]);
                $reduceStock->execute([$line['quantity'], $line['id'], $line['quantity']]);
                if ($reduceStock->rowCount() !== 1) throw new RuntimeException('Stock changed during checkout. Please try again.');
            }
<<<<<<< HEAD
            if ($stripe !== null) {
                $amount = (int)round($total * 100);
                if ($amount < 200) throw new InvalidArgumentException('The Stripe checkout minimum is RM 2.00.');
                $session = $stripe->create($orderId, $amount);
                $stripe->url($session);
                $savePayment = $this->db->prepare("UPDATE orders SET payment_status='unpaid', stripe_session_id=? WHERE id=?");
                $savePayment->execute([$session['id'], $orderId]);
            }
=======
>>>>>>> 4664628ecdfc668ce16b14d7cd5ea36b51f92ac3
            $this->db->commit();
            return $orderId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('SELECT o.*, c.name AS customer_name,c.email FROM orders o JOIN customers c ON c.id=o.customer_id ORDER BY o.created_at DESC')->fetchAll();
    }

    public function items(int $orderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id=?');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

<<<<<<< HEAD
    public function checkoutForCustomer(int $orderId, int $customerId): string
    {
        $stmt = $this->db->prepare('SELECT stripe_session_id FROM orders WHERE id=? AND customer_id=?');
        $stmt->execute([$orderId, $customerId]);
        $sessionId = $stmt->fetchColumn();
        if (!$sessionId) throw new InvalidArgumentException('Stripe checkout was not found for this order.');
        return $sessionId;
    }

    public function syncStripeSession(array $session, ?int $customerId = null): ?string
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id,customer_id,total_amount,payment_status FROM orders WHERE stripe_session_id=? FOR UPDATE');
            $stmt->execute([$session['id'] ?? '']);
            $order = $stmt->fetch();
            if (!$order || ($customerId !== null && (int)$order['customer_id'] !== $customerId)) {
                $this->db->commit();
                return null;
            }
            if (($session['livemode'] ?? null) !== false ||
                ($session['mode'] ?? '') !== 'payment' ||
                ($session['client_reference_id'] ?? '') !== (string)$order['id'] ||
                ($session['currency'] ?? '') !== 'myr' ||
                ($session['amount_total'] ?? null) !== (int)round((float)$order['total_amount'] * 100)) {
                throw new RuntimeException('Stripe payment details did not match the PawMart order.');
            }

            $payment = $order['payment_status'];
            if ($payment === 'unpaid') {
                if (($session['status'] ?? '') === 'complete' && ($session['payment_status'] ?? '') === 'paid') {
                    $payment = 'paid';
                    $update = $this->db->prepare("UPDATE orders SET payment_status='paid' WHERE id=?");
                    $update->execute([$order['id']]);
                } elseif (($session['status'] ?? '') === 'expired' && ($session['payment_status'] ?? '') === 'unpaid') {
                    $payment = 'expired';
                    $this->restoreStock((int)$order['id']);
                    $update = $this->db->prepare("UPDATE orders SET payment_status='expired', status='cancelled' WHERE id=?");
                    $update->execute([$order['id']]);
                }
            }
            $this->db->commit();
            return $payment;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }

=======
>>>>>>> 4664628ecdfc668ce16b14d7cd5ea36b51f92ac3
    public function setStatus(int $orderId, string $status): void
    {
        if (!in_array($status, ['pending','processing','shipped','completed','cancelled'], true)) {
            throw new InvalidArgumentException('Invalid order status.');
        }
        $this->db->beginTransaction();
        try {
<<<<<<< HEAD
            $stmt = $this->db->prepare('SELECT status, payment_status FROM orders WHERE id=? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            if (!$order) throw new InvalidArgumentException('Order not found.');
            if ($order['payment_status'] === 'unpaid') throw new InvalidArgumentException('Wait for Stripe payment or checkout expiration before updating the order.');
            $old = $order['status'];
            if ($old === 'cancelled' && $status !== 'cancelled') throw new InvalidArgumentException('Cancelled orders cannot be reopened.');
            if ($old === 'completed' && $status !== 'completed') throw new InvalidArgumentException('Completed orders cannot be changed.');
            if ($status === 'cancelled' && $old !== 'cancelled') {
                $this->restoreStock($orderId);
=======
            $stmt = $this->db->prepare('SELECT status FROM orders WHERE id=? FOR UPDATE');
            $stmt->execute([$orderId]);
            $old = $stmt->fetchColumn();
            if ($old === false) throw new InvalidArgumentException('Order not found.');
            if ($old === 'cancelled' && $status !== 'cancelled') throw new InvalidArgumentException('Cancelled orders cannot be reopened.');
            if ($old === 'completed' && $status !== 'completed') throw new InvalidArgumentException('Completed orders cannot be changed.');
            if ($status === 'cancelled' && $old !== 'cancelled') {
                $items = $this->db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id=?');
                $items->execute([$orderId]);
                $restore = $this->db->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');
                foreach ($items->fetchAll() as $line) {
                    if ($line['product_id'] !== null) $restore->execute([(int)$line['quantity'], (int)$line['product_id']]);
                }
>>>>>>> 4664628ecdfc668ce16b14d7cd5ea36b51f92ac3
            }
            $update = $this->db->prepare('UPDATE orders SET status=? WHERE id=?');
            $update->execute([$status, $orderId]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }
<<<<<<< HEAD

    private function restoreStock(int $orderId): void
    {
        $items = $this->db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id=? ORDER BY product_id');
        $items->execute([$orderId]);
        $restore = $this->db->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');
        foreach ($items->fetchAll() as $line) {
            if ($line['product_id'] !== null) $restore->execute([(int)$line['quantity'], (int)$line['product_id']]);
        }
    }
=======
>>>>>>> 4664628ecdfc668ce16b14d7cd5ea36b51f92ac3
}
