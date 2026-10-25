<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once dirname(__DIR__) . '/config/bootstrap.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
};
$pdo = db();
$pdo->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(8));
    $email = 'ci-' . $suffix . '@example.test';
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'editor')");
    $hash = password_hash('integration-test-password', PASSWORD_DEFAULT);
    $stmt->execute(['CI Test User', $email, $hash]);
    $userId = (int) $pdo->lastInsertId();
    $assert($userId > 0, 'inserir usuário');
    $assert(password_verify('integration-test-password', $hash), 'verificar senha');

    $alias = 'ci-' . $suffix;
    $stmt = $pdo->prepare('INSERT INTO links (user_id, destination_url, alias, title, description) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, 'https://example.com/path', $alias, 'Teste CI', '']);
    $linkId = (int) $pdo->lastInsertId();
    $assert($linkId > 0, 'inserir link');
    $_SESSION['user_id'] = $userId;
    $link = link_by_id($linkId);
    $assert($link !== null && $link['alias'] === $alias, 'consultar link do proprietário');

    $_SESSION['user_id'] = $userId + 1;
    $assert(link_by_id($linkId) === null, 'isolar link de outro usuário');
    $_SESSION['user_id'] = $userId;

    $stmt = $pdo->prepare('UPDATE links SET click_count = click_count + 1 WHERE id = ?');
    $stmt->execute([$linkId]);
    $pdo->prepare('INSERT INTO link_visits (link_id, visited_on, referrer_host) VALUES (?, CURRENT_DATE, ?)')->execute([$linkId, 'example.org']);
    $stmt = $pdo->prepare('SELECT click_count FROM links WHERE id = ?');
    $stmt->execute([$linkId]);
    $assert((int) $stmt->fetchColumn() === 1, 'registrar acesso');

    $duplicate = false;
    try {
        $stmt->closeCursor();
        $pdo->prepare('INSERT INTO links (user_id, destination_url, alias, title) VALUES (?, ?, ?, ?)')->execute([$userId, 'https://example.org', $alias, 'Duplicado']);
    } catch (PDOException $exception) {
        $duplicate = $exception->getCode() === '23000';
    }
    $assert($duplicate, 'impedir alias duplicado');
    $pdo->rollBack();
    fwrite(STDOUT, "OK: integração MySQL (usuário, senha, isolamento, link, acesso e alias único).\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, 'FALHOU integração MySQL: ' . $exception->getMessage() . "\n");
    exit(1);
}
