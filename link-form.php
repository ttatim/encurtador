<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_login();
$id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$link = $id > 0 ? link_by_id($id) : null;
if ($id > 0 && !$link) { http_response_code(404); require __DIR__ . '/pagina-de-erro.php'; exit; }
$data = $link ?: ['destination_url' => '', 'alias' => '', 'title' => '', 'description' => '', 'image_path' => '', 'redirect_code' => 302, 'is_active' => 1];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['destination_url'] = trim((string) ($_POST['destination_url'] ?? ''));
    $data['alias'] = trim((string) ($_POST['alias'] ?? ''));
    $data['title'] = trim((string) ($_POST['title'] ?? ''));
    $data['description'] = trim((string) ($_POST['description'] ?? ''));
    $data['redirect_code'] = (int) ($_POST['redirect_code'] ?? 302);
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    if (!validate_destination($data['destination_url'])) {
        $error = 'Informe uma URL HTTP ou HTTPS válida (máximo de 2.048 caracteres).';
    } elseif (($id > 0 && $data['alias'] === '') || ($data['alias'] !== '' && !validate_alias($data['alias']))) {
        $error = 'Alias inválido. Use 3 a 50 caracteres: letras, números, hífen ou sublinhado; evite nomes reservados.';
    } elseif ($data['title'] === '' || mb_strlen($data['title']) > 160 || mb_strlen($data['description']) > 500) {
        $error = 'Informe um título (até 160 caracteres) e uma descrição de até 500 caracteres.';
    } elseif (!in_array($data['redirect_code'], [301, 302], true)) {
        $error = 'Tipo de redirecionamento inválido.';
    } else {
        try {
            $data['image_path'] = safe_image_upload('image', $data['image_path']);
            if ($id > 0) {
                $stmt = db()->prepare('UPDATE links SET destination_url=?, alias=?, title=?, description=?, image_path=?, redirect_code=?, is_active=? WHERE id=? AND user_id=?');
                $stmt->execute([$data['destination_url'], $data['alias'], $data['title'], $data['description'], $data['image_path'], $data['redirect_code'], $data['is_active'], $id, current_user_id()]);
            } else {
                $stmt = db()->prepare('INSERT INTO links (user_id, destination_url, alias, title, description, image_path, redirect_code, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $saved = false;
                for ($attempt = 0; $attempt < 5 && !$saved; $attempt++) {
                    $alias = $data['alias'] !== '' ? $data['alias'] : new_alias();
                    try {
                        $stmt->execute([current_user_id(), $data['destination_url'], $alias, $data['title'], $data['description'], $data['image_path'], $data['redirect_code'], $data['is_active']]);
                        $saved = true;
                    } catch (PDOException $exception) {
                        if ($exception->getCode() !== '23000' || $data['alias'] !== '') { throw $exception; }
                    }
                }
                if (!$saved) { throw new RuntimeException('Não foi possível gerar um alias único.'); }
            }
            flash('success', $id > 0 ? 'Link atualizado com sucesso.' : 'Link criado com sucesso.');
            redirect_to('/dashboard.php');
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000' ? 'Este alias já está em uso. Escolha outro.' : 'Não foi possível salvar o link.';
            error_log('Link save failed: ' . $exception->getCode());
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}
render_header($id ? 'Editar link' : 'Novo link', 'dashboard');
?>
<main class="page-section"><div class="container" style="max-width:920px"><a class="text-decoration-none text-secondary" href="/dashboard.php">← Voltar ao painel</a><div class="d-flex justify-content-between align-items-center mt-3 mb-4"><div><h1 class="page-title h2 mb-1"><?= $id ? 'Editar link' : 'Criar novo link' ?></h1><p class="text-secondary mb-0">Personalize a URL e as informações de compartilhamento.</p></div></div>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="panel-card"><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="mb-3"><label for="destination_url" class="form-label fw-semibold">URL de destino *</label><input type="url" class="form-control" id="destination_url" name="destination_url" value="<?= e($data['destination_url']) ?>" placeholder="https://exemplo.com.br/produto" required maxlength="2048"><div class="form-text">O visitante será encaminhado para este endereço.</div></div>
  <div class="row g-3"><div class="col-md-7"><label for="alias" class="form-label fw-semibold">URL curta personalizada</label><div class="input-group"><span class="input-group-text"><?= e(rtrim(app_url(), '/')) ?>/</span><input type="text" class="form-control" id="alias" name="alias" value="<?= e($data['alias']) ?>" placeholder="minha-campanha" maxlength="50" pattern="[A-Za-z0-9][A-Za-z0-9_-]{2,49}"></div><div class="form-text">Deixe vazio para gerar automaticamente. De 3 a 50 caracteres.</div></div><div class="col-md-5"><label for="redirect_code" class="form-label fw-semibold">Redirecionamento</label><select class="form-select" id="redirect_code" name="redirect_code"><option value="302" <?= (int) $data['redirect_code'] === 302 ? 'selected' : '' ?>>302 · Temporário (recomendado)</option><option value="301" <?= (int) $data['redirect_code'] === 301 ? 'selected' : '' ?>>301 · Permanente</option></select></div></div>
  <hr class="my-4"><h2 class="h5 fw-bold">Informações da página</h2><p class="text-secondary small">Usadas na página de pré-visualização em /p/seu-alias. O link curto redireciona diretamente.</p>
  <div class="mb-3"><label for="title" class="form-label fw-semibold">Título *</label><input class="form-control" id="title" name="title" maxlength="160" required value="<?= e($data['title']) ?>" placeholder="Nome da campanha"></div>
  <div class="mb-3"><label for="description" class="form-label fw-semibold">Descrição</label><textarea class="form-control" id="description" name="description" maxlength="500" rows="3" placeholder="Descreva o conteúdo do link"><?= e($data['description']) ?></textarea></div>
  <div class="mb-3"><label for="image" class="form-label fw-semibold">Imagem / logo do link</label><input type="file" class="form-control" id="image" name="image" accept="image/png,image/jpeg,image/webp,image/x-icon" aria-describedby="imageHelp"><div id="imageHelp" class="form-text">PNG, JPG, WEBP ou ICO; até 2 MB.</div><?php if ($data['image_path'] !== ''): ?><img src="<?= e($data['image_path']) ?>" alt="Imagem atual" class="mt-2 rounded border" style="max-height:100px;max-width:220px"><?php endif; ?></div>
  <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" <?= $data['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="is_active">Link ativo</label></div>
  <div class="d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary px-4 py-2">Salvar link</button><a href="/dashboard.php" class="btn btn-outline-secondary px-4 py-2">Cancelar</a></div>
</form></div>
<?php if ($id): ?><div class="mt-4 text-end"><form method="post" action="/link-delete.php" onsubmit="return confirm('Excluir permanentemente este link e suas estatísticas?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button type="submit" class="btn btn-outline-danger">Excluir link</button></form></div><?php endif; ?>
</div></main>
<?php render_footer(); ?>
