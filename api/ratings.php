<?php
// ========================================
// API DE AVALIAÇÕES
// ========================================
// Local: api/ratings.php
// ========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$basePath = dirname(__DIR__);
require_once $basePath . '/config/config.php';
require_once $basePath . '/includes/session.php';
require_once $basePath . '/controllers/RatingsController.php';

$ratingsController = new RatingsController();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Criar/Atualizar avaliação
            if (!isLoggedIn()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário não autenticado.'
                ]);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = $input['event_id'] ?? $_POST['event_id'] ?? null;
            $rating = $input['rating'] ?? $_POST['rating'] ?? null;
            $comment = $input['comment'] ?? $_POST['comment'] ?? '';
            
            if (!$eventId || !$rating) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do evento e avaliação são obrigatórios.'
                ]);
                exit();
            }
            
            $result = $ratingsController->rateEvent($eventId, $rating, $comment);
            
            // Adicionar estatísticas atualizadas
            if ($result['success']) {
                $result['stats'] = $ratingsController->getEventRatingStats($eventId);
            }
            
            echo json_encode($result);
            break;
            
        case 'GET':
            $eventId = $_GET['event_id'] ?? null;
            $action = $_GET['action'] ?? 'stats';
            
            switch ($action) {
                case 'stats':
                    // Obter estatísticas de avaliação de um evento
                    if (!$eventId) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'ID do evento é obrigatório.'
                        ]);
                        exit();
                    }
                    
                    $stats = $ratingsController->getEventRatingStats($eventId);
                    
                    echo json_encode([
                        'success' => true,
                        'stats' => $stats
                    ]);
                    break;
                    
                case 'reviews':
                    // Obter reviews de um evento
                    if (!$eventId) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'ID do evento é obrigatório.'
                        ]);
                        exit();
                    }
                    
                    $limit = $_GET['limit'] ?? 10;
                    $offset = $_GET['offset'] ?? 0;
                    
                    $reviews = $ratingsController->getEventReviews($eventId, $limit, $offset);
                    $stats = $ratingsController->getEventRatingStats($eventId);
                    
                    echo json_encode([
                        'success' => true,
                        'reviews' => $reviews,
                        'stats' => $stats
                    ]);
                    break;
                    
                case 'user_rating':
                    // Obter avaliação do usuário para um evento
                    if (!isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Usuário não autenticado.'
                        ]);
                        exit();
                    }
                    
                    if (!$eventId) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'ID do evento é obrigatório.'
                        ]);
                        exit();
                    }
                    
                    $userRating = $ratingsController->getUserRating($eventId);
                    $canRate = $ratingsController->canRateEvent($eventId);
                    
                    echo json_encode([
                        'success' => true,
                        'user_rating' => $userRating,
                        'can_rate' => $canRate
                    ]);
                    break;
                    
                case 'to_rate':
                    // Obter eventos que o usuário pode avaliar
                    if (!isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Usuário não autenticado.'
                        ]);
                        exit();
                    }
                    
                    $events = $ratingsController->getEventsToRate();
                    
                    echo json_encode([
                        'success' => true,
                        'events' => $events
                    ]);
                    break;
                    
                case 'top_rated':
                    // Obter eventos mais bem avaliados
                    $limit = $_GET['limit'] ?? 10;
                    $events = $ratingsController->getTopRatedEvents($limit);
                    
                    echo json_encode([
                        'success' => true,
                        'events' => $events
                    ]);
                    break;
                    
                case 'general_stats':
                    // Estatísticas gerais
                    $stats = $ratingsController->getGeneralStats();
                    
                    echo json_encode([
                        'success' => true,
                        'stats' => $stats
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ação não reconhecida.'
                    ]);
                    break;
            }
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