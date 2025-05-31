<?php
// ========================================
// DEBUG COMPLETO DA API
// ========================================
// Local: api/debug_api.php
// ========================================

// Capturar todo output antes dos headers
ob_start();

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo json_encode(['status' => 'preflight_ok']);
    exit();
}

$debug_info = [];
$errors = [];

try {
    // 1. Verificar se está logado sem includes
    session_start();
    $debug_info['session_started'] = true;
    $debug_info['session_id'] = session_id();
    $debug_info['session_data'] = $_SESSION ?? [];
    
    // 2. Tentar incluir arquivos um por um
    $files_to_include = [
        'config/config.php',
        'includes/session.php',
        'controllers/FavoritesController.php'
    ];
    
    foreach ($files_to_include as $file) {
        $full_path = dirname(__DIR__) . '/' . $file;
        $debug_info['files'][$file] = [
            'path' => $full_path,
            'exists' => file_exists($full_path),
            'readable' => is_readable($full_path)
        ];
        
        if (file_exists($full_path)) {
            // Capturar output de cada include
            ob_start();
            $include_result = include_once $full_path;
            $include_output = ob_get_clean();
            
            $debug_info['files'][$file]['included'] = true;
            $debug_info['files'][$file]['output'] = $include_output;
            $debug_info['files'][$file]['output_length'] = strlen($include_output);
            
            if (!empty($include_output)) {
                $errors[] = "Arquivo $file produziu output: " . substr($include_output, 0, 100);
            }
        } else {
            $debug_info['files'][$file]['included'] = false;
            $errors[] = "Arquivo $file não encontrado";
        }
    }
    
    // 3. Verificar funções disponíveis
    $debug_info['functions'] = [
        'isLoggedIn' => function_exists('isLoggedIn'),
        'getUserId' => function_exists('getUserId'),
        'class_exists_FavoritesController' => class_exists('FavoritesController')
    ];
    
    // 4. Testar login se possível
    if (function_exists('isLoggedIn')) {
        $debug_info['user'] = [
            'is_logged_in' => isLoggedIn(),
            'user_id' => function_exists('getUserId') ? getUserId() : 'function_not_available',
            'user_name' => function_exists('getUserName') ? getUserName() : 'function_not_available'
        ];
    }
    
    // 5. Capturar qualquer output adicional
    $additional_output = ob_get_contents();
    ob_end_clean();
    
    // 6. Preparar resposta
    $response = [
        'success' => empty($errors),
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'debug_info' => $debug_info,
        'errors' => $errors,
        'additional_output' => $additional_output,
        'additional_output_length' => strlen($additional_output),
        'php_version' => PHP_VERSION,
        'server_info' => [
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not_set',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'not_set',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not_set'
        ]
    ];
    
    // Se tudo OK, tentar operação básica
    if (empty($errors) && class_exists('FavoritesController')) {
        try {
            $favController = new FavoritesController();
            $response['controller_test'] = 'success';
        } catch (Exception $e) {
            $response['controller_test'] = 'error: ' . $e->getMessage();
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
} catch (Error $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'fatal_error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>