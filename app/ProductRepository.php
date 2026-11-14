<?php
declare(strict_types=1);

final class ProductRepository
{
    public function __construct(private PDO $db) {}

    public function search(string $term = '', int $categoryId = 0): array
    {
        $sql = 'SELECT p.*, c.name AS category_name, pi.image_path FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1 WHERE p.is_active=1';
        $params = [];
        if ($term !== '') {
            $sql .= ' AND (p.name LIKE :term OR p.description LIKE :term)';
            $params['term'] = '%' . $term . '%';
        }
        if ($categoryId > 0) {
            $sql .= ' AND p.category_id=:category_id';
            $params['category_id'] = $categoryId;
        }
        $sql .= ' ORDER BY p.name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function all(string $term = ''): array
    {
        $sql = 'SELECT p.*, c.name AS category_name, pi.image_path FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1';
        if ($term !== '') {
            $sql .= ' WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?';
        }
        $sql .= ' ORDER BY p.name';
        $stmt = $this->db->prepare($sql);
        $needle = '%' . $term . '%';
        $stmt->execute($term === '' ? [] : [$needle, $needle, $needle]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT p.*, pi.image_path FROM products p LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1 WHERE p.id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function save(array $data, ?int $id = null): int
    {
        $values = [$data['category_id'] ?: null, trim($data['name']), trim($data['description']), (float)$data['price'], (int)$data['stock_quantity'], (int)$data['is_active']];
        if ($id) {
            $values[] = $id;
            $stmt = $this->db->prepare('UPDATE products SET category_id=?,name=?,description=?,price=?,stock_quantity=?,is_active=? WHERE id=?');
        } else {
            $stmt = $this->db->prepare('INSERT INTO products (category_id,name,description,price,stock_quantity,is_active) VALUES (?,?,?,?,?,?)');
        }
        $stmt->execute($values);
        return $id ?? (int)$this->db->lastInsertId();
    }

    public function setPrimaryImage(int $productId, string $imagePath): ?string
    {
        $stmt = $this->db->prepare('SELECT id,image_path FROM product_images WHERE product_id=? AND is_primary=1');
        $stmt->execute([$productId]);
        $current = $stmt->fetch();
        if ($current) {
            $update = $this->db->prepare('UPDATE product_images SET image_path=? WHERE id=?');
            $update->execute([$imagePath, $current['id']]);
            return $current['image_path'];
        }
        $insert = $this->db->prepare('INSERT INTO product_images (product_id,image_path,is_primary) VALUES (?,?,1)');
        $insert->execute([$productId, $imagePath]);
        return null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id=?');
        $stmt->execute([$id]);
    }
}
