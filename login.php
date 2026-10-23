<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
if (logged_in()) { redirect_to('/dashboard.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $identity = hash('sha256', $clientIp . '|' . $email . '|' . app_env('DB_PASS'));
    $stmt = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE identity_hash = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)');
    $stmt->execute([$identity]);
    $attempts = (int) $stmt->fetchColumn();
    if ($attempts >= 5) {
        http_response_code(429);
        $error = 'Muitas tentativas. Aguarde 15 minutos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) > 200) {
        $error = 'Credenciais inválidas.';
    } else {
        $stmt = db()->prepare('SELECT id, name, role, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            db()->prepare('DELETE FROM login_attempts WHERE identity_hash = ?')->execute([$identity]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            redirect_to('/dashboard.php');
        }
        db()->prepare('INSERT INTO login_attempts (identity_hash) VALUES (?)')->execute([$identity]);
        $error = 'Credenciais inválidas.';
    }
}
render_header('Entrar');
?>
<main class="page-section"><div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5"><div class="panel-card">
  <h1 class="h3 fw-bold mb-2">Bem-vindo de volta</h1><p class="text-secondary mb-4">Acesse seu painel para gerenciar os links.</p>
  <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="on"><?= csrf_field() ?>
    <div class="mb-3"><label class="form-label fw-semibold" for="email">E-mail</label><input type="email" class="form-control" id="email" name="email" value="<?= e((string) ($_POST['email'] ?? '')) ?>" required autocomplete="username" maxlength="190"></div>
    <div class="mb-4"><label class="form-label fw-semibold" for="password">Senha</label><input type="password" class="form-control" id="password" name="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary w-100 py-3">Entrar no painel</button>
  </form><p class="small text-secondary mt-4 mb-0">O primeiro administrador é criado pelo instalador CLI. O cadastro público está desativado por segurança.</p>
</div></div></div></div></main>
<?php render_footer(); ?>
