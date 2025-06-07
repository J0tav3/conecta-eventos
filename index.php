<?php
// ========================================
// CONECTA EVENTOS - HOMEPAGE COMPLETA
// ========================================
// Local: index.php
// Sistema completo de eventos públicos para Railway
// ========================================

// Configurações de erro para produção
error_reporting(0);
ini_set('display_errors', 0);

// Carregar configurações
require_once 'config/config.php';

// Fallback para constantes se não foram definidas
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Conecta Eventos');
}
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://conecta-eventos-production.up.railway.app');
}

// Carregar dependências com fallback
$includes = [
    'includes/session.php',
    'controllers/EventController.php'
];

foreach ($includes as $include) {
    if (file_exists($include)) {
        require_once $include;
    }
}

// Verificar mensagem de logout
$logoutMessage = '';
$logoutType = '';
if (isset($_COOKIE['logout_message'])) {
    $logoutMessage = $_COOKIE['logout_message'];
    $logoutType = $_COOKIE['logout_type'] ?? 'success';
    
    setcookie('logout_message', '', time() - 3600, '/');
    setcookie('logout_type', '', time() - 3600, '/');
}

$title = "Conecta Eventos - Plataforma de Eventos";

// Inicializar variáveis
$eventos = [];
$categorias = [];
$stats = [
    'total_eventos' => 0,
    'total_participantes' => 0,
    'eventos_hoje' => 0,
    'organizadores_ativos' => 0
];

// Tentar obter dados dos eventos
try {
    if (class_exists('EventController')) {
        $eventController = new EventController();
        
        // Filtros para eventos públicos
        $filters = [
            'status' => 'publicado',
            'limite' => 12,
            'ordem' => 'data_inicio'
        ];
        
        // Aplicar filtros da URL
        if (!empty($_GET['categoria'])) {
            $filters['categoria_id'] = (int)$_GET['categoria'];
        }
        if (!empty($_GET['cidade'])) {
            $filters['cidade'] = $_GET['cidade'];
        }
        if (!empty($_GET['busca'])) {
            $filters['busca'] = $_GET['busca'];
        }
        if (isset($_GET['gratuito'])) {
            $filters['gratuito'] = $_GET['gratuito'] === '1';
        }
        
        // Obter eventos
        $eventos = $eventController->getPublicEvents($filters);
        
        // Formatar eventos para exibição
        foreach ($eventos as &$evento) {
            $evento = $eventController->formatEventForDisplay($evento);
        }
        
        // Obter categorias
        $categorias = $eventController->getCategories();
        
        // Obter estatísticas básicas
        if (class_exists('Database')) {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Total de eventos publicados
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM eventos WHERE status = 'publicado'");
            $stmt->execute();
            $stats['total_eventos'] = $stmt->fetch()['total'];
            
            // Total de participantes únicos
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT id_participante) as total FROM inscricoes WHERE status = 'confirmada'");
            $stmt->execute();
            $stats['total_participantes'] = $stmt->fetch()['total'];
            
            // Eventos hoje
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM eventos WHERE status = 'publicado' AND DATE(data_inicio) = CURDATE()");
            $stmt->execute();
            $stats['eventos_hoje'] = $stmt->fetch()['total'];
            
            // Organizadores ativos
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT id_organizador) as total FROM eventos WHERE status = 'publicado'");
            $stmt->execute();
            $stats['organizadores_ativos'] = $stmt->fetch()['total'];
        }
        
    }
} catch (Exception $e) {
    error_log("Erro ao carregar eventos: " . $e->getMessage());
}

// Eventos em destaque (primeiros 6)
$eventosDestaque = array_slice($eventos, 0, 6);

// Próximos eventos (baseado na data)
$proximosEventos = array_filter($eventos, function($evento) {
    return strtotime($evento['data_inicio']) >= time();
});
$proximosEventos = array_slice($proximosEventos, 0, 8);

// Cidades populares
$cidades = [];
foreach ($eventos as $evento) {
    $cidade = $evento['local_cidade'];
    if (!isset($cidades[$cidade])) {
        $cidades[$cidade] = 0;
    }
    $cidades[$cidade]++;
}
arsort($cidades);
$cidadesPopulares = array_slice(array_keys($cidades), 0, 8);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    
    <!-- Meta Tags SEO -->
    <meta name="description" content="Conecta Eventos - Descubra e participe dos melhores eventos na sua região. Plataforma completa para organizar e participar de eventos.">
    <meta name="keywords" content="eventos, workshops, palestras, networking, cursos, seminários">
    <meta name="author" content="Conecta Eventos">
    
    <!-- Open Graph -->
    <meta property="og:title" content="Conecta Eventos - Plataforma de Eventos">
    <meta property="og:description" content="Descubra eventos incríveis na sua região">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><polygon points="0,0 1000,0 1000,100 0,80"/></svg>');
            background-size: cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .search-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
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
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .event-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        
        .event-image-placeholder {
            height: 200px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        .category-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 3;
        }
        
        .price-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 3;
        }
        
        .stats-section {
            background: #f8f9fa;
            padding: 3rem 0;
        }
        
        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .categories-section {
            padding: 4rem 0;
        }
        
        .category-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            border: 2px solid #f8f9fa;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .category-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
        
        .filter-pills {
            background: white;
            border-radius: 2rem;
            padding: 1rem 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .filter-pill {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            text-decoration: none;
            margin: 0.25rem;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .filter-pill:hover,
        .filter-pill.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
            text-decoration: none;
        }
        
        .load-more-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            border-radius: 2rem;
            transition: all 0.3s ease;
        }
        
        .load-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .footer-cta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
        }
        
        .btn-cta {
            background: white;
            color: #667eea;
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 2rem;
            transition: all 0.3s ease;
        }
        
        .btn-cta:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 0;
            }
            
            .search-box {
                padding: 1.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-absolute w-100" style="z-index: 100;">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-calendar-alt me-2"></i>Conecta Eventos
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <!-- Usuário logado -->
                        <li class="nav-item">
                            <?php if (function_exists('isOrganizer') && isOrganizer()): ?>
                                <a class="nav-link" href="views/dashboard/organizer.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            <?php else: ?>
                                <a class="nav-link" href="views/dashboard/participant.php">
                                    <i class="fas fa-user me-1"></i>Meu Painel
                                </a>
                            <?php endif; ?>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Sair
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Usuário não logado -->
                        <li class="nav-item">
                            <a class="nav-link" href="views/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="views/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Cadastrar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <!-- Mensagem de Logout -->
                <?php if ($logoutMessage): ?>
                    <div class="alert alert-<?php echo $logoutType; ?> alert-dismissible fade show mx-auto mb-4" style="max-width: 500px;" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($logoutMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <h1 class="display-4 fw-bold mb-3">
                    Descubra Eventos Incríveis
                </h1>
                <p class="lead fs-5 mb-4">
                    Conecte-se com experiências únicas e amplie suas oportunidades
                </p>
                
                <!-- Caixa de Busca -->
                <div class="search-box mx-auto" style="max-width: 800px;">
                    <form method="GET" action="">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label text-dark fw-semibold">O que você procura?</label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       name="busca" 
                                       placeholder="Digite palavras-chave..."
                                       value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-dark fw-semibold">Categoria</label>
                                <select class="form-select form-select-lg" name="categoria">
                                    <option value="">Todas</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id_categoria']; ?>" 
                                                <?php echo ($_GET['categoria'] ?? '') == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-dark fw-semibold">Cidade</label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       name="cidade" 
                                       placeholder="Qualquer cidade"
                                       value="<?php echo htmlspecialchars($_GET['cidade'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>Buscar
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filtros Adicionais -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="gratuito" value="1" 
                                           <?php echo ($_GET['gratuito'] ?? '') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-dark">
                                        Apenas eventos gratuitos
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Estatísticas -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['total_eventos']); ?></div>
                        <h5 class="text-muted">Eventos Ativos</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['total_participantes']); ?></div>
                        <h5 class="text-muted">Participantes</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['eventos_hoje']; ?></div>
                        <h5 class="text-muted">Eventos Hoje</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['organizadores_ativos']; ?></div>
                        <h5 class="text-muted">Organizadores</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filtros Rápidos -->
    <?php if (!empty($categorias) || !empty($cidadesPopulares)): ?>
        <section class="py-4">
            <div class="container">
                <div class="filter-pills text-center">
                    <h6 class="text-muted mb-3">Filtros Populares:</h6>
                    
                    <!-- Categorias -->
                    <?php foreach (array_slice($categorias, 0, 5) as $categoria): ?>
                        <a href="?categoria=<?php echo $categoria['id_categoria']; ?>" 
                           class="filter-pill <?php echo ($_GET['categoria'] ?? '') == $categoria['id_categoria'] ? 'active' : ''; ?>">
                            <i class="<?php echo $categoria['icone'] ?? 'fas fa-tag'; ?> me-1"></i>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <!-- Cidades -->
                    <?php foreach (array_slice($cidadesPopulares, 0, 4) as $cidade): ?>
                        <a href="?cidade=<?php echo urlencode($cidade); ?>" 
                           class="filter-pill <?php echo ($_GET['cidade'] ?? '') === $cidade ? 'active' : ''; ?>">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($cidade); ?>
                        </a>
                    <?php endforeach; ?>
                    
                    <!-- Limpar filtros -->
                    <?php if (!empty($_GET)): ?>
                        <a href="?" class="filter-pill text-danger">
                            <i class="fas fa-times me-1"></i>Limpar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Eventos em Destaque -->
    <?php if (!empty($eventosDestaque)): ?>
        <section class="py-5">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <h2 class="display-6 fw-bold mb-3">Eventos em Destaque</h2>
                        <p class="lead text-muted">Os melhores eventos selecionados para você</p>
                    </div>
                </div>
                
                <div class="row g-4">
                    <?php foreach (array_slice($eventosDestaque, 0, 6) as $evento): ?>
                        <div class="col-md-6 col-lg-4">
                            <article class="event-card card h-100">
                                <div class="position-relative">
                                    <?php if (!empty($evento['imagem_capa'])): ?>
                                        <img src="<?php echo $evento['imagem_url']; ?>" 
                                             alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                             class="event-image">
                                    <?php else: ?>
                                        <div class="event-image-placeholder">
                                            <i class="fas fa-calendar-alt fa-3x"></i>
                                        </div>
                                    
                                    <div class="mt-auto">
                                        <a href="views/events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                           class="btn btn-outline-primary btn-sm w-100">
                                            Ver Mais
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Load More Button (for future AJAX implementation) -->
                <?php if (count($eventos) >= 12): ?>
                    <div class="text-center mt-5">
                        <button id="load-more-btn" class="load-more-btn" onclick="showLoadMoreMessage()">
                            <i class="fas fa-plus me-2"></i>Carregar Mais Eventos
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <!-- Nenhum Evento Encontrado -->
        <section class="py-5">
            <div class="container">
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                    <h3 class="mb-3">
                        <?php if (!empty($_GET)): ?>
                            Nenhum evento encontrado
                        <?php else: ?>
                            Nenhum evento disponível
                        <?php endif; ?>
                    </h3>
                    <p class="text-muted mb-4">
                        <?php if (!empty($_GET)): ?>
                            Tente ajustar os filtros de busca ou remover algumas palavras-chave.
                        <?php else: ?>
                            Novos eventos são adicionados regularmente. Volte em breve!
                        <?php endif; ?>
                    </p>
                    
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <?php if (!empty($_GET)): ?>
                            <a href="?" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Ver Todos os Eventos
                            </a>
                        <?php endif; ?>
                        
                        <a href="views/auth/register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>Criar Conta
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Categorias -->
    <?php if (!empty($categorias)): ?>
        <section class="categories-section">
            <div class="container">
                <div class="row mb-5">
                    <div class="col-12 text-center">
                        <h2 class="display-6 fw-bold mb-3">Explore por Categoria</h2>
                        <p class="lead text-muted">Encontre eventos do seu interesse</p>
                    </div>
                </div>
                
                <div class="row g-4">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="?categoria=<?php echo $categoria['id_categoria']; ?>" 
                               class="text-decoration-none">
                                <div class="category-card">
                                    <div class="category-icon" style="background: <?php echo $categoria['cor'] ?? '#667eea'; ?>;">
                                        <i class="<?php echo $categoria['icone'] ?? 'fas fa-calendar'; ?>"></i>
                                    </div>
                                    <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($categoria['nome']); ?></h6>
                                    <p class="text-muted small mb-0">
                                        <?php echo htmlspecialchars($categoria['descricao'] ?? 'Eventos de ' . $categoria['nome']); ?>
                                    </p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="footer-cta">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-6 fw-bold mb-3">Pronto para Começar?</h2>
                    <p class="lead mb-4">
                        Junte-se a milhares de pessoas que já descobriram eventos incríveis através da nossa plataforma.
                    </p>
                    
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="views/auth/register.php?tipo=participante" class="btn btn-cta btn-lg">
                            <i class="fas fa-user me-2"></i>Sou Participante
                        </a>
                        <a href="views/auth/register.php?tipo=organizador" class="btn btn-cta btn-lg">
                            <i class="fas fa-calendar-plus me-2"></i>Sou Organizador
                        </a>
                    </div>
                    
                    <!-- Credenciais de Teste -->
                    <div class="mt-4">
                        <div class="card bg-white bg-opacity-10 border-0 mx-auto" style="max-width: 400px;">
                            <div class="card-body text-center">
                                <h6 class="card-title">
                                    <i class="fas fa-key me-2"></i>Testar o Sistema
                                </h6>
                                <p class="card-text mb-2 small">
                                    <strong>Email:</strong> admin@conectaeventos.com<br>
                                    <strong>Senha:</strong> admin123<br>
                                    <strong>Tipo:</strong> Organizador
                                </p>
                                <a href="views/auth/login.php" class="btn btn-outline-light btn-sm">
                                    <i class="fas fa-sign-in-alt me-1"></i>Fazer Login de Teste
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-calendar-alt me-2"></i>Conecta Eventos
                    </h5>
                    <p class="text-muted">
                        A plataforma completa para descobrir, organizar e participar dos melhores eventos.
                    </p>
                    
                    <div class="d-flex gap-3">
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>Online
                        </span>
                        <span class="badge bg-info">
                            <i class="fas fa-cloud me-1"></i>Railway
                        </span>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-code me-1"></i>PHP 8.2
                        </span>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Participantes</h6>
                    <ul class="list-unstyled">
                        <li><a href="?" class="text-muted text-decoration-none">Buscar Eventos</a></li>
                        <li><a href="views/auth/register.php" class="text-muted text-decoration-none">Criar Conta</a></li>
                        <li><a href="views/auth/login.php" class="text-muted text-decoration-none">Fazer Login</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Organizadores</h6>
                    <ul class="list-unstyled">
                        <li><a href="views/auth/register.php?tipo=organizador" class="text-muted text-decoration-none">Criar Conta</a></li>
                        <li><a href="#" class="text-muted text-decoration-none" onclick="showComingSoon()">Criar Evento</a></li>
                        <li><a href="#" class="text-muted text-decoration-none" onclick="showComingSoon()">Analytics</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Suporte</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none" onclick="showComingSoon()">Central de Ajuda</a></li>
                        <li><a href="#" class="text-muted text-decoration-none" onclick="showComingSoon()">Contato</a></li>
                        <li><a href="#" class="text-muted text-decoration-none" onclick="showComingSoon()">Termos de Uso</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Status</h6>
                    <div class="small">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></div>
                            <span class="text-muted">Sistema Online</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></div>
                            <span class="text-muted">Banco Conectado</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></div>
                            <span class="text-muted">API Funcionando</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Conecta Eventos. Desenvolvido por João Vitor da Silva.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Última atualização: <?php echo date('d/m/Y H:i'); ?> | 
                        <span class="badge bg-primary">v2.0</span>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Variáveis globais
        window.SITE_URL = '<?php echo SITE_URL; ?>';
        window.TOTAL_EVENTS = <?php echo count($eventos); ?>;
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });

        // Animações de entrada
        function animateElements() {
            const cards = document.querySelectorAll('.event-card, .category-card, .stat-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                    }
                });
            }, {
                threshold: 0.1
            });

            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                observer.observe(card);
            });
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
                max-width: 400px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                animation: slideInRight 0.3s ease-out;
            `;
            
            toast.innerHTML = `
                <i class="fas fa-${getIconForType(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }

        function getIconForType(type) {
            const icons = {
                'success': 'check-circle',
                'danger': 'exclamation-triangle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };
            return icons[type] || 'bell';
        }

        // Mensagens para funcionalidades em desenvolvimento
        function showComingSoon() {
            showToast('Esta funcionalidade estará disponível em breve!', 'info');
        }

        function showLoadMoreMessage() {
            showToast('Funcionalidade de carregamento será implementada em breve!', 'info');
        }

        // Scroll suave para âncoras
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

        // Lazy loading para imagens
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Analytics básico (apenas para demonstração)
        function trackEvent(eventName, eventData = {}) {
            console.log(`📊 Event Tracked: ${eventName}`, eventData);
            
            // Aqui você poderia integrar com Google Analytics, etc.
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, eventData);
            }
        }

        // Track page view
        trackEvent('page_view', {
            page_title: document.title,
            page_location: window.location.href,
            total_events_shown: window.TOTAL_EVENTS
        });

        // Track search actions
        document.querySelector('form').addEventListener('submit', function() {
            const searchTerm = this.querySelector('input[name="busca"]').value;
            const category = this.querySelector('select[name="categoria"]').value;
            const city = this.querySelector('input[name="cidade"]').value;
            
            trackEvent('search', {
                search_term: searchTerm,
                category: category,
                city: city
            });
        });

        // Track event card clicks
        document.querySelectorAll('.event-card a').forEach(link => {
            link.addEventListener('click', function() {
                const eventTitle = this.closest('.event-card').querySelector('.card-title').textContent;
                trackEvent('event_view', {
                    event_title: eventTitle.trim()
                });
            });
        });

        // Initialize animations
        animateElements();

        // Console info
        console.log('🎉 Conecta Eventos - Homepage Carregada');
        console.log('📊 Total de eventos:', window.TOTAL_EVENTS);
        console.log('🌐 Site URL:', window.SITE_URL);
        console.log('📱 User Agent:', navigator.userAgent);
        
        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log(`⚡ Página carregada em ${Math.round(loadTime)}ms`);
            
            trackEvent('page_performance', {
                load_time: Math.round(loadTime),
                dom_elements: document.querySelectorAll('*').length
            });
        });

        // Error handling
        window.addEventListener('error', function(e) {
            console.error('❌ Erro JavaScript:', e.error);
            trackEvent('javascript_error', {
                error_message: e.message,
                error_filename: e.filename,
                error_line: e.lineno
            });
        });

        // Service worker registration (para futuras melhorias de performance)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                // navigator.serviceWorker.register('/sw.js')
                //     .then(registration => console.log('SW registrado'))
                //     .catch(error => console.log('SW falhou'));
            });
        }
    </script>

    <!-- Styles para animações -->
    <style>
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .lazy {
            opacity: 0;
            transition: opacity 0.3s;
        }

        .loaded {
            opacity: 1;
        }

        /* Melhorias de acessibilidade */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Dark mode support (futuro) */
        @media (prefers-color-scheme: dark) {
            /* Estilos para dark mode serão implementados */
        }

        /* High contrast mode */
        @media (prefers-contrast: high) {
            .event-card {
                border: 2px solid #000;
            }
            
            .filter-pill {
                border-width: 2px;
            }
        }
    </style>
</body>
</html><?php endif; ?>
                                    
                                    <!-- Category Badge -->
                                    <?php if (!empty($evento['nome_categoria'])): ?>
                                        <span class="badge bg-primary category-badge">
                                            <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Price Badge -->
                                    <span class="badge bg-<?php echo $evento['evento_gratuito'] ? 'success' : 'warning'; ?> price-badge">
                                        <?php echo $evento['preco_formatado']; ?>
                                    </span>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($evento['titulo']); ?>
                                        <?php if ($evento['destaque']): ?>
                                            <i class="fas fa-star text-warning ms-1" title="Evento em destaque"></i>
                                        <?php endif; ?>
                                    </h5>
                                    
                                    <p class="card-text text-muted flex-grow-1">
                                        <?php echo htmlspecialchars(substr($evento['descricao'], 0, 120)); ?>...
                                    </p>
                                    
                                    <div class="event-meta mb-3">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo $evento['data_inicio_formatada']; ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $evento['horario_inicio_formatado']; ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo $evento['total_inscritos'] ?? 0; ?> inscritos
                                            <?php if ($evento['capacidade_maxima']): ?>
                                                / <?php echo $evento['capacidade_maxima']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <a href="views/events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                           class="btn btn-primary w-100">
                                            <i class="fas fa-eye me-2"></i>Ver Detalhes
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Todos os Eventos -->
    <?php if (!empty($eventos)): ?>
        <section class="py-5 bg-light">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <h2 class="display-6 fw-bold mb-3">
                            <?php if (!empty($_GET)): ?>
                                Resultados da Busca
                            <?php else: ?>
                                Todos os Eventos
                            <?php endif; ?>
                        </h2>
                        <p class="lead text-muted">
                            <?php echo count($eventos); ?> eventos encontrados
                        </p>
                    </div>
                </div>
                
                <div class="row g-4" id="eventos-list">
                    <?php foreach ($eventos as $evento): ?>
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <article class="event-card card h-100">
                                <div class="position-relative">
                                    <?php if (!empty($evento['imagem_capa'])): ?>
                                        <img src="<?php echo $evento['imagem_url']; ?>" 
                                             alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                             class="event-image"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="event-image-placeholder">
                                            <i class="fas fa-calendar-alt fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Price Badge -->
                                    <span class="badge bg-<?php echo $evento['evento_gratuito'] ? 'success' : 'warning'; ?> price-badge">
                                        <?php echo $evento['preco_formatado']; ?>
                                    </span>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title">
                                        <?php echo htmlspecialchars(strlen($evento['titulo']) > 50 ? substr($evento['titulo'], 0, 50) . '...' : $evento['titulo']); ?>
                                    </h6>
                                    
                                    <div class="event-meta mb-3 flex-grow-1">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo $evento['data_inicio_formatada']; ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                        </small>
                                        <?php if (!empty($evento['nome_categoria'])): ?>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-tag me-1"></i>
                                                <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>