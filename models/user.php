<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'usuarios';
    
    // Propriedades do usuário
    public $id_usuario;
    public $nome;
    public $email;
    public $senha;
    public $tipo;
    public $data_criacao;
    public $ativo;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Criar novo usuário
     */
    public function create($nome, $email, $senha, $tipo) {
        // Verificar se email já existe
        if ($this->emailExists($email)) {
            return [
                'success' => false, 
                'message' => 'Este e-mail já está cadastrado no sistema.'
            ];
        }
        
        // Validar dados
        $validation = $this->validateUserData($nome, $email, $senha, $tipo);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Preparar query
        $query = "INSERT INTO " . $this->table . " (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        // Hash da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        try {
            $result = $stmt->execute([$nome, $email, $senha_hash, $tipo]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Usuário cadastrado com sucesso!',
                    'user_id' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao cadastrar usuário: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao cadastrar usuário.'];
    }
    
    /**
     * Autenticar usuário
     */
    public function authenticate($email, $senha) {
        $query = "SELECT id_usuario, nome, email, senha, tipo, ativo 
                  FROM " . $this->table . " 
                  WHERE email = ? AND ativo = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verificar senha
            if (password_verify($senha, $user['senha'])) {
                // Remover senha dos dados retornados
                unset($user['senha']);
                return [
                    'success' => true,
                    'user' => $user,
                    'message' => 'Login realizado com sucesso!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Senha incorreta.'
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'E-mail não encontrado ou usuário inativo.'
        ];
    }
    
    /**
     * Verificar se email existe
     */
    public function emailExists($email) {
        $query = "SELECT id_usuario FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Buscar usuário por ID
     */
    public function findById($id) {
        $query = "SELECT id_usuario, nome, email, tipo, data_criacao, ativo 
                  FROM " . $this->table . " 
                  WHERE id_usuario = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return false;
    }
    
    /**
     * Atualizar último acesso
     */
    public function updateLastAccess($user_id) {
        $query = "UPDATE " . $this->table . " 
                  SET ultimo_acesso = CURRENT_TIMESTAMP 
                  WHERE id_usuario = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id]);
    }
    
    /**
     * Validar dados do usuário
     */
    private function validateUserData($nome, $email, $senha, $tipo) {
        $errors = [];
        
        // Validar nome
        if (empty(trim($nome))) {
            $errors[] = "Nome é obrigatório.";
        } elseif (strlen(trim($nome)) < 2) {
            $errors[] = "Nome deve ter pelo menos 2 caracteres.";
        } elseif (strlen(trim($nome)) > 100) {
            $errors[] = "Nome não pode ter mais de 100 caracteres.";
        }
        
        // Validar email
        if (empty(trim($email))) {
            $errors[] = "E-mail é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "E-mail inválido.";
        } elseif (strlen($email) > 100) {
            $errors[] = "E-mail não pode ter mais de 100 caracteres.";
        }
        
        // Validar senha
        if (empty($senha)) {
            $errors[] = "Senha é obrigatória.";
        } elseif (strlen($senha) < 6) {
            $errors[] = "Senha deve ter pelo menos 6 caracteres.";
        } elseif (strlen($senha) > 255) {
            $errors[] = "Senha muito longa.";
        }
        
        // Validar tipo
        if (!in_array($tipo, ['organizador', 'participante'])) {
            $errors[] = "Tipo de usuário inválido.";
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(' ', $errors)
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Contar usuários por tipo
     */
    public function countByType($tipo) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE tipo = ? AND ativo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$tipo]);
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Listar usuários recentes
     */
    public function getRecentUsers($limit = 10) {
        $query = "SELECT id_usuario, nome, email, tipo, data_criacao 
                  FROM " . $this->table . " 
                  WHERE ativo = 1 
                  ORDER BY data_criacao DESC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
}
?>