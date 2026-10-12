<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Cross-Site Request Forgery (CSRF) Protection
 */

declare(strict_types=1);

/**
 * Generate or get existing CSRF token for the session
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden input tag containing CSRF token
 */
function csrfField(): string {
    $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate submitted CSRF token
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforce CSRF check on POST requests or terminate with 403
 */
function requireCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!verifyCsrfToken($token)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid or expired security token. Please refresh the page.']);
            } else {
                echo '<!DOCTYPE html><html><head><title>Security Error</title><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"></head><body class="bg-gray-50 flex items-center justify-center min-h-screen p-4"><div class="bg-white p-6 rounded-2xl shadow-lg max-w-md text-center"><h1 class="text-xl font-bold text-red-600 mb-2">Invalid Security Token</h1><p class="text-gray-600 mb-4">Your session token has expired. Please go back and refresh the page to try again.</p><a href="javascript:history.back()" class="inline-block bg-green-600 text-white px-5 py-2.5 rounded-xl font-medium">Go Back</a></div></body></html>';
            }
            exit;
        }
    }
}
