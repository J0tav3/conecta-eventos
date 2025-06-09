<?php
// ==========================================
// TESTE DE FLUXO COMPLETO DE CADASTRO
// Local: test_registration_flow.php
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "🧪 Teste de Fluxo de Cadastro - Problema 1";
$test_results = [];
$overall_success = true;
$test_count = 0;
$success_count = 0;

// Controle de execução
$execute_tests = isset($_GET['run']) && $_GET['run'] === 'true';
$test_email = 'teste_' . time() . '@conectaeventos.com';

function runTest($name, $test_function, $critical = false) {
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
                'details' => $result['details'] ?? '',
                'critical' => $critical
            ];
        } else {
            if ($critical) $overall_success = false;
            return [
                'name' => $name,
                'status' => 'error',
                'message' => $result['message'],
                'details' => $result['details'] ?? '',
                'critical' => $critical
            ];
        }
    } catch (Exception $e) {
        if ($critical) $overall_success = false;
        return [
            'name' => $name,
            'status' => 'error',
            'message' => 'Erro na execução: ' . $e->getMessage(),
            'details' => '',
            'critical' => $critical
        ];
    }
}

// ==========================================
// TESTE 1: Preparação do Ambiente
// ==========================================
function testEnvironmentSetup() {
    $checks = [];
    
    // Verificar se session já está iniciada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar dependências
    $required_files = [
        'config/database.php',
        'controllers/AuthController.php',
        'views/auth/register.php'
    ];
    
    foreach ($required_files as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $checks[] = "✓ $file encontrado";
        } else {
            return [
                'success' => false,
                'message' => "Arquivo $file não encontrado",
                'details' => ''
            ];
        }
    }
    
    return [
        'success' => true,
        'message' => 'Ambiente preparado com sucesso',
        'details' => implode(', ', $checks)
    ];
}

// ==========================================
// TESTE 2: Conexão com Banco
// ==========================================
function testDatabaseConnection() {
    try {
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
                'message' => 'Conexão com banco estabelecida',
                'details' => "Driver: {$info['driver']}, Status: Conectado"
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
// TESTE 3: Validação do AuthController
// ==========================================
function testAuthController() {
    try {
        require_once __DIR__ . '/controllers/AuthController.php';
        
        if (!class_exists('AuthController')) {
            return [
                'success' => false,
                'message' => 'Classe AuthController não encontrada',
                'details' => ''
            ];
        }
        
        $auth = new AuthController();
        
        // Verificar métodos essenciais
        $required_methods = ['register', 'login', 'isLoggedIn'];
        $missing_methods = [];
        
        foreach ($required_methods as $method) {
            if (!method_exists($auth, $method)) {
                $missing_methods[] = $method;
            }
        }
        
        if (empty($missing_methods)) {
            return [
                'success' => true,
                'message' => 'AuthController carregado e funcional',
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
            'message' => 'Erro ao carregar AuthController: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 4: Simulação de Cadastro Válido
// ==========================================
function testValidRegistration() {
    global $test_email;
    
    try {
        require_once __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController();
        
        $valid_data = [
            'nome' => 'Usuário Teste Completo',
            'email' => $test_email,
            'senha' => 'senha123456',
            'confirma_senha' => 'senha123456',
            'tipo_usuario' => 'participante',
            'telefone' => '(11) 99999-8888',
            'cidade' => 'São Paulo',
            'estado' => 'SP'
        ];
        
        $result = $auth->register($valid_data);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Cadastro válido processado com sucesso',
                'details' => "Email: {$test_email}, Resultado: {$result['message']}"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Cadastro válido falhou: ' . $result['message'],
                'details' => 'Dados: ' . json_encode($valid_data)
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
// TESTE 5: Validação de Dados Inválidos
// ==========================================
function testInvalidRegistration() {
    try {
        require_once __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController();
        
        $invalid_tests = [
            [
                'data' => [
                    'nome' => '',
                    'email' => 'email-inválido',
                    'senha' => '123',
                    'confirma_senha' => '456',
                    'tipo_usuario' => 'invalido'
                ],
                'expected' => 'deveria falhar'
            ],
            [
                'data' => [
                    'nome' => 'A',
                    'email' => 'teste@test.com',
                    'senha' => 'ab',
                    'confirma_senha' => 'ab',
                    'tipo_usuario' => 'participante'
                ],
                'expected' => 'deveria falhar por senha muito curta'
            ]
        ];
        
        $failures = [];
        $successes = [];
        
        foreach ($invalid_tests as $test) {
            $result = $auth->register($test['data']);
            
            if ($result['success']) {
                $failures[] = "Teste passou quando {$test['expected']}";
            } else {
                $successes[] = "Rejeitou corretamente: {$result['message']}";
            }
        }
        
        if (empty($failures)) {
            return [
                'success' => true,
                'message' => 'Validação de dados funcionando',
                'details' => implode(', ', $successes)
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Problemas na validação',
                'details' => implode(', ', $failures)
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro no teste de validação: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 6: Teste de Duplicação de Email
// ==========================================
function testDuplicateEmailPrevention() {
    global $test_email;
    
    try {
        require_once __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController();
        
        // Tentar cadastrar novamente com o mesmo email
        $duplicate_data = [
            'nome' => 'Outro Usuário',
            'email' => $test_email,
            'senha' => 'outrasenha123',
            'confirma_senha' => 'outrasenha123',
            'tipo_usuario' => 'organizador',
            'telefone' => '(11) 88888-7777',
            'cidade' => 'Rio de Janeiro',
            'estado' => 'RJ'
        ];
        
        $result = $auth->register($duplicate_data);
        
        if (!$result['success'] && strpos(strtolower($result['message']), 'email') !== false) {
            return [
                'success' => true,
                'message' => 'Prevenção de email duplicado funcionando',
                'details' => "Rejeitou corretamente: {$result['message']}"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Permitiu email duplicado incorretamente',
                'details' => 'Resultado: ' . ($result['success'] ? 'sucesso' : $result['message'])
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro no teste de duplicação: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 7: Teste de Login com Usuário Criado
// ==========================================
function testLoginWithCreatedUser() {
    global $test_email;
    
    try {
        require_once __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController();
        
        $login_data = [
            'email' => $test_email,
            'senha' => 'senha123456'
        ];
        
        $result = $auth->login($login_data);
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Login com usuário criado funcionando',
                'details' => "Login realizado: {$result['message']}"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Falha no login com usuário criado: ' . $result['message'],
                'details' => "Email: $test_email"
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
// TESTE 8: Verificação da Sessão
// ==========================================
function testSessionManagement() {
    try {
        require_once __DIR__ . '/controllers/AuthController.php';
        $auth = new AuthController();
        
        // Verificar se usuário está logado após o login anterior
        $is_logged = $auth->isLoggedIn();
        
        if ($is_logged) {
            $user_data = $auth->getCurrentUser();
            return [
                'success' => true,
                'message' => 'Gestão de sessão funcionando',
                'details' => "Usuário logado: " . ($user_data['name'] ?? 'N/A')
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Sessão não foi criada após login',
                'details' => 'isLoggedIn() retornou false'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro no teste de sessão: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// ==========================================
// TESTE 9: Limpeza (Opcional)
// ==========================================
function testCleanup() {
    global $test_email;
    
    try {
        require_once __DIR__ . '/config/database.php';
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        if ($conn) {
            // Remover usuário de teste criado
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE email = ?");
            $result = $stmt->execute([$test_email]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Limpeza realizada com sucesso',
                    'details' => "Usuário teste removido: $test_email"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Falha na limpeza',
                    'details' => 'Não foi possível remover usuário teste'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Sem conexão para limpeza',
                'details' => ''
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro na limpeza: ' . $e->getMessage(),
            'details' => ''
        ];
    }
}

// Executar testes se solicitado
if ($execute_tests) {
    $test_results[] = runTest('1. Preparação do Ambiente', 'testEnvironmentSetup', true);
    $test_results[] = runTest('2. Conexão com Banco', 'testDatabaseConnection', true);
    $test_results[] = runTest('3. AuthController', 'testAuthController', true);
    $test_results[] = runTest('4. Cadastro Válido', 'testValidRegistration', true);
    $test_results[] = runTest('5. Validação de Dados', 'testInvalidRegistration', false);
    $test_results[] = runTest('6. Prevenção Email Duplicado', 'testDuplicateEmailPrevention', false);
    $test_results[] = runTest('7. Login com Usuário Criado', 'testLoginWithCreatedUser', true);
    $test_results[] = runTest('8. Gestão de Sessão', 'testSessionManagement', false);
    $test_results[] = runTest('9. Limpeza', 'testCleanup', false);
    
    $success_rate = ($success_count / $test_count) * 100;
}
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
            background: <?php echo $execute_tests ? ($overall_success ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 'linear-gradient(135deg, #dc3545 0%, #fd7e14 100%)') : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>;
            color: white;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255,255,255,0.95);
            color: #333;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 { 
            color: <?php echo $execute_tests ? ($overall_success ? '#28a745' : '#dc3545') : '#667eea'; ?>; 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 3px solid <?php echo $execute_tests ? ($overall_success ? '#28a745' : '#dc3545') : '#667eea'; ?>; 
            padding-bottom: 15px; 
        }
        .status-overall {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            background: <?php echo $execute_tests ? ($overall_success ? '#d4edda' : '#f8d7da') : '#e3f2fd'; ?>;
            border: 2px solid <?php echo $execute_tests ? ($overall_success ? '#28a745' : '#dc3545') : '#2196f3'; ?>;
            color: <?php echo $execute_tests ? ($overall_success ? '#155724' : '#721c24') : '#1565c0'; ?>;
        }
        .test-controls {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
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
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
        }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
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
        .critical-badge {
            background: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        .flow-diagram {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .flow-step {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .flow-step.completed {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .flow-step.failed {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .flow-arrow {
            text-align: center;
            font-size: 1.5rem;
            color: #007bff;
            margin: 5px 0;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste de Fluxo Completo - Cadastro de Usuários</h1>
        
        <?php if (!$execute_tests): ?>
            <!-- Modo de Apresentação -->
            <div class="status-overall">
                <h2>🔬 Laboratório de Testes - Problema 1</h2>
                <p>Este script testa o fluxo completo de cadastro de usuários em tempo real</p>
            </div>

            <div class="flow-diagram">
                <h3>🔄 Fluxo de Teste que será Executado:</h3>
                
                <div class="flow-step">
                    <strong>1️⃣ Preparação do Ambiente</strong> - Verificar arquivos e dependências
                </div>
                <div class="flow-arrow">⬇️</div>
                
                <div class="flow-step">
                    <strong>2️⃣ Conexão com Banco</strong> - Testar conectividade com Railway MySQL
                </div>
                <div class="flow-arrow">⬇️</div>
                
                <div class="flow-step">
                    <strong>3️⃣ AuthController</strong> - Validar carregamento e métodos
                </div>
                <div class="flow-arrow">⬇️</div>
                
                <div class="flow-step">
                    <strong>4️⃣ Cadastro Válido</strong> - Simular cadastro com dados corretos
                </div>
                <div class="flow-arrow">⬇️</div>
                
                <div class="flow-step">
                    <strong>5️⃣ Validação de Dados</strong> - Testar rejeição de dados inválidos
                </div>
                <div class="flow-arrow">⬇️</div>
                
                <div class="flow-step">
                    <strong>6️⃣ Prevenção Duplicação</strong> - Verificar se impede emails duplicados
                </div>
                <div class="flow-arrow">⬇️</div>
                
                <div class="flow-step">
                    <strong>7️⃣ Login</strong> - Testar login com usuário recém-criado
                </div>
                <div class="flow-arrow">⬇️</div>
                
                <div class="flow-step">
                    <strong>8️⃣ Gestão de Sessão</strong> - Verificar criação e manutenção de sessão
                </div>
                <div class="flow-arrow">⬇️</div>
                
                <div class="flow-step">
                    <strong>9️⃣ Limpeza</strong> - Remover dados de teste (opcional)
                </div>
            </div>

            <div class="warning-box">
                <h4>⚠️ Importante:</h4>
                <ul>
                    <li>Este teste criará um usuário temporário no banco de dados</li>
                    <li>O usuário será removido automaticamente ao final (se limpeza funcionar)</li>
                    <li>O teste é seguro e não afetará dados existentes</li>
                    <li>Email de teste: <code><?php echo $test_email; ?></code></li>
                </ul>
            </div>

            <div class="test-controls">
                <h3>🚀 Executar Testes</h3>
                <a href="?run=true" class="btn btn-success">
                    ▶️ Executar Teste Completo
                </a>
                <a href="verification_final.php" class="btn">
                    📋 Voltar para Verificação Final
                </a>
                <a href="index.php" class="btn">
                    🏠 Ir para o Site
                </a>
            </div>

        <?php else: ?>
            <!-- Resultados dos Testes -->
            <div class="status-overall">
                <h2>
                    <?php if ($overall_success): ?>
                        🎉 FLUXO DE CADASTRO FUNCIONANDO PERFEITAMENTE!
                    <?php else: ?>
                        ⚠️ PROBLEMAS DETECTADOS NO FLUXO DE CADASTRO
                    <?php endif; ?>
                </h2>
                <p>
                    <?php if ($overall_success): ?>
                        Todos os testes críticos passaram! O sistema de cadastro está operacional.
                    <?php else: ?>
                        Alguns testes falharam. Verifique os detalhes abaixo.
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
                <div class="stat-card">
                    <div class="stat-number"><?php echo $test_email; ?></div>
                    <div>Email de Teste</div>
                </div>
            </div>

            <!-- Resultados Detalhados -->
            <h3>📋 Resultados Detalhados do Fluxo</h3>
            
            <?php foreach ($test_results as $result): ?>
                <div class="test-result test-<?php echo $result['status']; ?>">
                    <div class="test-name">
                        <?php if ($result['status'] === 'success'): ?>
                            ✅
                        <?php else: ?>
                            ❌
                        <?php endif; ?>
                        <?php echo htmlspecialchars($result['name']); ?>
                        <?php if ($result['critical']): ?>
                            <span class="critical-badge">CRÍTICO</span>
                        <?php endif; ?>
                    </div>
                    <div><?php echo htmlspecialchars($result['message']); ?></div>
                    <?php if (!empty($result['details'])): ?>
                        <div class="test-details">
                            💡 <?php echo htmlspecialchars($result['details']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="test-controls">
                <h3>🔄 Próximas Ações</h3>
                
                <?php if ($overall_success): ?>
                    <a href="views/auth/register.php" class="btn btn-success">
                        🎯 Testar Cadastro Real
                    </a>
                    <a href="views/auth/login.php" class="btn btn-success">
                        🔑 Testar Login Real
                    </a>
                    <a href="index.php" class="btn btn-success">
                        🏠 Ver Site Funcionando
                    </a>
                <?php else: ?>
                    <a href="diagnostic.php" class="btn btn-danger">
                        🔧 Executar Diagnóstico
                    </a>
                    <a href="auto_config_railway.php" class="btn btn-danger">
                        ⚙️ Auto Configuração
                    </a>
                <?php endif; ?>
                
                <a href="?run=false" class="btn">
                    🔄 Executar Novamente
                </a>
                <a href="verification_final.php" class="btn">
                    📋 Verificação Final
                </a>
            </div>

            <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                <h4>📊 Relatório Final</h4>
                <p><strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                <p><strong>Email de Teste:</strong> <?php echo $test_email; ?></p>
                <p><strong>Taxa de Sucesso:</strong> <?php echo round($success_rate, 1); ?>%</p>
                <p><strong>Status:</strong> <?php echo $overall_success ? '🟢 Sistema Operacional' : '🔴 Requer Correções'; ?></p>
                
                <?php if ($overall_success): ?>
                    <div style="color: #28a745; font-weight: bold; font-size: 1.2rem; margin-top: 15px;">
                        ✅ PROBLEMA 1 RESOLVIDO COM SUCESSO!
                        <br><small style="font-weight: normal;">O sistema de cadastro de usuários está funcionando perfeitamente.</small>
                    </div>
                <?php else: ?>
                    <div style="color: #dc3545; font-weight: bold; font-size: 1.2rem; margin-top: 15px;">
                        ❌ PROBLEMA 1 AINDA PRECISA DE ATENÇÃO
                        <br><small style="font-weight: normal;">Alguns componentes críticos falharam nos testes.</small>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
        
        <!-- Informações Técnicas -->
        <div style="margin-top: 40px; padding: 20px; background: #e3f2fd; border-radius: 10px;">
            <h4>🔧 Informações Técnicas</h4>
            <div class="row">
                <div style="width: 50%; float: left;">
                    <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                    <strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
                    <strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                    <strong>Memória Usada:</strong> <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB
                </div>
                <div style="width: 50%; float: right;">
                    <strong>Domínio:</strong> conecta-eventos-production.up.railway.app<br>
                    <strong>Ambiente:</strong> <?php echo getenv('RAILWAY_ENVIRONMENT') ?: 'Local'; ?><br>
                    <strong>Teste ID:</strong> <?php echo substr($test_email, 6, 10); ?><br>
                    <strong>Status Geral:</strong> <?php echo $execute_tests ? ($overall_success ? '🟢 OK' : '🔴 FALHA') : '⚪ AGUARDANDO'; ?>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
        
        <!-- Troubleshooting -->
        <?php if ($execute_tests && !$overall_success): ?>
            <div style="margin-top: 40px; padding: 20px; background: #fff3cd; border-radius: 10px; border: 1px solid #ffeaa7;">
                <h4>🛠️ Guia de Troubleshooting</h4>
                
                <h5>Problemas Comuns e Soluções:</h5>
                <ul>
                    <li><strong>Erro de Conexão com Banco:</strong>
                        <ul>
                            <li>Verificar se DATABASE_URL está configurada no Railway</li>
                            <li>Executar: <code>railway env</code></li>
                            <li>Verificar se o MySQL está ativo no Railway</li>
                        </ul>
                    </li>
                    <li><strong>AuthController não encontrado:</strong>
                        <ul>
                            <li>Verificar se o arquivo existe em <code>controllers/AuthController.php</code></li>
                            <li>Verificar permissões de arquivo</li>
                            <li>Verificar sintaxe PHP no arquivo</li>
                        </ul>
                    </li>
                    <li><strong>Falha na Validação:</strong>
                        <ul>
                            <li>Verificar implementação dos métodos de validação</li>
                            <li>Testar cada campo individualmente</li>
                            <li>Verificar regex e regras de negócio</li>
                        </ul>
                    </li>
                    <li><strong>Problemas de Sessão:</strong>
                        <ul>
                            <li>Verificar configurações de sessão do PHP</li>
                            <li>Verificar se session_start() está sendo chamado</li>
                            <li>Verificar permissões de diretório de sessão</li>
                        </ul>
                    </li>
                </ul>
                
                <h5>Comandos Úteis para Debug:</h5>
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;">
                    railway logs --follow<br>
                    railway env<br>
                    railway connect MySQL<br>
                    php diagnostic.php<br>
                    php verification_final.php
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Próximos Passos -->
        <div style="margin-top: 40px; padding: 20px; background: #e8f5e8; border-radius: 10px;">
            <h4>🎯 Próximos Passos no Plano de Correções</h4>
            
            <?php if ($execute_tests && $overall_success): ?>
                <div style="color: #28a745;">
                    <h5>✅ PROBLEMA 1 CONCLUÍDO!</h5>
                    <p>Sistema de cadastro de usuários funcionando. Próximos problemas a resolver:</p>
                    <ol>
                        <li><strong>PROBLEMA 2:</strong> Validação Estado/Cidade no cadastro</li>
                        <li><strong>PROBLEMA 3:</strong> Sistema de inscrições não responde</li>
                        <li><strong>PROBLEMA 4:</strong> Sistema de favoritos não funciona</li>
                        <li><strong>PROBLEMA 5:</strong> Edição de eventos com erro</li>
                    </ol>
                </div>
            <?php else: ?>
                <div style="color: #dc3545;">
                    <h5>⚠️ PROBLEMA 1 EM ANDAMENTO</h5>
                    <p>Continue trabalhando na correção do sistema de cadastro antes de avançar:</p>
                    <ol>
                        <li>Executar diagnóstico completo</li>
                        <li>Corrigir problemas identificados nos testes</li>
                        <li>Executar este teste novamente</li>
                        <li>Confirmar 100% de sucesso</li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh opcional para testes em andamento
        <?php if ($execute_tests && !$overall_success): ?>
        console.log('🔴 Teste falhou. Auto-refresh em 60 segundos...');
        setTimeout(function() {
            if (confirm('Alguns testes falharam. Deseja executar novamente?')) {
                location.reload();
            }
        }, 60000);
        <?php elseif ($execute_tests && $overall_success): ?>
        console.log('✅ Todos os testes passaram!');
        
        // Celebração visual
        setTimeout(function() {
            document.body.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%, #17a2b8 200%)';
        }, 1000);
        <?php endif; ?>

        // Função para copiar logs
        function copyLogs() {
            const logs = document.querySelector('.test-results').innerText;
            navigator.clipboard.writeText(logs).then(function() {
                alert('Logs copiados para a área de transferência!');
            });
        }

        // Destacar testes críticos
        document.addEventListener('DOMContentLoaded', function() {
            const criticalTests = document.querySelectorAll('.critical-badge');
            criticalTests.forEach(badge => {
                badge.parentElement.parentElement.style.border = '2px solid #dc3545';
                badge.parentElement.parentElement.style.borderRadius = '10px';
            });
        });

        // Log de informações para debug
        console.log('🧪 Teste de Fluxo de Cadastro');
        console.log('📧 Email de teste: <?php echo $test_email; ?>');
        console.log('🔄 Executado: <?php echo $execute_tests ? "Sim" : "Não"; ?>');
        <?php if ($execute_tests): ?>
        console.log('📊 Taxa de sucesso: <?php echo round($success_rate); ?>%');
        console.log('✅ Status: <?php echo $overall_success ? "SUCESSO" : "FALHA"; ?>');
        <?php endif; ?>
    </script>
</body>
</html>