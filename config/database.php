<?php

class Database {

    // Las credenciales se leen desde variables de entorno (configuradas en Render).
    // Valores por defecto como fallback para desarrollo local.
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;

    public $conn;

    public function __construct() {
        $this->host     = getenv('DB_HOST')     ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
        $this->port     = getenv('DB_PORT')     ?: '4000';
        $this->db_name  = getenv('DB_NAME')     ?: 'myfantasy';
        $this->username = getenv('DB_USER')     ?: 'NP7yaZ8j67LCyUS.root';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    public function conectar() {

        $this->conn = null;

        // Ruta al certificado CA de TiDB Cloud (incluido en el proyecto)
        $ssl_ca = __DIR__ . '/tidb_ca.pem';

        try {

            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // TiDB Cloud Serverless REQUIERE conexion SSL
            if (file_exists($ssl_ca)) {
                $options[PDO::MYSQL_ATTR_SSL_CA]     = $ssl_ca;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $e) {

            die(json_encode([
                "success" => false,
                "mensaje" => "Error de conexion: " . $e->getMessage()
            ]));
        }

        return $this->conn;
    }
}