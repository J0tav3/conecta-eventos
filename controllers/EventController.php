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
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
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

            // Preparar dados com validação
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

            // Iniciar transação para garantir consistência
            $this->conn->beginTransaction();

            try {
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

                // Bind dos parâmetros com tipos específicos
                $stmt->bindValue(':organizador_id', $organizador_id, PDO::PARAM_INT);
                $stmt->bindValue(':categoria_id', $id_categoria, PDO::PARAM_INT);
                $stmt->bindValue(':titulo', $titulo, PDO::PARAM_STR);
                $stmt->bindValue(':descricao', $descricao, PDO::PARAM_STR);
                $stmt->bindValue(':data_inicio', $data_inicio, PDO::PARAM_STR);
                $stmt->bindValue(':data_fim', $data_fim, PDO::PARAM_STR);
                $stmt->bindValue(':horario_inicio', $horario_inicio, PDO::PARAM_STR);
                $stmt->bindValue(':horario_fim', $horario_fim, PDO::PARAM_STR);
                $stmt->bindValue(':local_nome', $local_nome, PDO::PARAM_STR);
                $stmt->bindValue(':local_endereco', $local_endereco, PDO::PARAM_STR);
                $stmt->bindValue(':local_cidade', $local_cidade, PDO::PARAM_STR);
                $stmt->bindValue(':local_estado', $local_estado, PDO::PARAM_STR);
                $stmt->bindValue(':local_cep', $local_cep, PDO::PARAM_STR);
                $stmt->bindValue(':evento_gratuito', $evento_gratuito, PDO::PARAM_INT);
                $stmt->bindValue(':preco', $preco, PDO::PARAM_STR);
                $stmt->bindValue(':capacidade_maxima', $capacidade_maxima, PDO::PARAM_INT);
                $stmt->bindValue(':requisitos', $requisitos, PDO::PARAM_STR);
                $stmt->bindValue(':informacoes_adicionais', $informacoes_adicionais, PDO::PARAM_STR);

                $result = $stmt->execute();

                if ($result) {
                    $evento_id = $this->conn->lastInsertId();
                    $this->log("Evento criado com ID: " . $evento_id);

                    // Commit da transação
                    $this->conn->commit();

                    return [
                        'success' => true,
                        'message' => 'Evento criado com sucesso!',
                        'evento_id' => $evento_id
                    ];
                } else {
                    $this->conn->rollback();
                    $this->log("Falha ao executar INSERT");
                    return [
                        'success' => false,
                        'message' => 'Erro ao criar evento.'
                    ];
                }

            } catch (Exception $e) {
                $this->conn->rollback();
                $this->log("Erro na transação: " . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Erro interno: ' . $e->getMessage()
                ];
            }

        } catch (Exception $e) {
            $this->log("Exception no create: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Dados de exemplo para eventos
     */
    private function getExampleEvents($params = []) {
        $eventos = [
            [
                'id_evento' => 1,
                'titulo' => 'Workshop de Desenvolvimento Web',
                'descricao' => 'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
                'data_inicio' => date('Y-m-d', strtotime('+7 days')),
                'horario_inicio' => '14:00:00',
                'local_cidade' => 'São Paulo',
                'evento_gratuito' => 1,
                'preco' => 0,
                'imagem_capa' => '',
                'id_categoria' => 1,
                'nome_categoria' => 'Tecnologia',
                'total_inscritos' => 45,
                'nome_organizador' => 'Tech Academy'
            ],
            [
                'id_evento' => 2,
                'titulo' => 'Palestra: Empreendedorismo Digital',
                'descricao' => 'Como criar e escalar um negócio digital no mercado atual.',
                'data_inicio' => date('Y-m-d', strtotime('+10 days')),
                'horario_inicio' => '19:00:00',
                'local_cidade' => 'Rio de Janeiro',
                'evento_gratuito' => 0,
                'preco' => 50.00,
                'imagem_capa' => '',
                'id_categoria' => 2,
                'nome_categoria' => 'Negócios',
                'total_inscritos' => 32,
                'nome_organizador' => 'Business Institute'
            ]
        ];

        // Aplicar limite se especificado
        if (isset($params['limite']) && $params['limite'] > 0) {
            $eventos = array_slice($eventos, 0, $params['limite']);
        }

        return $eventos;
    }

    /**
     * Categorias de exemplo
     */
    private function getExampleCategories() {
        return [
            ['id_categoria' => 1, 'nome' => 'Tecnologia'],
            ['id_categoria' => 2, 'nome' => 'Negócios'],
            ['id_categoria' => 3, 'nome' => 'Marketing'],
            ['id_categoria' => 4, 'nome' => 'Design'],
            ['id_categoria' => 5, 'nome' => 'Educação']
        ];
    }

    /**
     * Verificar se usuário pode editar evento
     */
    public function canEdit($eventId) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        if (!$this->conn) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare("SELECT id_organizador FROM eventos WHERE id_evento = ?");
            $stmt->execute([$eventId]);
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
    public function update($eventId, $data) {
        if (!$this->conn) {
            return ['success' => false, 'message' => 'Banco de dados indisponível'];
        }

        if (!$this->canEdit($eventId)) {
            return ['success' => false, 'message' => 'Sem permissão para editar este evento'];
        }

        try {
            // Preparar dados
            $titulo = trim($data['titulo'] ?? '');
            $descricao = trim($data['descricao'] ?? '');
            $id_categoria = !empty($data['id_categoria']) ? (int)$data['id_categoria'] : null;
            $data_inicio = $data['data_inicio'] ?? '';
            $data_fim = $data['data_fim'] ?? '';
            $horario_inicio = $data['horario_inicio'] ?? '';
            $horario_fim = $data['horario_fim'] ?? '';
            $local_nome = trim($data['local_nome'] ?? '');
            $local_endereco = trim($data['local_endereco'] ?? '');
            $local_cidade = trim($data['local_cidade'] ?? '');
            $local_estado = $data['local_estado'] ?? '';
            $local_cep = $data['local_cep'] ?? null;
            $capacidade_maxima = !empty($data['capacidade_maxima']) ? (int)$data['capacidade_maxima'] : null;
            $evento_gratuito = isset($data['evento_gratuito']) ? 1 : 0;
            $preco = $evento_gratuito ? 0 : (float)($data['preco'] ?? 0);
            $requisitos = trim($data['requisitos'] ?? '');
            $informacoes_adicionais = trim($data['informacoes_adicionais'] ?? '');
            $status = $data['status'] ?? 'rascunho';
            $destaque = isset($data['destaque']) ? 1 : 0;

            $sql = "UPDATE eventos SET 
                        titulo = :titulo,
                        descricao = :descricao,
                        id_categoria = :id_categoria,
                        data_inicio = :data_inicio,
                        data_fim = :data_fim,
                        horario_inicio = :horario_inicio,
                        horario_fim = :horario_fim,
                        local_nome = :local_nome,
                        local_endereco = :local_endereco,
                        local_cidade = :local_cidade,
                        local_estado = :local_estado,
                        local_cep = :local_cep,
                        capacidade_maxima = :capacidade_maxima,
                        evento_gratuito = :evento_gratuito,
                        preco = :preco,
                        requisitos = :requisitos,
                        informacoes_adicionais = :informacoes_adicionais,
                        status = :status,
                        destaque = :destaque,
                        data_atualizacao = NOW()
                    WHERE id_evento = :id";

            $stmt = $this->conn->prepare($sql);
            
            $result = $stmt->execute([
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':id_categoria' => $id_categoria,
                ':data_inicio' => $data_inicio,
                ':data_fim' => $data_fim,
                ':horario_inicio' => $horario_inicio,
                ':horario_fim' => $horario_fim,
                ':local_nome' => $local_nome,
                ':local_endereco' => $local_endereco,
                ':local_cidade' => $local_cidade,
                ':local_estado' => $local_estado,
                ':local_cep' => $local_cep,
                ':capacidade_maxima' => $capacidade_maxima,
                ':evento_gratuito' => $evento_gratuito,
                ':preco' => $preco,
                ':requisitos' => $requisitos,
                ':informacoes_adicionais' => $informacoes_adicionais,
                ':status' => $status,
                ':destaque' => $destaque,
                ':id' => $eventId
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Evento atualizado com sucesso!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erro ao atualizar evento.'
                ];
            }

        } catch (Exception $e) {
            $this->log("Erro ao atualizar evento: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Formatar evento para exibição
     */
    public function formatEventForDisplay($evento) {
        if (!$evento) return null;

        $evento['data_formatada'] = date('d/m/Y', strtotime($evento['data_inicio']));
        $evento['horario_formatado'] = date('H:i', strtotime($evento['horario_inicio']));
        $evento['preco_formatado'] = $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.');
        $evento['vagas_esgotadas'] = $evento['capacidade_maxima'] && $evento['total_inscritos'] >= $evento['capacidade_maxima'];
        $evento['percentual_ocupacao'] = $evento['capacidade_maxima'] ? ($evento['total_inscritos'] / $evento['capacidade_maxima']) * 100 : 0;

        return $evento;
    }

    /**
     * Obter estatísticas do evento
     */
    public function getEventStats($eventId) {
        if (!$this->conn) {
            return null;
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(CASE WHEN i.status = 'confirmada' THEN 1 END) as inscritos_confirmados,
                    COUNT(CASE WHEN i.status = 'pendente' THEN 1 END) as inscritos_pendentes,
                    COUNT(CASE WHEN i.status = 'cancelada' THEN 1 END) as inscritos_cancelados,
                    COUNT(*) as total_inscricoes
                FROM inscricoes i 
                WHERE i.id_evento = ?
            ");
            
            $stmt->execute([$eventId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $this->log("Erro ao buscar estatísticas: " . $e->getMessage());
            return null;
        }
    }
}
?>