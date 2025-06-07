<?php
// ========================================
// CONECTA EVENTOS - PÁGINA INICIAL PÚBLICA
// ========================================
// Local: conecta-eventos/index.php (RAIZ)
// ========================================

require_once 'config/config.php';
require_once 'includes/session.php';
require_once 'controllers/EventController.php';

$title = "Conecta Eventos - Encontre os melhores eventos";
$eventController = new EventController();

// Processar filtros de busca
$filters = [];
$searchTerm = $_GET['busca'] ?? '';
$categoryFilter = $_GET['categoria'] ?? '';
$cityFilter = $_GET['cidade'] ?? '';
$priceFilter = $_GET['preco'] ?? '';

if (!empty($searchTerm)) {
    $filters['busca'] = $searchTerm;
}
if (!empty($categoryFilter)) {
    $filters['categoria_id'] = $categoryFilter;
}
if (!empty($cityFilter)) {
    $filters['cidade'] = $cityFilter;
}
if ($priceFilter === 'gratuito') {
    $filters['gratuito'] = true;
} elseif ($priceFilter === 'pago') {
    $filters['gratuito'] = false;
}

// Obter eventos públicos com filtros
$eventosDestaque = $eventController->getPublicEvents(['limite' => 6, 'ordem' => 'destaque']);
$todosEventos = $eventController->getPublicEvents($filters);

// Obter categorias para filtro
$categorias = $eventController->getCategories();

// Obter cidades para filtro
try {
    $database = new Database();
    $conn = $database->getConnection();
    $stmt = $conn->prepare("SELECT DISTINCT local_cidade FROM eventos WHERE status = 'publicado' ORDER BY local_cidade");
    $stmt->execute();
    $cidades = $stmt->fetchAll();
} catch (Exception $e) {
    $cidades = [];
}

// Processar mensagem de logout
$logoutMessage = '';
if (isset($_COOKIE['logout_message'])) {
    $logoutMessage = $_COOKIE['logout_message'];
    $logoutType = $_COOKIE['logout_type'] ?? 'info';
    // Remover cookies
    setcookie('logout_message', '', time() - 3600, '/');
    setcookie('logout_type', '', time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 300;
            margin-bottom: 1rem;
        }
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .search-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .event-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
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
        .event-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 10;
        }
        .price-badge {
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
        }
        .category-badge {
            background: rgba(0, 123, 255, 0.9);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
        }
        .stats-section {
            background: #f8f9fa;
            padding: 3rem 0;
            margin: 3rem 0;
        }
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .filters-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            .hero-subtitle {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <strong>Conecta Eventos</strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <span class="navbar-text me-3">
                            Olá, <?php echo htmlspecialchars(getUserName()); ?>!
                        </span>
                        <?php if (isOrganizer()): ?>
                            <a class="nav-link" href="views/dashboard/organizer.php">Dashboard</a>
                            <a class="nav-link" href="views/events/list.php">Meus Eventos</a>
                        <?php else: ?>
                            <a class="nav-link" href="views/dashboard/participant.php">Meu Painel</a>
                        <?php endif; ?>
                        <a class="nav-link" href="logout.php">Sair</a>
                    <?php else: ?>
                        <a class="nav-link" href="views/auth/login.php">Login</a>
                        <a class="nav-link" href="views/auth/register.php">Cadastrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <!-- Mensagem de Logout -->
            <?php if ($logoutMessage): ?>
                <div class="alert alert-<?php echo $logoutType; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($logoutMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Conecte-se aos melhores eventos</h1>
                    <p class="hero-subtitle">
                        Descubra experiências incríveis, aprenda coisas novas e conheça pessoas interessantes.
                    </p>
                    <?php if (!isLoggedIn()): ?>
                        <div class="d-flex gap-3">
                            <a href="views/auth/register.php" class="btn btn-light btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Cadastrar-se
                            </a>
                            <a href="views/auth/login.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-6">
                    <!-- Busca Rápida -->
                    <div class="search-card">
                        <h4 class="text-dark mb-3">
                            <i class="fas fa-search me-2"></i>Encontre Eventos
                        </h4>
                        <form method="GET" action="">
                            <div class="row g-2">
                                <div class="col-12">
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           name="busca" 
                                           placeholder="Buscar eventos..."
                                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" name="categoria">
                                        <option value="">Todas as categorias</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['id_categoria']; ?>"
                                                    <?php echo $categoryFilter == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($categoria['nome']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-search me-2"></i>Buscar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Filtros Avançados -->
        <?php if (!empty($searchTerm) || !empty($categoryFilter) || !empty($cityFilter) || !empty($priceFilter)): ?>
            <div class="filters-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filtros Aplicados
                    </h5>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-2"></i>Limpar Filtros
                    </a>
                </div>
                
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <select class="form-select" name="cidade">
                            <option value="">Todas as cidades</option>
                            <?php foreach ($cidades as $cidade): ?>
                                <option value="<?php echo htmlspecialchars($cidade['local_cidade']); ?>"
                                        <?php echo $cityFilter === $cidade['local_cidade'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cidade['local_cidade']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Preço</label>
                        <select class="form-select" name="preco">
                            <option value="">Todos os preços</option>
                            <option value="gratuito" <?php echo $priceFilter === 'gratuito' ? 'selected' : ''; ?>>Gratuito</option>
                            <option value="pago" <?php echo $priceFilter === 'pago' ? 'selected' : ''; ?>>Pago</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <input type="text" 
                               class="form-control" 
                               name="busca" 
                               placeholder="Título ou descrição..."
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                    
                    <!-- Campos hidden para manter outros filtros -->
                    <?php if (!empty($categoryFilter)): ?>
                        <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

        <!-- Eventos em Destaque -->
        <?php if (empty($searchTerm) && empty($categoryFilter) && empty($cityFilter) && empty($priceFilter) && !empty($eventosDestaque)): ?>
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-star text-warning me-2"></i>
                        Eventos em Destaque
                    </h2>
                </div>
                
                <div class="row">
                    <?php foreach (array_slice($eventosDestaque, 0, 6) as $evento): ?>
                        <?php $evento = $eventController->formatEventForDisplay($evento); ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card event-card position-relative">
                                <div class="event-badge">
                                    <span class="price-badge">
                                        <?php echo $evento['preco_formatado']; ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($evento['imagem_capa'])): ?>
                                    <img src="<?php echo $evento['imagem_url']; ?>" 
                                         alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                         class="event-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-calendar-alt fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                        <?php if ($evento['nome_categoria']): ?>
                                            <span class="category-badge">
                                                <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="card-text text-muted">
                                        <?php echo substr(htmlspecialchars($evento['descricao']), 0, 100); ?>...
                                    </p>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo $evento['data_inicio_formatada']; ?>
                                            
                                            <i class="fas fa-clock ms-3 me-1"></i>
                                            <?php echo $evento['horario_inicio_formatado']; ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                            
                                            <i class="fas fa-users ms-3 me-1"></i>
                                            <?php echo $evento['total_inscritos'] ?? 0; ?> inscritos
                                        </small>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <a href="views/events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                           class="btn btn-primary">
                                            <i class="fas fa-eye me-2"></i>Ver Detalhes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Todos os Eventos -->
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?php if (!empty($searchTerm) || !empty($categoryFilter) || !empty($cityFilter) || !empty($priceFilter)): ?>
                        Resultados da Busca
                    <?php else: ?>
                        Todos os Eventos
                    <?php endif; ?>
                </h2>
                <span class="badge bg-secondary fs-6">
                    <?php echo count($todosEventos); ?> eventos encontrados
                </span>
            </div>
            
            <?php if (empty($todosEventos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h4>Nenhum evento encontrado</h4>
                    <p class="text-muted">Tente ajustar os filtros ou buscar por outros termos.</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Ver Todos os Eventos
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($todosEventos as $evento): ?>
                        <?php $evento = $eventController->formatEventForDisplay($evento); ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card event-card position-relative">
                                <div class="event-badge">
                                    <span class="price-badge">
                                        <?php echo $evento['preco_formatado']; ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($evento['imagem_capa'])): ?>
                                    <img src="<?php echo $evento['imagem_url']; ?>" 
                                         alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                         class="event-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-calendar-alt fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                        <?php if ($evento['nome_categoria']): ?>
                                            <span class="category-badge">
                                                <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="card-text text-muted">
                                        <?php echo substr(htmlspecialchars($evento['descricao']), 0, 100); ?>...
                                    </p>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo $evento['data_inicio_formatada']; ?>
                                            
                                            <i class="fas fa-clock ms-3 me-1"></i>
                                            <?php echo $evento['horario_inicio_formatado']; ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                            
                                            <i class="fas fa-users ms-3 me-1"></i>
                                            <?php echo $evento['total_inscritos'] ?? 0; ?> inscritos
                                        </small>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <a href="views/events/view.php?id=<?php echo $evento['id_evento']; ?>" 
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
        </section>

        <!-- Estatísticas -->
        <section class="stats-section">
            <div class="container">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($todosEventos); ?></div>
                            <div>Eventos Disponíveis</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($categorias); ?></div>
                            <div>Categorias</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($cidades); ?></div>
                            <div>Cidades</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php 
                                $eventosGratuitos = array_filter($todosEventos, function($e) { return $e['evento_gratuito']; });
                                echo count($eventosGratuitos); 
                                ?>
                            </div>
                            <div>Eventos Gratuitos</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-start">
                    <h5>Conecta Eventos</h5>
                    <p class="mb-0">Conectando pessoas através de experiências incríveis.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Conecta Eventos. Desenvolvido por João Vitor da Silva.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Smooth scroll para botões
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>