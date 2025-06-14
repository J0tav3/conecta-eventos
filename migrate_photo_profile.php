<?php
// ==========================================
// MIGRATION: ADICIONAR FOTO DE PERFIL
// Local: database/migrations/migrate_photo_profile.php
// ==========================================

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar se é admin (opcional - remova se não quiser essa verificação)
/*
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'organizador') {
    die('Acesso negado. Apenas administradores podem executar migrações.');
}
*/

require_once __DIR__ . '/../config/database.php';

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
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔧 Migration: Sistema de Foto de Perfil</h1>";
echo "<p><strong>Data:</strong> " . date('d/m/Y H:i:s') . "</p>";

$migrationResults = [];
$hasErrors = false;

try {
    // Obter conexão com banco
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Falha na conexão com o banco de dados");
    }
    
    echo "<div class='success'>✅ Conexão com banco estabelecida</div>";
    
    // ============================================
    // STEP 1: Verificar estrutura atual da tabela
    // ============================================
    echo "<div class='step'>";
    echo "<h3>📋 Step 1: Verificar estrutura atual da tabela 'usuarios'</h3>";
    
    $stmt = $conn->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Colunas atuais:</h4>";
    echo "<pre>";
    foreach ($columns as $column) {
        echo sprintf("%-20s %-15s %-10s %-10s %-15s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'], 
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
    // STEP 2: Adicionar coluna foto_perfil
    // ============================================
    echo "<div class='step'>";
    echo "<h3>🖼️ Step 2: Adicionar coluna 'foto_perfil'</h3>";
    
    if ($hasFotoPerfilColumn) {
        echo "<div class='warning'>⚠️ Coluna 'foto_perfil' já existe. Pulando...</div>";
        $migrationResults[] = ['step' => 'foto_perfil', 'status' => 'skipped', 'message' => 'Coluna já existe'];
    } else {
        try {
            $sql = "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER senha";
            $conn->exec($sql);
            echo "<div class='success'>✅ Coluna 'foto_perfil' adicionada com sucesso</div>";
            echo "<div class='info'>📝 SQL executado: <code>$sql</code></div>";
            $migrationResults[] = ['step' => 'foto_perfil', 'status' => 'success', 'message' => 'Coluna adicionada'];
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao adicionar coluna 'foto_perfil': " . $e->getMessage() . "</div>";
            $migrationResults[] = ['step' => 'foto_perfil', 'status' => 'error', 'message' => $e->getMessage()];
            $hasErrors = true;
        }
    }
    echo "</div>";
    
    // ============================================
    // STEP 3: Adicionar coluna data_atualizacao
    // ============================================
    echo "<div class='step'>";
    echo "<h3>📅 Step 3: Adicionar coluna 'data_atualizacao'</h3>";
    
    if ($hasDataAtualizacaoColumn) {
        echo "<div class='warning'>⚠️ Coluna 'data_atualizacao' já existe. Pulando...</div>";
        $migrationResults[] = ['step' => 'data_atualizacao', 'status' => 'skipped', 'message' => 'Coluna já existe'];
    } else {
        try {
            $sql = "ALTER TABLE usuarios ADD COLUMN data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao";
            $conn->exec($sql);
            echo "<div class='success'>✅ Coluna 'data_atualizacao' adicionada com sucesso</div>";
            echo "<div class='info'>📝 SQL executado: <code>$sql</code></div>";
            $migrationResults[] = ['step' => 'data_atualizacao', 'status' => 'success', 'message' => 'Coluna adicionada'];
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao adicionar coluna 'data_atualizacao': " . $e->getMessage() . "</div>";
            $migrationResults[] = ['step' => 'data_atualizacao', 'status' => 'error', 'message' => $e->getMessage()];
            $hasErrors = true;
        }
    }
    echo "</div>";
    
    // ============================================
    // STEP 4: Criar estrutura de diretórios
    // ============================================
    echo "<div class='step'>";
    echo "<h3>📁 Step 4: Criar estrutura de diretórios para uploads</h3>";
    
    $uploadsDir = __DIR__ . '/../uploads';
    $profilesDir = __DIR__ . '/../uploads/profiles';
    
    // Criar diretório uploads
    if (!file_exists($uploadsDir)) {
        if (mkdir($uploadsDir, 0755, true)) {
            echo "<div class='success'>✅ Diretório 'uploads/' criado</div>";
            $migrationResults[] = ['step' => 'uploads_dir', 'status' => 'success', 'message' => 'Diretório criado'];
        } else {
            echo "<div class='error'>❌ Falha ao criar diretório 'uploads/'</div>";
            $migrationResults[] = ['step' => 'uploads_dir', 'status' => 'error', 'message' => 'Falha ao criar'];
            $hasErrors = true;
        }
    } else {
        echo "<div class='info'>ℹ️ Diretório 'uploads/' já existe</div>";
        $migrationResults[] = ['step' => 'uploads_dir', 'status' => 'skipped', 'message' => 'Já existe'];
    }
    
    // Criar diretório profiles
    if (!file_exists($profilesDir)) {
        if (mkdir($profilesDir, 0755, true)) {
            echo "<div class='success'>✅ Diretório 'uploads/profiles/' criado</div>";
            $migrationResults[] = ['step' => 'profiles_dir', 'status' => 'success', 'message' => 'Diretório criado'];
        } else {
            echo "<div class='error'>❌ Falha ao criar diretório 'uploads/profiles/'</div>";
            $migrationResults[] = ['step' => 'profiles_dir', 'status' => 'error', 'message' => 'Falha ao criar'];
            $hasErrors = true;
        }
    } else {
        echo "<div class='info'>ℹ️ Diretório 'uploads/profiles/' já existe</div>";
        $migrationResults[] = ['step' => 'profiles_dir', 'status' => 'skipped', 'message' => 'Já existe'];
    }
    echo "</div>";
    
    // ============================================
    // STEP 5: Criar arquivos de segurança
    // ============================================
    echo "<div class='step'>";
    echo "<h3>🔒 Step 5: Criar arquivos de segurança</h3>";
    
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
</Files>

# Headers de segurança
<IfModule mod_headers.c>
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options DENY
</IfModule>";
    
    $htaccessPath = $profilesDir . '/.htaccess';
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        echo "<div class='success'>✅ Arquivo '.htaccess' criado</div>";
        $migrationResults[] = ['step' => 'htaccess', 'status' => 'success', 'message' => 'Arquivo criado'];
    } else {
        echo "<div class='error'>❌ Falha ao criar '.htaccess'</div>";
        $migrationResults[] = ['step' => 'htaccess', 'status' => 'error', 'message' => 'Falha ao criar'];
        $hasErrors = true;
    }
    
    // Criar index.php de proteção
    $indexContent = "<?php
// Proteção do diretório de uploads
header('HTTP/1.0 403 Forbidden');
exit('Acesso negado.');
?>";
    
    $indexPath = $profilesDir . '/index.php';
    if (file_put_contents($indexPath, $indexContent)) {
        echo "<div class='success'>✅ Arquivo 'index.php' de proteção criado</div>";
        $migrationResults[] = ['step' => 'index_protection', 'status' => 'success', 'message' => 'Arquivo criado'];
    } else {
        echo "<div class='error'>❌ Falha ao criar 'index.php' de proteção</div>";
        $migrationResults[] = ['step' => 'index_protection', 'status' => 'error', 'message' => 'Falha ao criar'];
        $hasErrors = true;
    }
    
    // Criar .gitkeep
    $gitkeepPath = $profilesDir . '/.gitkeep';
    file_put_contents($gitkeepPath, '# Keep this directory in Git');
    echo "<div class='info'>ℹ️ Arquivo '.gitkeep' criado</div>";
    
    echo "</div>";
    
    // ============================================
    // STEP 6: Testar permissões
    // ============================================
    echo "<div class='step'>";
    echo "<h3>🧪 Step 6: Testar permissões de escrita</h3>";
    
    $testFile = $profilesDir . '/test_write.tmp';
    if (file_put_contents($testFile, 'teste de escrita') !== false) {
        echo "<div class='success'>✅ Permissões de escrita OK</div>";
        unlink($testFile);
        $migrationResults[] = ['step' => 'write_permissions', 'status' => 'success', 'message' => 'Permissões OK'];
    } else {
        echo "<div class='error'>❌ SEM permissão de escrita no diretório</div>";
        echo "<div class='warning'>⚠️ Você precisa ajustar as permissões do diretório manualmente</div>";
        $migrationResults[] = ['step' => 'write_permissions', 'status' => 'error', 'message' => 'Sem permissão'];
        $hasErrors = true;
    }
    echo "</div>";
    
    // ============================================
    // STEP 7: Verificar estrutura final
    // ============================================
    echo "<div class='step'>";
    echo "<h3>✅ Step 7: Verificar estrutura final</h3>";
    
    // Verificar tabela atualizada
    $stmt = $conn->query("DESCRIBE usuarios");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $finalColumnNames = array_column($finalColumns, 'Field');
    
    echo "<h4>Estrutura final da tabela:</h4>";
    echo "<pre>";
    foreach ($finalColumns as $column) {
        $isNew = in_array($column['Field'], ['foto_perfil', 'data_atualizacao']) ? ' ⭐ NOVA' : '';
        echo sprintf("%-20s %-15s %-10s %-10s %-15s %s%s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'], 
            $column['Extra'],
            $isNew
        );
    }
    echo "</pre>";
    
    // Verificar diretórios
    echo "<h4>Estrutura de diretórios:</h4>";
    echo "<pre>";
    echo "uploads/\n";
    echo "└── profiles/\n";
    echo "    ├── .htaccess " . (file_exists($htaccessPath) ? '✅' : '❌') . "\n";
    echo "    ├── index.php " . (file_exists($indexPath) ? '✅' : '❌') . "\n";
    echo "    └── .gitkeep " . (file_exists($gitkeepPath) ? '✅' : '❌') . "\n";
    echo "</pre>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro crítico na migration: " . $e->getMessage() . "</div>";
    $hasErrors = true;
}

// ============================================
// RESUMO FINAL
// ============================================
echo "<div class='step'>";
echo "<h2>📊 Resumo da Migration</h2>";

$successCount = count(array_filter($migrationResults, fn($r) => $r['status'] === 'success'));
$errorCount = count(array_filter($migrationResults, fn($r) => $r['status'] === 'error'));
$skippedCount = count(array_filter($migrationResults, fn($r) => $r['status'] === 'skipped'));

echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Step</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Status</th>";
echo "<th style='padding: 10px; border: 1px solid #ddd; text-align: left;'>Mensagem</th>";
echo "</tr>";

foreach ($migrationResults as $result) {
    $statusIcon = match($result['status']) {
        'success' => '✅',
        'error' => '❌',
        'skipped' => '⏭️',
        default => '❓'
    };
    
    $statusColor = match($result['status']) {
        'success' => '#28a745',
        'error' => '#dc3545',
        'skipped' => '#ffc107',
        default => '#6c757d'
    };
    
    echo "<tr>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $result['step'] . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd; color: $statusColor;'>$statusIcon " . $result['status'] . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $result['message'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div style='display: flex; gap: 20px; margin: 20px 0;'>";
echo "<div class='success'>✅ Sucessos: $successCount</div>";
echo "<div class='error'>❌ Erros: $errorCount</div>";
echo "<div class='warning'>⏭️ Pulados: $skippedCount</div>";
echo "</div>";

if (!$hasErrors) {
    echo "<div class='success'>";
    echo "<h3>🎉 Migration concluída com sucesso!</h3>";
    echo "<p>O sistema de foto de perfil está pronto para uso.</p>";
    echo "</div>";
    
    echo "<h4>📋 Próximos passos:</h4>";
    echo "<ol>";
    echo "<li>Teste o upload de uma foto de perfil</li>";
    echo "<li>Verifique se as fotos são exibidas corretamente</li>";
    echo "<li>Confirme que os arquivos de segurança estão funcionando</li>";
    echo "</ol>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Migration concluída com erros</h3>";
    echo "<p>Alguns passos falharam. Verifique os erros acima e execute novamente se necessário.</p>";
    echo "</div>";
}

echo "</div>";

echo "<div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>";
echo "<h4>🔗 Links úteis:</h4>";
echo "<a href='../views/dashboard/participant-settings.php' class='btn'>Testar Configurações</a>";
echo "<a href='../index.php' class='btn btn-success'>Voltar ao Site</a>";
echo "<a href='../views/dashboard/participant.php' class='btn'>Dashboard</a>";

if ($hasErrors) {
    echo "<a href='?retry=1' class='btn btn-danger'>Executar Novamente</a>";
}

echo "</div>";

echo "</div>"; // container
echo "</body></html>";
?>