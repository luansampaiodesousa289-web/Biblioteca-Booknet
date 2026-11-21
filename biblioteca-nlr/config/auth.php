<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_start(['cookie_httponly'=>true, 'cookie_samesite'=>'Lax', 'cookie_secure'=>$https]);
}
function user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return in_array(user()['tipo'] ?? '', ['administrador','gestor','bibliotecario'], true); }
function require_login(): void { if (!user()) { flash('error', 'Entre para acessar esta área.'); redirect('login.php'); } }
function require_admin(): void { require_login(); if (!is_admin()) { http_response_code(403); exit('Acesso não autorizado.'); } }
function login_user(array $u): void { session_regenerate_id(true); $_SESSION['user'] = ['id'=>(int)$u['id'],'nome'=>$u['nome'],'email'=>$u['email'],'tipo'=>$u['tipo']]; }
