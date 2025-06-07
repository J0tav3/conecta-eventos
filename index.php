<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conecta Eventos - Teste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">🎉 Conecta Eventos</h1>
        <div class="alert alert-success text-center">
            <h4>Deploy realizado com sucesso!</h4>
            <p>PHP está funcionando no Railway</p>
            <p><strong>Data:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Próximos Passos:</h3>
                <ol>
                    <li>✅ Deploy realizado</li>
                    <li>🔄 Configurar banco de dados</li>
                    <li>🔄 Testar funcionalidades</li>
                </ol>
            </div>
            <div class="col-md-6">
                <h3>Informações Técnicas:</h3>
                <p><strong>Servidor:</strong> Railway</p>
                <p><strong>Ambiente:</strong> <?php echo $_ENV['RAILWAY_ENVIRONMENT'] ?? 'development'; ?></p>
                <p><strong>Database:</strong> <?php echo isset($_ENV['DATABASE_URL']) ? '✅ Configurado' : '❌ Não configurado'; ?></p>
            </div>
        </div>
    </div>
</body>
</html>