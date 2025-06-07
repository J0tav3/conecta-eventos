<?php
// ========================================
// CONFIGURAÇÃO PRINCIPAL - RAILWAY
// ========================================
// Local: config/config.php
// ========================================

// Definir constantes principais
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Conecta Eventos');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://conecta-eventos-production.up.railway.app');
}

if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'admin@conectaeventos.com');
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro para produção
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configurações de sessão
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Carregar database se existir
$databaseFile = __DIR__ . '/database.php';
if (file_exists($databaseFile)) {
    require_once $databaseFile;
}

// Funções auxiliares de configuração
function getEnvVar($name, $default = null) {
    return $_ENV[$name] ?? $default;
}

function isDevelopment() {
    return getEnvVar('APP_ENV', 'production') === 'development';
}

function isProduction() {
    return getEnvVar('APP_ENV', 'production') === 'production';
}

// Configurações específicas do ambiente
if (isDevelopment()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Headers de segurança
if (isProduction()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Verificar se todas as extensões necessárias estão ativas
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    error_log('Extensões PHP faltando: ' . implode(', ', $missingExtensions));
}

// Log de inicialização
if (isDevelopment()) {
    error_log('Config carregado - ' . date('Y-m-d H:i:s'));
}
?>