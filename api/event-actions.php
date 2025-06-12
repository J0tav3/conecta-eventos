<?php
// ==========================================
// API PARA AÇÕES DE EVENTOS
// Local: api/event-actions.php
// ==========================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se está logado e é organizador
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
$action = $_GET['action'] ?? '';
$eventId = $_GET['id'] ?? $_POST['id'] ?? 0;

$eventController = new EventController();
$userId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'publish':
                    $result = publishEvent($eventController, $eventId, $userId);
                    break;
                case 'unpublish':
                    $result = unpublishEvent($eventController, $eventId, $userId);
                    break;
                case 'delete':
                    $result = deleteEvent($eventController, $eventId, $userId);
                    break;
                default:
                    throw new Exception('Ação não reconhecida');
            }
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = $input['id'] ?? 0;
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'publish':
                    $result = publishEvent($eventController, $eventId, $userId);
                    break;
                case 'unpublish':
                    $result = unpublishEvent($eventController, $eventId, $userId);
                    break;
                default:
                    throw new Exception('Ação PUT não reconhecida');
            }
            break;
            
        case 'DELETE':
            $result = deleteEvent($eventController, $eventId, $userId);
            break;
            
        default:
            throw new Exception('Método HTTP não suportado');
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Publicar evento
 */
function publishEvent($eventController, $eventId, $userId) {
    if (!$eventController->canEdit($eventId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para publicar este evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        // Verificar se o evento existe e está em rascunho
        $stmt = $conn->prepare("SELECT titulo, status FROM eventos WHERE id_evento = ? AND id_organizador = ?");
        $stmt->execute([$eventId, $userId]);
        $evento = $stmt->fetch();
        
        if (!$evento) {
            return ['success' => false, 'message' => 'Evento não encontrado'];
        }
        
        if ($evento['status'] === 'publicado') {
            return ['success' => false, 'message' => 'Este evento já está publicado'];
        }
        
        // Atualizar status para publicado
        $stmt = $conn->prepare("UPDATE eventos SET status = 'publicado', data_atualizacao = NOW() WHERE id_evento = ?");
        $result = $stmt->execute([$eventId]);
        
        if ($result) {
            // Log da ação
            error_log("Evento publicado - ID: $eventId, Título: {$evento['titulo']}, Organizador: $userId");
            
            return [
                'success' => true,
                'message' => 'Evento publicado com sucesso!',
                'event_title' => $evento['titulo']
            ];
        } else {
            return ['success' => false, 'message' => 'Erro ao publicar evento'];
        }
        
    } catch (Exception $e) {
        error_log("Erro ao publicar evento: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}

/**
 * Despublicar evento
 */
function unpublishEvent($eventController, $eventId, $userId) {
    if (!$eventController->canEdit($eventId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para despublicar este evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        // Verificar se o evento existe e está publicado
        $stmt = $conn->prepare("SELECT titulo, status FROM eventos WHERE id_evento = ? AND id_organizador = ?");
        $stmt->execute([$eventId, $userId]);
        $evento = $stmt->fetch();
        
        if (!$evento) {
            return ['success' => false, 'message' => 'Evento não encontrado'];
        }
        
        if ($evento['status'] !== 'publicado') {
            return ['success' => false, 'message' => 'Este evento não está publicado'];
        }
        
        // Verificar se há inscrições confirmadas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM inscricoes WHERE id_evento = ? AND status = 'confirmada'");
        $stmt->execute([$eventId]);
        $inscricoes = $stmt->fetch();
        
        if ($inscricoes['total'] > 0) {
            return [
                'success' => false, 
                'message' => "Não é possível despublicar um evento com {$inscricoes['total']} inscrições confirmadas"
            ];
        }
        
        // Atualizar status para rascunho
        $stmt = $conn->prepare("UPDATE eventos SET status = 'rascunho', data_atualizacao = NOW() WHERE id_evento = ?");
        $result = $stmt->execute([$eventId]);
        
        if ($result) {
            // Log da ação
            error_log("Evento despublicado - ID: $eventId, Título: {$evento['titulo']}, Organizador: $userId");
            
            return [
                'success' => true,
                'message' => 'Evento despublicado com sucesso!',
                'event_title' => $evento['titulo']
            ];
        } else {
            return ['success' => false, 'message' => 'Erro ao despublicar evento'];
        }
        
    } catch (Exception $e) {
        error_log("Erro ao despublicar evento: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}

/**
 * Excluir evento
 */
function deleteEvent($eventController, $eventId, $userId) {
    if (!$eventController->canEdit($eventId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para excluir este evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Verificar se o evento existe
        $stmt = $conn->prepare("SELECT titulo, status FROM eventos WHERE id_evento = ? AND id_organizador = ?");
        $stmt->execute([$eventId, $userId]);
        $evento = $stmt->fetch();
        
        if (!$evento) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Evento não encontrado'];
        }
        
        // Verificar se há inscrições confirmadas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM inscricoes WHERE id_evento = ? AND status = 'confirmada'");
        $stmt->execute([$eventId]);
        $inscricoes = $stmt->fetch();
        
        if ($inscricoes['total'] > 0) {
            $conn->rollback();
            return [
                'success' => false, 
                'message' => "Não é possível excluir um evento com {$inscricoes['total']} inscrições confirmadas. Cancele o evento ao invés de excluí-lo."
            ];
        }
        
        // Excluir dados relacionados primeiro (devido às foreign keys)
        
        // 1. Excluir inscrições
        $stmt = $conn->prepare("DELETE FROM inscricoes WHERE id_evento = ?");
        $stmt->execute([$eventId]);
        
        // 2. Excluir favoritos
        $stmt = $conn->prepare("DELETE FROM favoritos WHERE id_evento = ?");
        $stmt->execute([$eventId]);
        
        // 3. Excluir notificações relacionadas
        $stmt = $conn->prepare("DELETE FROM notificacoes WHERE id_referencia = ? AND tipo = 'evento'");
        $stmt->execute([$eventId]);
        
        // 4. Finalmente, excluir o evento
        $stmt = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
        $result = $stmt->execute([$eventId]);
        
        if ($result) {
            $conn->commit();
            
            // Log da ação
            error_log("Evento excluído - ID: $eventId, Título: {$evento['titulo']}, Organizador: $userId");
            
            return [
                'success' => true,
                'message' => 'Evento excluído com sucesso!',
                'event_title' => $evento['titulo']
            ];
        } else {
            $conn->rollback();
            return ['success' => false, 'message' => 'Erro ao excluir evento'];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao excluir evento: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}
?>