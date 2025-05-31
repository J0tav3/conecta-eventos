<?php
// ========================================
// CONTROLLER DE AVALIAÇÕES
// ========================================
// Local: controllers/RatingsController.php
// ========================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

class RatingsController {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Adicionar/Atualizar avaliação de evento
     */
    public function rateEvent($eventId, $rating, $comment = '') {
        if (!isLoggedIn() || !isParticipant()) {
            return [
                'success' => false,
                'message' => 'Apenas participantes podem avaliar eventos.'
            ];
        }
        
        $userId = getUserId();
        
        // Validar rating
        if ($rating < 1 || $rating > 5) {
            return [
                'success' => false,
                'message' => 'A avaliação deve ser entre 1 e 5 estrelas.'
            ];
        }
        
        // Verificar se o usuário participou do evento
        $stmt = $this->conn->prepare("
            SELECT i.id_inscricao, e.data_fim 
            FROM inscricoes i 
            INNER JOIN eventos e ON i.id_evento = e.id_evento
            WHERE i.id_participante = ? AND i.id_evento = ? AND i.status = 'confirmada'
        ");
        $stmt->execute([$userId, $eventId]);
        $inscription = $stmt->fetch();
        
        if (!$inscription) {
            return [
                'success' => false,
                'message' => 'Você só pode avaliar eventos que participou.'
            ];
        }
        
        // Verificar se o evento já terminou
        if (strtotime($inscription['data_fim']) > time()) {
            return [
                'success' => false,
                'message' => 'Você só pode avaliar eventos que já terminaram.'
            ];
        }
        
        try {
            // Atualizar a avaliação na tabela de inscrições
            $stmt = $this->conn->prepare("
                UPDATE inscricoes 
                SET avaliacao_evento = ?, comentario_avaliacao = ?, data_avaliacao = CURRENT_TIMESTAMP
                WHERE id_participante = ? AND id_evento = ?
            ");
            
            $result = $stmt->execute([$rating, $comment, $userId, $eventId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Avaliação registrada com sucesso!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erro ao registrar avaliação.'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao salvar avaliação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter avaliação do usuário para um evento
     */
    public function getUserRating($eventId, $userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        if (!$userId) {
            return null;
        }
        
        $stmt = $this->conn->prepare("
            SELECT avaliacao_evento, comentario_avaliacao, data_avaliacao
            FROM inscricoes 
            WHERE id_participante = ? AND id_evento = ?
        ");
        $stmt->execute([$userId, $eventId]);
        
        $result = $stmt->fetch();
        
        if ($result && $result['avaliacao_evento']) {
            return [
                'rating' => $result['avaliacao_evento'],
                'comment' => $result['comentario_avaliacao'],
                'date' => $result['data_avaliacao']
            ];
        }
        
        return null;
    }
    
    /**
     * Obter estatísticas de avaliação de um evento
     */
    public function getEventRatingStats($eventId) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_avaliacoes,
                AVG(avaliacao_evento) as media_avaliacoes,
                SUM(CASE WHEN avaliacao_evento = 5 THEN 1 ELSE 0 END) as estrelas_5,
                SUM(CASE WHEN avaliacao_evento = 4 THEN 1 ELSE 0 END) as estrelas_4,
                SUM(CASE WHEN avaliacao_evento = 3 THEN 1 ELSE 0 END) as estrelas_3,
                SUM(CASE WHEN avaliacao_evento = 2 THEN 1 ELSE 0 END) as estrelas_2,
                SUM(CASE WHEN avaliacao_evento = 1 THEN 1 ELSE 0 END) as estrelas_1
            FROM inscricoes 
            WHERE id_evento = ? AND avaliacao_evento IS NOT NULL
        ");
        $stmt->execute([$eventId]);
        
        $stats = $stmt->fetch();
        
        if ($stats['total_avaliacoes'] > 0) {
            $stats['media_formatada'] = number_format($stats['media_avaliacoes'], 1);
            $stats['percentual_5'] = round(($stats['estrelas_5'] / $stats['total_avaliacoes']) * 100);
            $stats['percentual_4'] = round(($stats['estrelas_4'] / $stats['total_avaliacoes']) * 100);
            $stats['percentual_3'] = round(($stats['estrelas_3'] / $stats['total_avaliacoes']) * 100);
            $stats['percentual_2'] = round(($stats['estrelas_2'] / $stats['total_avaliacoes']) * 100);
            $stats['percentual_1'] = round(($stats['estrelas_1'] / $stats['total_avaliacoes']) * 100);
        } else {
            $stats['media_formatada'] = '0.0';
            $stats['percentual_5'] = 0;
            $stats['percentual_4'] = 0;
            $stats['percentual_3'] = 0;
            $stats['percentual_2'] = 0;
            $stats['percentual_1'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Obter avaliações com comentários de um evento
     */
    public function getEventReviews($eventId, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT i.avaliacao_evento, i.comentario_avaliacao, i.data_avaliacao,
                   u.nome as nome_participante
            FROM inscricoes i
            INNER JOIN usuarios u ON i.id_participante = u.id_usuario
            WHERE i.id_evento = ? AND i.avaliacao_evento IS NOT NULL
            ORDER BY i.data_avaliacao DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$eventId, $limit, $offset]);
        
        $reviews = $stmt->fetchAll();
        
        // Formatar dados
        foreach ($reviews as &$review) {
            $review['data_formatada'] = date('d/m/Y', strtotime($review['data_avaliacao']));
            $review['estrelas_html'] = $this->generateStarsHtml($review['avaliacao_evento']);
        }
        
        return $reviews;
    }
    
    /**
     * Obter eventos que o usuário pode avaliar
     */
    public function getEventsToRate($userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        if (!$userId) {
            return [];
        }
        
        $stmt = $this->conn->prepare("
            SELECT e.*, i.data_inscricao, i.avaliacao_evento,
                   u.nome as nome_organizador
            FROM inscricoes i
            INNER JOIN eventos e ON i.id_evento = e.id_evento
            LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
            WHERE i.id_participante = ? 
            AND i.status = 'confirmada'
            AND e.data_fim < NOW()
            ORDER BY e.data_fim DESC
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obter melhores eventos avaliados
     */
    public function getTopRatedEvents($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT e.*, AVG(i.avaliacao_evento) as media_avaliacoes,
                   COUNT(i.avaliacao_evento) as total_avaliacoes,
                   u.nome as nome_organizador,
                   c.nome as nome_categoria
            FROM eventos e
            INNER JOIN inscricoes i ON e.id_evento = i.id_evento
            LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
            LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
            WHERE i.avaliacao_evento IS NOT NULL
            AND e.status = 'publicado'
            GROUP BY e.id_evento
            HAVING COUNT(i.avaliacao_evento) >= 3
            ORDER BY AVG(i.avaliacao_evento) DESC, COUNT(i.avaliacao_evento) DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        $events = $stmt->fetchAll();
        
        foreach ($events as &$event) {
            $event['media_formatada'] = number_format($event['media_avaliacoes'], 1);
            $event['estrelas_html'] = $this->generateStarsHtml($event['media_avaliacoes']);
        }
        
        return $events;
    }
    
    /**
     * Verificar se usuário pode avaliar evento
     */
    public function canRateEvent($eventId, $userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        if (!$userId || !isParticipant()) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as can_rate
            FROM inscricoes i 
            INNER JOIN eventos e ON i.id_evento = e.id_evento
            WHERE i.id_participante = ? AND i.id_evento = ? 
            AND i.status = 'confirmada' AND e.data_fim < NOW()
        ");
        $stmt->execute([$userId, $eventId]);
        
        $result = $stmt->fetch();
        return $result['can_rate'] > 0;
    }
    
    /**
     * Gerar HTML das estrelas
     */
    private function generateStarsHtml($rating, $maxStars = 5) {
        $html = '';
        $rating = round($rating * 2) / 2; // Arredondar para 0.5
        
        for ($i = 1; $i <= $maxStars; $i++) {
            if ($rating >= $i) {
                $html .= '<i class="fas fa-star text-warning"></i>';
            } elseif ($rating >= $i - 0.5) {
                $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
            } else {
                $html .= '<i class="far fa-star text-muted"></i>';
            }
        }
        
        return $html;
    }
    
    /**
     * Estatísticas gerais de avaliações
     */
    public function getGeneralStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT i.id_evento) as eventos_avaliados,
                COUNT(*) as total_avaliacoes,
                AVG(i.avaliacao_evento) as media_geral,
                COUNT(DISTINCT i.id_participante) as participantes_que_avaliaram
            FROM inscricoes i
            WHERE i.avaliacao_evento IS NOT NULL
        ");
        $stmt->execute();
        
        $stats = $stmt->fetch();
        $stats['media_geral_formatada'] = number_format($stats['media_geral'] ?? 0, 1);
        
        return $stats;
    }
}
?>