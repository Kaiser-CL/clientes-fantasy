<?php
// api/obtener_galeria.php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// Ajusta la ruta a tu conexión de BD según tu estructura
require_once dirname(__DIR__) . '/db_config.php';

$tipo = $_GET['tipo'] ?? ''; // 'paquetes' o 'extras'
$idEntidad = intval($_GET['id'] ?? 0);

if (!in_array($tipo, ['paquetes', 'extras']) || $idEntidad <= 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Parámetros inválidos. Se requiere tipo (paquetes|extras) e id mayor a 0.'
    ]);
    exit;
}

try {
    // CORRECCIÓN: Se consulta 'servicio_galeria' usando 'id_servicio' y sus nombres reales de columna
    $sql = "SELECT id_galeria, ruta_archivo, tipo_archivo, fecha_registro 
            FROM servicio_galeria 
            WHERE id_servicio = ? 
            ORDER BY id_galeria DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEntidad]);
    $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'tipo' => $tipo,
        'id_entidad' => $idEntidad,
        'total' => count($archivos),
        'data' => $archivos
    ], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al consultar la base de datos: ' . $e->getMessage()
    ]);
}
?>
