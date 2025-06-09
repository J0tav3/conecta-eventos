<?php
// ==========================================
// CONFIGURAÇÃO DE BANCO RAILWAY - VERSÃO 2.0
// Local: config/database.php
// ==========================================

class Database {
    private $conn;
    private static $instance = null;
    private $connectionAttempts = 0;
    private $maxAttempts = 3;
    
    public function __construct() {
        $this->initializeConnection();
    }
    
    /**
     * Singleton pattern para uma única instância da conexão
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obter conexão ativa
     */
    public function getConnection() {
        // Se não há conexão, tentar reconectar
        if ($this->conn === null) {
            $this->initializeConnection();
        }
        
        // Testar se a conexão ainda está ativa
        if ($this->conn && !$this->isConnectionAlive()) {
            $this->initializeConnection();
        }
        
        return $this->conn;
    }
    
    /**
     * Inicializar conexão com o banco
     */
    private function initializeConnection() {
        $this->connectionAttempts = 0;
        
        while ($this->connectionAttempts < $this->maxAttempts) {
            try {
                $this->connectionAttempts++;
                $this->logStep("Tentativa de conexão #{$this->connectionAttempts}");
                
                // Tentar MySQL (Railway Production)
                if ($this->connectMySQL()) {
                    $this->logStep("Conexão MySQL estabelecida com sucesso!");
                    $this->verifyAndSetupDatabase();
                    return;
                }
                
                // Fallback para SQLite (desenvolvimento/emergência)
                if ($this->connectSQLite()) {
                    $this->logStep("Conexão SQLite estabelecida (modo fallback)");
                    $this->verifyAndSetupDatabase();
                    return;
                }
                
            } catch (Exception $e) {
                $this->logError("Erro na tentativa #{$this->connectionAttempts}: " . $e->getMessage());
                
                if ($this->connectionAttempts >= $this->maxAttempts) {
                    throw new Exception("Falha ao conectar após {$this->maxAttempts} tentativas: " . $e->getMessage());
                }
                
                // Aguardar antes da próxima tentativa
                sleep(1);
            }
        }
        
        throw new Exception("Não foi possível estabelecer conexão com o banco de dados");
    }
    
    /**
     * Conectar com MySQL (Railway)
     */
    private function connectMySQL() {
        // Verificar se DATABASE_URL está disponível
        $databaseUrl = $this->getDatabaseUrl();
        
        if (!$databaseUrl) {
            $this->logError("DATABASE_URL não encontrada");
            return false;
        }
        
        $this->logStep("DATABASE_URL encontrada: " . substr($databaseUrl, 0, 20) . "...");
        
        // Parse da URL
        $urlParts = parse_url($databaseUrl);
        
        if (!$urlParts || !isset($urlParts['host'], $urlParts['user'], $urlParts['path'])) {
            $this->logError("DATABASE_URL mal formatada");
            return false;
        }
        
        $host = $urlParts['host'];
        $port = $urlParts['port'] ?? 3306;
        $dbname = ltrim($urlParts['path'], '/');
        $username = $urlParts['user'];
        $password = $urlParts['pass'] ?? '';
        
        $this->logStep("Conectando: {$username}@{$host}:{$port}/{$dbname}");
        
        // Construir DSN
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ];
        
        $this->conn = new PDO($dsn, $username, $password, $options);
        $this->logStep("Conexão PDO MySQL criada");
        
        return true;
    }
    
    /**
     * Conectar com SQLite (fallback)
     */
    private function connectSQLite() {
        $this->logStep("Tentando conexão SQLite (fallback)");
        
        $dbPath = __DIR__ . '/../conecta_eventos.db';
        $dsn = "sqlite:{$dbPath}";
        
        $this->conn = new PDO($dsn);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        $this->logStep("Conexão SQLite estabelecida");
        
        return true;
    }
    
    /**
     * Obter DATABASE_URL de múltiplas fontes
     */
    private function getDatabaseUrl() {
        // Prioridade: $_ENV, getenv(), variáveis do Railway
        $sources = [
            $_ENV['DATABASE_URL'] ?? null,
            getenv('DATABASE_URL'),
            $_SERVER['DATABASE_URL'] ?? null,
            // Variáveis específicas do Railway
            $this->buildRailwayUrl()
        ];
        
        foreach ($sources as $url) {
            if (!empty($url)) {
                return $url;
            }
        }
        
        return null;
    }
    
    /**
     * Construir URL do Railway a partir de variáveis separadas
     */
    private function buildRailwayUrl() {
        $host = getenv('MYSQLHOST') ?: getenv('DB_HOST');
        $port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
        $database = getenv('MYSQLDATABASE') ?: getenv('DB_NAME');
        $username = getenv('MYSQLUSER') ?: getenv('DB_USER');
        $password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD');
        
        if ($host && $database && $username) {
            return "mysql://{$username}:{$password}@{$host}:{$port}/{$database}";
        }
        
        return null;
    }
    
    /**
     * Verificar se a conexão está ativa
     */
    private function isConnectionAlive() {
        try {
            $this->conn->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Verificar e configurar estrutura do banco
     */
    private function verifyAndSetupDatabase() {
        try {
            $this->logStep("Verificando estrutura do banco...");
            
            // Verificar se as tabelas principais existem
            if (!$this->tableExists('usuarios')) {
                $this->logStep("Tabela 'usuarios' não encontrada - criando estrutura...");
                $this->createTables();
                $this->insertSampleData();
            } else {
                $this->logStep("Estrutura do banco verificada ✓");
            }
            
        } catch (Exception $e) {
            $this->logError("Erro ao verificar estrutura: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verificar se tabela existe
     */
    private function tableExists($tableName) {
        try {
            $stmt = $this->conn->prepare("SELECT 1 FROM {$tableName} LIMIT 1");
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Criar todas as tabelas
     */
    private function createTables() {
        $sql = $this->getSQLForCurrentDriver();
        
        // Executar comandos separadamente
        $commands = explode(';', $sql);
        
        foreach ($commands as $command) {
            $command = trim($command);
            if (!empty($command)) {
                try {
                    $this->conn->exec($command);
                    $this->logStep("Comando SQL executado: " . substr($command, 0, 50) . "...");
                } catch (PDOException $e) {
                    $this->logError("Erro no comando SQL: " . $e->getMessage());
                    throw $e;
                }
            }
        }
        
        $this->logStep("Todas as tabelas criadas com sucesso!");
    }
    
    /**
     * Obter SQL adequado para o driver atual
     */
    private function getSQLForCurrentDriver() {
        $driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'mysql') {
            return $this->getMySQLSchema();
        } else {
            return $this->getSQLiteSchema();
        }
    }
    
    /**
     * Schema MySQL
     */
    private function getMySQLSchema() {
        return "
        CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            tipo ENUM('organizador', 'participante') NOT NULL DEFAULT 'participante',
            telefone VARCHAR(20) NULL,
            cidade VARCHAR(100) NULL,
            estado VARCHAR(2) NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultimo_acesso TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS categorias (
            id_categoria INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(50) NOT NULL,
            descricao TEXT,
            cor VARCHAR(7) DEFAULT '#007bff',
            icone VARCHAR(50) DEFAULT 'fa-calendar',
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS favoritos (
            id_favorito INT PRIMARY KEY AUTO_INCREMENT,
            id_usuario INT NOT NULL,
            id_evento INT NOT NULL,
            data_favoritado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
            UNIQUE KEY unique_favorito (id_usuario, id_evento)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
    
    /**
     * Schema SQLite
     */
    private function getSQLiteSchema() {
        return "
        CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('organizador', 'participante')) DEFAULT 'participante',
            telefone VARCHAR(20),
            cidade VARCHAR(100),
            estado VARCHAR(2),
            ativo BOOLEAN DEFAULT 1,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            ultimo_acesso DATETIME NULL
        );

        CREATE TABLE IF NOT EXISTS categorias (
            id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(50) NOT NULL,
            descricao TEXT,
            cor VARCHAR(7) DEFAULT '#007bff',
            icone VARCHAR(50) DEFAULT 'fa-calendar',
            ativo BOOLEAN DEFAULT 1,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS eventos (
            id_evento INTEGER PRIMARY KEY AUTOINCREMENT,
            id_organizador INTEGER NOT NULL,
            id_categoria INTEGER,
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
            capacidade_maxima INTEGER,
            preco DECIMAL(10,2) DEFAULT 0.00,
            evento_gratuito BOOLEAN DEFAULT 1,
            imagem_capa VARCHAR(255),
            link_externo VARCHAR(255),
            requisitos TEXT,
            informacoes_adicionais TEXT,
            status VARCHAR(20) DEFAULT 'rascunho' CHECK (status IN ('rascunho', 'publicado', 'cancelado', 'finalizado')),
            destaque BOOLEAN DEFAULT 0,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_organizador) REFERENCES usuarios(id_usuario),
            FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        );

        CREATE TABLE IF NOT EXISTS inscricoes (
            id_inscricao INTEGER PRIMARY KEY AUTOINCREMENT,
            id_evento INTEGER NOT NULL,
            id_participante INTEGER NOT NULL,
            data_inscricao DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'confirmada' CHECK (status IN ('pendente', 'confirmada', 'cancelada')),
            observacoes TEXT,
            presente BOOLEAN NULL,
            avaliacao_evento INTEGER CHECK (avaliacao_evento BETWEEN 1 AND 5),
            comentario_avaliacao TEXT,
            data_avaliacao DATETIME NULL,
            FOREIGN KEY (id_evento) REFERENCES eventos(id_evento),
            FOREIGN KEY (id_participante) REFERENCES usuarios(id_usuario),
            UNIQUE(id_evento, id_participante)
        );

        CREATE TABLE IF NOT EXISTS favoritos (
            id_favorito INTEGER PRIMARY KEY AUTOINCREMENT,
            id_usuario INTEGER NOT NULL,
            id_evento INTEGER NOT NULL,
            data_favoritado DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
            FOREIGN KEY (id_evento) REFERENCES eventos(id_evento),
            UNIQUE(id_usuario, id_evento)
        );

        CREATE TABLE IF NOT EXISTS notificacoes (
            id_notificacao INTEGER PRIMARY KEY AUTOINCREMENT,
            id_usuario INTEGER NOT NULL,
            titulo VARCHAR(100) NOT NULL,
            mensagem TEXT NOT NULL,
            tipo VARCHAR(20) DEFAULT 'sistema' CHECK (tipo IN ('sistema', 'evento', 'inscricao', 'avaliacao')),
            lida BOOLEAN DEFAULT 0,
            id_referencia INTEGER,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_leitura DATETIME NULL,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        )";
    }
    
    /**
     * Inserir dados de exemplo
     */
    private function insertSampleData() {
        try {
            // Verificar se já existem dados
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM usuarios");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                $this->logStep("Dados já existem no banco - pulando inserção de exemplo");
                return;
            }
            
            $this->logStep("Inserindo dados de exemplo...");
            
            // Inserir categorias
            $categorias = [
                ['Tecnologia', 'Eventos relacionados à tecnologia', '#007bff', 'fa-laptop'],
                ['Negócios', 'Eventos corporativos e de negócios', '#28a745', 'fa-briefcase'],
                ['Educação', 'Eventos educacionais e de aprendizado', '#ffc107', 'fa-graduation-cap'],
                ['Arte e Cultura', 'Eventos artísticos e culturais', '#e83e8c', 'fa-palette'],
                ['Esportes', 'Eventos esportivos e atividades físicas', '#fd7e14', 'fa-running'],
                ['Música', 'Shows, concertos e eventos musicais', '#6f42c1', 'fa-music']
            ];
            
            $stmt = $this->conn->prepare("
                INSERT INTO categorias (nome, descricao, cor, icone) 
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($categorias as $cat) {
                $stmt->execute($cat);
            }
            
            // Criar usuário administrador
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("
                INSERT INTO usuarios (nome, email, senha, tipo) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute(['Administrador', 'admin@conectaeventos.com', $adminPassword, 'organizador']);
            
            // Criar evento de exemplo
            $stmt = $this->conn->prepare("
                INSERT INTO eventos (
                    id_organizador, id_categoria, titulo, descricao,
                    data_inicio, data_fim, horario_inicio, horario_fim,
                    local_nome, local_endereco, local_cidade, local_estado,
                    preco, evento_gratuito, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                1, 1, 'Workshop de Desenvolvimento Web',
                'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
                date('Y-m-d', strtotime('+1 week')),
                date('Y-m-d', strtotime('+1 week')),
                '09:00', '17:00',
                'Centro de Convenções Tech', 'Rua da Tecnologia, 123',
                'São Paulo', 'SP',
                0.00, 1, 'publicado'
            ]);
            
            $this->logStep("Dados de exemplo inseridos com sucesso!");
            
        } catch (Exception $e) {
            $this->logError("Erro ao inserir dados de exemplo: " . $e->getMessage());
            // Não lançar exceção - dados de exemplo são opcionais
        }
    }
    
    /**
     * Log de passos (para debug)
     */
    private function logStep($message) {
        error_log("[DB-SETUP] " . $message);
        
        // Em desenvolvimento, também exibir na tela
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            echo "<div class='debug-log'>[DB] {$message}</div>";
        }
    }
    
    /**
     * Log de erros
     */
    private function logError($message) {
        error_log("[DB-ERROR] " . $message);
        
        // Em desenvolvimento, também exibir na tela
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            echo "<div class='debug-error'>[DB-ERROR] {$message}</div>";
        }
    }
    
    /**
     * Testar conectividade
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if (!$conn) {
                return ['success' => false, 'message' => 'Falha ao obter conexão'];
            }
            
            // Teste simples
            $stmt = $conn->query('SELECT 1 as test');
            $result = $stmt->fetch();
            
            if ($result && $result['test'] == 1) {
                $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
                return [
                    'success' => true, 
                    'message' => "Conexão ativa com {$driver}",
                    'driver' => $driver
                ];
            }
            
            return ['success' => false, 'message' => 'Teste de query falhou'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Informações da conexão atual
     */
    public function getConnectionInfo() {
        try {
            $conn = $this->getConnection();
            if (!$conn) {
                return ['error' => 'Sem conexão ativa'];
            }
            
            $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
            $version = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
            
            $info = [
                'driver' => $driver,
                'version' => $version,
                'attempts' => $this->connectionAttempts,
                'status' => 'connected'
            ];
            
            // Informações específicas do MySQL
            if ($driver === 'mysql') {
                try {
                    $stmt = $conn->query('SELECT DATABASE() as current_db');
                    $result = $stmt->fetch();
                    $info['database'] = $result['current_db'];
                } catch (Exception $e) {
                    $info['database'] = 'unknown';
                }
            }
            
            return $info;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

// ==========================================
// FUNÇÕES AUXILIARES GLOBAIS
// ==========================================

/**
 * Obter instância global do banco
 */
function getDatabase() {
    return Database::getInstance();
}

/**
 * Obter conexão global (para compatibilidade)
 */
function getDatabaseConnection() {
    return Database::getInstance()->getConnection();
}

/**
 * Testar conectividade
 */
function testDatabaseConnection() {
    return Database::getInstance()->testConnection();
}

/**
 * Informações da conexão
 */
function getDatabaseInfo() {
    return Database::getInstance()->getConnectionInfo();
}
?>