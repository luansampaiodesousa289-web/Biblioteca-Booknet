<?php require_once __DIR__.'/../config/auth.php'; require_login();
$pageTitle='Minha Estante'; require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/dashboard-nav.php';
$id=user()['id'];
$s=db()->prepare("SELECT l.*, e.status FROM emprestimos e JOIN livros l ON l.id=e.livro_id WHERE e.usuario_id=? ORDER BY e.created_at DESC");
$s->execute([$id]); $books=$s->fetchAll();
?>
<section class="panel"><h2>📚 Minha estante virtual</h2><p>Seus livros emprestados e favoritos aparecem aqui.</p><div class="book-grid">
<?php foreach($books as $b): ?><article class="card"><h3><?=e($b['titulo'])?></h3><p><?=e($b['autor'])?></p><span class="badge"><?=e($b['status'])?></span></article><?php endforeach; ?>
<?php if(!$books): ?><p class="empty">Você ainda não tem livros na estante.</p><?php endif; ?>
</div></section>
<?php require __DIR__.'/../includes/dashboard-footer.php'; ?>
