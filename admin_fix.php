<?php
// ========================================
// CORREÇÃO ESPECÍFICA DO ADMIN - RAILWAY
// ========================================
// Execute: admin_fix.php
// ========================================

echo "<h1>🔧 Correção do Admin - Railway</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<p>✅ Conexão estabelecida</p>";
    
    // Verificar se admin existe
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@conectaeventos.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p>✅ Admin encontrado: " . $admin['nome'] . "</p>";
        
        // Resetar senha do admin para garantir que funciona
        $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
        $result = $stmt->execute([$newPassword, 'admin@conectaeventos.com']);
        
        if ($result) {
            echo "<p>✅ Senha do admin resetada com sucesso!</p>";
            
            // Testar login
            require_once 'models/User.php';
            $userModel = new User();
            $loginTest = $userModel->authenticate('admin@conectaeventos.com', 'admin123');
            
            if ($loginTest['success']) {
                echo "<p>✅ Login do admin testado e funcionando!</p>";
                echo "<div class='success'>";
                echo "<h3>🎉 Admin Corrigido com Sucesso!</h3>";
                echo "<p><strong>Email:</strong> admin@conectaeventos.com</p>";
                echo "<p><strong>Senha:</strong> admin123</p>";
                echo "<p><strong>Tipo:</strong> " . $loginTest['user']['tipo'] . "</p>";
                echo "<a href='views/auth/login.php' class='btn'>Fazer Login Agora</a>";
                echo "</div>";
            } else {
                echo "<p class='error'>❌ Login ainda não funciona: " . $loginTest['message'] . "</p>";
            }
        } else {
            echo "<p class='error'>❌ Erro ao resetar senha</p>";
        }
        
    } else {
        echo "<p>⚠️ Admin não existe, criando novo...</p>";
        
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo, ativo) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            'Administrador', 
            'admin@conectaeventos.com', 
            $adminPassword, 
            'organizador',
            1
        ]);
        
        if ($result) {
            echo "<p>✅ Admin criado com sucesso!</p>";
            echo "<div class='success'>";
            echo "<h3>🎉 Admin Criado!</h3>";
            echo "<p><strong>Email:</strong> admin@conectaeventos.com</p>";
            echo "<p><strong>Senha:</strong> admin123</p>";
            echo "<p><strong>Tipo:</strong> organizador</p>";
            echo "<a href='views/auth/login.php' class='btn'>Fazer Login Agora</a>";
            echo "</div>";
        } else {
            echo "<p class='error'>❌ Erro ao criar admin</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
    
    // Se der erro de constraint, significa que o admin já existe
    if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
        echo "<div class='info'>";
        echo "<h3>ℹ️ Admin Já Existe</h3>";
        echo "<p>O usuário admin já está no banco de dados.</p>";
        echo "<p><strong>Email:</strong> admin@conectaeventos.com</p>";
        echo "<p><strong>Senha:</strong> admin123</p>";
        echo "<p>Tente fazer login com essas credenciais.</p>";
        echo "<a href='views/auth/login.php' class='btn'>Fazer Login</a>";
        echo "</div>";
    }
}

// CSS
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
    h1 { color: #007bff; }
    .error { color: #dc3545; font-weight: bold; }
    .success { 
        background: #d4edda; 
        border: 1px solid #c3e6cb; 
        padding: 20px; 
        border-radius: 5px; 
        margin: 20px 0; 
    }
    .info { 
        background: #d1ecf1; 
        border: 1px solid #bee5eb; 
        padding: 20px; 
        border-radius: 5px; 
        margin: 20px 0; 
    }
    .btn { 
        display: inline-block; 
        padding: 10px 20px; 
        background: #007bff; 
        color: white; 
        text-decoration: none; 
        border-radius: 5px; 
        margin: 10px 0; 
    }
    .btn:hover { background: #0056b3; color: white; text-decoration: none; }
</style>";
?>