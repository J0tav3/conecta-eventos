<?php
// ========================================
// EXEMPLO COMPLETO DE IMPLEMENTAÇÃO
// ========================================
// Local: views/layouts/header.php (versão final)
// ========================================

// Inicializar sessão se necessário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Carregar dependências
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';

// Dados do usuário para o atalho
$user_data = [
    'id' => getUserId(),
    'name' => getUserName(),
    'type' => getUserType(),
    'avatar' => null, // URL do avatar personalizado se disponível
    'is_online' => true,
    'last_activity' => time()
];

// Determinar URL do dashboard
$dashboard_url = SITE_URL;
$dashboard_title = 'Dashboard';

if (isLoggedIn()) {
    if (isOrganizer()) {
        $dashboard_url = SITE_URL . '/views/dashboard/organizer.php';
        $dashboard_title = 'Dashboard do Organizador';
    } else {
        $dashboard_url = SITE_URL . '/views/dashboard/participant.php';
        $dashboard_title = 'Painel do Participante';
    }
}

// Detectar página atual para highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Conecta Eventos'; ?></title>
    
    <!-- Meta tags SEO -->
    <meta name="description" content="Conecta Eventos - A melhor plataforma para organizar e participar de eventos">
    <meta name="keywords" content="eventos, organizador, participante, inscrições, networking">
    <meta name="author" content="Conecta Eventos">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $title ?? 'Conecta Eventos'; ?>">
    <meta property="og:description" content="Plataforma completa para eventos">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/public/images/og-image.jpg">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/public/images/favicon.ico">
    
    <!-- Preload do dashboard para usuários logados -->
    <?php if (isLoggedIn()): ?>
        <link rel="prefetch" href="<?php echo $dashboard_url; ?>">
    <?php endif; ?>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --user-shortcut-bg: rgba(255,255,255,0.1);
            --user-shortcut-hover: rgba(255,255,255,0.2);
            --user-shortcut-border: rgba(255,255,255,0.2);
        }
        
        .navbar-brand {
            font-weight: 700;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        /* ESTILO PRINCIPAL DO ATALHO DO USUÁRIO */
        .user-shortcut {
            background: var(--user-shortcut-bg);
            border: 1px solid var(--user-shortcut-border);
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            margin-left: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none !important;
            color: white !important;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            min-height: 44px; /* Mínimo para acessibilidade */
        }
        
        .user-shortcut:hover {
            background: var(--user-shortcut-hover);
            border-color: rgba(255,255,255,0.4);
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .user-shortcut:focus {
            outline: 2px solid rgba(255,255,255,0.7);
            outline-offset: 2px;
        }
        
        .user-shortcut:active {
            transform: translateY(0) scale(0.98);
        }
        
        /* Efeito shimmer no hover */
        .user-shortcut::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .user-shortcut:hover::before {
            left: 100%;
        }
        
        /* Avatar do usuário */
        .user-avatar {
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-weight: bold;
            font-size: 1.1rem;
            margin-right: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .user-shortcut:hover .user-avatar {
            transform: scale(1.1);
        }
        
        /* Indicador de status online */
        .online-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #28a745;
            border: 2px solid white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .online-indicator.offline {
            background: #dc3545;
        }
        
        /* Informações do usuário */
        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
            z-index: 2;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.2;
            white-space: nowrap;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-type {
            font-size: 0.75rem;
            opacity: 0.85;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Seta indicativa */
        .dashboard-arrow {
            margin-left: 0.75rem;
            transition: transform 0.3s ease;
            opacity: 0.8;
        }
        
        .user-shortcut:hover .dashboard-arrow {
            transform: translateX(4px);
            opacity: 1;
        }
        
        /* Badge de notificações */
        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .user-shortcut {
                margin-left: 0;
                margin-top: 0.5rem;
                min-width: 200px;
                justify-content: center;
            }
            
            .user-info {
                align-items: center;
                display: flex !important;
            }
            
            .dashboard-arrow {
                display: none !important;
            }
        }
        
        @media (max-width: 480px) {
            .user-name {
                max-width: 100px;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .user-shortcut.dark-mode {
                background: rgba(0,0,0,0.3);
                border-color: rgba(255,255,255,0.1);
            }
            
            .user-shortcut.dark-mode:hover {
                background: rgba(0,0,0,0.5);
            }
        }
        
        /* PWA mode */
        .user-shortcut.pwa-mode {
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        /* Accessibility */
        .user-shortcut:focus-visible {
            outline: 3px solid rgba(255,255,255,0.8);
            outline-offset: 2px;
        }
        
        /* Loading state */
        .user-avatar.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top: 2px solid #667eea;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Dropdown melhorado */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border-radius: 0.75rem;
            padding: 0.5rem;
            min-width: 280px;
            animation: fadeInUp 0.3s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            margin: 0.125rem 0;
        }
        
        .dropdown-item:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateX(8px);
        }
        
        .dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top" style="background: var(--primary-gradient);">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand text-white d-flex align-items-center" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-calendar-alt me-2"></i>
                <span>Conecta Eventos</span>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <i class="fas fa-bars text-white"></i>
            </button>
            
            <!-- Navigation Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Left Navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white-50 fw-medium" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-home me-1"></i>Início
                        </a>
                    </li>
                    
                    <?php if (isLoggedIn() && isOrganizer()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white-50 fw-medium" 
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-calendar-plus me-1"></i>Eventos
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/events/create.php">
                                    <i class="fas fa-plus me-2"></i>Criar Evento
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/events/list.php">
                                    <i class="fas fa-list me-2"></i>Meus Eventos
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/events/list.php?status=rascunho">
                                    <i class="fas fa-edit me-2"></i>Rascunhos
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Right Navigation -->
                <div class="d-flex align-items-center">
                    <?php if (isLoggedIn()): ?>
                        <!-- Notificações -->
                        <div class="position-relative me-3">
                            <a class="nav-link text-white-50 position-relative" href="#" title="Notificações">
                                <i class="fas fa-bell fs-5"></i>
                                <!-- <span class="notification-count">3</span> -->
                            </a>
                        </div>
                        
                        <!-- ATALHO PRINCIPAL DO USUÁRIO -->
                        <a href="<?php echo $dashboard_url; ?>" 
                           class="user-shortcut"
                           title="<?php echo $dashboard_title; ?>"
                           data-user-id="<?php echo $user_data['id']; ?>"
                           role="button"
                           tabindex="0"
                           aria-label="Ir para <?php echo $dashboard_title; ?>">
                            
                            <!-- Avatar com status online -->
                            <div class="user-avatar position-relative">
                                <?php if ($user_data['avatar']): ?>
                                    <img src="<?php echo $user_data['avatar']; ?>" 
                                         alt="Avatar" 
                                         class="w-100 h-100 rounded-circle">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                                <?php endif; ?>
                                
                                <div class="online-indicator <?php echo $user_data['is_online'] ? '' : 'offline'; ?>"
                                     title="<?php echo $user_data['is_online'] ? 'Online' : 'Offline'; ?>"></div>
                            </div>
                            
                            <!-- Informações do usuário -->
                            <div class="user-info d-none d-md-flex">
                                <span class="user-name">
                                    <?php echo htmlspecialchars($user_data['name']); ?>
                                </span>
                                <span class="user-type">
                                    <?php echo isOrganizer() ? 'Organizador' : 'Participante'; ?>
                                </span>
                            </div>
                            
                            <!-- Seta indicativa -->
                            <i class="fas fa-arrow-right dashboard-arrow d-none d-lg-inline"></i>
                        </a>
                        
                        <!-- Menu dropdown secundário -->
                        <div class="dropdown ms-2">
                            <a class="nav-link text-white-50" href="#" role="button" data-bs-toggle="dropdown" 
                               title="Mais opções" aria-label="Menu do usuário">
                                <i class="fas fa-ellipsis-v"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <!-- Header do usuário -->
                                <li>
                                    <div class="dropdown-header">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user_data['name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($user_data['type']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                
                                <!-- Dashboard (backup) -->
                                <li>
                                    <a class="dropdown-item" href="<?php echo $dashboard_url; ?>">
                                        <i class="fas fa-tachometer-alt me-2"></i>
                                        <?php echo isOrganizer() ? 'Dashboard' : 'Meu Painel'; ?>
                                    </a>
                                </li>
                                
                                <!-- Perfil -->
                                <li>
                                    <a class="dropdown-item" href="#" onclick="showComingSoon()">
                                        <i class="fas fa-user me-2"></i>Meu Perfil
                                    </a>
                                </li>
                                
                                <!-- Links específicos por tipo -->
                                <?php if (isParticipant()): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="showComingSoon()">
                                            <i class="fas fa-heart me-2"></i>Favoritos
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="showComingSoon()">
                                            <i class="fas fa-calendar me-2"></i>Meus Eventos
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="showComingSoon()">
                                            <i class="fas fa-chart-line me-2"></i>Analytics
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="showComingSoon()">
                                            <i class="fas fa-chart-bar me-2"></i>Relatórios
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                
                                <!-- Configurações -->
                                <li>
                                    <a class="dropdown-item" href="#" onclick="showComingSoon()">
                                        <i class="fas fa-cog me-2"></i>Configurações
                                    </a>
                                </li>
                                
                                <!-- Ajuda -->
                                <li>
                                    <a class="dropdown-item" href="#" onclick="showComingSoon()">
                                        <i class="fas fa-question-circle me-2"></i>Ajuda
                                    </a>
                                </li>
                                
                                <li><hr class="dropdown-divider"></li>
                                
                                <!-- Logout -->
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Sair
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Navegação para visitantes -->
                        <div class="d-flex align-items-center">
                            <a class="nav-link text-white-50 fw-medium me-3" 
                               href="<?php echo SITE_URL; ?>/views/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Entrar
                            </a>
                            <a class="btn btn-outline-light" 
                               href="<?php echo SITE_URL; ?>/views/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Cadastrar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Container principal -->
    <main>
        <!-- Flash Messages Globais -->
        <?php if (function_exists('showFlashMessage')): ?>
            <div id="flash-messages-container">
                <?php showFlashMessage(); ?>
            </div>
        <?php endif; ?>
        
        <!-- Breadcrumb automático (opcional) -->
        <?php if (isset($show_breadcrumb) && $show_breadcrumb): ?>
            <div class="container mt-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">
                                <i class="fas fa-home me-1"></i>Início
                            </a>
                        </li>
                        <?php if (isset($breadcrumb_items) && is_array($breadcrumb_items)): ?>
                            <?php foreach ($breadcrumb_items as $index => $item): ?>
                                <?php if ($index === count($breadcrumb_items) - 1): ?>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?php echo $item['url']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
        <?php endif; ?>
        
        <!-- Indicador de loading global -->
        <div id="global-loading" class="d-none">
            <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                 style="background: rgba(0,0,0,0.5); z-index: 9999;">
                <div class="text-center text-white">
                    <div class="spinner-border mb-3" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <div>Carregando...</div>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo da página será inserido aqui -->
    </main>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script do atalho do usuário -->
    <script>
        // ========================================
        // FUNCIONALIDADES DO ATALHO DO USUÁRIO
        // ========================================
        
        document.addEventListener('DOMContentLoaded', function() {
            const userShortcut = document.querySelector('.user-shortcut');
            const userAvatar = document.querySelector('.user-avatar');
            
            // Verificar se elementos existem
            if (!userShortcut) return;
            
            // ========================================
            // EVENTOS DO ATALHO
            // ========================================
            
            // Efeito hover melhorado
            userShortcut.addEventListener('mouseenter', function() {
                // Animar avatar
                if (userAvatar) {
                    userAvatar.style.transform = 'scale(1.1)';
                }
                
                // Mostrar tooltip
                showTooltip(this);
            });
            
            userShortcut.addEventListener('mouseleave', function() {
                // Resetar avatar
                if (userAvatar) {
                    userAvatar.style.transform = '';
                }
                
                // Esconder tooltip
                hideTooltip();
            });
            
            // Navegação por teclado
            userShortcut.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
            
            // Feedback visual no clique
            userShortcut.addEventListener('click', function(e) {
                // Efeito de clique
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Analytics
                trackDashboardAccess();
                
                // Loading state
                showGlobalLoading();
            });
            
            // ========================================
            // TOOLTIP DINÂMICO
            // ========================================
            
            function showTooltip(element) {
                hideTooltip(); // Remove tooltip existente
                
                const userName = element.querySelector('.user-name')?.textContent || '';
                const userType = element.querySelector('.user-type')?.textContent || '';
                const dashboardText = userType.toLowerCase().includes('organizador') ? 'Dashboard' : 'Meu Painel';
                
                const tooltip = document.createElement('div');
                tooltip.id = 'user-tooltip';
                tooltip.innerHTML = `
                    <div class="text-center">
                        <strong>Olá, ${userName}!</strong>
                        <br>
                        <small>Clique para ir ao ${dashboardText}</small>
                        <div class="tooltip-arrow"></div>
                    </div>
                `;
                
                // Estilo do tooltip
                tooltip.style.cssText = `
                    position: fixed;
                    background: rgba(0, 0, 0, 0.9);
                    color: white;
                    padding: 0.75rem 1rem;
                    border-radius: 0.5rem;
                    font-size: 0.85rem;
                    z-index: 9999;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
                    opacity: 0;
                    transform: translateY(-10px);
                    transition: all 0.3s ease;
                    pointer-events: none;
                    min-width: 160px;
                `;
                
                document.body.appendChild(tooltip);
                
                // Posicionar tooltip
                const rect = element.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                
                tooltip.style.top = `${rect.bottom + 10}px`;
                tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltipRect.width / 2)}px`;
                
                // Animar entrada
                setTimeout(() => {
                    tooltip.style.opacity = '1';
                    tooltip.style.transform = 'translateY(0)';
                }, 10);
            }
            
            function hideTooltip() {
                const tooltip = document.getElementById('user-tooltip');
                if (tooltip) {
                    tooltip.style.opacity = '0';
                    tooltip.style.transform = 'translateY(-10px)';
                    setTimeout(() => tooltip.remove(), 300);
                }
            }
            
            // ========================================
            // STATUS ONLINE/OFFLINE
            // ========================================
            
            function updateOnlineStatus() {
                const indicator = document.querySelector('.online-indicator');
                if (indicator) {
                    if (navigator.onLine) {
                        indicator.classList.remove('offline');
                        indicator.title = 'Online';
                    } else {
                        indicator.classList.add('offline');
                        indicator.title = 'Offline';
                    }
                }
            }
            
            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
            updateOnlineStatus();
            
            // ========================================
            // ATALHOS DE TECLADO
            // ========================================
            
            document.addEventListener('keydown', function(e) {
                // Alt + D para dashboard
                if (e.altKey && e.key === 'd') {
                    e.preventDefault();
                    userShortcut?.click();
                }
                
                // Alt + H para home
                if (e.altKey && e.key === 'h') {
                    e.preventDefault();
                    window.location.href = '<?php echo SITE_URL; ?>';
                }
            });
            
            // ========================================
            // LOADING GLOBAL
            // ========================================
            
            function showGlobalLoading() {
                const loading = document.getElementById('global-loading');
                if (loading) {
                    loading.classList.remove('d-none');
                    
                    // Auto-hide após 3 segundos (fallback)
                    setTimeout(() => {
                        hideGlobalLoading();
                    }, 3000);
                }
            }
            
            function hideGlobalLoading() {
                const loading = document.getElementById('global-loading');
                if (loading) {
                    loading.classList.add('d-none');
                }
            }
            
            // Esconder loading quando página carregar
            window.addEventListener('load', hideGlobalLoading);
            
            // ========================================
            // ANALYTICS E TRACKING
            // ========================================
            
            function trackDashboardAccess() {
                // Google Analytics
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'dashboard_access', {
                        'event_category': 'navigation',
                        'event_label': 'user_shortcut',
                        'user_type': '<?php echo getUserType(); ?>'
                    });
                }
                
                // Log para desenvolvimento
                console.log('Dashboard access via user shortcut');
            }
            
            // ========================================
            // RESPONSIVIDADE AVANÇADA
            // ========================================
            
            function handleResize() {
                const isMobile = window.innerWidth <= 768;
                const userInfo = document.querySelector('.user-info');
                
                if (userInfo) {
                    if (isMobile) {
                        userInfo.classList.remove('d-none', 'd-md-flex');
                        userInfo.classList.add('d-flex');
                    } else {
                        userInfo.classList.remove('d-flex');
                        userInfo.classList.add('d-none', 'd-md-flex');
                    }
                }
            }
            
            window.addEventListener('resize', handleResize);
            handleResize();
            
            // ========================================
            // PRELOAD E PERFORMANCE
            // ========================================
            
            // Preload dashboard on hover
            userShortcut.addEventListener('mouseenter', function() {
                const dashboardUrl = this.getAttribute('href');
                if (dashboardUrl && !document.querySelector(`link[href="${dashboardUrl}"]`)) {
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = dashboardUrl;
                    document.head.appendChild(link);
                }
            }, { once: true });
            
            console.log('✅ User shortcut initialized successfully');
        });
        
        // ========================================
        // FUNÇÕES GLOBAIS
        // ========================================
        
        function showComingSoon() {
            alert('🚧 Esta funcionalidade está em desenvolvimento!\n\nEm breve teremos esta feature disponível.');
        }
        
        // Função para mostrar notificações toast
        function showToast(message, type = 'info', duration = 4000) {
            const toastId = 'toast-' + Date.now();
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 400px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            `;
            
            const icons = {
                success: 'fas fa-check-circle',
                danger: 'fas fa-exclamation-triangle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            toast.innerHTML = `
                <i class="${icons[type] || icons.info} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove
            setTimeout(() => {
                const toastElement = document.getElementById(toastId);
                if (toastElement) {
                    const bsToast = bootstrap.Alert.getOrCreateInstance(toastElement);
                    bsToast.close();
                }
            }, duration);
        }
        
        // Auto-hide flash messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('#flash-messages-container .alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 5000);
            });
        });
    </script>
    
    <!-- CSS adicional para tooltips -->
    <style>
        .tooltip-arrow {
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-bottom: 5px solid rgba(0, 0, 0, 0.9);
        }
        
        /* Adicionar suporte para focus-visible */
        .user-shortcut:focus:not(:focus-visible) {
            outline: none;
        }
        
        /* Melhorar contraste em modo escuro */
        @media (prefers-color-scheme: dark) {
            .dropdown-menu {
                background-color: #2d3748;
                border: 1px solid #4a5568;
            }
            
            .dropdown-item {
                color: #e2e8f0;
            }
            
            .dropdown-item:hover {
                background-color: #4a5568;
                color: white;
            }
            
            .dropdown-header {
                background-color: #1a202c;
                color: #e2e8f0;
            }
        }
    </style>
</body>
</html>