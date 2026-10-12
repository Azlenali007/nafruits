<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Order Placed Confirmation (Matches Screenshot 8)
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$orderNumber = trim($_GET['order'] ?? '');
$order = null;

if (!empty($orderNumber)) {
    try {
        $order = Database::fetchOne(
            "SELECT * FROM orders WHERE order_number = ?",
            [$orderNumber]
        );
    } catch (Exception $e) {
        error_log('Error loading order success: ' . $e->getMessage());
    }
}

if (!$order) {
    header('Location: /index.php');
    exit;
}

$pageTitle = 'Order Placed! – Na Fresh';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-6 pb-12 flex flex-col items-center text-center space-y-6">

  <!-- Green Check Circle with Confetti (Matches Screenshot 8) -->
  <div class="relative my-4">
    <!-- Confetti emojis decoration -->
    <span class="absolute -top-3 -left-6 text-xl">🎉</span>
    <span class="absolute -top-4 right-0 text-xl">✨</span>
    <span class="absolute bottom-0 -right-6 text-xl">🍃</span>
    <span class="absolute bottom-0 -left-6 text-xl">🥭</span>

    <div class="w-24 h-24 rounded-full bg-green-700 text-white flex items-center justify-center shadow-2xl animate-pop-check">
      <i class="fas fa-check text-4xl"></i>
    </div>
  </div>

  <!-- Heading -->
  <div class="space-y-1">
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Order Placed!</h1>
    <p class="text-xs text-gray-500 max-w-xs mx-auto">Your order has been placed successfully and sent to our fresh packing dispatch team.</p>
  </div>

  <!-- Order Info Card (Matches Screenshot 8) -->
  <div class="w-full bg-white rounded-3xl p-5 border border-gray-100 shadow-sm text-left space-y-3">
    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
      <div>
        <span class="text-[10px] text-gray-400 font-bold uppercase block">Order ID</span>
        <span class="text-sm font-black text-green-900">#<?= e($order['order_number']) ?></span>
      </div>
      <div class="text-right">
        <span class="text-[10px] text-gray-400 font-bold uppercase block">Total Amount</span>
        <span class="text-sm font-black text-gray-900"><?= formatPrice($order['total_amount']) ?></span>
      </div>
    </div>

    <div class="space-y-1 text-xs">
      <div class="flex items-center justify-between text-gray-600">
        <span>Payment Method</span>
        <span class="font-bold uppercase text-gray-800"><?= e($order['payment_method']) ?></span>
      </div>
      <div class="flex items-center justify-between text-gray-600">
        <span>Payment Status</span>
        <span class="font-bold text-green-700 uppercase"><?= e($order['payment_status']) ?></span>
      </div>
      <div class="flex items-center justify-between text-gray-600">
        <span>Delivery Slot</span>
        <span class="font-bold text-gray-800"><?= e($order['delivery_slot']) ?></span>
      </div>
    </div>

    <div class="pt-2 border-t border-gray-100">
      <span class="text-[10px] text-gray-400 font-bold uppercase block mb-0.5">Delivery Address</span>
      <p class="text-xs text-gray-700 leading-snug"><?= e($order['delivery_address']) ?></p>
    </div>
  </div>

  <!-- Action Buttons (Matches Screenshot 8) -->
  <div class="w-full space-y-2.5 pt-2">
    <a href="/orders.php?order=<?= urlencode($order['order_number']) ?>" class="block w-full bg-green-700 hover:bg-green-800 text-white font-extrabold py-3.5 px-6 rounded-2xl shadow-md transition transform active:scale-95 text-sm sm:text-base">
      Track Your Order
    </a>
    <a href="/index.php" class="block w-full bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 font-bold py-3.5 px-6 rounded-2xl transition text-sm">
      Continue Shopping
    </a>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
