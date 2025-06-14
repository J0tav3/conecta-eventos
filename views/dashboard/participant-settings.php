<?php
// ========================================
// PÁGINA DE CONFIGURAÇÕES DO PARTICIPANTE
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

// URLs
$dashboardUrl = 'participant.php';
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

// Configurações de notificação específicas para participantes
$notification_settings = [
    'email_novos_eventos' => true,
    'email_eventos_favoritos' => true,
    'email_lembretes' => true,
    'email_promocoes' => false,
    'sms_lembretes' => true,
    'push_notifications' => true
];

// Configurações de privacidade
$privacy_settings = [
    'perfil_publico' => true,
    'mostrar_email' => false,
    'mostrar_telefone' => false,
    'mostrar_participacoes' => true,
    'receber_contato_organizadores' => true
];

// Preferências de eventos
$event_preferences = [
    'categorias_interesse' => ['Tecnologia', 'Design'],
    'formato_preferido' => 'presencial',
    'faixa_preco' => 'ate_100',
    'horario_preferido' => 'noite',
    'distancia_maxima' => '50',
    'idioma_preferido' => 'portugues'
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

        .category-tag {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            margin: 0.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-tag:hover {
            background: var(--secondary-color);
            transform: scale(1.05);
        }

        .category-tag.selected {
            background: var(--secondary-color);
            box-shadow: 0 2px 10px rgba(23, 162, 184, 0.3);
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

        .preferences-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }

        .preference-item {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 0.5rem 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .preference-item:hover {
            border-color: var(--primary-color);
            background: rgba(40, 167, 69, 0.05);
        }

        .preference-item.selected {
            border-color: var(--primary-color);
            background: rgba(40, 167, 69, 0.1);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #28a745 0%, #17a2b8 100%);">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Olá, <?php echo htmlspecialchars($userName); ?>!
                </span>
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
                                                <!-- Adicionar outros estados conforme necessário -->
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
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_preferences">
                                    
                                    <!-- Categorias de Interesse -->
                                    <div class="preferences-section">
                                        <h5 class="mb-3">Categorias de Interesse</h5>
                                        <p class="text-muted mb-3">Selecione as categorias de eventos que mais te interessam:</p>
                                        <div class="row">
                                            <?php
                                            $categorias = ['Tecnologia', 'Design', 'Negócios', 'Marketing', 'Educação', 
                                                         'Arte e Cultura', 'Esportes', 'Música', 'Culinária', 'Saúde'];
                                            foreach ($categorias as $categoria):
                                            ?>
                                                <div class="col-md-6 col-lg-4 mb-2">
                                                    <div class="preference-item <?php echo in_array($categoria, $event_preferences['categorias_interesse']) ? 'selected' : ''; ?>" 
                                                         onclick="toggleCategory(this, '<?php echo $categoria; ?>')">
                                                        <i class="fas fa-tag me-2"></i>
                                                        <?php echo $categoria; ?>
                                                        <input type="checkbox" name="categorias_interesse[]" 
                                                               value="<?php echo $categoria; ?>" 
                                                               style="display: none;"
                                                               <?php echo in_array($categoria, $event_preferences['categorias_interesse']) ? 'checked' : ''; ?>>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Formato Preferido -->
                                    <div class="preferences-section">
                                        <h5 class="mb-3">Formato Preferido</h5>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['formato_preferido'] === 'presencial' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'formato_preferido', 'presencial')">
                                                    <i class="fas fa-users me-2"></i>
                                                    Presencial
                                                    <input type="radio" name="formato_preferido" value="presencial" style="display: none;"
                                                           <?php echo $event_preferences['formato_preferido'] === 'presencial' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['formato_preferido'] === 'online' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'formato_preferido', 'online')">
                                                    <i class="fas fa-laptop me-2"></i>
                                                    Online
                                                    <input type="radio" name="formato_preferido" value="online" style="display: none;"
                                                           <?php echo $event_preferences['formato_preferido'] === 'online' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['formato_preferido'] === 'hibrido' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'formato_preferido', 'hibrido')">
                                                    <i class="fas fa-globe me-2"></i>
                                                    Híbrido
                                                    <input type="radio" name="formato_preferido" value="hibrido" style="display: none;"
                                                           <?php echo $event_preferences['formato_preferido'] === 'hibrido' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Faixa de Preço -->
                                    <div class="preferences-section">
                                        <h5 class="mb-3">Faixa de Preço Preferida</h5>
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['faixa_preco'] === 'gratuito' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'faixa_preco', 'gratuito')">
                                                    <i class="fas fa-gift me-2"></i>
                                                    Gratuito
                                                    <input type="radio" name="faixa_preco" value="gratuito" style="display: none;"
                                                           <?php echo $event_preferences['faixa_preco'] === 'gratuito' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['faixa_preco'] === 'ate_50' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'faixa_preco', 'ate_50')">
                                                    <i class="fas fa-dollar-sign me-2"></i>
                                                    Até R$ 50
                                                    <input type="radio" name="faixa_preco" value="ate_50" style="display: none;"
                                                           <?php echo $event_preferences['faixa_preco'] === 'ate_50' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['faixa_preco'] === 'ate_100' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'faixa_preco', 'ate_100')">
                                                    <i class="fas fa-coins me-2"></i>
                                                    Até R$ 100
                                                    <input type="radio" name="faixa_preco" value="ate_100" style="display: none;"
                                                           <?php echo $event_preferences['faixa_preco'] === 'ate_100' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['faixa_preco'] === 'acima_100' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'faixa_preco', 'acima_100')">
                                                    <i class="fas fa-credit-card me-2"></i>
                                                    Acima de R$ 100
                                                    <input type="radio" name="faixa_preco" value="acima_100" style="display: none;"
                                                           <?php echo $event_preferences['faixa_preco'] === 'acima_100' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Horário Preferido -->
                                    <div class="preferences-section">
                                        <h5 class="mb-3">Horário Preferido</h5>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['horario_preferido'] === 'manha' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'horario_preferido', 'manha')">
                                                    <i class="fas fa-sun me-2"></i>
                                                    Manhã (8h-12h)
                                                    <input type="radio" name="horario_preferido" value="manha" style="display: none;"
                                                           <?php echo $event_preferences['horario_preferido'] === 'manha' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['horario_preferido'] === 'tarde' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'horario_preferido', 'tarde')">
                                                    <i class="fas fa-cloud-sun me-2"></i>
                                                    Tarde (12h-18h)
                                                    <input type="radio" name="horario_preferido" value="tarde" style="display: none;"
                                                           <?php echo $event_preferences['horario_preferido'] === 'tarde' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <div class="preference-item <?php echo $event_preferences['horario_preferido'] === 'noite' ? 'selected' : ''; ?>" 
                                                     onclick="selectOption(this, 'horario_preferido', 'noite')">
                                                    <i class="fas fa-moon me-2"></i>
                                                    Noite (18h-22h)
                                                    <input type="radio" name="horario_preferido" value="noite" style="display: none;"
                                                           <?php echo $event_preferences['horario_preferido'] === 'noite' ? 'checked' : ''; ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Distância Máxima -->
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="distancia_maxima" class="form-label">Distância Máxima (km)</label>
                                            <select class="form-select" id="distancia_maxima" name="distancia_maxima">
                                                <option value="10" <?php echo $event_preferences['distancia_maxima'] === '10' ? 'selected' : ''; ?>>Até 10 km</option>
                                                <option value="25" <?php echo $event_preferences['distancia_maxima'] === '25' ? 'selected' : ''; ?>>Até 25 km</option>
                                                <option value="50" <?php echo $event_preferences['distancia_maxima'] === '50' ? 'selected' : ''; ?>>Até 50 km</option>
                                                <option value="100" <?php echo $event_preferences['distancia_maxima'] === '100' ? 'selected' : ''; ?>>Até 100 km</option>
                                                <option value="sem_limite" <?php echo $event_preferences['distancia_maxima'] === 'sem_limite' ? 'selected' : ''; ?>>Sem limite</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="idioma_preferido" class="form-label">Idioma Preferido</label>
                                            <select class="form-select" id="idioma_preferido" name="idioma_preferido">
                                                <option value="portugues" <?php echo $event_preferences['idioma_preferido'] === 'portugues' ? 'selected' : ''; ?>>Português</option>
                                                <option value="ingles" <?php echo $event_preferences['idioma_preferido'] === 'ingles' ? 'selected' : ''; ?>>Inglês</option>
                                                <option value="espanhol" <?php echo $event_preferences['idioma_preferido'] === 'espanhol' ? 'selected' : ''; ?>>Espanhol</option>
                                                <option value="qualquer" <?php echo $event_preferences['idioma_preferido'] === 'qualquer' ? 'selected' : ''; ?>>Qualquer idioma</option>
                                            </select>
                                        </div>
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
                                
                                <hr>
                                
                                <!-- Sessões Ativas -->
                                <h5 class="mb-3">Sessões Ativas</h5>
                                <div class="list-group">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Navegador Atual</h6>
                                            <p class="mb-1 text-muted">Chrome on Windows</p>
                                            <small class="text-success">Ativo agora</small>
                                        </div>
                                        <span class="badge bg-success">Atual</span>
                                    </div>
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
                                <p class="mb-0 text-muted">Escolha como deseja ser notificado sobre eventos</p>
                            </div>
                            <div class="settings-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_notifications">
                                    
                                    <h5 class="mb-3">E-mail</h5>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Novos eventos nas suas categorias de interesse</p>
                                            <small class="text-muted">Receba notificações quando novos eventos forem publicados</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="email_novos_eventos" 
                                                   <?php echo $notification_settings['email_novos_eventos'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Atualizações dos eventos favoritos</p>
                                            <small class="text-muted">Mudanças em eventos que você favoritou</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="email_eventos_favoritos" 
                                                   <?php echo $notification_settings['email_eventos_favoritos'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Lembretes de eventos próximos</p>
                                            <small class="text-muted">Lembrete 24h antes do evento</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="email_lembretes" 
                                                   <?php echo $notification_settings['email_lembretes'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <p class="mb-0">Promoções e eventos especiais</p>
                                            <small class="text-muted">Ofertas especiais e eventos promocionais</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="email_promocoes" 
                                                   <?php echo $notification_settings['email_promocoes'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h5 class="mb-3">SMS e Push</h5>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Lembretes por SMS</p>
                                            <small class="text-muted">SMS 2h antes do início do evento</small>
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
                                            <small class="text-muted">Permitir que outros participantes vejam seu perfil</small>
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
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <p class="mb-0">Mostrar Participações</p>
                                            <small class="text-muted">Exibir eventos que você participou no seu perfil</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="mostrar_participacoes" 
                                                   <?php echo $privacy_settings['mostrar_participacoes'] ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <p class="mb-0">Receber Contato de Organizadores</p>
                                            <small class="text-muted">Permitir que organizadores entrem em contato</small>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" name="receber_contato_organizadores" 
                                                   <?php echo $privacy_settings['receber_contato_organizadores'] ? 'checked' : ''; ?>>
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
                                    <h5 class="text-info">Exportar Dados</h5>
                                    <p class="mb-3">Baixe uma cópia de todos os seus dados na plataforma, incluindo inscrições, favoritos e histórico de participações.</p>
                                    <button class="btn btn-outline-info" onclick="exportData()">
                                        <i class="fas fa-download me-2"></i>Exportar Meus Dados
                                    </button>
                                </div>
                                
                                <!-- Cancelar Todas as Inscrições -->
                                <div class="danger-zone mb-4">
                                    <h5 class="text-warning">Cancelar Todas as Inscrições</h5>
                                    <p class="mb-3">Cancela todas as suas inscrições em eventos futuros. Esta ação pode afetar organizadores e outros participantes.</p>
                                    <button class="btn btn-warning" onclick="cancelAllSubscriptions()">
                                        <i class="fas fa-calendar-times me-2"></i>Cancelar Todas as Inscrições
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
                                        <strong>ATENÇÃO:</strong> Esta ação é irreversível. Todos os seus dados, inscrições, favoritos e histórico serão permanentemente removidos.
                                    </p>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirmDelete">
                                        <label class="form-check-label" for="confirmDelete">
                                            Eu entendo que esta ação é irreversível e cancelará todas as minhas inscrições
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
                        showToast('Imagem selecionada! Funcionalidade de upload em desenvolvimento.', 'info');
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
        
        // Função para toggle de categorias
        function toggleCategory(element, category) {
            element.classList.toggle('selected');
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = element.classList.contains('selected');
        }
        
        // Função para seleção única
        function selectOption(element, name, value) {
            // Remove seleção de todos os elementos do mesmo grupo
            const group = document.querySelectorAll(`input[name="${name}"]`);
            group.forEach(input => {
                input.closest('.preference-item').classList.remove('selected');
                input.checked = false;
            });
            
            // Adiciona seleção ao elemento clicado
            element.classList.add('selected');
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
        }
        
        // Função para exportar dados
        function exportData() {
            if (confirm('Deseja exportar todos os seus dados? Você receberá um link para download por email.')) {
                showToast('Solicitação de exportação enviada! Você receberá um email em breve.', 'info');
            }
        }
        
        // Função para cancelar todas as inscrições
        function cancelAllSubscriptions() {
            if (confirm('Tem certeza que deseja cancelar TODAS as suas inscrições em eventos futuros? Esta ação pode afetar organizadores e outros participantes.')) {
                if (confirm('CONFIRMAÇÃO FINAL: Esta ação cancelará todas as suas inscrições futuras. Continuar?')) {
                    showToast('Funcionalidade em desenvolvimento', 'warning');
                }
            }
        }
        
        // Função para desativar conta
        function deactivateAccount() {
            if (confirm('Tem certeza que deseja desativar sua conta? Suas inscrições em eventos futuros serão mantidas, mas você não receberá notificações.')) {
                showToast('Funcionalidade em desenvolvimento', 'warning');
            }
        }
        
        // Função para excluir conta
        function deleteAccount() {
            const confirmText = prompt('Para confirmar a exclusão, digite "EXCLUIR" em letras maiúsculas:');
            if (confirmText === 'EXCLUIR') {
                if (confirm('ÚLTIMA CONFIRMAÇÃO: Tem certeza absoluta que deseja excluir permanentemente sua conta? Todas as suas inscrições serão canceladas.')) {
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
        
        // Feedback visual ao salvar preferências
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = form.querySelector('input[name="action"]').value;
                
                if (action === 'update_preferences') {
                    e.preventDefault();
                    
                    // Simular salvamento
                    setTimeout(() => {
                        showToast('Preferências salvas! Você receberá recomendações personalizadas baseadas nas suas escolhas.', 'success');
                    }, 1000);
                }
            });
        });
        
        // Inicializar tooltips para elementos que precisam de explicação
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Animação de entrada das seções de preferências
        const preferencesSections = document.querySelectorAll('.preferences-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease-out';
                }
            });
        });
        
        preferencesSections.forEach(section => {
            observer.observe(section);
        });
        
        // Adicionar animação CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .preference-item {
                transform: scale(1);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .preference-item:hover {
                transform: scale(1.02);
                box-shadow: 0 4px 20px rgba(40, 167, 69, 0.15);
            }
            
            .preference-item.selected {
                animation: pulse 0.6s ease-in-out;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            .settings-card {
                animation: slideInLeft 0.5s ease-out;
            }
            
            @keyframes slideInLeft {
                from {
                    opacity: 0;
                    transform: translateX(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>