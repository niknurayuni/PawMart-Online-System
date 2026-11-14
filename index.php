<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/app/helpers.php';
require __DIR__ . '/app/AuthMiddleware.php';
require __DIR__ . '/app/Input.php';

set_exception_handler(static function (Throwable $exception): void {
    error_log('PawMart request failed: ' . get_class($exception) . ' (' . $exception->getCode() . ')');
    http_response_code(500);
    echo 'PawMart could not load this page. Please try again.';
});
require __DIR__ . '/app/Database.php';
require __DIR__ . '/app/ProductRepository.php';
require __DIR__ . '/app/CategoryRepository.php';
require __DIR__ . '/app/AuthService.php';
require __DIR__ . '/app/CustomerRepository.php';
require __DIR__ . '/app/OrderService.php';
require __DIR__ . '/app/Cart.php';
require __DIR__ . '/app/CartService.php';
require __DIR__ . '/app/StripeCheckout.php';

$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
$method = $_SERVER['REQUEST_METHOD'];
$isWebhook = $page === 'stripe-webhook' && $method === 'POST';
$action = '';
if (!$isWebhook) {
    AuthMiddleware::forPage($page);
    if ($method === 'POST') {
        require_csrf();
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        AuthMiddleware::forAction($action);
    }
}

try {
    $db = Database::connection();
} catch (PDOException $exception) {
    error_log('PawMart database connection failed: ' . $exception->getCode());
    http_response_code(503);
    exit('Cannot connect to MySQL. Follow the database setup steps in README.md, then refresh this page.');
}
$products = new ProductRepository($db);
$categories = new CategoryRepository($db);
$auth = new AuthService($db);
$customers = new CustomerRepository($db);
$orders = new OrderService($db);
$cart = new Cart();
$cartService = new CartService($cart, $products);

$stripe = new StripeCheckout(
    getenv('PAWMART_STRIPE_SECRET_KEY') ?: '',
    getenv('PAWMART_APP_URL') ?: 'http://localhost/pawmart1/index.php'
);

if ($isWebhook) {
    try {
        $event = StripeCheckout::verifyEvent(
            (string)file_get_contents('php://input'),
            $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '',
            getenv('PAWMART_STRIPE_WEBHOOK_SECRET') ?: ''
        );
        if (in_array($event['type'] ?? '', ['checkout.session.completed', 'checkout.session.expired'], true)) {
            $orders->syncStripeSession($stripe->retrieve($event['data']['object']['id'] ?? ''));
        }
        http_response_code(204);
    } catch (InvalidArgumentException $exception) {
        http_response_code(400);
    } catch (Throwable $exception) {
        error_log('PawMart Stripe webhook failed: ' . get_class($exception) . ' (' . $exception->getCode() . ')');
        http_response_code(503);
    }
    exit;
}

if ($page === 'payment-return' && $method === 'GET') {
    try {
        $session = $stripe->retrieve(Input::text($_GET['session_id'] ?? null, 'checkout reference', 255));
        $payment = $orders->syncStripeSession($session, (int)user()['id']);
        if ($payment === 'paid') {
            flash('Stripe test payment confirmed.');
        } else {
            flash('Payment could not be confirmed. Check your order history.', 'error');
        }
    } catch (RuntimeException|InvalidArgumentException $exception) {
        if ($exception instanceof PDOException) {
            error_log('PawMart payment verification database error: ' . $exception->getCode());
        }
        flash('Payment verification is unavailable. Check payment again from My orders.', 'error');
    }
    redirect('?page=orders');
}

if ($method === 'POST') {
    try {
        switch ($action) {
            case 'register':
                [$name, $email, $password] = Input::registration($_POST);
                $auth->register($name, $email, $password);
                $auth->login($email, $password);
                flash('Your PawMart account is ready.'); redirect('?page=home');
            case 'login':
                if (!$auth->login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) throw new InvalidArgumentException('Email or password was not recognized.');
                flash('Welcome back, ' . user()['name'] . '!'); redirect('?page=home');
            case 'admin_login':
                if (!$auth->login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) throw new InvalidArgumentException('Email or password was not recognized.');
                if (user()['role'] !== 'admin') {
                    unset($_SESSION['user']);
                    throw new InvalidArgumentException('This sign-in is for admin accounts. Use the regular sign-in for customer accounts.');
                }
                flash('Welcome to the PawMart admin area, ' . user()['name'] . '!'); redirect('?page=admin-products');
            case 'admin_setup':
                [$name, $email, $password] = Input::registration($_POST);
                $auth->createFirstAdmin($name, $email, $password);
                $auth->login($email, $password);
                flash('Admin account created. Welcome to PawMart!'); redirect('?page=admin-products');
            case 'logout':
                $_SESSION = [];
                session_destroy();
                redirect('?page=home');
            case 'profile_save':
                $name = trim((string)($_POST['name'] ?? ''));
                if ($name === '') throw new InvalidArgumentException('Enter your name.');
                $customers->updateProfile(user()['id'], $name, (string)($_POST['phone'] ?? ''), (string)($_POST['address'] ?? ''));
                $_SESSION['user']['name'] = $name;
                flash('Your profile has been updated.'); redirect('?page=profile');
            case 'cart_add':
                $cartService->add($_POST['product_id'] ?? null, $_POST['quantity'] ?? null);
                flash('Added to your cart.'); redirect('?page=home');
            case 'cart_update':
                $cartService->update($_POST['quantity'] ?? null);
                flash('Cart updated.'); redirect('?page=cart');
            case 'checkout':
                $stripe->assertConfigured();
                $customerId = (int)user()['id'];
                $orderId = $orders->place($customerId, $cart->items(), $stripe);
                $cart->clear();
                try {
                    $session = $stripe->retrieve($orders->checkoutForCustomer($orderId, $customerId));
                    redirect($stripe->url($session));
                } catch (RuntimeException|InvalidArgumentException $exception) {
                    error_log('PawMart checkout link unavailable: ' . get_class($exception) . ' (' . $exception->getCode() . ')');
                    flash('Order saved. Continue payment from My orders.', 'error');
                    redirect('?page=orders');
                }
            case 'payment_resume':
            case 'payment_check':
            case 'payment_cancel':
                $customerId = (int)user()['id'];
                $sessionId = $orders->checkoutForCustomer(Input::integer($_POST['id'] ?? null, 'order ID', 1), $customerId);
                $session = $stripe->retrieve($sessionId);
                $payment = $orders->syncStripeSession($session, $customerId);
                if ($action === 'payment_cancel' && $payment === 'unpaid' && ($session['status'] ?? '') === 'open') {
                    try {
                        $session = $stripe->expire($sessionId);
                    } catch (RuntimeException $exception) {
                        // A payment or expiration can win the race while cancellation is in flight.
                        $session = $stripe->retrieve($sessionId);
                        if (($session['status'] ?? '') === 'open') {
                            throw $exception;
                        }
                    }
                    $payment = $orders->syncStripeSession($session, $customerId);
                }
                if ($action === 'payment_resume' && $payment === 'unpaid' && ($session['status'] ?? '') === 'open') {
                    redirect($stripe->url($session));
                }
                flash(match ($payment) {
                    'paid' => 'Stripe test payment confirmed.',
                    'expired' => $action === 'payment_cancel'
                        ? 'Unpaid checkout cancelled. Reserved stock was released.'
                        : 'Stripe checkout expired. Reserved stock was released.',
                    default => 'Stripe payment is awaiting confirmation.',
                });
                redirect('?page=orders');
            case 'product_save':
                $productData = Input::product($_POST);
                if ($productData['category_id'] !== null && !$categories->exists($productData['category_id'])) {
                    throw new InvalidArgumentException('Choose an existing product category.');
                }
                $productId = Input::integer(($_POST['id'] ?? '') === '' ? 0 : $_POST['id'], 'product ID', 0) ?: null;
                if ($productId !== null && !$products->find($productId)) {
                    throw new InvalidArgumentException('That product no longer exists.');
                }
                $uploadedImagePath = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) throw new InvalidArgumentException('The image upload did not finish. Please try again.');
                    if ((int)$_FILES['image']['size'] > 3 * 1024 * 1024) throw new InvalidArgumentException('Choose an image smaller than 3 MB.');
                    $imageInfo = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
                    $imageTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($imageTypes[$imageInfo])) throw new InvalidArgumentException('Use a JPG, PNG, or WebP image.');
                    $uploadDirectory = __DIR__ . '/public/uploads/products';
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) throw new RuntimeException('Could not create the product image folder.');
                    $fileName = bin2hex(random_bytes(16)) . '.' . $imageTypes[$imageInfo];
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDirectory . '/' . $fileName)) throw new RuntimeException('Could not save the product image. Check folder permissions.');
                    $uploadedImagePath = 'public/uploads/products/' . $fileName;
                }
                $oldImagePath = null;
                try {
                    $db->beginTransaction();
                    $savedProductId = $products->save($productData, $productId);
                    if ($uploadedImagePath !== null) {
                        $oldImagePath = $products->setPrimaryImage($savedProductId, $uploadedImagePath);
                    }
                    $db->commit();
                } catch (Throwable $exception) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    if ($uploadedImagePath !== null) {
                        unlink(__DIR__ . '/' . $uploadedImagePath);
                    }
                    throw $exception;
                }
                if ($oldImagePath && str_starts_with($oldImagePath, 'public/uploads/products/')) {
                    $oldFile = __DIR__ . '/' . $oldImagePath;
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
                flash('Product saved.'); redirect('?page=admin-products');
            case 'product_delete':
                $productToDelete = $products->find((int)($_POST['id'] ?? 0)); $products->delete((int)($_POST['id'] ?? 0));
                if ($productToDelete && str_starts_with((string)$productToDelete['image_path'], 'public/uploads/products/')) { $oldFile = __DIR__ . '/' . $productToDelete['image_path']; if (is_file($oldFile)) unlink($oldFile); }
                flash('Product deleted.'); redirect('?page=admin-products');
            case 'category_save':
                $name = trim((string)($_POST['name'] ?? ''));
                if ($name === '') throw new InvalidArgumentException('Enter a category name.');
                $categories->save($name, !empty($_POST['id']) ? (int)$_POST['id'] : null); flash('Category saved.'); redirect('?page=admin-categories');
            case 'category_delete':
                $categories->delete((int)($_POST['id'] ?? 0)); flash('Category deleted. Products in it become uncategorized.'); redirect('?page=admin-categories');
            case 'category_update':
                $name = trim((string)($_POST['name'] ?? ''));
                if ($name === '') throw new InvalidArgumentException('Enter a category name.');
                $categories->save($name, (int)($_POST['id'] ?? 0)); flash('Category updated.'); redirect('?page=admin-categories');
            case 'order_status':
                $status = $_POST['status'] ?? null;
                if (!in_array($status, ['pending','processing','shipped','completed','cancelled'], true)) throw new InvalidArgumentException('Choose a valid order status.');
                $orders->setStatus(Input::integer($_POST['id'] ?? null, 'order ID', 1), $status); flash('Order status updated.'); redirect('?page=admin-orders');
            default: throw new InvalidArgumentException('Unknown action.');
        }
    } catch (PDOException $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('PawMart database action ' . $action . ' failed: ' . $exception->getCode());
        $duplicate = (int)($exception->errorInfo[1] ?? 0) === 1062;
        $message = match (true) {
            $duplicate && in_array($action, ['register', 'admin_setup'], true) => 'That email address is already registered.',
            $duplicate && in_array($action, ['category_save', 'category_update'], true) => 'That category name is already in use.',
            default => 'A database error prevented the request from completing. Please try again.',
        };
        flash($message, 'error');
        redirect('?page=' . rawurlencode($page));
    } catch (InvalidArgumentException|RuntimeException $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        flash($exception->getMessage(), 'error');
        redirect('?page=' . rawurlencode($page));
    }
}

$cartCount = $cart->count();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$categoryRows = $categories->all();
function paymentLabel(string $status): string {
    return match ($status) {
        'paid' => 'Paid (Stripe test)',
        'unpaid' => 'Awaiting payment',
        'expired' => 'Checkout expired',
        default => 'Legacy order',
    };
}

function layoutStart(string $title, int $cartCount, ?array $flash): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> · PawMart</title><link rel="icon" href="public/assets/favicon.ico?v=3" type="image/x-icon"><link rel="stylesheet" href="public/assets/style.css"><link rel="stylesheet" href="public/assets/enhancements.css"><script src="public/assets/live-search.js" defer></script></head><body>
<header class="topbar"><a class="brand" href="?page=<?= user() && user()['role']==='admin' ? 'admin-products' : 'home' ?>"><span class="brand-mark"><img src="public/assets/pawmart-logo.png" alt="PawMart logo"></span><span>PawMart<small>pet essentials, made easy</small></span></a><nav><?php if (user() && user()['role']==='admin'): ?><a href="?page=admin-products">Admin</a><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="nav-button">Sign out</button></form><?php else: ?><a href="?page=home">Shop</a><a href="?page=cart">Cart <b class="count"><?= $cartCount ?></b></a><?php if (user()): ?><a href="?page=profile">Profile</a><a href="?page=orders">My orders</a><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="nav-button">Sign out</button></form><?php else: ?><a href="?page=login">Sign in</a><a class="nav-cta" href="?page=register">Create account</a><?php endif; ?><?php endif; ?></nav></header>
<main class="container"><?php if ($flash): ?><div class="alert <?= e($flash[1]) ?>"><?= e($flash[0]) ?></div><?php endif; ?>
<?php }
function layoutEnd(): void { ?><footer><span>© <?= date('Y') ?> PawMart</span><span>Carefully chosen essentials for happy pets.</span></footer></main></body></html><?php }

if ($page === 'login' || $page === 'register' || $page === 'admin-login' || $page === 'admin-setup') {
    if ($page === 'admin-setup' && $auth->hasAdminAccount()) {
        layoutStart('Admin setup', $cartCount, $flash); ?>
        <section class="auth-card"><p class="eyebrow">PAWMART ADMIN</p><h1>Admin already set up</h1><p class="muted">An administrator account already exists. Sign in with that account to manage the shop.</p><a class="button primary" href="?page=admin-login">Go to admin sign in</a></section>
        <?php layoutEnd(); exit;
    }
    $isAdminLogin = $page === 'admin-login';
    $isAdminSetup = $page === 'admin-setup';
    layoutStart($isAdminSetup ? 'Create admin account' : ($isAdminLogin ? 'Admin sign in' : ($page === 'login' ? 'Sign in' : 'Create account')), $cartCount, $flash); ?>
    <section class="auth-card"><p class="eyebrow"><?= ($isAdminLogin || $isAdminSetup) ? 'PAWMART ADMIN' : 'PAWMART ACCOUNT' ?></p><h1><?= $isAdminSetup ? 'Create first admin' : ($isAdminLogin ? 'Admin sign in' : ($page === 'login' ? 'Welcome back' : 'Create your account')) ?></h1><p class="muted"><?= $isAdminSetup ? 'Set the name, email, and password for the first administrator.' : ($isAdminLogin ? 'Sign in with your administrator account to manage the shop.' : 'Shop for your best friend and keep your orders together.') ?></p>
    <form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="<?= $isAdminSetup ? 'admin_setup' : ($isAdminLogin ? 'admin_login' : ($page === 'login' ? 'login' : 'register')) ?>">
    <?php if ($page === 'register' || $isAdminSetup): ?><label>Your name<input name="name" required maxlength="120" autocomplete="name"></label><?php endif; ?>
    <label>Email address<input name="email" type="email" required autocomplete="email"></label><label>Password<input name="password" type="password" required minlength="8" autocomplete="<?= ($page === 'register' || $isAdminSetup) ? 'new-password' : 'current-password' ?>"></label><button class="button primary"> <?= $isAdminSetup ? 'Create admin account' : ($isAdminLogin ? 'Sign in as admin' : ($page === 'login' ? 'Sign in' : 'Create account')) ?></button></form>
    <p class="muted small"><?php if ($isAdminSetup): ?>Already have an admin account? <a href="?page=admin-login">Admin sign in</a>.<?php elseif ($isAdminLogin): ?>First setup? <a href="?page=admin-setup">Create the first admin account</a>.<br><br>Customer? <a href="?page=login">Regular sign in</a>.<?php elseif ($page === 'login'): ?>New to PawMart? <a href="?page=register">Create an account</a>.<br><br>Are you an administrator? <a href="?page=admin-login">Go to admin sign in</a>.<?php else: ?>Already registered? <a href="?page=login">Sign in</a>.<?php endif; ?></p></section>
    <?php layoutEnd(); exit;
}

if ($page === 'home') {
    $term = trim((string)($_GET['q'] ?? '')); $selectedCategory = (int)($_GET['category'] ?? 0);
    $items = $products->search();
    layoutStart('Shop', $cartCount, $flash); ?>
    <section class="hero"><div><p class="eyebrow">THE PAWMART PET SHOP</p><h1>Good days start<br>with happy paws.</h1><p>Everyday food, playful toys, and thoughtful care for the companions who make life better.</p><a class="button light" href="#catalog">Browse the shop <span>↓</span></a></div><div class="hero-art"><div class="sun"></div><div class="pet-emoji">🐾</div><div class="hero-tag">Made for<br>their good days</div></div></section>
    <section id="catalog" class="catalog"><div class="section-heading"><div><p class="eyebrow">SHOP PAWMART</p><h2>Pet favorites</h2></div><span class="muted" id="product-count"><?= count($items) ?> items</span></div>
    <form class="filters" method="get" data-live-search data-live-target="product-grid" data-live-count="product-count" data-live-empty="product-empty"><input type="hidden" name="page" value="home"><input name="q" value="<?= e($term) ?>" placeholder="Search food, toys, care..." aria-label="Search products"><select name="category"><option value="0">All categories</option><?php foreach ($categoryRows as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $selectedCategory === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?></select><button class="button dark">Search</button></form>
    <div class="product-grid" id="product-grid"><?php foreach ($items as $item): ?><article class="product-card" data-filter-item data-search="<?= e($item['name'] . ' ' . $item['description'] . ' ' . ($item['category_name'] ?? '')) ?>" data-category="<?= (int)($item['category_id'] ?? 0) ?>"><div class="product-art art-<?= (int)$item['id'] % 4 ?>"><?php if (!empty($item['image_path'])): ?><img class="product-image" src="<?= e($item['image_path']) ?>" alt="<?= e($item['name']) ?>" loading="lazy"><?php else: ?><span><?= e(strtoupper(substr($item['category_name'] ?: 'PET',0,1))) ?></span><i>🐾</i><?php endif; ?></div><div class="product-info"><div class="product-meta"><span><?= e($item['category_name'] ?: 'Pet essentials') ?></span><span><?= (int)$item['stock_quantity'] ?> in stock</span></div><h3><?= e($item['name']) ?></h3><p><?= e($item['description']) ?></p><div class="buy-row"><strong>RM <?= number_format((float)$item['price'],2) ?></strong><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cart_add"><input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="quantity" value="1"><button class="button add" <?= (int)$item['stock_quantity'] === 0 ? 'disabled' : '' ?>>Add to cart +</button></form></div></div></article><?php endforeach; ?></div><div class="empty" id="product-empty" <?= $items ? 'hidden' : '' ?>><div class="empty-icon">⌕</div><h3>No products found</h3><p>Try another search or category.</p></div></section>
    <?php layoutEnd(); exit;
}

if ($page === 'cart') {
    $lines = []; $total = 0.0;
    foreach ($cart->items() as $id => $qty) { $product = $products->find((int)$id); if ($product && $product['is_active']) { $product['quantity'] = min((int)$qty,(int)$product['stock_quantity']); $product['line_total'] = $product['quantity']*(float)$product['price']; $total += $product['line_total']; $lines[] = $product; } }
    layoutStart('Your cart', $cartCount, $flash); ?>
    <section class="page-head"><p class="eyebrow">YOUR PAWMART BAG</p><h1>Your cart</h1><p class="muted">Review your picks before placing your order.</p></section>
    <?php if (!$lines): ?><div class="empty"><div class="empty-icon">🛒</div><h3>Your cart is waiting for a little something</h3><p>Explore the shop and find a new favorite.</p><a class="button primary" href="?page=home">Browse products</a></div><?php else: ?><div class="cart-layout"><form method="post" class="cart-items"><?= csrf_field() ?><input type="hidden" name="action" value="cart_update"><?php foreach ($lines as $line): ?><div class="cart-line"><div class="mini-paw">🐾</div><div class="grow"><strong><?= e($line['name']) ?></strong><span>RM <?= number_format((float)$line['price'],2) ?> each</span></div><label class="qty">Qty<input type="number" name="quantity[<?= (int)$line['id'] ?>]" value="<?= (int)$line['quantity'] ?>" min="0" max="<?= (int)$line['stock_quantity'] ?>"></label><strong>RM <?= number_format((float)$line['line_total'],2) ?></strong></div><?php endforeach; ?><button class="button outline">Update cart</button><p class="small muted">Set quantity to 0 to remove an item.</p></form><aside class="summary"><h2>Order summary</h2><div class="summary-row"><span>Subtotal</span><span>RM <?= number_format($total,2) ?></span></div><div class="summary-row"><span>Delivery</span><span>Calculated at checkout</span></div><hr><div class="summary-row total"><span>Total</span><span>RM <?= number_format($total,2) ?></span></div><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="checkout"><button class="button primary full">Pay with Stripe (test)</button></form><?php if (!user()): ?><p class="small muted">Sign in or create an account to continue. <a href="?page=login">Sign in</a>.</p><?php endif; ?></aside></div><?php endif; ?>
    <?php layoutEnd(); exit;
}

if ($page === 'profile') {
    $profile = $customers->find(user()['id']);
    layoutStart('My profile', $cartCount, $flash); ?>
    <section class="page-head"><p class="eyebrow">YOUR PAWMART ACCOUNT</p><h1>My profile</h1><p class="muted">Keep your contact details up to date.</p></section>
    <section class="auth-card"><form method="post" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="profile_save"><label>Your name<input name="name" value="<?= e($profile['name'] ?? '') ?>" maxlength="120" required></label><label>Email address<input value="<?= e($profile['email'] ?? '') ?>" disabled></label><label>Phone<input name="phone" value="<?= e($profile['phone'] ?? '') ?>" maxlength="30"></label><label>Delivery address<textarea name="address" rows="4" maxlength="1000"><?= e($profile['address'] ?? '') ?></textarea></label><button class="button primary">Save profile</button></form></section>
    <?php layoutEnd(); exit;
}

if ($page === 'orders') {
    $customerOrders=$orders->forCustomer((int)user()['id']);
    layoutStart('My orders', $cartCount, $flash); ?><section class="page-head"><p class="eyebrow">ORDER HISTORY</p><h1>My orders</h1><p class="muted">A little update on the good things headed your way.</p></section>
    <?php if (!$customerOrders): ?><div class="empty"><h3>No orders yet</h3><p>Your first PawMart order will show up here.</p><a class="button primary" href="?page=home">Start shopping</a></div><?php else: ?><div class="order-list"><?php foreach ($customerOrders as $order): ?><article class="order-card"><div class="order-head"><div><span class="muted small">ORDER #<?= (int)$order['id'] ?></span><h3><?= date('M j, Y',strtotime($order['created_at'])) ?></h3></div><span class="status <?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></div><div class="order-items"><?php foreach ($orders->items((int)$order['id']) as $line): ?><div><span><?= e($line['product_name']) ?> × <?= (int)$line['quantity'] ?></span><span>RM <?= number_format((float)$line['unit_price']*(int)$line['quantity'],2) ?></span></div><?php endforeach; ?></div><div class="order-total"><span>Total</span><strong>RM <?= number_format((float)$order['total_amount'],2) ?></strong></div>
        <?php $paymentStatus = $order['payment_status'] ?? 'legacy'; ?>
        <p>Payment: <?= e(paymentLabel($paymentStatus)) ?></p>
        <?php if ($paymentStatus === 'unpaid'): ?>
            <form method="post" class="status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                <button class="button small-button" name="action" value="payment_resume">Continue payment</button>
                <button class="button small-button" name="action" value="payment_check">Check payment</button>
                <button class="button small-button" name="action" value="payment_cancel">Cancel unpaid checkout</button>
            </form>
        <?php endif; ?>
        </article><?php endforeach; ?></div><?php endif; ?>
    <?php layoutEnd(); exit;
}

if ($page === 'admin-products') {
    $editing = !empty($_GET['edit']) ? $products->find((int)$_GET['edit']) : null;
    $adminSearch = trim((string)($_GET['q'] ?? ''));
    $allProducts = $products->all();
    layoutStart('Manage products', $cartCount, $flash); ?><section class="page-head admin-head"><div><p class="eyebrow">PAWMART CONTROL ROOM</p><h1>Manage products</h1><p class="muted">Create, search, update, and remove shop products.</p></div><div class="admin-tabs"><a class="active" href="?page=admin-products">Products</a><a href="?page=admin-categories">Categories</a><a href="?page=admin-orders">Orders</a></div></section>
    <section class="admin-form"><h2><?= $editing ? 'Edit product' : 'Add a product' ?></h2><form method="post" class="form-grid" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="product_save"><input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>"><label>Product name<input name="name" required maxlength="150" value="<?= e($editing['name'] ?? '') ?>"></label><label>Category<select name="category_id"><option value="0">Uncategorized</option><?php foreach ($categoryRows as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)($editing['category_id'] ?? 0)===(int)$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option><?php endforeach; ?></select></label><label>Price (RM)<input name="price" type="number" min="0" step="0.01" required value="<?= e($editing['price'] ?? '0.00') ?>"></label><label>Stock quantity<input name="stock_quantity" type="number" min="0" step="1" required value="<?= e($editing['stock_quantity'] ?? '0') ?>"></label><label class="wide">Description<textarea name="description" rows="3" maxlength="2000"><?= e($editing['description'] ?? '') ?></textarea></label><label class="wide">Product picture (JPG, PNG, WebP; max 3 MB)<input name="image" type="file" accept="image/jpeg,image/png,image/webp"><?php if (!empty($editing['image_path'])): ?><span class="image-preview"><img src="<?= e($editing['image_path']) ?>" alt="Current product image">Current picture (choose a new file to replace it)</span><?php endif; ?></label><label class="check"><input type="checkbox" name="is_active" value="1" <?= !$editing || (int)$editing['is_active']===1?'checked':'' ?>> Visible in shop</label><div class="wide"><button class="button primary"><?= $editing ? 'Save changes' : 'Add product' ?></button><?php if ($editing): ?> <a class="button outline" href="?page=admin-products">Cancel</a><?php endif; ?></div></form></section>
    <form class="filters admin-search" method="get" data-live-search data-live-target="admin-products-table" data-live-count="admin-product-count" data-live-empty="admin-product-empty"><input type="hidden" name="page" value="admin-products"><input name="q" value="<?= e($adminSearch) ?>" placeholder="Search product name, description, or category..." aria-label="Search products"><button class="button dark">Search</button><?php if ($adminSearch !== ''): ?><a class="button outline" href="?page=admin-products">Clear</a><?php endif; ?><span class="muted small" id="admin-product-count"><?= count($allProducts) ?> items</span></form>
    <div class="table-wrap"><table><thead><tr><th>Product</th><th>Picture</th><th>Category</th><th>Price</th><th>Stock</th><th>Shop status</th><th>Actions</th></tr></thead><tbody id="admin-products-table"><?php foreach ($allProducts as $product): ?><tr data-filter-item data-search="<?= e($product['name'] . ' ' . $product['description'] . ' ' . ($product['category_name'] ?? '')) ?>"><td><strong><?= e($product['name']) ?></strong><small><?= e(substr($product['description'],0,70)) ?></small></td><td><span class="status <?= !empty($product['image_path']) ? 'completed' : 'cancelled' ?>"><?= !empty($product['image_path']) ? 'Added' : 'None' ?></span></td><td><?= e($product['category_name'] ?: 'Uncategorized') ?></td><td>RM <?= number_format((float)$product['price'],2) ?></td><td><?= (int)$product['stock_quantity'] ?></td><td><span class="status <?= $product['is_active']?'completed':'cancelled' ?>"><?= $product['is_active']?'Active':'Hidden' ?></span></td><td class="actions"><a href="?page=admin-products&edit=<?= (int)$product['id'] ?><?= $adminSearch !== '' ? '&q=' . rawurlencode($adminSearch) : '' ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this product?')"><?= csrf_field() ?><input type="hidden" name="action" value="product_delete"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><button class="link danger">Delete</button></form></td></tr><?php endforeach; ?></tbody></table><div class="table-empty" id="admin-product-empty" <?= $allProducts ? 'hidden' : '' ?>><?= $adminSearch !== '' ? 'No products matched your search.' : 'No products yet.' ?></div></div>
    <?php layoutEnd(); exit;
}

if ($page === 'admin-categories') {
    $allCategories=$categories->all(); $editingCategory=null; if (!empty($_GET['edit'])) foreach ($allCategories as $row) if ((int)$row['id']===(int)$_GET['edit']) $editingCategory=$row; layoutStart('Manage categories',$cartCount,$flash); ?><section class="page-head admin-head"><div><p class="eyebrow">PAWMART CONTROL ROOM</p><h1>Manage categories</h1><p class="muted">Keep the product catalog easy to browse.</p></div><div class="admin-tabs"><a href="?page=admin-products">Products</a><a class="active" href="?page=admin-categories">Categories</a><a href="?page=admin-orders">Orders</a></div></section>
    <section class="admin-form narrow"><h2><?= $editingCategory ? 'Edit category' : 'Add a category' ?></h2><form method="post" class="category-add"><?= csrf_field() ?><input type="hidden" name="action" value="<?= $editingCategory ? 'category_update' : 'category_save' ?>"><?php if ($editingCategory): ?><input type="hidden" name="id" value="<?= (int)$editingCategory['id'] ?>"><?php endif; ?><input name="name" required maxlength="100" placeholder="e.g. Grooming" value="<?= e($editingCategory['name'] ?? '') ?>"><button class="button primary"><?= $editingCategory ? 'Save changes' : 'Add category' ?></button><?php if ($editingCategory): ?><a class="button outline" href="?page=admin-categories">Cancel</a><?php endif; ?></form></section><div class="table-wrap"><table><thead><tr><th>Category</th><th>Products</th><th>Created</th><th>Actions</th></tr></thead><tbody><?php foreach ($allCategories as $cat): ?><tr><td><strong><?= e($cat['name']) ?></strong></td><td><?= (int)$cat['product_count'] ?></td><td><?= date('M j, Y',strtotime($cat['created_at'])) ?></td><td class="actions"><a href="?page=admin-categories&edit=<?= (int)$cat['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this category? Its products will remain in the shop.')"><?= csrf_field() ?><input type="hidden" name="action" value="category_delete"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>"><button class="link danger">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div>
    <?php layoutEnd(); exit;
}

if ($page === 'admin-orders') {
    $allOrders=$orders->all(); layoutStart('Manage orders',$cartCount,$flash); ?><section class="page-head admin-head"><div><p class="eyebrow">PAWMART CONTROL ROOM</p><h1>Manage orders</h1><p class="muted">Review orders and update their fulfillment status.</p></div><div class="admin-tabs"><a href="?page=admin-products">Products</a><a href="?page=admin-categories">Categories</a><a class="active" href="?page=admin-orders">Orders</a></div></section>
    <div class="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Payment</th><th>Update status</th></tr></thead><tbody><?php foreach ($allOrders as $order): ?><tr><td><strong>#<?= (int)$order['id'] ?></strong></td><td><?= e($order['customer_name']) ?><small><?= e($order['email']) ?></small></td><td><?= date('M j, Y',strtotime($order['created_at'])) ?></td><td>RM <?= number_format((float)$order['total_amount'],2) ?></td><td><span class="status <?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td><td><?= e(paymentLabel($order['payment_status'] ?? 'legacy')) ?></td><td><?php if (($order['payment_status'] ?? 'legacy') === 'unpaid'): ?>Awaiting Stripe<?php else: ?><form method="post" class="status-form"><?= csrf_field() ?><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?= (int)$order['id'] ?>"><select name="status"><?php foreach (['pending','processing','shipped','completed','cancelled'] as $status): ?><option value="<?= $status ?>" <?= $order['status']===$status?'selected':'' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select><button class="button small-button">Save</button></form><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$allOrders): ?><tr><td colspan="7" class="muted">No orders yet.</td></tr><?php endif; ?></tbody></table></div>
    <?php layoutEnd(); exit;
}

http_response_code(404); layoutStart('Page not found',$cartCount,$flash); ?><div class="empty"><h1>Page not found</h1><p>That PawMart page could not be found.</p><a class="button primary" href="?page=home">Return to shop</a></div><?php layoutEnd();
