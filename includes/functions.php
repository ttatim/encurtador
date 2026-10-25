<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $base = rtrim(app_env('APP_URL'), '/');
    if (!preg_match('~^https?://[^/]+~i', $base)) {
        $base = 'http://localhost';
    }
    return $base . '/' . ltrim($path, '/');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST'
        || !hash_equals(csrf_token(), (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(419);
        exit('Solicitação inválida. Atualize a página e tente novamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function redirect_to(string $path): never
{
    if (!preg_match('~^/[a-zA-Z0-9_/?=&%.-]*$~', $path) || str_starts_with($path, '//')) {
        $path = '/';
    }
    header('Location: ' . $path, true, 303);
    exit;
}

function logged_in(): bool
{
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
}

function require_login(): void
{
    if (!logged_in()) {
        redirect_to('/login.php');
    }
    $stmt = db()->prepare('SELECT name, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([current_user_id()]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION = [];
        session_regenerate_id(true);
        redirect_to('/login.php');
    }
    $_SESSION['user_name'] = (string) $user['name'];
    $_SESSION['user_role'] = (string) $user['role'];
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Acesso restrito ao administrador.');
    }
}

function valid_user_email(string $email): bool
{
    return strlen($email) <= 190 && (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function valid_user_password(string $password): bool
{
    return strlen($password) >= 12 && strlen($password) <= 200;
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function setting(string $key, string $fallback = ''): string
{
    static $settings = [];
    if (array_key_exists($key, $settings)) {
        return $settings[$key];
    }
    $stmt = db()->prepare('SELECT value FROM settings WHERE name = ? LIMIT 1');
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $settings[$key] = $result === false ? $fallback : (string) $result;
}

function save_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
    $stmt->execute([$key, $value]);
}

function validate_destination(string $url): ?string
{
    $url = trim($url);
    if (strlen($url) > 2048 || strlen($url) < 10 || preg_match('/[\x00-\x20\x7F]/', $url)) {
        return null;
    }
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme'], $parts['host'])
        || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        || isset($parts['user']) || isset($parts['pass'])
        || !filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    return $url;
}

function validate_alias(string $alias): bool
{
    $reserved = ['admin', 'api', 'assets', 'config', 'conexao', 'dashboard', 'database',
        'includes', 'index', 'login', 'logout', 'p', 'preview', 'redirect', 'scripts',
        'settings', 'tests', 'uploads', 'link-form', 'link-delete', 'pagina-de-erro',
        'robots', 'favicon', 'sitemap', 'health', 'www'];
    return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{2,49}$/D', $alias)
        && !in_array(strtolower($alias), $reserved, true);
}

function new_alias(): string
{
    return strtolower(bin2hex(random_bytes(5)));
}

function safe_image_upload(string $field, string $existing = ''): string
{
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    $file = $_FILES[$field];
    if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024
        || !is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException('Imagem inválida ou maior que 2 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Envie uma imagem PNG, JPG, WEBP ou ICO.');
    }
    if ($allowed[$mime] !== 'ico') {
        $dimensions = getimagesize($file['tmp_name']);
        if ($dimensions === false || $dimensions[0] > 6000 || $dimensions[1] > 6000) {
            throw new RuntimeException('Imagem inválida ou dimensões maiores que 6000 pixels.');
        }
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $directory = dirname(__DIR__) . '/uploads';
    if (!is_dir($directory) || !is_writable($directory)
        || !move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }
    return '/uploads/' . $name;
}

function link_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM links WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, current_user_id()]);
    return $stmt->fetch() ?: null;
}

function normalized_referrer(): ?string
{
    $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $host = parse_url($referrer, PHP_URL_HOST);
    return is_string($host) && strlen($host) <= 190 ? strtolower($host) : null;
}
