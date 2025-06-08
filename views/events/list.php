<?php
// ==========================================
// MEUS EVENTOS
// Local: views/events/list.php
// ==========================================

session_start();

// Verificar se está logado e é organizador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
    header("Location: ../dashboard/participant.php");
    exit;
}

$title = "Meus Eventos - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Organizador';

// URLs
$dashboardUrl = '../dashboard/organizer.php';
$homeUrl = '../../index.php';
$createEventUrl = 'create.php';

// Filtros
$status_filter = $_GET['status'] ?? '';
$categoria_filter = $_GET['categoria'] ?? '';

// Dados de exemplo dos eventos do organizador
$meus_eventos = [
    [
        'id_evento' => 1,
        'titulo' => 'Workshop de Desenvolvimento Web',
        'categoria' => 'Tecnologia',
        'data_inicio' => '2024-06-15',
        'horario_inicio' => '14:00',
        'local_cidade' => 'São Paulo',
        'status' => 'publicado',
        'participantes' => 45,
        'max_participantes' => 100,
        'evento_gratuito' => true,
        'preco' => 0,
        'created_at' => '2024-05-20'
    ],
    [
        'id_evento' => 2,
        'titulo' => 'Palestra sobre IA e Machine Learning',
        'categoria' => 'Tecnologia',
        'data_inicio' => '2024-06-20',
        'horario_inicio' => '19:00',
        'local_cidade' => 'Rio de Janeiro',
        'status' => 'publicado',
        'participantes' => 32,
        'max_participantes' => 200,
        'evento_gratuito' => false,
        'preco' => 50.00,
        'created_at' => '2024-05-22'
    ],
    [
        'id_evento' => 3,
        'titulo' => 'Meetup de Empreendedorismo Digital',
        'categoria' => 'Negócios',
        'data_inicio' => '2024-06-25',
        'horario_inicio' => '18:30',
        'local_cidade' => 'Belo Horizonte',
        'status' => 'rascunho',
        'participantes' => 0,
        'max_participantes' => 50,
        'evento_gratuito' => true,
        'preco' => 0,
        'created_at' => '2024-05-25'
    ],
    [
        'id_evento' => 4,
        'titulo' => 'Curso de Design UX/UI',
        'categoria' => 'Design',
        'data_inicio' => '2024-07-01',
        'horario_inicio' => '09:00',
        'local_cidade' => 'São Paulo',
        'status' => 'publicado',
        'participantes' => 28,
        'max_participantes' => 80,
        'evento_gratuito' => false,
        'preco' => 150.00,
        'created_at' => '2024-05-28'
    ],
    [
        'id_evento' => 5,
        'titulo' => 'Workshop de Marketing Digital',
        'categoria' => 'Marketing',
        'data_inicio' => '2024-07-10',
        'horario_inicio' => '15:00',
        'local_cidade' => 'Porto Alegre',
        'status' => 'cancelado',
        'participantes' => 12,
        'max_participantes' => 60,
        'evento_gratuito' => true,
        'preco' => 0,
        'created_at' => '2024-06-01'
    ]
];

// Aplicar filtros
$eventos_filtrados = $meus_eventos;

if (!empty($status_filter)) {
    $eventos_filtrados = array_filter($eventos_filtrados, function($evento) use ($status_filter) {
        return $evento['status'] === $status_filter;
    });
}

if (!empty($categoria_filter)) {
    $eventos_filtrados = array_filter($eventos_filtrados, function($evento) use ($categoria_filter) {
        return $evento['categoria'] === $categoria_filter;
    });
}

// Estatísticas
$total_eventos = count($meus_eventos);
$eventos_publicados = count(array_filter($meus_eventos, function($e) { return $e['status'] === 'publicado'; }));
$eventos_rascunho = count(array_filter($meus_eventos, function($e) { return $e['status'] === 'rascunho'; }));
$total_participantes = array_sum(array_column($meus_eventos, 'participantes'));
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
        body {
            background-color: #f8f9fa;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }
        
        .stat-card.primary { border-left-color: #667eea; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.info { border-left-color: #17a2b8; }
        
        .event-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-publicado { 
            background-color: #d4edda; 
            color: #155724; 
        }
        
        .status-rascunho { 
            background-color: #fff3cd; 
            color: #856404; 
        }
        
        .status-cancelado { 
            background-color: #f8d7da; 
            color: #721c24; 
        }
        
        .filters-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .progress-bar-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
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
                <li class="breadcrumb-item active" aria-current="page">Meus Eventos</li>
            </ol>
        </nav>
    </div>

    <!-- Header da Página -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-list me-2"></i>Meus Eventos</h1>
                    <p class="mb-0 fs-5">Gerencie todos os seus eventos em um só lugar</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?php echo $createEventUrl; ?>" class="btn btn-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Novo Evento
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container pb-5">
        <!-- Estatísticas Rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-primary"><?php echo $total_eventos; ?></h3>
                            <small class="text-muted">Total de Eventos</small>
                        </div>
                        <i class="fas fa-calendar fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-success"><?php echo $eventos_publicados; ?></h3>
                            <small class="text-muted">Publicados</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-warning"><?php echo $eventos_rascunho; ?></h3>
                            <small class="text-muted">Rascunhos</small>
                        </div>
                        <i class="fas fa-edit fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-info"><?php echo $total_participantes; ?></h3>
                            <small class="text-muted">Total Participantes</small>
                        </div>
                        <i class="fas fa-users fa-2x text-info"></i>
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
                            <select class="form-select" name="status">
                                <option value="">Todos os Status</option>
                                <option value="publicado" <?php echo $status_filter === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                                <option value="rascunho" <?php echo $status_filter === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                <option value="cancelado" <?php echo $status_filter === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <select class="form-select" name="categoria">
                                <option value="">Todas as Categorias</option>
                                <option value="Tecnologia" <?php echo $categoria_filter === 'Tecnologia' ? 'selected' : ''; ?>>Tecnologia</option>
                                <option value="Negócios" <?php echo $categoria_filter === 'Negócios' ? 'selected' : ''; ?>>Negócios</option>
                                <option value="Marketing" <?php echo $categoria_filter === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Design" <?php echo $categoria_filter === 'Design' ? 'selected' : ''; ?>>Design</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i>Filtrar
                            </button>
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Limpar
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-4 text-md-end">
                    <span class="text-muted">
                        <i class="fas fa-list me-1"></i>
                        <?php echo count($eventos_filtrados); ?> eventos encontrados
                    </span>
                </div>
            </div>
        </div>

        <!-- Lista de Eventos -->
        <?php if (empty($eventos_filtrados)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                <h4>Nenhum evento encontrado</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($status_filter) || !empty($categoria_filter)): ?>
                        Tente ajustar os filtros ou criar um novo evento.
                    <?php else: ?>
                        Você ainda não criou nenhum evento. Que tal começar agora?
                    <?php endif; ?>
                </p>
                <a href="<?php echo $createEventUrl; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Criar Primeiro Evento
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($eventos_filtrados as $evento): ?>
                <div class="event-card">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                    <div class="mb-2">
                                        <span class="badge bg-primary me-2"><?php echo $evento['categoria']; ?></span>
                                        <span class="status-badge status-<?php echo $evento['status']; ?>">
                                            <?php echo ucfirst($evento['status']); ?>
                                        </span>
                                    </div>
                                    <div class="text-muted">
                                        <small>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?> às 
                                            <?php echo date('H:i', strtotime($evento['horario_inicio'])); ?>
                                        </small>
                                        <br>
                                        <small>
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo $evento['local_cidade']; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>Participantes</h6>
                                <div class="progress mb-2">
                                    <div class="progress-bar progress-bar-custom" 
                                         style="width: <?php echo ($evento['participantes'] / $evento['max_participantes']) * 100; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $evento['participantes']; ?> / <?php echo $evento['max_participantes']; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-2 text-center">
                            <strong class="<?php echo $evento['evento_gratuito'] ? 'text-success' : 'text-primary'; ?>">
                                <?php echo $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.'); ?>
                            </strong>
                        </div>
                        
                        <div class="col-md-1">
                            <div class="dropdown">
                                <button class="btn btn-outline-primary btn-sm dropdown-toggle" 
                                        type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="view.php?id=<?php echo $evento['id_evento']; ?>">
                                            <i class="fas fa-eye me-2"></i>Visualizar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="edit.php?id=<?php echo $evento['id_evento']; ?>">
                                            <i class="fas fa-edit me-2"></i>Editar
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="participants.php?id=<?php echo $evento['id_evento']; ?>">
                                            <i class="fas fa-users me-2"></i>Participantes
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php if ($evento['status'] === 'rascunho'): ?>
                                        <li>
                                            <a class="dropdown-item text-success" href="publish.php?id=<?php echo $evento['id_evento']; ?>">
                                                <i class="fas fa-check me-2"></i>Publicar
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($evento['status'] === 'publicado'): ?>
                                        <li>
                                            <a class="dropdown-item text-warning" href="unpublish.php?id=<?php echo $evento['id_evento']; ?>">
                                                <i class="fas fa-pause me-2"></i>Despublicar
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li>
                                        <a class="dropdown-item text-danger" 
                                           href="delete.php?id=<?php echo $evento['id_evento']; ?>"
                                           onclick="return confirm('Tem certeza que deseja excluir este evento?')">
                                            <i class="fas fa-trash me-2"></i>Excluir
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
            const eventCards = document.querySelectorAll('.event-card');
            eventCards.forEach(card => {
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