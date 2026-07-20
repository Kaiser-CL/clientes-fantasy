<?php

class Database {

    private $host = "gateway01.us-east-1.prod.aws.tidbcloud.com"; // Tu Host de TiDB Cloud
    private $port = "4000";
    private $db_name = "myfantasy"; // O el nombre de tu BD en TiDB
    private $username = "tu_usuario.root";
    private $password = "tu_password_tidb";

    public $conn;

    public function conectar() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_SSL_CA => true,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $e) {
            die(json_encode([
                "success" => false,
                "mensaje" => "Error de conexión: " . $e->getMessage()
            ]));
        }

        return $this->conn;
    }
}
