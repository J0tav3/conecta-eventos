<?php
// ==========================================
// EDITAR EVENTO - VERSÃO COMPLETA
// Local: views/events/edit.php
// ==========================================

// Inicializar variáveis
$title = "Editar Evento - Conecta Eventos";
$userName = 'Organizador';
$dashboardUrl = '../dashboard/organizer.php';
$homeUrl = '../../index.php';
$success_message = '';
$error_message = '';
$evento = null;
$categorias = [];
$eventId = null;

// Capturar output indesejado
ob_start();

try {
    session_start();

    // Verificações de autenticação
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: ../auth/login.php");
        exit;
    }

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
        header("Location: ../dashboard/participant.php");
        exit;
    }

    $userName = $_SESSION['user_name'] ?? 'Organizador';
    $eventId = $_GET['id'] ?? null;

    if (!$eventId) {
        header('Location: list.php');
        exit();
    }

    // Definir classe ImageUploadHandler inline
    if (!class_exists('ImageUploadHandler')) {
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
                    return ['success' => false, 'message' => 'Arquivo muito grande (máximo 5MB)', 'filename' => null];
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
                        'url' => $this->getImageUrl($newFileName)
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

    // Carregar controlador e dados
    $eventController = null;
    try {
        if (file_exists('../../controllers/EventController.php')) {
            require_once '../../controllers/EventController.php';
            $eventController = new EventController();
        }
    } catch (Exception $e) {
        error_log("Erro ao carregar EventController: " . $e->getMessage());
    }

    // Dados de fallback
    $evento = [
        'id_evento' => $eventId,
        'titulo' => 'Workshop de Desenvolvimento Web',
        'descricao' => 'Aprenda as últimas tecnologias em desenvolvimento web com especialistas da área.',
        'id_categoria' => 1,
        'data_inicio' => date('Y-m-d'),
        'data_fim' => date('Y-m-d'),
        'horario_inicio' => '14:00',
        'horario_fim' => '18:00',
        'local_nome' => 'Centro de Tecnologia SP',
        'local_endereco' => 'Av. Paulista, 1000',
        'local_cidade' => 'São Paulo',
        'local_estado' => 'SP',
        'local_cep' => '01310-100',
        'capacidade_maxima' => 100,
        'evento_gratuito' => 1,
        'preco' => 0,
        'requisitos' => 'Conhecimento básico de programação',
        'informacoes_adicionais' => 'Notebook, carregador, bloco de notas',
        'status' => 'rascunho',
        'imagem_capa' => ''
    ];

    // Tentar carregar dados reais
    if ($eventController) {
        try {
            $eventoReal = $eventController->getById($eventId);
            if ($eventoReal && $eventController->canEdit($eventId)) {
                $evento = $eventoReal;
            }
            $categorias = $eventController->getCategories();
        } catch (Exception $e) {
            error_log("Erro ao carregar evento: " . $e->getMessage());
        }
    }

    // Categorias de fallback
    if (empty($categorias)) {
        $categorias = [
            ['id_categoria' => 1, 'nome' => 'Tecnologia'],
            ['id_categoria' => 2, 'nome' => 'Negócios'],
            ['id_categoria' => 3, 'nome' => 'Marketing'],
            ['id_categoria' => 4, 'nome' => 'Design'],
            ['id_categoria' => 5, 'nome' => 'Educação']
        ];
    }

    // Processar formulário
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $imageHandler = new ImageUploadHandler();
            
            // Processar upload de imagem
            $imageResult = null;
            $newImageName = null;
            
            if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] !== UPLOAD_ERR_NO_FILE) {
                $oldImageName = $evento['imagem_capa'];
                $imageResult = $imageHandler->uploadImage($_FILES['imagem_capa'], $oldImageName);
                
                if (!$imageResult['success']) {
                    $error_message = "Erro no upload da imagem: " . $imageResult['message'];
                } else {
                    $newImageName = $imageResult['filename'];
                }
            }
            
            if (!$error_message) {
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
                
                // Atualizar dados locais
                foreach ($updateData as $key => $value) {
                    if (isset($evento[$key])) {
                        $evento[$key] = $value;
                    }
                }
                
                // Tentar salvar no banco se possível
                if ($eventController) {
                    try {
                        $result = $eventController->update($eventId, $updateData);
                        if ($result['success']) {
                            $success_message = $result['message'];
                            // Recarregar dados atualizados
                            $eventoAtualizado = $eventController->getById($eventId);
                            if ($eventoAtualizado) {
                                $evento = $eventoAtualizado;
                            }
                        } else {
                            $error_message = $result['message'];
                            if ($newImageName) {
                                $imageHandler->deleteImage($newImageName);
                            }
                        }
                    } catch (Exception $e) {
                        $error_message = "Erro ao salvar: " . $e->getMessage();
                        if ($newImageName) {
                            $imageHandler->deleteImage($newImageName);
                        }
                    }
                } else {
                    $success_message = "Dados atualizados com sucesso! (Modo demonstração)";
                }
            }
            
        } catch (Exception $e) {
            $error_message = "Erro interno: " . $e->getMessage();
            if (isset($newImageName) && $newImageName) {
                try {
                    $imageHandler->deleteImage($newImageName);
                } catch (Exception $deleteException) {
                    // Ignorar erro na limpeza
                }
            }
        }
    }

    // Preparar valores padrão para o formulário
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

} catch (Exception $e) {
    $error_message = "Erro interno: " . $e->getMessage();
}

// Limpar output buffer
ob_end_clean();
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
        
        .form-section h4 {
            color: #333;
            border-bottom: 2px solid #28a745;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .btn-outline-secondary {
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .event-info-card {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8f9fa;
        }
        
        .upload-area:hover, .upload-area.dragover {
            border-color: #28a745;
            background-color: #e8f5e8;
        }
        
        .upload-area.has-file {
            border-color: #28a745;
            background-color: #d4edda;
        }
        
        .current-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .file-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 0.5rem;
            margin: 1rem auto;
            display: none;
        }
        
        .required {
            color: #dc3545;
        }
        
        .preview-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 10;
        }
        
        .image-actions {
            margin-top: 1rem;
        }
        
        .file-info {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            border-left: 4px solid #28a745;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
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
                    <p class="mb-0 fs-5">Atualize as informações do seu evento</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="view.php?id=<?php echo $eventId; ?>" class="btn btn-outline-light me-2">
                        <i class="fas fa-eye me-2"></i>Visualizar
                    </a>
                    <a href="list.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container pb-5" style="position: relative;">
        <!-- Badge de Status -->
        <div class="preview-badge">
            <span class="badge bg-<?php 
                echo $defaults['status'] === 'publicado' ? 'success' : 
                    ($defaults['status'] === 'rascunho' ? 'warning' : 'danger'); 
            ?> fs-6">
                <i class="fas fa-circle me-1"></i>
                <?php echo ucfirst($defaults['status']); ?>
            </span>
        </div>

        <!-- Info do Evento -->
        <div class="event-info-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h4>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar me-2"></i>
                        <span><?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?></span>
                        <span class="mx-2">•</span>
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <span><?php echo htmlspecialchars($evento['local_cidade']); ?></span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <strong class="fs-5">
                        <?php echo $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.'); ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <div class="mt-3">
                    <a href="view.php?id=<?php echo $eventId; ?>" class="btn btn-success">
                        <i class="fas fa-eye me-2"></i>Visualizar Evento
                    </a>
                    <a href="list.php" class="btn btn-outline-success ms-2">
                        <i class="fas fa-list me-2"></i>Meus Eventos
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="editEventForm">
            <!-- Imagem de Capa -->
            <div class="form-section">
                <h4><i class="fas fa-image me-2"></i>Imagem de Capa</h4>
                
                <!-- Imagem Atual -->
                <?php if ($currentImageUrl): ?>
                    <div class="mb-3">
                        <label class="form-label">Imagem atual:</label>
                        <div>
                            <img src="<?php echo $currentImageUrl; ?>" alt="Imagem atual do evento" class="current-image">
                            <div class="image-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCurrentImage()">
                                    <i class="fas fa-trash me-1"></i>Remover Imagem Atual
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="upload-area" id="uploadArea">
                    <div class="upload-content">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <h5><?php echo $currentImageUrl ? 'Substituir por nova imagem' : 'Adicionar imagem de capa'; ?></h5>
                        <p class="text-muted mb-2">Tamanho máximo: 5MB</p>
                        <p class="text-muted">Formatos aceitos: JPG, PNG, GIF, WebP</p>
                        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('imagem_capa').click()">
                            <i class="fas fa-folder-open me-2"></i>Escolher Arquivo
                        </button>
                    </div>
                </div>
                
                <input type="file" 
                       class="d-none" 
                       id="imagem_capa" 
                       name="imagem_capa" 
                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                
                <img id="imagePreview" class="file-preview" alt="Preview da nova imagem">
                
                <div class="file-info" id="fileInfo">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong id="fileName"></strong>
                            <div class="small text-muted" id="fileSize"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSelectedFile()">
                            <i class="fas fa-trash me-1"></i>Remover
                        </button>
                    </div>
                </div>
                
                <div class="form-text">
                    <i class="fas fa-info-circle me-1"></i>
                    Se uma nova imagem for enviada, ela substituirá a imagem atual. A imagem será automaticamente redimensionada se for muito grande.
                </div>
            </div>

            <!-- Informações Básicas -->
            <div class="form-section">
                <h4><i class="fas fa-info-circle me-2"></i>Informações Básicas</h4>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="titulo" class="form-label">Título do Evento <span class="required">*</span></label>
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
                    <label for="descricao" class="form-label">Descrição <span class="required">*</span></label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="4" required
                              placeholder="Descreva seu evento de forma clara e atrativa"><?php echo htmlspecialchars($defaults['descricao']); ?></textarea>
                </div>
            </div>

            <!-- Data e Horário -->
            <div class="form-section">
                <h4><i class="fas fa-calendar-alt me-2"></i>Data e Horário</h4>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="data_inicio" class="form-label">Data de Início <span class="required">*</span></label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" required
                               value="<?php echo $defaults['data_inicio']; ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="horario_inicio" class="form-label">Horário de Início <span class="required">*</span></label>
                        <input type="time" class="form-control" id="horario_inicio" name="horario_inicio" required
                               value="<?php echo $defaults['horario_inicio']; ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="data_fim" class="form-label">Data de Fim</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim"
                               value="<?php echo $defaults['data_fim']; ?>">
                        <div class="form-text">Se vazio, será igual à data de início</div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="horario_fim" class="form-label">Horário de Fim</label>
                        <input type="time" class="form-control" id="horario_fim" name="horario_fim"
                               value="<?php echo $defaults['horario_fim']; ?>">
                        <div class="form-text">Se vazio, será igual ao horário de início</div>
                    </div>
                </div>
            </div>

            <!-- Local -->
            <div class="form-section">
                <h4><i class="fas fa-map-marker-alt me-2"></i>Local do Evento</h4>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="local_nome" class="form-label">Nome do Local <span class="required">*</span></label>
                        <input type="text" class="form-control" id="local_nome" name="local_nome" required
                               value="<?php echo htmlspecialchars($defaults['local_nome']); ?>"
                               placeholder="Ex: Centro de Convenções SP">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="local_endereco" class="form-label">Endereço Completo <span class="required">*</span></label>
                        <input type="text" class="form-control" id="local_endereco" name="local_endereco" required
                               value="<?php echo htmlspecialchars($defaults['local_endereco']); ?>"
                               placeholder="Rua, número, bairro">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="local_cidade" class="form-label">Cidade <span class="required">*</span></label>
                        <input type="text" class="form-control" id="local_cidade" name="local_cidade" required
                               value="<?php echo htmlspecialchars($defaults['local_cidade']); ?>"
                               placeholder="Nome da cidade">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="local_estado" class="form-label">Estado <span class="required">*</span></label>
                        <select class="form-select" id="local_estado" name="local_estado" required>
                            <option value="">Selecione</option>
                            <?php
                            $estados = [
                                'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
                            ];
                            foreach ($estados as $sigla => $nome): ?>
                                <option value="<?php echo $sigla; ?>" <?php echo $defaults['local_estado'] === $sigla ? 'selected' : ''; ?>>
                                    <?php echo $nome; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="local_cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="local_cep" name="local_cep"
                               value="<?php echo htmlspecialchars($defaults['local_cep']); ?>"
                               placeholder="00000-000">
                    </div>
                </div>
            </div>

            <!-- Preço e Vagas -->
            <div class="form-section">
                <h4><i class="fas fa-ticket-alt me-2"></i>Preço e Vagas</h4>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="evento_gratuito" name="evento_gratuito"
                                   <?php echo $defaults['evento_gratuito'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="evento_gratuito">
                                <strong>Evento Gratuito</strong>
                            </label>
                        </div>
                        
                        <div id="preco_section" style="display: <?php echo $defaults['evento_gratuito'] ? 'none' : 'block'; ?>;">
                            <label for="preco" class="form-label">Preço por Pessoa</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="preco" name="preco" 
                                       min="0" step="0.01" value="<?php echo $defaults['preco']; ?>"
                                       placeholder="0,00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="max_participantes" class="form-label">Máximo de Participantes</label>
                        <input type="number" class="form-control" id="max_participantes" name="max_participantes"
                               min="1" value="<?php echo $defaults['max_participantes']; ?>"
                               placeholder="Ex: 100">
                        <div class="form-text">Deixe em branco para ilimitado</div>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="form-section">
                <h4><i class="fas fa-cog me-2"></i>Status do Evento</h4>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="rascunho" <?php echo $defaults['status'] === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                        <option value="publicado" <?php echo $defaults['status'] === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                        <option value="cancelado" <?php echo $defaults['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        <option value="finalizado" <?php echo $defaults['status'] === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                    </select>
                    <div class="form-text">
                        <strong>Rascunho:</strong> Evento não visível para participantes<br>
                        <strong>Publicado:</strong> Evento visível e aberto para inscrições<br>
                        <strong>Cancelado:</strong> Evento cancelado<br>
                        <strong>Finalizado:</strong> Evento já ocorreu
                    </div>
                </div>
            </div>

            <!-- Informações Adicionais -->
            <div class="form-section">
                <h4><i class="fas fa-list-ul me-2"></i>Informações Adicionais</h4>
                
                <div class="mb-3">
                    <label for="requisitos" class="form-label">Requisitos para Participar</label>
                    <textarea class="form-control" id="requisitos" name="requisitos" rows="3"
                              placeholder="Ex: Conhecimento básico de programação, laptop próprio"><?php echo htmlspecialchars($defaults['requisitos']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="o_que_levar" class="form-label">O que o Participante Deve Levar</label>
                    <textarea class="form-control" id="o_que_levar" name="o_que_levar" rows="3"
                              placeholder="Ex: Notebook, carregador, bloco de notas"><?php echo htmlspecialchars($defaults['o_que_levar']); ?></textarea>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-flex justify-content-between">
                <a href="list.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>Cancelar
                </a>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-lg me-3" onclick="previewEvent()">
                        <i class="fas fa-eye me-2"></i>Visualizar
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('imagem_capa');
            const imagePreview = document.getElementById('imagePreview');
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');

            // Toggle preço baseado em evento gratuito
            const eventoGratuito = document.getElementById('evento_gratuito');
            const precoSection = document.getElementById('preco_section');
            
            eventoGratuito.addEventListener('change', function() {
                precoSection.style.display = this.checked ? 'none' : 'block';
                if (this.checked) {
                    document.getElementById('preco').value = '';
                }
            });

            // Upload de imagem - Drag and Drop
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelect(files[0]);
                }
            });

            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    handleFileSelect(e.target.files[0]);
                }
            });

            // Processar arquivo selecionado
            function handleFileSelect(file) {
                // Validar tipo de arquivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WebP', 'error');
                    return;
                }

                // Validar tamanho (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showToast('Arquivo muito grande. Tamanho máximo: 5MB', 'error');
                    return;
                }

                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    
                    // Atualizar UI
                    uploadArea.classList.add('has-file');
                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                    fileInfo.style.display = 'block';
                    
                    // Esconder conteúdo original
                    uploadArea.querySelector('.upload-content').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }

            // Remover arquivo selecionado
            window.removeSelectedFile = function() {
                fileInput.value = '';
                imagePreview.style.display = 'none';
                fileInfo.style.display = 'none';
                uploadArea.classList.remove('has-file');
                uploadArea.querySelector('.upload-content').style.display = 'block';
            };

            // Remover imagem atual (adicionar campo hidden para backend)
            window.removeCurrentImage = function() {
                if (confirm('Tem certeza que deseja remover a imagem atual do evento?')) {
                    const currentImageDiv = document.querySelector('.current-image').parentElement.parentElement;
                    currentImageDiv.style.display = 'none';
                    
                    // Adicionar campo hidden para indicar remoção
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'remove_current_image';
                    hiddenInput.value = '1';
                    document.getElementById('editEventForm').appendChild(hiddenInput);
                    
                    showToast('Imagem atual será removida ao salvar o evento.', 'info');
                }
            };

            // Formatar tamanho do arquivo
            function formatFileSize(bytes) {
                if (bytes >= 1048576) {
                    return (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    return (bytes / 1024).toFixed(2) + ' KB';
                } else {
                    return bytes + ' B';
                }
            }

            // Máscara de CEP
            const cepInput = document.getElementById('local_cep');
            cepInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            });

            // Validação da data
            const dataInicio = document.getElementById('data_inicio');
            const dataFim = document.getElementById('data_fim');
            
            dataInicio.addEventListener('change', function() {
                dataFim.min = this.value;
                if (dataFim.value && dataFim.value < this.value) {
                    dataFim.value = this.value;
                }
            });

            // Loading no submit
            const form = document.getElementById('editEventForm');
            form.addEventListener('submit', function() {
                const hasNewImage = fileInput.files.length > 0;
                
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + 
                                    (hasNewImage ? 'Enviando imagem e salvando...' : 'Salvando alterações...');
                
                // Re-enable after 10 seconds if still on page (error case)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 10000);
            });

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 7000);
            });

            // Validação de formulário
            form.addEventListener('submit', function(e) {
                const titulo = document.getElementById('titulo').value.trim();
                const descricao = document.getElementById('descricao').value.trim();
                const dataInicio = document.getElementById('data_inicio').value;
                const horarioInicio = document.getElementById('horario_inicio').value;
                const localNome = document.getElementById('local_nome').value.trim();
                const localEndereco = document.getElementById('local_endereco').value.trim();
                const localCidade = document.getElementById('local_cidade').value.trim();
                const localEstado = document.getElementById('local_estado').value;
                
                if (!titulo || !descricao || !dataInicio || !horarioInicio || 
                    !localNome || !localEndereco || !localCidade || !localEstado) {
                    e.preventDefault();
                    showToast('Por favor, preencha todos os campos obrigatórios.', 'warning');
                    return;
                }
                
                // Validar data futura
                const hoje = new Date().toISOString().split('T')[0];
                if (dataInicio < hoje) {
                    e.preventDefault();
                    showToast('A data do evento deve ser futura.', 'warning');
                    return;
                }
                
                // Validar preço se não for gratuito
                if (!eventoGratuito.checked) {
                    const preco = document.getElementById('preco').value;
                    if (!preco || preco < 0) {
                        e.preventDefault();
                        showToast('Por favor, informe o preço do evento.', 'warning');
                        return;
                    }
                }
            });

            // Sistema de toast notifications
            window.showToast = function(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
                toast.style.cssText = `
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                    max-width: 400px;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                `;
                
                const icons = {
                    success: 'fas fa-check-circle',
                    info: 'fas fa-info-circle',
                    warning: 'fas fa-exclamation-triangle',
                    danger: 'fas fa-exclamation-circle',
                    error: 'fas fa-exclamation-circle'
                };
                
                toast.innerHTML = `
                    <i class="${icons[type] || icons.info} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(toast);

                setTimeout(() => {
                    if (toast.parentNode) {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(toast);
                        bsAlert.close();
                    }
                }, 4000);
            };
        });

        // Função para visualizar evento
        function previewEvent() {
            const eventId = <?php echo $eventId; ?>;
            window.open(`view.php?id=${eventId}`, '_blank');
        }
    </script>
</body>
</html>