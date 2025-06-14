header('Content-Type: application/json');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'upload_config' => [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ],
    'database' => [
        'url_exists' => !empty(getenv('DATABASE_URL')),
        'url_format' => getenv('DATABASE_URL') ? 'valid' : 'invalid'
    ],
    'directories' => [],
    'session' => []
];

// Verificar/criar diretórios
$dirs = ['/uploads', '/uploads/eventos', '/uploads/profiles'];
foreach ($dirs as $dir) {
    $fullPath = __DIR__ . $dir;
    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
    $debug['directories'][$dir] = [
        'exists' => file_exists($fullPath),
        'writable' => is_writable($fullPath)
    ];
}

// Testar sessão
session_start();
$debug['session'] = [
    'started' => session_status() === PHP_SESSION_ACTIVE,
    'id' => session_id(),
    'logged_in' => $_SESSION['logged_in'] ?? false,
    'user_type' => $_SESSION['user_type'] ?? null
];

// Testar banco
if (getenv('DATABASE_URL')) {
    try {
        $url_parts = parse_url(getenv('DATABASE_URL'));
        $host = $url_parts['host'] ?? 'localhost';
        $port = $url_parts['port'] ?? 3306;
        $dbname = ltrim($url_parts['path'] ?? '', '/');
        $username = $url_parts['user'] ?? '';
        $password = $url_parts['pass'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        
        $debug['database']['connection'] = 'success';
        
        // Verificar tabela eventos
        $stmt = $pdo->query("SHOW TABLES LIKE 'eventos'");
        $debug['database']['eventos_table_exists'] = $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        $debug['database']['connection'] = 'failed';
        $debug['database']['error'] = $e->getMessage();
    }
}

echo json_encode($debug, JSON_PRETTY_PRINT);