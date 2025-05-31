<?php
// ========================================
// TESTE DA API DE FAVORITOS
// ========================================
// Local: conecta-eventos/test_api.php (RAIZ)
// ========================================

require_once 'config/config.php';
require_once 'includes/session.php';
require_once 'controllers/FavoritesController.php';

$title = "Teste API Favoritos - " . SITE_NAME;

// Verificar se usuário está logado
$isLoggedIn = isLoggedIn();
$userId = $isLoggedIn ? getUserId() : null;

$favoritesController = new FavoritesController();
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
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .response-box {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.375rem;
            font-family: 'Courier New', monospace;
            margin: 0.5rem 0;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <h1 class="mb-4">
            <i class="fas fa-heart me-2"></i>
            Teste API de Favoritos
        </h1>
        
        <!-- Status de Login -->
        <div class="test-section">
            <h3><i class="fas fa-user me-2"></i>Status do Usuário</h3>
            
            <?php if ($isLoggedIn): ?>
                <p class="status-ok">✅ Usuário logado: <?php echo htmlspecialchars(getUserName()); ?></p>
                <p><strong>ID:</strong> <?php echo $userId; ?></p>
                <p><strong>Tipo:</strong> <?php echo getUserType(); ?></p>
            <?php else: ?>
                <p class="status-error">❌ Usuário não logado</p>
                <a href="views/auth/login.php" class="btn btn-primary">Fazer Login para Testar</a>
            <?php endif; ?>
        </div>

        <?php if ($isLoggedIn): ?>
            <!-- Teste Controller Direto -->
            <div class="test-section">
                <h3><i class="fas fa-code me-2"></i>Teste Controller Direto</h3>
                
                <?php
                try {
                    // Testar métodos do controller
                    $totalFavorites = $favoritesController->countUserFavorites($userId);
                    echo "<p class='status-ok'>✅ Controller carregado com sucesso</p>";
                    echo "<p><strong>Total de favoritos:</strong> $totalFavorites</p>";
                    
                    // Testar com evento ID 1
                    $isFavorite = $favoritesController->isFavorite($userId, 1);
                    echo "<p><strong>Evento ID 1 está nos favoritos:</strong> " . ($isFavorite ? 'Sim' : 'Não') . "</p>";
                    
                } catch (Exception $e) {
                    echo "<p class='status-error'>❌ Erro no controller: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>

            <!-- Teste API via JavaScript -->
            <div class="test-section">
                <h3><i class="fas fa-wifi me-2"></i>Teste API via AJAX</h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Testar com Evento ID:</h5>
                        <div class="input-group mb-3">
                            <input type="number" id="eventId" class="form-control" value="1" min="1">
                            <button class="btn btn-primary" onclick="testGetFavorite()">
                                <i class="fas fa-search me-2"></i>Verificar
                            </button>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="testAddFavorite()">
                                <i class="fas fa-heart me-2"></i>Adicionar aos Favoritos
                            </button>
                            <button class="btn btn-warning" onclick="testToggleFavorite()">
                                <i class="fas fa-exchange-alt me-2"></i>Toggle Favorito
                            </button>
                            <button class="btn btn-danger" onclick="testRemoveFavorite()">
                                <i class="fas fa-heart-broken me-2"></i>Remover dos Favoritos
                            </button>
                            <button class="btn btn-info" onclick="testGetAllFavorites()">
                                <i class="fas fa-list me-2"></i>Listar Todos os Favoritos
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Resposta da API:</h5>
                        <div id="apiResponse" class="response-box">
                            Aguardando teste...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações de Debug -->
            <div class="test-section">
                <h3><i class="fas fa-bug me-2"></i>Informações de Debug</h3>
                
                <p><strong>URL da API:</strong> <?php echo SITE_URL; ?>/api/favorites.php</p>
                <p><strong>Caminho do arquivo:</strong> <?php echo __DIR__; ?>/api/favorites.php</p>
                <p><strong>Arquivo existe:</strong> <?php echo file_exists(__DIR__ . '/api/favorites.php') ? 'Sim' : 'Não'; ?></p>
                
                <?php if (!file_exists(__DIR__ . '/api/favorites.php')): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Arquivo da API não encontrado!</strong><br>
                        Crie a pasta <code>api/</code> na raiz e adicione o arquivo <code>favorites.php</code>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const apiUrl = '<?php echo SITE_URL; ?>/api/favorites.php';
        
        function updateResponse(data) {
            document.getElementById('apiResponse').textContent = JSON.stringify(data, null, 2);
        }
        
        function getEventId() {
            return document.getElementById('eventId').value;
        }
        
        async function testGetFavorite() {
            try {
                const response = await fetch(`${apiUrl}?event_id=${getEventId()}`);
                const data = await response.json();
                updateResponse(data);
            } catch (error) {
                updateResponse({error: error.message});
            }
        }
        
        async function testAddFavorite() {
            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: getEventId(),
                        action: 'add'
                    })
                });
                const data = await response.json();
                updateResponse(data);
            } catch (error) {
                updateResponse({error: error.message});
            }
        }
        
        async function testToggleFavorite() {
            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: getEventId(),
                        action: 'toggle'
                    })
                });
                const data = await response.json();
                updateResponse(data);
            } catch (error) {
                updateResponse({error: error.message});
            }
        }
        
        async function testRemoveFavorite() {
            try {
                const response = await fetch(apiUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: getEventId()
                    })
                });
                const data = await response.json();
                updateResponse(data);
            } catch (error) {
                updateResponse({error: error.message});
            }
        }
        
        async function testGetAllFavorites() {
            try {
                const response = await fetch(apiUrl);
                const data = await response.json();
                updateResponse(data);
            } catch (error) {
                updateResponse({error: error.message});
            }
        }
    </script>
</body>
</html>