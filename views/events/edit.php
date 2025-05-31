<?php
require_once '../../config/config.php';
require_once '../../includes/session.php';
require_once '../../controllers/EventController.php';

// Verificar se usuário está logado e é organizador
requireLogin();
if (!isOrganizer()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$eventController = new EventController();

// Verificar se ID foi fornecido
$eventId = $_GET['id'] ?? null;
if (!$eventId) {
    header('Location: list.php');
    exit();
}

// Buscar evento
$evento = $eventController->getById($eventId);
if (!$evento || !$eventController->canEdit($eventId)) {
    setFlashMessage('Evento não encontrado ou você não tem permissão para editá-lo.', 'danger');
    header('Location: list.php');
    exit();
}

$title = "Editar: " . $evento['titulo'] . " - " . SITE_NAME;
$categorias = $eventController->getCategories();

$error_message = '';
$success_message = '';

// Processar formulário
if ($_POST) {
    $result = $eventController->update($eventId, $_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        // Recarregar dados do evento
        $evento = $eventController->getById($eventId);
    } else {
        $error_message = $result['message'];
    }
}

// Preparar valores para o formulário
$defaults = [
    'titulo' => $evento['titulo'],
    'descricao' => $evento['descricao'],
    'id_categoria' => $evento['id_categoria'],
    'data_inicio' => date('Y-m-d', strtotime($evento['data_inicio'])),
    'data_fim' => date('Y-m-d', strtotime($evento['data_fim'])),
    'horario_inicio' => $evento['horario_inicio'],
    'horario_fim' => $evento['horario_fim'],
    'local_nome' => $evento['local_nome'],
    'local_endereco' => $evento['local_endereco'],
    'local_cidade' => $evento['local_cidade'],
    'local_estado' => $evento['local_estado'],
    'local_cep' => $evento['local_cep'],
    'capacidade_maxima' => $evento['capacidade_maxima'],
    'preco' => $evento['preco'],
    'evento_gratuito' => $evento['evento_gratuito'],
    'link_externo' => $evento['link_externo'],
    'requisitos' => $evento['requisitos'],
    'informacoes_adicionais' => $evento['informacoes_adicionais'],
    'status' => $evento['status'],
    'destaque' => $evento['destaque']
];

// Sobrescrever com dados do POST em caso de erro
if ($_POST && $error_message) {
    foreach ($defaults as $key => $value) {
        if (isset($_POST[$key])) {
            $defaults[$key] = $_POST[$key];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
        .form-section {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        .required { color: #dc3545; }
        .current-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 0.375rem;
            border: 1px solid #dee2e6;
        }
        .price-toggle {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            background: #f8f9fa;
        }
        .event-stats {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../../views/layouts/header.php'; ?>

    <div class="container my-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-edit me-2"></i>Editar Evento</h2>
                <p class="text-muted"><?php echo htmlspecialchars($evento['titulo']); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="view.php?id=<?php echo $eventId; ?>" class="btn btn-outline-info me-2">
                    <i class="fas fa-eye me-2"></i>Visualizar
                </a>
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>

        <!-- Estatísticas do Evento -->
        <div class="event-stats mb-4">
            <div class="row text-center">
                <div class="col-md-3">
                    <h5 class="mb-1"><?php echo $evento['total_inscritos'] ?? 0; ?></h5>
                    <small class="text-muted">Inscritos</small>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-1">
                        <span class="badge bg-<?php 
                            echo $evento['status'] === 'publicado' ? 'success' : 
                                ($evento['status'] === 'rascunho' ? 'secondary' : 'danger'); 
                        ?>">
                            <?php echo ucfirst($evento['status']); ?>
                        </span>
                    </h5>
                    <small class="text-muted">Status</small>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-1"><?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?></h5>
                    <small class="text-muted">Data do Evento</small>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-1"><?php echo $evento['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($evento['preco'], 2, ',', '.'); ?></h5>
                    <small class="text-muted">Preço</small>
                </div>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <!-- SEÇÃO 1: Informações Básicas -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-info-circle me-2"></i>Informações Básicas
                </h4>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">
                                Título do Evento <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="titulo" 
                                   name="titulo" 
                                   value="<?php echo htmlspecialchars($defaults['titulo']); ?>"
                                   required 
                                   maxlength="200">
                            <div class="invalid-feedback">
                                Por favor, insira o título do evento.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="id_categoria" class="form-label">Categoria</label>
                            <select class="form-select" id="id_categoria" name="id_categoria">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>"
                                            <?php echo $defaults['id_categoria'] == $categoria['id_categoria'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descricao" class="form-label">
                        Descrição <span class="required">*</span>
                    </label>
                    <textarea class="form-control" 
                              id="descricao" 
                              name="descricao" 
                              rows="4" 
                              required><?php echo htmlspecialchars($defaults['descricao']); ?></textarea>
                    <div class="invalid-feedback">
                        Por favor, insira a descrição do evento.
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="imagem_capa" class="form-label">Nova Imagem de Capa</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="imagem_capa" 
                                   name="imagem_capa" 
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">Formatos aceitos: JPG, PNG, GIF, WebP. Tamanho máximo: 5MB.</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <?php if (!empty($evento['imagem_capa'])): ?>
                            <label class="form-label">Imagem Atual</label>
                            <div>
                                <img src="<?php echo SITE_URL . '/public/uploads/eventos/' . $evento['imagem_capa']; ?>" 
                                     alt="Imagem atual" 
                                     class="current-image">
                            </div>
                        <?php else: ?>
                            <div class="text-muted">
                                <i class="fas fa-image fa-3x mb-2"></i>
                                <p>Nenhuma imagem definida</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 2: Data e Horário -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-calendar-alt me-2"></i>Data e Horário
                </h4>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="data_inicio" class="form-label">
                                Data de Início <span class="required">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="data_inicio" 
                                   name="data_inicio" 
                                   value="<?php echo $defaults['data_inicio']; ?>"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, selecione a data de início.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="data_fim" class="form-label">
                                Data de Fim <span class="required">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="data_fim" 
                                   name="data_fim" 
                                   value="<?php echo $defaults['data_fim']; ?>"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, selecione a data de fim.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="horario_inicio" class="form-label">
                                Horário de Início <span class="required">*</span>
                            </label>
                            <input type="time" 
                                   class="form-control" 
                                   id="horario_inicio" 
                                   name="horario_inicio" 
                                   value="<?php echo $defaults['horario_inicio']; ?>"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, informe o horário de início.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="horario_fim" class="form-label">
                                Horário de Fim <span class="required">*</span>
                            </label>
                            <input type="time" 
                                   class="form-control" 
                                   id="horario_fim" 
                                   name="horario_fim" 
                                   value="<?php echo $defaults['horario_fim']; ?>"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, informe o horário de fim.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 3: Local -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-map-marker-alt me-2"></i>Local do Evento
                </h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="local_nome" class="form-label">
                                Nome do Local <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="local_nome" 
                                   name="local_nome" 
                                   value="<?php echo htmlspecialchars($defaults['local_nome']); ?>"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, informe o nome do local.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="local_endereco" class="form-label">
                                Endereço <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="local_endereco" 
                                   name="local_endereco" 
                                   value="<?php echo htmlspecialchars($defaults['local_endereco']); ?>"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, informe o endereço.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="local_cidade" class="form-label">
                                Cidade <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="local_cidade" 
                                   name="local_cidade" 
                                   value="<?php echo htmlspecialchars($defaults['local_cidade']); ?>"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, informe a cidade.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="local_estado" class="form-label">
                                Estado <span class="required">*</span>
                            </label>
                            <select class="form-select" id="local_estado" name="local_estado" required>
                                <option value="">Selecione</option>
                                <?php
                                $estados = [
                                    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                    'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                    'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                    'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                    'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                    'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                    'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                ];
                                foreach ($estados as $sigla => $nome): ?>
                                    <option value="<?php echo $sigla; ?>" <?php echo $defaults['local_estado'] === $sigla ? 'selected' : ''; ?>>
                                        <?php echo $nome; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione o estado.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="local_cep" class="form-label">CEP</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="local_cep" 
                                   name="local_cep" 
                                   value="<?php echo htmlspecialchars($defaults['local_cep']); ?>"
                                   placeholder="00000-000">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 4: Configurações -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-cog me-2"></i>Configurações do Evento
                </h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="capacidade_maxima" class="form-label">Capacidade Máxima</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="capacidade_maxima" 
                                   name="capacidade_maxima" 
                                   value="<?php echo $defaults['capacidade_maxima']; ?>"
                                   min="1">
                            <div class="form-text">Deixe em branco para capacidade ilimitada</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="price-toggle">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="evento_gratuito" 
                                       name="evento_gratuito"
                                       <?php echo $defaults['evento_gratuito'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="evento_gratuito">
                                    <strong>Evento Gratuito</strong>
                                </label>
                            </div>
                            
                            <div id="preco_section" style="display: <?php echo $defaults['evento_gratuito'] ? 'none' : 'block'; ?>;">
                                <label for="preco" class="form-label">Preço (R$)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="preco" 
                                       name="preco" 
                                       value="<?php echo $defaults['preco']; ?>"
                                       min="0" 
                                       step="0.01">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="link_externo" class="form-label">Link Externo</label>
                            <input type="url" 
                                   class="form-control" 
                                   id="link_externo" 
                                   name="link_externo" 
                                   value="<?php echo htmlspecialchars($defaults['link_externo']); ?>"
                                   placeholder="https://...">
                            <div class="form-text">Site, redes sociais ou informações adicionais</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="rascunho" <?php echo $defaults['status'] === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                                <option value="publicado" <?php echo $defaults['status'] === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                                <option value="cancelado" <?php echo $defaults['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                <option value="finalizado" <?php echo $defaults['status'] === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="destaque" 
                               name="destaque"
                               <?php echo $defaults['destaque'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="destaque">
                            <strong>Marcar como destaque</strong>
                            <br><small class="text-muted">Eventos em destaque aparecem primeiro nas listagens</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 5: Informações Adicionais -->
            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-info me-2"></i>Informações Adicionais
                </h4>
                
                <div class="mb-3">
                    <label for="requisitos" class="form-label">Requisitos</label>
                    <textarea class="form-control" 
                              id="requisitos" 
                              name="requisitos" 
                              rows="3"
                              placeholder="Ex: Trazer documento de identidade, ter mais de 18 anos..."><?php echo htmlspecialchars($defaults['requisitos']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="informacoes_adicionais" class="form-label">Informações Adicionais</label>
                    <textarea class="form-control" 
                              id="informacoes_adicionais" 
                              name="informacoes_adicionais" 
                              rows="3"
                              placeholder="Qualquer informação extra que os participantes devem saber..."><?php echo htmlspecialchars($defaults['informacoes_adicionais']); ?></textarea>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between">
                <a href="list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
                
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php include '../../views/layouts/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação de formulário
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                const forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Toggle preço quando evento gratuito
        document.getElementById('evento_gratuito').addEventListener('change', function() {
            const precoSection = document.getElementById('preco_section');
            const precoInput = document.getElementById('preco');
            
            if (this.checked) {
                precoSection.style.display = 'none';
                precoInput.value = '';
            } else {
                precoSection.style.display = 'block';
            }
        });

        // Sincronizar data fim com data início
        document.getElementById('data_inicio').addEventListener('change', function() {
            const dataFim = document.getElementById('data_fim');
            if (!dataFim.value || dataFim.value < this.value) {
                dataFim.value = this.value;
            }
            dataFim.min = this.value;
        });

        // Máscara CEP
        document.getElementById('local_cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 5) {
                value = value.replace(/^(\d{5})(\d{1,3})/, '$1-$2');
            }
            e.target.value = value;
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>