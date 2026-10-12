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
                   u.id as default_unit_id, u.unit_name as default_unit_name, u.price as default_unit_price, u.original_price as default_original_price
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
    <div class="absolute left-3.5 top-3 text-gray-400">
      <?= renderIcon('search', 'w-4 h-4 text-gray-400') ?>
    </div>
    <input type="search" 
           name="q" 
           value="<?= e($searchQuery) ?>" 
           placeholder="Search fresh mango, tender coconut, apple..." 
           class="w-full bg-white border border-gray-200 rounded-2xl pl-10 pr-10 py-2.5 text-xs sm:text-sm font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 shadow-xs transition">
    <?php if ($searchQuery): ?>
      <a href="/category.php<?= $selectedCategorySlug ? '?slug=' . urlencode($selectedCategorySlug) : '' ?>" class="absolute right-3.5 top-3 text-gray-400 hover:text-gray-600">
        <?= renderIcon('cross', 'w-4 h-4 text-gray-400') ?>
      </a>
    <?php endif; ?>
  </form>

  <!-- Categories Horizontal Scroll Bar -->
  <div class="flex space-x-2 overflow-x-auto no-scrollbar py-1">
    <a href="/category.php<?= $searchQuery ? '?q=' . urlencode($searchQuery) : '' ?>" 
       class="flex-shrink-0 px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= empty($selectedCategorySlug) ? 'bg-emerald-800 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-150 hover:border-emerald-500' ?>">
      All Items
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="/category.php?slug=<?= urlencode($cat['slug']) ?><?= $searchQuery ? '&q=' . urlencode($searchQuery) : '' ?>" 
         class="flex-shrink-0 px-3.5 py-1.5 rounded-xl text-xs font-bold transition <?= ($selectedCategorySlug === $cat['slug']) ? 'bg-emerald-800 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-150 hover:border-emerald-500' ?>">
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
      <select onchange="location = this.value;" class="bg-white border border-gray-200 rounded-xl py-1 px-2.5 text-xs font-bold text-gray-700 focus:outline-none focus:border-emerald-600">
        <option value="?slug=<?= urlencode($selectedCategorySlug) ?>&q=<?= urlencode($searchQuery) ?>&sort=popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Popular</option>
        <option value="?slug=<?= urlencode($selectedCategorySlug) ?>&q=<?= urlencode($searchQuery) ?>&sort=price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
        <option value="?slug=<?= urlencode($selectedCategorySlug) ?>&q=<?= urlencode($searchQuery) ?>&sort=price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        <option value="?slug=<?= urlencode($selectedCategorySlug) ?>&q=<?= urlencode($searchQuery) ?>&sort=rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
      </select>
    </div>
  </div>

  <!-- Product Grid -->
  <?php if (empty($products)): ?>
    <div class="bg-white rounded-3xl p-8 border border-gray-150 text-center my-6 space-y-3">
      <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto text-xl">
        <?= renderIcon('search', 'w-6 h-6 text-emerald-700') ?>
      </div>
      <h3 class="text-base font-bold text-gray-900">No items match your search</h3>
      <p class="text-xs text-gray-500 max-w-xs mx-auto">Try checking for different keywords like mango, coconut, or strawberry.</p>
      <div class="pt-2">
        <a href="/category.php" class="inline-block bg-emerald-800 text-white font-bold text-xs px-4 py-2 rounded-xl">Clear Filters</a>
      </div>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
      <?php foreach ($products as $prod): 
        $displayPrice = !empty($prod['default_unit_price']) ? (float)$prod['default_unit_price'] : (float)$prod['base_price'];
        $originalPrice = !empty($prod['default_original_price']) ? (float)$prod['default_original_price'] : null;
        $displayUnit = !empty($prod['default_unit_name']) ? $prod['default_unit_name'] : $prod['default_unit'];
        $unitId = !empty($prod['default_unit_id']) ? (int)$prod['default_unit_id'] : null;
      ?>
        <div class="product-card">
          
          <!-- Bounded Image Wrap + Badge + Favorite Icon -->
          <div class="product-card-img-wrap">
            <?php if (!empty($prod['badge_text'])): ?>
              <span class="product-card-badge"><?= e($prod['badge_text']) ?></span>
            <?php endif; ?>

            <button type="button" 
                    onclick="toggleFavorite(<?= $prod['id'] ?>, this)" 
                    class="product-card-fav btn-favorite"
                    title="Save to favorites">
              <?= renderIcon('heart', 'w-3.5 h-3.5') ?>
            </button>

            <a href="/product.php?id=<?= $prod['id'] ?>" class="w-full h-full flex items-center justify-center p-1">
              <img src="<?= e($prod['main_image']) ?>" alt="<?= e($prod['name']) ?>" loading="lazy">
            </a>
          </div>

          <!-- Product Details -->
          <div class="product-card-info">
            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider truncate"><?= e($prod['category_name']) ?></div>
            <a href="/product.php?id=<?= $prod['id'] ?>" class="product-card-title" title="<?= e($prod['name']) ?>">
              <?= e($prod['name']) ?>
            </a>
            <div class="product-card-unit"><?= e($displayUnit) ?></div>

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

          <div class="product-card-actions">
            <button type="button" 
                    onclick="addToCart(<?= $prod['id'] ?>, <?= $unitId ? $unitId : 'null' ?>, 1, this)"
                    class="product-card-add-btn w-full" 
                    title="Add to Cart">
              <?= renderIcon('shopping-cart', 'w-3 h-3 text-white') ?>
              <span>Add</span>
            </button>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

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
