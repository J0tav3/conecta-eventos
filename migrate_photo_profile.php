<?php
// ==========================================
// MIGRATION SIMPLES - FOTO DE PERFIL
// Local: migrate_simple.php (na raiz do projeto)
// ==========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migration - Foto de Perfil</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔧 Migration: Sistema de Foto de Perfil</h1>";
echo "<p><strong>Data:</strong> " . date('d/m/Y H:i:s') . "</p>";

try {
    // ============================================
    // CONECTAR DIRETO COM O BANCO RAILWAY
    // ============================================
    echo "<div class='step'>";
    echo "<h3>🔗 Conectando com o banco de dados...</h3>";
    
    // Obter DATABASE_URL do Railway
    $database_url = getenv('DATABASE_URL');
    
    if (!$database_url) {
        throw new Exception("DATABASE_URL não encontrada nas variáveis de ambiente");
    }
    
    echo "<div class='info'>✅ DATABASE_URL encontrada</div>";
    
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
    
    echo "<div class='info'>📍 Conectando em: $host:$port/$dbname</div>";
    
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
    $conn = new PDO($dsn, $username, $password, $options);
    
    echo "<div class='success'>✅ Conexão estabelecida com sucesso!</div>";
    echo "</div>";
    
    // ============================================
    // VERIFICAR ESTRUTURA ATUAL
    // ============================================
    echo "<div class='step'>";
    echo "<h3>📋 Verificando estrutura atual da tabela 'usuarios'</h3>";
    
    $stmt = $conn->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Colunas atuais:</h4>";
    echo "<pre>";
    printf("%-20s %-20s %-10s %-10s %-15s %s\n", 'CAMPO', 'TIPO', 'NULL', 'KEY', 'DEFAULT', 'EXTRA');
    echo str_repeat('-', 80) . "\n";
    foreach ($columns as $column) {
        printf("%-20s %-20s %-10s %-10s %-15s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'] ?? 'NULL', 
            $column['Extra']
        );
    }
    echo "</pre>";
    
    // Verificar se as colunas já existem
    $columnNames = array_column($columns, 'Field');
    $hasFotoPerfilColumn = in_array('foto_perfil', $columnNames);
    $hasDataAtualizacaoColumn = in_array('data_atualizacao', $columnNames);
    
    echo "</div>";
    
    // ============================================
    // EXECUTAR ALTERAÇÕES NO BANCO
    // ============================================
    echo "<div class='step'>";
    echo "<h3>🛠️ Executando alterações no banco de dados</h3>";
    
    $migrations = [];
    
    // Adicionar coluna foto_perfil
    if (!$hasFotoPerfilColumn) {
        try {
            $sql = "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER senha";
            $conn->exec($sql);
            echo "<div class='success'>✅ Coluna 'foto_perfil' adicionada</div>";
            echo "<div class='info'>SQL: <code>$sql</code></div>";
            $migrations[] = ['coluna' => 'foto_perfil', 'status' => 'adicionada'];
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao adicionar coluna 'foto_perfil': " . $e->getMessage() . "</div>";
            $migrations[] = ['coluna' => 'foto_perfil', 'status' => 'erro', 'mensagem' => $e->getMessage()];
        }
    } else {
        echo "<div class='warning'>⚠️ Coluna 'foto_perfil' já existe</div>";
        $migrations[] = ['coluna' => 'foto_perfil', 'status' => 'já existe'];
    }
    
    // Adicionar coluna data_atualizacao
    if (!$hasDataAtualizacaoColumn) {
        try {
            $sql = "ALTER TABLE usuarios ADD COLUMN data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao";
            $conn->exec($sql);
            echo "<div class='success'>✅ Coluna 'data_atualizacao' adicionada</div>";
            echo "<div class='info'>SQL: <code>$sql</code></div>";
            $migrations[] = ['coluna' => 'data_atualizacao', 'status' => 'adicionada'];
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao adicionar coluna 'data_atualizacao': " . $e->getMessage() . "</div>";
            $migrations[] = ['coluna' => 'data_atualizacao', 'status' => 'erro', 'mensagem' => $e->getMessage()];
        }
    } else {
        echo "<div class='warning'>⚠️ Coluna 'data_atualizacao' já existe</div>";
        $migrations[] = ['coluna' => 'data_atualizacao', 'status' => 'já existe'];
    }
    
    echo "</div>";
    
    // ============================================
    // CRIAR ESTRUTURA DE DIRETÓRIOS
    // ============================================
    echo "<div class='step'>";
    echo "<h3>📁 Criando estrutura de diretórios</h3>";
    
    $uploadsDir = __DIR__ . '/uploads';
    $profilesDir = __DIR__ . '/uploads/profiles';
    
    // Criar diretório uploads
    if (!file_exists($uploadsDir)) {
        if (mkdir($uploadsDir, 0755, true)) {
            echo "<div class='success'>✅ Diretório 'uploads/' criado</div>";
        } else {
            echo "<div class='error'>❌ Falha ao criar diretório 'uploads/'</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Diretório 'uploads/' já existe</div>";
    }
    
    // Criar diretório profiles
    if (!file_exists($profilesDir)) {
        if (mkdir($profilesDir, 0755, true)) {
            echo "<div class='success'>✅ Diretório 'uploads/profiles/' criado</div>";
        } else {
            echo "<div class='error'>❌ Falha ao criar diretório 'uploads/profiles/'</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Diretório 'uploads/profiles/' já existe</div>";
    }
    
    // Criar .htaccess
    $htaccessContent = "# Impedir execução de scripts
php_flag engine off
AddType text/plain .php .php3 .phtml .pht

# Apenas imagens
<Files ~ \"\\.(php|php3|phtml|pht|jsp|asp|aspx|cgi|pl)$\">
    Order allow,deny
    Deny from all
</Files>

# Permitir acesso às imagens
<Files ~ \"\\.(jpg|jpeg|png|gif|webp)$\">
    Order allow,deny
    Allow from all
</Files>";
    
    $htaccessPath = $profilesDir . '/.htaccess';
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "<div class='success'>✅ Arquivo '.htaccess' criado</div>";
    } else {
        echo "<div class='error'>❌ Falha ao criar '.htaccess'</div>";
    }
    
    // Criar index.php de proteção
    $indexContent = "<?php
// Proteção do diretório
header('HTTP/1.0 403 Forbidden');
exit('Acesso negado.');
?>";
    
    $indexPath = $profilesDir . '/index.php';
    if (file_put_contents($indexPath, $indexContent)) {
        echo "<div class='success'>✅ Arquivo 'index.php' de proteção criado</div>";
    } else {
        echo "<div class='error'>❌ Falha ao criar 'index.php' de proteção</div>";
    }
    
    echo "</div>";
    
    // ============================================
    // TESTAR PERMISSÕES
    // ============================================
    echo "<div class='step'>";
    echo "<h3>🧪 Testando permissões</h3>";
    
    $testFile = $profilesDir . '/test_write.tmp';
    if (file_put_contents($testFile, 'teste') !== false) {
        echo "<div class='success'>✅ Permissões de escrita OK</div>";
        unlink($testFile);
    } else {
        echo "<div class='error'>❌ SEM permissão de escrita</div>";
        echo "<div class='warning'>⚠️ Você pode precisar ajustar as permissões manualmente</div>";
    }
    
    echo "</div>";
    
    // ============================================
    // VERIFICAR ESTRUTURA FINAL
    // ============================================
    echo "<div class='step'>";
    echo "<h3>✅ Verificação final</h3>";
    
    // Verificar tabela atualizada
    $stmt = $conn->query("DESCRIBE usuarios");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Estrutura final da tabela 'usuarios':</h4>";
    echo "<pre>";
    printf("%-20s %-20s %-10s %-10s %-15s %s\n", 'CAMPO', 'TIPO', 'NULL', 'KEY', 'DEFAULT', 'EXTRA');
    echo str_repeat('-', 80) . "\n";
    foreach ($finalColumns as $column) {
        $isNew = in_array($column['Field'], ['foto_perfil', 'data_atualizacao']) ? ' ⭐' : '';
        printf("%-20s %-20s %-10s %-10s %-15s %s%s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'] ?? 'NULL', 
            $column['Extra'],
            $isNew
        );
    }
    echo "</pre>";
    
    echo "<h4>Estrutura de arquivos criada:</h4>";
    echo "<pre>";
    echo "uploads/\n";
    echo "└── profiles/\n";
    echo "    ├── .htaccess " . (file_exists($htaccessPath) ? '✅' : '❌') . "\n";
    echo "    ├── index.php " . (file_exists($indexPath) ? '✅' : '❌') . "\n";
    echo "    └── (arquivos de fotos serão salvos aqui)\n";
    echo "</pre>";
    
    echo "</div>";
    
    // ============================================
    // RESUMO FINAL
    // ============================================
    echo "<div class='step'>";
    echo "<h2>🎉 Migration Concluída!</h2>";
    
    echo "<h4>📊 Resumo das alterações:</h4>";
    echo "<ul>";
    foreach ($migrations as $migration) {
        $icon = match($migration['status']) {
            'adicionada' => '✅',
            'já existe' => '⚠️',
            'erro' => '❌',
            default => '❓'
        };
        echo "<li>$icon Coluna '{$migration['coluna']}': {$migration['status']}";
        if (isset($migration['mensagem'])) {
            echo " - " . $migration['mensagem'];
        }
        echo "</li>";
    }
    echo "</ul>";
    
    echo "<h4>🚀 Próximos passos:</h4>";
    echo "<ol>";
    echo "<li>Acesse as configurações do usuário</li>";
    echo "<li>Teste o upload de uma foto de perfil</li>";
    echo "<li>Verifique se a foto aparece em toda a interface</li>";
    echo "</ol>";
    
    echo "<h4>🔗 Links úteis:</h4>";
    echo "<p>";
    echo "<a href='views/dashboard/participant-settings.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Configurações</a>";
    echo "<a href='index.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Site Principal</a>";
    echo "<a href='views/dashboard/participant.php' style='padding: 10px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px;'>Dashboard</a>";
    echo "</p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Erro na Migration</h3>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>"; // container
echo "</body></html>";
?>