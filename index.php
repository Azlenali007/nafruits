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

  <!-- 1. Promotional Hero Banner: Fresh Coconut Water (High Contrast Dark Forest-Green on Light Green) -->
  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-100 via-green-100/90 to-emerald-50 border border-emerald-300/80 shadow-xs p-4 sm:p-6 flex items-center justify-between">
    
    <!-- Text Content (Dark Forest-Green with Maximum Contrast) -->
    <div class="z-10 max-w-[62%] sm:max-w-[65%] space-y-1.5 sm:space-y-2">
      <!-- Badge -->
      <span class="inline-flex items-center space-x-1.5 bg-[#064e3b] text-[#bbf7d0] text-[10px] sm:text-xs font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow-xs">
        <?= renderIcon('leaf', 'w-3 h-3 text-lime-300 stroke-[2.5]') ?>
        <span>Nature's Energy Drink</span>
      </span>

      <!-- Heading -->
      <h2 class="text-xl sm:text-3xl font-black text-[#064e3b] leading-tight tracking-tight mt-1">
        Fresh<br><span class="text-[#047857]">Coconut Water</span>
      </h2>

      <!-- Description (Clear, High-Contrast Dark Green, Visible on Mobile & Desktop) -->
      <p class="text-[11px] sm:text-xs text-[#064e3b] font-semibold leading-relaxed line-clamp-2 max-w-xs">
        Chilled pure raw electrolyte water cut fresh from coastal palms daily.
      </p>

      <!-- Price & CTA Action -->
      <div class="pt-1 flex items-center space-x-2">
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-white text-[#064e3b] font-black text-xs sm:text-sm shadow-xs border border-emerald-300">
          ₹ 40
        </span>

        <button onclick="addToCart(1, 1, 1, this)" 
                class="bg-[#064e3b] hover:bg-[#022c22] active:scale-95 text-white font-extrabold text-xs px-3.5 py-1.5 sm:py-2 rounded-xl transition shadow-sm flex items-center space-x-1.5 cursor-pointer"
                title="Add Fresh Coconut Water to Cart">
          <?= renderIcon('plus', 'w-3.5 h-3.5 text-white stroke-[2.5]') ?>
          <span>Add</span>
        </button>
      </div>
    </div>

    <!-- Product Image (Right Side, Beautifully Proportioned Tender Coconut) -->
    <div class="relative w-28 h-28 sm:w-40 sm:h-40 flex-shrink-0 -mr-1 flex items-center justify-center">
      <img src="https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80" 
           alt="Fresh Coconut Water" 
           class="w-full h-full object-cover rounded-2xl drop-shadow-md border-2 border-white transform hover:scale-105 transition duration-300">
    </div>

    <!-- Pagination dots indicator with dark green active dot -->
    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex items-center space-x-1.5 pointer-events-none">
      <span class="w-4 h-1.5 rounded-full bg-[#064e3b]"></span>
      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
    </div>
  </div>

  <!-- 2. Shop by Category Quick Tiles -->
  <div>
    <div class="flex items-center justify-between mb-2.5">
      <h3 class="text-sm sm:text-base font-black text-gray-900 tracking-tight">Shop by Category</h3>
      <a href="/category.php" class="text-xs font-extrabold text-emerald-700 hover:text-emerald-800 flex items-center space-x-1">
        <span>View All</span>
        <?= renderIcon('chevron-right', 'w-3.5 h-3.5 text-emerald-700') ?>
      </a>
    </div>

    <div class="flex space-x-2.5 overflow-x-auto no-scrollbar py-1">
      <a href="/category.php?slug=fresh-fruits" class="flex items-center space-x-2 bg-white border border-gray-150 rounded-2xl px-3.5 py-2 shadow-xs flex-shrink-0 hover:border-emerald-500 hover:bg-emerald-50/30 transition">
        <span class="w-7 h-7 rounded-xl bg-amber-50 flex items-center justify-center text-base">🍎</span>
        <span class="text-xs font-bold text-gray-800">Fresh Fruits</span>
      </a>
      <a href="/category.php?slug=coconut-water" class="flex items-center space-x-2 bg-emerald-50 border border-emerald-300 rounded-2xl px-3.5 py-2 shadow-xs flex-shrink-0 hover:border-emerald-600 transition">
        <span class="w-7 h-7 rounded-xl bg-emerald-100 flex items-center justify-center text-base">🥥</span>
        <span class="text-xs font-black text-emerald-900">Coconut & Water</span>
      </a>
      <a href="/category.php?slug=seasonal-fruits" class="flex items-center space-x-2 bg-white border border-gray-150 rounded-2xl px-3.5 py-2 shadow-xs flex-shrink-0 hover:border-emerald-500 hover:bg-emerald-50/30 transition">
        <span class="w-7 h-7 rounded-xl bg-amber-50 flex items-center justify-center text-base">🥭</span>
        <span class="text-xs font-bold text-gray-800">Seasonal</span>
      </a>
      <a href="/category.php?slug=exotic-berries" class="flex items-center space-x-2 bg-white border border-gray-150 rounded-2xl px-3.5 py-2 shadow-xs flex-shrink-0 hover:border-emerald-500 hover:bg-emerald-50/30 transition">
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
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base sm:text-lg font-black text-gray-900 tracking-tight"><?= e($section['title']) ?></h3>
              <?php if (!empty($section['subtitle'])): ?>
                <p class="text-[11px] text-gray-500"><?= e($section['subtitle']) ?></p>
              <?php endif; ?>
            </div>
            <a href="/category.php?section=<?= e($section['slug']) ?>" class="text-xs font-extrabold text-emerald-700 hover:text-emerald-800 flex items-center space-x-1">
              <span>See All</span>
              <?= renderIcon('chevron-right', 'w-3 h-3 text-emerald-700') ?>
            </a>
          </div>

          <!-- Horizontally Scrollable Cards Container (Showing ~2.3 cards on mobile with snap preview) -->
          <div id="carousel-<?= $section['id'] ?>" class="flex space-x-3 overflow-x-auto no-scrollbar carousel-snap pb-2 pt-1 -mx-4 px-4 sm:mx-0 sm:px-0">
            <?php foreach ($section['products'] as $prod): 
              $displayPrice = !empty($prod['default_unit_price']) ? (float)$prod['default_unit_price'] : (float)$prod['base_price'];
              $originalPrice = !empty($prod['default_original_price']) ? (float)$prod['default_original_price'] : null;
              $displayUnit = !empty($prod['default_unit_name']) ? $prod['default_unit_name'] : $prod['default_unit'];
              $unitId = !empty($prod['default_unit_id']) ? (int)$prod['default_unit_id'] : null;
            ?>
              <!-- Premium Product Card (Consistent 145px width on mobile, 175px on sm, 190px on md) -->
              <div class="w-[145px] sm:w-[170px] md:w-[190px] flex-shrink-0 bg-white rounded-2xl p-2.5 border border-gray-150 shadow-xs product-card flex flex-col justify-between relative group">
                
                <!-- Top Card Actions: Badge & Favorite Icon -->
                <div class="flex items-center justify-between absolute top-2 inset-x-2 z-10 pointer-events-none">
                  <?php if (!empty($prod['badge_text'])): ?>
                    <span class="bg-emerald-100/90 backdrop-blur-xs text-emerald-800 text-[9px] font-extrabold px-1.5 py-0.5 rounded-md shadow-xs pointer-events-auto">
                      <?= e($prod['badge_text']) ?>
                    </span>
                  <?php else: ?>
                    <span></span>
                  <?php endif; ?>

                  <!-- Favorite Button -->
                  <button type="button" 
                          onclick="toggleFavorite(<?= $prod['id'] ?>, this)" 
                          class="w-6 h-6 rounded-full bg-white/80 backdrop-blur-xs hover:bg-white text-gray-400 hover:text-red-500 shadow-xs flex items-center justify-center transition pointer-events-auto btn-favorite"
                          title="Save to favorites">
                    <?= renderIcon('heart', 'w-3.5 h-3.5') ?>
                  </button>
                </div>

                <!-- Product Image Container -->
                <a href="/product.php?id=<?= $prod['id'] ?>" class="block relative aspect-square w-full rounded-xl overflow-hidden bg-emerald-50/30 mb-2">
                  <img src="<?= e($prod['main_image']) ?>" 
                       alt="<?= e($prod['name']) ?>" 
                       loading="lazy" 
                       class="w-full h-full object-cover rounded-xl transform group-hover:scale-105 transition duration-300">
                </a>

                <!-- Product Title & Pack Unit -->
                <div class="space-y-0.5 mb-2">
                  <a href="/product.php?id=<?= $prod['id'] ?>" class="block font-black text-xs sm:text-sm text-gray-900 leading-snug line-clamp-1 hover:text-emerald-700">
                    <?= e($prod['name']) ?>
                  </a>
                  <div class="flex items-center text-[11px] text-gray-500 font-medium">
                    <span><?= e($displayUnit) ?></span>
                  </div>
                </div>

                <!-- Price and Quick Add Button Row -->
                <div class="flex items-center justify-between pt-1 border-t border-gray-50">
                  <div class="flex flex-col">
                    <?php if ($originalPrice && $originalPrice > $displayPrice): ?>
                      <span class="text-[10px] text-gray-400 line-through">
                        <?= formatPrice($originalPrice) ?>
                      </span>
                    <?php endif; ?>
                    <span class="text-xs sm:text-sm font-black text-emerald-950">
                      <?= formatPrice($displayPrice) ?>
                    </span>
                  </div>

                  <!-- Round Green Add to Cart Button (Matching Reference Screenshot) -->
                  <button type="button" 
                          onclick="addToCart(<?= $prod['id'] ?>, <?= $unitId ? $unitId : 'null' ?>, 1, this)"
                          class="w-8 h-8 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:scale-90 text-white flex items-center justify-center transition shadow-sm"
                          title="Add to Cart">
                    <?= renderIcon('shopping-cart', 'w-4 h-4 text-white') ?>
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
