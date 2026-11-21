<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function base_url(string $path = ''): string {
    // Caminho relativo à raiz do site (sem http:// nem host): funciona igual em
    // http e https, por IP ou domínio, atrás de proxy, no celular e no PC.
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    // Remove o segmento final de admin/aluno/api para obter a raiz do projeto.
    if (preg_match('~/(?:admin|aluno|api)/[^/]+$~', $script)) {
        $folder = preg_replace('~/(?:admin|aluno|api)/[^/]+$~', '', $script);
    } else {
        $folder = rtrim(str_replace('\\', '/', dirname($script)), '/');
    }
    if ($folder === '.' || $folder === '/') $folder = '';
    return $folder . ($path !== '' ? '/' . ltrim($path, '/') : '');
}
function redirect(string $path): never { header('Location: ' . base_url($path)); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Solicitação inválida. Atualize a página e tente novamente.'); } }
function flash(string $type, string $message): void { $_SESSION['flash'] = compact('type', 'message'); }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function upload_cover(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024) throw new RuntimeException('A capa deve ter no máximo 3 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Envie uma imagem JPG, PNG ou WEBP válida.');
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $dir = dirname(__DIR__) . '/assets/uploads/capas';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Não foi possível guardar a capa.');
    return $name;
}
function upload_pdf(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 20 * 1024 * 1024) throw new RuntimeException('O PDF deve ter no máximo 20 MB.');
    if ((new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) !== 'application/pdf') throw new RuntimeException('Envie um arquivo PDF válido.');
    $name = bin2hex(random_bytes(16)) . '.pdf';
    $dir = dirname(__DIR__) . '/assets/uploads/pdfs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Não foi possível guardar o PDF.');
    return $name;
}
function loan_status(array $loan): string { return $loan['status'] === 'ativo' && $loan['data_prevista_devolucao'] < date('Y-m-d') ? 'atrasado' : $loan['status']; }

function prova_gerar_ordem_alternativas(): array {
    $ordem = ['a','b','c','d'];
    shuffle($ordem);
    return $ordem;
}

function prova_garantir_ordens(int $tentativaId, array $questions): array {
    $pdo = db();
    $sel = $pdo->prepare('SELECT pergunta_id, ordem FROM prova_ordens_alternativas WHERE tentativa_id=?');
    $sel->execute([$tentativaId]);
    $ordens = [];
    foreach ($sel->fetchAll() as $row) $ordens[(int)$row['pergunta_id']] = (string)$row['ordem'];
    $ins = $pdo->prepare('INSERT INTO prova_ordens_alternativas(tentativa_id, pergunta_id, ordem) VALUES(?,?,?)');
    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        if (!isset($ordens[$qid])) {
            $ordens[$qid] = implode('', prova_gerar_ordem_alternativas());
            $ins->execute([$tentativaId, $qid, $ordens[$qid]]);
        }
    }
    return $ordens;
}

/**
 * Garante que as tabelas do módulo de provas existam no banco atual.
 * Isso permite atualizar instalações antigas do BookNet sem exigir
 * importação manual do SQL toda vez que o módulo for acessado.
 */
function ensure_assessment_tables(): void {
    static $done = false;
    if ($done) return;
    $pdo = db();

    $sql = [
        "CREATE TABLE IF NOT EXISTS provas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            disciplina VARCHAR(120) NOT NULL,
            descricao TEXT,
            duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
            publicada TINYINT(1) NOT NULL DEFAULT 1,
            criado_por INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_provas_criado_por (criado_por),
            INDEX idx_provas_publicada (publicada)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS prova_perguntas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            prova_id INT UNSIGNED NOT NULL,
            pergunta TEXT NOT NULL,
            alternativa_a VARCHAR(500) NOT NULL,
            alternativa_b VARCHAR(500) NOT NULL,
            alternativa_c VARCHAR(500) NOT NULL,
            alternativa_d VARCHAR(500) NOT NULL,
            correta ENUM('a','b','c','d') NOT NULL,
            INDEX idx_prova_perguntas_prova (prova_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS prova_tentativas (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            prova_id INT UNSIGNED NOT NULL,
            usuario_id INT UNSIGNED NOT NULL,
            iniciado_em DATETIME NOT NULL,
            finalizado_em DATETIME NULL,
            acertos INT UNSIGNED NOT NULL DEFAULT 0,
            total INT UNSIGNED NOT NULL DEFAULT 0,
            nota DECIMAL(5,2) NOT NULL DEFAULT 0,
            status ENUM('em_andamento','finalizada','expirada') NOT NULL DEFAULT 'em_andamento',
            UNIQUE KEY uma_tentativa (prova_id, usuario_id),
            INDEX idx_prova_tentativas_usuario (usuario_id),
            INDEX idx_prova_tentativas_prova (prova_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS prova_respostas (
            tentativa_id BIGINT UNSIGNED NOT NULL,
            pergunta_id INT UNSIGNED NOT NULL,
            resposta ENUM('a','b','c','d') NOT NULL,
            PRIMARY KEY (tentativa_id, pergunta_id),
            INDEX idx_prova_respostas_pergunta (pergunta_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS prova_ordens_alternativas (
            tentativa_id BIGINT UNSIGNED NOT NULL,
            pergunta_id INT UNSIGNED NOT NULL,
            ordem CHAR(4) NOT NULL,
            PRIMARY KEY (tentativa_id, pergunta_id),
            INDEX idx_prova_ordens_pergunta (pergunta_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach ($sql as $statement) $pdo->exec($statement);
    $done = true;
}

