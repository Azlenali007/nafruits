<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Business Reports & Analytics
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Reports & Financial Analytics – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

$monthlySales = Database::fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as sales_month, 
            COUNT(id) as total_orders, 
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COALESCE(SUM(delivery_fee), 0) as total_delivery_collected
     FROM orders 
     WHERE order_status != 'cancelled' 
     GROUP BY sales_month 
     ORDER BY sales_month DESC LIMIT 12"
);

$categoryPerformance = Database::fetchAll(
    "SELECT c.name as category_name, 
            COUNT(oi.id) as units_sold, 
            COALESCE(SUM(oi.total_price), 0) as revenue 
     FROM categories c 
     JOIN products p ON c.id = p.category_id 
     JOIN order_items oi ON p.id = oi.product_id 
     JOIN orders o ON oi.order_id = o.id 
     WHERE o.order_status != 'cancelled' 
     GROUP BY c.id 
     ORDER BY revenue DESC"
);

$paymentBreakdown = Database::fetchAll(
    "SELECT payment_method, COUNT(id) as order_count, SUM(total_amount) as total_amount 
     FROM orders 
     WHERE order_status != 'cancelled' 
     GROUP BY payment_method"
);
?>

<div class="space-y-6">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-black text-gray-900 tracking-tight">Business Reports & Analytics</h1>
      <p class="text-xs text-gray-500">Analyze sales by month, top fruit categories, and customer payment methods.</p>
    </div>
    <a href="/admin/orders.php?export=csv" class="bg-green-700 hover:bg-green-800 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition shadow-sm">
      Download Orders CSV
    </a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Monthly Revenue Table -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
      <h2 class="text-sm font-black text-gray-900">Monthly Sales Summary</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-2.5 px-3">Month</th>
              <th class="py-2.5 px-3">Orders</th>
              <th class="py-2.5 px-3">Delivery Fees</th>
              <th class="py-2.5 px-3 text-right">Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <?php foreach ($monthlySales as $ms): ?>
              <tr>
                <td class="py-2.5 px-3 font-bold text-gray-900"><?= date('F Y', strtotime($ms['sales_month'] . '-01')) ?></td>
                <td class="py-2.5 px-3"><?= (int)$ms['total_orders'] ?></td>
                <td class="py-2.5 px-3 text-gray-500"><?= formatPrice($ms['total_delivery_collected']) ?></td>
                <td class="py-2.5 px-3 font-black text-green-900 text-right"><?= formatPrice($ms['total_revenue']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Category Sales Performance -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
      <h2 class="text-sm font-black text-gray-900">Revenue by Fruit Category</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
            <tr>
              <th class="py-2.5 px-3">Category</th>
              <th class="py-2.5 px-3">Packs Sold</th>
              <th class="py-2.5 px-3 text-right">Total Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <?php foreach ($categoryPerformance as $cp): ?>
              <tr>
                <td class="py-2.5 px-3 font-bold text-gray-900"><?= e($cp['category_name']) ?></td>
                <td class="py-2.5 px-3"><?= (int)$cp['units_sold'] ?></td>
                <td class="py-2.5 px-3 font-black text-green-900 text-right"><?= formatPrice($cp['revenue']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Payment Methods Breakdown -->
  <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
    <h2 class="text-sm font-black text-gray-900">Payment Gateways Breakdown</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
      <?php foreach ($paymentBreakdown as $pb): ?>
        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200">
          <div class="font-extrabold uppercase text-gray-700"><?= e($pb['payment_method']) ?></div>
          <div class="text-base font-black text-green-900 mt-1"><?= formatPrice($pb['total_amount']) ?></div>
          <div class="text-[11px] text-gray-400"><?= (int)$pb['order_count'] ?> orders</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
