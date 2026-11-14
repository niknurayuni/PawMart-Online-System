<?php
declare(strict_types=1);

final class AuthService
{
    public function __construct(private PDO $db) {}

    public function register(string $name, string $email, string $password): void
    {
        $stmt = $this->db->prepare('INSERT INTO customers (name,email,password_hash) VALUES (?,?,?)');
        $stmt->execute([trim($name), strtolower(trim($email)), password_hash($password, PASSWORD_DEFAULT)]);
    }

    public function login(string $email, string $password): bool
    {
        $stmt = $this->db->prepare('SELECT id,name,email,password_hash,role FROM customers WHERE email=?');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) return false;
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
        return true;
    }

    public function hasAdminAccount(): bool
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM customers WHERE role='admin'")->fetchColumn() > 0;
    }

    public function createFirstAdmin(string $name, string $email, string $password): void
    {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->query('SELECT id FROM customers ORDER BY id LIMIT 1 FOR UPDATE');
            $lock->fetch();
            if ($this->hasAdminAccount()) throw new RuntimeException('An admin account already exists. Please use admin sign in.');
            $stmt = $this->db->prepare("INSERT INTO customers (name,email,password_hash,role) VALUES (?,?,?,'admin')");
            $stmt->execute([trim($name), strtolower(trim($email)), password_hash($password, PASSWORD_DEFAULT)]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }
}
