<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Conecta Eventos'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/public/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="navbar-text me-3">
                        Olá, <?php echo $_SESSION['user_name']; ?>!
                    </span>
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/logout.php">Sair</a>
                <?php else: ?>
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/views/auth/login.php">Login</a>
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/views/auth/register.php">Cadastrar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <main class="container mt-4">