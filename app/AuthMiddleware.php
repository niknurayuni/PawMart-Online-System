<?php
declare(strict_types=1);

final class AuthMiddleware
{
    public static function forPage(string $page): void
    {
        if (str_starts_with($page, 'admin-') && !in_array($page, ['admin-login', 'admin-setup'], true)) {
            require_admin();
        } elseif (in_array($page, ['profile', 'orders', 'payment-return'], true)) {
            require_customer();
        } elseif (in_array($page, ['home', 'cart'], true)) {
            block_admin_shopping();
        }
    }

    public static function forAction(string $action): void
    {
        switch ($action) {
            case 'product_save':
            case 'product_delete':
            case 'category_save':
            case 'category_update':
            case 'category_delete':
            case 'order_status':
                require_admin();
                break;
            case 'profile_save':
            case 'checkout':
            case 'payment_resume':
            case 'payment_check':
            case 'payment_cancel':
                require_customer();
                break;
            case 'cart_add':
            case 'cart_update':
                block_admin_shopping();
                break;
        }
    }
}
