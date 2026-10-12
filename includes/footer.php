<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Frontend Footer with Mobile Bottom Navigation
 */

declare(strict_types=1);
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$cartCount = getCartItemCount();
$isLoggedIn = isCustomerLoggedIn();
?>
  </main>

  <!-- 3. Fixed Mobile App Bottom Navigation (Matching Reference Screenshot) -->
  <nav class="fixed bottom-0 inset-x-0 z-40 bg-white border-t border-gray-200 bottom-nav">
    <div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-3 py-1.5 flex items-center justify-around relative">
      
      <!-- Home -->
      <a href="/index.php" class="flex flex-col items-center justify-center py-1 px-2 group <?= ($currentPath === '/' || $currentPath === '/index.php') ? 'text-green-700 font-bold' : 'text-gray-500 hover:text-green-600' ?>">
        <i class="fas fa-home text-lg mb-0.5 <?= ($currentPath === '/' || $currentPath === '/index.php') ? 'text-green-700' : '' ?>"></i>
        <span class="text-[10px]">Home</span>
      </a>

      <!-- Categories -->
      <a href="/category.php" class="flex flex-col items-center justify-center py-1 px-2 group <?= ($currentPath === '/category.php') ? 'text-green-700 font-bold' : 'text-gray-500 hover:text-green-600' ?>">
        <i class="fas fa-th-large text-lg mb-0.5 <?= ($currentPath === '/category.php') ? 'text-green-700' : '' ?>"></i>
        <span class="text-[10px]">Categories</span>
      </a>

      <!-- Floating Center Action Button (Quick Shop / Fresh Coconut) -->
      <a href="/product.php?id=1" class="relative -top-4 flex items-center justify-center w-12 h-12 rounded-full bg-green-700 text-white nav-center-action hover:bg-green-800 transition transform active:scale-95" title="Fresh Coconut Water">
        <i class="fas fa-seedling text-xl"></i>
      </a>

      <!-- Cart -->
      <a href="/cart.php" class="flex flex-col items-center justify-center py-1 px-2 relative group <?= ($currentPath === '/cart.php' || $currentPath === '/checkout.php') ? 'text-green-700 font-bold' : 'text-gray-500 hover:text-green-600' ?>">
        <div class="relative">
          <i class="fas fa-shopping-basket text-lg mb-0.5 <?= ($currentPath === '/cart.php') ? 'text-green-700' : '' ?>"></i>
          <span class="cart-badge-count absolute -top-1.5 -right-2 bg-red-500 text-white text-[9px] font-bold w-4 h-4 rounded-full flex items-center justify-center <?= $cartCount > 0 ? '' : 'hidden' ?>">
            <?= $cartCount ?>
          </span>
        </div>
        <span class="text-[10px]">Cart</span>
      </a>

      <!-- Profile / Orders -->
      <a href="<?= $isLoggedIn ? '/account.php' : '/login.php' ?>" class="flex flex-col items-center justify-center py-1 px-2 group <?= (str_starts_with($currentPath, '/account') || str_starts_with($currentPath, '/orders')) ? 'text-green-700 font-bold' : 'text-gray-500 hover:text-green-600' ?>">
        <i class="fas fa-user-circle text-lg mb-0.5 <?= ($isLoggedIn && str_starts_with($currentPath, '/account')) ? 'text-green-700' : '' ?>"></i>
        <span class="text-[10px]"><?= $isLoggedIn ? 'Profile' : 'Login' ?></span>
      </a>
      
    </div>
  </nav>

  <!-- Core JavaScript Assets -->
  <script src="/assets/js/main.js"></script>
</body>
</html>
