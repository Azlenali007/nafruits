<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Customer Logout Handler
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

customerLogout();
setFlash('info', 'You have been signed out.');
header('Location: /index.php');
exit;
