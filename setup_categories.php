<?php
// ==========================================
// SETUP DE CATEGORIAS BÁSICAS
// Local: setup_categories.php
// ==========================================

require_once __DIR__ . '/config/database.php';

function setupBasicCategories() {
    try {
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        if (!$conn) {
            throw new Exception("Falha ao conectar com o banco de dados");
        }
        
        echo "Conectado ao banco de dados.\n";
        
        // Categorias básicas que devem existir
        $categorias_basicas = [
            'Tecnologia',
            'Negócios',
            'Marketing',
            'Design',
            'Educação',
            'Saúde',
            'Esportes',
            'Arte e Cultura',
            'Gastronomia',
            'Turismo'
        ];
        
        $conn->beginTransaction();
        
        foreach ($categorias_basicas as $nome_categoria) {
            // Verificar se a categoria já existe (case-insensitive)
            $stmt = $conn->prepare("SELECT id_categoria FROM categorias WHERE LOWER(nome) = LOWER(?)");
            $stmt->execute([$nome_categoria]);
            
            if ($stmt->rowCount() == 0) {
                // Categoria não existe, criar
                $insert_stmt = $conn->prepare("INSERT INTO categorias (nome, ativo) VALUES (?, 1)");
                $insert_stmt->execute([$nome_categoria]);
                echo "✅ Categoria criada: " . $nome_categoria . "\n";
            } else {
                echo "ℹ️  Categoria já existe: " . $nome_categoria . "\n";
            }
        }
        
        $conn->commit();
        echo "\n✅ Setup de categorias concluído!\n";
        
        // Listar todas as categorias
        $stmt = $conn->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome ASC");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nCategorias ativas no sistema:\n";
        foreach ($categorias as $cat) {
            echo "  - ID: " . $cat['id_categoria'] . " | Nome: " . $cat['nome'] . "\n";
        }
        
        return true;
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        echo "Erro: " . $e->getMessage() . "\n";
        return false;
    }
}

// Executar apenas se chamado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "=== SETUP DE CATEGORIAS BÁSICAS ===\n";
    echo "Este script irá criar as categorias básicas se elas não existirem.\n\n";
    
    if (setupBasicCategories()) {
        echo "\n✅ Setup concluído com sucesso!\n";
        if (php_sapi_name() !== 'cli') {
            echo '<br><a href="index.php">Voltar ao início</a>';
        }
    } else {
        echo "\n❌ Erro durante o setup.\n";
    }
}
?>