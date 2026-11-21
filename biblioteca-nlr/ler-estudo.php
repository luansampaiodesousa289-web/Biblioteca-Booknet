<?php
require_once __DIR__.'/../config/auth.php';require_login();
$s=db()->prepare('SELECT arquivo_pdf FROM materiais_estudo WHERE id=?');$s->execute([(int)($_GET['id']??0)]);$m=$s->fetch();
if(!$m||!$m['arquivo_pdf']){http_response_code(404);exit('Material não encontrado.');}
$path=dirname(__DIR__).'/assets/uploads/pdfs/'.basename($m['arquivo_pdf']);if(!is_file($path)){http_response_code(404);exit('Arquivo não disponível.');}
header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="material-de-estudo.pdf"');header('Content-Length: '.filesize($path));header('X-Content-Type-Options: nosniff');readfile($path);exit;
