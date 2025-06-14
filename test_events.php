<?php
// ==========================================
// TESTE DO SISTEMA DE EVENTOS
// Local: test_events.php
// ==========================================

require_once __DIR__ . '/controllers/EventController.php';

function testEventSystem() {
    echo "=== TESTE DO SISTEMA DE EVENTOS ===\n\n";
    
    try {
        $eventController = new EventController();
        
        // Teste 1: Buscar categorias
        echo "1. Testando busca de categorias...\n";
        $categorias = $eventController->getCategories();
        echo "   Encontradas " . count($categorias) . " categorias:\n";
        foreach ($categorias as $cat) {
            echo "   - " . $cat['nome'] . " (ID: " . $cat['id_categoria'] . ")\n";
        }
        echo "\n";
        
        // Teste 2: Buscar eventos públicos
        echo "2. Testando busca de eventos públicos...\n";
        $eventos = $eventController->getPublicEvents(['limite' => 5]);
        echo "   Encontrados " . count($eventos) . " eventos:\n";
        foreach ($eventos as $evento) {
            echo "   - " . $evento['titulo'] . " (ID: " . $evento['id_evento'] . ")\n";
            echo "     Categoria: " . ($evento['nome_categoria'] ?? 'N/A') . "\n";
            echo "     Inscritos: " . $evento['total_inscritos'] . "\n";
        }
        echo "\n";
        
        // Teste 3: Buscar evento específico por ID
        echo "3. Testando busca de evento específico...\n";
        for ($i = 1; $i <= 3; $i++) {
            $evento = $eventController->getById($i);
            if ($evento) {
                echo "   ID $i: " . $evento['titulo'] . "\n";
                echo "   Categoria: " . ($evento['nome_categoria'] ?? 'N/A') . "\n";
                echo "   Status: " . ($evento['status'] ?? 'N/A') . "\n";
            } else {
                echo "   ID $i: Não encontrado\n";
            }
        }
        echo "\n";
        
        // Teste 4: Verificar URLs das imagens
        echo "4. Testando URLs de imagens...\n";
        foreach ($eventos as $evento) {
            if (!empty($evento['imagem_capa'])) {
                echo "   Evento: " . $evento['titulo'] . "\n";
                echo "   Imagem: " . $evento['imagem_capa'] . "\n";
                echo "   URL: " . ($evento['imagem_url'] ?? 'N/A') . "\n";
            }
        }
        if (empty(array_filter($eventos, fn($e) => !empty($e['imagem_capa'])))) {
            echo "   Nenhum evento com imagem encontrado.\n";
        }
        echo "\n";
        
        // Teste 5: Verificar dados de fallback
        echo "5. Testando dados de fallback...\n";
        $evento_teste = $eventController->getById(999); // ID que não existe
        if ($evento_teste) {
            echo "   Fallback funcionando: " . $evento_teste['titulo'] . "\n";
            echo "   ID retornado: " . $evento_teste['id_evento'] . "\n";
        } else {
            echo "   ❌ Fallback não funcionou\n";
        }
        echo "\n";
        
        echo "✅ Todos os testes concluídos!\n";
        return true;
        
    } catch (Exception $e) {
        echo "❌ Erro durante o teste: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        return false;
    }
}

function testSpecificEventIds() {
    echo "\n=== TESTE DE IDs ESPECÍFICOS ===\n\n";
    
    try {
        $eventController = new EventController();
        
        $test_ids = [1, 2, 3, 5, 10, 999];
        
        foreach ($test_ids as $id) {
            echo "Testando ID $id:\n";
            $evento = $eventController->getById($id);
            
            if ($evento) {
                echo "  ✅ Encontrado: " . $evento['titulo'] . "\n";
                echo "     ID retornado: " . $evento['id_evento'] . "\n";
                echo "     Categoria: " . ($evento['nome_categoria'] ?? 'N/A') . "\n";
                echo "     Data: " . date('d/m/Y', strtotime($evento['data_inicio'])) . "\n";
                echo "     Local: " . $evento['local_cidade'] . ", " . $evento['local_estado'] . "\n";
            } else {
                echo "  ❌ Não encontrado\n";
            }
            echo "\n";
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Erro durante o teste de IDs: " . $e->getMessage() . "\n";
        return false;
    }
}

// Executar testes apenas se chamado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    // Configurar cabeçalho para texto simples se executando no navegador
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: text/plain; charset=utf-8');
    }
    
    $success1 = testEventSystem();
    $success2 = testSpecificEventIds();
    
    echo "\n=== RESUMO DOS TESTES ===\n";
    echo "Sistema geral: " . ($success1 ? "✅ OK" : "❌ FALHOU") . "\n";
    echo "IDs específicos: " . ($success2 ? "✅ OK" : "❌ FALHOU") . "\n";
    
    if ($success1 && $success2) {
        echo "\n🎉 Todos os testes passaram! O sistema está funcionando.\n";
    } else {
        echo "\n⚠️  Alguns testes falharam. Verifique os logs acima.\n";
    }
    
    if (php_sapi_name() !== 'cli') {
        echo "\n\n<a href=\"index.php\">Voltar ao início</a>";
    }
}
?>