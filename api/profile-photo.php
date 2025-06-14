<?php
// ==========================================
// API DE UPLOAD DE FOTO DE PERFIL
// Local: api/profile-photo.php
// ==========================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado.'
    ]);
    exit();
}

require_once '../config/database.php';
require_once '../handlers/ProfileImageHandler.php';

$profileImageHandler = new ProfileImageHandler();
$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'POST':
            // Upload de nova foto
            if (!isset($_FILES['profile_photo'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhuma imagem foi enviada.'
                ]);
                exit();
            }
            
            $result = $profileImageHandler->uploadProfileImage($_FILES['profile_photo'], $userId);
            echo json_encode($result);
            break;
            
        case 'DELETE':
            // Remover foto de perfil
            $result = $profileImageHandler->removeProfileImage($userId);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método não permitido.'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>