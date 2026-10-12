<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Inventory & Stock Adjustments
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Inventory & Stock Movements – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Handle Stock Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    requireCsrf();

    $productId = (int)($_POST['product_id'] ?? 0);
    $adjType = $_POST['type'] ?? 'in'; // in, out, wastage, adjustment
    $qty = abs((float)($_POST['quantity'] ?? 0));
    $notes = trim($_POST['notes'] ?? '');

    if ($productId > 0 && $qty > 0) {
        $db = Database::getConnection();
        if ($db) {
            $db->beginTransaction();
            try {
                $p = Database::fetchOne("SELECT id, name, stock_quantity FROM products WHERE id = ? FOR UPDATE", [$productId]);
                if ($p) {
                    $prevStock = (float)$p['stock_quantity'];
                    $newStock = ($adjType === 'in') ? ($prevStock + $qty) : ($prevStock - $qty);

                    if ($newStock < 0) {
                        throw new RuntimeException("Cannot reduce stock below zero (current: {$prevStock}).");
                    }

                    Database::execute("UPDATE products SET stock_quantity = ? WHERE id = ?", [$newStock, $productId]);
                    Database::insert(
                        "INSERT INTO inventory_logs (product_id, type, quantity, previous_stock, current_stock, notes, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$productId, $adjType, $qty, $prevStock, $newStock, $notes, $_SESSION['admin_id']]
                    );

                    $db->commit();
                    logAuditAction((int)$_SESSION['admin_id'], 'stock_adjustment', "Adjusted stock for {$p['name']}: {$adjType} {$qty}");
                    setFlash('success', "Stock updated for '{$p['name']}'. New stock: {$newStock}");
                }
            } catch (Exception $e) {
                $db->rollBack();
                setFlash('error', $e->getMessage());
            }
        }
    }
    header('Location: /admin/inventory.php');
    exit;
}

$products = Database::fetchAll("SELECT id, name, sku, stock_quantity, low_stock_threshold, default_unit FROM products WHERE status = 'active' ORDER BY name ASC");
$lowStockItems = Database::fetchAll("SELECT * FROM products WHERE stock_quantity <= low_stock_threshold AND status = 'active'");
$logs = Database::fetchAll(
    "SELECT l.*, p.name as product_name, p.default_unit 
     FROM inventory_logs l 
     JOIN products p ON l.product_id = p.id 
     ORDER BY l.created_at DESC LIMIT 30"
);
?>

<div class="space-y-6">

  <div>
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Inventory & Stock Logs</h1>
    <p class="text-xs text-gray-500">Record stock replenishment, damage wastage, audit counts, and track live levels.</p>
  </div>

  <!-- Low Stock Warning Banner if any -->
  <?php if (!empty($lowStockItems)): ?>
    <div class="bg-red-50 border border-red-200 rounded-3xl p-4 space-y-2">
      <div class="flex items-center space-x-2 text-red-800 text-xs font-black">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Low Stock Alert (<?= count($lowStockItems) ?> items below minimum threshold)</span>
      </div>
      <div class="flex flex-wrap gap-2 pt-1">
        <?php foreach ($lowStockItems as $lsi): ?>
          <span class="bg-white border border-red-200 text-red-700 px-3 py-1 rounded-xl text-xs font-bold shadow-xs">
            <?= e($lsi['name']) ?>: only <?= (int)$lsi['stock_quantity'] ?> <?= e($lsi['default_unit']) ?> left
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Adjust Stock Form Card -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-3">
    <h2 class="text-sm font-black text-gray-900">Record Stock Adjustment</h2>
    <form action="/admin/inventory.php" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
      <?= csrfField() ?>
      <div>
        <label class="block font-bold text-gray-700 uppercase mb-1">Product</label>
        <select name="product_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>">
              <?= e($p['name']) ?> (Current: <?= (int)$p['stock_quantity'] ?> <?= e($p['default_unit']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block font-bold text-gray-700 uppercase mb-1">Action Type</label>
        <select name="type" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          <option value="in">Stock In (+ Restock from Farm)</option>
          <option value="wastage">Damage / Spoilage Wastage (-)</option>
          <option value="adjustment">Audit Count Correction</option>
        </select>
      </div>

      <div>
        <label class="block font-bold text-gray-700 uppercase mb-1">Quantity</label>
        <input type="number" step="0.5" min="0.5" name="quantity" required placeholder="Quantity" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
      </div>

      <div>
        <label class="block font-bold text-gray-700 uppercase mb-1">Reason / Notes</label>
        <input type="text" name="notes" placeholder="e.g. Fresh crate arrived" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
      </div>

      <div class="sm:col-span-4 text-right pt-1">
        <button type="submit" name="adjust_stock" value="1" class="bg-green-700 hover:bg-green-800 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-sm transition">
          Update Stock Quantity
        </button>
      </div>
    </form>
  </div>

  <!-- Recent Inventory Movement Logs -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
    <h2 class="text-sm font-black text-gray-900">Inventory Movement Audit Trail</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
          <tr>
            <th class="py-3 px-3">Date & Time</th>
            <th class="py-3 px-3">Product</th>
            <th class="py-3 px-3">Action</th>
            <th class="py-3 px-3">Qty</th>
            <th class="py-3 px-3">Prev → New</th>
            <th class="py-3 px-3">Notes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <?php foreach ($logs as $l): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-3 px-3 text-gray-400 text-[11px]"><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></td>
              <td class="py-3 px-3 font-bold text-gray-900"><?= e($l['product_name']) ?></td>
              <td class="py-3 px-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $l['type'] === 'in' ? 'bg-green-100 text-green-800' : ($l['type'] === 'wastage' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') ?>">
                  <?= e($l['type']) ?>
                </span>
              </td>
              <td class="py-3 px-3 font-black"><?= (float)$l['quantity'] ?></td>
              <td class="py-3 px-3 text-gray-500"><?= (float)$l['previous_stock'] ?> → <span class="font-bold text-gray-800"><?= (float)$l['current_stock'] ?></span></td>
              <td class="py-3 px-3 text-gray-500"><?= e($l['notes'] ?: $l['reference_id'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
