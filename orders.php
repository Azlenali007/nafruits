<?php
/**
 * NA FRESH – Fruits & Coconuts
 * My Orders & Order Tracking (Matches Screenshot 9)
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$filterStatus = trim($_GET['status'] ?? 'all'); // all, processing, shipped, delivered
$trackingOrderNumber = trim($_GET['order'] ?? '');

$orders = [];
$trackedOrder = null;
$trackedTimeline = [];

try {
    $currentCustomer = getCurrentCustomer();
    $customerId = $currentCustomer ? (int)$currentCustomer['id'] : 1; // Fallback to demo customer 1 if guest exploring

    $sql = "SELECT o.*, 
                   COUNT(oi.id) as item_count, 
                   MIN(oi.product_image) as preview_image 
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE (o.customer_id = ? OR o.order_number = ?)";
    $params = [$customerId, $trackingOrderNumber];

    if ($filterStatus !== 'all') {
        if ($filterStatus === 'shipped') {
            $sql .= " AND o.order_status IN ('out_for_delivery', 'packed')";
        } else {
            $sql .= " AND o.order_status = ?";
            $params[] = $filterStatus;
        }
    }

    $sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
    $orders = Database::fetchAll($sql, $params);

    // If tracking specific order
    if (!empty($trackingOrderNumber)) {
        $trackedOrder = Database::fetchOne("SELECT * FROM orders WHERE order_number = ?", [$trackingOrderNumber]);
        if ($trackedOrder) {
            $trackedTimeline = Database::fetchAll("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC", [$trackedOrder['id']]);
        }
    }
} catch (Exception $e) {
    error_log('Orders fetch error: ' . $e->getMessage());
}

$pageTitle = 'My Orders – Na Fresh';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-3 pb-8 space-y-4">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <a href="/account.php" class="w-9 h-9 rounded-full bg-white border border-gray-150 flex items-center justify-center text-gray-700 hover:bg-gray-100 transition shadow-xs">
      <?= renderIcon('arrow-left', 'w-4 h-4 text-gray-700') ?>
    </a>
    <h1 class="text-base font-black text-gray-900 tracking-tight">My Orders</h1>
    <div class="w-9"></div>
  </div>

  <!-- Filter Pills (Matches Screenshot 9) -->
  <div class="flex space-x-2 overflow-x-auto no-scrollbar py-1">
    <a href="/orders.php?status=all" class="px-4 py-1.5 rounded-full text-xs font-black transition <?= $filterStatus === 'all' ? 'bg-emerald-800 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-200' ?>">
      All
    </a>
    <a href="/orders.php?status=processing" class="px-4 py-1.5 rounded-full text-xs font-black transition <?= $filterStatus === 'processing' ? 'bg-emerald-800 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-200' ?>">
      Processing
    </a>
    <a href="/orders.php?status=shipped" class="px-4 py-1.5 rounded-full text-xs font-black transition <?= $filterStatus === 'shipped' ? 'bg-emerald-800 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-200' ?>">
      Shipped
    </a>
    <a href="/orders.php?status=delivered" class="px-4 py-1.5 rounded-full text-xs font-black transition <?= $filterStatus === 'delivered' ? 'bg-emerald-800 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-200' ?>">
      Delivered
    </a>
  </div>

  <!-- If Tracking Modal / Banner Active -->
  <?php if ($trackedOrder): ?>
    <div class="bg-white rounded-3xl p-5 border border-emerald-200 shadow-xs space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <span class="text-[10px] text-gray-400 font-bold uppercase block">Live Tracking</span>
          <h2 class="text-base font-black text-emerald-950">#<?= e($trackedOrder['order_number']) ?></h2>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-black uppercase <?= getStatusBadgeClass($trackedOrder['order_status']) ?>">
          <?= e($trackedOrder['order_status']) ?>
        </span>
      </div>

      <!-- Timeline visual with clean stroke icons -->
      <div class="space-y-3 pt-2">
        <?php 
        $steps = [
            'pending' => ['label' => 'Order Placed', 'icon' => 'check'],
            'confirmed' => ['label' => 'Confirmed', 'icon' => 'check-circle'],
            'processing' => ['label' => 'Packing Fresh', 'icon' => 'package'],
            'out_for_delivery' => ['label' => 'Out for Delivery', 'icon' => 'truck'],
            'delivered' => ['label' => 'Delivered', 'icon' => 'home']
        ];
        foreach ($steps as $stKey => $stData): 
            $currentStatus = $trackedOrder['order_status'];
            $statusWeights = ['pending' => 1, 'confirmed' => 2, 'processing' => 3, 'packed' => 3, 'out_for_delivery' => 4, 'delivered' => 5];
            $currentWeight = $statusWeights[$currentStatus] ?? 1;
            $stepWeight = $statusWeights[$stKey] ?? 1;
            $isDone = $currentWeight >= $stepWeight;
        ?>
          <div class="flex items-start space-x-3 text-xs">
            <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold flex-shrink-0 <?= $isDone ? 'bg-emerald-800 text-white' : 'bg-gray-100 text-gray-400' ?>">
              <?= renderIcon($stData['icon'], 'w-3.5 h-3.5') ?>
            </div>
            <div class="pt-1">
              <span class="font-black <?= $isDone ? 'text-gray-900' : 'text-gray-400' ?>"><?= $stData['label'] ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <div class="text-right pt-2 border-t border-gray-100">
        <a href="/orders.php" class="text-xs text-gray-500 hover:text-emerald-700 font-bold">Close Tracking</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Orders List (Matches Screenshot 9) -->
  <?php if (empty($orders)): ?>
    <div class="bg-white rounded-3xl p-8 border border-gray-150 shadow-xs text-center my-6 space-y-3">
      <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto text-gray-400 text-2xl">
        <?= renderIcon('package', 'w-8 h-8 text-gray-400') ?>
      </div>
      <h3 class="text-base font-bold text-gray-800">No orders found</h3>
      <p class="text-xs text-gray-500">You don't have any <?= $filterStatus !== 'all' ? $filterStatus : '' ?> orders yet.</p>
    </div>
  <?php else: ?>
    <div class="space-y-2.5">
      <?php foreach ($orders as $ord): 
        $createdDate = date('d M Y', strtotime($ord['created_at']));
      ?>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-150 shadow-xs flex items-center justify-between space-x-3 hover:border-emerald-200 transition">
          
          <!-- Thumbnail -->
          <div class="w-14 h-14 rounded-xl overflow-hidden bg-emerald-50/20 flex-shrink-0">
            <img src="<?= e($ord['preview_image'] ?: 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80') ?>" 
                 alt="Order Item" 
                 class="w-full h-full object-cover rounded-xl">
          </div>

          <!-- Order Summary Info (Matches Screenshot 9) -->
          <div class="flex-1 min-w-0 space-y-0.5">
            <div class="font-black text-xs sm:text-sm text-gray-900 truncate">
              #<?= e($ord['order_number']) ?>
            </div>
            <div class="text-xs text-gray-600 font-bold">
              <span class="text-emerald-950 font-black"><?= formatPrice($ord['total_amount']) ?></span> • <span class="font-normal text-gray-500"><?= (int)$ord['item_count'] ?> item<?= (int)$ord['item_count'] === 1 ? '' : 's' ?></span>
            </div>
            <div class="text-[11px] text-gray-400">
              Placed on <?= $createdDate ?>
            </div>
          </div>

          <!-- Status Badge (e.g. Processing [amber] or Delivered [green]) -->
          <div class="flex flex-col items-end space-y-1.5 flex-shrink-0">
            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider <?= getStatusBadgeClass($ord['order_status']) ?>">
              <?= e($ord['order_status']) ?>
            </span>
            <a href="/orders.php?order=<?= urlencode($ord['order_number']) ?>" class="text-[11px] font-bold text-emerald-700 hover:underline">
              Track
            </a>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
