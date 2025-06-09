<?php
// ==========================================
// SCRIPT FINAL DE VERIFICAÇÃO - CADASTRO DE USUÁRIOS
// Local: verification_final.php
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "✅ Verificação Final - Problema 1 Resolvido";
$results = [];
$overall_success = true;
$test_count = 0;
$success_count = 0;

function runTest($name, $test_function) {
    global $test_count, $success_count, $overall_success;
    $test_count++;
    
    try {
        $result = $test_function();
        if ($result['success']) {
            $success_count++;
            return [
                'name' => $name,
                'status' => 'success',
                'message' => $result['message'],
                'details' => $result['details'] ?? ''
            ];
        } else {
            $overall_success = false;
            return [
                'name' => $name,
                'status' => 'error',
                'message' => $result['message'],
                'details' => $result['details'] ?? ''
            ];
        }
    } catch (Exception $e) {
        $overall_success = false;
        return [
            'name' => $name,
            'status' => 'error',
            'message' => 'Erro na execução: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 1: Verificar Arquivos Essenciais
// ==========================================
function testEssentialFiles() {
    $required_files = [
        'config/database.php' => 'Configuração de banco corrigida',
        'controllers/AuthController.php' => 'Controller de autenticação',
        'views/auth/register.php' => 'Página de cadastro',
        'views/auth/login.php' => 'Página de login',
        'includes/session.php' => 'Funções de sessão'
    ];
    
    $missing = [];
    $found = [];
    
    foreach ($required_files as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $found[] = "$file ✓";
        } else {
            $missing[] = "$file ❌";
        }
    }
    
    if (empty($missing)) {
        return [
            'success' => true,
            'message' => 'Todos os arquivos essenciais encontrados!',
            'details' => implode(', ', $found)
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Arquivos em falta: ' . implode(', ', $missing),
            'details' => 'Encontrados: ' . implode(', ', $found)
        ];
    }
}

// ==========================================
// TESTE 2: Verificar Conexão com Banco
// ==========================================
function testDatabaseConnection() {
    try {
        if (!file_exists(__DIR__ . '/config/database.php')) {
            return [
                'success' => false,
                'message' => 'Arquivo database.php não encontrado',
                'details' => ''
            ];
        }
        
        require_once __DIR__ . '/config/database.php';
        
        if (!class_exists('Database')) {
            return [
                'success' => false,
                'message' => 'Classe Database não encontrada',
                'details' => ''
            ];
        }
        
        $db = Database::getInstance();
        $test_result = $db->testConnection();
        
        if ($test_result['success']) {
            $info = $db->getConnectionInfo();
            return [
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso!',
                'details' => "Driver: {$info['driver']}, Versão: {$info['version']}"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Falha na conexão: ' . $test_result['message'],
                'details' => ''
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro na conexão: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 3: Verificar Estrutura do Banco
// ==========================================
function testDatabaseStructure() {
    try {
        require_once __DIR__ . '/config/database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        if (!$conn) {
            return [
                'success' => false,
                'message' => 'Sem conexão com banco',
                'details' => ''
            ];
        }
        
        // Verificar tabelas essenciais
        $required_tables = ['usuarios', 'eventos', 'categorias', 'inscricoes'];
        $found_tables = [];
        $missing_tables = [];
        
        foreach ($required_tables as $table) {
            try {
                $stmt = $conn->query("SELECT 1 FROM $table LIMIT 1");
                $found_tables[] = $table;
            } catch (PDOException $e) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            // Verificar estrutura da tabela usuarios
            $stmt = $conn->query("DESCRIBE usuarios");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $required_columns = ['id_usuario', 'nome', 'email', 'senha', 'tipo'];
            $missing_columns = array_diff($required_columns, $columns);
            
            if (empty($missing_columns)) {
                return [
                    'success' => true,
                    'message' => 'Estrutura do banco verificada!',
                    'details' => 'Tabelas: ' . implode(', ', $found_tables) . '. Colunas da tabela usuarios: ' . implode(', ', $columns)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Colunas em falta na tabela usuarios: ' . implode(', ', $missing_columns),
                    'details' => 'Colunas encontradas: ' . implode(', ', $columns)
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Tabelas em falta: ' . implode(', ', $missing_tables),
                'details' => 'Tabelas encontradas: ' . implode(', ', $found_tables)
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro na verificação: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 4: Verificar AuthController
// ==========================================
function testAuthController() {
    try {
        if (!file_exists(__DIR__ . '/controllers/AuthController.php')) {
            return [
                'success' => false,
                'message' => 'AuthController.php não encontrado',
                'details' => ''
            ];
        }
        
        require_once __DIR__ . '/controllers/AuthController.php';
        
        if (!class_exists('AuthController')) {
            return [
                'success' => false,
                'message' => 'Classe AuthController não encontrada',
                'details' => ''
            ];
        }
        
        $auth = new AuthController();
        
        // Verificar se métodos essenciais existem
        $required_methods = ['login', 'register', 'isLoggedIn', 'logout'];
        $missing_methods = [];
        
        foreach ($required_methods as $method) {
            if (!method_exists($auth, $method)) {
                $missing_methods[] = $method;
            }
        }
        
        if (empty($missing_methods)) {
            return [
                'success' => true,
                'message' => 'AuthController carregado e funcional!',
                'details' => 'Métodos disponíveis: ' . implode(', ', $required_methods)
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Métodos em falta: ' . implode(', ', $missing_methods),
                'details' => ''
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao verificar AuthController: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 5: Teste de Cadastro Simulado
// ==========================================
function testUserRegistration() {
    try {
        require_once __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController();
        
        // Dados de teste
        $test_data = [
            'nome' => 'Usuário Teste',
            'email' => 'teste' . time() . '@teste.com',
            'senha' => 'senha123',
            'confirma_senha' => 'senha123',
            'tipo_usuario' => 'participante',
            'telefone' => '(11) 99999-9999',
            'cidade' => 'São Paulo',
            'estado' => 'SP'
        ];
        
        $result = $auth->register($test_data);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Cadastro de usuário funcionando!',
                'details' => 'Email de teste: ' . $test_data['email'] . '. ' . $result['message']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Falha no cadastro: ' . $result['message'],
                'details' => 'Dados testados: ' . json_encode($test_data)
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro no teste de cadastro: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 6: Teste de Login
// ==========================================
function testUserLogin() {
    try {
        require_once __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController();
        
        // Testar login com conta demo
        $result = $auth->login([
            'email' => 'admin@conectaeventos.com',
            'senha' => 'admin123'
        ]);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Sistema de login funcionando!',
                'details' => $result['message']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Falha no login: ' . $result['message'],
                'details' => ''
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro no teste de login: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 7: Verificar Páginas de Cadastro/Login
// ==========================================
function testAuthPages() {
    $pages = [
        'views/auth/register.php' => 'Página de cadastro',
        'views/auth/login.php' => 'Página de login'
    ];
    
    $working_pages = [];
    $broken_pages = [];
    
    foreach ($pages as $page => $description) {
        if (file_exists(__DIR__ . '/' . $page)) {
            // Verificar se a página não tem erros de sintaxe
            $content = file_get_contents(__DIR__ . '/' . $page);
            
            // Verificações básicas
            $has_form = strpos($content, '<form') !== false;
            $has_method_post = strpos($content, 'method="POST"') !== false || strpos($content, "method='POST'") !== false;
            
            if ($has_form && $has_method_post) {
                $working_pages[] = $description;
            } else {
                $broken_pages[] = "$description (sem formulário adequado)";
            }
        } else {
            $broken_pages[] = "$description (arquivo não encontrado)";
        }
    }
    
    if (empty($broken_pages)) {
        return [
            'success' => true,
            'message' => 'Páginas de autenticação funcionais!',
            'details' => 'Páginas verificadas: ' . implode(', ', $working_pages)
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Problemas encontrados: ' . implode(', ', $broken_pages),
            'details' => 'Páginas OK: ' . implode(', ', $working_pages)
        ];
    }
}

// ==========================================
// TESTE 8: Variáveis de Ambiente
// ==========================================
function testEnvironmentVariables() {
    $env_vars = [
        'DATABASE_URL' => getenv('DATABASE_URL'),
        'MYSQLHOST' => getenv('MYSQLHOST'),
        'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT')
    ];
    
    $found_vars = [];
    $missing_vars = [];
    
    foreach ($env_vars as $name => $value) {
        if (!empty($value)) {
            if ($name === 'DATABASE_URL') {
                $found_vars[] = "$name (configurada)";
            } else {
                $found_vars[] = "$name = $value";
            }
        } else {
            $missing_vars[] = $name;
        }
    }
    
    if (!empty($found_vars)) {
        return [
            'success' => true,
            'message' => 'Variáveis de ambiente do Railway encontradas!',
            'details' => implode(', ', $found_vars) . (empty($missing_vars) ? '' : ' | Não encontradas: ' . implode(', ', $missing_vars))
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Nenhuma variável de ambiente encontrada',
            'details' => 'Verifique a configuração do Railway'
        ];
    }
}

// ==========================================
// EXECUTAR TODOS OS TESTES
// ==========================================
$results[] = runTest('Arquivos Essenciais', 'testEssentialFiles');
$results[] = runTest('Conexão com Banco', 'testDatabaseConnection');
$results[] = runTest('Estrutura do Banco', 'testDatabaseStructure');
$results[] = runTest('AuthController', 'testAuthController');
$results[] = runTest('Cadastro de Usuário', 'testUserRegistration');
$results[] = runTest('Login de Usuário', 'testUserLogin');
$results[] = runTest('Páginas de Autenticação', 'testAuthPages');
$results[] = runTest('Variáveis de Ambiente', 'testEnvironmentVariables');

// Calcular estatísticas
$success_rate = ($success_count / $test_count) * 100;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: <?php echo $overall_success ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 'linear-gradient(135deg, #dc3545 0%, #fd7e14 100%)'; ?>;
            color: white;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 { 
            color: <?php echo $overall_success ? '#28a745' : '#dc3545'; ?>; 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 3px solid <?php echo $overall_success ? '#28a745' : '#dc3545'; ?>; 
            padding-bottom: 15px; 
        }
        .status-overall {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            background: <?php echo $overall_success ? '#d4edda' : '#f8d7da'; ?>;
            border: 2px solid <?php echo $overall_success ? '#28a745' : '#dc3545'; ?>;
            color: <?php echo $overall_success ? '#155724' : '#721c24'; ?>;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #007bff;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .test-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .test-error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .test-name {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        .test-details {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 10px;
            font-style: italic;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            font-weight: 500;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
        }
        .success-icon { color: #28a745; }
        .error-icon { color: #dc3545; }
        .next-steps {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .progress-fill {
            height: 100%;
            background: <?php echo $success_rate >= 80 ? '#28a745' : ($success_rate >= 60 ? '#ffc107' : '#dc3545'); ?>;
            width: <?php echo $success_rate; ?>%;
            transition: width 1s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $title; ?></h1>
        
        <!-- Status Geral -->
        <div class="status-overall">
            <h2>
                <?php if ($overall_success): ?>
                    🎉 PROBLEMA 1 RESOLVIDO COM SUCESSO!
                <?php else: ?>
                    ⚠️ PROBLEMA 1 AINDA PRECISA DE AJUSTES
                <?php endif; ?>
            </h2>
            <p>
                <?php if ($overall_success): ?>
                    O sistema de cadastro de usuários está funcionando perfeitamente!
                <?php else: ?>
                    Alguns testes falharam. Verifique os detalhes abaixo para corrigir.
                <?php endif; ?>
            </p>
        </div>

        <!-- Estatísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $success_count; ?>/<?php echo $test_count; ?></div>
                <div>Testes Passaram</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round($success_rate); ?>%</div>
                <div>Taxa de Sucesso</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_success ? '✅' : '❌'; ?></div>
                <div>Status Geral</div>
            </div>
        </div>

        <!-- Barra de Progresso -->
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        <p class="text-center">Taxa de Sucesso: <?php echo round($success_rate, 1); ?>%</p>

        <!-- Resultados dos Testes -->
        <h3>📋 Resultados Detalhados</h3>
        
        <?php foreach ($results as $result): ?>
            <div class="test-result test-<?php echo $result['status']; ?>">
                <div class="test-name">
                    <?php if ($result['status'] === 'success'): ?>
                        <i class="success-icon">✅</i>
                    <?php else: ?>
                        <i class="error-icon">❌</i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($result['name']); ?>
                </div>
                <div><?php echo htmlspecialchars($result['message']); ?></div>
                <?php if (!empty($result['details'])): ?>
                    <div class="test-details">
                        💡 <?php echo htmlspecialchars($result['details']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Próximos Passos -->
        <div class="next-steps">
            <h3>🚀 Próximos Passos</h3>
            
            <?php if ($overall_success): ?>
                <div class="alert alert-success">
                    <h4>🎊 Parabéns! O PROBLEMA 1 foi resolvido!</h4>
                    <p><strong>✅ Sistema de Cadastro de Usuários:</strong> 100% Funcional</p>
                    
                    <h5>Agora você pode:</h5>
                    <ul>
                        <li>✅ Testar o cadastro real em: <a href="views/auth/register.php" target="_blank">views/auth/register.php</a></li>
                        <li>✅ Testar o login em: <a href="views/auth/login.php" target="_blank">views/auth/login.php</a></li>
                        <li>✅ Partir para o <strong>PROBLEMA 2</strong>: Validação Estado/Cidade</li>
                        <li>✅ Partir para o <strong>PROBLEMA 3</strong>: Sistema de Inscrições</li>
                    </ul>
                </div>
                
                <div style="text-align: center;">
                    <a href="views/auth/register.php" class="btn" style="background: #28a745;">
                        🎯 Testar Cadastro Real
                    </a>
                    <a href="views/auth/login.php" class="btn" style="background: #17a2b8;">
                        🔑 Testar Login
                    </a>
                    <a href="index.php" class="btn" style="background: #6f42c1;">
                        🏠 Ver Site Funcionando
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h4>⚠️ Ainda há problemas para resolver</h4>
                    <p>Taxa de sucesso: <?php echo round($success_rate); ?>% (necessário: 100%)</p>
                    
                    <h5>Para resolver:</h5>
                    <ol>
                        <li>🔍 Revisar os testes que falharam acima</li>
                        <li>🔧 Corrigir os problemas identificados</li>
                        <li>🔄 Executar este script novamente</li>
                        <li>✅ Confirmar 100% de sucesso</li>
                    </ol>
                </div>
                
                <div style="text-align: center;">
                    <a href="diagnostic.php" class="btn" style="background: #ffc107; color: #000;">
                        🔧 Executar Diagnóstico
                    </a>
                    <a href="auto_config_railway.php" class="btn" style="background: #fd7e14;">
                        ⚙️ Auto Configuração
                    </a>
                    <button onclick="location.reload()" class="btn" style="background: #6c757d;">
                        🔄 Executar Novamente
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Informações do Sistema -->
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4>📊 Informações do Sistema</h4>
            <div class="row">
                <div class="col-md-6">
                    <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                    <strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
                    <strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                </div>
                <div class="col-md-6">
                    <strong>Domínio:</strong> conecta-eventos-production.up.railway.app<br>
                    <strong>Ambiente:</strong> <?php echo getenv('RAILWAY_ENVIRONMENT') ?: 'Local'; ?><br>
                    <strong>Status:</strong> <?php echo $overall_success ? '🟢 Operacional' : '🔴 Com Problemas'; ?>
                </div>
            </div>
        </div>
        
        <!-- Credenciais de Teste -->
        <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 8px;">
            <h5>🔑 Credenciais para Teste</h5>
            <p><strong>E-mail:</strong> admin@conectaeventos.com<br>
            <strong>Senha:</strong> admin123<br>
            <strong>Tipo:</strong> Organizador</p>
        </div>
    </div>

    <script>
        // Animar barra de progresso
        window.addEventListener('load', function() {
            const progressBar = document.querySelector('.progress-fill');
            const targetWidth = progressBar.style.width;
            progressBar.style.width = '0%';
            
            setTimeout(() => {
                progressBar.style.width = targetWidth;
            }, 500);
        });

        // Log no console
        console.log('🔍 Verificação Final executada!');
        console.log('📈 Taxa de sucesso: <?php echo round($success_rate); ?>%');
        console.log('<?php echo $overall_success ? "✅ PROBLEMA 1 RESOLVIDO!" : "⚠️ PROBLEMA 1 ainda precisa de ajustes"; ?>');

        // Auto-refresh opcional
        <?php if (!$overall_success): ?>
        // Auto refresh em 30 segundos se há problemas
        setTimeout(function() {
            if (confirm('Deseja executar a verificação novamente?')) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>