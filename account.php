<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Account & Profile
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

requireCustomerAuth();

$customer = getCurrentCustomer();
$addresses = [];
$recentOrders = [];

try {
    $addresses = Database::fetchAll("SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC", [$customer['id']]);
    $recentOrders = Database::fetchAll("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 3", [$customer['id']]);
} catch (Exception $e) {
    error_log('Error loading account data: ' . $e->getMessage());
}

$pageTitle = 'My Profile – Na Fresh';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-4 pb-12 space-y-4">

  <!-- Profile Card -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
    <div class="flex items-center space-x-3.5">
      <div class="w-14 h-14 rounded-full bg-green-100 text-green-800 flex items-center justify-center font-black text-xl border-2 border-green-300">
        <?= strtoupper(substr($customer['name'] ?? 'U', 0, 1)) ?>
      </div>
      <div>
        <h1 class="text-base font-black text-gray-900 leading-tight"><?= e($customer['name']) ?></h1>
        <div class="text-xs text-gray-500 font-medium"><?= e($customer['email']) ?></div>
        <div class="text-xs text-green-700 font-bold mt-0.5"><?= e($customer['phone']) ?></div>
      </div>
    </div>
    <a href="/logout.php" class="text-xs font-bold text-red-500 hover:text-red-700 bg-red-50 px-3 py-1.5 rounded-xl border border-red-100">
      Logout
    </a>
  </div>

  <!-- Quick Action Links -->
  <div class="grid grid-cols-2 gap-3">
    <a href="/orders.php" class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-3 hover:border-green-400 transition">
      <div class="w-10 h-10 rounded-xl bg-green-50 text-green-700 flex items-center justify-center text-lg">
        <i class="fas fa-box-open"></i>
      </div>
      <div>
        <div class="text-xs font-bold text-gray-900">My Orders</div>
        <div class="text-[10px] text-gray-500">Track & Reorder</div>
      </div>
    </a>
    <a href="/cart.php" class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center space-x-3 hover:border-green-400 transition">
      <div class="w-10 h-10 rounded-xl bg-lime-50 text-lime-700 flex items-center justify-center text-lg">
        <i class="fas fa-shopping-basket"></i>
      </div>
      <div>
        <div class="text-xs font-bold text-gray-900">Active Basket</div>
        <div class="text-[10px] text-gray-500"><?= getCartItemCount() ?> items ready</div>
      </div>
    </a>
  </div>

  <!-- Saved Addresses -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="text-sm font-black text-gray-900 flex items-center space-x-2">
        <i class="fas fa-map-marked-alt text-green-700"></i>
        <span>Saved Delivery Addresses</span>
      </h2>
      <a href="/checkout.php" class="text-xs font-bold text-green-700 hover:underline">+ Add New</a>
    </div>

    <div class="space-y-2">
      <?php if (empty($addresses)): ?>
        <p class="text-xs text-gray-400">No saved addresses yet.</p>
      <?php else: ?>
        <?php foreach ($addresses as $addr): ?>
          <div class="p-3 rounded-2xl bg-gray-50 border border-gray-200 text-xs">
            <div class="flex items-center justify-between mb-1">
              <span class="font-bold text-gray-800"><?= e($addr['type']) ?></span>
              <?php if ($addr['is_default']): ?>
                <span class="text-[10px] bg-green-100 text-green-800 font-extrabold px-2 py-0.5 rounded">Default</span>
              <?php endif; ?>
            </div>
            <div class="text-gray-600 leading-snug">
              <?= e($addr['address_line1']) ?>, <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['pincode']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Orders Snippet -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="text-sm font-black text-gray-900">Recent Activity</h2>
      <a href="/orders.php" class="text-xs font-bold text-green-700 hover:underline">View All Orders</a>
    </div>

    <div class="divide-y divide-gray-100">
      <?php if (empty($recentOrders)): ?>
        <p class="text-xs text-gray-400 py-2">No past orders yet.</p>
      <?php else: ?>
        <?php foreach ($recentOrders as $ro): ?>
          <div class="py-2.5 flex items-center justify-between">
            <div>
              <div class="text-xs font-bold text-gray-900">#<?= e($ro['order_number']) ?></div>
              <div class="text-[11px] text-gray-500"><?= date('d M Y, h:i A', strtotime($ro['created_at'])) ?></div>
            </div>
            <div class="text-right">
              <div class="text-xs font-extrabold text-green-800"><?= formatPrice($ro['total_amount']) ?></div>
              <span class="text-[10px] font-black uppercase <?= getStatusBadgeClass($ro['order_status']) ?> px-1.5 py-0.5 rounded">
                <?= e($ro['order_status']) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Help & WhatsApp Support -->
  <div class="bg-green-50 border border-green-200 rounded-3xl p-4 flex items-center justify-between text-xs">
    <div class="space-y-0.5">
      <div class="font-extrabold text-green-900">Need help with an order?</div>
      <div class="text-green-700">Chat directly with the NA Fresh orchard team.</div>
    </div>
    <a href="https://wa.me/<?= preg_replace('/\D/', '', getSetting('whatsapp_number', '919876543210')) ?>" target="_blank" class="bg-green-600 hover:bg-green-700 text-white font-bold px-3 py-2 rounded-xl flex items-center space-x-1 shadow-sm">
      <i class="fab fa-whatsapp"></i>
      <span>WhatsApp</span>
    </a>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
