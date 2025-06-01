<?php
// ========================================
// LISTA DE INSCRITOS DO EVENTO
// ========================================
// Local: views/events/subscribers.php
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

$eventController = new EventController();

// Verificar se ID foi fornecido
$eventId = $_GET['id'] ?? null;
if (!$eventId) {
    header('Location: list.php');
    exit();
}

// Buscar evento e verificar permissões
$evento = $eventController->getById($eventId);
if (!$evento || !$eventController->canEdit($eventId)) {
    setFlashMessage('Evento não encontrado ou você não tem permissão para visualizá-lo.', 'danger');
    header('Location: list.php');
    exit();
}

$title = "Inscritos: " . $evento['titulo'] . " - " . SITE_NAME;

// Processar ação de marcar presença
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'toggle_attendance') {
    $participantId = $_POST['participant_id'] ?? null;
    $present = $_POST['present'] ?? false;
    
    if ($participantId) {
        // Aqui você implementaria a lógica para marcar presença
        // Por agora, vamos simular o sucesso
        setFlashMessage('Presença atualizada com sucesso!', 'success');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $eventId);
        exit();
    }
}

// Simular dados de inscritos (substituir pela API real)
$inscritos = [
    [
        'id_inscricao' => 1,
        'id_participante' => 1,
        'nome_participante' => 'João Silva',
        'email_participante' => 'joao@email.com',
        'data_inscricao' => '2024-01-15 14:30:00',
        'status' => 'confirmada',
        'observacoes' => 'Primeira vez no evento',
        'presente' => null
    ],
    [
        'id_inscricao' => 2,
        'id_participante' => 2,
        'nome_participante' => 'Maria Santos',
        'email_participante' => 'maria@email.com',
        'data_inscricao' => '2024-01-16 09:15:00',
        'status' => 'confirmada',
        'observacoes' => '',
        'presente' => true
    ],
    [
        'id_inscricao' => 3,
        'id_participante' => 3,
        'nome_participante' => 'Pedro Costa',
        'email_participante' => 'pedro@email.com',
        'data_inscricao' => '2024-01-17 16:45:00',
        'status' => 'pendente',
        'observacoes' => 'Aguardando confirmação de pagamento',
        'presente' => null
    ]
];

$stats = [
    'total_inscritos' => count($inscritos),
    'confirmados' => count(array_filter($inscritos, fn($i) => $i['status'] === 'confirmada')),
    'pendentes' => count(array_filter($inscritos, fn($i) => $i['status'] === 'pendente')),
    'presentes' => count(array_filter($inscritos, fn($i) => $i['presente'] === true)),
    'ausentes' => count(array_filter($inscritos, fn($i) => $i['presente'] === false))
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        .subscriber-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .subscriber-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-confirmada { border-left-color: #28a745; }
        .status-pendente { border-left-color: #ffc107; }
        .status-cancelada { border-left-color: #dc3545; }
        
        .presence-btn {
            width: 100px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php include '../../views/layouts/header.php'; ?>

    <div class="container my-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-users me-2"></i>Lista de Inscritos</h2>
                <p class="text-muted"><?php echo htmlspecialchars($evento['titulo']); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="view.php?id=<?php echo $eventId; ?>" class="btn btn-outline-info me-2">
                    <i class="fas fa-eye me-2"></i>Ver Evento
                </a>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>

        <!-- Mensagens Flash -->
        <?php showFlashMessage(); ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stats-card">
                    <h3><?php echo $stats['total_inscritos']; ?></h3>
                    <p class="mb-0">Total</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3><?php echo $stats['confirmados']; ?></h3>
                    <p class="mb-0">Confirmados</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3><?php echo $stats['pendentes']; ?></h3>
                    <p class="mb-0">Pendentes</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3><?php echo $stats['presentes']; ?></h3>
                    <p class="mb-0">Presentes</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3><?php echo $stats['ausentes']; ?></h3>
                    <p class="mb-0">Ausentes</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <h3><?php echo $evento['capacidade_maxima'] ?? '∞'; ?></h3>
                    <p class="mb-0">Capacidade</p>
                </div>
            </div>
        </div>

        <!-- Ações em Massa -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Ações em Massa</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-outline-success me-2" onclick="markAllPresent()">
                            <i class="fas fa-check-double me-2"></i>Marcar Todos Presentes
                        </button>
                        <button class="btn btn-outline-primary" onclick="exportList()">
                            <i class="fas fa-download me-2"></i>Exportar Lista
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Inscritos -->
        <?php if (empty($inscritos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                <h4>Nenhum inscrito ainda</h4>
                <p class="text-muted">Quando pessoas se inscreverem no evento, elas aparecerão aqui.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($inscritos as $inscrito): ?>
                    <div class="col-12 mb-3">
                        <div class="card subscriber-card status-<?php echo $inscrito['status']; ?>">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Avatar e Informações -->
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <?php echo strtoupper(substr($inscrito['nome_participante'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($inscrito['nome_participante']); ?></h6>
                                                <p class="mb-0 text-muted small">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?php echo htmlspecialchars($inscrito['email_participante']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Status e Data -->
                                    <div class="col-md-3">
                                        <?php
                                        $statusClass = [
                                            'confirmada' => 'bg-success',
                                            'pendente' => 'bg-warning',
                                            'cancelada' => 'bg-danger'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $statusClass[$inscrito['status']] ?? 'bg-secondary'; ?> mb-2">
                                            <?php echo ucfirst($inscrito['status']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Inscrito em <?php echo date('d/m/Y H:i', strtotime($inscrito['data_inscricao'])); ?>
                                        </small>
                                    </div>
                                    
                                    <!-- Observações -->
                                    <div class="col-md-3">
                                        <?php if (!empty($inscrito['observacoes'])): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-comment me-1"></i>
                                                <?php echo htmlspecialchars($inscrito['observacoes']); ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">
                                                <i class="fas fa-comment-slash me-1"></i>
                                                Sem observações
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Controle de Presença -->
                                    <div class="col-md-2 text-end">
                                        <?php if ($inscrito['status'] === 'confirmada'): ?>
                                            <div class="btn-group presence-control" role="group">
                                                <button type="button" 
                                                        class="btn btn-sm <?php echo $inscrito['presente'] === true ? 'btn-success' : 'btn-outline-success'; ?>" 
                                                        onclick="markPresence(<?php echo $inscrito['id_participante']; ?>, true)"
                                                        title="Marcar presente">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm <?php echo $inscrito['presente'] === false ? 'btn-danger' : 'btn-outline-danger'; ?>" 
                                                        onclick="markPresence(<?php echo $inscrito['id_participante']; ?>, false)"
                                                        title="Marcar ausente">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            
                                            <?php if ($inscrito['presente'] !== null): ?>
                                                <div class="mt-2">
                                                    <small class="badge <?php echo $inscrito['presente'] ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $inscrito['presente'] ? 'Presente' : 'Ausente'; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small class="text-muted">
                                                Aguardando confirmação
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Marcar presença individual
        function markPresence(participantId, isPresent) {
            if (confirm(`Tem certeza que deseja marcar como ${isPresent ? 'presente' : 'ausente'}?`)) {
                // Aqui você faria a requisição AJAX para a API
                fetch('/conecta-eventos/api/subscriptions.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        event_id: <?php echo $eventId; ?>,
                        participant_id: participantId,
                        present: isPresent
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Presença atualizada!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showToast('Erro ao atualizar presença', 'error');
                });
            }
        }

        // Marcar todos como presentes
        function markAllPresent() {
            if (confirm('Tem certeza que deseja marcar TODOS os participantes confirmados como presentes?')) {
                showToast('Funcionalidade em desenvolvimento', 'info');
            }
        }

        // Exportar lista
        function exportList() {
            showToast('Funcionalidade em desenvolvimento', 'info');
        }

        // Sistema de toast notifications
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            `;
            
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 4000);
        }

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