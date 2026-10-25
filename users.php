<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_admin();
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'editor');
        if ($name === '' || mb_strlen($name) > 120 || !valid_user_email($email)
            || !valid_user_password($password) || !in_array($role, ['admin', 'editor'], true)) {
            $error = 'Confira nome, e-mail, senha (12 a 200 caracteres) e perfil.';
        } else {
            try {
                $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash('success', 'Usuário criado. Envie a senha inicial por um canal seguro.');
                redirect_to('/users.php');
            } catch (PDOException $exception) {
                error_log('User create failed: ' . $exception->getCode());
                $error = $exception->getCode() === '23000' ? 'E-mail já cadastrado.' : 'Não foi possível cadastrar o usuário.';
            }
        }
    } elseif ($action === 'role') {
        $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
        $role = (string) ($_POST['role'] ?? '');
        if ($targetId === 0 || !in_array($role, ['admin', 'editor'], true) || $targetId === current_user_id()) {
            $error = 'Alteração inválida. Não é permitido alterar seu próprio perfil.';
        } else {
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
                $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? FOR UPDATE');
                $stmt->execute([$targetId]);
                $previous = $stmt->fetchColumn();
                if ($previous === false) {
                    throw new DomainException('Usuário não encontrado.');
                }
                if ($previous === 'admin' && $role !== 'admin' && count($admins) < 2) {
                    throw new DomainException('É necessário manter pelo menos um administrador.');
                }
                $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                $stmt->execute([$role, $targetId]);
                $pdo->commit();
                flash('success', 'Permissão atualizada.');
                redirect_to('/users.php');
            } catch (DomainException $exception) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = $exception->getMessage();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log('User role update failed: ' . $exception->getCode());
                $error = 'Não foi possível alterar a permissão.';
            }
        }
    } else {
        $error = 'Operação inválida.';
    }
}
$page = max(1, min(100000, (int) ($_GET['page'] ?? 1)));
$limit = 20;
$total = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stmt = db()->prepare('SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, ($page - 1) * $limit, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();
render_header('Usuários', 'users');
?>
<main class="page-section"><div class="container" style="max-width:1100px">
<span class="eyebrow">ADMINISTRAÇÃO</span><h1 class="page-title h2 mt-3">Usuários e permissões</h1>
<p class="text-secondary mb-4">Administradores gerenciam a plataforma. Editores gerenciam apenas os próprios links.</p>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="panel-card mb-4"><h2 class="h5 fw-bold mb-3">Novo usuário</h2>
<form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="create">
<div class="row g-3">
<div class="col-md-6"><label class="form-label" for="name">Nome</label><input class="form-control" name="name" id="name" maxlength="120" required></div>
<div class="col-md-6"><label class="form-label" for="email">E-mail</label><input class="form-control" type="email" name="email" id="email" maxlength="190" required></div>
<div class="col-md-6"><label class="form-label" for="password">Senha inicial</label><input class="form-control" type="password" name="password" id="password" minlength="12" maxlength="200" autocomplete="new-password" required><div class="form-text">Mínimo de 12 caracteres; entregue por canal seguro.</div></div>
<div class="col-md-4"><label class="form-label" for="role">Perfil</label><select class="form-select" name="role" id="role"><option value="editor">Editor</option><option value="admin">Administrador</option></select></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Cadastrar</button></div>
</div></form></div>
<div class="panel-card"><h2 class="h5 fw-bold mb-3">Usuários cadastrados <span class="text-secondary small">(<?= $total ?>)</span></h2>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Usuário</th><th>E-mail</th><th>Perfil</th><th>Ação</th></tr></thead><tbody>
<?php foreach ($users as $user): ?><tr>
<td><?= e($user['name']) ?><?= (int) $user['id'] === current_user_id() ? ' (você)' : '' ?></td>
<td><?= e($user['email']) ?></td><td><?= e($user['role'] === 'admin' ? 'Administrador' : 'Editor') ?></td>
<td><?php if ((int) $user['id'] !== current_user_id()): ?>
<form method="post" class="d-flex gap-2 align-items-center"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
<select name="role" class="form-select form-select-sm" aria-label="Perfil de <?= e($user['name']) ?>"><option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Editor</option><option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrador</option></select>
<button class="btn btn-sm btn-outline-primary" type="submit">Salvar</button></form>
<?php else: ?><span class="text-secondary small">Perfil atual</span><?php endif; ?></td>
</tr><?php endforeach; ?><?php if (!$users): ?><tr><td colspan="4">Nenhum usuário cadastrado.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if ($total > $limit): ?><nav class="d-flex justify-content-end gap-2" aria-label="Paginação de usuários"><?php if ($page > 1): ?><a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page - 1 ?>">Anterior</a><?php endif; ?><?php if ($page * $limit < $total): ?><a class="btn btn-outline-secondary btn-sm" href="?page=<?= $page + 1 ?>">Próxima</a><?php endif; ?></nav><?php endif; ?>
</div></div></main>
<?php render_footer(); ?>
