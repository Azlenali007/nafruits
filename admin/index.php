<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Admin Dashboard with Live Database Statistics & Chart.js
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Dashboard – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Fetch Live Database Metrics
$metrics = [
    'today_sales' => 0.0,
    'total_sales' => 0.0,
    'total_orders' => 0,
    'pending_orders' => 0,
    'delivered_orders' => 0,
    'cancelled_orders' => 0,
    'total_customers' => 0,
    'total_products' => 0,
    'low_stock_count' => 0,
    'gross_profit' => 0.0,
    'net_profit' => 0.0,
];
$recentOrders = [];
$topProducts = [];
$salesChartLabels = [];
$salesChartData = [];

try {
    // 1. Sales metrics
    $todaySales = Database::fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'");
    $totalSales = Database::fetchValue("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status != 'cancelled'");
    $metrics['today_sales'] = (float)$todaySales;
    $metrics['total_sales'] = (float)$totalSales;

    // 2. Orders metrics
    $metrics['total_orders'] = (int)Database::fetchValue("SELECT COUNT(*) FROM orders");
    $metrics['pending_orders'] = (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'");
    $metrics['delivered_orders'] = (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE order_status = 'delivered'");
    $metrics['cancelled_orders'] = (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE order_status = 'cancelled'");

    // 3. Customer & Product counts
    $metrics['total_customers'] = (int)Database::fetchValue("SELECT COUNT(*) FROM customers");
    $metrics['total_products'] = (int)Database::fetchValue("SELECT COUNT(*) FROM products WHERE status = 'active'");
    $metrics['low_stock_count'] = (int)Database::fetchValue("SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold AND status = 'active'");

    // 4. Real Gross Profit calculation (Revenue from items minus product cost_price)
    $cogs = Database::fetchValue(
        "SELECT COALESCE(SUM(oi.quantity * p.cost_price), 0) 
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.id 
         JOIN orders o ON oi.order_id = o.id 
         WHERE o.order_status != 'cancelled'"
    );
    $metrics['gross_profit'] = max(0.0, $metrics['total_sales'] - (float)$cogs);

    // Net Profit: Gross Profit minus operating expenses
    $totalExpenses = (float)Database::fetchValue("SELECT COALESCE(SUM(amount), 0) FROM expenses");
    $metrics['net_profit'] = $metrics['gross_profit'] - $totalExpenses;

    // 5. Recent 6 Orders
    $recentOrders = Database::fetchAll(
        "SELECT id, order_number, customer_name, total_amount, payment_method, payment_status, order_status, created_at 
         FROM orders ORDER BY created_at DESC LIMIT 6"
    );

    // 6. Top Products
    $topProducts = Database::fetchAll(
        "SELECT p.id, p.name, p.main_image, p.base_price, COALESCE(SUM(oi.quantity), 0) as total_sold 
         FROM products p 
         LEFT JOIN order_items oi ON p.id = oi.product_id 
         WHERE p.status = 'active' 
         GROUP BY p.id 
         ORDER BY total_sold DESC LIMIT 5"
    );

    // 7. Last 7 Days Daily Sales for Chart.js
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayLabel = date('D, d M', strtotime($date));
        $daySum = (float)Database::fetchValue(
            "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = ? AND order_status != 'cancelled'",
            [$date]
        );
        $salesChartLabels[] = $dayLabel;
        $salesChartData[] = $daySum;
    }

} catch (Exception $e) {
    error_log('Admin dashboard query failure: ' . $e->getMessage());
}
?>

<div class="space-y-6">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-gray-900 tracking-tight">Overview Dashboard</h1>
      <p class="text-xs text-gray-500">Live operational & financial summary for Na Fresh.</p>
    </div>
    <div class="flex items-center space-x-2">
      <a href="/admin/orders.php" class="bg-green-700 hover:bg-green-800 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition shadow-sm">
        <i class="fas fa-plus mr-1"></i> View Orders
      </a>
      <a href="/admin/products.php" class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold px-3.5 py-2 rounded-xl transition shadow-sm">
        Add Product
      </a>
    </div>
  </div>

  <!-- Key Metrics 4-Grid Cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    
    <!-- Today's Sales -->
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-2">
      <div class="flex items-center justify-between text-gray-500">
        <span class="text-xs font-bold uppercase tracking-wider">Today's Sales</span>
        <div class="w-8 h-8 rounded-xl bg-green-50 text-green-700 flex items-center justify-center text-sm font-bold">
          <i class="fas fa-rupee-sign"></i>
        </div>
      </div>
      <div class="text-2xl font-black text-gray-900"><?= formatPrice($metrics['today_sales']) ?></div>
      <div class="text-[11px] text-green-700 font-semibold flex items-center">
        <i class="fas fa-calendar-day mr-1"></i> Fresh revenue today
      </div>
    </div>

    <!-- Total Sales -->
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-2">
      <div class="flex items-center justify-between text-gray-500">
        <span class="text-xs font-bold uppercase tracking-wider">Total Sales</span>
        <div class="w-8 h-8 rounded-xl bg-lime-50 text-lime-700 flex items-center justify-center text-sm font-bold">
          <i class="fas fa-coins"></i>
        </div>
      </div>
      <div class="text-2xl font-black text-gray-900"><?= formatPrice($metrics['total_sales']) ?></div>
      <div class="text-[11px] text-gray-500 font-medium">Lifetime revenue</div>
    </div>

    <!-- Orders Overview -->
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-2">
      <div class="flex items-center justify-between text-gray-500">
        <span class="text-xs font-bold uppercase tracking-wider">Orders</span>
        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-sm font-bold">
          <i class="fas fa-shopping-cart"></i>
        </div>
      </div>
      <div class="text-2xl font-black text-gray-900"><?= $metrics['total_orders'] ?></div>
      <div class="text-[11px] text-amber-600 font-bold">
        <?= $metrics['pending_orders'] ?> pending fulfillment
      </div>
    </div>

    <!-- Estimated Gross Profit -->
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-2">
      <div class="flex items-center justify-between text-gray-500">
        <span class="text-xs font-bold uppercase tracking-wider">Gross Profit</span>
        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-sm font-bold">
          <i class="fas fa-chart-pie"></i>
        </div>
      </div>
      <div class="text-2xl font-black text-green-800"><?= formatPrice($metrics['gross_profit']) ?></div>
      <div class="text-[11px] text-gray-500 font-medium">Sales minus fruit costs</div>
    </div>

  </div>

  <!-- Secondary Metrics Quick Row -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="bg-white rounded-2xl p-3 border border-gray-100 flex items-center space-x-3">
      <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-bold"><i class="fas fa-users"></i></div>
      <div>
        <div class="text-[10px] text-gray-400 font-bold uppercase">Customers</div>
        <div class="text-sm font-black text-gray-900"><?= $metrics['total_customers'] ?> registered</div>
      </div>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-gray-100 flex items-center space-x-3">
      <div class="w-9 h-9 rounded-xl bg-green-100 text-green-800 flex items-center justify-center text-xs font-bold"><i class="fas fa-apple-alt"></i></div>
      <div>
        <div class="text-[10px] text-gray-400 font-bold uppercase">Active Catalog</div>
        <div class="text-sm font-black text-gray-900"><?= $metrics['total_products'] ?> items</div>
      </div>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-gray-100 flex items-center space-x-3">
      <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold"><i class="fas fa-check-circle"></i></div>
      <div>
        <div class="text-[10px] text-gray-400 font-bold uppercase">Delivered</div>
        <div class="text-sm font-black text-gray-900"><?= $metrics['delivered_orders'] ?> orders</div>
      </div>
    </div>
    <div class="bg-white rounded-2xl p-3 border border-gray-100 flex items-center space-x-3">
      <div class="w-9 h-9 rounded-xl bg-red-100 text-red-800 flex items-center justify-center text-xs font-bold"><i class="fas fa-exclamation-triangle"></i></div>
      <div>
        <div class="text-[10px] text-gray-400 font-bold uppercase">Low Stock</div>
        <div class="text-sm font-black <?= $metrics['low_stock_count'] > 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $metrics['low_stock_count'] ?> items</div>
      </div>
    </div>
  </div>

  <!-- Chart & Top Products Row -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Sales Graph Chart.js (2 cols) -->
    <div class="lg:col-span-2 bg-white rounded-3xl p-5 sm:p-6 border border-gray-100 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-black text-gray-900">7-Day Sales Trend</h2>
          <p class="text-xs text-gray-400">Daily revenue performance</p>
        </div>
        <span class="text-xs font-bold text-green-700 bg-green-50 px-2.5 py-1 rounded-xl">Live</span>
      </div>
      <div class="relative h-64 sm:h-72">
        <canvas id="salesChart"></canvas>
      </div>
    </div>

    <!-- Top Selling Products (1 col) -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-gray-100 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-black text-gray-900">Best Sellers</h2>
        <a href="/admin/products.php" class="text-xs font-bold text-green-700 hover:underline">All Products</a>
      </div>

      <div class="divide-y divide-gray-100">
        <?php foreach ($topProducts as $tp): ?>
          <div class="py-2.5 flex items-center justify-between space-x-3">
            <div class="flex items-center space-x-2.5 min-w-0">
              <img src="<?= e($tp['main_image']) ?>" class="w-9 h-9 rounded-xl object-cover bg-gray-50 flex-shrink-0">
              <div class="min-w-0">
                <div class="text-xs font-bold text-gray-900 truncate"><?= e($tp['name']) ?></div>
                <div class="text-[11px] text-gray-500"><?= formatPrice($tp['base_price']) ?></div>
              </div>
            </div>
            <div class="text-right flex-shrink-0">
              <span class="text-xs font-black text-green-800"><?= (int)$tp['total_sold'] ?></span>
              <span class="text-[10px] text-gray-400 block font-medium">sold</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- Recent Orders Table -->
  <div class="bg-white rounded-3xl p-5 sm:p-6 border border-gray-100 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-sm font-black text-gray-900">Recent Customer Orders</h2>
        <p class="text-xs text-gray-400">Latest incoming delivery dispatches</p>
      </div>
      <a href="/admin/orders.php" class="text-xs font-bold text-green-700 hover:underline">Manage All Orders →</a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
          <tr>
            <th class="py-3 px-3">Order ID</th>
            <th class="py-3 px-3">Customer</th>
            <th class="py-3 px-3">Amount</th>
            <th class="py-3 px-3">Payment</th>
            <th class="py-3 px-3">Order Status</th>
            <th class="py-3 px-3">Placed At</th>
            <th class="py-3 px-3 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <?php foreach ($recentOrders as $ro): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-3 px-3 font-extrabold text-green-900">#<?= e($ro['order_number']) ?></td>
              <td class="py-3 px-3 font-bold text-gray-800"><?= e($ro['customer_name']) ?></td>
              <td class="py-3 px-3 font-black text-gray-900"><?= formatPrice($ro['total_amount']) ?></td>
              <td class="py-3 px-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $ro['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                  <?= e($ro['payment_method']) ?> • <?= e($ro['payment_status']) ?>
                </span>
              </td>
              <td class="py-3 px-3">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase <?= getStatusBadgeClass($ro['order_status']) ?>">
                  <?= e($ro['order_status']) ?>
                </span>
              </td>
              <td class="py-3 px-3 text-gray-400 text-[11px]"><?= date('d M, h:i A', strtotime($ro['created_at'])) ?></td>
              <td class="py-3 px-3 text-right">
                <a href="/admin/order-view.php?id=<?= $ro['id'] ?>" class="text-xs font-bold text-green-700 hover:text-green-900">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('salesChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($salesChartLabels) ?>,
      datasets: [{
        label: 'Sales Revenue (₹)',
        data: <?= json_encode($salesChartData) ?>,
        borderColor: '#15803d',
        backgroundColor: 'rgba(21, 128, 61, 0.08)',
        borderWidth: 3,
        tension: 0.35,
        fill: true,
        pointBackgroundColor: '#15803d',
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(val) { return '₹ ' + val; }
          }
        }
      }
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
