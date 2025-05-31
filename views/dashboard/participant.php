<?php
// ========================================
// DASHBOARD DO PARTICIPANTE
// ========================================
// Local: views/dashboard/participant.php
// ========================================

require_once '../../config/config.php';
require_once '../../includes/session.php';
require_once '../../controllers/EventController.php';

// Verificar se usuário está logado e é participante
requireLogin();
if (!isParticipant()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$title = "Meu Painel - " . SITE_NAME;
$eventController = new EventController();

// Obter estatísticas do participante
$userId = getUserId();

// Para as estatísticas de inscrições, vamos usar queries diretas por enquanto
// TODO: Implementar métodos específicos no EventController para participantes
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Estatísticas de inscrições
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_inscricoes,
            SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes
        FROM inscricoes 
        WHERE id_participante = ?
    ");
    $stmt->execute([$userId]);
    $statsInscricoes = $stmt->fetch();
    
    // Próximos eventos em que está inscrito
    $stmt = $conn->prepare("
        SELECT e.*, i.status as status_inscricao, i.data_inscricao,
               u.nome as nome_organizador,
               c.nome as nome_categoria
        FROM inscricoes i
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
        LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
        WHERE i.id_participante = ? 
        AND e.data_inicio >= NOW()
        AND i.status = 'confirmada'
        ORDER BY e.data_inicio ASC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $proximosEventos = $stmt->fetchAll();
    
    // Eventos recentes que participou
    $stmt = $conn->prepare("
        SELECT e.*, i.status as status_inscricao, i.data_inscricao,
               u.nome as nome_organizador,
               c.nome as nome_categoria
        FROM inscricoes i
        INNER JOIN eventos e ON i.id_evento = e.id_evento
        LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
        LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
        WHERE i.id_participante = ?
        ORDER BY i.data_inscricao DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $eventosRecentes = $stmt->fetchAll();
    
} catch (Exception $e) {
    $statsInscricoes = ['total_inscricoes' => 0, 'confirmadas' => 0, 'pendentes' => 0];
    $proximosEventos = [];
    $eventosRecentes = [];
}

// Eventos públicos disponíveis
$eventosDisponiveis = $eventController->getPublicEvents(['limite' => 6, 'ordem' => 'data_inicio']);
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        .event-card {
            border-left: 4px solid #28a745;
            background: #f8f9fa;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0 0.375rem 0.375rem 0;
            transition: all 0.3s ease;
        }
        .event-card:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .event-available {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        .event-available:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
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
                    <p class="lead mb-0">Descubra eventos incríveis e gerencie suas inscrições.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <a href="#eventos-disponiveis" class="btn quick-action">
                            <i class="fas fa-search me-2"></i>Buscar Eventos
                        </a>
                        <a href="#minhas-inscricoes" class="btn quick-action">
                            <i class="fas fa-ticket-alt me-2"></i>Minhas Inscrições
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagens Flash -->
        <?php showFlashMessage(); ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statsInscricoes['total_inscricoes']; ?></div>
                    <div>Total de Inscrições</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statsInscricoes['confirmadas']; ?></div>
                    <div>Inscrições Confirmadas</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statsInscricoes['pendentes']; ?></div>
                    <div>Aguardando Confirmação</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Próximos Eventos -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card" id="minhas-inscricoes">
                    <h4 class="mb-3">
                        <i class="fas fa-history me-2 text-info"></i>
                        Histórico de Inscrições
                    </h4>
                    
                    <?php if (empty($eventosRecentes)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma inscrição realizada ainda</p>
                            <a href="#eventos-disponiveis" class="btn btn-info btn-sm">
                                <i class="fas fa-search me-2"></i>Encontrar Eventos
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($eventosRecentes as $evento): ?>
                                <div class="event-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                            <p class="mb-1">
                                                <?php
                                                $statusClass = [
                                                    'confirmada' => 'bg-success',
                                                    'pendente' => 'bg-warning',
                                                    'cancelada' => 'bg-danger'
                                                ];
                                                $statusText = [
                                                    'confirmada' => 'Confirmada',
                                                    'pendente' => 'Pendente',
                                                    'cancelada' => 'Cancelada'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $statusClass[$evento['status_inscricao']] ?? 'bg-secondary'; ?>">
                                                    <?php echo $statusText[$evento['status_inscricao']] ?? $evento['status_inscricao']; ?>
                                                </span>
                                            </p>
                                            <small class="text-muted">
                                                Inscrito em <?php echo date('d/m/Y', strtotime($evento['data_inscricao'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <a href="../events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Eventos Disponíveis -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card" id="eventos-disponiveis">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-plus me-2 text-primary"></i>
                            Eventos Disponíveis
                        </h4>
                        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-outline-primary btn-sm">
                            Ver Todos os Eventos
                        </a>
                    </div>
                    
                    <?php if (empty($eventosDisponiveis)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum evento disponível no momento</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($eventosDisponiveis as $evento): ?>
                                <?php $evento = $eventController->formatEventForDisplay($evento); ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="event-available h-100">
                                        <div class="d-flex flex-column h-100">
                                            <div class="mb-2">
                                                <?php if (!empty($evento['imagem_capa'])): ?>
                                                    <img src="<?php echo $evento['imagem_url']; ?>" 
                                                         alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                                                         class="img-fluid rounded"
                                                         style="height: 120px; width: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                         style="height: 120px;">
                                                        <i class="fas fa-image fa-2x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo $evento['data_inicio_formatada']; ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($evento['local_cidade']); ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i>
                                                        <?php echo $evento['preco_formatado']; ?>
                                                    </small>
                                                </div>
                                                
                                                <?php if ($evento['nome_categoria']): ?>
                                                    <span class="badge bg-light text-dark mb-2">
                                                        <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mt-auto">
                                                <div class="d-grid">
                                                    <a href="../events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye me-2"></i>Ver Detalhes
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4 class="mb-3">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Ações Rápidas
                    </h4>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search me-2"></i>Buscar Eventos
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="#minhas-inscricoes" class="btn btn-outline-success w-100">
                                <i class="fas fa-ticket-alt me-2"></i>Minhas Inscrições
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="#" class="btn btn-outline-info w-100" onclick="alert('Em desenvolvimento!')">
                                <i class="fas fa-star me-2"></i>Favoritos
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="#" class="btn btn-outline-warning w-100" onclick="alert('Em desenvolvimento!')">
                                <i class="fas fa-user-edit me-2"></i>Editar Perfil
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

        // Scroll suave para seções
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
    </script>
</body>
</html>
                        <i class="fas fa-calendar-check me-2 text-success"></i>
                        Meus Próximos Eventos
                    </h4>
                    
                    <?php if (empty($proximosEventos)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Você não está inscrito em nenhum evento próximo</p>
                            <a href="#eventos-disponiveis" class="btn btn-success btn-sm">
                                <i class="fas fa-search me-2"></i>Buscar Eventos
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($proximosEventos as $evento): ?>
                                <div class="event-card">
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
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($evento['nome_organizador']); ?>
                                                </span>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success mb-2">Inscrito</span>
                                            <br>
                                            <a href="../events/view.php?id=<?php echo $evento['id_evento']; ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Histórico de Inscrições -->
            <div class="col-lg-6 mb-4">
                <div class="dashboard-card">
                    <h4 class="mb-3">