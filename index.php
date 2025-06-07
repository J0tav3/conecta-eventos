<?php
// ========================================
// DIAGNÓSTICO CORRIGIDO - RAILWAY
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

// 2. VARIÁVEIS DE AMBIENTE MELHORADAS
echo "<h2>2. Variáveis de Ambiente</h2>";
echo "DATABASE_URL existe: " . (isset($_ENV['DATABASE_URL']) ? '✅ SIM' : '❌ NÃO') . "<br>";

if (isset($_ENV['DATABASE_URL'])) {
    echo "DATABASE_URL completa: " . $_ENV['DATABASE_URL'] . "<br>";
    
    $dbUrl = parse_url($_ENV['DATABASE_URL']);
    echo "Parse URL resultado:<br>";
    echo "- Host: " . ($dbUrl['host'] ?? 'NÃO DEFINIDO') . "<br>";
    echo "- Path: " . ($dbUrl['path'] ?? 'NÃO DEFINIDO') . "<br>";
    echo "- User: " . ($dbUrl['user'] ?? 'NÃO DEFINIDO') . "<br>";
    echo "- Port: " . ($dbUrl['port'] ?? 3306) . "<br>";
    echo "- Scheme: " . ($dbUrl['scheme'] ?? 'NÃO DEFINIDO') . "<br>";
    
    if (isset($dbUrl['path'])) {
        $dbName = ltrim($dbUrl['path'], '/');
        echo "- DB Name: " . $dbName . "<br>";
    }
} else {
    echo "❌ DATABASE_URL não encontrada<br>";
}

// Verificar todas as variáveis de ambiente
echo "<h3>Outras variáveis Railway:</h3>";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'RAILWAY') !== false || strpos($key, 'DATABASE') !== false) {
        echo "$key: " . substr($value, 0, 50) . "...<br>";
    }
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
        echo "✅ $arquivo (" . number_format(filesize($caminho)) . " bytes)<br>";
    } else {
        echo "❌ $arquivo (NÃO ENCONTRADO)<br>";
        
        // Verificar se o diretório existe
        $dir = dirname($caminho);
        if (!file_exists($dir)) {
            echo "   📁 Diretório " . dirname($arquivo) . " não existe<br>";
        }
    }
}

// 4. CRIAR DIRETÓRIOS SE NECESSÁRIO
echo "<h2>4. Criando Diretórios Necessários</h2>";
$diretorios = ['models', 'public/uploads', 'public/uploads/eventos', 'backups', 'logs'];

foreach ($diretorios as $dir) {
    $caminho = __DIR__ . '/' . $dir;
    if (!file_exists($caminho)) {
        if (mkdir($caminho, 0755, true)) {
            echo "✅ Diretório $dir criado<br>";
            file_put_contents($caminho . '/.gitkeep', '');
        } else {
            echo "❌ Erro ao criar $dir<br>";
        }
    } else {
        echo "✅ Diretório $dir já existe<br>";
    }
}

// 5. TESTE DE INCLUDES
echo "<h2>5. Teste de Includes</h2>";
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

// 6. TESTE DE CONEXÃO COM BANCO
echo "<h2>6. Teste de Conexão com Banco</h2>";
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
        if (strpos($_ENV['DATABASE_URL'], 'mysql') !== false) {
            $stmt = $conn->prepare("SHOW TABLES");
        } else {
            $stmt = $conn->prepare("SELECT name FROM sqlite_master WHERE type='table'");
        }
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "📊 Tabelas encontradas: " . count($tables) . "<br>";
        if (count($tables) > 0) {
            echo "Tabelas: " . implode(', ', $tables) . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
        echo "Stack trace: " . $e->getTraceAsString() . "<br>";
    }
} else {
    echo "❌ Não foi possível testar conexão (database.php falhou)<br>";
}

// 7. INSTRUÇÕES PARA CRIAR MODELS
if (!file_exists(__DIR__ . '/models/Event.php') || !file_exists(__DIR__ . '/models/User.php')) {
    echo "<h2>7. ⚠️ AÇÃO NECESSÁRIA: Criar Models</h2>";
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<strong>Os arquivos models/Event.php e models/User.php não foram encontrados!</strong><br>";
    echo "Você precisa criar estes arquivos com o conteúdo fornecido anteriormente.<br><br>";
    
    echo "<strong>Passos:</strong><br>";
    echo "1. Crie o arquivo models/Event.php<br>";
    echo "2. Crie o arquivo models/User.php<br>";
    echo "3. Execute este diagnóstico novamente<br>";
    echo "</div>";
}

// 8. RESUMO E PRÓXIMAS AÇÕES
echo "<h2>8. Resumo</h2>";
echo "<strong>✅ Includes com sucesso:</strong> " . implode(', ', $includes_sucesso) . "<br>";
if (!empty($includes_erro)) {
    echo "<strong>❌ Includes com erro:</strong> " . implode(', ', $includes_erro) . "<br>";
}

echo "<br><strong>Status geral:</strong><br>";
if (isset($_ENV['DATABASE_URL']) && in_array('database.php', $includes_sucesso)) {
    if (file_exists(__DIR__ . '/models/Event.php') && file_exists(__DIR__ . '/models/User.php')) {
        echo "🎉 Sistema quase pronto! Apenas teste a conexão com banco.<br>";
    } else {
        echo "🔧 Crie os arquivos models/Event.php e models/User.php<br>";
    }
} else {
    echo "🔧 Corrija os problemas de configuração listados acima.<br>";
}

echo "</div>";
echo "<hr>";
echo "<p><strong>Data/Hora do teste:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>