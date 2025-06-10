<?php
// ==========================================
// SCRIPT DE DEBUG DO CADASTRO
// Local: test_registration_debug.php
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$title = "🔍 Debug do Sistema de Cadastro";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #667eea; 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea; 
            padding-bottom: 15px; 
        }
        .debug-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .success { 
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .error { 
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .warning { 
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .info { 
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .test-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin: 5px;
        }
        .btn:hover { background: #5a6fd8; }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            margin: 5px 0;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #667eea;
            outline: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug do Sistema de Cadastro</h1>
        
        <?php
        function debugLog($message, $type = 'info') {
            $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
            echo "<div class='$type'>{$icons[$type]} $message</div>";
            flush();
        }
        
        function debugCode($title, $content) {
            echo "<h5>$title</h5>";
            echo "<div class='code'>$content</div>";
        }
        ?>

        <!-- DEBUG 1: Verificar Ambiente -->
        <div class="debug-section">
            <h3>🌍 DEBUG 1: Ambiente do Sistema</h3>
            <?php
            debugLog("PHP Version: " . PHP_VERSION);
            debugLog("Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'));
            debugLog("Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__));
            
            // Verificar extensões PHP
            $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
            foreach ($required_extensions as $ext) {
                if (extension_loaded($ext)) {
                    debugLog("Extensão $ext: Carregada", 'success');
                } else {
                    debugLog("Extensão $ext: NÃO CARREGADA", 'error');
                }
            }
            
            // Verificar variáveis de ambiente
            $env_vars = [
                'DATABASE_URL' => getenv('DATABASE_URL'),
                'MYSQLHOST' => getenv('MYSQLHOST'),
                'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT')
            ];
            
            foreach ($env_vars as $name => $value) {
                if (!empty($value)) {
                    if ($name === 'DATABASE_URL') {
                        debugLog("$name: Configurada (" . substr($value, 0, 20) . "...)", 'success');
                    } else {
                        debugLog("$name: $value", 'success');
                    }
                } else {
                    debugLog("$name: Não configurada", 'warning');
                }
            }
            ?>
        </div>

        <!-- DEBUG 2: Testar Conexão de Banco -->
        <div class="debug-section">
            <h3>🗄️ DEBUG 2: Conexão com Banco de Dados</h3>
            <?php
            $database_url = getenv('DATABASE_URL');
            
            if (!$database_url) {
                debugLog("DATABASE_URL não encontrada", 'error');
                
                // Tentar variáveis alternativas
                $mysql_host = getenv('MYSQLHOST');
                $mysql_db = getenv('MYSQLDATABASE');
                $mysql_user = getenv('MYSQLUSER');
                $mysql_pass = getenv('MYSQLPASSWORD');
                $mysql_port = getenv('MYSQLPORT') ?: '3306';
                
                if ($mysql_host && $mysql_db && $mysql_user && $mysql_pass) {
                    $database_url = "mysql://$mysql_user:$mysql_pass@$mysql_host:$mysql_port/$mysql_db";
                    debugLog("Construindo DATABASE_URL a partir de variáveis MYSQL", 'info');
                }
            }
            
            if ($database_url) {
                try {
                    $url_parts = parse_url($database_url);
                    
                    debugLog("Host: " . $url_parts['host'], 'info');
                    debugLog("Database: " . ltrim($url_parts['path'], '/'), 'info');
                    debugLog("Port: " . ($url_parts['port'] ?? 3306), 'info');
                    
                    $dsn = sprintf(
                        "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                        $url_parts['host'],
                        $url_parts['port'] ?? 3306,
                        ltrim($url_parts['path'], '/')
                    );
                    
                    $pdo = new PDO($dsn, $url_parts['user'], $url_parts['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT => 10
                    ]);
                    
                    debugLog("Conexão estabelecida com sucesso!", 'success');
                    
                    // Testar query simples
                    $stmt = $pdo->query("SELECT VERSION() as version");
                    $version = $stmt->fetch()['version'];
                    debugLog("MySQL Version: $version", 'success');
                    
                    // Verificar tabela usuarios
                    try {
                        $stmt = $pdo->query("DESCRIBE usuarios");
                        $columns = $stmt->fetchAll();
                        debugLog("Tabela 'usuarios' existe com " . count($columns) . " colunas", 'success');
                        
                        foreach ($columns as $column) {
                            debugLog("Coluna: {$column['Field']} ({$column['Type']})", 'info');
                        }
                        
                    } catch (PDOException $e) {
                        debugLog("Tabela 'usuarios' não existe", 'error');
                        debugLog("Erro: " . $e->getMessage(), 'error');
                        
                        // Tentar criar tabela
                        debugLog("Tentando criar tabela usuarios...", 'warning');
                        try {
                            $create_sql = "
                            CREATE TABLE usuarios (
                                id_usuario INT PRIMARY KEY AUTO_INCREMENT,
                                nome VARCHAR(100) NOT NULL,
                                email VARCHAR(100) UNIQUE NOT NULL,
                                senha VARCHAR(255) NOT NULL,
                                tipo ENUM('organizador', 'participante') NOT NULL DEFAULT 'participante',
                                telefone VARCHAR(20),
                                cidade VARCHAR(100),
                                estado VARCHAR(2),
                                ativo BOOLEAN DEFAULT TRUE,
                                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                ultimo_acesso TIMESTAMP NULL
                            );";
                            
                            $pdo->exec($create_sql);
                            debugLog("Tabela 'usuarios' criada com sucesso!", 'success');
                            
                        } catch (PDOException $e) {
                            debugLog("Erro ao criar tabela: " . $e->getMessage(), 'error');
                        }
                    }
                    
                } catch (PDOException $e) {
                    debugLog("Erro na conexão: " . $e->getMessage(), 'error');
                    debugCode("Detalhes do erro", $e->getTraceAsString());
                }
            } else {
                debugLog("Nenhuma configuração de banco encontrada", 'error');
            }
            ?>
        </div>

        <!-- DEBUG 3: Testar AuthController -->
        <div class="debug-section">
            <h3>🔐 DEBUG 3: AuthController</h3>
            <?php
            $auth_file = __DIR__ . '/controllers/AuthController.php';
            
            if (file_exists($auth_file)) {
                debugLog("Arquivo AuthController encontrado", 'success');
                
                try {
                    // Verificar sintaxe do arquivo
                    $syntax_check = shell_exec("php -l $auth_file 2>&1");
                    if (strpos($syntax_check, 'No syntax errors') !== false) {
                        debugLog("Sintaxe do AuthController válida", 'success');
                    } else {
                        debugLog("Erro de sintaxe no AuthController", 'error');
                        debugCode("Erro de sintaxe", $syntax_check);
                    }
                    
                    // Tentar carregar o arquivo
                    ob_start();
                    $load_result = include_once $auth_file;
                    $output = ob_get_clean();
                    
                    if ($output) {
                        debugLog("Output durante carregamento:", 'warning');
                        debugCode("Output", $output);
                    }
                    
                    if (class_exists('AuthController')) {
                        debugLog("Classe AuthController carregada", 'success');
                        
                        try {
                            $auth = new AuthController();
                            debugLog("AuthController instanciado com sucesso", 'success');
                            
                            // Verificar métodos
                            $methods = get_class_methods($auth);
                            $required_methods = ['login', 'register', 'logout'];
                            
                            foreach ($required_methods as $method) {
                                if (in_array($method, $methods)) {
                                    debugLog("Método '$method' existe", 'success');
                                } else {
                                    debugLog("Método '$method' NÃO existe", 'error');
                                }
                            }
                            
                        } catch (Exception $e) {
                            debugLog("Erro ao instanciar AuthController: " . $e->getMessage(), 'error');
                            debugCode("Stack trace", $e->getTraceAsString());
                        }
                        
                    } else {
                        debugLog("Classe AuthController NÃO encontrada", 'error');
                    }
                    
                } catch (Exception $e) {
                    debugLog("Erro ao carregar AuthController: " . $e->getMessage(), 'error');
                }
                
            } else {
                debugLog("Arquivo AuthController NÃO encontrado", 'error');
                debugLog("Caminho esperado: $auth_file", 'info');
            }
            ?>
        </div>

        <!-- DEBUG 4: Teste de Cadastro Real -->
        <div class="debug-section">
            <h3>🧪 DEBUG 4: Teste de Cadastro</h3>
            
            <?php
            // Processar teste se dados foram enviados
            if ($_POST && isset($_POST['test_register'])) {
                debugLog("Iniciando teste de cadastro...", 'info');
                
                try {
                    if (file_exists($auth_file)) {
                        require_once $auth_file;
                        
                        if (class_exists('AuthController')) {
                            $auth = new AuthController();
                            
                            $test_data = [
                                'nome' => $_POST['nome'],
                                'email' => $_POST['email'],
                                'senha' => $_POST['senha'],
                                'confirma_senha' => $_POST['confirma_senha'],
                                'tipo_usuario' => $_POST['tipo_usuario'],
                                'telefone' => $_POST['telefone'] ?? '',
                                'cidade' => $_POST['cidade'] ?? '',
                                'estado' => $_POST['estado'] ?? ''
                            ];
                            
                            debugLog("Dados de teste preparados", 'info');
                            debugCode("Dados enviados", print_r($test_data, true));
                            
                            $result = $auth->register($test_data);
                            
                            if ($result['success']) {
                                debugLog("✅ CADASTRO FUNCIONOU! " . $result['message'], 'success');
                                
                                if (isset($_SESSION['user_id'])) {
                                    debugLog("Sessão criada - ID: " . $_SESSION['user_id'], 'success');
                                    debugLog("Nome: " . ($_SESSION['user_name'] ?? 'N/A'), 'info');
                                    debugLog("Tipo: " . ($_SESSION['user_type'] ?? 'N/A'), 'info');
                                }
                                
                            } else {
                                debugLog("❌ CADASTRO FALHOU: " . $result['message'], 'error');
                            }
                            
                        } else {
                            debugLog("Classe AuthController não carregou", 'error');
                        }
                    } else {
                        debugLog("AuthController não encontrado", 'error');
                    }
                    
                } catch (Exception $e) {
                    debugLog("Erro durante teste: " . $e->getMessage(), 'error');
                    debugCode("Exception", $e->getTraceAsString());
                }
            }
            ?>
            
            <!-- Formulário de Teste -->
            <div class="test-form">
                <h4>Formulário de Teste de Cadastro</h4>
                <form method="POST">
                    <input type="hidden" name="test_register" value="1">
                    
                    <label>Nome Completo:</label>
                    <input type="text" name="nome" class="form-control" value="Teste Debug <?php echo date('H:i:s'); ?>" required>
                    
                    <label>Email:</label>
                    <input type="email" name="email" class="form-control" value="teste.debug.<?php echo time(); ?>@test.com" required>
                    
                    <label>Senha:</label>
                    <input type="password" name="senha" class="form-control" value="senha123" required>
                    
                    <label>Confirmar Senha:</label>
                    <input type="password" name="confirma_senha" class="form-control" value="senha123" required>
                    
                    <label>Tipo de Usuário:</label>
                    <select name="tipo_usuario" class="form-control" required>
                        <option value="participante">Participante</option>
                        <option value="organizador">Organizador</option>
                    </select>
                    
                    <label>Telefone (opcional):</label>
                    <input type="text" name="telefone" class="form-control" value="(11) 99999-9999">
                    
                    <label>Cidade (opcional):</label>
                    <input type="text" name="cidade" class="form-control" value="São Paulo">
                    
                    <label>Estado (opcional):</label>
                    <select name="estado" class="form-control">
                        <option value="">Selecione...</option>
                        <option value="SP">São Paulo</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="MG">Minas Gerais</option>
                    </select>
                    
                    <br>
                    <button type="submit" class="btn">🧪 Executar Teste de Cadastro</button>
                </form>
            </div>
        </div>

        <!-- DEBUG 5: Informações de Sessão -->
        <div class="debug-section">
            <h3>📋 DEBUG 5: Informações de Sessão</h3>
            <?php
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            debugLog("Status da sessão: " . (session_status() == PHP_SESSION_ACTIVE ? 'Ativa' : 'Inativa'), 
                     session_status() == PHP_SESSION_ACTIVE ? 'success' : 'warning');
            
            if (session_status() == PHP_SESSION_ACTIVE) {
                debugLog("ID da sessão: " . session_id(), 'info');
                
                if (!empty($_SESSION)) {
                    debugLog("Dados da sessão encontrados:", 'success');
                    debugCode("$_SESSION", print_r($_SESSION, true));
                } else {
                    debugLog("Sessão vazia", 'warning');
                }
            }
            ?>
        </div>

        <!-- DEBUG 6: Arquivos de Configuração -->
        <div class="debug-section">
            <h3>⚙️ DEBUG 6: Arquivos de Configuração</h3>
            <?php
            $config_files = [
                'config/config.php' => 'Configuração Principal',
                'config/database.php' => 'Configuração de Banco',
                'includes/session.php' => 'Funções de Sessão',
                'views/auth/register.php' => 'Página de Cadastro',
                'views/auth/login.php' => 'Página de Login'
            ];
            
            foreach ($config_files as $file => $description) {
                $full_path = __DIR__ . '/' . $file;
                
                if (file_exists($full_path)) {
                    $size = filesize($full_path);
                    $modified = date('d/m/Y H:i:s', filemtime($full_path));
                    debugLog("$description: Existe (${size} bytes, modificado em $modified)", 'success');
                } else {
                    debugLog("$description: NÃO EXISTE", 'error');
                    debugLog("Caminho: $full_path", 'info');
                }
            }
            ?>
        </div>

        <!-- AÇÕES FINAIS -->
        <div style="text-align: center; margin-top: 40px; padding: 20px; background: #e3f2fd; border-radius: 10px;">
            <h3>🔧 Ações Recomendadas</h3>
            <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <a href="emergency_fix.php" class="btn">🚨 Correção de Emergência</a>
                <a href="views/auth/register.php" class="btn">📝 Teste Cadastro Real</a>
                <a href="views/auth/login.php" class="btn">🔑 Teste Login</a>
                <a href="verification_final.php" class="btn">✅ Verificação Final</a>
            </div>
            
            <div style="margin-top: 20px;">
                <button onclick="location.reload()" class="btn">🔄 Atualizar Debug</button>
                <button onclick="copyDebugInfo()" class="btn">📋 Copiar Info Debug</button>
            </div>
        </div>
    </div>

    <script>
        function copyDebugInfo() {
            const debugInfo = document.documentElement.outerHTML;
            navigator.clipboard.writeText(debugInfo).then(() => {
                alert('Informações de debug copiadas para a área de transferência!');
            }).catch(() => {
                alert('Erro ao copiar. Selecione e copie manualmente.');
            });
        }
        
        // Auto-refresh a cada 30 segundos se não há POST
        <?php if (!$_POST): ?>
        setTimeout(() => {
            if (confirm('Atualizar debug automaticamente?')) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
        
        console.log('🔍 Debug do cadastro carregado');
        console.log('📊 Timestamp:', new Date().toISOString());
    </script>
</body>
</html>