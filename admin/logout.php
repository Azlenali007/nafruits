<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Admin Logout
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

adminLogout();
header('Location: /admin/login.php');
exit;
