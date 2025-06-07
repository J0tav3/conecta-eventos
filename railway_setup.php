<?php
// ========================================
// SCRIPT DE CONFIGURAÇÃO PARA RAILWAY
// ========================================
// Execute este arquivo uma vez para configurar tudo
// ========================================

echo "<h1>🚀 Configurando Conecta Eventos no Railway</h1>";

// 1. Verificar e corrigir DATABASE_URL
echo "<h2>1. Verificando DATABASE_URL</h2>";

if (isset($_ENV['DATABASE_URL'])) {
    $dbUrl = $_ENV['DATABASE_URL'];
    echo "<p>✅ DATABASE_URL encontrada</p>";
    
    // Fazer parse da URL
    $parsed = parse_url($dbUrl);
    
    if ($parsed === false) {
        echo "<p class='error'>❌ Erro ao fazer parse da DATABASE_URL</p>";
    } else {
        echo "<p>✅ Parse da URL realizado com sucesso</p>";
        echo "<ul>";
        echo "<li><strong>Host:</strong> " . ($parsed['host'] ?? 'NÃO DEFINIDO') . "</li>";
        echo "<li><strong>Database:</strong> " . (isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'NÃO DEFINIDO') . "</li>";
        echo "<li><strong>User:</strong> " . ($parsed['user'] ?? 'NÃO DEFINIDO') . "</li>";
        echo "<li><strong>Port:</strong> " . ($parsed['port'] ?? 3306) . "</li>";
        echo "</ul>";
    }
} else {
    echo "<p class='error'>❌ DATABASE_URL não encontrada</p>";
}

// 2. Testar conexão com banco
echo "<h2>2. Testando Conexão com Banco</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<p>✅ Conexão estabelecida com sucesso</p>";
    
    // Verificar tabelas
    $stmt = $conn->prepare("SELECT name FROM sqlite_master WHERE type='table' UNION ALL SELECT TABLE_NAME as name FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE()");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Tabelas encontradas:</strong> " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro na conexão: " . $e->getMessage() . "</p>";
}

// 3. Verificar models
echo "<h2>3. Verificando Models</h2>";

$modelsToCheck = [
    'models/Event.php' => 'Event',
    'models/User.php' => 'User'
];

foreach ($modelsToCheck as $file => $class) {
    if (file_exists($file)) {
        echo "<p>✅ $file existe</p>";
        
        try {
            require_once $file;
            if (class_exists($class)) {
                echo "<p>✅ Classe $class carregada</p>";
                
                // Testar instância
                $instance = new $class();
                echo "<p>✅ Instância de $class criada</p>";
            } else {
                echo "<p class='error'>❌ Classe $class não encontrada</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao carregar $class: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ $file não encontrado</p>";
    }
}

// 4. Verificar controllers
echo "<h2>4. Verificando Controllers</h2>";

$controllersToCheck = [
    'controllers/EventController.php' => 'EventController',
    'controllers/AuthController.php' => 'AuthController'
];

foreach ($controllersToCheck as $file => $class) {
    if (file_exists($file)) {
        echo "<p>✅ $file existe</p>";
        
        try {
            require_once $file;
            if (class_exists($class)) {
                echo "<p>✅ Classe $class carregada</p>";
            } else {
                echo "<p class='error'>❌ Classe $class não encontrada</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao carregar $class: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ $file não encontrado</p>";
    }
}

// 5. Verificar sistema de sessão
echo "<h2>5. Verificando Sistema de Sessão</h2>";

try {
    require_once 'includes/session.php';
    echo "<p>✅ Sistema de sessão carregado</p>";
    
    // Testar funções
    $functions = ['isLoggedIn', 'getUserId', 'getUserName', 'isOrganizer', 'isParticipant'];
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "<p>✅ Função $func() disponível</p>";
        } else {
            echo "<p class='error'>❌ Função $func() não encontrada</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no sistema de sessão: " . $e->getMessage() . "</p>";
}

// 6. Criar usuário admin se não existir
echo "<h2>6. Verificando Usuário Administrador</h2>";

try {
    require_once 'models/User.php';
    $userModel = new User();
    
    // Verificar se admin existe
    if ($userModel->emailExists('admin@conectaeventos.com')) {
        echo "<p>✅ Usuário administrador já existe</p>";
    } else {
        echo "<p>⚠️ Criando usuário administrador...</p>";
        
        $result = $userModel->create(
            'Administrador',
            'admin@conectaeventos.com',
            'admin123',
            'organizador'
        );
        
        if ($result['success']) {
            echo "<p>✅ Usuário administrador criado com sucesso</p>";
        } else {
            echo "<p class='error'>❌ Erro ao criar admin: " . $result['message'] . "</p>";
        }
    }
    
    echo "<div class='login-info'>";
    echo "<h4>📋 Informações de Login</h4>";
    echo "<p><strong>E-mail:</strong> admin@conectaeventos.com</p>";
    echo "<p><strong>Senha:</strong> admin123</p>";
    echo "<p><strong>Tipo:</strong> Organizador</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro ao verificar usuário admin: " . $e->getMessage() . "</p>";
}

// 7. Teste completo do sistema
echo "<h2>7. Teste Completo do Sistema</h2>";

try {
    require_once 'controllers/EventController.php';
    $eventController = new EventController();
    
    echo "<p>✅ EventController instanciado</p>";
    
    // Testar busca de eventos
    $eventos = $eventController->getPublicEvents(['limite' => 5]);
    echo "<p>✅ Busca de eventos funcionando (" . count($eventos) . " encontrados)</p>";
    
    // Testar busca de categorias
    $categorias = $eventController->getCategories();
    echo "<p>✅ Busca de categorias funcionando (" . count($categorias) . " encontradas)</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no teste do sistema: " . $e->getMessage() . "</p>";
}

// 8. Verificar arquivos críticos
echo "<h2>8. Verificando Arquivos Críticos</h2>";

$criticalFiles = [
    'index.php' => 'Página principal',
    'logout.php' => 'Script de logout',
    'views/auth/login.php' => 'Página de login',
    'views/auth/register.php' => 'Página de registro',
    'views/dashboard/organizer.php' => 'Dashboard do organizador',
    'views/dashboard/participant.php' => 'Dashboard do participante',
    'api/favorites.php' => 'API de favoritos',
    'api/subscriptions.php' => 'API de inscrições'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p>✅ $description ($file)</p>";
    } else {
        echo "<p class='error'>❌ $description não encontrado ($file)</p>";
    }
}

// 9. Relatório final
echo "<h2>9. Relatório Final</h2>";

$allGood = true;

// Verificações essenciais
$checks = [
    'DATABASE_URL definida' => isset($_ENV['DATABASE_URL']),
    'Conexão com banco' => isset($conn) && $conn !== null,
    'Models criados' => file_exists('models/Event.php') && file_exists('models/User.php'),
    'Controllers funcionando' => class_exists('EventController'),
    'Sistema de sessão' => function_exists('isLoggedIn'),
    'Página principal' => file_exists('index.php')
];

echo "<div class='final-report'>";
foreach ($checks as $check => $status) {
    if ($status) {
        echo "<p>✅ $check</p>";
    } else {
        echo "<p class='error'>❌ $check</p>";
        $allGood = false;
    }
}
echo "</div>";

if ($allGood) {
    echo "<div class='success-message'>";
    echo "<h3>🎉 Sistema Configurado com Sucesso!</h3>";
    echo "<p>O Conecta Eventos está pronto para uso. Você pode:</p>";
    echo "<ul>";
    echo "<li>✅ Acessar a página principal</li>";
    echo "<li>✅ Fazer login com admin@conectaeventos.com / admin123</li>";
    echo "<li>✅ Criar novos eventos</li>";
    echo "<li>✅ Gerenciar inscrições</li>";
    echo "</ul>";
    echo "<a href='index.php' class='btn btn-primary'>🚀 Acessar Sistema</a>";
    echo "</div>";
} else {
    echo "<div class='error-message'>";
    echo "<h3>⚠️ Configuração Incompleta</h3>";
    echo "<p>Alguns problemas foram encontrados. Verifique os itens marcados em vermelho acima.</p>";
    echo "</div>";
}

// CSS para melhor apresentação
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    h2 { color: #495057; margin-top: 30px; }
    h3 { color: #28a745; }
    .error { color: #dc3545; font-weight: bold; }
    .success-message { 
        background: #d4edda; 
        border: 1px solid #c3e6cb; 
        padding: 20px; 
        border-radius: 5px; 
        margin: 20px 0; 
    }
    .error-message { 
        background: #f8d7da; 
        border: 1px solid #f1b0b7; 
        padding: 20px; 
        border-radius: 5px; 
        margin: 20px 0; 
    }
    .login-info { 
        background: #e3f2fd; 
        border: 1px solid #2196f3; 
        padding: 15px; 
        border-radius: 5px; 
        margin: 15px 0; 
    }
    .final-report { 
        background: #f8f9fa; 
        padding: 15px; 
        border-radius: 5px; 
        margin: 15px 0; 
    }
    .btn { 
        display: inline-block; 
        padding: 10px 20px; 
        background: #007bff; 
        color: white; 
        text-decoration: none; 
        border-radius: 5px; 
        margin: 10px 0; 
    }
    .btn:hover { background: #0056b3; }
    ul { margin: 10px 0; }
    li { margin: 5px 0; }
</style>";

// Auto-redirect se tudo estiver OK
if ($allGood) {
    echo "<script>
        console.log('Sistema configurado com sucesso!');
        setTimeout(function() {
            document.getElementById('auto-redirect').style.display = 'block';
        }, 5000);
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 10000);
    </script>";
    
    echo "<div id='auto-redirect' style='display:none;'>
        <p><strong>Redirecionando automaticamente em 5 segundos...</strong></p>
        <p>Ou clique no botão acima para ir agora.</p>
    </div>";
}

echo "<hr>";
echo "<p><small><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</small></p>";
echo "<p><small><strong>Versão:</strong> Conecta Eventos v1.0 - Railway Setup</small></p>";
?>