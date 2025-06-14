<?php
// ==========================================
// TESTE DE UPLOAD DE IMAGENS
// Local: test_upload.php (rodar na raiz do projeto)
// ==========================================

session_start();

// Simular usuário logado para teste
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_type'] = 'organizador';
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Teste';
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    try {
        // Incluir o handler
        if (file_exists(__DIR__ . '/handlers/ImageUploadHandler.php')) {
            require_once __DIR__ . '/handlers/ImageUploadHandler.php';
            
            $imageHandler = new ImageUploadHandler();
            $result = $imageHandler->uploadImage($_FILES['test_image']);
            
            if ($result['success']) {
                $message = "✅ Upload realizado com sucesso!\n";
                $message .= "Arquivo: " . $result['filename'] . "\n";
                $message .= "URL: " . $result['url'] . "\n";
                $messageType = 'success';
            } else {
                $message = "❌ Erro no upload: " . $result['message'];
                $messageType = 'error';
            }
        } else {
            $message = "❌ Arquivo ImageUploadHandler.php não encontrado. Execute setup_upload.php primeiro.";
            $messageType = 'error';
        }
    } catch (Exception $e) {
        $message = "❌ Erro interno: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Verificar estatísticas se handler existe
$stats = null;
if (file_exists(__DIR__ . '/handlers/ImageUploadHandler.php')) {
    try {
        require_once __DIR__ . '/handlers/ImageUploadHandler.php';
        $imageHandler = new ImageUploadHandler();
        $stats = $imageHandler->getUploadStats();
    } catch (Exception $e) {
        // Ignorar erros nas estatísticas
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Upload - Conecta Eventos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .test-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
        }
        .stats-card {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem 0;
        }
        .message {
            white-space: pre-line;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-container">
            <h1><i class="fas fa-upload me-2"></i>Teste de Upload de Imagens</h1>
            <p class="text-muted">Use esta página para testar se o sistema de upload está funcionando corretamente.</p>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de Teste -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-test-tube me-2"></i>Teste de Upload</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-area">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5>Selecione uma imagem para testar</h5>
                            <p class="text-muted">Máximo 5MB • JPG, PNG, GIF, WebP</p>
                            <input type="file" class="form-control" name="test_image" accept="image/*" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Testar Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estatísticas -->
            <?php if ($stats): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar me-2"></i>Estatísticas do Sistema</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stats-card">
                                    <strong>Total de Arquivos:</strong> <?php echo $stats['total_files']; ?><br>
                                    <strong>Tamanho Total:</strong> <?php echo $stats['total_size_formatted']; ?><br>
                                    <strong>Diretório:</strong> <code><?php echo htmlspecialchars($stats['upload_dir']); ?></code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stats-card">
                                    <strong>Tamanho Máximo:</strong> <?php echo $stats['max_file_size_formatted']; ?><br>
                                    <strong>Tipos Permitidos:</strong> <?php echo implode(', ', $stats['allowed_types']); ?><br>
                                    <strong>Status:</strong> <span class="text-success">✓ Operacional</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Verificações do Sistema -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-cogs me-2"></i>Verificações do Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Arquivos</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <?php if (file_exists(__DIR__ . '/handlers/ImageUploadHandler.php')): ?>
                                        <i class="fas fa-check text-success me-2"></i>ImageUploadHandler.php
                                    <?php else: ?>
                                        <i class="fas fa-times text-danger me-2"></i>ImageUploadHandler.php
                                    <?php endif; ?>
                                </li>
                                <li>
                                    <?php if (file_exists(__DIR__ . '/uploads/eventos')): ?>
                                        <i class="fas fa-check text-success me-2"></i>Diretório uploads/eventos/
                                    <?php else: ?>
                                        <i class="fas fa-times text-danger me-2"></i>Diretório uploads/eventos/
                                    <?php endif; ?>
                                </li>
                                <li>
                                    <?php if (file_exists(__DIR__ . '/uploads/eventos/.htaccess')): ?>
                                        <i class="fas fa-check text-success me-2"></i>.htaccess de proteção
                                    <?php else: ?>
                                        <i class="fas fa-times text-danger me-2"></i>.htaccess de proteção
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Extensões PHP</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <?php if (extension_loaded('gd')): ?>
                                        <i class="fas fa-check text-success me-2"></i>GD (processamento de imagem)
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>GD (limitado)
                                    <?php endif; ?>
                                </li>
                                <li>
                                    <?php if (extension_loaded('fileinfo')): ?>
                                        <i class="fas fa-check text-success me-2"></i>FileInfo (detecção de tipo)
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>FileInfo (limitado)
                                    <?php endif; ?>
                                </li>
                                <li>
                                    <?php if (ini_get('file_uploads')): ?>
                                        <i class="fas fa-check text-success me-2"></i>Upload de arquivos habilitado
                                    <?php else: ?>
                                        <i class="fas fa-times text-danger me-2"></i>Upload de arquivos desabilitado
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Configurações PHP</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>upload_max_filesize</strong></td>
                                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>post_max_size</strong></td>
                                        <td><?php echo ini_get('post_max_size'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>max_execution_time</strong></td>
                                        <td><?php echo ini_get('max_execution_time'); ?>s</td>
                                    </tr>
                                    <tr>
                                        <td><strong>memory_limit</strong></td>
                                        <td><?php echo ini_get('memory_limit'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Imagens Existentes -->
            <?php if (file_exists(__DIR__ . '/uploads/eventos')): ?>
                <?php
                $uploadedImages = glob(__DIR__ . '/uploads/eventos/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-images me-2"></i>Imagens Enviadas (<?php echo count($uploadedImages); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($uploadedImages)): ?>
                            <p class="text-muted text-center">Nenhuma imagem enviada ainda.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach (array_slice($uploadedImages, -12) as $imagePath): ?>
                                    <?php
                                    $fileName = basename($imagePath);
                                    $fileSize = filesize($imagePath);
                                    $imageUrl = 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $fileName;
                                    ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card">
                                            <img src="<?php echo $imageUrl; ?>" 
                                                 class="card-img-top" 
                                                 style="height: 150px; object-fit: cover;"
                                                 alt="<?php echo htmlspecialchars($fileName); ?>"
                                                 loading="lazy">
                                            <div class="card-body p-2">
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($fileName, 0, 20) . '...'); ?><br>
                                                    <?php echo number_format($fileSize / 1024, 1); ?> KB
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($uploadedImages) > 12): ?>
                                <p class="text-muted text-center">
                                    Mostrando as 12 imagens mais recentes de <?php echo count($uploadedImages); ?> total.
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Ações de Manutenção -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-tools me-2"></i>Manutenção</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="setup_upload.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-cog me-2"></i>Executar Setup
                            </a>
                            <small class="text-muted">Recria estrutura de pastas e arquivos</small>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-warning w-100 mb-2" onclick="cleanOldFiles()">
                                <i class="fas fa-broom me-2"></i>Limpar Arquivos Antigos
                            </button>
                            <small class="text-muted">Remove arquivos com mais de 30 dias</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Links de Navegação -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-link me-2"></i>Links Úteis</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="views/events/create.php" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-plus me-2"></i>Criar Evento
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="views/events/list.php" class="btn btn-info w-100 mb-2">
                                <i class="fas fa-list me-2"></i>Meus Eventos
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="index.php" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-home me-2"></i>Página Inicial
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cleanOldFiles() {
            if (confirm('Tem certeza que deseja limpar arquivos antigos? Esta ação não pode ser desfeita.')) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clean_old_files'
                })
                .then(response => response.text())
                .then(data => {
                    alert('Limpeza executada! Recarregue a página para ver os resultados.');
                })
                .catch(error => {
                    alert('Erro na limpeza: ' + error);
                });
            }
        }

        // Preview da imagem selecionada
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('imagePreview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'imagePreview';
                        preview.className = 'img-thumbnail mt-3';
                        preview.style.maxWidth = '200px';
                        preview.style.maxHeight = '200px';
                        e.target.parentElement.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>

<?php
// Processar ação de limpeza via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clean_old_files') {
    if (file_exists(__DIR__ . '/handlers/ImageUploadHandler.php')) {
        require_once __DIR__ . '/handlers/ImageUploadHandler.php';
        $imageHandler = new ImageUploadHandler();
        $deleted = $imageHandler->cleanOldUploads(30);
        echo "Arquivos removidos: $deleted";
    } else {
        echo "Handler não encontrado";
    }
    exit;
}
?>