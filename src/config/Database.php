<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class Database
{
    private $driver;
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $ssl;
    private $charset;
    public $conn;

    public function __construct()
    {
        // Check environment - default to local if not specified
        $env = isset($_ENV['APP_ENV']) ? $_ENV['APP_ENV'] : 'development';
        $prefix = $env === 'production' ? 'PROD_DB_' : 'LOCAL_DB_';
        
        // Set database connection parameters based on environment
        $this->host     = $_ENV[$prefix . 'HOST'];
        $this->port     = $_ENV[$prefix . 'PORT'];
        $this->db_name  = $_ENV[$prefix . 'DATABASE'];
        $this->username = $_ENV[$prefix . 'USERNAME'];
        $this->password = $_ENV[$prefix . 'PASSWORD'];
        $this->driver   = $_ENV[$prefix . 'DRIVER'];
        $this->ssl      = $_ENV[$prefix . 'SSL'] === 'true';
        $this->charset  = $_ENV[$prefix . 'CHARSET'] ?? 'utf8mb4';
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            // Build DSN with port
            $dsn = "{$this->driver}:host={$this->host};port={$this->port};dbname={$this->db_name}";
            
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Add SSL options if required
            if ($this->ssl) {
                if ($this->driver === 'pgsql') {
                    // For PostgreSQL
                    // Use the connection string approach for SSL
                    $dsn .= ";sslmode=require";
                } else if ($this->driver === 'mysql') {
                    // For MySQL
                    $options[\PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/ca.pem'; // Update with your certificate path if needed
                    $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
                }
            }
            
            $this->conn = new \PDO($dsn, $this->username, $this->password, $options);
            
        } catch (\PDOException $exception) {
            // Log the error message
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}