<?php
// ========================================
// CONECTA EVENTOS - PÁGINA INICIAL
// Versão REAL com banco de dados Railway
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

// Tentar carregar dependências
$dependencies_loaded = false;
try {
    if (file_exists('config/config.php')) {
        require_once 'config/config.php';
    }
    if (file_exists('includes/session.php')) {
        require_once 'includes/session.php';
    }
    if (file_exists('controllers/EventController.php')) {
        require_once 'controllers/EventController.php';
    }
    $dependencies_loaded = true;
} catch (Exception $e) {
    error_log("Erro ao carregar dependências: " . $e->getMessage());
}

// Verificar conexão com banco e carregar dados
if ($dependencies_loaded) {
    try {
        // Verificar se temos acesso ao banco
        $database_url = getenv('DATABASE_URL');
        if ($database_url) {
            error_log("DATABASE_URL encontrada: " . substr($database_url, 0, 20) . "...");
            
            // Tentar criar controller de eventos
            $eventController = new EventController();
            
            // Testar conexão básica
            $test_query = "SELECT 1 as test";
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            if ($conn) {
                $stmt = $conn->prepare($test_query);
                if ($stmt && $stmt->execute()) {
                    $database_connected = true;
                    error_log("Conexão com banco confirmada!");
                    
                    // Carregar eventos reais
                    $eventos = $eventController->getPublicEvents(['limite' => 50]);
                    error_log("Eventos carregados: " . count($eventos));
                    
                    // Carregar categorias reais
                    $categorias = $eventController->getCategories();
                    error_log("Categorias carregadas: " . count($categorias));
                    
                    // Verificar sessão do usuário
                    if (function_exists('isLoggedIn') && function_exists('getUserName')) {
                        $isUserLoggedIn = isLoggedIn();
                        $userName = getUserName() ?: '';
                    }
                    
                    // Buscar cidades dos eventos
                    try {
                        $cities_query = "SELECT DISTINCT local_cidade FROM eventos WHERE status = 'publicado' AND local_cidade IS NOT NULL AND local_cidade != '' ORDER BY local_cidade";
                        $stmt = $conn->prepare($cities_query);
                        $stmt->execute();
                        $cidades_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $cidades = $cidades_db ?: [];
                        error_log("Cidades carregadas: " . count($cidades));
                    } catch (Exception $e) {
                        error_log("Erro ao carregar cidades: " . $e->getMessage());
                    }
                } else {
                    error_log("Falha no teste de query");
                }
            } else {
                error_log("Conexão PDO é null");
            }
        } else {
            error_log("DATABASE_URL não encontrada");
            $connection_error = "DATABASE_URL não configurada";
        }
    } catch (Exception $e) {
        error_log("Erro na conexão com banco: " . $e->getMessage());
        $connection_error = $e->getMessage();
    }
}

// Dados de fallback se não conseguir conectar
if (empty($eventos)) {
    error_log("Usando eventos de fallback");
    $eventos = [
        [
            'id_evento' => 1,
            'titulo' => 'Workshop de Desenvolvimento Web',
            'descricao' => 'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
            'data_inicio' => date('Y-m-d', strtotime('+7 days')),
            'horario_inicio' => '14:00:00',
            'local_cidade' => 'São Paulo',
            'evento_gratuito' => 1,
            'preco' => 0,
            'imagem_capa' => '',
            'id_categoria' => 1,
            'nome_categoria' => 'Tecnologia',
            'total_inscritos' => 45,
            'nome_organizador' => 'Tech Academy'
        ],
        [
            'id_evento' => 2,
            'titulo' => 'Palestra: Empreendedorismo Digital',
            'descricao' => 'Como criar e escalar um negócio digital no mercado atual.',
            'data_inicio' => date('Y-m-d', strtotime('+10 days')),
            'horario_inicio' => '19:00:00',
            'local_cidade' => 'Rio de Janeiro',
            'evento_gratuito' => 0,
            'preco' => 50.00,
            'imagem_capa' => '',
            'id_categoria' => 2,
            'nome_categoria' => 'Negócios',
            'total_inscritos' => 32,
            'nome_organizador' => 'Business Institute'
        ]
    ];
}

if (empty($categorias)) {
    $categorias = [
        ['id_categoria' => 1, 'nome' => 'Tecnologia'],
        ['id_categoria' => 2, 'nome' => 'Negócios'],
        ['id_categoria' => 3, 'nome' => 'Marketing'],
        ['id_categoria' => 4, 'nome' => 'Design'],
        ['id_categoria' => 5, 'nome' => 'Educação']
    ];
}

if (empty($cidades)) {
    $cidades = [
        ['local_cidade' => 'São Paulo'],
        ['local_cidade' => 'Rio de Janeiro'],
        ['local_cidade' => 'Belo Horizonte'],
        ['local_cidade' => 'Porto Alegre'],
        ['local_cidade' => 'Brasília']
    ];
}

// Processar filtros de busca
$searchTerm = isset($_GET['busca']) ? trim(htmlspecialchars($_GET['busca'])) : '';
$categoryFilter = isset($_GET['categoria']) ? (int)$_GET['categoria'] : '';
$cityFilter = isset($_GET['cidade']) ? trim(htmlspecialchars($_GET['cidade'])) : '';

// Aplicar filtros se houver dados
$eventos_filtrados = $eventos;

if (!empty($searchTerm)) {
    $eventos_filtrados = array_filter($eventos_filtrados, function($evento) use ($searchTerm) {
        return stripos($evento['titulo'], $searchTerm) !== false || 
               stripos($evento['descricao'], $searchTerm) !== false;
    });
}

if (!empty($categoryFilter)) {
    $eventos_filtrados = array_filter($eventos_filtrados, function($evento) use ($categoryFilter) {
        return (int)$evento['id_categoria'] === $categoryFilter;
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

        .navbar-brand:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
                    <?php if ($isUserLoggedIn): ?>
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
                        <strong>Sistema Online:</strong> Conectado ao banco de dados Railway. Todos os recursos disponíveis.
                        <small class="d-block">Eventos: <?php echo count($eventos); ?> | Categorias: <?php echo count($categorias); ?></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert demo-alert alert-dismissible fade show m-0" role="alert">
            <div class="container">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
                    <div class="flex-grow-1">
                        <strong>Modo Limitado:</strong> 
                        <?php if ($connection_error): ?>
                            Erro de conexão: <?php echo htmlspecialchars($connection_error); ?>
                        <?php else: ?>
                            Usando dados de exemplo. Verifique a configuração do banco.
                        <?php endif; ?>
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
                            <?php if ($database_connected): ?>
                                Ainda não há eventos cadastrados. Volte em breve!
                            <?php else: ?>
                                Problemas de conexão com o banco de dados.
                            <?php endif; ?>
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
                                    <?php if ($evento['evento_gratuito']): ?>
                                        Gratuito
                                    <?php else: ?>
                                        R$ <?php echo number_format($evento['preco'], 2, ',', '.'); ?>
                                    <?php endif; ?>
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
                                            
                                            <i class="fas fa-users ms-3 me-1"></i>
                                            <?php echo (int)($evento['total_inscritos'] ?? 0); ?> inscritos
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
                            <div class="stat-number"><?php echo array_sum(array_column($eventos, 'total_inscritos')); ?></div>
                            <div class="stat-label">Total Participantes</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seção de CTA -->
        <?php if (!$isUserLoggedIn): ?>
        <section class="mb-5">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-users fa-3x text-primary"></i>
                            </div>
                            <h4>Para Participantes</h4>
                            <p class="text-muted">Descubra eventos incríveis, conecte-se com pessoas interessantes e aprenda coisas novas.</p>
                            <ul class="list-unstyled text-start">
                                <li><i class="fas fa-check text-success me-2"></i>Inscrição em eventos gratuitos e pagos</li>
                                <li><i class="fas fa-check text-success me-2"></i>Networking com outros participantes</li>
                                <li><i class="fas fa-check text-success me-2"></i>Certificados de participação</li>
                                <li><i class="fas fa-check text-success me-2"></i>Sistema de favoritos</li>
                            </ul>
                            <a href="views/auth/register.php?tipo=participante" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Cadastrar como Participante
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-calendar-plus fa-3x text-success"></i>
                            </div>
                            <h4>Para Organizadores</h4>
                            <p class="text-muted">Crie e gerencie seus eventos, alcance seu público-alvo e construa uma comunidade.</p>
                            <ul class="list-unstyled text-start">
                                <li><i class="fas fa-check text-success me-2"></i>Criação ilimitada de eventos</li>
                                <li><i class="fas fa-check text-success me-2"></i>Gestão completa de participantes</li>
                                <li><i class="fas fa-check text-success me-2"></i>Relatórios e analytics</li>
                                <li><i class="fas fa-check text-success me-2"></i>Sistema de pagamentos</li>
                            </ul>
                            <a href="views/auth/register.php?tipo=organizador" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Cadastrar como Organizador
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Categorias em Destaque -->
        <section class="mb-5">
            <h2 class="text-center mb-5">
                <i class="fas fa-tags me-2"></i>Explore por Categoria
            </h2>
            <div class="row">
                <?php 
                $categoria_icons = [
                    'Tecnologia' => 'fas fa-laptop-code',
                    'Negócios' => 'fas fa-briefcase',
                    'Marketing' => 'fas fa-bullhorn',
                    'Design' => 'fas fa-palette',
                    'Educação' => 'fas fa-graduation-cap',
                    'Saúde' => 'fas fa-heartbeat',
                    'Arte' => 'fas fa-paint-brush',
                    'Esporte' => 'fas fa-running',
                    'Música' => 'fas fa-music',
                    'Culinária' => 'fas fa-utensils'
                ];
                
                foreach ($categorias as $index => $categoria): 
                    if ($index >= 6) break; // Mostrar apenas 6 categorias
                    $icon = $categoria_icons[$categoria['nome']] ?? 'fas fa-calendar';
                ?>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <a href="?categoria=<?php echo $categoria['id_categoria']; ?>" 
                           class="text-decoration-none">
                            <div class="card text-center h-100 border-0 shadow-sm categoria-card">
                                <div class="card-body py-4">
                                    <i class="<?php echo $icon; ?> fa-2x text-primary mb-3"></i>
                                    <h6 class="card-title"><?php echo htmlspecialchars($categoria['nome']); ?></h6>
                                    <small class="text-muted">
                                        <?php 
                                        $eventos_categoria = array_filter($eventos, function($e) use ($categoria) {
                                            return $e['id_categoria'] == $categoria['id_categoria'];
                                        });
                                        echo count($eventos_categoria);
                                        ?> eventos
                                    </small>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Próximos Eventos em Destaque -->
        <?php 
        $eventos_destaque = array_slice($eventos, 0, 3);
        if (!empty($eventos_destaque)): 
        ?>
        <section class="mb-5">
            <h2 class="text-center mb-5">
                <i class="fas fa-star me-2"></i>Próximos Eventos em Destaque
            </h2>
            <div class="row">
                <?php foreach ($eventos_destaque as $evento): ?>
                    <div class="col-lg-4 mb-4">
                        <div class="card event-card-destaque h-100 border-0">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($evento['nome_categoria'] ?? 'Evento'); ?></span>
                                    <span class="badge <?php echo $evento['evento_gratuito'] ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.'); ?>
                                    </span>
                                </div>
                                
                                <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                <p class="card-text text-muted">
                                    <?php echo substr(htmlspecialchars($evento['descricao']), 0, 100); ?>...
                                </p>
                                
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-calendar text-primary me-2"></i>
                                        <small><?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?></small>
                                        <i class="fas fa-clock text-primary ms-3 me-2"></i>
                                        <small><?php echo date('H:i', strtotime($evento['horario_inicio'])); ?></small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <small><?php echo htmlspecialchars($evento['local_cidade']); ?></small>
                                        <i class="fas fa-users text-primary ms-3 me-2"></i>
                                        <small><?php echo $evento['total_inscritos']; ?> inscritos</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <a href="views/events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                       class="btn btn-outline-primary">
                                        Ver Detalhes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center">
                <a href="?todos=1" class="btn btn-primary btn-lg">
                    <i class="fas fa-calendar-alt me-2"></i>Ver Todos os Eventos
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- Newsletter -->
        <section class="mb-5">
            <div class="card border-0 shadow" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center text-white py-5">
                    <h3 class="mb-3">
                        <i class="fas fa-envelope me-2"></i>Fique por dentro dos melhores eventos
                    </h3>
                    <p class="lead mb-4">
                        Receba notificações sobre novos eventos na sua cidade e área de interesse.
                    </p>
                    
                    <?php if (!$isUserLoggedIn): ?>
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <form class="d-flex gap-2">
                                    <input type="email" class="form-control" placeholder="Seu melhor e-mail" required>
                                    <button type="submit" class="btn btn-light">
                                        <i class="fas fa-bell me-1"></i>Notificar
                                    </button>
                                </form>
                                <small class="text-white-50 mt-2 d-block">
                                    Sem spam. Cancele quando quiser.
                                </small>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="views/dashboard/settings.php" class="btn btn-light btn-lg">
                            <i class="fas fa-cog me-2"></i>Configurar Notificações
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>
                        <i class="fas fa-calendar-check me-2"></i>
                        Conecta Eventos
                    </h5>
                    <p class="text-white-50">
                        Conectando pessoas através de experiências incríveis. 
                        A melhor plataforma para descobrir e organizar eventos.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white-50 fs-4">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="text-white-50 fs-4">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-white-50 fs-4">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-white-50 fs-4">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Eventos</h6>
                    <ul class="list-unstyled">
                        <li><a href="?categoria=1" class="text-white-50 text-decoration-none">Tecnologia</a></li>
                        <li><a href="?categoria=2" class="text-white-50 text-decoration-none">Negócios</a></li>
                        <li><a href="?categoria=3" class="text-white-50 text-decoration-none">Marketing</a></li>
                        <li><a href="?categoria=4" class="text-white-50 text-decoration-none">Design</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Para Organizadores</h6>
                    <ul class="list-unstyled">
                        <li><a href="views/auth/register.php?tipo=organizador" class="text-white-50 text-decoration-none">Criar Conta</a></li>
                        <li><a href="views/events/create.php" class="text-white-50 text-decoration-none">Criar Evento</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Preços</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Recursos</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Suporte</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">Central de Ajuda</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Contato</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">FAQ</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Termos de Uso</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Empresa</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">Sobre Nós</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Carreira</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Imprensa</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Blog</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-white-50">
                        &copy; <?php echo date('Y'); ?> Conecta Eventos. Todos os direitos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-white-50">
                        Desenvolvido por <strong>João Vitor da Silva</strong>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animação das estatísticas
            const statNumbers = document.querySelectorAll('.stat-number');
            const observer = new IntersectionObserver((entries) => {
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
            });
            
            statNumbers.forEach(stat => observer.observe(stat));

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 8000);
            });

            // Hover effects para cards de categoria
            const categoriaCards = document.querySelectorAll('.categoria-card');
            categoriaCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });

            // Loading states para formulários
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Carregando...';
                        
                        // Re-enable após 5 segundos se ainda estiver na página
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 5000);
                    }
                });
            });

            // Smooth scroll para links internos
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

            // Parallax effect para hero section
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const parallax = document.querySelector('.hero-section');
                if (parallax) {
                    const speed = scrolled * 0.5;
                    parallax.style.transform = `translateY(${speed}px)`;
                }
            });

            // Toast notifications system
            window.showToast = function(message, type = 'info', duration = 5000) {
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
                toast.setAttribute('role', 'alert');
                toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                `;
                
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.parentElement.parentElement.remove()"></button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.remove();
                    }
                }, duration);
            };

            // Debug info no console
            console.log('%c🎉 Conecta Eventos', 'color: #667eea; font-size: 2em; font-weight: bold;');
            console.log('Sistema carregado com sucesso!');
            console.log('Eventos disponíveis:', <?php echo count($eventos); ?>);
            console.log('Banco conectado:', <?php echo $database_connected ? 'true' : 'false'; ?>);
        });
    </script>

    <style>
        .categoria-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .event-card-destaque {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .event-card-destaque:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .hero-section {
            min-height: 70vh;
            display: flex;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .search-card {
                padding: 1.5rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
        
        /* Melhorias de acessibilidade */
        .btn:focus,
        .form-control:focus,
        .form-select:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
        
        /* Preloader */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease;
        }
        
        .page-loader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <!-- Preloader -->
    <div class="page-loader" id="pageLoader">
        <div class="text-center text-white">
            <div class="loader-spinner mx-auto mb-3"></div>
            <div>Carregando eventos...</div>
        </div>
    </div>

    <script>
        // Hide preloader when page is fully loaded
        window.addEventListener('load', function() {
            const loader = document.getElementById('pageLoader');
            if (loader) {
                setTimeout(() => {
                    loader.classList.add('hidden');
                    setTimeout(() => loader.remove(), 500);
                }, 500);
            }
        });
    </script>
</body>
</html>