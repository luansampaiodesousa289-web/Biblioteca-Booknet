<?php
require_once __DIR__ . '/config/auth.php';

$counts = ['livros' => 0, 'alunos' => 0, 'emprestimos' => 0];
$popular = [];
try {
    $counts['livros'] = db()->query('SELECT COALESCE(SUM(quantidade),0) FROM livros')->fetchColumn();
    $counts['alunos'] = db()->query("SELECT COUNT(*) FROM usuarios WHERE tipo='aluno'")->fetchColumn();
    $counts['emprestimos'] = db()->query('SELECT COUNT(*) FROM emprestimos')->fetchColumn();
    $popular = db()->query('SELECT l.*, COUNT(e.id) total FROM livros l LEFT JOIN emprestimos e ON e.livro_id=l.id GROUP BY l.id ORDER BY total DESC, l.created_at DESC LIMIT 4')->fetchAll();
} catch (Throwable $e) {
}

$logado = user();
$minhaArea = base_url(is_admin() ? 'admin/dashboard.php' : 'aluno/dashboard.php');

$pageTitle = 'Biblioteca BookNet';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/public-nav.php';
?>
<section class="hero">
    <div>
        <span class="eyebrow">Biblioteca BookNet</span>
        <h1>Seu conhecimento começa aqui.</h1>
        <p>Encontre livros, leia online, faça quizzes e acompanhe seus empréstimos, tudo em um só lugar, no celular ou no computador.</p>
        <div class="hero-actions">
            <?php if ($logado): ?>
                <a class="btn btn-light" href="<?= $minhaArea ?>">Ir para minha área</a>
                <a class="btn btn-ghost" href="<?= base_url('aluno/livros.php') ?>">Explorar livros</a>
            <?php else: ?>
                <a class="btn btn-light" href="<?= base_url('cadastro.php') ?>">Criar conta</a>
                <a class="btn btn-ghost" href="<?= base_url('login.php') ?>">Entrar</a>
            <?php endif; ?>
        </div>
        <?php if (!$logado): ?>
            <p class="hero-note">Já tem conta? <a href="<?= base_url('login.php') ?>">Entre aqui</a> ou <a href="<?= base_url('aluno/livros.php') ?>">explore o catálogo</a>.</p>
        <?php endif; ?>
    </div>
    <div class="hero-art"><div class="book-stack"></div></div>
</section>

<div class="stats">
    <div class="stat"><b><?= number_format((int)$counts['livros'], 0, ',', '.') ?></b><span>livros no acervo</span></div>
    <div class="stat"><b><?= number_format((int)$counts['alunos'], 0, ',', '.') ?></b><span>alunos cadastrados</span></div>
    <div class="stat"><b><?= number_format((int)$counts['emprestimos'], 0, ',', '.') ?></b><span>empréstimos realizados</span></div>
    <div class="stat"><b>24h</b><span>acesso ao catálogo</span></div>
</div>

<section class="section">
    <div class="section-heading">
        <div>
            <span class="eyebrow" style="color:var(--blue)">Descubra</span>
            <h2 class="section-title">Livros em destaque</h2>
            <p class="subtext">Os títulos mais procurados pela nossa comunidade escolar.</p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('aluno/livros.php') ?>">Ver catálogo completo</a>
    </div>
    <div class="book-grid">
        <?php foreach ($popular as $l): ?>
            <article class="book-card">
                <?php if ($l['capa']): ?>
                    <img class="cover" loading="lazy" src="<?= base_url('assets/uploads/capas/' . $l['capa']) ?>" alt="Capa de <?= e($l['titulo']) ?>">
                <?php else: ?>
                    <div class="cover cover-placeholder"><?= e($l['titulo']) ?></div>
                <?php endif; ?>
                <div class="book-info">
                    <h3><?= e($l['titulo']) ?></h3>
                    <p><?= e($l['autor']) ?><?= $l['categoria'] ? ' · ' . e($l['categoria']) : '' ?></p>
                    <a class="btn btn-small" href="<?= base_url('aluno/livro.php?id=' . (int)$l['id']) ?>">Ver detalhes</a>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$popular): ?><div class="empty">O catálogo será exibido assim que os livros forem cadastrados.</div><?php endif; ?>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="section-heading">
        <div>
            <span class="eyebrow" style="color:var(--blue)">Como funciona</span>
            <h2 class="section-title">Comece em 3 passos</h2>
        </div>
    </div>
    <div class="steps">
        <div class="step"><span class="step-num">1</span><h3>Crie sua conta</h3><p>Use seu e-mail e o RA da escola. Leva menos de um minuto.</p></div>
        <div class="step"><span class="step-num">2</span><h3>Escolha um livro</h3><p>Leia livros digitais na hora ou peça um exemplar à equipe da biblioteca.</p></div>
        <div class="step"><span class="step-num">3</span><h3>Pratique e acompanhe</h3><p>Responda quizzes, veja seus empréstimos e acompanhe seu desempenho.</p></div>
    </div>
</section>

<?php if (!$logado): ?>
<section class="cta-band">
    <div>
        <h2>Pronto para começar a ler?</h2>
        <p>Crie sua conta ou entre para acessar o acervo completo.</p>
    </div>
    <div class="hero-actions" style="margin:0">
        <a class="btn btn-light" href="<?= base_url('cadastro.php') ?>">Criar conta</a>
        <a class="btn btn-ghost" href="<?= base_url('login.php') ?>">Entrar</a>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
