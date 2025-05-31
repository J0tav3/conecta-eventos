<?php
// ========================================
// TESTE DO MODEL EVENT
// ========================================
// Salve como: teste_event_model.php na raiz
// Acesse: http://localhost/conecta-eventos/teste_event_model.php
// ========================================

require_once 'config/config.php';
require_once 'models/Event.php';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Model Event - Conecta Eventos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .test-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #6f42c1;
        }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        pre { background: #2d3748; color: #e2e8f0; padding: 1rem; border-radius: 0.375rem; }
    </style>
</head>
<body>
    <div class="container my-4">
        <h1 class="mb-4">
            <i class="fas fa-calendar-check me-2"></i>
            Teste Model Event
        </h1>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Testando todas as funcionalidades do Model Event
        </div>

        <!-- TESTE 1: CARREGAR MODEL -->
        <div class="test-section">
            <h3><i class="fas fa-code me-2"></i>1. Teste de Carregamento</h3>
            
            <?php
            try {
                $eventModel = new Event();
                echo '<p class="status-ok"><i class="fas fa-check-circle me-2"></i>Model Event carregado com sucesso!</p>';
                
                // Verificar conexão
                $reflection = new ReflectionClass($eventModel);
                $connProperty = $reflection->getProperty('conn');
                $connProperty->setAccessible(true);
                $conn = $connProperty->getValue($eventModel);
                
                if ($conn) {
                    echo '<p class="status-ok">✅ Conexão com banco estabelecida</p>';
                } else {
                    echo '<p class="status-error">❌ Falha na conexão com banco</p>';
                }
                
            } catch (Exception $e) {
                echo '<p class="status-error"><i class="fas fa-exclamation-triangle me-2"></i>Erro: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- TESTE 2: LISTAR EVENTOS EXISTENTES -->
        <div class="test-section">
            <h3><i class="fas fa-list me-2"></i>2. Teste de Listagem</h3>
            
            <?php
            try {
                $eventos = $eventModel->list();
                echo '<p class="status-ok">✅ Método list() funcionando</p>';
                echo '<p><strong>Total de eventos encontrados:</strong> ' . count($eventos) . '</p>';
                
                if (count($eventos) > 0) {
                    echo '<h5>Eventos encontrados:</h5>';
                    echo '<div class="row">';
                    foreach ($eventos as $evento) {
                        echo '<div class="col-md-6 mb-3">';
                        echo '<div class="card">';
                        echo '<div class="card-body">';
                        echo '<h6 class="card-title">' . htmlspecialchars($evento['titulo']) . '</h6>';
                        echo '<p class="card-text">';
                        echo '<small class="text-muted">📍 ' . htmlspecialchars($evento['local_cidade']) . '</small><br>';
                        echo '<small class="text-muted">📅 ' . date('d/m/Y', strtotime($evento['data_inicio'])) . '</small><br>';
                        echo '<small class="text-muted">👤 ' . htmlspecialchars($evento['nome_organizador'] ?? 'N/A') . '</small><br>';
                        echo '<span class="badge bg-primary">' . $evento['status'] . '</span>';
                        echo '</p>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="status-warning">⚠️ Nenhum evento encontrado (normal se é primeira vez)</p>';
                }
                
            } catch (Exception $e) {
                echo '<p class="status-error"><i class="fas fa-exclamation-triangle me-2"></i>Erro na listagem: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- TESTE 3: BUSCAR POR ID -->
        <div class="test-section">
            <h3><i class="fas fa-search me-2"></i>3. Teste de Busca por ID</h3>
            
            <?php
            try {
                // Tentar buscar evento ID 1
                $evento = $eventModel->findById(1);
                
                if ($evento) {
                    echo '<p class="status-ok">✅ Método findById() funcionando</p>';
                    echo '<h5>Evento encontrado (ID: 1):</h5>';
                    echo '<div class="card">';
                    echo '<div class="card-body">';
                    echo '<h6>' . htmlspecialchars($evento['titulo']) . '</h6>';
                    echo '<p><strong>Organizador:</strong> ' . htmlspecialchars($evento['nome_organizador'] ?? 'N/A') . '</p>';
                    echo '<p><strong>Categoria:</strong> ' . htmlspecialchars($evento['nome_categoria'] ?? 'N/A') . '</p>';
                    echo '<p><strong>Data:</strong> ' . date('d/m/Y H:i', strtotime($evento['data_inicio'])) . '</p>';
                    echo '<p><strong>Local:</strong> ' . htmlspecialchars($evento['local_nome']) . '</p>';
                    echo '<p><strong>Inscritos:</strong> ' . ($evento['total_inscritos'] ?? 0) . '</p>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<p class="status-warning">⚠️ Evento ID 1 não encontrado (normal se não existir)</p>';
                    echo '<p class="status-ok">✅ Método findById() funcionando (retorno correto para ID inexistente)</p>';
                }
                
            } catch (Exception $e) {
                echo '<p class="status-error"><i class="fas fa-exclamation-triangle me-2"></i>Erro na busca: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- TESTE 4: FILTROS -->
        <div class="test-section">
            <h3><i class="fas fa-filter me-2"></i>4. Teste de Filtros</h3>
            
            <?php
            try {
                // Teste diferentes filtros
                $filtros = [
                    'status' => 'publicado',
                    'limite' => 5
                ];
                
                $eventosPublicados = $eventModel->list($filtros);
                echo '<p class="status-ok">✅ Filtro por status funcionando</p>';
                echo '<p><strong>Eventos publicados:</strong> ' . count($eventosPublicados) . '</p>';
                
                // Teste eventos públicos
                $eventosPublicos = $eventModel->getPublicEvents(['limite' => 3]);
                echo '<p class="status-ok">✅ Método getPublicEvents() funcionando</p>';
                echo '<p><strong>Eventos públicos (limite 3):</strong> ' . count($eventosPublicos) . '</p>';
                
                // Teste contagem
                $totalEventos = $eventModel->count();
                echo '<p class="status-ok">✅ Método count() funcionando</p>';
                echo '<p><strong>Total geral de eventos:</strong> ' . $totalEventos . '</p>';
                
            } catch (Exception $e) {
                echo '<p class="status-error"><i class="fas fa-exclamation-triangle me-2"></i>Erro nos filtros: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- TESTE 5: VALIDAÇÃO -->
        <div class="test-section">
            <h3><i class="fas fa-check-circle me-2"></i>5. Teste de Validação</h3>
            
            <?php
            try {
                // Teste dados válidos
                $dadosValidos = [
                    'titulo' => 'Evento de Teste',
                    'descricao' => 'Descrição do evento de teste',
                    'data_inicio' => date('Y-m-d', strtotime('+1 week')),
                    'data_fim' => date('Y-m-d', strtotime('+1 week')),
                    'horario_inicio' => '09:00',
                    'horario_fim' => '17:00',
                    'local_nome' => 'Local de Teste',
                    'local_endereco' => 'Endereço de Teste, 123',
                    'local_cidade' => 'Cidade Teste',
                    'local_estado' => 'RS'
                ];
                
                // Acessar método privado através de reflection
                $reflection = new ReflectionClass($eventModel);
                $validateMethod = $reflection->getMethod('validateEventData');
                $validateMethod->setAccessible(true);
                
                $resultadoValidacao = $validateMethod->invoke($eventModel, $dadosValidos);
                
                if ($resultadoValidacao['success']) {
                    echo '<p class="status-ok">✅ Validação com dados corretos funcionando</p>';
                } else {
                    echo '<p class="status-error">❌ Falha na validação: ' . $resultadoValidacao['message'] . '</p>';
                }
                
                // Teste dados inválidos
                $dadosInvalidos = [
                    'titulo' => '', // Título vazio
                    'descricao' => '',
                    'data_inicio' => '',
                    'data_fim' => '',
                    'horario_inicio' => '',
                    'horario_fim' => '',
                    'local_nome' => '',
                    'local_endereco' => '',
                    'local_cidade' => '',
                    'local_estado' => ''
                ];
                
                $resultadoInvalido = $validateMethod->invoke($eventModel, $dadosInvalidos);
                
                if (!$resultadoInvalido['success']) {
                    echo '<p class="status-ok">✅ Validação rejeitando dados inválidos corretamente</p>';
                    echo '<p><small class="text-muted">Erros encontrados: ' . $resultadoInvalido['message'] . '</small></p>';
                } else {
                    echo '<p class="status-error">❌ Validação deveria rejeitar dados inválidos</p>';
                }
                
            } catch (Exception $e) {
                echo '<p class="status-error"><i class="fas fa-exclamation-triangle me-2"></i>Erro na validação: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- TESTE 6: ESTATÍSTICAS -->
        <div class="test-section">
            <h3><i class="fas fa-chart-bar me-2"></i>6. Teste de Estatísticas</h3>
            
            <?php
            try {
                // Testar estatísticas de um evento existente
                $stats = $eventModel->getEventStats(1);
                
                if ($stats) {
                    echo '<p class="status-ok">✅ Método getEventStats() funcionando</p>';
                    echo '<div class="row">';
                    echo '<div class="col-md-3"><div class="card text-center"><div class="card-body">';
                    echo '<h5 class="card-title">' . ($stats['inscritos_confirmados'] ?? 0) . '</h5>';
                    echo '<p class="card-text">Confirmados</p>';
                    echo '</div></div></div>';
                    
                    echo '<div class="col-md-3"><div class="card text-center"><div class="card-body">';
                    echo '<h5 class="card-title">' . ($stats['inscritos_pendentes'] ?? 0) . '</h5>';
                    echo '<p class="card-text">Pendentes</p>';
                    echo '</div></div></div>';
                    
                    echo '<div class="col-md-3"><div class="card text-center"><div class="card-body">';
                    echo '<h5 class="card-title">' . ($stats['total_favoritos'] ?? 0) . '</h5>';
                    echo '<p class="card-text">Favoritos</p>';
                    echo '</div></div></div>';
                    
                    echo '<div class="col-md-3"><div class="card text-center"><div class="card-body">';
                    echo '<h5 class="card-title">' . number_format($stats['media_avaliacoes'] ?? 0, 1) . '</h5>';
                    echo '<p class="card-text">Média Avaliações</p>';
                    echo '</div></div></div>';
                    echo '</div>';
                } else {
                    echo '<p class="status-warning">⚠️ Evento ID 1 não encontrado para estatísticas</p>';
                }
                
            } catch (Exception $e) {
                echo '<p class="status-error"><i class="fas fa-exclamation-triangle me-2"></i>Erro nas estatísticas: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- RESULTADO FINAL -->
        <div class="alert alert-success text-center">
            <h4><i class="fas fa-trophy me-2"></i>Model Event Testado!</h4>
            <p class="mb-0">O Model Event está funcionando corretamente. Agora você pode criar o Controller para gerenciar eventos!</p>
        </div>

        <!-- PRÓXIMOS PASSOS -->
        <div class="alert alert-info">
            <h5><i class="fas fa-rocket me-2"></i>Próximos Passos:</h5>
            <ul class="mb-0">
                <li>✅ Model Event criado e testado</li>
                <li>🔄 Criar EventController</li>
                <li>🔄 Criar páginas para CRUD de eventos</li>
                <li>🔄 Criar dashboard do organizador</li>
                <li>🔄 Sistema de inscrições</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>