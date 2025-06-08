<?php
// ========================================
// PÁGINA DE CONFIGURAÇÕES DO DASHBOARD
// ========================================
// Local: views/dashboard/settings.php
// ========================================

session_start();

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$title = "Configurações - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userEmail = $_SESSION['user_email'] ?? '';
$userType = $_SESSION['user_type'] ?? 'participante';

// URLs
$dashboardUrl = $userType === 'organizador' ? 'organizer.php' : 'participant.php';
$homeUrl = '../../index.php';

$success_message = '';
$error_message = '';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            // Simular atualização do perfil
            $success_message = "Perfil atualizado com sucesso!";
            break;
            
        case 'change_password':
            // Simular mudança de senha
            $success_message = "Senha alterada com sucesso!";
            break;
            
        case 'update_notifications':
            // Simular atualização de notificações
            $success_message = "Preferências de notificação atualizadas!";
            break;
            
        case 'update_privacy':
            // Simular atualização de privacidade
            $success_message = "Configurações de privacidade atualizadas!";
            break;
            
        default:
            $error_message = "Ação não reconhecida.";
            break;
    }
}

// Dados de exemplo do usuário
$user_data = [
    'nome' => $userName,
    'email' => $userEmail,
    'telefone' => '(11) 99999-9999',
    'cidade' => 'São Paulo',
    'estado' => 'SP',
    'bio' => 'Organizador de eventos apaixonado por tecnologia e inovação.',
    'site' => 'https://meusite.com',
    'linkedin' => 'https://linkedin.com/in/usuario',
    'instagram' => '@usuario'
];

// Configurações de notificação
$notification_settings = [
    'email_novos_participantes' => true,
    'email_cancelamentos' => true,
    'email_avaliacoes' => false,
    'sms_lembretes' => true,
    'push_notifications' => true
];

// Configurações de privacidade
$privacy_settings = [
    'perfil_publico' => true,
    'mostrar_email' => false,
    'mostrar_telefone' => false,
    'eventos_publicos' => true
];
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
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            background-color: #f8f9fa;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .settings-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .settings-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .settings-body {
            padding: 2rem;
        }

        .settings-nav {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
        }

        .nav-pills .nav-link {
            color: #6c757d;
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }

        .avatar-upload {
            position: relative;
            display: inline-block;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }

        .avatar-upload:hover .avatar-overlay {
            opacity: 1;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .danger-zone {
            border: 2px solid var(--danger-color);
            border-radius: 1rem;
            padding: 1.5rem;
            background: #fff5f5;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Olá, <?php echo htmlspecialchars($userName); ?>!
                </span>
                <a class="nav-link" href="<?php echo $dashboardUrl; ?>">Dashboard</a>
                <a class="nav-link" href="../../logout.php">Sair</a>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo $dashboardUrl; ?>" class="text-decoration-none">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Configurações</li>
            </ol>
        </nav>
    </div>

    <!-- Header da Página -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-cog me-2"></i>Configurações</h1>
                    <p class="mb-0 fs-5">Gerencie suas preferências e configurações da conta</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?php echo $dashboardUrl; ?>" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container pb-5">
        <!-- Mensagens -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Navigation Sidebar -->
            <div class="col-lg-3">
                <div class="settings-nav p-3">
                    <ul class="nav nav-pills flex-column" id="settingsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active w-100 text-start" 
                                    id="profile-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#profile" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-user me-2"></i>Perfil
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" 
                                    id="security-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#security" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-shield-alt me-2"></i>Segurança
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" 
                                    id="notifications-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#notifications" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-bell me-2"></i>Notificações
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" 
                                    id="privacy-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#privacy" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-eye me-2"></i>Privacidade
                            </button>
                        </li>
                        <?php if ($userType === 'organizador'): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" 
                                    id="billing-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#billing" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-credit-card me-2"></i>Faturamento
                            </button>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 text-start" 
                                    id="danger-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#danger" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-exclamation-triangle me-2"></i>Zona de Perigo
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-lg-9">
                <div class="tab-content" id="settingsTabContent">
                    <!-- Perfil -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Informações do Perfil
                                </h4>
                                <p class="mb-0 text-muted">Atualize suas informações pessoais</p>
                            </div>
                            <div class="settings-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <!-- Avatar -->
                                    <div class="text-center mb-4">
                                        <div class="avatar-upload">
                                            <div class="avatar">
                                                <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                            </div>
                                            <div class="avatar-overlay">
                                                <i class="fas fa-camera text-white fa-lg"></i>
                                            </div>
                                            <input type="file" id="avatar" name="avatar" style="display: none;" accept="image/*">
                                        </div>
                                        <p class="mt-2 text-muted">Clique para alterar foto</p>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nome" class="form-label">Nome Completo</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?php echo htmlspecialchars($user_data['nome']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">E-mail</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user_data['email']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="telefone" class="form-label">Telefone</label>
                                            <input type="tel" class="form-control" id="telefone" name="telefone" 
                                                   value="<?php echo htmlspecialchars($user_data['telefone']); ?>">
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label for="cidade" class="form-label">Cidade</label>
                                            <input type="text" class="form-control" id="cidade" name="cidade" 
                                                   value="<?php echo htmlspecialchars($user_data['cidade']); ?>">
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select class="form-select" id="estado" name="estado">
                                                <option value="SP" <?php echo $user_data['estado'] === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                                                <option value="RJ" <?php echo $user_data['estado'] === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                                <option value="MG" <?php echo $user_data['estado'] === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                                                <!-- Adicionar outros estados conforme necessário -->
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Biografia</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user_data['bio']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="site" class="form-label">Site</label>
                                            <input type="url" class="form-control" id="site" name="site" 
                                                   value="<?php echo htmlspecialchars($user_data['site']); ?>">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="linkedin" class="form-label">LinkedIn</label>
                                            <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                                   value="<?php echo htmlspecialchars($user_data['linkedin']); ?>">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="instagram" class="form-label">Instagram</label>
                                            <input type="text" class="form-control" id="instagram" name="instagram" 
                                                   value="<?php echo htmlspecialchars($user_data['instagram']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Salvar Alterações
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Segurança -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>Segurança da Conta
                                </h4>
                                <p class="mb-0 text-muted">Configure senha e autenticação</p>
                            </div>
                            <div class="settings-body">
                                <!-- Alterar Senha -->
                                <h5 class="mb-3">Alterar Senha</h5>
                                <form method="POST" class="mb-4">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Senha Atual</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="new_password" class="form-label">Nova Senha</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Alterar Senha
                                    </button>
                                </form>
                                
                                <hr>
                                
                                <!-- Autenticação de Dois Fatores -->
                                <h5 class="mb-3">Autenticação de Dois Fatores</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-1">Adicione uma camada extra de segurança</p>
                                        <small class="text-muted">Requer código do seu celular para fazer login</small>
                                    </div>
                                    <button class="btn btn-outline-primary" onclick="alert('Em desenvolvimento!')">
                                        <i class="fas fa-mobile-alt me-2"></i>Configurar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notificações -->
                    <div class="tab-pane fade" id="notifications" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-bell me-2"></i>Preferências de Notificação
                                </h4>
                                <p class="mb-0 text-muted">Escolha como deseja ser notificado</p>
                            </div>
                            <div class="settings-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_notifications">
                                    
                                    <h5 class="mb-3">E-mail</h5>
                                    
                                    <?php
                                    $email_options = [
                                        'email_novos_participantes' => 'Novos participantes nos seus eventos',
                                        'email_cancelamentos' => 'Cancelamentos de inscrições',
                                        'email_avaliacoes' => 'Novas avaliações dos seus eventos'
                                    ];
                                    ?>
                                    
                                    <?php foreach ($email_options as $key => $label): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <p class="mb-0"><?php echo $label; ?></p>
                                            </div>
                                            <label class="switch">
                                                <input type="checkbox" name="<?php echo $key; ?>" 
                                                       <?php echo $notification_settings[$key] ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <hr>
                                    
                                    <h5 class="mb-3">SMS e Push</h5>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Lembretes por SMS</p>
                                            <small class="text-muted">Lembretes de eventos próximos</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="sms_lembretes" 
                                                   <?php echo $notification_settings['sms_lembretes'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <p class="mb-0">Notificações Push</p>
                                            <small class="text-muted">Notificações instantâneas no navegador</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="push_notifications" 
                                                   <?php echo $notification_settings['push_notifications'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Salvar Preferências
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Privacidade -->
                    <div class="tab-pane fade" id="privacy" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-eye me-2"></i>Configurações de Privacidade
                                </h4>
                                <p class="mb-0 text-muted">Controle a visibilidade das suas informações</p>
                            </div>
                            <div class="settings-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_privacy">
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Perfil Público</p>
                                            <small class="text-muted">Permitir que outros vejam seu perfil</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="perfil_publico" 
                                                   <?php echo $privacy_settings['perfil_publico'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Mostrar E-mail</p>
                                            <small class="text-muted">Exibir e-mail no seu perfil público</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="mostrar_email" 
                                                   <?php echo $privacy_settings['mostrar_email'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Mostrar Telefone</p>
                                            <small class="text-muted">Exibir telefone no seu perfil público</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="mostrar_telefone" 
                                                   <?php echo $privacy_settings['mostrar_telefone'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <p class="mb-0">Eventos Públicos</p>
                                            <small class="text-muted">Mostrar seus eventos no seu perfil</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="eventos_publicos" 
                                                   <?php echo $privacy_settings['eventos_publicos'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Salvar Configurações
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Faturamento -->
                    <?php if ($userType === 'organizador'): ?>
                    <div class="tab-pane fade" id="billing" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-credit-card me-2"></i>Faturamento e Pagamentos
                                </h4>
                                <p class="mb-0 text-muted">Gerencie métodos de pagamento e faturas</p>
                            </div>
                            <div class="settings-body">
                                <!-- Plano Atual -->
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title mb-1">Plano Gratuito</h5>
                                                <p class="card-text mb-0">Até 5 eventos por mês</p>
                                            </div>
                                            <div class="text-end">
                                                <h3 class="mb-0">R$ 0</h3>
                                                <small>/mês</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="card-title">Básico</h5>
                                                <h3 class="text-primary">R$ 29</h3>
                                                <p class="text-muted">/mês</p>
                                                <ul class="list-unstyled">
                                                    <li>✓ 20 eventos/mês</li>
                                                    <li>✓ 500 participantes</li>
                                                    <li>✓ Suporte básico</li>
                                                </ul>
                                                <button class="btn btn-outline-primary" onclick="alert('Em desenvolvimento!')">
                                                    Upgrade
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card text-center border-primary">
                                            <div class="card-body">
                                                <h5 class="card-title">Pro</h5>
                                                <h3 class="text-primary">R$ 69</h3>
                                                <p class="text-muted">/mês</p>
                                                <ul class="list-unstyled">
                                                    <li>✓ Eventos ilimitados</li>
                                                    <li>✓ 2000 participantes</li>
                                                    <li>✓ Analytics avançado</li>
                                                    <li>✓ Suporte prioritário</li>
                                                </ul>
                                                <button class="btn btn-primary" onclick="alert('Em desenvolvimento!')">
                                                    Upgrade
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="card-title">Enterprise</h5>
                                                <h3 class="text-primary">R$ 149</h3>
                                                <p class="text-muted">/mês</p>
                                                <ul class="list-unstyled">
                                                    <li>✓ Tudo do Pro</li>
                                                    <li>✓ Participantes ilimitados</li>
                                                    <li>✓ API personalizada</li>
                                                    <li>✓ Suporte 24/7</li>
                                                </ul>
                                                <button class="btn btn-outline-primary" onclick="alert('Em desenvolvimento!')">
                                                    Contatar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Métodos de Pagamento -->
                                <h5 class="mb-3">Métodos de Pagamento</h5>
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fab fa-cc-visa fa-2x text-primary me-3"></i>
                                        <div>
                                            <p class="mb-0">**** **** **** 1234</p>
                                            <small class="text-muted">Expira em 12/2026</small>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge bg-success me-2">Padrão</span>
                                        <button class="btn btn-sm btn-outline-danger" onclick="alert('Em desenvolvimento!')">
                                            Remover
                                        </button>
                                    </div>
                                </div>
                                
                                <button class="btn btn-outline-primary mb-4" onclick="alert('Em desenvolvimento!')">
                                    <i class="fas fa-plus me-2"></i>Adicionar Método de Pagamento
                                </button>
                                
                                <!-- Histórico de Faturas -->
                                <h5 class="mb-3">Histórico de Faturas</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Descrição</th>
                                                <th>Valor</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>01/06/2024</td>
                                                <td>Plano Gratuito</td>
                                                <td>R$ 0,00</td>
                                                <td><span class="badge bg-success">Ativo</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="alert('Em desenvolvimento!')">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Zona de Perigo -->
                    <div class="tab-pane fade" id="danger" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h4 class="mb-0 text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Zona de Perigo
                                </h4>
                                <p class="mb-0 text-muted">Ações irreversíveis que afetam sua conta</p>
                            </div>
                            <div class="settings-body">
                                <!-- Exportar Dados -->
                                <div class="danger-zone mb-4">
                                    <h5 class="text-danger">Exportar Dados</h5>
                                    <p class="mb-3">Baixe uma cópia de todos os seus dados na plataforma.</p>
                                    <button class="btn btn-outline-info" onclick="exportData()">
                                        <i class="fas fa-download me-2"></i>Exportar Meus Dados
                                    </button>
                                </div>
                                
                                <!-- Desativar Conta -->
                                <div class="danger-zone mb-4">
                                    <h5 class="text-warning">Desativar Conta</h5>
                                    <p class="mb-3">Sua conta será desativada temporariamente. Você pode reativá-la a qualquer momento fazendo login novamente.</p>
                                    <button class="btn btn-warning" onclick="deactivateAccount()">
                                        <i class="fas fa-pause me-2"></i>Desativar Conta
                                    </button>
                                </div>
                                
                                <!-- Excluir Conta -->
                                <div class="danger-zone">
                                    <h5 class="text-danger">Excluir Conta Permanentemente</h5>
                                    <p class="mb-3">
                                        <strong>ATENÇÃO:</strong> Esta ação é irreversível. Todos os seus dados, eventos e inscrições serão permanentemente removidos.
                                    </p>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirmDelete">
                                        <label class="form-check-label" for="confirmDelete">
                                            Eu entendo que esta ação é irreversível
                                        </label>
                                    </div>
                                    <button class="btn btn-danger" onclick="deleteAccount()" disabled id="deleteBtn">
                                        <i class="fas fa-trash me-2"></i>Excluir Conta Permanentemente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Avatar upload
            document.querySelector('.avatar-overlay').addEventListener('click', function() {
                document.getElementById('avatar').click();
            });
            
            document.getElementById('avatar').addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Aqui você pode implementar a prévia da imagem
                        alert('Imagem selecionada! Funcionalidade de upload em desenvolvimento.');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            // Máscara de telefone
            const telefoneInput = document.getElementById('telefone');
            if (telefoneInput) {
                telefoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                    e.target.value = value;
                });
            }
            
            // Validação de senhas
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                function validatePasswords() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('As senhas não coincidem');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                newPassword.addEventListener('change', validatePasswords);
                confirmPassword.addEventListener('keyup', validatePasswords);
            }
            
            // Checkbox para habilitar botão de exclusão
            const confirmDelete = document.getElementById('confirmDelete');
            const deleteBtn = document.getElementById('deleteBtn');
            
            if (confirmDelete && deleteBtn) {
                confirmDelete.addEventListener('change', function() {
                    deleteBtn.disabled = !this.checked;
                });
            }
            
            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 5000);
            });
            
            // Loading state para formulários
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
                        
                        // Re-enable após 3 segundos
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 3000);
                    }
                });
            });
        });
        
        // Função para exportar dados
        function exportData() {
            if (confirm('Deseja exportar todos os seus dados? Você receberá um link para download por email.')) {
                showToast('Solicitação de exportação enviada! Você receberá um email em breve.', 'info');
            }
        }
        
        // Função para desativar conta
        function deactivateAccount() {
            if (confirm('Tem certeza que deseja desativar sua conta? Você pode reativá-la fazendo login novamente.')) {
                showToast('Funcionalidade em desenvolvimento', 'warning');
            }
        }
        
        // Função para excluir conta
        function deleteAccount() {
            const confirmText = prompt('Para confirmar a exclusão, digite "EXCLUIR" em letras maiúsculas:');
            if (confirmText === 'EXCLUIR') {
                if (confirm('ÚLTIMA CONFIRMAÇÃO: Tem certeza absoluta que deseja excluir permanentemente sua conta?')) {
                    showToast('Funcionalidade em desenvolvimento', 'danger');
                }
            } else if (confirmText !== null) {
                alert('Texto de confirmação incorreto. Conta não foi excluída.');
            }
        }
        
        // Sistema de toast notifications
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            toast.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 400px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            `;
            
            const icons = {
                success: 'fas fa-check-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-exclamation-triangle',
                danger: 'fas fa-exclamation-circle'
            };
            
            toast.innerHTML = `
                <i class="${icons[type] || icons.info} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }
        
        // Smooth scroll para tabs
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                setTimeout(() => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }, 100);
            });
        });
    </script>
</body>
</html>