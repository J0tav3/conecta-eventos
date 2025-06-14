<?php
// ========================================
// PÁGINA DE CONFIGURAÇÕES DO PARTICIPANTE - ATUALIZADA
// ========================================
// Local: views/dashboard/participant-settings.php
// ========================================

session_start();

// Verificar se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

// Verificar se é participante
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'participante') {
    header("Location: organizer.php");
    exit;
}

$title = "Configurações - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Participante';
$userEmail = $_SESSION['user_email'] ?? '';
$userType = $_SESSION['user_type'] ?? 'participante';
$userPhoto = $_SESSION['user_photo'] ?? null;

// URLs
$dashboardUrl = 'participant.php';
$homeUrl = '../../index.php';

// Função para gerar URL da foto de perfil
function getProfilePhotoUrl($photoName) {
    if (!$photoName) return null;
    return 'https://conecta-eventos-production.up.railway.app/uploads/profiles/' . $photoName;
}

$profilePhotoUrl = getProfilePhotoUrl($userPhoto);

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
            
        case 'update_preferences':
            // Simular atualização de preferências de eventos
            $success_message = "Preferências de eventos atualizadas!";
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
    'data_nascimento' => '1990-01-01',
    'genero' => 'nao_informar',
    'cidade' => 'São Paulo',
    'estado' => 'SP',
    'bio' => 'Participante ativo de eventos de tecnologia e networking.',
    'profissao' => 'Desenvolvedor de Software',
    'empresa' => 'Tech Company',
    'linkedin' => 'https://linkedin.com/in/participante',
    'instagram' => '@participante'
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
            --primary-color: #28a745;
            --secondary-color: #17a2b8;
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
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
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
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .avatar-upload:hover {
            transform: translateY(-2px);
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
            overflow: hidden;
            position: relative;
            border: 3px solid #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
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

        .avatar.has-image {
            border-color: var(--success-color);
        }

        .photo-actions {
            text-align: center;
            margin-top: 1rem;
        }

        .photo-info {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .navbar-user-photo, .sidebar-user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            overflow: hidden;
        }

        .navbar-user-photo img, .sidebar-user-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-progress {
            margin-top: 1rem;
            display: none;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
        }
    </style>
</head>
<body data-user-name="<?php echo htmlspecialchars($userName); ?>">
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #28a745 0%, #17a2b8 100%);">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto d-flex align-items-center">
                <div class="d-flex align-items-center me-3">
                    <div class="navbar-user-photo me-2">
                        <?php if ($profilePhotoUrl): ?>
                            <img src="<?php echo $profilePhotoUrl; ?>" alt="Foto de Perfil">
                        <?php else: ?>
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <span class="navbar-text">
                        Olá, <?php echo htmlspecialchars($userName); ?>!
                    </span>
                </div>
                <a class="nav-link" href="<?php echo $dashboardUrl; ?>">Meu Painel</a>
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
                        <i class="fas fa-tachometer-alt me-1"></i>Meu Painel
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
                        <i class="fas fa-arrow-left me-2"></i>Voltar ao Painel
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
                                    id="preferences-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#preferences" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-heart me-2"></i>Preferências de Eventos
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
                                <p class="mb-0 text-muted">Atualize suas informações pessoais e foto de perfil</p>
                            </div>
                            <div class="settings-body">
                                <!-- Seção da Foto de Perfil -->
                                <div class="row mb-5">
                                    <div class="col-md-4 text-center">
                                        <div class="avatar-upload">
                                            <div class="avatar <?php echo $profilePhotoUrl ? 'has-image' : ''; ?>">
                                                <?php if ($profilePhotoUrl): ?>
                                                    <img src="<?php echo $profilePhotoUrl; ?>" alt="Foto de Perfil">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="avatar-overlay">
                                                <i class="fas fa-camera text-white fa-lg"></i>
                                            </div>
                                            <input type="file" id="avatar" name="avatar" style="display: none;" 
                                                   accept="image/*" data-max-size="2048">
                                        </div>
                                        
                                        <div class="photo-actions">
                                            <p class="text-muted small mb-2">
                                                Clique na foto para alterar<br>
                                                ou arraste uma imagem
                                            </p>
                                            
                                            <?php if ($profilePhotoUrl): ?>
                                                <button type="button" id="removePhotoBtn" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash me-1"></i>Remover Foto
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="photo-info">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Tamanho máximo:</small>
                                                <small><strong>2MB</strong></small>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Formatos aceitos:</small>
                                                <small><strong>JPG, PNG, GIF, WebP</strong></small>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small>Dimensão recomendada:</small>
                                                <small><strong>300x300px</strong></small>
                                            </div>
                                        </div>
                                        
                                        <div class="upload-progress">
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                     role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <small class="text-muted mt-1 d-block">Fazendo upload...</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <h5 class="mb-3">Personalize seu Perfil:</h5>
                                        <ul class="list-unstyled">
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Uma boa foto ajuda outros participantes a te reconhecerem
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Preencha sua biografia para se conectar melhor
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Adicione suas redes sociais para networking
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-info text-info me-2"></i>
                                                Suas informações podem ser vistas por organizadores
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <!-- Formulário de Dados Pessoais -->
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nome" class="form-label">Nome Completo</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?php echo htmlspecialchars($user_data['nome']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">E-mail</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="telefone" class="form-label">Telefone</label>
                                            <input type="tel" class="form-control" id="telefone" name="telefone" 
                                                   value="<?php echo htmlspecialchars($user_data['telefone']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" 
                                                   value="<?php echo htmlspecialchars($user_data['data_nascimento']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="genero" class="form-label">Gênero</label>
                                            <select class="form-select" id="genero" name="genero">
                                                <option value="nao_informar" <?php echo $user_data['genero'] === 'nao_informar' ? 'selected' : ''; ?>>Prefiro não informar</option>
                                                <option value="masculino" <?php echo $user_data['genero'] === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                                <option value="feminino" <?php echo $user_data['genero'] === 'feminino' ? 'selected' : ''; ?>>Feminino</option>
                                                <option value="outro" <?php echo $user_data['genero'] === 'outro' ? 'selected' : ''; ?>>Outro</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="cidade" class="form-label">Cidade</label>
                                            <input type="text" class="form-control" id="cidade" name="cidade" 
                                                   value="<?php echo htmlspecialchars($user_data['cidade']); ?>">
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select class="form-select" id="estado" name="estado">
                                                <option value="SP" <?php echo $user_data['estado'] === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                                                <option value="RJ" <?php echo $user_data['estado'] === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                                <option value="MG" <?php echo $user_data['estado'] === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                                                <option value="RS" <?php echo $user_data['estado'] === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="profissao" class="form-label">Profissão</label>
                                            <input type="text" class="form-control" id="profissao" name="profissao" 
                                                   value="<?php echo htmlspecialchars($user_data['profissao']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="empresa" class="form-label">Empresa</label>
                                            <input type="text" class="form-control" id="empresa" name="empresa" 
                                                   value="<?php echo htmlspecialchars($user_data['empresa']); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Biografia</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user_data['bio']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="linkedin" class="form-label">LinkedIn</label>
                                            <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                                   value="<?php echo htmlspecialchars($user_data['linkedin']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
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

                    <!-- Preferências de Eventos -->
                    <div class="tab-pane fade" id="preferences" role="tabpanel">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-heart me-2"></i>Preferências de Eventos
                                </h4>
                                <p class="mb-0 text-muted">Configure suas preferências para receber recomendações personalizadas</p>
                            </div>
                            <div class="settings-body">
                                <p>Configurações de preferências de eventos em desenvolvimento...</p>
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
                                <p>Funcionalidades de segurança em desenvolvimento...</p>
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
                                <p class="mb-0 text-muted">Escolha como deseja ser notificado sobre eventos</p>
                            </div>
                            <div class="settings-body">
                                <p>Configurações de notificação em desenvolvimento...</p>
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
                                <p>Configurações de privacidade em desenvolvimento...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript para Upload de Foto de Perfil -->
    <script src="../../public/js/profile-photo.js"></script>
    
    <!-- Outros scripts específicos da página -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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