<?php
// ========================================
// DASHBOARD DE ANALYTICS
// ========================================
// Local: views/analytics/dashboard.php
// ========================================

require_once '../../config/config.php';
require_once '../../includes/session.php';
require_once '../../controllers/EventController.php';

// Verificar se usuário está logado e é organizador
requireLogin();
if (!isOrganizer()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$title = "Analytics - " . SITE_NAME;
$eventController = new EventController();
$userId = getUserId();

// Simular dados para gráficos (substituir por dados reais da API)
$analyticsData = [
    'events_by_month' => [
        'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        'data' => [3, 5, 2, 8, 6, 4]
    ],
    'subscriptions_by_month' => [
        'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        'data' => [45, 78, 32, 98, 76, 54]
    ],
    'events_by_category' => [
        'labels' => ['Tecnologia', 'Negócios', 'Educação', 'Arte', 'Esporte'],
        'data' => [12, 8, 15, 6, 9]
    ],
    'events_by_status' => [
        'labels' => ['Publicados', 'Rascunhos', 'Cancelados', 'Finalizados'],
        'data' => [25, 5, 2, 18]
    ]
];

// Stats gerais
$stats = [
    'total_events' => 50,
    'total_subscriptions' => 1247,
    'total_participants' => 892,
    'avg_rating' => 4.3,
    'revenue' => 15750.00,
    'growth_rate' => 23.5
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            height: 100%;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stat-change {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <?php include '../../views/layouts/header.php'; ?>

    <div class="container my-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-chart-line me-2"></i>Dashboard de Analytics</h2>
                <p class="text-muted">Análise detalhada dos seus eventos e participantes</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-primary active" onclick="setPeriod('month')">Mês</button>
                    <button class="btn btn-outline-primary" onclick="setPeriod('quarter')">Trimestre</button>
                    <button class="btn btn-outline-primary" onclick="setPeriod('year')">Ano</button>
                </div>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_events']; ?></div>
                    <div>Total de Eventos</div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> +12% este mês
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_subscriptions']); ?></div>
                    <div>Inscrições</div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> +<?php echo $stats['growth_rate']; ?>%
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_participants']); ?></div>
                    <div>Participantes</div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> +18%
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                    <div>Avaliação Média</div>
                    <div class="stat-change">
                        <i class="fas fa-star"></i> Excelente
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number">R$ <?php echo number_format($stats['revenue'], 0, ',', '.'); ?></div>
                    <div>Receita</div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> +25%
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['growth_rate'], 1); ?>%</div>
                    <div>Crescimento</div>
                    <div class="stat-change">
                        <i class="fas fa-trending-up"></i> Mensal
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row">
            <!-- Eventos por Mês -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <h5 class="chart-title">
                        <i class="fas fa-calendar-alt me-2"></i>Eventos Criados por Mês
                    </h5>
                    <div class="chart-container">
                        <canvas id="eventsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Inscrições por Mês -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <h5 class="chart-title">
                        <i class="fas fa-user-plus me-2"></i>Inscrições por Mês
                    </h5>
                    <div class="chart-container">
                        <canvas id="subscriptionsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Eventos por Categoria -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <h5 class="chart-title">
                        <i class="fas fa-tags me-2"></i>Eventos por Categoria
                    </h5>
                    <div class="chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Status dos Eventos -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <h5 class="chart-title">
                        <i class="fas fa-chart-pie me-2"></i>Status dos Eventos
                    </h5>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Performance -->
        <div class="row">
            <div class="col-12">
                <div class="analytics-card">
                    <h5 class="chart-title">
                        <i class="fas fa-trophy me-2"></i>Top 5 Eventos Mais Populares
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Evento</th>
                                    <th>Categoria</th>
                                    <th>Inscritos</th>
                                    <th>Avaliação</th>
                                    <th>Receita</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <i class="fas fa-laptop-code"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Workshop de React</div>
                                                <small class="text-muted">Programação Avançada</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info">Tecnologia</span></td>
                                    <td><strong>156</strong> participantes</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">4.8</span>
                                            <div class="text-warning">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-success fw-bold">R$ 3.120,00</td>
                                    <td>15/03/2024</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Marketing Digital 2024</div>
                                                <small class="text-muted">Estratégias e Tendências</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-warning">Negócios</span></td>
                                    <td><strong>134</strong> participantes</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">4.6</span>
                                            <div class="text-warning">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="far fa-star"></i>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-success fw-bold">R$ 2.680,00</td>
                                    <td>22/03/2024</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <i class="fas fa-paint-brush"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold">Arte Contemporânea</div>
                                                <small class="text-muted">Exposição e Workshop</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-secondary">Arte</span></td>
                                    <td><strong>89</strong> participantes</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">4.9</span>
                                            <div class="text-warning">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted">Gratuito</td>
                                    <td>05/04/2024</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dados para os gráficos (em produção, vir de uma API)
        const analyticsData = <?php echo json_encode($analyticsData); ?>;

        // Configurações globais do Chart.js
        Chart.defaults.font.family = "'Segoe UI', 'Helvetica Neue', Arial, sans-serif";
        Chart.defaults.color = '#6c757d';

        // Gráfico de Eventos por Mês
        const eventsCtx = document.getElementById('eventsChart').getContext('2d');
        const eventsChart = new Chart(eventsCtx, {
            type: 'line',
            data: {
                labels: analyticsData.events_by_month.labels,
                datasets: [{
                    label: 'Eventos Criados',
                    data: analyticsData.events_by_month.data,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
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
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Gráfico de Inscrições por Mês
        const subscriptionsCtx = document.getElementById('subscriptionsChart').getContext('2d');
        const subscriptionsChart = new Chart(subscriptionsCtx, {
            type: 'bar',
            data: {
                labels: analyticsData.subscriptions_by_month.labels,
                datasets: [{
                    label: 'Inscrições',
                    data: analyticsData.subscriptions_by_month.data,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
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
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Gráfico de Eventos por Categoria (Doughnut)
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categoriesChart = new Chart(categoriesCtx, {
            type: 'doughnut',
            data: {
                labels: analyticsData.events_by_category.labels,
                datasets: [{
                    data: analyticsData.events_by_category.data,
                    backgroundColor: [
                        '#667eea',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6f42c1'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Gráfico de Status dos Eventos (Pie)
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: analyticsData.events_by_status.labels,
                datasets: [{
                    data: analyticsData.events_by_status.data,
                    backgroundColor: [
                        '#28a745',
                        '#6c757d',
                        '#dc3545',
                        '#343a40'
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
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Função para alterar período
        function setPeriod(period) {
            // Remover classe active de todos os botões
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Adicionar classe active ao botão clicado
            event.target.classList.add('active');
            
            // Aqui você faria uma requisição AJAX para obter novos dados
            console.log('Período selecionado:', period);
            
            // Simular atualização dos gráficos
            showToast(`Dados atualizados para o período: ${period}`, 'info');
        }

        // Sistema de notificações
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            `;
            
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 4000);
        }

        // Animação de entrada dos cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.analytics-card, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>