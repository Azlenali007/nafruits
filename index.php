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
    echo '<!DOCTYPE html><html><head><title>Under Maintenance - Na Fresh</title><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"></head><body class="bg-green-50 flex items-center justify-center min-h-screen p-4 text-center"><div class="bg-white p-8 rounded-3xl shadow-xl max-w-sm"><div class="text-6xl mb-4">🥥</div><h1 class="text-2xl font-black text-green-900 mb-2">Refreshing Our Orchard!</h1><p class="text-gray-600 text-sm mb-6">Na Fresh is undergoing brief scheduled maintenance to restock fresh inventory. We will be back in a few minutes!</p><p class="text-xs text-green-700 font-bold">Contact: ' . e(getSetting('shop_phone', '+91 98765 43210')) . '</p></div></body></html>';
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
                    u.id as default_unit_id, u.unit_name as default_unit_name, u.price as default_unit_price
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
    // If DB is not yet installed
    error_log('Error loading homepage: ' . $e->getMessage());
}
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-3 space-y-6">

  <!-- 1. Featured Hero Banner: Fresh Coconut Water (Matching Screenshot 2) -->
  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-green-700 to-lime-600 text-white shadow-lg p-5 sm:p-6 flex items-center justify-between">
    <div class="z-10 max-w-[62%] sm:max-w-[70%] space-y-2">
      <span class="inline-block bg-white/20 backdrop-blur-md text-lime-200 text-xs font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
        Nature's Energy Drink
      </span>
      <h2 class="text-2xl sm:text-3xl font-black leading-tight tracking-tight">
        Fresh<br><span class="text-lime-300">Coconut Water</span>
      </h2>
      <p class="text-xs text-green-100 hidden sm:block">Chilled pure raw electrolyte water cut fresh from coastal palms.</p>
      <div class="pt-1 flex items-center space-x-3">
        <span class="inline-flex items-center px-3 py-1 rounded-full bg-white text-green-800 font-extrabold text-sm shadow">
          ₹ 40
        </span>
        <button onclick="addToCart(1, 1, 1, this)" class="bg-lime-400 hover:bg-lime-300 text-green-950 font-black text-xs px-4 py-2 rounded-xl transition shadow active:scale-95 flex items-center space-x-1">
          <i class="fas fa-plus"></i>
          <span>Add</span>
        </button>
      </div>
    </div>

    <!-- Hero Image (Tender Coconut with Straw) -->
    <div class="relative w-32 h-32 sm:w-44 sm:h-44 flex-shrink-0 -mr-2">
      <img src="https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80" 
           alt="Fresh Coconut Water" 
           class="w-full h-full object-cover rounded-2xl drop-shadow-2xl border-2 border-white/20 transform hover:scale-105 transition duration-300">
    </div>

    <!-- Pagination dots indicator -->
    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex space-x-1.5 opacity-70">
      <span class="w-2.5 h-1.5 rounded-full bg-white"></span>
      <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
      <span class="w-1.5 h-1.5 rounded-full bg-white/50"></span>
    </div>
  </div>

  <!-- 2. Categories Quick Selector -->
  <div>
    <div class="flex items-center justify-between mb-2.5">
      <h3 class="text-base font-extrabold text-gray-900 tracking-tight">Shop by Category</h3>
      <a href="/category.php" class="text-xs font-bold text-green-700 hover:underline">View All</a>
    </div>
    <div class="flex space-x-3 overflow-x-auto no-scrollbar py-1">
      <a href="/category.php?slug=fresh-fruits" class="flex items-center space-x-2 bg-white border border-gray-200/80 rounded-2xl px-3.5 py-2 shadow-sm flex-shrink-0 hover:border-green-500 transition">
        <span class="text-lg">🍎</span>
        <span class="text-xs font-bold text-gray-800">Fresh Fruits</span>
      </a>
      <a href="/category.php?slug=coconut-water" class="flex items-center space-x-2 bg-green-50 border border-green-300 rounded-2xl px-3.5 py-2 shadow-sm flex-shrink-0 hover:border-green-600 transition">
        <span class="text-lg">🥥</span>
        <span class="text-xs font-bold text-green-900">Coconut & Water</span>
      </a>
      <a href="/category.php?slug=seasonal-fruits" class="flex items-center space-x-2 bg-white border border-gray-200/80 rounded-2xl px-3.5 py-2 shadow-sm flex-shrink-0 hover:border-green-500 transition">
        <span class="text-lg">🥭</span>
        <span class="text-xs font-bold text-gray-800">Seasonal</span>
      </a>
      <a href="/category.php?slug=exotic-berries" class="flex items-center space-x-2 bg-white border border-gray-200/80 rounded-2xl px-3.5 py-2 shadow-sm flex-shrink-0 hover:border-green-500 transition">
        <span class="text-lg">🍓</span>
        <span class="text-xs font-bold text-gray-800">Berries & Exotic</span>
      </a>
    </div>
  </div>

  <!-- 3. Dynamic Horizontally Swipeable Product Sections (Matches Screenshot 2) -->
  <?php if (!empty($sections)): ?>
    <?php foreach ($sections as $section): ?>
      <?php if (!empty($section['products'])): ?>
        <section class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-black text-gray-900 tracking-tight"><?= e($section['title']) ?></h3>
              <?php if (!empty($section['subtitle'])): ?>
                <p class="text-xs text-gray-500"><?= e($section['subtitle']) ?></p>
              <?php endif; ?>
            </div>
            <a href="/category.php?section=<?= e($section['slug']) ?>" class="text-xs font-bold text-green-700 hover:text-green-800 flex items-center space-x-1">
              <span>See All</span>
              <i class="fas fa-chevron-right text-[10px]"></i>
            </a>
          </div>

          <!-- Horizontally Scrollable Cards Container -->
          <div id="carousel-<?= $section['id'] ?>" class="flex space-x-3.5 overflow-x-auto no-scrollbar pb-2 pt-1 -mx-4 px-4 sm:mx-0 sm:px-0">
            <?php foreach ($section['products'] as $prod): 
              $displayPrice = !empty($prod['default_unit_price']) ? (float)$prod['default_unit_price'] : (float)$prod['base_price'];
              $displayUnit = !empty($prod['default_unit_name']) ? $prod['default_unit_name'] : $prod['default_unit'];
              $unitId = !empty($prod['default_unit_id']) ? (int)$prod['default_unit_id'] : null;
            ?>
              <div class="w-36 sm:w-44 flex-shrink-0 bg-white rounded-2xl p-2.5 border border-gray-100 shadow-sm product-card flex flex-col justify-between relative">
                
                <!-- Badge if any -->
                <?php if (!empty($prod['badge_text'])): ?>
                  <span class="absolute top-2 left-2 z-10 bg-green-100 text-green-800 text-[9px] font-bold px-1.5 py-0.5 rounded-md">
                    <?= e($prod['badge_text']) ?>
                  </span>
                <?php endif; ?>

                <!-- Product Image Link -->
                <a href="/product.php?id=<?= $prod['id'] ?>" class="block relative aspect-square w-full rounded-xl overflow-hidden bg-gray-50 mb-2">
                  <img src="<?= e($prod['main_image']) ?>" 
                       alt="<?= e($prod['name']) ?>" 
                       loading="lazy" 
                       class="w-full h-full object-cover rounded-xl transform hover:scale-105 transition duration-200">
                </a>

                <!-- Product Title & Unit -->
                <div class="space-y-1 mb-2">
                  <a href="/product.php?id=<?= $prod['id'] ?>" class="block font-bold text-xs sm:text-sm text-gray-900 leading-snug line-clamp-1 hover:text-green-700">
                    <?= e($prod['name']) ?>
                  </a>
                  <div class="flex items-center text-[11px] text-gray-500 font-medium">
                    <span><?= e($displayUnit) ?></span>
                  </div>
                </div>

                <!-- Price and Quick Add Button -->
                <div class="flex items-center justify-between pt-1 border-t border-gray-50">
                  <div class="flex flex-col">
                    <span class="text-xs sm:text-sm font-extrabold text-gray-900">
                      <?= formatPrice($displayPrice) ?>
                    </span>
                  </div>

                  <button type="button" 
                          onclick="addToCart(<?= $prod['id'] ?>, <?= $unitId ? $unitId : 'null' ?>, 1, this)"
                          class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-green-700 hover:bg-green-800 active:scale-90 text-white flex items-center justify-center transition shadow-sm"
                          title="Add to Cart">
                    <i class="fas fa-plus text-xs"></i>
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
      <a href="/install/index.php" class="inline-block bg-green-700 text-white text-xs font-bold px-4 py-2 rounded-xl">Open Installation Wizard</a>
    </div>
  <?php endif; ?>

  <!-- 4. Announcement / Telegram Banner -->
  <div class="bg-gradient-to-r from-blue-900 to-green-900 text-white p-4 rounded-3xl shadow-sm flex items-center justify-between">
    <div class="space-y-1 max-w-[75%]">
      <span class="text-[10px] bg-blue-500/30 text-blue-200 font-bold px-2 py-0.5 rounded-full uppercase">VIP Fresh Club</span>
      <h4 class="text-sm font-black">Join NA Fresh on Telegram</h4>
      <p class="text-xs text-gray-300">Get morning flash discount codes and daily fresh harvest notices.</p>
    </div>
    <a href="<?= e(getSetting('telegram_channel_url', 'https://t.me/nafreshfruits')) ?>" target="_blank" rel="noopener" class="w-10 h-10 rounded-2xl bg-blue-500 text-white flex items-center justify-center shadow-lg hover:bg-blue-600 transition flex-shrink-0">
      <i class="fab fa-telegram-plane text-xl"></i>
    </a>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
