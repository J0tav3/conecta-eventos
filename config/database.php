<?php
// ========================================
// CONFIGURAÇÃO SQLITE TEMPORÁRIA
// ========================================
// Use apenas para teste rápido no Railway
// Substitua config/database.php por este código temporariamente
// ========================================

class Database {
    private $conn;
    
    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        
        try {
            // Tentar MySQL primeiro (produção)
            if (isset($_ENV['DATABASE_URL'])) {
                $url = parse_url($_ENV['DATABASE_URL']);
                
                $host = $url['host'];
                $dbname = ltrim($url['path'], '/');
                $username = $url['user'];
                $password = $url['pass'];
                $port = $url['port'] ?? 3306;
                
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                $this->conn = new PDO($dsn, $username, $password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                return $this->conn;
            }
            
            // Fallback para SQLite (temporário)
            $dbPath = __DIR__ . '/../temp_database.sqlite';
            $dsn = "sqlite:$dbPath";
            
            $this->conn = new PDO($dsn);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Criar tabelas se não existirem
            $this->createTables();
            
            return $this->conn;
            
        } catch(PDOException $e) {
            error_log("Erro na conexão: " . $e->getMessage());
            throw new Exception("Erro na conexão com o banco de dados");
        }
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('organizador', 'participante')),
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
        ";
        
        $this->conn->exec($sql);
        
        // Inserir dados de exemplo se não existirem
        $this->insertSampleData();
    }
    
    private function insertSampleData() {
        // Verificar se já existe dados
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM usuarios");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            return; // Dados já existem
        }
        
        // Inserir categorias
        $categorias = "
        INSERT INTO categorias (nome, descricao, cor, icone) VALUES
        ('Tecnologia', 'Eventos relacionados à tecnologia', '#007bff', 'fa-laptop'),
        ('Negócios', 'Eventos corporativos e de negócios', '#28a745', 'fa-briefcase'),
        ('Educação', 'Eventos educacionais e de aprendizado', '#ffc107', 'fa-graduation-cap'),
        ('Arte e Cultura', 'Eventos artísticos e culturais', '#e83e8c', 'fa-palette'),
        ('Esportes', 'Eventos esportivos e atividades físicas', '#fd7e14', 'fa-running'),
        ('Música', 'Shows, concertos e eventos musicais', '#6f42c1', 'fa-music');
        ";
        
        $this->conn->exec($categorias);
        
        // Criar usuário admin
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $admin = "
        INSERT INTO usuarios (nome, email, senha, tipo) VALUES
        ('Administrador', 'admin@conectaeventos.com', '$adminPassword', 'organizador');
        ";
        
        $this->conn->exec($admin);
        
        // Criar evento de exemplo
        $evento = "
        INSERT INTO eventos (
            id_organizador, id_categoria, titulo, descricao,
            data_inicio, data_fim, horario_inicio, horario_fim,
            local_nome, local_endereco, local_cidade, local_estado,
            preco, evento_gratuito, status
        ) VALUES (
            1, 1, 'Workshop de Desenvolvimento Web',
            'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
            '" . date('Y-m-d', strtotime('+1 week')) . "',
            '" . date('Y-m-d', strtotime('+1 week')) . "',
            '09:00', '17:00',
            'Centro de Convenções Tech', 'Rua da Tecnologia, 123',
            'São Paulo', 'SP',
            0.00, 1, 'publicado'
        );
        ";
        
        $this->conn->exec($evento);
    }
}
?>