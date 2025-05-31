<?php
require_once '../../config/config.php';
require_once '../../includes/session.php';

// Redirecionar se já estiver logado
requireGuest();

$title = "Cadastro - " . SITE_NAME;
$error_message = '';
$success_message = '';

// Processar formulário de cadastro
if ($_POST) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha) || empty($tipo)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } elseif ($senha !== $confirmar_senha) {
        $error_message = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $error_message = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Por favor, insira um e-mail válido.';
    } elseif (!in_array($tipo, ['participante', 'organizador'])) {
        $error_message = 'Por favor, selecione um tipo de usuário válido.';
    } else {
        $result = $auth->register($nome, $email, $senha, $tipo);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Limpar campos após sucesso
            $_POST = [];
        } else {
            $error_message = $result['message'];
        }
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
        
        .btn-register {
            background: linear-gradient(45deg, #28a745, #1e7e34);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            color: white;
        }
        
        .btn-register:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .form-check-label {
            cursor: pointer;
        }
        
        .tipo-usuario-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .tipo-usuario-section h6 {
            color: #495057;
            margin-bottom: 0.75rem;
        }
        
        .form-check {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>
                            Criar Conta
                        </h2>
                        <p class="mb-0 mt-2 opacity-75">Junte-se ao Conecta Eventos</p>
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
                                <div class="mt-2">
                                    <a href="login.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-sign-in-alt me-1"></i>
                                        Fazer Login
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="form-floating">
                                <input type="text" 
                                       class="form-control" 
                                       id="nome" 
                                       name="nome" 
                                       placeholder="Seu nome completo"
                                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                                       required>
                                <label for="nome">
                                    <i class="fas fa-user me-2"></i>Nome Completo
                                </label>
                                <div class="invalid-feedback">
                                    Por favor, insira seu nome completo.
                                </div>
                            </div>
                            
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
                                       minlength="6"
                                       required>
                                <label for="senha">
                                    <i class="fas fa-lock me-2"></i>Senha
                                </label>
                                <div class="invalid-feedback">
                                    A senha deve ter pelo menos 6 caracteres.
                                </div>
                            </div>
                            
                            <div class="form-floating">
                                <input type="password" 
                                       class="form-control" 
                                       id="confirmar_senha" 
                                       name="confirmar_senha" 
                                       placeholder="Confirme sua senha"
                                       minlength="6"
                                       required>
                                <label for="confirmar_senha">
                                    <i class="fas fa-lock me-2"></i>Confirmar Senha
                                </label>
                                <div class="invalid-feedback">
                                    Por favor, confirme sua senha.
                                </div>
                            </div>
                            
                            <div class="tipo-usuario-section">
                                <h6>
                                    <i class="fas fa-users me-2"></i>Tipo de Usuário
                                </h6>
                                
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="tipo" 
                                           id="participante" 
                                           value="participante"
                                           <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'participante') ? 'checked' : ''; ?>
                                           required>
                                    <label class="form-check-label" for="participante">
                                        <strong>Participante</strong>
                                        <br>
                                        <small class="text-muted">Quero participar de eventos</small>
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="tipo" 
                                           id="organizador" 
                                           value="organizador"
                                           <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'organizador') ? 'checked' : ''; ?>
                                           required>
                                    <label class="form-check-label" for="organizador">
                                        <strong>Organizador</strong>
                                        <br>
                                        <small class="text-muted">Quero criar e gerenciar eventos</small>
                                    </label>
                                </div>
                                
                                <div class="invalid-feedback">
                                    Por favor, selecione um tipo de usuário.
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-register">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Criar Conta
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <p class="text-muted mb-0">
                                Já tem uma conta? 
                                <a href="login.php" class="text-decoration-none fw-bold">
                                    Faça login aqui
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
        
        // Validação personalizada para confirmação de senha
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            var senha = document.getElementById('senha').value;
            var confirmarSenha = this.value;
            
            if (senha !== confirmarSenha) {
                this.setCustomValidity('As senhas não coincidem.');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Auto-focus no primeiro campo
        document.getElementById('nome').focus();
        
        // Validação de tipo de usuário
        const radioButtons = document.querySelectorAll('input[name="tipo"]');
        const tipoSection = document.querySelector('.tipo-usuario-section');
        
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                tipoSection.classList.remove('is-invalid');
            });
        });
    </script>
</body>
</html>