# PawMart PHP + MySQL starter

Product Management for Lab 3 & 4: add, search, display, edit and confirm deletion.
Contains 12 fictional sample products and prices. This is a local admin demo with
no authentication; run it locally for learning. PHP 7.4+ with PDO MySQL is required.

## Setup using XAMPP on Windows
1. Extract the ZIP. Copy the entire `pawmart` folder to `C:\xampp\htdocs\pawmart`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin . Select Import, choose `database.sql`, and click Go.
4. Check `db.php`: host 127.0.0.1, database pawmart, user root, password empty.
   These are common local defaults; change them if your installation differs.
5. Open http://localhost/pawmart/ in your browser. Do not open PHP files by double-clicking.
6. Add a product, search it, edit it, cancel deletion, then confirm deletion.

Import database.sql once. Re-importing adds duplicate sample products, but does
not delete existing data. If Apache uses another port, include it in the URL.
If the database is unavailable, confirm MySQL is running and the credentials match.

## Four-member division
1. Member 1: database.sql, db.php, Product.php model/create method, add_product.php.
2. Member 2: products.php, product_table.php, Product.php getAll/search methods.
3. Member 3: edit_product.php, Product.php find/update methods.
4. Member 4: delete_product.php, Product.php delete method, integration and README.

Coordinate edits to Product.php; its shared methods are already wired together.
bootstrap.php handles shared validation/escaping/CSRF. form.php, header.php,
footer.php and style.css are shared interface files. Agree on ownership before editing.
Each member explains and captures screenshots of their own work. Member 4 compiles,
but everyone writes their own report section. Adapt this starter to your Lab 2 plan.

## Function behaviour
- Create: form checks required fields, non-negative price and whole-number stock.
- Search: exact ID or partial product name; shows Product not found for no matches.
- Display: complete list remains visible below search results.
- Edit: existing values loaded; save updates database and returns to refreshed table.
- Delete: full details shown; POST confirmation required; Cancel preserves record.
- Invalid edit/delete IDs show Product not found.
- Prepared queries, escaped HTML output and CSRF tokens are included.

## Report and evidence
Use TEST_CHECKLIST.md. Report: introduction, model/types/methods, create, search/display,
edit, delete, best-practices analysis, verification, contributions and conclusion.
Screenshots must come from your running app. Update GitHub with working code.

## Validation status
Archive integrity was checked. PHP and MySQL were unavailable in the packaging
environment, so syntax and database-backed behaviour have not been run-tested.
Verify the app on your local installation using the checklist. This starter does not include payment/cart/login.
