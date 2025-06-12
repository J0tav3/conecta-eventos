<?php
// ==========================================
// ARQUIVO DE TESTE PARA VERIFICAR CONFLITOS
// Local: test.php
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Teste de Conflitos - Conecta Eventos</h1>";

try {
    echo "<h3>✅ Teste 1: Carregando config.php</h3>";
    require_once 'config/config.php';
    echo "✅ config.php carregado com sucesso!<br>";
    echo "📊 SITE_URL definido: " . (defined('SITE_URL') ? SITE_URL : 'NÃO DEFINIDO') . "<br>";
    
    echo "<h3>✅ Teste 2: Carregando database.php</h3>";
    require_once 'config/database.php';
    echo "✅ database.php carregado com sucesso!<br>";
    echo "📊 Classe Database existe: " . (class_exists('Database') ? 'SIM' : 'NÃO') . "<br>";
    
    echo "<h3>✅ Teste 3: Carregando session.php</h3>";
    require_once 'includes/session.php';
    echo "✅ session.php carregado com sucesso!<br>";
    echo "📊 Função generateCSRFToken existe: " . (function_exists('generateCSRFToken') ? 'SIM' : 'NÃO') . "<br>";
    echo "📊 Função sanitizeInput existe: " . (function_exists('sanitizeInput') ? 'SIM' : 'NÃO') . "<br>";
    
    echo "<h3>✅ Teste 4: Testando Database</h3>";
    $database = Database::getInstance();
    echo "✅ Instância de Database criada!<br>";
    
    $connectionInfo = $database->getConnectionInfo();
    echo "📊 Status da conexão: " . $connectionInfo['status'] . "<br>";
    
    if ($database->isConnected()) {
        echo "✅ Conectado ao banco de dados!<br>";
        $testResult = $database->testConnection();
        echo "📊 Teste de conexão: " . $testResult['message'] . "<br>";
    } else {
        echo "⚠️ Não conectado ao banco (normal em desenvolvimento)<br>";
    }
    
    echo "<h3>✅ Teste 5: Testando AuthController</h3>";
    require_once 'controllers/AuthController.php';
    $authController = new AuthController();
    echo "✅ AuthController carregado com sucesso!<br>";
    
    $testConnectionResult = $authController->testConnection();
    echo "📊 Teste AuthController: " . $testConnectionResult['message'] . "<br>";
    
    echo "<h3>🎉 TODOS OS TESTES PASSARAM!</h3>";
    echo "<p style='color: green; font-weight: bold;'>✅ Sistema funcionando sem conflitos de funções/classes!</p>";
    
    echo "<h3>📋 Resumo dos Componentes:</h3>";
    echo "<ul>";
    echo "<li>✅ config.php - Configurações globais</li>";
    echo "<li>✅ database.php - Classe Database</li>";
    echo "<li>✅ session.php - Funções de sessão</li>";
    echo "<li>✅ AuthController.php - Controle de autenticação</li>";
    echo "</ul>";
    
    echo "<h3>🚀 Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li>✅ Resolver conflitos (FEITO)</li>";
    echo "<li>🔄 Testar index.php</li>";
    echo "<li>🔄 Testar páginas de login/registro</li>";
    echo "<li>🔄 Verificar funcionalidades completas</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ ERRO ENCONTRADO:</h3>";
    echo "<p style='color: red; font-weight: bold;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Server:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "</p>";
?>