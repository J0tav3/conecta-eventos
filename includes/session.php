<?php
// ========================================
// SISTEMA DE SESSÃO SIMPLIFICADO
// ========================================
// Local: includes/session.php
// ========================================

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir constantes se não estiverem definidas
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://conecta-eventos-production.up.railway.app');
}

/**
 * Verifica se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtém o ID do usuário logado
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtém o nome do usuário logado
 */
function getUserName() {
    return $_SESSION['user_name'] ?? 'Usuário';
}

/**
 * Obtém o email do usuário logado
 */
function getUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

/**
 * Obtém o tipo do usuário logado
 */
function getUserType() {
    return $_SESSION['user_type'] ?? 'participante';
}

/**
 * Verifica se o usuário é organizador
 */
function isOrganizer() {
    return isLoggedIn() && getUserType() === 'organizador';
}

/**
 * Verifica se o usuário é participante
 */
function isParticipant() {
    return isLoggedIn() && getUserType() === 'participante';
}

/**
 * Obtém dados completos do usuário logado
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => getUserId(),
        'name' => getUserName(),
        'email' => getUserEmail(),
        'type' => getUserType(),
        'login_time' => $_SESSION['login_time'] ?? null
    ];
}

/**
 * Requer que o usuário esteja logado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/views/auth/login.php');
        exit();
    }
}

/**
 * Requer que o usuário NÃO esteja logado (para páginas de login/registro)
 */
function requireGuest() {
    if (isLoggedIn()) {
        $redirectUrl = isOrganizer() 
            ? SITE_URL . '/views/dashboard/organizer.php'
            : SITE_URL . '/views/dashboard/participant.php';
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Realiza login do usuário
 */
function loginUser($userId, $userName, $userEmail, $userType) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['user_type'] = $userType;
    $_SESSION['login_time'] = time();
    
    // Regenerar ID da sessão para segurança
    session_regenerate_id(true);
    
    return true;
}

/**
 * Realiza logout do usuário
 */
function logoutUser() {
    // Destruir todas as variáveis de sessão
    $_SESSION = array();
    
    // Se existe um cookie de sessão, deletá-lo
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir a sessão
    session_destroy();
    
    return true;
}

/**
 * Verifica se a sessão é válida (prevenção contra sequestro de sessão)
 */
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Verificar se a sessão não expirou (24 horas)
    $maxLifetime = 24 * 60 * 60; // 24 horas em segundos
    
    if (isset($_SESSION['login_time']) && 
        (time() - $_SESSION['login_time']) > $maxLifetime) {
        
        logoutUser();
        return false;
    }
    
    return true;
}

/**
 * Define uma mensagem flash
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Obtém uma mensagem flash
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return ['message' => $message, 'type' => $type];
    }
    
    return null;
}

/**
 * Exibe uma mensagem flash (HTML)
 */
function showFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'danger' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        
        $class = $alertClass[$flash['type']] ?? 'alert-info';
        
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-info-circle me-2"></i>';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// Validar sessão automaticamente
if (isLoggedIn()) {
    validateSession();
}

// AuthController simplificado como classe compatível
class AuthController {
    public function isLoggedIn() {
        return isLoggedIn();
    }
    
    public function getCurrentUser() {
        return getCurrentUser();
    }
    
    public function isOrganizer() {
        return isOrganizer();
    }
    
    public function isParticipant() {
        return isParticipant();
    }
    
    public function requireAuth() {
        requireLogin();
    }
    
    public function requireGuest() {
        requireGuest();
    }
    
    public function login($email, $senha) {
        // Tentar autenticar com database se disponível
        if (class_exists('Database')) {
            try {
                $database = new Database();
                $conn = $database->getConnection();
                
                $stmt = $conn->prepare("SELECT id_usuario, nome, email, senha, tipo, ativo FROM usuarios WHERE email = ? AND ativo = 1");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch();
                    
                    if (password_verify($senha, $user['senha'])) {
                        loginUser($user['id_usuario'], $user['nome'], $user['email'], $user['tipo']);
                        
                        $redirectUrl = $user['tipo'] === 'organizador' 
                            ? SITE_URL . '/views/dashboard/organizer.php'
                            : SITE_URL . '/views/dashboard/participant.php';
                            
                        return [
                            'success' => true,
                            'message' => 'Login realizado com sucesso!',
                            'redirect' => $redirectUrl
                        ];
                    } else {
                        return ['success' => false, 'message' => 'Senha incorreta.'];
                    }
                }
                
                return ['success' => false, 'message' => 'E-mail não encontrado.'];
                
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erro no sistema de login.'];
            }
        }
        
        return ['success' => false, 'message' => 'Sistema de autenticação indisponível.'];
    }
    
    public function register($nome, $email, $senha, $confirmar_senha, $tipo) {
        // Validar senhas
        if ($senha !== $confirmar_senha) {
            return ['success' => false, 'message' => 'As senhas não coincidem.'];
        }
        
        // Tentar criar usuário com database se disponível
        if (class_exists('Database')) {
            try {
                $database = new Database();
                $conn = $database->getConnection();
                
                // Verificar se email já existe
                $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    return ['success' => false, 'message' => 'Este e-mail já está cadastrado.'];
                }
                
                // Criar usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$nome, $email, $senha_hash, $tipo])) {
                    return [
                        'success' => true,
                        'message' => 'Usuário cadastrado com sucesso!',
                        'user_id' => $conn->lastInsertId()
                    ];
                }
                
                return ['success' => false, 'message' => 'Erro ao cadastrar usuário.'];
                
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Erro no sistema de cadastro.'];
            }
        }
        
        return ['success' => false, 'message' => 'Sistema de cadastro indisponível.'];
    }
    
    public function logout() {
        logoutUser();
        return [
            'success' => true,
            'message' => 'Logout realizado com sucesso!',
            'redirect' => SITE_URL . '/index.php'
        ];
    }
    
    public function validateSession() {
        return validateSession();
    }
}

// Instância global para compatibilidade
$auth = new AuthController();