<?php
require_once __DIR__.'/../config/auth.php'; ensure_assessment_tables(); require_admin();

// Garante uma avaliação de Química com exatamente 5 questões para a área de Química.
// Só cria automaticamente se ainda não existir uma prova de Química.
$chemExists = db()->prepare("SELECT id FROM provas WHERE LOWER(TRIM(disciplina)) IN ('química','quimica') LIMIT 1");
$chemExists->execute();
if (!$chemExists->fetchColumn()) {
    db()->beginTransaction();
    try {
        $s = db()->prepare('INSERT INTO provas(titulo,disciplina,descricao,duracao_minutos,publicada,criado_por) VALUES(?,?,?,?,?,?)');
        $s->execute([
            'Avaliação de Química',
            'Química',
            'Avaliação de Química com 5 questões de múltipla escolha.',
            20,
            1,
            user()['id']
        ]);
        $chemId = (int)db()->lastInsertId();
        $questions = [
            ['Qual é o símbolo químico do oxigênio?', 'O', 'Ox', 'Og', 'O₂', 'a'],
            ['Qual é o pH de uma solução neutra, a 25 °C?', '0', '7', '10', '14', 'b'],
            ['Qual partícula possui carga elétrica negativa?', 'Próton', 'Nêutron', 'Elétron', 'Núcleo', 'c'],
            ['Qual é a fórmula química da água?', 'CO₂', 'H₂O', 'NaCl', 'O₂', 'b'],
            ['Qual elemento químico tem o símbolo Na?', 'Nitrogênio', 'Neônio', 'Sódio', 'Níquel', 'c']
        ];
        $q = db()->prepare('INSERT INTO prova_perguntas(prova_id,pergunta,alternativa_a,alternativa_b,alternativa_c,alternativa_d,correta) VALUES(?,?,?,?,?,?,?)');
        foreach ($questions as $row) $q->execute([$chemId, ...$row]);
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        // Não impede o restante da área administrativa de abrir.
    }
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  $action=$_POST['action']??'create';
  if($action==='delete'){
   $s=db()->prepare('DELETE FROM provas WHERE id=?'); $s->execute([(int)$_POST['id']]); flash('success','Prova excluída.'); redirect('admin/provas.php');
  }
  if($action==='toggle'){
   $s=db()->prepare('UPDATE provas SET publicada=1-publicada WHERE id=?'); $s->execute([(int)$_POST['id']]); flash('success','Status da prova atualizado.'); redirect('admin/provas.php');
  }
  $title=trim($_POST['titulo']??''); $subject=trim($_POST['disciplina']??''); $desc=trim($_POST['descricao']??''); $duration=max(1,min(300,(int)($_POST['duracao']??30)));
  if(!$title||!$subject) throw new RuntimeException('Título e disciplina são obrigatórios.');
  db()->beginTransaction();
  $s=db()->prepare('INSERT INTO provas(titulo,disciplina,descricao,duracao_minutos,publicada,criado_por) VALUES(?,?,?,?,?,?)');
  $s->execute([$title,$subject,$desc,$duration,isset($_POST['publicada'])?1:0,user()['id']]); $id=(int)db()->lastInsertId();
  $q=db()->prepare('INSERT INTO prova_perguntas(prova_id,pergunta,alternativa_a,alternativa_b,alternativa_c,alternativa_d,correta) VALUES(?,?,?,?,?,?,?)');
  foreach(($_POST['pergunta']??[]) as $i=>$text){
   $text=trim($text); if($text==='') continue;
   $vals=[trim($_POST['a'][$i]??''),trim($_POST['b'][$i]??''),trim($_POST['c'][$i]??''),trim($_POST['d'][$i]??'')];
   if(in_array('', $vals, true)) throw new RuntimeException('Todas as alternativas da questão preenchida são obrigatórias.');
   $correct=$_POST['correta'][$i]??'a'; if(!in_array($correct,['a','b','c','d'],true)) $correct='a';
   $q->execute([$id,$text,$vals[0],$vals[1],$vals[2],$vals[3],$correct]);
  }
  $count=(int)db()->query('SELECT COUNT(*) FROM prova_perguntas WHERE prova_id='.$id)->fetchColumn(); if($count<1) throw new RuntimeException('Adicione pelo menos uma questão.');
  db()->commit(); flash('success','Prova criada com '.$count.' questão(ões).'); redirect('admin/provas.php');
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}
}
$items=db()->query('SELECT p.*,COUNT(q.id) perguntas FROM provas p LEFT JOIN prova_perguntas q ON q.prova_id=p.id GROUP BY p.id ORDER BY p.created_at DESC')->fetchAll();
$pageTitle='Provas'; require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/dashboard-nav.php';
?>
<?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?>
<form class="panel" method="post" id="prova-form"><?=csrf_field()?><input type="hidden" name="action" value="create"><h2>Criar nova prova</h2>
<div class="form-grid"><div class="form-group"><label>Título</label><input required name="titulo" placeholder="Prova de Matemática"></div><div class="form-group"><label>Disciplina</label><input required name="disciplina" placeholder="Matemática"></div><div class="form-group"><label>Duração (minutos)</label><input required type="number" min="1" max="300" name="duracao" value="30"></div><div class="form-group" style="display:flex;align-items:center;gap:8px;padding-top:26px"><input type="checkbox" name="publicada" checked id="pub"><label for="pub">Publicar imediatamente</label></div></div>
<div class="form-group"><label>Descrição/instruções</label><textarea name="descricao" placeholder="Leia cada questão com atenção."></textarea></div>
<div id="questoes"></div><button type="button" class="btn btn-outline" onclick="addQuestion()">＋ Adicionar questão</button> <button class="btn">Publicar prova</button></form>
<section class="panel"><h2>Provas cadastradas</h2><div class="table-wrap"><table><thead><tr><th>Prova</th><th>Disciplina</th><th>Questões</th><th>Duração</th><th>Status</th><th>Ações</th></tr></thead><tbody><?php foreach($items as $p):?><tr><td><b><?=e($p['titulo'])?></b></td><td><?=e($p['disciplina'])?></td><td><?=$p['perguntas']?></td><td><?=$p['duracao_minutos']?> min</td><td><span class="badge <?=$p['publicada']?'':'off'?>"><?=$p['publicada']?'Publicada':'Rascunho'?></span></td><td style="display:flex;gap:6px;flex-wrap:wrap"><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn btn-small btn-outline"><?=$p['publicada']?'Despublicar':'Publicar'?></button></form><a class="btn btn-small btn-outline" href="<?=base_url('admin/editar-prova.php?id='.$p['id'])?>">Editar</a><form method="post" onsubmit="return confirm('Excluir esta prova e suas tentativas?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn btn-small btn-danger">Excluir</button></form></td></tr><?php endforeach;?><?php if(!$items):?><tr><td colspan="6" class="empty">Nenhuma prova cadastrada.</td></tr><?php endif;?></tbody></table></div></section>
<script>
let n=0;function addQuestion(){const i=n++;const box=document.createElement('fieldset');box.style='border:1px solid #d9e2ec;border-radius:10px;margin:14px 0;padding:14px';box.innerHTML=`<legend>Questão ${i+1}</legend><div class="form-group"><label>Enunciado</label><textarea required name="pergunta[${i}]"></textarea></div><div class="form-grid">${['a','b','c','d'].map(l=>`<div class="form-group"><label>Alternativa ${l.toUpperCase()}</label><input required name="${l}[${i}]"></div>`).join('')}</div><div class="form-group"><label>Resposta correta</label><select name="correta[${i}]"><option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option></select></div>`;document.getElementById('questoes').appendChild(box)}
addQuestion();addQuestion();addQuestion();
</script>
<?php require __DIR__.'/../includes/dashboard-footer.php';?>
