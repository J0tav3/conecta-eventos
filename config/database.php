<?php
// Configurações do banco de dados

// Configurações para Railway (produção)
if (isset($_ENV['DATABASE_URL'])) {
    $url = parse_url($_ENV['DATABASE_URL']);
    
    define('DB_HOST', $url['host']);
    define('DB_NAME', ltrim($url['path'], '/'));
    define('DB_USER', $url['user']);
    define('DB_PASS', $url['pass']);
    define('DB_PORT', $url['port'] ?? 3306);
    define('DB_CHARSET', 'utf8mb4');
} else {
    // Configurações locais
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'conecta_eventos');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', 3306);
    define('DB_CHARSET', 'utf8mb4');
}

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $port = DB_PORT ?? 3306;
    private $charset = DB_CHARSET;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Erro na conexão: " . $e->getMessage());
            throw new Exception("Erro na conexão com o banco de dados");
        }
        
        return $this->conn;
    }
}
?>