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
                                <div class="d-grid">
                                    <button class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Inscrever-se
                                    </button>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    Clique para se inscrever no evento
                                </small>
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
    <script>
        // Confirmações para ações importantes
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
    </script>
</body>
</html>