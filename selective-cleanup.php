<?php
// ==========================================
// LIMPEZA SELETIVA DE EVENTOS ESPECÍFICOS
// Local: selective-cleanup.php
// ==========================================

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>Limpeza Seletiva de Eventos</title>";
echo "<meta charset='utf-8'>";
echo "<style>
    body{font-family:Arial;margin:20px;background:#f5f5f5;} 
    .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
    .success{color:green;font-weight:bold;} 
    .error{color:red;font-weight:bold;} 
    .warning{color:orange;font-weight:bold;} 
    .info{color:blue;font-weight:bold;}
    .event-card{border:1px solid #ddd;margin:10px 0;padding:15px;border-radius:8px;background:#f9f9f9;}
    .event-card.selected{background:#ffe6e6;border-color:#ff6b6b;}
    .btn{padding:8px 15px;margin:5px;border:none;border-radius:5px;cursor:pointer;text-decoration:none;display:inline-block;}
    .btn-danger{background:#dc3545;color:white;} .btn-danger:hover{background:#c82333;}
    .btn-primary{background:#007bff;color:white;} .btn-primary:hover{background:#0056b3;}
    .btn-success{background:#28a745;color:white;} .btn-success:hover{background:#1e7e34;}
    table{width:100%;border-collapse:collapse;margin:15px 0;}
    th,td{border:1px solid #ddd;padding:8px;text-align:left;}
    th{background:#f8f9fa;}
</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h2>🎯 Limpeza Seletiva de Eventos</h2>";
echo "<p class='info'>ℹ️ Este script permite excluir eventos específicos, preservando outros.</p>";

try {
    // Conectar ao banco
    $database_url = getenv('DATABASE_URL');
    if (!$database_url) {
        throw new Exception("DATABASE_URL não encontrada");
    }

    $url_parts = parse_url($database_url);
    $host = $url_parts['host'];
    $port = $url_parts['port'] ?? 3306;
    $dbname = ltrim($url_parts['path'], '/');
    $username = $url_parts['user'];
    $password = $url_parts['pass'];

    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", 
                   $username, $password, [
                       PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                   ]);

    echo "<p class='success'>✅ Conectado ao banco: {$host}/{$dbname}</p>";

    // Processar exclusões se solicitado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_events'])) {
        $eventsToDelete = $_POST['event_ids'] ?? [];
        
        if (empty($eventsToDelete)) {
            echo "<p class='warning'>⚠️ Nenhum evento selecionado para exclusão.</p>";
        } else {
            echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:15px 0;'>";
            echo "<h3>🗑️ Excluindo Eventos Selecionados...</h3>";
            
            $pdo->beginTransaction();
            
            try {
                $deletedCount = 0;
                $totalInscricoes = 0;
                $totalFavoritos = 0;
                $totalNotificacoes = 0;
                
                foreach ($eventsToDelete as $eventId) {
                    $eventId = (int)$eventId;
                    
                    // Buscar dados do evento
                    $stmt = $pdo->prepare("SELECT titulo FROM eventos WHERE id_evento = ?");
                    $stmt->execute([$eventId]);
                    $evento = $stmt->fetch();
                    
                    if ($evento) {
                        echo "<p><strong>📅 Excluindo:</strong> ID {$eventId} - " . htmlspecialchars($evento['titulo']) . "</p>";
                        
                        // Contar e excluir inscrições
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscricoes WHERE id_evento = ?");
                        $stmt->execute([$eventId]);
                        $inscricoes = $stmt->fetchColumn();
                        $totalInscricoes += $inscricoes;
                        
                        $stmt = $pdo->prepare("DELETE FROM inscricoes WHERE id_evento = ?");
                        $stmt->execute([$eventId]);
                        
                        // Contar e excluir favoritos
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE id_evento = ?");
                        $stmt->execute([$eventId]);
                        $favoritos = $stmt->fetchColumn();
                        $totalFavoritos += $favoritos;
                        
                        $stmt = $pdo->prepare("DELETE FROM favoritos WHERE id_evento = ?");
                        $stmt->execute([$eventId]);
                        
                        // Contar e excluir notificações
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE id_referencia = ? AND tipo = 'evento'");
                        $stmt->execute([$eventId]);
                        $notificacoes = $stmt->fetchColumn();
                        $totalNotificacoes += $notificacoes;
                        
                        $stmt = $pdo->prepare("DELETE FROM notificacoes WHERE id_referencia = ? AND tipo = 'evento'");
                        $stmt->execute([$eventId]);
                        
                        // Excluir o evento
                        $stmt = $pdo->prepare("DELETE FROM eventos WHERE id_evento = ?");
                        $stmt->execute([$eventId]);
                        $affected = $stmt->rowCount();
                        
                        if ($affected > 0) {
                            $deletedCount++;
                            echo "<span style='color:green;'>✅ Excluído ({$inscricoes} inscrições, {$favoritos} favoritos, {$notificacoes} notificações)</span></p>";
                        } else {
                            echo "<span style='color:red;'>❌ Erro ao excluir</span></p>";
                        }
                    } else {
                        echo "<p style='color:orange;'>⚠️ Evento ID {$eventId} não encontrado</p>";
                    }
                }
                
                $pdo->commit();
                
                echo "<div style='background:#d4edda;padding:15px;border-radius:5px;margin:15px 0;'>";
                echo "<h4 class='success'>🎉 Exclusão Concluída!</h4>";
                echo "<ul>";
                echo "<li><strong>Eventos excluídos:</strong> {$deletedCount}</li>";
                echo "<li><strong>Inscrições removidas:</strong> {$totalInscricoes}</li>";
                echo "<li><strong>Favoritos removidos:</strong> {$totalFavoritos}</li>";
                echo "<li><strong>Notificações removidas:</strong> {$totalNotificacoes}</li>";
                echo "</ul>";
                echo "</div>";
                
                echo "<p><a href='?' class='btn btn-primary'>🔄 Atualizar Lista</a></p>";
                
            } catch (Exception $e) {
                $pdo->rollback();
                echo "<p class='error'>❌ Erro durante a exclusão: " . $e->getMessage() . "</p>";
            }
            
            echo "</div>";
        }
    }

    // Listar eventos existentes
    $stmt = $pdo->query("
        SELECT e.id_evento, e.titulo, e.data_inicio, e.data_criacao, e.status,
               COUNT(i.id_inscricao) as total_inscricoes
        FROM eventos e
        LEFT JOIN inscricoes i ON e.id_evento = i.id_evento
        GROUP BY e.id_evento
        ORDER BY e.id_evento
    ");
    $eventos = $stmt->fetchAll();

    echo "<h3>📋 Eventos Atuais no Banco</h3>";
    
    if (empty($eventos)) {
        echo "<p class='info'>ℹ️ Nenhum evento encontrado no banco.</p>";
    } else {
        echo "<form method='POST' onsubmit='return confirmDeletion()'>";
        echo "<div style='background:#e7f3ff;padding:15px;border-radius:5px;margin:15px 0;'>";
        echo "<p><strong>📊 Total de eventos:</strong> " . count($eventos) . "</p>";
        echo "<p><strong>Instruções:</strong> Marque os eventos que deseja excluir e clique em 'Excluir Selecionados'.</p>";
        echo "</div>";
        
        echo "<table>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Selecionar</th>";
        echo "<th>ID</th>";
        echo "<th>Título</th>";
        echo "<th>Data do Evento</th>";
        echo "<th>Criado em</th>";
        echo "<th>Status</th>";
        echo "<th>Inscrições</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($eventos as $evento) {
            $statusClass = $evento['status'] === 'publicado' ? 'success' : 'warning';
            echo "<tr>";
            echo "<td><input type='checkbox' name='event_ids[]' value='{$evento['id_evento']}' id='event_{$evento['id_evento']}'></td>";
            echo "<td>{$evento['id_evento']}</td>";
            echo "<td><label for='event_{$evento['id_evento']}'>" . htmlspecialchars($evento['titulo']) . "</label></td>";
            echo "<td>" . date('d/m/Y', strtotime($evento['data_inicio'])) . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($evento['data_criacao'])) . "</td>";
            echo "<td><span style='color:" . ($evento['status'] === 'publicado' ? 'green' : 'orange') . ";'>●</span> {$evento['status']}</td>";
            echo "<td>{$evento['total_inscricoes']}</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        
        echo "<div style='margin:20px 0;padding:15px;background:#f8f9fa;border-radius:5px;'>";
        echo "<p><input type='checkbox' id='select_all' onclick='toggleAll()'> <label for='select_all'><strong>Selecionar/Desmarcar Todos</strong></label></p>";
        echo "<button type='submit' name='delete_events' class='btn btn-danger'>🗑️ Excluir Eventos Selecionados</button>";
        echo "<a href='?' class='btn btn-primary'>🔄 Atualizar</a>";
        echo "</div>";
        
        echo "</form>";
    }

    // Mostrar estatísticas
    echo "<hr>";
    echo "<h3>📊 Estatísticas do Banco</h3>";
    $stats = [
        'eventos' => $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn(),
        'usuarios' => $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
        'inscricoes' => $pdo->query("SELECT COUNT(*) FROM inscricoes")->fetchColumn(),
        'categorias' => $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn()
    ];

    echo "<div style='display:flex;gap:20px;flex-wrap:wrap;'>";
    foreach ($stats as $tabela => $count) {
        $emoji = ['eventos' => '📅', 'usuarios' => '👥', 'inscricoes' => '📝', 'categorias' => '🏷️'][$tabela];
        echo "<div style='background:#f8f9fa;padding:15px;border-radius:5px;text-align:center;min-width:120px;'>";
        echo "<div style='font-size:24px;'>{$emoji}</div>";
        echo "<div style='font-size:24px;font-weight:bold;color:#007bff;'>{$count}</div>";
        echo "<div>" . ucfirst($tabela) . "</div>";
        echo "</div>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Erro de conexão: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='/index.php' class='btn btn-success'>🏠 Voltar ao Site Principal</a></p>";
echo "<p><small style='color:#666;'>Script executado em: " . date('d/m/Y H:i:s') . " | Modo: Seletivo</small></p>";

echo "</div>"; // container

// JavaScript para funcionalidades
echo "<script>
function toggleAll() {
    const selectAll = document.getElementById('select_all');
    const checkboxes = document.querySelectorAll('input[name=\"event_ids[]\"]');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function confirmDeletion() {
    const selected = document.querySelectorAll('input[name=\"event_ids[]\"]:checked');
    if (selected.length === 0) {
        alert('⚠️ Selecione pelo menos um evento para excluir.');
        return false;
    }
    
    const eventTitles = Array.from(selected).map(cb => {
        const row = cb.closest('tr');
        return row.cells[2].textContent.trim();
    });
    
    const message = `⚠️ ATENÇÃO: Você está prestes a excluir ${selected.length} evento(s):\\n\\n` +
                   eventTitles.map(title => `• ${title}`).join('\\n') +
                   `\\n\\nEsta ação também removerá:\\n• Todas as inscrições destes eventos\\n• Todos os favoritos destes eventos\\n• Todas as notificações destes eventos\\n\\nEsta ação é IRREVERSÍVEL.\\n\\nTem certeza absoluta?`;
    
    return confirm(message);
}
</script>";

echo "</body></html>";
?>