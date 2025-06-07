<?php
require_once '../../config/config.php';
require_once '../../includes/session.php';
require_once '../../controllers/EventController.php';

$eventController = new EventController();

// Verificar se ID foi fornecido
$eventId = $_GET['id'] ?? null;
if (!$eventId) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Buscar evento
$evento = $eventController->getById($eventId);
if (!$evento) {
    setFlashMessage('Evento não encontrado.', 'danger');
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Formatar evento para exibição
$evento = $eventController->formatEventForDisplay($evento);

$title = $evento['titulo'] . " - " . SITE_NAME;

// Verificar se o usuário pode editar este evento
$canEdit = isLoggedIn() && $eventController->canEdit($eventId);

// Obter estatísticas se for o organizador
$stats = null;
if ($canEdit) {
    $stats = $eventController->getEventStats($eventId);
}

// Verificar status de inscrição se for participante
$subscriptionStatus = null;
$canSubscribe = false;
if (isLoggedIn() && isParticipant()) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Verificar inscrição existente
        $stmt = $conn->prepare("
            SELECT * FROM inscricoes 
            WHERE id_participante = ? AND id_evento = ?
            ORDER BY data_inscricao DESC
            LIMIT 1
        ");
        $stmt->execute([getUserId(), $eventId]);
        $subscriptionStatus = $stmt->fetch();
        
        // Verificar se pode se inscrever
        $canSubscribe = !$subscriptionStatus || $subscriptionStatus['status'] === 'cancelada';
        $canSubscribe = $canSubscribe && $evento['status'] === 'publicado' && !$evento['vagas_esgotadas'];
    } catch (Exception $e) {
        error_log("Erro ao verificar inscrição: " . $e->getMessage());
    }
}
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
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .event-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .no-image {
            width: 100%;
            height: 300px;
            background: #f8f9fa;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            border: 2px dashed #dee2e6;
        }
        .info-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 0.375rem;
        }
        .info-item i {
            width: 30px;
            text-align: center;
            color: #007bff;
        }
        .badge-status {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
        }
        .btn-action {
            margin: 0.25rem;
        }

        /* Sistema de inscrição */
        .subscription-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .subscription-card.subscribed {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }
        
        .subscription-card.unavailable {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }

        #subscribe-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
            transition: all 0.3s ease;
        }

        #subscribe-btn:hover:not(:disabled) {
            background: linear-gradient(45deg, #218838, #1ea085);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            transform: translateY(-1px);
        }

        #unsubscribe-btn {
            transition: all 0.3s ease;
        }

        #unsubscribe-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease-out;
        }

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
    </style>
</head>
<body>
    <?php include '../../views/layouts/header.php'; ?>

    <!-- Cabeçalho do Evento -->
    <div class="event-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <h1 class="me-3"><?php echo htmlspecialchars($evento['titulo']); ?></h1>
                        <?php if ($evento['destaque']): ?>
                            <i class="fas fa-star fa-2x text-warning" title="Evento em destaque"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php
                        $statusClass = [
                            'rascunho' => 'bg-secondary',
                            'publicado' => 'bg-success',
                            'cancelado' => 'bg-danger',
                            'finalizado' => 'bg-dark'
                        ];
                        ?>
                        <span class="badge <?php echo $statusClass[$evento['status']] ?? 'bg-secondary'; ?> badge-status">
                            <?php echo $evento['status_nome']; ?>
                        </span>
                        
                        <?php if ($evento['nome_categoria']): ?>
                            <span class="badge bg-light text-dark badge-status">
                                <?php echo htmlspecialchars($evento['nome_categoria']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <span class="badge bg-warning text-dark badge-status">
                            <?php echo $evento['preco_formatado']; ?>
                        </span>
                    </div>
                    
                    <p class="lead mb-0"><?php echo htmlspecialchars($evento['nome_organizador']); ?></p>
                </div>
                
                <div class="col-md-4 text-end">
                    <?php if ($canEdit): ?>
                        <div class="btn-group" role="group">
                            <a href="edit.php?id=<?php echo $eventId; ?>" class="btn btn-warning btn-action">
                                <i class="fas fa-edit me-2"></i>Editar
                            </a>
                            <a href="subscribers.php?id=<?php echo $eventId; ?>" class="btn btn-info btn-action">
                                <i class="fas fa-users me-2"></i>Inscritos
                            </a>
                            <a href="list.php" class="btn btn-outline-light btn-action">
                                <i class="fas fa-list me-2"></i>Meus Eventos
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-outline-light btn-action">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <!-- Coluna Principal -->
            <div class="col-lg-8">
                <!-- Imagem do Evento -->
                <div class="mb-4">
                    <?php if (!empty($evento['imagem_capa'])): ?>
                        <img src="<?php echo $evento['imagem_url']; ?>" 
                             alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                             class="event-image">
                    <?php else: ?>
                        <div class="no-image">
                            <div class="text-center">
                                <i class="fas fa-image fa-4x mb-3"></i>
                                <h5>Nenhuma imagem disponível</h5>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Descrição -->
                <div class="info-card">
                    <h3><i class="fas fa-info-circle me-2"></i>Sobre o Evento</h3>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($evento['descricao'])); ?></p>
                </div>

                <!-- Requisitos -->
                <?php if (!empty($evento['requisitos'])): ?>
                    <div class="info-card">
                        <h4><i class="fas fa-list-check me-2"></i>Requisitos</h4>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($evento['requisitos'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Informações Adicionais -->
                <?php if (!empty($evento['informacoes_adicionais'])): ?>
                    <div class="info-card">
                        <h4><i class="fas fa-plus-circle me-2"></i>Informações Adicionais</h4>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($evento['informacoes_adicionais'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Estatísticas do Organizador -->
                <?php if ($canEdit && $stats): ?>
                    <div class="info-card">
                        <h4><i class="fas fa-chart-bar me-2"></i>Estatísticas do Evento</h4>
                        <div class="row text-center">
                            <div class="col-6 col-md-3 mb-3">
                                <div class="stats-card">
                                    <h3><?php echo $stats['inscritos_confirmados'] ?? 0; ?></h3>