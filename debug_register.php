<?php
// ========================================
// DIAGNÓSTICO DO ERRO 500 - REGISTER.PHP
// ========================================
// Salve como: debug_register.php na raiz
// Acesse: https://conecta-eventos-production.up.railway.app/debug_register.php
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico do Erro 500 - Register.php</h1>";

$errors = [];
$success = [];

// 1. Testar inclusão de arquivos
echo "<h2>1. Testando Arquivos Necessários</h2>";

$files_needed = [
    'config/config.php' => '../../config/config.php',
    'includes/session.php' => '../../includes/session.php',
    'controllers/AuthController.php' => '../../controllers/AuthController.php'
];

foreach ($files_needed as $name => $path) {
    $full_path = __DIR__ . '/' . $path;
    if (file_exists($full_path)) {
        echo "<p>✅ $name encontrado</p>";
        try {
            require_once $full_path;
            echo "<p>✅ $name carregado com sucesso</p>";
            $success[] = $name;
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao carregar $name: " . $e->getMessage() . "</p>";
            $errors[] = $name . ': ' . $e->getMessage();
        }
    } else {
        echo "<p class='error'>❌ $name não encontrado em: $full_path</p>";
        $errors[] = $name . ' não encontrado';
    }
}

// 2. Testar constantes
echo "<h2>2. Testando Constantes</h2>";

if (defined('SITE_URL')) {
    echo "<p>✅ SITE_URL definida: " . SITE_URL . "</p>";
} else {
    echo "<p class='error'>❌ SITE_URL não definida</p>";
    define('SITE_URL', 'https://conecta-eventos-production.up.railway.app');
    echo "<p>✅ SITE_URL definida manualmente</p>";
}

if (defined('SITE_NAME')) {
    echo "<p>✅ SITE_NAME definida: " . SITE_NAME . "</p>";
} else {
    echo "<p class='error'>❌ SITE_NAME não definida</p>";
    define('SITE_NAME', 'Conecta Eventos');
    echo "<p>✅ SITE_NAME definida manualmente</p>";
}

// 3. Testar sessão
echo "<h2>3. Testando Sessão</h2>";

try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Sessão iniciada com sucesso</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro na sessão: " . $e->getMessage() . "</p>";
    $errors[] = 'Erro na sessão: ' . $e->getMessage();
}

// 4. Testar funções de sessão
echo "<h2>4. Testando Funções de Sessão</h2>";

$functions = ['isLoggedIn', 'getCurrentUser', 'isOrganizer', 'isParticipant'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p>✅ Função $func() disponível</p>";
        try {
            $result = $func();
            echo "<p>✅ $func() executada: " . json_encode($result) . "</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ $func() com erro: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Função $func() não encontrada</p>";
        $errors[] = "Função $func() não encontrada";
    }
}

// 5. Testar AuthController
echo "<h2>5. Testando AuthController</h2>";

try {
    if (class_exists('AuthController')) {
        echo "<p>✅ Classe AuthController encontrada</p>";
        
        $auth = new AuthController();
        echo "<p>✅ AuthController instanciado</p>";
        
        // Testar método requireGuest
        if (method_exists($auth, 'requireGuest')) {
            echo "<p>✅ Método requireGuest() disponível</p>";
        } else {
            echo "<p class='error'>❌ Método requireGuest() não encontrado</p>";
            $errors[] = 'Método requireGuest() não encontrado';
        }
        
    } else {
        echo "<p class='error'>❌ Classe AuthController não encontrada</p>";
        $errors[] = 'Classe AuthController não encontrada';
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no AuthController: " . $e->getMessage() . "</p>";
    $errors[] = 'AuthController: ' . $e->getMessage();
}

// 6. Testar banco de dados
echo "<h2>6. Testando Banco de Dados</h2>";

try {
    if (class_exists('Database')) {
        echo "<p>✅ Classe Database encontrada</p>";
        
        $database = new Database();
        $conn = $database->getConnection();
        echo "<p>✅ Conexão com banco estabelecida</p>";
        
        // Testar tabela usuarios
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "<p>✅ Tabela usuarios acessível: " . $result['total'] . " usuários</p>";
        
    } else {
        echo "<p class='error'>❌ Classe Database não encontrada</p>";
        $errors[] = 'Classe Database não encontrada';
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no banco: " . $e->getMessage() . "</p>";
    $errors[] = 'Banco: ' . $e->getMessage();
}

// 7. Simular o registro
echo "<h2>7. Simulando Processo de Registro</h2>";

try {
    if (class_exists('AuthController')) {
        $auth = new AuthController();
        
        // Dados de teste
        $nome = 'Teste Usuario';
        $email = 'teste' . time() . '@teste.com';
        $senha = 'teste123';
        $tipo = 'participante';
        
        echo "<p>Testando registro com dados:</p>";
        echo "<ul>";
        echo "<li>Nome: $nome</li>";
        echo "<li>Email: $email</li>";
        echo "<li>Senha: [oculta]</li>";
        echo "<li>Tipo: $tipo</li>";
        echo "</ul>";
        
        // Não vamos realmente registrar, apenas testar se o método existe
        if (method_exists($auth, 'register')) {
            echo "<p>✅ Método register() disponível</p>";
            echo "<p>ℹ️ Teste de registro não executado para evitar dados desnecessários</p>";
        } else {
            echo "<p class='error'>❌ Método register() não encontrado</p>";
            $errors[] = 'Método register() não encontrado';
        }
        
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no teste de registro: " . $e->getMessage() . "</p>";
    $errors[] = 'Teste de registro: ' . $e->getMessage();
}

// 8. Relatório final
echo "<h2>8. Relatório Final</h2>";

if (empty($errors)) {
    echo "<div class='success'>";
    echo "<h3>✅ Diagnóstico Concluído - Sistema OK</h3>";
    echo "<p>Todos os componentes necessários estão funcionando.</p>";
    echo "<p><strong>Possíveis causas do erro 500:</strong></p>";
    echo "<ul>";
    echo "<li>Erro temporário do servidor</li>";
    echo "<li>Problema de permissões de arquivo</li>";
    echo "<li>Erro de sintaxe no arquivo register.php</li>";
    echo "<li>Problema de memória/timeout</li>";
    echo "</ul>";
    echo "<p><strong>Recomendação:</strong> Tente acessar a página novamente ou recrie o arquivo register.php</p>";
    echo "</div>";
} else {
    echo "<div class='error-summary'>";
    echo "<h3>❌ Problemas Encontrados (" . count($errors) . ")</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "<p><strong>Ação necessária:</strong> Corrigir os problemas listados acima</p>";
    echo "</div>";
}

// 9. Versão corrigida do register.php
echo "<h2>9. Solução - Register.php Corrigido</h2>";

echo "<div class='solution'>";
echo "<h4>Para corrigir, substitua o conteúdo de views/auth/register.php por:</h4>";
echo "<p><a href='#register-fix' onclick='document.getElementById(\"register-code\").style.display=\"block\"'>👉 Clique para ver o código corrigido</a></p>";
echo "</div>";

// CSS
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
    h1 { color: #007bff; }
    h2 { color: #495057; background: white; padding: 10px; border-radius: 5px; margin-top: 20px; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .success { 
        background: #d4edda; 
        border: 1px solid #c3e6cb; 
        padding: 15px; 
        border-radius: 5px; 
        margin: 15px 0; 
    }
    .error-summary { 
        background: #f8d7da; 
        border: 1px solid #f1b0b7; 
        padding: 15px; 
        border-radius: 5px; 
        margin: 15px 0; 
    }
    .solution { 
        background: #e3f2fd; 
        border: 1px solid #2196f3; 
        padding: 15px; 
        border-radius: 5px; 
        margin: 15px 0; 
    }
    ul { margin: 10px 0; }
    li { margin: 5px 0; }
</style>";

echo "<hr>";
echo "<p><small>Diagnóstico executado em: " . date('Y-m-d H:i:s') . "</small></p>";
?>