<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Diagnóstico Conecta Eventos</h2>";
echo "<p>Testando arquivos um por um...</p>";

// Teste 1: config.php
echo "<h3>1. Testando config/config.php</h3>";
if (file_exists('config/config.php')) {
    echo "✅ Arquivo existe<br>";
    try {
        require_once 'config/config.php';
        echo "✅ config.php carregado com sucesso<br>";
        echo "SITE_NAME: " . SITE_NAME . "<br>";
        echo "SITE_URL: " . SITE_URL . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao carregar config.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config/config.php não encontrado<br>";
}

echo "<hr>";

// Teste 2: database.php
echo "<h3>2. Testando config/database.php</h3>";
if (file_exists('config/database.php')) {
    echo "✅ Arquivo existe<br>";
    try {
        require_once 'config/database.php';
        echo "✅ database.php carregado<br>";
        
        // Testar conexão
        $database = new Database();
        $conn = $database->getConnection();
        echo "✅ Conexão com banco funcionando<br>";
    } catch (Exception $e) {
        echo "❌ Erro database.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config/database.php não encontrado<br>";
}

echo "<hr>";

// Teste 3: session.php
echo "<h3>3. Testando includes/session.php</h3>";
if (file_exists('includes/session.php')) {
    echo "✅ Arquivo existe<br>";
    try {
        require_once 'includes/session.php';
        echo "✅ session.php carregado<br>";
    } catch (Exception $e) {
        echo "❌ Erro session.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ includes/session.php não encontrado<br>";
}

echo "<hr>";

// Teste 4: EventController.php
echo "<h3>4. Testando controllers/EventController.php</h3>";
if (file_exists('controllers/EventController.php')) {
    echo "✅ Arquivo existe<br>";
    try {
        require_once 'controllers/EventController.php';
        echo "✅ EventController.php carregado<br>";
        
        $eventController = new EventController();
        echo "✅ EventController instanciado<br>";
    } catch (Exception $e) {
        echo "❌ Erro EventController.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ controllers/EventController.php não encontrado<br>";
}

echo "<hr>";
echo "<h3>✅ Diagnóstico concluído!</h3>";
?>