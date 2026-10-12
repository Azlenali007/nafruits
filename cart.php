<?php
/**
 * NA FRESH – Fruits & Coconuts
 * My Cart View (Matches Screenshot 4)
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

// Handle coupon submission via standard POST fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon_action'])) {
    $code = trim($_POST['coupon_code'] ?? '');
    if (!empty($code)) {
        $_SESSION['applied_coupon'] = $code;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon_action'])) {
    unset($_SESSION['applied_coupon']);
}

$pageTitle = 'My Cart – Na Fresh';
require_once __DIR__ . '/includes/header.php';

$cartData = getCartTotals();
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-3 pb-8 space-y-4">

  <!-- Header Row: Back, Title, Trash / Clear (Matching Screenshot 4) -->
  <div class="flex items-center justify-between">
    <a href="/index.php" class="w-9 h-9 rounded-full bg-white border border-gray-150 flex items-center justify-center text-gray-700 hover:bg-gray-100 transition shadow-xs">
      <?= renderIcon('arrow-left', 'w-4 h-4 text-gray-700') ?>
    </a>
    <h1 class="text-base font-black text-gray-900 tracking-tight">My Cart</h1>
    <?php if (!empty($cartData['items'])): ?>
      <button onclick="confirmClearCart()" class="w-9 h-9 rounded-full bg-white border border-gray-150 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-gray-100 transition shadow-xs" title="Clear Cart">
        <?= renderIcon('trash', 'w-4 h-4 text-gray-400 hover:text-red-500') ?>
      </button>
    <?php else: ?>
      <div class="w-9"></div>
    <?php endif; ?>
  </div>

  <?php if (empty($cartData['items'])): ?>
    <!-- Empty Cart State -->
    <div class="bg-white rounded-3xl p-8 border border-gray-150 shadow-xs text-center my-6 space-y-3">
      <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto text-emerald-700 text-2xl">
        <?= renderIcon('shopping-bag', 'w-8 h-8 text-emerald-700') ?>
      </div>
      <h2 class="text-lg font-black text-gray-900">Your basket is empty</h2>
      <p class="text-xs text-gray-500 max-w-xs mx-auto">Discover crisp farm-fresh fruits and cold tender coconuts handpicked for you.</p>
      <div class="pt-2">
        <a href="/index.php" class="inline-block bg-emerald-800 hover:bg-emerald-900 text-white font-extrabold text-xs px-6 py-3 rounded-2xl shadow-md transition">
          Browse Fresh Fruits
        </a>
      </div>
    </div>
  <?php else: ?>

    <!-- Cart Items List (Matching Screenshot 4) -->
    <div class="space-y-2.5">
      <?php foreach ($cartData['items'] as $item): ?>
        <div id="cart-row-<?= e($item['cart_key']) ?>" class="bg-white rounded-2xl p-3 border border-gray-150 shadow-xs flex items-center justify-between space-x-3">
          
          <!-- Product Thumbnail -->
          <a href="/product.php?id=<?= $item['product_id'] ?>" class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-emerald-50/20 flex-shrink-0">
            <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" class="w-full h-full object-cover rounded-xl">
          </a>

          <!-- Details -->
          <div class="flex-1 min-w-0">
            <a href="/product.php?id=<?= $item['product_id'] ?>" class="font-black text-xs sm:text-sm text-gray-900 truncate block hover:text-emerald-700">
              <?= e($item['name']) ?>
            </a>
            <div class="text-xs font-black text-emerald-950 mt-0.5">
              <?= formatPrice($item['price']) ?><span class="text-[11px] font-normal text-gray-500"> / <?= e($item['unit_name']) ?></span>
            </div>

            <!-- Quantity Counter ([-] 1 [+]) -->
            <div class="flex items-center space-x-2 mt-2">
              <div class="flex items-center bg-gray-100 rounded-xl p-0.5 border border-gray-200">
                <button type="button" 
                        onclick="updateItemQty('<?= e($item['cart_key']) ?>', <?= $item['quantity'] - 1 ?>)"
                        class="w-6 h-6 rounded-lg bg-white text-gray-700 font-bold shadow-xs hover:bg-gray-50 flex items-center justify-center transition">
                  <?= renderIcon('minus', 'w-3 h-3 text-gray-700 stroke-[2.5]') ?>
                </button>
                <span class="w-8 text-center font-black text-xs text-gray-800">
                  <?= (int)$item['quantity'] ?>
                </span>
                <button type="button" 
                        onclick="updateItemQty('<?= e($item['cart_key']) ?>', <?= $item['quantity'] + 1 ?>)"
                        class="w-6 h-6 rounded-lg bg-white text-gray-700 font-bold shadow-xs hover:bg-gray-50 flex items-center justify-center transition">
                  <?= renderIcon('plus', 'w-3 h-3 text-gray-700 stroke-[2.5]') ?>
                </button>
              </div>
            </div>
          </div>

          <!-- Red Trash Icon to Remove Item (Matching Screenshot 4) -->
          <button type="button" 
                  onclick="removeCartItem('<?= e($item['cart_key']) ?>')"
                  class="text-red-400 hover:text-red-600 p-2 transition" 
                  title="Remove Item">
            <?= renderIcon('trash', 'w-4 h-4 text-red-400 hover:text-red-600') ?>
          </button>

        </div>
      <?php endforeach; ?>
    </div>

    <!-- Coupon Input Card -->
    <div class="bg-white rounded-2xl p-3.5 border border-gray-150 shadow-xs">
      <form action="/cart.php" method="POST" class="flex items-center space-x-2">
        <div class="relative flex-1">
          <div class="absolute left-3 top-2.5 text-gray-400">
            <?= renderIcon('tag', 'w-3.5 h-3.5 text-gray-400') ?>
          </div>
          <input type="text" 
                 name="coupon_code" 
                 placeholder="Enter Coupon (e.g. FRESH50)" 
                 value="<?= e($_SESSION['applied_coupon'] ?? '') ?>"
                 class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-8 pr-3 py-2 text-xs font-black uppercase tracking-wider text-gray-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition">
        </div>
        <button type="submit" name="apply_coupon_action" value="1" class="bg-emerald-800 hover:bg-emerald-900 text-white font-extrabold text-xs px-4 py-2 rounded-xl transition">
          Apply
        </button>
      </form>

      <?php if (!empty($cartData['applied_coupon'])): ?>
        <div class="mt-2.5 flex items-center justify-between bg-emerald-50 border border-emerald-200 text-emerald-900 px-3 py-1.5 rounded-xl text-xs font-bold">
          <span class="flex items-center space-x-1.5">
            <?= renderIcon('check-circle', 'w-3.5 h-3.5 text-emerald-700') ?>
            <span>Coupon '<?= e($cartData['applied_coupon']['code']) ?>' applied!</span>
          </span>
          <form action="/cart.php" method="POST" class="inline">
            <button type="submit" name="remove_coupon_action" value="1" class="text-red-500 hover:underline text-[11px] font-bold">Remove</button>
          </form>
        </div>
      <?php elseif (!empty($cartData['coupon_error'])): ?>
        <div class="mt-2 text-red-600 text-xs font-semibold">
          <?= e($cartData['coupon_error']) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Price Breakdown Card (Matching Screenshot 4) -->
    <div class="bg-white rounded-2xl p-4 border border-gray-150 shadow-xs space-y-2">
      <div class="flex items-center justify-between text-xs text-gray-500 font-medium">
        <span>Subtotal</span>
        <span class="font-black text-gray-900"><?= formatPrice($cartData['subtotal']) ?></span>
      </div>

      <div class="flex items-center justify-between text-xs text-gray-500 font-medium">
        <span>Delivery Charge</span>
        <span class="font-black text-gray-900">
          <?= $cartData['delivery_fee'] > 0 ? formatPrice($cartData['delivery_fee']) : '<span class="text-emerald-700 font-bold">FREE</span>' ?>
        </span>
      </div>

      <?php if ($cartData['discount_amount'] > 0): ?>
        <div class="flex items-center justify-between text-xs text-emerald-700 font-medium">
          <span>Coupon Discount</span>
          <span class="font-bold">- <?= formatPrice($cartData['discount_amount']) ?></span>
        </div>
      <?php endif; ?>

      <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
        <span class="text-sm font-black text-gray-900">Total</span>
        <span class="text-lg font-black text-emerald-950"><?= formatPrice($cartData['total']) ?></span>
      </div>
    </div>

    <!-- Proceed to Checkout Button (Matching Screenshot 4) -->
    <div class="pt-1">
      <a href="/checkout.php" class="w-full bg-emerald-800 hover:bg-emerald-900 text-white font-extrabold py-3.5 px-6 rounded-2xl shadow-md transition transform active:scale-95 flex items-center justify-center space-x-2 text-sm sm:text-base">
        <span>Proceed to Checkout</span>
        <?= renderIcon('arrow-right', 'w-4 h-4 text-white') ?>
      </a>
    </div>

  <?php endif; ?>

</div>

<script>
function updateItemQty(cartKey, newQty) {
  const formData = new FormData();
  formData.append('action', 'update');
  formData.append('cart_key', cartKey);
  formData.append('quantity', newQty);

  fetch('/api/cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        window.location.reload();
      } else {
        showToast(data.error || 'Failed to update', 'error');
      }
    });
}

function removeCartItem(cartKey) {
  const formData = new FormData();
  formData.append('action', 'remove');
  formData.append('cart_key', cartKey);

  fetch('/api/cart.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        window.location.reload();
      }
    });
}

function confirmClearCart() {
  Swal.fire({
    title: 'Clear your cart?',
    text: 'All items will be removed from your basket.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#064e3b',
    cancelButtonColor: '#9ca3af',
    confirmButtonText: 'Yes, clear it'
  }).then((result) => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append('action', 'clear');
      fetch('/api/cart.php', { method: 'POST', body: formData })
        .then(() => window.location.reload());
    }
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
