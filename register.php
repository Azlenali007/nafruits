<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Registration
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

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || strlen($password) < 6) {
        $error = 'Please fill all required fields (password minimum 6 characters).';
    } else {
        $res = customerRegister($name, $email, $phone, $password);
        if ($res['success']) {
            setFlash('success', 'Welcome to Na Fresh, ' . $name . '!');
            header('Location: /index.php');
            exit;
        } else {
            $error = $res['error'] ?? 'Registration failed.';
        }
    }
}

$pageTitle = 'Register – Na Fresh';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto px-4 pt-6 pb-12">
  <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
    
    <div class="text-center space-y-1">
      <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center mx-auto text-green-700 text-xl font-bold mb-2">
        <i class="fas fa-user-plus"></i>
      </div>
      <h1 class="text-xl font-black text-gray-900">Create Account</h1>
      <p class="text-xs text-gray-500">Join Na Fresh for lightning fast fruit and coconut delivery.</p>
    </div>

    <?php if ($error): ?>
      <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-xs font-bold">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form action="/register.php" method="POST" class="space-y-3">
      <?= csrfField() ?>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Full Name</label>
        <input type="text" name="name" required placeholder="Rahul Sharma" value="<?= e($_POST['name'] ?? '') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-3.5 py-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600 focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Email Address</label>
        <input type="email" name="email" required placeholder="name@domain.com" value="<?= e($_POST['email'] ?? '') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-3.5 py-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600 focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Mobile Phone</label>
        <input type="tel" name="phone" required placeholder="+91 98980 12345" value="<?= e($_POST['phone'] ?? '') ?>" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-3.5 py-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600 focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Create Password</label>
        <input type="password" name="password" required minlength="6" placeholder="At least 6 characters" class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-3.5 py-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600 focus:outline-none">
      </div>

      <div class="pt-2">
        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-black py-3.5 px-4 rounded-2xl shadow-md transition transform active:scale-95 text-xs uppercase tracking-wider">
          Create Account
        </button>
      </div>
    </form>

    <div class="text-center text-xs text-gray-500 pt-2 border-t border-gray-100">
      Already have an account? 
      <a href="/login.php" class="font-extrabold text-green-700 hover:underline">Sign In</a>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
