<?php
// ==========================================
// EVENT MODEL
// Local: models/Event.php
// ==========================================

class Event {
    private $conn;
    private $table_name = "eventos";

    // Propriedades do evento
    public $id_evento;
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
    public $evento_gratuito;
    public $preco;
    public $max_participantes;
    public $categoria_id;
    public $organizador_id;
    public $status;
    public $imagem_capa;
    public $created_at;
    public $updated_at;

    public function __construct($db = null) {
        if ($db) {
            $this->conn = $db;
        }
    }

    // Buscar todos os eventos públicos
    public function getPublicEvents($limit = null, $offset = 0, $filters = []) {
        try {
            $query = "SELECT 
                        e.*,
                        c.nome as nome_categoria,
                        u.nome as nome_organizador,
                        COUNT(i.id_inscricao) as total_inscritos
                     FROM " . $this->table_name . " e
                     LEFT JOIN categorias c ON e.categoria_id = c.id_categoria
                     LEFT JOIN usuarios u ON e.organizador_id = u.id_usuario
                     LEFT JOIN inscricoes i ON e.id_evento = i.evento_id
                     WHERE e.status = 'publicado'
                     AND e.data_inicio >= CURDATE()";

            // Aplicar filtros
            if (!empty($filters['categoria_id'])) {
                $query .= " AND e.categoria_id = :categoria_id";
            }
            
            if (!empty($filters['cidade'])) {
                $query .= " AND e.local_cidade LIKE :cidade";
            }
            
            if (!empty($filters['busca'])) {
                $query .= " AND (e.titulo LIKE :busca OR e.descricao LIKE :busca)";
            }
            
            if (isset($filters['gratuito'])) {
                $query .= " AND e.evento_gratuito = :gratuito";
            }

            $query .= " GROUP BY e.id_evento";
            $query .= " ORDER BY e.data_inicio ASC";

            if ($limit) {
                $query .= " LIMIT :limit OFFSET :offset";
            }

            $stmt = $this->conn->prepare($query);

            // Bind dos filtros
            if (!empty($filters['categoria_id'])) {
                $stmt->bindParam(':categoria_id', $filters['categoria_id']);
            }
            
            if (!empty($filters['cidade'])) {
                $cidade_param = '%' . $filters['cidade'] . '%';
                $stmt->bindParam(':cidade', $cidade_param);
            }
            
            if (!empty($filters['busca'])) {
                $busca_param = '%' . $filters['busca'] . '%';
                $stmt->bindParam(':busca', $busca_param);
            }
            
            if (isset($filters['gratuito'])) {
                $stmt->bindParam(':gratuito', $filters['gratuito'], PDO::PARAM_BOOL);
            }

            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar eventos: " . $e->getMessage());
            return [];
        }
    }

    // Buscar evento por ID
    public function getEventById($id) {
        try {
            $query = "SELECT 
                        e.*,
                        c.nome as nome_categoria,
                        u.nome as nome_organizador,
                        u.email as email_organizador,
                        COUNT(i.id_inscricao) as total_inscritos
                     FROM " . $this->table_name . " e
                     LEFT JOIN categorias c ON e.categoria_id = c.id_categoria
                     LEFT JOIN usuarios u ON e.organizador_id = u.id_usuario
                     LEFT JOIN inscricoes i ON e.id_evento = i.evento_id
                     WHERE e.id_evento = :id
                     GROUP BY e.id_evento";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar evento: " . $e->getMessage());
            return false;
        }
    }

    // Buscar eventos por organizador
    public function getEventsByOrganizer($organizador_id, $limit = null) {
        try {
            $query = "SELECT 
                        e.*,
                        c.nome as nome_categoria,
                        COUNT(i.id_inscricao) as total_inscritos
                     FROM " . $this->table_name . " e
                     LEFT JOIN categorias c ON e.categoria_id = c.id_categoria
                     LEFT JOIN inscricoes i ON e.id_evento = i.evento_id
                     WHERE e.organizador_id = :organizador_id
                     GROUP BY e.id_evento
                     ORDER BY e.created_at DESC";

            if ($limit) {
                $query .= " LIMIT :limit";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':organizador_id', $organizador_id);
            
            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar eventos do organizador: " . $e->getMessage());
            return [];
        }
    }

    // Criar novo evento
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                     (titulo, descricao, data_inicio, data_fim, horario_inicio, horario_fim,
                      local_nome, local_endereco, local_cidade, local_estado, local_cep,
                      evento_gratuito, preco, max_participantes, categoria_id, organizador_id,
                      status, imagem_capa)
                     VALUES
                     (:titulo, :descricao, :data_inicio, :data_fim, :horario_inicio, :horario_fim,
                      :local_nome, :local_endereco, :local_cidade, :local_estado, :local_cep,
                      :evento_gratuito, :preco, :max_participantes, :categoria_id, :organizador_id,
                      :status, :imagem_capa)";

            $stmt = $this->conn->prepare($query);

            // Bind dos parâmetros
            $stmt->bindParam(':titulo', $this->titulo);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':data_inicio', $this->data_inicio);
            $stmt->bindParam(':data_fim', $this->data_fim);
            $stmt->bindParam(':horario_inicio', $this->horario_inicio);
            $stmt->bindParam(':horario_fim', $this->horario_fim);
            $stmt->bindParam(':local_nome', $this->local_nome);
            $stmt->bindParam(':local_endereco', $this->local_endereco);
            $stmt->bindParam(':local_cidade', $this->local_cidade);
            $stmt->bindParam(':local_estado', $this->local_estado);
            $stmt->bindParam(':local_cep', $this->local_cep);
            $stmt->bindParam(':evento_gratuito', $this->evento_gratuito);
            $stmt->bindParam(':preco', $this->preco);
            $stmt->bindParam(':max_participantes', $this->max_participantes);
            $stmt->bindParam(':categoria_id', $this->categoria_id);
            $stmt->bindParam(':organizador_id', $this->organizador_id);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':imagem_capa', $this->imagem_capa);

            if ($stmt->execute()) {
                $this->id_evento = $this->conn->lastInsertId();
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("Erro ao criar evento: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar evento
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET titulo = :titulo,
                         descricao = :descricao,
                         data_inicio = :data_inicio,
                         data_fim = :data_fim,
                         horario_inicio = :horario_inicio,
                         horario_fim = :horario_fim,
                         local_nome = :local_nome,
                         local_endereco = :local_endereco,
                         local_cidade = :local_cidade,
                         local_estado = :local_estado,
                         local_cep = :local_cep,
                         evento_gratuito = :evento_gratuito,
                         preco = :preco,
                         max_participantes = :max_participantes,
                         categoria_id = :categoria_id,
                         status = :status,
                         imagem_capa = :imagem_capa,
                         updated_at = NOW()
                     WHERE id_evento = :id_evento
                     AND organizador_id = :organizador_id";

            $stmt = $this->conn->prepare($query);

            // Bind dos parâmetros
            $stmt->bindParam(':titulo', $this->titulo);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':data_inicio', $this->data_inicio);
            $stmt->bindParam(':data_fim', $this->data_fim);
            $stmt->bindParam(':horario_inicio', $this->horario_inicio);
            $stmt->bindParam(':horario_fim', $this->horario_fim);
            $stmt->bindParam(':local_nome', $this->local_nome);
            $stmt->bindParam(':local_endereco', $this->local_endereco);
            $stmt->bindParam(':local_cidade', $this->local_cidade);
            $stmt->bindParam(':local_estado', $this->local_estado);
            $stmt->bindParam(':local_cep', $this->local_cep);
            $stmt->bindParam(':evento_gratuito', $this->evento_gratuito);
            $stmt->bindParam(':preco', $this->preco);
            $stmt->bindParam(':max_participantes', $this->max_participantes);
            $stmt->bindParam(':categoria_id', $this->categoria_id);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':imagem_capa', $this->imagem_capa);
            $stmt->bindParam(':id_evento', $this->id_evento);
            $stmt->bindParam(':organizador_id', $this->organizador_id);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Erro ao atualizar evento: " . $e->getMessage());
            return false;
        }
    }

    // Deletar evento
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . "
                     WHERE id_evento = :id_evento
                     AND organizador_id = :organizador_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_evento', $this->id_evento);
            $stmt->bindParam(':organizador_id', $this->organizador_id);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Erro ao deletar evento: " . $e->getMessage());
            return false;
        }
    }

    // Verificar se usuário está inscrito no evento
    public function isUserSubscribed($usuario_id) {
        try {
            $query = "SELECT id_inscricao FROM inscricoes 
                     WHERE evento_id = :evento_id AND usuario_id = :usuario_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':evento_id', $this->id_evento);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Erro ao verificar inscrição: " . $e->getMessage());
            return false;
        }
    }

    // Contar participantes
    public function countParticipants() {
        try {
            $query = "SELECT COUNT(*) as total FROM inscricoes WHERE evento_id = :evento_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':evento_id', $this->id_evento);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];

        } catch (Exception $e) {
            error_log("Erro ao contar participantes: " . $e->getMessage());
            return 0;
        }
    }

    // Verificar se evento tem vagas
    public function hasAvailableSlots() {
        if (!$this->max_participantes) {
            return true; // Sem limite
        }

        $current_participants = $this->countParticipants();
        return $current_participants < $this->max_participantes;
    }

    // Formatar dados para exibição
    public function formatForDisplay($event_data) {
        if (!$event_data) {
            return null;
        }

        $event_data['data_inicio_formatada'] = date('d/m/Y', strtotime($event_data['data_inicio']));
        $event_data['data_fim_formatada'] = date('d/m/Y', strtotime($event_data['data_fim']));
        $event_data['horario_inicio_formatado'] = date('H:i', strtotime($event_data['horario_inicio']));
        $event_data['horario_fim_formatado'] = date('H:i', strtotime($event_data['horario_fim']));
        
        if ($event_data['evento_gratuito']) {
            $event_data['preco_formatado'] = 'Gratuito';
        } else {
            $event_data['preco_formatado'] = 'R$ ' . number_format($event_data['preco'], 2, ',', '.');
        }

        // URL da imagem
        if ($event_data['imagem_capa']) {
            $event_data['imagem_url'] = 'uploads/eventos/' . $event_data['imagem_capa'];
        } else {
            $event_data['imagem_url'] = '';
        }

        return $event_data;
    }
}