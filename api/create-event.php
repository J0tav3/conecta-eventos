<?php
// ==========================================
// ARQUIVO 1: api/create-event.php (NOVO)
// ==========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log de debug para Railway
error_log("API create-event.php iniciada - Method: " . $_SERVER['REQUEST_METHOD']);

// Capturar todos os erros
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Iniciar sessão com verificação
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar se está logado
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        throw new Exception('Usuário não autenticado');
    }

    // Verificar se é organizador
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
        throw new Exception('Usuário não é organizador');
    }

    error_log("Usuário autenticado: " . ($_SESSION['user_name'] ?? 'N/A'));

    // Validar dados obrigatórios
    $required_fields = ['titulo', 'descricao', 'data_inicio', 'horario_inicio', 'local_nome', 'local_endereco', 'local_cidade', 'local_estado'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Campo obrigatório ausente: $field");
        }
    }

    error_log("Validação dos campos obrigatórios passou");

    // Sanitizar dados
    $evento_data = [
        'titulo' => trim($_POST['titulo']),
        'descricao' => trim($_POST['descricao']),
        'categoria' => !empty($_POST['categoria']) ? (int)$_POST['categoria'] : null,
        'data_inicio' => $_POST['data_inicio'],
        'data_fim' => !empty($_POST['data_fim']) ? $_POST['data_fim'] : $_POST['data_inicio'],
        'horario_inicio' => $_POST['horario_inicio'],
        'horario_fim' => !empty($_POST['horario_fim']) ? $_POST['horario_fim'] : $_POST['horario_inicio'],
        'local_nome' => trim($_POST['local_nome']),
        'local_endereco' => trim($_POST['local_endereco']),
        'local_cidade' => trim($_POST['local_cidade']),
        'local_estado' => $_POST['local_estado'],
        'local_cep' => $_POST['local_cep'] ?? null,
        'evento_gratuito' => isset($_POST['evento_gratuito']) ? 1 : 0,
        'preco' => isset($_POST['evento_gratuito']) ? 0 : (float)($_POST['preco'] ?? 0),
        'max_participantes' => !empty($_POST['max_participantes']) ? (int)$_POST['max_participantes'] : null,
        'requisitos' => trim($_POST['requisitos'] ?? ''),
        'o_que_levar' => trim($_POST['o_que_levar'] ?? ''),
        'organizador_id' => $_SESSION['user_id']
    ];

    error_log("Dados do evento preparados: " . json_encode(array_keys($evento_data)));

    // Processamento de imagem simplificado
    $imagem_nome = null;
    if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] !== UPLOAD_ERR_NO_FILE) {
        $resultado_upload = processarUploadImagem($_FILES['imagem_capa']);
        if ($resultado_upload['success']) {
            $imagem_nome = $resultado_upload['filename'];
            error_log("Imagem processada: $imagem_nome");
        } else {
            error_log("Erro no upload da imagem: " . $resultado_upload['message']);
            // Não falhar por causa da imagem, continuar sem ela
        }
    }

    // Conectar ao banco
    $database_url = getenv('DATABASE_URL');
    if (!$database_url) {
        throw new Exception('DATABASE_URL não configurada');
    }

    $url_parts = parse_url($database_url);
    if (!$url_parts) {
        throw new Exception('Formato de DATABASE_URL inválido');
    }

    $host = $url_parts['host'] ?? 'localhost';
    $port = $url_parts['port'] ?? 3306;
    $dbname = ltrim($url_parts['path'] ?? '', '/');
    $username = $url_parts['user'] ?? '';
    $password = $url_parts['pass'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    error_log("Conectado ao banco de dados");

    // Verificar se tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'eventos'");
    if ($stmt->rowCount() === 0) {
        throw new Exception('Tabela eventos não existe');
    }

    // Inserir evento no banco
    $sql = "INSERT INTO eventos (
                id_organizador, id_categoria, titulo, descricao, 
                data_inicio, data_fim, horario_inicio, horario_fim,
                local_nome, local_endereco, local_cidade, local_estado, local_cep,
                evento_gratuito, preco, capacidade_maxima,
                requisitos, informacoes_adicionais, imagem_capa, status, data_criacao
            ) VALUES (
                :organizador_id, :categoria_id, :titulo, :descricao,
                :data_inicio, :data_fim, :horario_inicio, :horario_fim,
                :local_nome, :local_endereco, :local_cidade, :local_estado, :local_cep,
                :evento_gratuito, :preco, :capacidade_maxima,
                :requisitos, :informacoes_adicionais, :imagem_capa, 'rascunho', NOW()
            )";

    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':organizador_id' => $evento_data['organizador_id'],
        ':categoria_id' => $evento_data['categoria'],
        ':titulo' => $evento_data['titulo'],
        ':descricao' => $evento_data['descricao'],
        ':data_inicio' => $evento_data['data_inicio'],
        ':data_fim' => $evento_data['data_fim'],
        ':horario_inicio' => $evento_data['horario_inicio'],
        ':horario_fim' => $evento_data['horario_fim'],
        ':local_nome' => $evento_data['local_nome'],
        ':local_endereco' => $evento_data['local_endereco'],
        ':local_cidade' => $evento_data['local_cidade'],
        ':local_estado' => $evento_data['local_estado'],
        ':local_cep' => $evento_data['local_cep'],
        ':evento_gratuito' => $evento_data['evento_gratuito'],
        ':preco' => $evento_data['preco'],
        ':capacidade_maxima' => $evento_data['max_participantes'],
        ':requisitos' => $evento_data['requisitos'],
        ':informacoes_adicionais' => $evento_data['o_que_levar'],
        ':imagem_capa' => $imagem_nome
    ];

    error_log("Executando INSERT no banco de dados");
    $result = $stmt->execute($params);

    if ($result) {
        $evento_id = $pdo->lastInsertId();
        error_log("Evento criado com sucesso. ID: $evento_id");

        echo json_encode([
            'success' => true,
            'message' => $imagem_nome ? 'Evento criado com sucesso e imagem enviada!' : 'Evento criado com sucesso!',
            'evento_id' => $evento_id,
            'redirect' => '/views/events/view.php?id=' . $evento_id
        ]);
    } else {
        throw new Exception('Falha ao executar INSERT');
    }

} catch (Exception $e) {
    error_log("Erro na API create-event: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Processar upload de imagem de forma simplificada
 */
function processarUploadImagem($file) {
    try {
        // Validações básicas
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            return ['success' => false, 'message' => 'Arquivo muito grande (máximo 5MB)'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload'];
        }

        // Diretório de upload
        $uploadDir = dirname(__DIR__) . '/uploads/eventos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'evento_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        // Mover arquivo
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'url' => 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $filename
            ];
        } else {
            return ['success' => false, 'message' => 'Erro ao salvar arquivo'];
        }
    } catch (Exception $e) {
        error_log("Erro no upload de imagem: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno no upload'];
    }
}
