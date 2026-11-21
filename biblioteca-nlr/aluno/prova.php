<?php
require_once __DIR__.'/../config/auth.php'; ensure_assessment_tables(); require_login();
$uid=user()['id']; $id=(int)($_GET['id']??0);
$s=db()->prepare('SELECT * FROM provas WHERE id=? AND publicada=1'); $s->execute([$id]); $prova=$s->fetch();
if(!$prova) exit('Prova não encontrada.');
$check=db()->prepare('SELECT * FROM prova_tentativas WHERE prova_id=? AND usuario_id=?'); $check->execute([$id,$uid]); $attempt=$check->fetch();
if($attempt && $attempt['status']!=='em_andamento') redirect('aluno/resultado-prova.php?id='.$attempt['id']);
$p=db()->prepare('SELECT * FROM prova_perguntas WHERE prova_id=? ORDER BY id'); $p->execute([$id]); $questions=$p->fetchAll();
if(!$questions) exit('Esta prova não possui questões.');
if($attempt){
    $attemptId=(int)$attempt['id']; $start=$attempt['iniciado_em'];
}else{
    $start=date('Y-m-d H:i:s');
    $ins=db()->prepare("INSERT INTO prova_tentativas(prova_id,usuario_id,iniciado_em,total) VALUES(?,?,?,?)");
    $ins->execute([$id,$uid,$start,count($questions)]); $attemptId=(int)db()->lastInsertId();
}
$ordens=prova_garantir_ordens($attemptId,$questions);
$remaining=max(0, ((int)$prova['duracao_minutos']*60) - (time()-strtotime($start)));
if($remaining===0 && $attempt && $attempt['status']==='em_andamento' && $_SERVER['REQUEST_METHOD']!=='POST'){
    $up=db()->prepare("UPDATE prova_tentativas SET finalizado_em=?,status='expirada' WHERE id=?"); $up->execute([date('Y-m-d H:i:s'),$attemptId]);
    redirect('aluno/resultado-prova.php?id='.$attemptId);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $started=strtotime($start); $expired=time()>=$started+((int)$prova['duracao_minutos']*60); $hits=0; db()->beginTransaction();
    db()->prepare('DELETE FROM prova_respostas WHERE tentativa_id=?')->execute([$attemptId]);
    $save=db()->prepare('INSERT INTO prova_respostas(tentativa_id,pergunta_id,resposta) VALUES(?,?,?)');
    foreach($questions as $q){
        $displayAnswer=$_POST['q'][$q['id']]??'';
        $ordem=str_split($ordens[(int)$q['id']]);
        if(in_array($displayAnswer,['a','b','c','d'],true)){
            $index=ord('a')-ord('a') + (ord($displayAnswer)-ord('a'));
            $originalAnswer=$ordem[$index] ?? '';
            if(in_array($originalAnswer,['a','b','c','d'],true)){
                $save->execute([$attemptId,$q['id'],$originalAnswer]);
                if($originalAnswer===$q['correta']) $hits++;
            }
        }
    }
    $total=count($questions); $nota=round(($hits/$total)*10,2); $status=$expired?'expirada':'finalizada';
    $up=db()->prepare('UPDATE prova_tentativas SET finalizado_em=?,acertos=?,total=?,nota=?,status=? WHERE id=? AND status="em_andamento"');
    $up->execute([date('Y-m-d H:i:s'),$hits,$total,$nota,$status,$attemptId]); db()->commit();
    redirect('aluno/resultado-prova.php?id='.$attemptId);
}
$pageTitle=$prova['titulo']; require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/dashboard-nav.php';
?><div class="panel"><div style="display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap"><div><h2><?=e($prova['titulo'])?></h2><p class="subtext"><?=e($prova['descricao'])?></p></div><div class="badge" id="timer" data-seconds="<?=$remaining?>">Tempo: --:--</div></div><div class="notice" id="warning">As alternativas desta prova são embaralhadas individualmente para cada aluno.</div></div>
<form class="panel" method="post" id="examForm"><?=csrf_field()?><?php foreach($questions as $i=>$q): $ordem=str_split($ordens[(int)$q['id']]);?><fieldset style="border:0;border-top:1px solid #d9e2ec;padding:18px 0"><legend><b><?=$i+1?>. <?=e($q['pergunta'])?></b></legend><?php foreach($ordem as $displayIndex=>$originalLetter): $displayLetter=chr(97+$displayIndex);?><label style="display:block;margin:11px 0"><input required type="radio" name="q[<?=$q['id']?>]" value="<?=$displayLetter?>"> <b><?=strtoupper($displayLetter)?>)</b> <?=e($q['alternativa_'.$originalLetter])?></label><?php endforeach;?></fieldset><?php endforeach;?><button class="btn" type="submit">Finalizar prova</button></form>
<script>(function(){const el=document.getElementById('timer'),form=document.getElementById('examForm');let s=parseInt(el.dataset.seconds,10);function tick(){const m=Math.floor(s/60),x=s%60;el.textContent='Tempo: '+String(m).padStart(2,'0')+':'+String(x).padStart(2,'0');if(s<=0){form.submit();return}s--;setTimeout(tick,1000)}tick()})();</script><?php require __DIR__.'/../includes/dashboard-footer.php';?>
