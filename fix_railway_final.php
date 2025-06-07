<?php
// ========================================
// CORREÇÃO FINAL PARA RAILWAY - ADMIN FIX
// ========================================
// Execute este arquivo para corrigir o problema do admin
// ========================================

echo "<h1>🔧 Correção Final Railway - Admin Fix</h1>";

// 1. Verificar conexão
echo "<h2>1. Verificando Conexão</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<p>✅ Conexão estabelecida</p>";
    
    // Verificar tipo de banco
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "<p><strong>Driver:</strong> $driver</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro na conexão: " . $e->getMessage() . "</p>";
    exit();
}

// 2. Verificar se admin existe
echo "<h2>2. Verificando Status do Admin</h2>";

try {
    $stmt = $conn->prepare("SELECT id_usuario, nome, email, tipo FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@conectaeventos.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p>✅ Admin já existe no banco!</p>";
        echo "<p><strong>ID:</strong> " . $admin['id_usuario'] . "</p>";
        echo "<p><strong>Nome:</strong> " . $admin['nome'] . "</p>";
        echo "<p><strong>Email:</strong> " . $admin['email'] . "</p>";
        echo "<p><strong>Tipo:</strong> " . $admin['tipo'] . "</p>";
        
        // Testar login
        echo "<h3>Testando Login do Admin</h3>";
        require_once 'models/User.php';
        $userModel = new User();
        
        $loginTest = $userModel->authenticate('admin@conectaeventos.com', 'admin123');
        
        if ($loginTest['success']) {
            echo "<p>✅ Login funcionando perfeitamente!</p>";
        } else {
            echo "<p class='warning'>⚠️ Login não funcionou: " . $loginTest['message'] . "</p>";
            
            // Resetar senha do admin
            echo "<h4>Resetando senha do admin...</h4>";
            $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
            $result = $stmt->execute([$newPassword, 'admin@conectaeventos.com']);
            
            if ($result) {
                echo "<p>✅ Senha do admin resetada!</p>";
                
                // Testar novamente
                $loginTest2 = $userModel->authenticate('admin@conectaeventos.com', 'admin123');
                if ($loginTest2['success']) {
                    echo "<p>✅ Login funcionando após reset!</p>";
                } else {
                    echo "<p class='error'>❌ Login ainda não funciona</p>";
                }
            }
        }
        
    } else {
        echo "<p>⚠️ Admin não existe, criando...</p>";
        
        // Criar admin corretamente
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute(['Administrador', 'admin@conectaeventos.com', $adminPassword, 'organizador']);
        
        if ($result) {
            echo "<p>✅ Admin criado com sucesso!</p>";
        } else {
            echo "<p class='error'>❌ Erro ao criar admin</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

// 3. Verificar eventos de exemplo
echo "<h2>3. Verificando Eventos de Exemplo</h2>";

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM eventos");
    $stmt->execute();
    $totalEventos = $stmt->fetch()['total'];
    
    echo "<p><strong>Total de eventos:</strong> $totalEventos</p>";
    
    if ($totalEventos == 0) {
        echo "<p>⚠️ Criando evento de exemplo...</p>";
        
        $eventoExemplo = "
        INSERT INTO eventos (
            id_organizador, id_categoria, titulo, descricao,
            data_inicio, data_fim, horario_inicio, horario_fim,
            local_nome, local_endereco, local_cidade, local_estado,
            preco, evento_gratuito, status, destaque
        ) VALUES (
            1, 1, 'Workshop de Desenvolvimento Web',
            'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área. Workshop prático e interativo para iniciantes e intermediários.',
            ?, ?, '09:00', '17:00',
            'Centro de Tecnologia', 'Rua da Inovação, 123',
            'São Paulo', 'SP',
            0.00, 1, 'publicado', 1
        )";
        
        $dataEvento = date('Y-m-d', strtotime('+1 week'));
        $stmt = $conn->prepare($eventoExemplo);
        $result = $stmt->execute([$dataEvento, $dataEvento]);
        
        if ($result) {
            echo "<p>✅ Evento de exemplo criado!</p>";
        } else {
            echo "<p class='warning'>⚠️ Erro ao criar evento de exemplo</p>";
        }
    } else {
        echo "<p>✅ Já existem eventos no sistema</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro nos eventos: " . $e->getMessage() . "</p>";
}

// 4. Testar sistema completo
echo "<h2>4. Teste do Sistema Completo</h2>";

try {
    // Testar Models
    require_once 'models/Event.php';
    $eventModel = new Event();
    echo "<p>✅ Event Model carregado</p>";
    
    // Testar Controllers
    require_once 'controllers/EventController.php';
    $eventController = new EventController();
    echo "<p>✅ EventController carregado</p>";
    
    require_once 'controllers/AuthController.php';
    $authController = new AuthController();
    echo "<p>✅ AuthController carregado</p>";
    
    // Testar busca de eventos
    $eventos = $eventController->getPublicEvents(['limite' => 3]);
    echo "<p>✅ Busca de eventos funcionando (" . count($eventos) . " encontrados)</p>";
    
    // Testar categorias
    $categorias = $eventController->getCategories();
    echo "<p>✅ Categorias funcionando (" . count($categorias) . " encontradas)</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro no sistema: " . $e->getMessage() . "</p>";
}

// 5. Criar arquivo de teste simples
echo "<h2>5. Criando Arquivo de Teste Simples</h2>";

$indexContent = '<?php
// Página inicial simples para teste
require_once "config/config.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conecta Eventos - Railway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4rem 0; }
        .card { box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: none; }
    </style>
</head>
<body>
    <div class="hero">
        <div class="container text-center">
            <h1 class="display-4 mb-4">🎉 Conecta Eventos</h1>
            <p class="lead">Plataforma de eventos funcionando no Railway!</p>
            <div class="mt-4">
                <a href="views/auth/login.php" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <a href="views/auth/register.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Cadastrar
                </a>
            </div>
        </div>
    </div>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-md-8 mx-auto text-center">
                <h2>Sistema Funcionando!</h2>
                <p class="lead">O Conecta Eventos está rodando no Railway.</p>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5>🔑 Credenciais de Teste</h5>
                        <p><strong>Email:</strong> admin@conectaeventos.com</p>
                        <p><strong>Senha:</strong> admin123</p>
                        <p><strong>Tipo:</strong> Organizador</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="views/auth/login.php" class="btn btn-primary">
                        Fazer Login Agora
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>';

if (file_put_contents('index_temp.php', $indexContent)) {
    echo "<p>✅ Arquivo index_temp.php criado</p>";
} else {
    echo "<p class='error'>❌ Erro ao criar arquivo de teste</p>";
}

// 6. Relatório final
echo "<h2>6. Relatório Final</h2>";

$problemas = [];
$sucessos = [];

// Verificar status geral
if (isset($admin) && $admin) {
    $sucessos[] = "Usuário admin existe e está configurado";
} else {
    $problemas[] = "Problema com usuário admin";
}

if (isset($loginTest) && $loginTest['success']) {
    $sucessos[] = "Sistema de login funcionando";
} else {
    $problemas[] = "Sistema de login com problemas";
}

if (isset($eventos) && count($eventos) > 0) {
    $sucessos[] = "Sistema de eventos funcionando";
} else {
    $sucessos[] = "Sistema básico funcionando (sem eventos ainda)";
}

echo "<div class='summary'>";
echo "<h3>✅ Sucessos (" . count($sucessos) . ")</h3>";
echo "<ul>";
foreach ($sucessos as $sucesso) {
    echo "<li>$sucesso</li>";
}
echo "</ul>";

if (!empty($problemas)) {
    echo "<h3>⚠️ Problemas (" . count($problemas) . ")</h3>";
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li>$problema</li>";
    }
    echo "</ul>";
}
echo "</div>";

if (empty($problemas)) {
    echo "<div class='success-final'>";
    echo "<h3>🎉 Sistema Railway 100% Funcional!</h3>";
    echo "<p>O Conecta Eventos está funcionando perfeitamente no Railway.</p>";
    
    echo "<div class='credentials'>";
    echo "<h4>📋 Informações de Acesso:</h4>";
    echo "<p><strong>URL:</strong> https://conecta-eventos-production.up.railway.app</p>";
    echo "<p><strong>Email:</strong> admin@conectaeventos.com</p>";
    echo "<p><strong>Senha:</strong> admin123</p>";
    echo "<p><strong>Tipo:</strong> Organizador (pode criar eventos)</p>";
    echo "</div>";
    
    echo "<div class='next-steps'>";
    echo "<h4>🚀 Próximos Passos:</h4>";
    echo "<ol>";
    echo "<li>Renomear index_temp.php para index.php se necessário</li>";
    echo "<li>Fazer login no sistema</li>";
    echo "<li>Criar eventos de teste</li>";
    echo "<li>Testar funcionalidades</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='actions'>";
    echo "<a href='views/auth/login.php' class='btn-action'>🔑 Fazer Login</a>";
    echo "<a href='index_temp.php' class='btn-action'>🏠 Ver Página Inicial</a>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div class='warning-final'>";
    echo "<h3>⚠️ Sistema Funcionando com Ressalvas</h3>";
    echo "<p>O sistema está rodando, mas alguns problemas foram encontrados.</p>";
    echo "<p>Verifique os itens listados acima.</p>";
    echo "</div>";
}

// CSS para melhor apresentação
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; line-height: 1.6; }
    h1 { color: #007bff; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
    h2 { color: #495057; margin-top: 30px; background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h3 { color: #28a745; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    
    .success-final { 
        background: linear-gradient(135deg, #d4edda, #c3e6cb); 
        border: 2px solid #28a745; 
        padding: 25px; 
        border-radius: 10px; 
        margin: 20px 0;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
    }
    
    .warning-final { 
        background: linear-gradient(135deg, #fff3cd, #ffeaa7); 
        border: 2px solid #ffc107; 
        padding: 25px; 
        border-radius: 10px; 
        margin: 20px 0;
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);
    }
    
    .credentials { 
        background: rgba(255, 255, 255, 0.7); 
        padding: 15px; 
        border-radius: 5px; 
        margin: 15px 0; 
    }
    
    .next-steps { 
        background: rgba(255, 255, 255, 0.7); 
        padding: 15px; 
        border-radius: 5px; 
        margin: 15px 0; 
    }
    
    .actions { margin: 20px 0; }
    
    .btn-action { 
        display: inline-block; 
        padding: 12px 25px; 
        margin: 5px 10px 5px 0;
        border-radius: 25px; 
        text-decoration: none; 
        font-weight: 600;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .btn-action:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 4px 20px rgba(0,0,0,0.2); 
        color: white;
        text-decoration: none;
    }
    
    .summary { 
        background: white; 
        padding: 20px; 
        border-radius: 10px; 
        margin: 20px 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    ul { margin: 10px 0; }
    li { margin: 5px 0; }
    p { margin: 8px 0; }
</style>";

echo "<hr style='margin: 30px 0; border: 2px solid #dee2e6;'>";
echo "<p style='text-align: center;'><small><strong>Conecta Eventos - Railway Deploy</strong> - " . date('Y-m-d H:i:s') . "</small></p>";
?>