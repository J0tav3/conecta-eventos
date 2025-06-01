<?php
// ========================================
// INTERFACE DE BACKUP
// ========================================
// Local: views/admin/backup.php
// ========================================

require_once '../../config/config.php';
require_once '../../includes/session.php';
require_once '../../scripts/backup.php';

// Verificar se usuário está logado e é organizador
requireLogin();
if (!isOrganizer()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$title = "Backup e Restore - " . SITE_NAME;
$backupManager = new BackupManager();

$message = '';
$messageType = '';

// Processar ações
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_backup':
            $result = $backupManager->createFullBackup();
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'restore_backup':
            $filename = $_POST['backup_file'] ?? '';
            if ($filename) {
                $result = $backupManager->restoreBackup($filename);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            }
            break;
    }
}

// Obter lista de backups
$backups = $backupManager->listBackups();
$logs = $backupManager->getLogs(20);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        .backup-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .backup-item {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .backup-item:hover {
            background: #f8f9fa;
            border-color: #007bff;
        }
        .log-container {
            background: #2d3748;
            color: #e2e8f0;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        .status-success { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-danger { background: #dc3545; }
    </style>
</head>
<body>
    <?php include '../../views/layouts/header.php'; ?>

    <div class="container my-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-database me-2"></i>Backup e Restore</h2>
                <p class="text-muted">Gerencie backups do sistema e restaure quando necessário</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="../dashboard/organizer.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                </a>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Criar Backup -->
            <div class="col-lg-6">
                <div class="backup-card">
                    <h4 class="mb-3">
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        Criar Novo Backup
                    </h4>
                    <p class="text-muted">
                        Criar um backup completo incluindo banco de dados e arquivos de upload.
                    </p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>O que será incluído:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Banco de dados completo</li>
                            <li>Arquivos de upload (imagens)</li>
                            <li>Configurações do sistema</li>
                        </ul>
                    </div>
                    
                    <form method="POST" onsubmit="return confirmBackup()">
                        <input type="hidden" name="action" value="create_backup">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-download me-2"></i>
                                Criar Backup Agora
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Status do Sistema -->
            <div class="col-lg-6">
                <div class="backup-card">
                    <h4 class="mb-3">
                        <i class="fas fa-heartbeat me-2 text-info"></i>
                        Status do Sistema
                    </h4>
                    
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="card border-0">
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo count($backups); ?></h3>
                                    <p class="mb-0">Backups Disponíveis</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card border-0">
                                <div class="card-body">
                                    <h3 class="text-success">
                                        <?php echo !empty($backups) ? date('d/m', strtotime($backups[0]['date'])) : 'N/A'; ?>
                                    </h3>
                                    <p class="mb-0">Último Backup</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Importante:</strong> Faça backups regulares para manter seus dados seguros.
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Backups -->
        <div class="backup-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="fas fa-archive me-2 text-primary"></i>
                    Backups Disponíveis
                </h4>
                <span class="badge bg-secondary"><?php echo count($backups); ?> backups</span>
            </div>
            
            <?php if (empty($backups)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>Nenhum backup encontrado</h5>
                    <p class="text-muted">Crie seu primeiro backup para proteger seus dados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome do Arquivo</th>
                                <th>Tamanho</th>
                                <th>Data de Criação</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $index => $backup): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-archive text-primary me-2"></i>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($backup['filename']); ?></div>
                                                <?php if ($index === 0): ?>
                                                    <small class="text-success">
                                                        <i class="fas fa-star"></i> Mais recente
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $backup['size']; ?></td>
                                    <td><?php echo $backup['date']; ?></td>
                                    <td>
                                        <span class="status-indicator status-success"></span>
                                        <span class="text-success">Completo</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning" 
                                                    onclick="restoreBackup('<?php echo htmlspecialchars($backup['filename']); ?>')"
                                                    title="Restaurar backup">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                            <a href="../../backups/<?php echo htmlspecialchars($backup['filename']); ?>" 
                                               class="btn btn-sm btn-outline-info"
                                               download
                                               title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteBackup('<?php echo htmlspecialchars($backup['filename']); ?>')"
                                                    title="Excluir backup">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Logs do Sistema -->
        <div class="backup-card">
            <h4 class="mb-3">
                <i class="fas fa-terminal me-2 text-secondary"></i>
                Logs do Sistema
            </h4>
            
            <div class="log-container">
                <?php if (empty($logs)): ?>
                    <div class="text-muted">Nenhum log disponível.</div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="mb-1"><?php echo htmlspecialchars($log); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="mt-3">
                <button class="btn btn-outline-secondary btn-sm" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt me-2"></i>Atualizar Logs
                </button>
            </div>
        </div>

        <!-- Configurações Avançadas -->
        <div class="backup-card">
            <h4 class="mb-3">
                <i class="fas fa-cogs me-2 text-warning"></i>
                Configurações Avançadas
            </h4>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>Backup Automático</h6>
                    <p class="text-muted small">Configure backups automáticos via cron job:</p>
                    <div class="alert alert-light">
                        <code>0 2 * * * php <?php echo __DIR__; ?>/../../scripts/backup.php backup</code>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>Restauração de Emergência</h6>
                    <p class="text-muted small">Via linha de comando:</p>
                    <div class="alert alert-light">
                        <code>php backup.php restore nome_do_arquivo.zip</code>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Atenção:</strong> A restauração de backup substituirá todos os dados atuais. 
                Certifique-se de fazer um backup antes de restaurar.
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Restauração -->
    <div class="modal fade" id="restoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Confirmar Restauração
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>ATENÇÃO:</strong> Esta ação irá substituir todos os dados atuais do sistema!</p>
                    <p>Tem certeza que deseja restaurar o backup:</p>
                    <p class="fw-bold" id="backupFileName"></p>
                    <div class="alert alert-danger">
                        <i class="fas fa-info-circle me-2"></i>
                        Recomendamos fazer um backup atual antes de prosseguir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="restore_backup">
                        <input type="hidden" name="backup_file" id="selectedBackupFile">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-upload me-2"></i>Restaurar Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirmar criação de backup
        function confirmBackup() {
            return confirm('Tem certeza que deseja criar um novo backup? Esta operação pode levar alguns minutos.');
        }

        // Restaurar backup
        function restoreBackup(filename) {
            document.getElementById('backupFileName').textContent = filename;
            document.getElementById('selectedBackupFile').value = filename;
            
            const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
            modal.show();
        }

        // Excluir backup
        function deleteBackup(filename) {
            if (confirm(`Tem certeza que deseja excluir o backup: ${filename}?`)) {
                // Implementar via AJAX
                fetch('delete_backup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        filename: filename
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Backup excluído com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('Erro ao excluir backup', 'error');
                });
            }
        }

        // Atualizar logs
        function refreshLogs() {
            location.reload();
        }

        // Sistema de toast
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            `;
            
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 4000);
        }

        // Auto-hide mensagens
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-dismissible')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);

        // Atualizar logs automaticamente a cada 30 segundos
        setInterval(function() {
            // Implementar atualização via AJAX se necessário
        }, 30000);
    </script>
</body>
</html>