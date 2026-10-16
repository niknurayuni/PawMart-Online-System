# PawMart Online System 

A web-based ordering platform for a single local pet supply store. Customers
can register, browse and search products, manage their cart, checkout orders,
and view order history. Admins manage products, categories, stock, and order
status.

## Tech Stack
- Frontend: [e.g. React / HTML-CSS-JS]
- Backend: [e.g. Node.js / Express]
- Database: [e.g. MongoDB / MySQL]

## Features

**Customer**
- Register an account, log in
- Manage profile
- Search products
- Manage cart (add / update / remove items, view total)
- Checkout order
- View order history

**Admin**
- Log in to admin account
- Manage products (add / update / delete / update stock)
- Manage categories (add / update / delete)
- Manage stock
- View orders
- Update order status

## System Design

- **Use Case Diagram**: `docs/use-case-diagram.png` — covers Customer use cases
  (Register, Log In, Manage Profile, Search Product, Manage Cart, Checkout
  Order, View Order History) and Admin use cases (Log In, Manage Products,
  Manage Categories, Manage Stock, View Orders, Update Order Status).
- **Class Diagram**: `docs/class-diagram.png` — core classes are Customer,
  Admin, Product, Category, Cart, CartItem, Order, and OrderItem.

## Data Model (from Class Diagram)

| Class | Key Attributes | Key Methods |
|---|---|---|
| Customer | customerID, name, email, password, phoneNumber, address | register(), login(), manageProfile(), searchProduct(), manageCart(), viewOrderHistory() |
| Admin | adminID, name, email, password | login(), manageProducts(), manageCategories(), manageStock(), viewOrders(), updateOrderStatus() |
| Product | productID, productName, description, price, stockQuantity, categoryID, petType | addProduct(), updateProduct(), deleteProduct(), updateStock(), searchProduct() |
| Category | categoryID, categoryName, description | addCategory(), updateCategory(), deleteCategory() |
| Cart | cartID, customerID | addItem(), updateItem(), removeItem(), calculateTotal(), clearCart() |
| CartItem | cartItemID, cartID, productID, quantity | updateQuantity(), calculateSubtotal() |
| Order | orderID, customerID, totalAmount, orderDate, orderStatus | createOrder(), viewOrder(), updateStatus() |
| OrderItem | orderItemID, orderID, productID, quantity, price | calculateSubtotal() |

**Key relationships**: Customer places Orders and owns one Cart; Cart contains
CartItems which refer to Products; Order contains OrderItems which refer to
Products; Admin manages Products, Categories, Orders; Product belongs to one
Category.

## Setup & Installation
1. Clone the repo: `git clone <repo-url>`
2. Install dependencies: `npm install`
3. Set up environment variables: copy `.env.example` to `.env` and fill in values
4. Run the app: `npm start`

## Folder Structure
```
pawmart/
├── frontend/       # UI components/pages
├── backend/         # API routes, controllers, models
├── docs/            # planning report, use case diagram, class diagram, coding standards
└── README.md
```

## Team Members & Roles
| Name | Role |
|---|---|
| [Nik] | Team Leader |
| [Athirah] | Frontend |
| [Balqis] | Backend |
| [Isra] | Database / Docs |


## 1. Naming Conventions Dictionary



| Element | Convention | Example |
|---|---|---|
| Variables | camelCase | `cartTotal`, `stockQty` |
| Functions / Methods | camelCase, verb-first | `addToCart()`, `updateStock()` |
| Classes | PascalCase | `Product`, `OrderService` |
| Constants | UPPER_SNAKE_CASE | `MAX_CART_ITEMS` |
| Files (components/modules) | kebab-case or PascalCase (match framework norm) | `product-list.js` or `ProductList.jsx` |
| Folders | lowercase, plural for collections | `models/`, `controllers/`, `routes/` |
| Database tables/columns | snake_case, plural tables | `orders`, `order_items`, `stock_qty` |
| Git branches | `type/short-description` | `feature/cart-checkout`, `fix/stock-bug` |

## 2. Function and Method Modularity

- One function does one job; if a function exceeds roughly 30–40 lines, split
  it into smaller functions.
- Business logic is separated from UI/routes (e.g. `controllers/` call
  `services/`, which contain the logic, rather than routes calling the
  database directly).
- No hardcoded values — use constants or config files for things like limits,
  tax rates, and API endpoints.
- Each function has a single, clearly named responsibility; avoid functions
  that both fetch data and format it for display.

## 3. Documentation in Code

- Every function/method has a short doc-comment above it stating purpose,
  parameters, and return value (e.g. JSDoc for JavaScript, docstrings for
  Python).
- Commit messages follow the format `type: short description`
  (e.g. `feat: add cart total calculation`, `fix: stock validation bug`).

## 4. Git Workflow (Continuous Integration)

- Feature branches off `main`, named `feature/xxx` or `fix/xxx`; no direct
  commits to `main`.
- Pull requests require at least one teammate's review and approval before
  merging.
- Team leader holds final merge authority on `main`.
- Working, tested code is merged into `main` at least twice a week to keep
  the build runnable and merge conflicts small.


## Lab 3 & 4 — Product Management

### Overview
PawMart is an online pet shop system. The Product Management
module allows an administrator to add, search, view, edit,
and delete pet products.

### Technologies Used
- PHP
- MySQL
- HTML and CSS
- XAMPP for the local development environment

### Implemented Functions
- Add a new product.
- Display all products in a table.
- Search for products and display matching results.
- Display a "Product not found" message for unsuccessful searches.
- Edit existing product details.
- Confirm deletion before removing a product.
- Display updated product data after changes.

### Product Information
Each product contains:
- Product ID
- Product name
- Category
- Description
- Price in RM
- Stock quantity

### How to Run PawMart Locally
1. Install XAMPP.
2. Copy the project folder into `C:\xampp\htdocs\pawmart`.
3. Start Apache and MySQL in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin`.
5. Create a database named `[your database name]` if the SQL
   export does not already create it.
6. Select the database and import `database/database.sql`.
7. Update the project's database connection file with your
   local MySQL host, database name, username, and password.
   If a connection template is provided, copy it to the
   required connection filename before editing.
8. Open `http://localhost/pawmart/` in your browser.

### How to Check the Functions
1. Add a sample product and confirm it appears in the table.
2. Search for an existing product.
3. Search for an unknown product and check the message.
4. Edit a product and confirm the updated values.
5. Cancel a deletion and confirm the product remains.
6. Confirm deletion and check the remaining product list.

