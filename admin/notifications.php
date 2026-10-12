<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Announcements, Popups & Telegram Broadcasts
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Announcements & Popups – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Handle Add / Edit Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_announcement'])) {
    requireCsrf();

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'popup'; // popup, banner, telegram, seasonal
    $targetUrl = trim($_POST['target_url'] ?? '');
    $buttonText = trim($_POST['action_button_text'] ?? 'Explore Now');

    if (!empty($title) && !empty($message)) {
        Database::insert(
            "INSERT INTO announcements (title, message, type, target_url, action_button_text, is_active) 
             VALUES (?, ?, ?, ?, ?, 1)",
            [$title, $message, $type, $targetUrl, $buttonText]
        );
        logAuditAction((int)$_SESSION['admin_id'], 'announcement_save', "Added announcement: {$title}");
        setFlash('success', "Announcement '{$title}' published.");
    }
    header('Location: /admin/notifications.php');
    exit;
}

$announcements = Database::fetchAll("SELECT * FROM announcements ORDER BY id DESC");
?>

<div class="space-y-6">

  <div>
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Announcements & Customer Notices</h1>
    <p class="text-xs text-gray-500">Configure promotional alerts, Telegram club joins, and seasonal fruit popups.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add Announcement Form (1 col) -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 text-xs">
      <h2 class="text-sm font-black text-gray-900">Publish Notice</h2>

      <form action="/admin/notifications.php" method="POST" class="space-y-3">
        <?= csrfField() ?>
        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Title *</label>
          <input type="text" name="title" required placeholder="e.g. Summer Special Fresh Coconuts!" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Message Body *</label>
          <textarea name="message" rows="3" required placeholder="Description displayed to shoppers..." class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800"></textarea>
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Notice Type</label>
          <select name="type" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
            <option value="popup">Storefront Popup Card</option>
            <option value="banner">Top Announcement Banner</option>
            <option value="telegram">Telegram Club Join Card</option>
            <option value="seasonal">Seasonal Harvest Alert</option>
          </select>
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Target Action Link</label>
          <input type="text" name="target_url" placeholder="e.g. /category.php?slug=coconut-water or Telegram link" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Action Button Text</label>
          <input type="text" name="action_button_text" value="Order Now" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
        </div>

        <button type="submit" name="save_announcement" value="1" class="w-full bg-green-700 hover:bg-green-800 text-white font-black p-2.5 rounded-xl transition shadow-sm">
          Publish Notice
        </button>
      </form>
    </div>

    <!-- Active Notices (2 cols) -->
    <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
      <h2 class="text-sm font-black text-gray-900">Current Announcements</h2>

      <div class="space-y-3">
        <?php foreach ($announcements as $a): ?>
          <div class="p-4 rounded-2xl border border-gray-200 bg-gray-50 flex items-start justify-between space-x-3 text-xs">
            <div class="space-y-1">
              <div class="flex items-center space-x-2">
                <span class="font-black text-gray-900 text-sm"><?= e($a['title']) ?></span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-800"><?= e($a['type']) ?></span>
              </div>
              <p class="text-gray-600 leading-relaxed"><?= e($a['message']) ?></p>
              <?php if (!empty($a['target_url'])): ?>
                <a href="<?= e($a['target_url']) ?>" target="_blank" class="text-green-700 font-bold hover:underline inline-block mt-1">
                  Link: <?= e($a['target_url']) ?> (<?= e($a['action_button_text']) ?>)
                </a>
              <?php endif; ?>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-800 flex-shrink-0">
              Active
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
