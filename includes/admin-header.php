<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Admin Header & Navigation Layout
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

requireAdminAuth('/admin/login.php');

$currentAdmin = getCurrentAdmin();
$adminTitle = $adminTitle ?? 'Admin Dashboard – Na Fresh';
$currentAdminPage = basename($_SERVER['PHP_SELF']);

// Count pending orders for badge
$pendingOrdersCount = 0;
$lowStockCount = 0;
try {
    $pendingOrdersCount = (int)Database::fetchValue("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'");
    $lowStockCount = (int)Database::fetchValue("SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold AND status = 'active'");
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($adminTitle) ?></title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🥥</text></svg>">
  
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
  
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Chart.js for Admin Analytics only -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 text-gray-800 antialiased font-sans min-h-screen flex flex-col md:flex-row">

  <!-- Mobile Top Admin Bar -->
  <div class="md:hidden bg-green-900 text-white px-4 py-3 flex items-center justify-between border-b border-green-800 sticky top-0 z-50">
    <div class="flex items-center space-x-2">
      <span class="text-xl font-black">Na Fresh</span>
      <span class="text-xs bg-green-800 text-lime-300 px-2 py-0.5 rounded font-bold">Admin</span>
    </div>
    <div class="flex items-center space-x-3">
      <a href="/index.php" target="_blank" class="text-xs text-green-200 hover:text-white" title="Open Storefront"><i class="fas fa-external-link-alt"></i></a>
      <button onclick="document.getElementById('admin-sidebar').classList.toggle('hidden')" class="text-white focus:outline-none">
        <i class="fas fa-bars text-lg"></i>
      </button>
    </div>
  </div>

  <!-- Responsive Admin Sidebar -->
  <aside id="admin-sidebar" class="hidden md:flex flex-col w-full md:w-64 bg-green-900 text-white flex-shrink-0 min-h-screen md:sticky md:top-0 md:h-screen overflow-y-auto">
    <!-- Brand -->
    <div class="p-5 border-b border-green-800 flex items-center justify-between">
      <div class="flex items-center space-x-2.5">
        <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-lime-400 text-xl font-bold">
          🥥
        </div>
        <div>
          <div class="font-extrabold text-base leading-tight">Na Fresh</div>
          <div class="text-[10px] text-green-300 font-semibold tracking-wider">MANAGEMENT PORTAL</div>
        </div>
      </div>
      <a href="/index.php" target="_blank" class="text-xs text-green-300 hover:text-white" title="View Live Shop">
        <i class="fas fa-external-link-alt"></i>
      </a>
    </div>

    <!-- Admin User Info -->
    <div class="px-5 py-3 border-b border-green-800/60 bg-green-950/40 flex items-center justify-between text-xs">
      <div>
        <div class="font-bold text-white"><?= e($currentAdmin['name'] ?? 'Admin') ?></div>
        <div class="text-[10px] text-lime-300 font-semibold"><?= e($currentAdmin['role_name'] ?? 'Super Admin') ?></div>
      </div>
      <a href="/admin/logout.php" class="text-red-300 hover:text-red-100 text-xs font-bold" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>

    <!-- Navigation Menu Links -->
    <nav class="flex-1 p-3 space-y-1 text-xs font-bold">
      <a href="/admin/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'index.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-tachometer-alt w-5 text-center text-sm"></i>
        <span>Dashboard</span>
      </a>

      <a href="/admin/orders.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition <?= in_array($currentAdminPage, ['orders.php', 'order-view.php']) ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <div class="flex items-center space-x-3">
          <i class="fas fa-shopping-bag w-5 text-center text-sm"></i>
          <span>Orders</span>
        </div>
        <?php if ($pendingOrdersCount > 0): ?>
          <span class="bg-amber-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-black"><?= $pendingOrdersCount ?></span>
        <?php endif; ?>
      </a>

      <a href="/admin/products.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'products.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-apple-alt w-5 text-center text-sm"></i>
        <span>Products & Units</span>
      </a>

      <a href="/admin/categories.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'categories.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-th-large w-5 text-center text-sm"></i>
        <span>Categories & Homepage</span>
      </a>

      <a href="/admin/inventory.php" class="flex items-center justify-between px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'inventory.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <div class="flex items-center space-x-3">
          <i class="fas fa-boxes w-5 text-center text-sm"></i>
          <span>Inventory & Stock</span>
        </div>
        <?php if ($lowStockCount > 0): ?>
          <span class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-black"><?= $lowStockCount ?></span>
        <?php endif; ?>
      </a>

      <a href="/admin/customers.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'customers.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-users w-5 text-center text-sm"></i>
        <span>Customers</span>
      </a>

      <a href="/admin/payments.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'payments.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-money-check-alt w-5 text-center text-sm"></i>
        <span>Payments & UPI QR</span>
      </a>

      <a href="/admin/delivery.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'delivery.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-motorcycle w-5 text-center text-sm"></i>
        <span>Delivery Zones & Slots</span>
      </a>

      <a href="/admin/coupons.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'coupons.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-ticket-alt w-5 text-center text-sm"></i>
        <span>Coupons & Offers</span>
      </a>

      <a href="/admin/notifications.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'notifications.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-bullhorn w-5 text-center text-sm"></i>
        <span>Announcements & Telegram</span>
      </a>

      <a href="/admin/reports.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'reports.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-chart-line w-5 text-center text-sm"></i>
        <span>Reports & Analytics</span>
      </a>

      <a href="/admin/settings.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition <?= $currentAdminPage === 'settings.php' ? 'bg-green-800 text-lime-300' : 'text-green-100 hover:bg-green-800/60' ?>">
        <i class="fas fa-cogs w-5 text-center text-sm"></i>
        <span>Shop Settings</span>
      </a>
    </nav>

    <!-- Footer link -->
    <div class="p-3 text-[10px] text-green-300/60 text-center border-t border-green-800">
      NA Fresh v1.0 • PHP 8.2 & MySQL
    </div>
  </aside>

  <!-- Main Content Area -->
  <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto max-w-7xl">
    <?php if (hasFlashes()): ?>
      <div class="mb-4 space-y-2">
        <?php foreach (getFlashes() as $f): ?>
          <div class="p-3.5 rounded-xl text-xs font-bold flex items-center justify-between <?= $f['type'] === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-800 border border-green-200' ?>">
            <span><?= e($f['message']) ?></span>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600">&times;</button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
