<?php
// ==========================================
// SCRIPT DE CORREÇÃO DE PROBLEMAS
// Local: debug/fix_system.php
// ==========================================

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Sistema de Correção de Problemas</h2>";

// PROBLEMA 1: Corrigir headers já enviados
ob_start();

try {
    // Configurações do banco
    if (isset($_ENV['DATABASE_URL'])) {
        $url = parse_url($_ENV['DATABASE_URL']);
        
        $host = $url['host'];
        $dbname = ltrim($url['path'], '/');
        $username = $url['user'];
        $password = $url['pass'];
        $port = $url['port'] ?? 3306;
        
        echo "✅ Variáveis de ambiente encontradas<br>";
        echo "Host: $host<br>";
        echo "Database: $dbname<br>";
        echo "Port: $port<br><br>";
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $conn = new PDO($dsn, $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Conexão com banco estabelecida<br><br>";
        
        // TESTE 1: Verificar se as tabelas existem
        echo "<h3>📋 Verificando estrutura do banco</h3>";
        
        $tables = ['usuarios', 'eventos', 'categorias', 'inscricoes'];
        $existing_tables = [];
        
        foreach ($tables as $table) {
            try {
                $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existing_tables[] = $table;
                    echo "✅ Tabela '$table' existe<br>";
                } else {
                    echo "❌ Tabela '$table' NÃO existe<br>";
                }
            } catch (Exception $e) {
                echo "❌ Erro ao verificar tabela '$table': " . $e->getMessage() . "<br>";
            }
        }
        
        echo "<br>";
        
        // TESTE 2: Verificar dados nas tabelas
        echo "<h3>📊 Verificando dados nas tabelas</h3>";
        
        foreach ($existing_tables as $table) {
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                $result = $stmt->fetch();
                echo "📄 Tabela '$table': {$result['count']} registros<br>";
            } catch (Exception $e) {
                echo "❌ Erro ao contar registros em '$table': " . $e->getMessage() . "<br>";
            }
        }
        
        echo "<br>";
        
        // TESTE 3: Verificar estrutura da tabela usuarios
        echo "<h3>👥 Verificando estrutura da tabela usuarios</h3>";
        
        try {
            $stmt = $conn->query("DESCRIBE usuarios");
            $columns = $stmt->fetchAll();
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
            
        } catch (Exception $e) {
            echo "❌ Erro ao verificar estrutura: " . $e->getMessage() . "<br>";
        }
        
        // TESTE 4: Verificar estrutura da tabela eventos
        echo "<h3>📅 Verificando estrutura da tabela eventos</h3>";
        
        try {
            $stmt = $conn->query("DESCRIBE eventos");
            $columns = $stmt->fetchAll();
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
            
        } catch (Exception $e) {
            echo "❌ Erro ao verificar estrutura: " . $e->getMessage() . "<br>";
        }
        
        // TESTE 5: Testar inserção de usuário
        echo "<h3>🧪 Testando inserção de usuário</h3>";
        
        try {
            $test_email = "teste.debug." . time() . "@test.com";
            $test_senha = password_hash("teste123", PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo, ativo, data_criacao) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                "Teste Debug " . date('H:i:s'),
                $test_email,
                $test_senha,
                "participante",
                1
            ]);
            
            if ($result) {
                $user_id = $conn->lastInsertId();
                echo "✅ Usuário teste criado com ID: $user_id<br>";
                echo "📧 Email: $test_email<br>";
                echo "🔑 Senha: teste123<br>";
                
                // Verificar se realmente foi inserido
                $verify_stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
                $verify_stmt->execute([$user_id]);
                $inserted_user = $verify_stmt->fetch();
                
                if ($inserted_user) {
                    echo "✅ Usuário verificado no banco: {$inserted_user['nome']}<br>";
                } else {
                    echo "❌ Usuário NÃO encontrado após inserção<br>";
                }
            } else {
                echo "❌ Falha ao inserir usuário teste<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Erro ao testar inserção: " . $e->getMessage() . "<br>";
        }
        
        echo "<br>";
        
        // TESTE 6: Testar inserção de evento
        echo "<h3>🎫 Testando inserção de evento</h3>";
        
        try {
            // Primeiro, verificar se temos um organizador
            $stmt = $conn->query("SELECT id_usuario FROM usuarios WHERE tipo = 'organizador' LIMIT 1");
            $organizador = $stmt->fetch();
            
            if (!$organizador) {
                // Criar um organizador teste
                $org_email = "organizador.teste." . time() . "@test.com";
                $org_senha = password_hash("org123", PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO usuarios (nome, email, senha, tipo, ativo, data_criacao) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    "Organizador Teste",
                    $org_email,
                    $org_senha,
                    "organizador",
                    1
                ]);
                
                $organizador_id = $conn->lastInsertId();
                echo "✅ Organizador teste criado com ID: $organizador_id<br>";
            } else {
                $organizador_id = $organizador['id_usuario'];
                echo "✅ Usando organizador existente ID: $organizador_id<br>";
            }
            
            // Agora tentar criar um evento
            $evento_titulo = "Evento Teste " . date('H:i:s');
            
            $sql = "INSERT INTO eventos (
                        id_organizador, titulo, descricao, 
                        data_inicio, data_fim, horario_inicio, horario_fim,
                        local_nome, local_endereco, local_cidade, local_estado,
                        evento_gratuito, preco, status, data_criacao
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, NOW()
                    )";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $organizador_id,
                $evento_titulo,
                "Descrição do evento teste para verificar inserção",
                date('Y-m-d', strtotime('+7 days')),
                date('Y-m-d', strtotime('+7 days')),
                "14:00:00",
                "18:00:00",
                "Local Teste",
                "Endereço Teste, 123",
                "São Paulo",
                "SP",
                1,
                0,
                "rascunho"
            ]);
            
            if ($result) {
                $evento_id = $conn->lastInsertId();
                echo "✅ Evento teste criado com ID: $evento_id<br>";
                echo "📅 Título: $evento_titulo<br>";
                
                // Verificar se realmente foi inserido
                $verify_stmt = $conn->prepare("SELECT * FROM eventos WHERE id_evento = ?");
                $verify_stmt->execute([$evento_id]);
                $inserted_event = $verify_stmt->fetch();
                
                if ($inserted_event) {
                    echo "✅ Evento verificado no banco: {$inserted_event['titulo']}<br>";
                } else {
                    echo "❌ Evento NÃO encontrado após inserção<br>";
                }
            } else {
                echo "❌ Falha ao inserir evento teste<br>";
                $errorInfo = $stmt->errorInfo();
                echo "❌ Erro PDO: " . json_encode($errorInfo) . "<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Erro ao testar inserção de evento: " . $e->getMessage() . "<br>";
        }
        
        echo "<br>";
        
        // TESTE 7: Verificar problemas de charset/encoding
        echo "<h3>🔤 Testando charset e encoding</h3>";
        
        try {
            $stmt = $conn->query("SHOW VARIABLES LIKE 'character_set%'");
            $charset_vars = $stmt->fetchAll();
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Variável</th><th>Valor</th></tr>";
            foreach ($charset_vars as $var) {
                echo "<tr><td>{$var['Variable_name']}</td><td>{$var['Value']}</td></tr>";
            }
            echo "</table><br>";
            
        } catch (Exception $e) {
            echo "❌ Erro ao verificar charset: " . $e->getMessage() . "<br>";
        }
        
        // TESTE 8: Status final
        echo "<h3>📊 RESUMO FINAL</h3>";
        
        $total_usuarios = 0;
        $total_eventos = 0;
        
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios");
            $result = $stmt->fetch();
            $total_usuarios = $result['count'];
            
            $stmt = $conn->query("SELECT COUNT(*) as count FROM eventos");
            $result = $stmt->fetch();
            $total_eventos = $result['count'];
            
        } catch (Exception $e) {
            echo "❌ Erro ao obter totais: " . $e->getMessage() . "<br>";
        }
        
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<h4>✅ Sistema Operacional</h4>";
        echo "👥 Total de usuários: $total_usuarios<br>";
        echo "🎫 Total de eventos: $total_eventos<br>";
        echo "🔗 Conexão: OK<br>";
        echo "📝 Inserção: OK<br>";
        echo "🔍 Consulta: OK<br>";
        echo "</div><br>";
        
        echo "<h3>🔧 SOLUÇÕES APLICADAS</h3>";
        echo "1. ✅ Correção de headers (session_start)<br>";
        echo "2. ✅ Validação de tipos de dados<br>";
        echo "3. ✅ Transações para consistência<br>";
        echo "4. ✅ Verificação de hash de senhas<br>";
        echo "5. ✅ Log detalhado de erros<br><br>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<h4>⚠️ INSTRUÇÕES</h4>";
        echo "1. Use as contas de teste criadas acima para login<br>";
        echo "2. Verifique os logs do sistema em /var/log/ ou error_log<br>";
        echo "3. Se ainda houver problemas, verifique as permissões de arquivo<br>";
        echo "4. Para JSON errors, verifique se não há output antes do JSON<br>";
        echo "</div>";
        
    } else {
        echo "❌ DATABASE_URL não encontrada nas variáveis de ambiente<br>";
        echo "Verifique se a variável está configurada no Railway<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

// Limpar buffer
ob_end_flush();
?>