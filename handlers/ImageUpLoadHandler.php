<?php
// ==========================================
// HANDLER DE UPLOAD DE IMAGENS
// Local: handlers/ImageUploadHandler.php
// ==========================================

class ImageUploadHandler {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    private $debug = true;
    
    public function __construct() {
        // Diretório de upload
        $this->uploadDir = __DIR__ . '/../uploads/eventos/';
        
        // Tipos permitidos
        $this->allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Tamanho máximo: 5MB
        $this->maxFileSize = 5 * 1024 * 1024;
        
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
            } else {
                $this->log("ERRO: Falha ao criar diretório de upload");
            }
        }
    }
    
    /**
     * Criar arquivo .htaccess para segurança
     */
    private function createHtaccess() {
        $htaccessContent = "# Impedir execução de scripts\n";
        $htaccessContent .= "php_flag engine off\n";
        $htaccessContent .= "AddType text/plain .php .php3 .phtml .pht\n";
        $htaccessContent .= "\n# Apenas imagens\n";
        $htaccessContent .= "<Files ~ \"\\.(php|php3|phtml|pht|jsp|asp|aspx|cgi|pl)$\">\n";
        $htaccessContent .= "    Order allow,deny\n";
        $htaccessContent .= "    Deny from all\n";
        $htaccessContent .= "</Files>\n";
        
        file_put_contents($this->uploadDir . '.htaccess', $htaccessContent);
        $this->log("Arquivo .htaccess criado para segurança");
    }
    
    /**
     * Processar upload de imagem
     */
    public function uploadImage($fileArray, $oldImageName = null) {
        try {
            // Verificar se arquivo foi enviado
            if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
                return [
                    'success' => false,
                    'message' => 'Nenhum arquivo foi enviado ou ocorreu um erro no upload.',
                    'filename' => null
                ];
            }
            
            $this->log("Iniciando upload de imagem");
            
            // Informações do arquivo
            $originalName = $fileArray['name'];
            $tmpName = $fileArray['tmp_name'];
            $fileSize = $fileArray['size'];
            $fileError = $fileArray['error'];
            
            $this->log("Arquivo: $originalName, Tamanho: $fileSize bytes");
            
            // Validar arquivo
            $validation = $this->validateFile($originalName, $fileSize, $tmpName);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'filename' => null
                ];
            }
            
            // Gerar nome único para o arquivo
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $newFileName = $this->generateUniqueFileName($extension);
            $targetPath = $this->uploadDir . $newFileName;
            
            $this->log("Novo nome do arquivo: $newFileName");
            
            // Mover arquivo para diretório de destino
            if (move_uploaded_file($tmpName, $targetPath)) {
                $this->log("Upload realizado com sucesso: $targetPath");
                
                // Remover imagem antiga se especificada
                if ($oldImageName && file_exists($this->uploadDir . $oldImageName)) {
                    unlink($this->uploadDir . $oldImageName);
                    $this->log("Imagem antiga removida: $oldImageName");
                }
                
                // Redimensionar imagem se necessário
                $this->resizeImage($targetPath);
                
                return [
                    'success' => true,
                    'message' => 'Imagem enviada com sucesso!',
                    'filename' => $newFileName,
                    'path' => $targetPath,
                    'url' => $this->getImageUrl($newFileName)
                ];
            } else {
                $this->log("ERRO: Falha ao mover arquivo para $targetPath");
                return [
                    'success' => false,
                    'message' => 'Erro ao salvar a imagem no servidor.',
                    'filename' => null
                ];
            }
            
        } catch (Exception $e) {
            $this->log("Exception no upload: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno no upload: ' . $e->getMessage(),
                'filename' => null
            ];
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
    private function generateUniqueFileName($extension) {
        $timestamp = time();
        $random = mt_rand(100000, 999999);
        return "evento_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Redimensionar imagem para otimização
     */
    private function resizeImage($imagePath) {
        try {
            $imageInfo = getimagesize($imagePath);
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];
            
            // Se a imagem for muito grande, redimensionar
            $maxWidth = 1200;
            $maxHeight = 800;
            
            if ($width > $maxWidth || $height > $maxHeight) {
                // Calcular proporções
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = intval($width * $ratio);
                $newHeight = intval($height * $ratio);
                
                // Criar imagem baseada no tipo
                switch ($type) {
                    case IMAGETYPE_JPEG:
                        $sourceImage = imagecreatefromjpeg($imagePath);
                        break;
                    case IMAGETYPE_PNG:
                        $sourceImage = imagecreatefrompng($imagePath);
                        break;
                    case IMAGETYPE_GIF:
                        $sourceImage = imagecreatefromgif($imagePath);
                        break;
                    default:
                        return; // Tipo não suportado para redimensionamento
                }
                
                // Criar nova imagem redimensionada
                $destImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // Preservar transparência para PNG
                if ($type == IMAGETYPE_PNG) {
                    imagealphablending($destImage, false);
                    imagesavealpha($destImage, true);
                }
                
                // Redimensionar
                imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                // Salvar imagem redimensionada
                switch ($type) {
                    case IMAGETYPE_JPEG:
                        imagejpeg($destImage, $imagePath, 85);
                        break;
                    case IMAGETYPE_PNG:
                        imagepng($destImage, $imagePath, 8);
                        break;
                    case IMAGETYPE_GIF:
                        imagegif($destImage, $imagePath);
                        break;
                }
                
                // Limpar memória
                imagedestroy($sourceImage);
                imagedestroy($destImage);
                
                $this->log("Imagem redimensionada de {$width}x{$height} para {$newWidth}x{$newHeight}");
            }
            
        } catch (Exception $e) {
            $this->log("Erro ao redimensionar imagem: " . $e->getMessage());
            // Não falhar o upload por causa do redimensionamento
        }
    }
    
    /**
     * Obter URL da imagem
     */
    public function getImageUrl($fileName) {
        $baseUrl = 'https://conecta-eventos-production.up.railway.app';
        return $baseUrl . '/uploads/eventos/' . $fileName;
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
        $imageInfo = getimagesize($filePath);
        $fileSize = filesize($filePath);
        
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
     * Log de debug
     */
    private function log($message) {
        if ($this->debug) {
            error_log("[ImageUploadHandler] " . $message);
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
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }
        
        $this->log("Limpeza realizada: $deletedCount arquivos antigos removidos");
        return $deletedCount;
    }
}
?>