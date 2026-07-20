<?php
$host = 'gateway01.us-east-1.prod.aws.tidbcloud.com'; 
$port = '4000';                                        
$db   = 'myfantasy';                                   
$user = 'NP7yaZ8j67LCyUS.root';                             
$pass = '5PPmozWdxCEXIjNR';                          

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    Pdo\Mysql::ATTR_SSL_CA       => '/etc/ssl/certs/ca-certificates.crt',
];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión a TiDB Cloud: " . $e->getMessage());
}