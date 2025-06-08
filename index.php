<?php
// ========================================
// CONECTA EVENTOS - PÁGINA INICIAL PÚBLICA
// ========================================
// Local: conecta-eventos/index.php (RAIZ)
// Versão otimizada para Railway Deploy
// ========================================

// Tratamento de erros robusto
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros em produção
ini_set('log_errors', 1);

// Verificar se os arquivos necessários existem
$requiredFiles = [
    'config/config.php',
    'includes/session.php',
    'controllers/EventController.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        die("Erro: Arquivo $file não encontrado. Verifique a estrutura do projeto.");
    }
}

try {
    require_once 'config/config.php';
    require_once 'includes/session.php';
    require_once 'controllers/EventController.php';
} catch (Exception $e) {
    error_log("Erro ao carregar arquivos: " . $e->getMessage());
    die("Erro interno do servidor. Tente novamente mais tarde.");
}

$title = "Conecta Eventos - Encontre os melhores eventos";

// Inicializar controlador com tratamento de erro
try {
    $eventController = new EventController();
} catch (Exception $e) {
    error_log("Erro ao inicializar EventController: " . $e->getMessage());
    die("Erro de conexão com o banco de dados.");
}

// Processar filtros de busca com sanitização
$filters = [];
$searchTerm = isset($_GET['busca']) ? trim(htmlspecialchars($_GET['busca'])) : '';
$categoryFilter = isset($_GET['categoria']) ? (int)$_GET['categoria'] : '';
$cityFilter = isset($_GET['cidade']) ? trim(htmlspecialchars($_GET['cidade'])) : '';
$priceFilter = isset($_GET['preco']) ? trim($_GET['preco']) : '';

// Validar e aplicar filtros
if (!empty($searchTerm) && strlen($searchTerm) >= 2) {
    $filters['busca'] = $searchTerm;
}
if (!empty($categoryFilter) && $categoryFilter > 0) {
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

// Obter dados com tratamento de erro
$eventosDestaque = [];
$todosEventos = [];
$categorias = [];
$cidades = [];

try {
    // Eventos de destaque
    $eventosDestaque = $eventController->getPublicEvents(['limite' => 6, 'ordem' => 'destaque']);
    
    // Todos os eventos com filtros
    $todosEventos = $eventController->getPublicEvents($filters);
    
    // Categorias para filtro
    $categorias = $eventController->getCategories();
    
    // Cidades para filtro
    if (class_exists('Database')) {
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            $stmt = $conn->prepare("SELECT DISTINCT local_cidade FROM eventos WHERE status = 'publicado' AND local_cidade IS NOT NULL AND local_cidade != '' ORDER BY local_cidade");
            $stmt->execute();
            $cidades = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }
} catch (Exception $e) {
    error_log("Erro ao carregar dados: " . $e->getMessage());
    // Continuar com arrays vazios em caso de erro
}

// Processar mensagem de logout
$logoutMessage = '';
$logoutType = 'info';
if (isset($_COOKIE['logout_message'])) {
    $logoutMessage = htmlspecialchars($_COOKIE['logout_message']);
    $logoutType = isset($_COOKIE['logout_type']) ? htmlspecialchars($_COOKIE['logout_type']) : 'info';
    
    // Remover cookies de forma segura
    if (setcookie('logout_message', '', time() - 3600, '/', '', false, true) === false) {
        error_log("Erro ao remover cookie logout_message");
    }
    if (setcookie('logout_type', '', time() - 3600, '/', '', false, true) === false) {
        error_log("Erro ao remover cookie logout_type");
    }
}

// Definir URL base segura
$siteUrl = defined('SITE_URL') ? SITE_URL : 'https://conecta-eventos-production.up.railway.app';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conecta Eventos - Encontre e participe dos melhores eventos em sua região. Descubra experiências incríveis e conecte-se com pessoas interessantes.">
    <meta name="keywords" content="eventos, workshops, palestras, networking, entretenimento">
    <meta name="author" content="João Vitor da Silva">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $siteUrl; ?>">
    <meta property="og:title" content="<?php echo $title; ?>">
    <meta property="og:description" content="Descubra experiências incríveis, aprenda coisas novas e conheça pessoas interessantes.">
    
    <title><?php echo $title; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --info-color: #007bff;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 300;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2.5vw, 1.25rem);
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .search-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .event-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        .event-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.3s ease;
        }

        .event-card:hover .event-image {
            transform: scale(1.05);
        }

        .no-image {
            height: 200px;
            background: linear-gradient(135deg, var(--light-color) 0%, #e9ecef 100%);
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
            font-size: 0.875rem;
            backdrop-filter: blur(5px);
        }

        .price-badge.paid {
            background: rgba(0, 123, 255, 0.9);
        }

        .category-badge {
            background: rgba(0, 123, 255, 0.9);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }

        .stats-section {
            background: var(--light-color);
            padding: 4rem 0;
            margin: 4rem 0;
        }

        .stat-item {
            text-align: center;
            padding: 1.5rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--info-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: var(--dark-color);
            font-weight: 500;
        }

        .filters-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 0.75rem;
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .loading-placeholder {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 0;
            }
            
            .search-card {
                padding: 1.5rem;
                margin-top: 1rem;
            }
            
            .filters-section {
                padding: 1.5rem;
            }
            
            .stats-section {
                padding: 2rem 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $siteUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <span class="navbar-text me-3">
                            <i class="fas fa-user-circle me-1"></i>
                            Olá, <?php echo function_exists('getUserName') ? htmlspecialchars(getUserName()) : 'Usuário'; ?>!
                        </span>
                        <?php if (function_exists('isOrganizer') && isOrganizer()): ?>
                            <a class="nav-link" href="views/dashboard/organizer.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                            <a class="nav-link" href="views/events/list.php">
                                <i class="fas fa-list me-1"></i>Meus Eventos
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="views/dashboard/participant.php">
                                <i class="fas fa-user me-1"></i>Meu Painel
                            </a>
                        <?php endif; ?>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Sair
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="views/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                        <a class="nav-link" href="views/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Cadastrar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <!-- Mensagem de Logout -->
            <?php if ($logoutMessage): ?>
                <div class="alert alert-<?php echo $logoutType; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $logoutMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Conecte-se aos melhores eventos</h1>
                    <p class="hero-subtitle">
                        Descubra experiências incríveis, aprenda coisas novas e conheça pessoas interessantes em eventos únicos.
                    </p>
                    <?php if (!function_exists('isLoggedIn') || !isLoggedIn()): ?>
                        <div class="d-flex flex-column flex-sm-row gap-3">
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
                        <form method="GET" action="" role="search">
                            <div class="row g-3">
                                <div class="col-12">
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           name="busca" 
                                           placeholder="Buscar eventos..."
                                           value="<?php echo $searchTerm; ?>"
                                           maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" name="categoria" aria-label="Selecionar categoria">
                                        <option value="">Todas as categorias</option>
                                        <?php if (!empty($categorias)): ?>
                                            <?php foreach ($categorias as $categoria): ?>
                                                <option value="<?php echo (int)$categoria['id_categoria']; ?>"
                                                        <?php echo $categoryFilter == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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
                        <select class="form-select" name="cidade" aria-label="Selecionar cidade">
                            <option value="">Todas as cidades</option>
                            <?php if (!empty($cidades)): ?>
                                <?php foreach ($cidades as $cidade): ?>
                                    <?php if (!empty($cidade['local_cidade'])): ?>
                                        <option value="<?php echo htmlspecialchars($cidade['local_cidade']); ?>"
                                                <?php echo $cityFilter === $cidade['local_cidade'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cidade['local_cidade']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Preço</label>
                        <select class="form-select" name="preco" aria-label="Filtrar por preço">
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
                               value="<?php echo $searchTerm; ?>"
                               maxlength="100">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                    </div>
                    
                    <!-- Campos hidden para manter outros filtros -->
                    <?php if (!empty($categoryFilter)): ?>
                        <input type="hidden" name="categoria" value="<?php echo (int)$categoryFilter; ?>">
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
                        <?php 
                        $evento = method_exists($eventController, 'formatEventForDisplay') ? 
                                  $eventController->formatEventForDisplay($evento) : $evento; 
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <?php include 'includes/event-card.php'; ?>
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
                    <?php echo count($todosEventos); ?> evento<?php echo count($todosEventos) !== 1 ? 's' : ''; ?> encontrado<?php echo count($todosEventos) !== 1 ? 's' : ''; ?>
                </span>
            </div>
            
            <?php if (empty($todosEventos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h4>Nenhum evento encontrado</h4>
                    <p class="text-muted mb-4">
                        <?php if (!empty($searchTerm) || !empty($categoryFilter) || !empty($cityFilter) || !empty($priceFilter)): ?>
                            Tente ajustar os filtros ou buscar por outros termos.
                        <?php else: ?>
                            Ainda não há eventos cadastrados. Volte em breve!
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($searchTerm) || !empty($categoryFilter) || !empty($cityFilter) || !empty($priceFilter)): ?>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Ver Todos os Eventos
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($todosEventos as $evento): ?>
                        <?php 
                        $evento = method_exists($eventController, 'formatEventForDisplay') ? 
                                  $eventController->formatEventForDisplay($evento) : $evento; 
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <?php include 'includes/event-card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Estatísticas -->
        <section class="stats-section">
            <div class="container">
                <div class="row">
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($todosEventos); ?></div>
                            <div class="stat-label">Eventos Disponíveis</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($categorias); ?></div>
                            <div class="stat-label">Categorias</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($cidades); ?></div>
                            <div class="stat-label">Cidades</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number">
                                <?php 
                                $eventosGratuitos = array_filter($todosEventos, function($e) { 
                                    return isset($e['evento_gratuito']) && $e['evento_gratuito']; 
                                });
                                echo count($eventosGratuitos); 
                                ?>
                            </div>
                            <div class="stat-label">Eventos Gratuitos</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-calendar-check me-2"></i>Conecta Eventos</h5>
                    <p class="mb-3">Conectando pessoas através de experiências incríveis.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white-50 hover-text-white" aria-label="Facebook">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <a href="#" class="text-white-50 hover-text-white" aria-label="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-white-50 hover-text-white" aria-label="Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">&copy; <?php echo date('Y'); ?> Conecta Eventos.</p>
                    <p class="mb-0 text-white-50">Desenvolvido com <i class="fas fa-heart text-danger"></i> por João Vitor da Silva</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb