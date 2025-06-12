<?php
// ==========================================
// SCRIPT DE MIGRAÇÃO DO BANCO DE DADOS
// Local: database-setup.php (na raiz do projeto)
// ==========================================

require_once 'config/database.php';

function setupDatabase() {
    echo "🚀 Iniciando configuração do banco de dados...\n";
    
    try {
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        if (!$conn) {
            throw new Exception("Falha na conexão com o banco");
        }
        
        echo "✅ Conectado ao banco MySQL\n";
        
        // 1. CRIAR TABELAS PRINCIPAIS (se não existirem)
        echo "📝 Criando tabelas principais...\n";
        $result = $database->createTables();
        
        if ($result['success']) {
            echo "✅ Tabelas principais criadas/verificadas\n";
        } else {
            echo "❌ Erro ao criar tabelas: " . $result['message'] . "\n";
        }
        
        // 2. EXECUTAR SCRIPT DE MELHORIAS
        echo "🔧 Executando melhorias na estrutura...\n";
        executeImprovements($conn);
        
        echo "🎉 Configuração do banco concluída com sucesso!\n";
        
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "\n";
        return false;
    }
    
    return true;
}

function executeImprovements($conn) {
    // Script SQL das melhorias (baseado no paste.txt)
    $improvements = "
        -- ==========================================
        -- MELHORIAS NA ESTRUTURA DO BANCO
        -- ==========================================
        
        -- Tabela para logs de eventos (histórico de alterações)
        CREATE TABLE IF NOT EXISTS event_logs (
            id_log INT AUTO_INCREMENT PRIMARY KEY,
            id_evento INT NOT NULL,
            id_usuario INT NOT NULL,
            acao VARCHAR(50) NOT NULL,
            detalhes TEXT,
            data_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            INDEX idx_evento_log (id_evento),
            INDEX idx_usuario_log (id_usuario),
            INDEX idx_data_log (data_log)
        );
        
        -- Tabela para auto-save (rascunhos automáticos)
        CREATE TABLE IF NOT EXISTS event_drafts (
            id_draft INT AUTO_INCREMENT PRIMARY KEY,
            id_evento INT NOT NULL,
            id_usuario INT NOT NULL,
            draft_data JSON NOT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
            UNIQUE KEY unique_event_draft (id_evento, id_usuario)
        );
        
        -- Adicionar campos extras na tabela eventos se não existirem
        SET @sql = (SELECT IF(
            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE table_name = 'eventos' 
             AND table_schema = DATABASE() 
             AND column_name = 'destaque') > 0,
            'SELECT \"Campo destaque já existe\"',
            'ALTER TABLE eventos ADD COLUMN destaque BOOLEAN DEFAULT FALSE AFTER status'
        ));
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        SET @sql = (SELECT IF(
            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE table_name = 'eventos' 
             AND table_schema = DATABASE() 
             AND column_name = 'link_externo') > 0,
            'SELECT \"Campo link_externo já existe\"',
            'ALTER TABLE eventos ADD COLUMN link_externo VARCHAR(255) NULL AFTER informacoes_adicionais'
        ));
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        SET @sql = (SELECT IF(
            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE table_name = 'eventos' 
             AND table_schema = DATABASE() 
             AND column_name = 'meta_keywords') > 0,
            'SELECT \"Campo meta_keywords já existe\"',
            'ALTER TABLE eventos ADD COLUMN meta_keywords TEXT NULL AFTER link_externo'
        ));
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        SET @sql = (SELECT IF(
            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE table_name = 'eventos' 
             AND table_schema = DATABASE() 
             AND column_name = 'meta_description') > 0,
            'SELECT \"Campo meta_description já existe\"',
            'ALTER TABLE eventos ADD COLUMN meta_description TEXT NULL AFTER meta_keywords'
        ));
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Adicionar índices para melhor performance
        CREATE INDEX IF NOT EXISTS idx_organizador_status ON eventos (id_organizador, status);
        CREATE INDEX IF NOT EXISTS idx_data_inicio ON eventos (data_inicio);
        CREATE INDEX IF NOT EXISTS idx_status_destaque ON eventos (status, destaque);
        CREATE INDEX IF NOT EXISTS idx_eventos_busca ON eventos(titulo, descricao);
        CREATE INDEX IF NOT EXISTS idx_eventos_local ON eventos(local_cidade, local_estado);
        CREATE INDEX IF NOT EXISTS idx_eventos_data_status ON eventos(data_inicio, status);
    ";
    
    try {
        // Executar cada comando SQL separadamente
        $commands = explode(';', $improvements);
        
        foreach ($commands as $command) {
            $command = trim($command);
            if (!empty($command) && !preg_match('/^--/', $command)) {
                try {
                    $conn->exec($command);
                    echo "✅ Comando executado: " . substr($command, 0, 50) . "...\n";
                } catch (Exception $e) {
                    // Alguns comandos podem falhar se já existirem, isso é normal
                    if (!strpos($e->getMessage(), 'already exists') && 
                        !strpos($e->getMessage(), 'Duplicate key')) {
                        echo "⚠️  Aviso: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        
        // Executar procedures e functions separadamente
        createStoredProcedures($conn);
        createViews($conn);
        insertSampleData($conn);
        
    } catch (Exception $e) {
        echo "❌ Erro ao executar melhorias: " . $e->getMessage() . "\n";
        throw $e;
    }
}

function createStoredProcedures($conn) {
    echo "📦 Criando procedures e functions...\n";
    
    // Procedure para limpar logs antigos
    $cleanupProcedure = "
    DROP PROCEDURE IF EXISTS CleanOldEventLogs;
    CREATE PROCEDURE CleanOldEventLogs(IN days_old INT)
    BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            RESIGNAL;
        END;
        
        START TRANSACTION;
        
        DELETE FROM event_logs 
        WHERE data_log < DATE_SUB(NOW(), INTERVAL days_old DAY);
        
        DELETE FROM event_drafts 
        WHERE data_atualizacao < DATE_SUB(NOW(), INTERVAL days_old DAY);
        
        COMMIT;
        
        SELECT ROW_COUNT() as deleted_records;
    END
    ";
    
    try {
        $conn->exec($cleanupProcedure);
        echo "✅ Procedure CleanOldEventLogs criada\n";
    } catch (Exception $e) {
        echo "⚠️  Aviso ao criar procedure: " . $e->getMessage() . "\n";
    }
    
    // Function para estatísticas do evento
    $statsFunction = "
    DROP FUNCTION IF EXISTS GetEventStats;
    CREATE FUNCTION GetEventStats(event_id INT) 
    RETURNS JSON
    READS SQL DATA
    DETERMINISTIC
    BEGIN
        DECLARE stats JSON;
        
        SELECT JSON_OBJECT(
            'total_inscricoes', COALESCE(COUNT(i.id_inscricao), 0),
            'inscricoes_confirmadas', COALESCE(SUM(CASE WHEN i.status = 'confirmada' THEN 1 ELSE 0 END), 0),
            'inscricoes_pendentes', COALESCE(SUM(CASE WHEN i.status = 'pendente' THEN 1 ELSE 0 END), 0),
            'inscricoes_canceladas', COALESCE(SUM(CASE WHEN i.status = 'cancelada' THEN 1 ELSE 0 END), 0),
            'receita_total', COALESCE(SUM(CASE WHEN i.status = 'confirmada' AND e.evento_gratuito = 0 THEN e.preco ELSE 0 END), 0),
            'capacidade_ocupada', COALESCE(
                CASE 
                    WHEN e.capacidade_maxima > 0 THEN 
                        ROUND((COUNT(CASE WHEN i.status = 'confirmada' THEN 1 END) / e.capacidade_maxima) * 100, 2)
                    ELSE 0 
                END, 0
            ),
            'dias_ate_evento', DATEDIFF(e.data_inicio, CURDATE()),
            'evento_status', e.status
        ) INTO stats
        FROM eventos e
        LEFT JOIN inscricoes i ON e.id_evento = i.id_evento
        WHERE e.id_evento = event_id
        GROUP BY e.id_evento;
        
        RETURN COALESCE(stats, JSON_OBJECT());
    END
    ";
    
    try {
        $conn->exec($statsFunction);
        echo "✅ Function GetEventStats criada\n";
    } catch (Exception $e) {
        echo "⚠️  Aviso ao criar function: " . $e->getMessage() . "\n";
    }
}

function createViews($conn) {
    echo "👁️  Criando views...\n";
    
    $eventsView = "
    CREATE OR REPLACE VIEW eventos_com_stats AS
    SELECT 
        e.*,
        c.nome as categoria_nome,
        u.nome as organizador_nome,
        u.email as organizador_email,
        COUNT(DISTINCT i.id_inscricao) as total_inscricoes,
        COUNT(DISTINCT CASE WHEN i.status = 'confirmada' THEN i.id_inscricao END) as inscricoes_confirmadas,
        COUNT(DISTINCT CASE WHEN i.status = 'pendente' THEN i.id_inscricao END) as inscricoes_pendentes,
        COUNT(DISTINCT f.id_favorito) as total_favoritos,
        COALESCE(
            CASE 
                WHEN e.capacidade_maxima > 0 THEN 
                    ROUND((COUNT(DISTINCT CASE WHEN i.status = 'confirmada' THEN i.id_inscricao END) / e.capacidade_maxima) * 100, 2)
                ELSE 0 
            END, 0
        ) as percentual_ocupacao,
        COALESCE(
            SUM(CASE WHEN i.status = 'confirmada' AND e.evento_gratuito = 0 THEN e.preco ELSE 0 END), 0
        ) as receita_total,
        DATEDIFF(e.data_inicio, CURDATE()) as dias_ate_evento,
        CASE 
            WHEN e.data_inicio < CURDATE() THEN 'passado'
            WHEN e.data_inicio = CURDATE() THEN 'hoje'
            WHEN DATEDIFF(e.data_inicio, CURDATE()) <= 7 THEN 'esta_semana'
            WHEN DATEDIFF(e.data_inicio, CURDATE()) <= 30 THEN 'este_mes'
            ELSE 'futuro'
        END as periodo_evento
    FROM eventos e
    LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
    LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
    LEFT JOIN inscricoes i ON e.id_evento = i.id_evento
    LEFT JOIN favoritos f ON e.id_evento = f.id_evento
    GROUP BY e.id_evento
    ";
    
    try {
        $conn->exec($eventsView);
        echo "✅ View eventos_com_stats criada\n";
    } catch (Exception $e) {
        echo "⚠️  Aviso ao criar view: " . $e->getMessage() . "\n";
    }
}

function insertSampleData($conn) {
    echo "📝 Inserindo dados de exemplo...\n";
    
    // Verificar se já existem categorias
    $stmt = $conn->query("SELECT COUNT(*) as count FROM categorias");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        $sampleCategories = "
        INSERT INTO categorias (nome, descricao, cor, ativo) VALUES
        ('Tecnologia', 'Eventos relacionados à tecnologia e inovação', '#007bff', 1),
        ('Negócios', 'Eventos de empreendedorismo e negócios', '#28a745', 1),
        ('Marketing', 'Eventos de marketing e publicidade', '#ffc107', 1),
        ('Design', 'Eventos de design e criatividade', '#e83e8c', 1),
        ('Educação', 'Eventos educacionais e de ensino', '#17a2b8', 1),
        ('Saúde', 'Eventos relacionados à saúde e bem-estar', '#20c997', 1),
        ('Arte', 'Eventos artísticos e culturais', '#6f42c1', 1),
        ('Esporte', 'Eventos esportivos e atividades físicas', '#fd7e14', 1),
        ('Gastronomia', 'Eventos gastronômicos e culinários', '#dc3545', 1),
        ('Música', 'Eventos musicais e shows', '#6610f2', 1)
        ";
        
        try {
            $conn->exec($sampleCategories);
            echo "✅ Categorias de exemplo inseridas\n";
        } catch (Exception $e) {
            echo "⚠️  Aviso ao inserir categorias: " . $e->getMessage() . "\n";
        }
    }
}

// Executar se chamado diretamente
if (php_sapi_name() === 'cli' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    setupDatabase();
}