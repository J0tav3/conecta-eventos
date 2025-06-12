<?php
// ==========================================
// EVENT CONTROLLER - VERSÃO COM BANCO REAL
// Local: controllers/EventController.php
// ==========================================

require_once __DIR__ . '/../config/database.php';

class EventController {
    private $db;
    private $conn;

    public function __construct() {
        try {
            $this->db = Database::getInstance();
            $this->conn = $this->db->getConnection();
            
            if (!$this->conn) {
                throw new Exception("Falha ao conectar com o banco de dados");
            }
        } catch (Exception $e) {
            error_log("EventController: Erro ao conectar: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Buscar eventos públicos
     */
    public function getPublicEvents($params = []) {
        if (!$this->conn) {
            return $this->getExampleEvents($params);
        }

        try {
            $limite = isset($params['limite']) ? (int)$params['limite'] : null;
            $ordem = isset($params['ordem']) ? $params['ordem'] : 'data_inicio';
            
            $query = "SELECT 
                        e.*,
                        c.nome as nome_categoria,
                        u.nome as nome_organizador,
                        COUNT(i.id_inscricao) as total_inscritos
                     FROM eventos e
                     LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                     LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                     LEFT JOIN inscricoes i ON e.id_evento = i.id_evento
                     WHERE e.status = 'publicado'
                     AND e.data_inicio >= CURDATE()";

            // Aplicar filtros
            if (!empty($params['categoria_id'])) {
                $query .= " AND e.id_categoria = :categoria_id";
            }
            
            if (!empty($params['cidade'])) {
                $query .= " AND e.local_cidade LIKE :cidade";
            }
            
            if (!empty($params['busca'])) {
                $query .= " AND (e.titulo LIKE :busca OR e.descricao LIKE :busca)";
            }
            
            if (isset($params['gratuito'])) {
                $query .= " AND e.evento_gratuito = :gratuito";
            }

            $query .= " GROUP BY e.id_evento";
            
            if ($ordem === 'destaque') {
                $query .= " ORDER BY e.destaque DESC, total_inscritos DESC, e.data_inicio ASC";
            } else {
                $query .= " ORDER BY e.data_inicio ASC";
            }

            if ($limite) {
                $query .= " LIMIT " . $limite;
            }

            $stmt = $this->conn->prepare($query);

            // Bind dos parâmetros
            if (!empty($params['categoria_id'])) {
                $stmt->bindParam(':categoria_id', $params['categoria_id']);
            }
            
            if (!empty($params['cidade'])) {
                $cidade_param = '%' . $params['cidade'] . '%';
                $stmt->bindParam(':cidade', $cidade_param);
            }
            
            if (!empty($params['busca'])) {
                $busca_param = '%' . $params['busca'] . '%';
                $stmt->bindParam(':busca', $busca_param);
            }
            
            if (isset($params['gratuito'])) {
                $stmt->bindParam(':gratuito', $params['gratuito'], PDO::PARAM_BOOL);
            }

            $stmt->execute();
            $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $eventos ?: [];

        } catch (Exception $e) {
            error_log("Erro ao buscar eventos: " . $e->getMessage());
            return $this->getExampleEvents($params);
        }
    }

    /**
     * Buscar categorias
     */
    public function getCategories() {
        if (!$this->conn) {
            return $this->getExampleCategories();
        }

        try {
            $query = "SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $categorias ?: $this->getExampleCategories();

        } catch (Exception $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            return $this->getExampleCategories();
        }
    }

    /**
     * Buscar evento por ID
     */
    public function getById($id) {
        if (!$this->conn) {
            return null;
        }

        try {
            $query = "SELECT 
                        e.*,
                        c.nome as nome_categoria,
                        u.nome as nome_organizador,
                        u.email as email_organizador,
                        COUNT(i.id_inscricao) as total_inscritos
                     FROM eventos e
                     LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                     LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                     LEFT JOIN inscricoes i ON e.id_evento = i.id_evento
                     WHERE e.id_evento = :id
                     GROUP BY e.id_evento";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar evento: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Criar novo evento
     */
    public function create($data) {
        if (!$this->conn) {
            return ['success' => false, 'message' => 'Banco de dados indisponível'];
        }

        try {
            // Validar dados obrigatórios
            $required = ['titulo', 'descricao', 'data_inicio', 'horario_inicio', 'local_nome', 'local_endereco', 'local_cidade', 'local_estado'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Campo obrigatório: $field"];
                }
            }

            // Verificar se usuário está logado
            session_start();
            if (!isset($_SESSION['user_id'])) {
                return ['success' => false, 'message' => 'Usuário não logado'];
            }

            $organizador_id = $_SESSION['user_id'];

            // Preparar dados
            $titulo = trim($data['titulo']);
            $descricao = trim($data['descricao']);
            $id_categoria = !empty($data['categoria']) ? (int)$data['categoria'] : null;
            $data_inicio = $data['data_inicio'];
            $data_fim = !empty($data['data_fim']) ? $data['data_fim'] : $data['data_inicio'];
            $horario_inicio = $data['horario_inicio'];
            $horario_fim = !empty($data['horario_fim']) ? $data['horario_fim'] : $data['horario_inicio'];
            $local_nome = trim($data['local_nome']);
            $local_endereco = trim($data['local_endereco']);
            $local_cidade = trim($data['local_cidade']);
            $local_estado = $data['local_estado'];
            $local_cep = !empty($data['local_cep']) ? $data['local_cep'] : null;
            $evento_gratuito = isset($data['evento_gratuito']) ? 1 : 0;
            $preco = $evento_gratuito ? 0 : (float)($data['preco'] ?? 0);
            $capacidade_maxima = !empty($data['max_participantes']) ? (int)$data['max_participantes'] : null;
            $requisitos = !empty($data['requisitos']) ? trim($data['requisitos']) : null;
            $informacoes_adicionais = !empty($data['o_que_levar']) ? trim($data['o_que_levar']) : null;

            // SQL de inserção
            $sql = "INSERT INTO eventos (
                        id_organizador, id_categoria, titulo, descricao, 
                        data_inicio, data_fim, horario_inicio, horario_fim,
                        local_nome, local_endereco, local_cidade, local_estado, local_cep,
                        evento_gratuito, preco, capacidade_maxima,
                        requisitos, informacoes_adicionais, status
                    ) VALUES (
                        :organizador_id, :categoria_id, :titulo, :descricao,
                        :data_inicio, :data_fim, :horario_inicio, :horario_fim,
                        :local_nome, :local_endereco, :local_cidade, :local_estado, :local_cep,
                        :evento_gratuito, :preco, :capacidade_maxima,
                        :requisitos, :informacoes_adicionais, 'rascunho'
                    )";

            $stmt = $this->conn->prepare($sql);

            // Bind dos parâmetros
            $stmt->bindParam(':organizador_id', $organizador_id);
            $stmt->bindParam(':categoria_id', $id_categoria);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim', $data_fim);
            $stmt->bindParam(':horario_inicio', $horario_inicio);
            $stmt->bindParam(':horario_fim', $horario_fim);
            $stmt->bindParam(':local_nome', $local_nome);
            $stmt->bindParam(':local_endereco', $local_endereco);
            $stmt->bindParam(':local_cidade', $local_cidade);
            $stmt->bindParam(':local_estado', $local_estado);
            $stmt->bindParam(':local_cep', $local_cep);
            $stmt->bindParam(':evento_gratuito', $evento_gratuito);
            $stmt->bindParam(':preco', $preco);
            $stmt->bindParam(':capacidade_maxima', $capacidade_maxima);
            $stmt->bindParam(':requisitos', $requisitos);
            $stmt->bindParam(':informacoes_adicionais', $informacoes_adicionais);

            if ($stmt->execute()) {
                $evento_id = $this->conn->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'Evento criado com sucesso!',
                    'evento_id' => $evento_id
                ];
            } else {
                return ['success' => false, 'message' => 'Erro ao criar evento'];
            }

        } catch (Exception $e) {
            error_log("Erro ao criar evento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar se usuário pode editar evento
     */
    public function canEdit($evento_id) {
        if (!$this->conn) {
            return false;
        }

        session_start();
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare("SELECT id_organizador FROM eventos WHERE id_evento = ?");
            $stmt->execute([$evento_id]);
            $evento = $stmt->fetch();

            return $evento && $evento['id_organizador'] == $_SESSION['user_id'];
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Formatar evento para exibição
     */
    public function formatEventForDisplay($event) {
        if (!$event) {
            return null;
        }

        $event['data_inicio_formatada'] = date('d/m/Y', strtotime($event['data_inicio']));
        $event['horario_inicio_formatado'] = date('H:i', strtotime($event['horario_inicio']));
        
        if ($event['evento_gratuito']) {
            $event['preco_formatado'] = 'Gratuito';
        } else {
            $event['preco_formatado'] = 'R$ ' . number_format($event['preco'], 2, ',', '.');
        }

        // URL da imagem
        if ($event['imagem_capa']) {
            $event['imagem_url'] = 'uploads/eventos/' . $event['imagem_capa'];
        } else {
            $event['imagem_url'] = '';
        }

        return $event;
    }

    /**
     * Buscar eventos do organizador
     */
    public function getEventsByOrganizer($organizador_id, $filters = []) {
        if (!$this->conn) {
            return [];
        }

        try {
            $query = "SELECT 
                        e.*,
                        c.nome as nome_categoria,
                        COUNT(i.id_inscricao) as total_inscritos
                     FROM eventos e
                     LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                     LEFT JOIN inscricoes i ON e.id_evento = i.id_evento
                     WHERE e.id_organizador = :organizador_id";

            // Aplicar filtros
            if (!empty($filters['status'])) {
                $query .= " AND e.status = :status";
            }
            
            if (!empty($filters['categoria'])) {
                $query .= " AND c.nome = :categoria";
            }

            $query .= " GROUP BY e.id_evento ORDER BY e.data_criacao DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':organizador_id', $organizador_id);
            
            if (!empty($filters['status'])) {
                $stmt->bindParam(':status', $filters['status']);
            }
            
            if (!empty($filters['categoria'])) {
                $stmt->bindParam(':categoria', $filters['categoria']);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar eventos do organizador: " . $e->getMessage());
            return [];
        }
    }

    // Dados de exemplo quando não há banco (fallback)
    private function getExampleEvents($params = []) {
        $eventos = [
            [
                'id_evento' => 1,
                'titulo' => 'Workshop de Desenvolvimento Web',
                'descricao' => 'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
                'data_inicio' => date('Y-m-d', strtotime('+7 days')),
                'horario_inicio' => '14:00:00',
                'local_cidade' => 'São Paulo',
                'evento_gratuito' => true,
                'preco' => 0,
                'imagem_capa' => '',
                'id_categoria' => 1,
                'nome_categoria' => 'Tecnologia',
                'total_inscritos' => 45
            ],
            [
                'id_evento' => 2,
                'titulo' => 'Palestra: Empreendedorismo Digital',
                'descricao' => 'Como criar e escalar um negócio digital no mercado atual.',
                'data_inicio' => date('Y-m-d', strtotime('+10 days')),
                'horario_inicio' => '19:00:00',
                'local_cidade' => 'Rio de Janeiro',
                'evento_gratuito' => false,
                'preco' => 50.00,
                'imagem_capa' => '',
                'id_categoria' => 2,
                'nome_categoria' => 'Negócios',
                'total_inscritos' => 32
            ]
        ];

        // Aplicar filtros se especificados
        if (!empty($params['categoria_id'])) {
            $eventos = array_filter($eventos, function($e) use ($params) {
                return $e['id_categoria'] == $params['categoria_id'];
            });
        }

        if (!empty($params['limite'])) {
            $eventos = array_slice($eventos, 0, $params['limite']);
        }

        return array_values($eventos);
    }

    // Categorias de exemplo
    private function getExampleCategories() {
        return [
            ['id_categoria' => 1, 'nome' => 'Tecnologia'],
            ['id_categoria' => 2, 'nome' => 'Negócios'],
            ['id_categoria' => 3, 'nome' => 'Marketing'],
            ['id_categoria' => 4, 'nome' => 'Design'],
            ['id_categoria' => 5, 'nome' => 'Sustentabilidade']
        ];
    }
}
?>