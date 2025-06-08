<?php
// ==========================================
// CONFIGURAÇÕES GLOBAIS
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

// Classe Database
class Database {
    private $connection;
    
    public function getConnection() {
        if ($this->connection === null) {
            try {
                $database_url = getenv('DATABASE_URL');
                
                if (!$database_url) {
                    throw new Exception('DATABASE_URL não configurada');
                }
                
                // Parse da URL
                $url_parts = parse_url($database_url);
                
                if (!$url_parts) {
                    throw new Exception('DATABASE_URL mal formatada');
                }
                
                $host = $url_parts['host'] ?? '';
                $port = $url_parts['port'] ?? 3306;
                $database = ltrim($url_parts['path'] ?? '', '/');
                $username = $url_parts['user'] ?? '';
                $password = $url_parts['pass'] ?? '';
                
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_TIMEOUT => 10
                ];
                
                $this->connection = new PDO($dsn, $username, $password, $options);
                
            } catch (Exception $e) {
                error_log("Erro de conexão com banco: " . $e->getMessage());
                throw $e;
            }
        }
        
        return $this->connection;
    }
}

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

// Função para gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

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