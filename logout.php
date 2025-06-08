<?php
// ==========================================
// SCRIPT DE LOGOUT
// Local: logout.php (raiz)
// ==========================================

session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se a sessão usa cookies, destruir o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Definir mensagem de logout
$logout_message = "Logout realizado com sucesso! Obrigado por usar o Conecta Eventos.";
$logout_type = "success";

// Criar cookies para mostrar mensagem na página inicial
setcookie('logout_message', $logout_message, time() + 10, '/', '', false, true);
setcookie('logout_type', $logout_type, time() + 10, '/', '', false, true);

// Redirecionar para a página inicial
header("Location: index.php");
exit;
?>