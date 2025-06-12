<?php
// ========================================
// DIAGNÓSTICO COMPLETO DO SISTEMA
// ========================================
// Local: debug_system.php (na raiz do projeto)
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => [],
    'database' => [],
    'files' => [],
    'session' => [],
    'auth' => [],
    'errors' => []
];

try {
    // 1. VERIFICAR AMBIENTE
    $debug['environment'] = [
        'php_version' => PHP_VERSION,
        'railway_env' => $_ENV['RAILWAY_ENVIRONMENT'] ?? 'not_set',
        'database_url' => !empty($_ENV['DATABASE_URL']) ? 'SET' : 'NOT_SET',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
    ];

    // 2. TESTAR BANCO DE DADOS
    $database_url = getenv('DATABASE_URL');
    if ($database_url) {
        $url_parts = parse_url($database_url);
        
        $debug['database']['url_parsed'] = [
            'host' => $url_parts['host'] ?? 'missing',
            'port' => $url_parts['port'] ?? 'missing',
            'dbname' => ltrim($url_parts['path'] ?? '', '/'),
            'user' => $url_parts['user'] ?? 'missing'
        ];

        try {
            $host = $url_parts['host'];
            $port = $url_parts['port'] ?? 3306;
            $dbname = ltrim($url_parts['path'] ?? '', '/');
            $username = $url_parts['user'];
            $password = $url_parts['pass'];

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $debug['database']['connection'] = 'SUCCESS';
            
            // Testar query simples
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            $debug['database']['query_test'] = $result ? 'SUCCESS' : 'FAILED';

            // Verificar tabelas
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $debug['database']['tables'] = $tables;

            // Verificar usuários na tabela
            if (in_array('usuarios', $tables)) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuarios");
                $count = $stmt->fetch();
                $debug['database']['users_count'] = $count['count'];

                // Verificar estrutura da tabela usuarios
                $stmt = $pdo->query("DESCRIBE usuarios");
                $structure = $stmt->fetchAll();
                $debug['database']['users_structure'] = $structure;

                // Verificar últimos usuários criados
                $stmt = $pdo->query("SELECT id_usuario, nome, email, tipo, data_criacao FROM usuarios ORDER BY data_criacao DESC LIMIT 3");
                $recent_users = $stmt->fetchAll();
                $debug['database']['recent_users'] = $recent_users;
            }

            // Verificar eventos na tabela
            if (in_array('eventos', $tables)) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM eventos");
                $count = $stmt->fetch();
                $debug['database']['events_count'] = $count['count'];

                // Verificar últimos eventos
                $stmt = $pdo->query("SELECT id_evento, titulo, status, data_criacao FROM eventos ORDER BY data_criacao DESC LIMIT 3");
                $recent_events = $stmt->fetchAll();
                $debug['database']['recent_events'] = $recent_events;
            }

        } catch (Exception $e) {
            $debug['database']['connection'] = 'FAILED';
            $debug['database']['error'] = $e->getMessage();
        }
    } else {
        $debug['database']['connection'] = 'NO_DATABASE_URL';
    }

    // 3. VERIFICAR ARQUIVOS IMPORTANTES
    $important_files = [
        'config/config.php',
        'config/database.php',
        'includes/session.php',
        'controllers/AuthController.php',
        'controllers/EventController.php',
        'views/auth/login.php',
        'views/auth/register.php'
    ];

    foreach ($important_files as $file) {
        $full_path = __DIR__ . '/' . $file;
        $debug['files'][$file] = [
            'exists' => file_exists($full_path),
            'readable' => is_readable($full_path),
            'size' => file_exists($full_path) ? filesize($full_path) : 0,
            'modified' => file_exists($full_path) ? date('Y-m-d H:i:s', filemtime($full_path)) : null
        ];
    }

    // 4. TESTAR AUTENTICAÇÃO
    if (file_exists(__DIR__ . '/controllers/AuthController.php')) {
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/controllers/AuthController.php';

        try {
            $authController = new AuthController();
            $debug['auth']['controller_loaded'] = true;

            // Testar conexão do AuthController
            $test_result = $authController->testConnection();
            $debug['auth']['connection_test'] = $test_result;

        } catch (Exception $e) {
            $debug['auth']['controller_loaded'] = false;
            $debug['auth']['error'] = $e->getMessage();
        }
    }

    // 5. VERIFICAR CONFIGURAÇÕES PHP
    $debug['php_config'] = [
        'session_started' => session_status() === PHP_SESSION_ACTIVE,
        'session_id' => session_id(),
        'password_hash_available' => function_exists('password_hash'),
        'pdo_available' => class_exists('PDO'),
        'pdo_mysql' => in_array('mysql', PDO::getAvailableDrivers()),
        'error_reporting' => error_reporting(),
        'display_errors' => ini_get('display_errors'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit')
    ];

    // 6. TESTE ESPECÍFICO DE SENHA
    $test_password = 'teste123';
    $hash1 = password_hash($test_password, PASSWORD_DEFAULT);
    $verify1 = password_verify($test_password, $hash1);
    
    $debug['password_test'] = [
        'original' => $test_password,
        'hash' => $hash1,
        'verify' => $verify1
    ];

} catch (Exception $e) {
    $debug['errors'][] = [
        'type' => 'GENERAL_ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

// Log do resultado
error_log("Debug System Result: " . json_encode($debug, JSON_PRETTY_PRINT));

echo json_encode($debug, JSON_PRETTY_PRINT);
?>