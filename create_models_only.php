<?php
// ========================================
// CRIAR APENAS OS MODELS - EMERGÊNCIA
// ========================================

echo "<h1>🔧 Criando Models - Emergência</h1>";

// Criar diretório models se não existir
$modelsDir = __DIR__ . '/models';
if (!file_exists($modelsDir)) {
    mkdir($modelsDir, 0755, true);
    echo "<p>✅ Diretório models/ criado</p>";
} else {
    echo "<p>✅ Diretório models/ já existe</p>";
}

// 1. CRIAR USER.PHP
$userContent = '<?php
require_once __DIR__ . \'/../config/database.php\';

class User {
    private $conn;
    private $table = \'usuarios\';
    
    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
        } catch (Exception $e) {
            error_log("Erro no User Model: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function create($nome, $email, $senha, $tipo) {
        if ($this->emailExists($email)) {
            return [\'success\' => false, \'message\' => \'Este e-mail já está cadastrado no sistema.\'];
        }
        
        $query = "INSERT INTO " . $this->table . " (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        try {
            $result = $stmt->execute([$nome, $email, $senha_hash, $tipo]);
            if ($result) {
                return [\'success\' => true, \'message\' => \'Usuário cadastrado com sucesso!\', \'user_id\' => $this->conn->lastInsertId()];
            }
        } catch (PDOException $e) {
            return [\'success\' => false, \'message\' => \'Erro ao cadastrar usuário: \' . $e->getMessage()];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao cadastrar usuário.\'];
    }
    
    public function authenticate($email, $senha) {
        $query = "SELECT id_usuario, nome, email, senha, tipo, ativo FROM " . $this->table . " WHERE email = ? AND ativo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            if (password_verify($senha, $user[\'senha\'])) {
                unset($user[\'senha\']);
                return [\'success\' => true, \'user\' => $user, \'message\' => \'Login realizado com sucesso!\'];
            } else {
                return [\'success\' => false, \'message\' => \'Senha incorreta.\'];
            }
        }
        
        return [\'success\' => false, \'message\' => \'E-mail não encontrado ou usuário inativo.\'];
    }
    
    public function emailExists($email) {
        $query = "SELECT id_usuario FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }
    
    public function findById($id) {
        $query = "SELECT id_usuario, nome, email, tipo, data_criacao, ativo FROM " . $this->table . " WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        return false;
    }
    
    public function updateLastAccess($user_id) {
        $query = "UPDATE " . $this->table . " SET ultimo_acesso = CURRENT_TIMESTAMP WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id]);
    }
}
?>';

// 2. CRIAR EVENT.PHP
$eventContent = '<?php
require_once __DIR__ . \'/../config/database.php\';

class Event {
    private $conn;
    private $table = \'eventos\';
    
    public function __construct() {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
        } catch (Exception $e) {
            error_log("Erro no Event Model: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " (
            id_organizador, id_categoria, titulo, descricao,
            data_inicio, data_fim, horario_inicio, horario_fim,
            local_nome, local_endereco, local_cidade, local_estado, local_cep,
            capacidade_maxima, preco, evento_gratuito, imagem_capa,
            link_externo, requisitos, informacoes_adicionais, status, destaque
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute([
                $data[\'id_organizador\'], $data[\'id_categoria\'] ?? null, $data[\'titulo\'], $data[\'descricao\'],
                $data[\'data_inicio\'], $data[\'data_fim\'], $data[\'horario_inicio\'], $data[\'horario_fim\'],
                $data[\'local_nome\'], $data[\'local_endereco\'], $data[\'local_cidade\'], $data[\'local_estado\'], $data[\'local_cep\'] ?? null,
                $data[\'capacidade_maxima\'] ?? null, $data[\'preco\'] ?? 0.00, $data[\'evento_gratuito\'] ?? true, $data[\'imagem_capa\'] ?? null,
                $data[\'link_externo\'] ?? null, $data[\'requisitos\'] ?? null, $data[\'informacoes_adicionais\'] ?? null, $data[\'status\'] ?? \'rascunho\', $data[\'destaque\'] ?? false
            ]);
            
            if ($result) {
                return [\'success\' => true, \'message\' => \'Evento criado com sucesso!\', \'event_id\' => $this->conn->lastInsertId()];
            }
        } catch (PDOException $e) {
            return [\'success\' => false, \'message\' => \'Erro ao criar evento: \' . $e->getMessage()];
        }
        
        return [\'success\' => false, \'message\' => \'Erro desconhecido ao criar evento.\'];
    }
    
    public function findById($id) {
        $query = "SELECT e.*, u.nome AS nome_organizador, u.email AS email_organizador, c.nome AS nome_categoria, c.cor AS cor_categoria, c.icone AS icone_categoria, (SELECT COUNT(*) FROM inscricoes i WHERE i.id_evento = e.id_evento AND i.status = \'confirmada\') AS total_inscritos FROM " . $this->table . " e LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario LEFT JOIN categorias c ON e.id_categoria = c.id_categoria WHERE e.id_evento = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        return false;
    }
    
    public function getPublicEvents($filters = []) {
        $where = [\'e.status = "publicado"\'];
        $params = [];
        
        if (!empty($filters[\'busca\'])) {
            $where[] = "(e.titulo LIKE ? OR e.descricao LIKE ?)";
            $params[] = "%{$filters[\'busca\']}%";
            $params[] = "%{$filters[\'busca\']}%";
        }
        
        if (!empty($filters[\'cidade\'])) {
            $where[] = "e.local_cidade LIKE ?";
            $params[] = "%{$filters[\'cidade\']}%";
        }
        
        if (!empty($filters[\'categoria_id\'])) {
            $where[] = "e.id_categoria = ?";
            $params[] = $filters[\'categoria_id\'];
        }
        
        $limit = "";
        if (!empty($filters[\'limite\'])) {
            $limit = "LIMIT " . intval($filters[\'limite\']);
        }
        
        $query = "SELECT e.*, u.nome AS nome_organizador, c.nome AS nome_categoria, c.cor AS cor_categoria, c.icone AS icone_categoria, (SELECT COUNT(*) FROM inscricoes i WHERE i.id_evento = e.id_evento AND i.status = \'confirmada\') AS total_inscritos FROM " . $this->table . " e LEFT JOIN usuarios u ON e.id_organizador = u.id_usuario LEFT JOIN categorias c ON e.id_categoria = c.id_categoria WHERE " . implode(\' AND \', $where) . " ORDER BY e.data_inicio ASC " . $limit;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function getCategories() {
        $query = "SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function formatEventForDisplay($event) {
        if (!$event) return null;
        
        $event[\'data_inicio_formatada\'] = date(\'d/m/Y\', strtotime($event[\'data_inicio\']));
        $event[\'data_fim_formatada\'] = date(\'d/m/Y\', strtotime($event[\'data_fim\']));
        $event[\'horario_inicio_formatado\'] = date(\'H:i\', strtotime($event[\'horario_inicio\']));
        $event[\'horario_fim_formatado\'] = date(\'H:i\', strtotime($event[\'horario_fim\']));
        $event[\'preco_formatado\'] = $event[\'evento_gratuito\'] ? \'Gratuito\' : \'R$ \' . number_format($event[\'preco\'], 2, \',\', \'.\');
        
        $event[\'imagem_url\'] = !empty($event[\'imagem_capa\']) 
            ? SITE_URL . \'/public/uploads/eventos/\' . $event[\'imagem_capa\']
            : SITE_URL . \'/public/images/evento-default.jpg\';
            
        $statusMap = [\'rascunho\' => \'Rascunho\', \'publicado\' => \'Publicado\', \'cancelado\' => \'Cancelado\', \'finalizado\' => \'Finalizado\'];
        $event[\'status_nome\'] = $statusMap[$event[\'status\']] ?? $event[\'status\'];
        
        if ($event[\'capacidade_maxima\']) {
            $event[\'vagas_disponiveis\'] = $event[\'capacidade_maxima\'] - ($event[\'total_inscritos\'] ?? 0);
            $event[\'vagas_esgotadas\'] = $event[\'vagas_disponiveis\'] <= 0;
        } else {
            $event[\'vagas_disponiveis\'] = null;
            $event[\'vagas_esgotadas\'] = false;
        }
        
        return $event;
    }
}
?>';

// Escrever arquivos
$userFile = $modelsDir . '/User.php';
$eventFile = $modelsDir . '/Event.php';

echo "<h2>Criando Arquivos:</h2>";

if (file_put_contents($userFile, $userContent)) {
    echo "<p>✅ <strong>models/User.php</strong> criado com sucesso!</p>";
} else {
    echo "<p>❌ Erro ao criar models/User.php</p>";
}

if (file_put_contents($eventFile, $eventContent)) {
    echo "<p>✅ <strong>models/Event.php</strong> criado com sucesso!</p>";
} else {
    echo "<p>❌ Erro ao criar models/Event.php</p>";
}

// Verificar se foram criados
echo "<h2>Verificação:</h2>";
echo "<p><strong>User.php existe:</strong> " . (file_exists($userFile) ? '✅ SIM' : '❌ NÃO') . "</p>";
echo "<p><strong>Event.php existe:</strong> " . (file_exists($eventFile) ? '✅ SIM' : '❌ NÃO') . "</p>";

if (file_exists($userFile) && file_exists($eventFile)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Models Criados!</h3>";
    echo "<p>Agora você pode executar:</p>";
    echo "<p><strong><a href='fix_final.php'>fix_final.php</a></strong></p>";
    echo "</div>";
}

echo "<style>body{font-family:Arial,sans-serif;margin:20px;} h1{color:#007bff;} h2{color:#495057;margin-top:20px;} .error{color:#dc3545;} .success{color:#28a745;} a{color:#007bff;}</style>";
?>