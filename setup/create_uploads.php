<?php
// ==========================================
// SCRIPT PARA CRIAR ESTRUTURA DE UPLOADS
// Local: setup/create_uploads.php
// ==========================================

// Este script deve ser executado uma vez para criar a estrutura de uploads

echo "=== CRIANDO ESTRUTURA DE UPLOADS ===\n";

// Diretórios a serem criados
$directories = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/eventos',
    __DIR__ . '/../uploads/temp'
];

$created = 0;
$errors = 0;

foreach ($directories as $dir) {
    echo "Criando diretório: $dir\n";
    
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Criado com sucesso\n";
            $created++;
        } else {
            echo "❌ Erro ao criar\n";
            $errors++;
        }
    } else {
        echo "ℹ️  Já existe\n";
    }
}

// Criar arquivo .htaccess para segurança no diretório uploads/eventos
$htaccessContent = "# Impedir execução de scripts
php_flag engine off
AddType text/plain .php .php3 .phtml .pht

# Apenas imagens
<Files ~ \"\\.(php|php3|phtml|pht|jsp|asp|aspx|cgi|pl)$\">
    Order allow,deny
    Deny from all
</Files>

# Permitir acesso a imagens
<Files ~ \"\\.(jpg|jpeg|png|gif|webp)$\">
    Order allow,deny
    Allow from all
</Files>

# Headers de segurança
<IfModule mod_headers.c>
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options DENY
    Header set X-XSS-Protection \"1; mode=block\"
</IfModule>
";

$htaccessPath = __DIR__ . '/../uploads/eventos/.htaccess';
echo "Criando .htaccess de segurança: $htaccessPath\n";

if (file_put_contents($htaccessPath, $htaccessContent)) {
    echo "✅ .htaccess criado com sucesso\n";
} else {
    echo "❌ Erro ao criar .htaccess\n";
    $errors++;
}

// Criar arquivo index.php para impedir listagem de diretórios
$indexContent = "<?php
// Impedir listagem de diretórios
header('HTTP/1.0 403 Forbidden');
exit('Acesso negado');
?>";

$indexPaths = [
    __DIR__ . '/../uploads/index.php',
    __DIR__ . '/../uploads/eventos/index.php',
    __DIR__ . '/../uploads/temp/index.php'
];

foreach ($indexPaths as $indexPath) {
    echo "Criando index.php de proteção: $indexPath\n";
    
    if (file_put_contents($indexPath, $indexContent)) {
        echo "✅ index.php criado\n";
    } else {
        echo "❌ Erro ao criar index.php\n";
        $errors++;
    }
}

// Testar permissões de escrita
echo "\n=== TESTANDO PERMISSÕES ===\n";

$testFile = __DIR__ . '/../uploads/eventos/test_write.tmp';
echo "Testando escrita em: $testFile\n";

if (file_put_contents($testFile, 'teste') !== false) {
    echo "✅ Permissão de escrita OK\n";
    unlink($testFile); // Remover arquivo de teste
} else {
    echo "❌ SEM permissão de escrita\n";
    $errors++;
}

// Criar arquivo README com instruções
$readmeContent = "# Diretório de Uploads - Conecta Eventos

## Estrutura:
- `/uploads/eventos/` - Imagens de capa dos eventos
- `/uploads/temp/` - Arquivos temporários

## Segurança:
- Execução de PHP desabilitada
- Apenas imagens são permitidas
- Headers de segurança configurados

## Configuração no Railway:

1. Certifique-se de que este diretório tem permissão de escrita
2. O diretório é criado automaticamente pelo ImageUploadHandler
3. Arquivos são validados antes do upload

## Limpeza:
- Execute o script de limpeza periodicamente
- Arquivos temporários são removidos automaticamente

Data de criação: " . date('Y-m-d H:i:s') . "
";

$readmePath = __DIR__ . '/../uploads/README.md';
file_put_contents($readmePath, $readmeContent);

echo "\n=== RESUMO ===\n";
echo "Diretórios criados: $created\n";
echo "Erros: $errors\n";

if ($errors === 0) {
    echo "✅ ESTRUTURA CRIADA COM SUCESSO!\n";
} else {
    echo "⚠️  ESTRUTURA CRIADA COM ALGUNS ERROS\n";
    echo "Verifique as permissões dos diretórios no servidor\n";
}

echo "\n=== PRÓXIMOS PASSOS ===\n";
echo "1. Inclua o ImageUploadHandler nos formulários\n";
echo "2. Atualize o EventController para salvar nomes de imagens\n";
echo "3. Configure o Railway para persistir o diretório uploads\n";
echo "4. Teste o upload de uma imagem\n";

echo "\n=== CONFIGURAÇÃO RAILWAY ===\n";
echo "Se estiver usando Railway, adicione as variáveis:\n";
echo "- UPLOAD_MAX_SIZE=5242880 (5MB)\n";
echo "- UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,gif,webp\n";

echo "\nScript concluído!\n";
?>