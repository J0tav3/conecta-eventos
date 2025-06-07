<?php
// ========================================
// CONECTA EVENTOS - PÁGINA INICIAL RAILWAY
// ========================================
// Versão simplificada e funcional para Railway
// ========================================

require_once 'config/config.php';

// Verificar se há mensagem de logout
$logoutMessage = '';
$logoutType = '';
if (isset($_COOKIE['logout_message'])) {
    $logoutMessage = $_COOKIE['logout_message'];
    $logoutType = $_COOKIE['logout_type'] ?? 'success';
    
    // Limpar cookies
    setcookie('logout_message', '', time() - 3600, '/');
    setcookie('logout_type', '', time() - 3600, '/');
}

$title = "Conecta Eventos - Plataforma de Eventos";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }
        .hero-content {
            text-align: center;
        }
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 300;
            margin-bottom: 1rem;
        }
        .hero-content .lead {
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #0056b3, #004085);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-outline-light {
            border: 2px solid white;
            font-weight: 600;
        }
        .status-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        footer {
            background: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <!-- Status Badge -->
    <div class="status-badge">
        <i class="fas fa-check-circle me-2"></i>Railway Online
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-absolute w-100" style="z-index: 100;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-calendar-alt me-2"></i>Conecta Eventos
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="mt-4">
                    <a href="views/auth/register.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-user-plus me-2"></i>Começar Agora
                    </a>
                    <a href="views/auth/login.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                    </a>
                </div>
                
                <!-- Credenciais de teste -->
                <div class="mt-5">
                    <div class="card bg-white bg-opacity-10 border-0 mx-auto" style="max-width: 500px;">
                        <div class="card-body text-center">
                            <h5 class="card-title">
                                <i class="fas fa-key me-2"></i>Credenciais de Teste
                            </h5>
                            <p class="card-text mb-2">
                                <strong>Email:</strong> admin@conectaeventos.com<br>
                                <strong>Senha:</strong> admin123<br>
                                <strong>Tipo:</strong> Organizador
                            </p>
                            <small class="text-white-50">Use essas credenciais para testar o sistema</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-5 fw-bold">Por que escolher o Conecta Eventos?</h2>
                    <p class="lead text-muted">Uma plataforma completa para organizar e participar de eventos</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <h4>Crie Eventos</h4>
                        <p class="text-muted">Organize seus eventos de forma simples e intuitiva. Gerencie inscrições, participantes e muito mais.</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4>Descubra Eventos</h4>
                        <p class="text-muted">Encontre eventos interessantes na sua região. Filtre por categoria, data e localização.</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100 text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Conecte-se</h4>
                        <p class="text-muted">Participe de eventos, conheça pessoas novas e amplie sua rede de contatos.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Status Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-12">
                    <h3 class="mb-4">
                        <i class="fas fa-server me-2 text-success"></i>
                        Status do Sistema
                    </h3>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <h5>Banco de Dados</h5>
                            <span class="badge bg-light text-success">Online</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-user-shield fa-2x mb-2"></i>
                            <h5>Autenticação</h5>
                            <span class="badge bg-light text-success">Funcionando</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i>
                            <h5>Eventos</h5>
                            <span class="badge bg-light text-success">Ativo</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-cloud fa-2x mb-2"></i>
                            <h5>Railway</h5>
                            <span class="badge bg-light text-success">Deployed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="display-6 fw-bold">Como Funciona</h2>
                    <p class="lead text-muted">Comece a usar em 3 passos simples</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <span class="fs-3 fw-bold">1</span>
                        </div>
                        <h4>Cadastre-se</h4>
                        <p class="text-muted">Crie sua conta como participante ou organizador em poucos segundos.</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <span class="fs-3 fw-bold">2</span>
                        </div>
                        <h4>Explore ou Crie</h4>
                        <p class="text-muted">Encontre eventos interessantes ou crie seus próprios eventos.</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <span class="fs-3 fw-bold">3</span>
                        </div>
                        <h4>Participe</h4>
                        <p class="text-muted">Inscreva-se nos eventos e aproveite experiências incríveis.</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="views/auth/register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-rocket me-2"></i>Começar Agora
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Conecta Eventos</h5>
                    <p class="text-muted">Plataforma completa para organizar e participar de eventos.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="mb-3">
                        <span class="badge bg-success me-2">
                            <i class="fas fa-check-circle me-1"></i>Railway Online
                        </span>
                        <span class="badge bg-info">
                            <i class="fas fa-code me-1"></i>PHP 8.2
                        </span>
                    </div>
                    <p class="text-muted small">
                        Desenvolvido por João Vitor da Silva<br>
                        Deploy automatizado no Railway
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Conecta Eventos. Todos os direitos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="text-muted small">
                        <i class="fas fa-server me-1"></i>
                        Hospedado no Railway
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Animação suave nos cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Efeito parallax suave no hero
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const heroSection = document.querySelector('.hero-section');
            if (heroSection) {
                const rate = scrolled * -0.5;
                heroSection.style.transform = `translateY(${rate}px)`;
            }
        });

        // Console log para debug
        console.log('🎉 Conecta Eventos - Railway');
        console.log('📅 Sistema de eventos funcionando');
        console.log('🔧 Deploy: Railway');
        console.log('💻 PHP Version: <?php echo PHP_VERSION; ?>');
        console.log('🌐 Site URL: <?php echo SITE_URL; ?>');
        
        // Mostrar informações no console
        console.group('🔑 Credenciais de Teste');
        console.log('Email: admin@conectaeventos.com');
        console.log('Senha: admin123');
        console.log('Tipo: Organizador');
        console.groupEnd();
    </script>
</body>
</html>navbar-nav ms-auto">
                    <a class="nav-link" href="views/auth/login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a class="nav-link" href="views/auth/register.php">
                        <i class="fas fa-user-plus me-1"></i>Cadastrar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <!-- Mensagem de Logout -->
                <?php if ($logoutMessage): ?>
                    <div class="alert alert-<?php echo $logoutType; ?> alert-dismissible fade show mx-auto" style="max-width: 500px;" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($logoutMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <h1 class="mb-4">Conecte-se aos Melhores Eventos</h1>
                <p class="lead">Descubra, participe e organize eventos incríveis na nossa plataforma.</p>
                
                <div class="