<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
$alias = (string) ($_GET['link'] ?? '');
if (!validate_alias($alias)) {
    http_response_code(404);
    require __DIR__ . '/pagina-de-erro.php';
    exit;
}
$stmt = db()->prepare('SELECT id, destination_url, redirect_code FROM links WHERE alias = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$alias]);
$link = $stmt->fetch();
if (!$link || !validate_destination($link['destination_url'])) {
    http_response_code(404);
    require __DIR__ . '/pagina-de-erro.php';
    exit;
}
try {
    db()->beginTransaction();
    db()->prepare('UPDATE links SET click_count = click_count + 1, last_clicked_at = NOW() WHERE id = ?')->execute([$link['id']]);
    db()->prepare('INSERT INTO link_visits (link_id, visited_on, referrer_host) VALUES (?, CURRENT_DATE, ?)')->execute([$link['id'], normalized_referrer()]);
    db()->commit();
} catch (Throwable $exception) {
    if (db()->inTransaction()) { db()->rollBack(); }
    error_log('Click tracking failed for link ' . (int) $link['id']);
}
header('Cache-Control: no-store, private');
header('Location: ' . $link['destination_url'], true, (int) $link['redirect_code'] === 301 ? 301 : 302);
exit;
