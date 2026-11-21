<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();

$campos = ['codigo_barras', 'isbn', 'titulo', 'autor', 'categoria', 'editora', 'ano_publicacao', 'quantidade', 'descricao', 'biografia_autor'];
$d = array_fill_keys($campos, '');
$d['quantidade'] = '1';
$error = '';
$semArquivo = ['error' => UPLOAD_ERR_NO_FILE];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cover = null;
    $pdf = null;

    try {
        foreach ($campos as $c) {
            $v = $_POST[$c] ?? '';
            $d[$c] = is_string($v) ? trim($v) : '';
        }

        if ($d['titulo'] === '' || $d['autor'] === '') {
            throw new RuntimeException('Título e autor são obrigatórios.');
        }

        $quantidade = filter_var($d['quantidade'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]);
        if ($quantidade === false) {
            throw new RuntimeException('Informe uma quantidade entre 1 e 9999.');
        }

        $ano = null;
        if ($d['ano_publicacao'] !== '') {
            $ano = filter_var($d['ano_publicacao'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => (int)date('Y') + 1]]);
            if ($ano === false) {
                throw new RuntimeException('Ano de publicação inválido.');
            }
        }

        if ($d['isbn'] !== '' && !preg_match('/^[0-9Xx\- ]{10,20}$/', $d['isbn'])) {
            throw new RuntimeException('ISBN inválido. Use apenas números e hífens (10 ou 13 dígitos).');
        }

        if ($d['codigo_barras'] !== '') {
            $dup = db()->prepare('SELECT COUNT(*) FROM livros WHERE codigo_barras = ?');
            $dup->execute([$d['codigo_barras']]);
            if ($dup->fetchColumn()) {
                throw new RuntimeException('Já existe um livro cadastrado com este código de barras.');
            }
        }

        $cover = upload_cover($_FILES['capa'] ?? $semArquivo);
        $pdf = upload_pdf($_FILES['arquivo_pdf'] ?? $semArquivo);

        // Campos vazios viram NULL (importante para o código de barras, que é UNIQUE).
        $nz = static fn(string $v): ?string => $v === '' ? null : $v;

        $s = db()->prepare('INSERT INTO livros(codigo_barras,isbn,titulo,autor,categoria,editora,ano_publicacao,quantidade,quantidade_disponivel,descricao,biografia_autor,capa,arquivo_pdf) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $s->execute([
            $nz($d['codigo_barras']), $nz($d['isbn']), $d['titulo'], $d['autor'],
            $nz($d['categoria']), $nz($d['editora']), $ano,
            $quantidade, $quantidade,
            $nz($d['descricao']), $nz($d['biografia_autor']),
            $cover, $pdf,
        ]);

        flash('success', $pdf
            ? 'Livro cadastrado! Ele já aparece em “Ler livros digitais”.'
            : 'Livro cadastrado com sucesso.');
        redirect('admin/livros.php');
    } catch (Throwable $e) {
        // Se algo falhou depois do upload, não deixa arquivos órfãos.
        $base = dirname(__DIR__) . '/assets/uploads/';
        if ($cover) @unlink($base . 'capas/' . $cover);
        if ($pdf) @unlink($base . 'pdfs/' . $pdf);
        $error = $e instanceof PDOException
            ? 'Não foi possível salvar o livro. Tente novamente.'
            : $e->getMessage();
    }
}

$categorias = db()->query("SELECT DISTINCT categoria FROM livros WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Cadastrar livro';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/dashboard-nav.php';
?>
<form class="panel" method="post" enctype="multipart/form-data" id="book-form">
    <?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>

    <section class="form-section" style="margin-top:0">
        <h3>Identificação</h3>
        <div class="form-grid">
            <div class="form-group"><label for="titulo">Título <span class="req">*</span></label><input required id="titulo" name="titulo" maxlength="255" value="<?= e($d['titulo']) ?>"></div>
            <div class="form-group"><label for="autor">Autor <span class="req">*</span></label><input required id="autor" name="autor" maxlength="180" value="<?= e($d['autor']) ?>"></div>
            <div class="form-group"><label for="isbn">ISBN</label><input id="isbn" name="isbn" maxlength="20" placeholder="978-85-..." value="<?= e($d['isbn']) ?>"></div>
            <div class="form-group"><label for="codigo_barras">Código de barras</label><input id="codigo_barras" name="codigo_barras" maxlength="80" value="<?= e($d['codigo_barras']) ?>"></div>
        </div>
    </section>

    <section class="form-section">
        <h3>Catálogo</h3>
        <div class="form-grid">
            <div class="form-group">
                <label for="categoria">Categoria</label>
                <input id="categoria" name="categoria" maxlength="100" list="lista-categorias" autocomplete="off" value="<?= e($d['categoria']) ?>">
                <datalist id="lista-categorias"><?php foreach ($categorias as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
            </div>
            <div class="form-group"><label for="editora">Editora</label><input id="editora" name="editora" maxlength="150" value="<?= e($d['editora']) ?>"></div>
            <div class="form-group"><label for="ano_publicacao">Ano de publicação</label><input id="ano_publicacao" type="number" min="1000" max="<?= (int)date('Y') + 1 ?>" name="ano_publicacao" value="<?= e($d['ano_publicacao']) ?>"></div>
            <div class="form-group"><label for="quantidade">Quantidade de exemplares <span class="req">*</span></label><input required id="quantidade" type="number" min="1" max="9999" name="quantidade" value="<?= e($d['quantidade']) ?>"></div>
        </div>
        <div class="form-group"><label for="descricao">Descrição</label><textarea id="descricao" name="descricao"><?= e($d['descricao']) ?></textarea></div>
        <div class="form-group"><label for="biografia_autor">Biografia do autor</label><textarea id="biografia_autor" name="biografia_autor"><?= e($d['biografia_autor']) ?></textarea></div>
    </section>

    <section class="form-section">
        <h3>Arquivos</h3>
        <div class="upload-row">
            <div class="upload-box">
                <label for="capa">Capa</label>
                <input type="file" id="capa" name="capa" accept="image/jpeg,image/png,image/webp">
                <small class="form-hint">JPG, PNG ou WEBP, até 3 MB.</small>
                <img class="cover-preview" id="cover-preview" alt="Pré-visualização da capa" hidden>
                <div class="file-info" id="cover-info" hidden></div>
            </div>
            <div class="upload-box">
                <label for="arquivo_pdf">PDF do livro <small>(opcional)</small></label>
                <input type="file" id="arquivo_pdf" name="arquivo_pdf" accept="application/pdf,.pdf">
                <small class="form-hint">Até 20 MB.</small>
                <div class="file-info" id="pdf-info" hidden></div>
            </div>
        </div>
        <p class="subtext">Sem PDF, o livro fica em “Livros da escola”. Com PDF, ele também aparece em “Ler livros digitais”.</p>
    </section>

    <div class="form-actions">
        <button class="btn" id="book-submit">Cadastrar livro</button>
        <a class="btn btn-outline" href="<?= base_url('admin/livros.php') ?>">Cancelar</a>
    </div>
</form>

<script>
(function () {
    const MB = 1024 * 1024;
    const capa = document.getElementById('capa');
    const pdf = document.getElementById('arquivo_pdf');
    const preview = document.getElementById('cover-preview');
    const coverInfo = document.getElementById('cover-info');
    const pdfInfo = document.getElementById('pdf-info');
    let objectUrl = null;

    function tamanho(bytes) {
        return bytes >= MB ? (bytes / MB).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function validar(input, limite, info, nome) {
        const f = input.files[0];
        info.hidden = true;
        if (!f) return false;
        if (f.size > limite) {
            info.hidden = false;
            info.style.color = 'var(--danger)';
            info.textContent = 'Este arquivo tem ' + tamanho(f.size) + ' e passa do limite de ' + (limite / MB) + ' MB.';
            input.value = '';
            return false;
        }
        info.hidden = false;
        info.style.color = '';
        info.textContent = f.name + ' · ' + tamanho(f.size);
        return true;
    }

    capa.addEventListener('change', function () {
        if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
        preview.hidden = true;
        if (validar(capa, 3 * MB, coverInfo, 'capa')) {
            objectUrl = URL.createObjectURL(capa.files[0]);
            preview.src = objectUrl;
            preview.hidden = false;
        }
    });

    pdf.addEventListener('change', function () { validar(pdf, 20 * MB, pdfInfo, 'PDF'); });

    document.getElementById('book-form').addEventListener('submit', function () {
        const b = document.getElementById('book-submit');
        b.disabled = true;
        b.textContent = 'Salvando...';
    });
})();
</script>
<?php require __DIR__ . '/../includes/dashboard-footer.php'; ?>
