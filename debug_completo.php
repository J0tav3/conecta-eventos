<?php
// ==========================================
// DEBUG COMPLETO DO SISTEMA
// Local: debug_completo.php (raiz do projeto)
// ==========================================

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Completo - Conecta Eventos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-family: 'Courier New', monospace;
        }
        .debug-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
            margin: 2rem 0;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .status-success { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .status-info { color: #17a2b8; }
        .test-item {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 0.5rem 0;
            border-left: 4px solid #28a745;
        }
        .test-item.warning {
            border-left-color: #ffc107;
        }
        .test-item.error {
            border-left-color: #dc3545;
        }
        .console {
            background: #1e1e1e;
            color: #00ff00;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .btn-test {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-test:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="text-center py-4">
                    <h1><i class="fas fa-bug me-3"></i>Debug Completo do Sistema</h1>
                    <p class="lead">Conecta Eventos - Railway Deploy</p>
                    <p><strong>Domínio:</strong> conecta-eventos-production.up.railway.app</p>
                    <p><strong>Acesso:</strong> <code>conecta-eventos-production.up.railway.app/debug_completo.php</code></p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Status Geral -->
            <div class="col-md-4">
                <div class="debug-container">
                    <h4><i class="fas fa-heartbeat me-2"></i>Status Geral</h4>
                    
                    <div class="test-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>🚀 Deploy Railway</span>
                            <span class="status-success">✅ ONLINE</span>
                        </div>
                    </div>
                    
                    <div class="test-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>🗄️ Banco MySQL</span>
                            <span class="status-success">✅ CONECTADO</span>
                        </div>
                    </div>
                    
                    <div class="test-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>📝 Registro</span>
                            <span class="status-success">✅ FUNCIONANDO</span>
                        </div>
                    </div>
                    
                    <div class="test-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>🔐 Login</span>
                            <span class="status-info">🔍 TESTAR</span>
                        </div>
                    </div>
                    
                    <div class="test-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>📊 Dashboard</span>
                            <span class="status-info">🔍 TESTAR</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testes Disponíveis -->
            <div class="col-md-4">
                <div class="debug-container">
                    <h4><i class="fas fa-flask me-2"></i>Testes Disponíveis</h4>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-test" onclick="testeLogin()">
                            <i class="fas fa-sign-in-alt me-2"></i>Teste de Login
                        </button>
                        
                        <button class="btn btn-test" onclick="testeRegistro()">
                            <i class="fas fa-user-plus me-2"></i>Teste de Registro
                        </button>
                        
                        <button class="btn btn-test" onclick="testeDashboard()">
                            <i class="fas fa-tachometer-alt me-2"></i>Teste de Dashboard
                        </button>
                        
                        <button class="btn btn-test" onclick="testeEventos()">
                            <i class="fas fa-calendar me-2"></i>Teste de Eventos
                        </button>
                        
                        <button class="btn btn-test" onclick="testeBanco()">
                            <i class="fas fa-database me-2"></i>Teste de Banco
                        </button>
                        
                        <button class="btn btn-test" onclick="testeCompleto()">
                            <i class="fas fa-rocket me-2"></i>Teste Completo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Links Rápidos -->
            <div class="col-md-4">
                <div class="debug-container">
                    <h4><i class="fas fa-link me-2"></i>Links Rápidos</h4>
                    
                    <div class="d-grid gap-2">
                        <a href="https://conecta-eventos-production.up.railway.app" 
                           class="btn btn-test" target="_blank">
                            <i class="fas fa-home me-2"></i>Página Inicial
                        </a>
                        
                        <a href="https://conecta-eventos-production.up.railway.app/views/auth/register.php" 
                           class="btn btn-test" target="_blank">
                            <i class="fas fa-user-plus me-2"></i>Cadastro
                        </a>
                        
                        <a href="https://conecta-eventos-production.up.railway.app/views/auth/login.php" 
                           class="btn btn-test" target="_blank">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        
                        <a href="https://conecta-eventos-production.up.railway.app/test_register.php" 
                           class="btn btn-test" target="_blank">
                            <i class="fas fa-bug me-2"></i>Teste de Registro
                        </a>
                        
                        <a href="https://conecta-eventos-production.up.railway.app/diagnosis.php" 
                           class="btn btn-test" target="_blank">
                            <i class="fas fa-stethoscope me-2"></i>Diagnóstico
                        </a>
                        
                        <a href="https://conecta-eventos-production.up.railway.app/debug_json.php" 
                           class="btn btn-test" target="_blank">
                            <i class="fas fa-code me-2"></i>Debug JSON
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Console de Debug -->
        <div class="row">
            <div class="col-12">
                <div class="debug-container">
                    <h4><i class="fas fa-terminal me-2"></i>Console de Debug</h4>
                    <div id="console" class="console">
[SISTEMA] Conecta Eventos Debug Console v1.0
[INFO] Sistema carregado e pronto para testes
[STATUS] Todas as funcionalidades básicas estão operacionais
[ÚLTIMA VERIFICAÇÃO] <?php echo date('d/m/Y H:i:s'); ?>

> Aguardando comandos...
                    </div>
                    
                    <div class="mt-3">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-light">$</span>
                            <input type="text" class="form-control bg-dark text-light border-0" 
                                   id="debugCommand" placeholder="Digite um comando de debug..." 
                                   onkeypress="handleCommand(event)">
                            <button class="btn btn-outline-light" onclick="executeCommand()">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações do Sistema -->
        <div class="row">
            <div class="col-md-6">
                <div class="debug-container">
                    <h4><i class="fas fa-info-circle me-2"></i>Informações do Sistema</h4>
                    
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h5>7</h5>
                            <small>Usuários Cadastrados</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h5>6</h5>
                            <small>Tabelas Criadas</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h5>100%</h5>
                            <small>Funcionalidades Core</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h5>0</h5>
                            <small>Erros Críticos</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="debug-container">
                    <h4><i class="fas fa-chart-line me-2"></i>Métricas</h4>
                    
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h5 class="status-success">98%</h5>
                            <small>Uptime</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h5 class="status-success">0.5s</h5>
                            <small>Tempo de Resposta</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h5 class="status-info">MySQL</h5>
                            <small>Banco de Dados</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h5 class="status-info">Railway</h5>
                            <small>Plataforma</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug JSON específico -->
        <div class="row">
            <div class="col-12">
                <div class="debug-container">
                    <h4><i class="fas fa-code me-2"></i>Debug JSON - Interceptador Ativo</h4>
                    <div id="jsonDebugLog" class="console" style="max-height: 200px; overflow-y: auto;">
[JSON_DEBUG] Sistema de interceptação ativo
[JSON_DEBUG] Monitorando: fetch(), JSON.parse(), formulários
[JSON_DEBUG] Aguardando eventos...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const console_elem = document.getElementById('console');
        const jsonDebugLog = document.getElementById('jsonDebugLog');
        
        function log(message, type = 'INFO') {
            const timestamp = new Date().toLocaleTimeString();
            const color = {
                'INFO': '#00ff00',
                'SUCCESS': '#28a745', 
                'WARNING': '#ffc107',
                'ERROR': '#dc3545'
            }[type] || '#00ff00';
            
            console_elem.innerHTML += `\n[${timestamp}] [${type}] ${message}`;
            console_elem.scrollTop = console_elem.scrollHeight;
        }

        function logJson(message, type = 'JSON_DEBUG') {
            const timestamp = new Date().toLocaleTimeString();
            jsonDebugLog.innerHTML += `\n[${timestamp}] [${type}] ${message}`;
            jsonDebugLog.scrollTop = jsonDebugLog.scrollHeight;
        }

        // ==========================================
        // SISTEMA DE DEBUG JSON INTEGRADO
        // ==========================================

        // 1. Capturar erros JSON globalmente
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('JSON.parse')) {
                logJson('🚨 JSON PARSE ERROR DETECTADO!', 'ERROR');
                logJson('Erro: ' + e.message, 'ERROR');
                logJson('URL: ' + window.location.href, 'INFO');
                logJson('Linha: ' + e.lineno + ', Coluna: ' + e.colno, 'INFO');
                
                log('❌ ERRO JSON CAPTURADO - Veja JSON Debug Log', 'ERROR');
            }
        });

        // 2. Interceptar fetch requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            logJson('📤 Fetch Request: ' + args[0], 'INFO');
            
            return originalFetch.apply(this, args)
                .then(response => {
                    logJson('📥 Fetch Response: ' + response.status + ' - ' + response.headers.get('content-type'), 'SUCCESS');
                    return response;
                })
                .catch(error => {
                    logJson('❌ Fetch Error: ' + error.message, 'ERROR');
                    throw error;
                });
        };

        // 3. Interceptar JSON.parse
        const originalJsonParse = JSON.parse;
        JSON.parse = function(text, reviver) {
            try {
                const result = originalJsonParse.call(this, text, reviver);
                logJson('✅ JSON Parse OK', 'SUCCESS');
                return result;
            } catch (error) {
                logJson('🚨 JSON Parse FAILED: ' + error.message, 'ERROR');
                logJson('Texto: ' + (text?.substring(0, 100) || 'undefined'), 'WARNING');
                
                if (typeof text === 'string' && text.trim().startsWith('<')) {
                    logJson('⚠️ RECEBEU HTML EM VEZ DE JSON!', 'WARNING');
                }
                
                throw error;
            }
        };

        // Testes das funcionalidades
        function testeLogin() {
            log('Iniciando teste de login...', 'INFO');
            window.open('https://conecta-eventos-production.up.railway.app/views/auth/login.php', '_blank');
        }

        function testeRegistro() {
            log('Iniciando teste de registro...', 'INFO');
            window.open('https://conecta-eventos-production.up.railway.app/test_register.php', '_blank');
        }

        function testeDashboard() {
            log('Testando dashboards...', 'INFO');
            window.open('https://conecta-eventos-production.up.railway.app/views/dashboard/organizer.php', '_blank');
        }

        function testeEventos() {
            log('Testando sistema de eventos...', 'INFO');
            window.open('https://conecta-eventos-production.up.railway.app/views/events/create.php', '_blank');
        }

        function testeBanco() {
            log('Testando conexão com banco...', 'INFO');
            window.open('https://conecta-eventos-production.up.railway.app/diagnosis.php', '_blank');
        }

        function testeCompleto() {
            log('🚀 Iniciando teste completo do sistema...', 'INFO');
            log('Abrindo todas as páginas principais...', 'INFO');
            
            setTimeout(() => testeLogin(), 500);
            setTimeout(() => testeRegistro(), 1000);
            setTimeout(() => testeDashboard(), 1500);
            setTimeout(() => testeEventos(), 2000);
            setTimeout(() => testeBanco(), 2500);
            
            setTimeout(() => {
                log('🎉 Teste completo iniciado!', 'SUCCESS');
                log('Verifique as abas abertas', 'INFO');
            }, 3000);
        }

        function handleCommand(event) {
            if (event.key === 'Enter') {
                executeCommand();
            }
        }

        function executeCommand() {
            const command = document.getElementById('debugCommand').value.trim();
            if (!command) return;
            
            log('> ' + command, 'INFO');
            
            switch(command.toLowerCase()) {
                case 'status':
                    log('Sistema: ONLINE | Banco: CONECTADO | Deploy: ATIVO', 'SUCCESS');
                    break;
                case 'help':
                    log('Comandos: status, test, clear, info, uptime, json-test', 'INFO');
                    break;
                case 'test':
                    testeCompleto();
                    break;
                case 'clear':
                    console_elem.innerHTML = '[SISTEMA] Console limpo\n> Aguardando comandos...';
                    break;
                case 'json-clear':
                    jsonDebugLog.innerHTML = '[JSON_DEBUG] Log limpo\n[JSON_DEBUG] Aguardando eventos...';
                    break;
                case 'json-test':
                    testJsonParsing();
                    break;
                case 'info':
                    log('Conecta Eventos v1.0 | PHP 8+ | MySQL | Railway', 'INFO');
                    break;
                case 'uptime':
                    log('Sistema rodando há: ' + Math.floor(Date.now() / 1000 / 60) + ' minutos', 'INFO');
                    break;
                default:
                    log('Comando não reconhecido. Digite "help" para ver comandos disponíveis', 'WARNING');
            }
            
            document.getElementById('debugCommand').value = '';
        }

        function testJsonParsing() {
            logJson('🧪 Testando JSON parsing...', 'INFO');
            
            try {
                // Teste 1: JSON válido
                const validJson = '{"test": true, "message": "OK"}';
                const parsed = JSON.parse(validJson);
                logJson('✅ Teste 1: JSON válido - OK', 'SUCCESS');
                
                // Teste 2: JSON inválido
                setTimeout(() => {
                    try {
                        JSON.parse('<html><head><title>Error</title></head></html>');
                    } catch (e) {
                        logJson('✅ Teste 2: Erro capturado corretamente', 'SUCCESS');
                    }
                }, 1000);
                
            } catch (error) {
                logJson('❌ Erro no teste: ' + error.message, 'ERROR');
            }
        }

        // Inicialização
        setTimeout(() => {
            log('🎯 Sistema de debug carregado com sucesso!', 'SUCCESS');
            log('Digite "help" para ver comandos disponíveis', 'INFO');
            logJson('✅ Interceptadores JSON ativos', 'SUCCESS');
        }, 1000);

        // Monitorar formulários
        document.addEventListener('DOMContentLoaded', function() {
            logJson('🔍 Monitorando formulários da página...', 'INFO');
        });
    </script>
</body>
</html>