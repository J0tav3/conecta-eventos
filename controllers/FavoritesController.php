<?php
// ========================================
// CONTROLLER DE FAVORITOS
// ========================================
// Local: controllers/FavoritesController.php
// ========================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

class FavoritesController {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Adicionar evento aos favoritos
     */
    public function addToFavorites($eventId) {
        if (!isLoggedIn()) {
            return [
                'success' => false,
                'message' => 'Você precisa estar logado para favoritar eventos.'
            ];
        }
        
        $userId = getUserId();
        
        // Verificar se já está nos favoritos
        if ($this->isFavorite($userId, $eventId)) {
            return [
                'success' => false,
                'message' => 'Este evento já está nos seus favoritos.'
            ];
        }
        
        // Verificar se o evento existe
        $stmt = $this->conn->prepare("SELECT id_evento FROM eventos WHERE id_evento = ? AND status = 'publicado'");
        $stmt->execute([$eventId]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'success' => false,
                'message' => 'Evento não encontrado.'
            ];
        }
        
        // Adicionar aos favoritos
        try {
            $stmt = $this->conn->prepare("INSERT INTO favoritos (id_usuario, id_evento) VALUES (?, ?)");
            $result = $stmt->execute([$userId, $eventId]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Evento adicionado aos favoritos!'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao adicionar aos favoritos: ' . $e->getMessage()
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro desconhecido ao adicionar aos favoritos.'
        ];
    }
    
    /**
     * Remover evento dos favoritos
     */
    public function removeFromFavorites($eventId) {
        if (!isLoggedIn()) {
            return [
                'success' => false,
                'message' => 'Você precisa estar logado.'
            ];
        }
        
        $userId = getUserId();
        
        try {
            $stmt = $this->conn->prepare("DELETE FROM favoritos WHERE id_usuario = ? AND id_evento = ?");
            $result = $stmt->execute([$userId, $eventId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Evento removido dos favoritos!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Evento não estava nos favoritos.'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao remover dos favoritos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Alternar status de favorito (toggle)
     */
    public function toggleFavorite($eventId) {
        if (!isLoggedIn()) {
            return [
                'success' => false,
                'message' => 'Você precisa estar logado.'
            ];
        }
        
        $userId = getUserId();
        
        if ($this->isFavorite($userId, $eventId)) {
            return $this->removeFromFavorites($eventId);
        } else {
            return $this->addToFavorites($eventId);
        }
    }
    
    /**
     * Verificar se evento está nos favoritos do usuário
     */
    public function isFavorite($userId, $eventId) {
        $stmt = $this->conn->prepare("SELECT id_favorito FROM favoritos WHERE id_usuario = ? AND id_evento = ?");
        $stmt->execute([$userId, $eventId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Obter todos os favoritos do usuário
     */
    public function getUserFavorites($userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        if (!$userId) {
            return [];
        }
        
        $query = "SELECT e.*, f.data_favoritado,
                         u.nome as nome_organizador,
                         c.nome as nome_categoria,
                         c.cor as cor_categoria,
                         (SELECT COUNT(*) FROM inscricoes i 
                          WHERE i.id_evento = e.id_evento AND i.status = 'confirmada') AS total_inscritos
                  FROM favoritos f
                  INNER JOIN eventos e ON f.id_evento = e.id_evento
                  LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                  LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                  WHERE f.id_usuario = ? AND e.status = 'publicado'
                  ORDER BY f.data_favoritado DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Contar favoritos do usuário
     */
    public function countUserFavorites($userId = null) {
        if (!$userId) {
            $userId = getUserId();
        }
        
        if (!$userId) {
            return 0;
        }
        
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM favoritos f
                                      INNER JOIN eventos e ON f.id_evento = e.id_evento
                                      WHERE f.id_usuario = ? AND e.status = 'publicado'");
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Obter eventos mais favoritados
     */
    public function getMostFavorited($limit = 10) {
        $query = "SELECT e.*, 
                         COUNT(f.id_favorito) as total_favoritos,
                         u.nome as nome_organizador,
                         c.nome as nome_categoria,
                         (SELECT COUNT(*) FROM inscricoes i 
                          WHERE i.id_evento = e.id_evento AND i.status = 'confirmada') AS total_inscritos
                  FROM eventos e
                  LEFT JOIN favoritos f ON e.id_evento = f.id_evento
                  LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                  LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                  WHERE e.status = 'publicado'
                  GROUP BY e.id_evento
                  ORDER BY total_favoritos DESC, e.data_inicio ASC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Verificar múltiplos favoritos para um usuário
     */
    public function checkMultipleFavorites($userId, $eventIds) {
        if (empty($eventIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($eventIds) - 1) . '?';
        $params = array_merge([$userId], $eventIds);
        
        $stmt = $this->conn->prepare("SELECT id_evento FROM favoritos 
                                      WHERE id_usuario = ? AND id_evento IN ($placeholders)");
        $stmt->execute($params);
        
        $favorites = [];
        while ($row = $stmt->fetch()) {
            $favorites[] = $row['id_evento'];
        }
        
        return $favorites;
    }
    
    /**
     * Estatísticas de favoritos do evento
     */
    public function getEventFavoriteStats($eventId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total_favoritos FROM favoritos WHERE id_evento = ?");
        $stmt->execute([$eventId]);
        
        $result = $stmt->fetch();
        return $result['total_favoritos'];
    }
}
?>