<?php
// ==========================================
// SCRIPT PARA CONFIGURAR ESTRUTURA DE UPLOAD
// Local: setup_upload.php (rodar na raiz do projeto)
// ==========================================

echo "=== CONFIGURAÇÃO DE UPLOAD DE IMAGENS ===\n";

// 1. Criar diretório handlers se não existir
$handlersDir = __DIR__ . '/handlers';
if (!file_exists($handlersDir)) {
    mkdir($handlersDir, 0755, true);
    echo "✓ Diretório handlers/ criado\n";
} else {
    echo "✓ Diretório handlers/ já existe\n";
}

// 2. Criar diretório uploads/eventos se não existir
$uploadsDir = __DIR__ . '/uploads/eventos';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
    echo "✓ Diretório uploads/eventos/ criado\n";
} else {
    echo "✓ Diretório uploads/eventos/ já existe\n";
}

// 3. Criar .htaccess no diretório uploads/eventos
$htaccessPath = $uploadsDir . '/.htaccess';
$htaccessContent = "# Impedir execução de scripts
php_flag engine off
AddType text/plain .php .php3 .phtml .pht

# Apenas imagens
<Files ~ \"\\.(php|php3|phtml|pht|jsp|asp|aspx|cgi|pl)$\">
    Order allow,deny
    Deny from all
</Files>

# Permitir acesso às imagens
<Files ~ \"\\.(jpg|jpeg|png|gif|webp)$\">
    Order allow,deny
    Allow from all
</Files>";

file_put_contents($htaccessPath, $htaccessContent);
echo "✓ Arquivo .htaccess criado em uploads/eventos/\n";

// 4. Criar index.php de proteção
$indexPath = $uploadsDir . '/index.php';
$indexContent = "<?php
// Proteção do diretório
header('HTTP/1.0 403 Forbidden');
exit('Acesso negado.');
?>";

file_put_contents($indexPath, $indexContent);
echo "✓ Arquivo index.php de proteção criado\n";

// 5. Verificar se ImageUploadHandler.php existe
$handlerPath = $handlersDir . '/ImageUploadHandler.php';
if (!file_exists($handlerPath)) {
    // Criar o arquivo ImageUploadHandler.php
    $handlerContent = file_get_contents('https://raw.githubusercontent.com/user/repo/main/handlers/ImageUploadHandler.php');
    // Como não temos acesso direto, vamos criar o conteúdo inline
    
    $handlerContent = '<?php
// ==========================================
// HANDLER DE UPLOAD DE IMAGENS - VERSÃO RAILWAY
// Local: handlers/ImageUploadHandler.php
// ==========================================

class ImageUploadHandler {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    private $debug = true;
    
    public function __construct() {
        // Diretório de upload
        $this->uploadDir = __DIR__ . "/../uploads/eventos/";
        
        // Tipos permitidos
        $this->allowedTypes = ["jpg", "jpeg", "png", "gif", "webp"];
        
        // Tamanho máximo: 5MB
        $this->maxFileSize = 5 * 1024 * 1024;
        
        // Criar diretório se não existir
        $this->ensureUploadDirectory();
    }
    
    private function ensureUploadDirectory() {
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
            $this->log("Diretório de upload criado: " . $this->uploadDir);
        }
    }
    
    public function uploadImage($fileArray, $oldImageName = null) {
        try {
            if (!isset($fileArray) || $fileArray["error"] !== UPLOAD_ERR_OK) {
                return [
                    "success" => false,
                    "message" => $this->getUploadErrorMessage($fileArray["error"] ?? UPLOAD_ERR_NO_FILE),
                    "filename" => null
                ];
            }
            
            $this->log("Iniciando upload de imagem");
            
            $originalName = $fileArray["name"];
            $tmpName = $fileArray["tmp_name"];
            $fileSize = $fileArray["size"];
            
            $this->log("Arquivo: $originalName, Tamanho: $fileSize bytes");
            
            $validation = $this->validateFile($originalName, $fileSize, $tmpName);
            if (!$validation["valid"]) {
                return [
                    "success" => false,
                    "message" => $validation["message"],
                    "filename" => null
                ];
            }
            
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $newFileName = $this->generateUniqueFileName($extension);
            $targetPath = $this->uploadDir . $newFileName;
            
            $this->log("Novo nome do arquivo: $newFileName");
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                $this->log("Upload realizado com sucesso: $targetPath");
                
                if ($oldImageName && $oldImageName !== $newFileName) {
                    $this->deleteImage($oldImageName);
                }
                
                $this->resizeImage($targetPath);
                
                return [
                    "success" => true,
                    "message" => "Imagem enviada com sucesso!",
                    "filename" => $newFileName,
                    "path" => $targetPath,
                    "url" => $this->getImageUrl($newFileName)
                ];
            } else {
                $this->log("ERRO: Falha ao mover arquivo para $targetPath");
                return [
                    "success" => false,
                    "message" => "Erro ao salvar a imagem no servidor.",
                    "filename" => null
                ];
            }
            
        } catch (Exception $e) {
            $this->log("Exception no upload: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Erro interno no upload: " . $e->getMessage(),
                "filename" => null
            ];
        }
    }
    
    private function validateFile($fileName, $fileSize, $tmpName) {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            return [
                "valid" => false,
                "message" => "Tipo de arquivo não permitido. Use: " . implode(", ", $this->allowedTypes)
            ];
        }
        
        if ($fileSize > $this->maxFileSize) {
            $maxSizeMB = $this->maxFileSize / (1024 * 1024);
            return [
                "valid" => false,
                "message" => "Arquivo muito grande. Tamanho máximo: {$maxSizeMB}MB"
            ];
        }
        
        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) {
            return [
                "valid" => false,
                "message" => "O arquivo enviado não é uma imagem válida."
            ];
        }
        
        $allowedMimes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
        if (!in_array($imageInfo["mime"], $allowedMimes)) {
            return [
                "valid" => false,
                "message" => "Tipo MIME não permitido."
            ];
        }
        
        return ["valid" => true, "message" => "Arquivo válido"];
    }
    
    private function generateUniqueFileName($extension) {
        $timestamp = time();
        $random = mt_rand(100000, 999999);
        return "evento_{$timestamp}_{$random}.{$extension}";
    }
    
    private function resizeImage($imagePath) {
        try {
            if (!extension_loaded("gd")) {
                $this->log("Extensão GD não disponível - pulando redimensionamento");
                return;
            }
            
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return;
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];
            
            $maxWidth = 1200;
            $maxHeight = 800;
            
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = intval($width * $ratio);
                $newHeight = intval($height * $ratio);
                
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
                        if (function_exists("imagecreatefromwebp")) {
                            $sourceImage = @imagecreatefromwebp($imagePath);
                        }
                        break;
                }
                
                if (!$sourceImage) {
                    return;
                }
                
                $destImage = imagecreatetruecolor($newWidth, $newHeight);
                
                if ($type == IMAGETYPE_PNG) {
                    imagealphablending($destImage, false);
                    imagesavealpha($destImage, true);
                    $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
                    imagefilledrectangle($destImage, 0, 0, $newWidth, $newHeight, $transparent);
                }
                
                if (imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
                    $saved = false;
                    switch ($type) {
                        case IMAGETYPE_JPEG:
                            $saved = imagejpeg($destImage, $imagePath, 85);
                            break;
                        case IMAGETYPE_PNG:
                            $saved = imagepng($destImage, $imagePath, 8);
                            break;
                        case IMAGETYPE_GIF:
                            $saved = imagegif($destImage, $imagePath);
                            break;
                        case IMAGETYPE_WEBP:
                            if (function_exists("imagewebp")) {
                                $saved = imagewebp($destImage, $imagePath, 85);
                            }
                            break;
                    }
                    
                    if ($saved) {
                        $this->log("Imagem redimensionada de {$width}x{$height} para {$newWidth}x{$newHeight}");
                    }
                }
                
                imagedestroy($sourceImage);
                imagedestroy($destImage);
            }
            
        } catch (Exception $e) {
            $this->log("Erro ao redimensionar imagem: " . $e->getMessage());
        }
    }
    
    public function getImageUrl($fileName) {
        if (!$fileName) return null;
        
        $baseUrl = "https://conecta-eventos-production.up.railway.app";
        return $baseUrl . "/uploads/eventos/" . $fileName;
    }
    
    public function deleteImage($fileName) {
        if (!$fileName) return true;
        
        $filePath = $this->uploadDir . $fileName;
        if (file_exists($filePath)) {
            $deleted = unlink($filePath);
            $this->log($deleted ? "Imagem deletada: $fileName" : "Falha ao deletar: $fileName");
            return $deleted;
        }
        
        return true;
    }
    
    public function imageExists($fileName) {
        if (!$fileName) return false;
        return file_exists($this->uploadDir . $fileName);
    }
    
    public function getImageInfo($fileName) {
        if (!$fileName || !$this->imageExists($fileName)) {
            return null;
        }
        
        $filePath = $this->uploadDir . $fileName;
        $imageInfo = @getimagesize($filePath);
        $fileSize = filesize($filePath);
        
        if (!$imageInfo) {
            return [
                "filename" => $fileName,
                "path" => $filePath,
                "url" => $this->getImageUrl($fileName),
                "size" => $fileSize,
                "size_formatted" => $this->formatFileSize($fileSize),
                "error" => "Não foi possível obter informações da imagem"
            ];
        }
        
        return [
            "filename" => $fileName,
            "path" => $filePath,
            "url" => $this->getImageUrl($fileName),
            "width" => $imageInfo[0],
            "height" => $imageInfo[1],
            "type" => $imageInfo["mime"],
            "size" => $fileSize,
            "size_formatted" => $this->formatFileSize($fileSize)
        ];
    }
    
    private function formatFileSize($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . " MB";
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . " KB";
        } else {
            return $bytes . " B";
        }
    }
    
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                return "Upload realizado com sucesso";
            case UPLOAD_ERR_INI_SIZE:
                return "Arquivo excede o tamanho máximo permitido pelo servidor";
            case UPLOAD_ERR_FORM_SIZE:
                return "Arquivo excede o tamanho máximo permitido pelo formulário";
            case UPLOAD_ERR_PARTIAL:
                return "Upload foi parcialmente realizado";
            case UPLOAD_ERR_NO_FILE:
                return "Nenhum arquivo foi enviado";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Diretório temporário não encontrado";
            case UPLOAD_ERR_CANT_WRITE:
                return "Falha ao escrever arquivo no disco";
            case UPLOAD_ERR_EXTENSION:
                return "Upload interrompido por extensão PHP";
            default:
                return "Erro desconhecido no upload";
        }
    }
    
    private function log($message) {
        if ($this->debug) {
            error_log("[ImageUploadHandler] " . $message);
        }
    }
    
    public function cleanOldUploads($daysOld = 30) {
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $files = glob($this->uploadDir . "*");
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                $fileName = basename($file);
                if (!in_array($fileName, [".htaccess", "index.php"])) {
                    if (unlink($file)) {
                        $deletedCount++;
                    }
                }
            }
        }
        
        $this->log("Limpeza realizada: $deletedCount arquivos antigos removidos");
        return $deletedCount;
    }
    
    public function getUploadStats() {
        $files = glob($this->uploadDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
        $totalFiles = count($files);
        $totalSize = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }
        
        return [
            "total_files" => $totalFiles,
            "total_size" => $totalSize,
            "total_size_formatted" => $this->formatFileSize($totalSize),
            "upload_dir" => $this->uploadDir,
            "max_file_size" => $this->maxFileSize,
            "max_file_size_formatted" => $this->formatFileSize($this->maxFileSize),
            "allowed_types" => $this->allowedTypes
        ];
    }
}
?>';
    
    file_put_contents($handlerPath, $handlerContent);
    echo "✓ Arquivo ImageUploadHandler.php criado\n";
} else {
    echo "✓ Arquivo ImageUploadHandler.php já existe\n";
}

// 6. Verificar permissões
$permissions = [
    $handlersDir => '755',
    $uploadsDir => '755',
    $htaccessPath => '644',
    $indexPath => '644'
];

foreach ($permissions as $path => $expectedPerm) {
    $currentPerm = substr(sprintf('%o', fileperms($path)), -3);
    if ($currentPerm >= $expectedPerm) {
        echo "✓ Permissões OK para $path ($currentPerm)\n";
    } else {
        chmod($path, octdec($expectedPerm));
        echo "✓ Permissões corrigidas para $path\n";
    }
}

// 7. Teste de escrita
$testFile = $uploadsDir . '/test_' . time() . '.txt';
if (file_put_contents($testFile, 'teste') !== false) {
    unlink($testFile);
    echo "✓ Teste de escrita no diretório uploads: OK\n";
} else {
    echo "❌ ERRO: Não foi possível escrever no diretório uploads\n";
}

// 8. Verificar extensões PHP necessárias
$extensions = ['gd', 'fileinfo'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ Extensão PHP $ext: OK\n";
    } else {
        echo "⚠️  Extensão PHP $ext: NÃO DISPONÍVEL (funcionalidade limitada)\n";
    }
}

// 9. Verificar configurações PHP
$phpSettings = [
    'file_uploads' => '1',
    'upload_max_filesize' => '10M',
    'post_max_size' => '15M',
    'max_execution_time' => '60'
];

foreach ($phpSettings as $setting => $recommended) {
    $current = ini_get($setting);
    echo "ℹ️  PHP $setting: $current (recomendado: $recommended)\n";
}

echo "\n=== CONFIGURAÇÃO CONCLUÍDA ===\n";
echo "✅ Estrutura de upload configurada com sucesso!\n";
echo "\nPróximos passos:\n";
echo "1. Acesse views/events/create.php para testar criação de eventos\n";
echo "2. Acesse views/events/edit.php para testar edição com upload\n";
echo "3. Verifique se as imagens aparecem corretamente no frontend\n";
echo "\nDiretórios criados:\n";
echo "- handlers/ImageUploadHandler.php\n";
echo "- uploads/eventos/\n";
echo "- uploads/eventos/.htaccess\n";
echo "- uploads/eventos/index.php\n";

?>