<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
require_login();
verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM links WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, current_user_id()]);
    flash('success', $stmt->rowCount() ? 'Link excluído.' : 'Link não encontrado.');
}
redirect_to('/dashboard.php');
