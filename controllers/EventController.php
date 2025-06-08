<?php
// ==========================================
// EVENT CONTROLLER - VERSÃO CORRIGIDA
// Local: controllers/EventController.php
// ==========================================

// Remover os requires que estão causando erro
// require_once '../models/Event.php';
// require_once '../models/User.php';
// require_once '../models/Category.php';

class EventController {
    private $db;
    private $conn;

    public function __construct() {
        // Tentar conectar com banco, mas não falhar se não conseguir
        try {
            if (class_exists('Database')) {
                $this->db = new Database();
                $this->conn = $this->db->getConnection();
            }
        } catch (Exception $e) {
            error_log("EventController: Erro ao conectar com banco: " . $e->getMessage());
            $this->conn = null;
        }
    }

    // Buscar eventos públicos
    public function getPublicEvents($params = []) {
        // Se não há conexão, retornar dados de exemplo
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
                     LEFT JOIN categorias c ON e.categoria_id = c.id_categoria
                     LEFT JOIN usuarios u ON e.organizador_id = u.id_usuario
                     LEFT JOIN inscricoes i ON e.id_evento = i.evento_id
                     WHERE e.status = 'publicado'
                     AND e.data_inicio >= CURDATE()";

            // Aplicar filtros
            if (!empty($params['categoria_id'])) {
                $query .= " AND e.categoria_id = :categoria_id";
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
                $query .= " ORDER BY total_inscritos DESC, e.data_inicio ASC";
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

    // Buscar categorias
    public function getCategories() {
        // Se não há conexão, retornar dados de exemplo
        if (!$this->conn) {
            return $this->getExampleCategories();
        }

        try {
            $query = "SELECT * FROM categorias WHERE status = 'ativo' ORDER BY nome ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $categorias ?: $this->getExampleCategories();

        } catch (Exception $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            return $this->getExampleCategories();
        }
    }

    // Buscar evento por ID
    public function getEventById($id) {
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
            return null;
        }
    }

    // Formatar evento para exibição
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

    // Dados de exemplo quando não há banco
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
                'categoria_id' => 1,
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
                'categoria_id' => 2,
                'nome_categoria' => 'Negócios',
                'total_inscritos' => 32
            ],
            [
                'id_evento' => 3,
                'titulo' => 'Curso: Marketing Digital Avançado',
                'descricao' => 'Estratégias avançadas de marketing digital e growth hacking.',
                'data_inicio' => date('Y-m-d', strtotime('+14 days')),
                'horario_inicio' => '09:00:00',
                'local_cidade' => 'Belo Horizonte',
                'evento_gratuito' => true,
                'preco' => 0,
                'imagem_capa' => '',
                'categoria_id' => 3,
                'nome_categoria' => 'Marketing',
                'total_inscritos' => 28
            ],
            [
                'id_evento' => 4,
                'titulo' => 'Meetup: Inteligência Artificial',
                'descricao' => 'Discussões sobre o futuro da IA e suas aplicações práticas.',
                'data_inicio' => date('Y-m-d', strtotime('+21 days')),
                'horario_inicio' => '18:30:00',
                'local_cidade' => 'Porto Alegre',
                'evento_gratuito' => true,
                'preco' => 0,
                'imagem_capa' => '',
                'categoria_id' => 1,
                'nome_categoria' => 'Tecnologia',
                'total_inscritos' => 67
            ],
            [
                'id_evento' => 5,
                'titulo' => 'Workshop: Design UX/UI',
                'descricao' => 'Princípios fundamentais de design de experiência do usuário.',
                'data_inicio' => date('Y-m-d', strtotime('+17 days')),
                'horario_inicio' => '13:00:00',
                'local_cidade' => 'Brasília',
                'evento_gratuito' => false,
                'preco' => 75.00,
                'imagem_capa' => '',
                'categoria_id' => 4,
                'nome_categoria' => 'Design',
                'total_inscritos' => 23
            ],
            [
                'id_evento' => 6,
                'titulo' => 'Conferência: Inovação e Sustentabilidade',
                'descricao' => 'Como a tecnologia pode ajudar na criação de um futuro sustentável.',
                'data_inicio' => date('Y-m-d', strtotime('+28 days')),
                'horario_inicio' => '08:00:00',
                'local_cidade' => 'Curitiba',
                'evento_gratuito' => false,
                'preco' => 120.00,
                'imagem_capa' => '',
                'categoria_id' => 5,
                'nome_categoria' => 'Sustentabilidade',
                'total_inscritos' => 89
            ]
        ];

        // Aplicar filtros se especificados
        if (!empty($params['categoria_id'])) {
            $eventos = array_filter($eventos, function($e) use ($params) {
                return $e['categoria_id'] == $params['categoria_id'];
            });
        }

        if (!empty($params['busca'])) {
            $eventos = array_filter($eventos, function($e) use ($params) {
                return stripos($e['titulo'], $params['busca']) !== false || 
                       stripos($e['descricao'], $params['busca']) !== false;
            });
        }

        if (!empty($params['cidade'])) {
            $eventos = array_filter($eventos, function($e) use ($params) {
                return stripos($e['local_cidade'], $params['cidade']) !== false;
            });
        }

        if (isset($params['gratuito'])) {
            $eventos = array_filter($eventos, function($e) use ($params) {
                return $e['evento_gratuito'] == $params['gratuito'];
            });
        }

        // Ordenar
        if (isset($params['ordem']) && $params['ordem'] === 'destaque') {
            usort($eventos, function($a, $b) {
                return $b['total_inscritos'] - $a['total_inscritos'];
            });
        }

        // Limitar resultados
        if (isset($params['limite'])) {
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
            ['id_categoria' => 5, 'nome' => 'Sustentabilidade'],
            ['id_categoria' => 6, 'nome' => 'Educação'],
            ['id_categoria' => 7, 'nome' => 'Entretenimento'],
            ['id_categoria' => 8, 'nome' => 'Saúde'],
            ['id_categoria' => 9, 'nome' => 'Esportes'],
            ['id_categoria' => 10, 'nome' => 'Arte & Cultura']
        ];
    }
}