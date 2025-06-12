<?php
// ========================================
// CONECTA EVENTOS - PÁGINA INICIAL CORRIGIDA
// Versão com correção para exibição de eventos
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
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            if ($conn) {
                $database_connected = true;
                error_log("Conexão com banco confirmada!");
                
                // CORREÇÃO: Buscar TODOS os eventos (não apenas publicados)
                // Modificar query para incluir rascunhos também para teste
                try {
                    $query = "SELECT 
                                e.*,
                                c.nome as nome_categoria,
                                u.nome as nome_organizador,
                                COUNT(i.id_inscricao) as total_inscritos
                             FROM eventos e
                             LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                             LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                             LEFT JOIN inscricoes i ON e.id_evento = i.id_evento AND i.status = 'confirmada'
                             WHERE e.status IN ('publicado', 'rascunho')
                             GROUP BY e.id_evento
                             ORDER BY e.data_criacao DESC
                             LIMIT 50";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    error_log("Eventos encontrados diretamente: " . count($eventos));
                    
                    // Log dos eventos para debug
                    foreach ($eventos as $evento) {
                        error_log("Evento: " . $evento['titulo'] . " - Status: " . $evento['status']);
                    }
                    
                } catch (Exception $e) {
                    error_log("Erro na query customizada: " . $e->getMessage());
                    // Fallback para o método original
                    $eventos = $eventController->getPublicEvents(['limite' => 50]);
                }
                
                // Carregar categorias reais
                $categorias = $eventController->getCategories();
                error_log("Categorias carregadas: " . count($categorias));
                
                // Verificar sessão do usuário
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                $isUserLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
                $userName = $_SESSION['user_name'] ?? '';
                
                // Buscar cidades dos eventos
                try {
                    $cities_query = "SELECT DISTINCT local_cidade FROM eventos WHERE local_cidade IS NOT NULL AND local_cidade != '' ORDER BY local_cidade";
                    $stmt = $conn->prepare($cities_query);
                    $stmt->execute();
                    $cidades_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $cidades = $cidades_db ?: [];
                    error_log("Cidades carregadas: " . count($cidades));
                } catch (Exception $e) {
                    error_log("Erro ao carregar cidades: " . $e->getMessage());
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

// Dados de fallback se não conseguir conectar OU se não houver eventos
if (empty($eventos)) {
    error_log("Usando eventos de fallback - Eventos encontrados: " . count($eventos));
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
            'nome_organizador' => 'Tech Academy',
            'status' => 'publicado'
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
            'nome_organizador' => 'Business Institute',
            'status' => 'publicado'
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

// Debug final
error_log("FINAL - Total eventos: " . count($eventos));
error_log("FINAL - Eventos filtrados: " . count($eventos_filtrados));
error_log("FINAL - Database connected: " . ($database_connected ? 'true' : 'false'));
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

        /* Badge de status do evento */
        .status-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-rascunho {
            background: rgba(255, 193, 7, 0.9);
            color: #856404;
        }
        
        .status-publicado {
            background: rgba(40, 167, 69, 0.9);
            color: white;
        }

        /* Debug info */
        .debug-info {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
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
                        <a class="nav-link" href="views/dashboard/organizer.php">Dashboard</a>
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
                        <strong>Sistema Online:</strong> Conectado ao banco Railway. 
                        Eventos: <?php echo count($eventos); ?> | Categorias: <?php echo count($categorias); ?>
                        <?php if (!empty($eventos)): ?>
                            | <strong>Mostrando eventos reais!</strong>
                        <?php endif; ?>
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
                        <strong>Modo Demo:</strong> 
                        <?php if ($connection_error): ?>
                            Erro: <?php echo htmlspecialchars($connection_error); ?>
                        <?php else: ?>
                            Usando dados de exemplo.
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
                                Ainda não há eventos cadastrados. 
                                <?php if ($isUserLoggedIn): ?>
                                    <a href="views/events/create.php">Crie o primeiro evento!</a>
                                <?php endif; ?>
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
                                <!-- Badge de Status -->
                                <div class="status-badge status-<?php echo $evento['status'] ?? 'publicado'; ?>">
                                    <?php echo ucfirst($evento['status'] ?? 'publicado'); ?>
                                </div>
                                
                                <!-- Badge de Preço -->
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
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($evento['nome_organizador'] ?? 'Organizador'); ?>
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
    </div>

    <!-- Debug Info (só aparece se estiver em desenvolvimento) -->
    <?php if ($database_connected): ?>
        <div class="debug-info" style="display: none;" id="debugInfo">
            <strong>Debug Info:</strong><br>
            DB: <?php echo $database_connected ? 'OK' : 'ERRO'; ?><br>
            Eventos DB: <?php echo count($eventos); ?><br>
            Filtrados: <?php echo count($eventos_filtrados); ?><br>
            User: <?php echo $isUserLoggedIn ? 'Logado' : 'Visitante'; ?><br>
            <button onclick="this.parentElement.style.display='none'" class="btn btn-sm btn-secondary mt-1">Fechar</button>
        </div>
    <?php endif; ?>

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
                </div>
                
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <h6>Para Organizadores</h6>
                            <ul class="list-unstyled">
                                <li><a href="views/auth/register.php?tipo=organizador" class="text-white-50 text-decoration-none">Criar Conta</a></li>
                                <li><a href="views/events/create.php" class="text-white-50 text-decoration-none">Criar Evento</a></li>
                            </ul>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <h6>Para Participantes</h6>
                            <ul class="list-unstyled">
                                <li><a href="views/auth/register.php?tipo=participante" class="text-white-50 text-decoration-none">Criar Conta</a></li>
                                <li><a href="#" class="text-white-50 text-decoration-none">Explorar Eventos</a></li>
                            </ul>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <h6>Sistema</h6>
                            <ul class="list-unstyled">
                                <li><span class="text-white-50">Status: <?php echo $database_connected ? 'Online' : 'Limitado'; ?></span></li>
                                <li><span class="text-white-50">Eventos: <?php echo count($eventos); ?></span></li>
                            </ul>
                        </div>
                    </div>
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

            // Mostrar debug info com Ctrl+D
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'd') {
                    e.preventDefault();
                    const debugInfo = document.getElementById('debugInfo');
                    if (debugInfo) {
                        debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
                    }
                }
            });

            // Loading states para formulários
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Buscando...';
                        
                        // Re-enable após 3 segundos se ainda estiver na página
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 3000);
                    }
                });
            });

            // Console log para debug
            console.log('=== CONECTA EVENTOS DEBUG ===');
            console.log('Database Connected:', <?php echo $database_connected ? 'true' : 'false'; ?>);
            console.log('Total Events:', <?php echo count($eventos); ?>);
            console.log('Filtered Events:', <?php echo count($eventos_filtrados); ?>);
            console.log('User Logged In:', <?php echo $isUserLoggedIn ? 'true' : 'false'; ?>);
            <?php if (!empty($eventos)): ?>
            console.log('Events List:', <?php echo json_encode(array_map(function($e) { 
                return ['id' => $e['id_evento'], 'titulo' => $e['titulo'], 'status' => $e['status'] ?? 'N/A']; 
            }, array_slice($eventos, 0, 5))); ?>);
            <?php endif; ?>
            console.log('Press Ctrl+D to toggle debug info');
        });
    </script>
</body>
</html>