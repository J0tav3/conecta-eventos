<?php
// ==========================================
// PÁGINA DE VISUALIZAÇÃO DE EVENTOS - VERSÃO COMPLETA COM SISTEMA DE INSCRIÇÕES
// Local: views/events/view.php
// ==========================================

session_start();

// Verificar se foi passado um ID
$evento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($evento_id <= 0) {
    header("Location: ../../index.php");
    exit;
}

$title = "Visualizar Evento - Conecta Eventos";
$homeUrl = '../../index.php';

// Verificar se usuário está logado
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['user_name'] ?? '';
$userType = $_SESSION['user_type'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

// Buscar dados do evento
$evento = null;
$error_message = '';

try {
    require_once '../../controllers/EventController.php';
    $eventController = new EventController();
    $evento = $eventController->getById($evento_id);
    
    if (!$evento) {
        error_log("Evento não encontrado para ID: " . $evento_id);
        header("Location: ../../index.php");
        exit;
    }
    
    error_log("Evento carregado: " . $evento['titulo'] . " (ID: " . $evento['id_evento'] . ")");
    
} catch (Exception $e) {
    error_log("Erro ao carregar evento: " . $e->getMessage());
    $error_message = "Erro ao carregar evento.";
}

// Se não conseguiu carregar e não há erro definido, usar dados de exemplo
if (!$evento && !$error_message) {
    $eventos_exemplo = [
        1 => [
            'id_evento' => 1,
            'titulo' => 'Workshop de Desenvolvimento Web',
            'descricao' => 'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
            'data_inicio' => date('Y-m-d', strtotime('+7 days')),
            'horario_inicio' => '14:00:00',
            'horario_fim' => '18:00:00',
            'local_nome' => 'Centro de Tecnologia SP',
            'local_endereco' => 'Av. Paulista, 1000 - São Paulo, SP',
            'local_cidade' => 'São Paulo',
            'local_estado' => 'SP',
            'evento_gratuito' => 1,
            'preco' => 0,
            'capacidade_maxima' => 100,
            'nome_categoria' => 'Tecnologia',
            'nome_organizador' => 'Tech Academy',
            'email_organizador' => 'contato@techacademy.com',
            'imagem_capa' => '',
            'total_inscritos' => 45,
            'status' => 'publicado'
        ]
    ];

    $evento = $eventos_exemplo[$evento_id] ?? $eventos_exemplo[1];
    $evento['id_evento'] = $evento_id;
}

// Garantir que campos obrigatórios existem
$evento['descricao_detalhada'] = $evento['descricao_detalhada'] ?? $evento['descricao'];
$evento['requisitos'] = $evento['requisitos'] ?? '';
$evento['informacoes_adicionais'] = $evento['informacoes_adicionais'] ?? '';

// URL da imagem
$currentImageUrl = '';
if (!empty($evento['imagem_capa'])) {
    $currentImageUrl = 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $evento['imagem_capa'];
}

// Verificar se usuário pode editar (se for organizador do evento)
$canEdit = false;
if ($isLoggedIn && $userType === 'organizador') {
    try {
        $canEdit = $eventController->canEdit($evento_id);
    } catch (Exception $e) {
        // Ignorar erro
    }
}

// Verificar se pode se inscrever
$canSubscribe = false;
$subscriptionStatus = null;

if ($isLoggedIn && $userType === 'participante') {
    $canSubscribe = ($evento['status'] === 'publicado' && 
                    strtotime($evento['data_inicio']) > time() &&
                    (!$evento['capacidade_maxima'] || $evento['total_inscritos'] < $evento['capacidade_maxima']));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($evento['titulo']) . ' - ' . $title; ?></title>
    
    <!-- Meta tags para o evento -->
    <meta name="event-id" content="<?php echo $evento['id_evento']; ?>">
    <meta name="description" content="<?php echo htmlspecialchars(substr($evento['descricao'], 0, 160)); ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            <?php if ($isLoggedIn): ?>class="user-logged-in"<?php endif; ?>
        }
        
        .hero-event {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-event::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .event-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 1rem;
        }
        
        .no-image {
            height: 300px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        .info-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        
        .subscription-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .subscription-card.subscribed {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
        
        .subscription-card.unavailable {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }
        
        .organizer-card {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .detail-section {
            margin-bottom: 2rem;
        }
        
        .detail-section h4 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .attendance-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        
        .btn-inscricao {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-inscricao:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .btn-cancelar {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-cancelar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .event-meta {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
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
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
    </style>
</head>
<body data-event-id="<?php echo $evento['id_evento']; ?>" 
      data-evento-gratuito="<?php echo $evento['evento_gratuito'] ? '1' : '0'; ?>"
      data-evento-preco="<?php echo $evento['preco']; ?>"
      <?php if ($isLoggedIn): ?>class="user-logged-in"<?php endif; ?>>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if ($isLoggedIn): ?>
                    <span class="navbar-text me-3">
                        Olá, <?php echo htmlspecialchars($userName); ?>!
                    </span>
                    <?php if ($userType === 'organizador'): ?>
                        <a class="nav-link" href="../dashboard/organizer.php">Dashboard</a>
                    <?php else: ?>
                        <a class="nav-link" href="../dashboard/participant.php">Meu Painel</a>
                    <?php endif; ?>
                    <a class="nav-link" href="../../logout.php">Sair</a>
                <?php else: ?>
                    <a class="nav-link" href="../auth/login.php">Login</a>
                    <a class="nav-link" href="../auth/register.php">Cadastrar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo $homeUrl; ?>" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i>Início
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo $homeUrl; ?>" class="text-decoration-none">Eventos</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($evento['titulo']); ?>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Hero Section -->
    <section class="hero-event">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <span class="badge bg-light text-dark fs-6 me-2">
                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($evento['nome_categoria']); ?>
                        </span>
                        <span class="badge bg-warning text-dark fs-6">
                            <i class="fas fa-users me-1"></i><span class="participant-count"><?php echo $evento['total_inscritos']; ?></span> inscritos
                        </span>
                    </div>
                    <h1 class="display-5 mb-3"><?php echo htmlspecialchars($evento['titulo']); ?></h1>
                    <p class="fs-5 mb-4"><?php echo htmlspecialchars($evento['descricao']); ?></p>
                    
                    <div class="event-meta">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar fa-lg me-3"></i>
                                    <div>
                                        <strong><?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?></strong>
                                        <div class="small opacity-75">
                                            <?php echo date('H:i', strtotime($evento['horario_inicio'])); ?>
                                            <?php if (!empty($evento['horario_fim']) && $evento['horario_fim'] !== $evento['horario_inicio']): ?>
                                                - <?php echo date('H:i', strtotime($evento['horario_fim'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-map-marker-alt fa-lg me-3"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($evento['local_cidade']); ?>, <?php echo htmlspecialchars($evento['local_estado']); ?></strong>
                                        <div class="small opacity-75"><?php echo htmlspecialchars($evento['local_nome']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="text-center">
                        <div class="badge bg-success fs-5 px-3 py-2">
                            <i class="fas fa-ticket-alt me-2"></i>
                            <?php if ($evento['evento_gratuito']): ?>
                                GRATUITO
                            <?php else: ?>
                                R$ <?php echo number_format($evento['preco'], 2, ',', '.'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <!-- Mensagens -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Conteúdo Principal -->
            <div class="col-lg-8">
                <!-- Imagem do Evento -->
                <div class="info-card">
                    <?php if ($currentImageUrl): ?>
                        <img src="<?php echo htmlspecialchars($currentImageUrl); ?>" 
                             alt="<?php echo htmlspecialchars($evento['titulo']); ?>"
                             class="event-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="no-image" style="display: none;">
                            <div class="text-center">
                                <i class="fas fa-image fa-4x mb-3"></i>
                                <h5>Imagem não disponível</h5>
                                <p class="text-muted">A imagem do evento não pôde ser carregada</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-image">
                            <div class="text-center">
                                <i class="fas fa-image fa-4x mb-3"></i>
                                <h5>Imagem do evento</h5>
                                <p class="text-muted">Em breve</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Descrição Detalhada -->
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle me-2"></i>Sobre o Evento</h4>
                    <div class="info-card">
                        <p><?php echo nl2br(htmlspecialchars($evento['descricao_detalhada'])); ?></p>
                    </div>
                </div>

                <!-- Requisitos -->
                <?php if (!empty($evento['requisitos'])): ?>
                    <div class="detail-section">
                        <h4><i class="fas fa-list-check me-2"></i>Requisitos</h4>
                        <div class="info-card">
                            <p><?php echo nl2br(htmlspecialchars($evento['requisitos'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- O que levar -->
                <?php if (!empty($evento['informacoes_adicionais'])): ?>
                    <div class="detail-section">
                        <h4><i class="fas fa-backpack me-2"></i>O que levar</h4>
                        <div class="info-card">
                            <p><?php echo nl2br(htmlspecialchars($evento['informacoes_adicionais'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Local -->
                <div class="detail-section">
                    <h4><i class="fas fa-map-marker-alt me-2"></i>Local do Evento</h4>
                    <div class="info-card">
                        <h6><?php echo htmlspecialchars($evento['local_nome']); ?></h6>
                        <p class="mb-2">
                            <i class="fas fa-map me-2"></i>
                            <?php echo htmlspecialchars($evento['local_endereco']); ?>
                        </p>
                        <p class="text-muted mb-0">
                            <i class="fas fa-city me-2"></i>
                            <?php echo htmlspecialchars($evento['local_cidade']); ?>, <?php echo htmlspecialchars($evento['local_estado']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Container de Inscrição -->
                <div id="subscription-container">
                    <!-- Status da Inscrição -->
                    <div id="subscription-status"></div>
                    
                    <!-- Botões de Inscrição -->
                    <?php if ($canEdit): ?>
                        <!-- Organizador - Botões de gerenciamento -->
                        <div class="subscription-card">
                            <h5 class="mb-3">
                                <i class="fas fa-user-tie me-2"></i>Seu Evento
                            </h5>
                            <div class="d-grid gap-2">
                                <a href="edit.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-light">
                                    <i class="fas fa-edit me-2"></i>Editar Evento
                                </a>
                                <a href="subscribers.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-outline-light">
                                    <i class="fas fa-users me-2"></i>Ver Inscritos
                                </a>
                            </div>
                        </div>
                    <?php elseif ($isLoggedIn && $userType === 'participante'): ?>
                        <!-- Participante - Sistema de inscrição -->
                        <div class="subscription-card">
                            <h5 class="mb-3">
                                <i class="fas fa-ticket-alt me-2"></i>
                                <?php if ($evento['evento_gratuito']): ?>
                                    Evento Gratuito
                                <?php else: ?>
                                    R$ <?php echo number_format($evento['preco'], 2, ',', '.'); ?>
                                <?php endif; ?>
                            </h5>
                            
                            <?php if ($canSubscribe): ?>
                                <button type="button" id="subscribe-btn" class="btn btn-inscricao">
                                    <i class="fas fa-user-plus me-2"></i>Inscrever-se
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-inscricao" disabled>
                                    <i class="fas fa-times me-2"></i>
                                    <?php 
                                    if ($evento['status'] !== 'publicado') {
                                        echo 'Evento não disponível';
                                    } elseif (strtotime($evento['data_inicio']) <= time()) {
                                        echo 'Evento já ocorreu';
                                    } elseif ($evento['capacidade_maxima'] && $evento['total_inscritos'] >= $evento['capacidade_maxima']) {
                                        echo 'Vagas esgotadas';
                                    } else {
                                        echo 'Inscrições encerradas';
                                    }
                                    ?>
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" id="unsubscribe-btn" class="btn btn-cancelar" style="display: none;">
                                <i class="fas fa-user-minus me-2"></i>Cancelar Inscrição
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Usuário não logado -->
                        <div class="subscription-card">
                            <h5 class="mb-3">
                                <i class="fas fa-ticket-alt me-2"></i>
                                <?php if ($evento['evento_gratuito']): ?>
                                    Evento Gratuito
                                <?php else: ?>
                                    R$ <?php echo number_format($evento['preco'], 2, ',', '.'); ?>
                                <?php endif; ?>
                            </h5>
                            <p class="mb-3">Faça login para se inscrever</p>
                            <a href="../auth/login.php" class="btn btn-light w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Informações de Participação -->
                <div class="attendance-info">
                    <h6><i class="fas fa-users me-2"></i>Participação</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Inscritos:</span>
                        <strong class="total-inscritos"><?php echo $evento['total_inscritos']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Vagas totais:</span>
                        <strong class="capacity-max"><?php echo $evento['capacidade_maxima'] ?: '∞'; ?></strong>
                    </div>
                    <?php if ($evento['capacidade_maxima']): ?>
                        <div class="d-flex justify-content-between">
                            <span>Vagas restantes:</span>
                            <strong class="text-success">
                                <?php echo max(0, $evento['capacidade_maxima'] - $evento['total_inscritos']); ?>
                            </strong>
                        </div>
                        
                        <div class="progress mt-3">
                            <div class="progress-bar bg-success" 
                                 style="width: <?php echo min(100, ($evento['total_inscritos'] / $evento['capacidade_maxima']) * 100); ?>%"
                                 aria-valuenow="<?php echo min(100, ($evento['total_inscritos'] / $evento['capacidade_maxima']) * 100); ?>"
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Organizador -->
                <div class="info-card">
                    <h6><i class="fas fa-user-tie me-2"></i>Organizador</h6>
                    <div class="organizer-card">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-building fa-2x text-primary me-3"></i>
                            <div>
                                <strong><?php echo htmlspecialchars($evento['nome_organizador']); ?></strong>
                                <div class="small text-muted">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?php echo htmlspecialchars($evento['email_organizador']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compartilhar -->
                <div class="info-card">
                    <h6><i class="fas fa-share-alt me-2"></i>Compartilhar</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm flex-fill" onclick="shareEvent('facebook')">
                            <i class="fab fa-facebook me-1"></i>Facebook
                        </button>
                        <button class="btn btn-outline-info btn-sm flex-fill" onclick="shareEvent('twitter')">
                            <i class="fab fa-twitter me-1"></i>Twitter
                        </button>
                        <button class="btn btn-outline-success btn-sm flex-fill" onclick="shareEvent('whatsapp')">
                            <i class="fab fa-whatsapp me-1"></i>WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sistema de Inscrições JavaScript -->
    <script src="../../public/js/subscriptions.js"></script>
    <script src="../../public/js/subscription-persistence.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Debug: Log do evento atual
            console.log('Evento carregado:', {
                id: <?php echo $evento['id_evento']; ?>,
                titulo: '<?php echo addslashes($evento['titulo']); ?>',
                url: window.location.href
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

            // Animação suave para seções
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            document.querySelectorAll('.detail-section, .info-card').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s ease-out';
                observer.observe(el);
            });

            // Listeners de eventos customizados do sistema de inscrições
            document.addEventListener('subscriptionSuccess', function(event) {
                console.log('Inscrição realizada com sucesso:', event.detail);
            });

            document.addEventListener('unsubscriptionSuccess', function(event) {
                console.log('Inscrição cancelada com sucesso:', event.detail);
            });
        });

        // Função de compartilhamento
        function shareEvent(platform) {
            const eventTitle = '<?php echo addslashes($evento['titulo']); ?>';
            const eventUrl = window.location.href;
            const eventText = `Confira este evento: ${eventTitle}`;

            let shareUrl = '';

            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(eventUrl)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(eventText)}&url=${encodeURIComponent(eventUrl)}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${encodeURIComponent(eventText + ' ' + eventUrl)}`;
                    break;
                default:
                    alert('Plataforma não suportada');
                    return;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        }

        // Função para copiar link
        function copyEventLink() {
            navigator.clipboard.writeText(window.location.href).then(function() {
                showToast('Link copiado para a área de transferência!', 'success');
            }, function(err) {
                console.error('Erro ao copiar: ', err);
                showToast('Erro ao copiar link', 'error');
            });
        }

        // Sistema de toast notifications (compatível com subscriptions.js)
        function showToast(message, type = 'info') {
            // Usar sistema de toast existente ou criar um simples
            if (typeof window.EventSubscriptions !== 'undefined' && window.EventSubscriptions.showToast) {
                window.EventSubscriptions.showToast(message, type);
                return;
            }

            // Fallback: criar toast simples
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
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>