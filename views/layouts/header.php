<?php
// ========================================
// HEADER GLOBAL CORRIGIDO
// ========================================
// Local: views/layouts/header.php
// ========================================

// Garantir que as configurações estejam carregadas
if (!defined('SITE_URL')) {
    $config_path = '';
    $current_dir = __DIR__;
    
    // Tentar encontrar config.php subindo os diretórios
    for ($i = 0; $i < 5; $i++) {
        $config_file = $current_dir . '/config/config.php';
        if (file_exists($config_file)) {
            require_once $config_file;
            break;
        }
        $current_dir = dirname($current_dir);
    }
    
    // Se ainda não encontrou, definir URL padrão
    if (!defined('SITE_URL')) {
        define('SITE_URL', 'https://conecta-eventos-production.up.railway.app');
    }
}

// Garantir que as funções de sessão estejam carregadas
if (!function_exists('isLoggedIn')) {
    $session_path = '';
    $current_dir = __DIR__;
    
    for ($i = 0; $i < 5; $i++) {
        $session_file = $current_dir . '/includes/session.php';
        if (file_exists($session_file)) {
            require_once $session_file;
            break;
        }
        $current_dir = dirname($current_dir);
    }
}

// Fallback para funções de sessão se não carregaram
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    function getUserName() {
        return $_SESSION['user_name'] ?? 'Usuário';
    }
    
    function getUserType() {
        return $_SESSION['user_type'] ?? 'participante';
    }
    
    function isOrganizer() {
        return isLoggedIn() && getUserType() === 'organizador';
    }
    
    function isParticipant() {
        return isLoggedIn() && getUserType() === 'participante';
    }
}

// Detectar a seção atual baseada na URL
$current_url = $_SERVER['REQUEST_URI'] ?? '';
$is_dashboard = strpos($current_url, '/dashboard/') !== false;
$is_events = strpos($current_url, '/events/') !== false;
$is_auth = strpos($current_url, '/auth/') !== false;
$is_home = $current_url === '/' || strpos($current_url, '/index.php') !== false;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conecta Eventos - Plataforma completa para organizar e participar de eventos">
    <meta name="keywords" content="eventos, organizador, participante, inscrições">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Conecta Eventos - Plataforma de Eventos'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/public/images/favicon.ico">
    
    <style>
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: white !important;
            transform: translateY(-1px);
        }
        .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.1);
            border-radius: 0.375rem;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border-radius: 0.5rem;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-calendar-alt me-2"></i>
                <span>Conecta Eventos</span>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon">
                    <i class="fas fa-bars text-white"></i>
                </span>
            </button>
            
            <!-- Navigation Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Left Navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_home ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-home me-1"></i>Início
                        </a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <!-- Dashboard Link -->
                        <li class="nav-item">
                            <?php if (isOrganizer()): ?>
                                <a class="nav-link <?php echo $is_dashboard ? 'active' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/views/dashboard/organizer.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            <?php else: ?>
                                <a class="nav-link <?php echo $is_dashboard ? 'active' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/views/dashboard/participant.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Meu Painel
                                </a>
                            <?php endif; ?>
                        </li>
                        
                        <!-- Events Link for Organizers -->
                        <?php if (isOrganizer()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo $is_events ? 'active' : ''; ?>" 
                                   href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-calendar-plus me-1"></i>Eventos
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/events/create.php">
                                            <i class="fas fa-plus me-2"></i>Criar Evento
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/events/list.php">
                                            <i class="fas fa-list me-2"></i>Meus Eventos
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/events/list.php?status=rascunho">
                                            <i class="fas fa-edit me-2"></i>Rascunhos
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <!-- Right Navigation -->
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <!-- Notifications (placeholder for future) -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="#" title="Notificações">
                                <i class="fas fa-bell"></i>
                                <!-- <span class="notification-badge">3</span> -->
                            </a>
                        </li>
                        
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" 
                               href="#" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr(getUserName(), 0, 1)); ?>
                                </div>
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars(getUserName()); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <div class="dropdown-header">
                                        <strong><?php echo htmlspecialchars(getUserName()); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo ucfirst(getUserType()); ?></small>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                
                                <!-- Dashboard -->
                                <li>
                                    <?php if (isOrganizer()): ?>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/dashboard/organizer.php">
                                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                        </a>
                                    <?php else: ?>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/dashboard/participant.php">
                                            <i class="fas fa-tachometer-alt me-2"></i>Meu Painel
                                        </a>
                                    <?php endif; ?>
                                </li>
                                
                                <!-- Profile -->
                                <li>
                                    <a class="dropdown-item" href="#" onclick="alert('Em desenvolvimento!')">
                                        <i class="fas fa-user me-2"></i>Meu Perfil
                                    </a>
                                </li>
                                
                                <!-- Favorites (for participants) -->
                                <?php if (isParticipant()): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="alert('Em desenvolvimento!')">
                                            <i class="fas fa-heart me-2"></i>Favoritos
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Analytics (for organizers) -->
                                <?php if (isOrganizer()): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="alert('Em desenvolvimento!')">
                                            <i class="fas fa-chart-line me-2"></i>Analytics
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                
                                <!-- Settings -->
                                <li>
                                    <a class="dropdown-item" href="#" onclick="alert('Em desenvolvimento!')">
                                        <i class="fas fa-cog me-2"></i>Configurações
                                    </a>
                                </li>
                                
                                <!-- Logout -->
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Sair
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest Navigation -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/views/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Entrar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/views/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Cadastrar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main>
        <!-- Flash Messages Global -->
        <?php if (function_exists('showFlashMessage')): ?>
            <?php showFlashMessage(); ?>
        <?php endif; ?>
        
        <!-- Page Content will be inserted here -->