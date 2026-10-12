<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Shop Settings, Maintenance Mode & Store Configuration
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Shop Settings – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Handle Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_shop_settings'])) {
    requireCsrf();

    $settingsKeys = [
        'shop_name',
        'shop_tagline',
        'shop_phone',
        'shop_email',
        'shop_address',
        'currency_symbol',
        'maintenance_mode',
        'telegram_channel_url',
        'whatsapp_number',
        'opening_time',
        'closing_time'
    ];

    foreach ($settingsKeys as $k) {
        $val = trim($_POST[$k] ?? '');
        if ($k === 'maintenance_mode') {
            $val = isset($_POST['maintenance_mode']) ? '1' : '0';
        }
        Database::execute(
            "INSERT INTO shop_settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = ?",
            [$k, $val, $val]
        );
    }

    logAuditAction((int)$_SESSION['admin_id'], 'shop_settings_update', 'Updated store information and settings');
    setFlash('success', 'Shop settings saved successfully.');
    header('Location: /admin/settings.php');
    exit;
}

$auditLogs = Database::fetchAll("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 15");
?>

<div class="space-y-6">

  <div>
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Shop Information & Settings</h1>
    <p class="text-xs text-gray-500">Configure business contact details, operating hours, and system maintenance mode.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Store Settings Form (2 cols) -->
    <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 text-xs">
      <h2 class="text-sm font-black text-gray-900">General Settings</h2>

      <form action="/admin/settings.php" method="POST" class="space-y-4">
        <?= csrfField() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Store Name</label>
            <input type="text" name="shop_name" value="<?= e(getSetting('shop_name', 'Na Fresh – Fruits & Coconuts')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Brand Tagline</label>
            <input type="text" name="shop_tagline" value="<?= e(getSetting('shop_tagline', 'Freshness in Every Bite')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Customer Care Phone</label>
            <input type="text" name="shop_phone" value="<?= e(getSetting('shop_phone', '+91 98765 43210')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Support Email</label>
            <input type="email" name="shop_email" value="<?= e(getSetting('shop_email', 'support@nafresh.com')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">WhatsApp Hotline</label>
            <input type="text" name="whatsapp_number" value="<?= e(getSetting('whatsapp_number', '+919876543210')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Telegram Channel URL</label>
            <input type="url" name="telegram_channel_url" value="<?= e(getSetting('telegram_channel_url', 'https://t.me/nafreshfruits')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Daily Opening Time</label>
            <input type="text" name="opening_time" value="<?= e(getSetting('opening_time', '07:00 AM')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase mb-1">Daily Closing Time</label>
            <input type="text" name="closing_time" value="<?= e(getSetting('closing_time', '09:30 PM')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
          </div>
        </div>

        <div>
          <label class="block font-bold text-gray-700 uppercase mb-1">Physical Store / Dispatch Address</label>
          <input type="text" name="shop_address" value="<?= e(getSetting('shop_address', 'Shop 12, Annapurna Main Road, Indore, MP 452009')) ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800">
        </div>

        <!-- Maintenance Mode Switch -->
        <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 flex items-center justify-between">
          <div>
            <div class="font-bold text-amber-900">Maintenance Mode</div>
            <div class="text-[11px] text-amber-700">Display maintenance notice on customer storefront while keeping admin accessible.</div>
          </div>
          <input type="checkbox" name="maintenance_mode" value="1" <?= getSetting('maintenance_mode') === '1' ? 'checked' : '' ?> class="rounded text-amber-600 w-5 h-5">
        </div>

        <div class="text-right pt-2">
          <button type="submit" name="save_shop_settings" value="1" class="bg-green-700 hover:bg-green-800 text-white font-black px-5 py-2.5 rounded-xl shadow-sm">
            Save Shop Settings
          </button>
        </div>
      </form>
    </div>

    <!-- Security Audit Logs (1 col) -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-3 text-xs">
      <h2 class="text-sm font-black text-gray-900">Recent Security Audit Logs</h2>
      <div class="space-y-2">
        <?php foreach ($auditLogs as $al): ?>
          <div class="p-2.5 rounded-xl bg-gray-50 border border-gray-200">
            <div class="flex items-center justify-between font-bold text-gray-800">
              <span class="text-green-800 uppercase text-[10px]"><?= e($al['action']) ?></span>
              <span class="text-gray-400 text-[10px]"><?= date('h:i A', strtotime($al['created_at'])) ?></span>
            </div>
            <p class="text-gray-600 text-[11px] mt-0.5"><?= e($al['details']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
