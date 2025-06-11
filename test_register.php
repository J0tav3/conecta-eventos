<?php
// ==========================================
// TESTE DE REGISTRO MELHORADO
// Local: test_register.php
// ==========================================

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>🧪 Teste de Registro Completo</h2>";

try {
    // 1. Verificar includes
    echo "<h3>1. Verificando arquivos necessários...</h3>";
    
    $files = [
        'config/config.php',
        'controllers/AuthController.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            echo "✅ $file encontrado<br>";
            require_once $file;
        } else {
            echo "❌ $file NÃO encontrado<br>";
        }
    }
    
    // 2. Testar conexão direta com banco
    echo "<h3>2. Testando conexão com banco...</h3>";
    
    $database_url = getenv('DATABASE_URL');
    if (!$database_url) {
        echo "❌ DATABASE_URL não encontrada<br>";
        exit;
    }
    
    $url_parts = parse_url($database_url);
    $host = $url_parts['host'];
    $port = $url_parts['port'] ?? 3306;
    $dbname = ltrim($url_parts['path'], '/');
    $username = $url_parts['user'];
    $password = $url_parts['pass'];
    
    echo "🔗 Conectando a: $host:$port/$dbname<br>";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Conexão direta com banco estabelecida<br>";
    
    // 3. Verificar tabela usuarios
    echo "<h3>3. Verificando estrutura da tabela usuarios...</h3>";
    
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 4. Contar usuários existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total = $stmt->fetch()['total'];
    echo "📊 Total de usuários na tabela: $total<br>";
    
    // 5. Testar AuthController
    echo "<h3>4. Testando AuthController...</h3>";
    
    if (!class_exists('AuthController')) {
        echo "❌ Classe AuthController não encontrada<br>";
        exit;
    }
    
    $auth = new AuthController();
    echo "✅ AuthController instanciado<br>";
    
    // 6. Teste de registro
    echo "<h3>5. Executando teste de registro...</h3>";
    
    $testEmail = "teste." . time() . "@example.com";
    $testData = [
        'nome' => 'Teste Usuario Completo',
        'email' => $testEmail,
        'senha' => 'teste123',
        'confirma_senha' => 'teste123',
        'tipo_usuario' => 'participante',
        'telefone' => '(11) 99999-9999',
        'cidade' => 'São Paulo',
        'estado' => 'SP'
    ];
    
    echo "📝 Dados de teste:<br>";
    echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "🚀 Executando registro...<br>";
    $result = $auth->register($testData);
    
    echo "📋 Resultado do AuthController:<br>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    
    // 7. Verificar se usuário foi realmente salvo
    echo "<h3>6. Verificando se usuário foi salvo no banco...</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$testEmail]);
    $savedUser = $stmt->fetch();
    
    if ($savedUser) {
        echo "✅ Usuário encontrado no banco!<br>";
        echo "<pre>" . json_encode($savedUser, JSON_PRETTY_PRINT) . "</pre>";
        
        // 8. Verificar nova contagem
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $newTotal = $stmt->fetch()['total'];
        echo "📊 Nova contagem de usuários: $newTotal (anterior: $total)<br>";
        
        if ($newTotal > $total) {
            echo "🎉 <strong>SUCESSO! Usuário foi salvo corretamente no banco!</strong><br>";
        } else {
            echo "⚠️ <strong>ATENÇÃO: Contagem não aumentou!</strong><br>";
        }
        
    } else {
        echo "❌ <strong>FALHA! Usuário NÃO foi encontrado no banco!</strong><br>";
    }
    
    // 9. Teste de validação
    echo "<h3>7. Testando validações...</h3>";
    
    $invalidTests = [
        ['nome' => '', 'email' => $testEmail, 'senha' => 'teste123', 'confirma_senha' => 'teste123', 'tipo_usuario' => 'participante'],
        ['nome' => 'Test', 'email' => 'email-invalido', 'senha' => 'teste123', 'confirma_senha' => 'teste123', 'tipo_usuario' => 'participante'],
        ['nome' => 'Test', 'email' => 'test@test.com', 'senha' => '123', 'confirma_senha' => '123', 'tipo_usuario' => 'participante'],
        ['nome' => 'Test', 'email' => 'test@test.com', 'senha' => 'teste123', 'confirma_senha' => 'teste456', 'tipo_usuario' => 'participante']
    ];
    
    foreach ($invalidTests as $i => $invalidData) {
        echo "🧪 Teste de validação " . ($i + 1) . ": ";
        $result = $auth->register($invalidData);
        if ($result['success']) {
            echo "❌ FALHA - deveria ter falhado<br>";
        } else {
            echo "✅ OK - falhou como esperado: " . $result['message'] . "<br>";
        }
    }
    
    echo "<h3>✅ Teste completo finalizado!</h3>";
    
} catch (Exception $e) {
    echo "<h3>❌ Erro durante o teste:</h3>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>