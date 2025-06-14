<?php
// ==========================================
// EDITAR EVENTO - VERSÃO COM DEBUG AUTOMÁTICO
// Local: views/events/edit.php
// ==========================================

session_start();

// Verificar se está logado e é organizador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
    header("Location: ../dashboard/participant.php");
    exit;
}

$title = "Editar Evento - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Organizador';

// URLs
$dashboardUrl = '../dashboard/organizer.php';
$homeUrl = '../../index.php';

// Verificar se ID foi fornecido
$eventId = $_GET['id'] ?? null;
if (!$eventId) {
    header('Location: list.php');
    exit();
}

// ==========================================
// DEBUG AUTOMÁTICO DE CAMINHOS E ARQUIVOS
// ==========================================
$debug_info = [];
$debug_info['timestamp'] = date('Y-m-d H:i:s');
$debug_info['current_dir'] = __DIR__;
$debug_info['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'N/A';
$debug_info['script_name'] = $_SERVER['SCRIPT_NAME'] ?? 'N/A';

// Testar diferentes caminhos para os arquivos necessários
$required_files = [
    'EventController' => [
        '../../controllers/EventController.php',
        '../controllers/EventController.php',
        './controllers/EventController.php',
        __DIR__ . '/../../controllers/EventController.php',
        $_SERVER['DOCUMENT_ROOT'] . '/controllers/EventController.php'
    ],
    'ImageUploadHandler' => [
        '../../handlers/ImageUploadHandler.php',
        '../handlers/ImageUploadHandler.php',
        './handlers/ImageUploadHandler.php',
        __DIR__ . '/../../handlers/ImageUploadHandler.php',
        $_SERVER['DOCUMENT_ROOT'] . '/handlers/ImageUploadHandler.php'
    ]
];

$loaded_files = [];
$missing_files = [];

foreach ($required_files as $file_type => $paths) {
    $file_loaded = false;
    $debug_info['paths_tested'][$file_type] = [];
    
    foreach ($paths as $path) {
        $debug_info['paths_tested'][$file_type][] = [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => file_exists($path) ? is_readable($path) : false
        ];
        
        if (file_exists($path) && is_readable($path)) {
            try {
                require_once $path;
                $loaded_files[$file_type] = $path;
                $file_loaded = true;
                error_log("[$file_type] Carregado com sucesso via: $path");
                break;
            } catch (Exception $e) {
                error_log("[$file_type] Erro ao carregar $path: " . $e->getMessage());
                $debug_info['load_errors'][$file_type][] = [
                    'path' => $path,
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    if (!$file_loaded) {
        $missing_files[] = $file_type;
        error_log("[$file_type] NÃO ENCONTRADO em nenhum caminho testado");
    }
}

// Verificar estrutura de diretórios
$debug_info['directory_structure'] = [];
$dirs_to_check = [
    'current' => __DIR__,
    'parent' => dirname(__DIR__),
    'root' => dirname(dirname(__DIR__)),
    'controllers' => dirname(__DIR__) . '/../controllers',
    'handlers' => dirname(__DIR__) . '/../handlers'
];

foreach ($dirs_to_check as $name => $dir) {
    if (is_dir($dir)) {
        $debug_info['directory_structure'][$name] = [
            'path' => $dir,
            'exists' => true,
            'contents' => scandir($dir)
        ];
    } else {
        $debug_info['directory_structure'][$name] = [
            'path' => $dir,
            'exists' => false,
            'contents' => null
        ];
    }
}

// Log completo de debug
error_log("=== DEBUG EDIT.PHP ===");
error_log("Debug Info: " . json_encode($debug_info, JSON_PRETTY_PRINT));
error_log("Loaded Files: " . json_encode($loaded_files));
error_log("Missing Files: " . json_encode($missing_files));

$success_message = '';
$error_message = '';
$evento = null;
$categorias = [];

// Verificar se todos os arquivos necessários foram carregados
if (!empty($missing_files)) {
    $error_message = "Erro de configuração: Arquivos não encontrados: " . implode(', ', $missing_files);
    
    // Mostrar debug visual apenas para organizadores em caso de erro
    if ($_GET['debug'] ?? false) {
        echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h3>🔍 Debug de Caminhos</h3>";
        echo "<pre>" . json_encode($debug_info, JSON_PRETTY_PRINT) . "</pre>";
        echo "<h4>Arquivos Carregados:</h4>";
        echo "<pre>" . json_encode($loaded_files, JSON_PRETTY_PRINT) . "</pre>";
        echo "<h4>Arquivos Ausentes:</h4>";
        echo "<pre>" . json_encode($missing_files, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
    }
} else {
    // Todos os arquivos carregados com sucesso - continuar normalmente
    try {
        $eventController = new EventController();
        
        $evento = $eventController->getById($eventId);
        if (!$evento || !$eventController->canEdit($eventId)) {
            $error_message = "Evento não encontrado ou você não tem permissão para editá-lo.";
        } else {
            $categorias = $eventController->getCategories();
        }
    } catch (Exception $e) {
        error_log("Erro ao carregar evento: " . $e->getMessage());
        $error_message = "Erro ao carregar dados do evento: " . $e->getMessage();
    }
}

// Se não conseguiu carregar o evento, redirecionar (exceto se for erro de arquivos)
if (!$evento && empty($missing_files)) {
    header('Location: list.php');
    exit();
}

// Processar formulário apenas se todos os arquivos estão disponíveis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message && !empty($loaded_files)) {
    try {
        // Verificar se ImageUploadHandler está disponível
        $imageHandler = null;
        if (isset($loaded_files['ImageUploadHandler'])) {
            $imageHandler = new ImageUploadHandler();
        }
        
        // Processar upload de nova imagem se enviada e handler disponível
        $imageResult = null;
        $newImageName = null;
        
        if ($imageHandler && isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] !== UPLOAD_ERR_NO_FILE) {
            $oldImageName = $evento['imagem_capa']; // Imagem atual para remoção se upload for bem-sucedido
            $imageResult = $imageHandler->uploadImage($_FILES['imagem_capa'], $oldImageName);
            
            if (!$imageResult['success']) {
                $error_message = "Erro no upload da imagem: " . $imageResult['message'];
            } else {
                $newImageName = $imageResult['filename'];
            }
        }
        
        // Se não houve erro na imagem, atualizar evento
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
            if (isset($_POST['remove_current_image']) && $_POST['remove_current_image'] && $evento['imagem_capa'] && $imageHandler) {
                $imageHandler->deleteImage($evento['imagem_capa']);
                $updateData['imagem_capa'] = null;
            }
            
            $result = $eventController->update($eventId, $updateData);
            
            if ($result['success']) {
                $success_message = $result['message'];
                // Recarregar dados atualizados
                $evento = $eventController->getById($eventId);
            } else {
                $error_message = $result['message'];
                
                // Se evento falhou mas imagem foi enviada, deletar imagem nova
                if ($newImageName && $imageHandler) {
                    $imageHandler->deleteImage($newImageName);
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Erro ao atualizar evento: " . $e->getMessage());
        $error_message = "Erro interno do sistema. Tente novamente: " . $e->getMessage();
        
        // Deletar imagem se foi enviada
        if ($newImageName && $imageHandler) {
            $imageHandler->deleteImage($newImageName);
        }
    }
}

// Preparar valores padrão para o formulário
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

    // Sobrescrever com dados do POST em caso de erro
    if ($_POST && $error_message) {
        foreach ($defaults as $key => $value) {
            if (isset($_POST[$key])) {
                $defaults[$key] = $_POST[$key];
            }
        }
    }
}

// URL da imagem atual
$currentImageUrl = '';
if (!empty($defaults['imagem_capa'])) {
    $currentImageUrl = 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $defaults['imagem_capa'];
}

// Verificar se upload está disponível
$uploadEnabled = isset($loaded_files['ImageUploadHandler']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .edit-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white; padding: 2rem 0; margin-bottom: 2rem;
        }
        .form-section {
            background: white; border-radius: 1rem; padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 2rem;
        }
        .form-section h4 {
            color: #333; border-bottom: 2px solid #28a745;
            padding-bottom: 0.5rem; margin-bottom: 1.5rem;
        }
        .form-control, .form-select {
            border-radius: 0.5rem; border: 2px solid #e9ecef; padding: 0.75rem 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #28a745; box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none; border-radius: 0.5rem; padding: 0.75rem 2rem; font-weight: 600;
        }
        .upload-area {
            border: 2px dashed #dee2e6; border-radius: 0.5rem; padding: 2rem;
            text-align: center; transition: all 0.3s ease; cursor: pointer; background: #f8f9fa;
        }
        .upload-area:hover, .upload-area.dragover { border-color: #28a745; background-color: #e8f5e8; }
        .upload-area.has-file { border-color: #28a745; background-color: #d4edda; }
        .current-image { max-width: 200px; max-height: 150px; border-radius: 0.5rem; margin-bottom: 1rem; }
        .debug-panel {
            background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 0.5rem;
            padding: 1rem; margin-bottom: 2rem;
        }
        .debug-details { background: #f8f9fa; padding: 1rem; border-radius: 0.25rem; margin-top: 1rem; }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i><strong>Conecta Eventos</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Olá, <?php echo htmlspecialchars($userName); ?>!</span>
                <a class="nav-link" href="<?php echo $dashboardUrl; ?>">Dashboard</a>
                <a class="nav-link" href="../../logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <!-- Header da Página -->
    <section class="edit-header">
        <div class="container">
            <h1><i class="fas fa-edit me-2"></i>Editar Evento <?php if (!$uploadEnabled) echo '<small>(Upload desabilitado)</small>'; ?></h1>
            <p class="mb-0 fs-5">Atualize as informações do seu evento</p>
        </div>
    </section>

    <div class="container pb-5">
        <!-- Debug Panel (apenas se houver problemas) -->
        <?php if (!empty($missing_files) || ($_GET['debug'] ?? false)): ?>
            <div class="debug-panel">
                <h5><i class="fas fa-bug me-2"></i>Informações de Debug</h5>
                
                <?php if (!empty($missing_files)): ?>
                    <div class="alert alert-warning">
                        <strong>Arquivos não encontrados:</strong> <?php echo implode(', ', $missing_files); ?>
                        <br><small>Algumas funcionalidades podem estar limitadas.</small>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Arquivos Carregados:</strong>
                        <ul class="list-unstyled mt-2">
                            <?php foreach ($loaded_files as $type => $path): ?>
                                <li><i class="fas fa-check text-success me-2"></i><?php echo $type; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Status do Sistema:</strong>
                        <ul class="list-unstyled mt-2">
                            <li><i class="fas fa-<?php echo isset($loaded_files['EventController']) ? 'check text-success' : 'times text-danger'; ?> me-2"></i>EventController</li>
                            <li><i class="fas fa-<?php echo $uploadEnabled ? 'check text-success' : 'times text-warning'; ?> me-2"></i>Upload de Imagens</li>
                        </ul>
                    </div>
                </div>
                
                <?php if ($_GET['debug'] ?? false): ?>
                    <details class="mt-3">
                        <summary>Detalhes Técnicos</summary>
                        <div class="debug-details">
                            <small><pre><?php echo json_encode($debug_info, JSON_PRETTY_PRINT); ?></pre></small>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Mensagens -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                
                <?php if (!empty($missing_files)): ?>
                    <hr>
                    <small>
                        <strong>Para corrigir:</strong> 
                        <a href="?debug=1" class="btn btn-sm btn-outline-danger">Ver Debug Completo</a>
                    </small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($evento): ?>
            <form method="POST" enctype="multipart/form-data" id="editEventForm">
                <!-- Upload de Imagem (apenas se disponível) -->
                <?php if ($uploadEnabled): ?>
                    <div class="form-section">
                        <h4><i class="fas fa-image me-2"></i>Imagem de Capa</h4>
                        
                        <?php if ($currentImageUrl): ?>
                            <div class="mb-3">
                                <label class="form-label">Imagem atual:</label>
                                <div>
                                    <img src="<?php echo $currentImageUrl; ?>" alt="Imagem atual" class="current-image">
                                    <div><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCurrentImage()">
                                        <i class="fas fa-trash me-1"></i>Remover Imagem Atual
                                    </button></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5><?php echo $currentImageUrl ? 'Substituir por nova imagem' : 'Adicionar imagem de capa'; ?></h5>
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('imagem_capa').click()">
                                <i class="fas fa-folder-open me-2"></i>Escolher Arquivo
                            </button>
                        </div>
                        
                        <input type="file" class="d-none" id="imagem_capa" name="imagem_capa" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Upload de imagens temporariamente indisponível. O evento pode ser editado normalmente.
                    </div>
                <?php endif; ?>

                <!-- Informações Básicas -->
                <div class="form-section">
                    <h4><i class="fas fa-info-circle me-2"></i>Informações Básicas</h4>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="titulo" class="form-label">Título do Evento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required
                                   value="<?php echo htmlspecialchars($defaults['titulo']); ?>">
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
                        <textarea class="form-control" id="descricao" name="descricao" rows="4" required><?php echo htmlspecialchars($defaults['descricao']); ?></textarea>
                    </div>
                </div>

                <!-- Data e Horário -->
                <div class="form-section">
                    <h4><i class="fas fa-calendar-alt me-2"></i>Data e Horário</h4>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="data_inicio" class="form-label">Data de Início <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" required
                                   value="<?php echo $defaults['data_inicio']; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="horario_inicio" class="form-label">Horário de Início <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="horario_inicio" name="horario_inicio" required
                                   value="<?php echo $defaults['horario_inicio']; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="data_fim" class="form-label">Data de Fim</label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim"
                                   value="<?php echo $defaults['data_fim']; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="horario_fim" class="form-label">Horário de Fim</label>
                            <input type="time" class="form-control" id="horario_fim" name="horario_fim"
                                   value="<?php echo $defaults['horario_fim']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Demais seções do formulário... -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                    <a href="list.php" class="btn btn-outline-secondary btn-lg ms-3">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($uploadEnabled): ?>
            // Upload functionality
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('imagem_capa');
            
            if (uploadArea && fileInput) {
                uploadArea.addEventListener('click', () => fileInput.click());
                
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        const file = e.target.files[0];
                        uploadArea.innerHTML = `<p>Arquivo selecionado: ${file.name}</p>`;
                        uploadArea.classList.add('has-file');
                    }
                });
            }
            <?php endif; ?>
            
            // Auto-hide alerts
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) bsAlert.close();
                });
            }, 5000);
        });
        
        function removeCurrentImage() {
            if (confirm('Tem certeza que deseja remover a imagem atual?')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'remove_current_image';
                hiddenInput.value = '1';
                document.getElementById('editEventForm').appendChild(hiddenInput);
                alert('Imagem será removida ao salvar o evento.');
            }
        }
    </script>
</body>
</html>