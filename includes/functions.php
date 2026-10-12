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

/**
 * Professional Lucide-standard SVG Icon Renderer
 */
function renderIcon(string $name, string $classes = 'w-5 h-5'): string {
    $c = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');
    return match ($name) {
        'search' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
        'user' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        'shopping-bag' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>',
        'shopping-cart' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>',
        'home' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
        'grid' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="14" rx="1"></rect><rect width="7" height="7" x="3" y="14" rx="1"></rect></svg>',
        'heart' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>',
        'heart-fill' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path></svg>',
        'map-pin' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
        'plus' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
        'minus' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
        'trash' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>',
        'share' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>',
        'arrow-left' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg>',
        'arrow-right' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>',
        'chevron-right' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg>',
        'clock' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
        'check' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
        'check-circle' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
        'leaf' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path></svg>',
        'shield' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
        'sparkles' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"></path></svg>',
        'star' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
        'package' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>',
        'truck' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="13" x="1" y="4" rx="2"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>',
        'cross' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
        'tag' => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path><path d="M7 7h.01"></path></svg>',
        default => '<svg class="' . $c . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle></svg>'
    };
}
