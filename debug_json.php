<?php
// ==========================================
// VERIFICAÇÃO DE ERROS JSON
// Local: debug_json.php
// ==========================================

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug de Erros JSON</h2>";

try {
    // 1. Verificar se o erro está relacionado ao JavaScript
    echo "<h3>1. Verificando JavaScript no Frontend</h3>";
    
    $pages_to_check = [
        '/views/auth/register.php',
        '/views/auth/login.php', 
        '/test_register.php',
        '/diagnosis.php'
    ];
    
    foreach ($pages_to_check as $page) {
        echo "<h4>Verificando: $page</h4>";
        
        // Simular verificação de resposta
        echo "<div style='background: #f8f9fa; padding: 1rem; border-left: 4px solid #007bff; margin: 1rem 0;'>";
        echo "<strong>Pontos a verificar:</strong><br>";
        echo "✓ Content-Type headers corretos<br>";
        echo "✓ Respostas JSON válidas<br>";
        echo "✓ Tratamento de erros JavaScript<br>";
        echo "✓ Encoding UTF-8<br>";
        echo "</div>";
    }
    
    // 2. Verificar APIs que retornam JSON
    echo "<h3>2. APIs que podem causar erro JSON</h3>";
    
    $potential_apis = [
        'AuthController->register()' => 'Pode retornar JSON em alguns casos',
        'EventController methods' => 'Possíveis retornos JSON',
        'AJAX requests' => 'Requisições assíncronas',
        'Form submissions' => 'Respostas de formulários'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 1rem 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Fonte</th><th>Descrição</th><th>Status</th></tr>";
    
    foreach ($potential_apis as $source => $desc) {
        echo "<tr>";
        echo "<td>$source</td>";
        echo "<td>$desc</td>";
        echo "<td style='color: #28a745;'>✅ Verificar</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Script de teste para capturar erros JSON
    echo "<h3>3. Script de Captura de Erros</h3>";
    echo "<div style='background: #1e1e1e; color: #00ff00; padding: 1rem; border-radius: 0.5rem; font-family: monospace;'>";
    echo "// Adicione este código JavaScript para capturar erros JSON:<br><br>";
    echo "window.addEventListener('error', function(e) {<br>";
    echo "&nbsp;&nbsp;if (e.message.includes('JSON.parse')) {<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;console.error('JSON Parse Error:', e);<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;console.error('URL:', window.location.href);<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;console.error('Stack:', e.error?.stack);<br>";
    echo "&nbsp;&nbsp;}<br>";
    echo "});<br>";
    echo "</div>";
    
    // 4. Verificações específicas
    echo "<h3>4. Verificações Específicas</h3>";
    
    // Verificar se existe algum echo ou print antes do JSON
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; margin: 1rem 0;'>";
    echo "<strong>⚠️ Possíveis causas do erro JSON:</strong><br>";
    echo "• Output antes de JSON (echo, print, whitespace)<br>";
    echo "• Headers incorretos (text/html em vez de application/json)<br>";
    echo "• Erro de PHP misturado com resposta JSON<br>";
    echo "• Encoding de caracteres (UTF-8)<br>";
    echo "• Resposta HTML quando esperado JSON<br>";
    echo "</div>";
    
    // 5. Teste simples de JSON
    echo "<h3>5. Teste de JSON Válido</h3>";
    
    $test_json = [
        'success' => true,
        'message' => 'Teste de JSON funcionando',
        'data' => [
            'timestamp' => time(),
            'system' => 'Conecta Eventos',
            'status' => 'operational'
        ]
    ];
    
    echo "<h4>JSON de teste:</h4>";
    echo "<pre style='background: #f8f9fa; padding: 1rem; border-radius: 0.5rem;'>";
    echo json_encode($test_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    
    // Verificar se o JSON é válido
    $json_string = json_encode($test_json);
    $decoded = json_decode($json_string, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<div style='color: #28a745;'>✅ JSON válido - Nenhum erro de encoding</div>";
    } else {
        echo "<div style='color: #dc3545;'>❌ Erro JSON: " . json_last_error_msg() . "</div>";
    }
    
    // 6. Recomendações
    echo "<h3>6. Recomendações para Resolver</h3>";
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 1rem; margin: 1rem 0;'>";
    echo "<strong>🔧 Passos para resolver:</strong><br><br>";
    echo "1. <strong>Verificar Network Tab:</strong> Abra DevTools > Network e veja qual requisição está falhando<br>";
    echo "2. <strong>Verificar Response:</strong> Clique na requisição e veja se a resposta é HTML em vez de JSON<br>";
    echo "3. <strong>Verificar Headers:</strong> Content-Type deve ser application/json<br>";
    echo "4. <strong>Verificar Console:</strong> Procure por outros erros JavaScript<br>";
    echo "5. <strong>Verificar PHP Errors:</strong> Veja se há erros de PHP sendo exibidos<br>";
    echo "</div>";
    
    // 7. Links para debug
    echo "<h3>7. Links de Debug</h3>";
    echo "<div style='display: flex; gap: 1rem; flex-wrap: wrap;'>";
    echo "<a href='https://conecta-eventos-production.up.railway.app/views/auth/register.php' target='_blank' style='background: #007bff; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 0.25rem;'>Testar Registro</a>";
    echo "<a href='https://conecta-eventos-production.up.railway.app/views/auth/login.php' target='_blank' style='background: #28a745; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 0.25rem;'>Testar Login</a>";
    echo "<a href='https://conecta-eventos-production.up.railway.app/test_register.php' target='_blank' style='background: #ffc107; color: black; padding: 0.5rem 1rem; text-decoration: none; border-radius: 0.25rem;'>Debug Registro</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 1rem; margin: 1rem 0;'>";
    echo "<strong>❌ Erro durante debug:</strong><br>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>📧 Próximos passos:</strong> Após executar este debug, teste as funcionalidades no navegador com DevTools aberto para capturar o erro JSON específico.</p>";
?>