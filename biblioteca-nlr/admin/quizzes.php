<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();

const QUIZ_MIN = 5;
const QUIZ_MAX = 10;
const QUIZ_LETRAS = ['a', 'b', 'c', 'd'];

function quiz_texto(mixed $v): string
{
    return is_string($v) ? trim($v) : '';
}

/** Desenha o bloco de uma pergunta (usado nas perguntas iniciais e no modelo do botão "Adicionar"). */
function quiz_render_pergunta(int $n, array $q): void
{ ?>
<fieldset class="quiz-question">
    <legend>Pergunta <?= $n ?></legend>
    <div class="form-group">
        <label>Enunciado</label>
        <input required maxlength="1000" name="pergunta[]" value="<?= e($q['p']) ?>">
    </div>
    <div class="form-grid">
        <?php foreach (QUIZ_LETRAS as $k): ?>
        <div class="form-group">
            <label>Alternativa <?= strtoupper($k) ?></label>
            <input required maxlength="500" name="<?= $k ?>[]" value="<?= e($q[$k]) ?>">
        </div>
        <?php endforeach; ?>
    </div>
    <div class="quiz-foot">
        <div class="form-group">
            <label>Resposta correta</label>
            <select name="correta[]">
                <?php foreach (QUIZ_LETRAS as $k): ?>
                <option value="<?= $k ?>" <?= $q['ok'] === $k ? 'selected' : '' ?>><?= strtoupper($k) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="button" class="btn btn-small btn-outline remove-question">Remover pergunta</button>
    </div>
</fieldset>
<?php }

$vazia = ['p' => '', 'a' => '', 'b' => '', 'c' => '', 'd' => '', 'ok' => 'a'];
$error = '';
$titulo = '';
$disciplina = '';
$descricao = '';
$perguntasForm = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $acao = quiz_texto($_POST['acao'] ?? 'criar');

    if ($acao === 'excluir') {
        $qid = (int)($_POST['id'] ?? 0);
        if ($qid > 0) {
            db()->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$qid]);
            flash('success', 'Quiz excluído.');
        }
        redirect('admin/quizzes.php');
    }

    $titulo = quiz_texto($_POST['titulo'] ?? '');
    $disciplina = quiz_texto($_POST['disciplina'] ?? '');
    $descricao = quiz_texto($_POST['descricao'] ?? '');

    // Reconstrói as perguntas enviadas para não perder nada se houver erro.
    $enviadas = is_array($_POST['pergunta'] ?? null) ? array_values($_POST['pergunta']) : [];
    $cols = [];
    foreach (array_merge(QUIZ_LETRAS, ['correta']) as $k) {
        $cols[$k] = is_array($_POST[$k] ?? null) ? array_values($_POST[$k]) : [];
    }
    foreach ($enviadas as $i => $texto) {
        $ok = quiz_texto($cols['correta'][$i] ?? '');
        $perguntasForm[] = [
            'p' => quiz_texto($texto),
            'a' => quiz_texto($cols['a'][$i] ?? ''),
            'b' => quiz_texto($cols['b'][$i] ?? ''),
            'c' => quiz_texto($cols['c'][$i] ?? ''),
            'd' => quiz_texto($cols['d'][$i] ?? ''),
            'ok' => in_array($ok, QUIZ_LETRAS, true) ? $ok : 'a',
        ];
    }

    try {
        if ($titulo === '' || $disciplina === '') {
            throw new RuntimeException('Título e disciplina são obrigatórios.');
        }
        if (mb_strlen($titulo) > 255 || mb_strlen($disciplina) > 120) {
            throw new RuntimeException('Título (máx. 255) ou disciplina (máx. 120) muito longos.');
        }

        $validas = [];
        foreach ($perguntasForm as $i => $item) {
            if ($item['p'] === '') continue;
            foreach (QUIZ_LETRAS as $k) {
                if ($item[$k] === '') {
                    throw new RuntimeException('Preencha todas as alternativas da pergunta ' . ($i + 1) . '.');
                }
                if (mb_strlen($item[$k]) > 500) {
                    throw new RuntimeException('A alternativa ' . strtoupper($k) . ' da pergunta ' . ($i + 1) . ' passa de 500 caracteres.');
                }
            }
            $validas[] = $item;
        }

        $count = count($validas);
        if ($count < QUIZ_MIN || $count > QUIZ_MAX) {
            throw new RuntimeException('O quiz precisa ter no mínimo ' . QUIZ_MIN . ' e no máximo ' . QUIZ_MAX . ' perguntas.');
        }

        db()->beginTransaction();
        $s = db()->prepare('INSERT INTO quizzes(titulo,disciplina,descricao,criado_por) VALUES(?,?,?,?)');
        $s->execute([$titulo, $disciplina, $descricao, user()['id']]);
        $quizId = (int)db()->lastInsertId();
        $ins = db()->prepare('INSERT INTO quiz_perguntas(quiz_id,pergunta,alternativa_a,alternativa_b,alternativa_c,alternativa_d,correta) VALUES(?,?,?,?,?,?,?)');
        foreach ($validas as $v) {
            $ins->execute([$quizId, $v['p'], $v['a'], $v['b'], $v['c'], $v['d'], $v['ok']]);
        }
        db()->commit();

        flash('success', 'Quiz publicado com ' . $count . ' perguntas.');
        redirect('admin/quizzes.php');
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = $e instanceof PDOException ? 'Não foi possível publicar o quiz. Tente novamente.' : $e->getMessage();
    }
}

// Sempre entre 5 e 10 blocos na tela.
$perguntasForm = array_slice($perguntasForm, 0, QUIZ_MAX);
while (count($perguntasForm) < QUIZ_MIN) $perguntasForm[] = $vazia;

$items = db()->query('SELECT q.*, COUNT(p.id) AS perguntas FROM quizzes q LEFT JOIN quiz_perguntas p ON p.quiz_id = q.id GROUP BY q.id ORDER BY q.created_at DESC')->fetchAll();

$pageTitle = 'Quizzes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/dashboard-nav.php';
?>
<form class="panel" method="post" id="quiz-form" style="margin-top:0">
    <?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="criar">
    <h2>Criar quiz</h2>
    <p class="subtext">O quiz deve ter <b>de <?= QUIZ_MIN ?> a <?= QUIZ_MAX ?> perguntas</b>. As alternativas são embaralhadas para cada aluno, e a ordem muda a cada nova tentativa.</p>

    <div class="form-grid">
        <div class="form-group"><label for="titulo">Título <span class="req">*</span></label><input required id="titulo" name="titulo" maxlength="255" placeholder="Revisão de frações" value="<?= e($titulo) ?>"></div>
        <div class="form-group"><label for="disciplina">Disciplina <span class="req">*</span></label><input required id="disciplina" name="disciplina" maxlength="120" placeholder="Matemática" value="<?= e($disciplina) ?>"></div>
    </div>
    <div class="form-group"><label for="descricao">Orientação para o aluno</label><textarea id="descricao" name="descricao"><?= e($descricao) ?></textarea></div>

    <div id="questions-container">
        <?php foreach ($perguntasForm as $i => $q) quiz_render_pergunta($i + 1, $q); ?>
    </div>
    <template id="question-template"><?php quiz_render_pergunta(0, $vazia); ?></template>

    <div class="form-actions">
        <button class="btn btn-outline" type="button" id="add-question">+ Adicionar pergunta</button>
        <span class="quiz-counter" id="question-counter"></span>
    </div>
    <div class="form-actions" style="margin-top:18px">
        <button class="btn" type="submit">Publicar quiz</button>
    </div>
</form>

<section class="panel">
    <h2>Quizzes publicados</h2>
    <?php if ($items): ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Título</th><th>Disciplina</th><th>Perguntas</th><th>Publicado em</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($items as $q): ?>
                <tr>
                    <td><b><?= e($q['titulo']) ?></b></td>
                    <td><?= e($q['disciplina']) ?></td>
                    <td><span class="badge"><?= (int)$q['perguntas'] ?> pergunta(s)</span></td>
                    <td><?= e(date('d/m/Y', strtotime((string)$q['created_at']))) ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                            <button class="btn btn-small btn-danger" data-confirm="Excluir este quiz e as respostas dos alunos? Esta ação não pode ser desfeita.">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty">Nenhum quiz publicado ainda.</div>
    <?php endif; ?>
</section>

<script>
(function () {
    const MIN = <?= QUIZ_MIN ?>, MAX = <?= QUIZ_MAX ?>;
    const box = document.getElementById('questions-container');
    const add = document.getElementById('add-question');
    const tpl = document.getElementById('question-template');
    const counter = document.getElementById('question-counter');

    function itens() { return box.querySelectorAll('.quiz-question'); }

    function atualiza() {
        const lista = itens();
        lista.forEach(function (f, i) {
            f.querySelector('legend').textContent = 'Pergunta ' + (i + 1);
            f.querySelector('.remove-question').hidden = lista.length <= MIN;
        });
        add.disabled = lista.length >= MAX;
        add.textContent = lista.length >= MAX ? 'Limite de ' + MAX + ' perguntas atingido' : '+ Adicionar pergunta';
        counter.textContent = lista.length + ' de ' + MAX + ' perguntas';
    }

    add.addEventListener('click', function () {
        if (itens().length >= MAX) return;
        box.appendChild(tpl.content.cloneNode(true));
        atualiza();
        const novo = box.lastElementChild;
        novo.scrollIntoView({ behavior: 'smooth', block: 'center' });
        novo.querySelector('input').focus({ preventScroll: true });
    });

    box.addEventListener('click', function (ev) {
        const b = ev.target.closest('.remove-question');
        if (!b || itens().length <= MIN) return;
        b.closest('.quiz-question').remove();
        atualiza();
    });

    atualiza();
})();
</script>
<?php require __DIR__ . '/../includes/dashboard-footer.php'; ?>
