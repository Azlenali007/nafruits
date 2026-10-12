<?php
/**
 * NA FRESH – Fruits & Coconuts
 * Authentication & Authorization (Customer & Admin)
 */

declare(strict_types=1);

// ==========================================
// Customer Authentication
// ==========================================

function isCustomerLoggedIn(): bool {
    return !empty($_SESSION['customer_id']);
}

function getCurrentCustomer(): ?array {
    if (!isCustomerLoggedIn()) {
        return null;
    }

    $customerId = (int)$_SESSION['customer_id'];
    $db = Database::getConnection();
    if (!$db) {
        return $_SESSION['customer_data'] ?? null;
    }

    $customer = Database::fetchOne(
        "SELECT id, name, email, phone, avatar, status, created_at FROM customers WHERE id = ? AND status = 'active'",
        [$customerId]
    );

    if (!$customer) {
        customerLogout();
        return null;
    }

    return $customer;
}

function customerLogin(string $emailOrPhone, string $password): bool {
    $db = Database::getConnection();
    if (!$db) {
        return false;
    }

    $customer = Database::fetchOne(
        "SELECT id, name, email, phone, password_hash, status FROM customers WHERE (email = ? OR phone = ?) LIMIT 1",
        [$emailOrPhone, $emailOrPhone]
    );

    if ($customer && $customer['status'] === 'active' && password_verify($password, $customer['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['customer_id'] = (int)$customer['id'];
        $_SESSION['customer_name'] = $customer['name'];
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['customer_phone'] = $customer['phone'];
        $_SESSION['customer_data'] = $customer;

        // Sync session cart with database cart
        syncSessionCartToDb((int)$customer['id']);
        return true;
    }

    return false;
}

function customerRegister(string $name, string $email, string $phone, string $password): array {
    $db = Database::getConnection();
    if (!$db) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    // Check unique email & phone
    $existing = Database::fetchOne(
        "SELECT id FROM customers WHERE email = ? OR phone = ? LIMIT 1",
        [$email, $phone]
    );

    if ($existing) {
        return ['success' => false, 'error' => 'An account with this email or phone number already exists'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $newId = Database::insert(
        "INSERT INTO customers (name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, 'active')",
        [$name, $email, $phone, $hash]
    );

    if ($newId) {
        customerLogin($email, $password);
        return ['success' => true, 'id' => (int)$newId];
    }

    return ['success' => false, 'error' => 'Could not register customer'];
}

function customerLogout(): void {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email'], $_SESSION['customer_phone'], $_SESSION['customer_data']);
}

function requireCustomerAuth(string $redirectUrl = '/login.php'): void {
    if (!isCustomerLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/index.php';
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// ==========================================
// Admin Authentication & Authorization
// ==========================================

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function getCurrentAdmin(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }

    $adminId = (int)$_SESSION['admin_id'];
    $db = Database::getConnection();
    if (!$db) {
        return $_SESSION['admin_data'] ?? null;
    }

    $admin = Database::fetchOne(
        "SELECT a.id, a.role_id, a.name, a.email, a.phone, a.status, r.role_name, r.permissions 
         FROM admins a 
         JOIN admin_roles r ON a.role_id = r.id 
         WHERE a.id = ? AND a.status = 'active'",
        [$adminId]
    );

    if (!$admin) {
        adminLogout();
        return null;
    }

    return $admin;
}

function adminLogin(string $email, string $password): bool {
    $db = Database::getConnection();
    if (!$db) {
        return false;
    }

    $admin = Database::fetchOne(
        "SELECT a.id, a.role_id, a.name, a.email, a.password_hash, a.status, r.role_name, r.permissions 
         FROM admins a 
         JOIN admin_roles r ON a.role_id = r.id 
         WHERE a.email = ? LIMIT 1",
        [$email]
    );

    if ($admin && $admin['status'] === 'active' && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role_name'];
        $_SESSION['admin_permissions'] = json_decode($admin['permissions'] ?? '{}', true) ?: [];
        $_SESSION['admin_data'] = $admin;

        Database::execute("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
        logAuditAction((int)$admin['id'], 'admin_login', 'Admin logged in');
        return true;
    }

    return false;
}

function adminLogout(): void {
    if (isAdminLoggedIn()) {
        logAuditAction((int)$_SESSION['admin_id'], 'admin_logout', 'Admin logged out');
    }
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role'], $_SESSION['admin_permissions'], $_SESSION['admin_data']);
}

function requireAdminAuth(string $redirectUrl = '/admin/login.php'): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . $redirectUrl);
        exit;
    }
}

function hasAdminPermission(string $permissionKey): bool {
    if (!isAdminLoggedIn()) {
        return false;
    }
    $perms = $_SESSION['admin_permissions'] ?? [];
    if (!empty($perms['all']) || $_SESSION['admin_role'] === 'Super Admin') {
        return true;
    }
    return !empty($perms[$permissionKey]);
}

function logAuditAction(?int $adminId, string $action, string $details = ''): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        Database::insert(
            "INSERT INTO audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$adminId, $action, $details, $ip]
        );
    } catch (Exception $e) {
        // Silently log
        error_log('Audit log failure: ' . $e->getMessage());
    }
}
