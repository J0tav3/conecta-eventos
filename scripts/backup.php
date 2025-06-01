<?php
// ========================================
// SISTEMA DE BACKUP AUTOMÁTICO
// ========================================
// Local: scripts/backup.php
// ========================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class BackupManager {
    private $backupDir;
    private $maxBackups;
    private $logFile;
    
    public function __construct() {
        $this->backupDir = __DIR__ . '/../backups/';
        $this->maxBackups = 30; // Manter 30 backups
        $this->logFile = $this->backupDir . 'backup.log';
        
        // Criar diretório se não existir
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Realizar backup completo
     */
    public function createFullBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . "backup_conecta_eventos_{$timestamp}.sql";
        
        try {
            $this->log("Iniciando backup completo...");
            
            // Backup do banco de dados
            $this->backupDatabase($backupFile);
            
            // Backup dos uploads
            $this->backupUploads($timestamp);
            
            // Compactar backup
            $this->compressBackup($timestamp);
            
            // Limpar backups antigos
            $this->cleanOldBackups();
            
            $this->log("Backup completo realizado com sucesso: {$timestamp}");
            
            return [
                'success' => true,
                'backup_file' => "backup_conecta_eventos_{$timestamp}.zip",
                'message' => 'Backup realizado com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->log("Erro no backup: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao realizar backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Backup do banco de dados
     */
    private function backupDatabase($backupFile) {
        $host = DB_HOST;
        $username = DB_USER;
        $password = DB_PASS;
        $database = DB_NAME;
        
        // Comando mysqldump
        $command = "mysqldump --host={$host} --user={$username}";
        if (!empty($password)) {
            $command .= " --password={$password}";
        }
        $command .= " --single-transaction --routines --triggers {$database} > {$backupFile}";
        
        // Executar comando
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception("Erro no mysqldump: " . implode("\n", $output));
        }
        
        if (!file_exists($backupFile) || filesize($backupFile) === 0) {
            throw new Exception("Arquivo de backup não foi criado ou está vazio");
        }
        
        $this->log("Backup do banco criado: " . basename($backupFile));
    }
    
    /**
     * Backup dos uploads
     */
    private function backupUploads($timestamp) {
        $uploadsDir = __DIR__ . '/../public/uploads/';
        $backupUploadsDir = $this->backupDir . "uploads_{$timestamp}/";
        
        if (file_exists($uploadsDir)) {
            mkdir($backupUploadsDir, 0755, true);
            $this->copyDirectory($uploadsDir, $backupUploadsDir);
            $this->log("Backup dos uploads criado");
        }
    }
    
    /**
     * Compactar backup
     */
    private function compressBackup($timestamp) {
        $zipFile = $this->backupDir . "backup_conecta_eventos_{$timestamp}.zip";
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Não foi possível criar arquivo ZIP");
        }
        
        // Adicionar SQL dump
        $sqlFile = $this->backupDir . "backup_conecta_eventos_{$timestamp}.sql";
        if (file_exists($sqlFile)) {
            $zip->addFile($sqlFile, "database.sql");
        }
        
        // Adicionar uploads
        $uploadsDir = $this->backupDir . "uploads_{$timestamp}/";
        if (file_exists($uploadsDir)) {
            $this->addDirectoryToZip($zip, $uploadsDir, 'uploads/');
        }
        
        $zip->close();
        
        // Remover arquivos temporários
        if (file_exists($sqlFile)) {
            unlink($sqlFile);
        }
        if (file_exists($uploadsDir)) {
            $this->removeDirectory($uploadsDir);
        }
        
        $this->log("Backup compactado: " . basename($zipFile));
    }
    
    /**
     * Limpar backups antigos
     */
    private function cleanOldBackups() {
        $files = glob($this->backupDir . "backup_conecta_eventos_*.zip");
        
        if (count($files) > $this->maxBackups) {
            // Ordenar por data de modificação
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remover arquivos mais antigos
            $filesToRemove = array_slice($files, 0, count($files) - $this->maxBackups);
            foreach ($filesToRemove as $file) {
                unlink($file);
                $this->log("Backup antigo removido: " . basename($file));
            }
        }
    }
    
    /**
     * Copiar diretório recursivamente
     */
    private function copyDirectory($source, $destination) {
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!file_exists($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item, $destPath);
            }
        }
    }
    
    /**
     * Adicionar diretório ao ZIP
     */
    private function addDirectoryToZip($zip, $source, $zipPath = '') {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $filePath = $zipPath . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                $zip->addEmptyDir($filePath);
            } else {
                $zip->addFile($item, $filePath);
            }
        }
    }
    
    /**
     * Remover diretório recursivamente
     */
    private function removeDirectory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Restaurar backup
     */
    public function restoreBackup($backupFile) {
        try {
            $this->log("Iniciando restauração do backup: {$backupFile}");
            
            $fullPath = $this->backupDir . $backupFile;
            if (!file_exists($fullPath)) {
                throw new Exception("Arquivo de backup não encontrado");
            }
            
            // Extrair ZIP
            $extractDir = $this->backupDir . 'temp_restore/';
            $this->extractBackup($fullPath, $extractDir);
            
            // Restaurar banco
            $sqlFile = $extractDir . 'database.sql';
            if (file_exists($sqlFile)) {
                $this->restoreDatabase($sqlFile);
            }
            
            // Restaurar uploads
            $uploadsSource = $extractDir . 'uploads/';
            $uploadsDestination = __DIR__ . '/../public/uploads/';
            if (file_exists($uploadsSource)) {
                $this->copyDirectory($uploadsSource, $uploadsDestination);
            }
            
            // Limpar arquivos temporários
            $this->removeDirectory($extractDir);
            
            $this->log("Restauração concluída com sucesso");
            
            return [
                'success' => true,
                'message' => 'Backup restaurado com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->log("Erro na restauração: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao restaurar backup: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extrair backup ZIP
     */
    private function extractBackup($zipFile, $extractDir) {
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile) !== TRUE) {
            throw new Exception("Não foi possível abrir arquivo ZIP");
        }
        
        if (!file_exists($extractDir)) {
            mkdir($extractDir, 0755, true);
        }
        
        $zip->extractTo($extractDir);
        $zip->close();
    }
    
    /**
     * Restaurar banco de dados
     */
    private function restoreDatabase($sqlFile) {
        $host = DB_HOST;
        $username = DB_USER;
        $password = DB_PASS;
        $database = DB_NAME;
        
        // Comando mysql
        $command = "mysql --host={$host} --user={$username}";
        if (!empty($password)) {
            $command .= " --password={$password}";
        }
        $command .= " {$database} < {$sqlFile}";
        
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception("Erro ao restaurar banco: " . implode("\n", $output));
        }
        
        $this->log("Banco de dados restaurado");
    }
    
    /**
     * Listar backups disponíveis
     */
    public function listBackups() {
        $files = glob($this->backupDir . "backup_conecta_eventos_*.zip");
        $backups = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('d/m/Y H:i:s', filemtime($file)),
                'timestamp' => filemtime($file)
            ];
        }
        
        // Ordenar por data (mais recente primeiro)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $backups;
    }
    
    /**
     * Formatar bytes
     */
    private function formatBytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Log de atividades
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obter logs
     */
    public function getLogs($lines = 50) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($logs), 0, $lines);
    }
}

// Se executado via linha de comando
if (php_sapi_name() === 'cli') {
    echo "Conecta Eventos - Sistema de Backup\n";
    echo "===================================\n\n";
    
    $backup = new BackupManager();
    
    $action = $argv[1] ?? 'backup';
    
    switch ($action) {
        case 'backup':
            echo "Iniciando backup...\n";
            $result = $backup->createFullBackup();
            echo $result['message'] . "\n";
            break;
            
        case 'list':
            echo "Backups disponíveis:\n";
            $backups = $backup->listBackups();
            foreach ($backups as $backup) {
                echo "- {$backup['filename']} ({$backup['size']}) - {$backup['date']}\n";
            }
            break;
            
        case 'restore':
            $file = $argv[2] ?? null;
            if (!$file) {
                echo "Uso: php backup.php restore <nome_do_arquivo>\n";
                exit(1);
            }
            echo "Restaurando backup: {$file}\n";
            $result = $backup->restoreBackup($file);
            echo $result['message'] . "\n";
            break;
            
        default:
            echo "Uso: php backup.php [backup|list|restore]\n";
            break;
    }
}
?>