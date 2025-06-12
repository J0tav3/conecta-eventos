<?php
// ==========================================
// DIAGNÓSTICO COMPLETO DO SISTEMA
// Local: test.php
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico do Sistema - Conecta Eventos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .code-block { background: #f8f9fa; padding: 1rem; border-radius: 0.5rem; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container my-4">
        <h1 class="mb-4">🔧 Diagnóstico do Sistema</h1>
        
        <?php
        $tests = [];
        $overall_status = 'ok';
        
        // ==============================================
        // TESTE 1: Variáveis de Ambiente
        // ==============================================
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>1. Variáveis de Ambiente</h3></div>";
        echo "<div class='card-body'>";
        
        $database_url = getenv('DATABASE_URL');
        if ($database_url) {
            echo "<p class='status-ok'><i class='fas fa-check'></i> DATABASE_URL encontrada</p>";
            $url_parts = parse_url($database_url);
            echo "<div class='code-block'>";
            echo "Host: " . ($url_parts['host'] ?? 'N/A') . "<br>";
            echo "Port: " . ($url_parts['port'] ?? 'N/A') . "<br>";
            echo "Database: " . ltrim($url_parts['path'] ?? '', '/') . "<br>";
            echo "User: " . ($url_parts['user'] ?? 'N/A') . "<br>";
            echo "</div>";
            $tests['env'] = 'ok';
        } else {
            echo "<p class='status-error'><i class='fas fa-times'></i> DATABASE_URL não encontrada</p>";
            $tests['env'] = 'error';
            $overall_status = 'error';
        }
        
        echo "</div></div>";
        
        // ==============================================
        // TESTE 2: Arquivos do Sistema
        // ==============================================
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>2. Arquivos do Sistema</h3></div>";
        echo "<div class='card-body'>";
        
        $required_files = [
            'config/config.php',
            'config/database.php',
            'includes/session.php',
            'controllers/AuthController.php',
            'controllers/EventController.php'
        ];
        
        $files_ok = true;
        foreach ($required_files as $file) {
            if (file_exists($file)) {
                echo "<p class='status-ok'><i class='fas fa-check'></i> $file</p>";
            } else {
                echo "<p class='status-error'><i class='fas fa-times'></i> $file (não encontrado)</p>";
                $files_ok = false;
            }
        }
        
        $tests['files'] = $files_ok ? 'ok' : 'error';
        if (!$files_ok) $overall_status = 'error';
        
        echo "</div></div>";
        
        // ==============================================
        // TESTE 3: Conexão com Banco
        // ==============================================
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>3. Conexão com Banco de Dados</h3></div>";
        echo "<div class='card-body'>";
        
        try {
            if (file_exists('config/database.php')) {
                require_once 'config/database.php';
                
                $database = Database::getInstance();
                $conn = $database->getConnection();
                
                if ($conn) {
                    echo "<p class='status-ok'><i class='fas fa-check'></i> Conexão estabelecida</p>";
                    
                    // Testar query
                    $stmt = $conn->query("SELECT 1 as test");
                    $result = $stmt->fetch();
                    
                    if ($result && $result['test'] == 1) {
                        echo "<p class='status-ok'><i class='fas fa-check'></i> Query de teste OK</p>";
                        $tests['database'] = 'ok';
                    } else {
                        echo "<p class='status-error'><i class='fas fa-times'></i> Query de teste falhou</p>";
                        $tests['database'] = 'error';
                        $overall_status = 'error';
                    }
                } else {
                    echo "<p class='status-error'><i class='fas fa-times'></i> Falha na conexão</p>";
                    $tests['database'] = 'error';
                    $overall_status = 'error';
                }
            } else {
                echo "<p class='status-error'><i class='fas fa-times'></i> Arquivo database.php não encontrado</p>";
                $tests['database'] = 'error';
                $overall_status = 'error';
            }
        } catch (Exception $e) {
            echo "<p class='status-error'><i class='fas fa-times'></i> Erro: " . $e->getMessage() . "</p>";
            $tests['database'] = 'error';
            $overall_status = 'error';
        }
        
        echo "</div></div>";
        
        // ==============================================
        // TESTE 4: Estrutura das Tabelas
        // ==============================================
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>4. Estrutura das Tabelas</h3></div>";
        echo "<div class='card-body'>";
        
        if (isset($conn) && $conn) {
            try {
                $tables = ['usuarios', 'categorias', 'eventos', 'inscricoes', 'favoritos', 'notificacoes'];
                $tables_ok = true;
                
                foreach ($tables as $table) {
                    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Contar registros
                        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM $table");
                        $count_stmt->execute();
                        $count = $count_stmt->fetch()['total'];
                        
                        echo "<p class='status-ok'><i class='fas fa-check'></i> $table ($count registros)</p>";
                    } else {
                        echo "<p class='status-error'><i class='fas fa-times'></i> $table (não existe)</p>";
                        $tables_ok = false;
                    }
                }
                
                $tests['tables'] = $tables_ok ? 'ok' : 'error';
                if (!$tables_ok) $overall_status = 'error';
                
            } catch (Exception $e) {
                echo "<p class='status-error'><i class='fas fa-times'></i> Erro ao verificar tabelas: " . $e->getMessage() . "</p>";
                $tests['tables'] = 'error';
                $overall_status = 'error';
            }
        } else {
            echo "<p class='status-error'><i class='fas fa-times'></i> Sem conexão com banco</p>";
            $tests['tables'] = 'error';
            $overall_status = 'error';
        }
        
        echo "</div></div>";
        
        // ==============================================
        // TESTE 5: Controladores
        // ==============================================
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>5. Controladores</h3></div>";
        echo "<div class='card-body'>";
        
        try {
            if (file_exists('controllers/AuthController.php') && file_exists('controllers/EventController.php')) {
                require_once 'controllers/AuthController.php';
                require_once 'controllers/EventController.php';
                
                // Testar AuthController
                try {
                    $authController = new AuthController();
                    echo "<p class='status-ok'><i class='fas fa-check'></i> AuthController instanciado</p>";
                } catch (Exception $e) {
                    echo "<p class='status-error'><i class='fas fa-times'></i> AuthController erro: " . $e->getMessage() . "</p>";
                    $overall_status = 'error';
                }
                
                // Testar EventController
                try {
                    $eventController = new EventController();
                    echo "<p class='status-ok'><i class='fas fa-check'></i> EventController instanciado</p>";
                    
                    // Testar busca de categorias
                    $categorias = $eventController->getCategories();
                    echo "<p class='status-ok'><i class='fas fa-check'></i> Categorias carregadas: " . count($categorias) . "</p>";
                    
                    // Testar busca de eventos
                    $eventos = $eventController->getPublicEvents(['limite' => 5]);
                    echo "<p class='status-ok'><i class='fas fa-check'></i> Eventos carregados: " . count($eventos) . "</p>";
                    
                    $tests['controllers'] = 'ok';
                } catch (Exception $e) {
                    echo "<p class='status-error'><i class='fas fa-times'></i> EventController erro: " . $e->getMessage() . "</p>";
                    $tests['controllers'] = 'error';
                    $overall_status = 'error';
                }
            } else {
                echo "<p class='status-error'><i class='fas fa-times'></i> Arquivos de controladores não encontrados</p>";
                $tests['controllers'] = 'error';
                $overall_status = 'error';
            }
        } catch (Exception $e) {
            echo "<p class='status-error'><i class='fas fa-times'></i> Erro geral: " . $e->getMessage() . "</p>";
            $tests['controllers'] = 'error';
            $overall_status = 'error';
        }
        
        echo "</div></div>";
        
        // ==============================================
        // TESTE 6: Sessão
        // ==============================================
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>6. Sistema de Sessão</h3></div>";
        echo "<div class='card-body'>";
        
        try {
            if (file_exists('includes/session.php')) {
                require_once 'includes/session.php';
                
                echo "<p class='status-ok'><i class='fas fa-check'></i> session.php carregado</p>";
                
                if (function_exists('isLoggedIn')) {
                    $logged = isLoggedIn();
                    echo "<p class='status-ok'><i class='fas fa-check'></i> Função isLoggedIn() disponível</p>";
                    echo "<p>Status atual: " . ($logged ? 'Logado' : 'Não logado') . "</p>";
                    
                    if ($logged) {
                        echo "<div class='code-block'>";
                        echo "ID: " . (getUserId() ?? 'N/A') . "<br>";
                        echo "Nome: " . (getUserName() ?? 'N/A') . "<br>";
                        echo "Email: " . (getUserEmail() ?? 'N/A') . "<br>";
                        echo "Tipo: " . (getUserType() ?? 'N/A') . "<br>";
                        echo "</div>";
                    }
                    
                    $tests['session'] = 'ok';
                } else {
                    echo "<p class='status-error'><i class='fas fa-times'></i> Funções de sessão não disponíveis</p>";
                    $tests['session'] = 'error';
                    $overall_status = 'error';
                }
            } else {
                echo "<p class='status-error'><i class='fas fa-times'></i> session.php não encontrado</p>";
                $tests['session'] = 'error';
                $overall_status = 'error';
            }
        } catch (Exception $e) {
            echo "<p class='status-error'><i class='fas fa-times'></i> Erro: " . $e->getMessage() . "</p>";
            $tests['session'] = 'error';
            $overall_status = 'error';
        }
        
        echo "</div></div>";
        
        // ==============================================
        // RESUMO FINAL
        // ==============================================
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'>";
        if ($overall_status === 'ok') {
            echo "<h3 class='status-ok'><i class='fas fa-check-circle'></i> Sistema Funcionando</h3>";
        } else {
            echo "<h3 class='status-error'><i class='fas fa-exclamation-circle'></i> Sistema com Problemas</h3>";
        }
        echo "</div>";
        echo "<div class='card-body'>";
        
        if ($overall_status === 'ok') {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Tudo funcionando!</h4>";
            echo "<p>O sistema está configurado corretamente e conectado ao banco de dados.</p>";
            echo "<p><strong>Próximos passos:</strong></p>";
            echo "<ol>";
            echo "<li>Acesse a <a href='database/init.php'>inicialização do banco</a> se ainda não fez</li>";
            echo "<li>Teste o <a href='views/auth/register.php'>cadastro de usuário</a></li>";
            echo "<li>Teste o <a href='views/auth/login.php'>login</a></li>";
            echo "<li>Acesse a <a href='index.php'>página inicial</a></li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h4>❌ Problemas encontrados</h4>";
            echo "<p>Corrija os erros acima antes de usar o sistema.</p>";
            echo "<p><strong>Ações recomendadas:</strong></p>";
            echo "<ol>";
            
            if ($tests['env'] ?? false !== 'ok') {
                echo "<li>Configurar a variável DATABASE_URL no Railway</li>";
            }
            
            if ($tests['database'] ?? false !== 'ok') {
                echo "<li>Executar <a href='database/init.php'>database/init.php</a> para criar tabelas</li>";
            }
            
            if ($tests['files'] ?? false !== 'ok') {
                echo "<li>Verificar se todos os arquivos foram enviados corretamente</li>";
            }
            
            echo "</ol>";
            echo "</div>";
        }
        
        echo "</div></div>";
        
        // ==============================================
        // INFORMAÇÕES TÉCNICAS
        // ==============================================
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h3>Informações Técnicas</h3></div>";
        echo "<div class='card-body'>";
        echo "<div class='row'>";
        
        echo "<div class='col-md-6'>";
        echo "<h5>PHP</h5>";
        echo "<div class='code-block'>";
        echo "Versão: " . PHP_VERSION . "<br>";
        echo "SAPI: " . php_sapi_name() . "<br>";
        echo "Timezone: " . date_default_timezone_get() . "<br>";
        echo "Data/Hora: " . date('Y-m-d H:i:s') . "<br>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='col-md-6'>";
        echo "<h5>Extensões</h5>";
        echo "<div class='code-block'>";
        echo "PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "<br>";
        echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "<br>";
        echo "OpenSSL: " . (extension_loaded('openssl') ? '✅' : '❌') . "<br>";
        echo "cURL: " . (extension_loaded('curl') ? '✅' : '❌') . "<br>";
        echo "</div>";
        echo "</div>";
        
        echo "</div>";
        echo "</div></div>";
        ?>
        
        <div class="text-center">
            <a href="index.php" class="btn btn-primary btn-lg">
                <i class="fas fa-home me-2"></i>Ir para o Site
            </a>
            <button onclick="location.reload()" class="btn btn-secondary btn-lg">
                <i class="fas fa-sync me-2"></i>Recarregar Teste
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>