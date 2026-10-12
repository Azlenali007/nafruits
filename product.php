<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Product Details View (Matches Screenshot 3)
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$productUnits = [];
$galleryImages = [];
$relatedProducts = [];

if ($productId > 0) {
    try {
        $product = Database::fetchOne(
            "SELECT p.*, c.name as category_name, c.slug as category_slug 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.id = ? AND p.status = 'active'",
            [$productId]
        );

        if ($product) {
            $productUnits = Database::fetchAll(
                "SELECT * FROM product_units WHERE product_id = ? ORDER BY is_default DESC, price ASC",
                [$productId]
            );

            $galleryImages = Database::fetchAll(
                "SELECT image_url FROM product_images WHERE product_id = ? ORDER BY display_order ASC",
                [$productId]
            );

            $relatedProducts = Database::fetchAll(
                "SELECT id, name, slug, main_image, base_price, default_unit FROM products 
                 WHERE category_id = ? AND id != ? AND status = 'active' LIMIT 6",
                [$product['category_id'], $productId]
            );
        }
    } catch (Exception $e) {
        error_log('Error fetching product: ' . $e->getMessage());
    }
}

if (!$product) {
    setFlash('error', 'Product not found or currently unavailable.');
    header('Location: /index.php');
    exit;
}

$pageTitle = $product['name'] . ' – Na Fresh';
require_once __DIR__ . '/includes/header.php';

$defaultUnit = !empty($productUnits) ? $productUnits[0] : null;
$initialPrice = $defaultUnit ? (float)$defaultUnit['price'] : (float)$product['base_price'];
$initialUnitName = $defaultUnit ? $defaultUnit['unit_name'] : $product['default_unit'];
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-3 pb-8 space-y-4">

  <!-- Top Navigation Bar: Back, Wishlist, Share (Matching Screenshot 3) -->
  <div class="flex items-center justify-between">
    <a href="javascript:history.back()" class="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-700 hover:bg-gray-100 transition shadow-sm">
      <i class="fas fa-arrow-left text-sm"></i>
    </a>
    <div class="flex items-center space-x-2">
      <button onclick="showToast('Added to favourites', 'success')" class="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:text-red-500 hover:bg-gray-100 transition shadow-sm">
        <i class="far fa-heart text-sm"></i>
      </button>
      <button onclick="if(navigator.share){navigator.share({title:'<?= e($product['name']) ?>',url:window.location.href})}else{showToast('Link copied to clipboard')}" class="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:text-green-600 hover:bg-gray-100 transition shadow-sm">
        <i class="fas fa-share-alt text-sm"></i>
      </button>
    </div>
  </div>

  <!-- Big Hero Product Image Card -->
  <div class="bg-white rounded-3xl p-4 border border-gray-100 shadow-sm relative overflow-hidden flex flex-col items-center">
    <div class="w-full aspect-square max-h-72 sm:max-h-96 rounded-2xl overflow-hidden bg-gray-50 flex items-center justify-center">
      <img id="main-product-img" 
           src="<?= e($product['main_image']) ?>" 
           alt="<?= e($product['name']) ?>" 
           class="w-full h-full object-cover rounded-2xl">
    </div>

    <!-- Thumbnail Gallery -->
    <?php if (!empty($galleryImages)): ?>
      <div class="flex items-center space-x-2.5 mt-3 overflow-x-auto no-scrollbar w-full justify-center">
        <button onclick="document.getElementById('main-product-img').src='<?= e($product['main_image']) ?>'" class="w-14 h-14 rounded-xl border-2 border-green-600 overflow-hidden focus:outline-none p-0.5">
          <img src="<?= e($product['main_image']) ?>" class="w-full h-full object-cover rounded-lg">
        </button>
        <?php foreach ($galleryImages as $img): ?>
          <button onclick="document.getElementById('main-product-img').src='<?= e($img['image_url']) ?>'" class="w-14 h-14 rounded-xl border border-gray-200 overflow-hidden hover:border-green-500 focus:border-green-600 focus:outline-none p-0.5">
            <img src="<?= e($img['image_url']) ?>" class="w-full h-full object-cover rounded-lg">
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Product Title, Rating & Price Row (Matching Screenshot 3) -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
    
    <div>
      <div class="flex items-center space-x-2 text-xs font-bold text-gray-500 mb-1">
        <span class="text-green-700 uppercase tracking-wider"><?= e($product['category_name']) ?></span>
        <span>•</span>
        <span>SKU: <?= e($product['sku']) ?></span>
      </div>

      <h1 class="text-2xl font-black text-gray-900 tracking-tight leading-tight">
        <?= e($product['name']) ?>
      </h1>

      <!-- Rating info: 4.8 (2.4k reviews) -->
      <div class="flex items-center space-x-1.5 mt-1.5 text-xs text-gray-500">
        <i class="fas fa-star text-amber-400"></i>
        <span class="font-extrabold text-gray-800"><?= number_format((float)$product['rating'], 1) ?></span>
        <span>(<?= number_format((int)$product['review_count']) ?> reviews)</span>
      </div>
    </div>

    <!-- Price & Badges -->
    <div class="flex items-center space-x-3 pt-1">
      <div class="flex items-baseline space-x-1">
        <span id="displayed-price" class="text-3xl font-black text-green-900">
          <?= formatPrice($initialPrice) ?>
        </span>
        <span id="displayed-unit" class="text-xs font-semibold text-gray-500">
          / <?= e($initialUnitName) ?>
        </span>
      </div>
      
      <span class="bg-green-100 text-green-800 text-xs font-extrabold px-3 py-1 rounded-full flex items-center space-x-1">
        <i class="fas fa-leaf text-[10px]"></i>
        <span><?= e($product['badge_text'] ?: '100% Natural') ?></span>
      </span>
    </div>

    <!-- Description -->
    <div class="text-xs sm:text-sm text-gray-600 leading-relaxed border-t border-b border-gray-100 py-3">
      <?= nl2br(e($product['description'] ?: $product['short_description'])) ?>
    </div>

    <!-- Unit / Pack Size Selection (Prices associated with selected unit) -->
    <?php if (!empty($productUnits)): ?>
      <div class="space-y-2">
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Select Pack / Unit:</label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
          <?php foreach ($productUnits as $idx => $u): ?>
            <button type="button" 
                    onclick="selectUnit(<?= $u['id'] ?>, <?= $u['price'] ?>, '<?= e($u['unit_name']) ?>', this)"
                    class="unit-option-btn text-left p-2.5 rounded-xl border text-xs font-medium transition <?= $idx === 0 ? 'border-green-600 bg-green-50 text-green-900 font-bold' : 'border-gray-200 text-gray-700 hover:border-gray-300' ?>">
              <div class="font-bold"><?= e($u['unit_name']) ?></div>
              <div class="text-green-700 font-extrabold text-xs mt-0.5"><?= formatPrice($u['price']) ?></div>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Quantity & Add to Cart Controls (Matching Screenshot 3) -->
    <div class="pt-2 flex items-center space-x-3">
      <!-- Quantity Counter -->
      <div class="flex items-center bg-gray-100 rounded-2xl p-1 border border-gray-200">
        <button type="button" class="btn-qty-minus w-9 h-9 rounded-xl bg-white text-gray-700 font-bold shadow-sm hover:bg-gray-50 flex items-center justify-center transition">
          <i class="fas fa-minus text-xs"></i>
        </button>
        <input type="number" id="product-qty" value="1" min="1" max="50" class="qty-input w-12 text-center bg-transparent font-extrabold text-sm text-gray-800 focus:outline-none" readonly>
        <button type="button" class="btn-qty-plus w-9 h-9 rounded-xl bg-white text-gray-700 font-bold shadow-sm hover:bg-gray-50 flex items-center justify-center transition">
          <i class="fas fa-plus text-xs"></i>
        </button>
      </div>

      <!-- Add to Cart Full Button -->
      <button type="button" 
              id="main-add-cart-btn"
              onclick="submitProductAddToCart(this)"
              class="flex-1 bg-green-700 hover:bg-green-800 text-white font-extrabold py-3.5 px-6 rounded-2xl shadow-md transition transform active:scale-95 flex items-center justify-center space-x-2 text-sm sm:text-base">
        <i class="fas fa-shopping-bag"></i>
        <span>Add to Cart</span>
      </button>
    </div>

    <!-- Three Value Badges (Matching Screenshot 3) -->
    <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-100 text-center">
      <div class="p-2 rounded-xl bg-green-50/50 flex flex-col items-center">
        <i class="fas fa-seedling text-green-600 text-base mb-1"></i>
        <span class="text-[11px] font-bold text-gray-800">100% Natural</span>
      </div>
      <div class="p-2 rounded-xl bg-green-50/50 flex flex-col items-center">
        <i class="fas fa-shield-alt text-green-600 text-base mb-1"></i>
        <span class="text-[11px] font-bold text-gray-800">Fresh & Hygienic</span>
      </div>
      <div class="p-2 rounded-xl bg-green-50/50 flex flex-col items-center">
        <i class="fas fa-bolt text-green-600 text-base mb-1"></i>
        <span class="text-[11px] font-bold text-gray-800">Boosts Energy</span>
      </div>
    </div>

  </div>

  <!-- Related Products Section -->
  <?php if (!empty($relatedProducts)): ?>
    <div class="pt-2">
      <h3 class="text-base font-black text-gray-900 mb-3 tracking-tight">You May Also Like</h3>
      <div class="flex space-x-3 overflow-x-auto no-scrollbar pb-2">
        <?php foreach ($relatedProducts as $rel): ?>
          <a href="/product.php?id=<?= $rel['id'] ?>" class="w-32 flex-shrink-0 bg-white p-2.5 rounded-2xl border border-gray-100 shadow-sm block text-center hover:border-green-500 transition">
            <img src="<?= e($rel['main_image']) ?>" class="w-full aspect-square object-cover rounded-xl mb-1.5">
            <div class="text-xs font-bold text-gray-800 truncate"><?= e($rel['name']) ?></div>
            <div class="text-xs font-extrabold text-green-800 mt-0.5"><?= formatPrice($rel['base_price']) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<script>
let currentSelectedUnitId = <?= $defaultUnit ? $defaultUnit['id'] : 'null' ?>;

function selectUnit(unitId, price, unitName, btnElement) {
  currentSelectedUnitId = unitId;
  document.getElementById('displayed-price').textContent = '₹ ' + price;
  document.getElementById('displayed-unit').textContent = '/ ' + unitName;
  
  document.querySelectorAll('.unit-option-btn').forEach(btn => {
    btn.classList.remove('border-green-600', 'bg-green-50', 'text-green-900', 'font-bold');
    btn.classList.add('border-gray-200', 'text-gray-700');
  });
  
  btnElement.classList.add('border-green-600', 'bg-green-50', 'text-green-900', 'font-bold');
  btnElement.classList.remove('border-gray-200', 'text-gray-700');
}

function submitProductAddToCart(btn) {
  const qty = parseFloat(document.getElementById('product-qty').value) || 1;
  addToCart(<?= $product['id'] ?>, currentSelectedUnitId, qty, btn);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
