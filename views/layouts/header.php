<?php
// Verifica se a sessão está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Informações do usuário
$usuario_logado = isset($_SESSION['usuario_id']);
$nome_usuario = $usuario_logado ? ($_SESSION['nome_usuario'] ?? 'Usuário') : null;
$email_usuario = $usuario_logado ? ($_SESSION['email_usuario'] ?? '') : null;
$tipo_usuario = $usuario_logado ? ($_SESSION['tipo_usuario'] ?? 'participante') : null;
$avatar_usuario = $usuario_logado ? ($_SESSION['avatar_usuario'] ?? null) : null;

// Função para gerar iniciais do nome
function getInitials($nome) {
    $palavras = explode(' ', trim($nome));
    $iniciais = '';
    foreach ($palavras as $palavra) {
        if (!empty($palavra)) {
            $iniciais .= strtoupper(substr($palavra, 0, 1));
        }
        if (strlen($iniciais) >= 2) break;
    }
    return $iniciais ?: 'U';
}

// Função para determinar cor do avatar baseada no nome
function getAvatarColor($nome) {
    $cores = [
        '#007bff', '#28a745', '#dc3545', '#ffc107', 
        '#17a2b8', '#6f42c1', '#e83e8c', '#fd7e14',
        '#20c997', '#6610f2', '#e91e63', '#795548'
    ];
    $indice = abs(crc32($nome)) % count($cores);
    return $cores[$indice];
}

$iniciais_usuario = $usuario_logado ? getInitials($nome_usuario) : '';
$cor_avatar = $usuario_logado ? getAvatarColor($nome_usuario) : '#007bff';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Conecta Eventos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Customizados -->
    <link href="/public/css/style.css" rel="stylesheet">
    <link href="/public/css/user-shortcut.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/public/images/favicon.ico">
    
    <!-- Meta tags para SEO -->
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Conecta Eventos - Plataforma de eventos e networking'; ?>">
    <meta name="keywords" content="eventos, networking, palestras, workshops, conecta eventos">
    <meta name="author" content="Conecta Eventos">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title : 'Conecta Eventos'; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? $page_description : 'Descubra e participe dos melhores eventos'; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="https://conecta-eventos-production.up.railway.app/public/images/og-image.jpg">
    
    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Conecta Eventos",
        "url": "https://conecta-eventos-production.up.railway.app",
        "description": "Plataforma de eventos e networking",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "https://conecta-eventos-production.up.railway.app/buscar?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
</head>
<body>
    <!-- Header Principal -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid px-4">
                <!-- Logo -->
                <a class="navbar-brand d-flex align-items-center" href="/">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <span class="fw-bold">Conecta Eventos</span>
                </a>

                <!-- Botão Mobile Menu -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Menu Principal -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" 
                               href="/">
                                <i class="fas fa-home me-1"></i>Início
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'eventos.php') ? 'active' : ''; ?>" 
                               href="/eventos">
                                <i class="fas fa-calendar me-1"></i>Eventos
                            </a>
                        </li>
                        <?php if ($usuario_logado && $tipo_usuario === 'organizador'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'criar-evento.php') ? 'active' : ''; ?>" 
                               href="/criar-evento">
                                <i class="fas fa-plus me-1"></i>Criar Evento
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'sobre.php') ? 'active' : ''; ?>" 
                               href="/sobre">
                                <i class="fas fa-info-circle me-1"></i>Sobre
                            </a>
                        </li>
                    </ul>

                    <!-- Área do Usuário -->
                    <div class="navbar-nav">
                        <?php if ($usuario_logado): ?>
                            <!-- Dropdown do Usuário -->
                            <div class="user-dropdown">
                                <a href="#" class="user-trigger" role="button" aria-haspopup="true" aria-expanded="false">
                                    <div class="user-avatar" style="background: <?php echo $cor_avatar; ?>">
                                        <?php if ($avatar_usuario): ?>
                                            <img src="<?php echo htmlspecialchars($avatar_usuario); ?>" 
                                                 alt="Avatar de <?php echo htmlspecialchars($nome_usuario); ?>"
                                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                        <?php else: ?>
                                            <?php echo $iniciais_usuario; ?>
                                        <?php endif; ?>
                                        <div class="user-status" title="Online"></div>
                                    </div>
                                    <span class="user-name"><?php echo htmlspecialchars($nome_usuario); ?></span>
                                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                                </a>

                                <div class="dropdown-menu">
                                    <!-- Header do Dropdown -->
                                    <div class="dropdown-header">
                                        <div class="dropdown-user-info">
                                            <div class="dropdown-avatar" style="background: <?php echo $cor_avatar; ?>">
                                                <?php if ($avatar_usuario): ?>
                                                    <img src="<?php echo htmlspecialchars($avatar_usuario); ?>" 
                                                         alt="Avatar de <?php echo htmlspecialchars($nome_usuario); ?>"
                                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                                <?php else: ?>
                                                    <?php echo $iniciais_usuario; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="dropdown-user-details">
                                                <h4><?php echo htmlspecialchars($nome_usuario); ?></h4>
                                                <p><?php echo htmlspecialchars($email_usuario); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Menu Items -->
                                    <a href="/dashboard" onclick="navigateToDashboard(); return false;">
                                        <i class="fas fa-tachometer-alt"></i>
                                        Dashboard
                                    </a>

                                    <a href="/perfil" onclick="quickAction('profile'); return false;">
                                        <i class="fas fa-user"></i>
                                        Meu Perfil
                                    </a>

                                    <?php if ($tipo_usuario === 'organizador'): ?>
                                    <a href="/meus-eventos">
                                        <i class="fas fa-calendar-check"></i>
                                        Meus Eventos
                                    </a>
                                    <?php endif; ?>

                                    <a href="/inscricoes">
                                        <i class="fas fa-ticket-alt"></i>
                                        Minhas Inscrições
                                    </a>

                                    <div class="dropdown-divider"></div>

                                    <a href="/configuracoes" onclick="quickAction('settings'); return false;">
                                        <i class="fas fa-cog"></i>
                                        Configurações
                                    </a>

                                    <a href="/notificacoes">
                                        <i class="fas fa-bell"></i>
                                        Notificações
                                        <?php 
                                        // Verificar se há notificações não lidas
                                        $notificacoes_nao_lidas = 0; // Implementar lógica de contagem
                                        if ($notificacoes_nao_lidas > 0): 
                                        ?>
                                        <span class="notification-badge"><?php echo $notificacoes_nao_lidas; ?></span>
                                        <?php endif; ?>
                                    </a>

                                    <a href="/ajuda" onclick="quickAction('help'); return false;">
                                        <i class="fas fa-question-circle"></i>
                                        Ajuda & Suporte
                                    </a>

                                    <div class="dropdown-divider"></div>

                                    <a href="/logout" class="text-danger" onclick="quickAction('logout'); return false;">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Sair
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Usuário não logado -->
                            <a href="/login" class="btn btn-outline-light me-2">
                                <i class="fas fa-sign-in-alt me-1"></i>Entrar
                            </a>
                            <a href="/registro" class="btn btn-light">
                                <i class="fas fa-user-plus me-1"></i>Cadastrar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Breadcrumb (opcional) -->
        <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
        <div class="breadcrumb-container">
            <div class="container-fluid px-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/" class="text-white-50">
                                <i class="fas fa-home"></i>
                            </a>
                        </li>
                        <?php foreach ($breadcrumb as $item): ?>
                            <?php if (isset($item['url'])): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?php echo htmlspecialchars($item['url']); ?>" class="text-white-50">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="breadcrumb-item active text-white" aria-current="page">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </header>

    <!-- Alert Global (para mensagens do sistema) -->
    <?php if (isset($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo $_SESSION['mensagem_tipo'] ?? 'info'; ?> alert-dismissible fade show m-0" role="alert">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-<?php echo ($_SESSION['mensagem_tipo'] ?? 'info') === 'success' ? 'check-circle' : (($_SESSION['mensagem_tipo'] ?? 'info') === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php 
    unset($_SESSION['mensagem'], $_SESSION['mensagem_tipo']); 
    endif; 
    ?>

    <!-- Main Content Container -->
    <main class="main-content">

    <!-- Scripts essenciais no head -->
    <script>
        // Configurações globais
        window.ConectaEventos = {
            baseUrl: '<?php echo 'https://' . $_SERVER['HTTP_HOST']; ?>',
            userId: <?php echo $usuario_logado ? $_SESSION['usuario_id'] : 'null'; ?>,
            userType: '<?php echo $tipo_usuario; ?>',
            isLoggedIn: <?php echo $usuario_logado ? 'true' : 'false'; ?>,
            csrfToken: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
        };

        // Função para mostrar notificações toast
        function showToast(message, type = 'info', duration = 5000) {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'danger' ? 'exclamation-triangle' : 'info-circle')} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, { delay: duration });
            bsToast.show();
            
            // Remove o elemento após ser escondido
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        // Função para confirmação de ações
        function confirmAction(message, callback, title = 'Confirmar Ação') {
            if (confirm(message)) {
                if (typeof callback === 'function') {
                    callback();
                }
                return true;
            }
            return false;
        }

        // Função para loading overlay
        function showLoading(show = true) {
            let overlay = document.getElementById('loading-overlay');
            
            if (show) {
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'loading-overlay';
                    overlay.className = 'loading-overlay';
                    overlay.innerHTML = `
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <div class="loading-text mt-3">Carregando...</div>
                        </div>
                    `;
                    document.body.appendChild(overlay);
                }
                overlay.style.display = 'flex';
            } else if (overlay) {
                overlay.style.display = 'none';
            }
        }

        // Atalhos de teclado globais
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K para busca rápida
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[type="search"], .search-input');
                if (searchInput) {
                    searchInput.focus();
                }
            }
        });

        // Função para atualizar URL sem recarregar
        function updateUrl(url, title = null) {
            if (history.pushState) {
                history.pushState(null, title, url);
                if (title) document.title = title;
            }
        }

        // Auto-dismiss de alerts após 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

    <!-- CSS adicional para loading e toast -->
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            backdrop-filter: blur(2px);
        }

        .loading-spinner {
            text-align: center;
            color: white;
        }

        .loading-text {
            font-size: 14px;
            opacity: 0.9;
        }

        .main-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 0 2px;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        .breadcrumb-container {
            background: rgba(0, 0, 0, 0.1);
            padding: 8px 0;
        }

        .breadcrumb {
            background: none;
            padding: 0;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-outline-light:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-light:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Responsive adjustments */
        @media (max-width: 991px) {
            .navbar-nav .nav-link {
                padding: 12px 16px;
                margin: 2px 0;
            }
            
            .user-dropdown .dropdown-menu {
                position: static;
                box-shadow: none;
                border: 1px solid rgba(255, 255, 255, 0.2);
                background: rgba(255, 255, 255, 0.95);
                margin-top: 8px;
                border-radius: 12px;
            }
        }

        /* Toast customizations */
        .toast-container .toast {
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        /* Melhorias de acessibilidade */
        .nav-link:focus,
        .btn:focus,
        .user-trigger:focus {
            outline: 2px solid rgba(255, 255, 255, 0.8);
            outline-offset: 2px;
        }

        /* Animações suaves */
        * {
            transition: all 0.3s ease;
        }

        .navbar-toggler {
            border: none;
            padding: 8px;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }
    </style>