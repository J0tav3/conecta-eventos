<?php
// ==========================================
// DASHBOARD DO PARTICIPANTE - VERSÃO ATUALIZADA
// Local: views/dashboard/participant.php
// ==========================================

session_start();

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$title = "Meu Painel - Participante";
$userName = $_SESSION['user_name'] ?? 'Participante';

// URLs
$homeUrl = '../../index.php';
$logoutUrl = '../../logout.php';

// Dados de exemplo para o dashboard
$stats = [
    'eventos_inscritos' => 5,
    'eventos_participados' => 12,
    'proximos_eventos' => 3,
    'eventos_favoritos' => 8
];

$proximos_eventos = [
    [
        'id' => 1,
        'titulo' => 'Workshop de Desenvolvimento Web',
        'data' => '2024-06-15',
        'horario' => '14:00',
        'local' => 'São Paulo',
        'categoria' => 'Tecnologia',
        'organizador' => 'Tech Academy'
    ],
    [
        'id' => 2,
        'titulo' => 'Palestra sobre IA',
        'data' => '2024-06-20',
        'horario' => '19:00',
        'local' => 'Rio de Janeiro',
        'categoria' => 'Tecnologia',
        'organizador' => 'AI Institute'
    ],
    [
        'id' => 3,
        'titulo' => 'Meetup de Empreendedorismo',
        'data' => '2024-06-25',
        'horario' => '18:30',
        'local' => 'Belo Horizonte',
        'categoria' => 'Negócios',
        'organizador' => 'StartupBH'
    ]
];

$eventos_recomendados = [
    [
        'id' => 4,
        'titulo' => 'Curso de Design UX/UI',
        'data' => '2024-07-01',
        'categoria' => 'Design',
        'preco' => 0,
        'participantes' => 45
    ],
    [
        'id' => 5,
        'titulo' => 'Hackathon de Inovação',
        'data' => '2024-07-05',
        'categoria' => 'Tecnologia',
        'preco' => 25.00,
        'participantes' => 120
    ]
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
            background: linear-gradient(135deg, var(--success-color) 0%, var(--info-color) 100%);
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
            color: var(--success-color);
        }

        .event-card {
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
            transition: all 0.2s;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .event-card:hover {
            border-color: var(--success-color);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
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

        .badge-category {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
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
            <small class="opacity-75">Painel do Participante</small>
        </div>

        <hr class="text-white-50">

        <div class="px-3 mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-circle fa-2x me-2"></i>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($userName); ?></div>
                    <small class="opacity-75">Participante</small>
                </div>
            </div>
        </div>

        <hr class="text-white-50">

        <nav>
            <ul class="sidebar-nav px-3">
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt me-2"></i>Meu Painel</a></li>
                <li><a href="<?php echo $homeUrl; ?>"><i class="fas fa-search me-2"></i>Explorar Eventos</a></li>
                <li><a href="subscriptions.php"><i class="fas fa-calendar me-2"></i>Meus Eventos</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart me-2"></i>Favoritos</a></li>
                <li><a href="history.php"><i class="fas fa-history me-2"></i>Histórico</a></li>
                <li><a href="participant-settings.php"><i class="fas fa-cog me-2"></i>Configurações</a></li>
            </ul>
        </nav>

        <hr class="text-white-50">

        <div class="px-3 mt-auto">
            <a href="<?php echo $homeUrl; ?>" class="sidebar-nav-link text-white-50 d-block py-2">
                <i class="fas fa-home me-2"></i>Página Inicial
            </a>
            <a href="<?php echo $logoutUrl; ?>" class="sidebar-nav-link text-white-50 d-block py-2">
                <i class="fas fa-sign-out-alt me-2"></i>Sair
            </a>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Meu Painel</h2>
                <p class="text-muted mb-0">Olá, <?php echo htmlspecialchars($userName); ?>! Veja seus eventos e descubra novos.</p>
            </div>
            <div>
                <a href="<?php echo $homeUrl; ?>" class="btn btn-success me-2">
                    <i class="fas fa-search me-2"></i>Explorar Eventos
                </a>
                <a href="participant-settings.php" class="btn btn-outline-primary">
                    <i class="fas fa-cog me-2"></i>Configurações
                </a>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-number"><?php echo $stats['eventos_inscritos']; ?></div>
                            <div class="text-muted">Eventos Inscritos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-check fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-number"><?php echo $stats['eventos_participados']; ?></div>
                            <div class="text-muted">Eventos Participados</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-history fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-number"><?php echo $stats['proximos_eventos']; ?></div>
                            <div class="text-muted">Próximos Eventos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="stat-number"><?php echo $stats['eventos_favoritos']; ?></div>
                            <div class="text-muted">Eventos Favoritos</div>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-heart fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Próximos Eventos -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Próximos Eventos
                        </h5>
                        <a href="subscriptions.php" class="btn btn-sm btn-outline-success">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($proximos_eventos)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h6>Nenhum evento próximo</h6>
                                <p class="text-muted">Explore eventos e faça suas inscrições!</p>
                                <a href="<?php echo $homeUrl; ?>" class="btn btn-success">
                                    <i class="fas fa-search me-2"></i>Explorar Eventos
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($proximos_eventos as $evento): ?>
                                <div class="event-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                            <div class="mb-2">
                                                <span class="badge-category"><?php echo $evento['categoria']; ?></span>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?php echo $evento['organizador']; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($evento['data'])); ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo $evento['horario']; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo $evento['local']; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-1">
                                            <a href="../events/view.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Eventos Recomendados -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-star me-2"></i>Recomendados para Você
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($eventos_recomendados as $evento): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <h6 class="mb-2"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                <div class="mb-2">
                                    <span class="badge-category"><?php echo $evento['categoria']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($evento['data'])); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo $evento['participantes']; ?>
                                    </small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <strong class="text-success">
                                        <?php echo $evento['preco'] > 0 ? 'R$ ' . number_format($evento['preco'], 2, ',', '.') : 'Gratuito'; ?>
                                    </strong>
                                    <a href="../events/view.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-eye me-1"></i>Ver
                                    </a>
                                </div>
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
                                <a href="<?php echo $homeUrl; ?>" class="btn btn-success w-100">
                                    <i class="fas fa-search me-2"></i>Explorar Eventos
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="favorites.php" class="btn btn-info w-100">
                                    <i class="fas fa-heart me-2"></i>Meus Favoritos
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="subscriptions.php" class="btn btn-warning w-100">
                                    <i class="fas fa-calendar me-2"></i>Minha Agenda
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="participant-settings.php" class="btn btn-primary w-100">
                                    <i class="fas fa-cog me-2"></i>Configurações
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção de Interesse -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5>
                            <i class="fas fa-lightbulb me-2 text-warning"></i>
                            Dica do Dia
                        </h5>
                        <p class="mb-3">
                            Configure suas preferências de eventos nas <a href="participant-settings.php" class="text-decoration-none">configurações</a> para receber recomendações personalizadas!
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="<?php echo $homeUrl; ?>" class="btn btn-success">
                                <i class="fas fa-search me-2"></i>Descobrir Eventos
                            </a>
                            <a href="participant-settings.php" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-2"></i>Personalizar
                            </a>
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

            // Efeito de clique nos botões
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Efeito visual de clique
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            });

            // Highlight do menu ativo
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.sidebar-nav a');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPath.split('/').pop()) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>