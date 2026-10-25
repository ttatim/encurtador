<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
require_login();
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $now = time();
    $lockedUntil = (int) ($_SESSION['password_change_locked_until'] ?? 0);
    $old = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if ($lockedUntil > $now) {
        http_response_code(429);
        $error = 'Muitas tentativas. Aguarde 15 minutos.';
    } elseif (!valid_user_password($new) || !hash_equals($new, $confirm) || $old === $new) {
        $error = 'Informe uma nova senha diferente da atual, entre 12 e 200 caracteres, e confirme-a.';
    } else {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([current_user_id()]);
        $hash = $stmt->fetchColumn();
        if (!is_string($hash) || !password_verify($old, $hash)) {
            $attempts = (int) ($_SESSION['password_change_failures'] ?? 0) + 1;
            $_SESSION['password_change_failures'] = $attempts;
            if ($attempts >= 5) { $_SESSION['password_change_locked_until'] = $now + 900; }
            $error = 'Senha atual incorreta.';
        } else {
            $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), current_user_id()]);
            unset($_SESSION['password_change_failures'], $_SESSION['password_change_locked_until']);
            session_regenerate_id(true);
            flash('success', 'Senha alterada com sucesso.');
            redirect_to('/account.php');
        }
    }
}
render_header('Minha conta', 'account');
?>
<main class="page-section"><div class="container" style="max-width:720px">
<span class="eyebrow">SEGURANÇA</span><h1 class="page-title h2 mt-3">Minha conta</h1>
<p class="text-secondary mb-4">Altere sua senha de acesso. Nunca reutilize senhas de outros serviços.</p>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="panel-card"><h2 class="h5 fw-bold mb-3">Alterar senha</h2>
<form method="post" autocomplete="on"><?= csrf_field() ?>
<div class="mb-3"><label class="form-label" for="current_password">Senha atual</label><input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required></div>
<div class="mb-3"><label class="form-label" for="new_password">Nova senha</label><input class="form-control" type="password" id="new_password" name="new_password" minlength="12" maxlength="200" autocomplete="new-password" required><div class="form-text">De 12 a 200 caracteres.</div></div>
<div class="mb-4"><label class="form-label" for="confirm_password">Confirme a nova senha</label><input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="12" maxlength="200" autocomplete="new-password" required></div>
<button class="btn btn-primary px-4" type="submit">Atualizar senha</button>
</form></div></div></main>
<?php render_footer(); ?>
