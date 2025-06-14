<?php
// ==========================================
// API PARA AÇÕES DE EVENTOS - VERSÃO MELHORADA
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
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas organizadores podem realizar esta ação.']);
    exit;
}

// Carregar dependências
require_once '../config/database.php';

// Incluir handlers se existirem
if (file_exists('../handlers/ImageUploadHandler.php')) {
    require_once '../handlers/ImageUploadHandler.php';
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$eventId = $_GET['id'] ?? $_POST['id'] ?? 0;
$userId = $_SESSION['user_id'];

// Log da requisição
error_log("[EVENT_ACTION] User: $userId, Action: $action, Event: $eventId, Method: $method");

try {
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'publish':
                    $result = publishEvent($eventId, $userId);
                    break;
                case 'unpublish':
                    $result = unpublishEvent($eventId, $userId);
                    break;
                case 'delete':
                    $result = deleteEvent($eventId, $userId);
                    break;
                case 'duplicate':
                    $result = duplicateEvent($eventId, $userId);
                    break;
                default:
                    throw new Exception('Ação não reconhecida: ' . $action);
            }
            break;
            
        case 'GET':
            switch ($action) {
                case 'get_event':
                    $result = getEventForEdit($eventId, $userId);
                    break;
                case 'get_stats':
                    $result = getEventStats($eventId, $userId);
                    break;
                default:
                    throw new Exception('Ação GET não reconhecida: ' . $action);
            }
            break;
            
        default:
            throw new Exception('Método HTTP não suportado: ' . $method);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("[EVENT_ACTION_ERROR] " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Verificar se usuário pode editar evento
 */
function canEditEvent($eventId, $userId) {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare("SELECT id_organizador FROM eventos WHERE id_evento = ?");
        $stmt->execute([$eventId]);
        $evento = $stmt->fetch();
        
        return $evento && $evento['id_organizador'] == $userId;
    } catch (Exception $e) {
        error_log("Erro ao verificar permissão: " . $e->getMessage());
        return false;
    }
}

/**
 * Publicar evento
 */
function publishEvent($eventId, $userId) {
    if (!canEditEvent($eventId, $userId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para publicar este evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Verificar se o evento existe e seu status atual
        $stmt = $conn->prepare("SELECT titulo, status, data_inicio, local_nome, local_endereco, local_cidade, local_estado, evento_gratuito, preco FROM eventos WHERE id_evento = ? AND id_organizador = ?");
        $stmt->execute([$eventId, $userId]);
        $evento = $stmt->fetch();
        
        if (!$evento) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Evento não encontrado'];
        }
        
        if ($evento['status'] === 'publicado') {
            $conn->rollback();
            return ['success' => false, 'message' => 'Este evento já está publicado'];
        }
        
        // Validações para publicação
        $validation = validateEventForPublication($evento);
        if (!$validation['valid']) {
            $conn->rollback();
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // Atualizar status para publicado
        $stmt = $conn->prepare("UPDATE eventos SET status = 'publicado', data_atualizacao = NOW() WHERE id_evento = ?");
        $result = $stmt->execute([$eventId]);
        
        if ($result) {
            $conn->commit();
            
            // Log da ação
            error_log("[EVENT_PUBLISHED] ID: $eventId, Título: {$evento['titulo']}, Organizador: $userId");
            
            return [
                'success' => true,
                'message' => 'Evento publicado com sucesso! Agora ele está visível para todos os usuários.',
                'event_title' => $evento['titulo'],
                'new_status' => 'publicado'
            ];
        } else {
            $conn->rollback();
            return ['success' => false, 'message' => 'Erro ao publicar evento. Tente novamente.'];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("[PUBLISH_ERROR] " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}

/**
 * Despublicar evento
 */
function unpublishEvent($eventId, $userId) {
    if (!canEditEvent($eventId, $userId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para despublicar este evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Verificar se o evento existe e está publicado
        $stmt = $conn->prepare("SELECT titulo, status FROM eventos WHERE id_evento = ? AND id_organizador = ?");
        $stmt->execute([$eventId, $userId]);
        $evento = $stmt->fetch();
        
        if (!$evento) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Evento não encontrado'];
        }
        
        if ($evento['status'] !== 'publicado') {
            $conn->rollback();
            return ['success' => false, 'message' => 'Este evento não está publicado'];
        }
        
        // Verificar se há inscrições confirmadas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM inscricoes WHERE id_evento = ? AND status = 'confirmada'");
        $stmt->execute([$eventId]);
        $inscricoes = $stmt->fetch();
        
        if ($inscricoes['total'] > 0) {
            $conn->rollback();
            return [
                'success' => false, 
                'message' => "Não é possível despublicar um evento com {$inscricoes['total']} inscrições confirmadas. Considere cancelar o evento."
            ];
        }
        
        // Atualizar status para rascunho
        $stmt = $conn->prepare("UPDATE eventos SET status = 'rascunho', data_atualizacao = NOW() WHERE id_evento = ?");
        $result = $stmt->execute([$eventId]);
        
        if ($result) {
            $conn->commit();
            
            // Log da ação
            error_log("[EVENT_UNPUBLISHED] ID: $eventId, Título: {$evento['titulo']}, Organizador: $userId");
            
            return [
                'success' => true,
                'message' => 'Evento despublicado com sucesso! Ele agora está como rascunho.',
                'event_title' => $evento['titulo'],
                'new_status' => 'rascunho'
            ];
        } else {
            $conn->rollback();
            return ['success' => false, 'message' => 'Erro ao despublicar evento. Tente novamente.'];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("[UNPUBLISH_ERROR] " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}

/**
 * Excluir evento
 */
function deleteEvent($eventId, $userId) {
    if (!canEditEvent($eventId, $userId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para excluir este evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        $conn->beginTransaction();
        
        // Verificar se o evento existe e buscar dados
        $stmt = $conn->prepare("SELECT titulo, status, imagem_capa FROM eventos WHERE id_evento = ? AND id_organizador = ?");
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
                'message' => "Não é possível excluir um evento com {$inscricoes['total']} inscrições confirmadas. Cancele o evento ou entre em contato com os participantes primeiro."
            ];
        }
        
        // Excluir dados relacionados primeiro
        
        // 1. Excluir inscrições
        $stmt = $conn->prepare("DELETE FROM inscricoes WHERE id_evento = ?");
        $stmt->execute([$eventId]);
        
        // 2. Excluir favoritos
        $stmt = $conn->prepare("DELETE FROM favoritos WHERE id_evento = ?");
        $stmt->execute([$eventId]);
        
        // 3. Excluir notificações relacionadas
        $stmt = $conn->prepare("DELETE FROM notificacoes WHERE id_referencia = ? AND tipo IN ('evento', 'event')");
        $stmt->execute([$eventId]);
        
        // 4. Excluir avaliações (se a tabela existir)
        try {
            $stmt = $conn->prepare("DELETE FROM avaliacoes WHERE id_evento = ?");
            $stmt->execute([$eventId]);
        } catch (Exception $e) {
            // Tabela pode não existir
        }
        
        // 5. Excluir logs do evento (se a tabela existir)
        try {
            $stmt = $conn->prepare("DELETE FROM event_logs WHERE id_evento = ?");
            $stmt->execute([$eventId]);
        } catch (Exception $e) {
            // Tabela pode não existir
        }
        
        // 6. Finalmente, excluir o evento
        $stmt = $conn->prepare("DELETE FROM eventos WHERE id_evento = ?");
        $result = $stmt->execute([$eventId]);
        
        if ($result && $stmt->rowCount() > 0) {
            $conn->commit();
            
            // Remover imagem se existir
            if ($evento['imagem_capa']) {
                $imagePath = '../uploads/eventos/' . $evento['imagem_capa'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                    error_log("[IMAGE_DELETED] {$evento['imagem_capa']} removida do evento excluído");
                }
            }
            
            // Log da ação
            error_log("[EVENT_DELETED] ID: $eventId, Título: {$evento['titulo']}, Organizador: $userId");
            
            return [
                'success' => true,
                'message' => 'Evento excluído com sucesso! Todos os dados relacionados foram removidos.',
                'event_title' => $evento['titulo']
            ];
        } else {
            $conn->rollback();
            return ['success' => false, 'message' => 'Erro ao excluir evento. Tente novamente.'];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("[DELETE_ERROR] " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}

/**
 * Duplicar evento
 */
function duplicateEvent($eventId, $userId) {
    if (!canEditEvent($eventId, $userId)) {
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
        
        // Ajustar data para próxima semana se necessário
        $dataOriginal = strtotime($evento['data_inicio']);
        $hoje = time();
        if ($dataOriginal <= $hoje) {
            $evento['data_inicio'] = date('Y-m-d', strtotime('+1 week'));
            if (!empty($evento['data_fim'])) {
                $diferenca = strtotime($evento['data_fim']) - $dataOriginal;
                $evento['data_fim'] = date('Y-m-d', strtotime($evento['data_inicio'] . ' +' . ($diferenca / 86400) . ' days'));
            }
        }
        
        // Remover referência à imagem (não duplicar imagem)
        $evento['imagem_capa'] = null;
        
        // Inserir evento duplicado
        $campos = array_keys($evento);
        $placeholders = ':' . implode(', :', $campos);
        $sql = "INSERT INTO eventos (" . implode(', ', $campos) . ") VALUES (" . $placeholders . ")";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($evento);
        
        if ($result) {
            $newEventId = $conn->lastInsertId();
            $conn->commit();
            
            error_log("[EVENT_DUPLICATED] Original: $eventId, Novo: $newEventId, Organizador: $userId");
            
            return [
                'success' => true,
                'message' => 'Evento duplicado com sucesso! Você pode editá-lo agora.',
                'new_event_id' => $newEventId,
                'new_event_title' => $evento['titulo']
            ];
        } else {
            $conn->rollback();
            return ['success' => false, 'message' => 'Erro ao duplicar evento'];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("[DUPLICATE_ERROR] " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}

/**
 * Buscar evento para edição
 */
function getEventForEdit($eventId, $userId) {
    if (!canEditEvent($eventId, $userId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para editar este evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT e.*, c.nome as nome_categoria, u.nome as nome_organizador
            FROM eventos e
            LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
            LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
            WHERE e.id_evento = ?
        ");
        $stmt->execute([$eventId]);
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$evento) {
            return ['success' => false, 'message' => 'Evento não encontrado'];
        }
        
        return [
            'success' => true,
            'evento' => $evento
        ];
        
    } catch (Exception $e) {
        error_log("[GET_EVENT_ERROR] " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao buscar evento'];
    }
}

/**
 * Obter estatísticas do evento
 */
function getEventStats($eventId, $userId) {
    if (!canEditEvent($eventId, $userId)) {
        return ['success' => false, 'message' => 'Você não tem permissão para ver estatísticas deste evento'];
    }
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco de dados'];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'confirmada' THEN 1 END) as confirmadas,
                COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
                COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as canceladas,
                COUNT(*) as total
            FROM inscricoes 
            WHERE id_evento = ?
        ");
        $stmt->execute([$eventId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        error_log("[GET_STATS_ERROR] " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao buscar estatísticas'];
    }
}

/**
 * Validar evento para publicação
 */
function validateEventForPublication($evento) {
    // Validações obrigatórias para publicação
    $required_fields = [
        'titulo' => 'Título',
        'data_inicio' => 'Data de início',
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
        return ['valid' => false, 'message' => 'A data do evento deve ser futura para publicação'];
    }
    
    // Validar preço para eventos pagos
    if (!$evento['evento_gratuito'] && $evento['preco'] <= 0) {
        return ['valid' => false, 'message' => 'Preço deve ser informado para eventos pagos'];
    }
    
    return ['valid' => true, 'message' => 'Evento válido para publicação'];
}
?>