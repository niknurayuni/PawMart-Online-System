<?php
declare(strict_types=1);

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function require_csrf(): void
{
<<<<<<< HEAD
    $expected = $_SESSION['csrf'] ?? null;
    $submitted = $_POST['csrf'] ?? null;
    if (!is_string($expected) || !is_string($submitted) || !hash_equals($expected, $submitted)) {
=======
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
>>>>>>> 4664628ecdfc668ce16b14d7cd5ea36b51f92ac3
        http_response_code(419); exit('Your form expired. Please go back and try again.');
    }
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) redirect('?page=login'); }
function require_customer(): void
{
    require_login();
    if (user()['role'] !== 'customer') redirect('?page=admin-products');
}
function block_admin_shopping(): void
{
    if (user() && user()['role'] === 'admin') redirect('?page=admin-products');
}
function require_admin(): void
{
    require_login();
    if (user()['role'] !== 'admin') { http_response_code(403); exit('Admin access required.'); }
}
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = [$message, $type]; }
