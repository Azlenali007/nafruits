<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Homepage (Matches Screenshot 2)
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

// Check Maintenance Mode
if (getSetting('maintenance_mode') === '1' && !isAdminLoggedIn()) {
    http_response_code(503);
    echo '<!DOCTYPE html><html><head><title>Under Maintenance - Na Fresh</title><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"></head><body class="bg-emerald-50 flex items-center justify-center min-h-screen p-4 text-center"><div class="bg-white p-8 rounded-3xl shadow-xl max-w-sm"><div class="text-6xl mb-4">🥥</div><h1 class="text-2xl font-black text-emerald-950 mb-2">Refreshing Our Orchard!</h1><p class="text-gray-600 text-sm mb-6">Na Fresh is undergoing brief scheduled maintenance to restock fresh inventory. We will be back in a few minutes!</p><p class="text-xs text-emerald-700 font-bold">Contact: ' . e(getSetting('shop_phone', '+91 98765 43210')) . '</p></div></body></html>';
    exit;
}

$pageTitle = 'Na Fresh – Fruits & Coconuts | Freshness in Every Bite';
require_once __DIR__ . '/includes/header.php';

// Fetch active homepage sections & their assigned products from MySQL
$sections = [];
$activeAnnouncements = [];
try {
    $dbSections = Database::fetchAll(
        "SELECT id, title, subtitle, slug, section_type FROM homepage_sections WHERE is_active = 1 ORDER BY display_order ASC"
    );

    foreach ($dbSections as $sec) {
        $products = Database::fetchAll(
            "SELECT p.id, p.name, p.slug, p.main_image, p.base_price, p.discount_price, p.default_unit, p.badge_text, p.rating, p.review_count, p.stock_quantity,
                    u.id as default_unit_id, u.unit_name as default_unit_name, u.price as default_unit_price, u.original_price as default_original_price
             FROM homepage_section_products hsp
             JOIN products p ON hsp.product_id = p.id
             LEFT JOIN product_units u ON (u.product_id = p.id AND u.is_default = 1)
             WHERE hsp.section_id = ? AND p.status = 'active'
             ORDER BY hsp.display_order ASC",
            [$sec['id']]
        );
        $sec['products'] = $products;
        $sections[] = $sec;
    }

    $activeAnnouncements = Database::fetchAll(
        "SELECT * FROM announcements WHERE is_active = 1 AND type IN ('popup', 'banner') ORDER BY display_order ASC LIMIT 2"
    );
} catch (Exception $e) {
    error_log('Error loading homepage: ' . $e->getMessage());
}
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-3 space-y-6">

  <!-- 1. Promotional Hero Banner: Fresh Coconut Water (Restored Premium Green Styling) -->
  <div class="hero-banner p-4 sm:p-6 flex items-center justify-between">
    
    <!-- Text Content (Dark Forest-Green with Maximum Contrast) -->
    <div class="z-10 max-w-[62%] sm:max-w-[65%] space-y-1.5 sm:space-y-2">
      <!-- Badge -->
      <span class="hero-badge">
        <?= renderIcon('leaf', 'w-3 h-3 text-lime-300 stroke-[2.5]') ?>
        <span>Nature's Energy Drink</span>
      </span>

      <!-- Heading -->
      <h2 class="hero-title tracking-tight mt-0.5">
        Fresh<br><span class="hero-title-accent">Coconut Water</span>
      </h2>

      <!-- Description (Clear, High-Contrast Dark Green, Visible on Mobile & Desktop) -->
      <p class="hero-desc line-clamp-2 max-w-xs">
        Chilled pure raw electrolyte water cut fresh from coastal palms daily.
      </p>

      <!-- Price & CTA Action -->
      <div class="pt-1 flex items-center space-x-2">
        <span class="hero-price-pill">
          ₹ 40
        </span>

        <button onclick="addToCart(1, 1, 1, this)" 
                class="hero-cta-btn"
                title="Add Fresh Coconut Water to Cart">
          <?= renderIcon('plus', 'w-3.5 h-3.5 text-white stroke-[2.5]') ?>
          <span>Add</span>
        </button>
      </div>
    </div>

    <!-- Product Image (Right Side, Beautifully Proportioned Tender Coconut) -->
    <div class="hero-img-wrap -mr-1">
      <img src="https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80" 
           alt="Fresh Coconut Water">
    </div>

    <!-- Pagination dots indicator with dark green active dot -->
    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex items-center space-x-1.5 pointer-events-none">
      <span class="w-4 h-1.5 rounded-full bg-[#064e3b]"></span>
      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
    </div>
  </div>

  <!-- 2. Shop by Category Quick Tiles -->
  <div>
    <div class="flex items-center justify-between mb-2.5">
      <h3 class="text-sm sm:text-base font-black text-gray-900 tracking-tight">Shop by Category</h3>
      <a href="/category.php" class="text-xs font-black text-emerald-800 hover:text-emerald-950 flex items-center space-x-1 whitespace-nowrap bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-full transition">
        <span>View All</span>
        <?= renderIcon('chevron-right', 'w-3.5 h-3.5 text-emerald-800') ?>
      </a>
    </div>

    <div class="flex space-x-2.5 overflow-x-auto no-scrollbar py-1">
      <a href="/category.php?slug=fresh-fruits" class="flex items-center space-x-2 bg-white border border-gray-200 rounded-2xl px-3.5 py-2 shadow-xs flex-shrink-0 hover:border-emerald-500 hover:bg-emerald-50/30 transition">
        <span class="w-7 h-7 rounded-xl bg-amber-50 flex items-center justify-center text-base">🍎</span>
        <span class="text-xs font-bold text-gray-800">Fresh Fruits</span>
      </a>
      <a href="/category.php?slug=coconut-water" class="flex items-center space-x-2 bg-emerald-50 border border-emerald-300 rounded-2xl px-3.5 py-2 shadow-xs flex-shrink-0 hover:border-emerald-600 transition">
        <span class="w-7 h-7 rounded-xl bg-emerald-100 flex items-center justify-center text-base">🥥</span>
        <span class="text-xs font-black text-emerald-900">Coconut & Water</span>
      </a>
      <a href="/category.php?slug=seasonal-fruits" class="flex items-center space-x-2 bg-white border border-gray-200 rounded-2xl px-3.5 py-2 shadow-xs flex-shrink-0 hover:border-emerald-500 hover:bg-emerald-50/30 transition">
        <span class="w-7 h-7 rounded-xl bg-amber-50 flex items-center justify-center text-base">🥭</span>
        <span class="text-xs font-bold text-gray-800">Seasonal</span>
      </a>
      <a href="/category.php?slug=exotic-berries" class="flex items-center space-x-2 bg-white border border-gray-200 rounded-2xl px-3.5 py-2 shadow-xs flex-shrink-0 hover:border-emerald-500 hover:bg-emerald-50/30 transition">
        <span class="w-7 h-7 rounded-xl bg-red-50 flex items-center justify-center text-base">🍓</span>
        <span class="text-xs font-bold text-gray-800">Berries & Exotic</span>
      </a>
    </div>
  </div>

  <!-- 3. Dynamic Horizontally Swipeable Product Sections (Matches Screenshot 2) -->
  <?php if (!empty($sections)): ?>
    <?php foreach ($sections as $section): ?>
      <?php if (!empty($section['products'])): ?>
        <section class="space-y-2.5">
          <div class="flex items-center justify-between mb-1">
            <div class="min-w-0 pr-2">
              <h3 class="text-base sm:text-lg font-black text-gray-900 tracking-tight leading-tight"><?= e($section['title']) ?></h3>
              <?php if (!empty($section['subtitle'])): ?>
                <p class="text-[11px] text-gray-500 font-medium truncate"><?= e($section['subtitle']) ?></p>
              <?php endif; ?>
            </div>
            <a href="/category.php?section=<?= e($section['slug']) ?>" class="flex-shrink-0 text-xs font-black text-emerald-800 hover:text-emerald-950 flex items-center space-x-1 whitespace-nowrap bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-full transition">
              <span>View All</span>
              <?= renderIcon('chevron-right', 'w-3 h-3 text-emerald-800 stroke-[2.5]') ?>
            </a>
          </div>

          <!-- Horizontally Scrollable Cards Container (Showing ~2.2 cards on mobile with snap preview) -->
          <div id="carousel-<?= $section['id'] ?>" class="carousel-snap-track">
            <?php foreach ($section['products'] as $prod): 
              $displayPrice = !empty($prod['default_unit_price']) ? (float)$prod['default_unit_price'] : (float)$prod['base_price'];
              $originalPrice = !empty($prod['default_original_price']) ? (float)$prod['default_original_price'] : null;
              $displayUnit = !empty($prod['default_unit_name']) ? $prod['default_unit_name'] : $prod['default_unit'];
              $unitId = !empty($prod['default_unit_id']) ? (int)$prod['default_unit_id'] : null;
              $discountPercent = ($originalPrice && $originalPrice > $displayPrice) ? round((($originalPrice - $displayPrice) / $originalPrice) * 100) : 0;
            ?>
              <!-- Compact & Proportional Product Card (Contains All 10 Required Elements) -->
              <div class="product-card">
                
                <!-- 1. Bounded Image Wrap + 2. Badge + 3. Favorite Button -->
                <div class="product-card-img-wrap">
                  <?php if (!empty($prod['badge_text'])): ?>
                    <span class="product-card-badge"><?= e($prod['badge_text']) ?></span>
                  <?php elseif ($discountPercent > 0): ?>
                    <span class="product-card-badge product-card-badge-discount"><?= $discountPercent ?>% OFF</span>
                  <?php endif; ?>

                  <button type="button" 
                          onclick="toggleFavorite(<?= $prod['id'] ?>, this)" 
                          class="product-card-fav btn-favorite"
                          title="Save to favorites">
                    <?= renderIcon('heart', 'w-3.5 h-3.5') ?>
                  </button>

                  <a href="/product.php?id=<?= $prod['id'] ?>" class="w-full h-full flex items-center justify-center p-1">
                    <img src="<?= e($prod['main_image']) ?>" 
                         alt="<?= e($prod['name']) ?>" 
                         loading="lazy">
                  </a>
                </div>

                <!-- 4. Product Name + 8. Unit Selector + 5. Current Price + 6. Original Price + 7. Discount Tag -->
                <div class="product-card-info">
                  <a href="/product.php?id=<?= $prod['id'] ?>" class="product-card-title" title="<?= e($prod['name']) ?>">
                    <?= e($prod['name']) ?>
                  </a>

                  <div class="product-card-unit">
                    <span><?= e($displayUnit) ?></span>
                  </div>

                  <div class="product-card-pricing">
                    <span class="product-card-price">
                      <?= formatPrice($displayPrice) ?>
                    </span>
                    <?php if ($originalPrice && $originalPrice > $displayPrice): ?>
                      <span class="product-card-orig-price">
                        <?= formatPrice($originalPrice) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- 9. Compact Quantity Selector + 10. Visible Add to Cart Button -->
                <div class="product-card-actions">
                  <div class="card-qty-control">
                    <button type="button" onclick="adjustCardQty(<?= $prod['id'] ?>, -1)" class="card-qty-btn" aria-label="Decrease quantity">
                      <?= renderIcon('minus', 'w-2.5 h-2.5 stroke-[2.5]') ?>
                    </button>
                    <span id="card-qty-<?= $prod['id'] ?>" class="card-qty-num">1</span>
                    <button type="button" onclick="adjustCardQty(<?= $prod['id'] ?>, 1)" class="card-qty-btn" aria-label="Increase quantity">
                      <?= renderIcon('plus', 'w-2.5 h-2.5 stroke-[2.5]') ?>
                    </button>
                  </div>

                  <button type="button" 
                          onclick="addCardItemToCart(<?= $prod['id'] ?>, <?= $unitId ? $unitId : 'null' ?>, this)"
                          class="product-card-add-btn" 
                          title="Add to Cart">
                    <?= renderIcon('shopping-cart', 'w-3 h-3 text-white') ?>
                    <span>Add</span>
                  </button>
                </div>

              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php else: ?>
    <!-- Notice if database is empty -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200 text-center my-6">
      <div class="text-4xl mb-2">🌴</div>
      <h3 class="text-base font-bold text-gray-800 mb-1">Welcome to Na Fresh</h3>
      <p class="text-xs text-gray-500 mb-4">Please run the installation wizard or seed the database to populate fresh products.</p>
      <a href="/install/index.php" class="inline-block bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-xl">Open Installation Wizard</a>
    </div>
  <?php endif; ?>

  <!-- 4. Announcement / Telegram Banner -->
  <div class="bg-gradient-to-r from-emerald-900 to-green-950 text-white p-4 rounded-3xl shadow-sm flex items-center justify-between border border-emerald-800/40">
    <div class="space-y-1 max-w-[75%]">
      <span class="text-[9px] bg-emerald-500/30 text-lime-300 font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">VIP Fresh Club</span>
      <h4 class="text-xs sm:text-sm font-black">Join NA Fresh on Telegram</h4>
      <p class="text-[11px] text-emerald-200/80 leading-snug">Get morning flash discount codes and daily fresh harvest notices.</p>
    </div>
    <a href="<?= e(getSetting('telegram_channel_url', 'https://t.me/nafreshfruits')) ?>" target="_blank" rel="noopener" class="w-10 h-10 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center shadow-lg transition flex-shrink-0" title="Join Telegram">
      <i class="fab fa-telegram-plane text-xl"></i>
    </a>
  </div>

</div>

<script>
function toggleFavorite(productId, btn) {
  btn.classList.toggle('active');
  const isActive = btn.classList.contains('active');
  btn.innerHTML = isActive 
    ? '<?= renderIcon("heart-fill", "w-3.5 h-3.5 text-red-500 fill-current") ?>' 
    : '<?= renderIcon("heart", "w-3.5 h-3.5 text-gray-400") ?>';
  showToast(isActive ? 'Saved to favorites!' : 'Removed from favorites', 'success');
}

function adjustCardQty(productId, delta) {
  const el = document.getElementById('card-qty-' + productId);
  if (!el) return;
  let current = parseInt(el.textContent, 10) || 1;
  current = Math.max(1, current + delta);
  el.textContent = current;
}

function addCardItemToCart(productId, unitId, btn) {
  const qtyEl = document.getElementById('card-qty-' + productId);
  const qty = qtyEl ? (parseInt(qtyEl.textContent, 10) || 1) : 1;
  addToCart(productId, unitId, qty, btn);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
