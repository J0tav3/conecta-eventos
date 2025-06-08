<?php
// ========================================
// CONECTA EVENTOS - PÁGINA INICIAL
// Versão robusta que funciona mesmo com problemas de DATABASE_URL
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$title = "Conecta Eventos - Encontre os melhores eventos";

// Inicialização de variáveis
$eventos = [];
$categorias = [];
$cidades = [];
$isUserLoggedIn = false;
$userName = '';
$database_connected = false;
$connection_error = '';

// Dados de exemplo para fallback
$eventos_exemplo = [
    [
        'id_evento' => 1,
        'titulo' => 'Workshop de Desenvolvimento Web',
        'descricao' => 'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
        'data_inicio' => date('Y-m-d', strtotime('+7 days')),
        'horario_inicio' => '14:00:00',
        'local_cidade' => 'São Paulo',
        'evento_gratuito' => true,
        'preco' => 0,
        'imagem_capa' => '',
        'categoria_id' => 1,
        'nome_categoria' => 'Tecnologia'
    ],
    [
        'id_evento' => 2,
        'titulo' => 'Palestra: Empreendedorismo Digital',
        'descricao' => 'Como criar e escalar um negócio digital no mercado atual.',
        'data_inicio' => date('Y-m-d', strtotime('+10 days')),
        'horario_inicio' => '19:00:00',
        'local_cidade' => 'Rio de Janeiro',
        'evento_gratuito' => false,
        'preco' => 50.00,
        'imagem_capa' => '',
        'categoria_id' => 2,
        'nome_categoria' => 'Negócios'
    ],
    [
        'id_evento' => 3,
        'titulo' => 'Curso: Marketing Digital Avançado',
        'descricao' => 'Estratégias avançadas de marketing digital e growth hacking.',
        'data_inicio' => date('Y-m-d', strtotime('+14 days')),
        'horario_inicio' => '09:00:00',
        'local_cidade' => 'Belo Horizonte',
        'evento_gratuito' => true,
        'preco' => 0,
        'imagem_capa' => '',
        'categoria_id' => 3,
        'nome_categoria' => 'Marketing'
    ],
    [
        'id_evento' => 4,
        'titulo' => 'Meetup: Inteligência Artificial',
        'descricao' => 'Discussões sobre o futuro da IA e suas aplicações práticas.',
        'data_inicio' => date('Y-m-d', strtotime('+21 days')),
        'horario_inicio' => '18:30:00',
        'local_cidade' => 'Porto Alegre',
        'evento_gratuito' => true,
        'preco' => 0,
        'imagem_capa' => '',
        'categoria_id' => 1,
        'nome_categoria' => 'Tecnologia'
    ],
    [
        'id_evento' => 5,
        'titulo' => 'Workshop: Design UX/UI',
        'descricao' => 'Princípios fundamentais de design de experiência do usuário.',
        'data_inicio' => date('Y-m-d', strtotime('+17 days')),
        'horario_inicio' => '13:00:00',
        'local_cidade' => 'Brasília',
        'evento_gratuito' => false,
        'preco' => 75.00,
        'imagem_capa' => '',
        'categoria_id' => 4,
        'nome_categoria' => 'Design'
    ],
    [
        'id_evento' => 6,
        'titulo' => 'Conferência: Inovação e Sustentabilidade',
        'descricao' => 'Como a tecnologia pode ajudar na criação de um futuro sustentável.',
        'data_inicio' => date('Y-m-d', strtotime('+28 days')),
        'horario_inicio' => '08:00:00',
        'local_cidade' => 'Curitiba',
        'evento_gratuito' => false,
        'preco' => 120.00,
        'imagem_capa' => '',
        'categoria_id' => 5,
        'nome_categoria' => 'Sustentabilidade'
    ]
];

$categorias_exemplo = [
    ['id_categoria' => 1, 'nome' => 'Tecnologia'],
    ['id_categoria' => 2, 'nome' => 'Negócios'],
    ['id_categoria' => 3, 'nome' => 'Marketing'],
    ['id_categoria' => 4, 'nome' => 'Design'],
    ['id_categoria' => 5, 'nome' => 'Sustentabilidade'],
    ['id_categoria' => 6, 'nome' => 'Educação'],
    ['id_categoria' => 7, 'nome' => 'Entretenimento']
];

$cidades_exemplo = [
    ['local_cidade' => 'São Paulo'],
    ['local_cidade' => 'Rio de Janeiro'],
    ['local_cidade' => 'Belo Horizonte'],
    ['local_cidade' => 'Porto Alegre'],
    ['local_cidade' => 'Brasília'],
    ['local_cidade' => 'Curitiba'],
    ['local_cidade' => 'Salvador'],
    ['local_cidade' => 'Fortaleza']
];

// Tentar conexão com banco
$database_url = getenv('DATABASE_URL');
if ($database_url) {
    try {
        // Verificar se os arquivos necessários existem
        if (file_exists('config/config.php') && 
            file_exists('includes/session.php') && 
            file_exists('controllers/EventController.php')) {
            
            require_once 'config/config.php';
            require_once 'includes/session.php';
            require_once 'controllers/EventController.php';
            
            // Tentar criar o controller
            $eventController = new EventController();
            
            // Tentar buscar dados
            $eventos_db = $eventController->getPublicEvents(['limite' => 50]);
            $categorias_db = $eventController->getCategories();
            
            if (!empty($eventos_db)) {
                $eventos = $eventos_db;
                $database_connected = true;
            }
            
            if (!empty($categorias_db)) {
                $categorias = $categorias_db;
            }
            
            // Verificar usuário logado
            $isUserLoggedIn = function_exists('isLoggedIn') ? isLoggedIn() : false;
            $userName = function_exists('getUserName') ? getUserName() : '';
            
            // Buscar cidades do banco
            if (class_exists('Database')) {
                $database = new Database();
                $conn = $database->getConnection();
                if ($conn) {
                    $stmt = $conn->prepare("SELECT DISTINCT local_cidade FROM eventos WHERE status = 'publicado' AND local_cidade IS NOT NULL ORDER BY local_cidade");
                    $stmt->execute();
                    $cidades_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($cidades_db)) {
                        $cidades = $cidades_db;
                    }
                }
            }
            
        }
    } catch (Exception $e) {
        error_log("Erro ao conectar com banco: " . $e->getMessage());
        $connection_error = $e->getMessage();
    }
}

// Se não conseguiu conectar ou não há dados, usar exemplos
if (empty($eventos)) {
    $eventos = $eventos_exemplo;
}
if (empty($categorias)) {
    $categorias = $categorias_exemplo;
}
if (empty($cidades)) {
    $cidades = $cidades_exemplo;
}

// Processar filtros de busca
$searchTerm = isset($_GET['busca']) ? trim(htmlspecialchars($_GET['busca'])) : '';
$categoryFilter = isset($_GET['categoria']) ? (int)$_GET['categoria'] : '';
$cityFilter = isset($_GET['cidade']) ? trim(htmlspecialchars($_GET['cidade'])) : '';

// Aplicar filtros
$eventos_filtrados = $eventos;

if (!empty($searchTerm)) {
    $eventos_filtrados = array_filter($eventos_filtrados, function($evento) use ($searchTerm) {
        return stripos($evento['titulo'], $searchTerm) !== false || 
               stripos($evento['descricao'], $searchTerm) !== false;
    });
}

if (!empty($categoryFilter)) {
    $eventos_filtrados = array_filter($eventos_filtrados, function($evento) use ($categoryFilter) {
        return $evento['categoria_id'] == $categoryFilter;
    });
}

if (!empty($cityFilter)) {
    $eventos_filtrados = array_filter($eventos_filtrados, function($evento) use ($cityFilter) {
        return $evento['local_cidade'] === $cityFilter;
    });
}

$siteUrl = 'https://conecta-eventos-production.up.railway.app';
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
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="1" fill="white" opacity="0.1"/><circle cx="40" cy="60" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 300;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
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
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            position: relative;
        }
        
        .no-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="10" height="10" patternUnits="userSpaceOnUse"><circle cx="5" cy="5" r="1" fill="currentColor" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>') repeat;
        }
        
        .status-alert {
            background: linear-gradient(45deg, #17a2b8, #20c997);
            border: none;
            color: white;
            font-weight: 500;
        }
        
        .demo-alert {
            background: linear-gradient(45deg, #ffeaa7, #fdcb6e);
            border: none;
            color: #2d3436;
            font-weight: 500;
        }
        
        .stats-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
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
            color: #007bff;
            margin-bottom: 0.5rem;
        }
        
        .price-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
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
        
        .filters-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <?php if ($database_connected && $isUserLoggedIn): ?>
                        <span class="navbar-text me-3">
                            <i class="fas fa-user-circle me-1"></i>
                            Olá, <?php echo htmlspecialchars($userName); ?>!
                        </span>
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

    <!-- Status Alert -->
    <?php if ($database_connected): ?>
        <div class="alert status-alert alert-dismissible fade show m-0" role="alert">
            <div class="container">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-lg me-3"></i>
                    <div class="flex-grow-1">
                        <strong>Sistema Online:</strong> Conectado ao banco de dados. Todos os recursos disponíveis.
                        <a href="test.php" class="btn btn-sm btn-outline-light ms-2">Ver Status</a>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert demo-alert alert-dismissible fade show m-0" role="alert">
            <div class="container">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-lg me-3"></i>
                    <div class="flex-grow-1">
                        <strong>Modo Demonstração:</strong> 
                        Mostrando dados de exemplo. Configure DATABASE_URL para conectar ao banco real.
                        <a href="test.php" class="btn btn-sm btn-outline-dark ms-2">Ver Diagnóstico</a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Conecte-se aos melhores eventos</h1>
                    <p class="fs-5 mb-4 opacity-90">
                        Descubra experiências incríveis, aprenda coisas novas e conheça pessoas interessantes em eventos únicos.
                    </p>
                    <?php if (!$isUserLoggedIn): ?>
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
                    <!-- Busca -->
                    <div class="search-card">
                        <h4 class="text-dark mb-3">
                            <i class="fas fa-search me-2"></i>Encontre Eventos
                        </h4>
                        <form method="GET" action="">
                            <div class="row g-3">
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
                                            <option value="<?php echo (int)$categoria['id_categoria']; ?>"
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

    <!-- Filtros Avançados -->
    <?php if (!empty($searchTerm) || !empty($categoryFilter) || !empty($cityFilter)): ?>
        <div class="container">
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
                    <div class="col-md-4">
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
                    
                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="categoria">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo (int)$categoria['id_categoria']; ?>"
                                        <?php echo $categoryFilter == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Buscar</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   name="busca" 
                                   placeholder="Título ou descrição..."
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Eventos -->
    <div class="container">
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?php if (!empty($searchTerm) || !empty($categoryFilter) || !empty($cityFilter)): ?>
                        Resultados da Busca
                    <?php else: ?>
                        Eventos Disponíveis
                    <?php endif; ?>
                </h2>
                <span class="badge bg-secondary fs-6">
                    <?php echo count($eventos_filtrados); ?> evento<?php echo count($eventos_filtrados) !== 1 ? 's' : ''; ?>
                </span>
            </div>
            
            <?php if (empty($eventos_filtrados)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h4>Nenhum evento encontrado</h4>
                    <p class="text-muted mb-4">
                        <?php if (!empty($searchTerm) || !empty($categoryFilter) || !empty($cityFilter)): ?>
                            Tente ajustar os filtros ou buscar por outros termos.
                        <?php else: ?>
                            Ainda não há eventos cadastrados. Volte em breve!
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($searchTerm) || !empty($categoryFilter) || !empty($cityFilter)): ?>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Ver Todos os Eventos
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($eventos_filtrados as $evento): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card event-card">
                                <div class="price-badge <?php echo $evento['evento_gratuito'] ? '' : 'paid'; ?>">
                                    <?php echo $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.'); ?>
                                </div>
                                
                                <?php if (!empty($evento['imagem_capa']) && file_exists("uploads/eventos/" . $evento['imagem_capa'])): ?>
                                    <img src="uploads/eventos/<?php echo htmlspecialchars($evento['imagem_capa']); ?>" 
                                         alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                         class="event-image"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-calendar-alt fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                        <?php if (!empty($evento['nome_categoria'])): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($evento['nome_categoria']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="card-text text-muted">
                                        <?php echo substr(htmlspecialchars($evento['descricao']), 0, 120); ?>...
                                    </p>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?>
                                            
                                            <i class="fas fa-clock ms-3 me-1"></i>
                                            <?php echo date('H:i', strtotime($evento['horario_inicio'])); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <a href="views/events/view.php?id=<?php echo (int)$evento['id_evento']; ?>" 
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
                    <div class="col-6 col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($eventos); ?></div>
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
                                $gratuitos = array_filter($eventos, function($e) { return $e['evento_gratuito']; });
                                echo count($gratuitos); 
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
                    <h5>
                        <i class="fas fa-calendar-check me-2"></i>
                        Conecta Eventos
                    </h5>
                    <p class="mb-3">Conectando pessoas através de experiências incríveis.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white-50" aria-label="Facebook">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <a href="#" class="text-white-50" aria-label="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-white-50" aria-label="Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <a href="#" class="text-white-50" aria-label="LinkedIn">
                            <i class="fab fa-linkedin fa-lg"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">&copy; <?php echo date('Y'); ?> Conecta Eventos.</p>
                    <p class="mb-1 text-white-50">Desenvolvido com <i class="fas fa-heart text-danger"></i> por João Vitor da Silva</p>
                    <?php if (!$database_connected): ?>
                        <small class="text-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Modo Demo - Configure DATABASE_URL para funcionalidade completa
                        </small>
                    <?php else: ?>
                        <small class="text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Sistema conectado e funcionando
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 5000);
            });

            // Smooth scroll for anchor links
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

            // Loading states for forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Carregando...';
                        
                        // Re-enable after 3 seconds (in case of issues)
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 3000);
                    }
                });
            });

            // Error handling for broken images
            document.querySelectorAll('img').forEach(img => {
                img.addEventListener('error', function() {
                    this.style.display = 'none';
                    const parent = this.parentElement;
                    if (parent && !parent.querySelector('.no-image')) {
                        const fallback = document.createElement('div');
                        fallback.className = 'no-image';
                        fallback.innerHTML = '<i class="fas fa-image fa-3x"></i>';
                        parent.appendChild(fallback);
                    }
                });
            });

            // Search form enhancements
            const searchInputs = document.querySelectorAll('input[name="busca"]');
            searchInputs.forEach(input => {
                // Clear search button
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'btn btn-outline-secondary btn-sm ms-2';
                clearBtn.innerHTML = '<i class="fas fa-times"></i>';
                clearBtn.title = 'Limpar busca';
                clearBtn.style.display = input.value ? 'inline-block' : 'none';
                
                clearBtn.addEventListener('click', function() {
                    input.value = '';
                    this.style.display = 'none';
                    input.form.submit();
                });
                
                input.addEventListener('input', function() {
                    clearBtn.style.display = this.value ? 'inline-block' : 'none';
                });
                
                if (input.parentElement.tagName !== 'DIV') {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'd-flex align-items-center';
                    input.parentNode.insertBefore(wrapper, input);
                    wrapper.appendChild(input);
                    wrapper.appendChild(clearBtn);
                }
            });

            // Stats counter animation
            const statNumbers = document.querySelectorAll('.stat-number');
            const animateStats = (entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = parseInt(entry.target.textContent);
                        let current = 0;
                        const increment = target / 30;
                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= target) {
                                entry.target.textContent = target;
                                clearInterval(timer);
                            } else {
                                entry.target.textContent = Math.floor(current);
                            }
                        }, 50);
                        observer.unobserve(entry.target);
                    }
                });
            };

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(animateStats, {
                    threshold: 0.5
                });
                statNumbers.forEach(stat => observer.observe(stat));
            }

            // Add loading animation to event cards
            const eventCards = document.querySelectorAll('.event-card');
            eventCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Performance monitoring
            if ('performance' in window) {
                window.addEventListener('load', function() {
                    setTimeout(() => {
                        const perfData = performance.getEntriesByType('navigation')[0];
                        if (perfData && perfData.loadEventEnd > 5000) {
                            console.warn('Página carregou lentamente:', Math.round(perfData.loadEventEnd) + 'ms');
                        } else {
                            console.log('Página carregada em:', Math.round(perfData.loadEventEnd) + 'ms');
                        }
                    }, 0);
                });
            }
        });

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('Erro JavaScript:', e.error);
        });

        // Service Worker registration (for future PWA features)
        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('Service Worker registrado:', registration);
                    })
                    .catch(registrationError => {
                        console.log('Falha no Service Worker:', registrationError);
                    });
            });
        }
    </script>
</body>
</html>