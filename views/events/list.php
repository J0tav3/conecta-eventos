<?php
// ==========================================
// MEUS EVENTOS - VERSÃO COMPLETA COM AÇÕES
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
$userId = $_SESSION['user_id'] ?? 0;

// URLs
$dashboardUrl = '../dashboard/organizer.php';
$homeUrl = '../../index.php';
$createEventUrl = 'create.php';

// Filtros
$status_filter = $_GET['status'] ?? '';
$categoria_filter = $_GET['categoria'] ?? '';

// Buscar eventos do organizador atual
$meus_eventos = [];
$error_message = '';
$success_message = '';

// Verificar se houve uma ação realizada
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'published':
            $success_message = "Evento publicado com sucesso!";
            break;
        case 'unpublished':
            $success_message = "Evento despublicado com sucesso!";
            break;
        case 'deleted':
            $success_message = "Evento excluído com sucesso!";
            break;
    }
}

try {
    require_once '../../controllers/EventController.php';
    $eventController = new EventController();
    
    $filters = [];
    if (!empty($status_filter)) {
        $filters['status'] = $status_filter;
    }
    if (!empty($categoria_filter)) {
        $filters['categoria'] = $categoria_filter;
    }
    
    $meus_eventos = $eventController->getEventsByOrganizer($userId, $filters);
    
    error_log("Eventos encontrados para usuário $userId: " . count($meus_eventos));
    
} catch (Exception $e) {
    error_log("Erro ao carregar eventos: " . $e->getMessage());
    $error_message = "Erro ao carregar eventos. Tente novamente.";
    $meus_eventos = [];
}

// Estatísticas
$total_eventos = count($meus_eventos);
$eventos_publicados = count(array_filter($meus_eventos, function($e) { return $e['status'] === 'publicado'; }));
$eventos_rascunho = count(array_filter($meus_eventos, function($e) { return $e['status'] === 'rascunho'; }));
$total_participantes = array_sum(array_column($meus_eventos, 'total_inscritos'));
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
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(2px);
        }
        
        .loading-spinner {
            text-align: center;
            color: white;
        }
        
        /* Confirmação de ação */
        .action-loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .action-loading .dropdown-toggle {
            position: relative;
        }
        
        .action-loading .dropdown-toggle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 16px;
            border: 2px solid #ccc;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <div class="mt-3">Processando...</div>
        </div>
    </div>

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
        <!-- Mensagens -->
        <?php if ($error_message): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

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
                        <?php echo count($meus_eventos); ?> eventos encontrados
                    </span>
                </div>
            </div>
        </div>

        <!-- Lista de Eventos -->
        <?php if (empty($meus_eventos)): ?>
            <div class="empty-state">
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
                    <i class="fas fa-plus me-2"></i>
                    <?php echo empty($meus_eventos) && empty($status_filter) && empty($categoria_filter) ? 'Criar Primeiro Evento' : 'Novo Evento'; ?>
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($meus_eventos as $evento): ?>
                <div class="event-card" data-event-id="<?php echo $evento['id_evento']; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start mb-2">
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h5>
                                    <div class="mb-2">
                                        <?php if (!empty($evento['nome_categoria'])): ?>
                                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($evento['nome_categoria']); ?></span>
                                        <?php endif; ?>
                                        <span class="status-badge status-<?php echo $evento['status']; ?>">
                                            <?php echo ucfirst($evento['status']); ?>
                                        </span>
                                    </div>
                                    <div class="text-muted">
                                        <small>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo $evento['data_formatada'] ?? date('d/m/Y', strtotime($evento['data_inicio'])); ?> às 
                                            <?php echo $evento['horario_formatado'] ?? date('H:i', strtotime($evento['horario_inicio'])); ?>
                                        </small>
                                        <br>
                                        <small>
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="text-center">
                                <h6>Participantes</h6>
                                <?php 
                                $participantes = $evento['total_inscritos'];
                                $capacidade = $evento['capacidade_maxima'] ?? 100;
                                $percentual = $capacidade > 0 ? ($participantes / $capacidade) * 100 : 0;
                                ?>
                                <div class="progress mb-2">
                                    <div class="progress-bar progress-bar-custom" 
                                         style="width: <?php echo min($percentual, 100); ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $participantes; ?> / <?php echo $capacidade ?: '∞'; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-2 text-center">
                            <strong class="<?php echo $evento['evento_gratuito'] ? 'text-success' : 'text-primary'; ?>">
                                <?php echo $evento['preco_formatado'] ?? ($evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.')); ?>
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
                                        <a class="dropdown-item" href="subscribers.php?id=<?php echo $evento['id_evento']; ?>">
                                            <i class="fas fa-users me-2"></i>Participantes
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    
                                    <?php if ($evento['status'] === 'rascunho'): ?>
                                        <li>
                                            <a class="dropdown-item text-success" href="#" 
                                               onclick="publishEvent(<?php echo $evento['id_evento']; ?>, '<?php echo htmlspecialchars($evento['titulo']); ?>')">
                                                <i class="fas fa-check me-2"></i>Publicar
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($evento['status'] === 'publicado'): ?>
                                        <li>
                                            <a class="dropdown-item text-warning" href="#" 
                                               onclick="unpublishEvent(<?php echo $evento['id_evento']; ?>, '<?php echo htmlspecialchars($evento['titulo']); ?>')">
                                                <i class="fas fa-pause me-2"></i>Despublicar
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <li>
                                        <a class="dropdown-item text-danger" href="#"
                                           onclick="deleteEvent(<?php echo $evento['id_evento']; ?>, '<?php echo htmlspecialchars($evento['titulo']); ?>')">
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
        // Configurações da API
        const API_BASE = '../../api/event-actions.php';
        
        document.addEventListener('DOMContentLoaded', function() {
            // Animação das estatísticas
            const statNumbers = document.querySelectorAll('.stat-card h3');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent) || 0;
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

        // Função para mostrar loading
        function showLoading(show = true) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = show ? 'flex' : 'none';
        }

        // Função para publicar evento
        function publishEvent(eventId, title) {
            if (confirm(`Tem certeza que deseja publicar o evento "${title}"?\n\nApós publicado, ele ficará visível para todos os usuários.`)) {
                showLoading(true);
                
                // Adicionar classe de loading ao card
                const eventCard = document.querySelector(`[data-event-id="${eventId}"]`);
                if (eventCard) {
                    eventCard.classList.add('action-loading');
                }
                
                fetch(API_BASE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=publish&id=${eventId}`
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (eventCard) {
                        eventCard.classList.remove('action-loading');
                    }
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'list.php?action=published';
                        }, 1500);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    if (eventCard) {
                        eventCard.classList.remove('action-loading');
                    }
                    showToast('Erro de conexão. Tente novamente.', 'error');
                    console.error('Erro:', error);
                });
            }
        }

        // Função para despublicar evento
        function unpublishEvent(eventId, title) {
            if (confirm(`Tem certeza que deseja despublicar o evento "${title}"?\n\nEle deixará de ser visível para os usuários, mas não será excluído.`)) {
                showLoading(true);
                
                const eventCard = document.querySelector(`[data-event-id="${eventId}"]`);
                if (eventCard) {
                    eventCard.classList.add('action-loading');
                }
                
                fetch(API_BASE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=unpublish&id=${eventId}`
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    if (eventCard) {
                        eventCard.classList.remove('action-loading');
                    }
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'list.php?action=unpublished';
                        }, 1500);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showLoading(false);
                    if (eventCard) {
                        eventCard.classList.remove('action-loading');
                    }
                    showToast('Erro de conexão. Tente novamente.', 'error');
                    console.error('Erro:', error);
                });
            }
        }

        // Função para excluir evento
        function deleteEvent(eventId, title) {
            // Primeiro aviso
            if (!confirm(`⚠️ ATENÇÃO: Você está prestes a EXCLUIR PERMANENTEMENTE o evento "${title}".\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza que deseja continuar?`)) {
                return;
            }
            
            // Segundo aviso - confirmação final
            const confirmText = prompt(`Para confirmar a exclusão, digite "EXCLUIR" (em maiúsculas):`);
            
            if (confirmText !== 'EXCLUIR') {
                if (confirmText !== null) {
                    showToast('Exclusão cancelada - texto de confirmação incorreto.', 'warning');
                }
                return;
            }
            
            showLoading(true);
            
            const eventCard = document.querySelector(`[data-event-id="${eventId}"]`);
            if (eventCard) {
                eventCard.classList.add('action-loading');
            }
            
            fetch(API_BASE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&id=${eventId}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                if (eventCard) {
                    eventCard.classList.remove('action-loading');
                }
                
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'list.php?action=deleted';
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                if (eventCard) {
                    eventCard.classList.remove('action-loading');
                }
                showToast('Erro de conexão. Tente novamente.', 'error');
                console.error('Erro:', error);
            });
        }

        // Sistema de toast notifications
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 10000;
                min-width: 300px;
                max-width: 400px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                animation: slideInRight 0.3s ease-out;
            `;
            
            const icons = {
                success: 'fas fa-check-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-exclamation-triangle',
                danger: 'fas fa-exclamation-circle',
                error: 'fas fa-exclamation-circle'
            };
            
            toast.innerHTML = `
                <i class="${icons[type] || icons.info} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            // Auto-remove após 5 segundos
            setTimeout(() => {
                if (toast.parentNode) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(toast);
                    bsAlert.close();
                }
            }, 5000);
        }

        // CSS para animações
        if (!document.getElementById('custom-animations')) {
            const style = document.createElement('style');
            style.id = 'custom-animations';
            style.textContent = `
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
                
                .action-loading {
                    opacity: 0.7;
                    pointer-events: none;
                }
                
                .action-loading .dropdown-toggle {
                    position: relative;
                }
                
                .action-loading .dropdown-toggle i {
                    opacity: 0;
                }
                
                .action-loading .dropdown-toggle::after {
                    content: '';
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    width: 16px;
                    height: 16px;
                    border: 2px solid #ccc;
                    border-top: 2px solid #007bff;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    0% { transform: translate(-50%, -50%) rotate(0deg); }
                    100% { transform: translate(-50%, -50%) rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>