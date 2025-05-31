<?php
// ========================================
// CONECTA EVENTOS - LOGOUT
// ========================================
// Local: conecta-eventos/logout.php
// ========================================

require_once 'config/config.php';
require_once 'includes/session.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Realizar logout
$result = $auth->logout();

// Definir mensagem flash
if ($result['success']) {
    // Como a sessão foi destruída, precisamos usar cookie temporário para a mensagem
    setcookie('logout_message', 'Logout realizado com sucesso!', time() + 10, '/');
    setcookie('logout_type', 'success', time() + 10, '/');
} else {
    setcookie('logout_message', 'Erro ao fazer logout.', time() + 10, '/');
    setcookie('logout_type', 'danger', time() + 10, '/');
}

// Redirecionar para página inicial
header('Location: ' . SITE_URL . '/index.php');
exit();
?>