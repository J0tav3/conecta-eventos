<?php
// ========================================
// API DE ANALYTICS E ESTATÍSTICAS
// ========================================
// Local: api/analytics.php
// ========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$basePath = dirname(__DIR__);
require_once $basePath . '/config/config.php';
require_once $basePath . '/includes/session.php';

// Verificar se usuário está logado e é organizador
if (!isLoggedIn() || !isOrganizer()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Apenas organizadores podem acessar analytics.'
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    $userId = getUserId();
    
    $action = $_GET['action'] ?? 'overview';
    $period = $_GET['period'] ?? 'month'; // month, quarter, year
    
    switch ($action) {
        case 'overview':
            $data = getOverviewStats($conn, $userId);
            break;
            
        case 'events_by_month':
            $data = getEventsByMonth($conn, $userId, $period);
            break;
            
        case 'subscriptions_by_month':
            $data = getSubscriptionsByMonth($conn, $userId, $period);
            break;
            
        case 'events_by_category':
            $data = getEventsByCategory($conn, $userId);
            break;
            
        case 'events_by_status':
            $data = getEventsByStatus($conn, $userId);
            break;
            
        case 'top_events':
            $limit = $_GET['limit'] ?? 5;
            $data = getTopEvents($conn, $userId, $limit);
            break;
            
        case 'revenue_stats':
            $data = getRevenueStats($conn, $userId, $period);
            break;
            
        case 'participant_stats':
            $data = getParticipantStats($conn, $userId, $period);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Ação não reconhecida.'
            ]);
            exit();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'period' => $period,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

/**
 * Estatísticas gerais (overview)
 */
function getOverviewStats($conn, $userId) {
    $stats = [];
    
    // Total de eventos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM eventos WHERE id_organizador = ?");
    $stmt->execute([$userId]);
    $stats['total_events'] = $stmt->fetch()['total'];
    
    // Total de inscrições
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM inscricoes i 
        INNER JOIN eventos e ON i.id_evento = e.id_evento 
        WHERE e.id_organizador = ? AND i.status = 'confirmada'
    ");
    $stmt->execute([$userId]);
    $stats['total_subscriptions'] = $stmt->fetch()['total'];
    
    // Participantes únicos
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT i.id_participante) as total 
        FROM inscricoes i 
        INNER JOIN eventos e ON i.id_evento = e.id_evento 
        WHERE e.id_organizador = ? AND i.status = 'confirmada'
    ");
    $stmt->execute([$userId]);
    $stats['unique_participants'] = $stmt->fetch()['total'];
    
    // Avaliação média
    $stmt = $conn->prepare("
        SELECT AVG(i.avaliacao_evento) as avg_rating 
        FROM inscricoes i 
        INNER JOIN eventos e ON i.id_evento = e.id_evento 
        WHERE e.id_organizador = ? AND i.avaliacao_evento IS NOT NULL
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    $stats['avg_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 2) : 0;
    
    // Receita total (apenas eventos pagos)
    $stmt = $conn->prepare("
        SELECT SUM(e.preco) as total_revenue 
        FROM inscricoes i 
        INNER JOIN eventos e ON i.id_evento = e.id_evento 
        WHERE e.id_organizador = ? AND i.status = 'confirmada' AND e.evento_gratuito = 0
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    $stats['total_revenue'] = $result['total_revenue'] ?? 0;
    
    // Taxa de crescimento (últimos 30 dias vs 30 dias anteriores)
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN e.data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent,
            COUNT(CASE WHEN e.data_criacao >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                       AND e.data_criacao < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as previous
        FROM eventos e 
        WHERE e.id_organizador = ?
    ");
    $stmt->execute([$userId]);
    $growth = $stmt->fetch();
    
    $stats['growth_rate'] = 0;
    if ($growth['previous'] > 0) {
        $stats['growth_rate'] = round((($growth['recent'] - $growth['previous']) / $growth['previous']) * 100, 1);
    }
    
    return $stats;
}

/**
 * Eventos criados por mês
 */
function getEventsByMonth($conn, $userId, $period) {
    $months = getPeriodMonths($period);
    
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(data_criacao, '%Y-%m') as month,
            COUNT(*) as count
        FROM eventos 
        WHERE id_organizador = ? 
        AND data_criacao >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(data_criacao, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$userId, $months]);
    $results = $stmt->fetchAll();
    
    // Organizar dados para os últimos meses
    $data = [];
    $labels = [];
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M', strtotime("-$i months"));
        
        $labels[] = $monthLabel;
        
        $count = 0;
        foreach ($results as $result) {
            if ($result['month'] === $month) {
                $count = $result['count'];
                break;
            }
        }
        $data[] = (int)$count;
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

/**
 * Inscrições por mês
 */
function getSubscriptionsByMonth($conn, $userId, $period) {
    $months = getPeriodMonths($period);
    
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(i.data_inscricao, '%Y-%m') as month,
            COUNT(*) as count
        FROM inscricoes i 
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE e.id_organizador = ? 
        AND i.status = 'confirmada'
        AND i.data_inscricao >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(i.data_inscricao, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$userId, $months]);
    $results = $stmt->fetchAll();
    
    $data = [];
    $labels = [];
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M', strtotime("-$i months"));
        
        $labels[] = $monthLabel;
        
        $count = 0;
        foreach ($results as $result) {
            if ($result['month'] === $month) {
                $count = $result['count'];
                break;
            }
        }
        $data[] = (int)$count;
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

/**
 * Eventos por categoria
 */
function getEventsByCategory($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(c.nome, 'Sem Categoria') as category_name,
            COUNT(*) as count
        FROM eventos e 
        LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
        WHERE e.id_organizador = ?
        GROUP BY e.id_categoria, c.nome
        ORDER BY count DESC
    ");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll();
    
    $labels = [];
    $data = [];
    
    foreach ($results as $result) {
        $labels[] = $result['category_name'];
        $data[] = (int)$result['count'];
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

/**
 * Eventos por status
 */
function getEventsByStatus($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM eventos 
        WHERE id_organizador = ?
        GROUP BY status
        ORDER BY count DESC
    ");
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll();
    
    $statusMap = [
        'publicado' => 'Publicados',
        'rascunho' => 'Rascunhos',
        'cancelado' => 'Cancelados',
        'finalizado' => 'Finalizados'
    ];
    
    $labels = [];
    $data = [];
    
    foreach ($results as $result) {
        $labels[] = $statusMap[$result['status']] ?? ucfirst($result['status']);
        $data[] = (int)$result['count'];
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

/**
 * Top eventos por popularidade
 */
function getTopEvents($conn, $userId, $limit) {
    $stmt = $conn->prepare("
        SELECT 
            e.id_evento,
            e.titulo,
            e.data_inicio,
            e.preco,
            e.evento_gratuito,
            c.nome as categoria,
            COUNT(i.id_inscricao) as total_inscricoes,
            AVG(i.avaliacao_evento) as avg_rating,
            SUM(CASE WHEN e.evento_gratuito = 0 THEN e.preco ELSE 0 END) as revenue
        FROM eventos e
        LEFT JOIN inscricoes i ON e.id_evento = i.id_evento AND i.status = 'confirmada'
        LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
        WHERE e.id_organizador = ?
        GROUP BY e.id_evento
        ORDER BY total_inscricoes DESC, avg_rating DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    $results = $stmt->fetchAll();
    
    foreach ($results as &$result) {
        $result['total_inscricoes'] = (int)$result['total_inscricoes'];
        $result['avg_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
        $result['revenue'] = (float)$result['revenue'];
        $result['data_inicio_formatada'] = date('d/m/Y', strtotime($result['data_inicio']));
        $result['preco_formatado'] = $result['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($result['preco'], 2, ',', '.');
    }
    
    return $results;
}

/**
 * Estatísticas de receita
 */
function getRevenueStats($conn, $userId, $period) {
    $months = getPeriodMonths($period);
    
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(i.data_inscricao, '%Y-%m') as month,
            SUM(e.preco) as revenue
        FROM inscricoes i 
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE e.id_organizador = ? 
        AND i.status = 'confirmada'
        AND e.evento_gratuito = 0
        AND i.data_inscricao >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(i.data_inscricao, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$userId, $months]);
    $results = $stmt->fetchAll();
    
    $data = [];
    $labels = [];
    $total = 0;
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M', strtotime("-$i months"));
        
        $labels[] = $monthLabel;
        
        $revenue = 0;
        foreach ($results as $result) {
            if ($result['month'] === $month) {
                $revenue = (float)$result['revenue'];
                break;
            }
        }
        $data[] = $revenue;
        $total += $revenue;
    }
    
    return [
        'labels' => $labels,
        'data' => $data,
        'total' => $total,
        'average' => $months > 0 ? $total / $months : 0
    ];
}

/**
 * Estatísticas de participantes
 */
function getParticipantStats($conn, $userId, $period) {
    $months = getPeriodMonths($period);
    
    // Novos participantes por mês
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(i.data_inscricao, '%Y-%m') as month,
            COUNT(DISTINCT i.id_participante) as new_participants
        FROM inscricoes i 
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        WHERE e.id_organizador = ? 
        AND i.status = 'confirmada'
        AND i.data_inscricao >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(i.data_inscricao, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$userId, $months]);
    $results = $stmt->fetchAll();
    
    // Participantes recorrentes
    $stmt = $conn->prepare("
        SELECT 
            i.id_participante,
            COUNT(*) as event_count,
            u.nome as participant_name
        FROM inscricoes i 
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        INNER JOIN usuarios u ON i.id_participante = u.id_usuario
        WHERE e.id_organizador = ? 
        AND i.status = 'confirmada'
        GROUP BY i.id_participante
        HAVING event_count > 1
        ORDER BY event_count DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recurring = $stmt->fetchAll();
    
    $data = [];
    $labels = [];
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M', strtotime("-$i months"));
        
        $labels[] = $monthLabel;
        
        $count = 0;
        foreach ($results as $result) {
            if ($result['month'] === $month) {
                $count = (int)$result['new_participants'];
                break;
            }
        }
        $data[] = $count;
    }
    
    return [
        'new_participants' => [
            'labels' => $labels,
            'data' => $data
        ],
        'recurring_participants' => $recurring
    ];
}

/**
 * Obter número de meses baseado no período
 */
function getPeriodMonths($period) {
    switch ($period) {
        case 'quarter':
            return 3;
        case 'year':
            return 12;
        case 'month':
        default:
            return 6;
    }
}
?>