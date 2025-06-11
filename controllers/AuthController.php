<?php
// ==========================================
// AUTH CONTROLLER - VERSÃO CORRIGIDA RAILWAY
// Local: controllers/AuthController.php
// ==========================================

require_once __DIR__ . '/../config/config.php';

class AuthController {
    private $db;
    private $conn;
    
    public function __construct() {
        // Iniciar sessão se não estiver iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        try {
            // Tentar conectar com banco
            if (class_exists('Database')) {
                $this->db = new Database();
                $this->conn = $this->db->getConnection();
            }
        } catch (Exception $e) {
            error_log("AuthController: Erro ao conectar: " . $e->getMessage());
            $this->conn = null;
        }
    }
    
    /**
     * Processar login
     */
    public function login($data) {
        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';
        
        // Validar entrada
        if (empty($email) || empty($senha)) {
            return [
                'success' => false,
                'message' => 'E-mail e senha são obrigatórios.'
            ];
        }
        
        // Verificar se tem conexão com banco
        if (!$this->conn) {
            return $this->handleDemoLogin($email, $senha);
        }
        
        try {
            // Tentar autenticar no banco
            $stmt = $this->conn->prepare("
                SELECT id_usuario, nome, email, senha, tipo, ativo 
                FROM usuarios 
                WHERE email = ? AND ativo = 1
            ");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (password_verify($senha, $user['senha'])) {
                    $this->createUserSession($user);
                    
                    // Atualizar último acesso
                    $this->updateLastAccess($user['id_usuario']);
                    
                    return [
                        'success' => true,
                        'message' => 'Login realizado com sucesso!',
                        'redirect' => $this->getRedirectUrl($user['tipo'])
                    ];
                } else {
                    return ['success' => false, 'message' => 'Senha incorreta.'];
                }
            }
            
            return ['success' => false, 'message' => 'E-mail não encontrado.'];
            
        } catch (Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            return $this->handleDemoLogin($email, $senha);
        }
    }
    
    /**
     * Processar cadastro
     */
    public function register($data) {
        error_log("AuthController::register - Dados recebidos: " . json_encode($data));
        
        $nome = trim($data['nome'] ?? '');
        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';
        $confirma_senha = $data['confirma_senha'] ?? '';
        $tipo_usuario = $data['tipo_usuario'] ?? 'participante';
        $telefone = trim($data['telefone'] ?? '');
        $cidade = trim($data['cidade'] ?? '');
        $estado = $data['estado'] ?? '';
        
        // Validações básicas
        $validation = $this->validateRegistration($data);
        if (!$validation['valid']) {
            error_log("AuthController::register - Validação falhou: " . $validation['message']);
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }
        
        // Verificar se tem conexão com banco
        if (!$this->conn) {
            error_log("AuthController::register - Sem conexão com banco");
            return [
                'success' => false,
                'message' => 'Sistema temporariamente indisponível. Tente novamente mais tarde.'
            ];
        }
        
        try {
            error_log("AuthController::register - Verificando email existente");
            
            // Verificar se email já existe
            $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                error_log("AuthController::register - Email já existe: " . $email);
                return [
                    'success' => false,
                    'message' => 'Este e-mail já está cadastrado. Tente fazer login ou use outro e-mail.'
                ];
            }
            
            error_log("AuthController::register - Email disponível, criando usuário");
            
            // Criar usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo, telefone, cidade, estado, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $this->conn->prepare($sql);
            
            error_log("AuthController::register - SQL preparado: " . $sql);
            error_log("AuthController::register - Parâmetros: " . json_encode([
                $nome, $email, '[SENHA_HASH]', $tipo_usuario, $telefone, $cidade, $estado
            ]));
            
            $result = $stmt->execute([
                $nome, 
                $email, 
                $senha_hash, 
                $tipo_usuario,
                $telefone ?: null,
                $cidade ?: null,
                $estado ?: null
            ]);
            
            error_log("AuthController::register - Resultado da execução: " . ($result ? 'success' : 'failed'));
            error_log("AuthController::register - Rows affected: " . $stmt->rowCount());
            
            if ($result && $stmt->rowCount() > 0) {
                $user_id = $this->conn->lastInsertId();
                error_log("AuthController::register - Usuário criado com ID: " . $user_id);
                
                // Fazer login automático
                $new_user = [
                    'id_usuario' => $user_id,
                    'nome' => $nome,
                    'email' => $email,
                    'tipo' => $tipo_usuario
                ];
                
                $this->createUserSession($new_user);
                
                return [
                    'success' => true,
                    'message' => 'Cadastro realizado com sucesso! Bem-vindo ao Conecta Eventos!',
                    'redirect' => $this->getRedirectUrl($tipo_usuario)
                ];
            }
            
            error_log("AuthController::register - Falha ao inserir usuário - result: " . var_export($result, true));
            
            // Verificar erro específico
            $errorInfo = $stmt->errorInfo();
            error_log("AuthController::register - Error info: " . json_encode($errorInfo));
            
            return [
                'success' => false,
                'message' => 'Erro ao criar conta. Detalhes: ' . ($errorInfo[2] ?? 'Erro desconhecido')
            ];
            
        } catch (Exception $e) {
            error_log("AuthController::register - Exception: " . $e->getMessage());
            error_log("AuthController::register - Exception trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Erro interno do sistema: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar dados de registro
     */
    private function validateRegistration($data) {
        $nome = trim($data['nome'] ?? '');
        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';
        $confirma_senha = $data['confirma_senha'] ?? '';
        $tipo_usuario = $data['tipo_usuario'] ?? '';
        
        if (empty($nome)) {
            return ['valid' => false, 'message' => 'Nome é obrigatório.'];
        }
        
        if (strlen($nome) < 2) {
            return ['valid' => false, 'message' => 'Nome deve ter pelo menos 2 caracteres.'];
        }
        
        if (empty($email)) {
            return ['valid' => false, 'message' => 'E-mail é obrigatório.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'E-mail inválido.'];
        }
        
        if (empty($senha)) {
            return ['valid' => false, 'message' => 'Senha é obrigatória.'];
        }
        
        if (strlen($senha) < 6) {
            return ['valid' => false, 'message' => 'Senha deve ter pelo menos 6 caracteres.'];
        }
        
        if ($senha !== $confirma_senha) {
            return ['valid' => false, 'message' => 'As senhas não coincidem.'];
        }
        
        if (!in_array($tipo_usuario, ['organizador', 'participante'])) {
            return ['valid' => false, 'message' => 'Tipo de usuário inválido.'];
        }
        
        return ['valid' => true, 'message' => 'Dados válidos'];
    }
    
    /**
     * Login demo para quando banco não está disponível
     */
    private function handleDemoLogin($email, $senha) {
        $demo_accounts = [
            'admin@conectaeventos.com' => [
                'senha' => 'admin123', 
                'tipo' => 'organizador', 
                'nome' => 'Administrador Demo',
                'id' => 1
            ],
            'user@conectaeventos.com' => [
                'senha' => 'user123', 
                'tipo' => 'participante', 
                'nome' => 'Usuário Demo',
                'id' => 2
            ]
        ];
        
        if (isset($demo_accounts[$email]) && $demo_accounts[$email]['senha'] === $senha) {
            $user = [
                'id_usuario' => $demo_accounts[$email]['id'],
                'nome' => $demo_accounts[$email]['nome'],
                'email' => $email,
                'tipo' => $demo_accounts[$email]['tipo']
            ];
            
            $this->createUserSession($user);
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso! (Modo Demo)',
                'redirect' => $this->getRedirectUrl($user['tipo'])
            ];
        }
        
        return [
            'success' => false,
            'message' => 'E-mail ou senha incorretos.'
        ];
    }
    
    /**
     * Criar sessão do usuário
     */
    private function createUserSession($user) {
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['tipo'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        error_log("AuthController::createUserSession - Sessão criada para usuário: " . $user['nome']);
        
        // Regenerar ID da sessão para segurança
        session_regenerate_id(true);
    }
    
    /**
     * Atualizar último acesso
     */
    private function updateLastAccess($user_id) {
        if (!$this->conn) return;
        
        try {
            $stmt = $this->conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id_usuario = ?");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar último acesso: " . $e->getMessage());
        }
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
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
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
     * Validar sessão (prevenção contra session hijacking)
     */
    public function validateSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Verificar se a sessão não expirou (24 horas)
        $maxLifetime = 24 * 60 * 60;
        
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > $maxLifetime) {
            
            $this->logout();
            return false;
        }
        
        return true;
    }
}
?>