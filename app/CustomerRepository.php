<?php
declare(strict_types=1);

final class CustomerRepository
{
    public function __construct(private PDO $db) {}

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id,name,email,phone,address FROM customers WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function updateProfile(int $id, string $name, string $phone, string $address): void
    {
        $stmt = $this->db->prepare('UPDATE customers SET name=?,phone=?,address=? WHERE id=?');
        $stmt->execute([trim($name), trim($phone) ?: null, trim($address) ?: null, $id]);
    }
}
