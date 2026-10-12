<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Account Management & Spending Analytics
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Customers – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Handle Toggle Customer Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    requireAdminAuth();
    $custId = (int)$_GET['id'];
    $cust = Database::fetchOne("SELECT status FROM customers WHERE id = ?", [$custId]);
    if ($cust) {
        $newStat = ($cust['status'] === 'active') ? 'banned' : 'active';
        Database::execute("UPDATE customers SET status = ? WHERE id = ?", [$newStat, $custId]);
        setFlash('info', 'Customer status updated.');
    }
    header('Location: /admin/customers.php');
    exit;
}

$customers = Database::fetchAll(
    "SELECT c.*, 
            COUNT(DISTINCT o.id) as total_orders, 
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            COUNT(DISTINCT ca.id) as address_count
     FROM customers c 
     LEFT JOIN orders o ON c.id = o.customer_id AND o.order_status != 'cancelled'
     LEFT JOIN customer_addresses ca ON c.id = ca.customer_id
     GROUP BY c.id 
     ORDER BY c.created_at DESC"
);
?>

<div class="space-y-6">

  <div>
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Registered Customers</h1>
    <p class="text-xs text-gray-500">View shopper profiles, order frequency, and lifetime fruit purchases.</p>
  </div>

  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
          <tr>
            <th class="py-3 px-3">Customer</th>
            <th class="py-3 px-3">Phone</th>
            <th class="py-3 px-3">Orders</th>
            <th class="py-3 px-3">Lifetime Spent</th>
            <th class="py-3 px-3">Addresses</th>
            <th class="py-3 px-3">Joined Date</th>
            <th class="py-3 px-3">Status</th>
            <th class="py-3 px-3 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <?php foreach ($customers as $c): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-3 px-3">
                <div class="font-extrabold text-gray-900"><?= e($c['name']) ?></div>
                <div class="text-[10px] text-gray-400"><?= e($c['email']) ?></div>
              </td>
              <td class="py-3 px-3 font-semibold text-green-800"><?= e($c['phone']) ?></td>
              <td class="py-3 px-3 font-bold"><?= (int)$c['total_orders'] ?> orders</td>
              <td class="py-3 px-3 font-black text-gray-900"><?= formatPrice($c['total_spent']) ?></td>
              <td class="py-3 px-3 text-gray-500"><?= (int)$c['address_count'] ?> saved</td>
              <td class="py-3 px-3 text-gray-400 text-[11px]"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
              <td class="py-3 px-3">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase <?= $c['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                  <?= e($c['status']) ?>
                </span>
              </td>
              <td class="py-3 px-3 text-right">
                <a href="/admin/customers.php?toggle_status=1&id=<?= $c['id'] ?>" class="text-[11px] font-bold <?= $c['status'] === 'active' ? 'text-red-500 hover:text-red-700' : 'text-green-600 hover:text-green-800' ?>">
                  <?= $c['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
