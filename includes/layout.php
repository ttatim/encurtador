<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

function render_header(string $pageTitle, string $active = ''): void
{
    $brand = setting('site_name', 'Linkly');
    $description = setting('site_description', 'Links mais curtos. Resultados maiores.');
    $logo = setting('site_logo', '');
    $favicon = setting('site_favicon', '');
    $flash = take_flash();
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($description) ?>">
  <title><?= e($pageTitle) ?> · <?= e($brand) ?></title>
  <?php if ($favicon !== ''): ?><link rel="icon" href="<?= e($favicon) ?>"><?php endif; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/app.css" rel="stylesheet">
</head>
<body>
<header class="site-nav border-bottom">
  <nav class="navbar navbar-expand-lg container py-3" aria-label="Navegação principal">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/">
      <?php if ($logo !== ''): ?><img src="<?= e($logo) ?>" alt="" class="brand-logo"><?php else: ?><span class="brand-icon">↗</span><?php endif; ?>
      <span><?= e($brand) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navigation" aria-controls="navigation" aria-expanded="false" aria-label="Abrir menu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navigation">
      <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <?php if (logged_in()): ?>
          <a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="/dashboard.php">Meus links</a>
          <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
          <a class="nav-link <?= $active === 'users' ? 'active' : '' ?>" href="/users.php">Usuários</a>
          <a class="nav-link <?= $active === 'settings' ? 'active' : '' ?>" href="/settings.php">Configurações</a>
          <?php endif; ?>
          <a class="nav-link <?= $active === 'account' ? 'active' : '' ?>" href="/account.php">Minha conta</a>
          <form action="/logout.php" method="post" class="m-0"><?= csrf_field() ?><button type="submit" class="btn btn-outline-secondary btn-sm ms-lg-2">Sair</button></form>
        <?php else: ?>
          <a class="btn btn-primary btn-sm px-4 ms-lg-2" href="/login.php">Acessar painel</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>
<?php if ($flash): ?>
<div class="container pt-4"><div class="alert alert-<?= e(in_array($flash['type'], ['success','danger','warning','info'], true) ? $flash['type'] : 'info') ?> alert-dismissible fade show" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div></div>
<?php endif; ?>
<?php
}

function render_footer(): void
{
    ?>
<footer class="footer mt-auto py-5"><div class="container d-flex flex-wrap justify-content-between gap-2"><span>© <?= date('Y') ?> <?= e(setting('site_name', 'Linkly')) ?></span><span>URLs sob seu controle · PHP 8.3</span></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="/assets/app.js" defer></script>
</body></html>
<?php
}
