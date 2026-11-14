# PawMart Lab 5 and 6 - implementation notes

This copy extends the uploaded PHP/MySQL PawMart application. Existing registration/profile, product/category CRUD, cart, checkout and order pages are preserved.

## Changes implemented
- Cart refuses out-of-stock products and removes inactive products when cart quantities are updated.
- Checkout rejects zero/negative cart quantities, in addition to existing stock and active-product checks.
- Order status changes validate allowed statuses within the service; cancelling an order restores quantities for products still present in the database, atomically with the status change. Repeated cancellation does not restock twice.
- Completed orders cannot be changed and cancelled orders cannot be reopened.
- Added a standalone cart regression test.

## Run locally
1. Copy `pawmart` into your PHP web server's document root.
2. Create/import the database from `database/pawmart.sql` using phpMyAdmin or MySQL CLI.
3. Configure `config.php` or environment variables for your local MySQL credentials.
4. Open `http://localhost/pawmart/` in your browser.
5. Run `php -d zend.assertions=1 -d assert.exception=1 tests/cart_test.php` from the `pawmart` folder.
6. Test customer sign-up/login, product search, add/update/remove cart, checkout, order history, admin order status changes, and cancellation stock restoration.

## Important
- MySQL end-to-end tests and browser screenshots require a running local PHP/MySQL server; they have NOT been run in this environment.
- Import the supplied SQL into a fresh test database: it includes sample records.
- Assign each member responsibility for documenting and validating their respective module. Capture genuine screenshots locally, not simulated evidence.
