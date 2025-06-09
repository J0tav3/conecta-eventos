<?php
// ==========================================
// AUTO CONFIGURAÇÃO RAILWAY
// Local: auto_config_railway.php
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

function autoConfigRailway() {
    $results = [];
    
    // 1. Verificar e criar config/database.php
    $results[] = createDatabaseConfig();
    
    // 2. Verificar e atualizar config/config.php
    $results[] = updateMainConfig();
    
    // 3. Verificar e atualizar includes/session.php
    $results[] = updateSessionConfig();
    
    // 4. Verificar e atualizar .htaccess
    $results[] = createHtaccess();
    
    // 5. Criar diretórios necessários
    $results[] = createDirectories();
    
    // 6. Testar conexão
    $results[] = testConnection();
    
    return $results;
}

function createDatabaseConfig() {
    $configPath = __DIR__ . '/config/database.php';
    
    // Verificar se diretório config existe
    if (!is_dir(dirname($configPath))) {
        mkdir(dirname($configPath), 0755, true);
    }
    
    $content = '<?php
// ==========================================
// CONFIGURAÇÃO DE BANCO RAILWAY - AUTO GERADO
// Local: config/database.php
// ==========================================

class Database {
    private $conn;
    private static $instance = null;
    private $connectionAttempts = 0;
    private $maxAttempts = 3;
    
    public function __construct() {
        $this->initializeConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        if ($this->conn === null) {
            $this->initializeConnection();
        }
        
        if ($this->conn && !$this->isConnectionAlive()) {
            $this->initializeConnection();
        }
        
        return $this->conn;
    }
    
    private function initializeConnection() {
        $this->connectionAttempts = 0;
        
        while ($this->connectionAttempts < $this->maxAttempts) {
            try {
                $this->connectionAttempts++;
                
                if ($this->connectMySQL()) {
                    $this->verifyAndSetupDatabase();
                    return;
                }
                
                if ($this->connectSQLite()) {
                    $this->verifyAndSetupDatabase();
                    return;
                }
                
            } catch (Exception $e) {
                if ($this->connectionAttempts >= $this->maxAttempts) {
                    throw new Exception("Falha ao conectar após {$this->maxAttempts} tentativas: " . $e->getMessage());
                }
                sleep(1);
            }
        }
        
        throw new Exception("Não foi possível estabelecer conexão com o banco de dados");
    }
    
    private function connectMySQL() {
        $databaseUrl = $this->getDatabaseUrl();
        
        if (!$databaseUrl) {
            return false;
        }
        
        $urlParts = parse_url($databaseUrl);
        
        if (!$urlParts || !isset($urlParts["host"], $urlParts["user"], $urlParts["path"])) {
            return false;
        }
        
        $host = $urlParts["host"];
        $port = $urlParts["port"] ?? 3306;
        $dbname = ltrim($urlParts["path"], "/");
        $username = $urlParts["user"];
        $password = $urlParts["pass"] ?? "";
        
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => false
        ];
        
        $this->conn = new PDO($dsn, $username, $password, $options);
        
        return true;
    }
    
    private function connectSQLite() {
        $dbPath = __DIR__ . "/../conecta_eventos.db";
        $dsn = "sqlite:{$dbPath}";
        
        $this->conn = new PDO($dsn);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return true;
    }
    
    private function getDatabaseUrl() {
        $sources = [
            $_ENV["DATABASE_URL"] ?? null,
            getenv("DATABASE_URL"),
            $_SERVER["DATABASE_URL"] ?? null,
            $this->buildRailwayUrl()
        ];
        
        foreach ($sources as $url) {
            if (!empty($url)) {
                return $url;
            }
        }
        
        return null;
    }
    
    private function buildRailwayUrl() {
        $host = getenv("MYSQLHOST") ?: getenv("DB_HOST");
        $port = getenv("MYSQLPORT") ?: getenv("DB_PORT") ?: "3306";
        $database = getenv("MYSQLDATABASE") ?: getenv("DB_NAME");
        $username = getenv("MYSQLUSER") ?: getenv("DB_USER");
        $password = getenv("MYSQLPASSWORD") ?: getenv("DB_PASSWORD");
        
        if ($host && $database && $username) {
            return "mysql://{$username}:{$password}@{$host}:{$port}/{$database}";
        }
        
        return null;
    }
    
    private function isConnectionAlive() {
        try {
            $this->conn->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function verifyAndSetupDatabase() {
        try {
            if (!$this->tableExists("usuarios")) {
                $this->createTables();
                $this->insertSampleData();
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    private function tableExists($tableName) {
        try {
            $stmt = $this->conn->prepare("SELECT 1 FROM {$tableName} LIMIT 1");
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function createTables() {
        $driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === "mysql") {
            $sql = $this->getMySQLSchema();
        } else {
            $sql = $this->getSQLiteSchema();
        }
        
        $commands = explode(";", $sql);
        
        foreach ($commands as $command) {
            $command = trim($command);
            if (!empty($command)) {
                $this->conn->exec($command);
            }
        }
    }
    
    private function getMySQLSchema() {
        return "
        CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            tipo ENUM(\"organizador\", \"participante\") NOT NULL DEFAULT \"participante\",
            telefone VARCHAR(20) NULL,
            cidade VARCHAR(100) NULL,
            estado VARCHAR(2) NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultimo_acesso TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS categorias (
            id_categoria INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(50) NOT NULL,
            descricao TEXT,
            cor VARCHAR(7) DEFAULT \"#007bff\",
            icone VARCHAR(50) DEFAULT \"fa-calendar\",
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS eventos (
            id_evento INT PRIMARY KEY AUTO_INCREMENT,
            id_organizador INT NOT NULL,
            id_categoria INT,
            titulo VARCHAR(200) NOT NULL,
            descricao TEXT NOT NULL,
            data_inicio DATE NOT NULL,
            data_fim DATE NOT NULL,
            horario_inicio TIME NOT NULL,
            horario_fim TIME NOT NULL,
            local_nome VARCHAR(100) NOT NULL,
            local_endereco VARCHAR(200) NOT NULL,
            local_cidade VARCHAR(50) NOT NULL,
            local_estado VARCHAR(2) NOT NULL,
            local_cep VARCHAR(10),
            capacidade_maxima INT,
            preco DECIMAL(10,2) DEFAULT 0.00,
            evento_gratuito BOOLEAN DEFAULT TRUE,
            imagem_capa VARCHAR(255),
            link_externo VARCHAR(255),
            requisitos TEXT,
            informacoes_adicionais TEXT,
            status ENUM(\"rascunho\", \"publicado\", \"cancelado\", \"finalizado\") DEFAULT \"rascunho\",
            destaque BOOLEAN DEFAULT FALSE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_organizador) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
    
    private function getSQLiteSchema() {
        return "
        CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT \"participante\",
            telefone VARCHAR(20),
            cidade VARCHAR(100),
            estado VARCHAR(2),
            ativo BOOLEAN DEFAULT 1,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            ultimo_acesso DATETIME NULL
        );

        CREATE TABLE IF NOT EXISTS categorias (
            id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(50) NOT NULL,
            descricao TEXT,
            cor VARCHAR(7) DEFAULT \"#007bff\",
            icone VARCHAR(50) DEFAULT \"fa-calendar\",
            ativo BOOLEAN DEFAULT 1,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS eventos (
            id_evento INTEGER PRIMARY KEY AUTOINCREMENT,
            id_organizador INTEGER NOT NULL,
            id_categoria INTEGER,
            titulo VARCHAR(200) NOT NULL,
            descricao TEXT NOT NULL,
            data_inicio DATE NOT NULL,
            data_fim DATE NOT NULL,
            horario_inicio TIME NOT NULL,
            horario_fim TIME NOT NULL,
            local_nome VARCHAR(100) NOT NULL,
            local_endereco VARCHAR(200) NOT NULL,
            local_cidade VARCHAR(50) NOT NULL,
            local_estado VARCHAR(2) NOT NULL,
            local_cep VARCHAR(10),
            capacidade_maxima INTEGER,
            preco DECIMAL(10,2) DEFAULT 0.00,
            evento_gratuito BOOLEAN DEFAULT 1,
            imagem_capa VARCHAR(255),
            link_externo VARCHAR(255),
            requisitos TEXT,
            informacoes_adicionais TEXT,
            status VARCHAR(20) DEFAULT \"rascunho\",
            destaque BOOLEAN DEFAULT 0,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_organizador) REFERENCES usuarios(id_usuario),
            FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        )";
    }
    
    private function insertSampleData() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM usuarios");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result["total"] > 0) {
                return;
            }
            
            // Categorias
            $categorias = [
                ["Tecnologia", "Eventos relacionados à tecnologia", "#007bff", "fa-laptop"],
                ["Negócios", "Eventos corporativos e de negócios", "#28a745", "fa-briefcase"],
                ["Educação", "Eventos educacionais e de aprendizado", "#ffc107", "fa-graduation-cap"]
            ];
            
            $stmt = $this->conn->prepare("INSERT INTO categorias (nome, descricao, cor, icone) VALUES (?, ?, ?, ?)");
            
            foreach ($categorias as $cat) {
                $stmt->execute($cat);
            }
            
            // Admin
            $adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute(["Administrador", "admin@conectaeventos.com", $adminPassword, "organizador"]);
            
        } catch (Exception $e) {
            // Dados de exemplo são opcionais
        }
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if (!$conn) {
                return ["success" => false, "message" => "Falha ao obter conexão"];
            }
            
            $stmt = $conn->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            if ($result && $result["test"] == 1) {
                $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
                return [
                    "success" => true, 
                    "message" => "Conexão ativa com {$driver}",
                    "driver" => $driver
                ];
            }
            
            return ["success" => false, "message" => "Teste de query falhou"];
            
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}

function getDatabase() {
    return Database::getInstance();
}

function getDatabaseConnection() {
    return Database::getInstance()->getConnection();
}
?>';
    
    if (file_put_contents($configPath, $content)) {
        return ['status' => 'success', 'message' => 'config/database.php criado com sucesso'];
    } else {
        return ['status' => 'error', 'message' => 'Falha ao criar config/database.php'];
    }
}

function updateMainConfig() {
    $configPath = __DIR__ . '/config/config.php';
    
    if (!is_dir(dirname($configPath))) {
        mkdir(dirname($configPath), 0755, true);
    }
    
    $content = '<?php
// ==========================================
// CONFIGURAÇÕES PRINCIPAIS - AUTO GERADO
// Local: config/config.php
// ==========================================

// URL do site
define("SITE_URL", "https://conecta-eventos-production.up.railway.app");
define("SITE_NAME", "Conecta Eventos");

// Configurações de timezone
date_default_timezone_set("America/Sao_Paulo");

// Configurações de erro para produção
error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("log_errors", 1);

// Configurações de sessão
ini_set("session.cookie_httponly", 1);
ini_set("session.cookie_secure", 1);
ini_set("session.use_strict_mode", 1);

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Funções auxiliares
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateCSRFToken() {
    if (!isset($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function verifyCSRFToken($token) {
    return isset($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
}

// Configurações de upload
define("UPLOAD_MAX_SIZE", 5 * 1024 * 1024); // 5MB
define("UPLOAD_ALLOWED_TYPES", ["jpg", "jpeg", "png", "gif", "webp"]);
define("UPLOAD_PATH", __DIR__ . "/../uploads/");

// Status padrão
define("DEFAULT_USER_STATUS", "ativo");
define("DEFAULT_EVENT_STATUS", "rascunho");
?>';
    
    if (file_put_contents($configPath, $content)) {
        return ['status' => 'success', 'message' => 'config/config.php atualizado'];
    } else {
        return ['status' => 'error', 'message' => 'Falha ao atualizar config/config.php'];
    }
}

function updateSessionConfig() {
    $sessionPath = __DIR__ . '/includes/session.php';
    
    if (!is_dir(dirname($sessionPath))) {
        mkdir(dirname($sessionPath), 0755, true);
    }
    
    $content = '<?php
// ==========================================
// SISTEMA DE SESSÃO - AUTO GERADO
// Local: includes/session.php
// ==========================================

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined("SITE_URL")) {
    define("SITE_URL", "https://conecta-eventos-production.up.railway.app");
}

function isLoggedIn() {
    return isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
}

function getUserId() {
    return $_SESSION["user_id"] ?? null;
}

function getUserName() {
    return $_SESSION["user_name"] ?? "Usuário";
}

function getUserEmail() {
    return $_SESSION["user_email"] ?? null;
}

function getUserType() {
    return $_SESSION["user_type"] ?? "participante";
}

function isOrganizer() {
    return isLoggedIn() && getUserType() === "organizador";
}

function isParticipant() {
    return isLoggedIn() && getUserType() === "participante";
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/views/auth/login.php");
        exit();
    }
}

function requireGuest() {
    if (isLoggedIn()) {
        $redirectUrl = isOrganizer() 
            ? SITE_URL . "/views/dashboard/organizer.php"
            : SITE_URL . "/views/dashboard/participant.php";
        header("Location: " . $redirectUrl);
        exit();
    }
}

function loginUser($userId, $userName, $userEmail, $userType) {
    $_SESSION["user_id"] = $userId;
    $_SESSION["user_name"] = $userName;
    $_SESSION["user_email"] = $userEmail;
    $_SESSION["user_type"] = $userType;
    $_SESSION["login_time"] = time();
    session_regenerate_id(true);
    return true;
}

function logoutUser() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    return true;
}

function setFlashMessage($message, $type = "info") {
    $_SESSION["flash_message"] = $message;
    $_SESSION["flash_type"] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION["flash_message"])) {
        $message = $_SESSION["flash_message"];
        $type = $_SESSION["flash_type"] ?? "info";
        
        unset($_SESSION["flash_message"]);
        unset($_SESSION["flash_type"]);
        
        return ["message" => $message, "type" => $type];
    }
    return null;
}

function showFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = [
            "success" => "alert-success",
            "error" => "alert-danger", 
            "danger" => "alert-danger",
            "warning" => "alert-warning",
            "info" => "alert-info"
        ];
        
        $class = $alertClass[$flash["type"]] ?? "alert-info";
        
        echo "<div class=\"alert $class alert-dismissible fade show\" role=\"alert\">";
        echo "<i class=\"fas fa-info-circle me-2\"></i>";
        echo htmlspecialchars($flash["message"]);
        echo "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>";
        echo "</div>";
    }
}
?>';
    
    if (file_put_contents($sessionPath, $content)) {
        return ['status' => 'success', 'message' => 'includes/session.php criado'];
    } else {
        return ['status' => 'error', 'message' => 'Falha ao criar includes/session.php'];
    }
}

function createHtaccess() {
    $htaccessPath = __DIR__ . '/.htaccess';
    
    $content = 'RewriteEngine On

# Configurações de segurança
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Ocultar versão do PHP
Header unset X-Powered-By

# Configurações de compressão
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Configurações de cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
</IfModule>

# Proteção de arquivos sensíveis
<Files "*.php~">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

# Redirecionamento de erro 404
ErrorDocument 404 /index.php

# Configurações de upload
php_value upload_max_filesize 5M
php_value post_max_size 10M
php_value max_execution_time 300
php_value memory_limit 256M';
    
    if (file_put_contents($htaccessPath, $content)) {
        return ['status' => 'success', 'message' => '.htaccess criado'];
    } else {
        return ['status' => 'warning', 'message' => 'Não foi possível criar .htaccess'];
    }
}

function createDirectories() {
    $directories = [
        'uploads',
        'uploads/eventos',
        'uploads/usuarios',
        'logs',
        'backups',
        'temp',
        'public/css',
        'public/js',
        'public/images'
    ];
    
    $created = [];
    $errors = [];
    
    foreach ($directories as $dir) {
        $path = __DIR__ . '/' . $dir;
        if (!is_dir($path)) {
            if (mkdir($path, 0755, true)) {
                $created[] = $dir;
                
                // Criar .gitkeep para manter diretórios vazios no Git
                file_put_contents($path . '/.gitkeep', '');
            } else {
                $errors[] = $dir;
            }
        }
    }
    
    if (empty($errors)) {
        return ['status' => 'success', 'message' => 'Diretórios criados: ' . implode(', ', $created)];
    } else {
        return ['status' => 'warning', 'message' => 'Alguns diretórios não puderam ser criados: ' . implode(', ', $errors)];
    }
}

function testConnection() {
    try {
        if (file_exists(__DIR__ . '/config/database.php')) {
            require_once __DIR__ . '/config/database.php';
            
            if (class_exists('Database')) {
                $db = Database::getInstance();
                $result = $db->testConnection();
                
                if ($result['success']) {
                    return ['status' => 'success', 'message' => 'Conexão de banco testada: ' . $result['message']];
                } else {
                    return ['status' => 'error', 'message' => 'Falha no teste de conexão: ' . $result['message']];
                }
            } else {
                return ['status' => 'error', 'message' => 'Classe Database não encontrada'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Arquivo database.php não encontrado'];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Erro no teste: ' . $e->getMessage()];
    }
}

// ==========================================
// EXECUTAR CONFIGURAÇÃO AUTOMÁTICA
// ==========================================

if ($_GET['action'] === 'auto_config' || !isset($_GET['action'])) {
    $results = autoConfigRailway();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// ==========================================
// INTERFACE WEB
// ==========================================
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Auto Configuração Railway</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #2c3e50; 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db; 
            padding-bottom: 15px; 
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .results {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
        }
        .result-item {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .result-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .result-warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .result-error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Auto Configuração Railway</h1>
        
        <div class="info-box">
            <h3>⚡ Configuração Automática</h3>
            <p>Este script irá configurar automaticamente todos os arquivos necessários para o funcionamento do Conecta Eventos no Railway:</p>
            <ul>
                <li>✅ Criação do <code>config/database.php</code> otimizado</li>
                <li>✅ Atualização do <code>config/config.php</code></li>
                <li>✅ Configuração do <code>includes/session.php</code></li>
                <li>✅ Criação de diretórios necessários</li>
                <li>✅ Configuração do <code>.htaccess</code></li>
                <li>✅ Teste de conectividade com o banco</li>
            </ul>
        </div>
        
        <div style="text-align: center;">
            <button class="btn" onclick="runAutoConfig()">
                🔧 Executar Configuração Automática
            </button>
            
            <a href="diagnostic.php" class="btn" style="background: #28a745;">
                📊 Executar Diagnóstico
            </a>
            
            <a href="index.php" class="btn" style="background: #6c757d;">
                🏠 Ir para o Site
            </a>
        </div>
        
        <div id="loading" class="loading" style="display: none;">
            <div class="spinner"></div>
            <p>Configurando sistema...</p>
        </div>
        
        <div id="results" class="results"></div>
        
        <div class="info-box">
            <h3>🔑 Credenciais Padrão</h3>
            <p><strong>E-mail:</strong> admin@conectaeventos.com<br>
            <strong>Senha:</strong> admin123<br>
            <strong>Tipo:</strong> Organizador</p>
        </div>
    </div>

    <script>
        function runAutoConfig() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('results').style.display = 'none';
            
            fetch('?action=auto_config')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    displayResults(data.results);
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    displayError('Erro na configuração: ' + error.message);
                });
        }
        
        function displayResults(results) {
            const resultsDiv = document.getElementById('results');
            let html = '<h3>📋 Resultados da Configuração</h3>';
            
            results.forEach(result => {
                const className = 'result-' + result.status;
                const icon = result.status === 'success' ? '✅' : 
                           result.status === 'warning' ? '⚠️' : '❌';
                
                html += `<div class="result-item ${className}">${icon} ${result.message}</div>`;
            });
            
            html += '<div style="margin-top: 20px; text-align: center;">';
            html += '<a href="views/auth/login.php" class="btn">🔑 Testar Login</a>';
            html += '<a href="views/auth/register.php" class="btn">📝 Testar Cadastro</a>';
            html += '<a href="diagnostic.php" class="btn">🔧 Executar Diagnóstico</a>';
            html += '</div>';
            
            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }
        
        function displayError(message) {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = `<div class="result-item result-error">❌ ${message}</div>`;
            resultsDiv.style.display = 'block';
        }
    </script>
</body>
</html>