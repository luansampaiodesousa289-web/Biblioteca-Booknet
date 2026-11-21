<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$usuarioId = (int)user()['id'];
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM quizzes WHERE id = ?');
$stmt->execute([$id]);
$quiz = $stmt->fetch();
if (!$quiz) {
    http_response_code(404);
    exit('Quiz não encontrado.');
}

$stmt = $pdo->prepare('SELECT * FROM quiz_perguntas WHERE quiz_id = ? ORDER BY id');
$stmt->execute([$id]);
$questions = $stmt->fetchAll();
if (!$questions) {
    flash('error', 'Este quiz ainda não tem perguntas.');
    redirect('aluno/quizzes.php');
}

/*
 * Cada aluno recebe uma ordem própria para as alternativas, salva no banco
 * para não mudar ao recarregar a página. Ex.: "cadb" significa
 * A = alternativa original C, B = original A, C = original D, D = original B.
 * Depois que o aluno envia o quiz, a ordem é apagada: na próxima tentativa
 * as alternativas são embaralhadas de novo.
 */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quiz_ordens_alternativas (
            usuario_id INT UNSIGNED NOT NULL,
            quiz_id INT UNSIGNED NOT NULL,
            pergunta_id INT UNSIGNED NOT NULL,
            ordem CHAR(4) NOT NULL,
            PRIMARY KEY (usuario_id, pergunta_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
            FOREIGN KEY (pergunta_id) REFERENCES quiz_perguntas(id) ON DELETE CASCADE
        )
    ");
} catch (Throwable $e) {
    http_response_code(500);
    exit('Não foi possível preparar o quiz agora. Tente novamente em instantes.');
}

$stmt = $pdo->prepare('SELECT pergunta_id, ordem FROM quiz_ordens_alternativas WHERE usuario_id = ? AND quiz_id = ?');
$stmt->execute([$usuarioId, $id]);
$savedOrders = [];
foreach ($stmt->fetchAll() as $row) {
    $savedOrders[(int)$row['pergunta_id']] = str_split((string)$row['ordem']);
}

$saveOrder = $pdo->prepare('INSERT INTO quiz_ordens_alternativas(usuario_id, quiz_id, pergunta_id, ordem) VALUES(?,?,?,?)');
foreach ($questions as $q) {
    $qid = (int)$q['id'];
    $ordem = $savedOrders[$qid] ?? [];
    if (count($ordem) !== 4 || count(array_unique($ordem)) !== 4) {
        $letters = ['a', 'b', 'c', 'd'];
        shuffle($letters);
        $savedOrders[$qid] = $letters;
        try {
            $saveOrder->execute([$usuarioId, $id, $qid, implode('', $letters)]);
        } catch (PDOException $e) {
            // Já existia (acesso simultâneo): usa a ordem que está no banco.
            $reload = $pdo->prepare('SELECT ordem FROM quiz_ordens_alternativas WHERE usuario_id = ? AND quiz_id = ? AND pergunta_id = ?');
            $reload->execute([$usuarioId, $id, $qid]);
            $existing = (string)$reload->fetchColumn();
            if (strlen($existing) === 4) $savedOrders[$qid] = str_split($existing);
        }
    }
}

$result = null;
$selected = [];
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // O value de cada alternativa é a letra ORIGINAL, então a correção
    // continua certa mesmo com as opções embaralhadas.
    $posted = is_array($_POST['q'] ?? null) ? $_POST['q'] : [];
    foreach ($questions as $q) {
        $a = $posted[$q['id']] ?? '';
        if (is_string($a) && in_array($a, ['a', 'b', 'c', 'd'], true)) {
            $selected[(int)$q['id']] = $a;
        }
    }

    $faltam = count($questions) - count($selected);
    if ($faltam > 0) {
        $formError = $faltam === 1
            ? 'Falta responder 1 pergunta. Ela está destacada abaixo.'
            : "Faltam $faltam perguntas. Elas estão destacadas abaixo.";
    } else {
        $hits = 0;
        $save = $pdo->prepare(
            'INSERT INTO quiz_respostas(usuario_id, pergunta_id, resposta) VALUES(?,?,?)
             ON DUPLICATE KEY UPDATE resposta = VALUES(resposta), created_at = CURRENT_TIMESTAMP'
        );
        try {
            $pdo->beginTransaction();
            foreach ($questions as $q) {
                $qid = (int)$q['id'];
                $save->execute([$usuarioId, $qid, $selected[$qid]]);
                if ($selected[$qid] === $q['correta']) $hits++;
            }
            $pdo->commit();

            $total = count($questions);
            $result = ['hits' => $hits, 'total' => $total, 'nota' => round($hits / $total * 10, 2)];

            // Próxima tentativa = novo embaralhamento.
            $pdo->prepare('DELETE FROM quiz_ordens_alternativas WHERE usuario_id = ? AND quiz_id = ?')->execute([$usuarioId, $id]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $formError = 'Não foi possível salvar suas respostas. Tente enviar de novo.';
        }
    }
}

$mensagem = '';
if ($result) {
    $n = $result['nota'];
    $mensagem = $n >= 9 ? 'Excelente! Você domina o assunto.'
        : ($n >= 7 ? 'Muito bom! Falta pouco para acertar tudo.'
        : ($n >= 5 ? 'Bom começo. Revise as questões erradas e tente de novo.'
        : 'Vale revisar o conteúdo e tentar mais uma vez.'));
}

$pageTitle = $quiz['titulo'];
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/dashboard-nav.php';
?>

<?php if ($result): ?>
<section class="panel quiz-review">
    <h2><?= e($quiz['titulo']) ?></h2>
    <div class="quiz-result">
        <div class="score-ring" style="--p:<?= (int)round($result['hits'] / $result['total'] * 100) ?>"><b><?= number_format($result['nota'], 1, ',', '.') ?></b></div>
        <div>
            <h3>Você acertou <?= $result['hits'] ?> de <?= $result['total'] ?> questões</h3>
            <p><?= e($mensagem) ?></p>
            <div class="form-actions">
                <a class="btn btn-light btn-small" href="<?= base_url('aluno/quiz.php?id=' . $id) ?>">Refazer quiz</a>
                <a class="btn btn-light btn-small" href="<?= base_url('aluno/quizzes.php') ?>">Outros quizzes</a>
            </div>
        </div>
    </div>

    <h2>Revisão das respostas</h2>
    <?php foreach ($questions as $i => $q): ?>
        <?php $qid = (int)$q['id']; $mine = $selected[$qid] ?? ''; $acertou = $mine === $q['correta']; ?>
        <article class="quiz-q">
            <h3><span><?= $i + 1 ?>.</span><?= e($q['pergunta']) ?></h3>
            <?php foreach ($savedOrders[$qid] as $pos => $letter): ?>
                <?php
                $cls = '';
                $tag = '';
                if ($letter === $q['correta']) { $cls = 'is-correct'; $tag = $acertou ? 'Você acertou' : 'Resposta correta'; }
                elseif ($letter === $mine) { $cls = 'is-wrong'; $tag = 'Sua resposta'; }
                ?>
                <div class="quiz-opt <?= $cls ?>">
                    <span class="letter"><?= chr(65 + $pos) ?></span>
                    <span class="opt-text"><?= e($q['alternativa_' . $letter]) ?></span>
                    <?php if ($tag): ?><em class="opt-tag"><?= $tag ?></em><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </article>
    <?php endforeach; ?>
</section>

<?php else: ?>
<form class="panel" method="post" id="quiz-form" novalidate>
    <?= csrf_field() ?>
    <h2><?= e($quiz['titulo']) ?></h2>
    <?php if ($quiz['descricao']): ?><p class="subtext"><?= e($quiz['descricao']) ?></p><?php endif; ?>
    <?php if ($formError): ?><div class="notice error" role="alert"><?= e($formError) ?></div><?php endif; ?>

    <div class="quiz-progress">
        <div class="quiz-meta"><span id="quiz-count">0 de <?= count($questions) ?> respondidas</span><span><?= e($quiz['disciplina']) ?></span></div>
        <div class="progress"><span id="quiz-bar"></span></div>
    </div>

    <?php foreach ($questions as $i => $q): ?>
        <?php $qid = (int)$q['id']; ?>
        <fieldset class="quiz-q <?= ($formError && !isset($selected[$qid])) ? 'is-missing' : '' ?>" style="margin-inline:0;min-width:0" data-qid="<?= $qid ?>">
            <legend class="sr-only" style="position:absolute;left:-9999px">Pergunta <?= $i + 1 ?></legend>
            <h3><span><?= $i + 1 ?>.</span><?= e($q['pergunta']) ?></h3>
            <?php foreach ($savedOrders[$qid] as $pos => $letter): ?>
                <label class="quiz-opt <?= ($selected[$qid] ?? '') === $letter ? 'is-selected' : '' ?>">
                    <input type="radio" name="q[<?= $qid ?>]" value="<?= $letter ?>" <?= ($selected[$qid] ?? '') === $letter ? 'checked' : '' ?>>
                    <span class="letter"><?= chr(65 + $pos) ?></span>
                    <span class="opt-text"><?= e($q['alternativa_' . $letter]) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>

    <div class="form-actions">
        <button class="btn" type="submit" id="quiz-submit">Enviar respostas</button>
        <a class="btn btn-outline" href="<?= base_url('aluno/quizzes.php') ?>">Voltar</a>
    </div>
</form>

<script>
(function () {
    const form = document.getElementById('quiz-form');
    const cards = Array.from(form.querySelectorAll('.quiz-q'));
    const bar = document.getElementById('quiz-bar');
    const count = document.getElementById('quiz-count');

    function answered(card) { return !!card.querySelector('input:checked'); }

    function atualiza() {
        const feitas = cards.filter(answered).length;
        bar.style.width = (feitas / cards.length * 100) + '%';
        count.textContent = feitas + ' de ' + cards.length + ' respondidas';
        cards.forEach(function (c) {
            c.querySelectorAll('.quiz-opt').forEach(function (o) {
                o.classList.toggle('is-selected', o.querySelector('input').checked);
            });
            if (answered(c)) c.classList.remove('is-missing');
        });
    }

    form.addEventListener('change', atualiza);

    form.addEventListener('submit', function (ev) {
        const faltando = cards.filter(function (c) { return !answered(c); });
        if (faltando.length) {
            ev.preventDefault();
            faltando.forEach(function (c) { c.classList.add('is-missing'); });
            faltando[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            count.textContent = 'Faltam ' + faltando.length + ' pergunta(s)';
            return;
        }
        const b = document.getElementById('quiz-submit');
        b.disabled = true;
        b.textContent = 'Corrigindo...';
    });

    atualiza();
    const primeiraFaltando = form.querySelector('.is-missing');
    if (primeiraFaltando) primeiraFaltando.scrollIntoView({ block: 'center' });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/dashboard-footer.php'; ?>
