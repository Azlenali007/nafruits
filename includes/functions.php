<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Core Business Logic, Formatting, Cart, and Security Utilities
 */

declare(strict_types=1);

/**
 * HTML Escaping shorthand
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency with symbol
 */
function formatPrice(float|int|string $amount): string {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 0);
}

/**
 * Get setting value from database or fallback default
 */
function getSetting(string $key, string $default = ''): string {
    static $settingsCache = null;

    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $rows = Database::fetchAll("SELECT key_name, key_value FROM shop_settings");
            foreach ($rows as $row) {
                $settingsCache[$row['key_name']] = $row['key_value'];
            }
        } catch (Exception $e) {
            // DB might be down or not yet installed
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Flash messages system
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlashes(): array {
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

function hasFlashes(): bool {
    return !empty($_SESSION['flash_messages']);
}

/**
 * Generate a unique sequential order number like #NF202510081023
 */
function generateOrderNumber(): string {
    $prefix = 'NF' . date('Ymd');
    $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    return $prefix . $random;
}

// ==========================================
// SHOPPING CART (Session + Database Persistence)
// ==========================================

function initCart(): void {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

/**
 * Return raw cart array: key => [product_id, unit_id, quantity]
 */
function getCart(): array {
    initCart();
    return $_SESSION['cart'];
}

function getCartItemCount(): int {
    $cart = getCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += (int)($item['quantity'] ?? 1);
    }
    return $count;
}

/**
 * Add or increment item in cart
 */
function addToCart(int $productId, ?int $unitId = null, float $quantity = 1): array {
    initCart();

    $db = Database::getConnection();
    if (!$db) {
        return ['success' => false, 'error' => 'Database connection unavailable'];
    }

    // Verify product exists and is active
    $product = Database::fetchOne("SELECT id, name, base_price, default_unit, stock_quantity, status FROM products WHERE id = ? AND status = 'active'", [$productId]);
    if (!$product) {
        return ['success' => false, 'error' => 'Product is currently unavailable'];
    }

    // Resolve unit if specified or pick default
    if ($unitId) {
        $unit = Database::fetchOne("SELECT id, unit_name, price, stock_quantity FROM product_units WHERE id = ? AND product_id = ?", [$unitId, $productId]);
    } else {
        $unit = Database::fetchOne("SELECT id, unit_name, price, stock_quantity FROM product_units WHERE product_id = ? AND is_default = 1 LIMIT 1", [$productId]);
    }

    $finalUnitId = $unit ? (int)$unit['id'] : null;
    $cartKey = $productId . '_' . ($finalUnitId ?? 0);

    $currentQty = $_SESSION['cart'][$cartKey]['quantity'] ?? 0;
    $newQty = $currentQty + $quantity;

    // Check available stock
    $availableStock = $unit ? (float)$unit['stock_quantity'] : (float)$product['stock_quantity'];
    if ($newQty > $availableStock) {
        return ['success' => false, 'error' => 'Only ' . $availableStock . ' available in stock'];
    }

    $_SESSION['cart'][$cartKey] = [
        'product_id' => $productId,
        'unit_id' => $finalUnitId,
        'quantity' => $newQty
    ];

    // If logged in, sync to database
    if (isCustomerLoggedIn()) {
        $customerId = (int)$_SESSION['customer_id'];
        Database::execute(
            "INSERT INTO customer_carts (customer_id, product_id, unit_id, quantity) 
             VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE quantity = ?",
            [$customerId, $productId, $finalUnitId, $newQty, $newQty]
        );
    }

    return [
        'success' => true,
        'message' => 'Added ' . $product['name'] . ' to cart',
        'item_count' => getCartItemCount()
    ];
}

/**
 * Update exact quantity
 */
function updateCartQuantity(string $cartKey, float $quantity): bool {
    initCart();

    if ($quantity <= 0) {
        removeFromCart($cartKey);
        return true;
    }

    if (!isset($_SESSION['cart'][$cartKey])) {
        return false;
    }

    $item = $_SESSION['cart'][$cartKey];
    $productId = (int)$item['product_id'];
    $unitId = !empty($item['unit_id']) ? (int)$item['unit_id'] : null;

    // Verify stock limits
    if ($unitId) {
        $unit = Database::fetchOne("SELECT stock_quantity FROM product_units WHERE id = ?", [$unitId]);
        $stock = $unit ? (float)$unit['stock_quantity'] : 0;
    } else {
        $product = Database::fetchOne("SELECT stock_quantity FROM products WHERE id = ?", [$productId]);
        $stock = $product ? (float)$product['stock_quantity'] : 0;
    }

    if ($quantity > $stock) {
        return false;
    }

    $_SESSION['cart'][$cartKey]['quantity'] = $quantity;

    if (isCustomerLoggedIn()) {
        $customerId = (int)$_SESSION['customer_id'];
        Database::execute(
            "UPDATE customer_carts SET quantity = ? WHERE customer_id = ? AND product_id = ? AND (unit_id = ? OR (unit_id IS NULL AND ? IS NULL))",
            [$quantity, $customerId, $productId, $unitId, $unitId]
        );
    }

    return true;
}

/**
 * Remove an item from cart
 */
function removeFromCart(string $cartKey): bool {
    initCart();
    if (!isset($_SESSION['cart'][$cartKey])) {
        return false;
    }

    $item = $_SESSION['cart'][$cartKey];
    unset($_SESSION['cart'][$cartKey]);

    if (isCustomerLoggedIn()) {
        $customerId = (int)$_SESSION['customer_id'];
        $productId = (int)$item['product_id'];
        $unitId = !empty($item['unit_id']) ? (int)$item['unit_id'] : null;
        Database::execute(
            "DELETE FROM customer_carts WHERE customer_id = ? AND product_id = ? AND (unit_id = ? OR (unit_id IS NULL AND ? IS NULL))",
            [$customerId, $productId, $unitId, $unitId]
        );
    }

    return true;
}

function clearCart(): void {
    $_SESSION['cart'] = [];
    unset($_SESSION['applied_coupon']);
    if (isCustomerLoggedIn()) {
        $customerId = (int)$_SESSION['customer_id'];
        Database::execute("DELETE FROM customer_carts WHERE customer_id = ?", [$customerId]);
    }
}

/**
 * Sync customer's saved DB cart with current session
 */
function syncSessionCartToDb(int $customerId): void {
    initCart();
    // 1. Fetch from DB
    $dbItems = Database::fetchAll("SELECT product_id, unit_id, quantity FROM customer_carts WHERE customer_id = ?", [$customerId]);
    foreach ($dbItems as $row) {
        $key = $row['product_id'] . '_' . ($row['unit_id'] ?? 0);
        if (!isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key] = [
                'product_id' => (int)$row['product_id'],
                'unit_id' => $row['unit_id'] ? (int)$row['unit_id'] : null,
                'quantity' => (float)$row['quantity']
            ];
        }
    }
    // 2. Push session items back to DB
    foreach ($_SESSION['cart'] as $item) {
        Database::execute(
            "INSERT INTO customer_carts (customer_id, product_id, unit_id, quantity) 
             VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE quantity = ?",
            [$customerId, $item['product_id'], $item['unit_id'], $item['quantity'], $item['quantity']]
        );
    }
}

/**
 * Compute full server-side recalculated cart summary:
 * Items with live prices from MySQL, subtotal, delivery charges, coupons, total
 */
function getCartTotals(?string $couponCode = null, ?string $pincode = null): array {
    initCart();
    $rawCart = $_SESSION['cart'];

    $items = [];
    $subtotal = 0.0;

    foreach ($rawCart as $cartKey => $entry) {
        $productId = (int)$entry['product_id'];
        $unitId = !empty($entry['unit_id']) ? (int)$entry['unit_id'] : null;
        $qty = (float)$entry['quantity'];

        $product = Database::fetchOne(
            "SELECT p.id, p.name, p.slug, p.main_image, p.base_price, p.default_unit, p.stock_quantity, p.status, c.name as category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.id = ?",
            [$productId]
        );

        if (!$product) {
            continue;
        }

        $unitName = $product['default_unit'];
        $unitPrice = (float)$product['base_price'];
        $unitStock = (float)$product['stock_quantity'];

        if ($unitId) {
            $unit = Database::fetchOne("SELECT id, unit_name, price, stock_quantity FROM product_units WHERE id = ? AND product_id = ?", [$unitId, $productId]);
            if ($unit) {
                $unitName = $unit['unit_name'];
                $unitPrice = (float)$unit['price'];
                $unitStock = (float)$unit['stock_quantity'];
            }
        }

        $lineTotal = $unitPrice * $qty;
        $subtotal += $lineTotal;

        $items[] = [
            'cart_key' => $cartKey,
            'product_id' => $product['id'],
            'unit_id' => $unitId,
            'name' => $product['name'],
            'slug' => $product['slug'],
            'image' => $product['main_image'],
            'unit_name' => $unitName,
            'price' => $unitPrice,
            'quantity' => $qty,
            'line_total' => $lineTotal,
            'in_stock' => ($qty <= $unitStock),
            'stock_available' => $unitStock
        ];
    }

    // Delivery calculation
    $defaultDeliveryFee = (float)getSetting('delivery_fee_default', '40.00');
    $freeDeliveryThreshold = (float)getSetting('free_delivery_threshold', '499.00');
    $deliveryFee = $defaultDeliveryFee;

    if ($pincode) {
        $zone = Database::fetchOne("SELECT delivery_fee, min_order_amount FROM delivery_zones WHERE pincode = ? AND is_active = 1", [$pincode]);
        if ($zone) {
            $deliveryFee = (float)$zone['delivery_fee'];
        }
    }

    // Free delivery rule if subtotal >= free threshold
    if ($subtotal >= $freeDeliveryThreshold && $subtotal > 0) {
        $deliveryFee = 0.00;
    }
    if ($subtotal <= 0) {
        $deliveryFee = 0.00;
    }

    // Coupon calculation
    $discountAmount = 0.0;
    $appliedCoupon = null;
    $couponError = null;
    $codeToCheck = $couponCode ?? ($_SESSION['applied_coupon'] ?? null);

    if ($codeToCheck && $subtotal > 0) {
        $coupon = Database::fetchOne(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1 
             AND (start_date IS NULL OR start_date <= CURDATE()) 
             AND (end_date IS NULL OR end_date >= CURDATE())",
            [strtoupper(trim($codeToCheck))]
        );

        if ($coupon) {
            if ($subtotal < (float)$coupon['min_order_amount']) {
                $couponError = 'Min order of ' . formatPrice($coupon['min_order_amount']) . ' required for coupon ' . $coupon['code'];
            } else {
                if ($coupon['type'] === 'percentage') {
                    $discountAmount = ($subtotal * (float)$coupon['discount_value']) / 100.0;
                    if (!empty($coupon['max_discount_amount']) && $discountAmount > (float)$coupon['max_discount_amount']) {
                        $discountAmount = (float)$coupon['max_discount_amount'];
                    }
                } else {
                    $discountAmount = min((float)$coupon['discount_value'], $subtotal);
                }
                $appliedCoupon = $coupon;
                $_SESSION['applied_coupon'] = $coupon['code'];
            }
        } else {
            $couponError = 'Coupon code is invalid or has expired';
            unset($_SESSION['applied_coupon']);
        }
    }

    $finalTotal = max(0.0, ($subtotal + $deliveryFee - $discountAmount));

    return [
        'items' => $items,
        'item_count' => count($items),
        'total_quantity' => array_sum(array_column($items, 'quantity')),
        'subtotal' => $subtotal,
        'delivery_fee' => $deliveryFee,
        'discount_amount' => $discountAmount,
        'applied_coupon' => $appliedCoupon,
        'coupon_error' => $couponError,
        'total' => $finalTotal
    ];
}

/**
 * Status color classes for badges
 */
function getStatusBadgeClass(string $status): string {
    return match (strtolower($status)) {
        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'confirmed' => 'bg-blue-100 text-blue-800 border-blue-200',
        'processing' => 'bg-amber-100 text-amber-800 border-amber-200',
        'packed' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'out_for_delivery' => 'bg-purple-100 text-purple-800 border-purple-200',
        'delivered', 'paid', 'active', 'completed' => 'bg-green-100 text-green-800 border-green-200',
        'cancelled', 'failed', 'refunded', 'inactive' => 'bg-red-100 text-red-800 border-red-200',
        default => 'bg-gray-100 text-gray-800 border-gray-200'
    };
}
