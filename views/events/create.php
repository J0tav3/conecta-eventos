<?php
// ==========================================
// CRIAR NOVO EVENTO
// Local: views/events/create.php
// ==========================================

session_start();

// Verificar se está logado e é organizador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
    header("Location: ../dashboard/participant.php");
    exit;
}

$title = "Criar Evento - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Organizador';

// URLs
$dashboardUrl = '../dashboard/organizer.php';
$homeUrl = '../../index.php';

$success_message = '';
$error_message = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simular criação do evento
    $titulo = trim($_POST['titulo'] ?? '');
    
    if (!empty($titulo)) {
        $success_message = "Evento '$titulo' criado com sucesso! Você pode visualizá-lo em 'Meus Eventos'.";
    } else {
        $error_message = "Por favor, preencha pelo menos o título do evento.";
    }
}

// Categorias disponíveis
$categorias = [
    1 => 'Tecnologia',
    2 => 'Negócios',
    3 => 'Marketing',
    4 => 'Design',
    5 => 'Educação',
    6 => 'Entretenimento',
    7 => 'Saúde',
    8 => 'Esportes',
    9 => 'Arte & Cultura',
    10 => 'Sustentabilidade'
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
        body {
            background-color: #f8f9fa;
        }
        
        .create-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .form-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .form-section h4 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 0.5rem;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .btn-outline-secondary {
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            background-color: #f8f9fa;
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
                <li class="breadcrumb-item active" aria-current="page">Criar Evento</li>
            </ol>
        </nav>
    </div>

    <!-- Header da Página -->
    <section class="create-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-plus-circle me-2"></i>Criar Novo Evento</h1>
                    <p class="mb-0 fs-5">Organize experiências incríveis para sua audiência</p>
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

        <form method="POST" enctype="multipart/form-data" id="createEventForm">
            <!-- Informações Básicas -->
            <div class="form-section">
                <h4><i class="fas fa-info-circle me-2"></i>Informações Básicas</h4>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="titulo" class="form-label">Título do Evento *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required
                               value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>"
                               placeholder="Ex: Workshop de Desenvolvimento Web">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="categoria" class="form-label">Categoria *</label>
                        <select class="form-select" id="categoria" name="categoria" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $id => $nome): ?>
                                <option value="<?php echo $id; ?>" 
                                        <?php echo ($_POST['categoria'] ?? '') == $id ? 'selected' : ''; ?>>
                                    <?php echo $nome; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição Curta *</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3" required
                              placeholder="Descrição resumida do evento (máximo 200 caracteres)"><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                    <div class="form-text">Máximo 200 caracteres</div>
                </div>
                
                <div class="mb-3">
                    <label for="descricao_detalhada" class="form-label">Descrição Detalhada</label>
                    <textarea class="form-control" id="descricao_detalhada" name="descricao_detalhada" rows="5"
                              placeholder="Descrição completa do evento, programação, palestrantes, etc."><?php echo htmlspecialchars($_POST['descricao_detalhada'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Data e Horário -->
            <div class="form-section">
                <h4><i class="fas fa-calendar-alt me-2"></i>Data e Horário</h4>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="data_inicio" class="form-label">Data de Início *</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" required
                               value="<?php echo $_POST['data_inicio'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="horario_inicio" class="form-label">Horário de Início *</label>
                        <input type="time" class="form-control" id="horario_inicio" name="horario_inicio" required
                               value="<?php echo $_POST['horario_inicio'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="data_fim" class="form-label">Data de Fim</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim"
                               value="<?php echo $_POST['data_fim'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="horario_fim" class="form-label">Horário de Fim</label>
                        <input type="time" class="form-control" id="horario_fim" name="horario_fim"
                               value="<?php echo $_POST['horario_fim'] ?? ''; ?>">
                    </div>
                </div>
            </div>

            <!-- Local -->
            <div class="form-section">
                <h4><i class="fas fa-map-marker-alt me-2"></i>Local do Evento</h4>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="local_nome" class="form-label">Nome do Local *</label>
                        <input type="text" class="form-control" id="local_nome" name="local_nome" required
                               value="<?php echo htmlspecialchars($_POST['local_nome'] ?? ''); ?>"
                               placeholder="Ex: Centro de Convenções SP">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="local_endereco" class="form-label">Endereço Completo *</label>
                        <input type="text" class="form-control" id="local_endereco" name="local_endereco" required
                               value="<?php echo htmlspecialchars($_POST['local_endereco'] ?? ''); ?>"
                               placeholder="Rua, número, bairro">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="local_cidade" class="form-label">Cidade *</label>
                        <input type="text" class="form-control" id="local_cidade" name="local_cidade" required
                               value="<?php echo htmlspecialchars($_POST['local_cidade'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="local_estado" class="form-label">Estado *</label>
                        <select class="form-select" id="local_estado" name="local_estado" required>
                            <option value="">Selecione</option>
                            <option value="AC" <?php echo ($_POST['local_estado'] ?? '') === 'AC' ? 'selected' : ''; ?>>Acre</option>
                            <option value="AL" <?php echo ($_POST['local_estado'] ?? '') === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                            <option value="AP" <?php echo ($_POST['local_estado'] ?? '') === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                            <option value="AM" <?php echo ($_POST['local_estado'] ?? '') === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                            <option value="BA" <?php echo ($_POST['local_estado'] ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                            <option value="CE" <?php echo ($_POST['local_estado'] ?? '') === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                            <option value="DF" <?php echo ($_POST['local_estado'] ?? '') === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                            <option value="ES" <?php echo ($_POST['local_estado'] ?? '') === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                            <option value="GO" <?php echo ($_POST['local_estado'] ?? '') === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                            <option value="MA" <?php echo ($_POST['local_estado'] ?? '') === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                            <option value="MT" <?php echo ($_POST['local_estado'] ?? '') === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                            <option value="MS" <?php echo ($_POST['local_estado'] ?? '') === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                            <option value="MG" <?php echo ($_POST['local_estado'] ?? '') === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                            <option value="PA" <?php echo ($_POST['local_estado'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pará</option>
                            <option value="PB" <?php echo ($_POST['local_estado'] ?? '') === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                            <option value="PR" <?php echo ($_POST['local_estado'] ?? '') === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                            <option value="PE" <?php echo ($_POST['local_estado'] ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                            <option value="PI" <?php echo ($_POST['local_estado'] ?? '') === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                            <option value="RJ" <?php echo ($_POST['local_estado'] ?? '') === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                            <option value="RN" <?php echo ($_POST['local_estado'] ?? '') === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                            <option value="RS" <?php echo ($_POST['local_estado'] ?? '') === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                            <option value="RO" <?php echo ($_POST['local_estado'] ?? '') === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                            <option value="RR" <?php echo ($_POST['local_estado'] ?? '') === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                            <option value="SC" <?php echo ($_POST['local_estado'] ?? '') === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                            <option value="SP" <?php echo ($_POST['local_estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                            <option value="SE" <?php echo ($_POST['local_estado'] ?? '') === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                            <option value="TO" <?php echo ($_POST['local_estado'] ?? '') === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="local_cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="local_cep" name="local_cep"
                               value="<?php echo htmlspecialchars($_POST['local_cep'] ?? ''); ?>"
                               placeholder="00000-000">
                    </div>
                </div>
            </div>

            <!-- Preço e Vagas -->
            <div class="form-section">
                <h4><i class="fas fa-ticket-alt me-2"></i>Preço e Vagas</h4>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="evento_gratuito" name="evento_gratuito"
                                   <?php echo ($_POST['evento_gratuito'] ?? '') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="evento_gratuito">
                                <strong>Evento Gratuito</strong>
                            </label>
                        </div>
                        
                        <div id="preco_section" style="display: <?php echo ($_POST['evento_gratuito'] ?? '') ? 'none' : 'block'; ?>;">
                            <label for="preco" class="form-label">Preço por Pessoa</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control" id="preco" name="preco" 
                                       min="0" step="0.01" value="<?php echo $_POST['preco'] ?? ''; ?>"
                                       placeholder="0,00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="max_participantes" class="form-label">Máximo de Participantes</label>
                        <input type="number" class="form-control" id="max_participantes" name="max_participantes"
                               min="1" value="<?php echo $_POST['max_participantes'] ?? ''; ?>"
                               placeholder="Ex: 100">
                        <div class="form-text">Deixe em branco para ilimitado</div>
                    </div>
                </div>
            </div>

            <!-- Imagem -->
            <div class="form-section">
                <h4><i class="fas fa-image me-2"></i>Imagem do Evento</h4>
                
                <div class="upload-area" onclick="document.getElementById('imagem_capa').click();">
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                    <h5>Clique para enviar uma imagem</h5>
                    <p class="text-muted">JPG, PNG ou GIF até 5MB</p>
                    <input type="file" id="imagem_capa" name="imagem_capa" 
                           accept="image/*" style="display: none;">
                </div>
            </div>

            <!-- Informações Adicionais -->
            <div class="form-section">
                <h4><i class="fas fa-list-ul me-2"></i>Informações Adicionais</h4>
                
                <div class="mb-3">
                    <label for="requisitos" class="form-label">Requisitos para Participar</label>
                    <textarea class="form-control" id="requisitos" name="requisitos" rows="3"
                              placeholder="Ex: Conhecimento básico de programação, laptop próprio"><?php echo htmlspecialchars($_POST['requisitos'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="o_que_levar" class="form-label">O que o Participante Deve Levar</label>
                    <textarea class="form-control" id="o_que_levar" name="o_que_levar" rows="3"
                              placeholder="Ex: Notebook, carregador, bloco de notas"><?php echo htmlspecialchars($_POST['o_que_levar'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Botões -->
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-save me-2"></i>Criar Evento
                </button>
                <a href="<?php echo $dashboardUrl; ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>Cancelar
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle preço baseado em evento gratuito
            const eventoGratuito = document.getElementById('evento_gratuito');
            const precoSection = document.getElementById('preco_section');
            
            eventoGratuito.addEventListener('change', function() {
                precoSection.style.display = this.checked ? 'none' : 'block';
                if (this.checked) {
                    document.getElementById('preco').value = '';
                }
            });

            // Máscara de CEP
            const cepInput = document.getElementById('local_cep');
            cepInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            });

            // Preview da imagem
            const imagemInput = document.getElementById('imagem_capa');
            const uploadArea = document.querySelector('.upload-area');
            
            imagemInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    uploadArea.innerHTML = `
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>Imagem selecionada!</h5>
                        <p class="text-muted">${file.name}</p>
                    `;
                }
            });

            // Validação da data
            const dataInicio = document.getElementById('data_inicio');
            const hoje = new Date().toISOString().split('T')[0];
            dataInicio.min = hoje;

            dataInicio.addEventListener('change', function() {
                const dataFim = document.getElementById('data_fim');
                dataFim.min = this.value;
            });

            // Loading no submit
            const form = document.getElementById('createEventForm');
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando evento...';
            });

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
        });
    </script>
</body>
</html>