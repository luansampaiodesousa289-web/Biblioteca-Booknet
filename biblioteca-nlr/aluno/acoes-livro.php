<?php
require_once __DIR__.'/../config/auth.php';require_login();if(is_admin()){http_response_code(403);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('aluno/livros.php');verify_csrf();$book=(int)($_POST['livro_id']??0);$action=$_POST['acao']??'';
if($action==='favorito'){db()->prepare('INSERT IGNORE INTO favoritos(usuario_id,livro_id) VALUES(?,?)')->execute([user()['id'],$book]);flash('success','Livro salvo nos favoritos.');}
if($action==='remover_favorito'){db()->prepare('DELETE FROM favoritos WHERE usuario_id=? AND livro_id=?')->execute([user()['id'],$book]);flash('success','Livro removido dos favoritos.');}
if($action==='reservar'){try{db()->prepare("INSERT INTO reservas(usuario_id,livro_id,status) VALUES(?,?, 'ativa')")->execute([user()['id'],$book]);flash('success','Reserva registrada. Avisaremos quando houver disponibilidade.');}catch(PDOException $e){flash('error','Você já possui uma reserva ativa para este livro.');}}
redirect('aluno/livro.php?id='.$book);
