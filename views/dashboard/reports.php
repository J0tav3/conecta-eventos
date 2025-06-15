<?php
// ==========================================
// RELATÓRIOS SIMPLES - SEM DEPENDÊNCIA DE API
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

// Dados simulados baseados no organizador
// Em produção, estes viriam do banco de dados
$overview = [
    'total_events' => 4, // Baseado no que vi no log anterior
    'total_subscriptions' => 12,
    'unique_participants' => 8,
    'avg_rating' => 4.2,
    'total_revenue' => 350.00,
    'growth_rate' => 15.3
];

$eventsByMonth = [
    'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
    'data' => [0, 1, 1, 1, 0, 1]
];

$subscriptionsByMonth = [
    'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
    'data' => [0, 3, 2, 4, 0, 3]
];

$eventsByCategory = [
    'labels' => ['Tecnologia', 'Negócios', 'Arte'],
    'data' => [2, 1, 1]
];

$eventsByStatus = [
    'labels' => ['Publicados', 'Rascunhos'],
    'data' => [3, 1]
];

$topEvents = [
    [
        'titulo' => 'Workshop de PHP',
        'categoria' => 'Tecnologia',
        'data_inicio_formatada' => '15/06/2025',
        'total_inscricoes' => 5,
        'avg_rating' => 4.5,
        'preco_formatado' => 'R$ 50,00'
    ],
    [
        'titulo' => 'Meetup de Startups',
        'categoria' => 'Negócios',
        'data_inicio_formatada' => '10/06/2025',
        'total_inscricoes' => 4,
        'avg_rating' => 4.0,
        'preco_formatado' => 'Gratuito'
    ],
    [
        'titulo' => 'Arte Digital',
        'categoria' => 'Arte',
        'data_inicio_formatada' => '05/06/2025',
        'total_inscricoes' => 3,
        'avg_rating' => 4.2,
        'preco_formatado' => 'R$ 30,00'
    ]
];

$revenueStats = [
    'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
    'data' => [0, 150, 100, 200, 0, 150],
    'total' => 600,
    'average' => 100
];

$periodo = $_GET['periodo'] ?? 'month';
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

        .status-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>
<body>
    <!-- Status Badge -->
    <div class="status-badge">
        <div class="alert alert-info alert-dismissible">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Dados de Demonstração</strong><br>
            Relatórios funcionais em desenvolvimento
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
                        Período: <?php echo $periodLabels[$periodo]; ?> | 
                        Usuário: <?php echo htmlspecialchars($userName); ?> (ID: <?php echo $userId; ?>)
                    </small>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <button class="btn btn-light" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                        <a href="../events/list.php" class="btn btn-outline-light">
                            <i class="fas fa-list me-2"></i>Ver Eventos
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
                    <div class="metric-number metric-primary"><?php echo $overview['total_events']; ?></div>
                    <h6>Total de Eventos</h6>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>
                        +<?php echo $overview['growth_rate']; ?>% vs período anterior
                    </small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-success"><?php echo $overview['total_subscriptions']; ?></div>
                    <h6>Total Inscrições</h6>
                    <small class="text-success">
                        <i class="fas fa-users me-1"></i><?php echo $overview['unique_participants']; ?> participantes únicos
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
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Receita por Período -->
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

        <!-- Top Eventos -->
        <div class="row">
            <div class="col-lg-8">
                <div class="report-card">
                    <h5 class="mb-4">
                        <i class="fas fa-trophy me-2"></i>Seus Eventos Mais Populares
                    </h5>
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
                                                <small class="text-muted"><?php echo htmlspecialchars($evento['categoria']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo $evento['data_inicio_formatada']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $evento['total_inscricoes']; ?></span>
                                        </td>
                                        <td>
                                            <span class="text-warning">
                                                <?php echo $evento['avg_rating']; ?> 
                                                <i class="fas fa-star"></i>
                                            </span>
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
                </div>
            </div>
            
            <!-- Categorias e Informações -->
            <div class="col-lg-4">
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

                <div class="report-card">
                    <h6 class="mb-3">
                        <i class="fas fa-info-circle me-2"></i>Resumo
                    </h6>
                    <div class="mb-3">
                        <small class="text-muted d-block">Período analisado:</small>
                        <strong><?php echo $periodLabels[$periodo]; ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Taxa de ocupação:</small>
                        <strong>75% em média</strong>
                    </div>
                    
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
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-chart-line fa-lg text-success me-3 mt-1"></i>
                                <div>
                                    <h6>Performance Sólida</h6>
                                    <p class="mb-0 small text-muted">
                                        Você tem <?php echo $overview['total_events']; ?> eventos ativos com 
                                        <?php echo $overview['total_subscriptions']; ?> inscrições totais. 
                                        Excelente avaliação média de <?php echo $overview['avg_rating']; ?>!
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-trending-up fa-lg text-success me-3 mt-1"></i>
                                <div>
                                    <h6>Crescimento Constante</h6>
                                    <p class="mb-0 small text-muted">
                                        Seus eventos cresceram <?php echo $overview['growth_rate']; ?>% 
                                        comparado ao período anterior. Continue assim!
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-users fa-lg text-primary me-3 mt-1"></i>
                                <div>
                                    <h6>Engajamento Alto</h6>
                                    <p class="mb-0 small text-muted">
                                        Você tem <?php echo $overview['unique_participants']; ?> participantes únicos, 
                                        mostrando boa fidelização do público.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dados para os gráficos
            const eventsByMonth = <?php echo json_encode($eventsByMonth); ?>;
            const subscriptionsByMonth = <?php echo json_encode($subscriptionsByMonth); ?>;
            const eventsByStatus = <?php echo json_encode($eventsByStatus); ?>;
            const revenueStats = <?php echo json_encode($revenueStats); ?>;

            // Gráfico de Eventos e Inscrições
            const ctx1 = document.getElementById('eventosChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: eventsByMonth.labels,
                    datasets: [{
                        label: 'Eventos Criados',
                        data: eventsByMonth.data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Inscrições',
                        data: subscriptionsByMonth.data,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
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
                            beginAtZero: true
                        }
                    }
                }
            });

            // Gráfico de Status dos Eventos
            const ctx2 = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: eventsByStatus.labels,
                    datasets: [{
                        data: eventsByStatus.data,
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#dc3545',
                            '#6c757d'
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

            // Gráfico de Receita
            const ctx3 = document.getElementById('revenueChart').getContext('2d');
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
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    }
                }
            });

            // Animação das métricas
            const metricNumbers = document.querySelectorAll('.metric-number');
            metricNumbers.forEach((metric, index) => {
                setTimeout(() => {
                    metric.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        metric.style.transform = 'scale(1)';
                    }, 200);
                }, index * 100);
            });

            // Auto-hide alert
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, 3000);
        });
    </script>
</body>
</html>