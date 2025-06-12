<?php
// ==========================================
// SCRIPT DE DEBUG PARA EVENTOS
// Local: debug_events.php (criar na raiz)
// ==========================================

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug de Eventos - Conecta Eventos</h2>";

try {
    // Carregar configurações
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        echo "✅ Database config carregado<br>";
    } else {
        echo "❌ Database config não encontrado<br>";
        exit;
    }

    // Conectar ao banco
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if (!$conn) {
        echo "❌ Falha na conexão com banco<br>";
        exit;
    }
    
    echo "✅ Conectado ao banco com sucesso<br><br>";
    
    // 1. VERIFICAR TODOS OS EVENTOS
    echo "<h3>📋 TODOS OS EVENTOS NO BANCO</h3>";
    
    $stmt = $conn->query("SELECT * FROM eventos ORDER BY data_criacao DESC");
    $todos_eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Total de eventos encontrados: " . count($todos_eventos) . "</strong><br><br>";
    
    if (count($todos_eventos) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Título</th><th>Status</th><th>Data Início</th><th>Organizador ID</th><th>Categoria ID</th><th>Criado em</th>";
        echo "</tr>";
        
        foreach ($todos_eventos as $evento) {
            echo "<tr>";
            echo "<td>" . $evento['id_evento'] . "</td>";
            echo "<td>" . htmlspecialchars($evento['titulo']) . "</td>";
            echo "<td>" . $evento['status'] . "</td>";
            echo "<td>" . $evento['data_inicio'] . "</td>";
            echo "<td>" . $evento['id_organizador'] . "</td>";
            echo "<td>" . $evento['id_categoria'] . "</td>";
            echo "<td>" . $evento['data_criacao'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<p style='color: red;'>❌ NENHUM EVENTO ENCONTRADO NO BANCO!</p>";
    }
    
    // 2. TESTAR QUERY DO EventController
    echo "<h3>🔍 TESTE DA QUERY DO EventController</h3>";
    
    $query = "SELECT 
                e.*,
                c.nome as nome_categoria,
                u.nome as nome_organizador,
                COUNT(i.id_inscricao) as total_inscritos
             FROM eventos e
             LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
             LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
             LEFT JOIN inscricoes i ON e.id_evento = i.id_evento AND i.status = 'confirmada'
             WHERE e.status = 'publicado'
             AND e.data_inicio >= CURDATE()
             GROUP BY e.id_evento
             ORDER BY e.data_inicio ASC";
    
    echo "<strong>Query original (apenas publicados e futuros):</strong><br>";
    echo "<code>" . htmlspecialchars($query) . "</code><br><br>";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $eventos_query_original = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Resultados da query original: " . count($eventos_query_original) . " eventos</strong><br><br>";
    
    // 3. TESTAR QUERY MODIFICADA (INCLUINDO RASCUNHOS)
    echo "<h3>🔧 TESTE DA QUERY MODIFICADA</h3>";
    
    $query_modificada = "SELECT 
                e.*,
                c.nome as nome_categoria,
                u.nome as nome_organizador,
                COUNT(i.id_inscricao) as total_inscritos
             FROM eventos e
             LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
             LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
             LEFT JOIN inscricoes i ON e.id_evento = i.id_evento AND i.status = 'confirmada'
             WHERE e.status IN ('publicado', 'rascunho')
             GROUP BY e.id_evento
             ORDER BY e.data_criacao DESC";
    
    echo "<strong>Query modificada (incluindo rascunhos):</strong><br>";
    echo "<code>" . htmlspecialchars($query_modificada) . "</code><br><br>";
    
    $stmt = $conn->prepare($query_modificada);
    $stmt->execute();
    $eventos_query_modificada = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Resultados da query modificada: " . count($eventos_query_modificada) . " eventos</strong><br><br>";
    
    if (count($eventos_query_modificada) > 0) {
        echo "<h4>📋 Eventos encontrados pela query modificada:</h4>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Título</th><th>Status</th><th>Categoria</th><th>Organizador</th><th>Data</th>";
        echo "</tr>";
        
        foreach ($eventos_query_modificada as $evento) {
            echo "<tr>";
            echo "<td>" . $evento['id_evento'] . "</td>";
            echo "<td>" . htmlspecialchars($evento['titulo']) . "</td>";
            echo "<td>" . $evento['status'] . "</td>";
            echo "<td>" . htmlspecialchars($evento['nome_categoria'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($evento['nome_organizador'] ?? 'N/A') . "</td>";
            echo "<td>" . $evento['data_inicio'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // 4. VERIFICAR PROBLEMAS COMUNS
    echo "<h3>⚠️ DIAGNÓSTICO DE PROBLEMAS</h3>";
    
    $problemas = [];
    
    // Verificar eventos sem status publicado
    $rascunhos = array_filter($todos_eventos, function($e) { return $e['status'] === 'rascunho'; });
    if (count($rascunhos) > 0) {
        $problemas[] = "📝 Há " . count($rascunhos) . " eventos em status 'rascunho' que não aparecem na lista pública";
    }
    
    // Verificar eventos com data passada
    $eventos_passados = array_filter($todos_eventos, function($e) { 
        return strtotime($e['data_inicio']) < strtotime('today'); 
    });
    if (count($eventos_passados) > 0) {
        $problemas[] = "📅 Há " . count($eventos_passados) . " eventos com data passada que não aparecem na lista";
    }
    
    // Verificar eventos sem organizador
    $sem_organizador = array_filter($todos_eventos, function($e) { return empty($e['id_organizador']); });
    if (count($sem_organizador) > 0) {
        $problemas[] = "👤 Há " . count($sem_organizador) . " eventos sem organizador válido";
    }
    
    // Verificar JOIN com usuários
    $stmt = $conn->query("SELECT COUNT(*) as total FROM eventos e LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario WHERE u.id_usuario IS NULL");
    $sem_usuario_valido = $stmt->fetch()['total'];
    if ($sem_usuario_valido > 0) {
        $problemas[] = "🔗 Há $sem_usuario_valido eventos com id_organizador que não corresponde a nenhum usuário";
    }
    
    if (empty($problemas)) {
        echo "<p style='color: green;'>✅ Nenhum problema óbvio detectado!</p>";
    } else {
        echo "<ul>";
        foreach ($problemas as $problema) {
            echo "<li style='color: orange;'>$problema</li>";
        }
        echo "</ul>";
    }
    
    // 5. SOLUÇÕES RECOMENDADAS
    echo "<h3>💡 SOLUÇÕES RECOMENDADAS</h3>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>✅ Para resolver o problema:</h4>";
    echo "<ol>";
    echo "<li><strong>Publicar eventos em rascunho:</strong><br>";
    if (count($rascunhos) > 0) {
        echo "<code>UPDATE eventos SET status = 'publicado' WHERE status = 'rascunho';</code><br>";
    }
    echo "</li>";
    
    echo "<li><strong>Atualizar datas passadas (para teste):</strong><br>";
    if (count($eventos_passados) > 0) {
        echo "<code>UPDATE eventos SET data_inicio = DATE_ADD(CURDATE(), INTERVAL 7 DAY) WHERE data_inicio < CURDATE();</code><br>";
    }
    echo "</li>";
    
    echo "<li><strong>Usar query modificada no EventController:</strong><br>";
    echo "Substituir a linha WHERE e.status = 'publicado' por WHERE e.status IN ('publicado', 'rascunho')<br>";
    echo "</li>";
    
    echo "<li><strong>Verificar index.php:</strong><br>";
    echo "Certificar-se de que está usando a versão corrigida do index.php<br>";
    echo "</li>";
    echo "</ol>";
    echo "</div>";
    
    // 6. EXECUTAR CORREÇÕES AUTOMÁTICAS
    echo "<h3>🔧 EXECUTAR CORREÇÕES</h3>";
    
    if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
        echo "<strong>🚀 Executando correções automáticas...</strong><br><br>";
        
        // Publicar todos os rascunhos
        if (count($rascunhos) > 0) {
            $stmt = $conn->prepare("UPDATE eventos SET status = 'publicado' WHERE status = 'rascunho'");
            $result = $stmt->execute();
            if ($result) {
                echo "✅ " . count($rascunhos) . " eventos em rascunho foram publicados<br>";
            }
        }
        
        // Atualizar datas passadas
        if (count($eventos_passados) > 0) {
            $stmt = $conn->prepare("UPDATE eventos SET data_inicio = DATE_ADD(CURDATE(), INTERVAL 7 DAY) WHERE data_inicio < CURDATE()");
            $result = $stmt->execute();
            if ($result) {
                echo "✅ Datas de eventos passados foram atualizadas para +7 dias<br>";
            }
        }
        
        echo "<br><strong>🎉 Correções aplicadas! Recarregue a página principal para ver os eventos.</strong><br>";
        echo "<a href='/'>👉 Ir para a página principal</a><br>";
        
    } else {
        echo "<a href='?fix=true' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
        echo "🔧 EXECUTAR CORREÇÕES AUTOMÁTICAS";
        echo "</a><br><br>";
        echo "<small>⚠️ Isso irá publicar todos os rascunhos e atualizar datas passadas</small>";
    }
    
    // 7. TESTE FINAL
    echo "<h3>🧪 TESTE FINAL</h3>";
    echo "<p>Depois das correções, os eventos devem aparecer na página principal.</p>";
    echo "<p><strong>Links úteis:</strong></p>";
    echo "<ul>";
    echo "<li><a href='/'>📋 Página Principal</a></li>";
    echo "<li><a href='/views/events/create.php'>➕ Criar Novo Evento</a></li>";
    echo "<li><a href='/views/dashboard/organizer.php'>👨‍💼 Dashboard do Organizador</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; color: #c62828;'>";
    echo "<h4>❌ ERRO:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>