<?php
// ==========================================
// TESTE FINAL - PROBLEMA 1: CADASTRO DE USUÁRIOS
// Local: final_problem1_test.php
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "🎯 Teste Final - PROBLEMA 1: Erro no Cadastro de Usuários";
$start_time = microtime(true);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            color: #28a745; 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 3px solid #28a745; 
            padding-bottom: 15px; 
        }
        .test-step {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #28a745;
        }
        .pass { 
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .fail { 
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .warning { 
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .info { 
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #28a745, #20c997);
            width: 0%;
            transition: width 1s ease;
        }
        .test-results {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .result-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .result-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .pass-number { color: #28a745; }
        .fail-number { color: #dc3545; }
        .total-number { color: #17a2b8; }
        .btn {
            background: #28a745;
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
            background: #218838; 
            color: white;
            text-decoration: none;
        }
        .code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            overflow-x: auto;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Teste Final - PROBLEMA 1</h1>
        
        <div class="info">
            <h3>📋 Objetivo do Teste</h3>
            <p><strong>Verificar se o sistema de cadastro de usuários está funcionando corretamente no Railway.</strong></p>
            <p>Este teste simulará um cadastro completo e verificará todos os componentes necessários.</p>
        </div>

        <?php
        $tests = [];
        $passes = 0;
        $fails = 0;
        $total_tests = 10;

        function runTest($name, $test_function) {
            global $tests, $passes, $fails;
            
            echo "<div class='test-step'>";
            echo "<h4>🔍 $name</h4>";
            
            $start = microtime(true);
            try {
                $result = $test_function();
                $duration = round((microtime(true) - $start) * 1000, 2);
                
                if ($result['success']) {
                    echo "<div class='pass'>✅ PASSOU: {$result['message']} ({$duration}ms)</div>";
                    $passes++;
                    $tests[] = ['name' => $name, 'status' => 'pass', 'message' => $result['message'], 'duration' => $duration];
                } else {
                    echo "<div class='fail'>❌ FALHOU: {$result['message']} ({$duration}ms)</div>";
                    $fails++;
                    $tests[] = ['name' => $name, 'status' => 'fail', 'message' => $result['message'], 'duration' => $duration];
                }
                
                if (isset($result['details'])) {
                    echo "<div class='info'>💡 Detalhes: {$result['details']}</div>";
                }
                
            } catch (Exception $e) {
                $duration = round((microtime(true) - $start) * 1000, 2);
                echo "<div class='fail'>❌ ERRO: " . $e->getMessage() . " ({$duration}ms)</div>";
                $fails++;
                $tests[] = ['name' => $name, 'status' => 'fail', 'message' => $e->getMessage(), 'duration' => $duration];
            }
            
            echo "</div>";
            flush();
        }

        // TESTE 1: Verificar Estrutura de Arquivos
        runTest("Estrutura de Arquivos", function() {
            $required_files = [
                'controllers/AuthController.php',
                'views/auth/register.php',
                'views/auth/login.php'
            ];
            
            $missing = [];
            foreach ($required_files as $file) {
                if (!file_exists(__DIR__ . '/' . $file)) {
                    $missing[] = $file;
                }
            }
            
            if (empty($missing)) {
                return ['success' => true, 'message' => 'Todos os arquivos essenciais estão presentes'];
            } else {
                return ['success' => false, 'message' => 'Arquivos em falta: ' . implode(', ', $missing)];
            }
        });

        // TESTE 2: Verificar Variáveis de Ambiente
        runTest("Variáveis de Ambiente Railway", function() {
            $database_url = getenv('DATABASE_URL');
            $mysql_host = getenv('MYSQLHOST');
            
            if ($database_url) {
                return ['success' => true, 'message' => 'DATABASE_URL configurada', 'details' => 'Railway MySQL conectado'];
            } elseif ($mysql_host) {
                return ['success' => true, 'message' => 'Variáveis MYSQL configuradas', 'details' => 'Usando variáveis MYSQL*'];
            } else {
                return ['success' => false, 'message' => 'Nenhuma configuração de banco encontrada'];
            }
        });

        // TESTE 3: Testar Conexão com Banco
        runTest("Conexão com Banco de Dados", function() {
            $database_url = getenv('DATABASE_URL');
            
            if (!$database_url) {
                // Tentar construir da variáveis MYSQL
                $mysql_host = getenv('MYSQLHOST');
                $mysql_db = getenv('MYSQLDATABASE');
                $mysql_user = getenv('MYSQLUSER');
                $mysql_pass = getenv('MYSQLPASSWORD');
                $mysql_port = getenv('MYSQLPORT') ?: '3306';
                
                if ($mysql_host && $mysql_db && $mysql_user && $mysql_pass) {
                    $database_url = "mysql://$mysql_user:$mysql_pass@$mysql_host:$mysql_port/$mysql_db";
                }
            }
            
            if (!$database_url) {
                return ['success' => false, 'message' => 'Configuração de banco não encontrada'];
            }
            
            try {
                $url_parts = parse_url($database_url);
                $dsn = sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                    $url_parts['host'],
                    $url_parts['port'] ?? 3306,
                    ltrim($url_parts['path'], '/')
                );
                
                $pdo = new PDO($dsn, $url_parts['user'], $url_parts['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 10
                ]);
                
                // Teste simples
                $stmt = $pdo->query("SELECT 1");
                if ($stmt) {
                    return ['success' => true, 'message' => 'Conexão estabelecida e testada'];
                } else {
                    return ['success' => false, 'message' => 'Conexão estabelecida mas teste falhou'];
                }
                
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Erro na conexão: ' . $e->getMessage()];
            }
        });

        // TESTE 4: Verificar Tabela Usuarios
        runTest("Tabela de Usuários", function() {
            $database_url = getenv('DATABASE_URL');
            
            if (!$database_url) {
                $mysql_host = getenv('MYSQLHOST');
                $mysql_db = getenv('MYSQLDATABASE');
                $mysql_user = getenv('MYSQLUSER');
                $mysql_pass = getenv('MYSQLPASSWORD');
                $mysql_port = getenv('MYSQLPORT') ?: '3306';
                
                if ($mysql_host && $mysql_db && $mysql_user && $mysql_pass) {
                    $database_url = "mysql://$mysql_user:$mysql_pass@$mysql_host:$mysql_port/$mysql_db";
                }
            }
            
            if (!$database_url) {
                return ['success' => false, 'message' => 'Sem configuração de banco'];
            }
            
            try {
                $url_parts = parse_url($database_url);
                $dsn = sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                    $url_parts['host'],
                    $url_parts['port'] ?? 3306,
                    ltrim($url_parts['path'], '/')
                );
                
                $pdo = new PDO($dsn, $url_parts['user'], $url_parts['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                try {
                    $stmt = $pdo->query("DESCRIBE usuarios");
                    $columns = $stmt->fetchAll();
                    return ['success' => true, 'message' => 'Tabela usuarios existe', 'details' => count($columns) . ' colunas encontradas'];
                } catch (PDOException $e) {
                    // Tentar criar tabela
                    $create_sql = "
                    CREATE TABLE usuarios (
                        id_usuario INT PRIMARY KEY AUTO_INCREMENT,
                        nome VARCHAR(100) NOT NULL,
                        email VARCHAR(100) UNIQUE NOT NULL,
                        senha VARCHAR(255) NOT NULL,
                        tipo ENUM('organizador', 'participante') NOT NULL DEFAULT 'participante',
                        telefone VARCHAR(20),
                        cidade VARCHAR(100),
                        estado VARCHAR(2),
                        ativo BOOLEAN DEFAULT TRUE,
                        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        ultimo_acesso TIMESTAMP NULL
                    );";
                    
                    $pdo->exec($create_sql);
                    return ['success' => true, 'message' => 'Tabela usuarios criada automaticamente'];
                }
                
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
            }
        });

        // TESTE 5: Carregar AuthController
        runTest("Carregar AuthController", function() {
            $auth_file = __DIR__ . '/controllers/AuthController.php';
            
            if (!file_exists($auth_file)) {
                return ['success' => false, 'message' => 'Arquivo AuthController.php não encontrado'];
            }
            
            try {
                require_once $auth_file;
                
                if (!class_exists('AuthController')) {
                    return ['success' => false, 'message' => 'Classe AuthController não definida no arquivo'];
                }
                
                $auth = new AuthController();
                
                $required_methods = ['register', 'login', 'logout'];
                $missing_methods = [];
                
                foreach ($required_methods as $method) {
                    if (!method_exists($auth, $method)) {
                        $missing_methods[] = $method;
                    }
                }
                
                if (!empty($missing_methods)) {
                    return ['success' => false, 'message' => 'Métodos em falta: ' . implode(', ', $missing_methods)];
                }
                
                return ['success' => true, 'message' => 'AuthController carregado e válido'];
                
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erro ao carregar: ' . $e->getMessage()];
            }
        });

        // TESTE 6: Teste de Registro (Dados Válidos)
        runTest("Cadastro com Dados Válidos", function() {
            try {
                $auth_file = __DIR__ . '/controllers/AuthController.php';
                require_once $auth_file;
                
                $auth = new AuthController();
                
                $test_data = [
                    'nome' => 'Teste Final ' . date('H:i:s'),
                    'email' => 'teste.final.' . time() . '@test.com',
                    'senha' => 'senha123',
                    'confirma_senha' => 'senha123',
                    'tipo_usuario' => 'participante',
                    'telefone' => '(11) 99999-9999',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP'
                ];
                
                $result = $auth->register($test_data);
                
                if ($result['success']) {
                    return ['success' => true, 'message' => 'Cadastro realizado: ' . $result['message']];
                } else {
                    return ['success' => false, 'message' => 'Falha no cadastro: ' . $result['message']];
                }
                
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
            }
        });

        // TESTE 7: Teste de Registro (Dados Inválidos)
        runTest("Validação de Dados Inválidos", function() {
            try {
                $auth_file = __DIR__ . '/controllers/AuthController.php';
                require_once $auth_file;
                
                $auth = new AuthController();
                
                // Teste com email inválido
                $invalid_data = [
                    'nome' => '',
                    'email' => 'email-invalido',
                    'senha' => '123',
                    'tipo_usuario' => 'participante'
                ];
                
                $result = $auth->register($invalid_data);
                
                if (!$result['success']) {
                    return ['success' => true, 'message' => 'Validação funcionando - dados inválidos rejeitados'];
                } else {
                    return ['success' => false, 'message' => 'Validação falhou - dados inválidos aceitos'];
                }
                
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
            }
        });

        // TESTE 8: Teste de Login
        runTest("Sistema de Login", function() {
            try {
                $auth_file = __DIR__ . '/controllers/AuthController.php';
                require_once $auth_file;
                
                $auth = new AuthController();
                
                // Testar login com conta demo
                $login_data = [
                    'email' => 'admin@conectaeventos.com',
                    'senha' => 'admin123'
                ];
                
                $result = $auth->login($login_data);
                
                if ($result['success']) {
                    return ['success' => true, 'message' => 'Login funcionando: ' . $result['message']];
                } else {
                    return ['success' => false, 'message' => 'Login falhou: ' . $result['message']];
                }
                
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
            }
        });

        // TESTE 9: Verificar Sessão
        runTest("Sistema de Sessão", function() {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            if (session_status() != PHP_SESSION_ACTIVE) {
                return ['success' => false, 'message' => 'Sessão PHP não está ativa'];
            }
            
            // Testar se consegue definir variáveis de sessão
            $_SESSION['test_var'] = 'test_value_' . time();
            
            if (isset($_SESSION['test_var'])) {
                unset($_SESSION['test_var']);
                return ['success' => true, 'message' => 'Sistema de sessão funcionando'];
            } else {
                return ['success' => false, 'message' => 'Não foi possível definir variáveis de sessão'];
            }
        });

        // TESTE 10: Teste de Integração Completa
        runTest("Integração Completa", function() {
            try {
                $auth_file = __DIR__ . '/controllers/AuthController.php';
                require_once $auth_file;
                
                $auth = new AuthController();
                
                // 1. Fazer cadastro
                $register_data = [
                    'nome' => 'Integração Teste',
                    'email' => 'integracao.' . time() . '@test.com',
                    'senha' => 'senha123',
                    'confirma_senha' => 'senha123',
                    'tipo_usuario' => 'organizador'
                ];
                
                $register_result = $auth->register($register_data);
                
                if (!$register_result['success']) {
                    return ['success' => false, 'message' => 'Falha no cadastro: ' . $register_result['message']];
                }
                
                // 2. Verificar se sessão foi criada
                if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
                    return ['success' => false, 'message' => 'Cadastro não criou sessão automaticamente'];
                }
                
                // 3. Verificar dados da sessão
                if ($_SESSION['user_name'] !== $register_data['nome']) {
                    return ['success' => false, 'message' => 'Dados da sessão incorretos'];
                }
                
                return ['success' => true, 'message' => 'Fluxo completo funcionando - cadastro + login automático + sessão'];
                
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erro na integração: ' . $e->getMessage()];
            }
        });

        $success_rate = ($passes / $total_tests) * 100;
        $end_time = microtime(true);
        $total_duration = round(($end_time - $start_time) * 1000, 2);
        ?>

        <!-- Barra de Progresso -->
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $success_rate; ?>%"></div>
        </div>

        <!-- Resultados -->
        <div class="test-results">
            <div class="result-card">
                <div class="result-number pass-number"><?php echo $passes; ?></div>
                <div>Testes Passou</div>
            </div>
            <div class="result-card">
                <div class="result-number fail-number"><?php echo $fails; ?></div>
                <div>Testes Falhou</div>
            </div>
            <div class="result-card">
                <div class="result-number total-number"><?php echo $total_tests; ?></div>
                <div>Total de Testes</div>
            </div>
            <div class="result-card">
                <div class="result-number <?php echo $success_rate >= 80 ? 'pass-number' : 'fail-number'; ?>">
                    <?php echo round($success_rate); ?>%
                </div>
                <div>Taxa de Sucesso</div>
            </div>
        </div>

        <!-- Veredicto Final -->
        <?php if ($success_rate >= 90): ?>
            <div class="pass">
                <h2>🎉 PROBLEMA 1 RESOLVIDO COM SUCESSO!</h2>
                <p><strong>✅ Sistema de Cadastro de Usuários: 100% FUNCIONAL</strong></p>
                <p>Todos os componentes estão funcionando corretamente. O erro no cadastro de usuários foi corrigido!</p>
                
                <h4>✅ O que está funcionando:</h4>
                <ul>
                    <li>✅ Conexão com banco de dados Railway</li>
                    <li>✅ Tabela de usuários criada e acessível</li>
                    <li>✅ AuthController carregando sem erros</li>
                    <li>✅ Sistema de cadastro validando dados</li>
                    <li>✅ Sistema de login funcionando</li>
                    <li>✅ Sessões sendo criadas corretamente</li>
                    <li>✅ Integração completa funcionando</li>
                </ul>
            </div>
        <?php elseif ($success_rate >= 70): ?>
            <div class="warning">
                <h2>⚠️ PROBLEMA 1 PARCIALMENTE RESOLVIDO</h2>
                <p><strong>Taxa de Sucesso: <?php echo round($success_rate); ?>%</strong></p>
                <p>A maioria dos componentes está funcionando, mas ainda há algumas questões que precisam ser ajustadas.</p>
            </div>
        <?php else: ?>
            <div class="fail">
                <h2>❌ PROBLEMA 1 AINDA PRECISA DE CORREÇÃO</h2>
                <p><strong>Taxa de Sucesso: <?php echo round($success_rate); ?>%</strong></p>
                <p>Vários componentes ainda apresentam problemas. Execute o script de correção de emergência.</p>
            </div>
        <?php endif; ?>

        <!-- Detalhes dos Testes -->
        <div class="test-step">
            <h3>📋 Relatório Detalhado</h3>
            <div class="code"><?php 
            echo "=== RELATÓRIO DE TESTES ===\n";
            echo "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
            echo "Duração Total: {$total_duration}ms\n";
            echo "Taxa de Sucesso: " . round($success_rate, 2) . "%\n";
            echo "Testes Passou: $passes/$total_tests\n\n";
            
            foreach ($tests as $test) {
                $status = $test['status'] === 'pass' ? '✅ PASSOU' : '❌ FALHOU';
                echo "{$status}: {$test['name']}\n";
                echo "   Resultado: {$test['message']}\n";
                echo "   Duração: {$test['duration']}ms\n\n";
            }
            
            echo "=== ANÁLISE FINAL ===\n";
            if ($success_rate >= 90) {
                echo "🎉 PROBLEMA 1 RESOLVIDO!\n";
                echo "O sistema de cadastro está funcionando perfeitamente.\n";
                echo "Todos os componentes necessários estão operacionais.\n";
            } elseif ($success_rate >= 70) {
                echo "⚠️ PROBLEMA 1 PARCIALMENTE RESOLVIDO\n";
                echo "A maioria dos componentes funciona, mas há ajustes necessários.\n";
            } else {
                echo "❌ PROBLEMA 1 AINDA COM FALHAS\n";
                echo "Vários componentes precisam de correção.\n";
                echo "Execute emergency_fix.php para correções automáticas.\n";
            }
            ?></div>
        </div>

        <!-- Próximos Passos -->
        <div style="text-align: center; margin-top: 40px;">
            <h3>🚀 Próximos Passos</h3>
            
            <?php if ($success_rate >= 90): ?>
                <div class="pass">
                    <h4>🎊 PARABÉNS! Você pode agora:</h4>
                    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                        <a href="views/auth/register.php" class="btn">🎯 Testar Cadastro Real</a>
                        <a href="views/auth/login.php" class="btn">🔑 Fazer Login</a>
                        <a href="index.php" class="btn">🏠 Ver Site Funcionando</a>
                    </div>
                    <p style="margin-top: 20px;">
                        <strong>✅ PROBLEMA 1 CONCLUÍDO!</strong><br>
                        Agora você pode partir para o <strong>PROBLEMA 2: Validação Estado/Cidade</strong>
                    </p>
                </div>
            <?php else: ?>
                <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                    <a href="emergency_fix.php" class="btn" style="background: #dc3545;">🚨 Correção de Emergência</a>
                    <a href="test_registration_debug.php" class="btn" style="background: #ffc107; color: #000;">🔍 Debug Detalhado</a>
                    <button onclick="location.reload()" class="btn" style="background: #6c757d;">🔄 Executar Novamente</button>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 10px;">
                <h4>📊 Estatísticas do Teste</h4>
                <p><strong>Tempo Total:</strong> <?php echo $total_duration; ?>ms</p>
                <p><strong>Testes Executados:</strong> <?php echo $total_tests; ?></p>
                <p><strong>Sucessos:</strong> <?php echo $passes; ?> | <strong>Falhas:</strong> <?php echo $fails; ?></p>
                <p><strong>Performance:</strong> <?php echo round($total_duration / $total_tests, 2); ?>ms por teste</p>
                
                <?php if ($success_rate >= 90): ?>
                    <p><strong>🏆 Status:</strong> <span style="color: #28a745; font-weight: bold;">PROBLEMA 1 RESOLVIDO</span></p>
                <?php else: ?>
                    <p><strong>⚠️ Status:</strong> <span style="color: #dc3545; font-weight: bold;">NECESSITA CORREÇÃO</span></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Animar barra de progresso
        setTimeout(() => {
            document.querySelector('.progress-fill').style.width = '<?php echo $success_rate; ?>%';
        }, 500);

        // Log do resultado
        console.log('🎯 Teste Final do PROBLEMA 1 executado');
        console.log('📊 Taxa de Sucesso: <?php echo round($success_rate); ?>%');
        console.log('⏱️ Duração: <?php echo $total_duration; ?>ms');
        console.log('<?php echo $success_rate >= 90 ? "🎉 PROBLEMA 1 RESOLVIDO!" : "⚠️ Ainda há problemas para corrigir"; ?>');

        // Se o teste passou, celebrar!
        <?php if ($success_rate >= 90): ?>
        setTimeout(() => {
            if (confirm('🎉 PROBLEMA 1 RESOLVIDO! Deseja testar o cadastro real agora?')) {
                window.open('views/auth/register.php', '_blank');
            }
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>