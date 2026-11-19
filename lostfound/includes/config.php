<?php
// Acts as a connector of php and sql, and holds helpers shared by every page

// ===================
// DEV SETTINGS
// ===================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===================
// PROJECT WEB ADDRESS
// ===================
// The folder name as it appears in the browser after "localhost".
// Example: http://localhost/lostfound/login.php  ->  '/lostfound'
// Change this if you rename the project folder.
define('BASE_URL', '/lostfound');

// ===================
// DATABASE CONNECTION
// ===================
$pdo = new PDO(
    "mysql:host=localhost;dbname=lostfound_pamp;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// ===================
// SESSION (keeps users logged in)
// ===================
session_set_cookie_params([
    'httponly' => true,    // JavaScript cannot read the session cookie
    'samesite' => 'Lax',
]);
session_start();

// ===================
// HELPER FUNCTIONS
// ===================

// Safe output: always wrap user-supplied text with e() before printing it
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Relative addresses such as "login.php" are turned into "/lostfound/login.php",
// so a redirect works no matter which folder the current page lives in.
function redirect(string $url): void {
    if (!preg_match('#^(https?:)?//|^/#', $url)) {
        $url = BASE_URL . '/' . $url;
    }
    header("Location: $url");
    exit;
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

// Put this at the top of any page that only logged-in users may open
function require_login(): void {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

// CSRF token: stops other websites from submitting our forms for the user
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}