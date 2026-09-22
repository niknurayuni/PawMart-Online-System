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
