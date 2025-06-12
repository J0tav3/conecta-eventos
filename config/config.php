<?php
// ==========================================
// CONFIGURAÇÕES GLOBAIS - VERSÃO CORRIGIDA
// Local: config/config.php
// ==========================================

// URL do site (SEMPRE usar a URL pública)
define('SITE_URL', 'https://conecta-eventos-production.up.railway.app');

// Configurações de banco de dados
// (Essas vêm das variáveis de ambiente do Railway)

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro
if ($_ENV['RAILWAY_ENVIRONMENT'] === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// CLASSE DATABASE REMOVIDA DAQUI - ESTÁ EM config/database.php

// Função para sanitizar dados
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// REMOVER AS FUNÇÕES CSRF DAQUI - ELAS ESTÃO EM session.php
// Configurações de upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Configurações de email (se necessário)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_EMAIL', 'noreply@conecta-eventos.com');
define('MAIL_FROM_NAME', 'Conecta Eventos');

// Configurações de paginação
define('ITEMS_PER_PAGE', 12);
define('MAX_PAGINATION_LINKS', 5);

// Status padrão
define('DEFAULT_USER_STATUS', 'ativo');
define('DEFAULT_EVENT_STATUS', 'rascunho');

?>