<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Category & Product Search / Browse
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$searchQuery = trim($_GET['q'] ?? '');
$selectedCategorySlug = trim($_GET['slug'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'popular'); // popular, price_asc, price_desc, rating

$categories = [];
$products = [];
$selectedCategory = null;

try {
    $categories = Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC");

    if (!empty($selectedCategorySlug)) {
        $selectedCategory = Database::fetchOne("SELECT * FROM categories WHERE slug = ? AND is_active = 1", [$selectedCategorySlug]);
    }

    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, 
                   u.id as default_unit_id, u.unit_name as default_unit_name, u.price as default_unit_price
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_units u ON (u.product_id = p.id AND u.is_default = 1)
            WHERE p.status = 'active'";
    $params = [];

    if (!empty($searchQuery)) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
        $searchTerm = '%' . $searchQuery . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($selectedCategory) {
        $sql .= " AND p.category_id = ?";
        $params[] = $selectedCategory['id'];
    }

    // Sorting
    switch ($sortBy) {
        case 'price_asc':
            $sql .= " ORDER BY COALESCE(u.price, p.base_price) ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY COALESCE(u.price, p.base_price) DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY p.rating DESC, p.review_count DESC";
            break;
        case 'popular':
        default:
            $sql .= " ORDER BY p.is_bestseller DESC, p.is_featured DESC, p.id DESC";
            break;
    }

    $products = Database::fetchAll($sql, $params);
} catch (Exception $e) {
    error_log('Category search error: ' . $e->getMessage());
}

$pageTitle = ($selectedCategory ? $selectedCategory['name'] : 'All Fruits & Coconuts') . ' – Na Fresh';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-3 pb-8 space-y-4">

  <!-- Top Search Input Bar -->
  <form action="/category.php" method="GET" class="relative">
    <?php if ($selectedCategorySlug): ?>
      <input type="hidden" name="slug" value="<?= e($selectedCategorySlug) ?>">
    <?php endif; ?>
    <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400 text-sm"></i>
    <input type="search" 
           name="q" 
           value="<?= e($searchQuery) ?>" 
           placeholder="Search fresh mango, tender coconut, apple..." 
           class="w-full bg-white border border-gray-200 rounded-2xl pl-10 pr-10 py-2.5 text-xs sm:text-sm font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:border-green-600 focus:ring-1 focus:ring-green-600 shadow-sm transition">
    <?php if ($searchQuery): ?>
      <a href="/category.php<?= $selectedCategorySlug ? '?slug=' . urlencode($selectedCategorySlug) : '' ?>" class="absolute right-3.5 top-3 text-gray-400 hover:text-gray-600">
        <i class="fas fa-times-circle"></i>
      </a>
    <?php endif; ?>
  </form>

  <!-- Categories Horizontal Scroll Bar -->
  <div class="flex space-x-2 overflow-x-auto no-scrollbar py-1">
    <a href="/category.php<?= $searchQuery ? '?q=' . urlencode($searchQuery) : '' ?>" 
       class="flex-shrink-0 px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= empty($selectedCategorySlug) ? 'bg-green-700 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:border-green-500' ?>">
      All Items
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="/category.php?slug=<?= urlencode($cat['slug']) ?><?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>" 
         class="flex-shrink-0 px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= ($selectedCategorySlug === $cat['slug']) ? 'bg-green-700 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:border-green-500' ?>">
        <?= e($cat['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Sort and Count Header -->
  <div class="flex items-center justify-between pt-1">
    <span class="text-xs font-bold text-gray-500">
      Showing <?= count($products) ?> fresh item<?= count($products) === 1 ? '' : 's' ?>
    </span>

    <div class="flex items-center space-x-1.5 text-xs">
      <span class="text-gray-500 font-medium">Sort:</span>
      <select onchange="location = this.value;" class="bg-white border border-gray-200 rounded-lg py-1 px-2 text-xs font-bold text-gray-700 focus:outline-none focus:border-green-600">
        <option value="?slug=<?= urlencode($selectedCategorySlug) ?>&q=<?= urlencode($searchQuery) ?>&sort=popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Popular</option>
        <option value="?slug=<?= urlencode($selectedCategorySlug) ?>&q=<?= urlencode($searchQuery) ?>&sort=price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
        <option value="?slug=<?= urlencode($selectedCategorySlug) ?>&q=<?= urlencode($searchQuery) ?>&sort=price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        <option value="?slug=<?= urlencode($selectedCategorySlug) ?>&q=<?= urlencode($searchQuery) ?>&sort=rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
      </select>
    </div>
  </div>

  <!-- Product Grid -->
  <?php if (empty($products)): ?>
    <div class="bg-white rounded-3xl p-8 border border-gray-100 text-center my-6 space-y-3">
      <div class="text-4xl">🔍</div>
      <h3 class="text-base font-bold text-gray-900">No items match your search</h3>
      <p class="text-xs text-gray-500 max-w-xs mx-auto">Try checking for different keywords like mango, coconut, or strawberry.</p>
      <div class="pt-2">
        <a href="/category.php" class="inline-block bg-green-700 text-white font-bold text-xs px-4 py-2 rounded-xl">Clear Filters</a>
      </div>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
      <?php foreach ($products as $prod): 
        $displayPrice = !empty($prod['default_unit_price']) ? (float)$prod['default_unit_price'] : (float)$prod['base_price'];
        $displayUnit = !empty($prod['default_unit_name']) ? $prod['default_unit_name'] : $prod['default_unit'];
        $unitId = !empty($prod['default_unit_id']) ? (int)$prod['default_unit_id'] : null;
      ?>
        <div class="bg-white rounded-2xl p-3 border border-gray-100 shadow-sm product-card flex flex-col justify-between relative">
          
          <?php if (!empty($prod['badge_text'])): ?>
            <span class="absolute top-2.5 left-2.5 z-10 bg-green-100 text-green-800 text-[9px] font-bold px-1.5 py-0.5 rounded-md">
              <?= e($prod['badge_text']) ?>
            </span>
          <?php endif; ?>

          <a href="/product.php?id=<?= $prod['id'] ?>" class="block relative aspect-square w-full rounded-xl overflow-hidden bg-gray-50 mb-2">
            <img src="<?= e($prod['main_image']) ?>" alt="<?= e($prod['name']) ?>" loading="lazy" class="w-full h-full object-cover rounded-xl">
          </a>

          <div class="space-y-1 mb-2">
            <div class="text-[10px] text-gray-400 font-bold uppercase truncate"><?= e($prod['category_name']) ?></div>
            <a href="/product.php?id=<?= $prod['id'] ?>" class="block font-bold text-xs sm:text-sm text-gray-900 leading-snug line-clamp-1 hover:text-green-700">
              <?= e($prod['name']) ?>
            </a>
            <div class="text-[11px] text-gray-500 font-medium"><?= e($displayUnit) ?></div>
          </div>

          <div class="flex items-center justify-between pt-1 border-t border-gray-50">
            <span class="text-xs sm:text-sm font-black text-gray-900"><?= formatPrice($displayPrice) ?></span>
            <button type="button" 
                    onclick="addToCart(<?= $prod['id'] ?>, <?= $unitId ? $unitId : 'null' ?>, 1, this)"
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-green-700 hover:bg-green-800 text-white flex items-center justify-center transition shadow-sm active:scale-90" 
                    title="Add to Cart">
              <i class="fas fa-plus text-xs"></i>
            </button>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
