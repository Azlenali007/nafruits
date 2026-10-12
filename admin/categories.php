<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Categories & Homepage Carousel Manager
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Categories & Homepage Layout – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    requireCsrf();
    $name = trim($_POST['name'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    $desc = trim($_POST['description'] ?? '');
    $img = trim($_POST['image'] ?? 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?auto=format&fit=crop&w=600&q=80');

    if (!empty($name)) {
        Database::insert(
            "INSERT INTO categories (name, slug, description, image, is_active) VALUES (?, ?, ?, ?, 1)",
            [$name, $slug, $desc, $img]
        );
        logAuditAction((int)$_SESSION['admin_id'], 'category_create', "Added category: {$name}");
        setFlash('success', "Category '{$name}' created.");
    }
    header('Location: /admin/categories.php');
    exit;
}

// Handle Add Homepage Section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    requireCsrf();
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-')) . '-sec';
    $order = (int)($_POST['display_order'] ?? 0);

    if (!empty($title)) {
        Database::insert(
            "INSERT INTO homepage_sections (title, slug, subtitle, section_type, display_order, is_active) VALUES (?, ?, ?, 'carousel', ?, 1)",
            [$title, $slug, $subtitle, $order]
        );
        logAuditAction((int)$_SESSION['admin_id'], 'section_create', "Added homepage section: {$title}");
        setFlash('success', "Homepage section '{$title}' created.");
    }
    header('Location: /admin/categories.php');
    exit;
}

$categories = Database::fetchAll("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.display_order ASC");
$sections = Database::fetchAll("SELECT * FROM homepage_sections ORDER BY display_order ASC");
?>

<div class="space-y-6">

  <div>
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Categories & Homepage Sections</h1>
    <p class="text-xs text-gray-500">Organize fruit classifications and live swipeable carousels on the storefront.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Categories Column -->
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-black text-gray-900">Categories</h2>
        <span class="text-xs text-gray-400"><?= count($categories) ?> total</span>
      </div>

      <!-- Add Category Form -->
      <form action="/admin/categories.php" method="POST" class="p-3 bg-gray-50 rounded-2xl border border-gray-200 space-y-2 text-xs">
        <?= csrfField() ?>
        <div class="font-bold text-gray-700">Add New Category:</div>
        <div class="grid grid-cols-2 gap-2">
          <input type="text" name="name" required placeholder="Category Name" class="bg-white border border-gray-300 rounded-xl p-2 text-xs">
          <input type="url" name="image" placeholder="Image URL (optional)" class="bg-white border border-gray-300 rounded-xl p-2 text-xs">
        </div>
        <button type="submit" name="add_category" value="1" class="w-full bg-green-700 hover:bg-green-800 text-white font-bold p-2 rounded-xl">
          Save Category
        </button>
      </form>

      <div class="divide-y divide-gray-100 text-xs">
        <?php foreach ($categories as $c): ?>
          <div class="py-2.5 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
              <img src="<?= e($c['image']) ?>" class="w-9 h-9 rounded-xl object-cover bg-gray-50">
              <div>
                <div class="font-extrabold text-gray-900"><?= e($c['name']) ?></div>
                <div class="text-[10px] text-gray-400"><?= (int)$c['product_count'] ?> products</div>
              </div>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $c['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' ?>">
              <?= $c['is_active'] ? 'Active' : 'Disabled' ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Homepage Sections Column -->
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-black text-gray-900">Homepage Swipeable Sections</h2>
        <span class="text-xs text-gray-400"><?= count($sections) ?> carousels</span>
      </div>

      <!-- Add Section Form -->
      <form action="/admin/categories.php" method="POST" class="p-3 bg-gray-50 rounded-2xl border border-gray-200 space-y-2 text-xs">
        <?= csrfField() ?>
        <div class="font-bold text-gray-700">Add Homepage Section:</div>
        <input type="text" name="title" required placeholder="Section Title (e.g. Fresh Fruits, Popular)" class="w-full bg-white border border-gray-300 rounded-xl p-2 text-xs">
        <input type="text" name="subtitle" placeholder="Subtitle" class="w-full bg-white border border-gray-300 rounded-xl p-2 text-xs">
        <button type="submit" name="add_section" value="1" class="w-full bg-green-700 hover:bg-green-800 text-white font-bold p-2 rounded-xl">
          Add Carousel Section
        </button>
      </form>

      <div class="divide-y divide-gray-100 text-xs">
        <?php foreach ($sections as $s): ?>
          <div class="py-2.5 flex items-center justify-between">
            <div>
              <div class="font-extrabold text-gray-900"><?= e($s['title']) ?></div>
              <div class="text-[10px] text-gray-400"><?= e($s['subtitle'] ?: 'Horizontal swipeable carousel') ?></div>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">
              Carousel Active
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
