<?php
// ==========================================
// HANDLER DE UPLOAD DE IMAGENS
// Local: handlers/ImageUploadHandler.php
// ==========================================

class ImageUploadHandler {
    
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    private $maxWidth;
    private $maxHeight;
    
    public function __construct() {
        // Diretório de upload
        $this->uploadDir = __DIR__ . '/../uploads/events/';
        
        // Tipos permitidos
        $this->allowedTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        // Tamanho máximo: 5MB
        $this->maxSize = 5 * 1024 * 1024;
        
        // Dimensões máximas para redimensionamento
        $this->maxWidth = 1920;
        $this->maxHeight = 1080;
        
        // Criar diretório se não existir
        $this->createUploadDirectory();
    }
    
    /**
     * Criar diretório de upload se não existir
     */
    private function createUploadDirectory() {
        if (!file_exists($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new Exception("Não foi possível criar o diretório de upload");
            }
        }
        
        // Criar arquivo .htaccess para segurança
        $htaccessFile = $this->uploadDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = "# Bloquear execução de scripts\n";
            $htaccessContent .= "Options -ExecCGI\n";
            $htaccessContent .= "AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n";
            $htaccessContent .= "# Permitir apenas imagens\n";
            $htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">\n";
            $htaccessContent .= "    Require all granted\n";
            $htaccessContent .= "</FilesMatch>\n";
            $htaccessContent .= "<FilesMatch \"\\.(php|pl|py|jsp|asp|sh|cgi)$\">\n";
            $htaccessContent .= "    Require all denied\n";
            $htaccessContent .= "</FilesMatch>\n";
            
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }
    
    /**
     * Fazer upload de uma imagem
     * 
     * @param array $file Arquivo $_FILES
     * @return array Resultado do upload
     */
    public function uploadImage($file) {
        try {
            // Validar arquivo
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Gerar nome único
            $filename = $this->generateUniqueFilename($file['name']);
            $targetPath = $this->uploadDir . $filename;
            
            // Mover arquivo temporário
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return [
                    'success' => false,
                    'message' => 'Erro ao mover arquivo para destino'
                ];
            }
            
            // Redimensionar se necessário
            $this->resizeImageIfNeeded($targetPath);
            
            // Otimizar qualidade
            $this->optimizeImage($targetPath);
            
            return [
                'success' => true,
                'message' => 'Imagem enviada com sucesso',
                'filename' => $filename,
                'path' => $targetPath,
                'url' => '/uploads/events/' . $filename,
                'size' => filesize($targetPath)
            ];
            
        } catch (Exception $e) {
            error_log("Erro no upload de imagem: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno no upload: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar arquivo de upload
     * 
     * @param array $file
     * @return array
     */
    private function validateFile($file) {
        // Verificar se houve erro no upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do servidor)',
                UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário)',
                UPLOAD_ERR_PARTIAL => 'Upload incompleto',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
                UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
                UPLOAD_ERR_CANT_WRITE => 'Erro de escrita no disco',
                UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
            ];
            
            return [
                'valid' => false,
                'message' => $errorMessages[$file['error']] ?? 'Erro desconhecido no upload'
            ];
        }
        
        // Verificar tamanho
        if ($file['size'] > $this->maxSize) {
            return [
                'valid' => false,
                'message' => 'Arquivo muito grande. Máximo: ' . $this->formatFileSize($this->maxSize)
            ];
        }
        
        // Verificar tipo MIME
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'valid' => false,
                'message' => 'Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WebP'
            ];
        }
        
        // Verificar se é realmente uma imagem
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'message' => 'Arquivo não é uma imagem válida'
            ];
        }
        
        // Verificar dimensões mínimas
        if ($imageInfo[0] < 100 || $imageInfo[1] < 100) {
            return [
                'valid' => false,
                'message' => 'Imagem muito pequena. Mínimo: 100x100 pixels'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Arquivo válido'
        ];
    }
    
    /**
     * Gerar nome único para arquivo
     * 
     * @param string $originalName
     * @return string
     */
    private function generateUniqueFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = 'evento_' . date('Y-m-d_H-i-s') . '_' . uniqid();
        return $baseName . '.' . $extension;
    }
    
    /**
     * Redimensionar imagem se exceder dimensões máximas
     * 
     * @param string $imagePath
     */
    private function resizeImageIfNeeded($imagePath) {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return;
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Verificar se precisa redimensionar
        if ($width <= $this->maxWidth && $height <= $this->maxHeight) {
            return; // Não precisa redimensionar
        }
        
        // Calcular novas dimensões mantendo proporção
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Criar imagem source
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($imagePath);
                break;
            default:
                return; // Tipo não suportado
        }
        
        if (!$source) return;
        
        // Criar imagem de destino
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparência para PNG e GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($destination, imagecolorallocatealpha($destination, 0, 0, 0, 127));
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
        }
        
        // Redimensionar
        imagecopyresampled(
            $destination, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );
        
        // Salvar imagem redimensionada
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($destination, $imagePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($destination, $imagePath, 6);
                break;
            case IMAGETYPE_GIF:
                imagegif($destination, $imagePath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($destination, $imagePath, 85);
                break;
        }
        
        // Limpar memória
        imagedestroy($source);
        imagedestroy($destination);
    }
    
    /**
     * Otimizar qualidade da imagem
     * 
     * @param string $imagePath
     */
    private function optimizeImage($imagePath) {
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) return;
        
        $type = $imageInfo[2];
        
        // Otimizar apenas JPEG e WebP
        if ($type !== IMAGETYPE_JPEG && $type !== IMAGETYPE_WEBP) {
            return;
        }
        
        // Carregar imagem
        $image = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($imagePath);
                break;
        }
        
        if (!$image) return;
        
        // Salvar com qualidade otimizada
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $imagePath, 80); // Qualidade 80%
                break;
            case IMAGETYPE_WEBP:
                imagewebp($image, $imagePath, 80); // Qualidade 80%
                break;
        }
        
        imagedestroy($image);
    }
    
    /**
     * Deletar uma imagem
     * 
     * @param string $filename
     * @return bool
     */
    public function deleteImage($filename) {
        try {
            $filePath = $this->uploadDir . $filename;
            
            if (file_exists($filePath)) {
                return unlink($filePath);
            }
            
            return true; // Arquivo já não existe
            
        } catch (Exception $e) {
            error_log("Erro ao deletar imagem: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter URL pública da imagem
     * 
     * @param string $filename
     * @return string
     */
    public function getImageUrl($filename) {
        if (empty($filename)) {
            return '/assets/images/event-placeholder.jpg'; // Imagem padrão
        }
        
        return '/uploads/events/' . $filename;
    }
    
    /**
     * Verificar se arquivo de imagem existe
     * 
     * @param string $filename
     * @return bool
     */
    public function imageExists($filename) {
        if (empty($filename)) return false;
        
        $filePath = $this->uploadDir . $filename;
        return file_exists($filePath);
    }
    
    /**
     * Obter informações de uma imagem
     * 
     * @param string $filename
     * @return array|null
     */
    public function getImageInfo($filename) {
        if (!$this->imageExists($filename)) {
            return null;
        }
        
        $filePath = $this->uploadDir . $filename;
        $imageInfo = getimagesize($filePath);
        
        if (!$imageInfo) return null;
        
        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageInfo[2],
            'mime' => $imageInfo['mime'],
            'size' => filesize($filePath),
            'url' => $this->getImageUrl($filename)
        ];
    }
    
    /**
     * Formatar tamanho de arquivo
     * 
     * @param int $bytes
     * @return string
     */
    private function formatFileSize($bytes) {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    /**
     * Limpar uploads antigos (opcional - para manutenção)
     * 
     * @param int $daysOld Arquivos mais antigos que X dias
     * @return int Número de arquivos removidos
     */
    public function cleanOldUploads($daysOld = 30) {
        $removed = 0;
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        
        try {
            $files = glob($this->uploadDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    if (unlink($file)) {
                        $removed++;
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("Erro ao limpar uploads antigos: " . $e->getMessage());
        }
        
        return $removed;
    }
}