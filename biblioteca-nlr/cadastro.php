<?php
require_once __DIR__ . '/config/auth.php';

if (user()) redirect(is_admin() ? 'admin/dashboard.php' : 'aluno/dashboard.php');

$cursos = ['Ensino Fundamental 1', 'Ensino Fundamental 2', 'Ensino Médio', 'Ensino Médio Técnico'];
$old = ['nome' => '', 'email' => '', 'telefone' => '', 'ra' => '', 'turma' => '', 'curso' => ''];
$errors = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($old as $campo => $_) {
        $valor = $_POST[$campo] ?? '';
        $old[$campo] = is_string($valor) ? trim($valor) : '';
    }
    $old['email'] = mb_strtolower($old['email']);
    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
    $confirmar = is_string($_POST['confirmar_senha'] ?? null) ? $_POST['confirmar_senha'] : '';

    // Validação campo a campo, para o aluno saber exatamente o que corrigir.
    $palavras = preg_split('/\s+/u', $old['nome'], -1, PREG_SPLIT_NO_EMPTY);
    if (count($palavras) < 2 || mb_strlen($old['nome']) > 150) {
        $errors['nome'] = 'Informe seu nome e sobrenome.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 150) {
        $errors['email'] = 'Informe um e-mail válido.';
    }
    $digitos = preg_replace('/\D/', '', $old['telefone']);
    if ($old['telefone'] !== '' && !in_array(strlen($digitos), [10, 11], true)) {
        $errors['telefone'] = 'Use DDD + número, por exemplo (11) 91234-5678.';
    }
    if (!preg_match('/^[A-Za-z0-9.\-]{3,20}$/', $old['ra'])) {
        $errors['ra'] = 'O RA deve ter de 3 a 20 caracteres (letras, números, ponto ou hífen).';
    }
    if ($old['turma'] === '' || mb_strlen($old['turma']) > 80) {
        $errors['turma'] = 'Informe sua turma, por exemplo 1º A.';
    }
    if (!in_array($old['curso'], $cursos, true)) {
        $errors['curso'] = 'Selecione o curso.';
    }
    if (strlen($senha) < 8 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
        $errors['senha'] = 'A senha precisa ter 8 ou mais caracteres, com letras e números.';
    } elseif (strlen($senha) > 72) {
        $errors['senha'] = 'A senha pode ter no máximo 72 caracteres.';
    } elseif ($senha !== $confirmar) {
        $errors['confirmar_senha'] = 'As senhas não coincidem.';
    }

    // E-mail e RA já usados: mensagem separada para cada um.
    if (!$errors) {
        $dup = db()->prepare('SELECT email, ra FROM usuarios WHERE email = ? OR ra = ?');
        $dup->execute([$old['email'], $old['ra']]);
        foreach ($dup->fetchAll() as $row) {
            if (mb_strtolower((string)$row['email']) === $old['email']) {
                $errors['email'] = 'Este e-mail já está cadastrado. Tente entrar na sua conta.';
            }
            if ((string)$row['ra'] === $old['ra']) {
                $errors['ra'] = 'Este RA já está cadastrado.';
            }
        }
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare("INSERT INTO usuarios(nome,email,senha,tipo,telefone,ra,turma,curso,status) VALUES(?,?,?,'aluno',?,?,?,?,'ativo')");
            $stmt->execute([
                $old['nome'],
                $old['email'],
                password_hash($senha, PASSWORD_DEFAULT),
                $old['telefone'] !== '' ? $old['telefone'] : null,
                $old['ra'],
                $old['turma'],
                $old['curso'],
            ]);
            flash('success', 'Cadastro realizado! Agora você já pode entrar.');
            redirect('login.php');
        } catch (PDOException $e) {
            $error = 'Não foi possível concluir o cadastro. Confira se o e-mail e o RA ainda não estão cadastrados e tente de novo.';
        }
    }

    if ($errors && $error === '') $error = 'Corrija os campos destacados abaixo.';
}

// Ajudantes de exibição (erro e destaque do campo).
$erro = static fn(string $c): string => isset($errors[$c])
    ? '<small class="field-error" id="erro-' . $c . '">' . e($errors[$c]) . '</small>' : '';
$inv = static fn(string $c): string => isset($errors[$c])
    ? ' class="is-invalid" aria-invalid="true" aria-describedby="erro-' . $c . '"' : '';

$pageTitle = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-page">
<form class="auth-card auth-card-wide" method="post" id="signup-form" novalidate>
    <a class="auth-back" href="<?= base_url('index.php') ?>">← Voltar ao início</a>
    <a class="brand" href="<?= base_url('index.php') ?>"><span class="brand-mark">B</span><span>Biblioteca BookNet<small>Antonio Branco</small></span></a>
    <div class="auth-tabs" role="tablist">
        <a href="<?= base_url('login.php') ?>">Entrar</a>
        <a class="active" href="<?= base_url('cadastro.php') ?>" aria-current="page">Criar conta</a>
    </div>
    <h1>Crie sua conta</h1>
    <p class="subtext">Leva menos de um minuto. Depois é só entrar e começar a ler.</p>

    <?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="nome">Nome completo <span class="req">*</span></label>
        <input required id="nome" name="nome" maxlength="150" autocomplete="name" value="<?= e($old['nome']) ?>"<?= $inv('nome') ?>>
        <?= $erro('nome') ?>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="email">E-mail <span class="req">*</span></label>
            <input required type="email" id="email" name="email" maxlength="150" autocomplete="email" value="<?= e($old['email']) ?>"<?= $inv('email') ?>>
            <?= $erro('email') ?>
        </div>
        <div class="form-group">
            <label for="telefone">Telefone <small>(opcional)</small></label>
            <input type="tel" id="telefone" name="telefone" maxlength="16" inputmode="numeric" autocomplete="tel" placeholder="(11) 91234-5678" value="<?= e($old['telefone']) ?>"<?= $inv('telefone') ?>>
            <?= $erro('telefone') ?>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="ra">RA <span class="req">*</span></label>
            <input required id="ra" name="ra" maxlength="20" autocomplete="off" value="<?= e($old['ra']) ?>"<?= $inv('ra') ?>>
            <?= $erro('ra') ?>
        </div>
        <div class="form-group">
            <label for="turma">Turma <span class="req">*</span></label>
            <input required id="turma" name="turma" maxlength="80" placeholder="1º A" value="<?= e($old['turma']) ?>"<?= $inv('turma') ?>>
            <?= $erro('turma') ?>
        </div>
    </div>

    <div class="form-group">
        <label for="curso">Curso <span class="req">*</span></label>
        <select required id="curso" name="curso"<?= $inv('curso') ?>>
            <option value="">Selecione o curso</option>
            <?php foreach ($cursos as $c): ?>
                <option <?= $old['curso'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $erro('curso') ?>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="senha">Senha <span class="req">*</span></label>
            <div class="password-field">
                <input required minlength="8" maxlength="72" type="password" id="senha" name="senha" autocomplete="new-password"<?= $inv('senha') ?>>
                <button type="button" class="password-toggle" data-toggle="senha" aria-label="Mostrar senha">Mostrar</button>
            </div>
            <div class="strength" id="strength" data-level="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <small class="form-hint" id="strength-text">Mínimo de 8 caracteres, com letras e números.</small>
            <?= $erro('senha') ?>
        </div>
        <div class="form-group">
            <label for="confirmar_senha">Confirmar senha <span class="req">*</span></label>
            <div class="password-field">
                <input required minlength="8" maxlength="72" type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password"<?= $inv('confirmar_senha') ?>>
                <button type="button" class="password-toggle" data-toggle="confirmar_senha" aria-label="Mostrar confirmação de senha">Mostrar</button>
            </div>
            <small class="form-hint match-msg" id="match-msg"></small>
            <?= $erro('confirmar_senha') ?>
        </div>
    </div>

    <button class="btn" style="width:100%" id="signup-submit">Criar minha conta</button>
    <p class="auth-alt">Já tem conta? <a href="<?= base_url('login.php') ?>">Entrar</a></p>
</form>
</main>

<script>
(function () {
    const form = document.getElementById('signup-form');
    const senha = document.getElementById('senha');
    const conf = document.getElementById('confirmar_senha');
    const tel = document.getElementById('telefone');
    const strength = document.getElementById('strength');
    const strengthText = document.getElementById('strength-text');
    const matchMsg = document.getElementById('match-msg');

    // Mostrar / ocultar senha
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.toggle);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Ocultar' : 'Mostrar';
        });
    });

    // Máscara de telefone: (11) 91234-5678
    tel.addEventListener('input', function () {
        const d = tel.value.replace(/\D/g, '').slice(0, 11);
        let v = d;
        if (d.length > 10) v = '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
        else if (d.length > 6) v = '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
        else if (d.length > 2) v = '(' + d.slice(0, 2) + ') ' + d.slice(2);
        else if (d.length > 0) v = '(' + d;
        tel.value = v;
    });

    // Força da senha
    const labels = ['Digite uma senha', 'Senha fraca', 'Senha razoável', 'Senha boa', 'Senha forte'];
    function nivel(s) {
        if (!s) return 0;
        let n = 0;
        if (s.length >= 8) n++;
        if (/[a-z]/.test(s) && /[A-Z]/.test(s)) n++;
        if (/\d/.test(s) && /[A-Za-z]/.test(s)) n++;
        if (s.length >= 12 || /[^A-Za-z0-9]/.test(s)) n++;
        return Math.max(1, n);
    }
    function atualizaSenha() {
        const n = nivel(senha.value);
        strength.dataset.level = n;
        strengthText.textContent = senha.value ? labels[n] : 'Mínimo de 8 caracteres, com letras e números.';
        atualizaConfirmacao();
    }
    function atualizaConfirmacao() {
        if (!conf.value) { matchMsg.textContent = ''; matchMsg.className = 'form-hint match-msg'; return; }
        const ok = conf.value === senha.value;
        matchMsg.textContent = ok ? 'As senhas conferem.' : 'As senhas ainda não coincidem.';
        matchMsg.className = 'form-hint match-msg ' + (ok ? 'ok' : 'bad');
    }
    senha.addEventListener('input', atualizaSenha);
    conf.addEventListener('input', atualizaConfirmacao);

    // Validação no navegador + evita envio duplo
    form.addEventListener('submit', function (ev) {
        if (!form.checkValidity()) {
            ev.preventDefault();
            form.reportValidity();
            return;
        }
        if (senha.value !== conf.value) {
            ev.preventDefault();
            conf.focus();
            atualizaConfirmacao();
            return;
        }
        const b = document.getElementById('signup-submit');
        b.disabled = true;
        b.textContent = 'Criando conta...';
    });

    atualizaSenha();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
