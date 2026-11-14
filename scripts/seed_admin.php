<?php
declare(strict_types=1);
require __DIR__ . '/../app/Database.php';
$db = Database::connection();
$email = 'admin@pawmart.test';
$check = $db->prepare('SELECT id FROM customers WHERE email=?');
$check->execute([$email]);
if (!$check->fetch()) {
    $insert = $db->prepare("INSERT INTO customers (name,email,password_hash,role) VALUES (?,?,?,'admin')");
    $insert->execute(['PawMart Admin', $email, password_hash('Admin123!', PASSWORD_DEFAULT)]);
    echo "Admin account created: {$email} / Admin123!\nChange the password after the first login.\n";
} else {
    echo "Admin account already exists: {$email}\n";
}
