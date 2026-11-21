<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

$stmt = db()->prepare('SELECT titulo, arquivo_pdf FROM livros WHERE id = ?');
$stmt->execute([(int)($_GET['id'] ?? 0)]);
$book = $stmt->fetch();

if (!$book || !$book['arquivo_pdf']) {
    http_response_code(404);
    exit('Livro digital não encontrado.');
}

$path = dirname(__DIR__) . '/assets/uploads/pdfs/' . basename($book['arquivo_pdf']);
if (!is_file($path)) {
    http_response_code(404);
    exit('O arquivo PDF não está disponível.');
}

// Libera a sessão: sem isso, a leitura de um PDF grande trava outras abas do aluno.
session_write_close();

// Nome do arquivo sem acentos ou caracteres especiais.
$nome = @iconv('UTF-8', 'ASCII//TRANSLIT', (string)$book['titulo']);
$nome = trim((string)preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)$nome), '-');
if ($nome === '') $nome = 'livro-digital';

$size = filesize($path);
$start = 0;
$end = $size - 1;

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nome . '.pdf"');
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

// Suporte a leitura parcial (Range): o navegador abre a primeira página sem baixar o arquivo todo.
if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m) || ($m[1] === '' && $m[2] === '')) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
    if ($m[1] === '') {
        $start = max(0, $size - (int)$m[2]);
    } else {
        $start = (int)$m[1];
        if ($m[2] !== '') $end = min((int)$m[2], $size - 1);
    }
    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}

$length = $end - $start + 1;
header('Content-Length: ' . $length);

$fh = fopen($path, 'rb');
fseek($fh, $start);
$restante = $length;
while ($restante > 0 && !feof($fh)) {
    $chunk = fread($fh, min(65536, $restante));
    if ($chunk === false) break;
    echo $chunk;
    $restante -= strlen($chunk);
    flush();
}
fclose($fh);
exit;
