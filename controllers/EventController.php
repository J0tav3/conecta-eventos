<?php
// ==========================================
// EVENT CONTROLLER - VERSÃO CORRIGIDA COMPLETA
// Local: controllers/EventController.php
// ==========================================

require_once __DIR__ . '/../config/database.php';

class EventController {
    private $db;
    private $conn;
    private $debug = true;

    public function __construct() {
        try {
            $this->db = Database::getInstance();
            $this->conn = $this->db->getConnection();
            
            if (!$this->conn) {
                throw new Exception("Falha ao conectar com o banco de dados");
            }
            
            $this->log("EventController conectado ao banco");
        } catch (Exception $e) {
            $this->log("Erro ao conectar: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function log($message) {
        if ($this->debug) {
            error_log("[EventController] " . $message);
        }
    }

    /**
     * Buscar eventos públicos
     */
    public function getPublicEvents($params = []) {
        if (!$this->conn) {
            $this->log("Sem conexão - retornando eventos de exemplo");
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
                     LEFT JOIN inscricoes i ON e.id_evento = i.id_evento AND i.status = 'confirmada'
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

            $this->log("Executando query: " . $query);
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
            
            $this->log("Encontrados " . count($eventos) . " eventos");
            return $eventos ?: [];

        } catch (Exception $e) {
            $this->log("Erro ao buscar eventos: " . $e->getMessage());
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
            $this->log("Encontradas " . count($categorias) . " categorias");
            return $categorias ?: $this->getExampleCategories();

        } catch (Exception $e) {
            $this->log("Erro ao buscar categorias: " . $e->getMessage());
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
                     LEFT JOIN inscricoes i ON e.id_evento = i.id_evento AND i.status = 'confirmada'
                     WHERE e.id_evento = :id
                     GROUP BY e.id_evento";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $evento = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->log("Evento encontrado: " . ($evento ? $evento['titulo'] : 'não encontrado'));
            return $evento;

        } catch (Exception $e) {
            $this->log("Erro ao buscar evento: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Criar novo evento
     */
    public function create($data) {
        if (!$this->conn) {
            $this->log("Sem conexão com banco");
            return ['success' => false, 'message' => 'Banco de dados indisponível'];
        }

        try {
            $this->log("Iniciando criação de evento");
            $this->log("Dados recebidos: " . json_encode(array_keys($data)));
            
            // Verificar se usuário está logado
            session_start();
            if (!isset($_SESSION['user_id'])) {
                $this->log("Usuário não logado");
                return ['success' => false, 'message' => 'Usuário não logado'];
            }

            $organizador_id = $_SESSION['user_id'];
            $this->log("Organizador ID: " . $organizador_id);

            // Validar dados obrigatórios
            $required = ['titulo', 'descricao', 'data_inicio', 'horario_inicio', 'local_nome', 'local_endereco', 'local_cidade', 'local_estado'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->log("Campo obrigatório ausente: " . $field);
                    return ['success' => false, 'message' => "Campo obrigatório: $field"];
                }
            }

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

            $this->log("Dados preparados para inserção");

            // SQL de inserção
            $sql = "INSERT INTO eventos (
                        id_organizador, id_categoria, titulo, descricao, 
                        data_inicio, data_fim, horario_inicio, horario_fim,
                        local_nome, local_endereco, local_cidade, local_estado, local_cep,
                        evento_gratuito, preco, capacidade_maxima,
                        requisitos, informacoes_adicionais, status, data_criacao
                    ) VALUES (
                        :organizador_id, :categoria_id, :titulo, :descricao,
                        :data_inicio, :data_fim, :horario_inicio, :horario_fim,
                        :local_nome, :local_endereco, :local_cidade, :local_estado, :local_cep,
                        :evento_gratuito, :preco, :capacidade_maxima,
                        :requisitos, :informacoes_adicionais, 'rascunho', NOW()
                    )";

            $stmt = $this->conn->prepare($sql);

            // Bind dos parâmetros
            $params = [
                ':organizador_id' => $organizador_id,
                ':categoria_id' => $id_categoria,
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':data_inicio' => $data_inicio,
                ':data_fim' => $data_fim,
                ':horario_inicio' => $horario_inicio,
                ':horario_fim' => $horario_fim,
                ':local_nome' => $local_nome,
                ':local_endereco' => $local_endereco,
                ':local_cidade' => $local_cidade,
                ':local_estado' => $local_estado,
                ':local_cep' => $local_cep,
                ':evento_gratuito' => $evento_gratuito,
                ':preco' => $preco,
                ':capacidade_maxima' => $capacidade_maxima,
                ':requisitos' => $requisitos,
                ':informacoes_adicionais' => $informacoes_adicionais
            ];

            $this->log("Executando INSERT com parâmetros: " . json_encode([
                'organizador_id' => $organizador_id,
                'titulo' => $titulo,
                'categoria_id' => $id_categoria,
                'data_inicio' => $data_inicio,
                'local_cidade' => $local_cidade
            ]));

            $result = $stmt->execute($params);
            
            $this->log("INSERT result: " . ($result ? 'TRUE' : 'FALSE'));
            $this->log("Affected rows: " . $stmt->rowCount());

            if ($result && $stmt->rowCount() > 0) {
                $evento_id = $this->conn->lastInsertId();
                $this->log("Evento criado com ID: " . $evento_id);
                
                // Verificar se foi realmente inserido
                $verify_stmt = $this->conn->prepare("SELECT id_evento, titulo FROM eventos WHERE id_evento = ?");
                $verify_stmt->execute([$evento_id]);
                $inserted_event = $verify_stmt->fetch();
                
                if ($inserted_event) {
                    $this->log("Evento verificado no banco: " . $inserted_event['titulo']);
                    return [
                        'success' => true,
                        'message' => 'Evento criado com sucesso!',
                        'evento_id' => $evento_id
                    ];
                } else {
                    $this->log("ERRO: Evento não encontrado após inserção");
                    return ['success' => false, 'message' => 'Erro ao verificar evento criado'];
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                $this->log("ERRO na inserção: " . json_encode($errorInfo));
                return ['success' => false, 'message' => 'Erro ao criar evento'];
            }

        } catch (Exception $e) {
            $this->log("Exception ao criar evento: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
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
            $this->log("Erro ao verificar permissão: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualizar evento
     */
    public function update($evento_id, $data) {
        if (!$this->conn) {
            return ['success' => false, 'message' => 'Banco de dados indisponível'];
        }

        try {
            // Verificar permissão
            if (!$this->canEdit($evento_id)) {
                return ['success' => false, 'message' => 'Você não tem permissão para editar este evento'];
            }

            // Preparar dados
            $titulo = trim($data['titulo']);
            $descricao = trim($data['descricao']);
            $id_categoria = !empty($data['id_categoria']) ? (int)$data['id_categoria'] : null;
            $data_inicio = $data['data_inicio'];
            $data_fim = $data['data_fim'];
            $horario_inicio = $data['horario_inicio'];
            $horario_fim = $data['horario_fim'];
            $local_nome = trim($data['local_nome']);
            $local_endereco = trim($data['local_endereco']);
            $local_cidade = trim($data['local_cidade']);
            $local_estado = $data['local_estado'];
            $local_cep = $data['local_cep'] ?: null;
            $evento_gratuito = isset($data['evento_gratuito']) ? 1 : 0;
            $preco = $evento_gratuito ? 0 : (float)($data['preco'] ?? 0);
            $capacidade_maxima = !empty($data['capacidade_maxima']) ? (int)$data['capacidade_maxima'] : null;
            $requisitos = !empty($data['requisitos']) ? trim($data['requisitos']) : null;
            $informacoes_adicionais = !empty($data['informacoes_adicionais']) ? trim($data['informacoes_adicionais']) : null;
            $status = $data['status'] ?? 'rascunho';
            $destaque = isset($data['destaque']) ? 1 : 0;

            $sql = "UPDATE eventos SET 
                        titulo = :titulo,
                        descricao = :descricao,
                        id_categoria = :categoria_id,
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
                        capacidade_maxima = :capacidade_maxima,
                        requisitos = :requisitos,
                        informacoes_adicionais = :informacoes_adicionais,
                        status = :status,
                        destaque = :destaque,
                        data_atualizacao = NOW()
                    WHERE id_evento = :evento_id";

            $stmt = $this->conn->prepare($sql);

            $params = [
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':categoria_id' => $id_categoria,
                ':data_inicio' => $data_inicio,
                ':data_fim' => $data_fim,
                ':horario_inicio' => $horario_inicio,
                ':horario_fim' => $horario_fim,
                ':local_nome' => $local_nome,
                ':local_endereco' => $local_endereco,
                ':local_cidade' => $local_cidade,
                ':local_estado' => $local_estado,
                ':local_cep' => $local_cep,
                ':evento_gratuito' => $evento_gratuito,
                ':preco' => $preco,
                ':capacidade_maxima' => $capacidade_maxima,
                ':requisitos' => $requisitos,
                ':informacoes_adicionais' => $informacoes_adicionais,
                ':status' => $status,
                ':destaque' => $destaque,
                ':evento_id' => $evento_id
            ];

            $result = $stmt->execute($params);

            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Evento atualizado com sucesso!'
                ];
            } else {
                return ['success' => false, 'message' => 'Nenhuma alteração foi feita'];
            }

        } catch (Exception $e) {
            $this->log("Erro ao atualizar evento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
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
                     LEFT JOIN inscricoes i ON e.id_evento = i.id_evento AND i.status = 'confirmada'
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
            $this->log("Erro ao buscar eventos do organizador: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Teste de conexão
     */
    public function testConnection() {
        if (!$this->conn) {
            return [
                'success' => false,
                'message' => 'Sem conexão com banco'
            ];
        }
        
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM eventos");
            $result = $stmt->fetch();
            
            return [
                'success' => true,
                'message' => 'Conexão OK - ' . $result['total'] . ' eventos no banco'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro no teste: ' . $e->getMessage()
            ];
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