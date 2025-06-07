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
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
        } catch (Exception $e) {
            error_log("Erro no User Model: " . $e->getMessage());
            throw $e;
        }
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
     * Atualizar perfil do usuário
     */
    public function updateProfile($user_id, $nome, $email) {
        // Verificar se email já existe para outro usuário
        $query = "SELECT id_usuario FROM " . $this->table . " WHERE email = ? AND id_usuario != ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'Este e-mail já está sendo usado por outro usuário.'
            ];
        }
        
        // Validar dados básicos
        if (empty(trim($nome))) {
            return ['success' => false, 'message' => 'Nome é obrigatório.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'E-mail inválido.'];
        }
        
        $query = "UPDATE " . $this->table . " 
                  SET nome = ?, email = ? 
                  WHERE id_usuario = ?";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$nome, $email, $user_id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Perfil atualizado com sucesso!'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao atualizar perfil.'];
    }
    
    /**
     * Alterar senha
     */
    public function changePassword($user_id, $senha_atual, $nova_senha) {
        // Buscar usuário atual
        $query = "SELECT senha FROM " . $this->table . " WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }
        
        $user = $stmt->fetch();
        
        // Verificar senha atual
        if (!password_verify($senha_atual, $user['senha'])) {
            return ['success' => false, 'message' => 'Senha atual incorreta.'];
        }
        
        // Validar nova senha
        if (strlen($nova_senha) < 6) {
            return ['success' => false, 'message' => 'Nova senha deve ter pelo menos 6 caracteres.'];
        }
        
        // Atualizar senha
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table . " SET senha = ? WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$nova_senha_hash, $user_id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Senha alterada com sucesso!'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao alterar status: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao alterar status.'];
    }
    
    /**
     * Listar usuários com filtros
     */
    public function list($filters = []) {
        $where = ['1=1'];
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filters['tipo'];
        }
        
        if (isset($filters['ativo'])) {
            $where[] = "ativo = ?";
            $params[] = $filters['ativo'] ? 1 : 0;
        }
        
        if (!empty($filters['busca'])) {
            $where[] = "(nome LIKE ? OR email LIKE ?)";
            $params[] = "%{$filters['busca']}%";
            $params[] = "%{$filters['busca']}%";
        }
        
        // Ordenação
        $orderBy = "data_criacao DESC";
        if (!empty($filters['ordem'])) {
            switch ($filters['ordem']) {
                case 'nome':
                    $orderBy = "nome ASC";
                    break;
                case 'email':
                    $orderBy = "email ASC";
                    break;
                case 'tipo':
                    $orderBy = "tipo ASC";
                    break;
            }
        }
        
        // Paginação
        $limit = "";
        if (!empty($filters['limite'])) {
            $limit = "LIMIT " . intval($filters['limite']);
            if (!empty($filters['offset'])) {
                $limit .= " OFFSET " . intval($filters['offset']);
            }
        }
        
        $query = "SELECT id_usuario, nome, email, tipo, ativo, data_criacao, ultimo_acesso
                  FROM " . $this->table . "
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY " . $orderBy . "
                  " . $limit;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
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
     * Contar total de usuários
     */
    public function count($filters = []) {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filters['tipo'];
        }
        
        if (isset($filters['ativo'])) {
            $where[] = "ativo = ?";
            $params[] = $filters['ativo'] ? 1 : 0;
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE " . implode(' AND ', $where);
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
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
    
    /**
     * Obter estatísticas do usuário
     */
    public function getUserStats($user_id) {
        $stats = [];
        
        // Se for organizador
        $user = $this->findById($user_id);
        if ($user && $user['tipo'] === 'organizador') {
            // Total de eventos criados
            $query = "SELECT COUNT(*) as total FROM eventos WHERE id_organizador = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats['total_eventos'] = $stmt->fetch()['total'];
            
            // Total de inscrições recebidas
            $query = "SELECT COUNT(*) as total 
                      FROM inscricoes i 
                      INNER JOIN eventos e ON i.id_evento = e.id_evento 
                      WHERE e.id_organizador = ? AND i.status = 'confirmada'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats['total_inscricoes'] = $stmt->fetch()['total'];
        } else {
            // Se for participante
            // Total de inscrições
            $query = "SELECT COUNT(*) as total FROM inscricoes WHERE id_participante = ? AND status = 'confirmada'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats['total_inscricoes'] = $stmt->fetch()['total'];
            
            // Total de favoritos
            $query = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats['total_favoritos'] = $stmt->fetch()['total'];
        }
        
        return $stats;
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
     * Resetar senha (para recuperação)
     */
    public function resetPassword($email, $nova_senha) {
        // Verificar se email existe
        if (!$this->emailExists($email)) {
            return ['success' => false, 'message' => 'E-mail não encontrado.'];
        }
        
        // Validar nova senha
        if (strlen($nova_senha) < 6) {
            return ['success' => false, 'message' => 'Nova senha deve ter pelo menos 6 caracteres.'];
        }
        
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table . " SET senha = ? WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$nova_senha_hash, $email]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Senha redefinida com sucesso!'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao redefinir senha: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao redefinir senha.'];
    }
    
    /**
     * Excluir usuário
     */
    public function delete($user_id) {
        // Verificar se usuário existe
        if (!$this->findById($user_id)) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }
        
        $query = "DELETE FROM " . $this->table . " WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$user_id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Usuário excluído com sucesso!'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir usuário: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao excluir usuário.'];
    }
}
?>erar senha: ' . $e->getMessage()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro desconhecido ao alterar senha.'];
    }
    
    /**
     * Ativar/Desativar usuário
     */
    public function toggleStatus($user_id, $ativo = true) {
        $query = "UPDATE " . $this->table . " SET ativo = ? WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$ativo ? 1 : 0, $user_id]);
            
            if ($result) {
                $status = $ativo ? 'ativado' : 'desativado';
                return [
                    'success' => true,
                    'message' => "Usuário $status com sucesso!"
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao alt