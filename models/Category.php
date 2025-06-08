<?php
// ==========================================
// CATEGORY MODEL
// Local: models/Category.php
// ==========================================

class Category {
    private $conn;
    private $table_name = "categorias";

    // Propriedades da categoria
    public $id_categoria;
    public $nome;
    public $descricao;
    public $icone;
    public $cor;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db = null) {
        if ($db) {
            $this->conn = $db;
        }
    }

    // Buscar todas as categorias ativas
    public function getAll() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE status = 'ativo' 
                     ORDER BY nome ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            return [];
        }
    }

    // Buscar categoria por ID
    public function getById($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id_categoria = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erro ao buscar categoria: " . $e->getMessage());
            return false;
        }
    }

    // Criar nova categoria
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                     (nome, descricao, icone, cor, status)
                     VALUES
                     (:nome, :descricao, :icone, :cor, :status)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':icone', $this->icone);
            $stmt->bindParam(':cor', $this->cor);
            $stmt->bindParam(':status', $this->status);

            if ($stmt->execute()) {
                $this->id_categoria = $this->conn->lastInsertId();
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("Erro ao criar categoria: " . $e->getMessage());
            return false;
        }
    }

    // Atualizar categoria
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET nome = :nome,
                         descricao = :descricao,
                         icone = :icone,
                         cor = :cor,
                         status = :status,
                         updated_at = NOW()
                     WHERE id_categoria = :id_categoria";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':nome', $this->nome);
            $stmt->bindParam(':descricao', $this->descricao);
            $stmt->bindParam(':icone', $this->icone);
            $stmt->bindParam(':cor', $this->cor);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':id_categoria', $this->id_categoria);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Erro ao atualizar categoria: " . $e->getMessage());
            return false;
        }
    }

    // Deletar categoria (soft delete)
    public function delete() {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET status = 'inativo',
                         updated_at = NOW()
                     WHERE id_categoria = :id_categoria";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_categoria', $this->id_categoria);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Erro ao deletar categoria: " . $e->getMessage());
            return false;
        }
    }

    // Contar eventos por categoria
    public function countEvents() {
        try {
            $query = "SELECT COUNT(*) as total FROM eventos 
                     WHERE categoria_id = :categoria_id AND status = 'publicado'";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':categoria_id', $this->id_categoria);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];

        } catch (Exception $e) {
            error_log("Erro ao contar eventos: " . $e->getMessage());
            return 0;
        }
    }
}