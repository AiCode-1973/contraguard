<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
verificarLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$forcar_download = isset($_GET['download']) && $_GET['download'] === '1';

if (!$id) {
    http_response_code(400);
    exit('Parâmetro inválido.');
}

$stmt = $pdo->prepare("SELECT * FROM garantia_anexos WHERE id = ?");
$stmt->execute([$id]);
$anexo = $stmt->fetch();

if (!$anexo) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$upload_dir = __DIR__ . '/../uploads/garantias/';
$caminho = $upload_dir . basename($anexo['nome_arquivo']);

if (!file_exists($caminho)) {
    http_response_code(404);
    exit('Arquivo não encontrado no servidor.');
}

$ext = strtolower(pathinfo($anexo['nome_arquivo'], PATHINFO_EXTENSION));

$tipos_mime = [
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$mime = $tipos_mime[$ext] ?? 'application/octet-stream';

$inline_types = ['pdf', 'png', 'jpg', 'jpeg'];
$disposicao = (!$forcar_download && in_array($ext, $inline_types)) ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposicao . '; filename="' . rawurlencode($anexo['nome_original']) . '"');
header('Content-Length: ' . filesize($caminho));
header('Cache-Control: private, no-cache');
readfile($caminho);
exit;
