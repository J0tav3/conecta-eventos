<?php
// ==========================================
// DEBUG DE SESSÃO - ARQUIVO TEMPORÁRIO
// Local: debug-session.php (raiz do projeto)
// ==========================================

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug de Sessão e Eventos</h2>";

// Informações da sessão
echo "<h3>📊 Dados da Sessão:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar se usuário está logado
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

echo "<h3>✅ Status do Login:</h3>";
echo "<ul>";
echo "<li><strong>Logado:</strong> " . ($isLoggedIn ? 'SIM' : 'NÃO') . "</li>";
echo "<li><strong>User ID:</strong> " . ($userId ?? 'NULL') . "</li>";
echo "<li><strong>Tipo:</strong> " . ($userType ?? 'NULL') . "</li>";
echo "</ul>";

// Testar conexão com banco
echo "<h3>🗄️ Teste de Conexão com Banco:</h3>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "✅ Conexão com banco: OK<br>";
        
        // Verificar tabela de eventos
        $stmt = $conn->query("SELECT COUNT(*) as total FROM eventos");
        $result = $stmt->fetch();
        echo "📋 Total de eventos no banco: " . $result['total'] . "<br>";
        
        // Verificar eventos por organizador
        if ($userId) {
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM eventos WHERE id_organizador = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            echo "👤 Eventos do usuário atual ($userId): " . $result['total'] . "<br>";
            
            // Listar eventos do usuário
            $stmt = $conn->prepare("SELECT id_evento, titulo, status, id_organizador, data_criacao FROM eventos WHERE id_organizador = ? ORDER BY data_criacao DESC");
            $stmt->execute([$userId]);
            $eventos = $stmt->fetchAll();
            
            if ($eventos) {
                echo "<h4>📝 Seus Eventos:</h4>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Título</th><th>Status</th><th>Organizador ID</th><th>Data Criação</th></tr>";
                foreach ($eventos as $evento) {
                    echo "<tr>";
                    echo "<td>" . $evento['id_evento'] . "</td>";
                    echo "<td>" . htmlspecialchars($evento['titulo']) . "</td>";
                    echo "<td>" . $evento['status'] . "</td>";
                    echo "<td>" . $evento['id_organizador'] . "</td>";
                    echo "<td>" . $evento['data_criacao'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "❌ Nenhum evento encontrado para este usuário<br>";
            }
            
            // Verificar todos os eventos (para comparação)
            $stmt = $conn->query("SELECT id_evento, titulo, status, id_organizador, data_criacao FROM eventos ORDER BY data_criacao DESC LIMIT 10");
            $todos_eventos = $stmt->fetchAll();
            
            if ($todos_eventos) {
                echo "<h4>🌐 Todos os Eventos (últimos 10):</h4>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Título</th><th>Status</th><th>Organizador ID</th><th>Data Criação</th><th>É Seu?</th></tr>";
                foreach ($todos_eventos as $evento) {
                    $isSeu = ($evento['id_organizador'] == $userId) ? '✅ SIM' : '❌ NÃO';
                    echo "<tr>";
                    echo "<td>" . $evento['id_evento'] . "</td>";
                    echo "<td>" . htmlspecialchars($evento['titulo']) . "</td>";
                    echo "<td>" . $evento['status'] . "</td>";
                    echo "<td>" . $evento['id_organizador'] . "</td>";
                    echo "<td>" . $evento['data_criacao'] . "</td>";
                    echo "<td>" . $isSeu . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
    } else {
        echo "❌ Erro: Sem conexão com banco<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

// Testar EventController
echo "<h3>🎯 Teste do EventController:</h3>";
try {
    require_once 'controllers/EventController.php';
    $eventController = new EventController();
    
    if ($userId) {
        $eventos = $eventController->getEventsByOrganizer($userId);
        echo "📊 Eventos retornados pelo controller: " . count($eventos) . "<br>";
        
        if ($eventos) {
            echo "<h4>📋 Lista do Controller:</h4>";
            foreach ($eventos as $evento) {
                echo "- " . htmlspecialchars($evento['titulo']) . " (ID: " . $evento['id_evento'] . ", Status: " . $evento['status'] . ")<br>";
            }
        }
    } else {
        echo "❌ Não é possível testar sem user_id<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro no EventController: " . $e->getMessage() . "<br>";
}

// Informações do servidor
echo "<h3>🖥️ Informações do Servidor:</h3>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Session ID:</strong> " . session_id() . "</li>";
echo "<li><strong>Session Save Path:</strong> " . session_save_path() . "</li>";
echo "<li><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "<li><strong>Environment:</strong> " . ($_ENV['RAILWAY_ENVIRONMENT'] ?? 'development') . "</li>";
echo "</ul>";

// Limpar variáveis de sessão (apenas para teste)
echo "<h3>🔧 Ações de Teste:</h3>";
echo "<a href='?action=clear_session' style='color: red;'>🗑️ Limpar Sessão (CUIDADO!)</a><br>";
echo "<a href='views/auth/login.php'>🔑 Ir para Login</a><br>";
echo "<a href='views/events/create.php'>➕ Criar Evento</a><br>";
echo "<a href='views/events/list.php'>📋 Lista de Eventos</a><br>";

if (isset($_GET['action']) && $_GET['action'] === 'clear_session') {
    session_destroy();
    echo "<script>alert('Sessão limpa! Faça login novamente.'); window.location.href = 'views/auth/login.php';</script>";
}

echo "<hr>";
echo "<p><small>Este arquivo é apenas para debug. Remova-o em produção.</small></p>";
?>