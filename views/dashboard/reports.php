<?php
// ==========================================
// RELATÓRIOS DO ORGANIZADOR - CONSULTA DIRETA
// Local: views/dashboard/reports.php
// ==========================================

session_start();

// Verificar se está logado e é organizador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
    header("Location: participant.php");
    exit;
}

// Incluir configurações e dependências
require_once '../../config/config.php';
require_once '../../includes/session.php';

// Verificar se as funções de sessão estão disponíveis
if (!function_exists('isLoggedIn') || !function_exists('isOrganizer')) {
    // Implementar verificações básicas se as funções não existirem
    function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    function isOrganizer() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'organizador';
    }
    
    function getUserId() {
        return $_SESSION['user_id'] ?? 0;
    }
}

$title = "Relatórios - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Organizador';
$userId = $_SESSION['user_id'] ?? 0;

// URLs
$dashboardUrl = 'organizer.php';
$homeUrl = '../../index.php';

// Período selecionado
$periodo = $_GET['periodo'] ?? 'month';

// Conectar ao banco
try {
    $database = new Database();
    $conn = $database->getConnection();
} catch (Exception $e) {
    die("Erro ao conectar com o banco: " . $e->getMessage());
}

// Função para obter número de meses baseado no período
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

// Buscar dados diretamente do banco
$months = getPeriodMonths($periodo);

// 1. Estatísticas gerais (overview)
$overview = [];

// Total de eventos
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM eventos WHERE id_organizador = ?");
$stmt->execute([$userId]);
$overview['total_events'] = (int) $stmt->fetch()['total'];

// Total de inscrições confirmadas
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM inscricoes i 
    INNER JOIN eventos e ON i.id_evento = e.id_evento 
    WHERE e.id_organizador = ? AND i.status = 'confirmada'
");
$stmt->execute([$userId]);
$overview['total_subscriptions'] = (int) $stmt->fetch()['total'];

// Participantes únicos
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT i.id_participante) as total 
    FROM inscricoes i 
    INNER JOIN eventos e ON i.id_evento = e.id_evento 
    WHERE e.id_organizador = ? AND i.status = 'confirmada'
");
$stmt->execute([$userId]);
$overview['unique_participants'] = (int) $stmt->fetch()['total'];

// Avaliação média
$stmt = $conn->prepare("
    SELECT AVG(i.avaliacao_evento) as avg_rating 
    FROM inscricoes i 
    INNER JOIN eventos e ON i.id_evento = e.id_evento 
    WHERE e.id_organizador = ? AND i.avaliacao_evento IS NOT NULL
");
$stmt->execute([$userId]);
$result = $stmt->fetch();
$overview['avg_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;

// Receita total (apenas eventos pagos)
$stmt = $conn->prepare("
    SELECT SUM(e.preco) as total_revenue 
    FROM inscricoes i 
    INNER JOIN eventos e ON i.id_evento = e.id_evento 
    WHERE e.id_organizador = ? AND i.status = 'confirmada' AND e.evento_gratuito = 0
");
$stmt->execute([$userId]);
$result = $stmt->fetch();
$overview['total_revenue'] = (float) ($result['total_revenue'] ?? 0);

// Taxa de crescimento (últimos vs anteriores)
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN e.data_criacao >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN 1 END) as recent,
        COUNT(CASE WHEN e.data_criacao >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                   AND e.data_criacao < DATE_SUB(NOW(), INTERVAL ? DAY) THEN 1 END) as previous
    FROM eventos e 
    WHERE e.id_organizador = ?
");
$periodDays = $months * 30;
$stmt->execute([$periodDays, $periodDays * 2, $periodDays, $userId]);
$growth = $stmt->fetch();

$overview['growth_rate'] = 0;
if ($growth['previous'] > 0) {
    $overview['growth_rate'] = round((($growth['recent'] - $growth['previous']) / $growth['previous']) * 100, 1);
}

// 2. Eventos por mês
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
$eventsByMonthData = $stmt->fetchAll();

$eventsByMonth = ['labels' => [], 'data' => []];
for ($i = $months - 1; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M/y', strtotime("-$i months"));
    
    $eventsByMonth['labels'][] = $monthLabel;
    
    $count = 0;
    foreach ($eventsByMonthData as $result) {
        if ($result['month'] === $month) {
            $count = (int) $result['count'];
            break;
        }
    }
    $eventsByMonth['data'][] = $count;
}

// 3. Inscrições por mês
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
$subscriptionsByMonthData = $stmt->fetchAll();

$subscriptionsByMonth = ['labels' => [], 'data' => []];
for ($i = $months - 1; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M/y', strtotime("-$i months"));
    
    $subscriptionsByMonth['labels'][] = $monthLabel;
    
    $count = 0;
    foreach ($subscriptionsByMonthData as $result) {
        if ($result['month'] === $month) {
            $count = (int) $result['count'];
            break;
        }
    }
    $subscriptionsByMonth['data'][] = $count;
}

// 4. Eventos por categoria
$stmt = $conn->prepare("
    SELECT 
        COALESCE(c.nome, 'Sem Categoria') as category_name,
        COUNT(*) as count
    FROM eventos e 
    LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
    WHERE e.id_organizador = ?
    GROUP BY e.id_categoria, c.nome
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$categoryData = $stmt->fetchAll();

$eventsByCategory = ['labels' => [], 'data' => []];
foreach ($categoryData as $result) {
    $eventsByCategory['labels'][] = $result['category_name'];
    $eventsByCategory['data'][] = (int) $result['count'];
}

// 5. Eventos por status
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
$statusData = $stmt->fetchAll();

$statusMap = [
    'publicado' => 'Publicados',
    'rascunho' => 'Rascunhos',
    'cancelado' => 'Cancelados',
    'finalizado' => 'Finalizados'
];

$eventsByStatus = ['labels' => [], 'data' => []];
foreach ($statusData as $result) {
    $eventsByStatus['labels'][] = $statusMap[$result['status']] ?? ucfirst($result['status']);
    $eventsByStatus['data'][] = (int) $result['count'];
}

// 6. Top eventos por popularidade
$stmt = $conn->prepare("
    SELECT 
        e.id_evento,
        e.titulo,
        e.data_inicio,
        e.preco,
        e.evento_gratuito,
        c.nome as categoria,
        COUNT(i.id_inscricao) as total_inscricoes,
        AVG(i.avaliacao_evento) as avg_rating
    FROM eventos e
    LEFT JOIN inscricoes i ON e.id_evento = i.id_evento AND i.status = 'confirmada'
    LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
    WHERE e.id_organizador = ?
    GROUP BY e.id_evento
    ORDER BY total_inscricoes DESC, avg_rating DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$topEvents = $stmt->fetchAll();

foreach ($topEvents as &$evento) {
    $evento['total_inscricoes'] = (int) $evento['total_inscricoes'];
    $evento['avg_rating'] = $evento['avg_rating'] ? round($evento['avg_rating'], 1) : 0;
    $evento['data_inicio_formatada'] = date('d/m/Y', strtotime($evento['data_inicio']));
    $evento['preco_formatado'] = $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.');
}

// 7. Estatísticas de receita
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
$revenueData = $stmt->fetchAll();

$revenueStats = ['labels' => [], 'data' => [], 'total' => 0, 'average' => 0];
$total = 0;

for ($i = $months - 1; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M/y', strtotime("-$i months"));
    
    $revenueStats['labels'][] = $monthLabel;
    
    $revenue = 0;
    foreach ($revenueData as $result) {
        if ($result['month'] === $month) {
            $revenue = (float) $result['revenue'];
            break;
        }
    }
    $revenueStats['data'][] = $revenue;
    $total += $revenue;
}

$revenueStats['total'] = $total;
$revenueStats['average'] = $months > 0 ? $total / $months : 0;

// Mapear períodos para labels
$periodLabels = [
    'month' => 'Últimos 6 meses',
    'quarter' => 'Último trimestre',
    'year' => 'Último ano'
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .report-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 4px solid;
        }
        
        .report-card.primary { border-left-color: #667eea; }
        .report-card.success { border-left-color: #28a745; }
        .report-card.warning { border-left-color: #ffc107; }
        .report-card.info { border-left-color: #17a2b8; }
        
        .metric-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
        }
        
        .metric-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .metric-primary { color: #667eea; }
        .metric-success { color: #28a745; }
        .metric-warning { color: #ffc107; }
        .metric-info { color: #17a2b8; }
        
        .chart-container {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            position: relative;
            height: 400px;
        }
        
        .table-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .period-selector {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .btn-period {
            border-radius: 2rem;
            padding: 0.5rem 1.5rem;
            margin: 0 0.25rem;
        }
        
        .btn-period.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .success-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>
<body>
    <!-- Success Badge -->
    <div class="success-badge">
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Dados Reais</strong><br>
            Seus relatórios personalizados!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>

    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Olá, <?php echo htmlspecialchars($userName); ?>!
                </span>
                <a class="nav-link" href="<?php echo $dashboardUrl; ?>">Dashboard</a>
                <a class="nav-link" href="../../logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo $dashboardUrl; ?>" class="text-decoration-none">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Relatórios</li>
            </ol>
        </nav>
    </div>

    <!-- Header da Página -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-chart-bar me-2"></i>Relatórios</h1>
                    <p class="mb-0 fs-5">Acompanhe o desempenho dos seus eventos</p>
                    <small class="opacity-75">
                        Período: <?php echo $periodLabels[$periodo] ?? 'Personalizado'; ?> | 
                        Dados em tempo real do seu organizador
                    </small>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <button class="btn btn-light" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                        <a href="../analytics/dashboard.php" class="btn btn-outline-light">
                            <i class="fas fa-chart-line me-2"></i>Analytics Avançado
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container pb-5">
        <!-- Seletor de Período -->
        <div class="period-selector">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Período de Análise:</h6>
                <div>
                    <a href="?periodo=month" class="btn btn-period <?php echo $periodo === 'month' ? 'active' : 'btn-outline-primary'; ?>">
                        6 Meses
                    </a>
                    <a href="?periodo=quarter" class="btn btn-period <?php echo $periodo === 'quarter' ? 'active' : 'btn-outline-primary'; ?>">
                        Trimestre
                    </a>
                    <a href="?periodo=year" class="btn btn-period <?php echo $periodo === 'year' ? 'active' : 'btn-outline-primary'; ?>">
                        Ano
                    </a>
                </div>
            </div>
        </div>

        <!-- Métricas Principais -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-primary"><?php echo number_format($overview['total_events']); ?></div>
                    <h6>Total de Eventos</h6>
                    <small class="<?php echo $overview['growth_rate'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <i class="fas fa-arrow-<?php echo $overview['growth_rate'] >= 0 ? 'up' : 'down'; ?> me-1"></i>
                        <?php echo abs($overview['growth_rate']); ?>% vs período anterior
                    </small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-success"><?php echo number_format($overview['total_subscriptions']); ?></div>
                    <h6>Total Inscrições</h6>
                    <small class="text-success">
                        <i class="fas fa-users me-1"></i><?php echo number_format($overview['unique_participants']); ?> participantes únicos
                    </small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-warning">R$ <?php echo number_format($overview['total_revenue'], 2, ',', '.'); ?></div>
                    <h6>Receita Total</h6>
                    <small class="text-info">
                        <i class="fas fa-chart-line me-1"></i>Média: R$ <?php echo number_format($revenueStats['average'], 2, ',', '.'); ?>/mês
                    </small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-info"><?php echo $overview['avg_rating']; ?></div>
                    <h6>Avaliação Média</h6>
                    <small class="text-warning">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $overview['avg_rating'] ? '' : 'text-muted'; ?>"></i>
                        <?php endfor; ?>
                    </small>
                </div>
            </div>
        </div>

        <?php if ($overview['total_events'] == 0): ?>
        <!-- Primeira Experiência para Usuários sem Dados -->
        <div class="row">
            <div class="col-12">
                <div class="report-card text-center">
                    <i class="fas fa-rocket fa-4x text-primary mb-4"></i>
                    <h3>Bem-vindo aos Relatórios!</h3>
                    <p class="mb-4">Você ainda não criou nenhum evento. Que tal começar criando seu primeiro evento para ver relatórios incríveis aqui?</p>
                    <a href="../events/create.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-plus me-2"></i>Criar Primeiro Evento
                    </a>
                    <a href="<?php echo $dashboardUrl; ?>" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5 class="mb-4">
                        <i class="fas fa-chart-line me-2"></i>Eventos e Inscrições por Período
                    </h5>
                    <canvas id="eventosChart"></canvas>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5 class="mb-4">
                        <i class="fas fa-chart-pie me-2"></i>Status dos Eventos
                    </h5>
                    <?php if (empty($eventsByStatus['data']) || array_sum($eventsByStatus['data']) == 0): ?>
                        <div class="no-data">
                            <i class="fas fa-chart-pie fa-3x mb-3"></i>
                            <p>Nenhum evento ainda</p>
                        </div>
                    <?php else: ?>
                        <canvas id="statusChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Receita por Período -->
        <?php if ($revenueStats['total'] > 0): ?>
        <div class="row">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="mb-4">
                        <i class="fas fa-dollar-sign me-2"></i>Receita por Período
                    </h5>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabelas de Dados -->
        <div class="row">
            <!-- Top Eventos -->
            <div class="col-lg-8">
                <div class="report-card">
                    <h5 class="mb-4">
                        <i class="fas fa-trophy me-2"></i>Seus Eventos Mais Populares
                    </h5>
                    <?php if (empty($topEvents)): ?>
                        <div class="no-data">
                            <i class="fas fa-trophy fa-3x mb-3"></i>
                            <p>Nenhum evento com inscrições ainda</p>
                        </div>
                    <?php else: ?>
                        <div class="table-card">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Evento</th>
                                        <th>Data</th>
                                        <th>Inscrições</th>
                                        <th>Avaliação</th>
                                        <th>Receita</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topEvents as $evento): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($evento['categoria'] ?? 'Sem categoria'); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <small><?php echo $evento['data_inicio_formatada']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $evento['total_inscricoes']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($evento['avg_rating'] > 0): ?>
                                                    <span class="text-warning">
                                                        <?php echo $evento['avg_rating']; ?> 
                                                        <i class="fas fa-star"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <small class="text-muted">Sem avaliações</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold">
                                                    <?php echo $evento['preco_formatado']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Categorias e Status -->
            <div class="col-lg-4">
                <?php if (!empty($eventsByCategory['data']) && array_sum($eventsByCategory['data']) > 0): ?>
                <div class="report-card mb-4">
                    <h6 class="mb-3">
                        <i class="fas fa-tags me-2"></i>Por Categoria
                    </h6>
                    <div class="table-card">
                        <table class="table table-sm">
                            <tbody>
                                <?php for ($i = 0; $i < count($eventsByCategory['labels']); $i++): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($eventsByCategory['labels'][$i]); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-primary"><?php echo $eventsByCategory['data'][$i]; ?></span>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <div class="report-card">
                    <h6 class="mb-3">
                        <i class="fas fa-info-circle me-2"></i>Resumo do Período
                    </h6>
                    <div class="mb-3">
                        <small class="text-muted d-block">Período analisado:</small>
                        <strong><?php echo $periodLabels[$periodo]; ?></strong>
                    </div>
                    
                    <?php if ($overview['unique_participants'] > 0): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Participantes únicos:</small>
                            <strong><?php echo number_format($overview['unique_participants']); ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($overview['total_revenue'] > 0): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Ticket médio:</small>
                            <strong>R$ <?php echo number_format($overview['total_revenue'] / max($overview['total_subscriptions'], 1), 2, ',', '.'); ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Relatório gerado em:</small>
                        <strong><?php echo date('d/m/Y H:i'); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insights -->
        <div class="row">
            <div class="col-12">
                <div class="report-card info">
                    <h5 class="mb-4">
                        <i class="fas fa-lightbulb me-2"></i>Insights dos Seus Eventos
                    </h5>
                    <div class="row">
                        <?php if ($overview['total_events'] > 0): ?>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-chart-line fa-lg text-success me-3 mt-1"></i>
                                    <div>
                                        <h6>Performance Geral</h6>
                                        <p class="mb-0 small text-muted">
                                            Você tem <?php echo $overview['total_events']; ?> eventos com 
                                            <?php echo $overview['total_subscriptions']; ?> inscrições totais.
                                            <?php if ($overview['avg_rating'] >= 4): ?>
                                                Excelente avaliação média de <?php echo $overview['avg_rating']; ?>!
                                            <?php elseif ($overview['avg_rating'] > 0): ?>
                                                Avaliação média de <?php echo $overview['avg_rating']; ?>.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($overview['growth_rate'] > 0): ?>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-trending-up fa-lg text-success me-3 mt-1"></i>
                                    <div>
                                        <h6>Crescimento Positivo</h6>
                                        <p class="mb-0 small text-muted">
                                            Seus eventos cresceram <?php echo $overview['growth_rate']; ?>% 
                                            comparado ao período anterior. Continue assim!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($overview['growth_rate'] < 0): ?>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-trending-down fa-lg text-warning me-3 mt-1"></i>
                                    <div>
                                        <h6>Oportunidade de Melhoria</h6>
                                        <p class="mb-0 small text-muted">
                                            Houve uma queda de <?php echo abs($overview['growth_rate']); ?>% 
                                            no período. Que tal criar mais eventos?
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($overview['total_revenue'] > 0): ?>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-dollar-sign fa-lg text-warning me-3 mt-1"></i>
                                    <div>
                                        <h6>Receita Gerada</h6>
                                        <p class="mb-0 small text-muted">
                                            Total de R$ <?php echo number_format($overview['total_revenue'], 2, ',', '.'); ?> 
                                            em eventos pagos. Média de R$ <?php echo number_format($revenueStats['average'], 2, ',', '.'); ?>/mês.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (count($eventsByCategory['labels']) > 1): ?>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-tags fa-lg text-info me-3 mt-1"></i>
                                    <div>
                                        <h6>Diversidade de Temas</h6>
                                        <p class="mb-0 small text-muted">
                                            Você organiza eventos em <?php echo count($eventsByCategory['labels']); ?> categorias diferentes. 
                                            Categoria mais popular: <strong><?php echo $eventsByCategory['labels'][0]; ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($overview['unique_participants'] > 0 && $overview['total_subscriptions'] > 0): ?>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-users fa-lg text-primary me-3 mt-1"></i>
                                    <div>
                                        <h6>Engajamento</h6>
                                        <p class="mb-0 small text-muted">
                                            <?php 
                                            $repeatedParticipants = round(($overview['total_subscriptions'] - $overview['unique_participants']) / max($overview['unique_participants'], 1), 1);
                                            if ($repeatedParticipants > 0.5): ?>
                                                Ótimo! Você tem participantes recorrentes, com média de <?php echo $repeatedParticipants; ?> inscrições por pessoa.
                                            <?php else: ?>
                                                Foque em fidelizar participantes para aumentar o engajamento.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; // fim do else para usuários com dados ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dados para os gráficos vindos do PHP
            const eventsByMonth = <?php echo json_encode($eventsByMonth); ?>;
            const subscriptionsByMonth = <?php echo json_encode($subscriptionsByMonth); ?>;
            const eventsByStatus = <?php echo json_encode($eventsByStatus); ?>;
            const revenueStats = <?php echo json_encode($revenueStats); ?>;

            // Gráfico de Eventos e Inscrições
            const eventsChartElement = document.getElementById('eventosChart');
            if (eventsChartElement) {
                const ctx1 = eventsChartElement.getContext('2d');
                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: eventsByMonth.labels,
                        datasets: [{
                            label: 'Eventos Criados',
                            data: eventsByMonth.data,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y'
                        }, {
                            label: 'Inscrições',
                            data: subscriptionsByMonth.data,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Eventos'
                                },
                                beginAtZero: true
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Inscrições'
                                },
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });
            }

            // Gráfico de Status dos Eventos
            const statusChartElement = document.getElementById('statusChart');
            if (statusChartElement && eventsByStatus.data.length > 0 && eventsByStatus.data.some(val => val > 0)) {
                const ctx2 = statusChartElement.getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: eventsByStatus.labels,
                        datasets: [{
                            data: eventsByStatus.data,
                            backgroundColor: [
                                '#28a745', // Publicados - verde
                                '#ffc107', // Rascunhos - amarelo
                                '#dc3545', // Cancelados - vermelho
                                '#6c757d'  // Finalizados - cinza
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Gráfico de Receita
            const revenueChartElement = document.getElementById('revenueChart');
            if (revenueChartElement && revenueStats.data && revenueStats.data.some(val => val > 0)) {
                const ctx3 = revenueChartElement.getContext('2d');
                new Chart(ctx3, {
                    type: 'bar',
                    data: {
                        labels: revenueStats.labels,
                        datasets: [{
                            label: 'Receita (R$)',
                            data: revenueStats.data,
                            backgroundColor: 'rgba(255, 193, 7, 0.8)',
                            borderColor: '#ffc107',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'R$ ' + value.toLocaleString('pt-BR', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Animação das métricas
            const metricNumbers = document.querySelectorAll('.metric-number');
            metricNumbers.forEach(metric => {
                const text = metric.textContent;
                const number = parseFloat(text.replace(/[^\d.,]/g, '').replace(',', '.'));
                
                if (!isNaN(number) && number > 0) {
                    let current = 0;
                    const increment = number / 50;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= number) {
                            metric.textContent = text;
                            clearInterval(timer);
                        } else {
                            if (text.includes('R)) {
                                metric.textContent = 'R$ ' + current.toLocaleString('pt-BR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            } else if (text.includes('.')) {
                                metric.textContent = current.toFixed(1);
                            } else {
                                metric.textContent = Math.floor(current).toLocaleString('pt-BR');
                            }
                        }
                    }, 30);
                }
            });

            // Auto-hide alerts
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(alert => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                });
            }, 4000);
        });
    </script>
</body>
</html>