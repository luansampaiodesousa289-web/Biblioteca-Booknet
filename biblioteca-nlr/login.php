<?php
require_once __DIR__ . '/config/auth.php';

if (user()) redirect(is_admin() ? 'admin/dashboard.php' : 'aluno/dashboard.php');

$error = '';
$emailDigitado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $emailDigitado = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $email = filter_var($emailDigitado, FILTER_VALIDATE_EMAIL);
    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';

    if (!$email || $senha === '') {
        $error = 'Informe um e-mail e uma senha válidos.';
    } else {
        $s = db()->prepare('SELECT * FROM usuarios WHERE email = ? AND status = \'ativo\' LIMIT 1');
        $s->execute([$email]);
        $u = $s->fetch();
        if (!$u || !password_verify($senha, $u['senha'])) {
            $error = 'E-mail ou senha incorretos.';
        } else {
            login_user($u);
            redirect(is_admin() ? 'admin/dashboard.php' : 'aluno/dashboard.php');
        }
    }
}

$pageTitle = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-page">
<form class="auth-card" method="post" id="login-form">
    <a class="auth-back" href="<?= base_url('index.php') ?>">← Voltar ao início</a>
    <a class="brand" href="<?= base_url('index.php') ?>"><span class="brand-mark">B</span><span>Biblioteca BookNet<small>Antonio Branco</small></span></a>

    <div class="auth-tabs" role="tablist">
        <a class="active" href="<?= base_url('login.php') ?>" aria-current="page">Entrar</a>
        <a href="<?= base_url('cadastro.php') ?>">Criar conta</a>
    </div>

    <h1>Que bom ver você.</h1>
    <p class="subtext">Entre para acessar a sua biblioteca.</p>

    <?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="email">E-mail</label>
        <input required type="email" id="email" name="email" autocomplete="email" inputmode="email" autocapitalize="none" value="<?= e($emailDigitado) ?>" <?= $error ? '' : 'autofocus' ?>>
    </div>
    <div class="form-group">
        <label for="senha" style="display:flex;justify-content:space-between"><span>Senha</span><a style="color:var(--blue);font-weight:500" href="<?= base_url('recuperar-senha.php') ?>">Esqueci a senha</a></label>
        <div class="password-field">
            <input required type="password" id="senha" name="senha" autocomplete="current-password" <?= $error ? 'autofocus' : '' ?>>
            <button type="button" class="password-toggle" id="toggle-senha" aria-label="Mostrar senha">Mostrar</button>
        </div>
    </div>
    <button class="btn" style="width:100%" id="login-submit">Entrar</button>
    <p class="auth-alt">Ainda não tem conta? <a href="<?= base_url('cadastro.php') ?>">Cadastre-se</a></p>
</form>
</main>
<script>
(function () {
    const senha = document.getElementById('senha');
    const t = document.getElementById('toggle-senha');
    t.addEventListener('click', function () {
        const mostrar = senha.type === 'password';
        senha.type = mostrar ? 'text' : 'password';
        t.textContent = mostrar ? 'Ocultar' : 'Mostrar';
    });
    document.getElementById('login-form').addEventListener('submit', function () {
        const b = document.getElementById('login-submit');
        b.disabled = true;
        b.textContent = 'Entrando...';
    });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
