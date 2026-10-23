<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_login();
$userId = current_user_id();
$stmt = db()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(click_count),0) AS clicks, COALESCE(SUM(is_active),0) AS active FROM links WHERE user_id = ?');
$stmt->execute([$userId]);
$totals = $stmt->fetch();
$page = max(1, min(100000, (int) ($_GET['page'] ?? 1)));
$limit = 15;
$offset = ($page - 1) * $limit;
$stmt = db()->prepare('SELECT id, alias, title, destination_url, is_active, click_count, created_at FROM links WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $userId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$links = $stmt->fetchAll();
render_header('Painel de links', 'dashboard');
?>
<main class="page-section"><div class="container">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><div><span class="eyebrow">PAINEL DE CONTROLE</span><h1 class="page-title h2 mt-3 mb-1">Olá, <?= e((string) ($_SESSION['user_name'] ?? '')) ?> 👋</h1><p class="text-secondary mb-0">Seus links e resultados em um só lugar.</p></div><a href="/link-form.php" class="btn btn-primary px-4 py-3">+ Novo link</a></div>
  <div class="row g-3 mb-4"><div class="col-sm-4"><div class="stat-card"><small>Links criados</small><strong><?= number_format((int) $totals['total'], 0, ',', '.') ?></strong></div></div><div class="col-sm-4"><div class="stat-card"><small>Acessos acumulados</small><strong><?= number_format((int) $totals['clicks'], 0, ',', '.') ?></strong></div></div><div class="col-sm-4"><div class="stat-card"><small>Links ativos</small><strong><?= number_format((int) $totals['active'], 0, ',', '.') ?></strong></div></div></div>
  <div class="panel-card"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Meus links</h2><span class="text-secondary small"><?= (int) $totals['total'] ?> cadastrados</span></div>
    <?php if (!$links): ?><div class="text-center py-5"><div class="feature-icon mx-auto mb-3">↗</div><h3 class="h5 fw-bold">Seu primeiro link começa aqui</h3><p class="text-secondary">Crie uma URL curta e compartilhe com seu público.</p><a class="btn btn-primary" href="/link-form.php">Criar link</a></div>
    <?php else: ?><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Link</th><th>Status</th><th>Acessos</th><th>Ações</th></tr></thead><tbody>
    <?php foreach ($links as $link): ?><tr><td><a class="short-url" href="<?= e(app_url($link['alias'])) ?>" target="_blank" rel="noopener noreferrer"><?= e(app_url($link['alias'])) ?></a><span class="long-url" title="<?= e($link['destination_url']) ?>"><?= e($link['title']) ?> · <?= e($link['destination_url']) ?></span></td><td><span class="badge rounded-pill text-bg-<?= $link['is_active'] ? 'success' : 'secondary' ?>"><?= $link['is_active'] ? 'Ativo' : 'Pausado' ?></span></td><td><?= number_format((int) $link['click_count'], 0, ',', '.') ?></td><td><div class="d-flex gap-2 flex-wrap"><button type="button" class="btn btn-sm btn-outline-secondary" data-copy="<?= e(app_url($link['alias'])) ?>">Copiar</button><a class="btn btn-sm btn-outline-primary" href="/link-form.php?id=<?= (int) $link['id'] ?>">Editar</a><a class="btn btn-sm btn-outline-secondary" href="/stats.php?id=<?= (int) $link['id'] ?>">Estatísticas</a></div></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php if ((int) $totals['total'] > $limit): ?><nav class="d-flex gap-2 justify-content-end" aria-label="Páginas de links"><?php if ($page > 1): ?><a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page - 1 ?>">Anterior</a><?php endif; ?><?php if ($page * $limit < (int) $totals['total']): ?><a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page + 1 ?>">Próxima</a><?php endif; ?></nav><?php endif; ?>
    <?php endif; ?>
  </div>
</div></main>
<?php render_footer(); ?>
