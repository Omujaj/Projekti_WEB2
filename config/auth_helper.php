<?php
/**
 * Authentication Helper Functions
 * Compatible MySQLi helper used across the project.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function appUrl(): string {
    if (defined('APP_URL')) {
        return APP_URL;
    }
    if (defined('BASE_URL')) {
        return BASE_URL;
    }
    return 'http://localhost/ProjektiWeb2';
}

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . appUrl() . '/auth/login.php');
        exit();
    }
}

function hasRole($roles): bool {
    $currentRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
    if ($currentRole === null) {
        return false;
    }
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }
    return $currentRole === $roles;
}

function requireRole($roles): void {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: ' . appUrl() . '/index.php?error=unauthorized');
        exit();
    }
}

function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function getCurrentUser(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $db = getDB();
    $userId = (int)$_SESSION['user_id'];

    $stmt = $db->prepare(
        "SELECT u.*, r.name AS role_name
         FROM users u
         JOIN roles r ON u.role_id = r.id
         WHERE u.id = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sanitize($value): string {
    return e($value);
}

function getCsrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirectWithMessage(string $url, string $type, string $message): void {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
    header('Location: ' . $url);
    exit();
}

function getFlashMessage(): ?array {
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }
    $flash = [
        'type' => $_SESSION['flash_type'] ?? 'info',
        'message' => $_SESSION['flash_message']
    ];
    unset($_SESSION['flash_type'], $_SESSION['flash_message']);
    return $flash;
}

/**
 * Activity logger.
 * Supports both old calls: logActivity('action', 'description')
 * and new calls: logActivity($userId, 'action', 'description').
 */
function logActivity(...$args): void {
    if (count($args) >= 3 && is_numeric($args[0])) {
        $userId = (int)$args[0];
        $action = (string)$args[1];
        $description = (string)$args[2];
    } else {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $action = (string)($args[0] ?? 'activity');
        $description = (string)($args[1] ?? '');
    }

    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $db->prepare(
        "INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
         VALUES (?, ?, ?, ?, NOW())"
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("isss", $userId, $action, $description, $ip);
    $stmt->execute();
    $stmt->close();
}
