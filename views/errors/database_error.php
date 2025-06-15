<?php
// ==========================================
// PÁGINA DE ERRO DO BANCO DE DADOS
// Local: views/errors/database_error.php
// ==========================================
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Conecta Eventos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-card {
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="fas fa-database error-icon"></i>
        <h2 class="mb-3">Oops! Problema Técnico</h2>
        <p class="text-muted mb-4">
            <?php echo isset($errorMessage) ? htmlspecialchars($errorMessage) : 'Erro inesperado ao acessar os dados.'; ?>
        </p>
        
        <div class="d-grid gap-2 d-md-block">
            <a href="../../views/dashboard/organizer.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
            </a>
            <button class="btn btn-outline-primary" onclick="window.location.reload()">
                <i class="fas fa-sync-alt me-2"></i>Tentar Novamente
            </button>
        </div>
        
        <hr class="my-4">
        
        <p class="small text-muted">
            Se o problema persistir, entre em contato com o suporte.
        </p>
    </div>
</body>
</html>