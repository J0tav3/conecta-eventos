<?php
// ==========================================
// API PARA EDIÇÃO DE EVENTOS
// Local: api/event-edit.php
// ==========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar se está logado e é organizador
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

require_once '../config/database.php';
require_once '../controllers/EventController.php';

$method = $_SERVER['REQUEST_METHOD'];
$eventController = new EventController();
$userId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            // Buscar dados do evento para edição
            $eventId = $_GET['id'] ?? 0;
            if (!$eventId) {
                throw new Exception('ID do evento é obrigatório');
            }
            
            $evento = $eventController->getById($eventId);
            if (!$evento) {
                throw new Exception('Evento não encontrado');
            }
            
            // Verificar se o usuário pode editar
            if (!$eventController->canEdit($eventId)) {
                throw new Exception('Você não tem permissão para editar este evento');
            }
            
            // Buscar categorias disponíveis
            $categorias = $eventController->getCategories();
            
            echo json_encode([
                'success' => true,
                'evento' => $evento,
                'categorias' => $categorias
            ]);
            break;
            
        case 'PUT':
        case 'POST':
            // Atualizar evento
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $eventId = $input['id'] ?? $_GET['id'] ?? 0;
            if (!$eventId) {
                throw new Exception('ID do evento é obrigatório');
            }
            
            // Verificar se o usuário pode editar
            if (!$eventController->canEdit($eventId)) {
                throw new Exception('Você não tem permissão para editar este evento');
            }
            
            // Validar dados obrigatórios
            $required_fields = ['titulo', 'descricao', 'data_inicio', 'horario_inicio', 'local_nome', 'local_endereco', 'local_cidade', 'local_estado'];
            foreach ($required_fields as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Campo obrigatório: $field");
                }
            }
            
            // Validar data
            if (strtotime($input['data_inicio']) < strtotime(date('Y-m-d'))) {
                throw new Exception('A data do evento deve ser futura');
            }
            
            // Validar preço se não for gratuito
            if (!isset($input['evento_gratuito']) || !$input['evento_gratuito']) {
                if (!isset($input['preco']) || $input['preco'] < 0) {
                    throw new Exception('Preço deve ser informado para eventos pagos');
                }
            }
            
            $result = $eventController->update($eventId, $input);
            
            if ($result['success']) {
                // Buscar dados atualizados
                $eventoAtualizado = $eventController->getById($eventId);
                $result['evento'] = $eventoAtualizado;
            }
            
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Método HTTP não suportado');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>