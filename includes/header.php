<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Frontend Header
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

$cartCount = getCartItemCount();
$currentCustomer = getCurrentCustomer();
$pageTitle = $pageTitle ?? 'Na Fresh – Fruits & Coconuts | Freshness in Every Bite';
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="Na Fresh delivers premium orchard-fresh fruits and natural tender coconut water right to your doorstep in minutes.">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🥥</text></svg>">
  
  <!-- Tailwind CSS via reliable CDN for production/server-rendered deployment -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
  
  <!-- Font Awesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- SweetAlert2 for mobile-friendly modals & toasts -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Custom App Stylesheet -->
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-50 text-gray-800 antialiased selection:bg-green-100 selection:text-green-800 min-h-screen flex flex-col">

  <!-- 1. Branded Intro Splash (Matching First Screen of Reference Image) -->
  <?php if ($currentPath === '/' || $currentPath === '/index.php'): ?>
  <div id="brand-splash" class="fixed inset-0 z-50 flex flex-col items-center justify-between p-8 text-white select-none">
    <div class="w-full flex justify-end pt-4">
      <button onclick="document.getElementById('brand-splash').style.display='none'" class="text-xs bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded-full backdrop-blur-md">Skip</button>
    </div>
    
    <div class="flex flex-col items-center text-center my-auto">
      <div class="relative w-28 h-28 mb-4 flex items-center justify-center bg-white/10 rounded-full border border-green-400/30 backdrop-blur-md shadow-2xl">
        <span class="text-6xl animate-bounce">🥥</span>
        <span class="absolute -bottom-1 -right-1 text-3xl">🥭</span>
      </div>
      <h1 class="text-4xl font-extrabold tracking-tight text-white flex items-center justify-center gap-1">
        <span class="text-green-400">Na</span> Fresh<span class="text-xs text-lime-300 align-top">™</span>
      </h1>
      <p class="text-sm font-medium text-green-200 mt-1 uppercase tracking-wider">Fruits & Coconuts</p>
      <div class="mt-3 px-4 py-1 rounded-full bg-green-900/60 border border-green-500/40 text-xs text-green-300 italic">
        "Freshness in Every Bite"
      </div>
    </div>

    <div class="w-full max-w-xs flex flex-col items-center pb-6">
      <div class="w-full bg-white/20 rounded-full h-1.5 overflow-hidden mb-2">
        <div class="bg-gradient-to-r from-lime-400 to-green-400 h-1.5 rounded-full animate-pulse" style="width: 80%"></div>
      </div>
      <span class="text-xs text-green-200/80 font-medium">Loading freshness...</span>
    </div>
  </div>
  <?php endif; ?>

  <!-- 2. Sticky Compact Mobile Header (Matching Second Screen of Reference Image) -->
  <header class="sticky top-0 z-40 bg-white border-b border-gray-100 shadow-sm transition-all duration-200">
    <div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 py-2.5 flex items-center justify-between">
      
      <!-- Logo & Brand Identity -->
      <a href="/index.php" class="flex items-center space-x-2 group">
        <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center text-green-700 border border-green-200 group-hover:scale-105 transition-transform">
          <i class="fas fa-seedling text-lg text-green-600"></i>
        </div>
        <div>
          <div class="flex items-center">
            <span class="text-xl font-extrabold tracking-tight text-green-800 leading-none">Na Fresh</span>
            <span class="text-xs text-green-600 ml-1 font-bold">🌴</span>
          </div>
          <span class="text-[10px] text-gray-500 font-semibold tracking-wider block -mt-0.5">FRUITS & COCONUTS</span>
        </div>
      </a>

      <!-- Right Action Icons: Search, User, Cart -->
      <div class="flex items-center space-x-2.5">
        <!-- Search Button -->
        <a href="/category.php" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-green-50 flex items-center justify-center text-gray-700 hover:text-green-700 transition" title="Search Fruits">
          <i class="fas fa-search text-sm"></i>
        </a>

        <!-- User Profile Button -->
        <a href="<?= $currentCustomer ? '/account.php' : '/login.php' ?>" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-green-50 flex items-center justify-center text-gray-700 hover:text-green-700 transition" title="<?= $currentCustomer ? e($currentCustomer['name']) : 'Login / Register' ?>">
          <i class="fas fa-user text-sm"></i>
        </a>

        <!-- Cart Icon with live badge count -->
        <a href="/cart.php" class="relative w-9 h-9 rounded-full bg-gray-100 hover:bg-green-50 flex items-center justify-center text-gray-700 hover:text-green-700 transition" title="View Cart">
          <i class="fas fa-shopping-bag text-sm"></i>
          <span class="cart-badge-count absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center <?= $cartCount > 0 ? '' : 'hidden' ?>">
            <?= $cartCount ?>
          </span>
        </a>
      </div>
    </div>

    <!-- Location Bar Bar -->
    <div class="bg-green-50/70 border-t border-green-100/60 px-4 py-1 text-xs text-green-900 flex items-center justify-between max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl">
      <div class="flex items-center space-x-1.5 truncate">
        <i class="fas fa-map-marker-alt text-green-600"></i>
        <span class="font-medium text-gray-600">Delivering in:</span>
        <span class="font-bold text-green-800 truncate">Indore, MP (30-45 mins)</span>
      </div>
      <a href="/checkout.php" class="text-[11px] font-bold text-green-700 hover:underline flex-shrink-0">Change</a>
    </div>
  </header>

  <!-- Flash Messages Banner if any -->
  <?php if (hasFlashes()): ?>
    <div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-3">
      <?php foreach (getFlashes() as $flash): ?>
        <div class="p-3 mb-2 rounded-xl text-sm font-medium flex items-center justify-between <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-800 border border-green-200' ?>">
          <span><?= e($flash['message']) ?></span>
          <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Main Content Container with bottom padding so bottom nav doesn't overlap -->
  <main class="flex-1 pb-24">
