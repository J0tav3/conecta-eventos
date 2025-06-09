<?php
// ==========================================
// CONFIGURAÇÃO DE BANCO - VERSÃO RAILWAY CORRIGIDA
// Local: config/database.php
// ==========================================

class Database {
    private static $instance = null;
    private $conn = null;
    private $connectionInfo = [];

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            // Obter DATABASE_URL do Railway
            $database_url = getenv('DATABASE_URL');
            
            if (!$database_url) {
                throw new Exception("DATABASE_URL não encontrada");
            }

            // Parse da URL de conexão
            $url_parts = parse_url($database_url);
            
            if (!$url_parts) {
                throw new Exception("Formato de DATABASE_URL inválido");
            }

            $host = $url_parts['host'] ?? 'localhost';
            $port = $url_parts['port'] ?? 3306;
            $dbname = ltrim($url_parts['path'] ?? '', '/');
            $username = $url_parts['user'] ?? '';
            $password = $url_parts['pass'] ?? '';

            // Armazenar informações da conexão
            $this->connectionInfo = [
                'host' => $host,
                'port' => $port,
                'database' => $dbname,
                'username' => $username,
                'driver' => 'mysql',
                'status' => 'connecting'
            ];

            // DSN para MySQL
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            // Opções do PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            // Criar conexão
            $this->conn = new PDO($dsn, $username, $password, $options);
            $this->connectionInfo['status'] = 'connected';
            $this->connectionInfo['version'] = $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION);

            error_log("Conectado ao banco MySQL: {$host}:{$port}/{$dbname}");

        } catch (Exception $e) {
            $this->connectionInfo['status'] = 'failed';
            $this->connectionInfo['error'] = $e->getMessage();
            
            error_log("Erro de conexão com banco: " . $e->getMessage());
            
            // Não fazer throw aqui para permitir modo degradado
            $this->conn = null;
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getConnectionInfo() {
        return $this->connectionInfo;
    }

    public function isConnected() {
        return $this->conn !== null && $this->connectionInfo['status'] === 'connected';
    }

    public function testConnection() {
        if (!$this->conn) {
            return [
                'success' => false,
                'message' => 'Sem conexão com banco de dados',
                'info' => $this->connectionInfo
            ];
        }

        try {
            // Teste simples de query
            $stmt = $this->conn->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            if ($result && $result['test'] == 1) {
                return [
                    'success' => true,
                    'message' => 'Conexão OK',
                    'info' => $this->connectionInfo
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Teste de query falhou',
                    'info' => $this->connectionInfo
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro no teste: ' . $e->getMessage(),
                'info' => $this->connectionInfo
            ];
        }
    }

    public function createTables() {
        if (!$this->conn) {
            return [
                'success' => false,
                'message' => 'Sem conexão com banco'
            ];
        }

        try {
            // SQL para criar todas as tabelas
            $sql = "
            CREATE TABLE IF NOT EXISTS usuarios (
                id_usuario INT PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                tipo ENUM('organizador', 'participante') NOT NULL,
                telefone VARCHAR(20),
                cidade VARCHAR(100),
                estado VARCHAR(2),
                ativo BOOLEAN DEFAULT TRUE,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ultimo_acesso TIMESTAMP NULL
            );

            CREATE TABLE IF NOT EXISTS categorias (
                id_categoria INT PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(50) NOT NULL,
                descricao TEXT,
                cor VARCHAR(7) DEFAULT '#007bff',
                icone VARCHAR(50) DEFAULT 'fa-calendar',
                ativo BOOLEAN DEFAULT TRUE,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS eventos (
                id_evento INT PRIMARY KEY AUTO_INCREMENT,
                id_organizador INT NOT NULL,
                id_categoria INT,
                titulo VARCHAR(200) NOT NULL,
                descricao TEXT NOT NULL,
                data_inicio DATE NOT NULL,
                data_fim DATE NOT NULL,
                horario_inicio TIME NOT NULL,
                horario_fim TIME NOT NULL,
                local_nome VARCHAR(100) NOT NULL,
                local_endereco VARCHAR(200) NOT NULL,
                local_cidade VARCHAR(50) NOT NULL,
                local_estado VARCHAR(2) NOT NULL,
                local_cep VARCHAR(10),
                capacidade_maxima INT,
                preco DECIMAL(10,2) DEFAULT 0.00,
                evento_gratuito BOOLEAN DEFAULT TRUE,
                imagem_capa VARCHAR(255),
                link_externo VARCHAR(255),
                requisitos TEXT,
                informacoes_adicionais TEXT,
                status ENUM('rascunho', 'publicado', 'cancelado', 'finalizado') DEFAULT 'rascunho',
                destaque BOOLEAN DEFAULT FALSE,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (id_organizador) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
                FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS inscricoes (
                id_inscricao INT PRIMARY KEY AUTO_INCREMENT,
                id_evento INT NOT NULL,
                id_participante INT NOT NULL,
                data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pendente', 'confirmada', 'cancelada') DEFAULT 'confirmada',
                observacoes TEXT,
                presente BOOLEAN NULL,
                avaliacao_evento INT CHECK (avaliacao_evento BETWEEN 1 AND 5),
                comentario_avaliacao TEXT,
                data_avaliacao TIMESTAMP NULL,
                FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
                FOREIGN KEY (id_participante) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
                UNIQUE KEY unique_inscricao (id_evento, id_participante)
            );

            CREATE TABLE IF NOT EXISTS favoritos (
                id_favorito INT PRIMARY KEY AUTO_INCREMENT,
                id_usuario INT NOT NULL,
                id_evento INT NOT NULL,
                data_favoritado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
                FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
                UNIQUE KEY unique_favorito (id_usuario, id_evento)
            );

            CREATE TABLE IF NOT EXISTS notificacoes (
                id_notificacao INT PRIMARY KEY AUTO_INCREMENT,
                id_usuario INT NOT NULL,
                titulo VARCHAR(100) NOT NULL,
                mensagem TEXT NOT NULL,
                tipo ENUM('sistema', 'evento', 'inscricao', 'avaliacao') DEFAULT 'sistema',
                lida BOOLEAN DEFAULT FALSE,
                id_referencia INT,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_leitura TIMESTAMP NULL,
                FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
            );
            ";

            // Executar SQL
            $this->conn->exec($sql);

            // Inserir dados iniciais
            $this->insertInitialData();

            return [
                'success' => true,
                'message' => 'Tabelas criadas com sucesso'
            ];

        } catch (Exception $e) {
            error_log("Erro ao criar tabelas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao criar tabelas: ' . $e->getMessage()
            ];
        }
    }

    private function insertInitialData() {
        try {
            // Verificar se já existem dados
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM usuarios");
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return; // Já tem dados
            }

            // Inserir categorias
            $categorias = "
            INSERT IGNORE INTO categorias (nome, descricao, cor, icone) VALUES
            ('Tecnologia', 'Eventos relacionados à tecnologia', '#007bff', 'fa-laptop'),
            ('Negócios', 'Eventos corporativos e de negócios', '#28a745', 'fa-briefcase'),
            ('Educação', 'Eventos educacionais e de aprendizado', '#ffc107', 'fa-graduation-cap'),
            ('Arte e Cultura', 'Eventos artísticos e culturais', '#e83e8c', 'fa-palette'),
            ('Esportes', 'Eventos esportivos e atividades físicas', '#fd7e14', 'fa-running'),
            ('Música', 'Shows, concertos e eventos musicais', '#6f42c1', 'fa-music');
            ";
            
            $this->conn->exec($categorias);

            // Criar usuário administrador
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $admin = "
            INSERT IGNORE INTO usuarios (nome, email, senha, tipo) VALUES
            ('Administrador', 'admin@conectaeventos.com', '$adminPassword', 'organizador');
            ";
            
            $this->conn->exec($admin);

            error_log("Dados iniciais inseridos com sucesso");

        } catch (Exception $e) {
            error_log("Erro ao inserir dados iniciais: " . $e->getMessage());
        }
    }

    public function __clone() {
        // Previne clonagem
    }

    public function __wakeup() {
        // Previne deserialização
        throw new Exception("Cannot unserialize singleton");
    }
}

// Função para obter instância da base de dados
function getDatabase() {
    return Database::getInstance();
}

// Função para obter conexão
function getConnection() {
    $db = Database::getInstance();
    return $db->getConnection();
}

?>