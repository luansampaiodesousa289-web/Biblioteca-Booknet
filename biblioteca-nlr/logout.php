<?php require_once __DIR__.'/config/auth.php';$_SESSION=[];session_destroy();session_start();flash('success','Você saiu da sua conta.');redirect('index.php');
