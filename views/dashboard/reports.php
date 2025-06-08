<?php
// ==========================================
// RELATÓRIOS DO ORGANIZADOR
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

// URLs
$dashboardUrl = 'organizer.php';
$homeUrl = '../../index.php';

// Dados de exemplo para relatórios
$periodo = $_GET['periodo'] ?? '30'; // 7, 30, 90, 365 dias

// Dados de eventos por período
$eventos_periodo = [
    'total' => 12,
    'publicados' => 8,
    'rascunhos' => 3,
    'cancelados' => 1
];

// Dados de participantes
$participantes_dados = [
    'total' => 245,
    'novos_mes' => 56,
    'taxa_conversao' => 78.5,
    'participantes_ativos' => 189
];

// Dados financeiros
$financeiro = [
    'receita_total' => 12580.50,
    'receita_mes' => 3200.00,
    'eventos_pagos' => 5,
    'ticket_medio' => 85.30
];

// Dados para gráficos (simulados)
$eventos_mes = [
    ['mes' => 'Jan', 'eventos' => 2, 'participantes' => 45],
    ['mes' => 'Feb', 'eventos' => 3, 'participantes' => 67],
    ['mes' => 'Mar', 'eventos' => 1, 'participantes' => 23],
    ['mes' => 'Apr', 'eventos' => 4, 'participantes' => 89],
    ['mes' => 'May', 'eventos' => 2, 'participantes' => 41],
    ['mes' => 'Jun', 'eventos' => 3, 'participantes' => 76]
];

$categorias_populares = [
    ['nome' => 'Tecnologia', 'eventos' => 5, 'participantes' => 156],
    ['nome' => 'Negócios', 'eventos' => 3, 'participantes' => 89],
    ['nome' => 'Marketing', 'eventos' => 2, 'participantes' => 45],
    ['nome' => 'Design', 'eventos' => 2, 'participantes' => 67]
];

$cidades_ativas = [
    ['cidade' => 'São Paulo', 'eventos' => 6, 'participantes' => 178],
    ['cidade' => 'Rio de Janeiro', 'eventos' => 3, 'participantes' => 89],
    ['cidade' => 'Belo Horizonte', 'eventos' => 2, 'participantes' => 56],
    ['cidade' => 'Porto Alegre', 'eventos' => 1, 'participantes' => 34]
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
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Imprimir Relatório
                    </button>
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
                    <a href="?periodo=7" class="btn btn-period <?php echo $periodo === '7' ? 'active' : 'btn-outline-primary'; ?>">
                        Últimos 7 dias
                    </a>
                    <a href="?periodo=30" class="btn btn-period <?php echo $periodo === '30' ? 'active' : 'btn-outline-primary'; ?>">
                        Últimos 30 dias
                    </a>
                    <a href="?periodo=90" class="btn btn-period <?php echo $periodo === '90' ? 'active' : 'btn-outline-primary'; ?>">
                        Últimos 90 dias
                    </a>
                    <a href="?periodo=365" class="btn btn-period <?php echo $periodo === '365' ? 'active' : 'btn-outline-primary'; ?>">
                        Último ano
                    </a>
                </div>
            </div>
        </div>

        <!-- Métricas Principais -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-primary"><?php echo $eventos_periodo['total']; ?></div>
                    <h6>Total de Eventos</h6>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>+15% vs período anterior
                    </small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-success"><?php echo $participantes_dados['total']; ?></div>
                    <h6>Total Participantes</h6>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>+23% vs período anterior
                    </small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-warning">R$ <?php echo number_format($financeiro['receita_total'], 2, ',', '.'); ?></div>
                    <h6>Receita Total</h6>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>+8% vs período anterior
                    </small>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number metric-info"><?php echo $participantes_dados['taxa_conversao']; ?>%</div>
                    <h6>Taxa de Conversão</h6>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>+5% vs período anterior
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <div class="col-lg-8">
                <div class="chart-container">
                    <h5 class="mb-4">
                        <i class="fas fa-chart-line me-2"></i>Eventos e Participantes por Mês
                    </h5>
                    <canvas id="eventosChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="chart-container">
                    <h5 class="mb-4">
                        <i class="fas fa-chart-pie me-2"></i>Status dos Eventos
                    </h5>
                    <canvas id="statusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabelas de Dados -->
        <div class="row">
            <div class="col-lg-6">
                <div class="report-card">
                    <h5 class="mb-4">
                        <i class="fas fa-tags me-2"></i>Categorias Mais Populares
                    </h5>
                    <div class="table-card">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Categoria</th>
                                    <th>Eventos</th>
                                    <th>Participantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias_populares as $categoria): ?>
                                    <tr>
                                        <td><?php echo $categoria['nome']; ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $categoria['eventos']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $categoria['participantes']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="report-card">
                    <h5 class="mb-4">
                        <i class="fas fa-map-marker-alt me-2"></i>Cidades com Mais Eventos
                    </h5>
                    <div class="table-card">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Cidade</th>
                                    <th>Eventos</th>
                                    <th>Participantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cidades_ativas as $cidade): ?>
                                    <tr>
                                        <td><?php echo $cidade['cidade']; ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $cidade['eventos']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?php echo $cidade['participantes']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-chart-line fa-lg text-success me-3 mt-1"></i>
                                <div>
                                    <h6>Crescimento Positivo</h6>
                                    <p class="mb-0 small text-muted">
                                        Seus eventos têm mostrado crescimento consistente. 
                                        Continue focando em eventos de Tecnologia.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-map-marker-alt fa-lg text-warning me-3 mt-1"></i>
                                <div>
                                    <h6>Expandir Localização</h6>
                                    <p class="mb-0 small text-muted">
                                        Considere criar eventos em Florianópolis e Recife 
                                        para aumentar seu alcance.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-clock fa-lg text-info me-3 mt-1"></i>
                                <div>
                                    <h6>Melhor Horário</h6>
                                    <p class="mb-0 small text-muted">
                                        Eventos no período da tarde (14h-18h) têm 
                                        maior taxa de participação.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-dollar-sign fa-lg text-success me-3 mt-1"></i>
                                <div>
                                    <h6>Precificação</h6>
                                    <p class="mb-0 small text-muted">
                                        Eventos na faixa de R$ 50-100 têm melhor 
                                        relação custo-benefício para participantes.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="report-card success">
                    <h5 class="mb-4">
                        <i class="fas fa-trophy me-2"></i>Conquistas
                    </h5>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-medal text-warning fa-lg me-3"></i>
                            <div>
                                <h6 class="mb-0">100+ Participantes</h6>
                                <small class="text-muted">Alcançou 245 participantes</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-star text-warning fa-lg me-3"></i>
                            <div>
                                <h6 class="mb-0">Organizador Ativo</h6>
                                <small class="text-muted">12 eventos criados</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-heart text-danger fa-lg me-3"></i>
                            <div>
                                <h6 class="mb-0">Bem Avaliado</h6>
                                <small class="text-muted">4.8/5 de avaliação média</small>
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
            // Gráfico de Eventos e Participantes
            const ctx1 = document.getElementById('eventosChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($eventos_mes, 'mes')); ?>,
                    datasets: [{
                        label: 'Eventos',
                        data: <?php echo json_encode(array_column($eventos_mes, 'eventos')); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    }, {
                        label: 'Participantes',
                        data: <?php echo json_encode(array_column($eventos_mes, 'participantes')); ?>,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
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
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Participantes'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });

            // Gráfico de Status dos Eventos
            const ctx2 = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Publicados', 'Rascunhos', 'Cancelados'],
                    datasets: [{
                        data: [
                            <?php echo $eventos_periodo['publicados']; ?>,
                            <?php echo $eventos_periodo['rascunhos']; ?>,
                            <?php echo $eventos_periodo['cancelados']; ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Animação das métricas
            const metricNumbers = document.querySelectorAll('.metric-number');
            metricNumbers.forEach(metric => {
                const text = metric.textContent;
                const number = parseFloat(text.replace(/[^\d.,]/g, '').replace(',', '.'));
                
                if (!isNaN(number)) {
                    let current = 0;
                    const increment = number / 50;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= number) {
                            metric.textContent = text;
                            clearInterval(timer);
                        } else {
                            if (text.includes('R$')) {
                                metric.textContent = 'R$ ' + current.toLocaleString('pt-BR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            } else if (text.includes('%')) {
                                metric.textContent = current.toFixed(1) + '%';
                            } else {
                                metric.textContent = Math.floor(current);
                            }
                        }
                    }, 30);
                }
            });
        });
    </script>
</body>
</html>