<?php
/** Member 1: Product module and storage methods. Members 2-4 own related operations. */
class Product {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }
    public function getAll(): array {
        return $this->db->query('SELECT * FROM products ORDER BY product_id DESC')->fetchAll();
    }
    public function find(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE product_id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function search(string $query): array {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE CAST(product_id AS CHAR) = ? OR product_name LIKE ? ORDER BY product_id DESC');
        $stmt->execute([$query, '%' . $query . '%']);
        return $stmt->fetchAll();
    }
    public function create(array $data): void {
        $stmt = $this->db->prepare('INSERT INTO products (product_name, category, description, price, stock_quantity) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(array_values($data));
    }
    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE products SET product_name=?, category=?, description=?, price=?, stock_quantity=? WHERE product_id=?');
        $stmt->execute([...array_values($data), $id]);
    }
    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM products WHERE product_id=?');
        $stmt->execute([$id]);
    }
}
