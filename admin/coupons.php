<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Coupons & Promotional Offers
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Coupons & Discounts – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Handle Add Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_coupon'])) {
    requireCsrf();

    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['type'] ?? 'percentage';
    $val = (float)($_POST['discount_value'] ?? 10);
    $minOrder = (float)($_POST['min_order_amount'] ?? 0);
    $maxDisc = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;

    if (!empty($code)) {
        Database::insert(
            "INSERT INTO coupons (code, type, discount_value, min_order_amount, max_discount_amount, is_active) 
             VALUES (?, ?, ?, ?, ?, 1) 
             ON DUPLICATE KEY UPDATE type=VALUES(type), discount_value=VALUES(discount_value), min_order_amount=VALUES(min_order_amount), max_discount_amount=VALUES(max_discount_amount)",
            [$code, $type, $val, $minOrder, $maxDisc]
        );
        logAuditAction((int)$_SESSION['admin_id'], 'coupon_save', "Saved coupon code: {$code}");
        setFlash('success', "Coupon '{$code}' saved successfully.");
    }
    header('Location: /admin/coupons.php');
    exit;
}

$coupons = Database::fetchAll("SELECT * FROM coupons ORDER BY id DESC");
?>

<div class="space-y-6">

  <div>
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Coupons & Special Offers</h1>
    <p class="text-xs text-gray-500">Create percentage discounts, flat cashbacks, and minimum order vouchers.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Coupon Card (1 col) -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 text-xs">
      <h2 class="text-sm font-black text-gray-900">Create New Coupon</h2>

      <form action="/admin/coupons.php" method="POST" class="space-y-3">
        <?= csrfField() ?>
        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Coupon Code *</label>
          <input type="text" name="code" required placeholder="e.g. SUMMER20 or FRESH50" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 uppercase font-black">
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Discount Type</label>
          <select name="type" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
            <option value="percentage">Percentage Off (%)</option>
            <option value="fixed">Flat Amount (₹)</option>
          </select>
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Discount Value *</label>
          <input type="number" step="0.5" name="discount_value" required placeholder="e.g. 10 or 50" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Min Order Amount (₹)</label>
          <input type="number" step="1" name="min_order_amount" value="150" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Max Discount Cap (₹, optional)</label>
          <input type="number" step="1" name="max_discount_amount" placeholder="e.g. 100" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
        </div>

        <button type="submit" name="save_coupon" value="1" class="w-full bg-green-700 hover:bg-green-800 text-white font-black p-2.5 rounded-xl transition shadow-sm">
          Save Coupon
        </button>
      </form>
    </div>

    <!-- Coupons Table (2 cols) -->
    <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
      <h2 class="text-sm font-black text-gray-900">Active Promo Codes</h2>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-3 px-3">Code</th>
              <th class="py-3 px-3">Discount</th>
              <th class="py-3 px-3">Min Order</th>
              <th class="py-3 px-3">Redeemed</th>
              <th class="py-3 px-3 text-right">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <?php foreach ($coupons as $cp): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="py-3 px-3 font-black text-green-900 tracking-wider"><?= e($cp['code']) ?></td>
                <td class="py-3 px-3 font-bold text-gray-800">
                  <?= $cp['type'] === 'percentage' ? (float)$cp['discount_value'] . '%' : formatPrice($cp['discount_value']) ?>
                </td>
                <td class="py-3 px-3"><?= formatPrice($cp['min_order_amount']) ?></td>
                <td class="py-3 px-3 font-semibold text-gray-600"><?= (int)$cp['used_count'] ?> times</td>
                <td class="py-3 px-3 text-right">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase <?= $cp['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' ?>">
                    <?= $cp['is_active'] ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
