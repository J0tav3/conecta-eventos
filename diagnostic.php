<?php
// ========================================
// FERRAMENTA DE DIAGNÓSTICO - RAILWAY
// ========================================
// Acesse: https://seu-app.railway.app/diagnostic.php
// ========================================

header('Content-Type: text/html; charset=utf-8');

// Função para verificar status
function checkStatus($condition, $message) {
    $icon = $condition ? '✅' : '❌';
    $class = $condition ? 'success' : 'error';
    return "<div class='$class'>$icon $message</div>";
}

// Função para testar inclusão de arquivos
function testInclude($file, $description) {
    $fullPath = __DIR__ . '/' . $file;
    $exists = file_exists($fullPath);
    $readable = $exists && is_readable($fullPath);
    
    echo checkStatus($exists, "$description existe: $fullPath");
    if ($exists) {
        echo checkStatus($readable, "$description é legível");
        if ($readable) {
            try {
                ob_start();
                $result = include_once $fullPath;
                $output = ob_get_clean();
                echo checkStatus(true, "$description incluído com sucesso");
                if (!empty($output)) {
                    echo "<div class='warning'>⚠️ Output durante inclusão: " . htmlspecialchars(substr($output, 0, 100)) . "</div>";
                }
            } catch (Exception $e) {
                echo checkStatus(false, "$description erro na inclusão: " . $e->getMessage());
            }
        }
    }
    echo "<br>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Diagnóstico - Conecta Eventos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .success { color: #27ae60; padding: 5px 0; }
        .error { color: #e74c3c; padding: 5px 0; font-weight: bold; }
        .warning { color: #f39c12; padding: 5px 0; }
        .info { color: #3498db; padding: 5px 0; }
        .code { background: #ecf0f1; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #3498db; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .status-card { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnóstico do Sistema - Conecta Eventos</h1>
        
        <div class="info">
            <strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
            <strong>Servidor:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'localhost'; ?><br>
            <strong>PHP:</strong> <?php echo PHP_VERSION; ?><br>
            <strong>Diretório:</strong> <?php echo __DIR__; ?>
        </div>

        <div class="section">
            <h2>🌐 1. Informações do Ambiente</h2>
            <div class="status-grid">
                <div class="status-card">
                    <h4>Servidor</h4>
                    <?php
                    echo checkStatus(true, "PHP Version: " . PHP_VERSION);
                    echo checkStatus(function_exists('mysqli_connect') || extension_loaded('pdo'), "MySQL Support disponível");
                    echo checkStatus(extension_loaded('pdo'), "PDO Extension");
                    echo checkStatus(extension_loaded('pdo_mysql'), "PDO MySQL Extension");
                    echo checkStatus(extension_loaded('json'), "JSON Extension");
                    echo checkStatus(extension_loaded('mbstring'), "MBString Extension");
                    ?>
                </div>
                
                <div class="status-card">
                    <h4>Sessão</h4>
                    <?php
                    session_start();
                    echo checkStatus(session_status() === PHP_SESSION_ACTIVE, "Sessão ativa");
                    echo checkStatus(!empty(session_id()), "Session ID: " . session_id());
                    echo checkStatus(ini_get('session.use_cookies'), "Cookies de sessão habilitados");
                    ?>
                </div>
                
                <div class="status-card">
                    <h4>Variáveis de Ambiente</h4>
                    <?php
                    echo checkStatus(isset($_ENV['DATABASE_URL']), "DATABASE_URL definida");
                    if (isset($_ENV['DATABASE_URL'])) {
                        $url = parse_url($_ENV['DATABASE_URL']);
                        echo checkStatus(isset($url['host']), "Database Host: " . ($url['host'] ?? 'N/A'));
                        echo checkStatus(isset($url['path']), "Database Name: " . (ltrim($url['path'] ?? '', '/') ?: 'N/A'));
                    }
                    echo checkStatus(isset($_ENV['PORT']), "PORT: " . ($_ENV['PORT'] ?? 'N/A'));
                    ?>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>📁 2. Verificação de Arquivos</h2>
            <?php
            testInclude('config/config.php', 'Configuração Principal');
            testInclude('config/database.php', 'Configuração do Banco');
            testInclude('includes/session.php', 'Sistema de Sessão');
            testInclude('controllers/AuthController.php', 'Controlador de Autenticação');
            testInclude('models/User.php', 'Model de Usuário');
            testInclude('models/Event.php', 'Model de Evento');
            ?>
        </div>

        <div class="section">
            <h2>🔧 3. Teste de Funcionalidades</h2>
            <?php
            // Teste de constantes
            echo "<h4>Constantes:</h4>";
            echo checkStatus(defined('SITE_URL'), "SITE_URL definida: " . (defined('SITE_URL') ? SITE_URL : 'N/A'));
            echo checkStatus(defined('SITE_NAME'), "SITE_NAME definida: " . (defined('SITE_NAME') ? SITE_NAME : 'N/A'));
            
            // Teste de funções de sessão
            echo "<h4>Funções de Sessão:</h4>";
            echo checkStatus(function_exists('isLoggedIn'), "isLoggedIn() disponível");
            echo checkStatus(function_exists('getUserId'), "getUserId() disponível");
            echo checkStatus(function_exists('requireLogin'), "requireLogin() disponível");
            echo checkStatus(function_exists('setFlashMessage'), "setFlashMessage() disponível");
            
            // Teste de classes
            echo "<h4>Classes:</h4>";
            echo checkStatus(class_exists('Database'), "Database class disponível");
            echo checkStatus(class_exists('AuthController'), "AuthController disponível");
            if (class_exists('User')) {
                echo checkStatus(true, "User model disponível");
            }
            if (class_exists('Event')) {
                echo checkStatus(true, "Event model disponível");
            }
            ?>
        </div>

        <div class="section">
            <h2>💾 4. Teste de Conexão com Banco</h2>
            <?php
            try {
                if (class_exists('Database')) {
                    $database = new Database();
                    $conn = $database->getConnection();
                    echo checkStatus(true, "Conexão estabelecida com sucesso");
                    
                    // Testar uma query simples
                    try {
                        $stmt = $conn->prepare("SELECT 1 as test");
                        $stmt->execute();
                        $result = $stmt->fetch();
                        echo checkStatus($result['test'] == 1, "Query de teste executada");
                    } catch (Exception $e) {
                        echo checkStatus(false, "Erro na query de teste: " . $e->getMessage());
                    }
                    
                    // Verificar tabelas
                    try {
                        $stmt = $conn->prepare("SHOW TABLES");
                        $stmt->execute();
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        echo checkStatus(count($tables) > 0, "Tabelas encontradas: " . count($tables));
                        if (count($tables) > 0) {
                            echo "<div class='info'>Tabelas: " . implode(', ', $tables) . "</div>";
                        }
                    } catch (Exception $e) {
                        echo checkStatus(false, "Erro ao listar tabelas: " . $e->getMessage());
                    }
                    
                } else {
                    echo checkStatus(false, "Classe Database não encontrada");
                }
            } catch (Exception $e) {
                echo checkStatus(false, "Erro na conexão: " . $e->getMessage());
            }
            ?>
        </div>

        <div class="section">
            <h2>🎯 5. Teste das APIs</h2>
            <div class="status-grid">
                <?php
                $apis = [
                    'api/favorites.php' => 'API de Favoritos',
                    'api/subscriptions.php' => 'API de Inscrições', 
                    'api/ratings.php' => 'API de Avaliações',
                    'api/analytics.php' => 'API de Analytics'
                ];
                
                foreach ($apis as $file => $name) {
                    echo "<div class='status-card'>";
                    echo "<h4>$name</h4>";
                    $exists = file_exists($file);
                    echo checkStatus($exists, "Arquivo existe: $file");
                    if ($exists) {
                        echo checkStatus(is_readable($file), "Arquivo é legível");
                        echo "<div class='info'>Tamanho: " . number_format(filesize($file)) . " bytes</div>";
                    }
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <div class="section">
            <h2>🔗 6. Links de Teste</h2>
            <a href="index.php" class="btn">🏠 Página Principal</a>
            <a href="views/auth/register.php" class="btn">📝 Registro</a>
            <a href="views/auth/login.php" class="btn">🔑 Login</a>
            <a href="railway_setup.php" class="btn">⚙️ Setup Railway</a>
            <a href="test_api.php" class="btn">🧪 Teste API</a>
        </div>

        <div class="section">
            <h2>📋 7. Relatório Final</h2>
            <?php
            $issues = [];
            
            // Verificações críticas
            if (!defined('SITE_URL')) $issues[] = "SITE_URL não definida";
            if (!class_exists('Database')) $issues[] = "Classe Database não encontrada";
            if (!function_exists('isLoggedIn')) $issues[] = "Funções de sessão não carregadas";
            if (!file_exists('config/config.php')) $issues[] = "config/config.php não encontrado";
            if (!file_exists('includes/session.php')) $issues[] = "includes/session.php não encontrado";
            
            if (empty($issues)) {
                echo "<div class='success'>🎉 <strong>Sistema funcionando corretamente!</strong></div>";
                echo "<div class='info'>Todos os componentes essenciais estão funcionando. O sistema está pronto para uso.</div>";
            } else {
                echo "<div class='error'>⚠️ <strong>Problemas encontrados:</strong></div>";
                foreach ($issues as $issue) {
                    echo "<div class='error'>• $issue</div>";
                }
                echo "<div class='warning'>Corrija os problemas acima para garantir o funcionamento completo do sistema.</div>";
            }
            ?>
        </div>

        <div class="section">
            <h2>🚀 8. Próximos Passos</h2>
            <div class="info">
                <strong>Se tudo estiver funcionando:</strong><br>
                1. Acesse a <a href="index.php">página principal</a><br>
                2. Teste o <a href="views/auth/register.php">registro de usuário</a><br>
                3. Teste o <a href="views/auth/login.php">login</a><br>
                4. Crie seu primeiro evento<br><br>
                
                <strong>Se houver problemas:</strong><br>
                1. Execute o <a href="railway_setup.php">setup do Railway</a><br>
                2. Verifique os logs do Railway<br>
                3. Certifique-se de que DATABASE_URL está configurada<br>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #ecf0f1; border-radius: 8px;">
            <h3>🔧 Conecta Eventos - Sistema de Diagnóstico</h3>
            <p>Desenvolvido por João Vitor da Silva | Railway Deployment</p>
            <small>Versão: 1.0 | Data: <?php echo date('Y-m-d H:i:s'); ?></small>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 30 segundos se houver erros
        const hasErrors = document.querySelectorAll('.error').length > 0;
        if (hasErrors) {
            console.log('🔧 Diagnóstico detectou erros. Auto-refresh em 30s...');
            // setTimeout(() => location.reload(), 30000);
        } else {
            console.log('✅ Sistema funcionando corretamente!');
        }
        
        // Log de informações
        console.log('🔧 Conecta Eventos - Diagnóstico');
        console.log('📊 Erros encontrados:', document.querySelectorAll('.error').length);
        console.log('✅ Verificações passou:', document.querySelectorAll('.success').length);
        console.log('⚠️ Avisos:', document.querySelectorAll('.warning').length);
    </script>
</body>
</html>