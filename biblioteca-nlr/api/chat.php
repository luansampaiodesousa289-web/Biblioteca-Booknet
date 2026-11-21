<?php
require_once __DIR__.'/../config/auth.php';require_login();header('Content-Type: application/json');
$id=(int)($_GET['aluno']??0);
$s=db()->prepare('SELECT c.*,u.nome,u.foto FROM chat_privado c JOIN usuarios u ON u.id=c.remetente_id WHERE (c.remetente_id=? AND c.destinatario_id=?) OR (c.remetente_id=? AND c.destinatario_id=?) ORDER BY c.id');
$s->execute([user()['id'],$id,$id,user()['id']]);
echo json_encode($s->fetchAll(),JSON_UNESCAPED_UNICODE);