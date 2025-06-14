<?php
// ==========================================
// SCRIPT PARA CRIAR DIRETÓRIO DE PERFIS
// Execute este arquivo uma vez para criar a estrutura
// ==========================================

echo "<h2>🔧 Criando estrutura para fotos de perfil</h2>";

try {
    // Diretório base de uploads
    $uploadsDir = __DIR__ . '/uploads';
    $profilesDir = __DIR__ . '/uploads/profiles';
    
    // Criar diretório uploads se não existir
    if (!file_exists($uploadsDir)) {
        if (mkdir($uploadsDir, 0755, true)) {
            echo "✅ Diretório uploads/ criado<br>";
        } else {
            throw new Exception("Falha ao criar diretório uploads/");
        }
    } else {
        echo "ℹ️ Diretório uploads/ já existe<br>";
    }
    
    // Criar diretório profiles se não existir
    if (!file_exists($profilesDir)) {
        if (mkdir($profilesDir, 0755, true)) {
            echo "✅ Diretório uploads/profiles/ criado<br>";
        } else {
            throw new Exception("Falha ao criar diretório uploads/profiles/");
        }
    } else {
        echo "ℹ️ Diretório uploads/profiles/ já existe<br>";
    }
    
    // Criar arquivo .htaccess para segurança
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
        echo "✅ Arquivo .htaccess criado<br>";
    } else {
        echo "⚠️ Falha ao criar .htaccess<br>";
    }
    
    // Criar arquivo index.php para proteção
    $indexContent = "<?php
// Proteção do diretório
header('HTTP/1.0 403 Forbidden');
exit('Acesso negado.');
?>";
    
    $indexPath = $profilesDir . '/index.php';
    if (file_put_contents($indexPath, $indexContent)) {
        echo "✅ Arquivo index.php de proteção criado<br>";
    } else {
        echo "⚠️ Falha ao criar index.php<br>";
    }
    
    // Criar arquivo .gitkeep
    $gitkeepPath = $profilesDir . '/.gitkeep';
    if (file_put_contents($gitkeepPath, '# Keep this directory')) {
        echo "✅ Arquivo .gitkeep criado<br>";
    }
    
    // Testar permissões de escrita
    $testFile = $profilesDir . '/test_write.tmp';
    if (file_put_contents($testFile, 'teste') !== false) {
        echo "✅ Permissões de escrita OK<br>";
        unlink($testFile);
    } else {
        echo "❌ SEM permissão de escrita no diretório<br>";
    }
    
    echo "<br><h3>🎉 Estrutura criada com sucesso!</h3>";
    echo "<p>Agora você pode fazer upload de fotos de perfil.</p>";
    
    echo "<h4>📋 Próximos passos:</h4>";
    echo "<ol>";
    echo "<li>Certifique-se de que o diretório tem permissão de escrita</li>";
    echo "<li>Teste o upload de uma foto de perfil</li>";
    echo "<li>Verifique se as fotos são exibidas corretamente</li>";
    echo "</ol>";
    
    echo "<h4>🔍 Estrutura criada:</h4>";
    echo "<pre>";
    echo "uploads/\n";
    echo "└── profiles/\n";
    echo "    ├── .htaccess (segurança)\n";
    echo "    ├── index.php (proteção)\n";
    echo "    └── .gitkeep (manter no Git)\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>