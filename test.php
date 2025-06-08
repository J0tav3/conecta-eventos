<?php
// ==========================================
// ARQUIVO: test.php
// Teste básico para diagnosticar o problema
// ==========================================

// Ativar todos os erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html><html><head><title>Teste de Diagnóstico</title></head><body>";
echo "<h1>🔍 Diagnóstico do Sistema</h1>";

// Teste 1: PHP básico
echo "<h2>1. PHP Funcionando</h2>";
echo "<p>✅ PHP versão: " . phpversion() . "</p>";
echo "<p>✅ Data/Hora: " . date('Y-m-d H:i:s') . "</p>";

// Teste 2: Informações do servidor
echo "<h2>2. Informações do Servidor</h2>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</p>";
echo "<p>Current Directory: " . getcwd() . "</p>";
echo "<p>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</p>";

// Teste 3: Estrutura de arquivos
echo "<h2>3. Estrutura de Arquivos</h2>";

$files_to_check = [
    'index.php',
    'config/config.php',
    'includes/session.php',
    'controllers/EventController.php',
    'views/auth/login.php',
    'public/css/style.css',
    'uploads/'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        if (is_dir($file)) {
            echo "<p>✅ Diretório: $file</p>";
        } else {
            echo "<p>✅ Arquivo: $file (" . filesize($file) . " bytes)</p>";
        }
    } else {
        echo "<p>❌ NÃO ENCONTRADO: $file</p>";
    }
}

// Teste 4: Listar arquivos do diretório atual
echo "<h2>4. Arquivos no Diretório Raiz</h2>";
$files = scandir('.');
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

// Teste 5: Verificar extensões PHP
echo "<h2>5. Extensões PHP Carregadas</h2>";
$extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'mbstring', 'curl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p>✅ $ext</p>";
    } else {
        echo "<p>❌ $ext (não carregada)</p>";
    }
}

// Teste 6: Verificar variáveis de ambiente
echo "<h2>6. Variáveis de Ambiente Importantes</h2>";
$env_vars = ['DATABASE_URL', 'PORT', 'RAILWAY_ENVIRONMENT'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value) {
        // Mascarar dados sensíveis
        if (strpos($var, 'URL') !== false || strpos($var, 'PASSWORD') !== false) {
            $value = substr($value, 0, 10) . '...';
        }
        echo "<p>✅ $var: $value</p>";
    } else {
        echo "<p>❌ $var: não definida</p>";
    }
}

// Teste 7: Tentar incluir config.php
echo "<h2>7. Teste de Inclusão de Arquivos</h2>";

if (file_exists('config/config.php')) {
    try {
        ob_start();
        include 'config/config.php';
        $output = ob_get_clean();
        echo "<p>✅ config.php incluído com sucesso</p>";
        if (!empty($output)) {
            echo "<p>⚠️ Output do config.php: " . htmlspecialchars($output) . "</p>";
        }
    } catch (Throwable $e) {
        echo "<p>❌ Erro ao incluir config.php: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Arquivo: " . $e->getFile() . "</p>";
        echo "<p>Linha: " . $e->getLine() . "</p>";
    }
} else {
    echo "<p>❌ config/config.php não encontrado</p>";
}

// Teste 8: Teste de conexão básica
echo "<h2>8. Teste de Banco de Dados</h2>";

// Verificar se existe DATABASE_URL
$database_url = getenv('DATABASE_URL');
if ($database_url) {
    echo "<p>✅ DATABASE_URL está definida</p>";
    
    // Tentar parsing da URL
    $url_parts = parse_url($database_url);
    if ($url_parts) {
        echo "<p>✅ URL parseada com sucesso</p>";
        echo "<p>Host: " . ($url_parts['host'] ?? 'N/A') . "</p>";
        echo "<p>Banco: " . (ltrim($url_parts['path'] ?? '', '/') ?: 'N/A') . "</p>";
        
        // Tentar conexão básica
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                $url_parts['host'],
                $url_parts['port'] ?? 3306,
                ltrim($url_parts['path'], '/')
            );
            
            $pdo = new PDO($dsn, $url_parts['user'], $url_parts['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            echo "<p>✅ Conexão com banco estabelecida</p>";
            
            // Teste básico de consulta
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "<p>✅ Query de teste executada: " . $result['test'] . "</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Erro na conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p>❌ Erro ao fazer parse da DATABASE_URL</p>";
    }
} else {
    echo "<p>❌ DATABASE_URL não está definida</p>";
}

// Teste 9: Verificar se há erros de sintaxe no index.php
echo "<h2>9. Verificação de Sintaxe do index.php</h2>";

if (file_exists('index.php')) {
    $php_code = file_get_contents('index.php');
    
    // Verificar sintaxe usando php -l (se disponível)
    $temp_file = tempnam(sys_get_temp_dir(), 'syntax_check');
    file_put_contents($temp_file, $php_code);
    
    $output = shell_exec("php -l $temp_file 2>&1");
    unlink($temp_file);
    
    if ($output) {
        if (strpos($output, 'No syntax errors') !== false) {
            echo "<p>✅ Sintaxe do index.php está correta</p>";
        } else {
            echo "<p>❌ Erro de sintaxe no index.php:</p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
    } else {
        echo "<p>⚠️ Não foi possível verificar sintaxe</p>";
    }
} else {
    echo "<p>❌ index.php não encontrado</p>";
}

echo "<h2>🏁 Fim do Diagnóstico</h2>";
echo "<p>Se você viu este resultado, o PHP básico está funcionando.</p>";
echo "<p>Procure por itens marcados com ❌ para identificar problemas.</p>";

echo "</body></html>";
?>