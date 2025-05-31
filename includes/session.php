php<?php
// Controle de Sessão - Conecta Eventos

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir controlador de autenticação
require_once __DIR__ . '/../controllers/AuthController.php';

// Instância global do controlador de autenticação
$auth = new AuthController();

/**
 * Funções auxiliares para verificação de autenticação
 */

function isLoggedIn() {
    global $auth;
    return $auth->isLoggedIn();
}

function requireLogin() {
    global $auth;
    $auth->requireAuth();
}

function requireGuest() {
    global $auth;
    $auth->requireGuest();
}

function getCurrentUser() {
    global $auth;
    return $auth->getCurrentUser();
}

function isOrganizer() {
    global $auth;
    return $auth->isOrganizer();
}

function isParticipant() {
    global $auth;
    return $auth->isParticipant();
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? null;
}

function getUserEmail() {
    return $_SESSION['user_email'] ?? null;
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Função para exibir mensagens flash
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

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

function showFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        echo "<div class='alert alert-{$flash['type']} alert-dismissible fade show' role='alert'>
                {$flash['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// Validar sessão automaticamente
if (isLoggedIn()) {
    $auth->validateSession();
}
?>