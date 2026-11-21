<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$cat = is_string($_GET['categoria'] ?? null) ? trim($_GET['categoria']) : '';
$filtrando = ($q !== '' || $cat !== '');

$where = "arquivo_pdf IS NOT NULL AND arquivo_pdf <> ''";
$params = [];
if ($q !== '') {
    $where .= ' AND (titulo LIKE ? OR autor LIKE ? OR isbn LIKE ? OR codigo_barras LIKE ?)';
    $params = array_fill(0, 4, '%' . $q . '%');
}
if ($cat !== '') {
    $where .= ' AND categoria = ?';
    $params[] = $cat;
}

$stmt = db()->prepare("SELECT id, titulo, autor, categoria, ano_publicacao, capa FROM livros WHERE $where ORDER BY titulo");
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = db()->query("SELECT DISTINCT categoria FROM livros WHERE arquivo_pdf IS NOT NULL AND arquivo_pdf <> '' AND categoria IS NOT NULL AND categoria <> '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

// Catálogo completo (id => título/capa) usado pela faixa "Continue lendo".
$catalogo = [];
foreach (db()->query("SELECT id, titulo, capa FROM livros WHERE arquivo_pdf IS NOT NULL AND arquivo_pdf <> ''")->fetchAll() as $l) {
    $catalogo[(int)$l['id']] = [
        'titulo' => $l['titulo'],
        'capa' => $l['capa'] ? base_url('assets/uploads/capas/' . $l['capa']) : '',
    ];
}

$total = count($books);
$pageTitle = 'Ler livros digitais';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/dashboard-nav.php';
?>
<?php if (!$filtrando): ?>
<section class="panel" id="continue-reading" hidden
         style="margin-top:0"
         data-books="<?= e(json_encode($catalogo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
         data-reader="<?= e(base_url('aluno/ler-livro.php')) ?>">
    <h2>Continue lendo</h2>
    <div class="continue-strip" id="continue-list"></div>
</section>
<?php endif; ?>

<form class="filterbar" method="get">
    <div class="searchbar"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar por título, autor ou ISBN..." autocomplete="off" aria-label="Pesquisar livro digital"></div>
    <select name="categoria" aria-label="Categoria" onchange="this.form.submit()">
        <option value="">Todas as categorias</option>
        <?php foreach ($categories as $c): ?>
            <option <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-small">Filtrar</button>
    <?php if ($filtrando): ?><a class="btn btn-small btn-outline" href="<?= base_url('aluno/livros-digitais.php') ?>">Limpar</a><?php endif; ?>
</form>

<div class="list-head">
    <p class="subtext"><?= $total ?> <?= $total === 1 ? 'livro digital' : 'livros digitais' ?><?= $filtrando ? ' encontrado(s)' : ' para ler agora, sem sair do navegador' ?>.</p>
</div>

<div class="book-grid">
    <?php foreach ($books as $l): ?>
        <?php $leitor = base_url('aluno/ler-livro.php?id=' . (int)$l['id']); ?>
        <article class="book-card">
            <?php if ($l['capa']): ?>
                <img class="cover" loading="lazy" src="<?= base_url('assets/uploads/capas/' . $l['capa']) ?>" alt="Capa de <?= e($l['titulo']) ?>">
            <?php else: ?>
                <div class="cover cover-placeholder"><?= e($l['titulo']) ?></div>
            <?php endif; ?>
            <div class="book-info">
                <span class="badge">PDF disponível</span>
                <h3 style="margin-top:10px"><a class="stretched" href="<?= $leitor ?>"><?= e($l['titulo']) ?></a></h3>
                <p><?= e($l['autor']) ?><br><?= e($l['categoria']) ?><?= ($l['categoria'] && $l['ano_publicacao']) ? ' · ' : '' ?><?= $l['ano_publicacao'] ? e((string)$l['ano_publicacao']) : '' ?></p>
                <div class="book-actions">
                    <span class="btn btn-small">Ler agora</span>
                    <a href="<?= base_url('aluno/livro.php?id=' . (int)$l['id']) ?>">Detalhes</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$books): ?>
        <div class="empty">
            <?= $filtrando ? 'Nenhum livro digital encontrado para essa busca.' : 'Ainda não há livros digitais disponíveis.' ?>
        </div>
    <?php endif; ?>
</div>

<script>
// "Continue lendo": últimos livros abertos neste dispositivo.
(function () {
    const box = document.getElementById('continue-reading');
    if (!box) return;
    let books = {};
    let recent = [];
    try { books = JSON.parse(box.dataset.books) || {}; } catch (e) {}
    try { recent = JSON.parse(localStorage.getItem('booknet-recent-books') || '[]'); } catch (e) {}
    if (!Array.isArray(recent)) return;

    const list = document.getElementById('continue-list');
    let shown = 0;
    recent.forEach(function (item) {
        const b = item && books[item.id];
        if (!b || shown >= 4) return;
        shown++;

        const a = document.createElement('a');
        a.className = 'continue-item';
        a.href = box.dataset.reader + '?id=' + encodeURIComponent(item.id);

        if (b.capa) {
            const img = document.createElement('img');
            img.src = b.capa;
            img.alt = '';
            a.appendChild(img);
        } else {
            const th = document.createElement('span');
            th.className = 'thumb';
            a.appendChild(th);
        }

        const txt = document.createElement('span');
        const strong = document.createElement('strong');
        strong.textContent = b.titulo;
        const small = document.createElement('small');
        small.textContent = 'Retomar leitura';
        txt.appendChild(strong);
        txt.appendChild(small);
        a.appendChild(txt);
        list.appendChild(a);
    });
    if (shown) box.hidden = false;
})();
</script>
<?php require __DIR__ . '/../includes/dashboard-footer.php'; ?>
