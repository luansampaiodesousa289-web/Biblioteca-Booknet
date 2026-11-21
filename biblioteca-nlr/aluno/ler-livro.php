<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT id, titulo, autor, arquivo_pdf FROM livros WHERE id = ?');
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book || !$book['arquivo_pdf']) {
    http_response_code(404);
    exit('Livro digital não encontrado.');
}

$pdfUrl = base_url('aluno/ler-pdf.php?id=' . (int)$book['id']);

$pageTitle = $book['titulo'];
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/dashboard-nav.php';
?>
<div class="reader-bar">
    <div class="reader-title">
        <h2><?= e($book['titulo']) ?></h2>
        <p><?= e($book['autor']) ?></p>
    </div>
    <div class="reader-actions">
        <a class="btn btn-small btn-outline" href="<?= base_url('aluno/livros-digitais.php') ?>">← Biblioteca digital</a>
        <a class="btn btn-small btn-outline" href="<?= base_url('aluno/livro.php?id=' . (int)$book['id']) ?>">Detalhes</a>
        <button type="button" class="btn btn-small btn-outline" id="fullscreen-btn">Tela cheia</button>
        <a class="btn btn-small" href="<?= $pdfUrl ?>" target="_blank" rel="noopener">Abrir em nova aba</a>
    </div>
</div>

<p class="notice reader-tip">Se aparecer só a primeira página, toque em <b>Abrir em nova aba</b> para ler o livro inteiro.</p>

<div class="reader-frame" id="reader-frame">
    <iframe id="pdf-frame" src="<?= $pdfUrl ?>#view=FitH" title="Leitura de <?= e($book['titulo']) ?>"></iframe>
</div>
<p class="subtext" style="font-size:13px">Seu navegador não mostra o PDF aqui? <a style="color:var(--blue)" href="<?= $pdfUrl ?>" target="_blank" rel="noopener">Abra o livro em uma nova aba</a>.</p>

<script>
(function () {
    // Guarda o livro em "Continue lendo" (apenas neste dispositivo).
    try {
        const key = 'booknet-recent-books';
        const id = <?= (int)$book['id'] ?>;
        let recent = JSON.parse(localStorage.getItem(key) || '[]');
        if (!Array.isArray(recent)) recent = [];
        recent = recent.filter(function (r) { return r && r.id !== id; });
        recent.unshift({ id: id, ts: Date.now() });
        localStorage.setItem(key, JSON.stringify(recent.slice(0, 12)));
    } catch (e) {}

    const frame = document.getElementById('reader-frame');
    const btn = document.getElementById('fullscreen-btn');
    if (!frame.requestFullscreen) { btn.hidden = true; return; }
    btn.addEventListener('click', function () {
        if (document.fullscreenElement) document.exitFullscreen();
        else frame.requestFullscreen();
    });
})();
</script>
<?php require __DIR__ . '/../includes/dashboard-footer.php'; ?>
