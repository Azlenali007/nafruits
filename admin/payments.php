<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Payment Gateways, UPI QR Setup & Manual Verification
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

$adminTitle = 'Payments & UPI Setup – Na Fresh Admin';
require_once __DIR__ . '/../includes/admin-header.php';

// Handle Save Payment Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gateways'])) {
    requireCsrf();

    $settingsToUpdate = [
        'upi_id' => trim($_POST['upi_id'] ?? ''),
        'upi_payee_name' => trim($_POST['upi_payee_name'] ?? ''),
        'cod_enabled' => isset($_POST['cod_enabled']) ? '1' : '0',
        'upi_enabled' => isset($_POST['upi_enabled']) ? '1' : '0',
        'razorpay_enabled' => isset($_POST['razorpay_enabled']) ? '1' : '0',
        'razorpay_key_id' => trim($_POST['razorpay_key_id'] ?? ''),
        'razorpay_key_secret' => trim($_POST['razorpay_key_secret'] ?? ''),
        'bank_transfer_enabled' => isset($_POST['bank_transfer_enabled']) ? '1' : '0',
        'bank_account_details' => trim($_POST['bank_account_details'] ?? '')
    ];

    foreach ($settingsToUpdate as $k => $v) {
        Database::execute(
            "INSERT INTO shop_settings (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = ?",
            [$k, $v, $v]
        );
    }

    logAuditAction((int)$_SESSION['admin_id'], 'payments_config_update', 'Updated payment gateway configurations');
    setFlash('success', 'Payment gateway settings updated successfully.');
    header('Location: /admin/payments.php');
    exit;
}

// Handle Manual UPI Verification Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_tx'])) {
    requireCsrf();
    $txId = (int)($_POST['tx_id'] ?? 0);
    $verAction = $_POST['ver_action'] ?? 'approve'; // approve or reject

    $tx = Database::fetchOne("SELECT * FROM payment_transactions WHERE id = ?", [$txId]);
    if ($tx) {
        if ($verAction === 'approve') {
            Database::execute("UPDATE payment_transactions SET status = 'completed', admin_verified = 1, verified_by = ? WHERE id = ?", [$_SESSION['admin_id'], $txId]);
            Database::execute("UPDATE orders SET payment_status = 'paid', order_status = 'confirmed' WHERE id = ?", [$tx['order_id']]);
            Database::insert("INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'confirmed', 'UPI transaction reference verified manually by Admin', ?)", [$tx['order_id'], $_SESSION['admin_name']]);
            setFlash('success', 'UPI Transaction verified and Order marked as PAID & CONFIRMED.');
        } else {
            Database::execute("UPDATE payment_transactions SET status = 'failed' WHERE id = ?", [$txId]);
            Database::execute("UPDATE orders SET payment_status = 'failed' WHERE id = ?", [$tx['order_id']]);
            setFlash('error', 'UPI Transaction rejected.');
        }
    }
    header('Location: /admin/payments.php');
    exit;
}

$transactions = Database::fetchAll(
    "SELECT pt.*, o.order_number, o.customer_name, o.customer_phone 
     FROM payment_transactions pt 
     JOIN orders o ON pt.order_id = o.id 
     ORDER BY pt.created_at DESC LIMIT 20"
);
?>

<div class="space-y-6">

  <div>
    <h1 class="text-2xl font-black text-gray-900 tracking-tight">Payment Gateways & UPI Verification</h1>
    <p class="text-xs text-gray-500">Configure Cash on Delivery, UPI QR (PhonePe/GPay), Razorpay credentials, and approve transaction references.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Payment Gateways Configuration (2 cols) -->
    <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
      <h2 class="text-sm font-black text-gray-900">Gateway Controls</h2>

      <form action="/admin/payments.php" method="POST" class="space-y-4 text-xs">
        <?= csrfField() ?>

        <!-- UPI / QR Configuration -->
        <div class="p-4 bg-purple-50/50 rounded-2xl border border-purple-100 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <i class="fas fa-qrcode text-purple-700 text-lg"></i>
              <span class="font-extrabold text-purple-900 text-sm">UPI / QR Payment</span>
            </div>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="upi_enabled" value="1" <?= getSetting('upi_enabled', '1') === '1' ? 'checked' : '' ?> class="rounded text-green-600">
              <span class="font-bold text-gray-700">Enable</span>
            </label>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-gray-700 uppercase mb-1">Merchant UPI ID / VPA</label>
              <input type="text" name="upi_id" value="<?= e(getSetting('upi_id', 'nafresh@icici')) ?>" class="w-full bg-white border border-gray-300 rounded-xl p-2 text-xs text-gray-800">
            </div>
            <div>
              <label class="block font-bold text-gray-700 uppercase mb-1">Payee Business Name</label>
              <input type="text" name="upi_payee_name" value="<?= e(getSetting('upi_payee_name', 'NA Fresh Organics')) ?>" class="w-full bg-white border border-gray-300 rounded-xl p-2 text-xs text-gray-800">
            </div>
          </div>
        </div>

        <!-- Cash on Delivery -->
        <div class="p-4 bg-amber-50/50 rounded-2xl border border-amber-100 flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <i class="fas fa-money-bill-wave text-amber-700 text-lg"></i>
            <div>
              <div class="font-extrabold text-amber-900 text-sm">Cash on Delivery (COD)</div>
              <div class="text-[11px] text-gray-500">Collect cash upon doorstep fruit handover</div>
            </div>
          </div>
          <label class="flex items-center space-x-2">
            <input type="checkbox" name="cod_enabled" value="1" <?= getSetting('cod_enabled', '1') === '1' ? 'checked' : '' ?> class="rounded text-green-600">
            <span class="font-bold text-gray-700">Enable</span>
          </label>
        </div>

        <!-- Razorpay Configuration -->
        <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <i class="fas fa-credit-card text-blue-700 text-lg"></i>
              <span class="font-extrabold text-blue-900 text-sm">Razorpay Integration</span>
            </div>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="razorpay_enabled" value="1" <?= getSetting('razorpay_enabled', '1') === '1' ? 'checked' : '' ?> class="rounded text-green-600">
              <span class="font-bold text-gray-700">Enable</span>
            </label>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-gray-700 uppercase mb-1">Razorpay Key ID</label>
              <input type="text" name="razorpay_key_id" value="<?= e(getSetting('razorpay_key_id', 'rzp_test_sample_key_nafresh')) ?>" class="w-full bg-white border border-gray-300 rounded-xl p-2 text-xs text-gray-800">
            </div>
            <div>
              <label class="block font-bold text-gray-700 uppercase mb-1">Razorpay Key Secret</label>
              <input type="password" name="razorpay_key_secret" value="<?= e(getSetting('razorpay_key_secret', 'sample_secret_key_nafresh')) ?>" class="w-full bg-white border border-gray-300 rounded-xl p-2 text-xs text-gray-800">
            </div>
          </div>
        </div>

        <!-- Bank Transfer Details -->
        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 space-y-2">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <i class="fas fa-university text-gray-700 text-lg"></i>
              <span class="font-extrabold text-gray-900 text-sm">Direct Bank Transfer</span>
            </div>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="bank_transfer_enabled" value="1" <?= getSetting('bank_transfer_enabled', '1') === '1' ? 'checked' : '' ?> class="rounded text-green-600">
              <span class="font-bold text-gray-700">Enable</span>
            </label>
          </div>
          <input type="text" name="bank_account_details" value="<?= e(getSetting('bank_account_details', 'Bank: HDFC Bank | A/C: 50200012345678 | IFSC: HDFC0001234 | Branch: Indore South')) ?>" class="w-full bg-white border border-gray-300 rounded-xl p-2 text-xs text-gray-800">
        </div>

        <div class="pt-2 text-right">
          <button type="submit" name="save_gateways" value="1" class="bg-green-700 hover:bg-green-800 text-white font-black px-5 py-2.5 rounded-xl shadow-sm text-xs">
            Save Gateway Settings
          </button>
        </div>
      </form>
    </div>

    <!-- Verification Tips / UPI QR Preview (1 col) -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 text-xs">
      <h2 class="text-sm font-black text-gray-900">Live UPI QR Code</h2>
      <div class="bg-purple-50 p-4 rounded-2xl border border-purple-200 flex flex-col items-center text-center space-y-2">
        <div class="w-32 h-32 bg-white p-2 rounded-xl border border-gray-200 shadow-xs flex items-center justify-center">
          <i class="fas fa-qrcode text-6xl text-gray-800"></i>
        </div>
        <div class="font-extrabold text-green-900"><?= e(getSetting('upi_id', 'nafresh@icici')) ?></div>
        <p class="text-[10px] text-gray-500">Customers scan this QR code on checkout to send funds directly to your verified bank account.</p>
      </div>
    </div>

  </div>

  <!-- Recent Payment Transactions & Manual Verification Queue -->
  <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm space-y-4">
    <h2 class="text-sm font-black text-gray-900">Payment Transactions & Verification Queue</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-gray-50 text-gray-400 uppercase font-extrabold text-[10px] tracking-wider border-b border-gray-100">
          <tr>
            <th class="py-3 px-3">Date</th>
            <th class="py-3 px-3">Order #</th>
            <th class="py-3 px-3">Customer</th>
            <th class="py-3 px-3">Gateway</th>
            <th class="py-3 px-3">UTR / Ref #</th>
            <th class="py-3 px-3">Amount</th>
            <th class="py-3 px-3">Status</th>
            <th class="py-3 px-3 text-right">Manual Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <?php foreach ($transactions as $t): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-3 px-3 text-gray-400 text-[11px]"><?= date('d M, h:i A', strtotime($t['created_at'])) ?></td>
              <td class="py-3 px-3 font-extrabold text-green-900">#<?= e($t['order_number']) ?></td>
              <td class="py-3 px-3"><?= e($t['customer_name']) ?></td>
              <td class="py-3 px-3 uppercase font-bold text-gray-700"><?= e($t['gateway']) ?></td>
              <td class="py-3 px-3 font-mono text-[11px] text-purple-800"><?= e($t['transaction_ref'] ?: '-') ?></td>
              <td class="py-3 px-3 font-black text-gray-900"><?= formatPrice($t['amount']) ?></td>
              <td class="py-3 px-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $t['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                  <?= e($t['status']) ?>
                </span>
              </td>
              <td class="py-3 px-3 text-right">
                <?php if ($t['gateway'] === 'upi_qr' && $t['status'] === 'pending'): ?>
                  <form action="/admin/payments.php" method="POST" class="inline-flex space-x-1">
                    <?= csrfField() ?>
                    <input type="hidden" name="tx_id" value="<?= $t['id'] ?>">
                    <button type="submit" name="verify_tx" value="1" onclick="this.form.querySelector('[name=ver_action]').value='approve'" class="bg-green-600 hover:bg-green-700 text-white font-bold px-2 py-1 rounded text-[10px]">Approve</button>
                    <button type="submit" name="verify_tx" value="1" onclick="this.form.querySelector('[name=ver_action]').value='reject'" class="bg-red-500 hover:bg-red-600 text-white font-bold px-2 py-1 rounded text-[10px]">Reject</button>
                    <input type="hidden" name="ver_action" value="approve">
                  </form>
                <?php else: ?>
                  <span class="text-gray-400 text-[11px]">Verified</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
