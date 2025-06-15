<?php
// ========================================
// LISTA DE INSCRITOS DO EVENTO - VERSÃO COMPLETA
// ========================================
// Local: views/events/subscribers.php
// ========================================

session_start();

// Verificar se usuário está logado e é organizador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit();
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
    header('Location: ../dashboard/participant.php');
    exit();
}

$title = "Inscritos do Evento - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Organizador';
$userId = $_SESSION['user_id'] ?? 0;

// URLs
$dashboardUrl = '../dashboard/organizer.php';
$homeUrl = '../../index.php';

// Verificar se ID foi fornecido
$eventId = $_GET['id'] ?? null;
if (!$eventId) {
    header('Location: list.php');
    exit();
}

$evento = null;
$inscritos = [];
$error_message = '';
$success_message = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'toggle_attendance':
            $participantId = $_POST['participant_id'] ?? null;
            $present = isset($_POST['present']) ? ($_POST['present'] === 'true' ? 1 : 0) : null;
            
            if ($participantId && $present !== null) {
                // Simular atualização de presença
                $success_message = "Presença atualizada com sucesso!";
            }
            break;
            
        case 'export_list':
            // Simular exportação
            $success_message = "Lista exportada com sucesso!";
            break;
            
        case 'send_notification':
            $message = $_POST['message'] ?? '';
            if (!empty($message)) {
                $success_message = "Notificação enviada para todos os participantes!";
            } else {
                $error_message = "Mensagem não pode estar vazia.";
            }
            break;
    }
}

// Tentar carregar dados reais
try {
    require_once '../../controllers/EventController.php';
    $eventController = new EventController();
    
    // Buscar evento e verificar permissões
    $evento = $eventController->getById($eventId);
    if (!$evento || !$eventController->canEdit($eventId)) {
        $error_message = 'Evento não encontrado ou você não tem permissão para visualizá-lo.';
        $evento = null;
    }
    
} catch (Exception $e) {
    error_log("Erro ao carregar evento: " . $e->getMessage());
    $error_message = "Erro ao carregar dados do evento.";
}

// Dados de fallback se não conseguir carregar
if (!$evento && !$error_message) {
    $evento = [
        'id_evento' => $eventId,
        'titulo' => 'Workshop de Desenvolvimento Web',
        'data_inicio' => date('Y-m-d', strtotime('+7 days')),
        'horario_inicio' => '14:00:00',
        'local_cidade' => 'São Paulo',
        'local_estado' => 'SP',
        'capacidade_maxima' => 100,
        'total_inscritos' => 5
    ];
}

// Dados de exemplo dos inscritos (substituir pela busca real no banco)
$inscritos = [
    [
        'id_inscricao' => 1,
        'id_participante' => 1,
        'nome_participante' => 'João Silva',
        'email_participante' => 'joao.silva@email.com',
        'telefone_participante' => '(11) 99999-1111',
        'data_inscricao' => '2024-06-01 14:30:00',
        'status' => 'confirmada',
        'observacoes' => 'Primeira vez participando',
        'presente' => null,
        'cidade_participante' => 'São Paulo',
        'profissao_participante' => 'Desenvolvedor'
    ],
    [
        'id_inscricao' => 2,
        'id_participante' => 2,
        'nome_participante' => 'Maria Santos',
        'email_participante' => 'maria.santos@email.com',
        'telefone_participante' => '(11) 99999-2222',
        'data_inscricao' => '2024-06-02 09:15:00',
        'status' => 'confirmada',
        'observacoes' => '',
        'presente' => true,
        'cidade_participante' => 'São Paulo',
        'profissao_participante' => 'Designer'
    ],
    [
        'id_inscricao' => 3,
        'id_participante' => 3,
        'nome_participante' => 'Pedro Costa',
        'email_participante' => 'pedro.costa@email.com',
        'telefone_participante' => '(11) 99999-3333',
        'data_inscricao' => '2024-06-03 16:45:00',
        'status' => 'pendente',
        'observacoes' => 'Aguardando confirmação',
        'presente' => null,
        'cidade_participante' => 'Campinas',
        'profissao_participante' => 'Estudante'
    ],
    [
        'id_inscricao' => 4,
        'id_participante' => 4,
        'nome_participante' => 'Ana Oliveira',
        'email_participante' => 'ana.oliveira@email.com',
        'telefone_participante' => '(11) 99999-4444',
        'data_inscricao' => '2024-06-04 11:20:00',
        'status' => 'confirmada',
        'observacoes' => 'Interessada em networking',
        'presente' => false,
        'cidade_participante' => 'São Paulo',
        'profissao_participante' => 'Gerente de Projetos'
    ],
    [
        'id_inscricao' => 5,
        'id_participante' => 5,
        'nome_participante' => 'Carlos Mendes',
        'email_participante' => 'carlos.mendes@email.com',
        'telefone_participante' => '(11) 99999-5555',
        'data_inscricao' => '2024-06-05 08:30:00',
        'status' => 'confirmada',
        'observacoes' => 'Experiência em React',
        'presente' => null,
        'cidade_participante' => 'Santos',
        'profissao_participante' => 'Desenvolvedor Frontend'
    ]
];

// Calcular estatísticas
$stats = [
    'total_inscritos' => count($inscritos),
    'confirmados' => count(array_filter($inscritos, fn($i) => $i['status'] === 'confirmada')),
    'pendentes' => count(array_filter($inscritos, fn($i) => $i['status'] === 'pendente')),
    'cancelados' => count(array_filter($inscritos, fn($i) => $i['status'] === 'cancelada')),
    'presentes' => count(array_filter($inscritos, fn($i) => $i['presente'] === true)),
    'ausentes' => count(array_filter($inscritos, fn($i) => $i['presente'] === false)),
    'nao_marcados' => count(array_filter($inscritos, fn($i) => $i['presente'] === null))
];

// Filtros
$status_filter = $_GET['status'] ?? '';
$presence_filter = $_GET['presence'] ?? '';

if (!empty($status_filter)) {
    $inscritos = array_filter($inscritos, fn($i) => $i['status'] === $status_filter);
}

if (!empty($presence_filter)) {
    if ($presence_filter === 'presente') {
        $inscritos = array_filter($inscritos, fn($i) => $i['presente'] === true);
    } elseif ($presence_filter === 'ausente') {
        $inscritos = array_filter($inscritos, fn($i) => $i['presente'] === false);
    } elseif ($presence_filter === 'nao_marcado') {
        $inscritos = array_filter($inscritos, fn($i) => $i['presente'] === null);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
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
        
        .subscriber-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        
        .subscriber-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .status-confirmada { border-left-color: #28a745; }
        .status-pendente { border-left-color: #ffc107; }
        .status-cancelada { border-left-color: #dc3545; }
        
        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 1rem;
            border-left: 4px solid;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.primary { border-left-color: #667eea; }
        .stats-card.success { border-left-color: #28a745; }
        .stats-card.warning { border-left-color: #ffc107; }
        .stats-card.danger { border-left-color: #dc3545; }
        .stats-card.info { border-left-color: #17a2b8; }
        
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
        
        .presence-btn {
            transition: all 0.3s ease;
        }
        
        .presence-btn:hover {
            transform: scale(1.1);
        }
        
        .filters-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .actions-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .notification-modal .form-control {
            border-radius: 0.5rem;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .export-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        
        .notification-btn {
            background: linear-gradient(135deg, #17a2b8, #138496);
            border: none;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo htmlspecialchars($homeUrl); ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Olá, <?php echo htmlspecialchars($userName); ?>!
                </span>
                <a class="nav-link" href="<?php echo htmlspecialchars($dashboardUrl); ?>">Dashboard</a>
                <a class="nav-link" href="../../logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="text-decoration-none">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="list.php" class="text-decoration-none">Meus Eventos</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Inscritos</li>
            </ol>
        </nav>
    </div>

    <!-- Header da Página -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-users me-2"></i>Lista de Inscritos</h1>
                    <p class="mb-0 fs-5">
                        <?php echo $evento ? htmlspecialchars($evento['titulo']) : 'Carregando evento...'; ?>
                    </p>
                    <?php if ($evento): ?>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?>
                            </span>
                            <span class="badge bg-light text-dark ms-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($evento['local_cidade']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="view.php?id=<?php echo $eventId; ?>" class="btn btn-outline-light me-2">
                        <i class="fas fa-eye me-2"></i>Ver Evento
                    </a>
                    <a href="list.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container pb-5">
        <!-- Mensagens -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
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

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stats-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-primary"><?php echo $stats['total_inscritos']; ?></h3>
                            <small class="text-muted">Total</small>
                        </div>
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stats-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-success"><?php echo $stats['confirmados']; ?></h3>
                            <small class="text-muted">Confirmados</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stats-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-warning"><?php echo $stats['pendentes']; ?></h3>
                            <small class="text-muted">Pendentes</small>
                        </div>
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stats-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-success"><?php echo $stats['presentes']; ?></h3>
                            <small class="text-muted">Presentes</small>
                        </div>
                        <i class="fas fa-user-check fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stats-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-danger"><?php echo $stats['ausentes']; ?></h3>
                            <small class="text-muted">Ausentes</small>
                        </div>
                        <i class="fas fa-user-times fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stats-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0 text-info"><?php echo $evento['capacidade_maxima'] ?? '∞'; ?></h3>
                            <small class="text-muted">Capacidade</small>
                        </div>
                        <i class="fas fa-chair fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações em Massa -->
        <div class="actions-card">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Ações em Massa</h5>
                </div>
                <div class="col-md-6 text-md-end">
                    <button class="btn export-btn me-2" onclick="exportList()">
                        <i class="fas fa-download me-2"></i>Exportar Lista
                    </button>
                    <button class="btn notification-btn me-2" data-bs-toggle="modal" data-bs-target="#notificationModal">
                        <i class="fas fa-bell me-2"></i>Notificar Participantes
                    </button>
                    <button class="btn btn-outline-success" onclick="markAllPresent()">
                        <i class="fas fa-check-double me-2"></i>Marcar Todos Presentes
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="id" value="<?php echo $eventId; ?>">
                        
                        <div class="col-md-4">
                            <select class="form-select" name="status">
                                <option value="">Todos os Status</option>
                                <option value="confirmada" <?php echo $status_filter === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                                <option value="pendente" <?php echo $status_filter === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="cancelada" <?php echo $status_filter === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <select class="form-select" name="presence">
                                <option value="">Todas as Presenças</option>
                                <option value="presente" <?php echo $presence_filter === 'presente' ? 'selected' : ''; ?>>Presente</option>
                                <option value="ausente" <?php echo $presence_filter === 'ausente' ? 'selected' : ''; ?>>Ausente</option>
                                <option value="nao_marcado" <?php echo $presence_filter === 'nao_marcado' ? 'selected' : ''; ?>>Não Marcado</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i>Filtrar
                            </button>
                            <a href="subscribers.php?id=<?php echo $eventId; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Limpar
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-4 text-md-end">
                    <span class="text-muted">
                        <i class="fas fa-list me-1"></i>
                        <?php echo count($inscritos); ?> participantes exibidos
                    </span>
                </div>
            </div>
        </div>

        <!-- Lista de Inscritos -->
        <?php if (empty($inscritos)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                <h4>Nenhum inscrito encontrado</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($status_filter) || !empty($presence_filter)): ?>
                        Tente ajustar os filtros para ver mais resultados.
                    <?php else: ?>
                        Quando pessoas se inscreverem no evento, elas aparecerão aqui.
                    <?php endif; ?>
                </p>
                <a href="view.php?id=<?php echo $eventId; ?>" class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>Ver Página do Evento
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($inscritos as $inscrito): ?>
                <div class="subscriber-card status-<?php echo $inscrito['status']; ?>">
                    <div class="row align-items-center">
                        <!-- Avatar e Informações Básicas -->
                        <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3">
                                    <?php echo strtoupper(substr($inscrito['nome_participante'], 0, 2)); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($inscrito['nome_participante']); ?></h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($inscrito['email_participante']); ?>
                                    </p>
                                    <?php if (!empty($inscrito['telefone_participante'])): ?>
                                        <p class="mb-0 text-muted small">
                                            <i class="fas fa-phone me-1"></i>
                                            <?php echo htmlspecialchars($inscrito['telefone_participante']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status e Informações Adicionais -->
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <?php
                            $statusClass = [
                                'confirmada' => 'bg-success',
                                'pendente' => 'bg-warning text-dark',
                                'cancelada' => 'bg-danger'
                            ];
                            ?>
                            <span class="badge <?php echo $statusClass[$inscrito['status']] ?? 'bg-secondary'; ?> mb-2">
                                <?php echo ucfirst($inscrito['status']); ?>
                            </span>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('d/m/Y H:i', strtotime($inscrito['data_inscricao'])); ?>
                            </small>
                            <br>
                            <?php if (!empty($inscrito['cidade_participante'])): ?>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($inscrito['cidade_participante']); ?>
                                </small>
                                <br>
                            <?php endif; ?>
                            <?php if (!empty($inscrito['profissao_participante'])): ?>
                                <small class="text-muted">
                                    <i class="fas fa-briefcase me-1"></i>
                                    <?php echo htmlspecialchars($inscrito['profissao_participante']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Observações -->
                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                            <?php if (!empty($inscrito['observacoes'])): ?>
                                <small class="text-muted">
                                    <i class="fas fa-comment me-1"></i>
                                    <strong>Observações:</strong><br>
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
                        <div class="col-lg-2 col-md-6">
                            <?php if ($inscrito['status'] === 'confirmada'): ?>
                                <div class="text-center">
                                    <div class="btn-group-vertical d-grid gap-1" role="group">
                                        <button type="button" 
                                                class="btn btn-sm presence-btn <?php echo $inscrito['presente'] === false ? 'btn-danger' : 'btn-outline-danger'; ?>" 
                                                onclick="markPresence(<?php echo $inscrito['id_participante']; ?>, <?php echo $inscrito['id_inscricao']; ?>, false, this)"
                                                title="Marcar ausente">
                                            <i class="fas fa-times me-1"></i>Ausente
                                        </button>
                                    </div>
                                    
                                    <?php if ($inscrito['presente'] !== null): ?>
                                        <div class="mt-2">
                                            <small class="badge <?php echo $inscrito['presente'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $inscrito['presente'] ? 'Confirmado' : 'Faltou'; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-hourglass-half me-1"></i>
                                        Aguardando<br>confirmação
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de Notificação -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">
                        <i class="fas fa-bell me-2"></i>Enviar Notificação
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" class="notification-form">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send_notification">
                        
                        <div class="mb-3">
                            <label for="notification-type" class="form-label">Tipo de Notificação</label>
                            <select class="form-select" id="notification-type" name="type">
                                <option value="all">Todos os participantes</option>
                                <option value="confirmed">Apenas confirmados</option>
                                <option value="pending">Apenas pendentes</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notification-subject" class="form-label">Assunto</label>
                            <input type="text" class="form-control" id="notification-subject" name="subject" 
                                   placeholder="Ex: Lembrete sobre o evento" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notification-message" class="form-label">Mensagem</label>
                            <textarea class="form-control" id="notification-message" name="message" rows="4" 
                                      placeholder="Digite sua mensagem aqui..." required></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            A notificação será enviada por email para os participantes selecionados.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn notification-btn">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Notificação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animação das estatísticas
            const statNumbers = document.querySelectorAll('.stats-card h3');
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

            // Loading state para formulários
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
                        
                        // Re-enable após 5 segundos
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 5000);
                    }
                });
            });
        });

        // Marcar presença individual
        function markPresence(participantId, inscricaoId, isPresent, button) {
            const action = isPresent ? 'presente' : 'ausente';
            
            if (confirm(`Tem certeza que deseja marcar como ${action}?`)) {
                // Mostrar loading no botão
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Simular requisição (substituir pela API real)
                setTimeout(() => {
                    // Atualizar interface
                    const card = button.closest('.subscriber-card');
                    const presenceButtons = card.querySelectorAll('.presence-btn');
                    const statusBadge = card.querySelector('.mt-2 .badge');
                    
                    // Reset all buttons
                    presenceButtons.forEach(btn => {
                        btn.className = btn.className.replace(/btn-(success|danger)/, 'btn-outline-$1');
                    });
                    
                    // Update clicked button
                    if (isPresent) {
                        button.className = button.className.replace('btn-outline-success', 'btn-success');
                    } else {
                        button.className = button.className.replace('btn-outline-danger', 'btn-danger');
                    }
                    
                    // Update or create status badge
                    if (statusBadge) {
                        statusBadge.className = `badge ${isPresent ? 'bg-success' : 'bg-danger'}`;
                        statusBadge.textContent = isPresent ? 'Confirmado' : 'Faltou';
                    } else {
                        const badgeContainer = card.querySelector('.mt-2');
                        if (badgeContainer) {
                            badgeContainer.innerHTML = `<small class="badge ${isPresent ? 'bg-success' : 'bg-danger'}">${isPresent ? 'Confirmado' : 'Faltou'}</small>`;
                        }
                    }
                    
                    // Restore button
                    button.disabled = false;
                    button.innerHTML = originalText;
                    
                    showToast(`Presença marcada como ${action}!`, 'success');
                    
                    // Atualizar estatísticas
                    updateStats();
                }, 1000);
            }
        }

        // Marcar todos como presentes
        function markAllPresent() {
            const confirmedCount = document.querySelectorAll('.status-confirmada').length;
            
            if (confirmedCount === 0) {
                showToast('Não há participantes confirmados para marcar presença.', 'warning');
                return;
            }
            
            if (confirm(`Tem certeza que deseja marcar TODOS os ${confirmedCount} participantes confirmados como presentes?`)) {
                // Simular processo
                let processed = 0;
                const interval = setInterval(() => {
                    processed++;
                    
                    if (processed <= confirmedCount) {
                        showToast(`Marcando presença... ${processed}/${confirmedCount}`, 'info');
                    }
                    
                    if (processed >= confirmedCount) {
                        clearInterval(interval);
                        showToast('Todos os participantes foram marcados como presentes!', 'success');
                        
                        // Atualizar interface
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                }, 300);
            }
        }

        // Exportar lista
        function exportList() {
            showToast('Preparando exportação...', 'info');
            
            // Simular exportação
            setTimeout(() => {
                // Criar dados CSV de exemplo
                const csvData = [
                    ['Nome', 'Email', 'Telefone', 'Status', 'Presença', 'Data Inscrição'],
                    ['João Silva', 'joao.silva@email.com', '(11) 99999-1111', 'Confirmada', 'Não marcado', '01/06/2024 14:30'],
                    ['Maria Santos', 'maria.santos@email.com', '(11) 99999-2222', 'Confirmada', 'Presente', '02/06/2024 09:15'],
                    // Adicionar mais dados conforme necessário
                ];
                
                // Converter para CSV
                const csvContent = csvData.map(row => row.join(',')).join('\n');
                
                // Criar download
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `inscritos_evento_${<?php echo $eventId; ?>}_${new Date().toISOString().split('T')[0]}.csv`;
                link.click();
                window.URL.revokeObjectURL(url);
                
                showToast('Lista exportada com sucesso!', 'success');
            }, 1500);
        }

        // Atualizar estatísticas na tela
        function updateStats() {
            // Esta função seria implementada para recalcular as estatísticas
            // baseadas no estado atual da presença
            console.log('Atualizando estatísticas...');
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

            setTimeout(() => {
                if (toast.parentNode) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(toast);
                    bsAlert.close();
                }
            }, 4000);
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
                
                .presence-btn {
                    transition: all 0.3s ease;
                }
                
                .presence-btn:hover {
                    transform: scale(1.05);
                }
                
                .subscriber-card {
                    transition: all 0.3s ease;
                }
                
                .subscriber-card:hover {
                    transform: translateY(-2px);
                }
                
                .stats-card {
                    transition: all 0.3s ease;
                }
                
                .stats-card:hover {
                    transform: translateY(-2px);
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>-sm presence-btn <?php echo $inscrito['presente'] === true ? 'btn-success' : 'btn-outline-success'; ?>" 
                                                onclick="markPresence(<?php echo $inscrito['id_participante']; ?>, <?php echo $inscrito['id_inscricao']; ?>, true, this)"
                                                title="Marcar presente">
                                            <i class="fas fa-check me-1"></i>Presente
                                        </button>
                                        <button type="button" 
                                                class="btn btn