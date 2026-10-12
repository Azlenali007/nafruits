<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Delivery Zones, Serviceable Pincodes & Time Slots
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Delivery Zones & Slots – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Handle Add / Edit Pincode Zone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_zone'])) {
    requireCsrf();

    $pincode = trim($_POST['pincode'] ?? '');
    $area = trim($_POST['area_name'] ?? '');
    $fee = (float)($_POST['delivery_fee'] ?? 40.0);
    $minOrder = (float)($_POST['min_order_amount'] ?? 100.0);
    $time = trim($_POST['estimated_minutes'] ?? '30 - 45 minutes');

    if (!empty($pincode) && !empty($area)) {
        Database::insert(
            "INSERT INTO delivery_zones (pincode, area_name, delivery_fee, min_order_amount, estimated_minutes, is_active) 
             VALUES (?, ?, ?, ?, ?, 1) 
             ON DUPLICATE KEY UPDATE area_name = VALUES(area_name), delivery_fee = VALUES(delivery_fee), min_order_amount = VALUES(min_order_amount), estimated_minutes = VALUES(estimated_minutes)",
            [$pincode, $area, $fee, $minOrder, $time]
        );
        logAuditAction((int)$_SESSION['admin_id'], 'delivery_zone_save', "Saved delivery pincode: {$pincode}");
        setFlash('success', "Delivery zone '{$pincode}' configured.");
    }
    header('Location: /admin/delivery.php');
    exit;
}

// Handle Global Delivery Fee settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_global_delivery'])) {
    requireCsrf();
    $defaultFee = (float)($_POST['delivery_fee_default'] ?? 40.0);
    $freeThreshold = (float)($_POST['free_delivery_threshold'] ?? 499.0);

    Database::execute("INSERT INTO shop_settings (key_name, key_value) VALUES ('delivery_fee_default', ?) ON DUPLICATE KEY UPDATE key_value = ?", [(string)$defaultFee, (string)$defaultFee]);
    Database::execute("INSERT INTO shop_settings (key_name, key_value) VALUES ('free_delivery_threshold', ?) ON DUPLICATE KEY UPDATE key_value = ?", [(string)$freeThreshold, (string)$freeThreshold]);

    setFlash('success', 'Global delivery fee rules updated.');
    header('Location: /admin/delivery.php');
    exit;
}

$zones = Database::fetchAll("SELECT * FROM delivery_zones ORDER BY pincode ASC");
$slots = Database::fetchAll("SELECT * FROM delivery_slots ORDER BY id ASC");
?>

<div class="space-y-6">

  <div>
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Delivery Zones & Serviceable Areas</h1>
    <p class="text-xs text-gray-500">Configure serviceable pincodes across Indore, delivery charges, and dispatch time slots.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Global Delivery Rules (1 col) -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 text-xs">
      <h2 class="text-sm font-black text-gray-900">Delivery Rules</h2>

      <form action="/admin/delivery.php" method="POST" class="space-y-3">
        <?= csrfField() ?>
        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Default Delivery Fee (₹)</label>
          <input type="number" step="1" name="delivery_fee_default" value="<?= e(getSetting('delivery_fee_default', '40.00')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Free Delivery Above (₹)</label>
          <input type="number" step="1" name="free_delivery_threshold" value="<?= e(getSetting('free_delivery_threshold', '499.00')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          <p class="text-[10px] text-gray-400 mt-1">Orders exceeding this subtotal automatically receive free delivery.</p>
        </div>

        <button type="submit" name="save_global_delivery" value="1" class="w-full bg-green-700 hover:bg-green-800 text-white font-black p-2.5 rounded-xl transition">
          Save Rules
        </button>
      </form>

      <!-- Time Slots list -->
      <div class="pt-3 border-t border-gray-100 space-y-2">
        <div class="font-black text-gray-900 text-xs">Active Dispatch Slots:</div>
        <?php foreach ($slots as $sl): ?>
          <div class="p-2 rounded-xl bg-green-50/60 border border-green-200 text-green-900 font-bold text-xs flex items-center justify-between">
            <span><?= e($sl['title']) ?></span>
            <span class="text-[10px] text-green-700 font-semibold"><?= (int)$sl['max_orders'] ?> max</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Serviceable Pincodes (2 cols) -->
    <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
      <h2 class="text-sm font-black text-gray-900">Serviceable Pincodes (Indore)</h2>

      <!-- Add Pincode form -->
      <form action="/admin/delivery.php" method="POST" class="p-3 bg-gray-50 rounded-2xl border border-gray-200 space-y-2 text-xs">
        <?= csrfField() ?>
        <div class="font-bold text-gray-700">Add / Update Pincode:</div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
          <input type="text" name="pincode" required placeholder="Pincode (e.g. 452001)" class="bg-white border border-gray-300 rounded-xl p-2 text-xs">
          <input type="text" name="area_name" required placeholder="Area Name(s)" class="bg-white border border-gray-300 rounded-xl p-2 text-xs">
          <input type="number" name="delivery_fee" placeholder="Fee (₹40)" value="40" class="bg-white border border-gray-300 rounded-xl p-2 text-xs">
          <input type="text" name="estimated_minutes" placeholder="30-45 mins" value="30 - 45 minutes" class="bg-white border border-gray-300 rounded-xl p-2 text-xs">
        </div>
        <button type="submit" name="save_zone" value="1" class="w-full bg-green-700 hover:bg-green-800 text-white font-bold p-2 rounded-xl">
          Save Serviceable Pincode
        </button>
      </form>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-2.5 px-3">Pincode</th>
              <th class="py-2.5 px-3">Covered Areas</th>
              <th class="py-2.5 px-3">Fee</th>
              <th class="py-2.5 px-3">ETA</th>
              <th class="py-2.5 px-3 text-right">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <?php foreach ($zones as $z): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="py-2.5 px-3 font-extrabold text-green-900"><?= e($z['pincode']) ?></td>
                <td class="py-2.5 px-3 font-semibold text-gray-800"><?= e($z['area_name']) ?></td>
                <td class="py-2.5 px-3 font-bold"><?= formatPrice($z['delivery_fee']) ?></td>
                <td class="py-2.5 px-3 text-gray-500"><?= e($z['estimated_minutes']) ?></td>
                <td class="py-2.5 px-3 text-right">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">Active</span>
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
