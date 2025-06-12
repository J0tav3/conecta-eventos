<?php
// ==========================================
// FUNÇÕES DE SESSÃO - VERSÃO CORRIGIDA
// Local: includes/session.php
// ==========================================

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Carregar configurações se ainda não foram carregadas
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Verificar se usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Verificar se é organizador
 */
function isOrganizer() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'organizador';
}

/**
 * Verificar se é participante
 */
function isParticipant() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'participante';
}

/**
 * Obter ID do usuário
 */
function getUserId() {
    return isLoggedIn() ? ($_SESSION['user_id'] ?? null) : null;
}

/**
 * Obter nome do usuário
 */
function getUserName() {
    return isLoggedIn() ? ($_SESSION['user_name'] ?? 'Usuário') : null;
}

/**
 * Obter email do usuário
 */
function getUserEmail() {
    return isLoggedIn() ? ($_SESSION['user_email'] ?? '') : null;
}

/**
 * Obter tipo do usuário
 */
function getUserType() {
    return isLoggedIn() ? ($_SESSION['user_type'] ?? 'participante') : null;
}

/**
 * Exigir login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/views/auth/login.php');
        exit();
    }
}

/**
 * Exigir que seja organizador
 */
function requireOrganizer() {
    requireLogin();
    if (!isOrganizer()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

/**
 * Exigir que seja participante
 */
function requireParticipant() {
    requireLogin();
    if (!isParticipant()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }
}

/**
 * Definir mensagem flash
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Obter e limpar mensagem flash
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Mostrar mensagem flash (para usar em views)
 */
function showFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $iconMap = [
            'success' => 'check-circle',
            'danger' => 'exclamation-triangle',
            'warning' => 'exclamation-triangle',
            'info' => 'info-circle'
        ];
        $icon = $iconMap[$flash['type']] ?? 'info-circle';
        
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-' . $icon . ' me-2"></i>';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * Criar sessão de usuário
 */
function createUserSession($userData) {
    $_SESSION['user_id'] = $userData['id_usuario'];
    $_SESSION['user_name'] = $userData['nome'];
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_type'] = $userData['tipo'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Regenerar ID da sessão por segurança
    session_regenerate_id(true);
}

/**
 * Destruir sessão
 */
function destroyUserSession() {
    // Limpar variáveis de sessão
    $_SESSION = array();
    
    // Deletar cookie de sessão se existir
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir sessão
    session_destroy();
}

/**
 * Gerar token CSRF (ÚNICA DECLARAÇÃO)
 */
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verificar token CSRF (ÚNICA DECLARAÇÃO)
 */
if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Limpar dados antigos da sessão
 */
function cleanupSession() {
    // Verificar se a sessão expirou (24 horas)
    $maxLifetime = 24 * 60 * 60;
    
    if (isset($_SESSION['login_time']) && 
        (time() - $_SESSION['login_time']) > $maxLifetime) {
        destroyUserSession();
        return false;
    }
    
    return true;
}

// Limpar sessão automaticamente se expirou
if (isLoggedIn()) {
    cleanupSession();
}
?>