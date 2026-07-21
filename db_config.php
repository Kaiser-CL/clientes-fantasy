<?php
$host = 'gateway01.us-east-1.prod.aws.tidbcloud.com';
$port = 4000;
$user = 'NP7yaZ8j67LCyUS.root'; // Reemplaza por tu usuario real de TiDB si no es este
$password = '5PPmozWdxCEXIjNR'; // Reemplaza por tu contraseña real
$dbname = 'myfantasy'; // Reemplaza por el nombre de tu base de datos si es diferente

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_SSL_CA       => '/etc/ssl/certs/ca-certificates.crt',
];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

/**
 * Función para registrar cambios en la bitácora desde PHP
 */
function registrar_bitacora($pdo, $tabla, $id_registro, $accion, $datos_anteriores = null, $datos_nuevos = null) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $id_usuario = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? null;
    
    $sql = "INSERT INTO historial_cambios (id_usuario, tabla_afectada, id_registro, accion, datos_anteriores, datos_nuevos) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_usuario,
            $tabla,
            $id_registro,
            $accion,
            $datos_anteriores ? json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE) : null,
            $datos_nuevos ? json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE) : null
        ]);
    } catch (\PDOException $e) {
        // Ignorar errores de bitácora para no interrumpir el flujo principal
        error_log("Error guardando bitácora: " . $e->getMessage());
    }
}
?>