<?php
// ========================================
// TESTE DAS APIS DE INSCRIÇÕES E AVALIAÇÕES
// ========================================
// Local: conecta-eventos/test_subscriptions.php (RAIZ)
// ========================================

require_once 'config/config.php';
require_once 'includes/session.php';
require_once 'controllers/EventController.php';

$title = "Teste APIs - " . SITE_NAME;
$isLoggedIn = isLoggedIn();
$eventController = new EventController();

// Obter eventos para teste
$eventos = $eventController->getPublicEvents(['limite' => 5]);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .test-section {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #007bff;
        }
        .api-response {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.375rem;
            font-family: 'Courier New', monospace;
            margin: 0.5rem 0;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        .event-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container my-4">
        <h1 class="mb-4">
            <i class="fas fa-rocket me-2"></i>
            Teste de APIs - Inscrições, Avaliações e Favoritos
        </h1>
        
        <!-- Status de Login -->
        <div class="test-section">
            <h3><i class="fas fa-user me-2"></i>Status do Usuário</h3>
            
            <?php if ($isLoggedIn): ?>
                <p class="status-ok">✅ Usuário logado: <?php echo htmlspecialchars(getUserName()); ?></p>
                <p><strong>ID:</strong> <?php echo getUserId(); ?></p>
                <p><strong>Tipo:</strong> <?php echo getUserType(); ?></p>
            <?php else: ?>
                <p class="status-error">❌ Usuário não logado</p>
                <a href="views/auth/login.php" class="btn btn-primary">Fazer Login para Testar</a>
            <?php endif; ?>
        </div>

        <?php if ($isLoggedIn): ?>
            <!-- Seletor de Evento -->
            <div class="test-section">
                <h3><i class="fas fa-calendar me-2"></i>Eventos Disponíveis para Teste</h3>
                
                <?php if (empty($eventos)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Nenhum evento disponível!</strong><br>
                        <a href="views/events/create.php" class="btn btn-primary btn-sm mt-2">Criar Evento de Teste</a>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label for="selectedEvent" class="form-label">Escolha um evento para testar:</label>
                        <select class="form-select" id="selectedEvent">
                            <?php foreach ($eventos as $evento): ?>
                                <option value="<?php echo $evento['id_evento']; ?>">
                                    <?php echo htmlspecialchars($evento['titulo']); ?> 
                                    (ID: <?php echo $evento['id_evento']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($eventos as $evento): ?>
                            <div class="col-md-6 mb-3">
                                <div class="event-card">
                                    <h6><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                                    <p class="mb-1"><strong>ID:</strong> <?php echo $evento['id_evento']; ?></p>
                                    <p class="mb-1"><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?></p>
                                    <p class="mb-0"><strong>Local:</strong> <?php echo htmlspecialchars($evento['local_cidade']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($eventos)): ?>
                <!-- Teste de Inscrições -->
                <div class="test-section">
                    <h3><i class="fas fa-ticket-alt me-2"></i>Teste de Inscrições</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" onclick="testSubscribe()">
                                    <i class="fas fa-user-plus me-2"></i>Inscrever-se no Evento
                                </button>
                                <button class="btn btn-info" onclick="testSubscriptionStatus()">
                                    <i class="fas fa-info-circle me-2"></i>Verificar Status da Inscrição
                                </button>
                                <button class="btn btn-warning" onclick="testUnsubscribe()">
                                    <i class="fas fa-user-times me-2"></i>Cancelar Inscrição
                                </button>
                                <button class="btn btn-primary" onclick="testMySubscriptions()">
                                    <i class="fas fa-list me-2"></i>Minhas Inscrições
                                </button>
                                <button class="btn btn-secondary" onclick="testSubscriptionStats()">
                                    <i class="fas fa-chart-bar me-2"></i>Minhas Estatísticas
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Resposta da API de Inscrições:</h5>
                            <div id="subscriptionsResponse" class="api-response">
                                Aguardando teste...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teste de Favoritos -->
                <div class="test-section">
                    <h3><i class="fas fa-heart me-2"></i>Teste de Favoritos</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-grid gap-2">
                                <button class="btn btn-danger" onclick="testAddFavorite()">
                                    <i class="fas fa-heart me-2"></i>Adicionar aos Favoritos
                                </button>
                                <button class="btn btn-info" onclick="testCheckFavorite()">
                                    <i class="fas fa-search me-2"></i>Verificar se é Favorito
                                </button>
                                <button class="btn btn-warning" onclick="testToggleFavorite()">
                                    <i class="fas fa-exchange-alt me-2"></i>Toggle Favorito
                                </button>
                                <button class="btn btn-primary" onclick="testMyFavorites()">
                                    <i class="fas fa-list me-2"></i>Meus Favoritos
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Resposta da API de Favoritos:</h5>
                            <div id="favoritesResponse" class="api-response">
                                Aguardando teste...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teste de Avaliações -->
                <div class="test-section">
                    <h3><i class="fas fa-star me-2"></i>Teste de Avaliações</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ratingValue" class="form-label">Avaliação (1-5 estrelas):</label>
                                <select class="form-select" id="ratingValue">
                                    <option value="5">⭐⭐⭐⭐⭐ - Excelente</option>
                                    <option value="4">⭐⭐⭐⭐ - Muito Bom</option>
                                    <option value="3">⭐⭐⭐ - Bom</option>
                                    <option value="2">⭐⭐ - Regular</option>
                                    <option value="1">⭐ - Ruim</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ratingComment" class="form-label">Comentário:</label>
                                <textarea class="form-control" id="ratingComment" rows="3" 
                                          placeholder="Comente sobre o evento..."></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-warning" onclick="testRateEvent()">
                                    <i class="fas fa-star me-2"></i>Avaliar Evento
                                </button>
                                <button class="btn btn-info" onclick="testEventRatingStats()">
                                    <i class="fas fa-chart-line me-2"></i>Estatísticas do Evento
                                </button>
                                <button class="btn btn-primary" onclick="testEventReviews()">
                                    <i class="fas fa-comments me-2"></i>Ver Avaliações do Evento
                                </button>
                                <button class="btn btn-secondary" onclick="testTopRatedEvents()">
                                    <i class="fas fa-trophy me-2"></i>Eventos Mais Bem Avaliados
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Resposta da API de Avaliações:</h5>
                            <div id="ratingsResponse" class="api-response">
                                Aguardando teste...
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const siteUrl = '<?php echo SITE_URL; ?>';
        
        function getSelectedEventId() {
            const select = document.getElementById('selectedEvent');
            return select ? select.value : null;
        }
        
        function updateResponse(elementId, data) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = JSON.stringify(data, null, 2);
            }
        }
        
        // ==========================================
        // TESTES DE INSCRIÇÕES
        // ==========================================
        
        async function testSubscribe() {
            try {
                const response = await fetch(`${siteUrl}/api/subscriptions.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: getSelectedEventId(),
                        observations: 'Inscrição via teste da API'
                    })
                });
                const data = await response.json();
                updateResponse('subscriptionsResponse', data);
            } catch (error) {
                updateResponse('subscriptionsResponse', {error: error.message});
            }
        }
        
        async function testSubscriptionStatus() {
            try {
                const response = await fetch(`${siteUrl}/api/subscriptions.php?action=status&event_id=${getSelectedEventId()}`);
                const data = await response.json();
                updateResponse('subscriptionsResponse', data);
            } catch (error) {
                updateResponse('subscriptionsResponse', {error: error.message});
            }
        }
        
        async function testUnsubscribe() {
            try {
                const response = await fetch(`${siteUrl}/api/subscriptions.php`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: getSelectedEventId()
                    })
                });
                const data = await response.json();
                updateResponse('subscriptionsResponse', data);
            } catch (error) {
                updateResponse('subscriptionsResponse', {error: error.message});
            }
        }
        
        async function testMySubscriptions() {
            try {
                const response = await fetch(`${siteUrl}/api/subscriptions.php?action=my_subscriptions`);
                const data = await response.json();
                updateResponse('subscriptionsResponse', data);
            } catch (error) {
                updateResponse('subscriptionsResponse', {error: error.message});
            }
        }
        
        async function testSubscriptionStats() {
            try {
                const response = await fetch(`${siteUrl}/api/subscriptions.php?action=stats`);
                const data = await response.json();
                updateResponse('subscriptionsResponse', data);
            } catch (error) {
                updateResponse('subscriptionsResponse', {error: error.message});
            }
        }
        
        // ==========================================
        // TESTES DE FAVORITOS
        // ==========================================
        
        async function testAddFavorite() {
            try {
                const response = await fetch(`${siteUrl}/api/favorites.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: getSelectedEventId(),
                        action: 'add'
                    })
                });
                const data = await response.json();
                updateResponse('favoritesResponse', data);
            } catch (error) {
                updateResponse('favoritesResponse', {error: error.message});
            }
        }
        
        async function testCheckFavorite() {
            try {
                const response = await fetch(`${siteUrl}/api/favorites.php?event_id=${getSelectedEventId()}`);
                const data = await response.json();
                updateResponse('favoritesResponse', data);
            } catch (error) {
                updateResponse('favoritesResponse', {error: error.message});
            }
        }
        
        async function testToggleFavorite() {
            try {
                const response = await fetch(`${siteUrl}/api/favorites.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: getSelectedEventId(),
                        action: 'toggle'
                    })
                });
                const data = await response.json();
                updateResponse('favoritesResponse', data);
            } catch (error) {
                updateResponse('favoritesResponse', {error: error.message});
            }
        }
        
        async function testMyFavorites() {
            try {
                const response = await fetch(`${siteUrl}/api/favorites.php`);
                const data = await response.json();
                updateResponse('favoritesResponse', data);
            } catch (error) {
                updateResponse('favoritesResponse', {error: error.message});
            }
        }
        
        // ==========================================
        // TESTES DE AVALIAÇÕES
        // ==========================================
        
        async function testRateEvent() {
            try {
                const rating = document.getElementById('ratingValue').value;
                const comment = document.getElementById('ratingComment').value;
                
                const response = await fetch(`${siteUrl}/api/ratings.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: getSelectedEventId(),
                        rating: parseInt(rating),
                        comment: comment
                    })
                });
                const data = await response.json();
                updateResponse('ratingsResponse', data);
            } catch (error) {
                updateResponse('ratingsResponse', {error: error.message});
            }
        }
        
        async function testEventRatingStats() {
            try {
                const response = await fetch(`${siteUrl}/api/ratings.php?action=stats&event_id=${getSelectedEventId()}`);
                const data = await response.json();
                updateResponse('ratingsResponse', data);
            } catch (error) {
                updateResponse('ratingsResponse', {error: error.message});
            }
        }
        
        async function testEventReviews() {
            try {
                const response = await fetch(`${siteUrl}/api/ratings.php?action=reviews&event_id=${getSelectedEventId()}`);
                const data = await response.json();
                updateResponse('ratingsResponse', data);
            } catch (error) {
                updateResponse('ratingsResponse', {error: error.message});
            }
        }
        
        async function testTopRatedEvents() {
            try {
                const response = await fetch(`${siteUrl}/api/ratings.php?action=top_rated&limit=5`);
                const data = await response.json();
                updateResponse('ratingsResponse', data);
            } catch (error) {
                updateResponse('ratingsResponse', {error: error.message});
            }
        }
    </script>
</body>
</html>