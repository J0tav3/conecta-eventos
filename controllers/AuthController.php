<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        // Iniciar sessão se não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->userModel = new User();
    }
    
    /**
     * Processar login
     */
    public function login($email, $senha) {
        // Validar entrada
        if (empty($email) || empty($senha)) {
            return [
                'success' => false,
                'message' => 'E-mail e senha são obrigatórios.'
            ];
        }
        
        // Tentar autenticar
        $result = $this->userModel->authenticate($email, $senha);
        
        if ($result['success']) {
            $user = $result['user'];
            
            // Criar sessão
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['tipo'];
            $_SESSION['login_time'] = time();
            
            // Atualizar último acesso
            $this->userModel->updateLastAccess($user['id_usuario']);
            
            // Regenerar ID da sessão para segurança
            session_regenerate_id(true);
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'redirect' => $this->getRedirectUrl($user['tipo'])
            ];
        }
        
        return $result;
    }
    
    /**
     * Processar cadastro
     */
    public function register($nome, $email, $senha, $confirmar_senha, $tipo) {
        // Validar senhas
        if ($senha !== $confirmar_senha) {
            return [
                'success' => false,
                'message' => 'As senhas não coincidem.'
            ];
        }
        
        // Tentar criar usuário
        $result = $this->userModel->create($nome, $email, $senha, $tipo);
        
        if ($result['success']) {
            // Auto-login após cadastro
            $loginResult = $this->login($email, $senha);
            
            if ($loginResult['success']) {
                $result['redirect'] = $loginResult['redirect'];
            }
        }
        
        return $result;
    }
    
    /**
     * Fazer logout
     */
    public function logout() {
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
        
        return [
            'success' => true,
            'message' => 'Logout realizado com sucesso!',
            'redirect' => SITE_URL . '/index.php'
        ];
    }
    
    /**
     * Verificar se usuário está logado
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Obter dados do usuário logado
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'type' => $_SESSION['user_type'],
            'login_time' => $_SESSION['login_time'] ?? null
        ];
    }
    
    /**
     * Verificar se usuário é organizador
     */
    public function isOrganizer() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'organizador';
    }
    
    /**
     * Verificar se usuário é participante
     */
    public function isParticipant() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'participante';
    }
    
    /**
     * Redirecionar usuário não autenticado
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/views/auth/login.php');
            exit();
        }
    }
    
    /**
     * Redirecionar se já estiver logado
     */
    public function requireGuest() {
        if ($this->isLoggedIn()) {
            header('Location: ' . $this->getRedirectUrl($_SESSION['user_type']));
            exit();
        }
    }
    
    /**
     * Obter URL de redirecionamento baseada no tipo de usuário
     */
    private function getRedirectUrl($userType) {
        switch ($userType) {
            case 'organizador':
                return SITE_URL . '/views/dashboard/organizer.php';
            case 'participante':
                return SITE_URL . '/views/dashboard/participant.php';
            default:
                return SITE_URL . '/index.php';
        }
    }
    
    /**
     * Verificar força da sessão (prevenção contra session hijacking)
     */
    public function validateSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Verificar se a sessão não expirou (24 horas)
        $maxLifetime = 24 * 60 * 60; // 24 horas em segundos
        
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > $maxLifetime) {
            
            $this->logout();
            return false;
        }
        
        return true;
    }
}
?>