<?php
// ==========================================
// PÁGINA DE LOGIN - VERSÃO CORRIGIDA
// Local: views/auth/login.php
// ==========================================

require_once '../../config/config.php';
require_once '../../includes/session.php';

// Se já estiver logado, redirecionar
if (function_exists('isLoggedIn') && isLoggedIn()) {
    if (function_exists('isOrganizer') && isOrganizer()) {
        header("Location: ../dashboard/organizer.php");
    } else {
        header("Location: ../dashboard/participant.php");
    }
    exit;
}

$title = "Login - Conecta Eventos";
$error_message = '';
$success_message = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        
        if (empty($email) || empty($senha)) {
            $error_message = "Por favor, preencha todos os campos.";
        } else {
            // Verificar se existe AuthController
            if (file_exists('../../controllers/AuthController.php')) {
                require_once '../../controllers/AuthController.php';
                $authController = new AuthController();
                $result = $authController->login($email, $senha);
                
                if ($result['success']) {
                    // Redirecionar baseado no tipo de usuário
                    if ($result['user']['tipo_usuario'] === 'organizador') {
                        header("Location: ../dashboard/organizer.php");
                    } else {
                        header("Location: ../dashboard/participant.php");
                    }
                    exit;
                } else {
                    $error_message = $result['message'];
                }
            } else {
                $error_message = "Sistema de autenticação não disponível.";
            }
        }
    } catch (Exception $e) {
        error_log("Erro no login: " . $e->getMessage());
        $error_message = "Erro interno. Tente novamente.";
    }
}

// URL base correta - Usar caminho relativo
$homeUrl = '../../index.php';

// Verificar se veio da página de registro
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success_message = "Conta criada com sucesso! Faça login para continuar.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .auth-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            margin: 2rem auto;
        }
        
        .auth-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        
        .auth-right {
            padding: 3rem;
        }
        
        .form-control {
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .btn-outline-secondary {
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
            transition: transform 0.2s ease;
        }
        
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .auth-left {
                padding: 2rem;
            }
            .auth-right {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header simples -->
    <nav class="navbar navbar-dark position-fixed w-100 top-0" style="background: rgba(0, 0, 0, 0.1); backdrop-filter: blur(10px); z-index: 1000;">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            <div class="navbar-nav">
                <a class="nav-link text-white" href="<?php echo $homeUrl; ?>">
                    <i class="fas fa-arrow-left me-1"></i>Voltar ao Início
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5 pt-5">
        <div class="auth-container">
            <div class="row g-0">
                <!-- Lado esquerdo - Informações -->
                <div class="col-md-5 auth-left">
                    <div>
                        <h2 class="mb-4">
                            <i class="fas fa-sign-in-alt fa-lg me-2"></i>
                            Bem-vindo de volta!
                        </h2>
                        <p class="fs-5 mb-4">
                            Faça login para acessar sua conta e descobrir eventos incríveis ou gerenciar seus próprios eventos.
                        </p>
                        
                        <div class="row text-center mt-4">
                            <div class="col-6">
                                <div class="feature-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h6>Seus Eventos</h6>
                                <small>Acesse seus eventos criados</small>
                            </div>
                            <div class="col-6">
                                <div class="feature-icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <h6>Favoritos</h6>
                                <small>Eventos que você salvou</small>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <p class="mb-2">
                                <small>Ainda não tem uma conta?</small>
                            </p>
                            <a href="register.php" class="btn btn-outline-light">
                                <i class="fas fa-user-plus me-2"></i>Criar Conta
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Lado direito - Formulário -->
                <div class="col-md-7 auth-right">
                    <div class="text-center mb-4">
                        <h3>Fazer Login</h3>
                        <p class="text-muted">Entre com suas credenciais para acessar sua conta</p>
                    </div>
                    
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
                    
                    <!-- Formulário -->
                    <form method="POST" id="loginForm">
                        <div class="mb-4">
                            <label for="email" class="form-label">E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="seu@email.com"
                                       autocomplete="email">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="senha" class="form-label">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="senha" 
                                       name="senha" 
                                       required
                                       placeholder="Sua senha"
                                       autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="lembrar" name="lembrar">
                                    <label class="form-check-label" for="lembrar">
                                        Lembrar-me
                                    </label>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <a href="#" class="text-decoration-none text-muted">
                                    <small>Esqueceu a senha?</small>
                                </a>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Entrar
                            </button>
                        </div>
                        
                        <div class="text-center mb-3">
                            <small class="text-muted">Ou continue como</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="<?php echo $homeUrl; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-eye me-2"></i>Visitante (Ver Eventos)
                            </a>
                        </div>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                Não tem uma conta? 
                                <a href="register.php" class="text-decoration-none">Criar conta</a>
                            </small>
                        </div>
                    </form>
                    
                    <!-- Demo Login (opcional) -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="text-muted mb-2">
                            <i class="fas fa-info-circle me-2"></i>Contas de Teste
                        </h6>
                        <small class="text-muted">
                            <strong>Organizador:</strong> admin@conectaeventos.com / admin123<br>
                            <strong>Participante:</strong> user@conectaeventos.com / user123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('senha');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
            
            // Loading state no submit
            const form = document.getElementById('loginForm');
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Entrando...';
                
                // Re-enable after 5 seconds if still on page (error case)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 5000);
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
            
            // Focus no primeiro campo
            document.getElementById('email').focus();
            
            // Demo login buttons
            const emailField = document.getElementById('email');
            const senhaField = document.getElementById('senha');
            
            // Adicionar botões de login rápido
            const demoSection = document.querySelector('.bg-light');
            if (demoSection) {
                const adminBtn = document.createElement('button');
                adminBtn.type = 'button';
                adminBtn.className = 'btn btn-sm btn-outline-primary me-2 mt-2';
                adminBtn.innerHTML = '<i class="fas fa-user-cog me-1"></i>Login Admin';
                adminBtn.onclick = function() {
                    emailField.value = 'admin@conectaeventos.com';
                    senhaField.value = 'admin123';
                };
                
                const userBtn = document.createElement('button');
                userBtn.type = 'button';
                userBtn.className = 'btn btn-sm btn-outline-success mt-2';
                userBtn.innerHTML = '<i class="fas fa-user me-1"></i>Login User';
                userBtn.onclick = function() {
                    emailField.value = 'user@conectaeventos.com';
                    senhaField.value = 'user123';
                };
                
                demoSection.appendChild(document.createElement('br'));
                demoSection.appendChild(adminBtn);
                demoSection.appendChild(userBtn);
            }
        });
    </script>
</body>
</html>