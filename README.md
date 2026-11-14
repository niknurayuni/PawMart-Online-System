# PawMart - PHP Full-Stack Lab 3 & Lab 4

A small online pet shop built with PHP 8+, MySQL, HTML, and CSS. The implementation follows the PawMart pet-shop scope in the supplied group report and the Lab 3/4 CRUD and integration requirements in the lab outline.

## Included features

- Customer registration, sign-in, profile editing, session-based shopping cart, checkout, and order history.
- Product search and category filtering for the storefront.
- Admin product create/search/edit/delete, category management, and order status updates.
- Prepared SQL statements, password hashing, server-side validation, output escaping, CSRF tokens, and transactional checkout with stock checks.
- Seed sample categories/products and create a demo administrator account.

## Requirements

- PHP 8.1 or later with `pdo_mysql` enabled.
- MySQL 8 or MariaDB 10.5 or later.

## Setup

1. Create the `pawmart` database and tables by importing `database/schema.sql` in MySQL Workbench or phpMyAdmin.
2. Configure the connection in `config.php`, or set `PAWMART_DB_HOST`, `PAWMART_DB_PORT`, `PAWMART_DB_NAME`, `PAWMART_DB_USER`, and `PAWMART_DB_PASS` environment variables.
3. From this project folder, run `php scripts/seed_admin.php` once to create the demo administrator.
4. Start the local PHP server with `php -S localhost:8000`.
5. Open `http://localhost:8000` in your browser.

### If the page says `pawmart.categories` does not exist

Import `database/repair_categories.sql` using phpMyAdmin's **Import** tab while using the `pawmart` database, then refresh the app. This repair creates the missing table and default categories without dropping existing records. For a new installation, import the complete `database/schema.sql` first instead.

Demo admin: `admin@pawmart.test` / `Admin123!`. Change this password after first use. Customers can register from the site.

## Lab 3 and Lab 4 coverage

| Lab requirement | Where it appears |
| --- | --- |
| Construct classes/modules with attributes and methods | `app/ProductRepository.php`, `app/CategoryRepository.php`, `app/CustomerRepository.php`, `app/Cart.php`, `app/OrderService.php`, `app/AuthService.php`, `app/Database.php` |
| Use suitable data types and input validation | POST handlers in `index.php`; MySQL types and constraints in `database/schema.sql` |
| Create/search/display records | Storefront search and category filters; admin product/category listings |
| Edit and delete records | Admin product edit/delete and category delete forms |
| Apply CRUD to modules and integrate the system | Products, categories, customer profile, cart, orders, and order items connected through the database and the shared entry point |
| Handle invalid input and edge cases | Validation, CSRF checks, escaped output, stock limits, database constraints, and transactional checkout |

## Database entities

`customers`, `categories`, `products`, `orders`, and `order_items`. The shopping cart is stored in the signed-in visitor's PHP session. Order items keep a product-name and unit-price snapshot so the order history remains readable when catalog details change.

## What to test

Run these checks in the browser after completing the setup. Use a customer account for customer features and the demo administrator account for admin features. The Stripe-specific payment checks are listed in the Labs 7 and 8 section below.

| Feature | What to do | Expected result |
| --- | --- | --- |
| Customer registration | Register with valid details, then try registering again with the same email. Also try an invalid email or a password that is too short. | A valid account is created. Duplicate email and invalid details are rejected with a message. |
| Sign-in and access | Sign in with a valid customer account, then try an incorrect password. Sign out and open a customer-only page. | Valid credentials sign in. Incorrect credentials are rejected, and protected customer pages require sign-in. |
| Product search and category filter | Search for a product that exists, search for a name that does not exist, and select a category. | Matching products or the selected category are shown. A no-match search gives a clear empty result. |
| Product management | As admin, add a product with valid details. Edit its name or price, then delete it. Try saving a negative price or stock value. | The product is created, updated, and deleted as requested. Invalid price or stock is rejected. |
| Category management | As admin, create a category, update its name, and delete it if it is not in use. | The category list reflects each valid change. Invalid or restricted changes show an appropriate message. |
| Shopping cart | Add a product, change its quantity, remove it, and try entering zero or more than the available stock. | Cart contents and total update correctly. Invalid quantities and quantities above stock are rejected. |
| Checkout and stock | Place an order with an item in stock. Then try to order more than the available quantity. | A valid order is saved with its order items and the correct total, and stock is reduced. An over-stock order is rejected without creating a partial order. |
| Order history and status | View the customer's order history. As admin, change an order's fulfillment status, then check it again as the customer. | The customer sees their order and the updated status. The order details remain readable. |
| Form protection | Submit a form normally, then try a stale or missing form token if you are checking security handling. | Normal submissions work. Requests without a valid CSRF token are rejected. |

## Notes for your lab submission

Run the app locally and capture your own screenshots of the storefront search, admin product CRUD, category management, cart/checkout, and order status update. The lab outline also asks for a report and GitHub code updates; this ZIP provides the implementation and setup notes, not fabricated screenshots or a submitted report.

## Labs 7 and 8 - Stripe Checkout

### Validation, access control, and product API

- Registration validates names, email addresses, and password lengths. Admin product forms validate names, prices, and stock. Cart forms reject quantities that exceed current stock.
- Database failures are logged server-side. Order changes use transactions to keep order and stock updates together.
- `app/AuthMiddleware.php` checks customer and admin access before protected actions. Guests can browse and manage their cart.
- Open `http://localhost:8000/api/products.php` to see active products as JSON. The endpoint accepts GET and returns 405 for other methods or 503 if the database is unavailable.

PawMart uses Stripe-hosted Checkout for test card payments in MYR. The shop reserves stock when it opens Checkout, records payment separately from fulfillment, and releases reserved stock when Stripe confirms expiration.

1. Start **MySQL** in XAMPP. In phpMyAdmin, select your existing `pawmart` database and import `database/lab7_8_stripe.sql` once. For a fresh database, import `database/schema.sql` first. Earlier orders will display **Legacy order** under payment.
2. Open the Stripe Dashboard in test mode and obtain a test secret key. Start PawMart from this folder in PowerShell. The first command prompts for the key without displaying it.

   ```powershell
   $env:PAWMART_STRIPE_SECRET_KEY = [System.Net.NetworkCredential]::new('', (Read-Host 'Stripe test secret key' -AsSecureString)).Password
   $env:PAWMART_APP_URL = 'http://localhost:8000/index.php'
   & C:\xampp\php\php.exe -S localhost:8000
   ```

3. Open http://localhost:8000/index.php, sign in as a customer, add a product, and click **Pay with Stripe (test)**.
4. For automatic updates, run this in a second terminal after installing and signing in to the Stripe CLI.

   ```powershell
   stripe listen --events checkout.session.completed,checkout.session.expired --forward-to "http://localhost:8000/index.php?page=stripe-webhook"
   ```

   Copy the webhook signing secret displayed by the CLI. In the first terminal, stop the PHP server with Ctrl+C, set the secret, and restart PHP. Keep the Stripe listener running.

   ```powershell
   $env:PAWMART_STRIPE_WEBHOOK_SECRET = [System.Net.NetworkCredential]::new('', (Read-Host 'Stripe webhook signing secret' -AsSecureString)).Password
   & C:\xampp\php\php.exe -S localhost:8000
   ```

   For Apache instead of the PHP development server, set the same environment variables in Apache's local configuration and restart Apache. Use the actual PawMart URL ending in `index.php` without a query string. The default is `http://localhost/pawmart1/index.php`.

| Test | Steps | Expected result |
| --- | --- | --- |
| Successful payment | Use test card `4242 4242 4242 4242`, a future expiry, and any three-digit CVC. | My orders shows **Paid (Stripe test)**. Fulfillment remains **Pending** until the admin updates it. |
| Declined payment | Use test card `4000 0000 0000 0002` with a future expiry and a three-digit CVC. | Stripe displays a decline. The order stays **Awaiting payment** and can be retried. |
| Back from checkout | Click Stripe's back button and use **Continue payment** on My orders. | PawMart opens the same active Checkout Session without creating a second order. |
| Cancel unpaid checkout | Return from Stripe and click **Cancel unpaid checkout** on My orders. | Stripe closes the payment link, PawMart marks the order **Cancelled**, and the reserved stock is restored once. |
| Expired checkout | Leave an unpaid session open until it expires after about 31 minutes. | The webhook marks the order **Cancelled** and releases stock once. Without the listener, sign in and click **Check payment** to synchronize it. |

PawMart also checks Stripe when the customer returns after payment. With the webhook listener running, payment and expiration can be recorded even if the customer closes their browser. Changing an order's fulfillment status to **Cancelled** does not issue a Stripe refund.

[Stripe test cards](https://docs.stripe.com/testing#cards) · [Stripe Checkout fulfillment](https://docs.stripe.com/checkout/fulfillment)
