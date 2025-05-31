<?php
// ========================================
// API DE FAVORITOS - VERSÃO CORRIGIDA
// ========================================
// Local: api/favorites.php
// ========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Ajustar caminhos baseado na localização
$basePath = dirname(__DIR__);
require_once $basePath . '/config/config.php';
require_once $basePath . '/includes/session.php';
require_once $basePath . '/controllers/FavoritesController.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado.'
    ]);
    exit();
}

$favoritesController = new FavoritesController();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Adicionar/Toggle favorito
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = $input['event_id'] ?? $_POST['event_id'] ?? null;
            $action = $input['action'] ?? $_POST['action'] ?? 'toggle';
            
            if (!$eventId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do evento é obrigatório.'
                ]);
                exit();
            }
            
            switch ($action) {
                case 'add':
                    $result = $favoritesController->addToFavorites($eventId);
                    break;
                case 'remove':
                    $result = $favoritesController->removeFromFavorites($eventId);
                    break;
                case 'toggle':
                default:
                    $result = $favoritesController->toggleFavorite($eventId);
                    break;
            }
            
            // Adicionar informação se está favoritado após a ação
            $result['is_favorite'] = $favoritesController->isFavorite(getUserId(), $eventId);
            $result['total_favorites'] = $favoritesController->getEventFavoriteStats($eventId);
            
            echo json_encode($result);
            break;
            
        case 'GET':
            // Obter favoritos do usuário ou verificar status
            $eventId = $_GET['event_id'] ?? null;
            $userId = getUserId();
            
            if ($eventId) {
                // Verificar se evento específico está nos favoritos
                $isFavorite = $favoritesController->isFavorite($userId, $eventId);
                $totalFavorites = $favoritesController->getEventFavoriteStats($eventId);
                
                echo json_encode([
                    'success' => true,
                    'is_favorite' => $isFavorite,
                    'total_favorites' => $totalFavorites
                ]);
            } else {
                // Obter todos os favoritos do usuário
                $favorites = $favoritesController->getUserFavorites($userId);
                $totalFavorites = $favoritesController->countUserFavorites($userId);
                
                echo json_encode([
                    'success' => true,
                    'favorites' => $favorites,
                    'total' => $totalFavorites
                ]);
            }
            break;
            
        case 'DELETE':
            // Remover favorito
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = $input['event_id'] ?? null;
            
            if (!$eventId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do evento é obrigatório.'
                ]);
                exit();
            }
            
            $result = $favoritesController->removeFromFavorites($eventId);
            $result['is_favorite'] = false;
            $result['total_favorites'] = $favoritesController->getEventFavoriteStats($eventId);
            
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
        'message' => 'Erro interno do servidor: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>