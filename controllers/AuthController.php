<?php
// ==========================================
// AUTH CONTROLLER - VERSÃO COM FOTO DE PERFIL
// Local: controllers/AuthController.php
// ==========================================

require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $conn;
    private $debug = true;
    
    public function __construct() {
        try {
            $database = Database::getInstance();
            $this->conn = $database->getConnection();
            
            if (!$this->conn) {
                $this->log("ERRO: Falha ao conectar com banco");
                throw new Exception("Falha na conexão com banco de dados");
            }
            
            $this->log("SUCCESS: Conectado com banco de dados");
            
        } catch (Exception $e) {
            $this->log("ERRO ao conectar: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function log($message) {
        if ($this->debug) {
            error_log("[AuthController] " . $message);
        }
    }
    
    /**
     * Processar login - ATUALIZADO COM FOTO DE PERFIL
     */
    public function login($data) {
        $email = trim($data['email'] ?? '');
        $senha = $data['senha'] ?? '';
        
        $this->log("Tentativa de login: " . $email);
        
        // Validar entrada
        if (empty($email) || empty($senha)) {
            return [
                'success' => false,
                'message' => 'E-mail e senha são obrigatórios.'
            ];
        }
        
        if (!$this->conn) {
            $this->log("ERRO: Sem conexão com banco");
            return [
                'success' => false,
                'message' => 'Sistema temporariamente indisponível.'
            ];
        }
        
        try {
            // Buscar usuário no banco - INCLUINDO FOTO DE PERFIL
            $stmt = $this->conn->prepare("
                SELECT id_usuario, nome, email, senha, tipo, ativo, foto_perfil 
                FROM usuarios 
                WHERE email = ? AND ativo = 1
            ");
            $stmt->execute([$email]);
            
            $this->log("Query executada, rows: " . $stmt->rowCount());
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                $this->log("Usuário encontrado: " . $user['nome']);
                $this->log("Hash no banco: " . substr($user['senha'], 0, 20) . "...");
                
                // CORREÇÃO: Verificar se a senha é texto plano ou hash
                $isPasswordHashed = strlen($user['senha']) === 60 && strpos($user['senha'], '$2y$') === 0;
                
                if ($isPasswordHashed) {
                    // Senha está hasheada - usar password_verify
                    $passwordMatch = password_verify($senha, $user['senha']);
                    $this->log("password_verify result: " . ($passwordMatch ? 'TRUE' : 'FALSE'));
                } else {
                    // Senha em texto plano - comparar diretamente e depois hashear
                    $passwordMatch = ($senha === $user['senha']);
                    $this->log("Direct match (plain text): " . ($passwordMatch ? 'TRUE' : 'FALSE'));
                    
                    if ($passwordMatch) {
                        // Converter para hash
                        $new_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $update_stmt = $this->conn->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
                        $update_stmt->execute([$new_hash, $user['id_usuario']]);
                        $this->log("Senha convertida para hash");
                    }
                }
                
                if ($passwordMatch) {
                    $this->log("Senha correta - criando sessão");
                    
                    $this->createUserSession($user);
                    $this->updateLastAccess($user['id_usuario']);
                    
                    $redirectUrl = $this->getRedirectUrl($user['tipo']);
                    
                    $this->log("Login bem-sucedido, redirecionando para: " . $redirectUrl);
                    
                    return [
                        'success' => true,
                        'message' => 'Login realizado com sucesso!',
                        'redirect' => $redirectUrl
                    ];
                } else {
                    $this->log("Senha incorreta");
                    return ['success' => false, 'message' => 'E-mail ou senha incorretos.'];
                }
            } else {
                $this->log("Usuário não encontrado ou inativo");
                return ['success' => false, 'message' => 'E-mail ou senha incorretos.'];
            }
            
        } catch (Exception $e) {
            $this->log("Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do sistema: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar cadastro
     */
    public function register($data) {
        $this->log("Iniciando registro");
        
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
            $this->log("Validação falhou: " . $validation['message']);
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }
        
        if (!$this->conn) {
            $this->log("Sem conexão com banco");
            return [
                'success' => false,
                'message' => 'Sistema temporariamente indisponível.'
            ];
        }
        
        try {
            $this->log("Verificando email existente: " . $email);
            
            // Verificar se email já existe
            $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $this->log("Email já existe");
                return [
                    'success' => false,
                    'message' => 'Este e-mail já está cadastrado. Tente fazer login ou use outro e-mail.'
                ];
            }
            
            $this->log("Email disponível, criando usuário");
            
            // Criar hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $this->log("Hash da senha criado: " . substr($senha_hash, 0, 20) . "...");
            
            // Inserir usuário - INCLUINDO CAMPOS PARA FOTO DE PERFIL
            $sql = "INSERT INTO usuarios (nome, email, senha, tipo, telefone, cidade, estado, ativo, data_criacao, data_atualizacao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($sql);
            
            $params = [
                $nome, 
                $email, 
                $senha_hash, 
                $tipo_usuario,
                $telefone ?: null,
                $cidade ?: null,
                $estado ?: null
            ];
            
            $this->log("Executando INSERT com parâmetros: " . json_encode([
                $nome, $email, '[HASH]', $tipo_usuario, $telefone, $cidade, $estado
            ]));
            
            $result = $stmt->execute($params);
            
            $this->log("INSERT result: " . ($result ? 'TRUE' : 'FALSE'));
            $this->log("Affected rows: " . $stmt->rowCount());
            
            if ($result && $stmt->rowCount() > 0) {
                $user_id = $this->conn->lastInsertId();
                $this->log("Usuário criado com ID: " . $user_id);
                
                // Verificar se o usuário foi realmente inserido
                $verify_stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
                $verify_stmt->execute([$user_id]);
                $inserted_user = $verify_stmt->fetch();
                
                if ($inserted_user) {
                    $this->log("Usuário verificado no banco");
                    
                    // Dados do novo usuário para sessão
                    $new_user = [
                        'id_usuario' => $user_id,
                        'nome' => $nome,
                        'email' => $email,
                        'tipo' => $tipo_usuario,
                        'ativo' => 1,
                        'foto_perfil' => null // Novo usuário sem foto
                    ];
                    
                    // Fazer login automático
                    $this->createUserSession($new_user);
                    
                    $this->log("Sessão criada, cadastro concluído");
                    
                    return [
                        'success' => true,
                        'message' => 'Cadastro realizado com sucesso! Bem-vindo ao Conecta Eventos!',
                        'redirect' => $this->getRedirectUrl($tipo_usuario)
                    ];
                } else {
                    $this->log("ERRO: Usuário não encontrado após inserção");
                    return [
                        'success' => false,
                        'message' => 'Erro ao verificar conta criada.'
                    ];
                }
            } else {
                $this->log("ERRO: Falha ao inserir - rowCount = " . $stmt->rowCount());
                
                // Pegar informações de erro
                $errorInfo = $stmt->errorInfo();
                $this->log("PDO Error: " . json_encode($errorInfo));
                
                return [
                    'success' => false,
                    'message' => 'Erro ao criar conta. Tente novamente.'
                ];
            }
            
        } catch (Exception $e) {
            $this->log("Exception no registro: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
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
     * Criar sessão do usuário - ATUALIZADA COM FOTO DE PERFIL
     */
    private function createUserSession($user) {
        // Garantir que a sessão está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['tipo'];
        $_SESSION['user_photo'] = $user['foto_perfil'] ?? null; // NOVO CAMPO
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        $this->log("Sessão criada para: " . $user['nome']);
        
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
            $this->log("Último acesso atualizado para usuário " . $user_id);
        } catch (Exception $e) {
            $this->log("Erro ao atualizar último acesso: " . $e->getMessage());
        }
    }
    
    /**
     * Fazer logout
     */
    public function logout() {
        // Garantir que a sessão está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
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
            'redirect' => 'https://conecta-eventos-production.up.railway.app/index.php'
        ];
    }
    
    /**
     * Verificar se usuário está logado
     */
    public function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Obter dados do usuário logado - ATUALIZADA COM FOTO DE PERFIL
     */
    public function getCurrentUser() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'type' => $_SESSION['user_type'],
            'photo' => $_SESSION['user_photo'] ?? null, // NOVO CAMPO
            'login_time' => $_SESSION['login_time'] ?? null
        ];
    }
    
    /**
     * Obter URL da foto de perfil
     */
    public function getProfilePhotoUrl($userId = null) {
        if (!$userId) {
            $currentUser = $this->getCurrentUser();
            if (!$currentUser || !$currentUser['photo']) {
                return null;
            }
            $photoName = $currentUser['photo'];
        } else {
            // Buscar foto do usuário específico
            try {
                $stmt = $this->conn->prepare("SELECT foto_perfil FROM usuarios WHERE id_usuario = ?");
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                $photoName = $result ? $result['foto_perfil'] : null;
            } catch (Exception $e) {
                return null;
            }
        }
        
        if (!$photoName) {
            return null;
        }
        
        $baseUrl = 'https://conecta-eventos-production.up.railway.app';
        return $baseUrl . '/uploads/profiles/' . $photoName;
    }
    
    /**
     * Atualizar foto de perfil na sessão
     */
    public function updateSessionPhoto($photoName) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($this->isLoggedIn()) {
            $_SESSION['user_photo'] = $photoName;
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar se usuário é organizador
     */
    public function isOrganizer() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'organizador';
    }
    
    /**
     * Verificar se usuário é participante
     */
    public function isParticipant() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'participante';
    }
    
    /**
     * Obter URL de redirecionamento baseada no tipo de usuário
     */
    private function getRedirectUrl($userType) {
        $base_url = 'https://conecta-eventos-production.up.railway.app';
        switch ($userType) {
            case 'organizador':
                return $base_url . '/views/dashboard/organizer.php';
            case 'participante':
                return $base_url . '/views/dashboard/participant.php';
            default:
                return $base_url . '/index.php';
        }
    }
    
    /**
     * Validar sessão (prevenção contra session hijacking)
     */
    public function validateSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
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
    
    /**
     * Teste de conexão com banco
     */
    public function testConnection() {
        if (!$this->conn) {
            return [
                'success' => false,
                'message' => 'Sem conexão com banco'
            ];
        }
        
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM usuarios");
            $result = $stmt->fetch();
            
            return [
                'success' => true,
                'message' => 'Conexão OK - ' . $result['total'] . ' usuários no banco'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro no teste: ' . $e->getMessage()
            ];
        }
    }
}
?>