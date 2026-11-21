<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$usuarioId = (int)user()['id'];

// Cada quiz com o desempenho mais recente do aluno (respostas já salvas).
$stmt = db()->prepare(
    'SELECT q.*,
            COUNT(p.id) AS perguntas,
            SUM(CASE WHEN r.resposta IS NOT NULL THEN 1 ELSE 0 END) AS respondidas,
            SUM(CASE WHEN r.resposta = p.correta THEN 1 ELSE 0 END) AS acertos
       FROM quizzes q
       JOIN quiz_perguntas p ON p.quiz_id = q.id
       LEFT JOIN quiz_respostas r ON r.pergunta_id = p.id AND r.usuario_id = ?
      GROUP BY q.id
      ORDER BY q.created_at DESC'
);
$stmt->execute([$usuarioId]);
$todos = $stmt->fetchAll();

$disciplinas = array_values(array_unique(array_map(static fn($r) => (string)$r['disciplina'], $todos)));
sort($disciplinas);

$filtro = is_string($_GET['disciplina'] ?? null) ? trim($_GET['disciplina']) : '';
$items = $filtro === ''
    ? $todos
    : array_values(array_filter($todos, static fn($r) => $r['disciplina'] === $filtro));

$feitos = count(array_filter($todos, static fn($r) => (int)$r['respondidas'] > 0));

$pageTitle = 'Quizzes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/dashboard-nav.php';
?>
<p class="subtext">Teste seus conhecimentos e pratique antes das avaliações.
    <?php if ($todos): ?><b><?= $feitos ?> de <?= count($todos) ?></b> quizzes já respondidos.<?php endif; ?>
</p>

<?php if (count($disciplinas) > 1): ?>
<nav class="quiz-chips" aria-label="Filtrar por disciplina">
    <a class="chip <?= $filtro === '' ? 'active' : '' ?>" href="<?= base_url('aluno/quizzes.php') ?>">Todos</a>
    <?php foreach ($disciplinas as $d): ?>
        <a class="chip <?= $filtro === $d ? 'active' : '' ?>" href="<?= base_url('aluno/quizzes.php?disciplina=' . urlencode($d)) ?>"><?= e($d) ?></a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>

<div class="book-grid">
    <?php foreach ($items as $q): ?>
        <?php
        $total = (int)$q['perguntas'];
        $acertos = (int)$q['acertos'];
        $feito = (int)$q['respondidas'] > 0;
        $nota = $total > 0 ? round($acertos / $total * 10, 1) : 0;
        ?>
        <article class="book-card">
            <div class="cover cover-placeholder" style="height:150px"><?= e($q['disciplina']) ?></div>
            <div class="book-info">
                <span class="badge"><?= $total ?> perguntas</span>
                <?php if ($feito): ?><span class="badge done">Respondido</span><?php endif; ?>
                <h3 style="margin-top:10px"><?= e($q['titulo']) ?></h3>
                <p><?= e($q['descricao']) ?></p>
                <?php if ($feito): ?>
                    <p class="quiz-card-score">Último resultado: <b><?= $acertos ?>/<?= $total ?></b> · nota <?= number_format($nota, 1, ',', '.') ?></p>
                    <div class="progress" style="margin-bottom:14px"><span style="width:<?= (int)round($acertos / max(1, $total) * 100) ?>%"></span></div>
                <?php endif; ?>
                <a class="btn btn-small" href="<?= base_url('aluno/quiz.php?id=' . (int)$q['id']) ?>"><?= $feito ? 'Refazer quiz' : 'Começar quiz' ?></a>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$items): ?>
        <div class="empty"><?= $filtro !== '' ? 'Nenhum quiz nessa disciplina.' : 'A equipe ainda não publicou quizzes.' ?></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/dashboard-footer.php'; ?>
