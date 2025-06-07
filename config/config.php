<?php
// config/config.php - Versão Railway
define('SITE_NAME', 'Conecta Eventos');

// URL fixa para Railway
define('SITE_URL', 'https://conecta-eventos-production.up.railway.app');
define('ADMIN_EMAIL', 'admin@conectaeventos.com');

date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro para produção
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
?>