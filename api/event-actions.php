<?php
// ==========================================
// API PARA AÇÕES DE EVENTOS - VERSÃO ATUALIZADA
// Local: api/event-actions.php
// ==========================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$eventId = $_GET['id'] ?? $_POST['id'] ?? 0;

$eventController = new EventController();
$userId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'get_event':
                    $result = getEventForEdit($eventController, $eventId, $userId);
                    break;
                case 'get_stats':
                    $result = getEventStats($eventController, $eventId, $userId);
                    break;
                default:
                    throw new Exception('Ação GET não reconhecida');
            }
            break;
            
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
                case 'duplicate':
                    $result = duplicateEvent($eventController, $eventId, $userId);
                    break;
                case 'update':
                    $result = updateEvent($eventController, $eventId, $userId, $_POST);
                    break;
                default:
                    throw new Exception('Ação POST não reconhecida');
            }
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $eventId = $input['id'] ?? $eventId;
            $action = $input['action'] ?? $action;
            
            switch ($action) {
                case 'publish':
                    $result = publishEvent($eventController, $eventId, $userId);
                    break;
                case 'unpublish':
                    $result = unpublishEvent($eventController, $eventId, $userId);
                    break;
                case 'update':
                    $result = updateEvent($eventController, $eventId, $userId, $input);
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
 * Buscar evento para edição
 */
function getEventForEdit($eventController, $eventId, $userId) {
    if (!$eventController->canEdit($eventId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para editar este evento'];
    }
    
    $evento = $eventController->getById($eventId);
    if (!$evento) {
        return ['success' => false, 'message' => 'Evento não encontrado'];
    }
    
    $categorias = $eventController->getCategories();
    $stats = $eventController->getEventStats($eventId);
    
    return [
        'success' => true,
        'evento' => $evento,
        'categorias' => $categorias,
        'stats' => $stats
    ];
}

/**
 * Obter estatísticas do evento
 */
function getEventStats($eventController, $eventId, $userId) {
    if (!$eventController->canEdit($eventId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para ver estatísticas deste evento'];
    }
    
    $stats = $eventController->getEventStats($eventId);
    
    return [
        'success' => true,
        'stats' => $stats
    ];
}

/**
 * Atualizar evento
 */
function updateEvent($eventController, $eventId, $userId, $data) {
    if (!$eventController->canEdit($eventId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para editar este evento'];
    }
    
    // Validar dados obrigatórios
    $required_fields = ['titulo', 'descricao', 'data_inicio', 'horario_inicio', 'local_nome', 'local_endereco', 'local_cidade', 'local_estado'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Campo obrigatório: $field"];
        }
    }
    
    // Validar data
    if (strtotime($data['data_inicio']) < strtotime(date('Y-m-d'))) {
        return ['success' => false, 'message' => 'A data do evento deve ser futura'];
    }
    
    // Validar preço se não for gratuito
    if (!isset($data['evento_gratuito']) && (!isset($data['preco']) || $data['preco'] < 0)) {
        return ['success' => false, 'message' => 'Preço deve ser informado para eventos pagos'];
    }
    
    $result = $eventController->update($eventId, $data);
    
    if ($result['success']) {
        // Log da ação
        error_log("Evento atualizado - ID: $eventId, Usuário: $userId");
        
        // Buscar dados atualizados
        $eventoAtualizado = $eventController->getById($eventId);
        $result['evento'] = $eventoAtualizado;
    }
    
    return $result;
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
        
        // Validar se o evento tem dados suficientes para publicação
        $validation = validateEventForPublication($conn, $eventId);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
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
        
        // 4. Excluir logs do evento
        $stmt = $conn->prepare("DELETE FROM event_logs WHERE id_evento = ?");
        $stmt->execute([$eventId]);
        
        // 5. Finalmente, excluir o evento
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

/**
 * Duplicar evento
 */
function duplicateEvent($eventController, $eventId, $userId) {
    if (!$eventController->canEdit($eventId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para duplicar este evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Buscar evento original
        $stmt = $conn->prepare("SELECT * FROM eventos WHERE id_evento = ? AND id_organizador = ?");
        $stmt->execute([$eventId, $userId]);
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$evento) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Evento não encontrado'];
        }
        
        // Preparar dados para duplicação
        unset($evento['id_evento']);
        $evento['titulo'] = $evento['titulo'] . ' (Cópia)';
        $evento['status'] = 'rascunho';
        $evento['data_criacao'] = date('Y-m-d H:i:s');
        $evento['data_atualizacao'] = date('Y-m-d H:i:s');
        
        // Ajustar data para próxima semana
        $evento['data_inicio'] = date('Y-m-d', strtotime($evento['data_inicio'] . ' +1 week'));
        $evento['data_fim'] = date('Y-m-d', strtotime($evento['data_fim'] . ' +1 week'));
        
        // Inserir evento duplicado
        $campos = array_keys($evento);
        $placeholders = ':' . implode(', :', $campos);
        $sql = "INSERT INTO eventos (" . implode(', ', $campos) . ") VALUES (" . $placeholders . ")";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($evento);
        
        if ($result) {
            $newEventId = $conn->lastInsertId();
            $conn->commit();
            
            error_log("Evento duplicado - Original: $eventId, Novo: $newEventId, Organizador: $userId");
            
            return [
                'success' => true,
                'message' => 'Evento duplicado com sucesso!',
                'new_event_id' => $newEventId,
                'new_event_title' => $evento['titulo']
            ];
        } else {
            $conn->rollback();
            return ['success' => false, 'message' => 'Erro ao duplicar evento'];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Erro ao duplicar evento: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}

/**
 * Validar evento para publicação
 */
function validateEventForPublication($conn, $eventId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM eventos WHERE id_evento = ?");
        $stmt->execute([$eventId]);
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$evento) {
            return ['valid' => false, 'message' => 'Evento não encontrado'];
        }
        
        // Validações obrigatórias
        $required_fields = [
            'titulo' => 'Título',
            'descricao' => 'Descrição',
            'data_inicio' => 'Data de início',
            'horario_inicio' => 'Horário de início',
            'local_nome' => 'Nome do local',
            'local_endereco' => 'Endereço',
            'local_cidade' => 'Cidade',
            'local_estado' => 'Estado'
        ];
        
        foreach ($required_fields as $field => $label) {
            if (empty($evento[$field])) {
                return ['valid' => false, 'message' => "Campo obrigatório não preenchido: $label"];
            }
        }
        
        // Validar data futura
        if (strtotime($evento['data_inicio']) < strtotime(date('Y-m-d'))) {
            return ['valid' => false, 'message' => 'A data do evento deve ser futura'];
        }
        
        // Validar preço para eventos pagos
        if (!$evento['evento_gratuito'] && $evento['preco'] <= 0) {
            return ['valid' => false, 'message' => 'Preço deve ser informado para eventos pagos'];
        }
        
        return ['valid' => true, 'message' => 'Evento válido para publicação'];
        
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Erro ao validar evento: ' . $e->getMessage()];
    }
}
?>