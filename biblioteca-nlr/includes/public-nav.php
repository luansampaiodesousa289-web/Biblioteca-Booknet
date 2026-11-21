<?php
$logado = user();
$atual = basename($_SERVER['SCRIPT_NAME'] ?? '');
$ativo = static fn(string $arq): string => $atual === $arq ? ' class="active" aria-current="page"' : '';
?>
<header class="nav">
    <a class="brand" href="<?= base_url('index.php') ?>"><span class="brand-mark">B</span><span>Biblioteca BookNet<small>Biblioteca Digital</small></span></a>
    <nav id="main-nav" aria-label="Menu principal">
        <a href="<?= base_url('index.php') ?>"<?= $ativo('index.php') ?>>Início</a>
        <a href="<?= base_url('aluno/livros.php') ?>"<?= $ativo('livros.php') ?>>Livros</a>
        <a href="<?= base_url('sobre.php') ?>"<?= $ativo('sobre.php') ?>>Sobre</a>
    </nav>
    <div class="nav-actions">
        <?php if ($logado): ?>
            <a class="btn btn-small" href="<?= base_url(is_admin() ? 'admin/dashboard.php' : 'aluno/dashboard.php') ?>">Minha área</a>
        <?php else: ?>
            <a class="btn btn-small btn-outline" href="<?= base_url('login.php') ?>">Entrar</a>
            <a class="btn btn-small" href="<?= base_url('cadastro.php') ?>">Criar conta</a>
        <?php endif; ?>
    </div>
    <button class="menu-toggle" aria-label="Abrir menu" aria-controls="main-nav">☰</button>
</header>
