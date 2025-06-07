<?php
// Configurações gerais do sistema
define('SITE_NAME', 'Conecta Eventos');

// URL dinâmica baseada no ambiente
if (isset($_ENV['RAILWAY_ENVIRONMENT'])) {
    // Produção no Railway - usar domínio fixo
    define('SITE_URL', 'https://conecta-eventos-production.up.railway.app');
} else {
    // Desenvolvimento local
    define('SITE_URL', 'http://localhost/conecta-eventos');
}

define('ADMIN_EMAIL', 'admin@conectaeventos.com');

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro para produção
if (isset($_ENV['RAILWAY_ENVIRONMENT'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>