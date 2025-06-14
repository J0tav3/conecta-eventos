<?php
// ==========================================
// DEBUG DA PÁGINA DE EDIÇÃO
// Local: debug_edit.php (criar na raiz do projeto)
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico da Página de Edição</h1>";

// 1. Verificar arquivos necessários
echo "<h2>📁 Verificação de Arquivos</h2>";
$files_to_check = [
    'views/events/edit.php',
    'controllers/EventController.php',
    'config/database.php',
    'includes/session.php',
    'handlers/ImageUploadHandler.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $readable = $exists ? is_readable($file) : false;
    $size = $exists ? filesize($file) : 0;
    
    echo "<p>";
    echo $exists ? "✅" : "❌";
    echo " <strong>$file</strong> - ";
    echo $exists ? "Existe" : "NÃO EXISTE";
    if ($exists) {
        echo " (Tamanho: " . number_format($size) . " bytes)";
        echo $readable ? " - Legível" : " - NÃO LEGÍVEL";
    }
    echo "</p>";
}

// 2. Simular sessão para teste
echo "<h2>🔐 Simulação de Sessão</h2>";
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_type'] = 'organizador';
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Teste Debug';
echo "<p>✅ Sessão simulada criada</p>";

// 3. Testar inclusão de arquivos um por um
echo "<h2>📦 Teste de Inclusões</h2>";

try {
    echo "<p>Testando config/database.php...</p>";
    if (file_exists('config/database.php')) {
        ob_start();
        require_once 'config/database.php';
        $output = ob_get_clean();
        echo "✅ config/database.php incluído com sucesso<br>";
        if (!empty($output)) {
            echo "⚠️ Output detectado: " . htmlspecialchars(substr($output, 0, 100)) . "<br>";
        }
    } else {
        echo "❌ config/database.php não encontrado<br>";
    }
    
    echo "<p>Testando includes/session.php...</p>";
    if (file_exists('includes/session.php')) {
        ob_start();
        require_once 'includes/session.php';
        $output = ob_get_clean();
        echo "✅ includes/session.php incluído com sucesso<br>";
        if (!empty($output)) {
            echo "⚠️ Output detectado: " . htmlspecialchars(substr($output, 0, 100)) . "<br>";
        }
    } else {
        echo "❌ includes/session.php não encontrado<br>";
    }
    
    echo "<p>Testando controllers/EventController.php...</p>";
    if (file_exists('controllers/EventController.php')) {
        ob_start();
        require_once 'controllers/EventController.php';
        $output = ob_get_clean();
        echo "✅ controllers/EventController.php incluído com sucesso<br>";
        if (!empty($output)) {
            echo "⚠️ Output detectado: " . htmlspecialchars(substr($output, 0, 100)) . "<br>";
        }
        
        // Testar instanciação
        try {
            $eventController = new EventController();
            echo "✅ EventController instanciado com sucesso<br>";
        } catch (Exception $e) {
            echo "❌ Erro ao instanciar EventController: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    } else {
        echo "❌ controllers/EventController.php não encontrado<br>";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO durante inclusões: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 4. Testar acesso direto à página edit
echo "<h2>🌐 Teste de Acesso à Página</h2>";

$editPageUrl = "views/events/edit.php?id=1";
echo "<p>Tentando acessar: <strong>$editPageUrl</strong></p>";

if (file_exists('views/events/edit.php')) {
    echo "<p>✅ Arquivo edit.php existe</p>";
    
    // Verificar se há erros de sintaxe
    $syntax_check = shell_exec("php -l views/events/edit.php 2>&1");
    if (strpos($syntax_check, 'No syntax errors') !== false) {
        echo "<p>✅ Sintaxe PHP válida</p>";
    } else {
        echo "<p>❌ Erro de sintaxe: " . htmlspecialchars($syntax_check) . "</p>";
    }
    
    // Tentar incluir com output buffering
    echo "<p>Tentando incluir com buffer...</p>";
    ob_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    try {
        $_GET['id'] = 1; // Simular ID
        include 'views/events/edit.php';
        $page_output = ob_get_contents();
        ob_end_clean();
        
        if (empty($page_output)) {
            echo "<p>❌ Página retornou output vazio (página em branco)</p>";
        } else {
            $output_length = strlen($page_output);
            echo "<p>✅ Página gerou output ($output_length caracteres)</p>";
            
            // Verificar se é HTML válido
            if (strpos($page_output, '<!DOCTYPE') !== false) {
                echo "<p>✅ Output contém DOCTYPE HTML</p>";
            } else {
                echo "<p>⚠️ Output não parece ser HTML completo</p>";
                echo "<p>Primeiros 200 caracteres:</p>";
                echo "<pre>" . htmlspecialchars(substr($page_output, 0, 200)) . "</pre>";
            }
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p>❌ Erro ao incluir página: " . htmlspecialchars($e->getMessage()) . "</p>";
    } catch (Error $e) {
        ob_end_clean();
        echo "<p>❌ Erro fatal ao incluir página: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ Arquivo edit.php não encontrado</p>";
}

// 5. Verificar logs de erro
echo "<h2>📋 Logs de Erro</h2>";
$error_log = ini_get('error_log');
echo "<p>Local do log de erros: " . htmlspecialchars($error_log) . "</p>";

if ($error_log && file_exists($error_log)) {
    $recent_errors = shell_exec("tail -20 " . escapeshellarg($error_log));
    if ($recent_errors) {
        echo "<h3>Últimos 20 erros:</h3>";
        echo "<pre style='background: #f8f9fa; padding: 1rem; border-radius: 0.5rem; overflow-x: auto;'>";
        echo htmlspecialchars($recent_errors);
        echo "</pre>";
    } else {
        echo "<p>Não foi possível ler o log de erros</p>";
    }
} else {
    echo "<p>Log de erros não encontrado ou não configurado</p>";
}

// 6. Verificar configuração PHP
echo "<h2>⚙️ Configuração PHP</h2>";
$php_settings = [
    'display_errors' => ini_get('display_errors'),
    'log_errors' => ini_get('log_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Configuração</th><th>Valor</th></tr>";
foreach ($php_settings as $setting => $value) {
    echo "<tr><td>$setting</td><td>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";

// 7. Verificar banco de dados
echo "<h2>🗄️ Teste de Banco de Dados</h2>";
try {
    if (class_exists('Database')) {
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        if ($conn) {
            echo "<p>✅ Conexão com banco estabelecida</p>";
            
            // Testar query simples
            $stmt = $conn->query("SELECT COUNT(*) as total FROM eventos LIMIT 1");
            if ($stmt) {
                $result = $stmt->fetch();
                echo "<p>✅ Query de teste executada com sucesso</p>";
                echo "<p>Total de eventos: " . ($result['total'] ?? 'N/A') . "</p>";
            } else {
                echo "<p>❌ Erro ao executar query de teste</p>";
            }
        } else {
            echo "<p>❌ Falha ao conectar com banco</p>";
        }
    } else {
        echo "<p>❌ Classe Database não encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro no teste de banco: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 8. Recomendações
echo "<h2>💡 Recomendações</h2>";
echo "<div style='background: #e7f3ff; padding: 1rem; border-radius: 0.5rem; border-left: 4px solid #007bff;'>";
echo "<h3>Para corrigir a página em branco:</h3>";
echo "<ol>";
echo "<li><strong>Substitua o arquivo edit.php</strong> pela versão com debug que criei</li>";
echo "<li><strong>Verifique os logs de erro</strong> para identificar problemas específicos</li>";
echo "<li><strong>Teste acesso com:</strong> <code>https://conecta-eventos-production.up.railway.app/views/events/edit.php?id=1</code></li>";
echo "<li><strong>Se ainda houver problemas,</strong> use a versão simplificada do edit.php</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🔗 Links de Teste</h2>";
$base_url = "https://conecta-eventos-production.up.railway.app";
echo "<ul>";
echo "<li><a href='$base_url/views/events/edit.php?id=1' target='_blank'>Testar Edit Direto</a></li>";
echo "<li><a href='$base_url/views/events/list.php' target='_blank'>Lista de Eventos</a></li>";
echo "<li><a href='$base_url/views/dashboard/organizer.php' target='_blank'>Dashboard</a></li>";
echo "<li><a href='$base_url/test_upload.php' target='_blank'>Teste de Upload</a></li>";
echo "</ul>";

// 9. Criar versão mínima de teste
echo "<h2>🛠️ Criar Versão de Teste</h2>";
$test_edit_content = '<?php
// VERSÃO DE TESTE MÍNIMA
session_start();
if (!isset($_SESSION["logged_in"])) {
    $_SESSION["logged_in"] = true;
    $_SESSION["user_type"] = "organizador";
    $_SESSION["user_id"] = 1;
    $_SESSION["user_name"] = "Teste";
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Teste Edit</title></head><body>";
echo "<h1>Teste da Página de Edição</h1>";
echo "<p>Se você está vendo isso, o PHP está funcionando!</p>";
echo "<p>Event ID: " . ($_GET["id"] ?? "não fornecido") . "</p>";
echo "<p>Usuário: " . $_SESSION["user_name"] . "</p>";
echo "<p>Timestamp: " . date("Y-m-d H:i:s") . "</p>";
echo "<p><a href=\"list.php\">Voltar para Lista</a></p>";
echo "</body></html>";
?>';

if (file_put_contents('views/events/edit_test.php', $test_edit_content)) {
    echo "<p>✅ Arquivo de teste criado: <strong>views/events/edit_test.php</strong></p>";
    echo "<p><a href='$base_url/views/events/edit_test.php?id=1' target='_blank'>🚀 Testar Versão Mínima</a></p>";
} else {
    echo "<p>❌ Erro ao criar arquivo de teste</p>";
}

echo "<hr>";
echo "<p><em>Diagnóstico concluído em " . date('Y-m-d H:i:s') . "</em></p>";
?>