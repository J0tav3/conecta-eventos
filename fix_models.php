<?php
// ========================================
// SCRIPT PARA CORRIGIR MODELS NO RAILWAY
// ========================================
// Execute este arquivo uma vez para criar os models corretos
// ========================================

echo "<h1>🔧 Corrigindo Models para Railway</h1>";

// 1. Criar models/Event.php
$eventModelContent = '<?php
require_once __DIR__ . \'/../config/database.php\';

class Event {
    private $conn;
    private $table = \'eventos\';
    
    // Propriedades do evento
    public $id_evento;
    public $id_organizador;
    public $id_categoria;
    public $titulo;
    public $descricao;
    public $data_inicio;
    public $data_fim;
    public $horario_inicio;
    public $horario_fim;
    public $local_nome;
    public $local_endereco;
    public $local_cidade;
    public $local_estado;
    public $local_cep;
    public $capacidade_maxima;
    public $preco;
    public $evento_gratuito;
    public $imagem_capa;
    public $link_externo;
    public $requisitos;
    public $informacoes_adicionais;
    public $status;
    public $destaque;
    public $data_criacao;
    public $data_atualizacao;
    
    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
        } catch (Exception $e) {
            error_log("Erro no Event Model: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Criar novo evento
     */
    public function create($data) {
        // Validar dados
        $validation = $this->validateEventData($data);
        if (!$validation[\'success\']) {
            return $validation;
        }
        
        // Preparar query
        $query = "INSERT INTO " . $this->table . " (
            id_organizador, id_categoria, titulo, descricao,
            data_inicio, data_fim, horario_inicio, horario_fim,
            local_nome, local_endereco, local_cidade, local_estado, local_cep,
            capacidade_maxima, preco, evento_gratuito, imagem_capa,
            link_externo, requisitos, informacoes_adicionais, status, destaque
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([
                $data[\'id_organizador\'],
                $data[\'id_categoria\'] ?? null,
                $data[\'titulo\'],
                $data[\'descricao\'],
                $data[\'data_inicio\'],
                $data[\'data_fim\'],
                $data[\'horario_inicio\'],
                $data[\'horario_fim\'],
                $data[\'local_nome\'],
                $data[\'local_endereco\'],
                $data[\'local_cidade\'],
                $data[\'local_estado\'],
                $data[\'local_cep\'] ?? null,
                $data[\'capacidade_maxima\'] ?? null,
                $data[\'preco\'] ?? 0.00,
                $data[\'evento_gratuito\'] ?? true,
                $data[\'imagem_capa\'] ?? null,
                $data[\'link_externo\'] ?? null,
                $data[\'requisitos\'] ?? null,
                $data[\'informacoes_adicionais\'] ?? null,
                $data[\'status\'] ?? \'rascunho\',
                $data[\'destaque\'] ?? false
            ]);
            
            if ($result) {
                return [
                    \'success\' => true,
                    \'message\' => \'Evento criado com sucesso!\',
                    \'event_id\' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao criar evento: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao criar evento.\'];
    }
    
    /**
     * Buscar evento por ID
     */
    public function findById($id) {
        $query = "SELECT e.*, 
                         u.nome AS nome_organizador,
                         u.email AS email_organizador,
                         c.nome AS nome_categoria,
                         c.cor AS cor_categoria,
                         c.icone AS icone_categoria,
                         (SELECT COUNT(*) FROM inscricoes i 
                          WHERE i.id_evento = e.id_evento AND i.status = \'confirmada\') AS total_inscritos
                  FROM " . $this->table . " e
                  LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                  LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                  WHERE e.id_evento = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return false;
    }
    
    /**
     * Atualizar evento
     */
    public function update($id, $data) {
        // Verificar se o evento existe
        if (!$this->findById($id)) {
            return [\'success\' => false, \'message\' => \'Evento não encontrado.\'];
        }
        
        // Validar dados
        $validation = $this->validateEventData($data);
        if (!$validation[\'success\']) {
            return $validation;
        }
        
        $query = "UPDATE " . $this->table . " SET 
                  id_categoria = ?, titulo = ?, descricao = ?,
                  data_inicio = ?, data_fim = ?, horario_inicio = ?, horario_fim = ?,
                  local_nome = ?, local_endereco = ?, local_cidade = ?, local_estado = ?, local_cep = ?,
                  capacidade_maxima = ?, preco = ?, evento_gratuito = ?, imagem_capa = ?,
                  link_externo = ?, requisitos = ?, informacoes_adicionais = ?, status = ?, destaque = ?
                  WHERE id_evento = ?";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([
                $data[\'id_categoria\'] ?? null,
                $data[\'titulo\'],
                $data[\'descricao\'],
                $data[\'data_inicio\'],
                $data[\'data_fim\'],
                $data[\'horario_inicio\'],
                $data[\'horario_fim\'],
                $data[\'local_nome\'],
                $data[\'local_endereco\'],
                $data[\'local_cidade\'],
                $data[\'local_estado\'],
                $data[\'local_cep\'] ?? null,
                $data[\'capacidade_maxima\'] ?? null,
                $data[\'preco\'] ?? 0.00,
                $data[\'evento_gratuito\'] ?? true,
                $data[\'imagem_capa\'] ?? null,
                $data[\'link_externo\'] ?? null,
                $data[\'requisitos\'] ?? null,
                $data[\'informacoes_adicionais\'] ?? null,
                $data[\'status\'] ?? \'rascunho\',
                $data[\'destaque\'] ?? false,
                $id
            ]);
            
            if ($result) {
                return [
                    \'success\' => true,
                    \'message\' => \'Evento atualizado com sucesso!\'
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao atualizar evento: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao atualizar evento.\'];
    }
    
    /**
     * Excluir evento
     */
    public function delete($id, $organizador_id) {
        // Verificar se o evento existe e pertence ao organizador
        $evento = $this->findById($id);
        if (!$evento) {
            return [\'success\' => false, \'message\' => \'Evento não encontrado.\'];
        }
        
        if ($evento[\'id_organizador\'] != $organizador_id) {
            return [\'success\' => false, \'message\' => \'Você não tem permissão para excluir este evento.\'];
        }
        
        $query = "DELETE FROM " . $this->table . " WHERE id_evento = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return [
                    \'success\' => true,
                    \'message\' => \'Evento excluído com sucesso!\'
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao excluir evento: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao excluir evento.\'];
    }
    
    /**
     * Listar eventos com filtros
     */
    public function list($filters = []) {
        $where = [\'1=1\']; // Base condition
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters[\'organizador_id\'])) {
            $where[] = "e.id_organizador = ?";
            $params[] = $filters[\'organizador_id\'];
        }
        
        if (!empty($filters[\'categoria_id\'])) {
            $where[] = "e.id_categoria = ?";
            $params[] = $filters[\'categoria_id\'];
        }
        
        if (!empty($filters[\'status\'])) {
            $where[] = "e.status = ?";
            $params[] = $filters[\'status\'];
        }
        
        if (!empty($filters[\'cidade\'])) {
            $where[] = "e.local_cidade LIKE ?";
            $params[] = "%{$filters[\'cidade\']}%";
        }
        
        if (!empty($filters[\'busca\'])) {
            $where[] = "(e.titulo LIKE ? OR e.descricao LIKE ?)";
            $params[] = "%{$filters[\'busca\']}%";
            $params[] = "%{$filters[\'busca\']}%";
        }
        
        if (isset($filters[\'gratuito\'])) {
            $where[] = "e.evento_gratuito = ?";
            $params[] = $filters[\'gratuito\'] ? 1 : 0;
        }
        
        if (!empty($filters[\'data_inicio\'])) {
            $where[] = "e.data_inicio >= ?";
            $params[] = $filters[\'data_inicio\'];
        }
        
        if (!empty($filters[\'data_fim\'])) {
            $where[] = "e.data_inicio <= ?";
            $params[] = $filters[\'data_fim\'];
        }
        
        // Ordenação
        $orderBy = "e.data_inicio ASC";
        if (!empty($filters[\'ordem\'])) {
            switch ($filters[\'ordem\']) {
                case \'data_desc\':
                    $orderBy = "e.data_inicio DESC";
                    break;
                case \'titulo\':
                    $orderBy = "e.titulo ASC";
                    break;
                case \'preco_asc\':
                    $orderBy = "e.preco ASC";
                    break;
                case \'preco_desc\':
                    $orderBy = "e.preco DESC";
                    break;
                case \'data_criacao\':
                    $orderBy = "e.data_criacao DESC";
                    break;
            }
        }
        
        // Paginação
        $limit = "";
        if (!empty($filters[\'limite\'])) {
            $limit = "LIMIT " . intval($filters[\'limite\']);
            if (!empty($filters[\'offset\'])) {
                $limit .= " OFFSET " . intval($filters[\'offset\']);
            }
        }
        
        $query = "SELECT e.*, 
                         u.nome AS nome_organizador,
                         c.nome AS nome_categoria,
                         c.cor AS cor_categoria,
                         c.icone AS icone_categoria,
                         (SELECT COUNT(*) FROM inscricoes i 
                          WHERE i.id_evento = e.id_evento AND i.status = \'confirmada\') AS total_inscritos,
                         CASE 
                            WHEN e.capacidade_maxima IS NOT NULL THEN 
                                e.capacidade_maxima - (SELECT COUNT(*) FROM inscricoes i WHERE i.id_evento = e.id_evento AND i.status = \'confirmada\')
                            ELSE NULL 
                         END AS vagas_restantes
                  FROM " . $this->table . " e
                  LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario
                  LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
                  WHERE " . implode(\' AND \', $where) . "
                  ORDER BY " . $orderBy . "
                  " . $limit;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar eventos públicos (para participantes)
     */
    public function getPublicEvents($filters = []) {
        $filters[\'status\'] = \'publicado\';
        return $this->list($filters);
    }
    
    /**
     * Buscar eventos do organizador
     */
    public function getEventsByOrganizer($organizador_id, $filters = []) {
        $filters[\'organizador_id\'] = $organizador_id;
        return $this->list($filters);
    }
    
    /**
     * Contar eventos
     */
    public function count($filters = []) {
        $where = [\'1=1\'];
        $params = [];
        
        // Aplicar os mesmos filtros da função list()
        if (!empty($filters[\'organizador_id\'])) {
            $where[] = "id_organizador = ?";
            $params[] = $filters[\'organizador_id\'];
        }
        
        if (!empty($filters[\'status\'])) {
            $where[] = "status = ?";
            $params[] = $filters[\'status\'];
        }
        
        if (!empty($filters[\'categoria_id\'])) {
            $where[] = "id_categoria = ?";
            $params[] = $filters[\'categoria_id\'];
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE " . implode(\' AND \', $where);
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return $result[\'total\'];
    }
    
    /**
     * Alterar status do evento
     */
    public function changeStatus($id, $status, $organizador_id) {
        $validStatuses = [\'rascunho\', \'publicado\', \'cancelado\', \'finalizado\'];
        
        if (!in_array($status, $validStatuses)) {
            return [\'success\' => false, \'message\' => \'Status inválido.\'];
        }
        
        // Verificar se o evento pertence ao organizador
        $evento = $this->findById($id);
        if (!$evento || $evento[\'id_organizador\'] != $organizador_id) {
            return [\'success\' => false, \'message\' => \'Evento não encontrado ou sem permissão.\'];
        }
        
        $query = "UPDATE " . $this->table . " SET status = ? WHERE id_evento = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$status, $id]);
            
            if ($result) {
                return [
                    \'success\' => true,
                    \'message\' => \'Status do evento atualizado com sucesso!\'
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao atualizar status: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao atualizar status.\'];
    }
    
    /**
     * Eventos em destaque
     */
    public function getFeaturedEvents($limit = 6) {
        return $this->list([
            \'status\' => \'publicado\',
            \'limite\' => $limit,
            \'ordem\' => \'data_inicio\'
        ]);
    }
    
    /**
     * Eventos próximos
     */
    public function getUpcomingEvents($limit = 10) {
        return $this->list([
            \'status\' => \'publicado\',
            \'data_inicio\' => date(\'Y-m-d H:i:s\'),
            \'limite\' => $limit,
            \'ordem\' => \'data_inicio\'
        ]);
    }
    
    /**
     * Validar dados do evento
     */
    private function validateEventData($data) {
        $errors = [];
        
        // Validar título
        if (empty(trim($data[\'titulo\']))) {
            $errors[] = "Título é obrigatório.";
        } elseif (strlen(trim($data[\'titulo\'])) > 200) {
            $errors[] = "Título não pode ter mais de 200 caracteres.";
        }
        
        // Validar descrição
        if (empty(trim($data[\'descricao\']))) {
            $errors[] = "Descrição é obrigatória.";
        }
        
        // Validar datas
        if (empty($data[\'data_inicio\'])) {
            $errors[] = "Data de início é obrigatória.";
        }
        
        if (empty($data[\'data_fim\'])) {
            $errors[] = "Data de fim é obrigatória.";
        }
        
        if (!empty($data[\'data_inicio\']) && !empty($data[\'data_fim\'])) {
            $dataInicio = new DateTime($data[\'data_inicio\']);
            $dataFim = new DateTime($data[\'data_fim\']);
            
            if ($dataInicio > $dataFim) {
                $errors[] = "Data de início não pode ser posterior à data de fim.";
            }
            
            if ($dataInicio < new DateTime()) {
                $errors[] = "Data de início não pode ser no passado.";
            }
        }
        
        // Validar horários
        if (empty($data[\'horario_inicio\'])) {
            $errors[] = "Horário de início é obrigatório.";
        }
        
        if (empty($data[\'horario_fim\'])) {
            $errors[] = "Horário de fim é obrigatório.";
        }
        
        // Validar local
        if (empty(trim($data[\'local_nome\']))) {
            $errors[] = "Nome do local é obrigatório.";
        }
        
        if (empty(trim($data[\'local_endereco\']))) {
            $errors[] = "Endereço do local é obrigatório.";
        }
        
        if (empty(trim($data[\'local_cidade\']))) {
            $errors[] = "Cidade é obrigatória.";
        }
        
        if (empty(trim($data[\'local_estado\']))) {
            $errors[] = "Estado é obrigatório.";
        }
        
        // Validar capacidade
        if (!empty($data[\'capacidade_maxima\']) && $data[\'capacidade_maxima\'] < 1) {
            $errors[] = "Capacidade máxima deve ser maior que zero.";
        }
        
        // Validar preço
        if (!empty($data[\'preco\']) && $data[\'preco\'] < 0) {
            $errors[] = "Preço não pode ser negativo.";
        }
        
        // Validar status
        $validStatuses = [\'rascunho\', \'publicado\', \'cancelado\', \'finalizado\'];
        if (!empty($data[\'status\']) && !in_array($data[\'status\'], $validStatuses)) {
            $errors[] = "Status inválido.";
        }
        
        if (!empty($errors)) {
            return [
                \'success\' => false,
                \'message\' => implode(\' \', $errors)
            ];
        }
        
        return [\'success\' => true];
    }
    
    /**
     * Obter estatísticas do evento
     */
    public function getEventStats($event_id) {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM inscricoes WHERE id_evento = ? AND status = \'confirmada\') as inscritos_confirmados,
                    (SELECT COUNT(*) FROM inscricoes WHERE id_evento = ? AND status = \'pendente\') as inscritos_pendentes,
                    (SELECT COUNT(*) FROM inscricoes WHERE id_evento = ? AND status = \'cancelada\') as inscritos_cancelados,
                    (SELECT COUNT(*) FROM favoritos WHERE id_evento = ?) as total_favoritos,
                    (SELECT AVG(avaliacao_evento) FROM inscricoes WHERE id_evento = ? AND avaliacao_evento IS NOT NULL) as media_avaliacoes";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$event_id, $event_id, $event_id, $event_id, $event_id]);
        
        return $stmt->fetch();
    }
}
?>';

// 2. Criar models/User.php
$userModelContent = '<?php
require_once __DIR__ . \'/../config/database.php\';

class User {
    private $conn;
    private $table = \'usuarios\';
    
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
                \'success\' => false, 
                \'message\' => \'Este e-mail já está cadastrado no sistema.\'
            ];
        }
        
        // Validar dados
        $validation = $this->validateUserData($nome, $email, $senha, $tipo);
        if (!$validation[\'success\']) {
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
                    \'success\' => true,
                    \'message\' => \'Usuário cadastrado com sucesso!\',
                    \'user_id\' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao cadastrar usuário: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao cadastrar usuário.\'];
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
            if (password_verify($senha, $user[\'senha\'])) {
                // Remover senha dos dados retornados
                unset($user[\'senha\']);
                return [
                    \'success\' => true,
                    \'user\' => $user,
                    \'message\' => \'Login realizado com sucesso!\'
                ];
            } else {
                return [
                    \'success\' => false,
                    \'message\' => \'Senha incorreta.\'
                ];
            }
        }
        
        return [
            \'success\' => false,
            \'message\' => \'E-mail não encontrado ou usuário inativo.\'
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
                \'success\' => false,
                \'message\' => \'Este e-mail já está sendo usado por outro usuário.\'
            ];
        }
        
        // Validar dados básicos
        if (empty(trim($nome))) {
            return [\'success\' => false, \'message\' => \'Nome é obrigatório.\'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [\'success\' => false, \'message\' => \'E-mail inválido.\'];
        }
        
        $query = "UPDATE " . $this->table . " 
                  SET nome = ?, email = ? 
                  WHERE id_usuario = ?";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$nome, $email, $user_id]);
            
            if ($result) {
                return [
                    \'success\' => true,
                    \'message\' => \'Perfil atualizado com sucesso!\'
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao atualizar perfil: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao atualizar perfil.\'];
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
            return [\'success\' => false, \'message\' => \'Usuário não encontrado.\'];
        }
        
        $user = $stmt->fetch();
        
        // Verificar senha atual
        if (!password_verify($senha_atual, $user[\'senha\'])) {
            return [\'success\' => false, \'message\' => \'Senha atual incorreta.\'];
        }
        
        // Validar nova senha
        if (strlen($nova_senha) < 6) {
            return [\'success\' => false, \'message\' => \'Nova senha deve ter pelo menos 6 caracteres.\'];
        }
        
        // Atualizar senha
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table . " SET senha = ? WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$nova_senha_hash, $user_id]);
            
            if ($result) {
                return [
                    \'success\' => true,
                    \'message\' => \'Senha alterada com sucesso!\'
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao alterar senha: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao alterar senha.\'];
    }
    
    /**
     * Listar usuários com filtros
     */
    public function list($filters = []) {
        $where = [\'1=1\'];
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters[\'tipo\'])) {
            $where[] = "tipo = ?";
            $params[] = $filters[\'tipo\'];
        }
        
        if (isset($filters[\'ativo\'])) {
            $where[] = "ativo = ?";
            $params[] = $filters[\'ativo\'] ? 1 : 0;
        }
        
        if (!empty($filters[\'busca\'])) {
            $where[] = "(nome LIKE ? OR email LIKE ?)";
            $params[] = "%{$filters[\'busca\']}%";
            $params[] = "%{$filters[\'busca\']}%";
        }
        
        // Ordenação
        $orderBy = "data_criacao DESC";
        if (!empty($filters[\'ordem\'])) {
            switch ($filters[\'ordem\']) {
                case \'nome\':
                    $orderBy = "nome ASC";
                    break;
                case \'email\':
                    $orderBy = "email ASC";
                    break;
                case \'tipo\':
                    $orderBy = "tipo ASC";
                    break;
            }
        }
        
        // Paginação
        $limit = "";
        if (!empty($filters[\'limite\'])) {
            $limit = "LIMIT " . intval($filters[\'limite\']);
            if (!empty($filters[\'offset\'])) {
                $limit .= " OFFSET " . intval($filters[\'offset\']);
            }
        }
        
        $query = "SELECT id_usuario, nome, email, tipo, ativo, data_criacao, ultimo_acesso
                  FROM " . $this->table . "
                  WHERE " . implode(\' AND \', $where) . "
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
        return $result[\'total\'];
    }
    
    /**
     * Contar total de usuários
     */
    public function count($filters = []) {
        $where = [\'1=1\'];
        $params = [];
        
        if (!empty($filters[\'tipo\'])) {
            $where[] = "tipo = ?";
            $params[] = $filters[\'tipo\'];
        }
        
        if (isset($filters[\'ativo\'])) {
            $where[] = "ativo = ?";
            $params[] = $filters[\'ativo\'] ? 1 : 0;
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE " . implode(\' AND \', $where);
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return $result[\'total\'];
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
        if ($user && $user[\'tipo\'] === \'organizador\') {
            // Total de eventos criados
            $query = "SELECT COUNT(*) as total FROM eventos WHERE id_organizador = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats[\'total_eventos\'] = $stmt->fetch()[\'total\'];
            
            // Total de inscrições recebidas
            $query = "SELECT COUNT(*) as total 
                      FROM inscricoes i 
                      INNER JOIN eventos e ON i.id_evento = e.id_evento 
                      WHERE e.id_organizador = ? AND i.status = \'confirmada\'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats[\'total_inscricoes\'] = $stmt->fetch()[\'total\'];
        } else {
            // Se for participante
            // Total de inscrições
            $query = "SELECT COUNT(*) as total FROM inscricoes WHERE id_participante = ? AND status = \'confirmada\'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats[\'total_inscricoes\'] = $stmt->fetch()[\'total\'];
            
            // Total de favoritos
            $query = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats[\'total_favoritos\'] = $stmt->fetch()[\'total\'];
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
        if (!in_array($tipo, [\'organizador\', \'participante\'])) {
            $errors[] = "Tipo de usuário inválido.";
        }
        
        if (!empty($errors)) {
            return [
                \'success\' => false,
                \'message\' => implode(\' \', $errors)
            ];
        }
        
        return [\'success\' => true];
    }
    
    /**
     * Resetar senha (para recuperação)
     */
    public function resetPassword($email, $nova_senha) {
        // Verificar se email existe
        if (!$this->emailExists($email)) {
            return [\'success\' => false, \'message\' => \'E-mail não encontrado.\'];
        }
        
        // Validar nova senha
        if (strlen($nova_senha) < 6) {
            return [\'success\' => false, \'message\' => \'Nova senha deve ter pelo menos 6 caracteres.\'];
        }
        
        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table . " SET senha = ? WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$nova_senha_hash, $email]);
            
            if ($result) {
                return [
                    \'success\' => true,
                    \'message\' => \'Senha redefinida com sucesso!\'
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao redefinir senha: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao redefinir senha.\'];
    }
    
    /**
     * Excluir usuário
     */
    public function delete($user_id) {
        // Verificar se usuário existe
        if (!$this->findById($user_id)) {
            return [\'success\' => false, \'message\' => \'Usuário não encontrado.\'];
        }
        
        $query = "DELETE FROM " . $this->table . " WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([$user_id]);
            
            if ($result) {
                return [
                    \'success\' => true,
                    \'message\' => \'Usuário excluído com sucesso!\'
                ];
            }
        } catch (PDOException $e) {
            return [
                \'success\' => false,
                \'message\' => \'Erro ao excluir usuário: \' . $e->getMessage()
            ];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao excluir usuário.\'];
    }
}
?>';

// Criar os arquivos
$modelsDir = __DIR__ . '/models';

// Criar Event.php
$eventFile = $modelsDir . '/Event.php';
if (file_put_contents($eventFile, $eventModelContent)) {
    echo "<p>✅ <strong>models/Event.php</strong> criado com sucesso!</p>";
} else {
    echo "<p>❌ Erro ao criar models/Event.php</p>";
}

// Criar User.php  
$userFile = $modelsDir . '/User.php';
if (file_put_contents($userFile, $userModelContent)) {
    echo "<p>✅ <strong>models/User.php</strong> criado com sucesso!</p>";
} else {
    echo "<p>❌ Erro ao criar models/User.php</p>";
}

// Verificar se os arquivos foram criados
echo "<h2>🔍 Verificação Final</h2>";
echo "<p><strong>Event.php existe:</strong> " . (file_exists($eventFile) ? '✅ SIM' : '❌ NÃO') . "</p>";
echo "<p><strong>User.php existe:</strong> " . (file_exists($userFile) ? '✅ SIM' : '❌ NÃO') . "</p>";

if (file_exists($eventFile) && file_exists($userFile)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Sucesso!</h3>";
    echo "<p>Os models foram criados com sucesso! Agora você pode:</p>";
    echo "<ul>";
    echo "<li>✅ Executar o diagnóstico novamente</li>";
    echo "<li>✅ Acessar a aplicação normalmente</li>";
    echo "<li>✅ Fazer login com: <strong>admin@conectaeventos.com</strong> / <strong>admin123</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // Apagar este arquivo após o uso
    echo "<p><small>Este arquivo será removido automaticamente em 10 segundos...</small></p>";
    echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 10000);</script>";
    
    // Tentar apagar o arquivo
    $currentFile = __FILE__;
    register_shutdown_function(function() use ($currentFile) {
        if (file_exists($currentFile)) {
            unlink($currentFile);
        }
    });
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f1b0b7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Erro</h3>";
    echo "<p>Houve um problema na criação dos arquivos. Verifique as permissões do diretório.</p>";
    echo "</div>";
}