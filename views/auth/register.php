<?php
// ==========================================
// PÁGINA DE CADASTRO - VERSÃO CORRIGIDA
// Local: views/auth/register.php
// ==========================================

require_once '../../config/config.php';
require_once '../../includes/session.php';

// Se já estiver logado, redirecionar
if (isLoggedIn()) {
    if (isOrganizer()) {
        header("Location: ../dashboard/organizer.php");
    } else {
        header("Location: ../dashboard/participant.php");
    }
    exit;
}

$title = "Cadastrar - Conecta Eventos";
$error_message = '';
$success_message = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once '../../controllers/AuthController.php';
        
        $authController = new AuthController();
        $result = $authController->register($_POST);
        
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    } catch (Exception $e) {
        error_log("Erro no cadastro: " . $e->getMessage());
        $error_message = "Erro interno. Tente novamente.";
    }
}

// URL base correta
$baseUrl = defined('SITE_URL') ? SITE_URL : 'https://conecta-eventos-production.up.railway.app';
$homeUrl = $baseUrl . '/index.php';
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
                            <i class="fas fa-user-plus fa-lg me-2"></i>
                            Junte-se a nós!
                        </h2>
                        <p class="fs-5 mb-4">
                            Crie sua conta e comece a descobrir eventos incríveis ou organize seus próprios eventos.
                        </p>
                        
                        <div class="row text-center mt-4">
                            <div class="col-6">
                                <div class="mb-2">
                                    <i class="fas fa-calendar-plus fa-2x"></i>
                                </div>
                                <h6>Organizar Eventos</h6>
                                <small>Crie e gerencie seus eventos</small>
                            </div>
                            <div class="col-6">
                                <div class="mb-2">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                                <h6>Participar</h6>
                                <small>Inscreva-se em eventos</small>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <p class="mb-2">
                                <small>Já tem uma conta?</small>
                            </p>
                            <a href="login.php" class="btn btn-outline-light">
                                <i class="fas fa-sign-in-alt me-2"></i>Fazer Login
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Lado direito - Formulário -->
                <div class="col-md-7 auth-right">
                    <div class="text-center mb-4">
                        <h3>Criar Conta</h3>
                        <p class="text-muted">Preencha os dados abaixo para se cadastrar</p>
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
                    <form method="POST" id="registerForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nome" 
                                       name="nome" 
                                       required
                                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                                       placeholder="Seu nome completo">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-mail *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="seu@email.com">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="senha" class="form-label">Senha *</label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="senha" 
                                           name="senha" 
                                           required
                                           minlength="6"
                                           placeholder="Mínimo 6 caracteres">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirma_senha" class="form-label">Confirmar Senha *</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirma_senha" 
                                       name="confirma_senha" 
                                       required
                                       placeholder="Digite a senha novamente">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefone" 
                                       name="telefone"
                                       value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>"
                                       placeholder="(11) 99999-9999">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="tipo_usuario" class="form-label">Tipo de Conta *</label>
                                <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                                    <option value="">Selecione...</option>
                                    <option value="participante" <?php echo ($_POST['tipo_usuario'] ?? '') === 'participante' ? 'selected' : ''; ?>>
                                        Participante (quero participar de eventos)
                                    </option>
                                    <option value="organizador" <?php echo ($_POST['tipo_usuario'] ?? '') === 'organizador' ? 'selected' : ''; ?>>
                                        Organizador (quero criar eventos)
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="cidade" 
                                       name="cidade"
                                       value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>"
                                       placeholder="Sua cidade">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Selecione...</option>
                                    <option value="AC" <?php echo ($_POST['estado'] ?? '') === 'AC' ? 'selected' : ''; ?>>Acre</option>
                                    <option value="AL" <?php echo ($_POST['estado'] ?? '') === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                                    <option value="AP" <?php echo ($_POST['estado'] ?? '') === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                                    <option value="AM" <?php echo ($_POST['estado'] ?? '') === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                                    <option value="BA" <?php echo ($_POST['estado'] ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                                    <option value="CE" <?php echo ($_POST['estado'] ?? '') === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                                    <option value="DF" <?php echo ($_POST['estado'] ?? '') === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                                    <option value="ES" <?php echo ($_POST['estado'] ?? '') === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                                    <option value="GO" <?php echo ($_POST['estado'] ?? '') === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                                    <option value="MA" <?php echo ($_POST['estado'] ?? '') === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                                    <option value="MT" <?php echo ($_POST['estado'] ?? '') === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                                    <option value="MS" <?php echo ($_POST['estado'] ?? '') === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                                    <option value="MG" <?php echo ($_POST['estado'] ?? '') === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                                    <option value="PA" <?php echo ($_POST['estado'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pará</option>
                                    <option value="PB" <?php echo ($_POST['estado'] ?? '') === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                                    <option value="PR" <?php echo ($_POST['estado'] ?? '') === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                                    <option value="PE" <?php echo ($_POST['estado'] ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                                    <option value="PI" <?php echo ($_POST['estado'] ?? '') === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                                    <option value="RJ" <?php echo ($_POST['estado'] ?? '') === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                    <option value="RN" <?php echo ($_POST['estado'] ?? '') === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                                    <option value="RS" <?php echo ($_POST['estado'] ?? '') === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                                    <option value="RO" <?php echo ($_POST['estado'] ?? '') === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                                    <option value="RR" <?php echo ($_POST['estado'] ?? '') === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                                    <option value="SC" <?php echo ($_POST['estado'] ?? '') === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                                    <option value="SP" <?php echo ($_POST['estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                                    <option value="SE" <?php echo ($_POST['estado'] ?? '') === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                                    <option value="TO" <?php echo ($_POST['estado'] ?? '') === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="termos" name="termos" required>
                                <label class="form-check-label" for="termos">
                                    Eu aceito os <a href="#" class="text-decoration-none">Termos de Uso</a> e 
                                    <a href="#" class="text-decoration-none">Política de Privacidade</a> *
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Criar Conta
                            </button>
                            
                            <a href="<?php echo $homeUrl; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar ao Início
                            </a>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Já tem uma conta? 
                                <a href="login.php" class="text-decoration-none">Fazer login</a>
                            </small>
                        </div>
                    </form>
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
            
            // Validação de senhas
            const form = document.getElementById('registerForm');
            const senha = document.getElementById('senha');
            const confirmaSenha = document.getElementById('confirma_senha');
            
            function validatePasswords() {
                if (senha.value !== confirmaSenha.value) {
                    confirmaSenha.setCustomValidity('As senhas não coincidem');
                } else {
                    confirmaSenha.setCustomValidity('');
                }
            }
            
            senha.addEventListener('change', validatePasswords);
            confirmaSenha.addEventListener('keyup', validatePasswords);
            
            // Máscara de telefone
            const telefone = document.getElementById('telefone');
            telefone.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            });
            
            // Loading state no submit
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando conta...';
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
        });
    </script>
</body>
</html>