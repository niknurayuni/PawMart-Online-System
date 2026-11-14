<?php
declare(strict_types=1);

final class Input
{
    public static function text(mixed $value, string $field, int $max, bool $required = true): string
    {
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidArgumentException("Enter a valid $field.");
        }
        $value = trim($value);
        if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max) {
            throw new InvalidArgumentException("Enter a $field of up to $max characters.");
        }
        return $value;
    }

    public static function integer(mixed $value, string $field, int $min, int $max = 2147483647): int
    {
        if (!is_int($value) && !is_string($value)) {
            throw new InvalidArgumentException("Enter a whole number for $field.");
        }
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($number === false) {
            throw new InvalidArgumentException("Enter a whole number from $min to $max for $field.");
        }
        return $number;
    }

    public static function registration(array $form): array
    {
        $name = self::text($form['name'] ?? null, 'name', 120);
        $email = strtolower(self::text($form['email'] ?? null, 'email address', 190));
        $password = $form['password'] ?? null;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (!is_string($password) || !mb_check_encoding($password, 'UTF-8') ||
            mb_strlen($password, 'UTF-8') < 8 || strlen($password) > 72 || str_contains($password, "\0") || trim($password) === '') {
            throw new InvalidArgumentException('Use a password with at least 8 characters and at most 72 bytes.');
        }
        return [$name, $email, $password];
    }

    public static function product(array $form): array
    {
        $categoryId = self::integer($form['category_id'] ?? 0, 'category ID', 0);

        return [
            'category_id' => $categoryId ?: null,
            'name' => self::text($form['name'] ?? null, 'product name', 150),
            'description' => self::text($form['description'] ?? '', 'description', 2000, false),
            'price' => self::price($form['price'] ?? null),
            'stock_quantity' => self::integer($form['stock_quantity'] ?? null, 'stock quantity', 0),
            'is_active' => self::integer($form['is_active'] ?? 0, 'visibility', 0, 1),
        ];
    }

    public static function price(mixed $value): string
    {
        if (!is_string($value) || !preg_match('/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D', trim($value))) {
            throw new InvalidArgumentException('Enter a price between RM 0 and RM 99,999,999.99 with up to two decimal places.');
        }
        return trim($value);
    }
}
