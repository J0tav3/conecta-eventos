<?php
// Script para inicializar o banco no Railway
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Ler e executar o arquivo schema.sql
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Dividir por comandos SQL individuais
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($commands as $command) {
        if (!empty($command)) {
            $conn->exec($command);
        }
    }
    
    echo "Banco de dados inicializado com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro ao inicializar banco: " . $e->getMessage() . "\n";
    exit(1);
}
?>