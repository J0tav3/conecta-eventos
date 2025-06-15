<?php
// ==========================================
// API PARA INSCRIÇÕES EM EVENTOS - VERSÃO CORRIGIDA
// Local: api/subscriptions.php
// ==========================================

// Configurações de erro e headers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Função para log de debug
function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] API Subscriptions: $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    error_log($logMessage);
}

// Função para resposta JSON
function jsonResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    logDebug('Response sent', $response);
    echo json_encode($response);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

logDebug("API iniciada", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'session' => [
        'logged_in' => $_SESSION['logged_in'] ?? false,
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null
    ],
    'request_data' => [
        'GET' => $_GET,
        'POST' => $_POST,
        'input' => file_get_contents('php://input')
    ]
]);

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    jsonResponse(false, 'Usuário não autenticado. Faça login para se inscrever.', null, 401);
}

// Verificar se é participante (exceto para métodos de consulta)
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'participante')) {
    jsonResponse(false, 'Apenas participantes podem se inscrever em eventos.', null, 403);
}

// Carregar dependências
$conn = null;
try {
    // Tentar carregar configuração de banco
    $configFiles = [
        __DIR__ . '/../config/database.php',
        __DIR__ . '/../config/config.php'
    ];
    
    foreach ($configFiles as $configFile) {
        if (file_exists($configFile)) {
            require_once $configFile;
            logDebug("Config carregado: $configFile");
            break;
        }
    }
    
    // Tentar conectar ao banco
    if (class_exists('Database')) {
        $database = Database::getInstance();
        $conn = $database->getConnection();
        logDebug("Conexão com banco estabelecida");
    } else {
        throw new Exception('Classe Database não encontrada');
    }
    
    if (!$conn) {
        throw new Exception('Falha na conexão com banco de dados');
    }
    
} catch (Exception $e) {
    logDebug("Erro de conexão: " . $e->getMessage());
    jsonResponse(false, 'Erro de conexão com banco de dados: ' . $e->getMessage(), null, 500);
}

// Garantir que a tabela de inscrições existe
try {
    $sql = "CREATE TABLE IF NOT EXISTS inscricoes (
        id_inscricao INT PRIMARY KEY AUTO_INCREMENT,
        id_evento INT NOT NULL,
        id_participante INT NOT NULL,
        data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('confirmada', 'pendente', 'cancelada') DEFAULT 'confirmada',
        observacoes TEXT,
        presente BOOLEAN NULL,
        avaliacao_evento INT CHECK (avaliacao_evento BETWEEN 1 AND 5),
        comentario_avaliacao TEXT,
        data_avaliacao TIMESTAMP NULL,
        INDEX idx_evento_participante (id_evento, id_participante),
        INDEX idx_participante (id_participante),
        INDEX idx_status (status),
        UNIQUE KEY unique_inscricao (id_evento, id_participante)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    logDebug("Tabela inscricoes verificada/criada");
} catch (Exception $e) {
    logDebug("Erro ao criar tabela inscricoes: " . $e->getMessage());
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

logDebug("Processando requisição", ['method' => $method, 'user_id' => $userId]);

try {
    switch ($method) {
        case 'POST':
            $result = handleSubscription($conn, $userId);
            break;
            
        case 'GET':
            $eventId = $_GET['event_id'] ?? null;
            if ($eventId) {
                $result = getSubscriptionStatus($conn, $eventId, $userId);
            } else {
                $result = getUserSubscriptions($conn, $userId);
            }
            break;
            
        case 'PUT':
            $result = updateSubscription($conn, $userId);
            break;
            
        case 'DELETE':
            $result = cancelSubscription($conn, $userId);
            break;
            
        default:
            throw new Exception('Método HTTP não suportado: ' . $method);
    }
    
    jsonResponse($result['success'], $result['message'], $result['data'] ?? null);
    
} catch (Exception $e) {
    logDebug("Erro na API: " . $e->getMessage());
    jsonResponse(false, 'Erro interno: ' . $e->getMessage(), null, 500);
}

/**
 * Processar inscrição em evento
 */
function handleSubscription($conn, $userId) {
    $eventId = $_POST['event_id'] ?? null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    logDebug("Iniciando inscrição", ['event_id' => $eventId, 'user_id' => $userId]);
    
    if (!$eventId) {
        return [
            'success' => false,
            'message' => 'ID do evento é obrigatório'
        ];
    }
    
    try {
        $conn->beginTransaction();
        
        // Verificar se o evento existe e está ativo
        $stmt = $conn->prepare("
            SELECT id_evento, titulo, data_inicio, horario_inicio, capacidade_maxima, 
                   evento_gratuito, preco, status, local_cidade, local_estado,
                   (SELECT COUNT(*) FROM inscricoes WHERE id_evento = ? AND status = 'confirmada') as total_inscritos
            FROM eventos 
            WHERE id_evento = ?
        ");
        $stmt->execute([$eventId, $eventId]);
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$evento) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Evento não encontrado'
            ];
        }
        
        logDebug("Evento encontrado", $evento);
        
        if ($evento['status'] !== 'publicado') {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Este evento não está disponível para inscrições'
            ];
        }
        
        // Verificar se a data não passou
        $dataEvento = strtotime($evento['data_inicio']);
        if ($dataEvento < time()) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Este evento já aconteceu'
            ];
        }
        
        // Verificar se há vagas disponíveis
        if ($evento['capacidade_maxima'] && $evento['total_inscritos'] >= $evento['capacidade_maxima']) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Não há mais vagas disponíveis para este evento'
            ];
        }
        
        // Verificar se já está inscrito
        $stmt = $conn->prepare("
            SELECT status FROM inscricoes 
            WHERE id_evento = ? AND id_participante = ?
            ORDER BY data_inscricao DESC
            LIMIT 1
        ");
        $stmt->execute([$eventId, $userId]);
        $inscricaoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inscricaoExistente) {
            if ($inscricaoExistente['status'] === 'confirmada') {
                $conn->rollback();
                return [
                    'success' => false,
                    'message' => 'Você já está inscrito neste evento'
                ];
            } elseif ($inscricaoExistente['status'] === 'pendente') {
                $conn->rollback();
                return [
                    'success' => false,
                    'message' => 'Você já tem uma inscrição pendente neste evento'
                ];
            }
        }
        
        // Criar nova inscrição
        $stmt = $conn->prepare("
            INSERT INTO inscricoes (id_evento, id_participante, data_inscricao, status, observacoes)
            VALUES (?, ?, NOW(), 'confirmada', ?)
        ");
        
        $result = $stmt->execute([$eventId, $userId, $observacoes]);
        
        if ($result) {
            $inscricaoId = $conn->lastInsertId();
            
            // Criar notificação para o organizador (se possível)
            try {
                $stmt = $conn->prepare("
                    INSERT INTO notificacoes (id_usuario, titulo, mensagem, tipo, id_referencia, data_criacao)
                    SELECT 
                        e.id_organizador,
                        'Nova inscrição',
                        CONCAT('Nova inscrição no evento \"', e.titulo, '\"'),
                        'inscricao',
                        ?,
                        NOW()
                    FROM eventos e 
                    WHERE e.id_evento = ?
                ");
                $stmt->execute([$inscricaoId, $eventId]);
            } catch (Exception $e) {
                logDebug("Erro ao criar notificação: " . $e->getMessage());
                // Não falhar a inscrição por causa da notificação
            }
            
            $conn->commit();
            
            logDebug("Inscrição realizada com sucesso", ['inscricao_id' => $inscricaoId]);
            
            return [
                'success' => true,
                'message' => 'Inscrição realizada com sucesso!',
                'data' => [
                    'inscricao_id' => $inscricaoId,
                    'evento_titulo' => $evento['titulo'],
                    'evento_data' => date('d/m/Y', strtotime($evento['data_inicio'])),
                    'evento_horario' => date('H:i', strtotime($evento['horario_inicio'])),
                    'evento_local' => $evento['local_cidade'] . ', ' . $evento['local_estado'],
                    'status' => 'confirmada'
                ]
            ];
        } else {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Erro ao processar inscrição. Tente novamente.'
            ];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        logDebug("Erro ao processar inscrição: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro interno ao processar inscrição'
        ];
    }
}

/**
 * Verificar status de inscrição
 */
function getSubscriptionStatus($conn, $eventId, $userId) {
    logDebug("Verificando status de inscrição", ['event_id' => $eventId, 'user_id' => $userId]);
    
    try {
        $stmt = $conn->prepare("
            SELECT i.*, e.titulo, e.data_inicio, e.horario_inicio, e.local_cidade, e.local_estado
            FROM inscricoes i
            JOIN eventos e ON i.id_evento = e.id_evento
            WHERE i.id_evento = ? AND i.id_participante = ? AND i.status != 'cancelada'
            ORDER BY i.data_inscricao DESC
            LIMIT 1
        ");
        $stmt->execute([$eventId, $userId]);
        $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inscricao && $inscricao['status'] === 'confirmada') {
            logDebug("Inscrição CONFIRMADA encontrada", $inscricao);
            return [
                'success' => true,
                'subscribed' => true,
                'data' => [
                    'status' => $inscricao['status'],
                    'data_inscricao' => $inscricao['data_inscricao'],
                    'observacoes' => $inscricao['observacoes'],
                    'evento_titulo' => $inscricao['titulo'],
                    'id_inscricao' => $inscricao['id_inscricao']
                ]
            ];
        } else {
            logDebug("Nenhuma inscrição CONFIRMADA encontrada", ['inscricao_encontrada' => $inscricao ? 'sim' : 'nao']);
            return [
                'success' => true,
                'subscribed' => false,
                'data' => null
            ];
        }
        
    } catch (Exception $e) {
        logDebug("Erro ao verificar status de inscrição: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro ao verificar status de inscrição'
        ];
    }
}

/**
 * Obter todas as inscrições do usuário
 */
function getUserSubscriptions($conn, $userId) {
    logDebug("Buscando inscrições do usuário", ['user_id' => $userId]);
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                i.*,
                e.titulo,
                e.descricao,
                e.data_inicio,
                e.horario_inicio,
                e.local_nome,
                e.local_cidade,
                e.local_estado,
                e.evento_gratuito,
                e.preco,
                e.status as evento_status,
                e.imagem_capa,
                u.nome as organizador_nome
            FROM inscricoes i
            JOIN eventos e ON i.id_evento = e.id_evento
            JOIN usuarios u ON e.id_organizador = u.id_usuario
            WHERE i.id_participante = ?
            ORDER BY i.data_inscricao DESC
        ");
        $stmt->execute([$userId]);
        $inscricoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logDebug("Inscrições encontradas", ['count' => count($inscricoes)]);
        
        return [
            'success' => true,
            'data' => $inscricoes
        ];
        
    } catch (Exception $e) {
        logDebug("Erro ao buscar inscrições do usuário: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro ao buscar inscrições'
        ];
    }
}

/**
 * Cancelar inscrição
 */
function cancelSubscription($conn, $userId) {
    // Decodificar dados JSON do DELETE
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['event_id'] ?? $_POST['event_id'] ?? null;
    
    logDebug("Cancelando inscrição", ['event_id' => $eventId, 'user_id' => $userId]);
    
    if (!$eventId) {
        return [
            'success' => false,
            'message' => 'ID do evento é obrigatório'
        ];
    }
    
    try {
        $conn->beginTransaction();
        
        // Verificar se a inscrição existe
        $stmt = $conn->prepare("
            SELECT i.*, e.titulo, e.data_inicio
            FROM inscricoes i
            JOIN eventos e ON i.id_evento = e.id_evento
            WHERE i.id_evento = ? AND i.id_participante = ? AND i.status = 'confirmada'
        ");
        $stmt->execute([$eventId, $userId]);
        $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$inscricao) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Inscrição não encontrada ou já cancelada'
            ];
        }
        
        // Verificar se pode cancelar (ex: até 24h antes do evento)
        $dataEvento = strtotime($inscricao['data_inicio']);
        $agora = time();
        $diferencaHoras = ($dataEvento - $agora) / 3600;
        
        if ($diferencaHoras < 24) {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Não é possível cancelar inscrições com menos de 24 horas de antecedência'
            ];
        }
        
        // Atualizar status da inscrição
        $stmt = $conn->prepare("
            UPDATE inscricoes 
            SET status = 'cancelada', data_atualizacao = NOW()
            WHERE id_inscricao = ?
        ");
        
        $result = $stmt->execute([$inscricao['id_inscricao']]);
        
        if ($result) {
            $conn->commit();
            
            logDebug("Inscrição cancelada com sucesso", ['inscricao_id' => $inscricao['id_inscricao']]);
            
            return [
                'success' => true,
                'message' => 'Inscrição cancelada com sucesso',
                'data' => [
                    'evento_titulo' => $inscricao['titulo']
                ]
            ];
        } else {
            $conn->rollback();
            return [
                'success' => false,
                'message' => 'Erro ao cancelar inscrição'
            ];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        logDebug("Erro ao cancelar inscrição: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro interno ao cancelar inscrição'
        ];
    }
}

/**
 * Atualizar inscrição (marcar presença, etc)
 */
function updateSubscription($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['event_id'] ?? null;
    $presente = $input['present'] ?? null;
    
    logDebug("Atualizando inscrição", ['event_id' => $eventId, 'user_id' => $userId, 'presente' => $presente]);
    
    if (!$eventId) {
        return [
            'success' => false,
            'message' => 'ID do evento é obrigatório'
        ];
    }
    
    try {
        // Verificar se a inscrição existe
        $stmt = $conn->prepare("
            SELECT id_inscricao FROM inscricoes 
            WHERE id_evento = ? AND id_participante = ? AND status = 'confirmada'
        ");
        $stmt->execute([$eventId, $userId]);
        $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$inscricao) {
            return [
                'success' => false,
                'message' => 'Inscrição não encontrada'
            ];
        }
        
        // Atualizar presença se fornecida
        if ($presente !== null) {
            $stmt = $conn->prepare("
                UPDATE inscricoes 
                SET presente = ?, data_atualizacao = NOW()
                WHERE id_inscricao = ?
            ");
            $result = $stmt->execute([$presente ? 1 : 0, $inscricao['id_inscricao']]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Presença atualizada com sucesso'
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Nenhuma atualização realizada'
        ];
        
    } catch (Exception $e) {
        logDebug("Erro ao atualizar inscrição: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro interno ao atualizar inscrição'
        ];
    }
}
?>