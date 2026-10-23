<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
$alias = (string) ($_GET['link'] ?? '');
if (!validate_alias($alias)) { http_response_code(404); require __DIR__ . '/pagina-de-erro.php'; exit; }
$stmt = db()->prepare('SELECT alias, title, description, image_path, destination_url FROM links WHERE alias = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$alias]);
$link = $stmt->fetch();
if (!$link) { http_response_code(404); require __DIR__ . '/pagina-de-erro.php'; exit; }
$title = $link['title'];
$description = $link['description'] !== '' ? $link['description'] : setting('site_description', 'Confira este link.');
$image = $link['image_path'] !== '' ? app_url(ltrim($link['image_path'], '/')) : '';
$shortUrl = app_url($alias);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title><meta name="description" content="<?= e($description) ?>"><meta property="og:type" content="website"><meta property="og:title" content="<?= e($title) ?>"><meta property="og:description" content="<?= e($description) ?>"><meta property="og:url" content="<?= e(app_url('p/' . $alias)) ?>">
<?php if ($image !== ''): ?><meta property="og:image" content="<?= e($image) ?>"><?php endif; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="/assets/app.css" rel="stylesheet"></head>
<body><main class="page-section d-flex align-items-center"><div class="container" style="max-width:640px"><div class="panel-card text-center">
<?php if ($image !== ''): ?><img src="<?= e($image) ?>" alt="Imagem do link" class="img-fluid rounded mb-4" style="max-height:220px"><?php endif; ?>
<h1 class="h2 fw-bold"><?= e($title) ?></h1><p class="text-secondary mb-4"><?= e($description) ?></p><a class="btn btn-primary px-4 py-3" href="<?= e($shortUrl) ?>" rel="nofollow">Continuar para o destino ↗</a><p class="small text-secondary mt-4 mb-0">Você será direcionado a um site externo: <?= e((string) parse_url($link['destination_url'], PHP_URL_HOST)) ?></p></div></div></main></body></html>
