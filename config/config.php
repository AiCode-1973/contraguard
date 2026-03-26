<?php
// Configurações do Banco de Dados Remoto
define('DB_HOST', '186.209.113.107');
define('DB_USER', 'dema5738_contraguard');
define('DB_PASS', 'Dema@1973');
define('DB_NAME', 'dema5738_contraguard');

// Configurações do App
define('APP_NAME', 'ContraGuard');
// Configurações dinâmicas de URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = ($domainName == 'localhost' || $domainName == '127.0.0.1') ? $protocol . $domainName . '/contraguard' : $protocol . $domainName;

define('APP_URL', $baseUrl);

// Fuso Horário
date_default_timezone_set('America/Sao_Paulo');

// Iniciar Sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
