<?php
// ========================================
// DIAGNÓSTICO COMPLETO - RAILWAY
// ========================================
// Salve como: diagnostic.php na raiz
// ========================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnóstico Conecta Eventos - Railway</h1>";
echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; margin: 10px 0;'>";

// 1. INFORMAÇÕES DO SISTEMA
echo "<h2>1. Informações do Sistema</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Não disponível') . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Path: " . __DIR__ . "<br>";
echo "Current User: " . get_current_user() . "<br>";
echo "Working Directory: " . getcwd() . "<br>";

// 2. VARIÁVEIS DE AMBIENTE
echo "<h2>2. Variáveis de Ambiente</h2>";
echo "DATABASE_URL existe: " . (isset($_ENV['DATABASE_URL']) ? '✅ SIM' : '❌ NÃO') . "<br>";
if (isset($_ENV['DATABASE_URL'])) {
    $dbUrl = parse_url($_ENV['DATABASE_URL']);
    echo "DB Host: " . $dbUrl['host'] . "<br>";
    echo "DB Name: " . ltrim($dbUrl['path'], '/') . "<br>";
    echo "DB User: " . $dbUrl['user'] . "<br>";
    echo "DB Port: " . ($dbUrl['port'] ?? 3306) . "<br>";
}

// 3. TESTE DE ARQUIVOS
echo "<h2>3. Verificação de Arquivos</h2>";
$arquivos_criticos = [
    'config/config.php',
    'config/database.php', 
    'includes/session.php',
    'controllers/EventController.php',
    'controllers/AuthController.php',
    'models/Event.php',
    'models/User.php'
];

foreach ($arquivos_criticos as $arquivo) {
    $caminho = __DIR__ . '/' . $arquivo;
    if (file_exists($caminho)) {
        echo "✅ $arquivo (". number_format(filesize($caminho)) ." bytes)<br>";
    } else {
        echo "❌ $arquivo (NÃO ENCONTRADO)<br>";
    }
}

// 4. TESTE DE INCLUDES
echo "<h2>4. Teste de Includes</h2>";
$includes_sucesso = [];
$includes_erro = [];

// Testar config.php
try {
    ob_start();
    require_once 'config/config.php';
    $output = ob_get_clean();
    if (empty($output)) {
        echo "✅ config/config.php carregado<br>";
        echo "SITE_NAME: " . (defined('SITE_NAME') ? SITE_NAME : 'NÃO DEFINIDO') . "<br>";
        echo "SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'NÃO DEFINIDO') . "<br>";
        $includes_sucesso[] = 'config.php';
    } else {
        echo "⚠️ config/config.php com output: " . htmlspecialchars($output) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ config/config.php ERRO: " . $e->getMessage() . "<br>";
    $includes_erro[] = 'config.php';
}

// Testar database.php
try {
    ob_start();
    require_once 'config/database.php';
    $output = ob_get_clean();
    if (empty($output)) {
        echo "✅ config/database.php carregado<br>";
        $includes_sucesso[] = 'database.php';
    } else {
        echo "⚠️ config/database.php com output: " . htmlspecialchars($output) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ config/database.php ERRO: " . $e->getMessage() . "<br>";
    $includes_erro[] = 'database.php';
}

// 5. TESTE DE CONEXÃO COM BANCO
echo "<h2>5. Teste de Conexão com Banco</h2>";
if (in_array('database.php', $includes_sucesso)) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        echo "✅ Conexão com banco estabelecida<br>";
        
        // Testar query simples
        $stmt = $conn->prepare("SELECT 1 as test");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✅ Query de teste executada: " . $result['test'] . "<br>";
        
        // Verificar tabelas
        $stmt = $conn->prepare("SHOW TABLES");
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "📊 Tabelas encontradas: " . count($tables) . "<br>";
        if (count($tables) > 0) {
            echo "Tabelas: " . implode(', ', $tables) . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Não foi possível testar conexão (database.php falhou)<br>";
}

// 6. TESTE DE SESSION
echo "<h2>6. Teste de Sessão</h2>";
try {
    ob_start();
    require_once 'includes/session.php';
    $output = ob_get_clean();
    if (empty($output)) {
        echo "✅ includes/session.php carregado<br>";
        echo "Session ID: " . session_id() . "<br>";
        echo "Função isLoggedIn() existe: " . (function_exists('isLoggedIn') ? '✅' : '❌') . "<br>";
        $includes_sucesso[] = 'session.php';
    } else {
        echo "⚠️ includes/session.php com output: " . htmlspecialchars($output) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ includes/session.php ERRO: " . $e->getMessage() . "<br>";
    $includes_erro[] = 'session.php';
}

// 7. TESTE DE CONTROLLERS
echo "<h2>7. Teste de Controllers</h2>";
if (in_array('session.php', $includes_sucesso)) {
    try {
        ob_start();
        require_once 'controllers/EventController.php';
        $output = ob_get_clean();
        if (empty($output)) {
            echo "✅ EventController carregado<br>";
            $eventController = new EventController();
            echo "✅ EventController instanciado<br>";
            $includes_sucesso[] = 'EventController';
        } else {
            echo "⚠️ EventController com output: " . htmlspecialchars($output) . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ EventController ERRO: " . $e->getMessage() . "<br>";
        $includes_erro[] = 'EventController';
    }
} else {
    echo "❌ Não foi possível testar EventController (session.php falhou)<br>";
}

// 8. TESTE DE MODELS
echo "<h2>8. Teste de Models</h2>";
try {
    ob_start();
    require_once 'models/Event.php';
    $output = ob_get_clean();
    if (empty($output)) {
        echo "✅ Model Event carregado<br>";
        $includes_sucesso[] = 'Event.php';
    } else {
        echo "⚠️ Model Event com output: " . htmlspecialchars($output) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Model Event ERRO: " . $e->getMessage() . "<br>";
    $includes_erro[] = 'Event.php';
}

// 9. PERMISSÕES DE ARQUIVO
echo "<h2>9. Permissões</h2>";
$diretorios_check = ['public/uploads', 'backups', 'logs'];
foreach ($diretorios_check as $dir) {
    if (file_exists($dir)) {
        echo "📁 $dir: " . (is_writable($dir) ? '✅ Gravável' : '❌ Não gravável') . "<br>";
    } else {
        echo "📁 $dir: ❌ Não existe<br>";
        if (mkdir($dir, 0755, true)) {
            echo "   ✅ Criado com sucesso<br>";
        } else {
            echo "   ❌ Erro ao criar<br>";
        }
    }
}

// 10. RESUMO
echo "<h2>10. Resumo</h2>";
echo "<strong>✅ Includes com sucesso:</strong> " . implode(', ', $includes_sucesso) . "<br>";
if (!empty($includes_erro)) {
    echo "<strong>❌ Includes com erro:</strong> " . implode(', ', $includes_erro) . "<br>";
}

echo "<br><strong>Próximos passos recomendados:</strong><br>";
if (empty($includes_erro)) {
    echo "🎉 Todos os components básicos funcionando! Pode usar o index.php completo.<br>";
} else {
    echo "🔧 Corrigir os erros listados acima antes de prosseguir.<br>";
}

echo "</div>";

// 11. TESTE PRÁTICO
echo "<h2>11. Teste Prático</h2>";
if (in_array('EventController', $includes_sucesso)) {
    try {
        echo "Testando busca de eventos...<br>";
        $eventos = $eventController->getPublicEvents(['limite' => 1]);
        echo "✅ Busca de eventos funcionando (" . count($eventos) . " eventos encontrados)<br>";
    } catch (Exception $e) {
        echo "❌ Erro na busca de eventos: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";
echo "<p><strong>Data/Hora do teste:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>