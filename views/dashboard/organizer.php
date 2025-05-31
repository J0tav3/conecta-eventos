<?php
// ========================================
// DASHBOARD DO ORGANIZADOR
// ========================================
// Local: views/dashboard/organizer.php
// ========================================

require_once '../../config/config.php';
require_once '../../includes/session.php';
require_once '../../controllers/EventController.php';

// Verificar se usuário está logado e é organizador
requireLogin();
if (!isOrganizer()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$title = "Dashboard - " . SITE_NAME;
$eventController = new EventController();

// Obter estatísticas do organizador
$userId = getUserId();

// Estatísticas gerais
$totalEventos = $eventController->count(['organizador_id' => $userId]);
$eventosPublicados = $eventController->count(['organizador_id' => $userId, 'status' => 'publicado']);
$eventosRascunho = $eventController->count(['organizador_id' => $userId, 'status' => 'rascunho']);

// Eventos recentes
$eventosRecentes = $eventController->getEventsByOrganizer($userId, ['limite' => 5, 'ordem' => 'data_criacao']);

// Próximos eventos
$proximosEventos = $eventController->getEventsByOrganizer($userId, [
    'status' => 'publicado',
    'data_inicio' => date('Y-m-d H:i:s'),
    'limite' => 5,
    'ordem' => 'data_inicio'
]);

// Calcular total de inscrições
$totalInscricoes = 0;
foreach ($eventosPublicados > 0 ? $eventController->getEventsByOrganizer($userId, ['status' => 'publicado']) : [] as $evento) {
    $totalInscricoes += $evento['total_inscritos'] ?? 0;
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
        .dashboard-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .quick-action {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            transition: all 0.3s ease;
        }
        .quick-action:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-1px);
        }
        .event-item {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0 0.375rem 0.375rem 0;
        }
    </style>
</head>
<body>
    <?php include '../../views/layouts/header.php'; ?>

    <div class="container my-4">
        <!-- Seção de Boas-vindas -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">Olá, <?php echo htmlspecialchars(getUserName()); ?>!</h1>
                    <p class="lead mb-0">Bem-vindo ao seu painel de controle. Gerencie seus eventos e acompanhe o desempenho.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <a href="../events/create.php" class="btn quick-action">
                            <i class="fas fa-plus me-2"></i>Novo Evento
                        </a>
                        <a href="../events/list.php" class="btn quick-action">
                            <i class="fas fa-list me-2"></i>Meus Eventos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagens Flash -->
        <?php showFlashMessage(); ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalEventos; ?></div>
                    <div>Total de Eventos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $eventosPublicados; ?></div>
                    <div>Eventos Publicados</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $eventosRascunho; ?></div>
                    <div>Rascunhos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalInscricoes; ?></div>
                    <div>Total de Inscrições</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Próximos Eventos -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <h4 class="mb-3">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Próximos Eventos
                    </h4>
                    
                    <?php if (empty($proximosEventos)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum evento próximo</p>
                            <a href="../events/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Criar Evento
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($proximosEventos as $evento): ?>
                                <div class="event-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                            <p class="mb-1 text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?>
                                                <i class="fas fa-clock ms-2 me-1"></i>
                                                <?php echo date('H:i', strtotime($evento['horario_inicio'])); ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                                <span class="ms-2">
                                                    <i class="fas fa-users me-1"></i>
                                                    <?php echo $evento['total_inscritos'] ?? 0; ?> inscritos
                                                </span>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <a href="../events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="../events/list.php?status=publicado" class="btn btn-outline-primary btn-sm">
                                Ver Todos os Eventos Publicados
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Eventos Recentes -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <h4 class="mb-3">
                        <i class="fas fa-clock me-2 text-success"></i>
                        Eventos Recentes
                    </h4>
                    
                    <?php if (empty($eventosRecentes)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum evento criado ainda</p>
                            <a href="../events/create.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-2"></i>Criar Primeiro Evento
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($eventosRecentes as $evento): ?>
                                <div class="event-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                            <p class="mb-1">
                                                <?php
                                                $statusClass = [
                                                    'rascunho' => 'bg-secondary',
                                                    'publicado' => 'bg-success',
                                                    'cancelado' => 'bg-danger',
                                                    'finalizado' => 'bg-dark'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $statusClass[$evento['status']] ?? 'bg-secondary'; ?>">
                                                    <?php echo ucfirst($evento['status']); ?>
                                                </span>
                                            </p>
                                            <small class="text-muted">
                                                Criado em <?php echo date('d/m/Y', strtotime($evento['data_criacao'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <a href="../events/edit.php?id=<?php echo $evento['id_evento']; ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="../events/list.php" class="btn btn-outline-success btn-sm">
                                Ver Todos os Meus Eventos
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4 class="mb-3">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Ações Rápidas
                    </h4>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="../events/create.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-plus me-2"></i>Novo Evento
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="../events/list.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-list me-2"></i>Gerenciar Eventos
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="../events/list.php?status=rascunho" class="btn btn-outline-warning w-100">
                                <i class="fas fa-draft2digital me-2"></i>Ver Rascunhos
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="#" class="btn btn-outline-secondary w-100" onclick="alert('Em desenvolvimento!')">
                                <i class="fas fa-chart-bar me-2"></i>Relatórios
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

        // Animar cards na entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card, .stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>