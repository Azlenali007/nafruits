<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Order Details, Status Workflow & Printable Receipt
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);

if (!$order) {
    setFlash('error', 'Order not found.');
    header('Location: /admin/orders.php');
    exit;
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    requireCsrf();

    $newStatus = $_POST['order_status'] ?? $order['order_status'];
    $newPaymentStatus = $_POST['payment_status'] ?? $order['payment_status'];
    $adminNotes = trim($_POST['status_notes'] ?? '');

    $db = Database::getConnection();
    if ($db) {
        $db->beginTransaction();
        try {
            // Update order record
            Database::execute(
                "UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?",
                [$newStatus, $newPaymentStatus, $orderId]
            );

            // Insert into history
            Database::insert(
                "INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, ?, ?, ?)",
                [$orderId, $newStatus, $adminNotes ?: "Status updated to {$newStatus}", $_SESSION['admin_name'] ?? 'Admin']
            );

            // If cancelled, restore stock!
            if ($newStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
                $orderItems = Database::fetchAll("SELECT product_id, quantity FROM order_items WHERE order_id = ?", [$orderId]);
                foreach ($orderItems as $item) {
                    Database::execute("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", [$item['quantity'], $item['product_id']]);
                    Database::insert(
                        "INSERT INTO inventory_logs (product_id, type, quantity, previous_stock, current_stock, reference_id, notes) 
                         SELECT id, 'order_cancellation', ?, stock_quantity - ?, stock_quantity, ?, 'Order Cancelled Stock Restoration' 
                         FROM products WHERE id = ?",
                        [$item['quantity'], $item['quantity'], $order['order_number'], $item['product_id']]
                    );
                }
            }

            $db->commit();
            logAuditAction((int)$_SESSION['admin_id'], 'order_status_update', "Updated Order #{$order['order_number']} to {$newStatus}");
            setFlash('success', "Order #{$order['order_number']} status updated.");
            header('Location: /admin/order-view.php?id=' . $orderId);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'Update error: ' . $e->getMessage());
        }
    }
}

$orderItems = Database::fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
$statusHistory = Database::fetchAll("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC", [$orderId]);

$adminTitle = 'Order #' . $order['order_number'] . ' – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">

  <!-- Printable Controls -->
  <div class="flex items-center justify-between no-print">
    <div class="flex items-center space-x-3">
      <a href="/admin/orders.php" class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-100">
        <i class="fas fa-arrow-left text-xs"></i>
      </a>
      <h1 class="text-xl font-black text-gray-900">Order #<?= e($order['order_number']) ?></h1>
      <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase <?= getStatusBadgeClass($order['order_status']) ?>">
        <?= e($order['order_status']) ?>
      </span>
    </div>
    <div class="flex items-center space-x-2">
      <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 font-bold text-xs px-3.5 py-2 rounded-xl hover:bg-gray-50 flex items-center space-x-1.5 shadow-sm">
        <i class="fas fa-print text-gray-600"></i>
        <span>Print Receipt</span>
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Order Items & Receipt (2 cols) -->
    <div class="lg:col-span-2 space-y-4">
      
      <!-- Items Card -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 printable-receipt">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div>
            <div class="font-black text-green-900 text-lg">Na Fresh – Fruits & Coconuts</div>
            <div class="text-[11px] text-gray-400">Order Placed: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
          </div>
          <div class="text-right">
            <div class="font-bold text-xs text-gray-700">Slot: <?= e($order['delivery_slot']) ?></div>
            <div class="text-[11px] text-gray-400">Pincode: <?= e($order['delivery_pincode']) ?></div>
          </div>
        </div>

        <div class="divide-y divide-gray-100">
          <?php foreach ($orderItems as $item): ?>
            <div class="py-3 flex items-center justify-between text-xs">
              <div class="flex items-center space-x-3">
                <img src="<?= e($item['product_image']) ?>" class="w-10 h-10 rounded-xl object-cover bg-gray-50 flex-shrink-0">
                <div>
                  <div class="font-extrabold text-gray-900"><?= e($item['product_name']) ?></div>
                  <div class="text-gray-400 text-[11px]"><?= formatPrice($item['price']) ?> / <?= e($item['unit_name']) ?></div>
                </div>
              </div>
              <div class="text-right">
                <div class="font-bold text-gray-600">Qty: <?= (int)$item['quantity'] ?></div>
                <div class="font-black text-gray-900 mt-0.5"><?= formatPrice($item['total_price']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Breakdown -->
        <div class="pt-3 border-t border-gray-100 space-y-2 text-xs">
          <div class="flex justify-between text-gray-600">
            <span>Subtotal</span>
            <span class="font-bold"><?= formatPrice($order['subtotal_amount']) ?></span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>Delivery Fee</span>
            <span class="font-bold"><?= formatPrice($order['delivery_fee']) ?></span>
          </div>
          <?php if ((float)$order['discount_amount'] > 0): ?>
            <div class="flex justify-between text-green-700 font-bold">
              <span>Coupon (<?= e($order['coupon_code'] ?? '') ?>)</span>
              <span>- <?= formatPrice($order['discount_amount']) ?></span>
            </div>
          <?php endif; ?>
          <div class="pt-2 border-t border-gray-100 flex justify-between text-sm font-black text-gray-900">
            <span>Total Payable</span>
            <span class="text-green-900"><?= formatPrice($order['total_amount']) ?></span>
          </div>
        </div>
      </div>

      <!-- Tracking Timeline -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-3">
        <h2 class="text-sm font-black text-gray-900">Status History Audit</h2>
        <div class="space-y-2 text-xs">
          <?php foreach ($statusHistory as $sh): ?>
            <div class="p-2.5 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between">
              <div>
                <span class="font-bold uppercase text-green-800"><?= e($sh['status']) ?></span>
                <span class="text-gray-500 ml-2"><?= e($sh['notes']) ?></span>
              </div>
              <div class="text-[10px] text-gray-400">
                <?= date('d M, h:i A', strtotime($sh['created_at'])) ?> by <?= e($sh['changed_by']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- Status Updater & Customer Details Sidebar (1 col) -->
    <div class="space-y-4">
      
      <!-- Status Updater Box -->
      <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
        <h2 class="text-sm font-black text-gray-900">Update Order State</h2>
        
        <form action="/admin/order-view.php?id=<?= $orderId ?>" method="POST" class="space-y-3 text-xs">
          <?= csrfField() ?>
          
          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Order Status</label>
            <select name="order_status" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600 font-bold">
              <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
              <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Processing / Packing</option>
              <option value="packed" <?= $order['order_status'] === 'packed' ? 'selected' : '' ?>>Packed Ready</option>
              <option value="out_for_delivery" <?= $order['order_status'] === 'out_for_delivery' ? 'selected' : '' ?>>Out for Delivery</option>
              <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
              <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Payment Status</label>
            <select name="payment_status" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600 font-bold">
              <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
              <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
              <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Notes / Update Log</label>
            <input type="text" name="status_notes" placeholder="e.g. Rider Rahul assigned" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          </div>

          <button type="submit" name="update_status" value="1" class="w-full bg-green-700 hover:bg-green-800 text-white font-black text-xs py-2.5 rounded-xl transition shadow-sm">
            Save Status Transition
          </button>
        </form>
      </div>

      <!-- Customer & Address Card -->
      <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-3 text-xs">
        <h2 class="text-sm font-black text-gray-900">Customer Details</h2>
        <div>
          <div class="font-extrabold text-gray-900"><?= e($order['customer_name']) ?></div>
          <div class="text-green-700 font-bold mt-0.5"><?= e($order['customer_phone']) ?></div>
          <div class="text-gray-500 text-[11px]"><?= e($order['customer_email']) ?></div>
        </div>

        <div class="pt-2 border-t border-gray-100">
          <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Delivery Address</span>
          <p class="text-gray-700 leading-snug"><?= e($order['delivery_address']) ?></p>
        </div>
      </div>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
