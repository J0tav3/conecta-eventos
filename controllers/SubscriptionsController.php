<?php
// ========================================
// CORREÇÃO DO MÉTODO subscribe
// ========================================
// Substitua este método em controllers/SubscriptionsController.php
// ========================================

/**
 * Inscrever participante em evento (versão corrigida)
 */
public function subscribe($eventId, $observations = '') {
    if (!isLoggedIn() || !isParticipant()) {
        return [
            'success' => false,
            'message' => 'Apenas participantes logados podem se inscrever em eventos.'
        ];
    }
    
    $participantId = getUserId();
    
    // Verificar se o evento existe e está publicado
    $stmt = $this->conn->prepare("
        SELECT e.*, 
               (SELECT COUNT(*) FROM inscricoes i WHERE i.id_evento = e.id_evento AND i.status = 'confirmada') as total_inscritos
        FROM eventos e 
        WHERE e.id_evento = ? AND e.status = 'publicado'
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Evento não encontrado ou não está disponível para inscrições.'
        ];
    }
    
    // Verificar se o evento já passou
    if (strtotime($event['data_inicio']) < time()) {
        return [
            'success' => false,
            'message' => 'Não é possível se inscrever em eventos que já começaram.'
        ];
    }
    
    // Verificar se o participante não é o organizador
    if ($event['id_organizador'] == $participantId) {
        return [
            'success' => false,
            'message' => 'Organizadores não podem se inscrever em seus próprios eventos.'
        ];
    }
    
    // VERIFICAÇÃO MELHORADA: Verificar inscrição existente
    $stmt = $this->conn->prepare("
        SELECT status FROM inscricoes 
        WHERE id_participante = ? AND id_evento = ?
        ORDER BY data_inscricao DESC
        LIMIT 1
    ");
    $stmt->execute([$participantId, $eventId]);
    $existingSubscription = $stmt->fetch();
    
    if ($existingSubscription) {
        if ($existingSubscription['status'] === 'confirmada') {
            return [
                'success' => false,
                'message' => 'Você já está inscrito neste evento.'
            ];
        } elseif ($existingSubscription['status'] === 'pendente') {
            return [
                'success' => false,
                'message' => 'Você já tem uma inscrição pendente neste evento.'
            ];
        } elseif ($existingSubscription['status'] === 'cancelada') {
            // Se foi cancelada, reativar a inscrição
            try {
                $stmt = $this->conn->prepare("
                    UPDATE inscricoes 
                    SET status = 'confirmada', observacoes = ?, data_inscricao = CURRENT_TIMESTAMP
                    WHERE id_participante = ? AND id_evento = ?
                ");
                
                $result = $stmt->execute([$observations, $participantId, $eventId]);
                
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Inscrição reativada com sucesso!'
                    ];
                }
            } catch (PDOException $e) {
                return [
                    'success' => false,
                    'message' => 'Erro ao reativar inscrição: ' . $e->getMessage()
                ];
            }
        }
    }
    
    // Verificar capacidade máxima
    if ($event['capacidade_maxima'] && $event['total_inscritos'] >= $event['capacidade_maxima']) {
        return [
            'success' => false,
            'message' => 'Este evento já atingiu sua capacidade máxima.'
        ];
    }
    
    try {
        // Criar nova inscrição
        $stmt = $this->conn->prepare("
            INSERT INTO inscricoes (id_evento, id_participante, observacoes, status)
            VALUES (?, ?, ?, 'confirmada')
        ");
        
        $result = $stmt->execute([$eventId, $participantId, $observations]);
        
        if ($result) {
            // Criar notificação para o participante
            if (class_exists('NotificationsController')) {
                $notificationsController = new NotificationsController();
                $notificationsController->notifySubscriptionConfirmed(
                    $participantId, 
                    $eventId, 
                    $event['titulo']
                );
            }
            
            return [
                'success' => true,
                'message' => 'Inscrição realizada com sucesso!'
            ];
        }
        
    } catch (PDOException $e) {
        // Se ainda der erro de duplicata, significa que houve condição de corrida
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return [
                'success' => false,
                'message' => 'Você já está inscrito neste evento.'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao realizar inscrição: ' . $e->getMessage()
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Erro desconhecido ao realizar inscrição.'
    ];
}
?>