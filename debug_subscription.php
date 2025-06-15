<?php
// ==========================================
// SCRIPT DE DEBUG PARA SISTEMA DE INSCRIÇÕES
// Local: debug_subscriptions.php
// ==========================================

session_start();
header('Content-Type: application/json');

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_data' => [
        'logged_in' => $_SESSION['logged_in'] ?? false,
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null,
        'user_name' => $_SESSION['user_name'] ?? null
    ],
    'request_data' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'query_string' => $_SERVER['QUERY_STRING'] ?? '',
        'post_data' => $_POST,
        'get_data' => $_GET
    ],
    'system_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? ''
    ],
    'files_check' => [
        'api_exists' => file_exists(__DIR__ . '/api/subscriptions.php'),
        'js_exists' => file_exists(__DIR__ . '/public/js/subscriptions.js'),
        'controller_exists' => file_exists(__DIR__ . '/controllers/EventController.php'),
        'config_exists' => file_exists(__DIR__ . '/config/database.php')
    ]
];

// Testar conexão com banco
$debug_info['database_test'] = ['status' => 'not_tested'];

try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        if ($conn) {
            $debug_info['database_test']['status'] = 'connected';
            
            // Testar se tabelas existem
            $tables = ['usuarios', 'eventos', 'inscricoes', 'categorias'];
            $debug_info['database_test']['tables'] = [];
            
            foreach ($tables as $table) {
                try {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    $debug_info['database_test']['tables'][$table] = [
                        'exists' => true,
                        'count' => $count
                    ];
                } catch (Exception $e) {
                    $debug_info['database_test']['tables'][$table] = [
                        'exists' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
        } else {
            $debug_info['database_test']['status'] = 'connection_failed';
        }
    } else {
        $debug_info['database_test']['status'] = 'config_not_found';
    }
} catch (Exception $e) {
    $debug_info['database_test'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Testar evento específico se ID fornecido
if (isset($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];
    $debug_info['event_test'] = ['event_id' => $eventId];
    
    try {
        if (isset($conn)) {
            $stmt = $conn->prepare("SELECT * FROM eventos WHERE id_evento = ?");
            $stmt->execute([$eventId]);
            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($evento) {
                $debug_info['event_test']['found'] = true;
                $debug_info['event_test']['event_data'] = [
                    'titulo' => $evento['titulo'],
                    'status' => $evento['status'],
                    'data_inicio' => $evento['data_inicio'],
                    'evento_gratuito' => $evento['evento_gratuito'],
                    'capacidade_maxima' => $evento['capacidade_maxima']
                ];
                
                // Verificar inscrições para este evento
                $stmt = $conn->prepare("SELECT COUNT(*) FROM inscricoes WHERE id_evento = ?");
                $stmt->execute([$eventId]);
                $debug_info['event_test']['total_inscricoes'] = $stmt->fetchColumn();
                
                // Verificar se usuário atual está inscrito
                if (isset($_SESSION['user_id'])) {
                    $stmt = $conn->prepare("SELECT * FROM inscricoes WHERE id_evento = ? AND id_participante = ? ORDER BY data_inscricao DESC LIMIT 1");
                    $stmt->execute([$eventId, $_SESSION['user_id']]);
                    $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $debug_info['event_test']['user_subscription'] = $inscricao ? [
                        'inscrito' => true,
                        'status' => $inscricao['status'],
                        'data_inscricao' => $inscricao['data_inscricao']
                    ] : ['inscrito' => false];
                }
            } else {
                $debug_info['event_test']['found'] = false;
            }
        }
    } catch (Exception $e) {
        $debug_info['event_test']['error'] = $e->getMessage();
    }
}

// Testar API de inscrições
$debug_info['api_test'] = [];

if (file_exists(__DIR__ . '/api/subscriptions.php')) {
    $debug_info['api_test']['file_exists'] = true;
    $debug_info['api_test']['file_readable'] = is_readable(__DIR__ . '/api/subscriptions.php');
    $debug_info['api_test']['file_size'] = filesize(__DIR__ . '/api/subscriptions.php');
} else {
    $debug_info['api_test']['file_exists'] = false;
}

// Informações sobre uploads (para imagens)
$debug_info['uploads_info'] = [
    'uploads_dir_exists' => is_dir(__DIR__ . '/uploads'),
    'eventos_uploads_exists' => is_dir(__DIR__ . '/uploads/eventos'),
    'uploads_writable' => is_writable(__DIR__ . '/uploads'),
    'max_upload_size' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// Informações sobre diretórios importantes
$important_dirs = [
    'root' => __DIR__,
    'api' => __DIR__ . '/api',
    'public' => __DIR__ . '/public',
    'views' => __DIR__ . '/views',
    'controllers' => __DIR__ . '/controllers',
    'config' => __DIR__ . '/config'
];

$debug_info['directories'] = [];
foreach ($important_dirs as $name => $path) {
    $debug_info['directories'][$name] = [
        'exists' => is_dir($path),
        'readable' => is_readable($path),
        'writable' => is_writable($path)
    ];
}

// Log errors
$debug_info['recent_errors'] = [];
if (function_exists('error_get_last')) {
    $last_error = error_get_last();
    if ($last_error) {
        $debug_info['recent_errors'][] = $last_error;
    }
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>