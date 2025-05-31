<?php
require_once '../../config/config.php';
require_once '../../includes/session.php';
require_once '../../controllers/EventController.php';

// Verificar se usuário está logado e é organizador
requireLogin();
if (!isOrganizer()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$title = "Meus Eventos - " . SITE_NAME;
$eventController = new EventController();

// Processar filtros
$filters = [];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['busca'])) {
    $filters['busca'] = $_GET['busca'];
}

// Paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

$pagination = $eventController->paginate($filters, $page, $perPage);
$eventos = $pagination['items'];

// Processar ações
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $eventId = $_POST['event_id'] ?? '';
    
    switch ($action) {
        case 'delete':
            $result = $eventController->delete($eventId);
            setFlashMessage($result['message'], $result['success'] ? 'success' : 'danger');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
            break;
            
        case 'change_status':
            $newStatus = $_POST['status'] ?? '';
            $result = $eventController->changeStatus($eventId, $newStatus);
            setFlashMessage($result['message'], $result['success'] ? 'success' : 'danger');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
            break;
            
        case 'duplicate':
            $result = $eventController->duplicate($eventId);
            setFlashMessage($result['message'], $result['success'] ? 'success' : 'danger');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
            break;
    }
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
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        .event-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .event-image {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 0.375rem;
        }
        .no-image {
            width: 100px;
            height: 70px;
            background: #f8f9fa;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include '../../views/layouts/header.php'; ?>

    <div class="container my-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-calendar-alt me-2"></i>Meus Eventos</h2>
                <p class="text-muted">Gerencie todos os seus eventos em um só lugar</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Novo Evento
                </a>
            </div>
        </div>

        <!-- Mensagens Flash -->
        <?php showFlashMessage(); ?>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="busca" class="form-label">Buscar</label>
                        <input type="text" 
                               class="form-control" 
                               id="busca" 
                               name="busca" 
                               placeholder="Título ou descrição..."
                               value="<?php echo htmlspecialchars($_GET['busca'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="rascunho" <?php echo ($_GET['status'] ?? '') === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                            <option value="publicado" <?php echo ($_GET['status'] ?? '') === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                            <option value="cancelado" <?php echo ($_GET['status'] ?? '') === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            <option value="finalizado" <?php echo ($_GET['status'] ?? '') === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Eventos -->
        <?php if (empty($eventos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4>Nenhum evento encontrado</h4>
                <p class="text-muted">Comece criando seu primeiro evento!</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Criar Primeiro Evento
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($eventos as $evento): ?>
                    <?php $evento = $eventController->formatEventForDisplay($evento); ?>
                    <div class="col-12 mb-3">
                        <div class="card event-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Imagem -->
                                    <div class="col-auto">
                                        <?php if (!empty($evento['imagem_capa'])): ?>
                                            <img src="<?php echo $evento['imagem_url']; ?>" 
                                                 alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                                 class="event-image">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="fas fa-image fa-2x"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Informações -->
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 class="card-title mb-1">
                                                    <?php echo htmlspecialchars($evento['titulo']); ?>
                                                    <?php if ($evento['destaque']): ?>
                                                        <i class="fas fa-star text-warning ms-1" title="Evento em destaque"></i>
                                                    <?php endif; ?>
                                                </h5>
                                                <p class="card-text text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                                    
                                                    <i class="fas fa-calendar ms-3 me-1"></i>
                                                    <?php echo $evento['data_inicio_formatada']; ?>
                                                    
                                                    <i class="fas fa-clock ms-3 me-1"></i>
                                                    <?php echo $evento['horario_inicio_formatado']; ?>
                                                </p>
                                                <p class="card-text mb-0">
                                                    <?php echo substr(htmlspecialchars($evento['descricao']), 0, 100); ?>...
                                                </p>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <!-- Status -->
                                                <?php
                                                $statusClass = [
                                                    'rascunho' => 'bg-secondary',
                                                    'publicado' => 'bg-success',
                                                    'cancelado' => 'bg-danger',
                                                    'finalizado' => 'bg-dark'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $statusClass[$evento['status']] ?? 'bg-secondary'; ?> status-badge mb-2">
                                                    <?php echo $evento['status_nome']; ?>
                                                </span>
                                                
                                                <!-- Estatísticas -->
                                                <div class="small text-muted mb-2">
                                                    <i class="fas fa-users me-1"></i>
                                                    <?php echo $evento['total_inscritos'] ?? 0; ?> inscritos
                                                    
                                                    <?php if ($evento['capacidade_maxima']): ?>
                                                        / <?php echo $evento['capacidade_maxima']; ?>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="small text-muted mb-3">
                                                    <strong><?php echo $evento['preco_formatado']; ?></strong>
                                                </div>
                                                
                                                <!-- Ações -->
                                                <div class="btn-group" role="group">
                                                    <a href="view.php?id=<?php echo $evento['id_evento']; ?>" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="Visualizar">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $evento['id_evento']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            data-bs-toggle="dropdown" 
                                                            title="Mais ações">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if ($evento['status'] === 'rascunho'): ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="change_status">
                                                                    <input type="hidden" name="event_id" value="<?php echo $evento['id_evento']; ?>">
                                                                    <input type="hidden" name="status" value="publicado">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-paper-plane me-2"></i>Publicar
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($evento['status'] === 'publicado'): ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="change_status">
                                                                    <input type="hidden" name="event_id" value="<?php echo $evento['id_evento']; ?>">
                                                                    <input type="hidden" name="status" value="cancelado">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-ban me-2"></i>Cancelar
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="duplicate">
                                                                <input type="hidden" name="event_id" value="<?php echo $evento['id_evento']; ?>">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="fas fa-copy me-2"></i>Duplicar
                                                                </button>
                                                            </form>
                                                        </li>
                                                        
                                                        <li><hr class="dropdown-divider"></li>
                                                        
                                                        <li>
                                                            <form method="POST" style="display: inline;" 
                                                                  onsubmit="return confirm('Tem certeza que deseja excluir este evento?')">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="event_id" value="<?php echo $evento['id_evento']; ?>">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-trash me-2"></i>Excluir
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Paginação de eventos">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $pagination['page'] - 1; ?>&<?php echo http_build_query($_GET); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i === $pagination['page'] ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $pagination['page'] + 1; ?>&<?php echo http_build_query($_GET); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted">
                    Mostrando <?php echo count($eventos); ?> de <?php echo $pagination['total']; ?> eventos
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>

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
    </script>
</body>
</html>