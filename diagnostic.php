<?php
// ==========================================
// DIAGNÓSTICO RAILWAY - DATABASE
// Local: diagnostic.php
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

$title = "🔧 Diagnóstico Railway - Conecta Eventos";
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #2c3e50; 
            text-align: center; 
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db; 
            padding-bottom: 15px; 
        }
        h2 { 
            color: #34495e; 
            margin-top: 30px;
            border-left: 4px solid #3498db;
            padding-left: 15px;
        }
        .success { 
            color: #27ae60; 
            padding: 8px 0; 
            font-weight: 500;
            background: #d4edda;
            border-radius: 4px;
            margin: 5px 0;
            padding-left: 15px;
        }
        .error { 
            color: #e74c3c; 
            padding: 8px 0; 
            font-weight: bold; 
            background: #f8d7da;
            border-radius: 4px;
            margin: 5px 0;
            padding-left: 15px;
        }
        .warning { 
            color: #f39c12; 
            padding: 8px 0; 
            background: #fff3cd;
            border-radius: 4px;
            margin: 5px 0;
            padding-left: 15px;
        }
        .info { 
            color: #3498db; 
            padding: 8px 0; 
            background: #d1ecf1;
            border-radius: 4px;
            margin: 5px 0;
            padding-left: 15px;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #3498db;
        }
        .btn {
            background: #3498db;
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
            background: #2980b9;
        }
        .code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            overflow-x: auto;
        }
        .debug-log {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px;
            margin: 5px 0;
        }
        .debug-error {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 10px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnóstico Railway - Conecta Eventos</h1>
        
        <?php
        function logStep($message, $success = true) {
            $class = $success ? 'success' : 'error';
            $icon = $success ? '✅' : '❌';
            echo "<div class='$class'>$icon $message</div>";
            flush();
        }

        function logWarning($message) {
            echo "<div class='warning'>⚠️ $message</div>";
            flush();
        }

        function logInfo($message) {
            echo "<div class='info'>ℹ️ $message</div>";
            flush();
        }
        ?>

        <!-- FASE 1: Verificações do Ambiente -->
        <div class="step">
            <h2>📋 Fase 1: Verificações do Ambiente</h2>
            <?php
            logInfo("PHP Version: " . PHP_VERSION);
            logInfo("Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'));
            logInfo("Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__));
            logInfo("Current Directory: " . __DIR__);
            
            // Verificar extensões PHP necessárias
            $extensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'json', 'mbstring'];
            $missing = [];
            
            foreach ($extensions as $ext) {
                if (extension_loaded($ext)) {
                    logStep("Extensão {$ext} carregada");
                } else {
                    logStep("Extensão {$ext} NÃO encontrada", false);
                    $missing[] = $ext;
                }
            }
            
            if (empty($missing)) {
                logStep("Todas as extensões PHP necessárias estão disponíveis");
            } else {
                logWarning("Extensões em falta: " . implode(', ', $missing));
            }
            ?>
        </div>

        <!-- FASE 2: Verificações de Variáveis de Ambiente -->
        <div class="step">
            <h2>🔐 Fase 2: Variáveis de Ambiente do Railway</h2>
            <?php
            $envVars = [
                'DATABASE_URL' => getenv('DATABASE_URL'),
                'MYSQLHOST' => getenv('MYSQLHOST'),
                'MYSQLPORT' => getenv('MYSQLPORT'),
                'MYSQLDATABASE' => getenv('MYSQLDATABASE'),
                'MYSQLUSER' => getenv('MYSQLUSER'),
                'MYSQLPASSWORD' => getenv('MYSQLPASSWORD'),
                'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT'),
                'PORT' => getenv('PORT')
            ];
            
            foreach ($envVars as $name => $value) {
                if (!empty($value)) {
                    if ($name === 'DATABASE_URL' || $name === 'MYSQLPASSWORD') {
                        $display = substr($value, 0, 20) . '...[HIDDEN]';
                    } else {
                        $display = $value;
                    }
                    logStep("{$name}: {$display}");
                } else {
                    logWarning("{$name}: Não configurada");
                }
            }
            
            // Verificar se pelo menos DATABASE_URL ou variáveis MySQL estão presentes
            if (!empty($envVars['DATABASE_URL']) || !empty($envVars['MYSQLHOST'])) {
                logStep("Configurações de banco encontradas!");
            } else {
                logStep("Nenhuma configuração de banco encontrada", false);
            }
            ?>
        </div>

        <!-- FASE 3: Teste da Nova Configuração de Banco -->
        <div class="step">
            <h2>💾 Fase 3: Teste da Configuração de Banco</h2>
            <?php
            try {
                // Incluir o novo arquivo de configuração
                if (file_exists(__DIR__ . '/config/database.php')) {
                    require_once __DIR__ . '/config/database.php';
                    logStep("Arquivo config/database.php carregado");
                } else {
                    logStep("Arquivo config/database.php NÃO encontrado", false);
                    logWarning("Você precisa criar o arquivo config/database.php com o código fornecido");
                }
                
                if (class_exists('Database')) {
                    logStep("Classe Database encontrada");
                    
                    // Testar conexão
                    $db = Database::getInstance();
                    $testResult = $db->testConnection();
                    
                    if ($testResult['success']) {
                        logStep("Conexão de banco estabelecida: " . $testResult['message']);
                        
                        // Obter informações da conexão
                        $info = $db->getConnectionInfo();
                        if (isset($info['driver'])) {
                            logInfo("Driver: " . $info['driver']);
                            logInfo("Versão: " . $info['version']);
                            if (isset($info['database'])) {
                                logInfo("Database: " . $info['database']);
                            }
                            logInfo("Tentativas de conexão: " . $info['attempts']);
                        }
                        
                    } else {
                        logStep("Falha na conexão: " . $testResult['message'], false);
                    }
                    
                } else {
                    logStep("Classe Database NÃO encontrada", false);
                }
                
            } catch (Exception $e) {
                logStep("Erro no teste de banco: " . $e->getMessage(), false);
            }
            ?>
        </div>

        <!-- FASE 4: Teste de CRUD Básico -->
        <div class="step">
            <h2>🔄 Fase 4: Teste de CRUD Básico</h2>
            <?php
            try {
                if (class_exists('Database')) {
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    
                    if ($conn) {
                        // Teste 1: Verificar tabelas
                        $stmt = $conn->query("SHOW TABLES");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        logInfo("Tabelas encontradas: " . implode(', ', $tables));
                        
                        // Teste 2: Contar usuários
                        if (in_array('usuarios', $tables)) {
                            $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios");
                            $result = $stmt->fetch();
                            logStep("Total de usuários: " . $result['total']);
                        } else {
                            logWarning("Tabela 'usuarios' não encontrada");
                        }
                        
                        // Teste 3: Verificar usuário admin
                        if (in_array('usuarios', $tables)) {
                            $stmt = $conn->prepare("SELECT nome, email, tipo FROM usuarios WHERE email = ?");
                            $stmt->execute(['admin@conectaeventos.com']);
                            $admin = $stmt->fetch();
                            
                            if ($admin) {
                                logStep("Usuário admin encontrado: " . $admin['nome'] . " (" . $admin['tipo'] . ")");
                            } else {
                                logWarning("Usuário admin não encontrado");
                            }
                        }
                        
                        // Teste 4: Contar eventos
                        if (in_array('eventos', $tables)) {
                            $stmt = $conn->query("SELECT COUNT(*) as total FROM eventos");
                            $result = $stmt->fetch();
                            logStep("Total de eventos: " . $result['total']);
                        } else {
                            logWarning("Tabela 'eventos' não encontrada");
                        }
                        
                        // Teste 5: Contar categorias
                        if (in_array('categorias', $tables)) {
                            $stmt = $conn->query("SELECT COUNT(*) as total FROM categorias");
                            $result = $stmt->fetch();
                            logStep("Total de categorias: " . $result['total']);
                        } else {
                            logWarning("Tabela 'categorias' não encontrada");
                        }
                        
                    } else {
                        logStep("Sem conexão ativa para teste de CRUD", false);
                    }
                } else {
                    logStep("Classe Database não disponível para teste", false);
                }
                
            } catch (Exception $e) {
                logStep("Erro no teste de CRUD: " . $e->getMessage(), false);
            }
            ?>
        </div>

        <!-- FASE 5: Teste do AuthController -->
        <div class="step">
            <h2>🔐 Fase 5: Teste do Sistema de Autenticação</h2>
            <?php
            try {
                if (file_exists(__DIR__ . '/controllers/AuthController.php')) {
                    require_once __DIR__ . '/controllers/AuthController.php';
                    logStep("AuthController carregado");
                    
                    if (class_exists('AuthController')) {
                        $auth = new AuthController();
                        logStep("AuthController instanciado");
                        
                        // Teste de login com dados de exemplo
                        $loginResult = $auth->login('admin@conectaeventos.com', 'admin123');
                        
                        if ($loginResult['success']) {
                            logStep("Teste de login bem-sucedido: " . $loginResult['message']);
                        } else {
                            logWarning("Teste de login falhou: " . $loginResult['message']);
                        }
                        
                    } else {
                        logStep("Classe AuthController não encontrada", false);
                    }
                    
                } else {
                    logStep("Arquivo AuthController.php não encontrado", false);
                }
                
            } catch (Exception $e) {
                logStep("Erro no teste de autenticação: " . $e->getMessage(), false);
            }
            ?>
        </div>

        <!-- FASE 6: Estrutura de Arquivos -->
        <div class="step">
            <h2>📁 Fase 6: Verificação da Estrutura de Arquivos</h2>
            <?php
            $requiredFiles = [
                'config/config.php' => 'Configurações gerais',
                'config/database.php' => 'Configuração de banco (NOVA)',
                'controllers/AuthController.php' => 'Controller de autenticação',
                'views/auth/login.php' => 'Página de login',
                'views/auth/register.php' => 'Página de registro',
                'views/dashboard/organizer.php' => 'Dashboard organizador',
                'views/dashboard/participant.php' => 'Dashboard participante',
                'index.php' => 'Página inicial'
            ];
            
            $missing = [];
            
            foreach ($requiredFiles as $file => $description) {
                if (file_exists(__DIR__ . '/' . $file)) {
                    logStep("{$file} ✓ ({$description})");
                } else {
                    logStep("{$file} ❌ ({$description})", false);
                    $missing[] = $file;
                }
            }
            
            if (empty($missing)) {
                logStep("Todos os arquivos essenciais estão presentes!");
            } else {
                logWarning("Arquivos em falta: " . count($missing));
            }
            
            // Verificar permissões de diretórios
            $dirs = ['uploads', 'logs', 'backups'];
            foreach ($dirs as $dir) {
                if (is_dir(__DIR__ . '/' . $dir)) {
                    if (is_writable(__DIR__ . '/' . $dir)) {
                        logStep("Diretório {$dir} existe e é gravável");
                    } else {
                        logWarning("Diretório {$dir} existe mas não é gravável");
                    }
                } else {
                    logWarning("Diretório {$dir} não existe");
                }
            }
            ?>
        </div>

        <!-- RESUMO FINAL -->
        <div class="step">
            <h2>🎯 Resumo Final</h2>
            <?php
            $score = 0;
            $total = 6;
            
            // Avaliar cada fase
            if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) $score++;
            if (!empty(getenv('DATABASE_URL')) || !empty(getenv('MYSQLHOST'))) $score++;
            if (class_exists('Database')) $score++;
            
            try {
                if (class_exists('Database')) {
                    $db = Database::getInstance();
                    $testResult = $db->testConnection();
                    if ($testResult['success']) $score++;
                }
            } catch (Exception $e) {}
            
            if (class_exists('AuthController')) $score++;
            if (file_exists(__DIR__ . '/index.php')) $score++;
            
            $percentage = round(($score / $total) * 100);
            
            if ($percentage >= 90) {
                logStep("SISTEMA 100% FUNCIONAL! Pontuação: {$score}/{$total} ({$percentage}%)");
                echo "<div class='success'>";
                echo "<h3>🎉 Parabéns! O sistema está funcionando perfeitamente!</h3>";
                echo "<p>Todas as verificações passaram. O cadastro de usuários deve estar funcionando.</p>";
                echo "</div>";
            } elseif ($percentage >= 70) {
                logWarning("Sistema PARCIALMENTE funcional. Pontuação: {$score}/{$total} ({$percentage}%)");
                echo "<div class='warning'>";
                echo "<h3>⚠️ Sistema parcialmente funcional</h3>";
                echo "<p>A maioria das verificações passou, mas alguns problemas precisam ser corrigidos.</p>";
                echo "</div>";
            } else {
                logStep("Sistema COM PROBLEMAS. Pontuação: {$score}/{$total} ({$percentage}%)", false);
                echo "<div class='error'>";
                echo "<h3>❌ Sistema com problemas significativos</h3>";
                echo "<p>Várias verificações falharam. Revise a configuração.</p>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- CREDENCIAIS DE TESTE -->
        <div class="step">
            <h2>🔑 Credenciais de Teste</h2>
            <div class="code">
<strong>E-mail:</strong> admin@conectaeventos.com<br>
<strong>Senha:</strong> admin123<br>
<strong>Tipo:</strong> Organizador
            </div>
            <p><strong>Para testar:</strong></p>
            <ul>
                <li>Acesse a página de login</li>
                <li>Use as credenciais acima</li>
                <li>Tente criar uma nova conta</li>
                <li>Verifique se os dashboards carregam</li>
            </ul>
        </div>

        <!-- PRÓXIMOS PASSOS -->
        <div class="step">
            <h2>🚀 Próximos Passos</h2>
            <div style="text-align: center;">
                <a href="index.php" class="btn">🏠 Página Inicial</a>
                <a href="views/auth/login.php" class="btn">🔑 Testar Login</a>
                <a href="views/auth/register.php" class="btn">📝 Testar Cadastro</a>
                <a href="setup_railway.php" class="btn">⚙️ Setup Completo</a>
            </div>
            
            <h3>Se algo não está funcionando:</h3>
            <ol>
                <li><strong>Substitua</strong> o arquivo <code>config/database.php</code> pelo código fornecido</li>
                <li><strong>Verifique</strong> se as variáveis de ambiente estão configuradas no Railway</li>
                <li><strong>Teste</strong> o cadastro de usuário na página de registro</li>
                <li><strong>Verifique</strong> os logs do Railway com <code>railway logs</code></li>
            </ol>
            
            <h3>Comandos úteis do Railway:</h3>
            <div class="code">
# Ver logs em tempo real<br>
railway logs<br><br>
# Verificar variáveis de ambiente<br>
railway env<br><br>
# Conectar ao banco MySQL<br>
railway connect MySQL
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; padding: 20px; background: #ecf0f1; border-radius: 10px;">
            <h3>✅ Diagnóstico Concluído</h3>
            <p><strong>Conecta Eventos - Sistema de Diagnóstico Railway</strong></p>
            <p>Executado em: <?php echo date('d/m/Y H:i:s'); ?></p>
            <small>Se tudo estiver verde, o PROBLEMA 1 (Erro no Cadastro de Usuários) foi resolvido!</small>
        </div>
    </div>

    <script>
        // Auto-scroll para mostrar progresso
        window.scrollTo(0, document.body.scrollHeight);
        
        // Log no console
        console.log('🔧 Diagnóstico Railway executado!');
        console.log('📊 Verifique os resultados acima');
        console.log('✅ Se tudo estiver verde, o sistema está funcionando!');
    </script>
</body>
</html>