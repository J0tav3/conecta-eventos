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

        /* Estilos para o sistema de inscrição */
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

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .toast-notification {
            animation: slideInRight 0.3s ease-out;
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

                <!-- Ações para Participantes -->
                <?php if (!$canEdit && $evento['status'] === 'publicado'): ?>
                    <div class="info-card text-center">
                        <h4><i class="fas fa-ticket-alt me-2"></i>Participar</h4>
                        
                        <?php if (isLoggedIn()): ?>
                            <?php if ($evento['vagas_esgotadas']): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Vagas esgotadas
                                </div>
                            <?php else: ?>
                                <!-- Container dinâmico para inscrição -->
                                <div id="subscription-container">
                                    <div class="d-grid">
                                        <button id="subscribe-btn" class="btn btn-primary btn-lg">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Inscrever-se
                                        </button>
                                    </div>
                                    <small class="text-muted mt-2 d-block">
                                        Clique para se inscrever no evento
                                    </small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Faça login para se inscrever
                            </div>
                            <div class="d-grid">
                                <a href="<?php echo SITE_URL; ?>/views/auth/login.php" class="btn btn-outline-primary">
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
        // Definir variáveis globais para o JavaScript
        window.EVENT_ID = <?php echo $eventId; ?>;
        window.USER_ID = <?php echo isLoggedIn() ? getUserId() : 'null'; ?>;
        window.SITE_URL = '<?php echo SITE_URL; ?>';
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
            if (this.userId) {
                this.checkSubscriptionStatus();
            }
        }

        bindEvents() {
            const subscribeBtn = document.getElementById('subscribe-btn');
            if (subscribeBtn) {
                subscribeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleSubscription();
                });
            }

            const unsubscribeBtn = document.getElementById('unsubscribe-btn');
            if (unsubscribeBtn) {
                unsubscribeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.unsubscribe();
                });
            }
        }

        async checkSubscriptionStatus() {
            try {
                const response = await fetch(`${this.apiUrl}?action=status&event_id=${this.eventId}`);
                const data = await response.json();

                if (data.success) {
                    this.updateButtonState(data.is_subscribed, data.subscription_status);
                }
            } catch (error) {
                console.error('Erro ao verificar status da inscrição:', error);
            }
        }

        async toggleSubscription() {
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
                    this.updateButtonState(data.is_subscribed, data.subscription_status);
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
                    this.updateButtonState(false);
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

        updateButtonState(isSubscribed, subscriptionStatus = null) {
            const subscriptionContainer = document.getElementById('subscription-container');
            if (!subscriptionContainer) return;

            if (isSubscribed) {
                subscriptionContainer.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Você está inscrito neste evento!
                        ${subscriptionStatus ? `<br><small>Status: ${this.getStatusText(subscriptionStatus.status || 'confirmada')}</small>` : ''}
                    </div>
                    <div class="d-grid">
                        <button id="unsubscribe-btn" class="btn btn-outline-danger">
                            <i class="fas fa-times me-2"></i>
                            Cancelar Inscrição
                        </button>
                    </div>
                `;
            } else {
                subscriptionContainer.innerHTML = `
                    <div class="d-grid">
                        <button id="subscribe-btn" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Inscrever-se
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        Clique para se inscrever no evento
                    </small>
                `;
            }
            
            this.bindEvents();
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
            const existingToasts = document.querySelectorAll('.toast-notification');
            existingToasts.forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = `toast-notification alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 400px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                animation: slideInRight 0.3s ease-out;
            `;
            
            toast.innerHTML = `
                <i class="fas fa-${this.getIconForType(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.style.animation = 'slideOutRight 0.3s ease-in';
                    setTimeout(() => toast.remove(), 300);
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

        getStatusText(status) {
            const statusMap = {
                'confirmada': 'Confirmada',
                'pendente': 'Pendente',
                'cancelada': 'Cancelada'
            };
            return statusMap[status] || status;
        }
    }

    // Inicializar o sistema quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        if (window.EVENT_ID) {
            new SubscriptionManager(window.EVENT_ID, window.USER_ID);
        }
    });

    // Confirmações para ações importantes
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form button[type="submit"]').forEach(button => {
            const form = button.closest('form');
            const action = form.querySelector('input[name="action"]')?.value;
            
            if (['delete', 'change_status'].includes(action)) {
                button.addEventListener('click', function(e) {
                    if (!confirm('Tem certeza que deseja realizar esta ação?')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
    });
    class FavoritesManager {
    constructor(eventId, userId = null) {
        this.eventId = eventId;
        this.userId = userId;
        this.apiUrl = window.SITE_URL + '/api/favorites.php';
        this.init();
    }

    init() {
        this.createFavoriteButton();
        this.bindEvents();
        if (this.userId) {
            this.checkFavoriteStatus();
        }
    }

    createFavoriteButton() {
        // Encontrar um local para adicionar o botão de favorito
        const eventHeader = document.querySelector('.event-header .col-md-8');
        if (eventHeader && this.userId) {
            const favoriteContainer = document.createElement('div');
            favoriteContainer.id = 'favorite-container';
            favoriteContainer.className = 'mt-3';
            favoriteContainer.innerHTML = `
                <button id="favorite-btn" class="btn btn-outline-light" title="Adicionar aos favoritos">
                    <i class="far fa-heart me-2"></i>
                    <span class="favorite-text">Favoritar</span>
                </button>
            `;
            eventHeader.appendChild(favoriteContainer);
        }
    }

    bindEvents() {
        const favoriteBtn = document.getElementById('favorite-btn');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleFavorite();
            });
        }
    }

    async checkFavoriteStatus() {
        try {
            const response = await fetch(`${this.apiUrl}?event_id=${this.eventId}`);
            const data = await response.json();

            if (data.success) {
                this.updateFavoriteButton(data.is_favorite, data.total_favorites);
            }
        } catch (error) {
            console.error('Erro ao verificar status do favorito:', error);
        }
    }

    async toggleFavorite() {
        if (!this.userId) {
            this.showLoginRequired();
            return;
        }

        const btn = document.getElementById('favorite-btn');
        const originalHtml = btn.innerHTML;
        
        // Estado de loading
        this.setButtonLoading(btn, true);

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    event_id: this.eventId,
                    action: 'toggle'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccessMessage(data.message);
                this.updateFavoriteButton(data.is_favorite, data.total_favorites);
                this.animateButton(btn);
            } else {
                this.showErrorMessage(data.message);
            }
        } catch (error) {
            console.error('Erro ao favoritar:', error);
            this.showErrorMessage('Erro de conexão. Tente novamente.');
        } finally {
            this.setButtonLoading(btn, false, originalHtml);
        }
    }

    updateFavoriteButton(isFavorite, totalFavorites = 0) {
        const btn = document.getElementById('favorite-btn');
        const favoriteText = btn.querySelector('.favorite-text');
        const icon = btn.querySelector('i');

        if (isFavorite) {
            btn.className = 'btn btn-danger';
            icon.className = 'fas fa-heart me-2';
            favoriteText.textContent = 'Favoritado';
            btn.title = 'Remover dos favoritos';
        } else {
            btn.className = 'btn btn-outline-light';
            icon.className = 'far fa-heart me-2';
            favoriteText.textContent = 'Favoritar';
            btn.title = 'Adicionar aos favoritos';
        }

        // Atualizar contador se existir
        this.updateFavoriteCounter(totalFavorites);
    }

    updateFavoriteCounter(total) {
        let counterElement = document.getElementById('favorite-counter');
        if (!counterElement) {
            // Criar contador se não existir
            const favoriteContainer = document.getElementById('favorite-container');
            if (favoriteContainer) {
                counterElement = document.createElement('small');
                counterElement.id = 'favorite-counter';
                counterElement.className = 'text-light ms-2';
                favoriteContainer.appendChild(counterElement);
            }
        }
        
        if (counterElement) {
            counterElement.textContent = `${total} ${total === 1 ? 'pessoa favoritou' : 'pessoas favoritaram'}`;
        }
    }

    setButtonLoading(button, isLoading, originalHtml = '') {
        if (isLoading) {
            button.disabled = true;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Processando...
            `;
        } else {
            button.disabled = false;
            if (originalHtml) {
                button.innerHTML = originalHtml;
            }
        }
    }

    animateButton(button) {
        button.style.transform = 'scale(1.1)';
        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 200);
    }

    showSuccessMessage(message) {
        this.showToast(message, 'success');
    }

    showErrorMessage(message) {
        this.showToast(message, 'danger');
    }

    showLoginRequired() {
        this.showToast('Você precisa fazer login para favoritar eventos.', 'info');
    }

    showToast(message, type = 'info') {
        // Usar o mesmo sistema de toast do SubscriptionManager
        const toast = document.createElement('div');
        toast.className = `toast-notification alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = `
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease-out;
        `;
        
        toast.innerHTML = `
            <i class="fas fa-${this.getIconForType(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    }

    getIconForType(type) {
        const icons = {
            'success': 'heart',
            'danger': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'bell';
    }
}

// Inicializar o sistema de favoritos
document.addEventListener('DOMContentLoaded', function() {
    if (window.EVENT_ID && window.USER_ID) {
        new FavoritesManager(window.EVENT_ID, window.USER_ID);
    }
});
    </script>
</body>
</html>