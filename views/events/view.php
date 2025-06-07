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
                                    <p class="mb-0">Confirmados</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-3">
                                <div class="stats-card">
                                    <h3><?php echo $stats['inscritos_pendentes'] ?? 0; ?></h3>
                                    <p class="mb-0">Pendentes</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-3">
                                <div class="stats-card">
                                    <h3><?php echo $stats['total_favoritos'] ?? 0; ?></h3>
                                    <p class="mb-0">Favoritos</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-3">
                                <div class="stats-card">
                                    <h3><?php echo number_format($stats['media_avaliacoes'] ?? 0, 1); ?></h3>
                                    <p class="mb-0">Avaliação</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Coluna Lateral -->
            <div class="col-lg-4">
                <!-- Informações Básicas -->
                <div class="info-card">
                    <h4><i class="fas fa-calendar-alt me-2"></i>Informações</h4>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar me-3"></i>
                        <div>
                            <strong>Data</strong><br>
                            <?php if ($evento['data_inicio_formatada'] === $evento['data_fim_formatada']): ?>
                                <?php echo $evento['data_inicio_formatada']; ?>
                            <?php else: ?>
                                <?php echo $evento['data_inicio_formatada']; ?> a <?php echo $evento['data_fim_formatada']; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-clock me-3"></i>
                        <div>
                            <strong>Horário</strong><br>
                            <?php echo $evento['horario_inicio_formatado']; ?> às <?php echo $evento['horario_fim_formatado']; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt me-3"></i>
                        <div>
                            <strong>Local</strong><br>
                            <?php echo htmlspecialchars($evento['local_nome']); ?><br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($evento['local_endereco']); ?><br>
                                <?php echo htmlspecialchars($evento['local_cidade']); ?> - <?php echo htmlspecialchars($evento['local_estado']); ?>
                                <?php if ($evento['local_cep']): ?>
                                    <br>CEP: <?php echo htmlspecialchars($evento['local_cep']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-users me-3"></i>
                        <div>
                            <strong>Participantes</strong><br>
                            <?php echo $evento['total_inscritos'] ?? 0; ?> inscritos
                            <?php if ($evento['capacidade_maxima']): ?>
                                de <?php echo $evento['capacidade_maxima']; ?>
                                <br><small class="text-muted">
                                    <?php if ($evento['vagas_esgotadas']): ?>
                                        <span class="text-danger">Vagas esgotadas</span>
                                    <?php else: ?>
                                        <?php echo $evento['vagas_disponiveis']; ?> vagas restantes
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-money-bill me-3"></i>
                        <div>
                            <strong>Investimento</strong><br>
                            <?php echo $evento['preco_formatado']; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($evento['link_externo'])): ?>
                        <div class="info-item">
                            <i class="fas fa-link me-3"></i>
                            <div>
                                <strong>Link Externo</strong><br>
                                <a href="<?php echo htmlspecialchars($evento['link_externo']); ?>" 
                                   target="_blank" 
                                   class="text-decoration-none">
                                    Mais informações <i class="fas fa-external-link-alt ms-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Organizador -->
                <div class="info-card">
                    <h4><i class="fas fa-user me-2"></i>Organizador</h4>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-user fa-lg"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($evento['nome_organizador']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($evento['email_organizador']); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Sistema de Inscrição para Participantes -->
                <?php if (!$canEdit && $evento['status'] === 'publicado'): ?>
                    <div class="subscription-card <?php echo $subscriptionStatus && $subscriptionStatus['status'] === 'confirmada' ? 'subscribed' : ($evento['vagas_esgotadas'] ? 'unavailable' : ''); ?>">
                        <h4><i class="fas fa-ticket-alt me-2"></i>Participar</h4>
                        
                        <?php if (isLoggedIn()): ?>
                            <div id="subscription-container">
                                <?php if ($subscriptionStatus && $subscriptionStatus['status'] === 'confirmada'): ?>
                                    <!-- Já inscrito -->
                                    <div class="alert alert-info">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Você está inscrito neste evento!
                                        <br><small>Inscrito em: <?php echo date('d/m/Y H:i', strtotime($subscriptionStatus['data_inscricao'])); ?></small>
                                    </div>
                                    <div class="d-grid">
                                        <button id="unsubscribe-btn" class="btn btn-outline-light">
                                            <i class="fas fa-times me-2"></i>
                                            Cancelar Inscrição
                                        </button>
                                    </div>
                                <?php elseif ($subscriptionStatus && $subscriptionStatus['status'] === 'pendente'): ?>
                                    <!-- Pendente -->
                                    <div class="alert alert-warning">
                                        <i class="fas fa-clock me-2"></i>
                                        Sua inscrição está pendente de confirmação
                                    </div>
                                <?php elseif ($evento['vagas_esgotadas']): ?>
                                    <!-- Vagas esgotadas -->
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Vagas esgotadas
                                    </div>
                                <?php elseif ($canSubscribe): ?>
                                    <!-- Pode se inscrever -->
                                    <div class="d-grid">
                                        <button id="subscribe-btn" class="btn btn-light btn-lg">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Inscrever-se
                                        </button>
                                    </div>
                                    <small class="text-light mt-2 d-block">
                                        Clique para se inscrever no evento
                                    </small>
                                <?php else: ?>
                                    <!-- Não pode se inscrever -->
                                    <div class="alert alert-warning">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Inscrições não disponíveis
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Não logado -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Faça login para se inscrever
                            </div>
                            <div class="d-grid">
                                <a href="<?php echo SITE_URL; ?>/views/auth/login.php" class="btn btn-light">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Fazer Login
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Ações para Organizador -->
                <?php if ($canEdit): ?>
                    <div class="info-card">
                        <h4><i class="fas fa-cog me-2"></i>Ações</h4>
                        <div class="d-grid gap-2">
                            <a href="edit.php?id=<?php echo $eventId; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Editar Evento
                            </a>
                            
                            <a href="subscribers.php?id=<?php echo $eventId; ?>" class="btn btn-info">
                                <i class="fas fa-users me-2"></i>
                                Ver Inscritos (<?php echo $evento['total_inscritos'] ?? 0; ?>)
                            </a>
                            
                            <?php if ($evento['status'] === 'rascunho'): ?>
                                <form method="POST" action="list.php" style="display: inline;">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                    <input type="hidden" name="status" value="publicado">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Publicar Evento
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($evento['status'] === 'publicado'): ?>
                                <form method="POST" action="list.php" style="display: inline;">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                    <input type="hidden" name="status" value="cancelado">
                                    <button type="submit" class="btn btn-warning w-100"
                                            onclick="return confirm('Tem certeza que deseja cancelar este evento?')">
                                        <i class="fas fa-ban me-2"></i>Cancelar Evento
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" action="list.php" style="display: inline;">
                                <input type="hidden" name="action" value="duplicate">
                                <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-copy me-2"></i>Duplicar Evento
                                </button>
                            </form>
                            
                            <hr>
                            
                            <form method="POST" action="list.php" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                <button type="submit" class="btn btn-danger w-100"
                                        onclick="return confirm('Tem certeza que deseja excluir este evento? Esta ação não pode ser desfeita.')">
                                    <i class="fas fa-trash me-2"></i>Excluir Evento
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Variables for JavaScript -->
    <script>
        window.EVENT_ID = <?php echo $eventId; ?>;
        window.USER_ID = <?php echo isLoggedIn() ? getUserId() : 'null'; ?>;
        window.SITE_URL = '<?php echo SITE_URL; ?>';
        window.IS_PARTICIPANT = <?php echo isLoggedIn() && isParticipant() ? 'true' : 'false'; ?>;
    </script>

    <!-- Sistema de Inscrição AJAX -->
    <script>
    class SubscriptionManager {
        constructor(eventId, userId = null) {
            this.eventId = eventId;
            this.userId = userId;
            this.apiUrl = window.SITE_URL + '/api/subscriptions.php';
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            const subscribeBtn = document.getElementById('subscribe-btn');
            const unsubscribeBtn = document.getElementById('unsubscribe-btn');
            
            if (subscribeBtn) {
                subscribeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.subscribe();
                });
            }

            if (unsubscribeBtn) {
                unsubscribeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.unsubscribe();
                });
            }
        }

        async subscribe() {
            if (!this.userId) {
                this.showLoginRequired();
                return;
            }

            const btn = document.getElementById('subscribe-btn');
            const originalText = btn.innerHTML;
            
            this.setButtonLoading(btn, true);

            try {
                const response = await fetch(this.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        event_id: this.eventId,
                        observations: 'Inscrição via plataforma web'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccessMessage(data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showErrorMessage(data.message);
                }
            } catch (error) {
                console.error('Erro na inscrição:', error);
                this.showErrorMessage('Erro de conexão. Tente novamente.');
            } finally {
                this.setButtonLoading(btn, false, originalText);
            }
        }

        async unsubscribe() {
            if (!confirm('Tem certeza que deseja cancelar sua inscrição?')) {
                return;
            }

            const btn = document.getElementById('unsubscribe-btn');
            const originalText = btn.innerHTML;
            
            this.setButtonLoading(btn, true);

            try {
                const response = await fetch(this.apiUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        event_id: this.eventId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccessMessage(data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showErrorMessage(data.message);
                }
            } catch (error) {
                console.error('Erro ao cancelar inscrição:', error);
                this.showErrorMessage('Erro de conexão. Tente novamente.');
            } finally {
                this.setButtonLoading(btn, false, originalText);
            }
        }

        setButtonLoading(button, isLoading, originalText = '') {
            if (isLoading) {
                button.disabled = true;
                button.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Processando...
                `;
            } else {
                button.disabled = false;
                if (originalText) {
                    button.innerHTML = originalText;
                }
            }
        }

        showSuccessMessage(message) {
            this.showToast(message, 'success');
        }

        showErrorMessage(message) {
            this.showToast(message, 'danger');
        }

        showLoginRequired() {
            this.showToast('Você precisa fazer login para se inscrever.', 'info');
            setTimeout(() => {
                window.location.href = window.SITE_URL + '/views/auth/login.php';
            }, 2000);
        }

        showToast(message, type = 'info') {
            // Remove toasts existentes
            const existingToasts = document.querySelectorAll('.toast-notification');
            existingToasts.forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = `toast-notification alert alert-${type} alert-dismissible fade show`;
            
            toast.innerHTML = `
                <i class="fas fa-${this.getIconForType(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            // Auto-remove
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }

        getIconForType(type) {
            const icons = {
                'success': 'check-circle',
                'danger': 'exclamation-triangle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };
            return icons[type] || 'bell';
        }
    }

    // Inicializar quando DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        if (window.EVENT_ID && window.IS_PARTICIPANT) {
            new SubscriptionManager(window.EVENT_ID, window.USER_ID);
        }
    });
    </script>
</body>
</html>