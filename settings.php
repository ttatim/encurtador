<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_login();
if (($_SESSION['user_role'] ?? '') !== 'admin') { http_response_code(403); exit('Acesso restrito ao administrador.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['site_name'] ?? ''));
    $description = trim((string) ($_POST['site_description'] ?? ''));
    if ($name === '' || mb_strlen($name) > 80 || mb_strlen($description) > 240) {
        $error = 'Nome obrigatório (até 80 caracteres) e descrição de até 240 caracteres.';
    } else {
        try {
            $logo = safe_image_upload('site_logo', setting('site_logo'));
            $favicon = safe_image_upload('site_favicon', setting('site_favicon'));
            save_setting('site_name', $name);
            save_setting('site_description', $description);
            save_setting('site_logo', $logo);
            save_setting('site_favicon', $favicon);
            flash('success', 'Identidade visual atualizada.');
            redirect_to('/settings.php');
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}
render_header('Configurações', 'settings');
?>
<main class="page-section"><div class="container" style="max-width:880px"><span class="eyebrow">PERSONALIZAÇÃO</span><h1 class="page-title h2 mt-3 mb-2">Configurações da plataforma</h1><p class="text-secondary mb-4">Defina a identidade visual exibida na página inicial e no painel.</p>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="panel-card"><form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
<div class="mb-3"><label class="form-label fw-semibold" for="site_name">Nome da plataforma</label><input class="form-control" id="site_name" name="site_name" maxlength="80" required value="<?= e(setting('site_name', 'Linkly')) ?>"></div>
<div class="mb-3"><label class="form-label fw-semibold" for="site_description">Descrição do site</label><textarea class="form-control" id="site_description" name="site_description" maxlength="240" rows="3"><?= e(setting('site_description', 'Links mais curtos. Resultados maiores.')) ?></textarea></div>
<div class="row g-3 mb-4"><div class="col-md-6"><label class="form-label fw-semibold" for="site_logo">Logo</label><input type="file" class="form-control" id="site_logo" name="site_logo" accept="image/png,image/jpeg,image/webp,image/x-icon"><small class="text-secondary">Até 2 MB</small><?php if (setting('site_logo') !== ''): ?><div><img class="mt-2 rounded border p-2" style="max-height:90px;max-width:240px" src="<?= e(setting('site_logo')) ?>" alt="Logo atual"></div><?php endif; ?></div><div class="col-md-6"><label class="form-label fw-semibold" for="site_favicon">Favicon</label><input type="file" class="form-control" id="site_favicon" name="site_favicon" accept="image/png,image/jpeg,image/webp,image/x-icon"><small class="text-secondary">Até 2 MB</small><?php if (setting('site_favicon') !== ''): ?><div><img class="mt-2 rounded border p-2" style="max-height:60px" src="<?= e(setting('site_favicon')) ?>" alt="Favicon atual"></div><?php endif; ?></div></div>
<button class="btn btn-primary px-4 py-2" type="submit">Salvar configurações</button></form></div>
<div class="alert alert-info mt-4">O domínio dos links curtos é configurado na variável <code>APP_URL</code> do arquivo <code>.env</code>, para impedir alterações indevidas pelo painel.</div>
</div></main>
<?php render_footer(); ?>
