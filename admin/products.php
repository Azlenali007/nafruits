<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Admin Product Management (CRUD, Pack Units, Prices & Stock)
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Products & Pack Units – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

$action = $_GET['action'] ?? 'list';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$categories = Database::fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");

// Handle Product Save (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    requireCsrf();

    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $sku = trim($_POST['sku'] ?? ('NF-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6))));
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    $costPrice = (float)($_POST['cost_price'] ?? 0.0);
    $basePrice = (float)($_POST['base_price'] ?? 0.0);
    $defaultUnit = trim($_POST['default_unit'] ?? 'kg');
    $stockQty = (float)($_POST['stock_quantity'] ?? 0);
    $lowStockThreshold = (float)($_POST['low_stock_threshold'] ?? 5);
    $badgeText = trim($_POST['badge_text'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $imageUrl = trim($_POST['main_image'] ?? 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?auto=format&fit=crop&w=600&q=80');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isBestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';

    if ($productId > 0) {
        // Update product
        Database::execute(
            "UPDATE products SET name = ?, category_id = ?, cost_price = ?, base_price = ?, default_unit = ?, 
                    stock_quantity = ?, low_stock_threshold = ?, badge_text = ?, description = ?, main_image = ?, 
                    is_featured = ?, is_bestseller = ?, status = ? WHERE id = ?",
            [$name, $categoryId, $costPrice, $basePrice, $defaultUnit, $stockQty, $lowStockThreshold, $badgeText, $description, $imageUrl, $isFeatured, $isBestseller, $status, $productId]
        );
        logAuditAction((int)$_SESSION['admin_id'], 'product_update', "Updated product ID {$productId}: {$name}");
        setFlash('success', "Product '{$name}' updated successfully.");
    } else {
        // Create new product
        $newId = Database::insert(
            "INSERT INTO products (category_id, sku, name, slug, main_image, cost_price, base_price, default_unit, stock_quantity, low_stock_threshold, badge_text, description, is_featured, is_bestseller, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$categoryId, $sku, $name, $slug, $imageUrl, $costPrice, $basePrice, $defaultUnit, $stockQty, $lowStockThreshold, $badgeText, $description, $isFeatured, $isBestseller, $status]
        );
        $productId = (int)$newId;

        // Create default unit entry
        Database::insert(
            "INSERT INTO product_units (product_id, unit_name, price, stock_quantity, is_default) VALUES (?, ?, ?, ?, 1)",
            [$productId, '1 ' . $defaultUnit, $basePrice, $stockQty]
        );

        logAuditAction((int)$_SESSION['admin_id'], 'product_create', "Created product ID {$productId}: {$name}");
        setFlash('success', "Product '{$name}' created successfully.");
    }

    header('Location: /admin/products.php');
    exit;
}

// Handle Delete Product
if ($action === 'delete' && $productId > 0) {
    requireCsrf();
    Database::execute("UPDATE products SET status = 'inactive' WHERE id = ?", [$productId]);
    logAuditAction((int)$_SESSION['admin_id'], 'product_delete', "Deactivated product ID {$productId}");
    setFlash('info', 'Product set to inactive.');
    header('Location: /admin/products.php');
    exit;
}

// Edit Form Data if edit requested
$editProduct = null;
$productUnits = [];
if ($action === 'edit' && $productId > 0) {
    $editProduct = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
    $productUnits = Database::fetchAll("SELECT * FROM product_units WHERE product_id = ?", [$productId]);
}

// Fetch all products
$products = Database::fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     ORDER BY p.id DESC"
);
?>

<div class="space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-black text-gray-900 tracking-tight">Products & Pack Sizes</h1>
      <p class="text-xs text-gray-500">Manage catalog, units (kg, 500g, pc), inventory, and selling prices.</p>
    </div>
    <?php if ($action !== 'create' && $action !== 'edit'): ?>
      <a href="/admin/products.php?action=create" class="bg-green-700 hover:bg-green-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition">
        <i class="fas fa-plus mr-1"></i> Add New Fruit
      </a>
    <?php else: ?>
      <a href="/admin/products.php" class="bg-white border border-gray-300 text-gray-700 font-bold text-xs px-4 py-2.5 rounded-xl hover:bg-gray-50 transition">
        Cancel
      </a>
    <?php endif; ?>
  </div>

  <!-- Create / Edit Product Form Panel -->
  <?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
      <h2 class="text-lg font-black text-gray-900">
        <?= $action === 'edit' ? 'Edit Product: ' . e($editProduct['name'] ?? '') : 'Add New Product' ?>
      </h2>

      <form action="/admin/products.php<?= $productId ? '?id=' . $productId : '' ?>" method="POST" class="space-y-4">
        <?= csrfField() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Product Name *</label>
            <input type="text" name="name" required value="<?= e($editProduct['name'] ?? '') ?>" placeholder="e.g. Mango (Alphonso) or Fresh Coconut Water" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Category *</label>
            <select name="category_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (!empty($editProduct) && $editProduct['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Purchase Cost Price (₹)</label>
            <input type="number" step="0.5" name="cost_price" value="<?= e($editProduct['cost_price'] ?? '0.00') ?>" placeholder="e.g. 75.00" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Selling Base Price (₹) *</label>
            <input type="number" step="0.5" name="base_price" required value="<?= e($editProduct['base_price'] ?? '0.00') ?>" placeholder="e.g. 120.00" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Default Unit *</label>
            <select name="default_unit" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
              <option value="kg" <?= (!empty($editProduct) && $editProduct['default_unit'] === 'kg') ? 'selected' : '' ?>>kg</option>
              <option value="500 g" <?= (!empty($editProduct) && $editProduct['default_unit'] === '500 g') ? 'selected' : '' ?>>500 g</option>
              <option value="250 g" <?= (!empty($editProduct) && $editProduct['default_unit'] === '250 g') ? 'selected' : '' ?>>250 g</option>
              <option value="piece" <?= (!empty($editProduct) && $editProduct['default_unit'] === 'piece') ? 'selected' : '' ?>>piece / whole</option>
              <option value="pack" <?= (!empty($editProduct) && $editProduct['default_unit'] === 'pack') ? 'selected' : '' ?>>pack / bottle</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Current Stock Quantity</label>
            <input type="number" step="1" name="stock_quantity" value="<?= e($editProduct['stock_quantity'] ?? '50') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Low Stock Alert Threshold</label>
            <input type="number" step="1" name="low_stock_threshold" value="<?= e($editProduct['low_stock_threshold'] ?? '5') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Badge Text (Optional)</label>
            <input type="text" name="badge_text" value="<?= e($editProduct['badge_text'] ?? '100% Natural') ?>" placeholder="e.g. 100% Natural, Sweet & Ripe" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Product Main Image URL</label>
          <input type="url" name="main_image" value="<?= e($editProduct['main_image'] ?? 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Description</label>
          <textarea name="description" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600"><?= e($editProduct['description'] ?? '') ?></textarea>
        </div>

        <div class="flex items-center space-x-6 text-xs font-bold text-gray-700 pt-2">
          <label class="flex items-center space-x-2">
            <input type="checkbox" name="is_featured" value="1" <?= (!empty($editProduct) && $editProduct['is_featured']) ? 'checked' : '' ?> class="rounded text-green-600">
            <span>Featured Product</span>
          </label>
          <label class="flex items-center space-x-2">
            <input type="checkbox" name="is_bestseller" value="1" <?= (!empty($editProduct) && $editProduct['is_bestseller']) ? 'checked' : '' ?> class="rounded text-green-600">
            <span>Best Seller</span>
          </label>
        </div>

        <div class="pt-3 border-t border-gray-100 flex items-center justify-end space-x-3">
          <a href="/admin/products.php" class="px-4 py-2 border rounded-xl text-xs text-gray-600">Cancel</a>
          <button type="submit" name="save_product" value="1" class="bg-green-700 hover:bg-green-800 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-md">
            Save Product
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- Products List Table -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
          <tr>
            <th class="py-3 px-3">Product</th>
            <th class="py-3 px-3">Category</th>
            <th class="py-3 px-3">Price / Unit</th>
            <th class="py-3 px-3">Cost</th>
            <th class="py-3 px-3">Stock</th>
            <th class="py-3 px-3">Status</th>
            <th class="py-3 px-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <?php foreach ($products as $p): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-3 px-3">
                <div class="flex items-center space-x-2.5">
                  <img src="<?= e($p['main_image']) ?>" class="w-10 h-10 rounded-xl object-cover bg-gray-50 flex-shrink-0">
                  <div>
                    <div class="font-extrabold text-gray-900"><?= e($p['name']) ?></div>
                    <div class="text-[10px] text-gray-400">SKU: <?= e($p['sku']) ?></div>
                  </div>
                </div>
              </td>
              <td class="py-3 px-3 font-medium text-gray-600"><?= e($p['category_name']) ?></td>
              <td class="py-3 px-3 font-black text-gray-900">
                <?= formatPrice($p['base_price']) ?> <span class="text-[10px] text-gray-400 font-normal">/ <?= e($p['default_unit']) ?></span>
              </td>
              <td class="py-3 px-3 text-gray-500"><?= formatPrice($p['cost_price']) ?></td>
              <td class="py-3 px-3">
                <span class="font-extrabold <?= (float)$p['stock_quantity'] <= (float)$p['low_stock_threshold'] ? 'text-red-600' : 'text-green-800' ?>">
                  <?= (int)$p['stock_quantity'] ?> <?= e($p['default_unit']) ?>
                </span>
              </td>
              <td class="py-3 px-3">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase <?= $p['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>">
                  <?= e($p['status']) ?>
                </span>
              </td>
              <td class="py-3 px-3 text-right space-x-2">
                <a href="/admin/products.php?action=edit&id=<?= $p['id'] ?>" class="text-xs font-bold text-green-700 hover:underline">Edit</a>
                <a href="/product.php?id=<?= $p['id'] ?>" target="_blank" class="text-xs font-bold text-gray-400 hover:text-gray-600">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
