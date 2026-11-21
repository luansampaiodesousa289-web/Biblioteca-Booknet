<?php
require_once __DIR__.'/../config/auth.php'; require_login();
$q=trim($_GET['q']??''); $cat=$_GET['categoria']??'';
$sql="SELECT * FROM livros WHERE (arquivo_pdf IS NULL OR arquivo_pdf='')"; $p=[];
if($q){$sql.=" AND (titulo LIKE ? OR autor LIKE ? OR isbn LIKE ? OR codigo_barras LIKE ?)"; $p=array_fill(0,4,'%'.$q.'%');}
if($cat){$sql.=' AND categoria=?'; $p[]=$cat;}
$sql.=' ORDER BY titulo'; $s=db()->prepare($sql); $s->execute($p); $books=$s->fetchAll();
$categories=db()->query("SELECT DISTINCT categoria FROM livros WHERE (arquivo_pdf IS NULL OR arquivo_pdf='') AND categoria<>'' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$pageTitle='Livros da escola'; require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/dashboard-nav.php';
?>
<div><p class="subtext">Aqui ficam os livros físicos da escola, sem PDF digital cadastrado.</p><form class="filterbar" method="get"><div class="searchbar"><input type="search" name="q" value="<?=e($q)?>" placeholder="Pesquisar livro..." autocomplete="off"></div><select name="categoria"><option value="">Todas as categorias</option><?php foreach($categories as $c):?><option <?=$cat===$c?'selected':''?>><?=e($c)?></option><?php endforeach;?></select><button class="btn btn-small">Filtrar</button></form><div class="book-grid"><?php foreach($books as $l):?><article class="book-card"><?php if($l['capa']):?><img class="cover" src="<?=base_url('assets/uploads/capas/'.$l['capa'])?>" alt="Capa de <?=e($l['titulo'])?>"><?php else:?><div class="cover cover-placeholder"><?=e($l['titulo'])?></div><?php endif;?><div class="book-info"><h3><?=e($l['titulo'])?></h3><p><?=e($l['autor'])?><br><?=e($l['categoria'])?> · <?=e($l['ano_publicacao'])?></p><span class="badge <?=$l['quantidade_disponivel']?'':'off'?>"><?=$l['quantidade_disponivel']?'Disponível':'Indisponível'?></span><a class="btn btn-small" style="float:right" href="<?=base_url('aluno/livro.php?id='.(int)$l['id'])?>">Detalhes</a></div></article><?php endforeach;?><?php if(!$books):?><div class="empty">Nenhum livro físico da escola encontrado.</div><?php endif;?></div></div>
<?php require __DIR__.'/../includes/dashboard-footer.php'; ?>
