<?php
declare(strict_types=1);

function app_env(string $name, string $default = ''): string
{
    static $values = null;
    if ($values === null) {
        $values = [];
        $path = dirname(__DIR__) . '/.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $values[trim($key)] = trim(trim($value), " \t\n\r\0\x0B\"'");
            }
        }
    }
    $serverValue = getenv($name);
    return $serverValue !== false ? (string) $serverValue : ($values[$name] ?? $default);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = app_env('DB_HOST');
    $name = app_env('DB_NAME');
    $user = app_env('DB_USER');
    if ($host === '' || $name === '' || $user === '') {
        http_response_code(503);
        exit('Aplicação ainda não configurada. Consulte o README.md.');
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . $host . ';port=' . (int) app_env('DB_PORT', '3306') . ';dbname=' . $name . ';charset=utf8mb4',
            $user,
            app_env('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getCode());
        http_response_code(503);
        exit('Serviço temporariamente indisponível.');
    }
    return $pdo;
}

if (PHP_SAPI !== 'cli') {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || str_starts_with(strtolower(app_env('APP_URL')), 'https://');
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('encurtador_session');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure,
            'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
}

require_once dirname(__DIR__) . '/includes/functions.php';
