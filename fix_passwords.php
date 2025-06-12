<?php
// ========================================
// SCRIPT PARA CORRIGIR SENHAS
// ========================================
// Local: fix_passwords.php (na raiz do projeto)
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    require_once 'controllers/AuthController.php';

    echo "🔧 Iniciando correção de senhas...\n\n";

    $database = Database::getInstance();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception("Não foi possível conectar ao banco");
    }

    echo "✅ Conectado ao banco de dados\n";

    // Buscar usuários com senhas que parecem estar em texto plano
    $stmt = $conn->prepare("
        SELECT id_usuario, nome, email, senha, LENGTH(senha) as senha_length
        FROM usuarios 
        WHERE LENGTH(senha) < 50 OR senha NOT REGEXP '^\\$2[ayb]\\$'
        ORDER BY data_criacao DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();

    echo "📋 Encontrados " . count($users) . " usuários com senhas a corrigir\n\n";

    if (empty($users)) {
        echo "✅ Todas as senhas já estão corretamente hasheadas!\n";
        exit(json_encode(['success' => true, 'message' => 'Nenhuma correção necessária']));
    }

    $fixed = 0;
    $errors = 0;

    foreach ($users as $user) {
        echo "👤 Usuário: " . $user['email'] . "\n";
        echo "   Nome: " . $user['nome'] . "\n";
        echo "   Senha atual (length): " . $user['senha_length'] . "\n";
        echo "   Senha atual (início): " . substr($user['senha'], 0, 20) . "...\n";

        // Verificar se é realmente texto plano
        $is_plain_text = (strlen($user['senha']) < 50) || (!str_starts_with($user['senha'], '$2'));

        if ($is_plain_text) {
            // Criar novo hash
            $new_hash = password_hash($user['senha'], PASSWORD_DEFAULT);
            
            echo "   🔄 Criando novo hash...\n";
            echo "   Novo hash: " . substr($new_hash, 0, 30) . "...\n";

            // Atualizar no banco
            $update_stmt = $conn->prepare("
                UPDATE usuarios 
                SET senha = ? 
                WHERE id_usuario = ?
            ");
            
            if ($update_stmt->execute([$new_hash, $user['id_usuario']])) {
                echo "   ✅ Senha atualizada com sucesso!\n";
                
                // Verificar se a nova senha funciona
                $verify_result = password_verify($user['senha'], $new_hash);
                echo "   🔍 Verificação: " . ($verify_result ? 'OK' : 'FALHOU') . "\n";
                
                $fixed++;
            } else {
                echo "   ❌ Erro ao atualizar senha\n";
                $errors++;
            }
        } else {
            echo "   ℹ️  Senha já parece estar hasheada corretamente\n";
        }
        
        echo "\n";
    }

    echo "📊 RESUMO:\n";
    echo "   Corrigidas: $fixed senhas\n";
    echo "   Erros: $errors\n";
    echo "   Total processados: " . count($users) . "\n\n";

    if ($fixed > 0) {
        echo "🎉 Processo concluído! Agora teste fazer login novamente.\n";
    }

    // Testar as senhas corrigidas
    echo "\n🧪 TESTANDO SENHAS CORRIGIDAS:\n\n";
    
    $authController = new AuthController();
    
    // Buscar usuários recém-corrigidos
    $test_stmt = $conn->prepare("
        SELECT id_usuario, nome, email, senha 
        FROM usuarios 
        ORDER BY data_criacao DESC 
        LIMIT 3
    ");
    $test_stmt->execute();
    $test_users = $test_stmt->fetchAll();

    foreach ($test_users as $user) {
        echo "🔑 Testando usuário: " . $user['email'] . "\n";
        
        // Tentar senhas comuns que podem ter sido usadas
        $test_passwords = ['123456', 'admin123', 'user123', 'teste123', '123'];
        
        foreach ($test_passwords as $test_pass) {
            $verify = password_verify($test_pass, $user['senha']);
            if ($verify) {
                echo "   ✅ Senha '$test_pass' funciona!\n";
                echo "   📝 Use esta senha para fazer login\n";
                break;
            }
        }
        echo "\n";
    }

    echo "✨ Processo finalizado!\n";
    echo "📌 INSTRUÇÕES:\n";
    echo "   1. Tente fazer login com as senhas que você lembra\n";
    echo "   2. Se não lembrar, use as senhas testadas acima\n";
    echo "   3. Para novos usuários, use senhas normais - elas serão hasheadas corretamente\n";

    $result = [
        'success' => true,
        'fixed' => $fixed,
        'errors' => $errors,
        'total' => count($users),
        'message' => "Processo concluído. $fixed senhas corrigidas."
    ];

    echo "\n" . json_encode($result, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo json_encode($error, JSON_PRETTY_PRINT);
}
?>