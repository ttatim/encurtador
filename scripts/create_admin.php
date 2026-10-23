<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Somente CLI.');
}
require_once dirname(__DIR__) . '/config/bootstrap.php';
$email = strtolower(trim((string) ($argv[1] ?? '')));
$name = trim((string) ($argv[2] ?? 'Administrador'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || $name === '' || strlen($name) > 120) {
    fwrite(STDERR, "Uso: php scripts/create_admin.php email@dominio.com 'Nome do administrador'\n");
    exit(1);
}
fwrite(STDOUT, "Digite uma senha forte (mínimo 12 caracteres) e pressione Enter: ");
$password = rtrim((string) fgets(STDIN), "\r\n");
if (strlen($password) < 12 || strlen($password) > 200) {
    fwrite(STDERR, "Senha inválida.\n");
    exit(1);
}
try {
    $stmt = db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Administrador criado.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "Não foi possível criar o administrador (verifique duplicidade e banco).\n");
    exit(1);
}
