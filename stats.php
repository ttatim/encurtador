<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_login();
$id = (int) ($_GET['id'] ?? 0);
$link = $id > 0 ? link_by_id($id) : null;
if (!$link) { http_response_code(404); require __DIR__ . '/pagina-de-erro.php'; exit; }
$stmt = db()->prepare('SELECT visited_on, COUNT(*) AS total FROM link_visits WHERE link_id = ? AND visited_on >= CURRENT_DATE - INTERVAL 29 DAY GROUP BY visited_on ORDER BY visited_on DESC');
$stmt->execute([$id]);
$days = $stmt->fetchAll();
$stmt = db()->prepare('SELECT COALESCE(referrer_host, \'Acesso direto / desconhecido\') AS origin, COUNT(*) AS total FROM link_visits WHERE link_id = ? GROUP BY origin ORDER BY total DESC LIMIT 10');
$stmt->execute([$id]);
$origins = $stmt->fetchAll();
render_header('Estatísticas', 'dashboard');
?>
<main class="page-section"><div class="container"><a class="text-secondary text-decoration-none" href="/dashboard.php">← Voltar ao painel</a><h1 class="page-title h2 mt-3 mb-2">Estatísticas do link</h1><p class="text-secondary mb-4"><?= e($link['title']) ?> · <a href="<?= e(app_url($link['alias'])) ?>" target="_blank" rel="noopener noreferrer"><?= e(app_url($link['alias'])) ?></a></p>
<div class="row g-4"><div class="col-lg-4"><div class="stat-card"><small>Acessos totais</small><strong><?= number_format((int) $link['click_count'], 0, ',', '.') ?></strong><small>Desde a criação</small></div></div><div class="col-lg-8"><div class="panel-card"><h2 class="h5 fw-bold mb-3">Últimos 30 dias</h2><div class="table-responsive"><table class="table"><thead><tr><th>Data</th><th>Acessos</th></tr></thead><tbody><?php foreach ($days as $day): ?><tr><td><?= e($day['visited_on']) ?></td><td><?= (int) $day['total'] ?></td></tr><?php endforeach; ?><?php if (!$days): ?><tr><td colspan="2" class="text-secondary">Ainda não há acessos.</td></tr><?php endif; ?></tbody></table></div></div></div>
<div class="col-12"><div class="panel-card"><h2 class="h5 fw-bold mb-3">Principais origens</h2><div class="table-responsive"><table class="table"><thead><tr><th>Origem</th><th>Acessos</th></tr></thead><tbody><?php foreach ($origins as $origin): ?><tr><td><?= e($origin['origin']) ?></td><td><?= (int) $origin['total'] ?></td></tr><?php endforeach; ?><?php if (!$origins): ?><tr><td colspan="2" class="text-secondary">Ainda não há dados.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
</div></main>
<?php render_footer(); ?>
