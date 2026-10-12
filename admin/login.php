<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Admin Secure Login
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

if (isAdminLoggedIn()) {
    header('Location: /admin/index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        if (adminLogin($email, $password)) {
            header('Location: /admin/index.php');
            exit;
        } else {
            $error = 'Invalid administrative credentials or account suspended.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal Login – Na Fresh</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-gray-100 flex items-center justify-center min-h-screen p-4">

  <div class="w-full max-w-md bg-gray-800 rounded-3xl p-8 border border-gray-700 shadow-2xl space-y-6">
    
    <div class="text-center space-y-2">
      <div class="w-16 h-16 rounded-2xl bg-green-900/60 border border-green-600/40 text-lime-400 text-3xl flex items-center justify-center mx-auto shadow-inner">
        🥥
      </div>
      <h1 class="text-2xl font-black tracking-tight text-white">Na Fresh Admin</h1>
      <p class="text-xs text-gray-400">Restricted Management & Inventory Console</p>
    </div>

    <?php if ($error): ?>
      <div class="p-3 bg-red-900/40 border border-red-700 text-red-200 rounded-2xl text-xs font-bold text-center">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form action="/admin/login.php" method="POST" class="space-y-4">
      <?= csrfField() ?>

      <div>
        <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1">Admin Email</label>
        <div class="relative">
          <i class="fas fa-envelope absolute left-3.5 top-3.5 text-gray-500 text-xs"></i>
          <input type="email" name="email" required placeholder="admin@nafresh.com" value="<?= e($_POST['email'] ?? 'admin@nafresh.com') ?>" class="w-full bg-gray-900 border border-gray-700 rounded-2xl pl-10 pr-3 py-2.5 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-green-500">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1">Password</label>
        <div class="relative">
          <i class="fas fa-lock absolute left-3.5 top-3.5 text-gray-500 text-xs"></i>
          <input type="password" name="password" required placeholder="••••••••" value="admin123" class="w-full bg-gray-900 border border-gray-700 rounded-2xl pl-10 pr-3 py-2.5 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-green-500">
        </div>
      </div>

      <div class="pt-2">
        <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-black py-3 px-4 rounded-2xl shadow-lg transition transform active:scale-95 text-xs uppercase tracking-wider flex items-center justify-center space-x-2">
          <i class="fas fa-sign-in-alt"></i>
          <span>Secure Sign In</span>
        </button>
      </div>
    </form>

    <div class="bg-gray-900/70 p-3 rounded-2xl border border-gray-700/60 text-center text-xs space-y-1">
      <span class="text-[11px] text-gray-400 font-bold block">Default Credentials:</span>
      <span class="text-lime-400 font-mono text-[11px]">admin@nafresh.com / admin123</span>
    </div>

    <div class="text-center text-xs text-gray-500">
      <a href="/index.php" class="text-green-400 hover:underline">← Back to Customer Storefront</a>
    </div>

  </div>

</body>
</html>
