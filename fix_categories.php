<?php
// ==========================================
// SCRIPT PARA CORRIGIR CATEGORIAS DUPLICADAS
// Local: fix_categories.php (executar uma vez)
// ==========================================

require_once __DIR__ . '/config/database.php';

function fixDuplicateCategories() {
    try {
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        if (!$conn) {
            throw new Exception("Falha ao conectar com o banco de dados");
        }
        
        echo "Conectado ao banco de dados.\n";
        
        // Buscar todas as categorias
        $stmt = $conn->query("SELECT * FROM categorias ORDER BY id_categoria ASC");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Encontradas " . count($categorias) . " categorias.\n";
        
        // Agrupar por nome (case-insensitive)
        $grupos = [];
        foreach ($categorias as $categoria) {
            $nome_lower = strtolower(trim($categoria['nome']));
            if (!isset($grupos[$nome_lower])) {
                $grupos[$nome_lower] = [];
            }
            $grupos[$nome_lower][] = $categoria;
        }
        
        echo "Encontrados " . count($grupos) . " grupos únicos de categorias.\n";
        
        $conn->beginTransaction();
        
        foreach ($grupos as $nome_grupo => $lista_categorias) {
            if (count($lista_categorias) > 1) {
                // Há duplicatas
                echo "Processando duplicatas para: " . $nome_grupo . " (" . count($lista_categorias) . " duplicatas)\n";
                
                // Manter a primeira categoria (menor ID)
                $categoria_principal = $lista_categorias[0];
                $ids_para_remover = [];
                
                for ($i = 1; $i < count($lista_categorias); $i++) {
                    $ids_para_remover[] = $lista_categorias[$i]['id_categoria'];
                }
                
                if (!empty($ids_para_remover)) {
                    // Atualizar eventos que usam as categorias duplicadas
                    $ids_str = implode(',', $ids_para_remover);
                    $update_stmt = $conn->prepare("UPDATE eventos SET id_categoria = ? WHERE id_categoria IN ($ids_str)");
                    $update_stmt->execute([$categoria_principal['id_categoria']]);
                    
                    echo "  - Atualizados eventos para usar categoria ID: " . $categoria_principal['id_categoria'] . "\n";
                    
                    // Remover categorias duplicadas
                    $delete_stmt = $conn->prepare("DELETE FROM categorias WHERE id_categoria IN ($ids_str)");
                    $delete_stmt->execute();
                    
                    echo "  - Removidas " . count($ids_para_remover) . " categorias duplicadas\n";
                }
            }
        }
        
        $conn->commit();
        echo "Limpeza concluída com sucesso!\n";
        
        // Verificar resultado
        $stmt = $conn->query("SELECT COUNT(*) as total FROM categorias");
        $total_final = $stmt->fetch()['total'];
        echo "Total de categorias após limpeza: " . $total_final . "\n";
        
        // Listar categorias restantes
        $stmt = $conn->query("SELECT * FROM categorias ORDER BY nome ASC");
        $categorias_finais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nCategorias finais:\n";
        foreach ($categorias_finais as $cat) {
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
    echo "=== SCRIPT DE CORREÇÃO DE CATEGORIAS DUPLICADAS ===\n";
    echo "ATENÇÃO: Este script irá remover categorias duplicadas.\n";
    echo "Certifique-se de ter um backup do banco de dados.\n\n";
    
    // Se executando via linha de comando
    if (php_sapi_name() === 'cli') {
        echo "Deseja continuar? (s/n): ";
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($input) !== 's') {
            echo "Operação cancelada.\n";
            exit;
        }
    } else {
        // Se executando via web, adicionar confirmação JavaScript
        echo '<script>
            if (!confirm("ATENÇÃO: Este script irá remover categorias duplicadas.\\nCertifique-se de ter um backup do banco de dados.\\n\\nDeseja continuar?")) {
                window.location.href = "index.php";
            }
        </script>';
    }
    
    echo "\nIniciando correção...\n";
    if (fixDuplicateCategories()) {
        echo "\n✅ Correção concluída com sucesso!\n";
        if (php_sapi_name() !== 'cli') {
            echo '<br><a href="index.php">Voltar ao início</a>';
        }
    } else {
        echo "\n❌ Erro durante a correção.\n";
    }
}
?>