<?php
require_once __DIR__ . '/../config/database.php';

class Event {
    private $conn;
    private $table = 'eventos';
    
    // Propriedades do evento
    public $id_evento;
    public $id_organizador;
    public $id_categoria;
    public $titulo;
    public $descricao;
    public $data_inicio;
    public $data_fim;
    public $horario_inicio;
    public $horario_fim;
    public $local_nome;
    public $local_endereco;
    public $local_cidade;
    public $local_estado;
    public $local_cep;
    public $capacidade_maxima;
    public $preco;
    public $evento_gratuito;
    public $imagem_capa;
    public $link_externo;
    public $requisitos;
    public $informacoes_adicionais;
    public $status;
    public $destaque;
    public $data_criacao;
    public $data_atualizacao;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Criar novo evento
     */
    public function create($data) {
        // Validar dados
        $validation = $this->validateEventData($data);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Preparar query
        $query = "INSERT INTO " . $this->table . " (
            id_organizador, id_categoria, titulo, descricao,
            data_inicio, data_fim, horario_inicio, horario_fim,
            local_nome, local_endereco, local_cidade, local_estado, local_cep,
            capacidade_maxima, preco, evento_gratuito, imagem_capa,
            link_externo, requisitos, informacoes_adicionais, status, destaque
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([
                $data['id_organizador'],
                $data['id_categoria'] ?? null,
                $data['titulo'],
                $data['descricao'],
                $data['data_inicio'],
                $data['data_fim'],
                $data['horario_inicio'],
                $data['horario_fim'],
                $data['local_nome'],
                $data['local_endereco'],
                $data['local_cidade'],
                $data['local_estado'],
                $data['local_cep'] ?? null,
                $data['capacidade_maxima'] ?? null,
                $data['preco'] ?? 0.00,
                $data['evento_gratuito'] ?? true,
                $data['imagem_capa'] ?? null,
                $data['link_externo'] ?? null,
                $data['requisitos'] ?? null,
                $data['informacoes_adicionais'] ?? null,
                $data['status'] ?? 'rascunho',
                $data['destaque'] ?? false
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Evento criado com sucesso!',
                    'event_id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar evento: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao criar evento.'];
    }
    
    /**
     * Buscar evento por ID
     */
    public function findById($id) {
        $query = "SELECT e.*, 
                         u.nome AS nome_organizador,
                         u.email AS email_organizador,
                         c.nome AS nome_categoria,
                         c.cor AS cor_categoria,
                         c.icone AS icone_categoria,
                         (SELECT COUNT(*) FROM inscricoes i 
                          WHERE i.id_evento = e.id_evento AND i.status = 'confirmada') AS total_inscritos
                  FROM " . $this->table . " e
                  LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                  LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                  WHERE e.id_evento = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return false;
    }
    
    /**
     * Atualizar evento
     */
    public function update($id, $data) {
        // Verificar se o evento existe
        if (!$this->findById($id)) {
            return ['success' => false, 'message' => 'Evento não encontrado.'];
        }
        
        // Validar dados
        $validation = $this->validateEventData($data);
        if (!$validation['success']) {
            return $validation;
        }
        
        $query = "UPDATE " . $this->table . " SET 
                  id_categoria = ?, titulo = ?, descricao = ?,
                  data_inicio = ?, data_fim = ?, horario_inicio = ?, horario_fim = ?,
                  local_nome = ?, local_endereco = ?, local_cidade = ?, local_estado = ?, local_cep = ?,
                  capacidade_maxima = ?, preco = ?, evento_gratuito = ?, imagem_capa = ?,
                  link_externo = ?, requisitos = ?, informacoes_adicionais = ?, status = ?, destaque = ?
                  WHERE id_evento = ?";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([
                $data['id_categoria'] ?? null,
                $data['titulo'],
                $data['descricao'],
                $data['data_inicio'],
                $data['data_fim'],
                $data['horario_inicio'],
                $data['horario_fim'],
                $data['local_nome'],
                $data['local_endereco'],
                $data['local_cidade'],
                $data['local_estado'],
                $data['local_cep'] ?? null,
                $data['capacidade_maxima'] ?? null,
                $data['preco'] ?? 0.00,
                $data['evento_gratuito'] ?? true,
                $data['imagem_capa'] ?? null,
                $data['link_externo'] ?? null,
                $data['requisitos'] ?? null,
                $data['informacoes_adicionais'] ?? null,
                $data['status'] ?? 'rascunho',
                $data['destaque'] ?? false,
                $id
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Evento atualizado com sucesso!'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar evento: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao atualizar evento.'];
    }
    
    /**
     * Excluir evento
     */
    public function delete($id, $organizador_id) {
        // Verificar se o evento existe e pertence ao organizador
        $evento = $this->findById($id);
        if (!$evento) {
            return ['success' => false, 'message' => 'Evento não encontrado.'];
        }
        
        if ($evento['id_organizador'] != $organizador_id) {
            return ['success' => false, 'message' => 'Você não tem permissão para excluir este evento.'];
        }
        
        $query = "DELETE FROM " . $this->table . " WHERE id_evento = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Evento excluído com sucesso!'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir evento: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao excluir evento.'];
    }
    
    /**
     * Listar eventos com filtros
     */
    public function list($filters = []) {
        $where = ['1=1']; // Base condition
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters['organizador_id'])) {
            $where[] = "e.id_organizador = ?";
            $params[] = $filters['organizador_id'];
        }
        
        if (!empty($filters['categoria_id'])) {
            $where[] = "e.id_categoria = ?";
            $params[] = $filters['categoria_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['cidade'])) {
            $where[] = "e.local_cidade LIKE ?";
            $params[] = "%{$filters['cidade']}%";
        }
        
        if (!empty($filters['busca'])) {
            $where[] = "(e.titulo LIKE ? OR e.descricao LIKE ?)";
            $params[] = "%{$filters['busca']}%";
            $params[] = "%{$filters['busca']}%";
        }
        
        if (isset($filters['gratuito'])) {
            $where[] = "e.evento_gratuito = ?";
            $params[] = $filters['gratuito'] ? 1 : 0;
        }
        
        if (!empty($filters['data_inicio'])) {
            $where[] = "e.data_inicio >= ?";
            $params[] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $where[] = "e.data_inicio <= ?";
            $params[] = $filters['data_fim'];
        }
        
        // Ordenação
        $orderBy = "e.data_inicio ASC";
        if (!empty($filters['ordem'])) {
            switch ($filters['ordem']) {
                case 'data_desc':
                    $orderBy = "e.data_inicio DESC";
                    break;
                case 'titulo':
                    $orderBy = "e.titulo ASC";
                    break;
                case 'preco_asc':
                    $orderBy = "e.preco ASC";
                    break;
                case 'preco_desc':
                    $orderBy = "e.preco DESC";
                    break;
            }
        }
        
        // Paginação
        $limit = "";
        if (!empty($filters['limite'])) {
            $limit = "LIMIT " . intval($filters['limite']);
            if (!empty($filters['offset'])) {
                $limit .= " OFFSET " . intval($filters['offset']);
            }
        }
        
        $query = "SELECT e.*, 
                         u.nome AS nome_organizador,
                         c.nome AS nome_categoria,
                         c.cor AS cor_categoria,
                         c.icone AS icone_categoria,
                         (SELECT COUNT(*) FROM inscricoes i 
                          WHERE i.id_evento = e.id_evento AND i.status = 'confirmada') AS total_inscritos,
                         CASE 
                            WHEN e.capacidade_maxima IS NOT NULL THEN 
                                e.capacidade_maxima - (SELECT COUNT(*) FROM inscricoes i WHERE i.id_evento = e.id_evento AND i.status = 'confirmada')
                            ELSE NULL 
                         END AS vagas_restantes
                  FROM " . $this->table . " e
                  LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                  LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY " . $orderBy . "
                  " . $limit;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar eventos públicos (para participantes)
     */
    public function getPublicEvents($filters = []) {
        $filters['status'] = 'publicado';
        return $this->list($filters);
    }
    
    /**
     * Buscar eventos do organizador
     */
    public function getEventsByOrganizer($organizador_id, $filters = []) {
        $filters['organizador_id'] = $organizador_id;
        return $this->list($filters);
    }
    
    /**
     * Contar eventos
     */
    public function count($filters = []) {
        $where = ['1=1'];
        $params = [];
        
        // Aplicar os mesmos filtros da função list()
        if (!empty($filters['organizador_id'])) {
            $where[] = "id_organizador = ?";
            $params[] = $filters['organizador_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE " . implode(' AND ', $where);
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Alterar status do evento
     */
    public function changeStatus($id, $status, $organizador_id) {
        $validStatuses = ['rascunho', 'publicado', 'cancelado', 'finalizado'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Status inválido.'];
        }
        
        // Verificar se o evento pertence ao organizador
        $evento = $this->findById($id);
        if (!$evento || $evento['id_organizador'] != $organizador_id) {
            return ['success' => false, 'message' => 'Evento não encontrado ou sem permissão.'];
        }
        
        $query = "UPDATE " . $this->table . " SET status = ? WHERE id_evento = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$status, $id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Status do evento atualizado com sucesso!'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar status: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao atualizar status.'];
    }
    
    /**
     * Eventos em destaque
     */
    public function getFeaturedEvents($limit = 6) {
        return $this->list([
            'status' => 'publicado',
            'limite' => $limit,
            'ordem' => 'data_inicio'
        ]);
    }
    
    /**
     * Eventos próximos
     */
    public function getUpcomingEvents($limit = 10) {
        return $this->list([
            'status' => 'publicado',
            'data_inicio' => date('Y-m-d H:i:s'),
            'limite' => $limit,
            'ordem' => 'data_inicio'
        ]);
    }
    
    /**
     * Validar dados do evento
     */
    private function validateEventData($data) {
        $errors = [];
        
        // Validar título
        if (empty(trim($data['titulo']))) {
            $errors[] = "Título é obrigatório.";
        } elseif (strlen(trim($data['titulo'])) > 200) {
            $errors[] = "Título não pode ter mais de 200 caracteres.";
        }
        
        // Validar descrição
        if (empty(trim($data['descricao']))) {
            $errors[] = "Descrição é obrigatória.";
        }
        
        // Validar datas
        if (empty($data['data_inicio'])) {
            $errors[] = "Data de início é obrigatória.";
        }
        
        if (empty($data['data_fim'])) {
            $errors[] = "Data de fim é obrigatória.";
        }
        
        if (!empty($data['data_inicio']) && !empty($data['data_fim'])) {
            $dataInicio = new DateTime($data['data_inicio']);
            $dataFim = new DateTime($data['data_fim']);
            
            if ($dataInicio > $dataFim) {
                $errors[] = "Data de início não pode ser posterior à data de fim.";
            }
            
            if ($dataInicio < new DateTime()) {
                $errors[] = "Data de início não pode ser no passado.";
            }
        }
        
        // Validar horários
        if (empty($data['horario_inicio'])) {
            $errors[] = "Horário de início é obrigatório.";
        }
        
        if (empty($data['horario_fim'])) {
            $errors[] = "Horário de fim é obrigatório.";
        }
        
        // Validar local
        if (empty(trim($data['local_nome']))) {
            $errors[] = "Nome do local é obrigatório.";
        }
        
        if (empty(trim($data['local_endereco']))) {
            $errors[] = "Endereço do local é obrigatório.";
        }
        
        if (empty(trim($data['local_cidade']))) {
            $errors[] = "Cidade é obrigatória.";
        }
        
        if (empty(trim($data['local_estado']))) {
            $errors[] = "Estado é obrigatório.";
        }
        
        // Validar capacidade
        if (!empty($data['capacidade_maxima']) && $data['capacidade_maxima'] < 1) {
            $errors[] = "Capacidade máxima deve ser maior que zero.";
        }
        
        // Validar preço
        if (!empty($data['preco']) && $data['preco'] < 0) {
            $errors[] = "Preço não pode ser negativo.";
        }
        
        // Validar status
        $validStatuses = ['rascunho', 'publicado', 'cancelado', 'finalizado'];
        if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors[] = "Status inválido.";
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(' ', $errors)
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Obter estatísticas do evento
     */
    public function getEventStats($event_id) {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM inscricoes WHERE id_evento = ? AND status = 'confirmada') as inscritos_confirmados,
                    (SELECT COUNT(*) FROM inscricoes WHERE id_evento = ? AND status = 'pendente') as inscritos_pendentes,
                    (SELECT COUNT(*) FROM inscricoes WHERE id_evento = ? AND status = 'cancelada') as inscritos_cancelados,
                    (SELECT COUNT(*) FROM favoritos WHERE id_evento = ?) as total_favoritos,
                    (SELECT AVG(avaliacao_evento) FROM inscricoes WHERE id_evento = ? AND avaliacao_evento IS NOT NULL) as media_avaliacoes";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$event_id, $event_id, $event_id, $event_id, $event_id]);
        
        return $stmt->fetch();
    }
}
?>