<?php
// ==========================================
// USER MODEL
// Local: models/User.php
// ==========================================

class User {
    private $conn;
    private $table_name = "usuarios";

    // Propriedades do usuário
    public $id_usuario;
    public $nome;
    public $email;
    public $senha;
    public $tipo_usuario;
    public $telefone;
    public $foto_perfil;
    public $bio;
    public $data_nascimento;
    public $cidade;
    public $estado;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db = null) {
        if ($db) {
            $this->conn = $db;
        }
    }

    // Criar usuário
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                     (nome, email, senha, tipo_usuario, telefone, cidade, estado, status)
                     VALUES
                     (:nome, :email, :senha, :tipo_usuario, :telefone, :cidade, :estado, :status)";

            $stmt = $this->conn->prepare($query);

            // Hash da senha
            $senha_hash = password_hash($this->senha, PASSWORD_DEFAULT);

            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':tipo_usuario', $this->tipo_usuario);
            $stmt->bindParam(':telefone', $this->telefone);
            $stmt->bindParam(':cidade', $this->cidade);
            $stmt->bindParam(':estado', $this->estado);
            $stmt->bindParam(':status', $this->status);

            if ($stmt->execute()) {
                $this->id_usuario = $this->conn->lastInsertId();
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return false;
        }
    }

    // Buscar usuário por email
    public function getByEmail($email) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email AND status = 'ativo'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar usuário: " . $e->getMessage());
            return false;
        }
    }

    // Buscar usuário por ID
    public function getById($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id_usuario = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar usuário: " . $e->getMessage());
            return false;
        }
    }

    // Verificar login
    public function login($email, $senha) {
        try {
            $user = $this->getByEmail($email);
            
            if ($user && password_verify($senha, $user['senha'])) {
                return $user;
            }

            return false;

        } catch (Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar usuário
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET nome = :nome,
                         telefone = :telefone,
                         bio = :bio,
                         data_nascimento = :data_nascimento,
                         cidade = :cidade,
                         estado = :estado,
                         foto_perfil = :foto_perfil,
                         updated_at = NOW()
                     WHERE id_usuario = :id_usuario";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':telefone', $this->telefone);
            $stmt->bindParam(':bio', $this->bio);
            $stmt->bindParam(':data_nascimento', $this->data_nascimento);
            $stmt->bindParam(':cidade', $this->cidade);
            $stmt->bindParam(':estado', $this->estado);
            $stmt->bindParam(':foto_perfil', $this->foto_perfil);
            $stmt->bindParam(':id_usuario', $this->id_usuario);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            return false;
        }
    }

    // Verificar se email já existe
    public function emailExists($email, $exclude_id = null) {
        try {
            $query = "SELECT id_usuario FROM " . $this->table_name . " WHERE email = :email";
            
            if ($exclude_id) {
                $query .= " AND id_usuario != :exclude_id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            
            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id);
            }

            $stmt->execute();
            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Erro ao verificar email: " . $e->getMessage());
            return false;
        }
    }
}