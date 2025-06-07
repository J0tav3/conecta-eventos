<?php
// ========================================
// PÁGINA PRINCIPAL - CONECTA EVENTOS
// ========================================
// Local: conecta-eventos/index.php
// ========================================

require_once 'config/config.php';
require_once 'includes/session.php';
require_once 'controllers/EventController.php';

$title = SITE_NAME . " - Plataforma de Eventos";
$eventController = new EventController();

// Verificar se há mensagem de logout via cookie
$logoutMessage = '';
if (isset($_COOKIE['logout_message'])) {
    $logoutMessage = $_COOKIE['logout_message'];
    $logoutType = $_COOKIE['logout_type'] ?? 'info';
    // Limpar cookies
    setcookie('logout_message', '', time() - 3600, '/');
    setcookie('logout_type', '', time() - 3600, '/');
}

// Obter eventos em destaque
try {
    $eventosDestaque = $eventController->getPublicEvents(['limite' => 6, 'ordem' => 'data_inicio']);
} catch (Exception $e) {
    $eventosDestaque = [];
    error_log("Erro ao buscar eventos: " . $e->getMessage());
}

// Processar busca
$filtros = [];
if (!empty($_GET['busca'])) {
    $filtros['busca'] = $_GET['busca'];
}
if (!empty($_GET['cidade'])) {
    $filtros['cidade'] = $_GET['cidade'];
}
if (!empty($_GET['categoria'])) {
    $filtros['categoria_id'] = $_GET['categoria'];
}

$eventosBusca = [];
if (!empty($filtros)) {
    try {
        $eventosBusca = $eventController->getPublicEvents($filtros);
    } catch (Exception $e) {
        error_log("Erro na busca: " . $e->getMessage());
    }
}

// Obter categorias para o filtro
try {
    $categorias = $eventController->getCategories();
} catch (Exception $e) {
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 70vh;
            display: flex;
            align-items: center;
        }
        .event-card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .event-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .no-image {
            height: 200px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        .category-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 2;
        }
        .search-section {
            background: white;
            padding: 2rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .btn-hero {
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 2rem;
            transition: all 0.3s ease;
        }
        .btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-calendar-alt me-2"></i>
                Conecta Eventos
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#eventos">Eventos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#categorias">Categorias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#sobre">Sobre</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars(getUserName()); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (isOrganizer()): ?>
                                    <li><a class="dropdown-item" href="views/dashboard/organizer.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="views/events/create.php">
                                        <i class="fas fa-plus me-2"></i>Criar Evento
                                    </a></li>
                                    <li><a class="dropdown-item" href="views/events/list.php">
                                        <i class="fas fa-list me-2"></i>Meus Eventos
                                    </a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="views/dashboard/participant.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Meu Painel
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
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

    <!-- Mensagem de Logout -->
    <?php if ($logoutMessage): ?>
        <div class="alert alert-<?php echo $logoutType; ?> alert-dismissible fade show mb-0" role="alert">
            <div class="container">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($logoutMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 mb-4">
                        Descubra Eventos <br>
                        <span class="text-warning">Incríveis</span>
                    </h1>
                    <p class="lead mb-4">
                        Conecte-se com experiências únicas na sua região. 
                        Participe de eventos ou organize o seu próprio!
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (!isLoggedIn()): ?>
                            <a href="views/auth/register.php" class="btn btn-warning btn-hero">
                                <i class="fas fa-user-plus me-2"></i>
                                Criar Conta Gratuita
                            </a>
                        <?php endif; ?>
                        <a href="#eventos" class="btn btn-outline-light btn-hero">
                            <i class="fas fa-search me-2"></i>
                            Explorar Eventos
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="row">
                        <div class="col-4">
                            <div class="feature-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h5>Eventos Únicos</h5>
                            <p>Encontre experiências especiais</p>
                        </div>
                        <div class="col-4">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5>Comunidade</h5>
                            <p>Conecte-se com pessoas</p>
                        </div>
                        <div class="col-4">
                            <div class="feature-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h5>Qualidade</h5>
                            <p>Eventos avaliados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção de Busca -->
    <section class="search-section" id="busca">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form method="GET" action="#eventos" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="busca" class="form-label">
                                <i class="fas fa-search me-1"></i>Buscar Eventos
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="busca" 
                                   name="busca" 
                                   placeholder="Digite palavras-chave..."
                                   value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="cidade" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Cidade
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cidade" 
                                   name="cidade" 
                                   placeholder="Qualquer cidade"
                                   value="<?php echo htmlspecialchars($_GET['cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="categoria" class="form-label">
                                <i class="fas fa-tag me-1"></i>Categoria
                            </label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="">Todas as categorias</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>"
                                            <?php echo ($_GET['categoria'] ?? '') == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Resultados da Busca -->
    <?php if (!empty($filtros)): ?>
        <section class="py-5" id="resultados">
            <div class="container">
                <h2 class="text-center mb-4">
                    <i class="fas fa-search me-2"></i>
                    Resultados da Busca
                    <small class="text-muted">(<?php echo count($eventosBusca); ?> encontrados)</small>
                </h2>
                
                <?php if (empty($eventosBusca)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>Nenhum evento encontrado</h4>
                        <p class="text-muted">Tente ajustar os filtros de busca.</p>
                        <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Ver Todos os Eventos
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($eventosBusca as $evento): ?>
                            <?php $evento = $eventController->formatEventForDisplay($evento); ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card event-card h-100">
                                    <div class="position-relative">
                                        <?php if (!empty($evento['imagem_capa'])): ?>
                                            <img src="<?php echo $evento['imagem_url']; ?>" 
                                                 alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                                 class="event-image">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image fa-3x"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($evento['nome_categoria']): ?>
                                            <span class="badge bg-primary category-badge">
                                                <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                        <p class="card-text text-muted flex-grow-1">
                                            <?php echo substr(htmlspecialchars($evento['descricao']), 0, 100) . '...'; ?>
                                        </p>
                                        
                                        <div class="event-details mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-calendar me-2 text-primary"></i>
                                                <small><?php echo $evento['data_inicio_formatada']; ?></small>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-clock me-2 text-primary"></i>
                                                <small><?php echo $evento['horario_inicio_formatado']; ?></small>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                                <small><?php echo htmlspecialchars($evento['local_cidade']); ?></small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-money-bill me-2 text-primary"></i>
                                                <small class="fw-bold"><?php echo $evento['preco_formatado']; ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-auto">
                                            <a href="views/events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                               class="btn btn-primary w-100">
                                                <i class="fas fa-eye me-2"></i>Ver Detalhes
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Eventos em Destaque -->
    <section class="py-5 bg-light" id="eventos">
        <div class="container">
            <div class="text-center mb-5">
                <h2>
                    <i class="fas fa-star me-2"></i>
                    <?php echo !empty($filtros) ? 'Outros Eventos' : 'Eventos em Destaque'; ?>
                </h2>
                <p class="text-muted">Descubra experiências incríveis na sua região</p>
            </div>
            
            <?php if (empty($eventosDestaque)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h4>Nenhum evento disponível</h4>
                    <p class="text-muted">Novos eventos serão publicados em breve!</p>
                    <?php if (isLoggedIn() && isOrganizer()): ?>
                        <a href="views/events/create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Criar Primeiro Evento
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($eventosDestaque as $evento): ?>
                        <?php $evento = $eventController->formatEventForDisplay($evento); ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card event-card h-100">
                                <div class="position-relative">
                                    <?php if (!empty($evento['imagem_capa'])): ?>
                                        <img src="<?php echo $evento['imagem_url']; ?>" 
                                             alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                             class="event-image">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image fa-3x"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($evento['nome_categoria']): ?>
                                        <span class="badge bg-primary category-badge">
                                            <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                    <p class="card-text text-muted flex-grow-1">
                                        <?php echo substr(htmlspecialchars($evento['descricao']), 0, 100) . '...'; ?>
                                    </p>
                                    
                                    <div class="event-details mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-calendar me-2 text-primary"></i>
                                            <small><?php echo $evento['data_inicio_formatada']; ?></small>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-clock me-2 text-primary"></i>
                                            <small><?php echo $evento['horario_inicio_formatado']; ?></small>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                            <small><?php echo htmlspecialchars($evento['local_cidade']); ?></small>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-users me-2 text-primary"></i>
                                            <small><?php echo $evento['total_inscritos'] ?? 0; ?> inscritos</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-money-bill me-2 text-primary"></i>
                                            <small class="fw-bold"><?php echo $evento['preco_formatado']; ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <a href="views/events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                           class="btn btn-primary w-100">
                                            <i class="fas fa-eye me-2"></i>Ver Detalhes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">Quer ver mais eventos? Use os filtros de busca acima!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categorias -->
    <section class="py-5" id="categorias">
        <div class="container">
            <div class="text-center mb-5">
                <h2><i class="fas fa-tags me-2"></i>Categorias</h2>
                <p class="text-muted">Encontre eventos por categoria de interesse</p>
            </div>
            
            <?php if (!empty($categorias)): ?>
                <div class="row">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="col-lg-2 col-md-4 col-6 mb-4">
                            <a href="?categoria=<?php echo $categoria['id_categoria']; ?>#eventos" 
                               class="text-decoration-none">
                                <div class="card text-center h-100 category-card" 
                                     style="border-left: 4px solid <?php echo $categoria['cor']; ?>;">
                                    <div class="card-body">
                                        <i class="<?php echo $categoria['icone']; ?> fa-2x mb-3" 
                                           style="color: <?php echo $categoria['cor']; ?>;"></i>
                                        <h6 class="card-title"><?php echo htmlspecialchars($categoria['nome']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($categoria['descricao']); ?></small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Seção Como Funciona -->
    <section class="py-5 bg-light" id="sobre">
        <div class="container">
            <div class="text-center mb-5">
                <h2><i class="fas fa-question-circle me-2"></i>Como Funciona</h2>
                <p class="text-muted">É simples participar ou organizar eventos</p>
            </div>
            
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h4>Para Participantes</h4>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Cadastre-se gratuitamente</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Busque eventos por categoria ou localização</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Inscreva-se nos eventos de seu interesse</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Receba confirmações e lembretes</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Avalie os eventos que participou</li>
                            </ul>
                            <?php if (!isLoggedIn()): ?>
                                <a href="views/auth/register.php?tipo=participante" class="btn btn-success">
                                    <i class="fas fa-user-plus me-2"></i>Cadastrar como Participante
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 h-100">
                        <div class="card-body text-center">
                            <div class="feature-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <h4>Para Organizadores</h4>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>Crie sua conta de organizador</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>Publique eventos facilmente</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>Gerencie inscrições e participantes</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>Acompanhe estatísticas em tempo real</li>
                                <li class="mb-2"><i class="fas fa-check text-primary me-2"></i>Receba avaliações dos participantes</li>
                            </ul>
                            <?php if (!isLoggedIn()): ?>
                                <a href="views/auth/register.php?tipo=organizador" class="btn btn-primary">
                                    <i class="fas fa-calendar-plus me-2"></i>Cadastrar como Organizador
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-calendar-alt me-2"></i>Conecta Eventos</h5>
                    <p class="text-muted">
                        Plataforma completa para descobrir e organizar eventos incríveis.
                        Conecte-se com experiências únicas na sua região.
                    </p>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Participantes</h6>
                    <ul class="list-unstyled">
                        <li><a href="#eventos" class="text-muted text-decoration-none">Buscar Eventos</a></li>
                        <li><a href="#categorias" class="text-muted text-decoration-none">Categorias</a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li><a href="views/auth/register.php" class="text-muted text-decoration-none">Cadastrar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Organizadores</h6>
                    <ul class="list-unstyled">
                        <?php if (isLoggedIn() && isOrganizer()): ?>
                            <li><a href="views/events/create.php" class="text-muted text-decoration-none">Criar Evento</a></li>
                            <li><a href="views/dashboard/organizer.php" class="text-muted text-decoration-none">Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="views/auth/register.php" class="text-muted text-decoration-none">Cadastrar</a></li>
                            <li><a href="#sobre" class="text-muted text-decoration-none">Como Funciona</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <h6>Estatísticas</h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h4 text-warning"><?php echo count($eventosDestaque); ?></div>
                            <small class="text-muted">Eventos Ativos</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-warning"><?php echo count($categorias); ?></div>
                            <small class="text-muted">Categorias</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Conecta Eventos. Desenvolvido por João Vitor da Silva.</p>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        <i class="fas fa-code me-1"></i>
                        Sistema de Gestão de Eventos
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Smooth scrolling para links âncora
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

        // Animação de entrada dos cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Aplicar animação aos cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.event-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                card.style.transitionDelay = `${index * 0.1}s`;
                observer.observe(card);
            });
        });
    </script>
</body>
</html>