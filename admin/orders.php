<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Admin Order Management & CSV Export
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Order Management – Na Fresh Admin';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    requireAdminAuth();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="na_fresh_orders_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order Number', 'Date', 'Customer Name', 'Phone', 'Address', 'Pincode', 'Subtotal', 'Delivery Fee', 'Total', 'Payment Method', 'Payment Status', 'Order Status']);

    $exportRows = Database::fetchAll("SELECT * FROM orders ORDER BY created_at DESC");
    foreach ($exportRows as $r) {
        // Prevent formula injection in spreadsheet software
        $safeOrderNo = "'" . $r['order_number'];
        $safePhone = "'" . $r['customer_phone'];
        fputcsv($output, [
            $safeOrderNo,
            $r['created_at'],
            $r['customer_name'],
            $safePhone,
            $r['delivery_address'],
            $r['delivery_pincode'],
            $r['subtotal_amount'],
            $r['delivery_fee'],
            $r['total_amount'],
            $r['payment_method'],
            $r['payment_status'],
            $r['order_status']
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../includes/admin-header.php';

$filterStatus = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($filterStatus !== 'all') {
    $sql .= " AND o.order_status = ?";
    $params[] = $filterStatus;
}

$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
$orders = Database::fetchAll($sql, $params);
?>

<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-gray-900 tracking-tight">Order Fulfillment Pipeline</h1>
      <p class="text-xs text-gray-500">Track and dispatch customer fruit orders across Indore.</p>
    </div>
    <div class="flex items-center space-x-2">
      <a href="/admin/orders.php?export=csv" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold text-xs px-3.5 py-2 rounded-xl transition shadow-sm flex items-center space-x-1.5">
        <i class="fas fa-file-csv text-green-700"></i>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Filter & Search Bar -->
  <div class="bg-white rounded-3xl p-4 border border-gray-100 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
    <!-- Status Filter Buttons -->
    <div class="flex space-x-1.5 overflow-x-auto no-scrollbar w-full sm:w-auto">
      <?php 
      $statuses = ['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
      foreach ($statuses as $k => $label): 
      ?>
        <a href="/admin/orders.php?status=<?= $k ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
           class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 <?= $filterStatus === $k ? 'bg-green-700 text-white shadow-sm' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Search Input -->
    <form action="/admin/orders.php" method="GET" class="relative w-full sm:w-64">
      <?php if ($filterStatus !== 'all'): ?>
        <input type="hidden" name="status" value="<?= e($filterStatus) ?>">
      <?php endif; ?>
      <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search Order # or phone..." class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-1.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
    </form>
  </div>

  <!-- Orders Table -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
          <tr>
            <th class="py-3 px-3">Order Number</th>
            <th class="py-3 px-3">Date</th>
            <th class="py-3 px-3">Customer</th>
            <th class="py-3 px-3">Items</th>
            <th class="py-3 px-3">Total</th>
            <th class="py-3 px-3">Payment</th>
            <th class="py-3 px-3">Order Status</th>
            <th class="py-3 px-3 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="8" class="text-center py-8 text-gray-400">No orders match the selected criteria.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $o): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="py-3 px-3 font-black text-green-900">#<?= e($o['order_number']) ?></td>
                <td class="py-3 px-3 text-gray-400 text-[11px]"><?= date('d M, h:i A', strtotime($o['created_at'])) ?></td>
                <td class="py-3 px-3">
                  <div class="font-bold text-gray-800"><?= e($o['customer_name']) ?></div>
                  <div class="text-[10px] text-gray-400"><?= e($o['customer_phone']) ?></div>
                </td>
                <td class="py-3 px-3 font-semibold"><?= (int)$o['item_count'] ?> items</td>
                <td class="py-3 px-3 font-black text-gray-900"><?= formatPrice($o['total_amount']) ?></td>
                <td class="py-3 px-3">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $o['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                    <?= e($o['payment_method']) ?> • <?= e($o['payment_status']) ?>
                  </span>
                </td>
                <td class="py-3 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase <?= getStatusBadgeClass($o['order_status']) ?>">
                    <?= e($o['order_status']) ?>
                  </span>
                </td>
                <td class="py-3 px-3 text-right">
                  <a href="/admin/order-view.php?id=<?= $o['id'] ?>" class="bg-green-50 hover:bg-green-100 text-green-800 font-extrabold px-3 py-1.5 rounded-xl transition">
                    Details & Invoice
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
