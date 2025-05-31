<?php
// ========================================
// CONTROLLER DE NOTIFICAÇÕES
// ========================================
// Local: controllers/NotificationsController.php
// ========================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

class NotificationsController {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Criar nova notificação
     */
    public function create($userId, $titulo, $mensagem, $tipo = 'sistema', $idReferencia = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notificacoes (id_usuario, titulo, mensagem, tipo, id_referencia) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$userId, $titulo, $mensagem, $tipo, $idReferencia]);
            
            if ($result) {
                return [
                    'success' => true,
                    'notification_id' => $this->conn->lastInsertId()
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar notificação: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao criar notificação.'];
    }
    
    /**
     * Obter notificações do usuário
     */
    public function getUserNotifications($userId = null, $limit = 50, $onlyUnread = false) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        if (!$userId) {
            return [];
        }
        
        $where = "id_usuario = ?";
        $params = [$userId];
        
        if ($onlyUnread) {
            $where .= " AND lida = 0";
        }
        
        $query = "SELECT * FROM notificacoes 
                  WHERE $where 
                  ORDER BY data_criacao DESC 
                  LIMIT ?";
        
        $params[] = $limit;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Marcar notificação como lida
     */
    public function markAsRead($notificationId, $userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        try {
            $stmt = $this->conn->prepare("
                UPDATE notificacoes 
                SET lida = 1, data_leitura = CURRENT_TIMESTAMP 
                WHERE id_notificacao = ? AND id_usuario = ?
            ");
            
            $result = $stmt->execute([$notificationId, $userId]);
            
            return [
                'success' => $result && $stmt->rowCount() > 0,
                'message' => $result ? 'Notificação marcada como lida.' : 'Notificação não encontrada.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao marcar como lida: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Marcar todas as notificações como lidas
     */
    public function markAllAsRead($userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        try {
            $stmt = $this->conn->prepare("
                UPDATE notificacoes 
                SET lida = 1, data_leitura = CURRENT_TIMESTAMP 
                WHERE id_usuario = ? AND lida = 0
            ");
            
            $result = $stmt->execute([$userId]);
            $affected = $stmt->rowCount();
            
            return [
                'success' => $result,
                'message' => "Foram marcadas $affected notificações como lidas.",
                'affected_rows' => $affected
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao marcar todas como lidas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Contar notificações não lidas
     */
    public function countUnread($userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        if (!$userId) {
            return 0;
        }
        
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE id_usuario = ? AND lida = 0");
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Excluir notificação
     */
    public function delete($notificationId, $userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        try {
            $stmt = $this->conn->prepare("DELETE FROM notificacoes WHERE id_notificacao = ? AND id_usuario = ?");
            $result = $stmt->execute([$notificationId, $userId]);
            
            return [
                'success' => $result && $stmt->rowCount() > 0,
                'message' => $result ? 'Notificação excluída.' : 'Notificação não encontrada.'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir notificação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Notificações específicas para eventos
     */
    
    /**
     * Notificar sobre novo evento
     */
    public function notifyNewEvent($eventId, $eventTitle) {
        // Notificar todos os participantes ativos
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE tipo = 'participante' AND ativo = 1");
        $stmt->execute();
        $participants = $stmt->fetchAll();
        
        $titulo = "Novo evento disponível!";
        $mensagem = "O evento '$eventTitle' foi publicado. Confira e se inscreva!";
        
        $notificationsCreated = 0;
        foreach ($participants as $participant) {
            $result = $this->create($participant['id_usuario'], $titulo, $mensagem, 'evento', $eventId);
            if ($result['success']) {
                $notificationsCreated++;
            }
        }
        
        return [
            'success' => true,
            'notifications_created' => $notificationsCreated
        ];
    }
    
    /**
     * Notificar sobre inscrição confirmada
     */
    public function notifySubscriptionConfirmed($userId, $eventId, $eventTitle) {
        $titulo = "Inscrição confirmada!";
        $mensagem = "Sua inscrição no evento '$eventTitle' foi confirmada. Não esqueça de comparecer!";
        
        return $this->create($userId, $titulo, $mensagem, 'inscricao', $eventId);
    }
    
    /**
     * Notificar sobre evento próximo
     */
    public function notifyUpcomingEvent($userId, $eventId, $eventTitle, $eventDate) {
        $titulo = "Evento próximo!";
        $mensagem = "O evento '$eventTitle' acontecerá em $eventDate. Prepare-se!";
        
        return $this->create($userId, $titulo, $mensagem, 'evento', $eventId);
    }
    
    /**
     * Notificar sobre cancelamento de evento
     */
    public function notifyEventCancelled($eventId, $eventTitle) {
        // Buscar todos os inscritos no evento
        $stmt = $this->conn->prepare("
            SELECT DISTINCT i.id_participante 
            FROM inscricoes i 
            WHERE i.id_evento = ? AND i.status = 'confirmada'
        ");
        $stmt->execute([$eventId]);
        $participants = $stmt->fetchAll();
        
        $titulo = "Evento cancelado";
        $mensagem = "Infelizmente o evento '$eventTitle' foi cancelado. Lamentamos o inconveniente.";
        
        $notificationsCreated = 0;
        foreach ($participants as $participant) {
            $result = $this->create($participant['id_participante'], $titulo, $mensagem, 'evento', $eventId);
            if ($result['success']) {
                $notificationsCreated++;
            }
        }
        
        return [
            'success' => true,
            'notifications_created' => $notificationsCreated
        ];
    }
    
    /**
     * Limpeza automática de notificações antigas
     */
    public function cleanOldNotifications($daysOld = 30) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notificacoes 
                WHERE data_criacao < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY) 
                AND lida = 1
            ");
            
            $result = $stmt->execute([$daysOld]);
            $affected = $stmt->rowCount();
            
            return [
                'success' => $result,
                'message' => "Foram removidas $affected notificações antigas.",
                'deleted_count' => $affected
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro na limpeza: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Formatar notificação para exibição
     */
    public function formatNotification($notification) {
        $notification['data_criacao_formatada'] = date('d/m/Y H:i', strtotime($notification['data_criacao']));
        
        // Adicionar ícone baseado no tipo
        $icons = [
            'evento' => 'fas fa-calendar-alt',
            'inscricao' => 'fas fa-ticket-alt',
            'sistema' => 'fas fa-cog',
            'avaliacao' => 'fas fa-star'
        ];
        
        $notification['icone'] = $icons[$notification['tipo']] ?? 'fas fa-bell';
        
        // Adicionar classe de cor
        $colors = [
            'evento' => 'primary',
            'inscricao' => 'success',
            'sistema' => 'info',
            'avaliacao' => 'warning'
        ];
        
        $notification['cor'] = $colors[$notification['tipo']] ?? 'secondary';
        
        return $notification;
    }
}
?>