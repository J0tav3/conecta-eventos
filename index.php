<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conecta Eventos - Em Manutenção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            min-height: 60vh;
            display: flex;
            align-items: center;
        }
        
        .maintenance-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1rem;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .feature-card {
            border: none;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            height: 100%;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-calendar-check me-2"></i>
                <strong>Conecta Eventos</strong>
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class="fas fa-tools me-1"></i>
                    Sistema em Manutenção
                </span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="maintenance-card">
                        <div class="pulse">
                            <i class="fas fa-tools fa-4x text-primary mb-4"></i>
                        </div>
                        <h1 class="text-dark mb-3">Sistema em Manutenção</h1>
                        <p class="text-muted mb-4 fs-5">
                            Estamos trabalhando para melhorar sua experiência. 
                            O site voltará ao ar em breve!
                        </p>
                        
                        <div class="row text-center mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <i class="fas fa-clock fa-2x text-info"></i>
                                </div>
                                <h5>Tempo Estimado</h5>
                                <p class="text-muted">15-30 minutos</p>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <i class="fas fa-shield-alt fa-2x text-success"></i>
                                </div>
                                <h5>Seus Dados</h5>
                                <p class="text-muted">Estão seguros</p>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <i class="fas fa-rocket fa-2x text-warning"></i>
                                </div>
                                <h5>Melhorias</h5>
                                <p class="text-muted">Performance e estabilidade</p>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Status:</strong> Corrigindo problemas de conectividade com o banco de dados
                        </div>
                        
                        <button class="btn btn-primary btn-lg" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Tentar Novamente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Preview -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12 mb-4">
                <h2 class="text-center mb-5">
                    <i class="fas fa-star text-warning me-2"></i>
                    O que você encontrará quando voltarmos
                </h2>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card feature-card bg-light">
                    <div class="feature-icon text-primary">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h4>Criar Eventos</h4>
                    <p class="text-muted">
                        Organize seus eventos de forma simples e intuitiva. 
                        Gerencie inscrições e participantes.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="card feature-card bg-light">
                    <div class="feature-icon text-success">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>Encontrar Eventos</h4>
                    <p class="text-muted">
                        Descubra eventos interessantes na sua região. 
                        Filtre por categoria, data e localização.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="card feature-card bg-light">
                    <div class="feature-icon text-info">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Conectar Pessoas</h4>
                    <p class="text-muted">
                        Participe de eventos, conheça pessoas novas 
                        e amplie sua rede de contatos.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Info -->
    <section class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h5>
                        <i class="fas fa-envelope me-2"></i>
                        Precisa de Ajuda?
                    </h5>
                    <p class="mb-0">
                        Entre em contato: suporte@conectaeventos.com
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Última atualização: <span id="current-time"></span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Atualizar horário
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleString('pt-BR');
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        // Auto refresh a cada 2 minutos
        setTimeout(() => {
            location.reload();
        }, 120000);
        
        // Animação do botão
        document.querySelector('button').addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Carregando...';
            this.disabled = true;
            
            setTimeout(() => {
                location.reload();
            }, 2000);
        });
    </script>
</body>
</html>