<?php
// ========================================
// PÁGINA DE FAVORITOS - PARTICIPANTE
// ========================================
// Local: views/dashboard/favorites.php
// ========================================

session_start();

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$title = "Meus Favoritos - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Participante';
$userType = $_SESSION['user_type'] ?? 'participante';

// URLs
$dashboardUrl = $userType === 'organizador' ? 'organizer.php' : 'participant.php';
$homeUrl = '../../index.php';

$success_message = '';
$error_message = '';

// Processar remoção de favorito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $eventId = $_POST['event_id'] ?? 0;
    if ($eventId > 0) {
        $success_message = "Evento removido dos favoritos com sucesso!";
    } else {
        $error_message = "Erro ao remover evento dos favoritos.";
    }
}

// Dados de exemplo dos eventos favoritos
$eventos_favoritos = [
    [
        'id_evento' => 1,
        'titulo' => 'Workshop de Desenvolvimento Web',
        'descricao' => 'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
        'data_inicio' => '2024-06-15',
        'horario_inicio' => '14:00',
        'local_cidade' => 'São Paulo',
        'local_estado' => 'SP',
        'categoria' => 'Tecnologia',
        'organizador' => 'Tech Academy',
        'evento_gratuito' => true,
        'preco' => 0,
        'total_inscritos' => 45,
        'max_participantes' => 100,
        'data_favoritado' => '2024-06-01 10:30:00',
        'imagem_capa' => '',
        'status' => 'publicado'
    ],
    [
        'id_evento' => 2,
        'titulo' => 'Curso de Design UX/UI',
        'descricao' => 'Princípios fundamentais de design de experiência do usuário.',
        'data_inicio' => '2024-07-01',
        'horario_inicio' => '09:00',
        'local_cidade' => 'Rio de Janeiro',
        'local_estado' => 'RJ',
        'categoria' => 'Design',
        'organizador' => 'Design Institute',
        'evento_gratuito' => false,
        'preco' => 150.00,
        'total_inscritos' => 28,
        'max_participantes' => 80,
        'data_favoritado' => '2024-05-28 16:45:00',
        'imagem_capa' => '',
        'status' => 'publicado'
    ],
    [
        'id_evento' => 3,
        'titulo' => 'Meetup de Empreendedorismo',
        'descricao' => 'Encontro para empreendedores discutirem ideias e oportunidades.',
        'data_inicio' => '2024-06-25',
        'horario_inicio' => '18:30',
        'local_cidade' => 'Belo Horizonte',
        'local_estado' => 'MG',
        'categoria' => 'Negócios',
        'organizador' => 'StartupBH',
        'evento_gratuito' => true,
        'preco' => 0,
        'total_inscritos' => 32,
        'max_participantes' => 50,
        'data_favoritado' => '2024-05-20 14:20:00',
        'imagem_capa' => '',
        'status' => 'publicado'
    ],
    [
        'id_evento' => 4,
        'titulo' => 'Conferência de IA',
        'descricao' => 'O futuro da inteligência artificial e suas aplicações.',
        'data_inicio' => '2024-08-10',
        'horario_inicio' => '08:00',
        'local_cidade' => 'São Paulo',
        'local_estado' => 'SP',
        'categoria' => 'Tecnologia',
        'organizador' => 'AI Conference',
        'evento_gratuito' => false,
        'preco' => 300.00,
        'total_inscritos' => 89,
        'max_participantes' => 200,
        'data_favoritado' => '2024-05-15 09:10:00',
        'imagem_capa' => '',
        'status' => 'publicado'
    ]
];

// Estatísticas dos favoritos
$stats = [
    'total_favoritos' => count($eventos_favoritos),
    'eventos_gratuitos' => count(array_filter($eventos_favoritos, fn($e) => $e['evento_gratuito'])),
    'eventos_pagos' => count(array_filter($eventos_favoritos, fn($e) => !$e['evento_gratuito'])),
    'categorias_unicas' => count(array_unique(array_column($eventos_favoritos, 'categoria')))
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
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            background-color: #f8f9fa;
        }

        .page-header {
            background: linear-gradient(135deg, var(--success-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.info { border-left-color: var(--info-color); }

        .favorite-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .favorite-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .favorite-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 0.5rem;
            border-radius: 50%;
            font-size: 1.2rem;
            z-index: 2;
        }

        .event-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .no-image {
            height: 200px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .price-badge {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
        }

        .price-badge.paid {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .category-badge {
            background: var(--info-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .favorite-date {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .remove-btn {
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            transform: scale(1.1);
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .filters-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #28a745 0%, #17a2b8 100%);">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Olá, <?php echo htmlspecialchars($userName); ?>!
                </span>
                <a class="nav-link" href="<?php echo $dashboardUrl; ?>">Meu Painel</a>
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
                        <i class="fas fa-tachometer-alt me-1"></i>Meu Painel
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Favoritos</li>
            </ol>
        </nav>
    </div>

    <!-- Header da Página -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-heart me-2"></i>Meus Favoritos</h1>
                    <p class="mb-0 fs-5">Eventos que você salvou para participar depois</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?php echo $homeUrl; ?>" class="btn btn-outline-light">
                        <i class="fas fa-search me-2"></i>Explorar Eventos
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container pb-5">
        <!-- Mensagens -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-primary"><?php echo $stats['total_favoritos']; ?></h3>
                            <small class="text-muted">Total de Favoritos</small>
                        </div>
                        <i class="fas fa-heart fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-success"><?php echo $stats['eventos_gratuitos']; ?></h3>
                            <small class="text-muted">Eventos Gratuitos</small>
                        </div>
                        <i class="fas fa-gift fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-warning"><?php echo $stats['eventos_pagos']; ?></h3>
                            <small class="text-muted">Eventos Pagos</small>
                        </div>
                        <i class="fas fa-credit-card fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-info"><?php echo $stats['categorias_unicas']; ?></h3>
                            <small class="text-muted">Categorias</small>
                        </div>
                        <i class="fas fa-tags fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select class="form-select" name="categoria">
                                <option value="">Todas as categorias</option>
                                <option value="Tecnologia">Tecnologia</option>
                                <option value="Design">Design</option>
                                <option value="Negócios">Negócios</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <select class="form-select" name="tipo">
                                <option value="">Todos os tipos</option>
                                <option value="gratuito">Apenas Gratuitos</option>
                                <option value="pago">Apenas Pagos</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i>Filtrar
                            </button>
                            <a href="favorites.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Limpar
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-4 text-md-end">
                    <span class="text-muted">
                        <i class="fas fa-heart me-1"></i>
                        <?php echo count($eventos_favoritos); ?> eventos salvos
                    </span>
                </div>
            </div>
        </div>

        <!-- Lista de Favoritos -->
        <?php if (empty($eventos_favoritos)): ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken fa-4x mb-4"></i>
                <h4>Nenhum evento favoritado ainda</h4>
                <p class="mb-4">Explore eventos incríveis e salve os que mais te interessam!</p>
                <a href="<?php echo $homeUrl; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Explorar Eventos
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($eventos_favoritos as $evento): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="favorite-card">
                            <div class="favorite-badge" title="Remover dos favoritos">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Remover este evento dos favoritos?')">
                                    <input type="hidden" name="event_id" value="<?php echo $evento['id_evento']; ?>">
                                    <button type="submit" name="remove_favorite" class="btn p-0 remove-btn" style="background: none; border: none; color: white;">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Imagem -->
                            <?php if (!empty($evento['imagem_capa'])): ?>
                                <img src="../../uploads/eventos/<?php echo htmlspecialchars($evento['imagem_capa']); ?>" 
                                     alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                     class="event-image">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-calendar-alt fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Conteúdo -->
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                    <span class="category-badge"><?php echo $evento['categoria']; ?></span>
                                </div>
                                
                                <p class="text-muted mb-3">
                                    <?php echo substr(htmlspecialchars($evento['descricao']), 0, 100); ?>...
                                </p>
                                
                                <!-- Informações do evento -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-calendar text-primary me-2"></i>
                                        <span><?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?></span>
                                        <i class="fas fa-clock text-primary ms-3 me-2"></i>
                                        <span><?php echo date('H:i', strtotime($evento['horario_inicio'])); ?></span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <span><?php echo htmlspecialchars($evento['local_cidade']); ?>, <?php echo $evento['local_estado']; ?></span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        <span><?php echo htmlspecialchars($evento['organizador']); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Preço e participantes -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="price-badge <?php echo $evento['evento_gratuito'] ? '' : 'paid'; ?>">
                                        <?php echo $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.'); ?>
                                    </span>
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo $evento['total_inscritos']; ?>/<?php echo $evento['max_participantes']; ?>
                                    </small>
                                </div>
                                
                                <!-- Data que foi favoritado -->
                                <div class="favorite-date mb-3">
                                    <i class="fas fa-heart me-1"></i>
                                    Favoritado em <?php echo date('d/m/Y', strtotime($evento['data_favoritado'])); ?>
                                </div>
                                
                                <!-- Ações -->
                                <div class="d-grid gap-2">
                                    <a href="../events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>Ver Detalhes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Ações em massa -->
        <?php if (!empty($eventos_favoritos)): ?>
            <div class="text-center mt-4">
                <button class="btn btn-outline-danger" onclick="clearAllFavorites()">
                    <i class="fas fa-trash me-2"></i>Limpar Todos os Favoritos
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animação das estatísticas
            const statNumbers = document.querySelectorAll('.stat-card h3');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(current);
                    }
                }, 50);
            });

            // Hover effects nos cards
            const favoriteCards = document.querySelectorAll('.favorite-card');
            favoriteCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 5000);
            });
        });

        // Função para limpar todos os favoritos
        function clearAllFavorites() {
            if (confirm('Tem certeza que deseja remover TODOS os eventos dos favoritos? Esta ação não pode ser desfeita.')) {
                alert('Funcionalidade em desenvolvimento! Em breve você poderá limpar todos os favoritos de uma vez.');
            }
        }

        // Adicionar efeito de loading nos botões de remoção
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = form.querySelector('button');
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
            });
        });
    </script>
</body>
</html>