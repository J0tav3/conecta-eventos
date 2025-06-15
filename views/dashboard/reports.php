<?php
// ==========================================
// RELATÓRIOS DO ORGANIZADOR - VERSÃO CORRIGIDA
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

$title = "Relatórios - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Organizador';
$userId = $_SESSION['user_id'] ?? 0;

// URLs
$dashboardUrl = 'organizer.php';
$homeUrl = '../../index.php';
$apiUrl = 'https://conecta-eventos-production.up.railway.app/api/analytics.php';

// Período selecionado
$periodo = $_GET['periodo'] ?? 'month'; // month, quarter, year

// Função para fazer requisições à API
function fetchAnalyticsData($action, $period = 'month', $limit = null) {
    global $apiUrl;
    
    $params = [
        'action' => $action,
        'period' => $period
    ];
    
    if ($limit) {
        $params['limit'] = $limit;
    }
    
    $url = $apiUrl . '?' . http_build_query($params);
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json',
                'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
            ]
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['success'] ? $data['data'] : null;
}

// Buscar dados da API
$overview = fetchAnalyticsData('overview', $periodo);
$eventsByMonth = fetchAnalyticsData('events_by_month', $periodo);
$subscriptionsByMonth = fetchAnalyticsData('subscriptions_by_month', $periodo);
$eventsByCategory = fetchAnalyticsData('events_by_category', $periodo);
$eventsByStatus = fetchAnalyticsData('events_by_status', $periodo);
$topEvents = fetchAnalyticsData('top_events', $periodo, 5);
$revenueStats = fetchAnalyticsData('revenue_stats', $periodo);
$participantStats = fetchAnalyticsData('participant_stats', $periodo);

// Dados padrão caso a API não responda
if (!$overview) {
    $overview = [
        'total_events' => 0,
        'total_subscriptions' => 0,
        'unique_participants' => 0,
        'avg_rating' => 0,
        'total_revenue' => 0,
        'growth_rate' => 0
    ];
}

if (!$eventsByMonth) {
    $eventsByMonth = ['labels' => [], 'data' => []];
}

if (!$subscriptionsByMonth) {
    $subscriptionsByMonth = ['labels' => [], 'data' => []];
}

if (!$eventsByCategory) {
    $eventsByCategory = ['labels' => ['Nenhuma categoria'], 'data' => [0]];
}

if (!$eventsByStatus) {
    $eventsByStatus = ['labels' => ['Sem dados'], 'data' => [0]];
}

if (!$topEvents) {
    $topEvents = [];
}

if (!$revenueStats) {
    $revenueStats = [
        'labels' => [],
        'data' => [],
        'total' => 0,
        'average' => 0
    ];
}

// Calcular crescimento dos participantes (simulado se não houver dados suficientes)
$participantGrowth = 0;
if ($participantStats && isset($participantStats['new_participants']['data'])) {
    $data = $participantStats['new_participants']['data'];
    if (count($data) >= 2) {
        $current = array_slice($data, -3); // Últimos 3 meses
        $previous = array_slice($data, -6, 3); // 3 meses anteriores
        
        $currentSum = array_sum($current);
        $previousSum = array_sum($previous);
        
        if ($previousSum > 0) {
            $participantGrowth = round((($currentSum - $previousSum) / $previousSum) * 100, 1);
        }
    }
}

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
        .report-card.danger { border-left-color: #dc3545; }
        
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
        }
        
        .table-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .table-card .table {
            margin-bottom: 0;
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

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
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
                    <small class="opacity-75">Período: <?php echo $periodLabels[$periodo] ?? 'Personalizado'; ?></small>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <button class="btn btn-light" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                        <button class="btn btn-outline-light" onclick="exportData()">
                            <i class="fas fa-download me-2"></i>Exportar
                        </button>
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

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2">Carregando dados...</p>
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
                    <div class="metric-number metric-info"><?php echo number_format($overview['avg_rating'], 1); ?></div>
                    <h6>Avaliação Média</h6>
                    <small class="text-warning">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $overview['avg_rating'] ? '' : 'text-muted'; ?>"></i>
                        <?php endfor; ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5 class="mb-4">
                        <i class="fas fa-chart-line me-2"></i>Eventos e Inscrições por Período
                    </h5>
                    <?php if (empty($eventsByMonth['data']) && empty($subscriptionsByMonth['data'])): ?>
                        <div class="no-data">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <p>Nenhum dado disponível para o período selecionado</p>
                        </div>
                    <?php else: ?>
                        <canvas id="eventosChart" width="400" height="200"></canvas>
                    <?php endif; ?>
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
                            <p>Nenhum evento criado ainda</p>
                        </div>
                    <?php else: ?>
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Receita por Período -->
        <?php if (!empty($revenueStats['data']) && array_sum($revenueStats['data']) > 0): ?>
        <div class="row">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="mb-4">
                        <i class="fas fa-dollar-sign me-2"></i>Receita por Período
                    </h5>
                    <canvas id="revenueChart" width="400" height="200"></canvas>
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
                        <i class="fas fa-trophy me-2"></i>Top 5 Eventos Mais Populares
                    </h5>
                    <?php if (empty($topEvents)): ?>
                        <div class="no-data">
                            <i class="fas fa-trophy fa-3x mb-3"></i>
                            <p>Nenhum evento com inscrições ainda</p>
                            <a href="../events/create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Criar Primeiro Evento
                            </a>
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
                                                <small><?php echo $evento['data_inicio_formatada'] ?? date('d/m/Y', strtotime($evento['data_inicio'])); ?></small>
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
                                                    <?php echo $evento['preco_formatado'] ?? ($evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.')); ?>
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
            
            <!-- Categorias -->
            <div class="col-lg-4">
                <div class="report-card">
                    <h5 class="mb-4">
                        <i class="fas fa-tags me-2"></i>Eventos por Categoria
                    </h5>
                    <?php if (empty($eventsByCategory['data']) || array_sum($eventsByCategory['data']) == 0): ?>
                        <div class="no-data">
                            <i class="fas fa-tags fa-2x mb-2"></i>
                            <p class="small">Nenhuma categoria definida</p>
                        </div>
                    <?php else: ?>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Insights e Recomendações -->
        <div class="row">
            <div class="col-lg-8">
                <div class="report-card info">
                    <h5 class="mb-4">
                        <i class="fas fa-lightbulb me-2"></i>Insights e Recomendações
                    </h5>
                    <div class="row">
                        <?php if ($overview['total_events'] > 0): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-chart-line fa-lg text-success me-3 mt-1"></i>
                                    <div>
                                        <h6>Performance Geral</h6>
                                        <p class="mb-0 small text-muted">
                                            Você tem <?php echo $overview['total_events']; ?> eventos com 
                                            <?php echo $overview['total_subscriptions']; ?> inscrições totais.
                                            <?php if ($overview['avg_rating'] >= 4): ?>
                                                Excelente avaliação média!
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($overview['growth_rate'] > 0): ?>
                            <div class="col-md-6 mb-3">
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
                        <?php endif; ?>
                        
                        <?php if ($overview['total_revenue'] > 0): ?>
                            <div class="col-md-6 mb-3">
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
                        
                        <?php if ($overview['total_events'] == 0): ?>
                            <div class="col-12">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-rocket fa-lg text-primary me-3 mt-1"></i>
                                    <div>
                                        <h6>Comece Agora!</h6>
                                        <p class="mb-3 small text-muted">
                                            Você ainda não criou nenhum evento. Que tal começar criando seu primeiro evento?
                                        </p>
                                        <a href="../events/create.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-2"></i>Criar Primeiro Evento
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="report-card success">
                    <h5 class="mb-4">
                        <i class="fas fa-info-circle me-2"></i>Informações
                    </h5>
                    <div class="mb-3">
                        <small class="text-muted d-block">Último período analisado:</small>
                        <strong><?php echo $periodLabels[$periodo] ?? 'Personalizado'; ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Participantes únicos:</small>
                        <strong><?php echo number_format($overview['unique_participants']); ?></strong>
                    </div>
                    
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dados para os gráficos vindos do PHP
            const eventsByMonth = <?php echo json_encode($eventsByMonth); ?>;
            const subscriptionsByMonth = <?php echo json_encode($subscriptionsByMonth); ?>;
            const eventsByStatus = <?php echo json_encode($eventsByStatus); ?>;
            const revenueStats = <?php echo json_encode($revenueStats); ?>;

            // Gráfico de Eventos e Inscrições se houver dados
            const eventsChartElement = document.getElementById('eventosChart');
            if (eventsChartElement && (eventsByMonth.data.length > 0 || subscriptionsByMonth.data.length > 0)) {
                const ctx1 = eventsChartElement.getContext('2d');
                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: eventsByMonth.labels || subscriptionsByMonth.labels,
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