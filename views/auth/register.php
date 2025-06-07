<?php
// ========================================
// REGISTER.PHP - VERSÃO CORRIGIDA RAILWAY
// ========================================
// Local: views/auth/register.php
// ========================================

// Desabilitar exibição de erros em produção
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão primeiro
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir constantes se não existirem
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://conecta-eventos.railway.internal');
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Conecta Eventos');
}

// Função para buscar arquivos em múltiplos caminhos
function findFile($relativePath) {
    $possiblePaths = [
        __DIR__ . '/../../' . $relativePath,
        __DIR__ . '/../' . $relativePath,
        __DIR__ . '/' . $relativePath,
        dirname(dirname(__DIR__)) . '/' . $relativePath,
        dirname(__DIR__) . '/' . $relativePath
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return false;
}

// Carregar arquivos necessários com fallback
$configFile = findFile('config/config.php');
if ($configFile) {
    require_once $configFile;
}

$databaseFile = findFile('config/database.php');
if ($databaseFile) {
    require_once $databaseFile;
}

// Funções de sessão com fallback
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('requireGuest')) {
    function requireGuest() {
        if (isLoggedIn()) {
            $redirect_url = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'organizador' 
                ? SITE_URL . '/views/dashboard/organizer.php'
                : SITE_URL . '/views/dashboard/participant.php';
            header('Location: ' . $redirect_url);
            exit();
        }
    }
}

// Redirecionar se já estiver logado
requireGuest();

$title = "Cadastro - " . SITE_NAME;
$error_message = '';
$success_message = '';

// Processar formulário de cadastro
if ($_POST) {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        
        // Validações básicas
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
            // Tentar processar o cadastro
            try {
                // Tentar usar Database class se disponível
                if (class_exists('Database')) {
                    $database = new Database();
                    $conn = $database->getConnection();
                    
                    // Verificar se email já existe
                    $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->rowCount() > 0) {
                        $error_message = 'Este e-mail já está cadastrado no sistema.';
                    } else {
                        // Criar usuário
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
                        
                        if ($stmt->execute([$nome, $email, $senha_hash, $tipo])) {
                            $success_message = 'Usuário cadastrado com sucesso! Faça login para continuar.';
                            // Limpar campos após sucesso
                            $_POST = [];
                        } else {
                            $error_message = 'Erro ao cadastrar usuário. Tente novamente.';
                        }
                    }
                } else {
                    // Fallback se Database class não estiver disponível
                    $error_message = 'Sistema temporariamente indisponível. Tente novamente mais tarde.';
                }
                
            } catch (Exception $e) {
                $error_message = 'Erro no sistema. Tente novamente mais tarde.';
            }
        }
        
    } catch (Exception $e) {
        $error_message = 'Erro no processamento. Tente novamente.';
    }
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

        .system-status {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
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
                        <!-- Status do Sistema -->
                        <div class="system-status">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>
                                <strong>Sistema:</strong> Online | 
                                <strong>Banco:</strong> <?php echo class_exists('Database') ? 'Conectado' : 'Verificando...'; ?> |
                                <strong>Sessão:</strong> Ativa
                            </small>
                        </div>

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
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Voltar ao Início
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-dismissible')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 10000);

        // Debug info no console
        console.log('🔧 Conecta Eventos - Register Debug');
        console.log('📍 URL:', window.location.href);
        console.log('🌐 SITE_URL:', '<?php echo SITE_URL; ?>');
        console.log('💾 Database Class:', <?php echo class_exists('Database') ? 'true' : 'false'; ?>);
        console.log('📁 Session Active:', <?php echo session_status() === PHP_SESSION_ACTIVE ? 'true' : 'false'; ?>);
    </script>
</body>
</html>