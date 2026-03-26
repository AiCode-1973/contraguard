<?php
// Configurações do Banco de Dados Remoto
define('DB_HOST', '186.209.113.107');
define('DB_USER', 'dema5738_contraguard');
define('DB_PASS', 'Dema@1973');
define('DB_NAME', 'dema5738_contraguard');

// Configurações do App
define('APP_NAME', 'ContraGuard');
define('APP_URL', 'http://localhost/contraguard');

// Fuso Horário
date_default_timezone_set('America/Sao_Paulo');

// Iniciar Sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
