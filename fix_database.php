<?php
// ==========================================
// SCRIPT PARA CORRIGIR ESTRUTURA DO BANCO
// Local: fix_database.php
// ==========================================

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Correção da Estrutura do Banco de Dados</h2>";

try {
    // Verificar DATABASE_URL
    $database_url = getenv('DATABASE_URL');
    if (!$database_url) {
        echo "❌ DATABASE_URL não encontrada<br>";
        exit;
    }
    
    // Parse da URL
    $url_parts = parse_url($database_url);
    $host = $url_parts['host'];
    $port = $url_parts['port'] ?? 3306;
    $dbname = ltrim($url_parts['path'], '/');
    $username = $url_parts['user'];
    $password = $url_parts['pass'];
    
    echo "🔗 Conectando ao banco: $host:$port/$dbname<br>";
    
    // Conectar
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Conectado com sucesso<br><br>";
    
    // 1. Verificar se tabela usuarios existe
    echo "<h3>1. Verificando tabela usuarios...</h3>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Tabela usuarios não existe. Criando...<br>";
        
        $createTable = "
        CREATE TABLE usuarios (
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
        )";
        
        $pdo->exec($createTable);
        echo "✅ Tabela usuarios criada<br>";
    } else {
        echo "✅ Tabela usuarios existe<br>";
    }
    
    // 2. Verificar estrutura da tabela
    echo "<h3>2. Verificando estrutura da tabela...</h3>";
    
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll();
    
    $existingColumns = [];
    foreach ($columns as $col) {
        $existingColumns[$col['Field']] = $col;
    }
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Verificar e adicionar colunas necessárias
    echo "<h3>3. Verificando colunas obrigatórias...</h3>";
    
    $requiredColumns = [
        'id_usuario' => 'INT PRIMARY KEY AUTO_INCREMENT',
        'nome' => 'VARCHAR(100) NOT NULL',
        'email' => 'VARCHAR(100) UNIQUE NOT NULL',
        'senha' => 'VARCHAR(255) NOT NULL',
        'tipo' => "ENUM('organizador', 'participante') NOT NULL",
        'telefone' => 'VARCHAR(20)',
        'cidade' => 'VARCHAR(100)',
        'estado' => 'VARCHAR(2)',
        'ativo' => 'BOOLEAN DEFAULT TRUE',
        'data_criacao' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'ultimo_acesso' => 'TIMESTAMP NULL'
    ];
    
    foreach ($requiredColumns as $colName => $colDef) {
        if (!isset($existingColumns[$colName])) {
            echo "⚠️ Coluna '$colName' não existe. Adicionando...<br>";
            try {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN $colName $colDef");
                echo "✅ Coluna '$colName' adicionada<br>";
            } catch (Exception $e) {
                echo "❌ Erro ao adicionar coluna '$colName': " . $e->getMessage() . "<br>";
            }
        } else {
            echo "✅ Coluna '$colName' existe<br>";
        }
    }
    
    // 4. Criar outras tabelas necessárias
    echo "<h3>4. Criando outras tabelas...</h3>";
    
    $tables = [
        'categorias' => "
        CREATE TABLE IF NOT EXISTS categorias (
            id_categoria INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(50) NOT NULL,
            descricao TEXT,
            cor VARCHAR(7) DEFAULT '#007bff',
            icone VARCHAR(50) DEFAULT 'fa-calendar',
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'eventos' => "
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
        )",
        
        'inscricoes' => "
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
        )",
        
        'favoritos' => "
        CREATE TABLE IF NOT EXISTS favoritos (
            id_favorito INT PRIMARY KEY AUTO_INCREMENT,
            id_usuario INT NOT NULL,
            id_evento INT NOT NULL,
            data_favoritado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
            UNIQUE KEY unique_favorito (id_usuario, id_evento)
        )",
        
        'notificacoes' => "
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
        )"
    ];
    
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Tabela '$tableName' criada/verificada<br>";
        } catch (Exception $e) {
            echo "⚠️ Erro na tabela '$tableName': " . $e->getMessage() . "<br>";
        }
    }
    
    // 5. Inserir dados iniciais
    echo "<h3>5. Inserindo dados iniciais...</h3>";
    
    // Verificar se já existem categorias
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias");
    $totalCategorias = $stmt->fetch()['total'];
    
    if ($totalCategorias == 0) {
        echo "📝 Inserindo categorias padrão...<br>";
        
        $categorias = [
            ['Tecnologia', 'Eventos relacionados à tecnologia', '#007bff', 'fa-laptop'],
            ['Negócios', 'Eventos corporativos e de negócios', '#28a745', 'fa-briefcase'],
            ['Educação', 'Eventos educacionais e de aprendizado', '#ffc107', 'fa-graduation-cap'],
            ['Arte e Cultura', 'Eventos artísticos e culturais', '#e83e8c', 'fa-palette'],
            ['Esportes', 'Eventos esportivos e atividades físicas', '#fd7e14', 'fa-running'],
            ['Música', 'Shows, concertos e eventos musicais', '#6f42c1', 'fa-music'],
            ['Saúde', 'Eventos sobre saúde e bem-estar', '#20c997', 'fa-heartbeat'],
            ['Marketing', 'Eventos de marketing e vendas', '#6610f2', 'fa-bullhorn']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categorias (nome, descricao, cor, icone) VALUES (?, ?, ?, ?)");
        
        foreach ($categorias as $cat) {
            $stmt->execute($cat);
        }
        
        echo "✅ " . count($categorias) . " categorias inseridas<br>";
    } else {
        echo "✅ Categorias já existem ($totalCategorias encontradas)<br>";
    }
    
    // Verificar se já existe usuário admin
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'organizador'");
    $totalOrganizadores = $stmt->fetch()['total'];
    
    if ($totalOrganizadores == 0) {
        echo "👤 Criando usuário administrador...<br>";
        
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'Administrador',
            'admin@conectaeventos.com',
            $adminPassword,
            'organizador'
        ]);
        
        echo "✅ Usuário administrador criado<br>";
        echo "📧 Email: admin@conectaeventos.com<br>";
        echo "🔑 Senha: admin123<br>";
    } else {
        echo "✅ Organizadores já existem ($totalOrganizadores encontrados)<br>";
    }
    
    // 6. Resumo final
    echo "<h3>6. Resumo do banco de dados:</h3>";
    
    $tables = ['usuarios', 'categorias', 'eventos', 'inscricoes', 'favoritos', 'notificacoes'];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Tabela</th><th>Registros</th><th>Status</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
            $count = $stmt->fetch()['total'];
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$count</td>";
            echo "<td style='color: green;'>✅ OK</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>-</td>";
            echo "<td style='color: red;'>❌ Erro</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<h3>🎉 Banco de dados configurado com sucesso!</h3>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Testar o registro de novos usuários</li>";
    echo "<li>✅ Verificar login com conta admin</li>";
    echo "<li>✅ Criar alguns eventos de teste</li>";
    echo "</ul>";
    
    echo "<p><a href='test_register.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Executar Teste de Registro</a></p>";
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Ir para o Site</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Erro:</h3>";
    echo "<p>Mensagem: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
    echo "<pre style='background: #f8f8f8; padding: 10px; overflow: auto;'>" . $e->getTraceAsString() . "</pre>";
}
?>