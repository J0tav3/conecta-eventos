<?php
// ==========================================
// CONFIGURAÇÕES GERAIS - VERSÃO LIMPA
// Local: config/config.php
// ==========================================

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Configurações de sessão
ini_set('session.cookie_lifetime', 86400); // 24 horas
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_secure', 0); // Para desenvolvimento local
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Configurações de upload
ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '6M');
ini_set('max_file_uploads', 10);

// Configurações de charset
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// URLs e caminhos da aplicação
define('BASE_URL', 'https://conecta-eventos-production.up.railway.app');
define('BASE_PATH', __DIR__ . '/../');
define('UPLOAD_PATH', BASE_PATH . 'uploads/');
define('PROFILE_UPLOAD_PATH', UPLOAD_PATH . 'profiles/');
define('EVENT_UPLOAD_PATH', UPLOAD_PATH . 'eventos/');

/**
 * Função para sanitizar dados de entrada
 * Previne XSS e outros ataques
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Função para criar diretórios se não existirem
 */
function ensureDirectoryExists($path) {
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
        
        // Criar arquivo .htaccess para segurança
        $htaccessPath = $path . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Impedir execução de scripts\n";
            $htaccessContent .= "php_flag engine off\n";
            $htaccessContent .= "AddType text/plain .php .php3 .phtml .pht\n";
            $htaccessContent .= "\n# Apenas imagens para uploads\n";
            $htaccessContent .= "<Files ~ \"\\.(php|php3|phtml|pht|jsp|asp|aspx|cgi|pl)$\">\n";
            $htaccessContent .= "    Order allow,deny\n";
            $htaccessContent .= "    Deny from all\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
        }
        
        // Criar index.php para proteção
        $indexPath = $path . '/index.php';
        if (!file_exists($indexPath)) {
            $indexContent = "<?php\n";
            $indexContent .= "// Proteção do diretório\n";
            $indexContent .= "header('HTTP/1.0 403 Forbidden');\n";
            $indexContent .= "exit('Acesso negado.');\n";
            $indexContent .= "?>";
            
            file_put_contents($indexPath, $indexContent);
        }
    }
}

/**
 * Função para debug seguro
 */
function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    
    error_log("[CONECTA_EVENTOS] $logMessage");
}

/**
 * Função para gerar URLs seguras
 */
function generateUrl($path = '') {
    $baseUrl = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    
    return $baseUrl . ($path ? '/' . $path : '');
}

/**
 * Função para verificar se é HTTPS
 */
function isHttps() {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        $_SERVER['SERVER_PORT'] == 443 ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
}

/**
 * Headers de segurança
 */
function setSecurityHeaders() {
    // Prevenir clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevenir MIME sniffing
    header('X-Content-Type-Options: nosniff');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy básico
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;");
}

// Criar diretórios necessários
ensureDirectoryExists(UPLOAD_PATH);
ensureDirectoryExists(PROFILE_UPLOAD_PATH);
ensureDirectoryExists(EVENT_UPLOAD_PATH);

// Aplicar headers de segurança
setSecurityHeaders();

// Log de inicialização
debugLog("Aplicação inicializada", [
    'timestamp' => date('Y-m-d H:i:s'),
    'base_url' => BASE_URL,
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
]);
?>