<?php
// ==========================================
// DASHBOARD DO ORGANIZADOR
// Local: views/dashboard/organizer.php
// ==========================================

session_start();

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

// Verificar se é organizador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
    header("Location: participant.php");
    exit;
}

$title = "Dashboard - Organizador";
$userName = $_SESSION['user_name'] ?? 'Organizador';

// URLs
$homeUrl = '../../index.php';
$logoutUrl = '../../logout.php';

// Dados de exemplo para o dashboard
$stats = [
    'total_eventos' => 12,
    'eventos_ativos' => 8,
    'total_participantes' => 245,
    'eventos_mes' => 3
];

$eventos_recentes = [
    [
        'id' => 1,
        'titulo' => 'Workshop de Desenvolvimento Web',
        'data' => '2024-06-15',
        'participantes' => 45,
        'status' => 'ativo',
        'categoria' => 'Tecnologia'
    ],
    [
        'id' => 2,
        'titulo' => 'Palestra sobre IA',
        'data' => '2024-06-20',
        'participantes' => 32,
        'status' => 'ativo',
        'categoria' => 'Tecnologia'
    ],
    [
        'id' => 3,
        'titulo' => 'Meetup de Empreendedorismo',
        'data' => '2024-06-25',
        'participantes' => 28,
        'status' => 'rascunho',
        'categoria' => 'Negócios'
    ]
];

$atividades_recentes = [
    ['acao' => 'Nova inscrição no Workshop de Desenvolvimento Web', 'tempo' => '2 horas atrás'],
    ['acao' => 'Evento "Palestra sobre IA" foi publicado', 'tempo' => '1 dia atrás'],
    ['acao' => '5 novas inscrições no Meetup de Empreendedorismo', 'tempo' => '2 dias atrás'],
    ['acao' => 'Evento "Workshop React" foi criado', 'tempo' => '3 dias atrás']
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
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.info { border-left-color: var(--info-color); }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .event-card {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
        }

        .event-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-ativo { background-color: #d4edda; color: #155724; }
        .status-rascunho { background-color: #fff3cd; color: #856404; }
        .status-cancelado { background-color: #f8d7da; color: #721c24; }

        .activity-item {
            padding: 0.75rem;
            border-left: 3px solid var(--primary-color);
            background: white;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 0.25rem 0;
        }

        .sidebar-nav a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 0.75rem 1rem;
            display: block;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <h4 class="mb-0">
                <i class="fas fa-calendar-check me-2"></i>
                Conecta Eventos
            </h4>
            <small class="opacity-75">Painel do Organizador</small>
        </div>

        <hr class="text-white-50">

        <div class="px-3 mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-circle fa-2x me-2"></i>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($userName); ?></div>
                    <small class="opacity-75">Organizador</small>
                </div>
            </div>
        </div>

        <hr class="text-white-50">

        <nav>
            <ul class="sidebar-nav px-3">
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                <li><a href="#"><i class="fas fa-calendar-plus me-2"></i>Criar Evento</a></li>
                <li><a href="#"><i class="fas fa-list me-2"></i>Meus Eventos</a></li>
                <li><a href="#"><i class="fas fa-users me-2"></i>Participantes</a></li>
                <li><a href="#"><i class="fas fa-chart-bar me-2"></i>Relatórios</a></li>
                <li><a href="#"><i class="fas fa-cog me-2"></i>Configurações</a></li>
            </ul>
        </nav>

        <hr class="text-white-50">

        <div class="px-3 mt-auto">
            <a href="<?php echo $homeUrl; ?>" class="sidebar-nav-link text-white-50">
                <i class="fas fa-home me-2"></i>Página Inicial
            </a>
            <a href="<?php echo $logoutUrl; ?>" class="sidebar-nav-link text-white-50">
                <i class="fas fa-sign-out-alt me-2"></i>Sair
            </a>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Dashboard</h2>
                <p class="text-muted mb-0">Bem-vindo de volta, <?php echo htmlspecialchars($userName); ?>!</p>
            </div>
            <div>
                <button class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Novo Evento
                </button>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-number"><?php echo $stats['total_eventos']; ?></div>
                            <div class="text-muted">Total de Eventos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-number"><?php echo $stats['eventos_ativos']; ?></div>
                            <div class="text-muted">Eventos Ativos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-play-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-number"><?php echo $stats['total_participantes']; ?></div>
                            <div class="text-muted">Total Participantes</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-number"><?php echo $stats['eventos_mes']; ?></div>
                            <div class="text-muted">Eventos este Mês</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-month fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Eventos Recentes -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Eventos Recentes
                        </h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($eventos_recentes as $evento): ?>
                            <div class="event-card p-3 mb-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-tag me-1"></i><?php echo $evento['categoria']; ?>
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($evento['data'])); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo $evento['participantes']; ?> inscritos
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="status-badge status-<?php echo $evento['status']; ?>">
                                            <?php echo ucfirst($evento['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Atividades Recentes -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bell me-2"></i>Atividades Recentes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($atividades_recentes as $atividade): ?>
                            <div class="activity-item">
                                <div class="fw-bold mb-1"><?php echo htmlspecialchars($atividade['acao']); ?></div>
                                <small class="text-muted"><?php echo $atividade['tempo']; ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Ações Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <button class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Criar Evento
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button class="btn btn-success w-100">
                                    <i class="fas fa-bullhorn me-2"></i>Promover Evento
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button class="btn btn-info w-100">
                                    <i class="fas fa-chart-line me-2"></i>Ver Relatórios
                                </button>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button class="btn btn-warning w-100">
                                    <i class="fas fa-cog me-2"></i>Configurações
                                </button>
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
            // Animação das estatísticas
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(current);
                    }
                }, 30);
            });

            // Hover effects nos cards
            const cards = document.querySelectorAll('.stat-card, .event-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>