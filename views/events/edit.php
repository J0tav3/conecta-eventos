<?php
// ==========================================
// EDITAR EVENTO - VERSÃO COM DEBUG
// Local: views/events/edit.php
// ==========================================

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log de início
error_log("DEBUG: Iniciando edit.php");

try {
    session_start();
    error_log("DEBUG: Sessão iniciada");

    // Verificar se está logado e é organizador
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        error_log("DEBUG: Usuário não logado, redirecionando");
        header("Location: ../auth/login.php");
        exit;
    }

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
        error_log("DEBUG: Usuário não é organizador, redirecionando");
        header("Location: ../dashboard/participant.php");
        exit;
    }

    error_log("DEBUG: Verificações de login OK");

    $title = "Editar Evento - Conecta Eventos";
    $userName = $_SESSION['user_name'] ?? 'Organizador';

    // URLs
    $dashboardUrl = '../dashboard/organizer.php';
    $homeUrl = '../../index.php';

    // Verificar se ID foi fornecido
    $eventId = $_GET['id'] ?? null;
    if (!$eventId) {
        error_log("DEBUG: ID do evento não fornecido");
        header('Location: list.php');
        exit();
    }

    error_log("DEBUG: Event ID: " . $eventId);

    $success_message = '';
    $error_message = '';
    $evento = null;
    $categorias = [];

    // Carregar dados do evento
    try {
        error_log("DEBUG: Tentando carregar EventController");
        
        // Verificar se arquivo existe antes de incluir
        $eventControllerPath = '../../controllers/EventController.php';
        if (!file_exists($eventControllerPath)) {
            throw new Exception("EventController.php não encontrado em: " . $eventControllerPath);
        }
        
        require_once $eventControllerPath;
        error_log("DEBUG: EventController incluído");
        
        $eventController = new EventController();
        error_log("DEBUG: EventController instanciado");
        
        $evento = $eventController->getById($eventId);
        error_log("DEBUG: Evento carregado: " . ($evento ? 'SIM' : 'NÃO'));
        
        if (!$evento || !$eventController->canEdit($eventId)) {
            $error_message = "Evento não encontrado ou você não tem permissão para editá-lo.";
            error_log("DEBUG: Erro - " . $error_message);
        } else {
            $categorias = $eventController->getCategories();
            error_log("DEBUG: Categorias carregadas: " . count($categorias));
        }
    } catch (Exception $e) {
        error_log("DEBUG: Erro ao carregar evento: " . $e->getMessage());
        $error_message = "Erro ao carregar dados do evento: " . $e->getMessage();
    }

    // Se não conseguiu carregar o evento, mostrar erro em vez de redirecionar
    if (!$evento && !$error_message) {
        $error_message = "Evento não encontrado";
    }

    // Definir classe ImageUploadHandler inline para evitar problemas
    if (!class_exists('ImageUploadHandler')) {
        error_log("DEBUG: Criando ImageUploadHandler inline");
        
        class ImageUploadHandler {
            private $uploadDir;
            private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            private $maxFileSize = 5242880; // 5MB
            
            public function __construct() {
                $this->uploadDir = __DIR__ . '/../../uploads/eventos/';
                if (!file_exists($this->uploadDir)) {
                    mkdir($this->uploadDir, 0755, true);
                }
            }
            
            public function uploadImage($fileArray, $oldImageName = null) {
                if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
                    return ['success' => false, 'message' => 'Erro no upload', 'filename' => null];
                }
                
                $extension = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, $this->allowedTypes)) {
                    return ['success' => false, 'message' => 'Tipo não permitido', 'filename' => null];
                }
                
                if ($fileArray['size'] > $this->maxFileSize) {
                    return ['success' => false, 'message' => 'Arquivo muito grande', 'filename' => null];
                }
                
                $newFileName = 'evento_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
                $targetPath = $this->uploadDir . $newFileName;
                
                if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
                    if ($oldImageName && file_exists($this->uploadDir . $oldImageName)) {
                        unlink($this->uploadDir . $oldImageName);
                    }
                    return [
                        'success' => true,
                        'message' => 'Upload realizado!',
                        'filename' => $newFileName,
                        'url' => 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $newFileName
                    ];
                }
                
                return ['success' => false, 'message' => 'Erro ao salvar', 'filename' => null];
            }
            
            public function deleteImage($fileName) {
                if ($fileName && file_exists($this->uploadDir . $fileName)) {
                    return unlink($this->uploadDir . $fileName);
                }
                return true;
            }
            
            public function getImageUrl($fileName) {
                return 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $fileName;
            }
        }
    }

    // Processar formulário apenas se tiver dados válidos
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message && $evento) {
        error_log("DEBUG: Processando formulário POST");
        
        try {
            $imageHandler = new ImageUploadHandler();
            
            // Processar upload de nova imagem se enviada
            $imageResult = null;
            $newImageName = null;
            
            if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] !== UPLOAD_ERR_NO_FILE) {
                error_log("DEBUG: Processando upload de imagem");
                $oldImageName = $evento['imagem_capa'];
                $imageResult = $imageHandler->uploadImage($_FILES['imagem_capa'], $oldImageName);
                
                if (!$imageResult['success']) {
                    $error_message = "Erro no upload da imagem: " . $imageResult['message'];
                    error_log("DEBUG: Erro no upload - " . $error_message);
                } else {
                    $newImageName = $imageResult['filename'];
                    error_log("DEBUG: Upload bem-sucedido - " . $newImageName);
                }
            }
            
            // Se não houve erro na imagem, atualizar evento
            if (!$error_message) {
                error_log("DEBUG: Preparando dados para atualização");
                
                // Preparar dados para atualização
                $updateData = [
                    'titulo' => trim($_POST['titulo'] ?? ''),
                    'descricao' => trim($_POST['descricao'] ?? ''),
                    'id_categoria' => !empty($_POST['categoria']) ? (int)$_POST['categoria'] : null,
                    'data_inicio' => $_POST['data_inicio'] ?? '',
                    'data_fim' => $_POST['data_fim'] ?? $_POST['data_inicio'],
                    'horario_inicio' => $_POST['horario_inicio'] ?? '',
                    'horario_fim' => $_POST['horario_fim'] ?? $_POST['horario_inicio'],
                    'local_nome' => trim($_POST['local_nome'] ?? ''),
                    'local_endereco' => trim($_POST['local_endereco'] ?? ''),
                    'local_cidade' => trim($_POST['local_cidade'] ?? ''),
                    'local_estado' => $_POST['local_estado'] ?? '',
                    'local_cep' => $_POST['local_cep'] ?? null,
                    'capacidade_maxima' => !empty($_POST['max_participantes']) ? (int)$_POST['max_participantes'] : null,
                    'evento_gratuito' => isset($_POST['evento_gratuito']) ? 1 : 0,
                    'preco' => isset($_POST['evento_gratuito']) ? 0 : (float)($_POST['preco'] ?? 0),
                    'requisitos' => trim($_POST['requisitos'] ?? ''),
                    'informacoes_adicionais' => trim($_POST['o_que_levar'] ?? ''),
                    'status' => $_POST['status'] ?? 'rascunho'
                ];
                
                // Adicionar nova imagem se foi enviada
                if ($newImageName) {
                    $updateData['imagem_capa'] = $newImageName;
                }
                
                // Verificar se deve remover imagem atual
                if (isset($_POST['remove_current_image']) && $_POST['remove_current_image'] && $evento['imagem_capa']) {
                    $imageHandler->deleteImage($evento['imagem_capa']);
                    $updateData['imagem_capa'] = null;
                }
                
                error_log("DEBUG: Tentando atualizar evento");
                $result = $eventController->update($eventId, $updateData);
                
                if ($result['success']) {
                    $success_message = $result['message'];
                    error_log("DEBUG: Evento atualizado com sucesso");
                    // Recarregar dados atualizados
                    $evento = $eventController->getById($eventId);
                } else {
                    $error_message = $result['message'];
                    error_log("DEBUG: Erro ao atualizar evento - " . $error_message);
                    
                    // Se evento falhou mas imagem foi enviada, deletar imagem nova
                    if ($newImageName) {
                        $imageHandler->deleteImage($newImageName);
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("DEBUG: Exception no processamento POST: " . $e->getMessage());
            $error_message = "Erro interno do sistema: " . $e->getMessage();
            
            // Deletar imagem se foi enviada
            if (isset($newImageName) && $newImageName) {
                try {
                    $imageHandler->deleteImage($newImageName);
                } catch (Exception $deleteException) {
                    error_log("DEBUG: Erro ao deletar imagem após falha: " . $deleteException->getMessage());
                }
            }
        }
    }

    // Preparar valores padrão para o formulário (mesmo se não tiver evento)
    $defaults = [];
    if ($evento) {
        $defaults = [
            'titulo' => $evento['titulo'] ?? '',
            'descricao' => $evento['descricao'] ?? '',
            'categoria' => $evento['id_categoria'] ?? '',
            'data_inicio' => date('Y-m-d', strtotime($evento['data_inicio'] ?? 'now')),
            'data_fim' => date('Y-m-d', strtotime($evento['data_fim'] ?? $evento['data_inicio'] ?? 'now')),
            'horario_inicio' => $evento['horario_inicio'] ?? '',
            'horario_fim' => $evento['horario_fim'] ?? '',
            'local_nome' => $evento['local_nome'] ?? '',
            'local_endereco' => $evento['local_endereco'] ?? '',
            'local_cidade' => $evento['local_cidade'] ?? '',
            'local_estado' => $evento['local_estado'] ?? '',
            'local_cep' => $evento['local_cep'] ?? '',
            'max_participantes' => $evento['capacidade_maxima'] ?? '',
            'evento_gratuito' => $evento['evento_gratuito'] ?? false,
            'preco' => $evento['preco'] ?? '',
            'requisitos' => $evento['requisitos'] ?? '',
            'o_que_levar' => $evento['informacoes_adicionais'] ?? '',
            'status' => $evento['status'] ?? 'rascunho',
            'imagem_capa' => $evento['imagem_capa'] ?? ''
        ];
    } else {
        // Valores padrão vazios se não tiver evento
        $defaults = [
            'titulo' => '', 'descricao' => '', 'categoria' => '',
            'data_inicio' => date('Y-m-d'), 'data_fim' => date('Y-m-d'),
            'horario_inicio' => '', 'horario_fim' => '',
            'local_nome' => '', 'local_endereco' => '', 'local_cidade' => '',
            'local_estado' => '', 'local_cep' => '', 'max_participantes' => '',
            'evento_gratuito' => false, 'preco' => '', 'requisitos' => '',
            'o_que_levar' => '', 'status' => 'rascunho', 'imagem_capa' => ''
        ];
    }

    // Sobrescrever com dados do POST em caso de erro
    if ($_POST && $error_message) {
        foreach ($defaults as $key => $value) {
            if (isset($_POST[$key])) {
                $defaults[$key] = $_POST[$key];
            }
        }
    }

    // URL da imagem atual
    $currentImageUrl = '';
    if ($defaults['imagem_capa']) {
        $currentImageUrl = 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $defaults['imagem_capa'];
    }

    error_log("DEBUG: Chegou ao final do processamento PHP, iniciando HTML");

} catch (Exception $e) {
    error_log("FATAL ERROR: " . $e->getMessage());
    echo "<!DOCTYPE html><html><head><title>Erro</title></head><body>";
    echo "<h1>Erro Fatal</h1>";
    echo "<p>Ocorreu um erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='../dashboard/organizer.php'>Voltar ao Dashboard</a></p>";
    echo "</body></html>";
    exit;
}

// Fallback para categorias vazias
if (empty($categorias)) {
    $categorias = [
        ['id_categoria' => 1, 'nome' => 'Tecnologia'],
        ['id_categoria' => 2, 'nome' => 'Negócios'],
        ['id_categoria' => 3, 'nome' => 'Marketing'],
        ['id_categoria' => 4, 'nome' => 'Design'],
        ['id_categoria' => 5, 'nome' => 'Educação']
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .edit-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .form-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .error-section {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo htmlspecialchars($homeUrl); ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Olá, <?php echo htmlspecialchars($userName); ?>!
                </span>
                <a class="nav-link" href="<?php echo htmlspecialchars($dashboardUrl); ?>">Dashboard</a>
                <a class="nav-link" href="../../logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="text-decoration-none">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="list.php" class="text-decoration-none">Meus Eventos</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Editar Evento</li>
            </ol>
        </nav>
    </div>

    <!-- Header da Página -->
    <section class="edit-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-edit me-2"></i>Editar Evento</h1>
                    <p class="mb-0 fs-5">
                        <?php if ($evento): ?>
                            Atualize as informações do seu evento
                        <?php else: ?>
                            Problema ao carregar evento
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php if ($evento): ?>
                        <a href="view.php?id=<?php echo $eventId; ?>" class="btn btn-outline-light me-2">
                            <i class="fas fa-eye me-2"></i>Visualizar
                        </a>
                    <?php endif; ?>
                    <a href="list.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container pb-5">
        <!-- Mensagens -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$evento): ?>
            <!-- Seção de Erro se evento não foi carregado -->
            <div class="error-section">
                <h3><i class="fas fa-exclamation-triangle me-2"></i>Evento Não Encontrado</h3>
                <p>Não foi possível carregar os dados do evento. Isso pode acontecer por:</p>
                <ul>
                    <li>O evento não existe</li>
                    <li>Você não tem permissão para editar este evento</li>
                    <li>Problemas de conexão com o banco de dados</li>
                </ul>
                <div class="mt-3">
                    <a href="list.php" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>Ver Meus Eventos
                    </a>
                    <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulário de Edição -->
            <form method="POST" enctype="multipart/form-data" id="editEventForm">
                <!-- Informações Básicas -->
                <div class="form-section">
                    <h4><i class="fas fa-info-circle me-2"></i>Informações Básicas</h4>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="titulo" class="form-label">Título do Evento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required
                                   value="<?php echo htmlspecialchars($defaults['titulo']); ?>"
                                   placeholder="Ex: Workshop de Desenvolvimento Web">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="categoria" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>" 
                                            <?php echo $defaults['categoria'] == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4" required
                                  placeholder="Descreva seu evento de forma clara e atrativa"><?php echo htmlspecialchars($defaults['descricao']); ?></textarea>
                    </div>
                </div>

                <!-- Botões -->
                <div class="d-flex justify-content-between">
                    <a href="list.php" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <!-- Debug Info (apenas para desenvolvimento) -->
        <div class="mt-5 p-3 bg-light border rounded" style="font-size: 0.875rem;">
            <h6>Debug Info:</h6>
            <ul class="mb-0">
                <li>Event ID: <?php echo htmlspecialchars($eventId ?? 'N/A'); ?></li>
                <li>Evento carregado: <?php echo $evento ? 'SIM' : 'NÃO'; ?></li>
                <li>Categorias: <?php echo count($categorias); ?></li>
                <li>Usuário: <?php echo htmlspecialchars($userName); ?></li>
                <li>Método: <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('Página carregada com sucesso');
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            });
        }, 5000);
    </script>
</body>
</html>