<?php
declare(strict_types=1);

final class CategoryRepository
{
    public function __construct(private PDO $db) {}

    public function all(): array
    {
        return $this->db->query('SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM categories WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetchColumn() !== false;
    }

    public function save(string $name, ?int $id = null): void
    {
        if ($id) {
            $stmt = $this->db->prepare('UPDATE categories SET name=? WHERE id=?');
            $stmt->execute([trim($name), $id]);
        } else {
            $stmt = $this->db->prepare('INSERT INTO categories (name) VALUES (?)');
            $stmt->execute([trim($name)]);
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id=?');
        $stmt->execute([$id]);
    }
}
