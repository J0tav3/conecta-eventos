<?php
require_once '../../config/config.php';
require_once '../../includes/session.php';

// Redirecionar se já estiver logado
requireGuest();

$title = "Login - " . SITE_NAME;
$error_message = '';
$success_message = '';

// Processar formulário de login
if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($email) && !empty($senha)) {
        $result = $auth->login($email, $senha);
        
        if ($result['success']) {
            // Redirecionar para dashboard
            header('Location: ' . $result['redirect']);
            exit();
        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = 'Por favor, preencha todos os campos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        .auth-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .auth-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .auth-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .btn-login {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
        }
        
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
    </style>
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2 class="mb-0">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Fazer Login
                        </h2>
                        <p class="mb-0 mt-2 opacity-75">Acesse sua conta no Conecta Eventos</p>
                    </div>
                    
                    <div class="auth-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
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
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="form-floating">
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="seu@email.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required>
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>E-mail
                                </label>
                                <div class="invalid-feedback">
                                    Por favor, insira um e-mail válido.
                                </div>
                            </div>
                            
                            <div class="form-floating">
                                <input type="password" 
                                       class="form-control" 
                                       id="senha" 
                                       name="senha" 
                                       placeholder="Sua senha"
                                       required>
                                <label for="senha">
                                    <i class="fas fa-lock me-2"></i>Senha
                                </label>
                                <div class="invalid-feedback">
                                    Por favor, insira sua senha.
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Entrar
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <p class="text-muted mb-0">
                                Não tem uma conta? 
                                <a href="register.php" class="text-decoration-none fw-bold">
                                    Cadastre-se aqui
                                </a>
                            </p>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a href="../../index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Voltar ao Início
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FontAwesome para ícones -->
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validação de formulário
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Auto-focus no primeiro campo
        document.getElementById('email').focus();
    </script>
</body>
</html>