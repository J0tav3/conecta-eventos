<?php
// ==========================================
// EVENT CONTROLLER - VERSÃO CORRIGIDA
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
                $