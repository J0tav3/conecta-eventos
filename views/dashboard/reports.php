<?php
// ==========================================
// RELATÓRIOS DO ORGANIZADOR - COM DEBUG DA API
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
$baseUrl = 'https://conecta-eventos-production.up.railway.app';

// Período selecionado
$periodo = $_GET['periodo'] ?? 'month';

// Debug - mostrar informações da sessão
$debugInfo = [
    'user_id' => $userId,
    'user_name' => $userName,
    'user_type' => $_SESSION['user_type'] ?? 'não definido',
    'session_id' => session_id(),
    'cookies' => $_COOKIE
];

// Função melhorada para fazer requisições à API
function fetchAnalyticsData($action, $period = 'month', $limit = null) {
    global $baseUrl, $debugInfo;
    
    $params = [
        'action' => $action,
        'period' => $period
    ];
    
    if ($limit) {
        $params['limit'] = $limit;
    }
    
    $url = $baseUrl . '/api/analytics.php?' . http_build_query($params);
    
    // Preparar cookies para enviar
    $cookieHeader = '';
    if (isset($_COOKIE)) {
        $cookiePairs = [];
        foreach ($_COOKIE as $name => $value) {
            $cookiePairs[] = $name . '=' . $value;
        }
        $cookieHeader = implode('; ', $cookiePairs);
    }
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json',
                'Cookie: ' . $cookieHeader,
                'User-Agent: Mozilla/5.0 (compatible; Conecta-Eventos/1.0)'
            ],
            'timeout' => 30
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    // Debug da requisição
    $debugInfo['api_calls'][] = [
        'action' => $action,
        'url' => $url,
        'cookies_sent' => $cookieHeader,
        'response_received' => $response !== false,
        'response_length' => $response ? strlen($response) : 0,
        'response_preview' => $response ? substr($response, 0, 200) : 'ERRO'
    ];
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    return ($data && isset($data['success']) && $data['success']) ? $data['data'] : null;
}

// Buscar dados da API
$overview = fetchAnalyticsData('overview', $periodo);
$eventsByMonth = fetchAnalyticsData('events_by_month', $periodo);
$subscriptionsByMonth = fetchAnalyticsData('subscriptions_by_month', $periodo);
$eventsByCategory = fetchAnalyticsData('events_by_category', $periodo);
$eventsByStatus = fetchAnalyticsData('events_by_status', $periodo);
$topEvents = fetchAnalyticsData('top_events', $periodo, 5);
$revenueStats = fetchAnalyticsData('revenue_stats', $periodo);

// Verificar se conseguiu dados reais
$hasRealData = ($overview !== null);

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
    $eventsByCategory = ['labels' => ['Nenhum dado'], 'data' => [0]];
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

// Mapear períodos para labels
$periodLabels = [
    'month' => 'Últimos 6 meses',
    'quarter' => 'Último trimestre',
    'year' => 'Último ano'
];

// Mostrar debug se estiver em modo de desenvolvimento
$showDebug = isset($_GET['debug']) && $_GET['debug'] == '1';
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

        .api-status {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }

        .debug-panel {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            font-family: monospace;
            font-size: 0.85rem;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Status da API -->
    <div class="api-status">
        <?php if (!$hasRealData): ?>
            <div class="alert alert-warning alert-dismissible">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Dados Simulados</strong><br>
                Não foi possível conectar com a API.
                <br>
                <small>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['debug' => '1'])); ?>" class="text-decoration-none">
                        Ver detalhes do debug
                    </a>
                </small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php else: ?>
            <div class="alert alert-success alert-dismissible">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Dados Reais</strong><br>
                Conectado com a API com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
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
                        Período: <?php echo $periodLabels[$periodo] ?? 'Personalizado'; ?>
                        <?php if (!$hasRealData): ?>
                            | <span class="text-warning">⚠️ Dados simulados</span>
                        <?php endif; ?>
                    </small>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group">
                        <button class="btn btn-light" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                        <button class="btn btn-outline-light" onclick="testApiConnection()">
                            <i class="fas fa-plug me-2"></i>Testar API
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container pb-5">
        <!-- Debug Panel (apenas se solicitado) -->
        <?php if ($showDebug): ?>
            <div class="debug-panel">
                <h6><i class="fas fa-bug me-2"></i>Informações de Debug</h6>
                <strong>Sessão:</strong><br>
                <?php echo htmlspecialchars(print_r($debugInfo, true)); ?>
                
                <hr>
                
                <strong>Testes de API:</strong><br>
                <button class="btn btn-sm btn-primary me-2" onclick="testSpecificEndpoint('overview')">Testar Overview</button>
                <button class="btn btn-sm btn-primary me-2" onclick="testSpecificEndpoint('events_by_month')">Testar Eventos</button>
                <button class="btn btn-sm btn-primary" onclick="testAllEndpoints()">Testar Todos</button>
                
                <div id="apiTestResults" class="mt-3"></div>
            </div>
        <?php endif; ?>

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

        <!-- Primeira Experiência para Usuários sem Dados -->
        <?php if ($overview['total_events'] == 0): ?>
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

        <!-- Gráficos (apenas se houver dados) -->
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

        <!-- Top Eventos -->
        <div class="row">
            <div class="col-12">
                <div class="report-card">
                    <h5 class="mb-4">
                        <i class="fas fa-trophy me-2"></i>Seus Eventos Mais Populares
                    </h5>
                    <?php if (empty($topEvents)): ?>
                        <div class="no-data">
                            <i class="fas fa-trophy fa-3x mb-3"></i>
                            <p>Nenhum evento com inscrições ainda</p>
                            <a href="../events/create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Criar Evento
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
        </div>

        <?php endif; // fim do else para usuários com dados ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Função para testar conexão com API
        async function testApiConnection() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testando...';
            btn.disabled = true;
            
            try {
                const response = await fetch('<?php echo $baseUrl; ?>/api/analytics.php?action=overview&period=month');
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ API funcionando! Dados encontrados para o usuário logado.');
                    location.reload();
                } else {
                    alert('❌ API respondeu mas retornou erro: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                alert('❌ Erro ao conectar com a API: ' + error.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Função para testar endpoint específico
        async function testSpecificEndpoint(action) {
            const results = document.getElementById('apiTestResults');
            results.innerHTML += `<p>Testando ${action}...</p>`;
            
            try {
                const response = await fetch(`<?php echo $baseUrl; ?>/api/analytics.php?action=${action}&period=month`);
                const text = await response.text();
                
                results.innerHTML += `<div class="alert alert-info">
                    <strong>${action}:</strong><br>
                    Status: ${response.status}<br>
                    Response: <pre>${text.substring(0, 500)}${text.length > 500 ? '...' : ''}</pre>
                </div>`;
            } catch (error) {
                results.innerHTML += `<div class="alert alert-danger">
                    <strong>${action}:</strong> Erro - ${error.message}
                </div>`;
            }
        }

        // Função para testar todos os endpoints
        async function testAllEndpoints() {
            const endpoints = ['overview', 'events_by_month', 'subscriptions_by_month', 'events_by_category', 'events_by_status', 'top_events'];
            document.getElementById('apiTestResults').innerHTML = '<h6>Testando todos os endpoints...</h6>';
            
            for (const endpoint of endpoints) {
                await testSpecificEndpoint(endpoint);
            }
        }

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(alert => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
</body>
</html>