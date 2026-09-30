<?php
function base_url(string $path = ''): string {
    $base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (str_ends_with($base, '/views')) $base = dirname($base);
    return $base . '/' . ltrim($path, '/');
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header("Location: $url"); exit; }
function require_login(): void {
    if (empty($_SESSION['user'])) redirect('index.php?page=login');
}
function require_role(array $roles): void {
    require_login();
    if (!in_array($_SESSION['user']['rol'], $roles, true)) {
        http_response_code(403); exit('Acceso denegado.');
    }
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419); exit('Token CSRF inválido.');
    }
}
