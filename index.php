<?php
// ===========================================
// INDEX.PHP DE EMERGÊNCIA - VERSÃO MÍNIMA
// ===========================================
// Use esta versão para voltar o site ao ar rapidamente

// Desabilitar exibição de erros em produção
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Definir título
$title = "Conecta Eventos - Encontre os melhores eventos";

// Verificar se arquivos essenciais existem
$hasConfig = file_exists('config/config.php');
$hasSession = file_exists('includes/session.php');
$hasController = file_exists('controllers/EventController.php');

// Tentar carregar apenas se existirem
$eventos = [];
$categorias = [];
$cidades = [];
$isUserLoggedIn = false;
$userName = '';

if ($hasConfig && $hasSession && $hasController) {
    try {
        require_once 'config/config.php';
        require_once 'includes/session.php';
        require_once 'controllers/EventController.php';
        
        $eventController = new EventController();
        $eventos = $eventController->getPublicEvents(['limite' => 12]);
        $categorias = $eventController->getCategories();
        
        $isUserLoggedIn = function_exists('isLoggedIn') ? isLoggedIn() : false;
        $userName = function_exists('getUserName') ? getUserName() : '';
        
        // Buscar cidades
        if (class_exists('Database')) {
            $database = new Database();
            $conn = $database->getConnection();
            if ($conn) {
                $stmt = $conn->prepare("SELECT DISTINCT local_cidade FROM eventos WHERE status = 'publicado' AND local_cidade IS NOT NULL ORDER BY local_cidade");
                $stmt->execute();
                $cidades = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao carregar dados: " . $e->getMessage());
        // Continue com arrays vazios
    }
}

// Processar filtros de busca
$searchTerm = isset($_GET['busca']) ? trim(htmlspecialchars($_GET['busca'])) : '';
$categoryFilter = isset($_GET['categoria']) ? (int)$_GET['categoria'] : '';

// Filtrar eventos se houver busca
if (!empty($searchTerm) && !empty($eventos)) {
    $eventos = array_filter($eventos, function($evento) use ($searchTerm) {
        return stripos($evento['titulo'], $searchTerm) !== false || 
               stripos($evento['descricao'], $searchTerm) !== false;
    });
}

if (!empty($categoryFilter) && !empty($eventos)) {
    $eventos = array_filter($eventos, function($evento) use ($categoryFilter) {
        return $evento['categoria_id'] == $categoryFilter;
    });
}

// URL do site
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
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 300;
            margin-bottom: 1rem;
        }
        
        .search-card {
            background: rgba(255, 255, 255, 0.95);
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
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $siteUrl; ?>">
                <strong>Conecta Eventos</strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <?php if ($isUserLoggedIn): ?>
                        <span class="navbar-text me-3">
                            Olá, <?php echo htmlspecialchars($userName); ?>!
                        </span>
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
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Conecte-se aos melhores eventos</h1>
                    <p class="fs-5 mb-4">
                        Descubra experiências incríveis, aprenda coisas novas e conheça pessoas interessantes.
                    </p>
                    <?php if (!$isUserLoggedIn): ?>
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
                    <!-- Busca -->
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
                    <?php echo !empty($searchTerm) || !empty($categoryFilter) ? 'Resultados da Busca' : 'Eventos Disponíveis'; ?>
                </h2>
                <span class="badge bg-secondary fs-6">
                    <?php echo count($eventos); ?> eventos
                </span>
            </div>
            
            <?php if (empty($eventos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h4>Nenhum evento encontrado</h4>
                    <p class="text-muted">
                        <?php if (!empty($searchTerm) || !empty($categoryFilter)): ?>
                            Tente ajustar os filtros ou buscar por outros termos.
                        <?php else: ?>
                            Ainda não há eventos cadastrados.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($searchTerm) || !empty($categoryFilter)): ?>
                        <a href="index.php" class="btn btn-primary">Ver Todos os Eventos</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($eventos as $evento): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card event-card">
                                <?php if (!empty($evento['imagem_capa'])): ?>
                                    <img src="uploads/eventos/<?php echo htmlspecialchars($evento['imagem_capa']); ?>" 
                                         alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                         class="event-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-calendar-alt fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                    
                                    <p class="card-text text-muted">
                                        <?php echo substr(htmlspecialchars($evento['descricao']), 0, 100); ?>...
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
                                            
                                            <i class="fas fa-dollar-sign ms-3 me-1"></i>
                                            <?php echo $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.'); ?>
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

        <!-- Stats -->
        <section class="bg-light py-5 mt-5">
            <div class="container">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h3 class="text-primary"><?php echo count($eventos); ?></h3>
                        <p>Eventos Disponíveis</p>
                    </div>
                    <div class="col-md-4">
                        <h3 class="text-primary"><?php echo count($categorias); ?></h3>
                        <p>Categorias</p>
                    </div>
                    <div class="col-md-4">
                        <h3 class="text-primary"><?php echo count($cidades); ?></h3>
                        <p>Cidades</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Conecta Eventos. Desenvolvido por João Vitor da Silva.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>