<?php
// ==========================================
// INDEX.PHP ULTRA MÍNIMO PARA DEBUG
// ==========================================

// Mostrar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Log de debug
error_log("INDEX.PHP: Iniciando...");

try {
    echo "<!DOCTYPE html><html><head><title>Conecta Eventos - Debug</title>";
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo "</head><body class='bg-light'>";
    
    echo "<div class='container mt-5'>";
    echo "<div class='row justify-content-center'>";
    echo "<div class='col-md-8'>";
    
    echo "<div class='alert alert-info'>";
    echo "<h2><i class='fas fa-bug'></i> Debug Mode</h2>";
    echo "<p>PHP está funcionando! Versão: " . phpversion() . "</p>";
    echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";
    echo "</div>";
    
    // Teste básico de arquivos
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Teste de Arquivos</h5></div>";
    echo "<div class='card-body'>";
    
    $files_to_test = [
        'config/config.php',
        'includes/session.php',
        'controllers/EventController.php'
    ];
    
    foreach ($files_to_test as $file) {
        if (file_exists($file)) {
            echo "<p class='text-success'>✅ $file - OK</p>";
        } else {
            echo "<p class='text-danger'>❌ $file - NÃO ENCONTRADO</p>";
        }
    }
    echo "</div></div>";
    
    // Teste de variáveis de ambiente
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Variáveis de Ambiente</h5></div>";
    echo "<div class='card-body'>";
    
    $database_url = getenv('DATABASE_URL');
    if ($database_url) {
        echo "<p class='text-success'>✅ DATABASE_URL configurada</p>";
    } else {
        echo "<p class='text-warning'>⚠️ DATABASE_URL não configurada</p>";
    }
    
    echo "<p>PORT: " . (getenv('PORT') ?: 'não definida') . "</p>";
    echo "<p>RAILWAY_ENVIRONMENT: " . (getenv('RAILWAY_ENVIRONMENT') ?: 'não definida') . "</p>";
    echo "</div></div>";
    
    // Teste básico sem incluir outros arquivos
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Sistema Básico</h5></div>";
    echo "<div class='card-body'>";
    echo "<p class='text-success'>✅ PHP funcionando</p>";
    echo "<p class='text-success'>✅ Bootstrap carregado</p>";
    echo "<p class='text-success'>✅ Sem erros fatais</p>";
    echo "</div></div>";
    
    // Eventos de exemplo (hardcoded)
    $eventos_exemplo = [
        [
            'titulo' => 'Workshop de Tecnologia',
            'descricao' => 'Aprenda as últimas tendências',
            'data' => date('d/m/Y', strtotime('+7 days')),
            'cidade' => 'São Paulo',
            'gratuito' => true
        ],
        [
            'titulo' => 'Palestra de Empreendedorismo', 
            'descricao' => 'Como iniciar seu próprio negócio',
            'data' => date('d/m/Y', strtotime('+10 days')),
            'cidade' => 'Rio de Janeiro',
            'gratuito' => false
        ]
    ];
    
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'><h5>Eventos de Exemplo</h5></div>";
    echo "<div class='card-body'>";
    echo "<div class='row'>";
    
    foreach ($eventos_exemplo as $evento) {
        echo "<div class='col-md-6 mb-3'>";
        echo "<div class='card'>";
        echo "<div class='card-body'>";
        echo "<h6 class='card-title'>" . htmlspecialchars($evento['titulo']) . "</h6>";
        echo "<p class='card-text'>" . htmlspecialchars($evento['descricao']) . "</p>";
        echo "<small class='text-muted'>";
        echo "<i class='fas fa-calendar'></i> " . $evento['data'] . " | ";
        echo "<i class='fas fa-map-marker-alt'></i> " . $evento['cidade'] . " | ";
        echo $evento['gratuito'] ? '<span class="text-success">Gratuito</span>' : '<span class="text-primary">Pago</span>';
        echo "</small>";
        echo "</div></div></div>";
    }
    
    echo "</div></div></div>";
    
    // Links de navegação
    echo "<div class='card'>";
    echo "<div class='card-header'><h5>Navegação</h5></div>";
    echo "<div class='card-body'>";
    echo "<a href='test.php' class='btn btn-info me-2'>Ver Diagnóstico Completo</a>";
    echo "<a href='views/auth/login.php' class='btn btn-primary me-2'>Login</a>";
    echo "<a href='views/auth/register.php' class='btn btn-success'>Cadastrar</a>";
    echo "</div></div>";
    
    echo "</div></div></div>";
    
    // Footer simples
    echo "<footer class='bg-dark text-white text-center py-3 mt-5'>";
    echo "<p>&copy; 2024 Conecta Eventos - Modo Debug Ativo</p>";
    echo "</footer>";
    
    echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>';
    echo "</body></html>";
    
    error_log("INDEX.PHP: Concluído com sucesso");
    
} catch (Exception $e) {
    error_log("ERRO no index.php: " . $e->getMessage());
    echo "<h1>Erro Fatal</h1>";
    echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
} catch (Error $e) {
    error_log("ERRO FATAL no index.php: " . $e->getMessage());
    echo "<h1>Erro Fatal PHP</h1>";
    echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}
?>