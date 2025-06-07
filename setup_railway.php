<?php
// ========================================
// SETUP AUTOMÁTICO PARA RAILWAY
// ========================================
// Acesse uma única vez: https://seu-app.railway.app/setup_railway.php
// ========================================

header('Content-Type: text/html; charset=utf-8');

function logStep($message, $success = true) {
    $icon = $success ? '✅' : '❌';
    $class = $success ? 'success' : 'error';
    echo "<div class='$class'>$icon $message</div>";
    flush();
    ob_flush();
}

function createFileIfNotExists($filePath, $content, $description) {
    if (!file_exists($filePath)) {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            logStep("Diretório criado: $dir");
        }
        
        if (file_put_contents($filePath, $content)) {
            logStep("$description criado: $filePath");
            return true;
        } else {
            logStep("Erro ao criar $description: $filePath", false);
            return false;
        }
    } else {
        logStep("$description já existe: $filePath");
        return true;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Setup Railway - Conecta Eventos</title>
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
        h2 { 
            color: #34495e; 
            margin-top: 30px;
            border-left: 4px solid #3498db;
            padding-left: 15px;
        }
        .success { 
            color: #27ae60; 
            padding: 8px 0; 
            font-weight: 500;
        }
        .error { 
            color: #e74c3c; 
            padding: 8px 0; 
            font-weight: bold; 
        }
        .warning { 
            color: #f39c12; 
            padding: 8px 0; 
        }
        .info { 
            color: #3498db; 
            padding: 8px 0; 
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #3498db;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #219a52;
        }
        .progress {
            background: #ecf0f1;
            height: 20px;
            border-radius: 10px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(45deg, #3498db, #2ecc71);
            height: 100%;
            transition: width 0.3s ease;
        }
        .code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            overflow-x: auto;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f1b0b7;
            color: #721c24;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Setup Automático - Railway</h1>
        
        <div class="alert alert-info">
            <strong>🔧 Conecta Eventos</strong><br>
            Este script irá configurar automaticamente todos os arquivos necessários para o funcionamento no Railway.
        </div>

        <?php
        $totalSteps = 8;
        $currentStep = 0;
        
        function updateProgress() {
            global $currentStep, $totalSteps;
            $currentStep++;
            $percentage = ($currentStep / $totalSteps) * 100;
            echo "<div class='progress'><div class='progress-bar' style='width: {$percentage}%'></div></div>";
            echo "<div class='info'>Progresso: {$currentStep}/{$totalSteps} ({$percentage}%)</div>";
        }
        ?>

        <div class="step">
            <h2>📋 Passo 1: Verificação do Ambiente</h2>
            <?php
            logStep("PHP Version: " . PHP_VERSION);
            logStep("Diretório atual: " . __DIR__);
            logStep("Servidor: " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            logStep("DATABASE_URL: " . (isset($_ENV['DATABASE_URL']) ? 'Configurada' : 'Não configurada'));
            updateProgress();
            ?>
        </div>

        <div class="step">
            <h2>📁 Passo 2: Criando Estrutura de Diretórios</h2>
            <?php
            $directories = [
                'config',
                'includes', 
                'controllers',
                'models',
                'views/auth',
                'views/dashboard',
                'views/events',
                'views/layouts',
                'api',
                'public/css',
                'public/js',
                'public/images',
                'public/uploads/eventos',
                'backups',
                'logs'
            ];
            
            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    if (mkdir($dir, 0755, true)) {
                        logStep("Diretório criado: $dir");
                    } else {
                        logStep("Erro ao criar diretório: $dir", false);
                    }
                } else {
                    logStep("Diretório já existe: $dir");
                }
            }
            updateProgress();
            ?>
        </div>

        <div class="step">
            <h2>⚙️ Passo 3: Configuração Principal</h2>
            <?php
            $configContent = '<?php
// Configuração Railway - Conecta Eventos
if (!defined("SITE_NAME")) {
    define("SITE_NAME", "Conecta Eventos");
}

if (!defined("SITE_URL")) {
    define("SITE_URL", "https://conecta-eventos-production.up.railway.app");
}

if (!defined("ADMIN_EMAIL")) {
    define("ADMIN_EMAIL", "admin@conectaeventos.com");
}

date_default_timezone_set("America/Sao_Paulo");

// Produção
error_reporting(0);
ini_set("display_errors", 0);
ini_set("log_errors", 1);

// Sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>';

            createFileIfNotExists('config/config.php', $configContent, 'Arquivo de configuração');
            updateProgress();
            ?>
        </div>

        <div class="step">
            <h2>💾 Passo 4: Configuração do Banco de Dados</h2>
            <?php
            $databaseContent = '<?php
class Database {
    private $conn;
    
    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        
        try {
            if (isset($_ENV["DATABASE_URL"]) && !empty($_ENV["DATABASE_URL"])) {
                $url = parse_url($_ENV["DATABASE_URL"]);
                
                if ($url === false) {
                    throw new Exception("Erro ao fazer parse da DATABASE_URL");
                }
                
                $host = $url["host"];
                $dbname = ltrim($url["path"], "/");
                $username = $url["user"];
                $password = $url["pass"] ?? "";
                $port = $url["port"] ?? 3306;
                
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                $this->conn = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
                
                return $this->conn;
            }
            
            // Fallback SQLite
            $dbPath = __DIR__ . "/../database.sqlite";
            $dsn = "sqlite:$dbPath";
            
            $this->conn = new PDO($dsn);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $this->createTables();
            
            return $this->conn;
            
        } catch(Exception $e) {
            throw new Exception("Erro na conexão: " . $e->getMessage());
        }
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('organizador', 'participante')),
            ativo BOOLEAN DEFAULT 1,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            ultimo_acesso DATETIME NULL
        );

        CREATE TABLE IF NOT EXISTS categorias (
            id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(50) NOT NULL,
            descricao TEXT,
            cor VARCHAR(7) DEFAULT '#007bff',
            icone VARCHAR(50) DEFAULT 'fa-calendar',
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
            status VARCHAR(20) DEFAULT 'rascunho' CHECK (status IN ('rascunho', 'publicado', 'cancelado', 'finalizado')),
            destaque BOOLEAN DEFAULT 0,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_organizador) REFERENCES usuarios(id_usuario),
            FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        );

        CREATE TABLE IF NOT EXISTS inscricoes (
            id_inscricao INTEGER PRIMARY KEY AUTOINCREMENT,
            id_evento INTEGER NOT NULL,
            id_participante INTEGER NOT NULL,
            data_inscricao DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'confirmada' CHECK (status IN ('pendente', 'confirmada', 'cancelada')),
            observacoes TEXT,
            presente BOOLEAN NULL,
            avaliacao_evento INTEGER CHECK (avaliacao_evento BETWEEN 1 AND 5),
            comentario_avaliacao TEXT,
            data_avaliacao DATETIME NULL,
            FOREIGN KEY (id_evento) REFERENCES eventos(id_evento),
            FOREIGN KEY (id_participante) REFERENCES usuarios(id_usuario),
            UNIQUE(id_evento, id_participante)
        );

        CREATE TABLE IF NOT EXISTS favoritos (
            id_favorito INTEGER PRIMARY KEY AUTOINCREMENT,
            id_usuario INTEGER NOT NULL,
            id_evento INTEGER NOT NULL,
            data_favoritado DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
            FOREIGN KEY (id_evento) REFERENCES eventos(id_evento),
            UNIQUE(id_usuario, id_evento)
        );";
        
        $this->conn->exec($sql);
        
        // Inserir dados de exemplo
        $this->insertSampleData();
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
            $categorias = "
            INSERT INTO categorias (nome, descricao, cor, icone) VALUES
            ('Tecnologia', 'Eventos relacionados à tecnologia', '#007bff', 'fa-laptop'),
            ('Negócios', 'Eventos corporativos e de negócios', '#28a745', 'fa-briefcase'),
            ('Educação', 'Eventos educacionais e de aprendizado', '#ffc107', 'fa-graduation-cap'),
            ('Arte e Cultura', 'Eventos artísticos e culturais', '#e83e8c', 'fa-palette');";
            
            $this->conn->exec($categorias);
            
            // Admin
            $adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
            $admin = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES ('Administrador', 'admin@conectaeventos.com', '$adminPassword', 'organizador');";
            
            $this->conn->exec($admin);
            
        } catch (Exception $e) {
            error_log("Erro ao inserir dados: " . $e->getMessage());
        }
    }
}
?>';

            createFileIfNotExists('config/database.php', $databaseContent, 'Configuração do banco');
            updateProgress();
            ?>
        </div>

        <div class="step">
            <h2>🔐 Passo 5: Sistema de Sessão</h2>
            <?php
            $sessionContent = '<?php
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

class AuthController {
    public function login($email, $senha) {
        if (class_exists("Database")) {
            try {
                $database = new Database();
                $conn = $database->getConnection();
                
                $stmt = $conn->prepare("SELECT id_usuario, nome, email, senha, tipo, ativo FROM usuarios WHERE email = ? AND ativo = 1");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch();
                    
                    if (password_verify($senha, $user["senha"])) {
                        loginUser($user["id_usuario"], $user["nome"], $user["email"], $user["tipo"]);
                        
                        $redirectUrl = $user["tipo"] === "organizador" 
                            ? SITE_URL . "/views/dashboard/organizer.php"
                            : SITE_URL . "/views/dashboard/participant.php";
                            
                        return [
                            "success" => true,
                            "message" => "Login realizado com sucesso!",
                            "redirect" => $redirectUrl
                        ];
                    } else {
                        return ["success" => false, "message" => "Senha incorreta."];
                    }
                }
                
                return ["success" => false, "message" => "E-mail não encontrado."];
                
            } catch (Exception $e) {
                return ["success" => false, "message" => "Erro no sistema de login."];
            }
        }
        
        return ["success" => false, "message" => "Sistema indisponível."];
    }
}

$auth = new AuthController();
?>';

            createFileIfNotExists('includes/session.php', $sessionContent, 'Sistema de sessão');
            updateProgress();
            ?>
        </div>

        <div class="step">
            <h2>🎯 Passo 6: Testando Conexão</h2>
            <?php
            try {
                if (file_exists('config/database.php')) {
                    require_once 'config/database.php';
                    logStep("Arquivo database.php carregado");
                    
                    if (class_exists('Database')) {
                        logStep("Classe Database encontrada");
                        
                        $database = new Database();
                        $conn = $database->getConnection();
                        logStep("Conexão estabelecida com sucesso");
                        
                        // Teste de query
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios");
                        $stmt->execute();
                        $result = $stmt->fetch();
                        logStep("Usuários no banco: " . $result['total']);
                        
                    } else {
                        logStep("Classe Database não encontrada", false);
                    }
                } else {
                    logStep("Arquivo database.php não encontrado", false);
                }
            } catch (Exception $e) {
                logStep("Erro na conexão: " . $e->getMessage(), false);
            }
            updateProgress();
            ?>
        </div>

        <div class="step">
            <h2>📄 Passo 7: Criando Arquivos Essenciais</h2>
            <?php
            // Criar .htaccess
            $htaccessContent = 'RewriteEngine On

# Redirect to index.php if file doesn\'t exist
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Hide PHP version
Header unset X-Powered-By';

            createFileIfNotExists('.htaccess', $htaccessContent, 'Arquivo .htaccess');
            
            // Criar .gitignore
            $gitignoreContent = '# Uploads e arquivos temporários
public/uploads/*
!public/uploads/.gitkeep
*.tmp
*.cache
*.log

# Configurações locais
config/local.php
.env.local

# Dependências
vendor/
node_modules/

# Arquivos do sistema
.DS_Store
Thumbs.db
*.swp
*.swo

# Backups
backups/*
!backups/.gitkeep

# IDEs
.vscode/
.idea/
*.sublime-*

# Logs
logs/*
!logs/.gitkeep';

            createFileIfNotExists('.gitignore', $gitignoreContent, 'Arquivo .gitignore');
            
            // Criar arquivo de uploads gitkeep
            createFileIfNotExists('public/uploads/.gitkeep', '', 'GitKeep uploads');
            createFileIfNotExists('backups/.gitkeep', '', 'GitKeep backups');
            createFileIfNotExists('logs/.gitkeep', '', 'GitKeep logs');
            
            updateProgress();
            ?>
        </div>

        <div class="step">
            <h2>🎉 Passo 8: Finalizando Setup</h2>
            <?php
            // Verificações finais
            $checksOk = 0;
            $totalChecks = 5;
            
            if (file_exists('config/config.php')) {
                logStep("✓ config/config.php criado");
                $checksOk++;
            }
            
            if (file_exists('config/database.php')) {
                logStep("✓ config/database.php criado");
                $checksOk++;
            }
            
            if (file_exists('includes/session.php')) {
                logStep("✓ includes/session.php criado");
                $checksOk++;
            }
            
            if (is_dir('public/uploads')) {
                logStep("✓ Diretório de uploads criado");
                $checksOk++;
            }
            
            if (class_exists('Database')) {
                logStep("✓ Sistema de banco funcionando");
                $checksOk++;
            }
            
            updateProgress();
            
            if ($checksOk === $totalChecks) {
                echo '<div class="alert alert-success">';
                echo '<h3>🎉 Setup Concluído com Sucesso!</h3>';
                echo '<p>Todos os componentes foram configurados corretamente. O sistema está pronto para uso.</p>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">';
                echo '<h3>⚠️ Setup Parcialmente Concluído</h3>';
                echo "<p>$checksOk de $totalChecks verificações passaram. Alguns problemas podem existir.</p>";
                echo '</div>';
            }
            ?>
        </div>

        <div class="alert alert-info">
            <h3>🔑 Credenciais de Acesso</h3>
            <div class="code">
<strong>E-mail:</strong> admin@conectaeventos.com<br>
<strong>Senha:</strong> admin123<br>
<strong>Tipo:</strong> Organizador
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <h2>🚀 Próximos Passos</h2>
            <a href="index.php" class="btn btn-success">🏠 Ir para o Site</a>
            <a href="views/auth/login.php" class="btn">🔑 Fazer Login</a>
            <a href="diagnostic.php" class="btn">🔧 Executar Diagnóstico</a>
            <a href="views/auth/register.php" class="btn">📝 Testar Registro</a>
        </div>

        <div style="text-align: center; margin-top: 40px; padding: 20px; background: #ecf0f1; border-radius: 10px;">
            <h3>🔧 Conecta Eventos - Setup Railway</h3>
            <p><strong>Desenvolvido por João Vitor da Silva</strong></p>
            <p>Deployment automático no Railway | Versão 1.0</p>
            <small>Setup executado em: <?php echo date('d/m/Y H:i:s'); ?></small>
        </div>
    </div>

    <script>
        // Auto-scroll para o final da página
        window.scrollTo(0, document.body.scrollHeight);
        
        // Log de conclusão
        console.log('🚀 Setup do Railway concluído!');
        console.log('📊 Progresso: <?php echo $currentStep; ?>/<?php echo $totalSteps; ?>');
        console.log('✅ Sistema pronto para uso!');
        
        // Redirecionar automaticamente após 10 segundos se tudo estiver OK
        <?php if ($checksOk === $totalChecks): ?>
        let countdown = 10;
        const redirectTimer = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(redirectTimer);
                window.location.href = 'index.php';
            }
        }, 1000);
        
        console.log('⏰ Redirecionando para página principal em 10 segundos...');
        <?php endif; ?>
    </script>
</body>
</html>