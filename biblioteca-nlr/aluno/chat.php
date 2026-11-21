<?php
require_once __DIR__.'/../config/auth.php'; require_login();
if(user()['tipo']!=='aluno'){http_response_code(403);exit;}

$alunos=db()->prepare("SELECT id,nome,foto FROM usuarios WHERE tipo='aluno' AND status='ativo' AND id<>? ORDER BY nome");
$alunos->execute([user()['id']]); $alunos=$alunos->fetchAll();
$dest=(int)($_GET['aluno']??0);

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $m=trim($_POST['mensagem']??'');
    $d=(int)($_POST['destinatario_id']??0);
    if($m!==''&&$d){
        db()->prepare('INSERT INTO chat_privado(remetente_id,destinatario_id,mensagem) VALUES(?,?,?)')->execute([user()['id'],$d,$m]);
    }
    redirect('aluno/chat.php?aluno='.$d);
}

require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/dashboard-nav.php';
$fu=db()->prepare('SELECT foto FROM usuarios WHERE id=?');
$fu->execute([user()['id']]);
$fotoUser=$fu->fetchColumn() ?: 'assets/uploads/perfis/default.png';
?>
<section class="panel whatsapp-chat">
<div class="chat-profile">
<img class="profile-avatar-img" src="<?=e(base_url($fotoUser ?: 'assets/uploads/perfis/default.png'))?>" alt="Foto">
<div><h2>💬 Conversas</h2><p><?=htmlspecialchars(user()['nome'])?> · Online</p><small>📚 Estante • Favoritos • Empréstimos</small></div>
</div>

<div class="chat-layout">
<aside class="chat-users">
<h3>👥 Conversas</h3>
<?php foreach($alunos as $a): ?>
<a class="chat-user" href="?aluno=<?=$a['id']?>">
<img class="avatar" src="<?=e(base_url($a['foto'] ?: 'assets/uploads/perfis/default.png'))?>" alt="Foto">
<span><?=e($a['nome'])?><small>Online</small></span>
</a>
<?php endforeach; ?>
</aside>

<main class="chat-box">
<div id="chat-messages" class="chat-messages"></div>
<?php if($dest): ?>
<form class="chat-compose" method="post">
<?=csrf_field()?>
<input type="hidden" name="destinatario_id" value="<?=$dest?>">
<input name="mensagem" maxlength="500" required placeholder="Digite uma mensagem...">
<button class="btn">➤</button>
</form>
<?php else: ?>
<p class="empty-chat">Escolha uma conversa para começar.</p>
<?php endif; ?>
</main>
</div>
</section>

<script>
const id=<?=$dest?>;
const minhaId=<?=user()['id']?>;
async function load(){
 if(!id)return;
 let r=await fetch('../api/chat.php?aluno='+id);
 let d=await r.json();
 document.querySelector('#chat-messages').innerHTML=d.map(x=>
 `<div class="chat-message ${x.remetente_id==minhaId?'mine':''}">
   <img class="msg-avatar" src="<?=base_url()?>/${x.foto ? x.foto : 'assets/uploads/perfis/default.png'}">
   <div><b>${x.nome}</b><p>${x.mensagem}</p></div>
 </div>`).join('');
 let box=document.querySelector('#chat-messages');box.scrollTop=box.scrollHeight;
}
load();setInterval(load,4000);
</script>
<?php require __DIR__.'/../includes/dashboard-footer.php'; ?>
