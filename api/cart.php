<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Cart API Endpoint (JSON)
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
        $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 1.0;

        if ($productId <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid product or quantity']);
            exit;
        }

        $res = addToCart($productId, $unitId, $quantity);
        echo json_encode($res);
        exit;

    case 'update':
        $cartKey = $_POST['cart_key'] ?? '';
        $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 1.0;

        $ok = updateCartQuantity($cartKey, $quantity);
        if ($ok) {
            $totals = getCartTotals();
            echo json_encode([
                'success' => true,
                'item_count' => getCartItemCount(),
                'totals' => $totals
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Could not update quantity or exceeds stock']);
        }
        exit;

    case 'remove':
        $cartKey = $_POST['cart_key'] ?? '';
        $ok = removeFromCart($cartKey);
        $totals = getCartTotals();
        echo json_encode([
            'success' => $ok,
            'item_count' => getCartItemCount(),
            'totals' => $totals
        ]);
        exit;

    case 'clear':
        clearCart();
        echo json_encode(['success' => true, 'item_count' => 0]);
        exit;

    case 'apply_coupon':
        $code = trim($_POST['coupon_code'] ?? '');
        $totals = getCartTotals($code);
        if ($totals['coupon_error']) {
            echo json_encode(['success' => false, 'error' => $totals['coupon_error'], 'totals' => $totals]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Coupon applied!', 'totals' => $totals]);
        }
        exit;

    case 'remove_coupon':
        unset($_SESSION['applied_coupon']);
        $totals = getCartTotals();
        echo json_encode(['success' => true, 'totals' => $totals]);
        exit;

    case 'get':
    default:
        $totals = getCartTotals();
        echo json_encode(['success' => true, 'totals' => $totals]);
        exit;
}
