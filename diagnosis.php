<?php
// ==========================================
// DIAGNÓSTICO COMPLETO DO SISTEMA
// Local: diagnosis.php
// ==========================================

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Diagnóstico do Sistema - Conecta Eventos</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; }";
echo ".error { color: #dc3545; }";
echo ".warning { color: #ffc107; }";
echo ".info { color: #17a2b8; }";
echo "table { width: 100%; border-collapse: collapse; margin: 10px 0; }";
echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }";
echo "th { background-color: #f8f9fa; }";
echo ".status-ok { background: #d4edda; color: #155724; }";
echo ".status-error { background: #f8d7da; color: #721c24; }";
echo ".status-warning { background: #fff3cd; color: #856404; }";
echo ".btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; color: white; }";
echo ".btn-primary { background: #007bff; }";
echo ".btn-success { background: #28a745; }";
echo ".btn-warning { background: #ffc107; color: #212529; }";
echo ".btn-danger { background: #dc3545; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔍 Diagnóstico Completo do Sistema</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";

$diagnostics = [];
$overallStatus = 'success';

try {
    // 1. Verificar PHP e extensões
    echo "<h2>1. 🐘 Ambiente PHP</h2>";
    
    $phpDiagnostics = [
        'PHP Version' => PHP_VERSION,
        'PDO Extension' => extension_loaded('pdo') ? '✅ Carregado' : '❌ Não carregado',
        'PDO MySQL' => extension_loaded('pdo_mysql') ? '✅ Carregado' : '❌ Não carregado',
        'OpenSSL' => extension_loaded('openssl') ? '✅ Carregado' : '❌ Não carregado',
        'JSON' => extension_loaded('json') ? '✅ Carregado' : '❌ Não carregado',
        'Session Support' => session_status() !== PHP_SESSION_DISABLED ? '✅ Disponível' : '❌ Desabilitado'
    ];
    
    echo "<table>";
    foreach ($phpDiagnostics as $item => $status) {
        $class = strpos($status, '✅') !== false ? 'status-ok' : 'status-error';
        echo "<tr class='$class'><td>$item</td><td>$status</td></tr>";
        if (strpos($status, '❌') !== false) $overallStatus = 'error';
    }
    echo "</table>";
    
    // 2. Verificar variáveis de ambiente
    echo "<h2>2. 🌍 Variáveis de Ambiente</h2>";
    
    $envVars = [
        'DATABASE_URL' => getenv('DATABASE_URL') ? '✅ Configurada' : '❌ Não encontrada',
        'PORT' => getenv('PORT') ? '✅ Porta: ' . getenv('PORT') : '⚠️ Usando padrão',
        'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT') ? '✅ ' . getenv('RAILWAY_ENVIRONMENT') : '⚠️ Não detectado'
    ];
    
    echo "<table>";
    foreach ($envVars as $var => $status) {
        $class = strpos($status, '✅') !== false ? 'status-ok' : 
                (strpos($status, '⚠️') !== false ? 'status-warning' : 'status-error');
        echo "<tr class='$class'><td>$var</td><td>$status</td></tr>";
        if (strpos($status, '❌') !== false) $overallStatus = 'error';
    }
    echo "</table>";
    
    // 3. Verificar arquivos do sistema
    echo "<h2>3. 📁 Arquivos do Sistema</h2>";
    
    $files = [
        'config/config.php' => 'Configuração principal',
        'includes/session.php' => 'Funções de sessão',
        'controllers/AuthController.php' => 'Controller de autenticação',
        'controllers/EventController.php' => 'Controller de eventos',
        'views/auth/login.php' => 'Página de login',
        'views/auth/register.php' => 'Página de registro',
        'index.php' => 'Página inicial'
    ];
    
    echo "<table>";
    echo "<tr><th>Arquivo</th><th>Descrição</th><th>Status</th><th>Tamanho</th></tr>";
    foreach ($files as $file => $desc) {
        $exists = file_exists($file);
        $status = $exists ? '✅ Existe' : '❌ Não encontrado';
        $size = $exists ? number_format(filesize($file)) . ' bytes' : '-';
        $class = $exists ? 'status-ok' : 'status-error';
        echo "<tr class='$class'><td>$file</td><td>$desc</td><td>$status</td><td>$size</td></tr>";
        if (!$exists) $overallStatus = 'error';
    }
    echo "</table>";
    
    // 4. Teste de conexão com banco
    echo "<h2>4. 🗄️ Banco de Dados</h2>";
    
    $database_url = getenv('DATABASE_URL');
    if ($database_url) {
        try {
            $url_parts = parse_url($database_url);
            $host = $url_parts['host'];
            $port = $url_parts['port'] ?? 3306;
            $dbname = ltrim($url_parts['path'], '/');
            $username = $url_parts['user'];
            $password = $url_parts['pass'];
            
            echo "<p><strong>Servidor:</strong> $host:$port</p>";
            echo "<p><strong>Database:</strong> $dbname</p>";
            echo "<p><strong>Usuário:</strong> $username</p>";
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            echo "<p class='success'>✅ Conexão estabelecida com sucesso</p>";
            
            // Verificar tabelas
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredTables = ['usuarios', 'categorias', 'eventos', 'inscricoes', 'favoritos', 'notificacoes'];
            
            echo "<h3>Tabelas no Banco:</h3>";
            echo "<table>";
            echo "<tr><th>Tabela</th><th>Status</th><th>Registros</th></tr>";
            
            foreach ($requiredTables as $table) {
                if (in_array($table, $tables)) {
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
                        $count = $stmt->fetch()['total'];
                        echo "<tr class='status-ok'><td>$table</td><td>✅ Existe</td><td>$count</td></tr>";
                    } catch (Exception $e) {
                        echo "<tr class='status-warning'><td>$table</td><td>⚠️ Erro ao contar</td><td>-</td></tr>";
                    }
                } else {
                    echo "<tr class='status-error'><td>$table</td><td>❌ Não existe</td><td>-</td></tr>";
                    $overallStatus = 'error';
                }
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro de conexão: " . $e->getMessage() . "</p>";
            $overallStatus = 'error';
        }
    } else {
        echo "<p class='error'>❌ DATABASE_URL não configurada</p>";
        $overallStatus = 'error';
    }
    
    // 5. Teste de classes
    echo "<h2>5. 🔧 Classes e Controllers</h2>";
    
    $classes = [
        'Database' => 'Classe de conexão com banco',
        'AuthController' => 'Controller de autenticação',
        'EventController' => 'Controller de eventos'
    ];
    
    echo "<table>";
    echo "<tr><th>Classe</th><th>Descrição</th><th>Status</th></tr>";
    
    foreach ($classes as $className => $desc) {
        try {
            if ($className === 'Database') {
                require_once 'config/config.php';
            } elseif ($className === 'AuthController') {
                require_once 'controllers/AuthController.php';
            } elseif ($className === 'EventController') {
                require_once 'controllers/EventController.php';
            }
            
            if (class_exists($className)) {
                echo "<tr class='status-ok'><td>$className</td><td>$desc</td><td>✅ Carregada</td></tr>";
            } else {
                echo "<tr class='status-error'><td>$className</td><td>$desc</td><td>❌ Não encontrada</td></tr>";
                $overallStatus = 'error';
            }
        } catch (Exception $e) {
            echo "<tr class='status-error'><td>$className</td><td>$desc</td><td>❌ Erro: " . $e->getMessage() . "</td></tr>";
            $overallStatus = 'error';
        }
    }
    echo "</table>";
    
    // 6. Teste de funções de sessão
    echo "<h2>6. 🔐 Sistema de Sessão</h2>";
    
    $sessionFunctions = ['isLoggedIn', 'getUserId', 'getUserName', 'isOrganizer', 'isParticipant'];
    
    echo "<table>";
    echo "<tr><th>Função</th><th>Status</th></tr>";
    
    try {
        if (file_exists('includes/session.php')) {
            require_once 'includes/session.php';
        }
        
        foreach ($sessionFunctions as $func) {
            $exists = function_exists($func) ? '✅ Existe' : '❌ Não encontrada';
            $class = function_exists($func) ? 'status-ok' : 'status-error';
            echo "<tr class='$class'><td>$func()</td><td>$exists</td></tr>";
            if (!function_exists($func)) $overallStatus = 'error';
        }
    } catch (Exception $e) {
        echo "<tr class='status-error'><td colspan='2'>❌ Erro ao carregar session.php: " . $e->getMessage() . "</td></tr>";
        $overallStatus = 'error';
    }
    echo "</table>";
    
    // 7. Resumo final
    echo "<h2>7. 📊 Resumo do Diagnóstico</h2>";
    
    if ($overallStatus === 'success') {
        echo "<div class='status-ok' style='padding: 20px; border-radius: 5px; text-align: center;'>";
        echo "<h3>🎉 Sistema OK!</h3>";
        echo "<p>Todos os componentes estão funcionando corretamente.</p>";
        echo "</div>";
    } else {
        echo "<div class='status-error' style='padding: 20px; border-radius: 5px; text-align: center;'>";
        echo "<h3>⚠️ Problemas Detectados</h3>";
        echo "<p>Alguns componentes precisam de atenção. Verifique os itens marcados em vermelho acima.</p>";
        echo "</div>";
    }
    
    // 8. Ações disponíveis
    echo "<h2>8. 🛠️ Ações Disponíveis</h2>";
    
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='fix_database.php' class='btn btn-warning'>🔧 Corrigir Banco de Dados</a>";
    echo "<a href='test_register.php' class='btn btn-primary'>🧪 Testar Registro</a>";
    echo "<a href='index.php' class='btn btn-success'>🏠 Ir para o Site</a>";
    
    if ($overallStatus === 'success') {
        echo "<a href='views/auth/register.php' class='btn btn-primary'>📝 Página de Cadastro</a>";
        echo "<a href='views/auth/login.php' class='btn btn-primary'>🔐 Página de Login</a>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='status-error' style='padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ Erro Fatal no Diagnóstico</h3>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<pre style='background: #f8f8f8; padding: 10px; overflow: auto;'>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>