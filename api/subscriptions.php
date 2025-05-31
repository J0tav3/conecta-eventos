<?php
// ========================================
// API DE INSCRIÇÕES
// ========================================
// Local: api/subscriptions.php
// ========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$basePath = dirname(__DIR__);
require_once $basePath . '/config/config.php';
require_once $basePath . '/includes/session.php';
require_once $basePath . '/controllers/SubscriptionsController.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado.'
    ]);
    exit();
}

$subscriptionsController = new SubscriptionsController();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Inscrever em evento
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = $input['event_id'] ?? $_POST['event_id'] ?? null;
            $observations = $input['observations'] ?? $_POST['observations'] ?? '';
            
            if (!$eventId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do evento é obrigatório.'
                ]);
                exit();
            }
            
            $result = $subscriptionsController->subscribe($eventId, $observations);
            
            // Adicionar informações atualizadas
            if ($result['success']) {
                $result['is_subscribed'] = true;
                $result['subscription_status'] = $subscriptionsController->getSubscriptionStatus(getUserId(), $eventId);
            }
            
            echo json_encode($result);
            break;
            
        case 'DELETE':
            // Cancelar inscrição
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
            
            $result = $subscriptionsController->unsubscribe($eventId);
            
            if ($result['success']) {
                $result['is_subscribed'] = false;
            }
            
            echo json_encode($result);
            break;
            
        case 'GET':
            $action = $_GET['action'] ?? 'status';
            $eventId = $_GET['event_id'] ?? null;
            
            switch ($action) {
                case 'status':
                    // Verificar status de inscrição
                    if (!$eventId) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'ID do evento é obrigatório.'
                        ]);
                        exit();
                    }
                    
                    $isSubscribed = $subscriptionsController->isSubscribed(getUserId(), $eventId);
                    $subscriptionStatus = $subscriptionsController->getSubscriptionStatus(getUserId(), $eventId);
                    $canSubscribe = $subscriptionsController->canSubscribe($eventId);
                    
                    echo json_encode([
                        'success' => true,
                        'is_subscribed' => $isSubscribed,
                        'subscription_status' => $subscriptionStatus,
                        'can_subscribe' => $canSubscribe['can_subscribe'],
                        'reason' => $canSubscribe['reason']
                    ]);
                    break;
                    
                case 'my_subscriptions':
                    // Obter inscrições do usuário
                    $status = $_GET['status'] ?? null;
                    $subscriptions = $subscriptionsController->getParticipantSubscriptions(getUserId(), $status);
                    
                    echo json_encode([
                        'success' => true,
                        'subscriptions' => $subscriptions,
                        'total' => count($subscriptions)
                    ]);
                    break;
                    
                case 'upcoming':
                    // Próximos eventos do participante
                    $limit = $_GET['limit'] ?? 5;
                    $events = $subscriptionsController->getUpcomingEvents(getUserId(), $limit);
                    
                    echo json_encode([
                        'success' => true,
                        'events' => $events,
                        'total' => count($events)
                    ]);
                    break;
                    
                case 'stats':
                    // Estatísticas do participante
                    $stats = $subscriptionsController->getParticipantStats(getUserId());
                    
                    echo json_encode([
                        'success' => true,
                        'stats' => $stats
                    ]);
                    break;
                    
                case 'event_subscriptions':
                    // Inscrições de um evento (para organizadores)
                    if (!isOrganizer()) {
                        http_response_code(403);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Apenas organizadores podem ver inscrições.'
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
                    
                    $subscriptions = $subscriptionsController->getEventSubscriptions($eventId, getUserId());
                    $stats = $subscriptionsController->getEventSubscriptionStats($eventId);
                    
                    echo json_encode([
                        'success' => true,
                        'subscriptions' => $subscriptions,
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
            
        case 'PUT':
            // Marcar presença (para organizadores)
            if (!isOrganizer()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Apenas organizadores podem marcar presença.'
                ]);
                exit();
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = $input['event_id'] ?? null;
            $participantId = $input['participant_id'] ?? null;
            $present = $input['present'] ?? true;
            
            if (!$eventId || !$participantId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID do evento e do participante são obrigatórios.'
                ]);
                exit();
            }
            
            $result = $subscriptionsController->markAttendance($eventId, $participantId, $present);
            
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