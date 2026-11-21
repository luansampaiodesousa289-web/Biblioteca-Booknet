<?php
require_once __DIR__.'/../config/auth.php'; require_login();
if(user()['tipo']!=='aluno'){http_response_code(403);exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$n=trim($_POST['nome']??'');$d=trim($_POST['descricao']??'');if($n){db()->prepare("INSERT INTO clubes_livro(nome,descricao,criador_id) VALUES(?,?,?)")->execute([$n,$d,user()['id']]);}}
$clubes=db()->query("SELECT c.*,u.nome criador FROM clubes_livro c JOIN usuarios u ON u.id=c.criador_id ORDER BY c.id DESC")->fetchAll();
$pageTitle='Clubes do livro'; require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/dashboard-nav.php';
?>
<section class="panel club-card"><h2>📚 Clube do livro</h2><p>Crie grupos para ler e conversar sobre livros.</p><form class="club-create" method="post"><?=csrf_field()?><input name="nome" placeholder="Nome do clube" required><textarea name="descricao" placeholder="Descrição"></textarea><button class="btn">Criar</button></form><hr><div class="club-list"><?php foreach($clubes as $c): ?><div class="club-item"><h3>📖 <?=e($c['nome'])?></h3><p><?=e($c['descricao'])?></p><small>Criado por <?=e($c['criador'])?></small></div><?php endforeach;?></div></section>
<?php require __DIR__.'/../includes/dashboard-footer.php'; ?>