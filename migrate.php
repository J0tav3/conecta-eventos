<?php
// ==========================================
// MIGRAÇÃO TEMPORÁRIA - DELETAR APÓS USO
// ==========================================

require_once 'config/database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        die("Erro: Não foi possível conectar ao banco");
    }
    
    echo "<h2>🚀 Migração do Sistema de Fotos de Perfil</h2>";
    echo "Conectado ao banco com sucesso!<br><br>";
    
    // 1. Adicionar coluna foto_perfil
    echo "1. Adicionando coluna foto_perfil...<br>";
    try {
        $sql1 = "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER email";
        $conn->exec($sql1);
        echo "✅ Coluna foto_perfil adicionada<br>";
    } catch (Exception $e) {
        echo "⚠️ Coluna foto_perfil já existe ou erro: " . $e->getMessage() . "<br>";
    }
    
    // 2. Adicionar coluna data_atualizacao
    echo "2. Adicionando coluna data_atualizacao...<br>";
    try {
        $sql2 = "ALTER TABLE usuarios ADD COLUMN data_atualizacao TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER data_criacao";
        $conn->exec($sql2);
        echo "✅ Coluna data_atualizacao adicionada<br>";
    } catch (Exception $e) {
        echo "⚠️ Coluna data_atualizacao já existe ou erro: " . $e->getMessage() . "<br>";
    }
    
    // 3. Criar índice
    echo "3. Criando índice...<br>";
    try {
        $sql3 = "CREATE INDEX idx_usuarios_foto_perfil ON usuarios(foto_perfil)";
        $conn->exec($sql3);
        echo "✅ Índice criado<br>";
    } catch (Exception $e) {
        echo "⚠️ Índice já existe ou erro: " . $e->getMessage() . "<br>";
    }
    
    // 4. Verificar estrutura
    echo "<br>4. Estrutura atual da tabela usuarios:<br>";
    $stmt = $conn->query("DESCRIBE usuarios");
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-family: monospace;'>";
    echo "<tr style='background: #f5f5f5;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $highlight = '';
        if ($row['Field'] === 'foto_perfil' || $row['Field'] === 'data_atualizacao') {
            $highlight = 'style="background: #e8f5e8; font-weight: bold;"';
        }
        
        echo "<tr $highlight>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
    echo "<strong>🎉 Migração concluída com sucesso!</strong><br>";
    echo "As colunas <strong>foto_perfil</strong> e <strong>data_atualizacao</strong> foram adicionadas.";
    echo "</div>";
    
    echo "<br><div style='background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<strong>⚠️ IMPORTANTE:</strong> Delete este arquivo (migrate.php) após a migração!<br>";
    echo "Agora você pode continuar com a implementação do upload de fotos.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "❌ Erro: " . $e->getMessage();
    echo "</div>";
}
?>