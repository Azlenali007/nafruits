<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Login
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

if (isCustomerLoggedIn()) {
    header('Location: /account.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $emailOrPhone = trim($_POST['email_or_phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($emailOrPhone) || empty($password)) {
        $error = 'Please enter your email/phone and password.';
    } else {
        if (customerLogin($emailOrPhone, $password)) {
            $redirect = $_SESSION['redirect_after_login'] ?? '/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid credentials or inactive account.';
        }
    }
}

$pageTitle = 'Login – Na Fresh';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto px-4 pt-6 pb-12">
  <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
    
    <div class="text-center space-y-1">
      <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center mx-auto text-green-700 text-xl font-bold mb-2">
        <i class="fas fa-seedling"></i>
      </div>
      <h1 class="text-xl font-black text-gray-900">Welcome Back</h1>
      <p class="text-xs text-gray-500">Sign in to track orders and save your delivery addresses.</p>
    </div>

    <?php if ($error): ?>
      <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-xs font-bold">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form action="/login.php" method="POST" class="space-y-3.5">
      <?= csrfField() ?>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Email or Mobile Number</label>
        <div class="relative">
          <i class="fas fa-envelope absolute left-3.5 top-3.5 text-gray-400 text-xs"></i>
          <input type="text" name="email_or_phone" required placeholder="customer@example.com or 9898012345" value="<?= e($_POST['email_or_phone'] ?? '') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-2xl pl-10 pr-3 py-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600 focus:outline-none">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Password</label>
        <div class="relative">
          <i class="fas fa-lock absolute left-3.5 top-3.5 text-gray-400 text-xs"></i>
          <input type="password" name="password" required placeholder="••••••••" class="w-full bg-gray-50 border border-gray-200 rounded-2xl pl-10 pr-3 py-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600 focus:outline-none">
        </div>
      </div>

      <div class="pt-2">
        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-black py-3.5 px-4 rounded-2xl shadow-md transition transform active:scale-95 text-xs uppercase tracking-wider">
          Sign In
        </button>
      </div>
    </form>

    <!-- Quick Demo Login Helper for Testing -->
    <div class="p-3 bg-green-50 rounded-2xl border border-green-200 text-center text-xs space-y-1">
      <div class="font-bold text-green-900">Demo Customer Account:</div>
      <div class="text-[11px] text-green-700">Email: <span class="font-bold">customer@example.com</span> | Pass: <span class="font-bold">password123</span></div>
      <button type="button" onclick="fillDemoCustomer()" class="text-[10px] font-black uppercase text-green-800 underline mt-1">Auto-fill Demo</button>
    </div>

    <div class="text-center text-xs text-gray-500 pt-2 border-t border-gray-100">
      Don't have an account? 
      <a href="/register.php" class="font-extrabold text-green-700 hover:underline">Register Now</a>
    </div>

  </div>
</div>

<script>
function fillDemoCustomer() {
  document.querySelector('input[name="email_or_phone"]').value = 'customer@example.com';
  document.querySelector('input[name="password"]').value = 'password123';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
