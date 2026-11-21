<footer class="site-footer">
    <div><strong>Biblioteca BookNet</strong><br><span><?= date('Y') ?></span></div>
    <nav class="footer-links" aria-label="Rodapé">
        <a href="<?= base_url('index.php') ?>">Início</a>
        <a href="<?= base_url('aluno/livros.php') ?>">Livros</a>
        <a href="<?= base_url('sobre.php') ?>">Sobre</a>
        <?php if (function_exists('user') && user()): ?>
            <a href="<?= base_url(is_admin() ? 'admin/dashboard.php' : 'aluno/dashboard.php') ?>">Minha área</a>
        <?php else: ?>
            <a href="<?= base_url('login.php') ?>">Entrar</a>
            <a href="<?= base_url('cadastro.php') ?>">Criar conta</a>
        <?php endif; ?>
    </nav>
</footer></body></html>
