<?php
// ==========================================
// HANDLER DE IMAGENS DE PERFIL - CORRIGIDO
// Local: handlers/ProfileImageHandler.php
// ==========================================

class ProfileImageHandler {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    private $debug = true;
    
    public function __construct() {
        // Diretório de upload - ajustado para Railway
        $this->uploadDir = __DIR__ . '/../uploads/profiles/';
        
        // Tipos permitidos
        $this->allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Tamanho máximo: 2MB para fotos de perfil
        $this->maxFileSize = 2 * 1024 * 1024;
        
        // Criar diretório se não existir
        $this->ensureUploadDirectory();
    }
    
    /**
     * Garantir que o diretório de upload existe
     */
    private function ensureUploadDirectory() {
        if (!file_exists($this->uploadDir)) {
            $created = mkdir($this->uploadDir, 0755, true);
            if ($created) {
                $this->log("Diretório de upload criado: " . $this->uploadDir);
                
                // Criar .htaccess para segurança
                $this->createHtaccess();
                
                // Criar index.php para proteção
                $this->createIndexProtection();
            } else {
                $this->log("ERRO: Falha ao criar diretório de upload");
            }
        }
    }
    
    /**
     * Criar arquivo .htaccess para segurança
     */
    private function createHtaccess() {
        $htaccessPath = $this->uploadDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Impedir execução de scripts\n";
            $htaccessContent .= "php_flag engine off\n";
            $htaccessContent .= "AddType text/plain .php .php3 .phtml .pht\n";
            $htaccessContent .= "\n# Apenas imagens\n";
            $htaccessContent .= "<Files ~ \"\\.(php|php3|phtml|pht|jsp|asp|aspx|cgi|pl)$\">\n";
            $htaccessContent .= "    Order allow,deny\n";
            $htaccessContent .= "    Deny from all\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
            $this->log("Arquivo .htaccess criado para segurança");
        }
    }
    
    /**
     * Criar index.php para proteção do diretório
     */
    private function createIndexProtection() {
        $indexPath = $this->uploadDir . 'index.php';
        if (!file_exists($indexPath)) {
            $indexContent = "<?php\n";
            $indexContent .= "// Proteção do diretório\n";
            $indexContent .= "header('HTTP/1.0 403 Forbidden');\n";
            $indexContent .= "exit('Acesso negado.');\n";
            $indexContent .= "?>";
            
            file_put_contents($indexPath, $indexContent);
            $this->log("Arquivo index.php de proteção criado");
        }
    }
    
    /**
     * Upload de imagem de perfil
     */
    public function uploadProfileImage($fileArray, $userId) {
        try {
            // Verificar se arquivo foi enviado
            if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
                return [
                    'success' => false,
                    'message' => $this->getUploadErrorMessage($fileArray['error'] ?? UPLOAD_ERR_NO_FILE)
                ];
            }
            
            $this->log("Iniciando upload de foto de perfil para usuário: $userId");
            
            // Informações do arquivo
            $originalName = $fileArray['name'];
            $tmpName = $fileArray['tmp_name'];
            $fileSize = $fileArray['size'];
            
            $this->log("Arquivo: $originalName, Tamanho: $fileSize bytes");
            
            // Validar arquivo
            $validation = $this->validateFile($originalName, $fileSize, $tmpName);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Buscar imagem atual do usuário
            $currentImage = $this->getCurrentProfileImage($userId);
            
            // Gerar nome único para o arquivo
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $newFileName = $this->generateUniqueFileName($userId, $extension);
            $targetPath = $this->uploadDir . $newFileName;
            
            $this->log("Novo nome do arquivo: $newFileName");
            
            // Mover arquivo para diretório de destino
            if (move_uploaded_file($tmpName, $targetPath)) {
                $this->log("Upload realizado com sucesso: $targetPath");
                
                // Redimensionar e otimizar imagem
                $this->processProfileImage($targetPath);
                
                // Atualizar banco de dados
                $dbResult = $this->updateProfileImageInDatabase($userId, $newFileName);
                
                if ($dbResult) {
                    // CORREÇÃO: Atualizar a sessão com a nova foto
                    $this->updateSessionPhoto($newFileName);
                    
                    // Remover imagem antiga se existir
                    if ($currentImage && $currentImage !== $newFileName) {
                        $this->deleteImage($currentImage);
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Foto de perfil atualizada com sucesso!',
                        'image_info' => [
                            'filename' => $newFileName,
                            'url' => $this->getImageUrl($newFileName)
                        ]
                    ];
                } else {
                    // Se falhou no banco, remover arquivo enviado
                    $this->deleteImage($newFileName);
                    return [
                        'success' => false,
                        'message' => 'Erro ao salvar imagem no banco de dados.'
                    ];
                }
            } else {
                $this->log("ERRO: Falha ao mover arquivo para $targetPath");
                return [
                    'success' => false,
                    'message' => 'Erro ao salvar a imagem no servidor.'
                ];
            }
            
        } catch (Exception $e) {
            $this->log("Exception no upload: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno no upload: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Remover foto de perfil
     */
    public function removeProfileImage($userId) {
        try {
            // Buscar imagem atual
            $currentImage = $this->getCurrentProfileImage($userId);
            
            if (!$currentImage) {
                return [
                    'success' => false,
                    'message' => 'Usuário não possui foto de perfil para remover.'
                ];
            }
            
            // Remover do banco de dados
            $dbResult = $this->updateProfileImageInDatabase($userId, null);
            
            if ($dbResult) {
                // CORREÇÃO: Atualizar a sessão removendo a foto
                $this->updateSessionPhoto(null);
                
                // Remover arquivo físico
                $this->deleteImage($currentImage);
                
                $this->log("Foto de perfil removida - User ID: $userId, Image: $currentImage");
                
                return [
                    'success' => true,
                    'message' => 'Foto de perfil removida com sucesso!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erro ao remover foto do banco de dados.'
                ];
            }
            
        } catch (Exception $e) {
            $this->log("Erro ao remover foto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * NOVO: Atualizar foto de perfil na sessão
     */
    private function updateSessionPhoto($photoName) {
        // Garantir que a sessão está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $_SESSION['user_photo'] = $photoName;
            $this->log("Sessão atualizada com nova foto: " . ($photoName ?: 'NULL'));
            return true;
        }
        
        $this->log("ERRO: Usuário não está logado, não foi possível atualizar sessão");
        return false;
    }
    
    /**
     * Buscar imagem atual do perfil
     */
    private function getCurrentProfileImage($userId) {
        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();
            
            if (!$conn) {
                return null;
            }
            
            $stmt = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result ? $result['foto_perfil'] : null;
            
        } catch (Exception $e) {
            $this->log("Erro ao buscar imagem atual: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualizar imagem no banco de dados
     */
    private function updateProfileImageInDatabase($userId, $fileName) {
        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();
            
            if (!$conn) {
                return false;
            }
            
            // CORREÇÃO: Verificar se a tabela tem a coluna foto_perfil
            $stmt = $conn->prepare("SHOW COLUMNS FROM usuarios LIKE 'foto_perfil'");
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                // Adicionar coluna se não existir
                $this->log("Adicionando coluna foto_perfil à tabela usuarios");
                $alterSql = "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER senha";
                $conn->exec($alterSql);
            }
            
            $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?");
            $result = $stmt->execute([$fileName, $userId]);
            
            $this->log("Banco atualizado - User: $userId, Image: " . ($fileName ?: 'NULL') . ", Result: " . ($result ? 'SUCCESS' : 'FAIL'));
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("Erro ao atualizar banco: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validar arquivo enviado
     */
    private function validateFile($fileName, $fileSize, $tmpName) {
        // Verificar extensão
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            return [
                'valid' => false,
                'message' => 'Tipo de arquivo não permitido. Use: ' . implode(', ', $this->allowedTypes)
            ];
        }
        
        // Verificar tamanho
        if ($fileSize > $this->maxFileSize) {
            $maxSizeMB = $this->maxFileSize / (1024 * 1024);
            return [
                'valid' => false,
                'message' => "Arquivo muito grande. Tamanho máximo: {$maxSizeMB}MB"
            ];
        }
        
        // Verificar se é realmente uma imagem
        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'message' => 'O arquivo enviado não é uma imagem válida.'
            ];
        }
        
        // Verificar MIME type
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageInfo['mime'], $allowedMimes)) {
            return [
                'valid' => false,
                'message' => 'Tipo MIME não permitido.'
            ];
        }
        
        return ['valid' => true, 'message' => 'Arquivo válido'];
    }
    
    /**
     * Gerar nome único para arquivo
     */
    private function generateUniqueFileName($userId, $extension) {
        $timestamp = time();
        return "profile_{$userId}_{$timestamp}.{$extension}";
    }
    
    /**
     * Processar imagem de perfil (redimensionar e otimizar)
     */
    private function processProfileImage($imagePath) {
        try {
            // Verificar se extensão GD está disponível
            if (!extension_loaded('gd')) {
                $this->log("Extensão GD não disponível - pulando processamento");
                return;
            }
            
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                $this->log("Não foi possível obter informações da imagem");
                return;
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];
            
            // Dimensões para foto de perfil
            $targetSize = 300; // 300x300 pixels
            
            // Só processar se a imagem for maior que o target
            if ($width > $targetSize || $height > $targetSize) {
                // Criar imagem baseada no tipo
                $sourceImage = null;
                switch ($type) {
                    case IMAGETYPE_JPEG:
                        $sourceImage = @imagecreatefromjpeg($imagePath);
                        break;
                    case IMAGETYPE_PNG:
                        $sourceImage = @imagecreatefrompng($imagePath);
                        break;
                    case IMAGETYPE_GIF:
                        $sourceImage = @imagecreatefromgif($imagePath);
                        break;
                    case IMAGETYPE_WEBP:
                        if (function_exists('imagecreatefromwebp')) {
                            $sourceImage = @imagecreatefromwebp($imagePath);
                        }
                        break;
                }
                
                if (!$sourceImage) {
                    $this->log("Não foi possível criar imagem fonte");
                    return;
                }
                
                // Calcular dimensões mantendo proporção (crop para quadrado)
                $size = min($width, $height);
                $x = ($width - $size) / 2;
                $y = ($height - $size) / 2;
                
                // Criar nova imagem quadrada
                $destImage = imagecreatetruecolor($targetSize, $targetSize);
                
                // Preservar transparência para PNG
                if ($type == IMAGETYPE_PNG) {
                    imagealphablending($destImage, false);
                    imagesavealpha($destImage, true);
                    $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
                    imagefilledrectangle($destImage, 0, 0, $targetSize, $targetSize, $transparent);
                }
                
                // Redimensionar e fazer crop
                if (imagecopyresampled($destImage, $sourceImage, 0, 0, $x, $y, $targetSize, $targetSize, $size, $size)) {
                    // Salvar imagem processada
                    $saved = false;
                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            $saved = imagejpeg($destImage, $imagePath, 90);
                            break;
                        case IMAGETYPE_PNG:
                            $saved = imagepng($destImage, $imagePath, 8);
                            break;
                        case IMAGETYPE_GIF:
                            $saved = imagegif($destImage, $imagePath);
                            break;
                        case IMAGETYPE_WEBP:
                            if (function_exists('imagewebp')) {
                                $saved = imagewebp($destImage, $imagePath, 90);
                            }
                            break;
                    }
                    
                    if ($saved) {
                        $this->log("Imagem processada: {$width}x{$height} -> {$targetSize}x{$targetSize}");
                    }
                }
                
                // Limpar memória
                imagedestroy($sourceImage);
                imagedestroy($destImage);
            }
            
        } catch (Exception $e) {
            $this->log("Erro ao processar imagem: " . $e->getMessage());
            // Não falhar o upload por causa do processamento
        }
    }
    
    /**
     * Obter URL da imagem
     */
    public function getImageUrl($fileName) {
        if (!$fileName) return null;
        
        $baseUrl = 'https://conecta-eventos-production.up.railway.app';
        return $baseUrl . '/uploads/profiles/' . $fileName;
    }
    
    /**
     * Deletar imagem
     */
    public function deleteImage($fileName) {
        if (!$fileName) return true;
        
        $filePath = $this->uploadDir . $fileName;
        if (file_exists($filePath)) {
            $deleted = unlink($filePath);
            $this->log($deleted ? "Imagem deletada: $fileName" : "Falha ao deletar: $fileName");
            return $deleted;
        }
        
        $this->log("Arquivo não existe para deletar: $fileName");
        return true; // Arquivo não existe, considerar como "deletado"
    }
    
    /**
     * Verificar se imagem existe
     */
    public function imageExists($fileName) {
        if (!$fileName) return false;
        return file_exists($this->uploadDir . $fileName);
    }
    
    /**
     * Obter informações da imagem
     */
    public function getImageInfo($fileName) {
        if (!$fileName || !$this->imageExists($fileName)) {
            return null;
        }
        
        $filePath = $this->uploadDir . $fileName;
        $imageInfo = @getimagesize($filePath);
        $fileSize = filesize($filePath);
        
        if (!$imageInfo) {
            return [
                'filename' => $fileName,
                'path' => $filePath,
                'url' => $this->getImageUrl($fileName),
                'size' => $fileSize,
                'size_formatted' => $this->formatFileSize($fileSize),
                'error' => 'Não foi possível obter informações da imagem'
            ];
        }
        
        return [
            'filename' => $fileName,
            'path' => $filePath,
            'url' => $this->getImageUrl($fileName),
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageInfo['mime'],
            'size' => $fileSize,
            'size_formatted' => $this->formatFileSize($fileSize)
        ];
    }
    
    /**
     * Formatar tamanho do arquivo
     */
    private function formatFileSize($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    /**
     * Obter mensagem de erro de upload
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                return 'Upload realizado com sucesso';
            case UPLOAD_ERR_INI_SIZE:
                return 'Arquivo excede o tamanho máximo permitido pelo servidor';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Arquivo excede o tamanho máximo permitido pelo formulário';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload foi parcialmente realizado';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo foi enviado';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Diretório temporário não encontrado';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao escrever arquivo no disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload interrompido por extensão PHP';
            default:
                return 'Erro desconhecido no upload';
        }
    }
    
    /**
     * Log de debug
     */
    private function log($message) {
        if ($this->debug) {
            error_log("[ProfileImageHandler] " . $message);
        }
    }
    
    /**
     * Limpar uploads antigos (manutenção)
     */
    public function cleanOldUploads($daysOld = 30) {
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $files = glob($this->uploadDir . '*');
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                // Não deletar arquivos de proteção
                $fileName = basename($file);
                if (!in_array($fileName, ['.htaccess', 'index.php'])) {
                    // Verificar se a imagem ainda está sendo usada
                    if (!$this->isImageInUse($fileName)) {
                        if (unlink($file)) {
                            $deletedCount++;
                        }
                    }
                }
            }
        }
        
        $this->log("Limpeza realizada: $deletedCount arquivos antigos removidos");
        return $deletedCount;
    }
    
    /**
     * Verificar se a imagem está sendo usada
     */
    private function isImageInUse($fileName) {
        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();
            
            if (!$conn) {
                return true; // Em caso de dúvida, não deletar
            }
            
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE foto_perfil = ?");
            $stmt->execute([$fileName]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            $this->log("Erro ao verificar uso da imagem: " . $e->getMessage());
            return true; // Em caso de erro, não deletar
        }
    }
    
    /**
     * Obter estatísticas do diretório de upload
     */
    public function getUploadStats() {
        $files = glob($this->uploadDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        $totalFiles = count($files);
        $totalSize = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatFileSize($totalSize),
            'upload_dir' => $this->uploadDir,
            'max_file_size' => $this->maxFileSize,
            'max_file_size_formatted' => $this->formatFileSize($this->maxFileSize),
            'allowed_types' => $this->allowedTypes
        ];
    }
}
?>