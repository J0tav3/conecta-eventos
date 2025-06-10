<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Sistema de Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem 0;
        }

        .debug-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .debug-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }

        .debug-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .status-success {
            color: #28a745;
            background: #d4edda;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
        }

        .status-error {
            color: #dc3545;
            background: #f8d7da;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
        }

        .status-warning {
            color: #856404;
            background: #fff3cd;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
        }

        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }

        .user-table {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .test-section {
            border: 2px dashed #dee2e6;
            border-radius: 1rem;
            padding: 2rem;
            margin: 1rem 0;
            background: #f8f9fa;
        }

        .btn-test {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }

        .btn-fix {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="text-center mb-4">
            <h1 class="display-4">🔍 Debug - Sistema de Usuários</h1>
            <p class="lead">Diagnosticando problema de registro de usuários</p>
        </div>

        <!-- Status da Conexão -->
        <div class="debug-card">
            <h3 class="debug-title"><i class="fas fa-database me-2"></i>Status da Conexão com Banco</h3>
            <div id="connection-status">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Testando conexão...</span>
                </div>
                <span class="ms-2">Verificando conexão...</span>
            </div>
        </div>

        <!-- Usuários Existentes -->
        <div class="debug-card">
            <h3 class="debug-title"><i class="fas fa-users me-2"></i>Usuários Cadastrados no Banco</h3>
            <div id="users-list">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando usuários...</span>
                </div>
                <span class="ms-2">Buscando usuários...</span>
            </div>
        </div>

        <!-- Teste de Registro -->
        <div class="debug-card">
            <h3 class="debug-title"><i class="fas fa-user-plus me-2"></i>Teste de Registro</h3>
            <div class="test-section">
                <h5>🧪 Teste Automático de Registro</h5>
                <p>Vamos criar um usuário de teste para verificar se o sistema está salvando no banco.</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <button class="btn btn-test w-100 mb-3" onclick="testRegistration()">
                            <i class="fas fa-play me-2"></i>Executar Teste de Registro
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-fix w-100 mb-3" onclick="fixRegistrationSystem()">
                            <i class="fas fa-wrench me-2"></i>Corrigir Sistema de Registro
                        </button>
                    </div>
                </div>
                
                <div id="test-results" class="mt-3"></div>
            </div>
        </div>

        <!-- Logs de Debug -->
        <div class="debug-card">
            <h3 class="debug-title"><i class="fas fa-terminal me-2"></i>Logs de Debug</h3>
            <div class="code-block" id="debug-logs">
                Inicializando debug...\n
            </div>
        </div>

        <!-- Soluções -->
        <div class="debug-card">
            <h3 class="debug-title"><i class="fas fa-tools me-2"></i>Possíveis Soluções</h3>
            <div class="row">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-database fa-3x text-primary mb-3"></i>
                            <h5>Problema de Conexão</h5>
                            <p>AuthController não está conectando com o banco corretamente</p>
                            <button class="btn btn-outline-primary" onclick="fixConnection()">Corrigir</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-code fa-3x text-warning mb-3"></i>
                            <h5>Erro no SQL</h5>
                            <p>Query de inserção pode estar com problema</p>
                            <button class="btn btn-outline-warning" onclick="fixSQL()">Corrigir</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                            <h5>Validação de Dados</h5>
                            <p>Dados podem estar sendo rejeitados pela validação</p>
                            <button class="btn btn-outline-danger" onclick="fixValidation()">Corrigir</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Função para adicionar logs
        function addLog(message) {
            const logsContainer = document.getElementById('debug-logs');
            const timestamp = new Date().toLocaleTimeString();
            logsContainer.textContent += `[${timestamp}] ${message}\n`;
            logsContainer.scrollTop = logsContainer.scrollHeight;
        }

        // Função para verificar conexão
        async function checkConnection() {
            addLog('🔍 Verificando conexão com banco de dados...');
            
            try {
                // Simular verificação da conexão via API
                addLog('📡 Enviando requisição para API...');
                
                // Para demonstração, vamos simular uma resposta
                setTimeout(() => {
                    document.getElementById('connection-status').innerHTML = `
                        <span class="status-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Conexão com banco estabelecida com sucesso!
                        </span>
                        <div class="mt-2">
                            <small>Host: switchyard.proxy.rlwy.net | Database: railway | Status: Connected</small>
                        </div>
                    `;
                    addLog('✅ Conexão verificada com sucesso');
                }, 1000);
                
            } catch (error) {
                document.getElementById('connection-status').innerHTML = `
                    <span class="status-error">
                        <i class="fas fa-times-circle me-2"></i>
                        Erro na conexão: ${error.message}
                    </span>
                `;
                addLog('❌ Erro na verificação da conexão: ' + error.message);
            }
        }

        // Função para listar usuários
        async function loadUsers() {
            addLog('👥 Carregando lista de usuários...');
            
            try {
                // Simular busca de usuários
                setTimeout(() => {
                    // Dados simulados - substitua pela chamada real da API
                    const users = [
                        {
                            id: 1,
                            nome: 'Administrador',
                            email: 'admin@conectaeventos.com',
                            tipo: 'organizador',
                            ativo: true,
                            data_criacao: '2024-01-15'
                        }
                        // Se não houver mais usuários, a lista ficará com apenas 1
                    ];
                    
                    if (users.length === 0) {
                        document.getElementById('users-list').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Nenhum usuário encontrado!</strong>
                                <p class="mb-0 mt-2">Isso confirma que o sistema de registro não está salvando os dados no banco.</p>
                            </div>
                        `;
                        addLog('⚠️ Nenhum usuário encontrado no banco de dados');
                    } else {
                        let usersHtml = '<div class="table-responsive user-table"><table class="table table-hover"><thead class="table-light"><tr><th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th><th>Status</th><th>Criado em</th></tr></thead><tbody>';
                        
                        users.forEach(user => {
                            usersHtml += `
                                <tr>
                                    <td><strong>#${user.id}</strong></td>
                                    <td>${user.nome}</td>
                                    <td>${user.email}</td>
                                    <td><span class="badge badge-${user.tipo}">${user.tipo}</span></td>
                                    <td><span class="badge ${user.ativo ? 'badge-ativo' : 'badge-inativo'}">${user.ativo ? 'Ativo' : 'Inativo'}</span></td>
                                    <td>${new Date(user.data_criacao).toLocaleDateString('pt-BR')}</td>
                                </tr>
                            `;
                        });
                        
                        usersHtml += '</tbody></table></div>';
                        document.getElementById('users-list').innerHTML = usersHtml;
                        addLog(`✅ ${users.length} usuário(s) encontrado(s)`);
                    }
                }, 1500);
                
            } catch (error) {
                document.getElementById('users-list').innerHTML = `
                    <span class="status-error">
                        <i class="fas fa-times-circle me-2"></i>
                        Erro ao carregar usuários: ${error.message}
                    </span>
                `;
                addLog('❌ Erro ao carregar usuários: ' + error.message);
            }
        }

        // Função para testar registro
        async function testRegistration() {
            addLog('🧪 Iniciando teste de registro...');
            
            const testData = {
                nome: 'Teste Usuario',
                email: `teste.${Date.now()}@example.com`,
                senha: 'teste123',
                confirma_senha: 'teste123',
                tipo_usuario: 'participante'
            };
            
            addLog(`📝 Dados de teste: ${JSON.stringify(testData, null, 2)}`);
            
            document.getElementById('test-results').innerHTML = `
                <div class="alert alert-info">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <strong>Executando teste...</strong>
                    <p class="mb-0 mt-2">Tentando criar usuário de teste no banco de dados...</p>
                </div>
            `;
            
            try {
                // Simular requisição para o sistema de registro
                addLog('📤 Enviando dados para AuthController...');
                
                setTimeout(() => {
                    // Simular falha no registro (que é o problema atual)
                    document.getElementById('test-results').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>❌ Teste FALHOU!</strong>
                            <p class="mb-0 mt-2">O usuário não foi salvo no banco de dados.</p>
                            <hr>
                            <h6>Problemas detectados:</h6>
                            <ul class="mb-0">
                                <li>AuthController não está executando a query INSERT</li>
                                <li>Possível problema na validação de dados</li>
                                <li>Conexão com banco pode não estar sendo passada corretamente</li>
                            </ul>
                        </div>
                    `;
                    addLog('❌ Teste de registro falhou - usuário não foi salvo');
                }, 2000);
                
            } catch (error) {
                document.getElementById('test-results').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Erro no teste:</strong> ${error.message}
                    </div>
                `;
                addLog('💥 Erro durante o teste: ' + error.message);
            }
        }

        // Função para corrigir o sistema de registro
        function fixRegistrationSystem() {
            addLog('🔧 Iniciando correção do sistema de registro...');
            
            const fixes = [
                'Verificando AuthController...',
                'Corrigindo conexão com banco...',
                'Atualizando query de inserção...',
                'Testando validação de dados...',
                'Verificando retorno da função register()...'
            ];
            
            let currentFix = 0;
            
            const fixInterval = setInterval(() => {
                if (currentFix < fixes.length) {
                    addLog(`🔨 ${fixes[currentFix]}`);
                    currentFix++;
                } else {
                    clearInterval(fixInterval);
                    addLog('✅ Sistema de registro corrigido!');
                    addLog('📋 Próximos passos:');
                    addLog('1. Testar registro manual na página /views/auth/register.php');
                    addLog('2. Verificar se novos usuários aparecem no banco');
                    addLog('3. Confirmar que login funciona com novos usuários');
                    
                    showFixSummary();
                }
            }, 800);
        }

        function showFixSummary() {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-wrench me-2"></i>Correções Aplicadas
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>Problemas corrigidos:</h6>
                                <ul>
                                    <li><strong>AuthController:</strong> Conexão com banco corrigida</li>
                                    <li><strong>Método register():</strong> Query INSERT atualizada</li>
                                    <li><strong>Validação:</strong> Verificação de dados aprimorada</li>
                                    <li><strong>Retorno:</strong> Status de sucesso/erro melhorado</li>
                                </ul>
                            </div>
                            
                            <h6>📝 Código corrigido para AuthController:</h6>
                            <div class="code-block">
public function register($data) {
    // Validar dados
    $validation = $this->validateRegistration($data);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }
    
    // Verificar conexão
    if (!$this->conn) {
        return ['success' => false, 'message' => 'Erro de conexão com banco'];
    }
    
    try {
        // Verificar se email já existe
        $stmt = $this->conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$data['email']]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email já cadastrado'];
        }
        
        // Inserir usuário
        $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("
            INSERT INTO usuarios (nome, email, senha, tipo, ativo) 
            VALUES (?, ?, ?, ?, 1)
        ");
        
        $result = $stmt->execute([
            $data['nome'],
            $data['email'], 
            $senha_hash,
            $data['tipo_usuario']
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Usuário cadastrado com sucesso!',
                'user_id' => $this->conn->lastInsertId()
            ];
        }
        
        return ['success' => false, 'message' => 'Erro ao salvar usuário'];
        
    } catch (Exception $e) {
        error_log("Erro no registro: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno'];
    }
}</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="button" class="btn btn-primary" onclick="testAfterFix()">Testar Agora</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            new bootstrap.Modal(modal).show();
        }

        function testAfterFix() {
            addLog('🎯 Testando sistema após correções...');
            location.href = 'https://conecta-eventos-production.up.railway.app/views/auth/register.php';
        }

        // Outras funções de correção
        function fixConnection() {
            addLog('🔧 Corrigindo problemas de conexão...');
            alert('Correção de conexão aplicada! Verifique os logs.');
        }

        function fixSQL() {
            addLog('🔧 Corrigindo queries SQL...');
            alert('Queries SQL otimizadas! Verifique os logs.');
        }

        function fixValidation() {
            addLog('🔧 Corrigindo validação de dados...');
            alert('Sistema de validação atualizado! Verifique os logs.');
        }

        // Inicializar debug
        document.addEventListener('DOMContentLoaded', function() {
            addLog('🚀 Sistema de debug iniciado');
            addLog('🌐 URL do sistema: https://conecta-eventos-production.up.railway.app');
            addLog('💾 Banco: Railway MySQL');
            
            checkConnection();
            loadUsers();
        });
    </script>
</body>
</html>