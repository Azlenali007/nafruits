<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Frontend Footer with Properly Docked Mobile Bottom Navigation
 */

declare(strict_types=1);
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$cartCount = getCartItemCount();
$isLoggedIn = isCustomerLoggedIn();
?>
  </main>

  <!-- 3. Fixed Mobile App Bottom Navigation (Clean, Solid, Docked, Zero Floating Circles) -->
  <nav class="bottom-nav">
    <div class="max-w-md mx-auto sm:max-w-xl md:max-w-2xl px-2 h-16 grid grid-cols-5 items-center">
      
      <!-- 1. Home -->
      <a href="/index.php" class="flex flex-col items-center justify-center py-1 text-center group transition select-none <?= ($currentPath === '/' || $currentPath === '/index.php') ? 'text-emerald-900 font-black' : 'text-gray-500 hover:text-emerald-700' ?>">
        <?= renderIcon('home', 'w-5 h-5 ' . (($currentPath === '/' || $currentPath === '/index.php') ? 'text-emerald-800 stroke-[2.5]' : 'text-gray-400 group-hover:text-emerald-700')) ?>
        <span class="text-[10px] mt-1 <?= ($currentPath === '/' || $currentPath === '/index.php') ? 'font-black text-emerald-900' : 'font-medium' ?>">Home</span>
      </a>

      <!-- 2. Categories -->
      <a href="/category.php" class="flex flex-col items-center justify-center py-1 text-center group transition select-none <?= ($currentPath === '/category.php') ? 'text-emerald-900 font-black' : 'text-gray-500 hover:text-emerald-700' ?>">
        <?= renderIcon('grid', 'w-5 h-5 ' . (($currentPath === '/category.php') ? 'text-emerald-800 stroke-[2.5]' : 'text-gray-400 group-hover:text-emerald-700')) ?>
        <span class="text-[10px] mt-1 <?= ($currentPath === '/category.php') ? 'font-black text-emerald-900' : 'font-medium' ?>">Categories</span>
      </a>

      <!-- 3. Centre Brand Action (Balanced, aligned cleanly inside the bar, no awkward floating offset) -->
      <a href="/product.php?id=1" class="flex flex-col items-center justify-center py-1 text-center group transition select-none" title="Fresh Tender Coconuts">
        <div class="w-8 h-8 rounded-xl bg-emerald-800 text-white flex items-center justify-center shadow-xs group-hover:bg-emerald-900 transition active:scale-95">
          <?= renderIcon('leaf', 'w-4 h-4 text-lime-300 stroke-[2.5]') ?>
        </div>
        <span class="text-[10px] mt-0.5 font-bold text-emerald-900">Fresh</span>
      </a>

      <!-- 4. Cart -->
      <a href="/cart.php" class="flex flex-col items-center justify-center py-1 text-center relative group transition select-none <?= ($currentPath === '/cart.php' || $currentPath === '/checkout.php') ? 'text-emerald-900 font-black' : 'text-gray-500 hover:text-emerald-700' ?>">
        <div class="relative">
          <?= renderIcon('shopping-bag', 'w-5 h-5 ' . (($currentPath === '/cart.php' || $currentPath === '/checkout.php') ? 'text-emerald-800 stroke-[2.5]' : 'text-gray-400 group-hover:text-emerald-700')) ?>
          <span class="cart-badge-count absolute -top-1.5 -right-2.5 bg-red-500 text-white text-[9px] font-black w-4 h-4 rounded-full flex items-center justify-center shadow-xs <?= $cartCount > 0 ? '' : 'hidden' ?>">
            <?= $cartCount ?>
          </span>
        </div>
        <span class="text-[10px] mt-1 <?= ($currentPath === '/cart.php' || $currentPath === '/checkout.php') ? 'font-black text-emerald-900' : 'font-medium' ?>">Cart</span>
      </a>

      <!-- 5. Profile -->
      <a href="<?= $isLoggedIn ? '/account.php' : '/login.php' ?>" class="flex flex-col items-center justify-center py-1 text-center group transition select-none <?= (str_starts_with($currentPath, '/account') || str_starts_with($currentPath, '/orders')) ? 'text-emerald-900 font-black' : 'text-gray-500 hover:text-emerald-700' ?>">
        <?= renderIcon('user', 'w-5 h-5 ' . (($isLoggedIn && str_starts_with($currentPath, '/account')) ? 'text-emerald-800 stroke-[2.5]' : 'text-gray-400 group-hover:text-emerald-700')) ?>
        <span class="text-[10px] mt-1 <?= (str_starts_with($currentPath, '/account') || str_starts_with($currentPath, '/orders')) ? 'font-black text-emerald-900' : 'font-medium' ?>"><?= $isLoggedIn ? 'Profile' : 'Profile' ?></span>
      </a>
      
    </div>
  </nav>

  <!-- Core JavaScript Assets -->
  <script src="/assets/js/main.js"></script>
</body>
</html>
